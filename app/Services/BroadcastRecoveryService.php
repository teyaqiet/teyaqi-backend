<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\BroadcastRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastRecoveryService
{
    /**
     * Recipients stuck in queued state for this long
     * are considered abandoned.
     */
    protected int $queuedTimeoutMinutes = 10;

    /**
     * Recipients stuck in sending state for this long
     * are considered abandoned.
     */
    protected int $sendingTimeoutMinutes = 5;

    /**
     * Maximum number of recovery attempts.
     *
     * This is intentionally separate from the queue job's
     * $tries because the recipient may have been recovered
     * after a worker/server failure.
     */
    protected int $maxAttempts = 4;

    /**
     * Recover stale broadcast recipients.
     */
    public function recover(): array
    {
        $queuedRecovered = $this->recoverStaleQueued();
        $sendingRecovered = $this->recoverStaleSending();

        $broadcastsRestarted = $this->restartAffectedBroadcasts();

        return [
            'queued_recovered' => $queuedRecovered,
            'sending_recovered' => $sendingRecovered,
            'broadcasts_restarted' => $broadcastsRestarted,
        ];
    }

    /**
     * Recover recipients that were queued but whose
     * jobs apparently never started.
     */
    protected function recoverStaleQueued(): int
    {
        $cutoff = now()->subMinutes(
            $this->queuedTimeoutMinutes
        );

        $count = 0;

        BroadcastRecipient::query()
            ->where('status', 'queued')
            ->where('updated_at', '<=', $cutoff)
            ->whereHas('broadcast', function ($query) {
                $query->whereIn('status', [
                    'prepared',
                    'sending',
                ]);
            })
            ->orderBy('id')
            ->chunkById(
                500,
                function ($recipients) use (&$count) {
                    foreach ($recipients as $recipient) {
                        if ($this->recoverRecipient($recipient)) {
                            $count++;
                        }
                    }
                }
            );

        return $count;
    }

    /**
     * Recover recipients that were being sent when
     * the worker disappeared or timed out.
     */
    protected function recoverStaleSending(): int
    {
        $cutoff = now()->subMinutes(
            $this->sendingTimeoutMinutes
        );

        $count = 0;

        BroadcastRecipient::query()
            ->where('status', 'sending')
            ->where('updated_at', '<=', $cutoff)
            ->whereHas('broadcast', function ($query) {
                $query->whereIn('status', [
                    'prepared',
                    'sending',
                ]);
            })
            ->orderBy('id')
            ->chunkById(
                500,
                function ($recipients) use (&$count) {
                    foreach ($recipients as $recipient) {
                        if ($this->recoverRecipient($recipient)) {
                            $count++;
                        }
                    }
                }
            );

        return $count;
    }

    /**
     * Recover one stale recipient.
     */
    protected function recoverRecipient(
        BroadcastRecipient $recipient
    ): bool {
        return DB::transaction(function () use ($recipient) {
            $fresh = BroadcastRecipient::query()
                ->lockForUpdate()
                ->find($recipient->id);

            if (!$fresh) {
                return false;
            }

            /**
             * Another worker may have completed the
             * recipient while recovery was running.
             */
            if ($fresh->status === 'sent') {
                return false;
            }

            /**
             * Only stale active states can be recovered.
             */
            if (!in_array(
                $fresh->status,
                [
                    'queued',
                    'sending',
                ],
                true
            )) {
                return false;
            }

            /**
             * Make sure the parent broadcast still exists
             * and is active.
             */
            $broadcast = Broadcast::query()
                ->find($fresh->broadcast_id);

            if (!$broadcast) {
                $fresh->update([
                    'status' => 'failed',
                    'error_message' => 'Broadcast no longer exists.',
                ]);

                return true;
            }

            /**
             * Never resurrect a cancelled broadcast.
             */
            if ($broadcast->status === 'cancelled') {
                $fresh->update([
                    'status' => 'failed',
                    'error_message' => 'Broadcast was cancelled.',
                ]);

                return true;
            }

            /**
             * If the broadcast is already terminal, don't
             * send this recipient again.
             */
            if (in_array(
                $broadcast->status,
                [
                    'completed',
                    'failed',
                ],
                true
            )) {
                return false;
            }

            /**
             * If the recipient has already consumed its
             * allowed attempts, permanently fail it.
             */
            if ((int) $fresh->attempts >= $this->maxAttempts) {
                $fresh->update([
                    'status' => 'failed',
                    'error_message' => 'Recipient exceeded the maximum number of delivery attempts.',
                ]);

                Log::warning(
                    'Broadcast recipient permanently failed during recovery.',
                    [
                        'recipient_id' => $fresh->id,
                        'broadcast_id' => $fresh->broadcast_id,
                        'attempts' => $fresh->attempts,
                    ]
                );

                return true;
            }

            /**
             * Return the recipient to the pending pool.
             *
             * The coordinator will pick it up again.
             */
            $fresh->update([
                'status' => 'pending',
                'error_message' => 'Recipient delivery was recovered after becoming stale.',
            ]);

            Log::warning(
                'Stale broadcast recipient recovered.',
                [
                    'recipient_id' => $fresh->id,
                    'broadcast_id' => $fresh->broadcast_id,
                    'previous_status' => $recipient->status,
                    'attempts' => $fresh->attempts,
                ]
            );

            return true;
        });
    }

    /**
     * Restart broadcasts that have pending recipients but
     * no active coordinator is currently progressing them.
     *
     * We only normalize their status here.
     *
     * The coordinator itself is dispatched separately.
     */
    protected function restartAffectedBroadcasts(): int
    {
        $count = 0;

        Broadcast::query()
            ->whereIn('status', [
                'prepared',
                'sending',
            ])
            ->whereHas('recipients', function ($query) {
                $query->where('status', 'pending');
            })
            ->orderBy('id')
            ->chunkById(
                100,
                function ($broadcasts) use (&$count) {
                    foreach ($broadcasts as $broadcast) {
                        /**
                         * Make sure it is in sending state.
                         */
                        if ($broadcast->status !== 'sending') {
                            $broadcast->update([
                                'status' => 'sending',
                            ]);
                        }

                        /**
                         * Dispatch a coordinator.
                         *
                         * ShouldBeUnique prevents duplicate
                         * coordinators for this broadcast.
                         */
                        ProcessBroadcastBatch::dispatch(
                            $broadcast->id,
                            100
                        )
                            ->onQueue('broadcasts');

                        $count++;
                    }
                }
            );

        return $count;
    }
}