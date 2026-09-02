<?php

namespace App\Listeners;

use App\Events\Player\StreakAtRisk;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchStreakAtRiskAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(StreakAtRisk $event): void
    {
        $user = $event->user->fresh();

        $this->dispatcher->dispatch(
            'streak_at_risk',

            data: [
                'current_streak' => $event->currentStreak,
                'hours_remaining' => $event->hoursRemaining,
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

                'hours_remaining' => $event->hoursRemaining,
            ],
        );
    }
}