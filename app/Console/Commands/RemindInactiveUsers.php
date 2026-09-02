<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RemindInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remind-inactive-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    // Find users who haven't played in exactly 24 hours
    $oneDayAgo = now()->subDay()->startOfHour();
    
    $users = User::where('last_played_at', $oneDayAgo)->get();

    foreach ($users as $user) {
        $rule = NotificationRule::where('event_type', 'inactivity_reminder')->first();
        
        if ($rule && $user->telegram_id) {
            $this->dispatchTelegram($user, $rule->message_template);
        }
    }
}
}
