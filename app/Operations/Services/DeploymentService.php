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

            'binaries' => [
                'composer' => config(
                    'operations.deployments.binaries.composer',
                    'composer'
                ),

                'node' => config(
                    'operations.deployments.binaries.node',
                    'node'
                ),

                'npm' => config(
                    'operations.deployments.binaries.npm',
                    'npm'
                ),

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
        return OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->find($id);
    }

    /**
     * Get successful deployments that are valid rollback targets.
     */
    public function rollbackTargets(
        int $limit = 20,
        ?string $environment = null
    ) {
        return OperationDeployment::query()
            ->with('adminUser:id,name,email')
            ->where('status', 'completed')
            ->whereNotNull('commit_hash')
            ->when(
                $environment,
                fn ($query) => $query->where(
                    'environment',
                    $environment
                )
            )
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Determine whether a deployment can be rolled back to.
     */
    public function canRollback(
        OperationDeployment $deployment
    ): bool {
        return $deployment->status === 'completed'
            && !empty($deployment->commit_hash);
    }

    /**
     * Create a rollback deployment.
     *
     * This creates a NEW deployment record. The original
     * deployment is never modified.
     */
    public function createRollback(
        OperationDeployment $targetDeployment,
        ?string $reason = null
    ): OperationDeployment {
        $config = $this->config();

        if (! $config['enabled']) {
            throw new RuntimeException(
                'Deployment system is disabled.'
            );
        }

        /*
         * Target must be a successful deployment.
         */
        if (! $this->canRollback($targetDeployment)) {
            throw new RuntimeException(
                'This deployment cannot be used as a rollback target.'
            );
        }

        /*
         * Make sure the target belongs to the configured
         * deployment environment.
         */
        if (
            $targetDeployment->environment !==
            $config['environment']
        ) {
            throw new RuntimeException(
                'The selected deployment belongs to a different environment.'
            );
        }

        /*
         * Prevent two rollback/deployment creation requests
         * from being created simultaneously.
         */
        $creationLock = cache()->lock(
            'teyaqi:operations:deployment:create',
            30
        );

        if (! $creationLock->get()) {
            throw new RuntimeException(
                'Another deployment is currently being created. Please try again.'
            );
        }

        try {
            /*
             * Check the deployment lock.
             *
             * This is an immediate UX-level protection.
             * The queued job still acquires the final lock.
             */
            $deploymentLockService =
                app(DeploymentLockService::class);

            $existingLock =
                $deploymentLockService->current(
                    $targetDeployment->environment
                );

            if ($existingLock) {
                throw new RuntimeException(
                    "Deployment #{$existingLock->deployment_id} is already running in {$targetDeployment->environment}."
                );
            }

            /*
             * Verify target commit exists locally/remotely.
             */
            $commitCheck = $this->runCommand(
                [
                    'git',
                    'cat-file',
                    '-e',
                    $targetDeployment->commit_hash . '^{commit}',
                ],
                $config['path'],
                30
            );

            if (! $commitCheck->successful()) {
                /*
                 * Try fetching the remote before giving up.
                 */
                $fetch = $this->runCommand(
                    [
                        'git',
                        'fetch',
                        '--all',
                        '--prune',
                    ],
                    $config['path'],
                    $config['timeout']
                );

                if (! $fetch->successful()) {
                    throw new RuntimeException(
                        'Rollback target commit is not available and Git fetch failed: ' .
                        trim(
                            $fetch->errorOutput()
                        )
                    );
                }

                $commitCheck = $this->runCommand(
                    [
                        'git',
                        'cat-file',
                        '-e',
                        $targetDeployment->commit_hash . '^{commit}',
                    ],
                    $config['path'],
                    30
                );

                if (! $commitCheck->successful()) {
                    throw new RuntimeException(
                        "Rollback target commit [{$targetDeployment->commit_hash}] could not be found."
                    );
                }
            }

            /*
             * Determine the currently deployed commit.
             */
            $currentCommitResult = $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    'HEAD',
                ],
                $config['path'],
                30
            );

            if (! $currentCommitResult->successful()) {
                throw new RuntimeException(
                    'Unable to determine the current Git commit.'
                );
            }

            $currentCommit = trim(
                $currentCommitResult->output()
            );

            /*
             * Don't create a pointless rollback.
             */
            if (
                $currentCommit ===
                $targetDeployment->commit_hash
            ) {
                throw new RuntimeException(
                    'The application is already running the selected commit.'
                );
            }

            /*
             * Get target commit message.
             */
            $targetMessageResult = $this->runCommand(
                [
                    'git',
                    'log',
                    '-1',
                    '--pretty=%s',
                    $targetDeployment->commit_hash,
                ],
                $config['path'],
                30
            );

            $targetCommitMessage =
                $targetMessageResult->successful()
                    ? trim(
                        $targetMessageResult->output()
                    )
                    : $targetDeployment->commit_message;

            /*
             * Create the rollback deployment.
             *
             * We initially keep commit_hash as the target
             * commit because that is the version this operation
             * is intended to deploy.
             */
            $deployment = OperationDeployment::create([
                'environment' =>
                    $targetDeployment->environment,

                'branch' =>
                    $targetDeployment->branch
                    ?: $config['branch'],

                'commit_hash' =>
                    $targetDeployment->commit_hash,

                'commit_message' =>
                    $targetCommitMessage,

                'status' => 'pending',

                'triggered_by' =>
                    auth('admin')->id(),

                'metadata' => [
                    'remote' =>
                        $config['remote'],

                    'path' =>
                        $config['path'],

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

                    'pipeline' =>
                        $this->pipelineOverview(
                            $config['pipeline']
                        ),

                    /*
                     * Rollback information.
                     */
                    'rollback' => [
                        'is_rollback' => true,

                        'source_deployment_id' =>
                            $targetDeployment->id,

                        'from_commit' =>
                            $currentCommit,

                        'to_commit' =>
                            $targetDeployment->commit_hash,

                        'from_commit_message' =>
                            $this->getCommitMessage(
                                $config['path'],
                                $currentCommit
                            ),

                        'to_commit_message' =>
                            $targetCommitMessage,

                        'reason' =>
                            $reason
                                ? trim($reason)
                                : null,

                        'created_at' =>
                            now()->toIso8601String(),
                    ],
                ],
            ]);

            /*
             * Queue the same execution job.
             *
             * ExecuteDeploymentJob will determine whether this
             * is a normal deployment or rollback.
             */
            ExecuteDeploymentJob::dispatch(
                $deployment->id
            );

            return $deployment->fresh();

        } finally {
            $creationLock->release();
        }
    }

    /**
     * Execute a deployment.
     */
    public function execute(
        OperationDeployment $deployment
    ): OperationDeployment {
        /*
         * Rollbacks use a dedicated execution path.
         */
        if ($deployment->isRollback()) {
            return $this->executeRollback(
                $deployment
            );
        }

        return $this->executeStandardDeployment(
            $deployment
        );
    }

    /**
     * Execute a standard deployment.
     *
     * This contains the existing deployment behavior.
     */
    protected function executeStandardDeployment(
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

            $anotherDeploymentRunning =
                OperationDeployment::query()
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

            $metadata =
                $this->initializePipelineMetadata(
                    $deployment->metadata ?? [],
                    $config['pipeline']
                );

            $deployment->update([
                'status' => 'running',

                'started_at' => $startedAt,

                'completed_at' => null,

                'duration_seconds' => null,

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

                $deployedCommit =
                    $this->runCommand(
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

                $deployedCommitHash =
                    trim(
                        $deployedCommit->output()
                    );

                $deployedMessage =
                    $this->runCommand(
                        [
                            'git',
                            'log',
                            '-1',
                            '--pretty=%s',
                        ],
                        $config['path'],
                        30
                    );

                $previousDeployment =
                    OperationDeployment::query()
                        ->where(
                            'id',
                            '!=',
                            $deployment->id
                        )
                        ->where(
                            'environment',
                            $deployment->environment
                        )
                        ->where(
                            'branch',
                            $deployment->branch
                        )
                        ->where(
                            'status',
                            'completed'
                        )
                        ->whereNotNull('commit_hash')
                        ->latest('id')
                        ->first();

                $previousCommitHash =
                    $previousDeployment?->commit_hash;

                $changedFiles =
                    $this->getChangedFiles(
                        $config['path'],
                        $previousCommitHash,
                        $deployedCommitHash
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

                    'metadata' =>
                        array_merge(
                            $deployment->metadata ?? [],
                            [
                                'git' => [
                                    'previous_commit' =>
                                        $previousCommitHash,

                                    'deployed_commit' =>
                                        $deployedCommitHash,

                                    'changed_files' =>
                                        $changedFiles,
                                ],
                            ]
                        ),
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

                $output[] =
                    'Previous commit: ' .
                    (
                        $previousCommitHash
                        ?: 'none'
                    );

                $output[] =
                    'Changed files: ' .
                    (
                        $changedFiles['total']
                        ?? 0
                    );

                $deployment->update([
                    'output' =>
                        implode(
                            PHP_EOL . PHP_EOL,
                            $output
                        ),
                ]);

                $this->runPipeline(
                    $output,
                    $deployment,
                    $config
                );

                $completedAt = now();

                $duration = max(
                    0,
                    $completedAt->getTimestamp() -
                    $startedAt->getTimestamp()
                );

                $deployment =
                    $deployment->fresh();

                $deployment->update([
                    'status' =>
                        'completed',

                    'completed_at' =>
                        $completedAt,

                    'duration_seconds' =>
                        $duration,
                ]);

                $this->auditDeployment(
                    $deployment,
                    'deployment.completed',
                    'Deployment pipeline completed successfully.',
                    'success'
                );

            } catch (Throwable $e) {
                $failedAt = now();

                $duration = max(
                    0,
                    $failedAt->getTimestamp() -
                    $startedAt->getTimestamp()
                );

                $deployment->update([
                    'status' =>
                        'failed',

                    'completed_at' =>
                        $failedAt,

                    'duration_seconds' =>
                        $duration,

                    'error' =>
                        $e->getMessage(),
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
     * Execute a rollback deployment.
     */
    protected function executeRollback(
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
            $deployment =
                OperationDeployment::find(
                    $deployment->id
                );

            if (! $deployment) {
                throw new RuntimeException(
                    'Rollback deployment not found.'
                );
            }

            if ($deployment->status !== 'pending') {
                return $deployment->fresh();
            }

            $rollback =
                data_get(
                    $deployment->metadata,
                    'rollback',
                    []
                );

            $targetCommit =
                $rollback['to_commit']
                ?? $deployment->commit_hash;

            if (! $targetCommit) {
                throw new RuntimeException(
                    'Rollback target commit is missing.'
                );
            }

            /*
             * Make sure no other deployment is active.
             */
            $anotherDeploymentRunning =
                OperationDeployment::query()
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

            $metadata =
                $this->initializePipelineMetadata(
                    $deployment->metadata ?? [],
                    $config['pipeline']
                );

            /*
             * Git is used for the rollback checkout.
             */
            $metadata['pipeline']['git']['status'] =
                'pending';

            $deployment->update([
                'status' =>
                    'running',

                'started_at' =>
                    $startedAt,

                'completed_at' =>
                    null,

                'duration_seconds' =>
                    null,

                'error' =>
                    null,

                'metadata' =>
                    $metadata,
            ]);

            $deployment =
                $deployment->fresh();

            $this->auditDeployment(
                $deployment,
                'rollback.started',
                "Rollback started to commit {$targetCommit}.",
                'success'
            );

            $output = [];

            try {
                /*
                 * Verify repository.
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

                /*
                 * Fetch latest refs.
                 */
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

                /*
                 * Verify target commit.
                 */
                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Verify rollback target',
                    [
                        'git',
                        'cat-file',
                        '-e',
                        $targetCommit . '^{commit}',
                    ],
                    $config['path'],
                    30,
                    'git',
                    false
                );

                /*
                 * Checkout the target commit.
                 *
                 * We use detached HEAD intentionally.
                 * This makes the rollback deterministic and
                 * prevents Git from moving the deployment back
                 * to the branch's latest commit.
                 */
                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Checkout rollback commit',
                    [
                        'git',
                        'checkout',
                        '--detach',
                        $targetCommit,
                    ],
                    $config['path'],
                    60,
                    'git',
                    true
                );

                /*
                 * Update rollback Git metadata.
                 */
                $currentCommitResult =
                    $this->runCommand(
                        [
                            'git',
                            'rev-parse',
                            'HEAD',
                        ],
                        $config['path'],
                        30
                    );

                if (
                    ! $currentCommitResult->successful()
                ) {
                    throw new RuntimeException(
                        'Unable to verify the rollback commit.'
                    );
                }

                $actualCommit =
                    trim(
                        $currentCommitResult->output()
                    );

                if ($actualCommit !== $targetCommit) {
                    throw new RuntimeException(
                        "Rollback verification failed. Expected {$targetCommit}, got {$actualCommit}."
                    );
                }

                $targetMessage =
                    $this->getCommitMessage(
                        $config['path'],
                        $targetCommit
                    );

                $changedFiles =
                    $this->getChangedFiles(
                        $config['path'],
                        $rollback['from_commit']
                            ?? null,
                        $targetCommit
                    );

                $metadata =
                    $deployment->metadata ?? [];

                $metadata['git'] = [
                    'previous_commit' =>
                        $rollback['from_commit']
                        ?? null,

                    'deployed_commit' =>
                        $targetCommit,

                    'changed_files' =>
                        $changedFiles,
                ];

                $metadata['rollback']['actual_commit'] =
                    $actualCommit;

                $metadata['rollback']['completed_at'] =
                    now()->toIso8601String();

                $deployment->update([
                    'commit_hash' =>
                        $targetCommit,

                    'commit_message' =>
                        $targetMessage,

                    'metadata' =>
                        $metadata,
                ]);

                $output[] =
                    '[' .
                    now()->format('Y-m-d H:i:s') .
                    '] Rollback target selected';

                $output[] =
                    'From commit: ' .
                    (
                        $rollback['from_commit']
                        ?? 'unknown'
                    );

                $output[] =
                    'To commit: ' .
                    $targetCommit;

                $output[] =
                    'Message: ' .
                    (
                        $targetMessage
                        ?: 'unknown'
                    );

                $output[] =
                    'Changed files: ' .
                    (
                        $changedFiles['total']
                        ?? 0
                    );

                $deployment->update([
                    'output' =>
                        implode(
                            PHP_EOL . PHP_EOL,
                            $output
                        ),
                ]);

                /*
                 * Run the application pipeline.
                 *
                 * Database migrations are intentionally still
                 * forward-only. We do NOT run migrate:rollback.
                 */
                $this->runPipeline(
                    $output,
                    $deployment,
                    $config
                );

                /*
                 * Rollback successful.
                 */
                $completedAt = now();

                $duration = max(
                    0,
                    $completedAt->getTimestamp() -
                    $startedAt->getTimestamp()
                );

                $deployment =
                    $deployment->fresh();

                $deployment->update([
                    'status' =>
                        'completed',

                    'completed_at' =>
                        $completedAt,

                    'duration_seconds' =>
                        $duration,
                ]);

                $this->auditDeployment(
                    $deployment,
                    'rollback.completed',
                    "Rollback completed successfully to commit {$targetCommit}.",
                    'success'
                );

            } catch (Throwable $e) {
                $failedAt = now();

                $duration = max(
                    0,
                    $failedAt->getTimestamp() -
                    $startedAt->getTimestamp()
                );

                $deployment->update([
                    'status' =>
                        'failed',

                    'completed_at' =>
                        $failedAt,

                    'duration_seconds' =>
                        $duration,

                    'error' =>
                        $e->getMessage(),
                ]);

                $this->auditDeployment(
                    $deployment,
                    'rollback.failed',
                    'Rollback failed: ' .
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
     * Run the normal application pipeline.
     */
    protected function runPipeline(
        array &$output,
        OperationDeployment $deployment,
        array $config
    ): void {
        /*
         * Composer.
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
                'Composer pipeline stage is disabled.',
                'composer'
            );
        }

        /*
         * NPM.
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
                'NPM pipeline stage is disabled.',
                'npm'
            );
        }

        /*
         * Build.
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
                'Build pipeline stage is disabled.',
                'build'
            );
        }

        /*
         * Migrations.
         *
         * IMPORTANT:
         * This is intentionally the normal forward migration
         * command. Rollback deployments do NOT call
         * migrate:rollback.
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
                'Migrations pipeline stage is disabled.',
                'migrations'
            );
        }

        /*
         * Optimize.
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
                'Optimization stage is disabled.',
                'optimize'
            );
        }

        /*
         * Queue restart.
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
                'Queue restart stage is disabled.',
                'queue_restart'
            );
        }

        /*
         * Health check.
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
        } else {
            $this->recordSkippedStep(
                $output,
                $deployment,
                'Run health checks',
                'Health check stage is disabled.',
                'health_check'
            );
        }
    }

    /**
     * Get a Git commit message.
     */
    protected function getCommitMessage(
        string $path,
        ?string $commit
    ): ?string {
        if (! $commit) {
            return null;
        }

        $result = $this->runCommand(
            [
                'git',
                'log',
                '-1',
                '--pretty=%s',
                $commit,
            ],
            $path,
            30
        );

        return $result->successful()
            ? trim($result->output())
            : null;
    }

    /**
     * Initialize runtime pipeline metadata.
     */
    protected function initializePipelineMetadata(
        array $metadata,
        array $pipeline
    ): array {
        $pipelineState =
            $this->pipelineOverview(
                $pipeline
            );

        foreach (
            $pipelineState
            as $key => $stage
        ) {
            $pipelineState[$key]['status'] =
                $stage['enabled']
                    ? 'pending'
                    : 'skipped';

            $pipelineState[$key]['started_at'] =
                null;

            $pipelineState[$key]['completed_at'] =
                null;

            $pipelineState[$key]['message'] =
                null;
        }

        $metadata['pipeline'] =
            $pipelineState;

        return $metadata;
    }

    /**
     * Update runtime pipeline status.
     */
    protected function updatePipelineStatus(
        OperationDeployment $deployment,
        string $key,
        string $status,
        ?string $message = null
    ): void {
        $metadata =
            $deployment->metadata ?? [];

        $pipeline =
            $metadata['pipeline'] ?? [];

        if (! isset($pipeline[$key])) {
            $pipeline[$key] = [
                'enabled' => true,
            ];
        }

        $pipeline[$key]['status'] =
            $status;

        if ($message !== null) {
            $pipeline[$key]['message'] =
                $message;
        }

        if ($status === 'running') {
            $pipeline[$key]['started_at'] =
                now()->toIso8601String();

            $pipeline[$key]['completed_at'] =
                null;
        }

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
            $pipeline[$key]['completed_at'] =
                now()->toIso8601String();
        }

        $metadata['pipeline'] =
            $pipeline;

        $deployment->update([
            'metadata' =>
                $metadata,
        ]);
    }

    /**
     * Get changed Git files.
     */
    protected function getChangedFiles(
        string $path,
        ?string $previousCommit,
        string $deployedCommit
    ): array {
        if (! $deployedCommit) {
            return [
                'total' => 0,
                'additions' => 0,
                'deletions' => 0,
                'files' => [],
            ];
        }

        if (! $previousCommit) {
            $parentResult =
                $this->runCommand(
                    [
                        'git',
                        'rev-parse',
                        $deployedCommit . '^',
                    ],
                    $path,
                    30
                );

            if ($parentResult->successful()) {
                $previousCommit =
                    trim(
                        $parentResult->output()
                    );
            }
        }

        if (! $previousCommit) {
            return [
                'total' => 0,
                'additions' => 0,
                'deletions' => 0,
                'files' => [],
            ];
        }

        $nameStatus =
            $this->runCommand(
                [
                    'git',
                    'diff',
                    '--name-status',
                    '-M',
                    $previousCommit,
                    $deployedCommit,
                ],
                $path,
                60
            );

        $numstat =
            $this->runCommand(
                [
                    'git',
                    'diff',
                    '--numstat',
                    '-M',
                    $previousCommit,
                    $deployedCommit,
                ],
                $path,
                60
            );

        if (! $nameStatus->successful()) {
            Log::channel('daily')->warning(
                'Unable to calculate deployment changed files.',
                [
                    'previous_commit' =>
                        $previousCommit,

                    'deployed_commit' =>
                        $deployedCommit,

                    'error' =>
                        trim(
                            $nameStatus->errorOutput()
                        ),
                ]
            );

            return [
                'total' => 0,
                'additions' => 0,
                'deletions' => 0,
                'files' => [],
            ];
        }

        $numstatMap =
            $this->parseGitNumstat(
                $numstat->successful()
                    ? $numstat->output()
                    : ''
            );

        $files = [];

        foreach (
            preg_split(
                '/\r\n|\r|\n/',
                trim(
                    $nameStatus->output()
                )
            ) ?: []
            as $line
        ) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts =
                explode(
                    "\t",
                    $line
                );

            $statusCode =
                $parts[0] ?? '';

            $status =
                'modified';

            $oldPath =
                null;

            $pathName =
                $parts[1] ?? '';

            switch (true) {
                case str_starts_with(
                    $statusCode,
                    'A'
                ):
                    $status = 'added';

                    $pathName =
                        $parts[1] ?? '';

                    break;

                case str_starts_with(
                    $statusCode,
                    'D'
                ):
                    $status = 'deleted';

                    $pathName =
                        $parts[1] ?? '';

                    break;

                case str_starts_with(
                    $statusCode,
                    'R'
                ):
                    $status = 'renamed';

                    $oldPath =
                        $parts[1] ?? null;

                    $pathName =
                        $parts[2] ?? '';

                    break;

                case str_starts_with(
                    $statusCode,
                    'C'
                ):
                    $status = 'copied';

                    $oldPath =
                        $parts[1] ?? null;

                    $pathName =
                        $parts[2] ?? '';

                    break;

                case str_starts_with(
                    $statusCode,
                    'M'
                ):
                default:
                    $status = 'modified';

                    $pathName =
                        $parts[1] ?? '';

                    break;
            }

            $pathName =
                $this->cleanGitPath(
                    $pathName
                );

            if ($oldPath !== null) {
                $oldPath =
                    $this->cleanGitPath(
                        $oldPath
                    );
            }

            $stats =
                $numstatMap[$pathName]
                ?? [
                    'additions' => 0,
                    'deletions' => 0,
                ];

            $files[] = [
                'path' =>
                    $pathName,

                'old_path' =>
                    $oldPath,

                'status' =>
                    $status,

                'additions' =>
                    $stats['additions'],

                'deletions' =>
                    $stats['deletions'],
            ];
        }

        return [
            'total' =>
                count($files),

            'additions' =>
                array_sum(
                    array_column(
                        $files,
                        'additions'
                    )
                ),

            'deletions' =>
                array_sum(
                    array_column(
                        $files,
                        'deletions'
                    )
                ),

            'files' =>
                $files,
        ];
    }

    /**
     * Parse Git numstat output.
     */
    protected function parseGitNumstat(
        string $output
    ): array {
        $map = [];

        foreach (
            preg_split(
                '/\r\n|\r|\n/',
                trim($output)
            ) ?: []
            as $line
        ) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts =
                explode(
                    "\t",
                    $line
                );

            if (count($parts) < 3) {
                continue;
            }

            $additions =
                $parts[0];

            $deletions =
                $parts[1];

            $path =
                $parts[2];

            $additions =
                $additions === '-'
                    ? 0
                    : (int) $additions;

            $deletions =
                $deletions === '-'
                    ? 0
                    : (int) $deletions;

            $path =
                $this->cleanGitPath(
                    $path
                );

            $map[$path] = [
                'additions' =>
                    $additions,

                'deletions' =>
                    $deletions,
            ];
        }

        return $map;
    }

    /**
     * Clean Git path.
     */
    protected function cleanGitPath(
        string $path
    ): string {
        $path = trim($path);

        if (
            strlen($path) >= 2 &&
            $path[0] === '"' &&
            $path[
                strlen($path) - 1
            ] === '"'
        ) {
            $path =
                substr(
                    $path,
                    1,
                    -1
                );

            $path =
                preg_replace_callback(
                    '/\\\\([0-7]{3})/',
                    static function ($matches) {
                        return chr(
                            octdec(
                                $matches[1]
                            )
                        );
                    },
                    $path
                ) ?? $path;
        }

        return $path;
    }

    /**
     * Validate branch.
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
     * Check executable status.
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

        $result =
            $this->runCommand(
                $command,
                $path,
                15
            );

        return [
            'status' =>
                $result->successful()
                    ? 'healthy'
                    : 'failed',

            'message' =>
                $result->successful()
                    ? "{$name} is installed and executable."
                    : "{$name} check failed or executable not found.",

            'output' =>
                trim(
                    $result->output()
                ),

            'error' =>
                trim(
                    $result->errorOutput()
                ),
        ];
    }

    /**
     * Run configured pipeline step.
     */
    protected function runConfiguredPipelineStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        string $key,
        array $config
    ): void {
        $command =
            data_get(
                $config,
                "pipeline.{$key}.command"
            );

        if (
            ! is_array($command) ||
            empty($command)
        ) {
            $binaries =
                $config['binaries'];

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

        $command =
            $this->resolvePipelineCommand(
                $key,
                $command,
                $config
            );

        $timeout =
            (int) data_get(
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
            $timeout,
            $key,
            true
        );
    }

    /**
     * Resolve pipeline executable.
     */
    protected function resolvePipelineCommand(
        string $key,
        array $command,
        array $config
    ): array {
        $binaries =
            $config['binaries'];

        return match ($key) {
            'composer' => [
                $binaries['composer'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'npm' => [
                $binaries['node'],
                $binaries['npm_cli'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'build' => [
                $binaries['node'],
                $binaries['npm_cli'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'migrations' => [
                $binaries['php'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'optimize' => [
                $binaries['php'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'queue_restart' => [
                $binaries['php'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            'health_check' => [
                $binaries['php'],
                ...array_slice(
                    $command,
                    1
                ),
            ],

            default =>
                $command,
        };
    }

    /**
     * Record skipped stage.
     */
    protected function recordSkippedStep(
        array &$output,
        OperationDeployment $deployment,
        string $stepName,
        string $reason,
        ?string $pipelineKey = null
    ): void {
        $timestamp =
            now()->format(
                'Y-m-d H:i:s'
            );

        $output[] =
            "[{$timestamp}] Skipped step: {$stepName}";

        $output[] =
            $reason;

        $deployment->update([
            'output' =>
                implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
        ]);

        if ($pipelineKey) {
            $this->updatePipelineStatus(
                $deployment,
                $pipelineKey,
                'skipped',
                $reason
            );
        }
    }

    /**
     * Run one deployment step.
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
        if ($pipelineKey) {
            $this->updatePipelineStatus(
                $deployment,
                $pipelineKey,
                'running',
                "Running {$stepName}."
            );
        }

        $timestamp =
            now()->format(
                'Y-m-d H:i:s'
            );

        $output[] =
            "[{$timestamp}] Starting step: {$stepName}";

        $output[] =
            'Command: ' .
            implode(
                ' ',
                array_map(
                    static fn ($value) =>
                        (string) $value,
                    $command
                )
            );

        $deployment->update([
            'output' =>
                implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
        ]);

        $result =
            $this->runCommand(
                $command,
                $path,
                $timeout
            );

        if ($result->output()) {
            $output[] =
                trim(
                    $result->output()
                );
        }

        if ($result->errorOutput()) {
            $output[] =
                trim(
                    $result->errorOutput()
                );
        }

        $deployment->update([
            'output' =>
                implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
        ]);

        if (! $result->successful()) {
            if ($pipelineKey) {
                $this->updatePipelineStatus(
                    $deployment,
                    $pipelineKey,
                    'failed',
                    "Deployment step [{$stepName}] failed."
                );
            }

            throw new RuntimeException(
                "Deployment step [{$stepName}] failed: " .
                trim(
                    $result->errorOutput()
                    ?: $result->output()
                )
            );
        }

        $completedTimestamp =
            now()->format(
                'Y-m-d H:i:s'
            );

        $output[] =
            "[{$completedTimestamp}] Completed step: {$stepName}";

        $deployment->update([
            'output' =>
                implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
        ]);

        if (
            $pipelineKey &&
            $completeStage
        ) {
            $this->updatePipelineStatus(
                $deployment,
                $pipelineKey,
                'completed',
                "Completed {$stepName}."
            );
        }
    }

    /**
     * Build controlled process environment.
     */
    protected function processEnvironment(): array
    {
        $config =
            $this->config();

        $operationsStorage =
            storage_path(
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

        $directories = [];

        foreach ([
            $config['binaries']['php'],
            $config['binaries']['node'],
            $config['binaries']['npm'],
            $config['binaries']['npm_cli'],
            $config['binaries']['composer'],
        ] as $binary) {
            $directory =
                $this->binaryDirectory(
                    $binary
                );

            if ($directory !== null) {
                $directories[] =
                    $directory;
            }
        }

        $directories =
            array_values(
                array_unique(
                    array_filter(
                        $directories
                    )
                )
            );

        $existingPath =
            getenv('PATH') ?: '';

        $pathParts =
            array_merge(
                $directories,
                $this->splitWindowsPath(
                    $existingPath
                )
            );

        $seen = [];

        $pathParts =
            array_values(
                array_filter(
                    $pathParts,
                    function ($value) use (
                        &$seen
                    ) {
                        $normalized =
                            strtolower(
                                rtrim(
                                    trim($value),
                                    '\\/'
                                )
                            );

                        if (
                            $normalized === ''
                        ) {
                            return false;
                        }

                        if (
                            isset(
                                $seen[$normalized]
                            )
                        ) {
                            return false;
                        }

                        $seen[$normalized] =
                            true;

                        return true;
                    }
                )
            );

        $separator =
            DIRECTORY_SEPARATOR === '\\'
                ? ';'
                : ':';

        return [
            'PATH' =>
                implode(
                    $separator,
                    $pathParts
                ),

            'NODE_PATH' =>
                $this->binaryDirectory(
                    $config['binaries']['node']
                ) ?? '',

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
     * Get binary directory.
     */
    protected function binaryDirectory(
        string $binary
    ): ?string {
        $binary =
            trim($binary);

        if ($binary === '') {
            return null;
        }

        if (
            ! str_contains($binary, '\\') &&
            ! str_contains($binary, '/')
        ) {
            return null;
        }

        $directory =
            dirname(
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
     * Split PATH.
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
     * Run controlled process.
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
        $level =
            match (strtolower($level)) {
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