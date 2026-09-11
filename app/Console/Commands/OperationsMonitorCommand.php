<?php

namespace App\Console\Commands;

use App\Operations\Services\AlertDetectorService;
use Illuminate\Console\Command;
use Throwable;

class OperationsMonitorCommand extends Command
{
    protected $signature = 'operations:monitor';

    protected $description = 'Run all Operations Center alert detectors.';

    public function handle(
        AlertDetectorService $detectorService
    ): int {
        $this->info('Running Operations Center alert monitoring...');

        try {
            $results = $detectorService->detect();
        } catch (Throwable $e) {
            $this->error(
                'Alert monitoring failed: ' . $e->getMessage()
            );

            report($e);

            return self::FAILURE;
        }

        foreach ($results as $detector => $result) {
            if ($result['success']) {
                $this->line(
                    sprintf(
                        '  ✓ %-12s %d alert(s)',
                        ucfirst($detector) . ':',
                        $result['count']
                    )
                );

                continue;
            }

            $this->error(
                sprintf(
                    '  ✗ %-12s %s',
                    ucfirst($detector) . ':',
                    $result['error'] ?? 'Unknown error.'
                )
            );
        }

        $totalAlerts = collect($results)
            ->sum('count');

        $failedDetectors = collect($results)
            ->filter(
                fn (array $result) => ! $result['success']
            )
            ->count();

        $this->newLine();

        $this->info(
            "Monitoring complete. {$totalAlerts} active alert(s) detected."
        );

        if ($failedDetectors > 0) {
            $this->warn(
                "{$failedDetectors} detector(s) failed."
            );

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}