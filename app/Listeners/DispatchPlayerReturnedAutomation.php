<?php

namespace App\Listeners;

use App\Events\Player\PlayerReturned;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchPlayerReturnedAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(PlayerReturned $event): void
    {
        $user = $event->user->fresh();

        $this->dispatcher->dispatch(
            'player_returned',

            data: [
                'days_inactive' => $event->daysInactive,
                'previous_streak' => $event->previousStreak,
            ],

            context: [
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'telegram_id' => $user->telegram_id,

                'level' => $user->level_data['level'] ?? 1,
                'total_xp' => $user->total_xp,

                'current_streak' => $user->current_streak,
                'best_streak' => $user->best_streak,

                'daily_lives' => $user->daily_lives,

                'days_inactive' => $event->daysInactive,
                'previous_streak' => $event->previousStreak,
            ],
        );
    }
}