<?php

namespace App\Operations\Services;

use App\Models\OperationDeployment;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
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
            'enabled' => (bool) config('operations.deployments.enabled', false),
            'environment' => config(
                'operations.deployments.environment',
                'staging'
            ),
            'branch' => config(
                'operations.deployments.branch',
                'staging'
            ),
            'path' => config(
                'operations.deployments.path',
                base_path()
            ) ?: base_path(),
            'timeout' => (int) config(
                'operations.deployments.timeout',
                600
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
                    ['pending', 'running']
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
                fn ($query) => $query->where('status', $status)
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
         * Operations Center deployment feature.
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
         * Git repository.
         */
        $gitCheck = $this->runCommand(
            ['git', 'rev-parse', '--is-inside-work-tree'],
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
            'output' => trim($gitCheck->output()),
            'error' => trim($gitCheck->errorOutput()),
        ];

        /*
         * Git executable.
         */
        $gitVersion = $this->runCommand(
            ['git', '--version'],
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
        ];

        /*
         * Deployment directory.
         */
        $checks['directory'] = [
            'status' => is_dir($config['path'])
                ? 'healthy'
                : 'failed',
            'message' => is_dir($config['path'])
                ? 'Deployment directory exists.'
                : 'Deployment directory does not exist.',
        ];

        /*
         * Another deployment running?
         */
        $activeDeployment = OperationDeployment::query()
            ->whereIn('status', ['pending', 'running'])
            ->exists();

        $checks['no_active_deployment'] = [
            'status' => ! $activeDeployment
                ? 'healthy'
                : 'failed',
            'message' => ! $activeDeployment
                ? 'No deployment is currently running.'
                : 'Another deployment is already running.',
        ];

        $healthy = collect($checks)
            ->every(fn ($check) => $check['status'] === 'healthy');

        return [
            'status' => $healthy ? 'healthy' : 'failed',
            'ready' => $healthy,
            'checks' => $checks,
        ];
    }

    /**
     * Create a pending deployment record.
     */
    public function createDeployment(
        ?string $branch = null,
        ?string $environment = null,
        ?int $adminUserId = null
    ): OperationDeployment {
        $config = $this->config();

        if (! $config['enabled']) {
            throw new RuntimeException(
                'Deployment system is disabled.'
            );
        }

        $preflight = $this->preflight();

        if (! $preflight['ready']) {
            throw new RuntimeException(
                'Deployment preflight checks failed.'
            );
        }

        $branch ??= $config['branch'];
        $environment ??= $config['environment'];

        /*
         * Get the commit we are currently deploying.
         */
        $commitHash = null;
        $commitMessage = null;

        $commit = $this->runCommand(
            ['git', 'rev-parse', 'HEAD'],
            $config['path'],
            30
        );

        if ($commit->successful()) {
            $commitHash = trim($commit->output());
        }

        $message = $this->runCommand(
            ['git', 'log', '-1', '--pretty=%s'],
            $config['path'],
            30
        );

        if ($message->successful()) {
            $commitMessage = trim($message->output());
        }

        return OperationDeployment::create([
            'environment' => $environment,
            'branch' => $branch,
            'commit_hash' => $commitHash,
            'commit_message' => $commitMessage,
            'status' => 'pending',
            'triggered_by' => $adminUserId,
            'metadata' => [
                'deployment_path' => $config['path'],
                'triggered_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Execute a deployment.
     *
     * This method intentionally contains only predefined commands.
     * No arbitrary command from the browser is accepted.
     */
    public function execute(
        OperationDeployment $deployment
    ): OperationDeployment {
        $config = $this->config();

        $startedAt = now();

        $deployment->update([
            'status' => 'running',
            'started_at' => $startedAt,
            'error' => null,
        ]);

        $output = [];

        try {
            /*
             * Step 1: Verify repository.
             */
            $this->runDeploymentStep(
                $output,
                $deployment,
                'Verify Git repository',
                ['git', 'rev-parse', '--is-inside-work-tree'],
                $config['path']
            );

            /*
             * Step 2: Fetch latest remote changes.
             */
            $this->runDeploymentStep(
                $output,
                $deployment,
                'Fetch Git changes',
                ['git', 'fetch', '--all', '--prune'],
                $config['path']
            );

            /*
             * Step 3: Checkout configured branch.
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
             * Step 4: Pull latest code.
             */
            $this->runDeploymentStep(
                $output,
                $deployment,
                'Pull latest code',
                [
                    'git',
                    'pull',
                    '--ff-only',
                    'origin',
                    $deployment->branch,
                ],
                $config['path']
            );

            /*
             * Refresh the actual deployed commit.
             */
            $commit = $this->runCommand(
                ['git', 'rev-parse', 'HEAD'],
                $config['path'],
                30
            );

            if ($commit->successful()) {
                $deployment->update([
                    'commit_hash' => trim($commit->output()),
                ]);
            }

            $message = $this->runCommand(
                ['git', 'log', '-1', '--pretty=%s'],
                $config['path'],
                30
            );

            if ($message->successful()) {
                $deployment->update([
                    'commit_message' => trim($message->output()),
                ]);
            }

            /*
             * Save successful deployment.
             */
            $completedAt = now();

            $deployment->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
                'duration_seconds' => $startedAt->diffInSeconds(
                    $completedAt
                ),
                'output' => implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
            ]);

            return $deployment->fresh();
        } catch (Throwable $exception) {
            $completedAt = now();

            $deployment->update([
                'status' => 'failed',
                'completed_at' => $completedAt,
                'duration_seconds' => $startedAt->diffInSeconds(
                    $completedAt
                ),
                'output' => implode(
                    PHP_EOL . PHP_EOL,
                    $output
                ),
                'error' => $exception->getMessage(),
            ]);

            Log::error(
                'Operations deployment failed.',
                [
                    'deployment_id' => $deployment->id,
                    'environment' => $deployment->environment,
                    'branch' => $deployment->branch,
                    'exception' => $exception,
                ]
            );

            throw $exception;
        }
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
        $output[] = '[' . now()->format('Y-m-d H:i:s') . '] ' . $label;

        $result = $this->runCommand(
            $command,
            $path,
            (int) config(
                'operations.deployments.timeout',
                600
            )
        );

        if ($result->output()) {
            $output[] = trim($result->output());
        }

        if ($result->errorOutput()) {
            $output[] = trim($result->errorOutput());
        }

        /*
         * Persist progress while deployment is running.
         */
        $deployment->update([
            'output' => implode(
                PHP_EOL . PHP_EOL,
                $output
            ),
        ]);

        if (! $result->successful()) {
            throw new RuntimeException(
                "{$label} failed: " .
                trim($result->errorOutput() ?: $result->output())
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