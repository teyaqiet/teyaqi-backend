<?php

namespace App\Operations\Services;

use App\Jobs\ExecuteDeploymentJob;
use App\Models\OperationDeployment;
use Illuminate\Support\Facades\DB;
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
        return config('operations.deployments', []);
    }

    /**
     * -------------------------------------------------------------
     * PREFLIGHT
     * -------------------------------------------------------------
     *
     * Check whether the server is ready to deploy.
     */
    public function preflight(): array
    {
        $config = $this->config();

        $checks = [];
        $failed = 0;
        $warnings = 0;

        /*
         * ---------------------------------------------------------
         * DEPLOYMENT SYSTEM
         * ---------------------------------------------------------
         */

        $enabled = (bool) ($config['enabled'] ?? false);

        $checks[] = [
            'name' => 'Deployment system',
            'status' => $enabled ? 'passed' : 'failed',
            'message' => $enabled
                ? 'Deployment system is enabled.'
                : 'Deployment system is disabled.',
        ];

        if (!$enabled) {
            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * DEPLOYMENT PATH
         * ---------------------------------------------------------
         */

        $path = $config['path'] ?? base_path();

        if (is_dir($path)) {
            $checks[] = [
                'name' => 'Deployment path',
                'status' => 'passed',
                'message' => "Deployment path exists: {$path}",
            ];
        } else {
            $checks[] = [
                'name' => 'Deployment path',
                'status' => 'failed',
                'message' => "Deployment path does not exist: {$path}",
            ];

            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * GIT
         * ---------------------------------------------------------
         */

        $gitCheck = $this->runCommand(
            ['git', '--version'],
            base_path()
        );

        if ($gitCheck->successful()) {
            $checks[] = [
                'name' => 'Git',
                'status' => 'passed',
                'message' => trim($gitCheck->output()),
            ];
        } else {
            $checks[] = [
                'name' => 'Git',
                'status' => 'failed',
                'message' => trim(
                    $gitCheck->errorOutput()
                ) ?: 'Git is not available.',
            ];

            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * GIT REPOSITORY
         * ---------------------------------------------------------
         */

        if (is_dir($path)) {
            $repoCheck = $this->runCommand(
                ['git', 'rev-parse', '--is-inside-work-tree'],
                $path
            );

            if (
                $repoCheck->successful()
                && trim($repoCheck->output()) === 'true'
            ) {
                $checks[] = [
                    'name' => 'Git repository',
                    'status' => 'passed',
                    'message' => 'Deployment directory is a Git repository.',
                ];
            } else {
                $checks[] = [
                    'name' => 'Git repository',
                    'status' => 'failed',
                    'message' => 'Deployment directory is not a valid Git repository.',
                ];

                $failed++;
            }
        }

        /*
         * ---------------------------------------------------------
         * PHP
         * ---------------------------------------------------------
         */

        $phpCheck = $this->executableCheck(
            $config['binaries']['php'] ?? 'php',
            ['--version'],
            $path
        );

        $checks[] = $phpCheck;

        if ($phpCheck['status'] === 'failed') {
            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * COMPOSER
         * ---------------------------------------------------------
         */

        $composerCheck = $this->executableCheck(
            $config['binaries']['composer'] ?? 'composer',
            ['--version'],
            $path
        );

        $checks[] = $composerCheck;

        if ($composerCheck['status'] === 'failed') {
            $failed++;
        }

        /*
 * ---------------------------------------------------------
 * NODE / NPM
 * ---------------------------------------------------------
 */

$pipeline = $this->pipelineOverview(
    $config['pipeline'] ?? []
);

if ($pipeline['npm']['enabled'] ?? false) {
    $nodeCheck = $this->executableCheck(
        $config['binaries']['node'] ?? 'node',
        ['--version'],
        $path,
        'warning'
    );

    $checks[] = $nodeCheck;

    if ($nodeCheck['status'] === 'warning') {
        $warnings++;
    }

    if ($nodeCheck['status'] === 'failed') {
        $failed++;
    }

    $npmCheck = $this->executableCheck(
        $config['binaries']['npm'] ?? 'npm',
        ['--version'],
        $path,
        'warning'
    );

    $checks[] = $npmCheck;

    if ($npmCheck['status'] === 'warning') {
        $warnings++;
    }

    if ($npmCheck['status'] === 'failed') {
        $failed++;
    }
} else {
    $checks[] = [
        'name' => 'Node / npm',
        'status' => 'skipped',
        'message' => 'Node/npm pipeline step is disabled for this backend deployment.',
    ];
}

        /*
         * ---------------------------------------------------------
         * DATABASE
         * ---------------------------------------------------------
         */

        try {
            DB::connection()->getPdo();

            $checks[] = [
                'name' => 'Database',
                'status' => 'passed',
                'message' => 'Database connection is available.',
            ];
        } catch (Throwable $e) {
            $checks[] = [
                'name' => 'Database',
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];

            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * DISK SPACE
         * ---------------------------------------------------------
         */

        if (is_dir($path)) {
            $freeBytes = @disk_free_space($path);

            if ($freeBytes !== false) {
                $minimumDisk = (int) (
                    $config['minimum_disk_space'] ?? 524288000
                );

                if ($freeBytes >= $minimumDisk) {
                    $checks[] = [
                        'name' => 'Disk space',
                        'status' => 'passed',
                        'message' =>
                            $this->formatBytes($freeBytes)
                            . ' free.',
                    ];
                } else {
                    $checks[] = [
                        'name' => 'Disk space',
                        'status' => 'warning',
                        'message' =>
                            $this->formatBytes($freeBytes)
                            . ' free. Recommended minimum: '
                            . $this->formatBytes($minimumDisk),
                    ];

                    $warnings++;
                }
            } else {
                $checks[] = [
                    'name' => 'Disk space',
                    'status' => 'warning',
                    'message' => 'Unable to determine available disk space.',
                ];

                $warnings++;
            }
        }

        /*
         * ---------------------------------------------------------
         * BRANCH
         * ---------------------------------------------------------
         */

        $branch = $config['branch'] ?? 'main';

        try {
            $this->validateBranch($branch);

            $checks[] = [
                'name' => 'Deployment branch',
                'status' => 'passed',
                'message' => "Branch name '{$branch}' is valid.",
            ];
        } catch (Throwable $e) {
            $checks[] = [
                'name' => 'Deployment branch',
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];

            $failed++;
        }

        /*
         * ---------------------------------------------------------
         * RESULT
         * ---------------------------------------------------------
         */

        return [
            'ready' => $failed === 0,

            'status' => $failed === 0
                ? ($warnings > 0 ? 'warning' : 'passed')
                : 'failed',

            'checks' => $checks,

            'summary' => [
                'total' => count($checks),
                'passed' => collect($checks)
                    ->where('status', 'passed')
                    ->count(),
                'failed' => $failed,
                'warnings' => $warnings,
                'skipped' => collect($checks)
                    ->where('status', 'skipped')
                    ->count(),
            ],
        ];
    }

    /**
     * -------------------------------------------------------------
     * OVERVIEW
     * -------------------------------------------------------------
     */
    public function overview(): array
    {
        $config = $this->config();

        $latest = OperationDeployment::query()
            ->latest('id')
            ->first();

        $running = OperationDeployment::query()
            ->where('status', 'running')
            ->latest('id')
            ->first();

        $pending = OperationDeployment::query()
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $completed = OperationDeployment::query()
            ->where('status', 'completed')
            ->count();

        $failed = OperationDeployment::query()
            ->where('status', 'failed')
            ->count();

        return [
            'enabled' => (bool) ($config['enabled'] ?? false),

            'environment' =>
                $config['environment'] ?? 'staging',

            'branch' =>
                $config['branch'] ?? 'main',

            'path' =>
                $config['path'] ?? base_path(),

            'running' => $running,

            'pending' => $pending,

            'latest' => $latest,

            'statistics' => [
                'completed' => $completed,
                'failed' => $failed,
                'total' => OperationDeployment::count(),
            ],

            'pipeline' =>
                $this->pipelineOverview(
                    $config['pipeline'] ?? []
                ),
        ];
    }

    /**
     * -------------------------------------------------------------
     * PIPELINE OVERVIEW
     * -------------------------------------------------------------
     */
    public function pipelineOverview(array $pipeline = []): array
    {
        return [
            'composer' => [
                'enabled' => (bool) (
                    $pipeline['composer']['enabled'] ?? true
                ),
            ],

            'npm' => [
                'enabled' => (bool) (
                    $pipeline['npm']['enabled'] ?? false
                ),
            ],

            'build' => [
                'enabled' => (bool) (
                    $pipeline['build']['enabled'] ?? false
                ),
            ],

            'migrations' => [
                'enabled' => (bool) (
                    $pipeline['migrations']['enabled'] ?? true
                ),
            ],

            'optimize' => [
                'enabled' => (bool) (
                    $pipeline['optimize']['enabled'] ?? true
                ),
            ],

            'queue_restart' => [
                'enabled' => (bool) (
                    $pipeline['queue_restart']['enabled'] ?? true
                ),
            ],

            'health_check' => [
                'enabled' => (bool) (
                    $pipeline['health_check']['enabled'] ?? true
                ),
            ],
        ];
    }

    /**
     * -------------------------------------------------------------
     * DEPLOYMENT HISTORY
     * -------------------------------------------------------------
     */
    public function deployments(int $limit = 20)
    {
        return OperationDeployment::query()
            ->with('adminUser')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * -------------------------------------------------------------
     * FIND DEPLOYMENT
     * -------------------------------------------------------------
     */
    public function find(int $id): ?OperationDeployment
    {
        return OperationDeployment::query()
            ->with([
                'adminUser',
                'rollbackOf',
                'rollbacks',
            ])
            ->find($id);
    }

    /**
     * -------------------------------------------------------------
     * CREATE DEPLOYMENT
     * -------------------------------------------------------------
     *
     * This is the method called by DeploymentController.
     *
     * The controller passes:
     *
     * [
     *     'environment' => 'staging',
     *     'branch' => 'main',
     * ]
     */
    public function createDeployment(
        array $data
    ): OperationDeployment {
        $config = $this->config();

        /*
         * ---------------------------------------------------------
         * SYSTEM ENABLED
         * ---------------------------------------------------------
         */

        if (!($config['enabled'] ?? false)) {
            throw new RuntimeException(
                'Deployment system is currently disabled.'
            );
        }

        /*
         * ---------------------------------------------------------
         * INPUT
         * ---------------------------------------------------------
         */

        $environment = trim(
            (string) (
                $data['environment']
                ?? $config['environment']
                ?? 'staging'
            )
        );

        $branch = trim(
            (string) (
                $data['branch']
                ?? $config['branch']
                ?? 'main'
            )
        );

        if ($environment === '') {
            throw new RuntimeException(
                'Deployment environment is required.'
            );
        }

        if ($branch === '') {
            throw new RuntimeException(
                'Deployment branch is required.'
            );
        }

        /*
         * ---------------------------------------------------------
         * ENVIRONMENT VALIDATION
         * ---------------------------------------------------------
         */

        $configuredEnvironment =
            $config['environment'] ?? 'staging';

        if ($environment !== $configuredEnvironment) {
            throw new RuntimeException(
                "Environment '{$environment}' is not configured for deployment."
            );
        }

        /*
         * ---------------------------------------------------------
         * BRANCH VALIDATION
         * ---------------------------------------------------------
         */

        $this->validateBranch($branch);

        /*
         * ---------------------------------------------------------
         * DEPLOYMENT PATH
         * ---------------------------------------------------------
         */

        $path = $config['path'] ?? base_path();

        if (!is_dir($path)) {
            throw new RuntimeException(
                "Deployment path does not exist: {$path}"
            );
        }

        /*
         * ---------------------------------------------------------
         * VERIFY GIT
         * ---------------------------------------------------------
         */

        $repoCheck = $this->runCommand(
            ['git', 'rev-parse', '--is-inside-work-tree'],
            $path
        );

        if (
            !$repoCheck->successful()
            || trim($repoCheck->output()) !== 'true'
        ) {
            throw new RuntimeException(
                'Deployment path is not a valid Git repository.'
            );
        }

        /*
         * ---------------------------------------------------------
         * CHECK REMOTE
         * ---------------------------------------------------------
         */

        $remote = $config['remote'] ?? 'origin';

        $remoteCheck = $this->runCommand(
            ['git', 'remote', 'get-url', $remote],
            $path
        );

        if (!$remoteCheck->successful()) {
            throw new RuntimeException(
                "Git remote '{$remote}' is not configured."
            );
        }

        /*
         * ---------------------------------------------------------
         * CREATION LOCK
         * ---------------------------------------------------------
         *
         * Prevent two HTTP requests from creating deployments
         * at exactly the same time.
         */
        $creationLock = cache()->lock(
            'teyaqi:operations:deployment:create',
            30
        );

        if (!$creationLock->get()) {
            throw new RuntimeException(
                'Another deployment is currently being created. Please try again.'
            );
        }

        try {
            /*
             * -----------------------------------------------------
             * DEPLOYMENT LOCK
             * -----------------------------------------------------
             */

            $deploymentLockService =
                app(DeploymentLockService::class);

            $existingLock =
                $deploymentLockService->current(
                    $environment
                );

            if ($existingLock) {
                throw new RuntimeException(
                    "Deployment #{$existingLock->deployment_id} "
                    . "is already running in {$environment}."
                );
            }

            /*
             * -----------------------------------------------------
             * ACTIVE DEPLOYMENT CHECK
             * -----------------------------------------------------
             *
             * There should only be one deployment running/pending
             * through the operations center.
             */
            $activeDeployment =
                OperationDeployment::query()
                    ->whereIn(
                        'status',
                        ['pending', 'running']
                    )
                    ->latest('id')
                    ->first();

            if ($activeDeployment) {
                throw new RuntimeException(
                    "Deployment #{$activeDeployment->id} "
                    . "is already {$activeDeployment->status}."
                );
            }

            /*
             * -----------------------------------------------------
             * CREATE DATABASE RECORD
             * -----------------------------------------------------
             */

            $deployment = OperationDeployment::create([
                'environment' => $environment,

                'branch' => $branch,

                /*
                 * The actual commit is resolved by the worker after
                 * fetching/checking out the requested branch.
                 */
                'commit_hash' => null,

                'commit_message' => null,

                'status' => 'pending',

                'triggered_by' =>
                    auth('admin')->id(),

                'started_at' => null,

                'completed_at' => null,

                'duration_seconds' => null,

                'output' => null,

                'error' => null,

                'type' => 'deployment',

                'rollback_of' => null,

                'metadata' => [
                    'deployment' => [
                        'is_rollback' => false,
                        'created_at' => now()->toIso8601String(),
                    ],

                    'environment' => $environment,

                    'branch' => $branch,

                    'path' => $path,

                    'remote' => $remote,

                    'pipeline' =>
                        $this->pipelineOverview(
                            $config['pipeline'] ?? []
                        ),
                ],
            ]);

            /*
             * -----------------------------------------------------
             * QUEUE EXECUTION
             * -----------------------------------------------------
             */

            try {
                ExecuteDeploymentJob::dispatch(
                    $deployment->id
                );
            } catch (Throwable $e) {
                /*
                 * If queue dispatch itself fails, don't leave a
                 * deployment permanently stuck in pending.
                 */
                $deployment->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                throw $e;
            }

            return $deployment->fresh();

        } finally {
            $creationLock->release();
        }
    }

    /**
     * -------------------------------------------------------------
     * ROLLBACK TARGETS
     * -------------------------------------------------------------
     */
    public function rollbackTargets(int $limit = 10)
    {
        return OperationDeployment::query()
            ->where('status', 'completed')
            ->whereNotNull('commit_hash')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * -------------------------------------------------------------
     * CAN ROLLBACK
     * -------------------------------------------------------------
     */
    public function canRollback(
        OperationDeployment $deployment
    ): bool {
        return $deployment->status === 'completed'
            && !empty($deployment->commit_hash)
            && !$deployment->isRollback();
    }

    /**
     * -------------------------------------------------------------
     * CREATE ROLLBACK
     * -------------------------------------------------------------
     */
    public function createRollback(
        OperationDeployment $target
    ): OperationDeployment {
        if (!$this->canRollback($target)) {
            throw new RuntimeException(
                'This deployment cannot be rolled back.'
            );
        }

        $config = $this->config();

        $environment = $target->environment;
        $branch = $target->branch;

        /*
         * Prevent duplicate rollback creation.
         */
        $creationLock = cache()->lock(
            'teyaqi:operations:deployment:create',
            30
        );

        if (!$creationLock->get()) {
            throw new RuntimeException(
                'Another deployment is currently being created.'
            );
        }

        try {
            /*
             * Environment lock check.
             */
            $deploymentLockService =
                app(DeploymentLockService::class);

            $existingLock =
                $deploymentLockService->current(
                    $environment
                );

            if ($existingLock) {
                throw new RuntimeException(
                    "Deployment #{$existingLock->deployment_id} "
                    . "is already running."
                );
            }

            /*
             * Prevent another pending/running deployment.
             */
            $activeDeployment =
                OperationDeployment::query()
                    ->whereIn(
                        'status',
                        ['pending', 'running']
                    )
                    ->latest('id')
                    ->first();

            if ($activeDeployment) {
                throw new RuntimeException(
                    "Deployment #{$activeDeployment->id} "
                    . "is already {$activeDeployment->status}."
                );
            }

            /*
             * Create rollback deployment.
             */
            $deployment = OperationDeployment::create([
                'environment' => $environment,

                'branch' => $branch,

                'commit_hash' => $target->commit_hash,

                'commit_message' =>
                    'Rollback to '
                    . $target->commit_hash,

                'status' => 'pending',

                'triggered_by' =>
                    auth('admin')->id(),

                'started_at' => null,

                'completed_at' => null,

                'duration_seconds' => null,

                'output' => null,

                'error' => null,

                'type' => 'rollback',

                'rollback_of' => $target->id,

                'metadata' => [
                    'rollback' => [
                        'is_rollback' => true,

                        'target_deployment_id' =>
                            $target->id,

                        'target_commit' =>
                            $target->commit_hash,

                        'target_commit_message' =>
                            $target->commit_message,

                        'created_at' =>
                            now()->toIso8601String(),
                    ],

                    'environment' =>
                        $environment,

                    'branch' =>
                        $branch,

                    'path' =>
                        $config['path'] ?? base_path(),

                    'remote' =>
                        $config['remote'] ?? 'origin',
                ],
            ]);

            /*
             * Queue rollback execution.
             */
            try {
                ExecuteDeploymentJob::dispatch(
                    $deployment->id
                );
            } catch (Throwable $e) {
                $deployment->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                ]);

                throw $e;
            }

            return $deployment->fresh();

        } finally {
            $creationLock->release();
        }
    }

    /**
     * -------------------------------------------------------------
     * EXECUTE DEPLOYMENT
     * -------------------------------------------------------------
     */
    public function execute(
        OperationDeployment $deployment
    ): void {
        if ($deployment->status !== 'pending') {
            return;
        }

        /*
         * Mark deployment as running.
         */
        $deployment->update([
            'status' => 'running',
            'started_at' => now(),
            'completed_at' => null,
            'duration_seconds' => null,
            'error' => null,
        ]);

        try {
            if ($deployment->isRollback()) {
                $this->executeRollback($deployment);
            } else {
                $this->executeStandardDeployment($deployment);
            }
        } catch (Throwable $e) {
            /*
             * Make sure any exception is persisted even if the
             * lower-level execution method did not catch it.
             */
            if ($deployment->fresh()->status !== 'failed') {
                $fresh = $deployment->fresh();

                $duration = $fresh->started_at
                    ? max(
                        0,
                        now()->getTimestamp()
                            - $fresh->started_at->getTimestamp()
                    )
                    : null;

                $fresh->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                    'duration_seconds' => $duration,
                ]);
            }

            throw $e;
        }
    }

    /**
     * -------------------------------------------------------------
     * STANDARD DEPLOYMENT
     * -------------------------------------------------------------
     */
    protected function executeStandardDeployment(
        OperationDeployment $deployment
    ): void {
        $config = $this->config();

        $path = $config['path'] ?? base_path();

        $branch = $deployment->branch;

        $remote = $config['remote'] ?? 'origin';

        $timeout = (int) (
            $config['timeout'] ?? 600
        );

        /*
         * ---------------------------------------------------------
         * EXECUTION LOCK
         * ---------------------------------------------------------
         */
        $lockSeconds = max(
            $timeout + 60,
            120
        );

        $lock = cache()->lock(
            'teyaqi:operations:deployment',
            $lockSeconds
        );

        if (!$lock->get()) {
            throw new RuntimeException(
                'Another deployment is already running.'
            );
        }

        try {
            /*
             * -----------------------------------------------------
             * ACTIVE DEPLOYMENT CHECK
             * -----------------------------------------------------
             */
            $otherDeployment =
                OperationDeployment::query()
                    ->whereIn(
                        'status',
                        ['pending', 'running']
                    )
                    ->where(
                        'id',
                        '!=',
                        $deployment->id
                    )
                    ->latest('id')
                    ->first();

            if ($otherDeployment) {
                throw new RuntimeException(
                    "Deployment #{$otherDeployment->id} "
                    . "is already {$otherDeployment->status}."
                );
            }

            /*
             * -----------------------------------------------------
             * VERIFY REPOSITORY
             * -----------------------------------------------------
             */
            $repoCheck = $this->runCommand(
                ['git', 'rev-parse', '--is-inside-work-tree'],
                $path
            );

            if (
                !$repoCheck->successful()
                || trim($repoCheck->output()) !== 'true'
            ) {
                throw new RuntimeException(
                    'Deployment path is not a valid Git repository.'
                );
            }

            /*
             * -----------------------------------------------------
             * FETCH
             * -----------------------------------------------------
             */
            $fetch = $this->runCommand(
                [
                    'git',
                    'fetch',
                    $remote,
                    '--prune',
                ],
                $path,
                $timeout
            );

            if (!$fetch->successful()) {
                throw new RuntimeException(
                    "Git fetch failed:\n"
                    . trim($fetch->errorOutput())
                );
            }

            /*
             * -----------------------------------------------------
             * CHECKOUT BRANCH
             * -----------------------------------------------------
             */
            $this->validateBranch($branch);

            $checkout = $this->runCommand(
                [
                    'git',
                    'checkout',
                    $branch,
                ],
                $path,
                $timeout
            );

            if (!$checkout->successful()) {
                throw new RuntimeException(
                    "Git checkout failed:\n"
                    . trim($checkout->errorOutput())
                );
            }

            /*
             * -----------------------------------------------------
             * PULL
             * -----------------------------------------------------
             */
            $pull = $this->runCommand(
                [
                    'git',
                    'pull',
                    '--ff-only',
                    $remote,
                    $branch,
                ],
                $path,
                $timeout
            );

            if (!$pull->successful()) {
                throw new RuntimeException(
                    "Git pull failed:\n"
                    . trim($pull->errorOutput())
                );
            }

            /*
             * -----------------------------------------------------
             * RESOLVE COMMIT
             * -----------------------------------------------------
             */
            $commitCheck = $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    'HEAD',
                ],
                $path
            );

            if (!$commitCheck->successful()) {
                throw new RuntimeException(
                    'Unable to resolve deployed Git commit.'
                );
            }

            $commitHash =
                trim($commitCheck->output());

            $commitMessage =
                $this->getCommitMessage(
                    $path,
                    $timeout
                );

            /*
             * -----------------------------------------------------
             * PREVIOUS DEPLOYMENT
             * -----------------------------------------------------
             */
            $previous =
                OperationDeployment::query()
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
                    ->where(
                        'id',
                        '!=',
                        $deployment->id
                    )
                    ->latest('id')
                    ->first();

            /*
             * -----------------------------------------------------
             * CHANGED FILES
             * -----------------------------------------------------
             */
            $changedFiles = [];

            if (
                $previous
                && !empty($previous->commit_hash)
            ) {
                $changedFiles =
                    $this->getChangedFiles(
                        $path,
                        $previous->commit_hash,
                        $commitHash,
                        $timeout
                    );
            }

            /*
             * -----------------------------------------------------
             * INITIAL METADATA
             * -----------------------------------------------------
             */
            $metadata =
                $this->initializePipelineMetadata(
                    $deployment
                );

            $metadata['git'] = [
                'remote' => $remote,
                'branch' => $branch,
                'commit' => $commitHash,
                'previous_commit' =>
                    $previous?->commit_hash,
                'commit_message' =>
                    $commitMessage,
                'changed_files' =>
                    $changedFiles,
            ];

            $deployment->update([
                'commit_hash' => $commitHash,

                'commit_message' =>
                    $commitMessage,

                'metadata' =>
                    $metadata,
            ]);

            /*
             * -----------------------------------------------------
             * PIPELINE
             * -----------------------------------------------------
             */
            $this->runPipeline(
                $deployment,
                $path,
                $timeout
            );

            /*
             * -----------------------------------------------------
             * COMPLETE
             * -----------------------------------------------------
             */
            $fresh = $deployment->fresh();

            $duration = $fresh->started_at
                ? max(
                    0,
                    now()->getTimestamp()
                        - $fresh->started_at->getTimestamp()
                )
                : null;

            $fresh->update([
                'status' => 'completed',

                'completed_at' => now(),

                'duration_seconds' =>
                    $duration,
            ]);

            $this->auditDeployment(
                $fresh,
                'success',
                'Deployment completed successfully.'
            );

        } catch (Throwable $e) {
            $fresh = $deployment->fresh();

            $duration = $fresh->started_at
                ? max(
                    0,
                    now()->getTimestamp()
                        - $fresh->started_at->getTimestamp()
                )
                : null;

            $fresh->update([
                'status' => 'failed',

                'error' => $e->getMessage(),

                'completed_at' => now(),

                'duration_seconds' =>
                    $duration,
            ]);

            $this->auditDeployment(
                $fresh,
                'failed',
                'Deployment failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;

        } finally {
            $lock->release();
        }
    }

    /**
     * -------------------------------------------------------------
     * ROLLBACK EXECUTION
     * -------------------------------------------------------------
     */
    protected function executeRollback(
        OperationDeployment $deployment
    ): void {
        $config = $this->config();

        $path = $config['path'] ?? base_path();

        $remote = $config['remote'] ?? 'origin';

        $timeout = (int) (
            $config['timeout'] ?? 600
        );

        $lockSeconds = max(
            $timeout + 60,
            120
        );

        $lock = cache()->lock(
            'teyaqi:operations:deployment',
            $lockSeconds
        );

        if (!$lock->get()) {
            throw new RuntimeException(
                'Another deployment is already running.'
            );
        }

        try {
            /*
             * -----------------------------------------------------
             * TARGET COMMIT
             * -----------------------------------------------------
             */
            $rollback = $deployment->rollbackMetadata();

            $targetCommit =
                $rollback['target_commit'] ?? null;

            if (!$targetCommit) {
                throw new RuntimeException(
                    'Rollback target commit is missing.'
                );
            }

            /*
             * -----------------------------------------------------
             * ACTIVE DEPLOYMENT CHECK
             * -----------------------------------------------------
             */
            $otherDeployment =
                OperationDeployment::query()
                    ->whereIn(
                        'status',
                        ['pending', 'running']
                    )
                    ->where(
                        'id',
                        '!=',
                        $deployment->id
                    )
                    ->latest('id')
                    ->first();

            if ($otherDeployment) {
                throw new RuntimeException(
                    "Deployment #{$otherDeployment->id} "
                    . "is already {$otherDeployment->status}."
                );
            }

            /*
             * -----------------------------------------------------
             * INITIAL METADATA
             * -----------------------------------------------------
             */
            $metadata =
                $this->initializePipelineMetadata(
                    $deployment
                );

            $metadata['rollback'] = array_merge(
                $rollback,
                [
                    'target_commit' =>
                        $targetCommit,
                ]
            );

            $deployment->update([
                'commit_hash' => $targetCommit,

                'metadata' => $metadata,
            ]);

            /*
             * -----------------------------------------------------
             * VERIFY REPOSITORY
             * -----------------------------------------------------
             */
            $repoCheck = $this->runCommand(
                ['git', 'rev-parse', '--is-inside-work-tree'],
                $path
            );

            if (
                !$repoCheck->successful()
                || trim($repoCheck->output()) !== 'true'
            ) {
                throw new RuntimeException(
                    'Deployment path is not a valid Git repository.'
                );
            }

            /*
             * -----------------------------------------------------
             * FETCH
             * -----------------------------------------------------
             */
            $fetch = $this->runCommand(
                [
                    'git',
                    'fetch',
                    $remote,
                    '--prune',
                ],
                $path,
                $timeout
            );

            if (!$fetch->successful()) {
                throw new RuntimeException(
                    "Git fetch failed:\n"
                    . trim($fetch->errorOutput())
                );
            }

            /*
             * -----------------------------------------------------
             * VERIFY TARGET COMMIT
             * -----------------------------------------------------
             */
            $targetCheck = $this->runCommand(
                [
                    'git',
                    'cat-file',
                    '-e',
                    "{$targetCommit}^{commit}",
                ],
                $path,
                $timeout
            );

            if (!$targetCheck->successful()) {
                throw new RuntimeException(
                    "Rollback target commit {$targetCommit} "
                    . "does not exist in the repository."
                );
            }

            /*
             * -----------------------------------------------------
             * DETACHED CHECKOUT
             * -----------------------------------------------------
             */
            $checkout = $this->runCommand(
                [
                    'git',
                    'checkout',
                    '--detach',
                    $targetCommit,
                ],
                $path,
                $timeout
            );

            if (!$checkout->successful()) {
                throw new RuntimeException(
                    "Rollback checkout failed:\n"
                    . trim($checkout->errorOutput())
                );
            }

            /*
             * -----------------------------------------------------
             * VERIFY ACTUAL COMMIT
             * -----------------------------------------------------
             */
            $actualCommit = $this->runCommand(
                [
                    'git',
                    'rev-parse',
                    'HEAD',
                ],
                $path
            );

            if (!$actualCommit->successful()) {
                throw new RuntimeException(
                    'Unable to verify rollback commit.'
                );
            }

            $actualCommitHash =
                trim($actualCommit->output());

            if ($actualCommitHash !== $targetCommit) {
                throw new RuntimeException(
                    'Rollback verification failed. '
                    . "Expected {$targetCommit}, got {$actualCommitHash}."
                );
            }

            /*
             * -----------------------------------------------------
             * COMMIT MESSAGE
             * -----------------------------------------------------
             */
            $commitMessage =
                $this->getCommitMessage(
                    $path,
                    $timeout
                );

            $metadata['git'] = [
                'remote' => $remote,

                'branch' =>
                    $deployment->branch,

                'commit' =>
                    $actualCommitHash,

                'commit_message' =>
                    $commitMessage,

                'rollback' => true,
            ];

            $deployment->update([
                'commit_hash' =>
                    $actualCommitHash,

                'commit_message' =>
                    $commitMessage,

                'metadata' =>
                    $metadata,
            ]);

            /*
             * -----------------------------------------------------
             * RUN PIPELINE
             * -----------------------------------------------------
             *
             * The same normal pipeline is used.
             *
             * IMPORTANT:
             * migrations remain forward-only.
             */
            $this->runPipeline(
                $deployment,
                $path,
                $timeout
            );

            /*
             * -----------------------------------------------------
             * COMPLETE
             * -----------------------------------------------------
             */
            $fresh = $deployment->fresh();

            $duration = $fresh->started_at
                ? max(
                    0,
                    now()->getTimestamp()
                        - $fresh->started_at->getTimestamp()
                )
                : null;

            $fresh->update([
                'status' => 'completed',

                'completed_at' => now(),

                'duration_seconds' =>
                    $duration,
            ]);

            $this->auditDeployment(
                $fresh,
                'success',
                'Rollback completed successfully.'
            );

        } catch (Throwable $e) {
            $fresh = $deployment->fresh();

            $duration = $fresh->started_at
                ? max(
                    0,
                    now()->getTimestamp()
                        - $fresh->started_at->getTimestamp()
                )
                : null;

            $fresh->update([
                'status' => 'failed',

                'error' => $e->getMessage(),

                'completed_at' => now(),

                'duration_seconds' =>
                    $duration,
            ]);

            $this->auditDeployment(
                $fresh,
                'failed',
                'Rollback failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;

        } finally {
            $lock->release();
        }
    }

    /**
     * -------------------------------------------------------------
     * RUN PIPELINE
     * -------------------------------------------------------------
     */
    protected function runPipeline(
        OperationDeployment $deployment,
        string $path,
        int $timeout
    ): void {
        $config = $this->config();

        $pipeline =
            $config['pipeline'] ?? [];

        /*
         * ---------------------------------------------------------
         * COMPOSER
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'composer',
            config: $pipeline['composer'] ?? [],
            defaultCommand: [
                $config['binaries']['composer']
                    ?? 'composer',
                'install',
                '--no-interaction',
                '--prefer-dist',
                '--optimize-autoloader',
            ]
        );

        /*
         * ---------------------------------------------------------
         * NPM
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'npm',
            config: $pipeline['npm'] ?? [],
            defaultCommand: [
                $config['binaries']['npm'] ?? 'npm',
                'ci',
            ]
        );

        /*
         * ---------------------------------------------------------
         * BUILD
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'build',
            config: $pipeline['build'] ?? [],
            defaultCommand: [
                $config['binaries']['npm'] ?? 'npm',
                'run',
                'build',
            ]
        );

        /*
         * ---------------------------------------------------------
         * MIGRATIONS
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'migrations',
            config: $pipeline['migrations'] ?? [],
            defaultCommand: [
                $config['binaries']['php'] ?? 'php',
                'artisan',
                'migrate',
                '--force',
            ]
        );

        /*
         * ---------------------------------------------------------
         * OPTIMIZE
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'optimize',
            config: $pipeline['optimize'] ?? [],
            defaultCommand: [
                $config['binaries']['php'] ?? 'php',
                'artisan',
                'optimize',
            ]
        );

        /*
         * ---------------------------------------------------------
         * QUEUE RESTART
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'queue_restart',
            config: $pipeline['queue_restart'] ?? [],
            defaultCommand: [
                $config['binaries']['php'] ?? 'php',
                'artisan',
                'queue:restart',
            ]
        );

        /*
         * ---------------------------------------------------------
         * HEALTH CHECK
         * ---------------------------------------------------------
         */
        $this->runConfiguredPipelineStep(
            deployment: $deployment,
            path: $path,
            timeout: $timeout,
            name: 'health_check',
            config: $pipeline['health_check'] ?? [],
            defaultCommand: [
                $config['binaries']['php'] ?? 'php',
                'artisan',
                'about',
            ]
        );
    }

    /**
     * -------------------------------------------------------------
     * COMMIT MESSAGE
     * -------------------------------------------------------------
     */
    protected function getCommitMessage(
        string $path,
        int $timeout = 120
    ): ?string {
        $result = $this->runCommand(
            [
                'git',
                'log',
                '-1',
                '--pretty=%s',
            ],
            $path,
            $timeout
        );

        if (!$result->successful()) {
            return null;
        }

        $message = trim(
            $result->output()
        );

        return $message !== ''
            ? $message
            : null;
    }

    /**
     * -------------------------------------------------------------
     * PIPELINE METADATA
     * -------------------------------------------------------------
     */
    protected function initializePipelineMetadata(
        OperationDeployment $deployment
    ): array {
        $metadata =
            $deployment->metadata ?? [];

        $metadata['pipeline'] =
            $metadata['pipeline'] ?? [];

        $metadata['pipeline']['started_at'] =
            now()->toIso8601String();

        $metadata['pipeline']['status'] =
            'running';

        return $metadata;
    }

    /**
     * -------------------------------------------------------------
     * UPDATE PIPELINE STATUS
     * -------------------------------------------------------------
     */
    protected function updatePipelineStatus(
        OperationDeployment $deployment,
        string $name,
        string $status,
        array $data = []
    ): void {
        $metadata =
            $deployment->fresh()->metadata ?? [];

        $metadata['pipeline'] =
            $metadata['pipeline'] ?? [];

        $metadata['pipeline']['steps'] =
            $metadata['pipeline']['steps'] ?? [];

        $metadata['pipeline']['steps'][$name] =
            array_merge(
                $metadata['pipeline']['steps'][$name] ?? [],
                [
                    'status' => $status,
                    'updated_at' =>
                        now()->toIso8601String(),
                ],
                $data
            );

        if (
            in_array(
                $status,
                ['failed', 'error'],
                true
            )
        ) {
            $metadata['pipeline']['status'] =
                'failed';
        }

        $deployment->update([
            'metadata' => $metadata,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * GET CHANGED FILES
     * -------------------------------------------------------------
     */
    protected function getChangedFiles(
        string $path,
        string $fromCommit,
        string $toCommit,
        int $timeout = 120
    ): array {
        $result = $this->runCommand(
            [
                'git',
                'diff',
                '--numstat',
                $fromCommit,
                $toCommit,
            ],
            $path,
            $timeout
        );

        if (!$result->successful()) {
            return [];
        }

        return $this->parseGitNumstat(
            $result->output()
        );
    }

    /**
     * -------------------------------------------------------------
     * PARSE GIT NUMSTAT
     * -------------------------------------------------------------
     */
    protected function parseGitNumstat(
        string $output
    ): array {
        $files = [];

        $lines = preg_split(
            '/\r\n|\r|\n/',
            trim($output)
        );

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split(
                '/\s+/u',
                $line,
                3
            );

            if (count($parts) < 3) {
                continue;
            }

            $added = $parts[0];
            $deleted = $parts[1];
            $file = $this->cleanGitPath(
                $parts[2]
            );

            $files[] = [
                'file' => $file,

                'additions' =>
                    is_numeric($added)
                        ? (int) $added
                        : 0,

                'deletions' =>
                    is_numeric($deleted)
                        ? (int) $deleted
                        : 0,

                'binary' =>
                    $added === '-'
                    || $deleted === '-',
            ];
        }

        return $files;
    }

    /**
     * -------------------------------------------------------------
     * CLEAN GIT PATH
     * -------------------------------------------------------------
     */
    protected function cleanGitPath(
        string $path
    ): string {
        $path = trim($path);

        /*
         * Git rename format:
         *
         * old => new
         */
        if (
            str_contains($path, '=>')
        ) {
            $path = trim(
                str_replace(
                    ['{', '}'],
                    '',
                    $path
                )
            );

            $parts = preg_split(
                '/\s*=>\s*/',
                $path
            );

            if (
                is_array($parts)
                && count($parts) >= 2
            ) {
                return trim(
                    end($parts)
                );
            }
        }

        return trim(
            $path,
            "\"'"
        );
    }

    /**
     * -------------------------------------------------------------
     * VALIDATE BRANCH
     * -------------------------------------------------------------
     */
    protected function validateBranch(
        string $branch
    ): void {
        if (
            preg_match(
                '/^[A-Za-z0-9._\/-]+$/',
                $branch
            ) !== 1
        ) {
            throw new RuntimeException(
                'Invalid Git branch name.'
            );
        }

        if (
            str_starts_with($branch, '-')
            || str_contains($branch, '..')
            || str_contains($branch, '@{')
        ) {
            throw new RuntimeException(
                'Invalid Git branch name.'
            );
        }
    }

    /**
 * -------------------------------------------------------------
 * EXECUTABLE CHECK
 * -------------------------------------------------------------
 */
protected function executableCheck(
    string $executable,
    array $arguments = [],
    ?string $workingDirectory = null,
    string $missingStatus = 'failed'
): array {
    $result = $this->runCommand(
        array_merge(
            [$executable],
            $arguments
        ),
        $workingDirectory
    );

    if ($result->successful()) {
        return [
            'name' => $executable,

            'status' => 'passed',

            'message' => trim(
                $result->output()
            ),
        ];
    }

    $message = trim(
        $result->errorOutput()
    ) ?: "Executable '{$executable}' is not available.";

    return [
        'name' => $executable,

        'status' => $missingStatus,

        'message' => $message,
    ];
}

    /**
     * -------------------------------------------------------------
     * CONFIGURED PIPELINE STEP
     * -------------------------------------------------------------
     */
    protected function runConfiguredPipelineStep(
        OperationDeployment $deployment,
        string $path,
        int $timeout,
        string $name,
        array $config,
        array $defaultCommand
    ): void {
        $enabled =
            (bool) ($config['enabled'] ?? true);

        if (!$enabled) {
            $this->recordSkippedStep(
                $deployment,
                $name
            );

            return;
        }

        $command =
            $this->resolvePipelineCommand(
                $name,
                $config['command'] ?? null,
                $defaultCommand
            );

        $this->runDeploymentStep(
            deployment: $deployment,
            path: $path,
            timeout: (int) (
                $config['timeout'] ?? $timeout
            ),
            name: $name,
            command: $command
        );
    }

    /**
     * -------------------------------------------------------------
     * RESOLVE PIPELINE COMMAND
     * -------------------------------------------------------------
     */
    protected function resolvePipelineCommand(
        string $name,
        mixed $configuredCommand,
        array $defaultCommand
    ): array {
        if (
            is_array($configuredCommand)
            && count($configuredCommand) > 0
        ) {
            return array_values(
                array_map(
                    static fn ($value) => (string) $value,
                    $configuredCommand
                )
            );
        }

        if (
            is_string($configuredCommand)
            && trim($configuredCommand) !== ''
        ) {
            return $this->splitWindowsPath(
                $configuredCommand
            );
        }

        return $defaultCommand;
    }

    /**
     * -------------------------------------------------------------
     * RECORD SKIPPED STEP
     * -------------------------------------------------------------
     */
    protected function recordSkippedStep(
        OperationDeployment $deployment,
        string $name
    ): void {
        $this->updatePipelineStatus(
            $deployment,
            $name,
            'skipped',
            [
                'finished_at' =>
                    now()->toIso8601String(),
            ]
        );
    }

    /**
     * -------------------------------------------------------------
     * RUN DEPLOYMENT STEP
     * -------------------------------------------------------------
     */
    protected function runDeploymentStep(
        OperationDeployment $deployment,
        string $path,
        int $timeout,
        string $name,
        array $command
    ): void {
        $startedAt = microtime(true);

        $this->updatePipelineStatus(
            $deployment,
            $name,
            'running',
            [
                'started_at' =>
                    now()->toIso8601String(),

                'command' =>
                    $command,
            ]
        );

        try {
            $result = $this->runCommand(
                $command,
                $path,
                $timeout
            );

            $output =
                trim($result->output());

            $error =
                trim($result->errorOutput());

            $duration =
                round(
                    microtime(true)
                    - $startedAt,
                    3
                );

            if (!$result->successful()) {
                $this->updatePipelineStatus(
                    $deployment,
                    $name,
                    'failed',
                    [
                        'finished_at' =>
                            now()->toIso8601String(),

                        'duration_seconds' =>
                            $duration,

                        'output' =>
                            $output,

                        'error' =>
                            $error,
                    ]
                );

                throw new RuntimeException(
                    "Pipeline step '{$name}' failed."
                    . (
                        $error !== ''
                            ? "\n{$error}"
                            : ''
                    )
                );
            }

            $this->updatePipelineStatus(
                $deployment,
                $name,
                'completed',
                [
                    'finished_at' =>
                        now()->toIso8601String(),

                    'duration_seconds' =>
                        $duration,

                    'output' =>
                        $output,

                    'error' =>
                        $error,
                ]
            );

        } catch (Throwable $e) {
            $this->updatePipelineStatus(
                $deployment,
                $name,
                'failed',
                [
                    'finished_at' =>
                        now()->toIso8601String(),

                    'duration_seconds' =>
                        round(
                            microtime(true)
                            - $startedAt,
                            3
                        ),

                    'error' =>
                        $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
 * -------------------------------------------------------------
 * PROCESS ENVIRONMENT
 * -------------------------------------------------------------
 */
protected function processEnvironment(
    string $path
): array {
    $config = $this->config();

    /*
     * ---------------------------------------------------------
     * BASE ENVIRONMENT
     * ---------------------------------------------------------
     *
     * Start with the PHP process environment, then allow
     * deployment-specific variables to override it.
     */
    $environment = array_merge(
        $_ENV,
        $this->systemEnvironment(),
        $config['environment_variables'] ?? []
    );

    /*
     * ---------------------------------------------------------
     * COMPOSER
     * ---------------------------------------------------------
     *
     * Composer requires HOME or COMPOSER_HOME.
     *
     * The web/queue process does not necessarily inherit the
     * same shell environment as an SSH session, so explicitly
     * provide these values.
     */
    $environment['HOME'] =
        $environment['HOME']
        ?? getenv('HOME')
        ?? '/home/lememaar';

    $environment['COMPOSER_HOME'] =
        $environment['COMPOSER_HOME']
        ?? getenv('COMPOSER_HOME')
        ?? '/home/lememaar/.composer';

    /*
     * ---------------------------------------------------------
     * PATH
     * ---------------------------------------------------------
     *
     * Preserve the server's existing PATH.
     */
    $environment['PATH'] =
        $environment['PATH']
        ?? getenv('PATH')
        ?? '/usr/local/bin:/usr/bin:/bin';

    /*
     * ---------------------------------------------------------
     * NODE BINARY
     * ---------------------------------------------------------
     *
     * If Node is configured using an absolute path, make its
     * directory available through PATH.
     */
    $node =
        $config['binaries']['node']
        ?? null;

    if (
        is_string($node)
        && $node !== ''
        && (
            str_contains($node, '/')
            || str_contains($node, '\\')
        )
    ) {
        $nodeDirectory =
            $this->binaryDirectory($node);

        if ($nodeDirectory !== '') {
            $environment['PATH'] =
                $nodeDirectory
                . PATH_SEPARATOR
                . $environment['PATH'];
        }
    }

    return $environment;
}

/**
 * -------------------------------------------------------------
 * SYSTEM ENVIRONMENT
 * -------------------------------------------------------------
 */
protected function systemEnvironment(): array
{
    $environment = [];

    foreach (
        [
            'PATH',
            'HOME',
            'COMPOSER_HOME',
            'USER',
            'SHELL',
            'LANG',
            'LC_ALL',
        ] as $key
    ) {
        $value = getenv($key);

        if ($value !== false) {
            $environment[$key] = $value;
        }
    }

    return $environment;
}

    /**
     * -------------------------------------------------------------
     * BINARY DIRECTORY
     * -------------------------------------------------------------
     */
    protected function binaryDirectory(
        string $binary
    ): string {
        $binary = trim($binary);

        if ($binary === '') {
            return '';
        }

        $binary = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $binary
        );

        $directory = dirname($binary);

        if (
            $directory === '.'
            || $directory === DIRECTORY_SEPARATOR
        ) {
            return '';
        }

        return $directory;
    }

    /**
     * -------------------------------------------------------------
     * SPLIT WINDOWS PATH
     * -------------------------------------------------------------
     */
    protected function splitWindowsPath(
        string $command
    ): array {
        $command = trim($command);

        if ($command === '') {
            return [];
        }

        /*
         * Basic command-line tokenizer.
         *
         * Supports quoted arguments while preserving spaces
         * inside quotes.
         */
        preg_match_all(
            '/"([^"]*)"|\'([^\']*)\'|(\S+)/',
            $command,
            $matches
        );

        $tokens = [];

        foreach ($matches[0] as $index => $full) {
            if (
                isset($matches[1][$index])
                && $matches[1][$index] !== ''
            ) {
                $tokens[] =
                    $matches[1][$index];
            } elseif (
                isset($matches[2][$index])
                && $matches[2][$index] !== ''
            ) {
                $tokens[] =
                    $matches[2][$index];
            } else {
                $tokens[] =
                    $matches[3][$index];
            }
        }

        return $tokens;
    }

    /**
     * -------------------------------------------------------------
     * RUN COMMAND
     * -------------------------------------------------------------
     */
    protected function runCommand(
        array $command,
        ?string $workingDirectory = null,
        ?int $timeout = null
    ) {
        $command = array_values(
            array_map(
                static fn ($value) => (string) $value,
                $command
            )
        );

        if (empty($command)) {
            throw new RuntimeException(
                'Cannot execute an empty command.'
            );
        }

        $process = Process::path(
            $workingDirectory ?: base_path()
        );

        $environment =
            $this->processEnvironment(
                $workingDirectory ?: base_path()
            );

        if (!empty($environment)) {
            $process = $process->env(
                $environment
            );
        }

        if ($timeout !== null) {
            $process = $process->timeout(
                $timeout
            );
        }

        Log::debug(
            'Operations command execution.',
            [
                'command' =>
                    $command,

                'working_directory' =>
                    $workingDirectory,

                'timeout' =>
                    $timeout,
            ]
        );

        return $process->run(
            $command
        );
    }

    /**
     * -------------------------------------------------------------
     * FORMAT BYTES
     * -------------------------------------------------------------
     */
    protected function formatBytes(
        int|float $bytes,
        int $precision = 2
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
            'PB',
        ];

        $power = min(
            (int) floor(
                log($bytes, 1024)
            ),
            count($units) - 1
        );

        return round(
            $bytes / (1024 ** $power),
            $precision
        ) . ' ' . $units[$power];
    }

    /**
     * -------------------------------------------------------------
     * AUDIT DEPLOYMENT
     * -------------------------------------------------------------
     */
    protected function auditDeployment(
        OperationDeployment $deployment,
        string $status,
        string $description,
        array $metadata = []
    ): void {
        try {
            app(OperationAuditService::class)->log(
                action: 'deployment.execute',
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

                        'type' =>
                            $deployment->type,

                        'rollback_of' =>
                            $deployment->rollback_of,
                    ],
                    $metadata
                )
            );
        } catch (Throwable $e) {
            /*
             * Audit failure must never destroy the deployment
             * execution result.
             */
            Log::error(
                'Failed to audit deployment.',
                [
                    'deployment_id' =>
                        $deployment->id,

                    'error' =>
                        $e->getMessage(),
                ]
            );
        }
    }
}