<?php

namespace App\Services;

use App\Events\Player\StreakReached;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StreakService
{
    /**
     * Active Engine: triggered only when a user completes a quiz.
     */
    public function updateStreak(User $user): array
    {
        return DB::transaction(function () use ($user) {

            $streak = $user->streak ?: $user->streak()->create([
                'user_id' => $user->id,
                'freeze_shields' => 1,
                'current_streak' => 0,
                'best_streak' => 0,
                'timezone' => 'Africa/Addis_Ababa',
            ]);

            $tz = $streak->timezone ?: 'Africa/Addis_Ababa';

            $todayStart = Carbon::now($tz)
                ->startOfDay()
                ->utc();

            $yesterdayStart = Carbon::now($tz)
                ->subDay()
                ->startOfDay()
                ->utc();

            $lastPlayed = $streak->last_played_at
                ? Carbon::parse($streak->last_played_at)->utc()
                : null;

            $status = 'maintained';
            $rewardedFreeze = false;
            $streakIncreased = false;

            /*
            |--------------------------------------------------------------------------
            | Determine streak state
            |--------------------------------------------------------------------------
            */

            if (is_null($lastPlayed)) {

                $streak->current_streak = 1;
                $status = 'incremented';
                $streakIncreased = true;

            } elseif (
                $lastPlayed->greaterThanOrEqualTo($todayStart)
            ) {

                // Already played today.
                $status = 'maintained';

            } elseif (
                $lastPlayed->greaterThanOrEqualTo($yesterdayStart)
            ) {

                // Played yesterday, continue streak.
                $streak->current_streak++;
                $status = 'incremented';
                $streakIncreased = true;

            } else {

                // Streak was broken.
                $streak->current_streak = 1;
                $status = 'reset';
            }

            /*
            |--------------------------------------------------------------------------
            | Freeze reward
            |--------------------------------------------------------------------------
            */

            if (
                $streakIncreased &&
                $streak->current_streak > 0 &&
                $streak->current_streak % 7 === 0
            ) {
                $streak->freeze_shields++;
                $rewardedFreeze = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Best streak
            |--------------------------------------------------------------------------
            */

            if (
                $streak->current_streak >
                ($streak->best_streak ?? 0)
            ) {
                $streak->best_streak =
                    $streak->current_streak;
            }

            /*
            |--------------------------------------------------------------------------
            | Persist streak
            |--------------------------------------------------------------------------
            */

            $streak->last_played_at =
                Carbon::now()->utc();

            $streak->save();

            /*
            |--------------------------------------------------------------------------
            | Sync User
            |--------------------------------------------------------------------------
            */

            $user->update([
                'current_streak' =>
                    (int) $streak->current_streak,

                'best_streak' =>
                    (int) $streak->best_streak,

                'last_played_date' =>
                    Carbon::now($tz)->toDateString(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Streak history
            |--------------------------------------------------------------------------
            */

            $user->streakHistories()->updateOrCreate(
                [
                    'activity_date' =>
                        Carbon::now($tz)->toDateString(),
                ],
                [
                    'status' => 'played',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Automation Event
            |--------------------------------------------------------------------------
            |
            | The event implements ShouldDispatchAfterCommit, so the
            | automation will only run after this DB transaction succeeds.
            |
            */

            if ($streakIncreased) {
                event(
                    new StreakReached(
                        $user->fresh(),
                        (int) $streak->current_streak
                    )
                );
            }

            return [
                'status' =>
                    $status,

                'count' =>
                    (int) $streak->current_streak,

                'best_streak' =>
                    (int) $streak->best_streak,

                'freezes_left' =>
                    (int) $streak->freeze_shields,

                'rewarded_freeze' =>
                    $rewardedFreeze,

                'next_reset_at' =>
                    Carbon::now($tz)
                        ->endOfDay()
                        ->utc()
                        ->toIso8601String(),
            ];
        });
    }

    /**
     * Proactive Guard Engine.
     */
    public function passiveStreakCheck(User $user): array
    {
        $user->loadMissing('streak');

        $streak = $user->streak;

        if (!$streak) {
            return [
                'status' => 'dead',
                'current_streak' => 0,
                'next_reset_at' => null,
            ];
        }

        $tz =
            $streak->timezone
            ?: 'Africa/Addis_Ababa';

        $yesterdayStart = Carbon::now($tz)
            ->subDay()
            ->startOfDay()
            ->utc();

        $lastPlayed = $streak->last_played_at
            ? Carbon::parse($streak->last_played_at)->utc()
            : null;

        $nextResetStr = Carbon::now($tz)
            ->endOfDay()
            ->utc()
            ->toIso8601String();

        /*
        |--------------------------------------------------------------------------
        | Still active
        |--------------------------------------------------------------------------
        */

        if (
            is_null($lastPlayed) ||
            $lastPlayed->greaterThanOrEqualTo(
                $yesterdayStart
            )
        ) {
            return [
                'status' =>
                    $streak->current_streak > 0
                        ? 'active'
                        : 'dead',

                'current_streak' =>
                    (int) $streak->current_streak,

                'next_reset_at' =>
                    $nextResetStr,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Missed window
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $user,
            $streak,
            $tz,
            $nextResetStr
        ) {

            /*
            |--------------------------------------------------------------------------
            | Auto freeze
            |--------------------------------------------------------------------------
            */

            if ($streak->freeze_shields > 0) {

                $streak->freeze_shields--;

                $streak->last_played_at =
                    Carbon::now($tz)
                        ->subDay()
                        ->startOfDay()
                        ->utc();

                $streak->save();

                $user->update([
                    'current_streak' =>
                        (int) $streak->current_streak,
                ]);

                $user->streakHistories()->create([
                    'activity_date' =>
                        Carbon::now($tz)
                            ->subDay()
                            ->toDateString(),

                    'status' => 'frozen',
                ]);

                return [
                    'status' => 'frozen',

                    'current_streak' =>
                        (int) $streak->current_streak,

                    'next_reset_at' =>
                        $nextResetStr,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Reset
            |--------------------------------------------------------------------------
            */

            if ($streak->current_streak > 0) {

                $streak->update([
                    'current_streak' => 0,
                ]);

                $user->update([
                    'current_streak' => 0,
                ]);

                return [
                    'status' => 'reset',
                    'current_streak' => 0,
                    'next_reset_at' => $nextResetStr,
                ];
            }

            return [
                'status' => 'dead',
                'current_streak' => 0,
                'next_reset_at' => $nextResetStr,
            ];
        });
    }

    /**
     * Manually consume a freeze shield.
     */
    public function manuallyConsumeFreezeShield(
        User $user
    ): array {
        return DB::transaction(function () use ($user) {

            $user->loadMissing('streak');

            $streak = $user->streak;

            if (
                !$streak ||
                $streak->freeze_shields <= 0
            ) {
                throw new \Exception(
                    'No freeze shields available.'
                );
            }

            $tz =
                $streak->timezone
                ?: 'Africa/Addis_Ababa';

            $streak->freeze_shields--;

            $streak->last_played_at =
                Carbon::now($tz)
                    ->subDay()
                    ->startOfDay()
                    ->utc();

            $streak->save();

            $user->update([
                'current_streak' =>
                    (int) $streak->current_streak,
            ]);

            $user->streakHistories()->create([
                'activity_date' =>
                    Carbon::now($tz)
                        ->subDay()
                        ->toDateString(),

                'status' => 'frozen',
            ]);

            return [
                'success' => true,

                'freezes_left' =>
                    (int) $streak->freeze_shields,

                'current_streak' =>
                    (int) $streak->current_streak,

                'next_reset_at' =>
                    Carbon::now($tz)
                        ->endOfDay()
                        ->utc()
                        ->toIso8601String(),
            ];
        });
    }
}