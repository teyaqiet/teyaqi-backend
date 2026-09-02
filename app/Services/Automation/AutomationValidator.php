<?php

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationNode;
use Illuminate\Support\Collection;

class AutomationValidator
{
    public function validate(Automation $automation): array
    {
        $automation->loadMissing([
            'nodes',
            'connections',
        ]);

        $errors = [];
        $warnings = [];

        $nodes = $automation->nodes;
        $connections = $automation->connections;

        /*
        |--------------------------------------------------------------------------
        | Basic automation checks
        |--------------------------------------------------------------------------
        */

        if (trim((string) $automation->name) === '') {
            $errors[] = [
                'code' => 'automation.name.required',
                'message' => 'Automation name is required.',
            ];
        }

        if ($nodes->isEmpty()) {
            $errors[] = [
                'code' => 'automation.nodes.empty',
                'message' => 'Automation has no nodes.',
            ];

            return $this->result(
                $errors,
                $warnings
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Trigger checks
        |--------------------------------------------------------------------------
        */

        $triggers = $nodes->filter(
            fn (AutomationNode $node) =>
                $node->enabled
                && (
                    $node->type === 'trigger'
                    || $node->component === 'trigger'
                )
        );

        if ($triggers->isEmpty()) {
            $errors[] = [
                'code' => 'trigger.missing',
                'message' => 'Automation must have an enabled trigger node.',
            ];
        }

        if ($triggers->count() > 1) {
            $warnings[] = [
                'code' => 'trigger.multiple',
                'message' => 'Automation contains multiple trigger nodes.',
            ];
        }

        foreach ($triggers as $trigger) {
            $config = $this->config($trigger);

            $event = trim((string) (
                $config['event']
                ?? ''
            ));

            if ($event === '') {
                $errors[] = [
                    'code' => 'trigger.event.required',
                    'node_id' => $trigger->id,
                    'message' => "Trigger node {$trigger->id} does not have an event configured.",
                ];
            }

            $outgoing = $connections->where(
                'source_node_id',
                $trigger->id
            );

            if ($outgoing->isEmpty()) {
                $errors[] = [
                    'code' => 'trigger.connection.missing',
                    'node_id' => $trigger->id,
                    'message' => "Trigger node {$trigger->id} has no outgoing connection.",
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Node checks
        |--------------------------------------------------------------------------
        */

        foreach ($nodes as $node) {
            if (!$node->enabled) {
                $warnings[] = [
                    'code' => 'node.disabled',
                    'node_id' => $node->id,
                    'message' => "Node {$node->id} ({$node->component}) is disabled.",
                ];

                continue;
            }

            if (!$this->isKnownComponent($node->component)) {
                $errors[] = [
                    'code' => 'node.component.invalid',
                    'node_id' => $node->id,
                    'message' => "Node {$node->id} uses unknown component '{$node->component}'.",
                ];

                continue;
            }

            $this->validateNodeConfig(
                $node,
                $errors,
                $warnings
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Connection checks
        |--------------------------------------------------------------------------
        */

        $nodeIds = $nodes
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($connections as $connection) {
            if (!in_array(
                (int) $connection->source_node_id,
                $nodeIds,
                true
            )) {
                $errors[] = [
                    'code' => 'connection.source.missing',
                    'connection_id' => $connection->id,
                    'message' => "Connection {$connection->id} references a missing source node.",
                ];
            }

            if (!in_array(
                (int) $connection->target_node_id,
                $nodeIds,
                true
            )) {
                $errors[] = [
                    'code' => 'connection.target.missing',
                    'connection_id' => $connection->id,
                    'message' => "Connection {$connection->id} references a missing target node.",
                ];
            }

            if (
                !$connection->source_handle
                || !$connection->target_handle
            ) {
                $errors[] = [
                    'code' => 'connection.handle.missing',
                    'connection_id' => $connection->id,
                    'message' => "Connection {$connection->id} is missing a source or target handle.",
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Graph reachability
        |--------------------------------------------------------------------------
        */

        if ($triggers->isNotEmpty()) {
            $reachable = $this->reachableNodes(
                $triggers->first(),
                $nodes,
                $connections
            );

            $enabledNodeIds = $nodes
                ->filter(fn ($node) => $node->enabled)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($enabledNodeIds as $nodeId) {
                if (!in_array(
                    $nodeId,
                    $reachable,
                    true
                )) {
                    $errors[] = [
                        'code' => 'graph.node.unreachable',
                        'node_id' => $nodeId,
                        'message' => "Node {$nodeId} is unreachable from the trigger.",
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Condition branch checks
        |--------------------------------------------------------------------------
        */

        foreach ($nodes as $node) {
            if (
                !$node->enabled
                || $node->component !== 'condition'
            ) {
                continue;
            }

            $conditionConnections = $connections->where(
                'source_node_id',
                $node->id
            );

            $handles = $conditionConnections
                ->pluck('source_handle')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!in_array('true', $handles, true)) {
                $warnings[] = [
                    'code' => 'condition.true.missing',
                    'node_id' => $node->id,
                    'message' => "Condition node {$node->id} has no True branch.",
                ];
            }

            if (!in_array('false', $handles, true)) {
                $warnings[] = [
                    'code' => 'condition.false.missing',
                    'node_id' => $node->id,
                    'message' => "Condition node {$node->id} has no False branch.",
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        return $this->result(
            $errors,
            $warnings
        );
    }

    protected function validateNodeConfig(
        AutomationNode $node,
        array &$errors,
        array &$warnings
    ): void {
        $config = $this->config($node);

        switch ($node->component) {
            case 'trigger':

                if (
                    empty($config['event'])
                    && empty($config['trigger_type'])
                ) {
                    $errors[] = [
                        'code' => 'trigger.config.invalid',
                        'node_id' => $node->id,
                        'message' => "Trigger node {$node->id} has no trigger event.",
                    ];
                }

                break;

            case 'condition':

                if (empty($config['field'])) {
                    $errors[] = [
                        'code' => 'condition.field.required',
                        'node_id' => $node->id,
                        'message' => "Condition node {$node->id} requires a field.",
                    ];
                }

                if (empty($config['operator'])) {
                    $errors[] = [
                        'code' => 'condition.operator.required',
                        'node_id' => $node->id,
                        'message' => "Condition node {$node->id} requires an operator.",
                    ];
                }

                break;

            case 'telegram_message':

                if (
                    empty($config['message'])
                    || trim((string) $config['message']) === ''
                ) {
                    $errors[] = [
                        'code' => 'telegram.message.required',
                        'node_id' => $node->id,
                        'message' => "Telegram Message node {$node->id} requires a message.",
                    ];
                }

                $recipient = $config['recipient']
                    ?? 'context.telegram_id';

                if (
                    $recipient === 'config.chat_id'
                    && empty($config['chat_id'])
                ) {
                    $errors[] = [
                        'code' => 'telegram.chat_id.required',
                        'node_id' => $node->id,
                        'message' => "Telegram Message node {$node->id} requires a Chat ID when using Custom Chat ID.",
                    ];
                }

                break;

            case 'delay':

                $duration = $config['duration']
                    ?? null;

                if (
                    $duration === null
                    || !is_numeric($duration)
                    || (float) $duration <= 0
                ) {
                    $errors[] = [
                        'code' => 'delay.duration.invalid',
                        'node_id' => $node->id,
                        'message' => "Delay node {$node->id} requires a duration greater than zero.",
                    ];
                }

                break;

            case 'end':
                break;
        }
    }

    protected function config(
        AutomationNode $node
    ): array {
        $config = $node->config ?? [];

        return is_array($config)
            ? $config
            : [];
    }

    protected function isKnownComponent(
        ?string $component
    ): bool {
        return app(NodeRegistry::class)->has(
            (string) $component
        );
    }

    protected function reachableNodes(
        AutomationNode $start,
        Collection $nodes,
        Collection $connections
    ): array {
        $visited = [];
        $queue = [(int) $start->id];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if (in_array(
                $current,
                $visited,
                true
            )) {
                continue;
            }

            $visited[] = $current;

            $targets = $connections
                ->where('source_node_id', $current)
                ->pluck('target_node_id');

            foreach ($targets as $target) {
                $targetId = (int) $target;

                if (!in_array(
                    $targetId,
                    $visited,
                    true
                )) {
                    $queue[] = $targetId;
                }
            }
        }

        return $visited;
    }

    protected function result(
        array $errors,
        array $warnings
    ): array {
        return [
            'valid' => empty($errors),
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
        ];
    }
}