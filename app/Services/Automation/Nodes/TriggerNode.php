<?php

namespace App\Services\Automation\Nodes;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\NodeResult;

class TriggerNode
{
    public function handle(
        AutomationExecution $execution,
        AutomationNode $node,
        array $context
    ): NodeResult {
        return NodeResult::continue(
            output: [
                'trigger' => [
                    'type' => $execution->trigger_type,
                    'data' => $execution->trigger_data ?? [],
                ],
            ]
        );
    }
}