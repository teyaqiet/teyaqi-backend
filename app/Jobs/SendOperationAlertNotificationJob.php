<?php

namespace App\Jobs;

use App\Models\OperationAlert;
use App\Operations\Services\AlertNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendOperationAlertNotificationJob implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Number of times Laravel should attempt this job.
     */
    public int $tries = 3;

    /**
     * Maximum number of seconds the job may run.
     */
    public int $timeout = 120;

    /**
     * Retry delays in seconds.
     */
    public array $backoff = [
        10,
        60,
        300,
    ];

    public function __construct(
        public int $alertId,
        public string $event,
    ) {
        $this->onQueue('operations');
    }

    /**
     * Prevent duplicate delivery attempts for the
     * same alert/event while one is already running.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'operations-alert-notification:'
                . $this->alertId
                . ':'
                . $this->event
            ))->expireAfter(180),
        ];
    }

    public function handle(
        AlertNotificationService $notificationService
    ): void {
        $alert = OperationAlert::find($this->alertId);

        /*
         * The alert may have been deleted before the queued
         * notification was processed.
         */
        if (! $alert) {
            return;
        }

        $notificationService->notifyEvent(
            $alert,
            $this->event
        );
    }

    /**
     * Handle a permanently failed job.
     *
     * The notification service is responsible for recording
     * channel-level failures. This is for queue-level failures.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}