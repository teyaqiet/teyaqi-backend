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

            /*
             * Explicit deployment executables.
             */
            'binaries' => [
                'composer' => config(
                    'operations.deployments.binaries.composer',
                    'composer'
                ),

                'node' => config(
                    'operations.deployments.binaries.node',
                    'node'
                ),

                /*
                 * Kept for backwards compatibility/reference.
                 *
                 * NPM deployment commands should NOT execute npm.cmd
                 * directly on Windows.
                 */
                'npm' => config(
                    'operations.deployments.binaries.npm',
                    'npm'
                ),

                /*
                 * Direct npm CLI entry point.
                 *
                 * Instead of:
                 *
                 * npm.cmd -> cmd.exe -> node.exe
                 *
                 * we use:
                 *
                 * node.exe -> npm-cli.js
                 */
                'npm_cli' => config(
                    'operations.deployments.binaries.npm_cli',
                    'C:/Program Files/nodejs/node_modules/npm/bin/npm-cli.js'
                ),

                'php' => config(
                    'operations.deployments.binaries.php',
                    'php'
                ),
            ],

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
                $config['binaries']['composer'],
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
        $nodeRequired =
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
            );

        $checks['node'] = $this->executableCheck(
            'Node.js',
            [
                $config['binaries']['node'],
                '--version',
            ],
            $config['path'],
            $nodeRequired
        );

        /*
         * NPM.
         *
         * IMPORTANT:
         *
         * Do NOT execute npm.cmd here.
         *
         * On Windows under Apache/PHP, npm.cmd can cause:
         *
         * npm.cmd -> cmd.exe -> node.exe
         *
         * to fail during Node crypto initialization.
         *
         * We therefore execute:
         *
         * node.exe -> npm-cli.js
         */
        $checks['npm'] = $this->executableCheck(
            'NPM',
            [
                $config['binaries']['node'],
                $config['binaries']['npm_cli'],
                '--version',
            ],
            $config['path'],
            $nodeRequired
        );

        /*
         * PHP.
         */
        $checks['php'] = $this->executableCheck(
            'PHP',
            [
                $config['binaries']['php'],
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
                $config['path'] .
                DIRECTORY_SEPARATOR .
                'composer.lock'
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
                $config['path'] .
                DIRECTORY_SEPARATOR .
                'package-lock.json'
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
            $preflight = $this->preflight();

            if (! $preflight['ready']) {
                throw new RuntimeException(
                    'Deployment preflight checks failed.'
                );
            }

            $environment = $data['environment']
                ?? $config['environment'];

            $branch = $data['branch']
                ?? $config['branch'];

            $branch = trim($branch);

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

                    'binaries' => [
                        'composer' =>
                            $config['binaries']['composer'],

                        'node' =>
                            $config['binaries']['node'],

                        'npm' =>
                            $config['binaries']['npm'],

                        'npm_cli' =>
                            $config['binaries']['npm_cli'],

                        'php' =>
                            $config['binaries']['php'],
                    ],

                    'pipeline' => $this->pipelineOverview(
                        $config['pipeline']
                    ),
                ],
            ]);

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
     */
    public function execute(
        OperationDeployment $deployment
    ): OperationDeployment {
        $config = $this->config();

        $lockSeconds = max(
            $config['timeout'] + 60,
            120
        );

        $lock = cache()->lock(
            'teyaqi:operations:deployment',
            $lockSeconds
        );

        if (! $lock->block($lockSeconds)) {
            throw new RuntimeException(
                'Unable to acquire the deployment lock.'
            );
        }

        try {
            $deployment = OperationDeployment::find(
                $deployment->id
            );

            if (! $deployment) {
                throw new RuntimeException(
                    'Deployment not found.'
                );
            }

            if ($deployment->status !== 'pending') {
                return $deployment->fresh();
            }

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

            $startedAt = now();

            $deployment->update([
                'status' => 'running',

                'started_at' => $startedAt,

                'completed_at' => null,

                'duration_seconds' => null,

                'error' => null,
            ]);

            $deployment = $deployment->fresh();

            $this->auditDeployment(
                $deployment,
                'deployment.started',
                'Deployment pipeline started.',
                'success'
            );

            $output = [];

            try {
                /*
                 * Git operations.
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
                 * Record actual deployed commit.
                 */
                $deployedCommit = $this->runCommand(
                    [
                        'git',
                        'rev-parse',
                        'HEAD',
                    ],
                    $config['path'],
                    30
                );

                if (! $deployedCommit->successful()) {
                    throw new RuntimeException(
                        'Unable to determine deployed Git commit: ' .
                        trim(
                            $deployedCommit->errorOutput()
                        )
                    );
                }

                $deployedCommitHash = trim(
                    $deployedCommit->output()
                );

                $deployedMessage = $this->runCommand(
                    [
                        'git',
                        'log',
                        '-1',
                        '--pretty=%s',
                    ],
                    $config['path'],
                    30
                );

                $deployment->update([
                    'commit_hash' => $deployedCommitHash ?: null,

                    'commit_message' => $deployedMessage->successful()
                        ? trim($deployedMessage->output())
                        : null,
                ]);

                $output[] =
                    '[' . now()->format('Y-m-d H:i:s') . '] Deployment commit';

                $output[] =
                    'Commit: ' .
                    ($deployedCommitHash ?: 'unknown');

                $output[] =
                    'Message: ' .
                    (
                        $deployedMessage->successful()
                            ? trim($deployedMessage->output())
                            : 'unknown'
                    );

                $deployment->update([
                    'output' => implode(
                        PHP_EOL . PHP_EOL,
                        $output
                    ),
                ]);

                /*
                 * Composer Pipeline Stage.
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
                 * NPM Pipeline Stage.
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
                 * Assets Build Stage.
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
                        'Build frontend assets',
                        'build',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Build frontend assets',
                        'Build pipeline stage is disabled.'
                    );
                }

                /*
                 * Database Migrations Stage.
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
                        'Migrations pipeline stage is disabled.'
                    );
                }

                /*
                 * Cache & Framework Optimization Stage.
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
                        'Optimize framework caches',
                        'optimize',
                        $config
                    );
                } else {
                    $this->recordSkippedStep(
                        $output,
                        $deployment,
                        'Optimize framework caches',
                        'Optimization stage is disabled.'
                    );
                }

                /*
                 * Queue Restart Stage.
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
                 * Health Check Stage.
                 */
                if (
                    (bool) data_get(
                        $config,
                        'pipeline.health_check.enabled',
                        true
                    )
                ) {
                    $this->runConfiguredPipelineStep(
                        $output,
                        $deployment,
                        'Run health checks',
                        'health_check',
                        $config
                    );
                }

                $completedAt = now();

                $duration =
                    $completedAt->diffInSeconds(
                        $startedAt
                    );

                $deployment->update([
                    'status' => 'completed',

                    'completed_at' => $completedAt,

                    'duration_seconds' => $duration,
                ]);

                $this->auditDeployment(
                    $deployment,
                    'deployment.completed',
                    'Deployment pipeline completed successfully.',
                    'success'
                );
            } catch (Throwable $e) {
                $failedAt = now();

                $duration =
                    $failedAt->diffInSeconds(
                        $startedAt
                    );

                $deployment->update([
                    'status' => 'failed',

                    'completed_at' => $failedAt,

                    'duration_seconds' => $duration,

                    'error' => $e->getMessage(),
                ]);

                $this->auditDeployment(
                    $deployment,
                    'deployment.failed',
                    'Deployment pipeline failed: ' .
                        $e->getMessage(),
                    'error'
                );

                throw $e;
            }

            return $deployment->fresh();
        } finally {
            $lock->release();
        }
    }

    /**
     * Validate the branch name.
     */
    protected function validateBranch(
        string $branch
    ): void {
        if (
            empty($branch) ||
            ! preg_match(
                '/^[a-zA-Z0-9_\-\.\/]+$/',
                $branch
            )
        ) {
            throw new RuntimeException(
                "Invalid branch name format: [{$branch}]"
            );
        }
    }

    /**
     * Check executable status for preflight.
     */
    protected function executableCheck(
        string $name,
        array $command,
        string $path,
        bool $required = true
    ): array {
        if (! $required) {
            return [
                'status' => 'skipped',

                'message' =>
                    "{$name} check skipped (not required).",
            ];
        }

        $result = $this->runCommand(
            $command,
            $path,
            15
        );

        return [
            'status' => $result->successful()
                ? 'healthy'
                : 'failed',

            'message' => $result->successful()
                ? "{$name} is installed and executable."
                : "{$name} check failed or executable not found.",

            'output' => trim(
                $result->output()
            ),

            'error' => trim(
                $result->errorOutput()
            ),
        ];
    }

    /**
     * Helper to run configured steps with custom fallback commands.
     */
    protected function runConfiguredPipelineStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        string $key,
        array $config
    ): void {
        $command = data_get(
            $config,
            "pipeline.{$key}.command"
        );

        if (
            ! is_array($command) ||
            empty($command)
        ) {
            $binaries = $config['binaries'];

            $command = match ($key) {
                'composer' => [
                    $binaries['composer'],
                    'install',
                    '--no-interaction',
                    '--prefer-dist',
                    '--optimize-autoloader',
                ],

                'npm' => [
                    'npm',
                    'ci',
                ],

                'build' => [
                    'npm',
                    'run',
                    'build',
                ],

                'migrations' => [
                    $binaries['php'],
                    'artisan',
                    'migrate',
                    '--force',
                ],

                'optimize' => [
                    $binaries['php'],
                    'artisan',
                    'optimize',
                ],

                'queue_restart' => [
                    $binaries['php'],
                    'artisan',
                    'queue:restart',
                ],

                'health_check' => [
                    $binaries['php'],
                    'artisan',
                    'about',
                ],

                default => throw new RuntimeException(
                    "No command configured for step: {$key}"
                ),
            };
        }

        /*
         * Resolve the configured command to the actual executable.
         *
         * NPM/build:
         *
         *     npm ci
         *
         * becomes:
         *
         *     node.exe npm-cli.js ci
         *
         * This avoids npm.cmd -> cmd.exe on Windows.
         */
        $command = $this->resolvePipelineCommand(
            $key,
            $command,
            $config
        );

        $timeout = (int) data_get(
            $config,
            "pipeline.{$key}.timeout",
            $config['timeout']
        );

        $this->runDeploymentStep(
            $output,
            $deployment,
            $stepName,
            $command,
            $config['path'],
            $timeout
        );
    }

    /**
     * Resolve a pipeline command to the configured executable.
     */
    protected function resolvePipelineCommand(
        string $key,
        array $command,
        array $config
    ): array {
        $binaries = $config['binaries'];

        return match ($key) {
            /*
             * Composer.
             */
            'composer' => [
                $binaries['composer'],
                ...array_slice($command, 1),
            ],

            /*
             * NPM dependencies.
             *
             * npm ci
             *
             * becomes:
             *
             * node.exe npm-cli.js ci
             */
            'npm' => [
                $binaries['node'],
                $binaries['npm_cli'],
                ...array_slice($command, 1),
            ],

            /*
             * Frontend build.
             *
             * npm run build
             *
             * becomes:
             *
             * node.exe npm-cli.js run build
             */
            'build' => [
                $binaries['node'],
                $binaries['npm_cli'],
                ...array_slice($command, 1),
            ],

            /*
             * Laravel commands.
             */
            'migrations' => [
                $binaries['php'],
                ...array_slice($command, 1),
            ],

            'optimize' => [
                $binaries['php'],
                ...array_slice($command, 1),
            ],

            'queue_restart' => [
                $binaries['php'],
                ...array_slice($command, 1),
            ],

            'health_check' => [
                $binaries['php'],
                ...array_slice($command, 1),
            ],

            default => $command,
        };
    }

    /**
     * Record a skipped stage in the deployment log.
     */
    protected function recordSkippedStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        string $reason
    ): void {
        $timestamp = now()->format(
            'Y-m-d H:i:s'
        );

        $output[] =
            "[{$timestamp}] Skipped step: {$stepName}";

        $output[] = $reason;

        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);
    }

    /**
     * Run a single deployment step.
     */
    protected function runDeploymentStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        array $command,
        string $path,
        int $timeout = 600
    ): void {
        $timestamp = now()->format(
            'Y-m-d H:i:s'
        );

        $output[] =
            "[{$timestamp}] Starting step: {$stepName}";

        /*
         * Record the actual command for easier debugging.
         */
        $output[] =
            'Command: ' .
            implode(
                ' ',
                array_map(
                    static fn ($value) => (string) $value,
                    $command
                )
            );

        $result = $this->runCommand(
            $command,
            $path,
            $timeout
        );

        if ($result->output()) {
            $output[] = trim(
                $result->output()
            );
        }

        if ($result->errorOutput()) {
            $output[] = trim(
                $result->errorOutput()
            );
        }

        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);

        if (! $result->successful()) {
            throw new RuntimeException(
                "Deployment step [{$stepName}] failed: " .
                trim(
                    $result->errorOutput()
                    ?: $result->output()
                )
            );
        }

        $output[] =
            "[{$timestamp}] Completed step: {$stepName}";

        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);
    }

    /**
     * Build a controlled environment for deployment processes.
     *
     * The Apache/PHP process can have a very different environment
     * from the CLI. Deployment tools therefore receive an explicit,
     * predictable environment.
     */
    protected function processEnvironment(): array
    {
        $config = $this->config();

        /*
         * Dedicated deployment storage.
         */
        $operationsStorage = storage_path(
            'framework' .
            DIRECTORY_SEPARATOR .
            'operations'
        );

        $tempDirectory =
            $operationsStorage .
            DIRECTORY_SEPARATOR .
            'tmp';

        $npmCacheDirectory =
            $operationsStorage .
            DIRECTORY_SEPARATOR .
            'npm-cache';

        $homeDirectory =
            $operationsStorage .
            DIRECTORY_SEPARATOR .
            'home';

        /*
         * Ensure directories exist.
         */
        foreach ([
            $operationsStorage,
            $tempDirectory,
            $npmCacheDirectory,
            $homeDirectory,
        ] as $directory) {
            if (! is_dir($directory)) {
                if (
                    ! mkdir(
                        $directory,
                        0777,
                        true
                    )
                    &&
                    ! is_dir($directory)
                ) {
                    throw new RuntimeException(
                        "Unable to create deployment directory: {$directory}"
                    );
                }
            }
        }

        /*
         * Add directories containing explicitly configured
         * deployment executables.
         */
        $directories = [];

        foreach ([
            $config['binaries']['php'],
            $config['binaries']['node'],
            $config['binaries']['npm'],
            $config['binaries']['npm_cli'],
            $config['binaries']['composer'],
        ] as $binary) {
            $directory = $this->binaryDirectory(
                $binary
            );

            if ($directory !== null) {
                $directories[] = $directory;
            }
        }

        $directories = array_values(
            array_unique(
                array_filter(
                    $directories
                )
            )
        );

        /*
         * Preserve the existing system PATH.
         */
        $existingPath = getenv('PATH') ?: '';

        $pathParts = array_merge(
            $directories,
            $this->splitWindowsPath(
                $existingPath
            )
        );

        /*
         * Remove duplicate PATH entries.
         */
        $seen = [];

        $pathParts = array_values(
            array_filter(
                $pathParts,
                function ($value) use (&$seen) {
                    $normalized = strtolower(
                        rtrim(
                            trim($value),
                            '\\/'
                        )
                    );

                    if ($normalized === '') {
                        return false;
                    }

                    if (isset($seen[$normalized])) {
                        return false;
                    }

                    $seen[$normalized] = true;

                    return true;
                }
            )
        );

        $separator =
            DIRECTORY_SEPARATOR === '\\'
                ? ';'
                : ':';

        return [
            /*
             * Explicit executable PATH.
             */
            'PATH' => implode(
                $separator,
                $pathParts
            ),

            /*
             * Node module lookup.
             */
            'NODE_PATH' =>
                $this->binaryDirectory(
                    $config['binaries']['node']
                ) ?? '',

            /*
             * Preserve NODE_OPTIONS if explicitly configured,
             * but do not invent any.
             */
            'NODE_OPTIONS' => '--openssl-legacy-provider',

            /*
             * Explicit PHP executable.
             */
            'PHP_BINARY' =>
                $config['binaries']['php'],

            /*
             * Writable temporary directory.
             */
            'TEMP' => $tempDirectory,

            'TMP' => $tempDirectory,

            'TMPDIR' => $tempDirectory,

            /*
             * Dedicated npm cache.
             */
            'npm_config_cache' =>
                $npmCacheDirectory,

            /*
             * Dedicated npm home.
             */
            'HOME' => $homeDirectory,

            'USERPROFILE' => $homeDirectory,
        ];
    }

    /**
     * Get the directory containing a binary.
     */
    protected function binaryDirectory(
        string $binary
    ): ?string {
        $binary = trim($binary);

        if ($binary === '') {
            return null;
        }

        /*
         * Commands such as "git" don't have an explicit directory.
         */
        if (
            ! str_contains($binary, '\\') &&
            ! str_contains($binary, '/')
        ) {
            return null;
        }

        $directory = dirname(
            str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $binary
            )
        );

        if (
            $directory === '.' ||
            $directory === ''
        ) {
            return null;
        }

        return $directory;
    }

    /**
     * Split PATH correctly for Windows/Linux.
     *
     * IMPORTANT:
     * Windows uses ":" inside drive letters such as C:\.
     * Therefore Windows PATH must only be split on ";".
     */
    protected function splitWindowsPath(
        string $path
    ): array {
        if ($path === '') {
            return [];
        }

        return DIRECTORY_SEPARATOR === '\\'
            ? (
                preg_split(
                    '/;/',
                    $path,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: []
            )
            : (
                preg_split(
                    '/:/',
                    $path,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ) ?: []
            );
    }

    /**
     * Execute a process using the controlled deployment environment.
     */
    protected function runCommand(
        array $command,
        string $path,
        int $timeout = 600
    ) {
        return Process::path($path)
            ->env(
                $this->processEnvironment()
            )
            ->timeout($timeout)
            ->run($command);
    }

    /**
     * Audit deployment event.
     */
    protected function auditDeployment(
        OperationDeployment $deployment,
        string $event,
        string $message,
        string $level = 'info'
    ): void {
        Log::channel('daily')->log(
            $level,
            "Deployment #{$deployment->id} [{$event}]: {$message}"
        );
    }
}