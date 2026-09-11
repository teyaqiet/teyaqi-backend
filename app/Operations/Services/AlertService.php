<?php

namespace App\Operations\Services;

use App\Jobs\SendOperationAlertNotificationJob;
use App\Models\OperationAlert;
use App\Models\OperationAlertNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AlertService
{
    public function trigger(
        string $type,
        string $source,
        string $title,
        string $message,
        string $severity = OperationAlert::SEVERITY_WARNING,
        array $data = []
    ): ?OperationAlert {
        return DB::transaction(function () use (
            $type,
            $source,
            $title,
            $message,
            $severity,
            $data
        ) {
            $now = Carbon::now();

            /*
             * Find an existing active alert.
             *
             * If the same condition is detected again, update
             * the existing alert instead of creating another one.
             *
             * This prevents monitoring from creating duplicate
             * alerts every minute.
             */
            $activeAlert = OperationAlert::query()
                ->where('type', $type)
                ->where('source', $source)
                ->whereIn('status', [
                    OperationAlert::STATUS_ACTIVE,
                    OperationAlert::STATUS_ACKNOWLEDGED,
                ])
                ->latest('id')
                ->first();

            /*
             * Existing alert:
             *
             * Update the alert but do NOT send another
             * notification.
             *
             * This prevents notification spam when the
             * monitoring command runs repeatedly.
             */
            if ($activeAlert) {
                $activeAlert->update([
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'last_detected_at' => $now,
                ]);

                return $activeAlert->fresh();
            }

            /*
             * Check whether the latest resolved alert was
             * manually resolved.
             *
             * If an administrator manually resolved the alert,
             * do not immediately recreate it while the underlying
             * condition still exists.
             */
            $latestResolved = OperationAlert::query()
                ->where('type', $type)
                ->where('source', $source)
                ->where(
                    'status',
                    OperationAlert::STATUS_RESOLVED
                )
                ->latest('id')
                ->first();

            if (
                $latestResolved &&
                $latestResolved->resolution_type ===
                    OperationAlert::RESOLUTION_MANUAL
            ) {
                return null;
            }

            /*
             * Create a new alert.
             */
            $alert = OperationAlert::create([
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'source' => $source,
                'status' => OperationAlert::STATUS_ACTIVE,
                'data' => $data,
                'first_detected_at' => $now,
                'last_detected_at' => $now,
            ]);

            /*
             * Queue the notification only after the database
             * transaction has successfully committed.
             *
             * This is important because the queued job uses
             * the alert ID. We do not want a notification job
             * running for an alert that later gets rolled back.
             */
            DB::afterCommit(function () use ($alert) {
                SendOperationAlertNotificationJob::dispatch(
                    $alert->id,
                    OperationAlertNotification::EVENT_TRIGGERED
                );
            });

            return $alert;
        });
    }

    public function acknowledge(
        OperationAlert $alert,
        ?int $adminId = null
    ): OperationAlert {
        /*
         * A resolved alert cannot be acknowledged.
         */
        if ($alert->isResolved()) {
            return $alert;
        }

        $alert->update([
            'status' => OperationAlert::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => Carbon::now(),
            'acknowledged_by' => $adminId,
        ]);

        return $alert->fresh();
    }

    public function resolve(
        OperationAlert $alert,
        ?int $adminId = null,
        ?string $reason = null
    ): OperationAlert {
        /*
         * Do nothing if the alert has already been resolved.
         */
        if ($alert->isResolved()) {
            return $alert;
        }

        $alert->update([
            'status' => OperationAlert::STATUS_RESOLVED,
            'resolved_at' => Carbon::now(),
            'resolved_by' => $adminId,
            'resolution_type' => OperationAlert::RESOLUTION_MANUAL,
            'resolved_reason' => $reason
                ?? 'Manually resolved by administrator.',
        ]);

        $resolvedAlert = $alert->fresh();

        /*
         * Queue the resolved notification after the database
         * transaction has committed.
         */
        DB::afterCommit(function () use ($resolvedAlert) {
            SendOperationAlertNotificationJob::dispatch(
                $resolvedAlert->id,
                OperationAlertNotification::EVENT_RESOLVED
            );
        });

        return $resolvedAlert;
    }

    public function resolveByType(
        string $type,
        string $source,
        ?int $adminId = null
    ): int {
        return DB::transaction(function () use (
            $type,
            $source,
            $adminId
        ) {
            $now = Carbon::now();

            /*
             * Find all active alerts matching the condition.
             */
            $alerts = OperationAlert::query()
                ->where('type', $type)
                ->where('source', $source)
                ->whereIn('status', [
                    OperationAlert::STATUS_ACTIVE,
                    OperationAlert::STATUS_ACKNOWLEDGED,
                ])
                ->get();

            if ($alerts->isEmpty()) {
                return 0;
            }

            /*
             * Resolve the active alerts automatically because
             * the underlying condition has disappeared.
             */
            $resolvedCount = OperationAlert::query()
                ->whereIn(
                    'id',
                    $alerts->pluck('id')->all()
                )
                ->update([
                    'status' => OperationAlert::STATUS_RESOLVED,
                    'resolved_at' => $now,
                    'resolved_by' => $adminId,
                    'resolution_type' =>
                        OperationAlert::RESOLUTION_AUTOMATIC,
                    'resolved_reason' =>
                        'Underlying condition cleared automatically.',
                ]);

            /*
             * Reload the resolved alerts so the notification
             * jobs receive the correct final state.
             */
            $resolvedAlerts = OperationAlert::query()
                ->whereIn(
                    'id',
                    $alerts->pluck('id')->all()
                )
                ->get();

            /*
             * Queue one resolved notification for every alert
             * that was actually resolved.
             *
             * The jobs are dispatched only after this transaction
             * commits successfully.
             */
            foreach ($resolvedAlerts as $resolvedAlert) {
                DB::afterCommit(
                    function () use ($resolvedAlert) {
                        SendOperationAlertNotificationJob::dispatch(
                            $resolvedAlert->id,
                            OperationAlertNotification::EVENT_RESOLVED
                        );
                    }
                );
            }

            /*
             * Preserve the existing manual-resolution recovery
             * behavior.
             *
             * If an administrator manually resolved an alert,
             * we mark it as automatically cleared once the
             * underlying condition actually disappears.
             */
            $manualAlert = OperationAlert::query()
                ->where('type', $type)
                ->where('source', $source)
                ->where(
                    'status',
                    OperationAlert::STATUS_RESOLVED
                )
                ->where(
                    'resolution_type',
                    OperationAlert::RESOLUTION_MANUAL
                )
                ->latest('id')
                ->first();

            if ($manualAlert) {
                $manualAlert->update([
                    'resolution_type' =>
                        OperationAlert::RESOLUTION_AUTOMATIC,
                    'resolved_reason' =>
                        'Underlying condition later cleared automatically.',
                ]);
            }

            return $resolvedCount;
        });
    }

    public function activeAlerts(?int $limit = null)
    {
        $query = OperationAlert::query()
            ->active()
            ->orderByRaw("
                CASE severity
                    WHEN 'critical' THEN 1
                    WHEN 'warning' THEN 2
                    WHEN 'info' THEN 3
                    ELSE 4
                END
            ")
            ->latest('last_detected_at');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function statistics(): array
    {
        return [
            'total' => OperationAlert::count(),

            'active' => OperationAlert::active()->count(),

            'critical' => OperationAlert::active()
                ->where(
                    'severity',
                    OperationAlert::SEVERITY_CRITICAL
                )
                ->count(),

            'warning' => OperationAlert::active()
                ->where(
                    'severity',
                    OperationAlert::SEVERITY_WARNING
                )
                ->count(),

            'info' => OperationAlert::active()
                ->where(
                    'severity',
                    OperationAlert::SEVERITY_INFO
                )
                ->count(),

            'resolved' => OperationAlert::resolved()->count(),
        ];
    }
}