<?php

namespace App\Services\Automation\Triggers;

use Illuminate\Support\Collection;

class AutomationTriggerDispatcher
{
    public function __construct(
        protected AutomationTriggerManager $manager
    ) {
    }

    public function dispatch(
        string $type,
        array $data = [],
        array $context = [],
    ): Collection {
        $trigger = new AutomationTrigger(
            type: $type,
            data: $data,
            context: $context,
        );

        return $this->manager->dispatch(
            $trigger
        );
    }
}