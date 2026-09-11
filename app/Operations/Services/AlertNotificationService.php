<?php

namespace App\Operations\Services;

use App\Exceptions\TelegramPermanentException;
use App\Exceptions\TelegramRetryableException;
use App\Models\OperationAlert;
use App\Models\OperationAlertNotification;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlertNotificationService
{
    public function __construct(
        protected TelegramService $telegramService,
    ) {}

    public function notifyTriggered(
        OperationAlert $alert
    ): array {
        return $this->notify(
            $alert,
            OperationAlertNotification::EVENT_TRIGGERED
        );
    }

    public function notifyResolved(
        OperationAlert $alert
    ): array {
        return $this->notify(
            $alert,
            OperationAlertNotification::EVENT_RESOLVED
        );
    }

    public function notifyEvent(
        OperationAlert $alert,
        string $event
    ): array {
        return $this->notify(
            $alert,
            $event
        );
    }

    protected function notify(
        OperationAlert $alert,
        string $event
    ): array {
        if (! config(
            'operations.notifications.enabled',
            true
        )) {
            return [];
        }

        if (! $this->shouldNotifySeverity($alert)) {
            return [];
        }

        if (! $this->shouldNotifyEvent($event)) {
            return [];
        }

        $results = [];

        if (
            config(
                'operations.notifications.telegram.enabled',
                false
            )
        ) {
            $results['telegram'] =
                $this->sendTelegram(
                    $alert,
                    $event
                );
        }

        if (
            config(
                'operations.notifications.email.enabled',
                false
            )
        ) {
            $results['email'] =
                $this->sendEmail(
                    $alert,
                    $event
                );
        }

        return $results;
    }

    protected function sendTelegram(
        OperationAlert $alert,
        string $event
    ): array {
        $recipient = config(
            'operations.notifications.telegram.chat_id'
        );

        if (! $recipient) {
            return [
                'success' => false,
                'status' =>
                    OperationAlertNotification::STATUS_FAILED,
                'error' =>
                    'Telegram recipient is not configured.',
            ];
        }

        $message = $this->buildMessage(
            $alert,
            $event
        );

        $notification =
            OperationAlertNotification::create([
                'alert_id' => $alert->id,
                'channel' => 'telegram',
                'recipient' => (string) $recipient,
                'event' => $event,
                'status' =>
                    OperationAlertNotification::STATUS_PENDING,
                'message' => $message,
            ]);

        try {
            $response =
                $this->telegramService->sendMessage(
                    $recipient,
                    $message,
                    [],
                    'HTML'
                );

            $notification->update([
                'status' =>
                    OperationAlertNotification::STATUS_SENT,
                'sent_at' => now(),
                'error' => null,
            ]);

            return [
                'success' => true,
                'status' =>
                    OperationAlertNotification::STATUS_SENT,
                'notification_id' =>
                    $notification->id,
                'response' => $response,
            ];
        } catch (
            TelegramRetryableException |
            TelegramPermanentException $e
        ) {
            $this->markFailed(
                $notification,
                $e
            );

            return [
                'success' => false,
                'status' =>
                    OperationAlertNotification::STATUS_FAILED,
                'notification_id' =>
                    $notification->id,
                'error' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            $this->markFailed(
                $notification,
                $e
            );

            return [
                'success' => false,
                'status' =>
                    OperationAlertNotification::STATUS_FAILED,
                'notification_id' =>
                    $notification->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function sendEmail(
        OperationAlert $alert,
        string $event
    ): array {
        $recipient = config(
            'operations.notifications.email.recipient'
        );

        if (! $recipient) {
            return [
                'success' => false,
                'status' =>
                    OperationAlertNotification::STATUS_FAILED,
                'error' =>
                    'Email recipient is not configured.',
            ];
        }

        $message = $this->buildMessage(
            $alert,
            $event
        );

        $notification =
            OperationAlertNotification::create([
                'alert_id' => $alert->id,
                'channel' => 'email',
                'recipient' => $recipient,
                'event' => $event,
                'status' =>
                    OperationAlertNotification::STATUS_PENDING,
                'message' => $message,
            ]);

        return [
            'success' => true,
            'status' =>
                OperationAlertNotification::STATUS_PENDING,
            'notification_id' =>
                $notification->id,
        ];
    }

    protected function buildMessage(
        OperationAlert $alert,
        string $event
    ): string {
        if (
            $event ===
            OperationAlertNotification::EVENT_RESOLVED
        ) {
            $prefix = '✅ <b>ALERT RESOLVED</b>';
        } else {
            $prefix = match ($alert->severity) {
                OperationAlert::SEVERITY_CRITICAL =>
                    '🚨 <b>CRITICAL ALERT</b>',

                OperationAlert::SEVERITY_WARNING =>
                    '⚠️ <b>WARNING</b>',

                default =>
                    'ℹ️ <b>OPERATIONS ALERT</b>',
            };
        }

        $lines = [
            $prefix,
            '',
            '<b>' . $this->escapeHtml(
                $alert->title
            ) . '</b>',
            '',
            $this->escapeHtml(
                $alert->message
            ),
            '',
            '<b>Type:</b> '
                . $this->escapeHtml($alert->type),

            '<b>Source:</b> '
                . $this->escapeHtml($alert->source),

            '<b>Severity:</b> '
                . $this->escapeHtml($alert->severity),
        ];

        if ($alert->last_detected_at) {
            $lines[] =
                '<b>Detected:</b> '
                . $alert->last_detected_at
                    ->format('Y-m-d H:i:s');
        }

        if (
            $event ===
            OperationAlertNotification::EVENT_RESOLVED
            && $alert->resolved_at
        ) {
            $lines[] =
                '<b>Resolved:</b> '
                . $alert->resolved_at
                    ->format('Y-m-d H:i:s');
        }

        if (
            $event ===
            OperationAlertNotification::EVENT_RESOLVED
            && $alert->resolved_reason
        ) {
            $lines[] =
                '<b>Reason:</b> '
                . $this->escapeHtml(
                    $alert->resolved_reason
                );
        }

        return implode("\n", $lines);
    }

    protected function markFailed(
        OperationAlertNotification $notification,
        Throwable $e
    ): void {
        $notification->update([
            'status' =>
                OperationAlertNotification::STATUS_FAILED,
            'error' => $e->getMessage(),
        ]);

        Log::error(
            'Operations alert notification failed.',
            [
                'notification_id' =>
                    $notification->id,

                'alert_id' =>
                    $notification->alert_id,

                'channel' =>
                    $notification->channel,

                'event' =>
                    $notification->event,

                'error' =>
                    $e->getMessage(),
            ]
        );
    }

    protected function shouldNotifySeverity(
        OperationAlert $alert
    ): bool {
        return (bool) config(
            'operations.notifications.severities.'
                . $alert->severity,
            false
        );
    }

    protected function shouldNotifyEvent(
        string $event
    ): bool {
        return (bool) config(
            'operations.notifications.events.'
                . $event,
            false
        );
    }

    protected function escapeHtml(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    


}