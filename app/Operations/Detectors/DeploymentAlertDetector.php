<?php

namespace App\Operations\Detectors;

use App\Models\OperationAlert;
use App\Models\OperationAlertRule;
use App\Models\OperationDeployment;
use App\Operations\Services\AlertService;

class DeploymentAlertDetector
{
    public const TYPE_FAILED = 'deployment.failed';

    public const TYPE_STUCK = 'deployment.stuck';

    public const TYPE_ROLLBACK = 'deployment.rollback';

    public const SOURCE = 'deployment';

    public function __construct(
        protected AlertService $alertService,
    ) {}

    public function detect(): array
    {
        $alerts = [];

        $failedAlert = $this->detectFailedDeployment();

        if ($failedAlert) {
            $alerts[] = $failedAlert;
        }

        $stuckAlert = $this->detectStuckDeployment();

        if ($stuckAlert) {
            $alerts[] = $stuckAlert;
        }

        $rollbackAlert = $this->detectRollbackDeployment();

        if ($rollbackAlert) {
            $alerts[] = $rollbackAlert;
        }

        return $alerts;
    }

    protected function detectFailedDeployment(): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_FAILED);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $latestDeployment = OperationDeployment::query()
            ->latest('id')
            ->first();

        if (! $latestDeployment) {
            $this->alertService->resolveByType(
                self::TYPE_FAILED,
                self::SOURCE
            );

            return null;
        }

        if ($latestDeployment->status === 'failed') {
            $severity = $rule->severity
                ?: OperationAlert::SEVERITY_CRITICAL;

            $error = $latestDeployment->error;

            $message = $error
                ? "The latest deployment failed: {$error}"
                : 'The latest deployment failed and requires attention.';

            return $this->alertService->trigger(
                type: self::TYPE_FAILED,
                source: self::SOURCE,
                title: 'Deployment failed',
                message: $message,
                severity: $severity,
                data: [
                    'deployment_id' => $latestDeployment->id,
                    'environment' => $latestDeployment->environment,
                    'branch' => $latestDeployment->branch,
                    'commit_hash' => $latestDeployment->commit_hash,
                    'commit_message' =>
                        $latestDeployment->commit_message,
                    'status' => $latestDeployment->status,
                    'error' => $error,
                    'started_at' =>
                        $latestDeployment->started_at?->toIso8601String(),
                    'completed_at' =>
                        $latestDeployment->completed_at?->toIso8601String(),
                    'duration_seconds' =>
                        $latestDeployment->duration_seconds,
                ],
            );
        }

        if ($latestDeployment->status === 'completed') {
            $this->alertService->resolveByType(
                self::TYPE_FAILED,
                self::SOURCE
            );
        }

        return null;
    }

    protected function detectStuckDeployment(): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_STUCK);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $latestDeployment = OperationDeployment::query()
            ->latest('id')
            ->first();

        if (! $latestDeployment) {
            $this->alertService->resolveByType(
                self::TYPE_STUCK,
                self::SOURCE
            );

            return null;
        }

        if ($latestDeployment->status !== 'running') {
            $this->alertService->resolveByType(
                self::TYPE_STUCK,
                self::SOURCE
            );

            return null;
        }

        if (! $latestDeployment->started_at) {
            return null;
        }

        $stuckMinutes = (int) $rule->getConfiguration(
            'stuck_minutes',
            30
        );

        $ageMinutes = $latestDeployment->started_at
            ->diffInMinutes(now());

        if ($ageMinutes < $stuckMinutes) {
            return null;
        }

        $severity = $rule->severity
            ?: OperationAlert::SEVERITY_WARNING;

        return $this->alertService->trigger(
            type: self::TYPE_STUCK,
            source: self::SOURCE,
            title: 'Deployment appears stuck',
            message:
                "Deployment #{$latestDeployment->id} has been running for {$ageMinutes} minutes without completing.",
            severity: $severity,
            data: [
                'deployment_id' => $latestDeployment->id,
                'environment' => $latestDeployment->environment,
                'branch' => $latestDeployment->branch,
                'commit_hash' => $latestDeployment->commit_hash,
                'commit_message' =>
                    $latestDeployment->commit_message,
                'status' => $latestDeployment->status,
                'started_at' =>
                    $latestDeployment->started_at?->toIso8601String(),
                'age_minutes' => $ageMinutes,
                'stuck_minutes' => $stuckMinutes,
            ],
        );
    }

    /**
     * Detect the latest rollback deployment.
     *
     * Rollbacks are events rather than ongoing conditions.
     * We therefore create one alert per rollback deployment
     * and do not automatically resolve it.
     */
    protected function detectRollbackDeployment(): ?OperationAlert
    {
        $rule = $this->getRule(self::TYPE_ROLLBACK);

        if (! $rule || ! $rule->isEnabled()) {
            return null;
        }

        $latestRollback = OperationDeployment::query()
            ->where(function ($query) {
                $query
                    ->where('type', 'rollback')
                    ->orWhereNotNull('rollback_of');
            })
            ->latest('id')
            ->first();

        if (! $latestRollback) {
            return null;
        }

        /*
         * Check whether this exact rollback has already
         * generated an alert.
         *
         * We cannot rely only on AlertService::trigger()
         * because rollback alerts are event-based and a
         * manually resolved older rollback must not block
         * a completely new rollback.
         */
        $existingAlert = OperationAlert::query()
            ->where('type', self::TYPE_ROLLBACK)
            ->where('source', self::SOURCE)
            ->whereJsonContains(
                'data->deployment_id',
                $latestRollback->id
            )
            ->latest('id')
            ->first();

        if ($existingAlert) {
            return null;
        }

        $severity = $rule->severity
            ?: OperationAlert::SEVERITY_INFO;

        $rollbackOf = $latestRollback->rollback_of;

        $title = 'Deployment rollback detected';

        $message = $rollbackOf
            ? "Rollback deployment #{$latestRollback->id} reverted deployment #{$rollbackOf}."
            : "Rollback deployment #{$latestRollback->id} was detected.";

        return $this->alertService->trigger(
            type: self::TYPE_ROLLBACK,
            source: self::SOURCE,
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'deployment_id' => $latestRollback->id,
                'rollback_of' => $rollbackOf,
                'environment' => $latestRollback->environment,
                'branch' => $latestRollback->branch,
                'commit_hash' => $latestRollback->commit_hash,
                'commit_message' =>
                    $latestRollback->commit_message,
                'status' => $latestRollback->status,
                'type' => $latestRollback->type,
                'started_at' =>
                    $latestRollback->started_at?->toIso8601String(),
                'completed_at' =>
                    $latestRollback->completed_at?->toIso8601String(),
                'duration_seconds' =>
                    $latestRollback->duration_seconds,
            ],
        );
    }

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