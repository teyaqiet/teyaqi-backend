<?php

namespace App\Operations\Detectors;

use App\Models\OperationAlert;
use App\Models\OperationAlertRule;
use App\Operations\Services\AlertService;
use App\Operations\Services\QueueService;

class QueueAlertDetector
{
    public const TYPE_FAILED_JOBS = 'queue.failed_jobs';
    public const TYPE_PENDING_JOBS = 'queue.pending_jobs';

    public function __construct(
        protected QueueService $queueService,
        protected AlertService $alertService,
    ) {}

    /**
     * Run all queue alert checks.
     */
    public function detect(): array
    {
        $overview = $this->queueService->overview();

        $alerts = [];

        $failedAlert = $this->detectFailedJobs(
            (int) $overview['failed_jobs']
        );

        if ($failedAlert) {
            $alerts[] = $failedAlert;
        }

        $pendingAlert = $this->detectPendingJobs(
            (int) $overview['pending_jobs']
        );

        if ($pendingAlert) {
            $alerts[] = $pendingAlert;
        }

        return $alerts;
    }

    /**
     * Detect failed queue jobs.
     */
    protected function detectFailedJobs(int $failedJobs): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_FAILED_JOBS);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $warningThreshold = (int) $rule->getConfiguration(
            'warning_threshold',
            1
        );

        $criticalThreshold = (int) $rule->getConfiguration(
            'critical_threshold',
            10
        );

        if ($failedJobs < $warningThreshold) {
            $this->alertService->resolveByType(
                self::TYPE_FAILED_JOBS,
                'queue'
            );

            return null;
        }

        $severity = $failedJobs >= $criticalThreshold
            ? OperationAlert::SEVERITY_CRITICAL
            : ($rule->severity ?: OperationAlert::SEVERITY_WARNING);

        $title = $severity === OperationAlert::SEVERITY_CRITICAL
            ? 'Critical queue failure'
            : 'Queue jobs have failed';

        $message = $severity === OperationAlert::SEVERITY_CRITICAL
            ? "{$failedJobs} failed queue jobs require attention."
            : "{$failedJobs} failed queue job(s) detected.";

        return $this->alertService->trigger(
            type: self::TYPE_FAILED_JOBS,
            source: 'queue',
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'failed_jobs' => $failedJobs,
                'warning_threshold' => $warningThreshold,
                'critical_threshold' => $criticalThreshold,
            ],
        );
    }

    /**
     * Detect excessive pending queue jobs.
     */
    protected function detectPendingJobs(int $pendingJobs): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_PENDING_JOBS);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $warningThreshold = (int) $rule->getConfiguration(
            'warning_threshold',
            100
        );

        $criticalThreshold = (int) $rule->getConfiguration(
            'critical_threshold',
            500
        );

        if ($pendingJobs < $warningThreshold) {
            $this->alertService->resolveByType(
                self::TYPE_PENDING_JOBS,
                'queue'
            );

            return null;
        }

        $severity = $pendingJobs >= $criticalThreshold
            ? OperationAlert::SEVERITY_CRITICAL
            : ($rule->severity ?: OperationAlert::SEVERITY_WARNING);

        $title = $severity === OperationAlert::SEVERITY_CRITICAL
            ? 'Critical queue backlog'
            : 'Queue backlog is growing';

        $message = $severity === OperationAlert::SEVERITY_CRITICAL
            ? "{$pendingJobs} pending queue jobs detected."
            : "{$pendingJobs} pending queue jobs are waiting to be processed.";

        return $this->alertService->trigger(
            type: self::TYPE_PENDING_JOBS,
            source: 'queue',
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'pending_jobs' => $pendingJobs,
                'warning_threshold' => $warningThreshold,
                'critical_threshold' => $criticalThreshold,
            ],
        );
    }

    /**
     * Get the alert rule for a detector type.
     */
    protected function getRule(string $type): ?OperationAlertRule
    {
        return OperationAlertRule::query()
            ->enabled()
            ->forType($type)
            ->latest('id')
            ->first();
    }
}