<?php

namespace App\Jobs;

use App\Exceptions\TelegramRetryableException;
use App\Models\BroadcastRecipient;
use App\Services\BroadcastService;
use App\Services\TelegramRateLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendBroadcastRecipient implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of attempts.
     *
     * 1 initial attempt + 3 retries.
     */
    public int $tries = 4;

    /**
     * Maximum execution time.
     */
    public int $timeout = 90;

    /**
     * How long the unique job lock should remain.
     */
    public int $uniqueFor = 3600;

    /**
     * Default retry delays.
     *
     * Attempt 1 -> 10 seconds
     * Attempt 2 -> 30 seconds
     * Attempt 3 -> 60 seconds
     */
    public array $backoff = [
        10,
        30,
        60,
    ];

    /**
     * Create a new job.
     */
    public function __construct(
        public int $recipientId
    ) {
        $this->onQueue('broadcasts');
    }

    /**
     * Unique job identifier.
     *
     * Only one job for a recipient can exist
     * in the queue at the same time.
     */
    public function uniqueId(): string
    {
        return 'broadcast-recipient:' . $this->recipientId;
    }

    /**
     * Prevent concurrent processing of the same recipient.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->releaseAfter(10)
                ->expireAfter(180),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        BroadcastService $broadcastService,
        TelegramRateLimiter $rateLimiter
    ): void {
        $recipient = BroadcastRecipient::query()
            ->with([
                'broadcast',
                'user',
            ])
            ->find($this->recipientId);

        /**
         * Recipient no longer exists.
         */
        if (!$recipient) {
            return;
        }

        /**
         * Never send an already successful recipient again.
         */
        if ($recipient->status === 'sent') {
            $this->dispatchNextBatch($recipient);

            return;
        }

        /**
         * Broadcast no longer exists.
         */
        if (!$recipient->broadcast) {
            $this->markPermanentFailure(
                $recipient,
                'Broadcast no longer exists.'
            );

            return;
        }

        $broadcast = $recipient->broadcast;

        /**
         * The broadcast must still be active.
         */
        if (!in_array(
            $broadcast->status,
            ['prepared', 'sending'],
            true
        )) {
            return;
        }

        /**
         * User no longer exists.
         */
        if (!$recipient->user) {
            $this->markPermanentFailure(
                $recipient,
                'Recipient user no longer exists.'
            );

            $this->updateBroadcastStatus($recipient);

            return;
        }

        $user = $recipient->user;

        /**
         * Validate Telegram ID.
         */
        if (
            $user->telegram_id === null ||
            trim((string) $user->telegram_id) === ''
        ) {
            $this->markPermanentFailure(
                $recipient,
                'User does not have a Telegram ID.'
            );

            $this->updateBroadcastStatus($recipient);

            return;
        }

        /**
         * Re-read recipient status immediately before sending.
         *
         * This protects against stale model state.
         */
        $freshStatus = BroadcastRecipient::query()
            ->whereKey($recipient->id)
            ->value('status');

        /**
         * Already processed.
         */
        if ($freshStatus === 'sent') {
            $this->dispatchNextBatch($recipient);

            return;
        }

        /**
         * Only active states may be processed.
         */
        if (!in_array(
            $freshStatus,
            ['pending', 'queued', 'sending'],
            true
        )) {
            return;
        }

        /**
         * Atomically mark recipient as sending.
         */
        $claimed = BroadcastRecipient::query()
            ->whereKey($recipient->id)
            ->whereIn('status', [
                'pending',
                'queued',
                'sending',
            ])
            ->update([
                'status' => 'sending',
                'error_message' => null,
            ]);

        if (!$claimed) {
            return;
        }

        /**
         * Increment attempt counter.
         */
        BroadcastRecipient::query()
            ->whereKey($recipient->id)
            ->increment('attempts');

        /**
         * Refresh recipient before delivery.
         */
        $recipient->refresh();

        try {
            /**
             * ----------------------------------------------------------
             * Telegram global rate limiting
             * ----------------------------------------------------------
             *
             * All broadcast workers share the same TelegramRateLimiter.
             * This prevents multiple queue workers from exceeding the
             * configured global Telegram broadcast rate.
             */
            $rateLimiter->wait();

            /**
             * Re-check broadcast status after waiting.
             *
             * The job may have waited for the rate limiter, during which
             * time the broadcast could have been cancelled or completed.
             */
            $recipient->load([
                'broadcast',
                'user',
            ]);

            if (!$recipient->broadcast) {
                $this->markPermanentFailure(
                    $recipient,
                    'Broadcast no longer exists.'
                );

                return;
            }

            if (!in_array(
                $recipient->broadcast->status,
                ['prepared', 'sending'],
                true
            )) {
                /**
                 * Do not mark it as failed if the broadcast was stopped
                 * while this job was waiting.
                 */
                return;
            }

            /**
             * Send through BroadcastService.
             */
            $result = $broadcastService->sendRecipient(
                $recipient
            );

            /**
             * Never overwrite a successful delivery.
             */
            $currentStatus = BroadcastRecipient::query()
                ->whereKey($recipient->id)
                ->value('status');

            if ($currentStatus === 'sent') {
                $this->dispatchNextBatch($recipient);

                return;
            }

            /**
             * Successful delivery.
             */
            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'delivered_at' => $result['delivered_at'] ?? now(),
                'error_message' => null,
                'response' => $this->normalizeResponse(
                    $result['response'] ?? $result
                ),
            ]);

            /**
             * Update parent broadcast.
             */
            $recipient->load('broadcast');

            $this->updateBroadcastStatus($recipient);

            /**
             * Continue processing the next batch.
             */
            $this->dispatchNextBatch($recipient);
        } catch (Throwable $exception) {
            /**
             * Retryable error.
             */
            if ($this->isRetryable($exception)) {
                $recipient->update([
                    'status' => 'pending',
                    'error_message' => $this->safeErrorMessage(
                        $exception
                    ),
                ]);

                /**
                 * Telegram explicitly requested a retry delay.
                 */
                $retryAfter = $this->getRetryAfter($exception);

                if ($retryAfter !== null) {
                    $this->release(
                        min($retryAfter, 3600)
                    );

                    return;
                }

                /**
                 * Let Laravel handle the configured backoff.
                 */
                throw $exception;
            }

            /**
             * Permanent failure.
             */
            $this->markPermanentFailure(
                $recipient,
                $this->safeErrorMessage($exception)
            );

            $recipient->load('broadcast');

            $this->updateBroadcastStatus($recipient);

            /**
             * Continue processing the next batch even if this
             * individual recipient failed permanently.
             */
            $this->dispatchNextBatch($recipient);
        }
    }

    /**
     * Dispatch the next broadcast batch.
     *
     * The ProcessBroadcastBatch job is unique per broadcast,
     * so if multiple recipients finish at nearly the same time,
     * Laravel will prevent duplicate coordinators from running.
     */
    protected function dispatchNextBatch(
        BroadcastRecipient $recipient
    ): void {
        $broadcast = $recipient->broadcast;

        if (!$broadcast) {
            return;
        }

        $broadcast->refresh();

        /**
         * Do not continue a terminal broadcast.
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
         * There may still be pending recipients.
         *
         * The coordinator will determine whether another batch
         * needs to be dispatched.
         */
        ProcessBroadcastBatch::dispatch(
            $broadcast->id,
            $this->resolveBatchSize($broadcast)
        );
    }

    /**
     * Resolve the batch size for the next coordinator.
     *
     * Uses the configured default if no broadcast-specific
     * batch size exists.
     */
    protected function resolveBatchSize(
        $broadcast
    ): int {
        return max(
            1,
            (int) config(
                'services.telegram.broadcast_batch_size',
                100
            )
        );
    }

    /**
     * Determine whether an exception should be retried.
     */
    protected function isRetryable(
        Throwable $exception
    ): bool {
        /**
         * Custom retryable exception.
         */
        if (
            method_exists($exception, 'isRetryable') &&
            $exception->isRetryable()
        ) {
            return true;
        }

        /**
         * Explicit Telegram retryable exception.
         */
        if ($exception instanceof TelegramRetryableException) {
            return true;
        }

        /**
         * HTTP status code.
         */
        $code = (int) $exception->getCode();

        if (in_array(
            $code,
            [
                408,
                425,
                429,
                500,
                502,
                503,
                504,
            ],
            true
        )) {
            return true;
        }

        /**
         * Error message.
         */
        $message = strtolower(
            $exception->getMessage()
        );

        /**
         * Telegram rate-limit errors.
         */
        $rateLimitErrors = [
            'too many requests',
            'retry after',
            'flood control',
            'rate limit',
            'rate_limit',
            '429',
        ];

        foreach ($rateLimitErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        /**
         * Temporary network/server errors.
         */
        $temporaryErrors = [
            'connection',
            'timeout',
            'timed out',
            'temporarily unavailable',
            'temporary failure',
            'network',
            'could not resolve host',
            'connection reset',
            'connection refused',
            'server error',
            'service unavailable',
            'gateway timeout',
            'bad gateway',
            'dns',
            'socket',
        ];

        foreach ($temporaryErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract Telegram retry_after value.
     */
    protected function getRetryAfter(
        Throwable $exception
    ): ?int {
        /**
         * Preferred method.
         */
        if (method_exists($exception, 'getRetryAfter')) {
            $retryAfter = $exception->getRetryAfter();

            if ($retryAfter !== null) {
                return max(1, (int) $retryAfter);
            }
        }

        /**
         * Public property fallback.
         */
        if (
            property_exists($exception, 'retryAfter') &&
            $exception->retryAfter !== null
        ) {
            return max(
                1,
                (int) $exception->retryAfter
            );
        }

        /**
         * Parse:
         *
         * "retry after 30"
         */
        $message = strtolower(
            $exception->getMessage()
        );

        if (preg_match(
            '/retry\s+after\s+(\d+)/i',
            $message,
            $matches
        )) {
            return max(
                1,
                (int) $matches[1]
            );
        }

        return null;
    }

    /**
     * Mark a recipient as permanently failed.
     */
    protected function markPermanentFailure(
        BroadcastRecipient $recipient,
        string $message
    ): void {
        $recipient->update([
            'status' => 'failed',
            'error_message' => mb_substr(
                trim($message),
                0,
                1000
            ),
        ]);
    }

    /**
     * Handle a job that has exhausted all retries.
     */
    public function failed(
        Throwable $exception
    ): void {
        $recipient = BroadcastRecipient::query()
            ->with('broadcast')
            ->find($this->recipientId);

        if (!$recipient) {
            return;
        }

        /**
         * Never overwrite successful delivery.
         */
        if ($recipient->status === 'sent') {
            return;
        }

        /**
         * Final failure.
         */
        $recipient->update([
            'status' => 'failed',
            'error_message' => mb_substr(
                $this->safeErrorMessage($exception),
                0,
                1000
            ),
        ]);

        /**
         * Update broadcast status.
         */
        $this->updateBroadcastStatus($recipient);

        /**
         * Continue processing other recipients.
         */
        $this->dispatchNextBatch($recipient);
    }

    /**
     * Update the parent broadcast status.
     */
    protected function updateBroadcastStatus(
        BroadcastRecipient $recipient
    ): void {
        $broadcast = $recipient->broadcast;

        if (!$broadcast) {
            return;
        }

        $broadcast->refresh();

        /**
         * Pending / queued / sending means
         * the broadcast is still running.
         */
        $hasActive = $broadcast
            ->recipients()
            ->whereIn('status', [
                'pending',
                'queued',
                'sending',
            ])
            ->exists();

        if ($hasActive) {
            if ($broadcast->status !== 'sending') {
                $broadcast->update([
                    'status' => 'sending',
                ]);
            }

            return;
        }

        /**
         * Any failure means the broadcast finished
         * with a failed state.
         */
        $hasFailed = $broadcast
            ->recipients()
            ->where('status', 'failed')
            ->exists();

        if ($hasFailed) {
            $broadcast->update([
                'status' => 'failed',
                'sent_at' => $broadcast->sent_at ?? now(),
            ]);

            return;
        }

        /**
         * No active or failed recipients remain.
         * Therefore everything succeeded.
         */
        $broadcast->update([
            'status' => 'completed',
            'sent_at' => $broadcast->sent_at ?? now(),
        ]);
    }

    /**
     * Normalize a Telegram response before storing it.
     */
    protected function normalizeResponse(
        mixed $response
    ): ?string {
        if ($response === null) {
            return null;
        }

        if (is_string($response)) {
            return mb_substr(
                $response,
                0,
                10000
            );
        }

        try {
            return mb_substr(
                json_encode(
                    $response,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_PARTIAL_OUTPUT_ON_ERROR
                ) ?: '',
                0,
                10000
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Get a safe error message.
     */
    protected function safeErrorMessage(
        Throwable $exception
    ): string {
        $message = trim(
            $exception->getMessage()
        );

        if ($message === '') {
            $message = get_class($exception);
        }

        return mb_substr(
            $message,
            0,
            1000
        );
    }
}