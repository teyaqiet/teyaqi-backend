<?php

namespace App\Listeners;

use App\Events\Player\XpMilestoneReached;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchXpMilestoneAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(XpMilestoneReached $event): void
    {
        $user = $event->user->fresh();

        $this->dispatcher->dispatch(
        'xp_milestone',

        data: [
            'previous_xp' => $event->previousXp,
            'new_xp' => $event->newXp,
            'milestone' => $event->milestone,
        ],

        context: [
            'user_id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'telegram_id' => $user->telegram_id,

            'previous_xp' => $event->previousXp,
            'total_xp' => $user->total_xp,
            'milestone' => $event->milestone,

            'level' => $user->level_data['level'] ?? 1,

            'current_streak' => $user->current_streak,
            'best_streak' => $user->best_streak,
            'daily_lives' => $user->daily_lives,
        ],
    );
    }
}