<?php

namespace App\Observers;

use App\Models\User;
use App\Models\NotificationRule;
use App\Traits\SendsTelegramNotifications;
use Spatie\Permission\Models\Role;

class UserObserver
{
    use SendsTelegramNotifications;

    public function created(User $user): void
    {
        if (Role::where('name', 'player')->exists()) {
            $user->assignRole('player');
        }
    }

    public function updated(User $user): void
    {
        // 1. Check XP (Level Up)
        if ($user->wasChanged('total_xp')) {
            $this->fireRule($user, 'level_up', $user->total_xp);
        }

        // 2. Check Streak Milestone
        if ($user->wasChanged('current_streak')) {
            $this->fireRule($user, 'streak_milestone', $user->current_streak);
        }

        // 3. Check Out of Lives
        if ($user->wasChanged('daily_lives') && $user->daily_lives === 0) {
            $this->fireRule($user, 'out_of_lives', 0);
        }

        // 4. Check Last Play Date (e.g., if they just finished a game)
        if ($user->wasChanged('last_played_at')) {
            // You could send a "Good job playing today!" or 
            // logic for "Welcome back after 3 days"
            $this->fireRule($user, 'last_played', 0); 
        }

        // 5. Check Life Regeneration (Manual or via App)
        if ($user->wasChanged('daily_lives') && $user->daily_lives >= 5) {
            $this->fireRule($user, 'life_refilled', 5);
        }
    }

    protected function fireRule(User $user, string $type, $value): void
{
    $query = NotificationRule::where('event_type', $type)
        ->where('is_active', true);

    // Dynamic Logic:
    $rule = match($type) {
        // Trigger if lives are GREATER THAN OR EQUAL to threshold
        'life_refilled' => $query->where('threshold', '<=', $value)->first(),
        
        // Default: Match exact threshold (XP milestones, Streaks)
        default => $query->where('threshold', $value)->first(),
    };

    if ($rule && $user->telegram_id) {
        $this->dispatchTelegram($user, $rule->message_template);
    }
}
}