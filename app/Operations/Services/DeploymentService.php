<?php

namespace App\Operations\Services;

use App\Jobs\ExecuteDeploymentJob;
use App\Models\OperationDeployment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class DeploymentService
{
    /**
     * Get deployment configuration.
     */
    public function config(): array
    {
        return [
            'enabled' => (bool) config(
                'operations.deployments.enabled',
                false
            ),

            'environment' => config(
                'operations.deployments.environment',
                'staging'
            ),

            'branch' => config(
                'operations.deployments.branch',
                'main'
            ),

            'path' => config(
                'operations.deployments.path',
                base_path()
            ) ?: base_path(),

            'timeout' => (int) config(
                'operations.deployments.timeout',
                600
            ),

            'remote' => config(
                'operations.deployments.remote',
                'origin'
            ),

            'pipeline' => config(
                'operations.deployments.pipeline',
                []
            ),
        ];
    }

    /**
     * Return deployment overview information.
     */
    public function overview(): array
    {
        $config = $this->config();

        $latest = OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->latest('id')
            ->first();

        $running = OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->whereIn('status', [
                'pending',
                'running',
            ])
            ->latest('id')
            ->first();

        return [
            'enabled' => $config['enabled'],

            'environment' => $config['environment'],

            'branch' => $config['branch'],

            'path' => $config['path'],

            'timeout' => $config['timeout'],

            'remote' => $config['remote'],

            'pipeline' => $this->pipelineOverview(
                $config['pipeline']
            ),

            'latest_deployment' => $latest,

            'running_deployment' => $running,

            'statistics' => [
                'total' => OperationDeployment::count(),

                'successful' => OperationDeployment::where(
                    'status',
                    'completed'
                )->count(),

                'failed' => OperationDeployment::where(
                    'status',
                    'failed'
                )->count(),

                'running' => OperationDeployment::whereIn(
                    'status',
                    [
                        'pending',
                        'running',
                    ]
                )->count(),
            ],
        ];
    }

    /**
     * Return enabled/disabled pipeline stages.
     */
    protected function pipelineOverview(
        array $pipeline
    ): array {
        return [
            'composer' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'composer.enabled',
                    true
                ),
            ],

            'npm' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'npm.enabled',
                    true
                ),
            ],

            'build' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'build.enabled',
                    true
                ),
            ],

            'migrations' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'migrations.enabled',
                    true
                ),
            ],

            'optimize' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'optimize.enabled',
                    true
                ),
            ],

            'queue_restart' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'queue_restart.enabled',
                    true
                ),
            ],

            'health_check' => [
                'enabled' => (bool) data_get(
                    $pipeline,
                    'health_check.enabled',
                    true
                ),
            ],
        ];
    }

    /**
     * List deployment history.
     */
    public function deployments(
        int $perPage = 20,
        ?string $status = null,
        ?string $environment = null
    ) {
        return OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->when(
                $status,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $environment,
                fn ($query) => $query->where(
                    'environment',
                    $environment
                )
            )
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Find a deployment.
     */
    public function find(int $id): ?OperationDeployment
    {
        return OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->find($id);
    }

    /**
     * Check whether a deployment can be started.
     */
    public function preflight(): array
    {
        $config = $this->config();

        $pipeline = $config['pipeline'];

        $checks = [];

        /*
         * Deployment feature.
         */
        $checks['deployment_enabled'] = [
            'status' => $config['enabled']
                ? 'healthy'
                : 'failed',

            'message' => $config['enabled']
                ? 'Deployment system is enabled.'
                : 'Deployment system is disabled.',
        ];

        /*
         * Deployment directory.
         */
        $directoryExists = is_dir($config['path']);

        $checks['directory'] = [
            'status' => $directoryExists
                ? 'healthy'
                : 'failed',

            'message' => $directoryExists
                ? 'Deployment directory exists.'
                : 'Deployment directory does not exist.',
        ];

        /*
         * Git repository.
         */
        if ($directoryExists) {
            $gitCheck = $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    '--is-inside-work-tree',
                ],
                $config['path'],
                30
            );

            $checks['git_repository'] = [
                'status' => $gitCheck->successful()
                    ? 'healthy'
                    : 'failed',

                'message' => $gitCheck->successful()
                    ? 'Git repository detected.'
                    : 'Git repository was not detected.',

                'output' => trim(
                    $gitCheck->output()
                ),

                'error' => trim(
                    $gitCheck->errorOutput()
                ),
            ];
        } else {
            $checks['git_repository'] = [
                'status' => 'failed',

                'message' =>
                    'Cannot check Git because the deployment directory does not exist.',

                'output' => '',

                'error' => '',
            ];
        }

        /*
         * Git executable.
         */
        $checks['git'] = $this->executableCheck(
            'Git',
            [
                'git',
                '--version',
            ],
            $config['path']
        );

        /*
         * Git remote.
         */
        if ($directoryExists) {
            $remoteCheck = $this->runCommand(
                [
                    'git',
                    'remote',
                    'get-url',
                    $config['remote'],
                ],
                $config['path'],
                30
            );

            $checks['git_remote'] = [
                'status' => $remoteCheck->successful()
                    ? 'healthy'
                    : 'failed',

                'message' => $remoteCheck->successful()
                    ? 'Git remote is configured.'
                    : 'Configured Git remote was not found.',

                'remote' => $config['remote'],

                'url' => trim(
                    $remoteCheck->output()
                ),

                'error' => trim(
                    $remoteCheck->errorOutput()
                ),
            ];
        } else {
            $checks['git_remote'] = [
                'status' => 'failed',

                'message' =>
                    'Cannot check Git remote.',

                'remote' => $config['remote'],

                'url' => null,

                'error' => '',
            ];
        }

        /*
         * Composer.
         */
        $checks['composer'] = $this->executableCheck(
            'Composer',
            [
                'composer',
                '--version',
            ],
            $config['path'],
            (bool) data_get(
                $pipeline,
                'composer.enabled',
                true
            )
        );

        /*
         * Node.js.
         */
        $checks['node'] = $this->executableCheck(
            'Node.js',
            [
                'node',
                '--version',
            ],
            $config['path'],
            (bool) data_get(
                $pipeline,
                'npm.enabled',
                true
            )
        );

        /*
         * NPM.
         */
        $checks['npm'] = $this->executableCheck(
            'NPM',
            [
                'npm',
                '--version',
            ],
            $config['path'],
            (
                (bool) data_get(
                    $pipeline,
                    'npm.enabled',
                    true
                )
                ||
                (bool) data_get(
                    $pipeline,
                    'build.enabled',
                    true
                )
            )
        );

        /*
         * PHP.
         */
        $checks['php'] = $this->executableCheck(
            'PHP',
            [
                'php',
                '--version',
            ],
            $config['path']
        );

        /*
         * Package files.
         */
        if (
            (bool) data_get(
                $pipeline,
                'composer.enabled',
                true
            )
        ) {
            $composerLockExists = is_file(
                $config['path'] . DIRECTORY_SEPARATOR . 'composer.lock'
            );

            $checks['composer_lock'] = [
                'status' => $composerLockExists
                    ? 'healthy'
                    : 'failed',

                'message' => $composerLockExists
                    ? 'composer.lock exists.'
                    : 'composer.lock was not found.',
            ];
        } else {
            $checks['composer_lock'] = [
                'status' => 'skipped',

                'message' =>
                    'Composer dependency installation is disabled.',
            ];
        }

        /*
         * NPM lock file.
         */
        if (
            (bool) data_get(
                $pipeline,
                'npm.enabled',
                true
            )
            ||
            (bool) data_get(
                $pipeline,
                'build.enabled',
                true
            )
        ) {
            $packageLockExists = is_file(
                $config['path'] . DIRECTORY_SEPARATOR . 'package-lock.json'
            );

            $checks['npm_lock'] = [
                'status' => $packageLockExists
                    ? 'healthy'
                    : 'failed',

                'message' => $packageLockExists
                    ? 'package-lock.json exists.'
                    : 'package-lock.json was not found.',
            ];
        } else {
            $checks['npm_lock'] = [
                'status' => 'skipped',

                'message' =>
                    'NPM dependency installation and build are disabled.',
            ];
        }

        /*
         * Another deployment active?
         */
        $activeDeployment = OperationDeployment::query()
            ->whereIn(
                'status',
                [
                    'pending',
                    'running',
                ]
            )
            ->exists();

        $checks['no_active_deployment'] = [
            'status' => ! $activeDeployment
                ? 'healthy'
                : 'failed',

            'message' => ! $activeDeployment
                ? 'No deployment is currently running.'
                : 'Another deployment is already running.',
        ];

        /*
         * Overall result.
         *
         * "skipped" is intentionally considered healthy because
         * disabled pipeline stages are valid configuration.
         */
        $healthy = collect($checks)
            ->every(
                fn ($check) =>
                    in_array(
                        $check['status'],
                        [
                            'healthy',
                            'skipped',
                        ],
                        true
                    )
            );

        return [
            'status' => $healthy
                ? 'healthy'
                : 'failed',

            'ready' => $healthy,

            'checks' => $checks,
        ];
    }

    /**
     * Create a pending deployment and queue execution.
     */
    public function createDeployment(
        array $data = []
    ): OperationDeployment {
        $config = $this->config();

        if (! $config['enabled']) {
            throw new RuntimeException(
                'Deployment system is disabled.'
            );
        }

        /*
         * Prevent concurrent deployment creation.
         */
        $lock = cache()->lock(
            'teyaqi:operations:deployment:create',
            30
        );

        if (! $lock->get()) {
            throw new RuntimeException(
                'Another deployment is currently being created. Please try again.'
            );
        }

        try {
            /*
             * Make sure the system is ready.
             */
            $preflight = $this->preflight();

            if (! $preflight['ready']) {
                throw new RuntimeException(
                    'Deployment preflight checks failed.'
                );
            }

            /*
             * Environment.
             */
            $environment = $data['environment']
                ?? $config['environment'];

            /*
             * Branch.
             */
            $branch = $data['branch']
                ?? $config['branch'];

            $branch = trim($branch);

            /*
             * Validate deployment branch.
             */
            $this->validateBranch($branch);

            /*
             * Get current local commit.
             */
            $currentCommit = $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    'HEAD',
                ],
                $config['path'],
                30
            );

            if (! $currentCommit->successful()) {
                throw new RuntimeException(
                    'Unable to determine current Git commit: ' .
                    trim(
                        $currentCommit->errorOutput()
                    )
                );
            }

            $commitHash = trim(
                $currentCommit->output()
            );

            /*
             * Get current commit message.
             */
            $currentMessage = $this->runCommand(
                [
                    'git',
                    'log',
                    '-1',
                    '--pretty=%s',
                ],
                $config['path'],
                30
            );

            $commitMessage = $currentMessage->successful()
                ? trim($currentMessage->output())
                : null;

            /*
             * Create deployment record.
             */
            $deployment = OperationDeployment::create([
                'environment' => $environment,

                'branch' => $branch,

                'commit_hash' => $commitHash ?: null,

                'commit_message' => $commitMessage ?: null,

                'status' => 'pending',

                'triggered_by' => auth('admin')->id(),

                'metadata' => [
                    'remote' => $config['remote'],

                    'path' => $config['path'],

                    'pipeline' => $this->pipelineOverview(
                        $config['pipeline']
                    ),
                ],
            ]);

            /*
             * Queue deployment execution.
             */
            ExecuteDeploymentJob::dispatch(
                $deployment->id
            );

            return $deployment->fresh();
        } finally {
            $lock->release();
        }
    }

    /**
     * Execute a deployment.
     *
     * The deployment lock is held for the entire pipeline.
     */
    public function execute(
        OperationDeployment $deployment
    ): OperationDeployment {
        $config = $this->config();

        /*
         * Deployment timeout.
         *
         * The lock must survive the complete deployment.
         */
        $lockSeconds = max(
            $config['timeout'] + 60,
            120
        );

        $lock = cache()->lock(
            'teyaqi:operations:deployment',
            $lockSeconds
        );

        /*
         * Wait for another deployment instead of immediately
         * failing the queued job.
         */
        if (! $lock->block($lockSeconds)) {
            throw new RuntimeException(
                'Unable to acquire the deployment lock.'
            );
        }

        try {
            /*
             * Always reload the latest database state.
             */
            $deployment = OperationDeployment::find(
                $deployment->id
            );

            if (! $deployment) {
                throw new RuntimeException(
                    'Deployment not found.'
                );
            }

            /*
             * Prevent duplicate execution.
             */
            if ($deployment->status !== 'pending') {
                return $deployment->fresh();
            }

            /*
             * Make sure no other deployment is active.
             *
             * Because this code is protected by the same global
             * deployment lock, this check is now serialized.
             */
            $anotherDeploymentRunning = OperationDeployment::query()
                ->whereIn(
                    'status',
                    [
                        'pending',
                        'running',
                    ]
                )
                ->where(
                    'id',
                    '!=',
                    $deployment->id
                )
                ->exists();

            if ($anotherDeploymentRunning) {
                throw new RuntimeException(
                    'Another deployment is already pending or running.'
                );
            }

            /*
             * Mark deployment as running.
             */
            $startedAt = now();

            $deployment->update([
                'status' => 'running',

                'started_at' => $startedAt,

                'completed_at' => null,

                'duration_seconds' => null,

                'error' => null,
            ]);

            $deployment = $deployment->fresh();

            /*
             * Audit execution start.
             */
            $this->auditDeployment(
                $deployment,
                'deployment.started',
                'Deployment pipeline started.',
                'success'
            );

            $output = [];

            try {
                /*
                 |--------------------------------------------------------------------------
                 | Git Pipeline
                 |--------------------------------------------------------------------------
                 */

                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Verify Git repository',
                    [
                        'git',
                        'rev-parse',
                        '--is-inside-work-tree',
                    ],
                    $config['path'],
                    30
                );

                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Fetch Git changes',
                    [
                        'git',
                        'fetch',
                        '--all',
                        '--prune',
                    ],
                    $config['path'],
                    $config['timeout']
                );

                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Checkout deployment branch',
                    [
                        'git',
                        'checkout',
                        $deployment->branch,
                    ],
                    $config['path'],
                    30
                );

                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Pull latest code',
                    [
                        'git',
                        'pull',
                        '--ff-only',
                        $config['remote'],
                        $deployment->branch,
                    ],
                    $config['path'],
                    $config['timeout']
                );

                /*
                 |--------------------------------------------------------------------------
                 | Composer
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.composer.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Install Composer dependencies',
                        'composer',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Install Composer dependencies',
                        'Composer pipeline stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | NPM
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.npm.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Install NPM dependencies',
                        'npm',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Install NPM dependencies',
                        'NPM pipeline stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Next.js build
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.build.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Build frontend application',
                        'build',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Build frontend application',
                        'Frontend build stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Database migrations
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.migrations.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Run database migrations',
                        'migrations',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Run database migrations',
                        'Database migration stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Laravel optimization
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.optimize.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Optimize Laravel application',
                        'optimize',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Optimize Laravel application',
                        'Laravel optimization stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Queue restart
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.queue_restart.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Restart queue workers',
                        'queue_restart',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Restart queue workers',
                        'Queue restart stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Final health check
                 |--------------------------------------------------------------------------
                 */

                if (
                    (bool) data_get(
                        $config,
                        'pipeline.health_check.enabled',
                        true
                    )
                ) {
                    $this->runHealthCheck(
                        $output,
                        $deployment,
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Application health check',
                        'Health check stage is disabled.'
                    );
                }

                /*
                 |--------------------------------------------------------------------------
                 | Deployment completed
                 |--------------------------------------------------------------------------
                 */

                $commit = $this->runCommand(
                    [
                        'git',
                        'rev-parse',
                        'HEAD',
                    ],
                    $config['path'],
                    30
                );

                if ($commit->successful()) {
                    $deployment->update([
                        'commit_hash' => trim(
                            $commit->output()
                        ),
                    ]);
                }

                $message = $this->runCommand(
                    [
                        'git',
                        'log',
                        '-1',
                        '--pretty=%s',
                    ],
                    $config['path'],
                    30
                );

                if ($message->successful()) {
                    $deployment->update([
                        'commit_message' => trim(
                            $message->output()
                        ),
                    ]);
                }

                $completedAt = now();

                $deployment->update([
                    'status' => 'completed',

                    'completed_at' => $completedAt,

                    'duration_seconds' => $startedAt
                        ->diffInSeconds($completedAt),

                    'output' => implode(
                        PHP_EOL . PHP_EOL,
                        $output
                    ),

                    'error' => null,
                ]);

                $deployment = $deployment->fresh();

                /*
                 * Audit successful deployment.
                 */
                $this->auditDeployment(
                    $deployment,
                    'deployment.completed',
                    'Deployment pipeline completed successfully.',
                    'success'
                );

                return $deployment;

            } catch (Throwable $exception) {
                /*
                 * Deployment failed.
                 */
                $completedAt = now();

                $deployment->update([
                    'status' => 'failed',

                    'completed_at' => $completedAt,

                    'duration_seconds' => $startedAt
                        ->diffInSeconds($completedAt),

                    'output' => implode(
                        PHP_EOL . PHP_EOL,
                        $output
                    ),

                    'error' => $exception->getMessage(),
                ]);

                $deployment = $deployment->fresh();

                /*
                 * Audit failure.
                 */
                $this->auditDeployment(
                    $deployment,
                    'deployment.failed',
                    'Deployment pipeline failed.',
                    'failed',
                    [
                        'error' =>
                            $exception->getMessage(),
                    ]
                );

                Log::error(
                    'Operations deployment failed.',
                    [
                        'deployment_id' =>
                            $deployment->id,

                        'environment' =>
                            $deployment->environment,

                        'branch' =>
                            $deployment->branch,

                        'exception' =>
                            $exception,
                    ]
                );

                throw $exception;
            }
        } finally {
            /*
             * Always release the deployment lock.
             */
            $lock->release();
        }
    }

    /**
     * Execute a configured pipeline stage.
     */
    protected function runConfiguredPipelineStep(
        array &$output,
        OperationDeployment $deployment,
        string $label,
        string $stage,
        array $config
    ): void {
        $pipeline = $config['pipeline'];

        $command = data_get(
            $pipeline,
            "{$stage}.command"
        );

        $timeout = (int) data_get(
            $pipeline,
            "{$stage}.timeout",
            $config['timeout']
        );

        if (! is_array($command) || empty($command)) {
            throw new RuntimeException(
                "Deployment pipeline stage [{$stage}] has no valid command configured."
            );
        }

        $this->runDeploymentStep(
            $output,
            $deployment,
            $label,
            $command,
            $config['path'],
            $timeout
        );
    }

    /**
     * Run the final application health check.
     */
    protected function runHealthCheck(
        array &$output,
        OperationDeployment $deployment,
        array $config
    ): void {
        $label = 'Application health check';

        $output[] =
            '[' .
            now()->format('Y-m-d H:i:s') .
            '] ' .
            $label;

        $started = microtime(true);

        try {
            $health = app(
                DatabaseService::class
            )->overview();

            $latency = round(
                (microtime(true) - $started) * 1000,
                2
            );

            if (
                ($health['status'] ?? null)
                !== 'healthy'
            ) {
                $output[] =
                    'Database health check failed.';

                $output[] = json_encode(
                    $health,
                    JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_SLASHES
                );

                $deployment->update([
                    'output' => implode(
                        PHP_EOL . PHP_EOL,
                        $output
                    ),
                ]);

                throw new RuntimeException(
                    'Application health check failed.'
                );
            }

            $output[] =
                'Database health check passed.';

            $output[] =
                'Latency: ' .
                $latency .
                ' ms';

            $output[] =
                'Database: ' .
                ($health['database'] ?? 'unknown');

            $output[] =
                'Server version: ' .
                ($health['server_version'] ?? 'unknown');

            $deployment->update([
                'output' => implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
            ]);

        } catch (Throwable $exception) {
            $deployment->update([
                'output' => implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
            ]);

            throw new RuntimeException(
                "{$label} failed: " .
                $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    /**
     * Record a skipped deployment step.
     */
    protected function recordSkippedStep(
        array &$output,
        OperationDeployment $deployment,
        string $label,
        string $reason
    ): void {
        $output[] =
            '[' .
            now()->format('Y-m-d H:i:s') .
            '] ' .
            $label .
            ' [SKIPPED]';

        $output[] = $reason;

        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);
    }

    /**
     * Check whether an executable is available.
     */
    protected function executableCheck(
        string $name,
        array $command,
        string $path,
        bool $enabled = true
    ): array {
        if (! $enabled) {
            return [
                'status' => 'skipped',

                'message' =>
                    "{$name} check is disabled.",

                'output' => '',

                'error' => '',
            ];
        }

        $result = $this->runCommand(
            $command,
            $path,
            30
        );

        return [
            'status' => $result->successful()
                ? 'healthy'
                : 'failed',

            'message' => $result->successful()
                ? trim($result->output())
                : "{$name} is unavailable.",

            'output' => trim(
                $result->output()
            ),

            'error' => trim(
                $result->errorOutput()
            ),
        ];
    }

    /**
     * Validate a deployment branch.
     */
    protected function validateBranch(
        string $branch
    ): void {
        $branch = trim($branch);

        if ($branch === '') {
            throw new RuntimeException(
                'Deployment branch cannot be empty.'
            );
        }

        /*
         * Git option-like value.
         */
        if (str_starts_with($branch, '-')) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }

        /*
         * Whitespace/control characters.
         */
        if (
            preg_match(
                '/[\s\x00-\x1F\x7F]/',
                $branch
            )
        ) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }

        /*
         * Normal Git branch characters only.
         */
        if (
            ! preg_match(
                '/^[A-Za-z0-9._\/-]+$/',
                $branch
            )
        ) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }

        /*
         * Git does not allow consecutive dots.
         */
        if (str_contains($branch, '..')) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }

        /*
         * Reject Git's special @{ syntax.
         */
        if (str_contains($branch, '@{')) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }
    }

    /**
     * Write a deployment audit event.
     */
    protected function auditDeployment(
        OperationDeployment $deployment,
        string $action,
        string $description,
        string $status,
        array $metadata = []
    ): void {
        app(OperationAuditService::class)->log(
            action: $action,

            module: 'deployments',

            status: $status,

            description: $description,

            metadata: array_merge(
                [
                    'deployment_id' =>
                        $deployment->id,

                    'environment' =>
                        $deployment->environment,

                    'branch' =>
                        $deployment->branch,

                    'commit_hash' =>
                        $deployment->commit_hash,
                ],
                $metadata
            ),
        );
    }

    /**
     * Run one predefined deployment step.
     */
    protected function runDeploymentStep(
        array &$output,
        OperationDeployment $deployment,
        string $label,
        array $command,
        string $path,
        int $timeout
    ): void {
        /*
         * Record step start.
         */
        $output[] =
            '[' .
            now()->format('Y-m-d H:i:s') .
            '] ' .
            $label;

        /*
         * Execute command.
         */
        $result = $this->runCommand(
            $command,
            $path,
            $timeout
        );

        /*
         * Capture stdout.
         */
        if ($result->output()) {
            $output[] = trim(
                $result->output()
            );
        }

        /*
         * Capture stderr.
         */
        if ($result->errorOutput()) {
            $output[] = trim(
                $result->errorOutput()
            );
        }

        /*
         * Persist progress immediately.
         */
        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);

        /*
         * Fail the deployment if the command failed.
         */
        if (! $result->successful()) {
            throw new RuntimeException(
                "{$label} failed: " .
                trim(
                    $result->errorOutput()
                    ?: $result->output()
                )
            );
        }
    }

    /**
     * Execute a controlled process.
     */
    protected function runCommand(
        array $command,
        string $path,
        int $timeout
    ) {
        return Process::path($path)
            ->timeout($timeout)
            ->run($command);
    }
}