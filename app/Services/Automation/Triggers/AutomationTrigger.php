<?php

namespace App\Services\Automation\Triggers;

use App\Models\Automation;

class AutomationTrigger
{
    public function __construct(
        protected string $type,
        protected array $data = [],
        protected array $context = [],
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function context(): array
    {
        return $this->context;
    }

    public function matches(Automation $automation): bool
    {
        if ($automation->status !== 'active') {
            return false;
        }

        $trigger = $automation->nodes->first(
            fn ($node) =>
                $node->type === 'trigger' ||
                $node->component === 'trigger'
        );

        if (!$trigger) {
            return false;
        }

        $config = $trigger->config;

        if (is_string($config)) {
            $config = json_decode($config, true);
        }

        if (!is_array($config)) {
            return false;
        }

        if (
            array_key_exists('enabled', $config) &&
            !$config['enabled']
        ) {
            return false;
        }

        return ($config['event'] ?? null) === $this->type;
    }
}