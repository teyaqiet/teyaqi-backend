<?php

namespace App\Listeners;

use App\Events\Player\GameCompleted;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

class DispatchGameCompletedAutomation
{
    public function __construct(
        protected AutomationTriggerDispatcher $dispatcher
    ) {
    }

    public function handle(GameCompleted $event): void
    {
        $user = $event->user->fresh();
        $session = $event->session->fresh();

        $this->dispatcher->dispatch(
            'game_completed',

            data: [
                'session_id' => $session->id,
                'correct_answers' => $session->correct_answers,
                'total_questions' => $session->total_questions,
                'lives_lost' => $session->lives_lost,
                'xp_earned' => $session->xp_earned,
                'perfect_bonus' => $session->bonus_xp,
            ],

            context: [
                'user_id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'telegram_id' => $user->telegram_id,

                'level' => $user->level,
                'total_xp' => $user->total_xp,

                'current_streak' => $user->current_streak,
                'best_streak' => $user->best_streak,

                'daily_lives' => $user->daily_lives,

                'correct_answers' => $session->correct_answers,
                'total_questions' => $session->total_questions,
                'lives_lost' => $session->lives_lost,
                'xp_earned' => $session->xp_earned,
            ],
        );
    }
}