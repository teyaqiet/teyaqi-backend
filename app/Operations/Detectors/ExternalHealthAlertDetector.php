<?php

namespace App\Operations\Detectors;

use App\Models\OperationAlert;
use App\Models\OperationAlertRule;
use App\Operations\Services\AlertService;
use App\Operations\Services\ExternalHealthMonitorService;

class ExternalHealthAlertDetector
{
    public const TYPE = 'application.external_health';

    public const LATENCY_TYPE = 'application.external_health.latency';

    public const SOURCE = 'external_health';

    public function __construct(
        protected ExternalHealthMonitorService $monitorService,
        protected AlertService $alertService,
    ) {}

    public function detect(): array
    {
        $result = $this->monitorService->check();

        /*
         * Application cannot be reached.
         */
        if (! $result['reachable']) {
            return $this->handleUnreachable($result);
        }

        /*
         * Application responded, but the health endpoint
         * reported an unhealthy state.
         */
        if (! $result['healthy']) {
            return $this->handleUnhealthy($result);
        }

        /*
         * Application is healthy.
         *
         * Now check response latency.
         */
        return $this->handleLatency($result);
    }

    /**
     * Handle an unreachable application.
     */
    protected function handleUnreachable(array $result): array
    {
        $rule = $this->getRule();

        if (! $rule || ! $rule->isEnabled()) {
            return [];
        }

        /*
         * The application is unreachable, so any previous
         * latency alert is no longer relevant.
         */
        $this->alertService->resolveByType(
            self::LATENCY_TYPE,
            self::SOURCE
        );

        $message = 'The application health endpoint is unreachable.';

        if (! empty($result['error'])) {
            $message .= ' '
                . 'Connection error: '
                . $result['error'];
        }

        $alert = $this->alertService->trigger(
            type: self::TYPE,
            source: self::SOURCE,
            title: 'Application is unreachable',
            message: $message,
            severity: OperationAlert::SEVERITY_CRITICAL,
            data: [
                'alert_category' => 'availability',
                'reachable' => false,
                'healthy' => false,
                'status_code' => null,
                'url' => $result['url'],
                'response_time_ms' => null,
                'error' => $result['error'] ?? null,
            ],
        );

        return $alert ? [$alert] : [];
    }

    /**
     * Handle a reachable but unhealthy application.
     */
    protected function handleUnhealthy(array $result): array
    {
        $rule = $this->getRule();

        if (! $rule || ! $rule->isEnabled()) {
            return [];
        }

        /*
         * Latency cannot be considered separately when the
         * health endpoint itself is unhealthy.
         */
        $this->alertService->resolveByType(
            self::LATENCY_TYPE,
            self::SOURCE
        );

        $statusCode = $result['status_code'];

        if ($statusCode === 503) {
            $title = 'Application health check failed';

            $message = 'The application is reachable, '
                . 'but the health endpoint reports an unhealthy state.';
        } else {
            $title = 'Application health endpoint failed';

            $message = $statusCode !== null
                ? "The application health endpoint returned HTTP {$statusCode}."
                : 'The application health endpoint returned an unexpected response.';
        }

        $severity = $rule->severity
            ?: OperationAlert::SEVERITY_CRITICAL;

        $alert = $this->alertService->trigger(
            type: self::TYPE,
            source: self::SOURCE,
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'alert_category' => 'health',
                'reachable' => $result['reachable'],
                'healthy' => $result['healthy'],
                'status_code' => $statusCode,
                'url' => $result['url'],
                'response_time_ms' => $result['response_time_ms'] ?? null,
                'body' => $result['body'] ?? null,
            ],
        );

        return $alert ? [$alert] : [];
    }

    /**
     * Handle application response latency.
     */
    protected function handleLatency(array $result): array
    {
        $rule = $this->getLatencyRule();

        if (! $rule || ! $rule->isEnabled()) {
            return [];
        }

        $responseTimeMs = $result['response_time_ms'] ?? null;

        /*
         * If timing information is unavailable, we cannot
         * evaluate latency.
         */
        if ($responseTimeMs === null) {
            $this->alertService->resolveByType(
                self::LATENCY_TYPE,
                self::SOURCE
            );

            /*
             * The application itself is healthy, so resolve
             * any previous availability/health alert.
             */
            $this->alertService->resolveByType(
                self::TYPE,
                self::SOURCE
            );

            return [];
        }

        $warningMs = (float) $rule->getConfiguration(
            'warning_ms',
            2000
        );

        $criticalMs = (float) $rule->getConfiguration(
            'critical_ms',
            5000
        );

        /*
         * Response time is healthy.
         */
        if ($responseTimeMs < $warningMs) {
            $this->alertService->resolveByType(
                self::LATENCY_TYPE,
                self::SOURCE
            );

            /*
             * Application is healthy again, so resolve any
             * previous availability/health alert.
             */
            $this->alertService->resolveByType(
                self::TYPE,
                self::SOURCE
            );

            return [];
        }

        /*
         * Determine severity based on latency.
         */
        $severity = $responseTimeMs >= $criticalMs
            ? OperationAlert::SEVERITY_CRITICAL
            : OperationAlert::SEVERITY_WARNING;

        if ($severity === OperationAlert::SEVERITY_CRITICAL) {
            $title = 'Application response is critically slow';

            $message = sprintf(
                'The application health endpoint responded in %.2f ms, exceeding the critical latency threshold of %.0f ms.',
                $responseTimeMs,
                $criticalMs
            );
        } else {
            $title = 'Application response is slow';

            $message = sprintf(
                'The application health endpoint responded in %.2f ms, exceeding the warning latency threshold of %.0f ms.',
                $responseTimeMs,
                $warningMs
            );
        }

        /*
         * Latency uses a separate alert type so it does not
         * conflict with availability or health alerts.
         */
        $alert = $this->alertService->trigger(
            type: self::LATENCY_TYPE,
            source: self::SOURCE,
            title: $title,
            message: $message,
            severity: $severity,
            data: [
                'alert_category' => 'latency',
                'reachable' => true,
                'healthy' => true,
                'status_code' => $result['status_code'],
                'url' => $result['url'],
                'response_time_ms' => $responseTimeMs,
                'warning_ms' => $warningMs,
                'critical_ms' => $criticalMs,
            ],
        );

        return $alert ? [$alert] : [];
    }

    /**
     * Get the main external health alert rule.
     */
    protected function getRule(): ?OperationAlertRule
    {
        return OperationAlertRule::query()
            ->enabled()
            ->forType(self::TYPE)
            ->latest('id')
            ->first();
    }

    /**
     * Get the dedicated latency alert rule.
     */
    protected function getLatencyRule(): ?OperationAlertRule
    {
        return OperationAlertRule::query()
            ->enabled()
            ->forType(self::LATENCY_TYPE)
            ->latest('id')
            ->first();
    }
}