<?php

namespace App\Operations\Detectors;

use App\Models\OperationAlert;
use App\Models\OperationAlertRule;
use App\Operations\Services\AlertService;
use App\Operations\Services\ApplicationHealthService;

class ApplicationHealthAlertDetector
{
    public const TYPE = 'application.health';

    public const SOURCE = 'application';

    public function __construct(
        protected ApplicationHealthService $healthService,
        protected AlertService $alertService,
    ) {}

    public function detect(): array
    {
        $health = $this->healthService->check();

        $failedChecks = collect($health)
            ->filter(
                fn (array $check) => ! $check['healthy']
            );

        if ($failedChecks->isEmpty()) {
            $this->alertService->resolveByType(
                self::TYPE,
                self::SOURCE
            );

            return [];
        }

        $rule = $this->getRule();

        if (! $rule || ! $rule->isEnabled()) {
            return [];
        }

        $failedNames = $failedChecks
            ->keys()
            ->implode(', ');

        $severity = $rule->severity
            ?: OperationAlert::SEVERITY_CRITICAL;

        $message = "Application health check failed: {$failedNames}.";

        $alert = $this->alertService->trigger(
            type: self::TYPE,
            source: self::SOURCE,
            title: 'Application health check failed',
            message: $message,
            severity: $severity,
            data: [
                'checks' => $health,
                'failed_checks' => $failedChecks->keys()->values()->all(),
            ],
        );

        return $alert ? [$alert] : [];
    }

    protected function getRule(): ?OperationAlertRule
    {
        return OperationAlertRule::query()
            ->enabled()
            ->forType(self::TYPE)
            ->latest('id')
            ->first();
    }
}