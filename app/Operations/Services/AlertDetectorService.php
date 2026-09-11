<?php

namespace App\Operations\Services;

use App\Operations\Detectors\ApplicationHealthAlertDetector;
use App\Operations\Detectors\BackupAlertDetector;
use App\Operations\Detectors\DeploymentAlertDetector;
use App\Operations\Detectors\ExternalHealthAlertDetector;
use App\Operations\Detectors\QueueAlertDetector;
use Throwable;

class AlertDetectorService
{
    public function __construct(
        protected QueueAlertDetector $queueAlertDetector,
        protected BackupAlertDetector $backupAlertDetector,
        protected DeploymentAlertDetector $deploymentAlertDetector,
        protected ApplicationHealthAlertDetector $applicationHealthAlertDetector,
        protected ExternalHealthAlertDetector $externalHealthAlertDetector,
    ) {}

    public function detect(): array
    {
        $results = [];

        $this->runDetector(
            'queue',
            fn () => $this->queueAlertDetector->detect(),
            $results
        );

        $this->runDetector(
            'backup',
            fn () => $this->backupAlertDetector->detect(),
            $results
        );

        $this->runDetector(
            'deployment',
            fn () => $this->deploymentAlertDetector->detect(),
            $results
        );

        $this->runDetector(
            'application',
            fn () => $this->applicationHealthAlertDetector->detect(),
            $results
        );

        $this->runDetector(
            'external_health',
            fn () => $this->externalHealthAlertDetector->detect(),
            $results
        );

        return $results;
    }

    protected function runDetector(
        string $name,
        callable $detector,
        array &$results
    ): void {
        try {
            $alerts = $detector();

            $results[$name] = [
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts),
            ];
        } catch (Throwable $e) {
            report($e);

            $results[$name] = [
                'success' => false,
                'alerts' => [],
                'count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
}