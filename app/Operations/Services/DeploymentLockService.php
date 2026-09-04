<?php

namespace App\Operations\Services;

use App\Models\OperationDeployment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeploymentLockService
{
    /**
     * Default lock lifetime in minutes.
     */
    protected int $lockMinutes = 30;

    /**
     * Remove expired locks.
     */
    public function cleanupExpired(): void
    {
        DB::table('operation_deployment_locks')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Check whether an active deployment lock exists.
     */
    public function isLocked(string $environment = 'staging'): bool
    {
        $this->cleanupExpired();

        return DB::table('operation_deployment_locks')
            ->where('environment', $environment)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get the currently active deployment lock.
     */
    public function current(string $environment = 'staging'): ?object
    {
        $this->cleanupExpired();

        return DB::table('operation_deployment_locks')
            ->where('environment', $environment)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Acquire a deployment lock.
     *
     * @throws RuntimeException
     */
    public function acquire(
        OperationDeployment $deployment,
        ?int $minutes = null
    ): object {
        $environment = $deployment->environment;

        $this->cleanupExpired();

        return DB::transaction(function () use ($deployment, $environment, $minutes) {
            $existing = DB::table('operation_deployment_locks')
                ->where('environment', $environment)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new RuntimeException(
                    "Deployment #{$existing->deployment_id} is already running."
                );
            }

            $lockMinutes = $minutes ?? $this->lockMinutes;

            $lockId = DB::table('operation_deployment_locks')->insertGetId([
                'deployment_id' => $deployment->id,
                'environment' => $environment,
                'locked_at' => now(),
                'expires_at' => now()->addMinutes($lockMinutes),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('operation_deployment_locks')
                ->where('id', $lockId)
                ->first();
        });
    }

    /**
     * Release the lock belonging to a deployment.
     */
    public function release(
        OperationDeployment $deployment
    ): bool {
        return DB::table('operation_deployment_locks')
            ->where('deployment_id', $deployment->id)
            ->delete() > 0;
    }

    /**
     * Extend an existing deployment lock.
     */
    public function extend(
        OperationDeployment $deployment,
        ?int $minutes = null
    ): bool {
        $lockMinutes = $minutes ?? $this->lockMinutes;

        return DB::table('operation_deployment_locks')
            ->where('deployment_id', $deployment->id)
            ->where('expires_at', '>', now())
            ->update([
                'expires_at' => now()->addMinutes($lockMinutes),
                'updated_at' => now(),
            ]) > 0;
    }

    /**
     * Get lock information for a deployment.
     */
    public function forDeployment(
        OperationDeployment $deployment
    ): ?object {
        return DB::table('operation_deployment_locks')
            ->where('deployment_id', $deployment->id)
            ->where('expires_at', '>', now())
            ->first();
    }
}