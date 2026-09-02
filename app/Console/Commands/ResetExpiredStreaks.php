<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ResetExpiredStreaks extends Command
{
    /**
     * The terminal command execution name token
     */
    protected $signature = 'streaks:reset-expired';

    /**
     * The terminal description block
     */
    protected $description = 'Wipe or automatically freeze streaks for users who missed their local timezone midnight cutoff windows.';

    /**
     * Execute the console command safely with minimal memory allocation profiles.
     */
    public function handle(): int
    {
        $this->info('Starting automated user streak expiration synchronization sweep...');
        $autoFrozen = 0;
        $hardReset = 0;

        // 🔋 Chunk processing reads only 100 users at a time to prevent server memory crashes
        User::whereHas('streak', function ($query) {
            $query->where('current_streak', '>', 0);
        })->with('streak')->chunk(100, function ($users) use (&$autoFrozen, &$hardReset) {
            foreach ($users as $user) {
                $streak = $user->streak;
                $tz = $streak->timezone ?? 'Africa/Addis_Ababa';
                
                // 🕒 Build absolute local cutoff timeline boundaries normalized to UTC
                $yesterdayStart = Carbon::now($tz)->subDay()->startOfDay()->utc();
                $lastPlayed = $streak->last_played_at ? Carbon::parse($streak->last_played_at)->utc() : null;

                // 🚨 Trigger check condition: The user completely missed their local calendar yesterday window
                if (is_null($lastPlayed) || $lastPlayed->lt($yesterdayStart)) {
                    
                    if ($streak->freeze_shields > 0) {
                        // 🛡️ MITIGATION ROUTE: Auto-consume an available safety shield item
                        $streak->decrement('freeze_shields');
                        
                        // Fake a historical play anchor to yesterday so they remain alive for today's session
                        $streak->update([
                            'last_played_at' => Carbon::now($tz)->subDay()->startOfDay()->utc()
                        ]);
                        
                        $user->streakHistories()->create([
                            'activity_date' => Carbon::now($tz)->subDay()->toDateString(),
                            'status' => 'frozen'
                        ]);

                        $autoFrozen++;
                    } else {
                        // ❌ DESTRUCTION ROUTE: Clear streak baseline variables entirely
                        $streak->update(['current_streak' => 0]);
                        $user->update(['current_streak' => 0]);

                        $hardReset++;
                    }
                }
            }
        });

        $this->info("Sweep finalized. Protected via Freeze: {$autoFrozen} users. Reset to baseline: {$hardReset} users.");

        return Command::SUCCESS;
    }
}