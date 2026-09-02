<?php

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationExecution;
use Illuminate\Support\Str;

class AutomationEngine
{
    public function __construct(
        protected AutomationExecutor $executor
    ) {
    }

    /**
     * Start an automation.
     */
    public function run(
        Automation $automation,
        array $context = [],
        ?string $triggerType = null,
        array $triggerData = []
    ): AutomationExecution {
        if ($automation->status !== 'active') {
            throw new \RuntimeException(
                'Automation is not active.'
            );
        }

        $execution = AutomationExecution::create([
            'automation_id' => $automation->id,
            'execution_id' => (string) Str::uuid(),

            'trigger_type' => $triggerType,

            'trigger_data' => $triggerData,

            'status' => 'pending',

            'context' => $context,

            'started_at' => now(),
        ]);

        $automation->increment('total_runs');

        $this->executor->execute($execution);

        return $execution->fresh();
    }
}