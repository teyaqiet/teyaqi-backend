<?php

namespace App\Operations\Detectors;

use App\Models\OperationAlert;
use App\Models\OperationAlertRule;
use App\Models\OperationBackup;
use App\Operations\Services\AlertService;
use Illuminate\Support\Carbon;

class BackupAlertDetector
{
    public const TYPE_FAILED = 'backup.failed';

    public const TYPE_STALE = 'backup.stale';

    public const SOURCE = 'backup';

    public function __construct(
        protected AlertService $alertService,
    ) {}

    /**
     * Run all backup health checks.
     */
    public function detect(): array
    {
        $alerts = [];

        $failedAlert = $this->detectFailedBackup();

        if ($failedAlert) {
            $alerts[] = $failedAlert;
        }

        $staleAlert = $this->detectStaleBackup();

        if ($staleAlert) {
            $alerts[] = $staleAlert;
        }

        return $alerts;
    }

    /**
     * Detect whether the latest database backup failed.
     */
    protected function detectFailedBackup(): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_FAILED);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $latestBackup = OperationBackup::query()
            ->where('type', 'database')
            ->latest('id')
            ->first();

        /*
         * No backup has ever been created.
         *
         * The stale detector handles this case.
         */
        if (! $latestBackup) {
            $this->alertService->resolveByType(
                self::TYPE_FAILED,
                self::SOURCE
            );

            return null;
        }

        /*
         * Latest backup succeeded.
         */
        if ($latestBackup->status === 'completed') {
            $this->alertService->resolveByType(
                self::TYPE_FAILED,
                self::SOURCE
            );

            return null;
        }

        /*
         * Only treat an explicitly failed backup as a failure alert.
         *
         * Pending/running backups are handled by the stale detector.
         */
        if ($latestBackup->status !== 'failed') {
            return null;
        }

        $severity = $rule->severity
            ?: OperationAlert::SEVERITY_WARNING;

        $error = $latestBackup->error;

        $message = $error
            ? "The latest database backup failed: {$error}"
            : 'The latest database backup failed and requires attention.';

        return $this->alertService->trigger(
            type: self::TYPE_FAILED,
            source: self::SOURCE,
            title: 'Database backup failed',
            message: $message,
            severity: $severity,
            data: [
                'backup_id' => $latestBackup->id,
                'status' => $latestBackup->status,
                'error' => $error,
                'created_at' => $latestBackup->created_at?->toIso8601String(),
                'completed_at' => $latestBackup->completed_at?->toIso8601String(),
            ],
        );
    }

    /**
     * Detect whether successful backups have become stale.
     */
    protected function detectStaleBackup(): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_STALE);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $warningHours = (int) $rule->getConfiguration(
            'warning_hours',
            24
        );

        $criticalHours = (int) $rule->getConfiguration(
            'critical_hours',
            48
        );

        $latestSuccessfulBackup = OperationBackup::query()
            ->where('type', 'database')
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->first();

        /*
         * No successful backup has ever existed.
         */
        if (! $latestSuccessfulBackup) {
            return $this->alertService->trigger(
                type: self::TYPE_STALE,
                source: self::SOURCE,
                title: 'No successful database backup',
                message: 'No successful database backup has been recorded.',
                severity: OperationAlert::SEVERITY_CRITICAL,
                data: [
                    'latest_successful_backup_id' => null,
                    'age_hours' => null,
                    'warning_hours' => $warningHours,
                    'critical_hours' => $criticalHours,
                ],
            );
        }

        $completedAt = Carbon::parse(
            $latestSuccessfulBackup->completed_at
        );

        $ageHours = round(
            $completedAt->diffInMinutes(now()) / 60,
            1
        );

        /*
         * Backup is healthy.
         */
        if ($ageHours < $warningHours) {
            $this->alertService->resolveByType(
                self::TYPE_STALE,
                self::SOURCE
            );

            return null;
        }

        $severity = $ageHours >= $criticalHours
            ? OperationAlert::SEVERITY_CRITICAL
            : ($rule->severity ?: OperationAlert::SEVERITY_WARNING);

        $title = $severity === OperationAlert::SEVERITY_CRITICAL
            ? 'Database backup is critically stale'
            : 'Database backup is stale';

        $message = $severity === OperationAlert::SEVERITY_CRITICAL
            ? "The latest successful database backup is {$ageHours} hours old."
            : "No successful database backup has completed within the last {$warningHours} hours.";

        return $this->alertService->trigger(
            type: self::TYPE_STALE,
            source: self::SOURCE,
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'latest_successful_backup_id' =>
                    $latestSuccessfulBackup->id,

                'latest_successful_backup_at' =>
                    $completedAt->toIso8601String(),

                'age_hours' => $ageHours,

                'warning_hours' => $warningHours,

                'critical_hours' => $criticalHours,
            ],
        );
    }

    /**
     * Get the latest enabled rule for a detector type.
     */
    protected function getRule(
        string $type
    ): ?OperationAlertRule {
        return OperationAlertRule::query()
            ->enabled()
            ->forType($type)
            ->latest('id')
            ->first();
    }
}