<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessBroadcastBatch implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of attempts for the coordinator.
     */
    public int $tries = 3;

    /**
     * Maximum execution time.
     */
    public int $timeout = 60;

    /**
     * How long the unique lock should remain.
     *
     * This prevents multiple coordinators for the same
     * broadcast from running at the same time.
     */
    public int $uniqueFor = 300;

    /**
     * Number of recipients processed per batch.
     */
    public int $batchSize;

    /**
     * Delay before the coordinator checks for the next batch.
     *
     * This prevents the coordinator from flooding the queue
     * with thousands of jobs immediately.
     */
    public int $continuationDelay = 1;

    /**
     * Create a new job.
     */
    public function __construct(
        public int $broadcastId,
        int $batchSize = 100
    ) {
        $this->batchSize = max(1, $batchSize);

        $this->onQueue('broadcasts');
    }

    /**
     * Unique identifier.
     *
     * Only one coordinator can exist for a broadcast
     * at the same time.
     */
    public function uniqueId(): string
    {
        return 'broadcast-batch:' . $this->broadcastId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $broadcast = Broadcast::query()
            ->find($this->broadcastId);

        /**
         * Broadcast no longer exists.
         */
        if (!$broadcast) {
            return;
        }

        /**
         * Only active broadcasts may be processed.
         */
        if (!$this->isActiveBroadcast($broadcast)) {
            return;
        }

        /**
         * Make sure the broadcast is in sending state.
         */
        $this->markAsSending($broadcast);

        /**
         * Atomically claim the next batch.
         */
        $recipientIds = $this->claimNextBatch(
            $broadcast
        );

        /**
         * Nothing else is pending.
         *
         * There may still be queued/sending recipient jobs,
         * so finalization checks all recipient states.
         */
        if ($recipientIds === []) {
            $this->finalizeIfFinished($broadcast);

            return;
        }

        /**
         * Dispatch the claimed recipients.
         */
        $this->dispatchRecipients(
            $recipientIds
        );

        /**
         * Continue processing the next batch.
         *
         * The continuation is delayed slightly so the queue
         * does not get flooded by the coordinator itself.
         */
        $this->dispatchNextBatch();
    }

    /**
     * Determine whether the broadcast is currently active.
     */
    protected function isActiveBroadcast(
        Broadcast $broadcast
    ): bool {
        return in_array(
            $broadcast->status,
            [
                'prepared',
                'sending',
            ],
            true
        );
    }

    /**
     * Mark the broadcast as sending.
     */
    protected function markAsSending(
        Broadcast $broadcast
    ): void {
        if ($broadcast->status === 'sending') {
            return;
        }

        $broadcast->update([
            'status' => 'sending',
        ]);

        $broadcast->refresh();
    }

    /**
     * Atomically claim the next batch of pending recipients.
     *
     * Returns recipient IDs that were successfully claimed.
     */
    protected function claimNextBatch(
        Broadcast $broadcast
    ): array {
        return DB::transaction(function () use ($broadcast) {
            /**
             * Lock the pending rows while selecting them.
             *
             * This protects against another process attempting
             * to claim the same recipients.
             */
            $recipients = BroadcastRecipient::query()
                ->where('broadcast_id', $broadcast->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->limit($this->batchSize)
                ->lockForUpdate()
                ->get();

            if ($recipients->isEmpty()) {
                return [];
            }

            $recipientIds = $recipients
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            /**
             * Claim all selected recipients while still
             * inside the transaction.
             */
            BroadcastRecipient::query()
                ->whereIn('id', $recipientIds)
                ->where('broadcast_id', $broadcast->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'queued',
                    'error_message' => null,
                ]);

            return $recipientIds;
        });
    }

    /**
     * Dispatch recipient jobs.
     */
    protected function dispatchRecipients(
        array $recipientIds
    ): void {
        foreach ($recipientIds as $recipientId) {
            SendBroadcastRecipient::dispatch(
                $recipientId
            )
                ->onQueue('broadcasts')
                ->afterCommit();
        }
    }

    /**
     * Schedule another coordinator pass.
     */
    protected function dispatchNextBatch(): void
    {
        self::dispatch(
            $this->broadcastId,
            $this->batchSize
        )
            ->delay(
                now()->addSeconds(
                    max(0, $this->continuationDelay)
                )
            )
            ->onQueue('broadcasts');
    }

    /**
     * Finalize the broadcast if all recipients have
     * reached a terminal state.
     */
    protected function finalizeIfFinished(
        Broadcast $broadcast
    ): void {
        $broadcast->refresh();

        /**
         * Do not finalize cancelled broadcasts.
         */
        if ($broadcast->status === 'cancelled') {
            return;
        }

        /**
         * Do not finalize already completed/failed broadcasts.
         */
        if (in_array(
            $broadcast->status,
            [
                'completed',
                'failed',
            ],
            true
        )) {
            return;
        }

        /**
         * If any recipient is still active, the broadcast
         * is not finished.
         */
        $hasActiveRecipients = $broadcast
            ->recipients()
            ->whereIn(
                'status',
                [
                    'pending',
                    'queued',
                    'sending',
                ]
            )
            ->exists();

        if ($hasActiveRecipients) {
            /**
             * Make sure the broadcast remains in sending state.
             */
            if ($broadcast->status !== 'sending') {
                $broadcast->update([
                    'status' => 'sending',
                ]);
            }

            return;
        }

        /**
         * Check for failed recipients.
         */
        $hasFailedRecipients = $broadcast
            ->recipients()
            ->where('status', 'failed')
            ->exists();

        if ($hasFailedRecipients) {
            $broadcast->update([
                'status' => 'failed',
                'sent_at' => $broadcast->sent_at ?? now(),
            ]);

            return;
        }

        /**
         * No active or failed recipients remain.
         *
         * The broadcast successfully completed.
         */
        $broadcast->update([
            'status' => 'completed',
            'sent_at' => $broadcast->sent_at ?? now(),
        ]);
    }

    /**
     * Handle a failed coordinator job.
     *
     * We deliberately do not mark recipients as failed here.
     * The coordinator may fail before processing them.
     */
    public function failed(
        Throwable $exception
    ): void {
        $broadcast = Broadcast::query()
            ->find($this->broadcastId);

        if (!$broadcast) {
            return;
        }

        /**
         * Never overwrite a terminal state.
         */
        if (in_array(
            $broadcast->status,
            [
                'completed',
                'failed',
                'cancelled',
            ],
            true
        )) {
            return;
        }

        /**
         * Keep the broadcast active.
         *
         * The coordinator may be retried by Laravel.
         */
        $broadcast->update([
            'status' => 'sending',
        ]);
    }
}