<?php

namespace App\Jobs;

use App\Models\OperationDeployment;
use App\Operations\Services\DeploymentLockService;
use App\Operations\Services\DeploymentRollbackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteRollbackJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Rollbacks are intentionally not retried automatically.
     *
     * A rollback can perform destructive Git operations,
     * so an automatic retry could create an unsafe state.
     */
    public int $tries = 1;

    /**
     * Use the same deployment timeout as normal deployments.
     */
    public int $timeout;

    public function __construct(
        public int $deploymentId
    ) {
        $this->timeout = (int) config(
            'operations.deployments.timeout',
            600
        );
    }

    /**
     * Execute the rollback.
     */
    public function handle(
        DeploymentRollbackService $rollbackService
    ): void {
        $deployment = OperationDeployment::find(
            $this->deploymentId
        );

        /*
         * The deployment may have been deleted before
         * the queued job was processed.
         */
        if (! $deployment) {
            return;
        }

        /*
         * Never execute the same rollback twice.
         */
        if ($deployment->status !== 'pending') {
            return;
        }

        /*
         * The service is responsible for:
         *
         * - acquiring the deployment lock
         * - executing the rollback
         * - updating progress
         * - releasing the lock
         * - marking success/failure
         */
        $rollbackService->execute(
            $deployment
        );
    }

    /**
     * Handle a queue-level failure.
     *
     * This catches failures such as:
     *
     * - job timeout
     * - worker failure
     * - unhandled exception escaping the service
     */
    public function failed(
        Throwable $exception
    ): void {
        $deployment =
            OperationDeployment::find(
                $this->deploymentId
            );

        if (! $deployment) {
            return;
        }

        /*
         * Always release the deployment lock.
         */
        app(
            DeploymentLockService::class
        )->release(
            $deployment
        );

        $completedAt = now();

        $duration =
            $deployment->started_at
                ? max(
                    0,
                    $completedAt->getTimestamp() -
                    $deployment
                        ->started_at
                        ->getTimestamp()
                )
                : null;

        $deployment->update([
            'status' =>
                'failed',

            'error' =>
                $exception->getMessage(),

            'completed_at' =>
                $completedAt,

            'duration_seconds' =>
                $duration,
        ]);
    }
}