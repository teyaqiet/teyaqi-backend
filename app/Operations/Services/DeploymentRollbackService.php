<?php

namespace App\Operations\Services;

use App\Models\OperationDeployment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Throwable;

class DeploymentRollbackService
{
    public function __construct(
        protected DeploymentLockService $lockService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Available Deployments
    |--------------------------------------------------------------------------
    */

    public function availableDeployments(
        string $environment = 'staging',
        int $limit = 20
    ) {
        return OperationDeployment::query()
            ->where('environment', $environment)
            ->where('type', 'deployment')
            ->where('status', 'completed')
            ->whereNotNull('commit_hash')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (OperationDeployment $deployment) {
                $previousCommit = data_get(
                    $deployment->metadata,
                    'git.previous_commit'
                );

                return [
                    'id' => $deployment->id,
                    'environment' => $deployment->environment,
                    'branch' => $deployment->branch,
                    'commit_hash' => $deployment->commit_hash,
                    'commit_short' => $this->shortCommit(
                        $deployment->commit_hash
                    ),
                    'commit_message' => $deployment->commit_message,
                    'created_at' => $deployment->created_at,
                    'previous_commit' => $previousCommit,
                    'previous_commit_short' => $this->shortCommit(
                        $previousCommit
                    ),
                    'rollback_available' =>
                        ! empty($previousCommit),
                ];
            })
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */

    public function preview(
        OperationDeployment $deployment
    ): array {
        if ($deployment->type !== 'deployment') {
            throw new RuntimeException(
                'Only normal deployments can be rolled back.'
            );
        }

        if ($deployment->status !== 'completed') {
            throw new RuntimeException(
                'Only completed deployments can be rolled back.'
            );
        }

        if (empty($deployment->commit_hash)) {
            throw new RuntimeException(
                'The selected deployment does not have a commit hash.'
            );
        }

        /*
         * IMPORTANT:
         *
         * The selected deployment is the deployment we want to UNDO.
         *
         * Therefore its own commit is NOT the rollback target.
         *
         * Example:
         *
         * Deployment #12
         * deployed_commit  = 49ddb1af
         * previous_commit   = 536583f7
         *
         * Rolling back #12 means:
         *
         * 49ddb1af -> 536583f7
         */
        $targetCommit = data_get(
            $deployment->metadata,
            'git.previous_commit'
        );

        if (empty($targetCommit)) {
            throw new RuntimeException(
                "Deployment #{$deployment->id} does not have a previous commit recorded, so it cannot be rolled back safely."
            );
        }

        $currentCommit = $this->currentCommit(
            $deployment
        );

        if ($currentCommit === $targetCommit) {
            throw new RuntimeException(
                'The application is already at the rollback target commit.'
            );
        }

        $this->verifyCommitExists(
            $deployment,
            $targetCommit
        );

        /*
         * Find the deployment that originally deployed
         * the rollback target commit.
         */
        $targetDeployment = OperationDeployment::query()
            ->where('environment', $deployment->environment)
            ->where('branch', $deployment->branch)
            ->where('status', 'completed')
            ->where('type', 'deployment')
            ->where('commit_hash', $targetCommit)
            ->latest('id')
            ->first();

        /*
         * Get the target commit message directly from Git.
         * This means preview still works even if the original
         * deployment record cannot be found.
         */
        $targetCommitMessage = $this->commitMessage(
            $deployment,
            $targetCommit
        );

        /*
         * Changes currently deployed by the selected deployment
         * compared with the rollback target.
         *
         * target -> current
         *
         * These are the changes that the rollback will remove.
         */
        $changedFiles = $this->getChangedFiles(
            $deployment,
            $targetCommit,
            $currentCommit
        );

        return [
            'deployment_id' => $deployment->id,

            'deployment_type' =>
                $deployment->type,

            'environment' =>
                $deployment->environment,

            'branch' =>
                $deployment->branch,

            'current_commit' =>
                $currentCommit,

            'current_commit_short' =>
                $this->shortCommit($currentCommit),

            'current_commit_message' =>
                $this->commitMessage(
                    $deployment,
                    $currentCommit
                ),

            'target_commit' =>
                $targetCommit,

            'target_commit_short' =>
                $this->shortCommit($targetCommit),

            'target_deployment_id' =>
                $targetDeployment?->id,

            'target_deployment_type' =>
                $targetDeployment?->type,

            'target_commit_message' =>
                $targetCommitMessage,

            'target_created_at' =>
                $targetDeployment?->created_at,

            'changed_files' =>
                $changedFiles,

            'locked' =>
                $this->lockService->isLocked(
                    $deployment->environment
                ),

            'lock' =>
                $this->lockService->current(
                    $deployment->environment
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Create Rollback
    |--------------------------------------------------------------------------
    */

    public function createRollback(
        OperationDeployment $target,
        ?int $triggeredBy = null
    ): OperationDeployment {
        if ($target->type !== 'deployment') {
            throw new RuntimeException(
                'Only normal deployments can be rolled back.'
            );
        }

        if ($target->status !== 'completed') {
            throw new RuntimeException(
                'Only completed deployments can be rolled back.'
            );
        }

        $targetCommit = data_get(
            $target->metadata,
            'git.previous_commit'
        );

        if (empty($targetCommit)) {
            throw new RuntimeException(
                "Deployment #{$target->id} does not have a previous commit and cannot be rolled back."
            );
        }

        $currentCommit = $this->currentCommit($target);

        if ($currentCommit === $targetCommit) {
            throw new RuntimeException(
                'The application is already at the rollback target commit.'
            );
        }

        $this->verifyCommitExists(
            $target,
            $targetCommit
        );

        /*
         * Make sure there isn't another deployment/rollback
         * running in this environment.
         */
        $lock = $this->lockService->current(
            $target->environment
        );

        if ($lock) {
            throw new RuntimeException(
                "Deployment #{$lock->deployment_id} is already running in {$target->environment}."
            );
        }

        /*
         * Find the deployment that originally deployed
         * the target commit.
         */
        $targetDeployment = OperationDeployment::query()
            ->where('environment', $target->environment)
            ->where('branch', $target->branch)
            ->where('status', 'completed')
            ->where('type', 'deployment')
            ->where('commit_hash', $targetCommit)
            ->latest('id')
            ->first();

        $metadata = [
            'rollback' => [
                'target_deployment_id' =>
                    $target->id,

                'target_deployment_type' =>
                    $target->type,

                'current_commit' =>
                    $currentCommit,

                'target_commit' =>
                    $targetCommit,

                'target_commit_message' =>
                    $this->commitMessage(
                        $target,
                        $targetCommit
                    ),

                'original_target_deployment_id' =>
                    $targetDeployment?->id,

                'created_at' =>
                    now()->toIso8601String(),
            ],

            'pipeline' =>
                $this->initializePipelineMetadata(),

            'git' => [
                'current_commit' =>
                    $currentCommit,

                'target_commit' =>
                    $targetCommit,

                'target_deployment_id' =>
                    $targetDeployment?->id,
            ],
        ];

        return OperationDeployment::create([
            'environment' =>
                $target->environment,

            'branch' =>
                $target->branch,

            /*
             * For a rollback record, commit_hash represents
             * the commit we intend to deploy/restore.
             */
            'commit_hash' =>
                $targetCommit,

            'commit_message' =>
                'Rollback to ' .
                $this->shortCommit($targetCommit),

            'status' =>
                'pending',

            'type' =>
                'rollback',

            'rollback_of' =>
                $target->id,

            'triggered_by' =>
                $triggeredBy,

            'metadata' =>
                $metadata,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Execute Rollback
    |--------------------------------------------------------------------------
    */

    public function execute(
        OperationDeployment $rollback
    ): void {
        if ($rollback->type !== 'rollback') {
            throw new RuntimeException(
                'The selected deployment is not a rollback.'
            );
        }

        if ($rollback->status !== 'pending') {
            return;
        }

        $targetDeployment = OperationDeployment::find(
            $rollback->rollback_of
        );

        if (! $targetDeployment) {
            throw new RuntimeException(
                'The deployment being rolled back could not be found.'
            );
        }

        /*
         * Resolve the REAL rollback target.
         *
         * We do NOT use:
         *
         * $targetDeployment->commit_hash
         *
         * because that is the commit we are undoing.
         */
        $targetCommit = data_get(
            $targetDeployment->metadata,
            'git.previous_commit'
        );

        if (empty($targetCommit)) {
            throw new RuntimeException(
                "Deployment #{$targetDeployment->id} does not have a previous commit."
            );
        }

        $currentCommit = $this->currentCommit(
            $rollback
        );

        if ($currentCommit === $targetCommit) {
            throw new RuntimeException(
                'The application is already at the rollback target commit.'
            );
        }

        $this->verifyCommitExists(
            $rollback,
            $targetCommit
        );

        /*
         * Acquire the same deployment lock used by
         * normal deployments.
         */
        $this->lockService->acquire(
            $rollback
        );

        $startedAt = now();

        $rollback->update([
            'status' =>
                'running',

            'started_at' =>
                $startedAt,

            'error' =>
                null,

            'output' =>
                null,
        ]);

        try {
            $this->appendOutput(
                $rollback,
                "Starting rollback #{$rollback->id}."
            );

            $this->appendOutput(
                $rollback,
                "Rolling back deployment #{$targetDeployment->id}."
            );

            $this->appendOutput(
                $rollback,
                "Current commit: {$currentCommit}"
            );

            $this->appendOutput(
                $rollback,
                "Target commit: {$targetCommit}"
            );

            /*
             * ---------------------------------------------------------
             * Git
             * ---------------------------------------------------------
             */

            $this->runStep(
                $rollback,
                'git',
                'Fetching latest Git references...',
                ['git', 'fetch', '--all', '--prune'],
                120
            );

            $this->runStep(
                $rollback,
                'git',
                'Verifying rollback commit...',
                [
                    'git',
                    'cat-file',
                    '-e',
                    "{$targetCommit}^{commit}",
                ],
                60
            );

            /*
             * Make sure the working tree is clean before
             * destructive checkout/reset operations.
             */
            $this->ensureCleanWorkingTree(
                $rollback
            );

            /*
             * Checkout the target branch first.
             */
            $branch = $rollback->branch ?: 'main';

            $this->runStep(
                $rollback,
                'git',
                "Checking out branch {$branch}...",
                [
                    'git',
                    'checkout',
                    $branch,
                ],
                60
            );

            /*
             * Reset the application to the previous commit.
             */
            $this->runStep(
                $rollback,
                'git',
                "Resetting application to {$targetCommit}...",
                [
                    'git',
                    'reset',
                    '--hard',
                    $targetCommit,
                ],
                120
            );

            /*
             * Verify that Git actually reached the target.
             */
            $deployedCommit = $this->currentCommit(
                $rollback
            );

            if ($deployedCommit !== $targetCommit) {
                throw new RuntimeException(
                    "Rollback verification failed. Expected {$targetCommit}, got {$deployedCommit}."
                );
            }

            /*
             * ---------------------------------------------------------
             * Composer
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'composer'
            );

            /*
             * ---------------------------------------------------------
             * NPM
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'npm'
            );

            /*
             * ---------------------------------------------------------
             * Frontend Build
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'build'
            );

            /*
             * ---------------------------------------------------------
             * Database Migrations
             * ---------------------------------------------------------
             *
             * IMPORTANT:
             *
             * We intentionally do NOT run:
             *
             * migrate:rollback
             *
             * because reverting database migrations automatically
             * can destroy production data/schema.
             *
             * Normal forward migrations are safe to run.
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'migrations'
            );

            /*
             * ---------------------------------------------------------
             * Optimize
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'optimize'
            );

            /*
             * ---------------------------------------------------------
             * Queue Restart
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'queue_restart'
            );

            /*
             * ---------------------------------------------------------
             * Health Check
             * ---------------------------------------------------------
             */

            $this->runConfiguredPipelineStep(
                $rollback,
                'health_check'
            );

            /*
             * Final Git verification.
             */
            $finalCommit = $this->currentCommit(
                $rollback
            );

            if ($finalCommit !== $targetCommit) {
                throw new RuntimeException(
                    "Final rollback verification failed. Expected {$targetCommit}, got {$finalCommit}."
                );
            }

            $completedAt = now();

            $duration = max(
                0,
                $completedAt->getTimestamp() -
                $startedAt->getTimestamp()
            );

            $metadata = $rollback->metadata ?? [];

            $metadata['rollback']['deployed_commit'] =
                $finalCommit;

            $metadata['rollback']['completed_at'] =
                $completedAt->toIso8601String();

            $metadata['git']['deployed_commit'] =
                $finalCommit;

            $rollback->update([
                'status' =>
                    'completed',

                'commit_hash' =>
                    $finalCommit,

                'commit_message' =>
                    $this->commitMessage(
                        $rollback,
                        $finalCommit
                    ),

                'completed_at' =>
                    $completedAt,

                'duration_seconds' =>
                    $duration,

                'metadata' =>
                    $metadata,
            ]);

            $this->appendOutput(
                $rollback,
                "Rollback completed successfully."
            );

            $this->audit(
                $rollback,
                'completed',
                "Rollback completed successfully. Restored {$finalCommit}."
            );
        } catch (Throwable $e) {
            $failedAt = now();

            $duration = max(
                0,
                $failedAt->getTimestamp() -
                $startedAt->getTimestamp()
            );

            $rollback->update([
                'status' =>
                    'failed',

                'completed_at' =>
                    $failedAt,

                'duration_seconds' =>
                    $duration,

                'error' =>
                    $e->getMessage(),
            ]);

            $this->appendOutput(
                $rollback,
                "Rollback failed: {$e->getMessage()}"
            );

            $this->audit(
                $rollback,
                'failed',
                $e->getMessage(),
                'error'
            );

            throw $e;
        } finally {
            $this->lockService->release(
                $rollback
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Pipeline
    |--------------------------------------------------------------------------
    */

    protected function initializePipelineMetadata(): array
    {
        $pipeline = config(
            'operations.deployments.pipeline',
            []
        );

        $state = [];

        /*
         * Git is always part of a rollback.
         */
        $state['git'] = [
            'enabled' => true,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
            'message' => null,
        ];

        foreach ($pipeline as $key => $config) {
            $enabled =
                $config['enabled'] ?? true;

            $state[$key] = [
                'enabled' =>
                    $enabled,

                'status' =>
                    $enabled
                        ? 'pending'
                        : 'skipped',

                'started_at' =>
                    null,

                'completed_at' =>
                    null,

                'message' =>
                    null,
            ];
        }

        return $state;
    }

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

    protected function runConfiguredPipelineStep(
        OperationDeployment $deployment,
        string $key
    ): void {
        $config =
            config(
                "operations.deployments.pipeline.{$key}",
                []
            );

        if (
            ($config['enabled'] ?? true) === false
        ) {
            $this->updatePipelineStatus(
                $deployment,
                $key,
                'skipped',
                'Step disabled.'
            );

            return;
        }

        $command =
            $config['command'] ??
            $this->defaultCommand($key);

        $timeout =
            (int) (
                $config['timeout'] ??
                config(
                    'operations.deployments.timeout',
                    600
                )
            );

        $this->runStep(
            $deployment,
            $key,
            "Running {$key}...",
            $command,
            $timeout
        );
    }

    protected function defaultCommand(
        string $key
    ): array {
        return match ($key) {
            'composer' => [
                'composer',
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
                'php',
                'artisan',
                'migrate',
                '--force',
            ],

            'optimize' => [
                'php',
                'artisan',
                'optimize',
            ],

            'queue_restart' => [
                'php',
                'artisan',
                'queue:restart',
            ],

            'health_check' => [
                'php',
                'artisan',
                'about',
            ],

            default => [
                'php',
                'artisan',
                'about',
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Run Step
    |--------------------------------------------------------------------------
    */

    protected function runStep(
        OperationDeployment $deployment,
        string $key,
        string $message,
        array $command,
        int $timeout = 600
    ): void {
        $this->updatePipelineStatus(
            $deployment,
            $key,
            'running',
            $message
        );

        $this->appendOutput(
            $deployment,
            $message
        );

        try {
            $resolvedCommand =
                $this->resolvePipelineCommand(
                    $key,
                    $command
                );

            $result =
                $this->runCommand(
                    $deployment,
                    $resolvedCommand,
                    $timeout
                );

            $output =
                trim(
                    $result->output()
                );

            $errorOutput =
                trim(
                    $result->errorOutput()
                );

            if ($output !== '') {
                $this->appendOutput(
                    $deployment,
                    $output
                );
            }

            if ($errorOutput !== '') {
                $this->appendOutput(
                    $deployment,
                    $errorOutput
                );
            }

            if (! $result->successful()) {
                $this->updatePipelineStatus(
                    $deployment,
                    $key,
                    'failed',
                    $errorOutput !== ''
                        ? $errorOutput
                        : 'Command failed.'
                );

                throw new RuntimeException(
                    $message .
                    ' failed with exit code ' .
                    $result->exitCode() .
                    '.'
                );
            }

            $this->updatePipelineStatus(
                $deployment,
                $key,
                'completed',
                "{$message} completed successfully."
            );

            $this->appendOutput(
                $deployment,
                "{$message} completed successfully."
            );
        } catch (Throwable $e) {
            $this->updatePipelineStatus(
                $deployment,
                $key,
                'failed',
                $e->getMessage()
            );

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Command Resolution
    |--------------------------------------------------------------------------
    */

    protected function resolvePipelineCommand(
        string $key,
        array $command
    ): array {
        $config =
            config(
                'operations.deployments',
                []
            );

        $binaries =
            $config['binaries'] ?? [];

        $first =
            $command[0] ?? null;

        if ($key === 'npm' || $key === 'build') {
            $node =
                $binaries['node'] ??
                'node';

            $npmCli =
                env(
                    'OPERATIONS_NPM_CLI'
                );

            if (! $npmCli) {
                $npmCli =
                    'npm';
            }

            return [
                $node,
                $npmCli,
                ...array_slice(
                    $command,
                    1
                ),
            ];
        }

        if ($first === 'composer') {
            $composer =
                $binaries['composer'] ??
                'composer';

            return [
                $composer,
                ...array_slice(
                    $command,
                    1
                ),
            ];
        }

        if ($first === 'php') {
            $php =
                $binaries['php'] ??
                'php';

            return [
                $php,
                ...array_slice(
                    $command,
                    1
                ),
            ];
        }

        return $command;
    }

    /*
    |--------------------------------------------------------------------------
    | Git
    |--------------------------------------------------------------------------
    */

    protected function currentCommit(
        OperationDeployment $deployment
    ): string {
        $result = $this->runCommand(
            $deployment,
            ['git', 'rev-parse', 'HEAD'],
            60
        );

        if (! $result->successful()) {
            throw new RuntimeException(
                'Unable to determine the current Git commit.'
            );
        }

        return trim(
            $result->output()
        );
    }

    protected function verifyCommitExists(
        OperationDeployment $deployment,
        string $commit
    ): void {
        $result = $this->runCommand(
            $deployment,
            [
                'git',
                'cat-file',
                '-e',
                "{$commit}^{commit}",
            ],
            60
        );

        if (! $result->successful()) {
            throw new RuntimeException(
                "Git commit {$commit} does not exist in the repository."
            );
        }
    }

    protected function ensureCleanWorkingTree(
        OperationDeployment $deployment
    ): void {
        $result = $this->runCommand(
            $deployment,
            [
                'git',
                'status',
                '--porcelain',
                '--untracked-files=all',
            ],
            60
        );

        if (! $result->successful()) {
            throw new RuntimeException(
                'Unable to check Git working tree status.'
            );
        }

        $status =
            trim(
                $result->output()
            );

        if ($status !== '') {
            throw new RuntimeException(
                "Rollback stopped because the Git working tree is not clean:\n{$status}"
            );
        }
    }

    protected function commitMessage(
        OperationDeployment $deployment,
        string $commit
    ): ?string {
        $result = $this->runCommand(
            $deployment,
            [
                'git',
                'log',
                '-1',
                '--pretty=%s',
                $commit,
            ],
            60
        );

        if (! $result->successful()) {
            return null;
        }

        $message =
            trim(
                $result->output()
            );

        return $message !== ''
            ? $message
            : null;
    }


protected function getChangedFiles(
    OperationDeployment $deployment,
    string $fromCommit,
    string $toCommit
): array {
    /*
     * Get name/status information first.
     *
     * Example:
     *
     * M       app/Example.php
     * A       app/NewFile.php
     * D       app/OldFile.php
     * R100    old.php    new.php
     */
    $statusResult = $this->runCommand(
        $deployment,
        [
            'git',
            'diff',
            '--name-status',
            '-M',
            $fromCommit,
            $toCommit,
        ],
        120
    );

    if (! $statusResult->successful()) {
        throw new RuntimeException(
            'Unable to determine changed files.'
        );
    }

    /*
     * Get line statistics.
     *
     * --numstat produces:
     *
     * additions    deletions    file
     *
     * Example:
     *
     * 12   4   app/Example.php
     */
    $numstatResult = $this->runCommand(
        $deployment,
        [
            'git',
            'diff',
            '--numstat',
            '-M',
            $fromCommit,
            $toCommit,
        ],
        120
    );

    if (! $numstatResult->successful()) {
        throw new RuntimeException(
            'Unable to determine changed-file statistics.'
        );
    }

    /*
     * Build a statistics lookup by path.
     */
    $statistics = [];

    $numstatLines = preg_split(
        '/\r\n|\r|\n/',
        trim(
            $numstatResult->output()
        )
    );

    foreach ($numstatLines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        /*
         * Numstat is tab-separated.
         *
         * Binary files can contain:
         *
         * -
         * -
         *
         * instead of numeric values.
         */
        $parts = preg_split(
            '/\t+/',
            $line,
            3
        );

        if (count($parts) < 3) {
            continue;
        }

        $additions =
            is_numeric($parts[0])
                ? (int) $parts[0]
                : 0;

        $deletions =
            is_numeric($parts[1])
                ? (int) $parts[1]
                : 0;

        $path = $parts[2];

        /*
         * Handle rename notation.
         *
         * Git may return:
         *
         * old => new
         */
        if (
            str_contains(
                $path,
                ' => '
            )
        ) {
            $renameParts =
                preg_split(
                    '/\s+=>\s+/',
                    $path,
                    2
                );

            $path =
                $renameParts[1] ??
                $path;
        }

        $statistics[$path] = [
            'additions' =>
                $additions,

            'deletions' =>
                $deletions,
        ];
    }

    /*
     * Parse name/status information.
     */
    $statusLines = preg_split(
        '/\r\n|\r|\n/',
        trim(
            $statusResult->output()
        )
    );

    $files = [];

    $totalAdditions = 0;
    $totalDeletions = 0;

    foreach ($statusLines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $parts = preg_split(
            '/\s+/',
            $line,
            3
        );

        $status =
            $parts[0] ?? '';

        $oldPath = null;
        $path = null;

        /*
         * Rename / copy.
         */
        if (
            str_starts_with(
                $status,
                'R'
            ) ||
            str_starts_with(
                $status,
                'C'
            )
        ) {
            $oldPath =
                $parts[1] ?? null;

            $path =
                $parts[2] ?? null;
        } else {
            $path =
                $parts[1] ?? null;
        }

        $normalizedStatus =
            $this->normalizeGitStatus(
                $status
            );

        /*
         * Find line statistics.
         */
        $fileStats =
            $statistics[$path] ??
            null;

        /*
         * For renames Git may have stored the
         * statistics under a different representation.
         */
        if (
            ! $fileStats &&
            $oldPath
        ) {
            $fileStats =
                $statistics[$oldPath] ??
                null;
        }

        $additions =
            $fileStats['additions'] ??
            0;

        $deletions =
            $fileStats['deletions'] ??
            0;

        $files[] = [
            'path' =>
                $path,

            'old_path' =>
                $oldPath,

            'status' =>
                $normalizedStatus,

            'additions' =>
                $additions,

            'deletions' =>
                $deletions,
        ];

        $totalAdditions +=
            $additions;

        $totalDeletions +=
            $deletions;
    }

    return [
        'total' =>
            count($files),

        'additions' =>
            $totalAdditions,

        'deletions' =>
            $totalDeletions,

        'files' =>
            $files,
    ];
}


    protected function normalizeGitStatus(
        string $status
    ): string {
        return match (
            strtoupper(
                substr(
                    $status,
                    0,
                    1
                )
            )
        ) {
            'A' => 'added',
            'M' => 'modified',
            'D' => 'deleted',
            'R' => 'renamed',
            'C' => 'copied',
            default => 'modified',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Process
    |--------------------------------------------------------------------------
    */

    protected function runCommand(
        OperationDeployment $deployment,
        array $command,
        int $timeout = 600
    ) {
        $path =
            config(
                'operations.deployments.path',
                base_path()
            );

        $environment =
            $this->processEnvironment();

        return Process::path($path)
            ->env($environment)
            ->timeout($timeout)
            ->run(
                $command
            );
    }

    protected function processEnvironment(): array
    {
        $basePath =
            storage_path(
                'framework/operations'
            );

        $tempPath =
            $basePath . DIRECTORY_SEPARATOR . 'tmp';

        $homePath =
            $basePath . DIRECTORY_SEPARATOR . 'home';

        $npmCachePath =
            $basePath . DIRECTORY_SEPARATOR . 'npm';

        foreach ([
            $tempPath,
            $homePath,
            $npmCachePath,
        ] as $directory) {
            if (! is_dir($directory)) {
                @mkdir(
                    $directory,
                    0777,
                    true
                );
            }
        }

        $path =
            env(
                'PATH',
                getenv('PATH') ?: ''
            );

        return [
            'APP_ENV' =>
                config(
                    'app.env'
                ),

            'NODE_OPTIONS' =>
                '--openssl-legacy-provider',

            'TEMP' =>
                $tempPath,

            'TMP' =>
                $tempPath,

            'HOME' =>
                $homePath,

            'NPM_CONFIG_CACHE' =>
                $npmCachePath,

            'PATH' =>
                $path,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Output
    |--------------------------------------------------------------------------
    */

    protected function appendOutput(
        OperationDeployment $deployment,
        string $output
    ): void {
        $existing =
            $deployment->output ?? '';

        $deployment->update([
            'output' =>
                $existing .
                ($existing !== ''
                    ? PHP_EOL
                    : '') .
                '[' .
                now()->format(
                    'Y-m-d H:i:s'
                ) .
                '] ' .
                $output,
        ]);

        $deployment->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function shortCommit(
        ?string $commit
    ): ?string {
        if (
            empty($commit)
        ) {
            return null;
        }

        return substr(
            $commit,
            0,
            8
        );
    }

    protected function audit(
        OperationDeployment $deployment,
        string $event,
        string $message,
        string $level = 'info'
    ): void {
        $level = match (
            strtolower($level)
        ) {
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
            "Rollback #{$deployment->id} [{$event}]: {$message}"
        );
    }
}