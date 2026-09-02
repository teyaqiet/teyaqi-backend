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
         *
         * Only check Git if the directory exists.
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
        $gitVersion = $this->runCommand(
            [
                'git',
                '--version',
            ],
            $config['path'],
            30
        );

        $checks['git'] = [
            'status' => $gitVersion->successful()
                ? 'healthy'
                : 'failed',

            'message' => $gitVersion->successful()
                ? trim($gitVersion->output())
                : 'Git is unavailable.',

            'output' => trim(
                $gitVersion->output()
            ),

            'error' => trim(
                $gitVersion->errorOutput()
            ),
        ];

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
         * Overall preflight result.
         */
        $healthy = collect($checks)
            ->every(
                fn ($check) =>
                    $check['status'] === 'healthy'
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
     *
     * Deployment creation itself is protected by an atomic lock
     * so two administrators cannot create competing deployments
     * at exactly the same time.
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
         *
         * We don't hold this lock during the actual deployment.
         * The execution lock below handles that.
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
     * A global atomic lock guarantees that only one deployment
     * can modify the deployment directory at a time.
     */
    public function execute(
        OperationDeployment $deployment
    ): OperationDeployment {
        $config = $this->config();

        /*
         * Keep the lock for longer than the maximum deployment
         * execution time so it cannot expire during a deployment.
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
         * Do not wait for another deployment.
         *
         * The queue worker should fail this job immediately and
         * the deployment will remain pending only if we don't
         * handle this carefully.
         */
        if (! $lock->get()) {
            throw new RuntimeException(
                'Another deployment is currently running. Please wait until it finishes.'
            );
        }

        try {
            /*
             * Re-fetch the deployment after acquiring the lock.
             *
             * This prevents stale model data from being used.
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
             * Double-check active deployments.
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

            /*
             * Audit execution start.
             */
            $this->auditDeployment(
                $deployment,
                'deployment.started',
                'Deployment execution started.',
                'success'
            );

            $output = [];

            try {
                /*
                 * Step 1:
                 * Verify Git repository.
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
                    $config['path']
                );

                /*
                 * Step 2:
                 * Fetch remote changes.
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
                    $config['path']
                );

                /*
                 * Step 3:
                 * Checkout deployment branch.
                 */
                $this->runDeploymentStep(
                    $output,
                    $deployment,
                    'Checkout deployment branch',
                    [
                        'git',
                        'checkout',
                        $deployment->branch,
                    ],
                    $config['path']
                );

                /*
                 * Step 4:
                 * Pull latest code.
                 */
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
                    $config['path']
                );

                /*
                 * Step 5:
                 * Refresh deployed commit hash.
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

                /*
                 * Step 6:
                 * Refresh deployed commit message.
                 */
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

                /*
                 * Deployment completed.
                 */
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
                    'Deployment completed successfully.',
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
                    'Deployment execution failed.',
                    'failed',
                    [
                        'error' => $exception->getMessage(),
                    ]
                );

                Log::error(
                    'Operations deployment failed.',
                    [
                        'deployment_id' => $deployment->id,

                        'environment' =>
                            $deployment->environment,

                        'branch' =>
                            $deployment->branch,

                        'exception' => $exception,
                    ]
                );

                throw $exception;
            }
        } finally {
            /*
             * Always release the execution lock.
             */
            $lock->release();
        }
    }

    /**
     * Validate a deployment branch.
     *
     * Prevents malformed Git references and command-like values
     * from entering the deployment pipeline.
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
         * Reject values that begin with a dash because Git may
         * interpret them as command options.
         */
        if (str_starts_with($branch, '-')) {
            throw new RuntimeException(
                'Invalid deployment branch.'
            );
        }

        /*
         * Reject whitespace and shell/control characters.
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
         * Only allow normal Git branch characters.
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
        string $path
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
            (int) config(
                'operations.deployments.timeout',
                600
            )
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