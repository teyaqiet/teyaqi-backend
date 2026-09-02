<?php

namespace App\Services\Automation\Nodes;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\NodeResult;

class EndNode
{
    public function handle(
        AutomationExecution $execution,
        AutomationNode $node,
        array $context
    ): NodeResult {
        $config = $node->config ?? [];

        $status = $config['status'] ?? 'success';

        if (!in_array($status, [
            'success',
            'failed',
            'cancelled',
        ], true)) {
            $status = 'success';
        }

        return NodeResult::finish(
            output: [
                'status' => $status,
            ],
            status: $status
        );
    }
}