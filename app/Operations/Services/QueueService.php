<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueService
{
    /**
     * Get queue overview.
     */
    public function overview(): array
    {
        $driver = config('queue.default');

        $pending = $this->pendingJobsCount();
        $processing = $this->processingJobsCount();
        $delayed = $this->delayedJobsCount();
        $failed = $this->failedJobsCount();

        $oldestPending = $this->oldestPendingJob();

        return [
            'driver' => $driver,

            'connection' => $driver,

            'status' => $this->determineStatus(
                $pending,
                $processing,
                $failed
            ),

            /*
             * Queue counts.
             */
            'pending_jobs' => $pending,

            'processing_jobs' => $processing,

            'delayed_jobs' => $delayed,

            'failed_jobs' => $failed,

            'queue_size' => $pending + $processing,

            /*
             * Useful queue information.
             */
            'oldest_pending_job' => $oldestPending,

            'oldest_pending_age' => $this->oldestPendingAge(
                $oldestPending
            ),

            'last_created_job_at' => $this->latestCreatedAt(),

            /*
             * Database queue information.
             */
            'queue_table' => $this->queueTable(),

            'failed_jobs_available' => $this->failedJobsTableExists(),

            'supports_database_inspection' => $driver === 'database',

            /*
             * Explain the worker model instead of pretending
             * we can detect a permanent worker from the database.
             */
            'worker_mode' => $driver === 'database'
                ? 'cron / stop-when-empty'
                : 'unknown',

            'worker_description' => $driver === 'database'
                ? 'Queue workers are triggered by the server scheduler and exit when the queue is empty.'
                : 'Worker state is not available for this queue driver.',
        ];
    }

    /**
     * Get queued jobs.
     *
     * Includes pending, processing and delayed jobs.
     */
    public function jobs(
        int $perPage = 25,
        ?string $search = null,
        string $filter = 'all'
    ) {
        if (! $this->supportsDatabaseQueue()) {
            return collect();
        }

        $table = $this->queueTable();

        $query = DB::table($table);

        /*
         * Filter jobs.
         */
        switch ($filter) {
            case 'pending':
                $query->whereNull('reserved_at')
                    ->where('available_at', '<=', now()->timestamp);
                break;

            case 'processing':
                $query->whereNotNull('reserved_at');
                break;

            case 'delayed':
                $query->whereNull('reserved_at')
                    ->where('available_at', '>', now()->timestamp);
                break;

            case 'all':
            default:
                break;
        }

        /*
         * Search by:
         *
         * - queue
         * - payload
         * - id
         */
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('queue', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $results = $query
            ->orderByRaw(
                'CASE WHEN reserved_at IS NOT NULL THEN 0 ELSE 1 END'
            )
            ->orderBy('available_at')
            ->orderBy('id')
            ->paginate($perPage);

        /*
         * Transform the raw Laravel queue payload into
         * useful information for the dashboard.
         */
        $results->getCollection()->transform(
            fn ($job) => $this->transformJob($job)
        );

        return $results;
    }

    /**
     * Find a queued job.
     */
    public function findJob(int $id): ?object
    {
        if (! $this->supportsDatabaseQueue()) {
            return null;
        }

        $job = DB::table($this->queueTable())
            ->where('id', $id)
            ->first();

        if (! $job) {
            return null;
        }

        return $this->transformJob(
            $job,
            true
        );
    }

    /**
     * Get failed jobs.
     */
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
                        ->orWhere('exception', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('failed_at')
            ->paginate($perPage);
    }

    /**
     * Find failed job.
     */
    public function findFailedJob(string $id): ?object
    {
        if (! $this->failedJobsTableExists()) {
            return null;
        }

        return DB::table('failed_jobs')
            ->where('id', $id)
            ->first();
    }

    /**
     * Retry a failed job.
     */
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
            Queue::connection(
                config('queue.default')
            )->pushRaw(
                $job->payload
            );

            DB::table('failed_jobs')
                ->where('id', $id)
                ->delete();

            return true;

        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Delete failed job.
     */
    public function deleteFailedJob(string $id): bool
    {
        if (! $this->failedJobsTableExists()) {
            return false;
        }

        return DB::table('failed_jobs')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Count pending jobs.
     */
    protected function pendingJobsCount(): int
    {
        if (! $this->supportsDatabaseQueue()) {
            return 0;
        }

        try {
            return DB::table($this->queueTable())
                ->whereNull('reserved_at')
                ->where('available_at', '<=', now()->timestamp)
                ->count();

        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Count currently reserved / processing jobs.
     */
    protected function processingJobsCount(): int
    {
        if (! $this->supportsDatabaseQueue()) {
            return 0;
        }

        try {
            return DB::table($this->queueTable())
                ->whereNotNull('reserved_at')
                ->count();

        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Count delayed jobs.
     */
    protected function delayedJobsCount(): int
    {
        if (! $this->supportsDatabaseQueue()) {
            return 0;
        }

        try {
            return DB::table($this->queueTable())
                ->whereNull('reserved_at')
                ->where('available_at', '>', now()->timestamp)
                ->count();

        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Count failed jobs.
     */
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

    /**
     * Get oldest pending job.
     */
    protected function oldestPendingJob(): ?object
    {
        if (! $this->supportsDatabaseQueue()) {
            return null;
        }

        try {
            $job = DB::table($this->queueTable())
                ->whereNull('reserved_at')
                ->where('available_at', '<=', now()->timestamp)
                ->orderBy('available_at')
                ->orderBy('id')
                ->first();

            return $job
                ? $this->transformJob($job)
                : null;

        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Calculate age of oldest pending job.
     */
    protected function oldestPendingAge(
        ?object $job
    ): ?int {
        if (! $job || ! isset($job->created_at)) {
            return null;
        }

        return max(
            0,
            now()->timestamp - (int) $job->created_at
        );
    }

    /**
     * Get latest created job timestamp.
     */
    protected function latestCreatedAt(): ?int
    {
        if (! $this->supportsDatabaseQueue()) {
            return null;
        }

        try {
            return DB::table($this->queueTable())
                ->max('created_at');

        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Transform Laravel queue database row.
     */
    protected function transformJob(
        object $job,
        bool $includePayload = false
    ): object {
        $payload = $this->decodePayload(
            $job->payload ?? null
        );

        $job->display_name =
            $payload['displayName']
            ?? $payload['job']
            ?? 'Unknown Job';

        $job->job_class =
            $payload['displayName']
            ?? $payload['job']
            ?? null;

        $job->job_uuid =
            $payload['uuid']
            ?? null;

        $job->status = $this->jobStatus($job);

        $job->attempts_count =
            isset($job->attempts)
                ? (int) $job->attempts
                : 0;

        $job->created_at_timestamp =
            isset($job->created_at)
                ? (int) $job->created_at
                : null;

        $job->available_at_timestamp =
            isset($job->available_at)
                ? (int) $job->available_at
                : null;

        $job->reserved_at_timestamp =
            isset($job->reserved_at)
                ? (int) $job->reserved_at
                : null;

        $job->created_at_human =
            $this->timestampToDate(
                $job->created_at_timestamp
            );

        $job->available_at_human =
            $this->timestampToDate(
                $job->available_at_timestamp
            );

        $job->reserved_at_human =
            $this->timestampToDate(
                $job->reserved_at_timestamp
            );

        if ($includePayload) {
            $job->decoded_payload = $payload;
        } else {
            unset($job->payload);
        }

        return $job;
    }

    /**
     * Determine queue job status.
     */
    protected function jobStatus(object $job): string
    {
        if (
            isset($job->reserved_at) &&
            $job->reserved_at !== null
        ) {
            return 'processing';
        }

        if (
            isset($job->available_at) &&
            (int) $job->available_at > now()->timestamp
        ) {
            return 'delayed';
        }

        return 'pending';
    }

    /**
     * Decode queue payload.
     */
    protected function decodePayload(
        ?string $payload
    ): array {
        if (! $payload) {
            return [];
        }

        try {
            $decoded = json_decode(
                $payload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded)
                ? $decoded
                : [];

        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Convert timestamp to ISO string.
     */
    protected function timestampToDate(
        ?int $timestamp
    ): ?string {
        if (! $timestamp) {
            return null;
        }

        try {
            return now()
                ->setTimestamp($timestamp)
                ->toIso8601String();

        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Determine overall queue health.
     */
    protected function determineStatus(
        int $pending,
        int $processing,
        int $failed
    ): string {
        /*
         * Failed jobs don't necessarily mean the queue
         * itself is broken.
         */
        if ($pending > 0 && $processing === 0) {
            return 'attention';
        }

        if ($failed > 0) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Determine whether the current queue driver can
     * be inspected through the database queue table.
     */
    protected function supportsDatabaseQueue(): bool
    {
        return config('queue.default') === 'database';
    }

    /**
     * Get configured queue table.
     */
    protected function queueTable(): string
    {
        return config(
            'queue.connections.database.table',
            'jobs'
        );
    }

    /**
     * Check failed jobs table.
     */
    protected function failedJobsTableExists(): bool
    {
        try {
            return DB::getSchemaBuilder()
                ->hasTable('failed_jobs');

        } catch (Throwable) {
            return false;
        }
    }
}