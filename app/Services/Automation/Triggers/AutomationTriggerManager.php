<?php

namespace App\Services\Automation\Triggers;

use App\Models\Automation;
use App\Services\Automation\AutomationEngine;
use Illuminate\Support\Collection;
use Throwable;

class AutomationTriggerManager
{
    public function __construct(
        protected AutomationEngine $engine
    ) {
    }

    public function dispatch(
        AutomationTrigger $trigger
    ): Collection {
        $automations = $this->findMatchingAutomations(
            $trigger
        );

        $executions = collect();

        foreach ($automations as $automation) {
            try {

                $execution = $this->engine->run(
                    $automation,
                    context: $trigger->context(),
                    triggerType: $trigger->type(),
                    triggerData: $trigger->data(),
                );

                $executions->push($execution);

            } catch (Throwable $e) {

                report($e);
            }
        }

        return $executions;
    }

    public function findMatchingAutomations(
        AutomationTrigger $trigger
    ): Collection {
        return Automation::query()
            ->where('status', 'active')
            ->with([
                'nodes',
            ])
            ->get()
            ->filter(
                fn (Automation $automation) =>
                    $trigger->matches($automation)
            )
            ->values();
    }
}