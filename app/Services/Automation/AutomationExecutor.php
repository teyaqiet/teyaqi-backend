<?php

namespace App\Services\Automation;

use App\Jobs\ResumeAutomationExecution;
use App\Models\AutomationConnection;
use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AutomationExecutor
{
    /**
     * In-memory timers for executions that finish during
     * the current PHP request/process.
     *
     * @var array<int, float>
     */
    protected array $timers = [];

    public function __construct(
        protected NodeExecutor $nodeExecutor,
    ) {
    }

    /**
     * Start executing an automation.
     */
    public function execute(
        AutomationExecution $execution
    ): void {
        $this->timers[$execution->id] =
            microtime(true);

        try {

            $execution->update([
                'status' =>
                    'running',

                'started_at' =>
                    $execution->started_at
                    ?? now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Find Trigger
            |--------------------------------------------------------------------------
            */

            $trigger =
                AutomationNode::query()
                    ->where(
                        'automation_id',
                        $execution->automation_id
                    )
                    ->where(function ($query) {

                        $query
                            ->where(
                                'type',
                                'trigger'
                            )
                            ->orWhere(
                                'component',
                                'trigger'
                            );

                    })
                    ->where(
                        'enabled',
                        true
                    )
                    ->first();


            if (!$trigger) {

                throw new RuntimeException(
                    'Automation does not have an active trigger node.'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | Execute Trigger
            |--------------------------------------------------------------------------
            */

            $this->executeNode(
                $execution,
                $trigger
            );


        } catch (Throwable $e) {

            $this->failExecution(
                $execution,
                $e
            );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resume Execution
    |--------------------------------------------------------------------------
    */

    /**
     * Resume an execution from a specific node.
     *
     * This method is called by ResumeAutomationExecution
     * after a delay has elapsed.
     */
    public function resumeFromNode(
        AutomationExecution $execution,
        AutomationNode $node
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Reload fresh state
        |--------------------------------------------------------------------------
        */

        $execution->refresh();

        $node->refresh();


        /*
        |--------------------------------------------------------------------------
        | Safety Checks
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
        | Make sure the node belongs to this automation
        |--------------------------------------------------------------------------
        */

        if (
            (int) $node->automation_id !==
            (int) $execution->automation_id
        ) {

            throw new RuntimeException(
                'Resume node does not belong to the automation execution.'
            );

        }


        if (!$node->enabled) {

            throw new RuntimeException(
                "Cannot resume automation from disabled node {$node->id}."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Start Running Again
        |--------------------------------------------------------------------------
        */

        $execution->update([
            'status' =>
                'running',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Mark Delay As Resumed
        |--------------------------------------------------------------------------
        */

        $context =
            $execution->context ?? [];

        if (!is_array($context)) {

            $context = [];

        }


        if (
            isset($context['delay']) &&
            is_array($context['delay'])
        ) {

            $context['delay']['status'] =
                'completed';

            $context['delay']['resumed_at'] =
                now()->toIso8601String();

        }


        $execution->update([
            'context' =>
                $context,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Continue Workflow
        |--------------------------------------------------------------------------
        */

        try {

            $this->executeNode(
                $execution,
                $node
            );

        } catch (Throwable $e) {

            $this->failExecution(
                $execution,
                $e
            );

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Execute Node
    |--------------------------------------------------------------------------
    */

    protected function executeNode(
        AutomationExecution $execution,
        AutomationNode $node
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Execute Node Handler
        |--------------------------------------------------------------------------
        */

        $result =
            $this->nodeExecutor->execute(
                $execution,
                $node
            );


        /*
        |--------------------------------------------------------------------------
        | Finished
        |--------------------------------------------------------------------------
        */

        if (
    $result->isFinished()
) {

    $this->completeExecution(
        $execution,
        $result
    );

    return;
}


        /*
        |--------------------------------------------------------------------------
        | Waiting
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | A waiting node MUST NOT continue synchronously.
        |
        | Instead:
        |
        | 1. Find its next node.
        | 2. Save the path.
        | 3. Schedule a queue job.
        | 4. Mark execution as pending.
        | 5. Return.
        |
        */

        if (
            $result->isWaiting()
        ) {

            $this->scheduleWaitingNode(
                $execution,
                $node,
                $result
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Find Outgoing Connections
        |--------------------------------------------------------------------------
        */

        $connections =
            AutomationConnection::query()
                ->where(
                    'automation_id',
                    $execution->automation_id
                )
                ->where(
                    'source_node_id',
                    $node->id
                )
                ->with(
                    'targetNode'
                )
                ->get();


        Log::debug(
            'AUTOMATION NODE CONNECTIONS',
            [
                'execution_id' =>
                    $execution->execution_id,

                'node_id' =>
                    $node->id,

                'component' =>
                    $node->component,

                'connections' =>
                    $connections
                        ->map(
                            fn ($connection) => [

                                'id' =>
                                    $connection->id,

                                'target_node_id' =>
                                    $connection->target_node_id,

                                'source_handle' =>
                                    $connection->source_handle,

                                'target_handle' =>
                                    $connection->target_handle,

                                'target_exists' =>
                                    (bool)
                                    $connection->targetNode,

                                'target_enabled' =>
                                    (bool)
                                    $connection
                                        ->targetNode
                                        ?->enabled,

                            ]
                        )
                        ->values()
                        ->all(),
            ]
        );


        if (
            $connections->isEmpty()
        ) {

            throw new RuntimeException(
                "Node {$node->id} ({$node->component}) completed but has no outgoing connections."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Standard Node
        |--------------------------------------------------------------------------
        */

        if (
            $node->component !==
            'condition'
        ) {

            $connection =
                $connections->first(
                    fn ($connection) =>
                        (
                            $connection
                                ->source_handle
                            ?? 'output'
                        ) === 'output'
                );


            if (!$connection) {

                throw new RuntimeException(
                    "Node {$node->id} ({$node->component}) has no standard output connection."
                );

            }


            $this->recordPath(
                $execution,
                $node,
                $connection
            );


            $target =
                $connection->targetNode;


            if (
                !$target ||
                !$target->enabled
            ) {

                throw new RuntimeException(
                    "Target node for node {$node->id} is missing or disabled."
                );

            }


            $this->executeNode(
                $execution,
                $target
            );


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Condition Node
        |--------------------------------------------------------------------------
        */

        foreach (
            $connections
            as $connection
        ) {

            $handle =
                $connection->source_handle
                ?? 'output';


            if (
                !$result->shouldFollow(
                    $handle
                )
            ) {

                continue;

            }


            $target =
                $connection->targetNode;


            if (
                !$target ||
                !$target->enabled
            ) {

                continue;

            }


            $this->recordPath(
                $execution,
                $node,
                $connection,
                [
                    'condition' =>
                        $result
                            ->output[
                                'condition'
                            ]
                            ?? null,

                    'selected_handle' =>
                        $handle,
                ]
            );


            $this->executeNode(
                $execution,
                $target
            );


            return;
        }


        throw new RuntimeException(
            "Condition node {$node->id} completed but no branch matched the result."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule Waiting Node
    |--------------------------------------------------------------------------
    */

    protected function scheduleWaitingNode(
        AutomationExecution $execution,
        AutomationNode $node,
        NodeResult $result
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Read Delay
        |--------------------------------------------------------------------------
        */

        $seconds =
            (int) data_get(
                $result->output,
                'delay.seconds',
                0
            );


        /*
        |--------------------------------------------------------------------------
        | Safety
        |--------------------------------------------------------------------------
        */

        if ($seconds < 0) {

            $seconds = 0;

        }


        /*
        |--------------------------------------------------------------------------
        | Find Next Node
        |--------------------------------------------------------------------------
        */

        $connection =
            AutomationConnection::query()
                ->where(
                    'automation_id',
                    $execution->automation_id
                )
                ->where(
                    'source_node_id',
                    $node->id
                )
                ->where(
                    'source_handle',
                    'output'
                )
                ->with(
                    'targetNode'
                )
                ->first();


        if (!$connection) {

            throw new RuntimeException(
                "Delay node {$node->id} has no output connection."
            );

        }


        $target =
            $connection->targetNode;


        if (
            !$target ||
            !$target->enabled
        ) {

            throw new RuntimeException(
                "Delay node {$node->id} has an invalid or disabled target node."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Record Path
        |--------------------------------------------------------------------------
        */

        $this->recordPath(
            $execution,
            $node,
            $connection
        );


        /*
        |--------------------------------------------------------------------------
        | Save Waiting State
        |--------------------------------------------------------------------------
        */

        $context =
            $execution->context ?? [];

        if (!is_array($context)) {

            $context = [];

        }


        $context['delay'] = array_merge(

            is_array(
                $context['delay']
                ?? null
            )
                ? $context['delay']
                : [],

            [
                'duration' =>
                    data_get(
                        $result->output,
                        'delay.duration'
                    ),

                'unit' =>
                    data_get(
                        $result->output,
                        'delay.unit'
                    ),

                'seconds' =>
                    $seconds,

                'status' =>
                    'waiting',

                'node_id' =>
                    $node->id,

                'target_node_id' =>
                    $target->id,

                'scheduled_at' =>
                    now()->toIso8601String(),

                'resume_at' =>
                    now()
                        ->addSeconds(
                            $seconds
                        )
                        ->toIso8601String(),
            ]

        );


        $execution->update([
            'status' =>
                'pending',

            'context' =>
                $context,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Dispatch Delayed Continuation
        |--------------------------------------------------------------------------
        */

        ResumeAutomationExecution::dispatch(
            $execution->id,
            $target->id
        )->delay(
            now()->addSeconds(
                $seconds
            )
        );


        Log::info(
            'AUTOMATION DELAY SCHEDULED',
            [
                'execution_id' =>
                    $execution->execution_id,

                'delay_node_id' =>
                    $node->id,

                'target_node_id' =>
                    $target->id,

                'seconds' =>
                    $seconds,

                'resume_at' =>
                    now()
                        ->addSeconds(
                            $seconds
                        )
                        ->toDateTimeString(),
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Record Automation Path
    |--------------------------------------------------------------------------
    */

    protected function recordPath(
        AutomationExecution $execution,
        AutomationNode $source,
        AutomationConnection $connection,
        array $extra = []
    ): void {

        $context =
            $execution->context ?? [];

        if (!is_array($context)) {

            $context = [];

        }


        $path =
            $context['_automation_path']
            ?? [];

        if (!is_array($path)) {

            $path = [];

        }


        $path[] =
            array_merge(
                [
                    'source_node_id' =>
                        $source->id,

                    'target_node_id' =>
                        $connection->target_node_id,

                    'source_handle' =>
                        $connection->source_handle
                        ?? 'output',

                    'target_handle' =>
                        $connection->target_handle
                        ?? 'input',
                ],
                $extra
            );


        $execution->update([
            'context' =>
                array_merge(
                    $context,
                    [
                        '_automation_path' =>
                            $path,

                        '_last_connection' => [

                            'source_node_id' =>
                                $source->id,

                            'target_node_id' =>
                                $connection
                                    ->target_node_id,

                            'source_handle' =>
                                $connection
                                    ->source_handle
                                ?? 'output',

                        ],
                    ]
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Complete Execution
    |--------------------------------------------------------------------------
    */

    protected function completeExecution(
    AutomationExecution $execution,
    NodeResult $result
): void {

    /*
    |--------------------------------------------------------------------------
    | Resolve final status
    |--------------------------------------------------------------------------
    */

    $status = $result->status
        ?? data_get(
            $result->output,
            'status'
        )
        ?? 'success';

    /*
    |--------------------------------------------------------------------------
    | Normalize status
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim(
            (string) $status
        )
    );

    if (!in_array(
        $status,
        [
            'success',
            'failed',
            'cancelled',
        ],
        true
    )) {
        $status = 'success';
    }

    /*
    |--------------------------------------------------------------------------
    | Map workflow status to execution status
    |--------------------------------------------------------------------------
    |
    | Database currently uses:
    |
    | pending
    | running
    | completed
    | failed
    | cancelled
    |
    | End Node uses:
    |
    | success
    | failed
    | cancelled
    |
    */

    $executionStatus = match ($status) {
        'failed' =>
            'failed',

        'cancelled' =>
            'cancelled',

        default =>
            'completed',
    };

    $completedAt = now();

    /*
    |--------------------------------------------------------------------------
    | Execution result
    |--------------------------------------------------------------------------
    */

    $execution->update([
        'status' =>
            $executionStatus,

        'completed_at' =>
            $completedAt,

        'duration_ms' =>
            $this->duration(
                $execution
            ),

        'error_message' =>
            $status === 'failed'
                ? data_get(
                    $result->output,
                    'message'
                )
                : null,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Automation statistics
    |--------------------------------------------------------------------------
    */

    $automation =
        $execution->automation;

    if ($automation) {

        if ($status === 'success') {

            $automation->increment(
                'successful_runs'
            );

        } elseif ($status === 'failed') {

            $automation->increment(
                'failed_runs'
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Last run
        |--------------------------------------------------------------------------
        */

        $automation->update([
            'last_run_at' =>
                $completedAt,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cleanup timer
    |--------------------------------------------------------------------------
    */

    unset(
        $this->timers[
            $execution->id
        ]
    );
}


    /*
    |--------------------------------------------------------------------------
    | Fail Execution
    |--------------------------------------------------------------------------
    */

    protected function failExecution(
        AutomationExecution $execution,
        Throwable $e
    ): void {

        $completedAt =
            now();


        $execution->update([
            'status' =>
                'failed',

            'error_message' =>
                $e->getMessage(),

            'completed_at' =>
                $completedAt,

            'duration_ms' =>
                $this->duration(
                    $execution
                ),
        ]);


        $automation =
            $execution->automation;


        if ($automation) {

            $automation->increment(
                'failed_runs'
            );

            $automation->update([
                'last_run_at' =>
                    $completedAt,
            ]);

        }


        unset(
            $this->timers[
                $execution->id
            ]
        );


        report($e);
    }


    /*
    |--------------------------------------------------------------------------
    | Duration
    |--------------------------------------------------------------------------
    */

    protected function duration(
        AutomationExecution $execution
    ): ?int {

        $timer =
            $this->timers[
                $execution->id
            ]
            ?? null;


        if ($timer !== null) {

            return max(
                0,
                (int) round(
                    (
                        microtime(true)
                        -
                        $timer
                    ) * 1000
                )
            );

        }


        if (
            !$execution->started_at
        ) {

            return null;

        }


        return max(
            0,
            (int) round(
                abs(
                    now()->diffInMilliseconds(
                        $execution->started_at,
                        true
                    )
                )
            )
        );
    }
}