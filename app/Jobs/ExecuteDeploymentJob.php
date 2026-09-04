<?php

namespace App\Jobs;

use App\Models\OperationDeployment;
use App\Operations\Services\DeploymentLockService;
use App\Operations\Services\DeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExecuteDeploymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public int $deploymentId
    ) {
        $this->timeout = (int) config(
            'operations.deployments.timeout',
            600
        );
    }

    public function handle(
        DeploymentService $deploymentService,
        DeploymentLockService $deploymentLockService
    ): void {
        $deployment = OperationDeployment::find(
            $this->deploymentId
        );

        if (!$deployment) {
            return;
        }

        /*
         * Do not execute deployments that are no longer pending.
         */
        if ($deployment->status !== 'pending') {
            return;
        }

        /*
         * Acquire the deployment lock.
         *
         * This prevents two deployments from running
         * against the same environment at the same time.
         */
        $deploymentLockService->acquire($deployment);

        try {
            /*
             * Execute the actual deployment.
             */
            $deploymentService->execute($deployment);
        } finally {
            /*
             * Always release the lock.
             *
             * This runs when the deployment succeeds,
             * fails, or throws an exception.
             */
            $deploymentLockService->release($deployment);
        }
    }

    public function failed(Throwable $exception): void
    {
        $deployment = OperationDeployment::find(
            $this->deploymentId
        );

        if (!$deployment) {
            return;
        }

        /*
         * Safety cleanup.
         *
         * If Laravel marks the job as failed after an
         * exception/timeout, make sure its lock is removed.
         */
        app(DeploymentLockService::class)->release($deployment);

        $deployment->update([
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'completed_at' => now(),
            'duration_seconds' => $deployment->started_at
                ? max(
                    0,
                    now()->getTimestamp() -
                    $deployment->started_at->getTimestamp()
                )
                : null,
        ]);
    }
}