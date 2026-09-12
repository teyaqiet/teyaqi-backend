<?php

namespace App\Console\Commands;

use App\Events\Player\PlayerInactive;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class DispatchPlayerInactivity extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'players:dispatch-inactivity';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch PlayerInactive events for players who have not played their Daily Challenge today.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting player inactivity detection...');

        $checked = 0;
        $inactive = 0;
        $skippedActive = 0;
        $skippedNew = 0;
        $skippedDuplicate = 0;

        /*
        |--------------------------------------------------------------------------
        | Only consider players who have a streak record.
        |--------------------------------------------------------------------------
        |
        | Players who have never started playing are not considered inactive.
        |
        */

        User::query()
            ->whereNotNull('telegram_id')
            ->whereHas('streak')
            ->with('streak')
            ->chunkById(100, function ($users) use (
                &$checked,
                &$inactive,
                &$skippedActive,
                &$skippedNew,
                &$skippedDuplicate
            ) {
                foreach ($users as $user) {
                    $checked++;

                    $streak = $user->streak;

                    if (!$streak) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Use the player's configured timezone.
                    |--------------------------------------------------------------------------
                    */

                    $timezone = $streak->timezone ?: 'Africa/Addis_Ababa';

                    $today = Carbon::now($timezone)->toDateString();

                    /*
                    |--------------------------------------------------------------------------
                    | Has the player actually played today?
                    |--------------------------------------------------------------------------
                    |
                    | We intentionally use StreakHistory rather than last_played_at.
                    | Freeze shields can modify last_played_at without actual gameplay.
                    |
                    */

                    $playedToday = $user->streakHistories()
                        ->where('status', 'played')
                        ->whereDate('activity_date', $today)
                        ->exists();

                    if ($playedToday) {
                        $skippedActive++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find the player's most recent real Daily Challenge play.
                    |--------------------------------------------------------------------------
                    */

                    $lastPlayedHistory = $user->streakHistories()
                        ->where('status', 'played')
                        ->orderByDesc('activity_date')
                        ->first();

                    /*
                    |--------------------------------------------------------------------------
                    | Never send inactivity notifications to players
                    | who have never played.
                    |--------------------------------------------------------------------------
                    */

                    if (!$lastPlayedHistory) {
                        $skippedNew++;

                        continue;
                    }

                    $lastPlayedDate = Carbon::createFromFormat(
    'Y-m-d',
    $lastPlayedHistory->activity_date->toDateString(),
    $timezone
)->startOfDay();

$todayDate = Carbon::now($timezone)->startOfDay();

$daysInactive = (int) $lastPlayedDate->diffInDays($todayDate);

                    /*
                    |--------------------------------------------------------------------------
                    | Safety check.
                    |--------------------------------------------------------------------------
                    */

                    if ($daysInactive < 1) {
    $this->line(
        "Player #{$user->id} — {$daysInactive} day(s) inactive. Skipping."
    );

    continue;
}

                    /*
                    |--------------------------------------------------------------------------
                    | Prevent duplicate inactivity events for the same player
                    | on the same local calendar day.
                    |--------------------------------------------------------------------------
                    |
                    | The scheduler can safely run more than once per day.
                    |
                    */

                    $idempotencyKey = sprintf(
                        'player-inactive:%s:%s',
                        $user->id,
                        $today
                    );

                    $wasLocked = Cache::add(
                        $idempotencyKey,
                        true,
                        now()->addDays(2)
                    );

                    if (!$wasLocked) {
                        $skippedDuplicate++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Previous streak
                    |--------------------------------------------------------------------------
                    */

                    $previousStreak = (int) ($streak->current_streak ?? 0);

                    /*
                    |--------------------------------------------------------------------------
                    | Last activity timestamp.
                    |--------------------------------------------------------------------------
                    |
                    | StreakHistory only stores a date, so we use the start of
                    | the player's last active day as the activity timestamp.
                    |
                    */

                    $lastActivityAt = $lastPlayedDate
                        ->copy()
                        ->utc()
                        ->toIso8601String();

                    /*
                    |--------------------------------------------------------------------------
                    | Dispatch the event.
                    |--------------------------------------------------------------------------
                    |
                    | The event listener will send this into the Automation system.
                    | This command does NOT send Telegram messages directly.
                    |
                    */

                    event(
                        new PlayerInactive(
                            user: $user->fresh(),
                            daysInactive: $daysInactive,
                            previousStreak: $previousStreak,
                            lastActivityAt: $lastActivityAt,
                        )
                    );

                    $inactive++;

                    $this->line(
                        "Player #{$user->id} — {$daysInactive} day(s) inactive."
                    );
                }
            });

        $this->newLine();

        $this->info('Inactivity detection completed.');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Players checked', $checked],
                ['Inactive events dispatched', $inactive],
                ['Already played today', $skippedActive],
                ['Never played', $skippedNew],
                ['Already processed today', $skippedDuplicate],
            ]
        );

        return self::SUCCESS;
    }
}