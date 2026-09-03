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
     * Pipeline stages used by the deployment system.
     */
    protected array $pipelineStages = [
        'git',
        'composer',
        'npm',
        'build',
        'migrations',
        'optimize',
        'queue_restart',
        'health_check',
    ];

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

        /*
         * If a deployment is pending/running, make sure the frontend
         * receives a normalized runtime pipeline structure.
         */
        if ($running) {
            $running = $this->normalizeDeploymentPipeline(
                $running
            );
        }

        if ($latest) {
            $latest = $this->normalizeDeploymentPipeline(
                $latest
            );
        }

        return [
            'enabled' => $config['enabled'],

            'environment' => $config['environment'],

            'branch' => $config['branch'],

            'path' => $config['path'],

            'timeout' => $config['timeout'],

            'remote' => $config['remote'],

            /*
             * Configuration-level pipeline.
             */
            'pipeline' => $this->pipelineOverview(
                $config['pipeline']
            ),

            'latest_deployment' => $latest,

            /*
             * Runtime deployment.
             */
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
            'git' => [
                'enabled' => true,
            ],

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
        $deployment = OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->find($id);

        if (! $deployment) {
            return null;
        }

        return $this->normalizeDeploymentPipeline(
            $deployment
        );
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
        $directoryExists = is_dir(
            $config['path']
        );

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
         * Composer lock.
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
         * NPM lock.
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

        /*
         * Prevent two deployment creation requests
         * from happening simultaneously.
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
             * Check whether another deployment is already
             * pending or running.
             */
            $existingDeployment = OperationDeployment::query()
                ->whereIn(
                    'status',
                    [
                        'pending',
                        'running',
                    ]
                )
                ->latest('id')
                ->first();

            if ($existingDeployment) {
                throw new RuntimeException(
                    "Deployment #{$existingDeployment->id} is already pending or running."
                );
            }

            /*
             * Run preflight checks.
             */
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

            $branch = trim(
                (string) $branch
            );

            $this->validateBranch(
                $branch
            );

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
             * Create deployment.
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

                    /*
                     * IMPORTANT:
                     *
                     * This is now a runtime pipeline structure.
                     */
                    'pipeline' =>
                        $this->initializePipelineMetadata(
                            [],
                            $config['pipeline']
                        ),
                ],
            ]);

            /*
             * Queue actual deployment execution.
             */
            ExecuteDeploymentJob::dispatch(
                $deployment->id
            );

            return $this->normalizeDeploymentPipeline(
                $deployment->fresh()
            );
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

        /*
         * Keep the lock slightly longer than the deployment
         * timeout.
         */
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

            /*
             * Only pending deployments can be executed.
             */
            if ($deployment->status !== 'pending') {
                return $this->normalizeDeploymentPipeline(
                    $deployment->fresh()
                );
            }

            /*
             * Check for another deployment.
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

            $startedAt = now();

            /*
             * Initialize the runtime pipeline BEFORE running
             * the first command.
             *
             * This is what allows the frontend to immediately
             * see all stages.
             */
            $metadata = $this->initializePipelineMetadata(
                $deployment->metadata ?? [],
                $config['pipeline']
            );

            $deployment->update([
                'status' => 'running',

                'started_at' => $startedAt,

                'completed_at' => null,

                'duration_seconds' => null,

                'output' => null,

                'error' => null,

                'metadata' => $metadata,
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
                 * =====================================================
                 * GIT STAGE
                 * =====================================================
                 *
                 * Git contains multiple commands but is displayed
                 * as one pipeline stage.
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
                    30,
                    'git',
                    false
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
                    $config['timeout'],
                    'git',
                    false
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
                    30,
                    'git',
                    false
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
                    $config['timeout'],
                    'git',
                    true
                );

                /*
                 * Get actual deployed commit.
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

                /*
                 * Get deployed commit message.
                 */
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
                    'commit_hash' =>
                        $deployedCommitHash ?: null,

                    'commit_message' =>
                        $deployedMessage->successful()
                            ? trim(
                                $deployedMessage->output()
                            )
                            : null,
                ]);

                $output[] =
                    '[' .
                    now()->format('Y-m-d H:i:s') .
                    '] Deployment commit';

                $output[] =
                    'Commit: ' .
                    (
                        $deployedCommitHash
                            ?: 'unknown'
                    );

                $output[] =
                    'Message: ' .
                    (
                        $deployedMessage->successful()
                            ? trim(
                                $deployedMessage->output()
                            )
                            : 'unknown'
                    );

                $this->saveOutput(
                    $deployment,
                    $output
                );

                /*
                 * =====================================================
                 * COMPOSER
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'composer',
                    stepName: 'Install Composer dependencies',
                    config: $config
                );

                /*
                 * =====================================================
                 * NPM
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'npm',
                    stepName: 'Install NPM dependencies',
                    config: $config
                );

                /*
                 * =====================================================
                 * FRONTEND BUILD
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'build',
                    stepName: 'Build frontend assets',
                    config: $config
                );

                /*
                 * =====================================================
                 * DATABASE MIGRATIONS
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'migrations',
                    stepName: 'Run database migrations',
                    config: $config
                );

                /*
                 * =====================================================
                 * OPTIMIZE
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'optimize',
                    stepName: 'Optimize framework caches',
                    config: $config
                );

                /*
                 * =====================================================
                 * QUEUE RESTART
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'queue_restart',
                    stepName: 'Restart queue workers',
                    config: $config
                );

                /*
                 * =====================================================
                 * HEALTH CHECK
                 * =====================================================
                 */
                $this->runOrSkipConfiguredStage(
                    output: $output,
                    deployment: $deployment,
                    key: 'health_check',
                    stepName: 'Run health checks',
                    config: $config
                );

                /*
                 * =====================================================
                 * SUCCESS
                 * =====================================================
                 */

                $completedAt = now();

                $duration = max(
                    0,
                    $completedAt->getTimestamp() -
                    $startedAt->getTimestamp()
                );

                /*
                 * Make absolutely sure all enabled stages are
                 * completed before marking deployment successful.
                 */
                $deployment = $deployment->fresh();

                $deployment->update([
                    'status' => 'completed',

                    'completed_at' => $completedAt,

                    'duration_seconds' => $duration,

                    'error' => null,
                ]);

                $this->auditDeployment(
                    $deployment,
                    'deployment.completed',
                    'Deployment pipeline completed successfully.',
                    'success'
                );
            } catch (Throwable $e) {
                /*
                 * Try to identify the currently running stage and
                 * mark it as failed.
                 */
                $deployment = $deployment->fresh();

                $this->markCurrentStageFailed(
                    $deployment,
                    $e->getMessage()
                );

                $failedAt = now();

                $duration = max(
                    0,
                    $failedAt->getTimestamp() -
                    $startedAt->getTimestamp()
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

            return $this->normalizeDeploymentPipeline(
                $deployment->fresh()
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Run a configured stage or mark it as skipped.
     */
    protected function runOrSkipConfiguredStage(
        array &$output,
        OperationDeployment $deployment,
        string $key,
        string $stepName,
        array $config
    ): void {
        $enabled = (bool) data_get(
            $config,
            "pipeline.{$key}.enabled",
            true
        );

        if (! $enabled) {
            $this->recordSkippedStep(
                $output,
                $deployment,
                $key,
                $stepName,
                "{$stepName} is disabled."
            );

            return;
        }

        $this->runConfiguredPipelineStep(
            $output,
            $deployment,
            $stepName,
            $key,
            $config
        );
    }

    /**
     * Run a configured pipeline step.
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
         * Resolve configured command.
         */
        $command = $this->resolvePipelineCommand(
            $key,
            $command,
            $config
        );

        /*
         * Step-specific timeout.
         */
        $timeout = (int) data_get(
            $config,
            "pipeline.{$key}.timeout",
            $config['timeout']
        );

        /*
         * Execute the stage.
         */
        $this->runDeploymentStep(
            $output,
            $deployment,
            $stepName,
            $command,
            $config['path'],
            $timeout,
            $key,
            true
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
             * NPM:
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
             * Build:
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
     * Run one deployment command.
     *
     * The important part here is that the pipeline status is updated
     * BEFORE the command starts and AFTER it completes.
     *
     * That allows the frontend to poll the deployment record and
     * display real runtime progress.
     */
    protected function runDeploymentStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        array $command,
        string $path,
        int $timeout = 600,
        ?string $pipelineKey = null,
        bool $completeStage = true
    ): void {
        /*
         * Mark stage as running BEFORE executing the command.
         */
        if ($pipelineKey) {
            $this->updatePipelineStatus(
                $deployment,
                $pipelineKey,
                'running',
                $stepName
            );
        }

        $timestamp = now()->format(
            'Y-m-d H:i:s'
        );

        /*
         * Make the command readable in the deployment log.
         */
        $commandString = implode(
            ' ',
            array_map(
                static fn ($value) => (string) $value,
                $command
            )
        );

        $output[] =
            "[{$timestamp}] Starting step: {$stepName}";

        $output[] =
            "Command: {$commandString}";

        /*
         * Save immediately so the UI can see the stage.
         */
        $this->saveOutput(
            $deployment,
            $output
        );

        try {
            /*
             * Execute the actual process.
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
             * Save process output.
             */
            $this->saveOutput(
                $deployment,
                $output
            );

            /*
             * Command failed.
             */
            if (! $result->successful()) {
                $errorMessage = trim(
                    $result->errorOutput()
                    ?: $result->output()
                );

                if ($errorMessage === '') {
                    $errorMessage =
                        "Process exited with code {$result->exitCode()}.";
                }

                if ($pipelineKey) {
                    $this->updatePipelineStatus(
                        $deployment,
                        $pipelineKey,
                        'failed',
                        "{$stepName} failed."
                    );
                }

                throw new RuntimeException(
                    "Deployment step [{$stepName}] failed: " .
                    $errorMessage
                );
            }

            /*
             * Command succeeded.
             */
            $completedTimestamp = now()->format(
                'Y-m-d H:i:s'
            );

            $output[] =
                "[{$completedTimestamp}] Completed step: {$stepName}";

            $this->saveOutput(
                $deployment,
                $output
            );

            /*
             * Only mark the stage completed when requested.
             *
             * Git uses several commands but should appear as one
             * stage in the UI.
             */
            if (
                $pipelineKey &&
                $completeStage
            ) {
                $this->updatePipelineStatus(
                    $deployment,
                    $pipelineKey,
                    'completed',
                    "{$stepName} completed successfully."
                );
            }
        } catch (Throwable $e) {
            /*
             * Make sure the stage is marked failed.
             */
            if ($pipelineKey) {
                $this->updatePipelineStatus(
                    $deployment,
                    $pipelineKey,
                    'failed',
                    $e->getMessage()
                );
            }

            /*
             * Make sure output is persisted.
             */
            $this->saveOutput(
                $deployment,
                $output
            );

            throw $e;
        }
    }

    /**
     * Record a skipped pipeline stage.
     */
    protected function recordSkippedStep(
        array &$output,
        OperationDeployment $deployment,
        string $key,
        string $stepName,
        string $reason
    ): void {
        $timestamp = now()->format(
            'Y-m-d H:i:s'
        );

        /*
         * Mark runtime stage as skipped.
         */
        $this->updatePipelineStatus(
            $deployment,
            $key,
            'skipped',
            $reason
        );

        $output[] =
            "[{$timestamp}] Skipped step: {$stepName}";

        $output[] =
            $reason;

        $this->saveOutput(
            $deployment,
            $output
        );
    }

    /**
     * Initialize the runtime pipeline metadata.
     *
     * Example:
     *
     * pipeline:
     *   git:
     *     enabled: true
     *     status: pending
     *
     *   composer:
     *     enabled: true
     *     status: pending
     */
    protected function initializePipelineMetadata(
        array $metadata,
        array $pipelineConfig
    ): array {
        $pipeline = [];

        /*
         * Git is always enabled because every deployment
         * needs Git operations.
         */
        $pipeline['git'] = [
            'enabled' => true,

            'status' => 'pending',

            'message' => null,

            'started_at' => null,

            'completed_at' => null,

            'updated_at' =>
                now()->toIso8601String(),
        ];

        /*
         * Configurable stages.
         */
        foreach ([
            'composer',
            'npm',
            'build',
            'migrations',
            'optimize',
            'queue_restart',
            'health_check',
        ] as $key) {
            $enabled = (bool) data_get(
                $pipelineConfig,
                "{$key}.enabled",
                true
            );

            $pipeline[$key] = [
                'enabled' => $enabled,

                'status' => $enabled
                    ? 'pending'
                    : 'skipped',

                'message' => $enabled
                    ? null
                    : ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $key
                        )
                    ) . ' is disabled.',

                'started_at' => null,

                'completed_at' => null,

                'updated_at' =>
                    now()->toIso8601String(),
            ];
        }

        $metadata['pipeline'] = $pipeline;

        return $metadata;
    }

    /**
     * Update one runtime pipeline stage.
     */
    protected function updatePipelineStatus(
        OperationDeployment $deployment,
        string $key,
        string $status,
        ?string $message = null
    ): void {
        /*
         * Always work with the latest database record.
         *
         * This is important because the deployment is being
         * polled by the frontend while the worker is executing.
         */
        $deployment = $deployment->fresh();

        if (! $deployment) {
            return;
        }

        $metadata = $deployment->metadata ?? [];

        $pipeline = $metadata['pipeline'] ?? [];

        /*
         * Preserve existing stage information.
         */
        $stage = is_array(
            $pipeline[$key] ?? null
        )
            ? $pipeline[$key]
            : [];

        /*
         * Preserve enabled state.
         */
        $stage['enabled'] = (bool) (
            $stage['enabled'] ?? true
        );

        /*
         * Runtime status.
         */
        $stage['status'] = $status;

        /*
         * Current update time.
         */
        $stage['updated_at'] =
            now()->toIso8601String();

        /*
         * Running timestamp.
         */
        if (
            $status === 'running' &&
            empty($stage['started_at'])
        ) {
            $stage['started_at'] =
                now()->toIso8601String();
        }

        /*
         * Completed timestamp.
         */
        if (
            in_array(
                $status,
                [
                    'completed',
                    'failed',
                    'skipped',
                ],
                true
            )
        ) {
            $stage['completed_at'] =
                now()->toIso8601String();
        }

        /*
         * Store message.
         */
        if ($message !== null) {
            $stage['message'] = $message;
        }

        $pipeline[$key] = $stage;

        $metadata['pipeline'] = $pipeline;

        /*
         * Save immediately.
         */
        $deployment->update([
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark the currently running stage as failed.
     *
     * This is a safety net for unexpected exceptions.
     */
    protected function markCurrentStageFailed(
        OperationDeployment $deployment,
        string $message
    ): void {
        $metadata = $deployment->metadata ?? [];

        $pipeline = $metadata['pipeline'] ?? [];

        foreach ($pipeline as $key => $stage) {
            if (
                ! is_array($stage)
            ) {
                continue;
            }

            if (
                ($stage['status'] ?? null) === 'running'
            ) {
                $this->updatePipelineStatus(
                    $deployment,
                    $key,
                    'failed',
                    $message
                );

                return;
            }
        }
    }

    /**
     * Normalize a deployment's pipeline structure.
     *
     * This also makes older deployment records compatible with
     * the new UI.
     */
    protected function normalizeDeploymentPipeline(
        OperationDeployment $deployment
    ): OperationDeployment {
        $metadata = $deployment->metadata ?? [];

        $pipeline = $metadata['pipeline'] ?? [];

        /*
         * Older deployments may have only:
         *
         * composer:
         *   enabled: true
         *
         * Convert those to:
         *
         * composer:
         *   enabled: true
         *   status: pending
         */
        foreach ($this->pipelineStages as $key) {
            if (
                ! isset($pipeline[$key]) ||
                ! is_array($pipeline[$key])
            ) {
                $pipeline[$key] = [
                    'enabled' => $key === 'git'
                        ? true
                        : (bool) data_get(
                            $this->config(),
                            "pipeline.{$key}.enabled",
                            true
                        ),

                    'status' => 'pending',

                    'message' => null,

                    'started_at' => null,

                    'completed_at' => null,

                    'updated_at' =>
                        now()->toIso8601String(),
                ];

                continue;
            }

            /*
             * Older records.
             */
            if (
                ! isset(
                    $pipeline[$key]['status']
                )
            ) {
                $pipeline[$key]['status'] =
                    'pending';
            }

            if (
                ! array_key_exists(
                    'message',
                    $pipeline[$key]
                )
            ) {
                $pipeline[$key]['message'] =
                    null;
            }

            if (
                ! array_key_exists(
                    'started_at',
                    $pipeline[$key]
                )
            ) {
                $pipeline[$key]['started_at'] =
                    null;
            }

            if (
                ! array_key_exists(
                    'completed_at',
                    $pipeline[$key]
                )
            ) {
                $pipeline[$key]['completed_at'] =
                    null;
            }
        }

        /*
         * Put normalized pipeline back onto the model.
         *
         * We don't save here because this method is also used
         * by read-only API requests.
         */
        $metadata['pipeline'] = $pipeline;

        $deployment->setAttribute(
            'metadata',
            $metadata
        );

        return $deployment;
    }

    /**
     * Save deployment output.
     */
    protected function saveOutput(
        OperationDeployment $deployment,
        array $output
    ): void {
        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);
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
     * Build a controlled environment for deployment processes.
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
         * Preserve existing PATH.
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
            'PATH' => implode(
                $separator,
                $pathParts
            ),

            'NODE_PATH' =>
                $this->binaryDirectory(
                    $config['binaries']['node']
                ) ?? '',

            /*
             * Preserve the Node compatibility setting that
             * was previously used for your Windows environment.
             */
            'NODE_OPTIONS' =>
                '--openssl-legacy-provider',

            'PHP_BINARY' =>
                $config['binaries']['php'],

            'TEMP' =>
                $tempDirectory,

            'TMP' =>
                $tempDirectory,

            'TMPDIR' =>
                $tempDirectory,

            'npm_config_cache' =>
                $npmCacheDirectory,

            'HOME' =>
                $homeDirectory,

            'USERPROFILE' =>
                $homeDirectory,
        ];
    }

    /**
     * Get the directory containing a binary.
     */
    protected function binaryDirectory(
        string $binary
    ): ?string {
        $binary = trim(
            $binary
        );

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
     * Split PATH correctly.
     *
     * Windows:
     * ;
     *
     * Linux:
     * :
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
        $level = match (strtolower($level)) {
            'success' => 'info',
            'failed' => 'error',
            'error' => 'error',
            'warning' => 'warning',
            'notice' => 'notice',
            'debug' => 'debug',
            'critical' => 'critical',
            'alert' => 'alert',
            'emergency' => 'emergency',
            default => 'info',
        };

        Log::channel('daily')->log(
            $level,
            "Deployment #{$deployment->id} [{$event}]: {$message}"
        );
    }
}