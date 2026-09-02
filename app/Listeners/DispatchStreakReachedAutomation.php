<?php

namespace App\Listeners;

use App\Events\Player\StreakReached;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchStreakReachedAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(StreakReached $event): void
    {
        $user = $event->user->fresh();

        $this->dispatcher->dispatch(
            'streak_reached',
            data: [
                'streak' => $event->streak,
            ],
            context: [
                'user_id' => $user->id,
                'name' => $user->name,
                'streak' => $event->streak,
                'current_streak' => $user->current_streak,
                'best_streak' => $user->best_streak,
                'total_xp' => $user->total_xp,
                'telegram_id' => $user->telegram_id,
                'username' => $user->username,
            ],
        );
    }
}