<?php

namespace App\Services\Automation;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Models\AutomationNodeExecution;
use Throwable;

class ExecutionLogger
{
    /**
     * High-resolution timers for individual node executions.
     *
     * @var array<int, float>
     */
    protected array $nodeTimers = [];

    /**
     * Start logging a node execution.
     */
    public function start(
        AutomationExecution $execution,
        AutomationNode $node,
        array $input = []
    ): AutomationNodeExecution {
        $log = AutomationNodeExecution::create([
            'execution_id' => $execution->id,
            'node_id' => $node->id,

            'status' => 'running',

            'input' => $input,

            'started_at' => now(),
        ]);

        /*
         * Start high-resolution timer after the log
         * has been created.
         */
        $this->nodeTimers[$log->id] = microtime(true);

        return $log;
    }

    /**
     * Mark node execution as completed.
     */
    public function complete(
        AutomationNodeExecution $log,
        array $output = []
    ): void {
        $completedAt = now();

        $duration = $this->getDuration($log);

        /*
         * Extra defensive protection.
         */
        $duration = max(
            0,
            (int) $duration
        );

        $log->update([
            'status' => 'completed',

            'output' => $output,

            'completed_at' => $completedAt,

            'duration_ms' => $duration,

            'error_message' => null,
        ]);

        /*
         * Clean up timer.
         */
        unset(
            $this->nodeTimers[$log->id]
        );
    }

    /**
     * Mark node execution as failed.
     */
    public function fail(
        AutomationNodeExecution $log,
        Throwable $exception
    ): void {
        $completedAt = now();

        $duration = $this->getDuration($log);

        /*
         * Extra defensive protection.
         */
        $duration = max(
            0,
            (int) $duration
        );

        $log->update([
            'status' => 'failed',

            'error_message' => $exception->getMessage(),

            'completed_at' => $completedAt,

            'duration_ms' => $duration,
        ]);

        /*
         * Clean up timer.
         */
        unset(
            $this->nodeTimers[$log->id]
        );
    }

    /**
     * Get node execution duration in milliseconds.
     *
     * @return int
     */
    protected function getDuration(
        AutomationNodeExecution $log
    ): int {
        /*
         * Preferred method:
         * high-resolution timer.
         */
        $startedAt = $this->nodeTimers[$log->id] ?? null;

        if ($startedAt !== null) {
            $duration = (
                microtime(true) - $startedAt
            ) * 1000;

            return max(
                0,
                (int) round($duration)
            );
        }

        /*
         * Defensive fallback.
         */
        if (!$log->started_at) {
            return 0;
        }

        /*
         * Use timestamp arithmetic instead of Carbon
         * diffInMilliseconds().
         */
        $startedTimestamp = $log
            ->started_at
            ->getTimestamp();

        $currentTimestamp = now()->getTimestamp();

        $duration = (
            $currentTimestamp - $startedTimestamp
        ) * 1000;

        return max(
            0,
            (int) $duration
        );
    }
}