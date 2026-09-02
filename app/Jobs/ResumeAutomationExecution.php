<?php

namespace App\Jobs;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\AutomationExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ResumeAutomationExecution implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $executionId,
        public int $nodeId,
    ) {
    }

    public function handle(
        AutomationExecutor $executor
    ): void {

        $execution = AutomationExecution::query()
            ->find($this->executionId);

        if (!$execution) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Don't resume finished executions
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $execution->status,
                [
                    'completed',
                    'failed',
                    'cancelled',
                ],
                true
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verify that this execution is actually waiting
        |--------------------------------------------------------------------------
        */

        $context = $execution->context ?? [];

        if (!is_array($context)) {
            $context = [];
        }

        $delay = $context['delay'] ?? null;

        if (!is_array($delay)) {
            throw new \RuntimeException(
                "Automation execution {$execution->execution_id} has no delay state."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent stale / duplicate resume jobs
        |--------------------------------------------------------------------------
        */

        if (
            (int) ($delay['target_node_id'] ?? 0)
            !== $this->nodeId
        ) {
            throw new \RuntimeException(
                "Automation execution {$execution->execution_id} is not waiting for node {$this->nodeId}."
            );
        }

        if (
            ($delay['status'] ?? null)
            !== 'waiting'
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Find Resume Node
        |--------------------------------------------------------------------------
        */

        $node = AutomationNode::query()
            ->where(
                'automation_id',
                $execution->automation_id
            )
            ->where(
                'id',
                $this->nodeId
            )
            ->where(
                'enabled',
                true
            )
            ->first();

        if (!$node) {
            throw new \RuntimeException(
                "Unable to resume automation. Node {$this->nodeId} was not found."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resume
        |--------------------------------------------------------------------------
        */

        $executor->resumeFromNode(
            $execution,
            $node
        );
    }

    public function failed(
        Throwable $exception
    ): void {

        $execution = AutomationExecution::query()
            ->find($this->executionId);

        if (!$execution) {
            return;
        }

        $execution->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}