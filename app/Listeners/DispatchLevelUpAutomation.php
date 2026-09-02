<?php

namespace App\Listeners;

use App\Events\Player\LevelUp;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchLevelUpAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(LevelUp $event): void
    {
        $user = $event->user->fresh();

        $this->dispatcher->dispatch(
            'level_up',

            data: [
                'previous_level' => $event->previousLevel,
                'new_level' => $event->newLevel,
            ],

            context: [
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'telegram_id' => $user->telegram_id,

                'previous_level' => $event->previousLevel,
                'level' => $event->newLevel,

                'total_xp' => $user->total_xp,

                'current_streak' => $user->current_streak,
                'best_streak' => $user->best_streak,

                'daily_lives' => $user->daily_lives,
            ],
        );
    }
}