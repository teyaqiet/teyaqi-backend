<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueService
{
    public function overview(): array
    {
        $driver = config('queue.default');

        $pending = $this->pendingJobsCount();
        $failed = $this->failedJobsCount();

        return [
            'driver' => $driver,
            'connection' => $driver,
            'status' => 'healthy',
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'queue_size' => $pending,
            'failed_jobs_available' => $this->failedJobsTableExists(),
        ];
    }

    public function failedJobs(
        int $perPage = 25,
        ?string $search = null
    ) {
        if (! $this->failedJobsTableExists()) {
            return collect();
        }

        return DB::table('failed_jobs')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('uuid', 'like', "%{$search}%")
                        ->orWhere('queue', 'like', "%{$search}%")
                        ->orWhere('exception', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('failed_at')
            ->paginate($perPage);
    }

    public function findFailedJob(string $id): ?object
    {
        if (! $this->failedJobsTableExists()) {
            return null;
        }

        return DB::table('failed_jobs')
            ->where('id', $id)
            ->first();
    }

    public function retryFailedJob(string $id): bool
    {
        if (! $this->failedJobsTableExists()) {
            return false;
        }

        $job = $this->findFailedJob($id);

        if (! $job) {
            return false;
        }

        try {
            Queue::connection(config('queue.default'))
                ->pushRaw($job->payload);

            DB::table('failed_jobs')
                ->where('id', $id)
                ->delete();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteFailedJob(string $id): bool
    {
        if (! $this->failedJobsTableExists()) {
            return false;
        }

        return DB::table('failed_jobs')
            ->where('id', $id)
            ->delete() > 0;
    }

    protected function pendingJobsCount(): int
    {
        $driver = config('queue.default');

        try {
            if ($driver === 'database') {
                return DB::table(
                    config('queue.connections.database.table', 'jobs')
                )->count();
            }

            return 0;
        } catch (Throwable) {
            return 0;
        }
    }

    protected function failedJobsCount(): int
    {
        try {
            if (! $this->failedJobsTableExists()) {
                return 0;
            }

            return DB::table('failed_jobs')->count();
        } catch (Throwable) {
            return 0;
        }
    }

    protected function failedJobsTableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable('failed_jobs');
        } catch (Throwable) {
            return false;
        }
    }
}