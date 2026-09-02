<?php

namespace App\Services\Automation\Nodes;

use App\Models\AutomationExecution;
use App\Models\AutomationNode;
use App\Services\Automation\NodeResult;

class ConditionNode
{
    public function handle(
        AutomationExecution $execution,
        AutomationNode $node,
        array $context
    ): NodeResult {
        $config = $node->config ?? [];

        $field = $config['field'] ?? null;
        $operator = $config['operator'] ?? 'equals';
        $expected = $config['value'] ?? null;

        $actual = data_get(
            $context,
            $field
        );

        $result = match ($operator) {

            'equals' =>
                $actual == $expected,

            'not_equals' =>
                $actual != $expected,

            'greater_than' =>
                $actual > $expected,

            'greater_than_or_equal' =>
                $actual >= $expected,

            'less_than' =>
                $actual < $expected,

            'less_than_or_equal' =>
                $actual <= $expected,

            'contains' =>
                is_string($actual)
                && str_contains(
                    $actual,
                    $expected
                ),

            'is_true' =>
                (bool) $actual === true,

            'is_false' =>
                (bool) $actual === false,

            default => false,
        };

        return NodeResult::continue(
            handle: $result ? 'true' : 'false',

            output: [
                'condition' => [
                    'field' => $field,
                    'actual' => $actual,
                    'expected' => $expected,
                    'operator' => $operator,
                    'result' => $result,
                ],
            ]
        );
    }
}