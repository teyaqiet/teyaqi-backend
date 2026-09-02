<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class LifeManagerService
{
    const MAX_LIVES = 5;
    const REGEN_TIME_MINS = 30;

    /**
     * Lazy-calculates and synchronizes time-based life recovery.
     */
    public function refreshLives(User $user): User
    {
        $now = now();

        // If user already has max lives, just keep the baseline timer current
        if ($user->daily_lives >= self::MAX_LIVES) {
            $user->daily_lives = self::MAX_LIVES;
            $user->lives_updated_at = $now;
            $user->save();
            return $user;
        }

        // Initialize tracking timestamp if empty
        if (!$user->lives_updated_at) {
            $user->lives_updated_at = $now;
            $user->save();
            return $user;
        }

        $lastUpdate = Carbon::parse($user->lives_updated_at);
        $diffInMinutes = $lastUpdate->diffInMinutes($now);

        // Check if at least one 30-minute block has closed
        if ($diffInMinutes >= self::REGEN_TIME_MINS) {
            $gained = floor($diffInMinutes / self::REGEN_TIME_MINS);
            $newBalance = min(self::MAX_LIVES, $user->daily_lives + $gained);

            $user->daily_lives = $newBalance;

            // Move the timestamp forward only by the discrete 30-min chunks consumed.
            // This preserves the fractional remainder for your frontend nextHeartInMs timer!
            $minutesSpent = $gained * self::REGEN_TIME_MINS;
            $user->lives_updated_at = $lastUpdate->addMinutes($minutesSpent);
            $user->save();
        }

        return $user;
    }

    /**
     * Deducts lives based on game performance mistakes.
     */
    public function applyPenalty(User $user, int $wrongAnswersCount): User
    {
        if ($wrongAnswersCount <= 0) {
            return $user;
        }

        $oldLives = $user->daily_lives;
        $user->daily_lives = max(0, $user->daily_lives - $wrongAnswersCount);

        // If they were at full capacity but just lost a life, start the recovery timer right now
        if ($oldLives >= self::MAX_LIVES && $user->daily_lives < self::MAX_LIVES) {
            $user->lives_updated_at = now();
        }

        $user->save();
        return $user;
    }
}