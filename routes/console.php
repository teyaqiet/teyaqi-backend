<?php

use App\Jobs\RecoverStaleBroadcastRecipients;
use App\Models\ApiLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Game Maintenance
|--------------------------------------------------------------------------
*/

Schedule::command(
    'quiz:calibrate-difficulty --min-attempts=20'
)->dailyAt('03:00');

Schedule::command(
    'streaks:reset-expired'
)->dailyAt('00:00');

/*
|--------------------------------------------------------------------------
| Broadcast Recovery
|--------------------------------------------------------------------------
|
| Runs every minute to recover broadcast recipients that
| became stuck in queued or sending state.
|
*/

Schedule::job(
    new RecoverStaleBroadcastRecipients()
)
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Operations Center Alert Monitoring
|--------------------------------------------------------------------------
|
| Check operational conditions every minute and create, update,
| or resolve alerts automatically.
|
*/

Schedule::command('operations:monitor')
    ->everyMinute()
    ->name('operations.alert-monitoring')
    ->withoutOverlapping()
    ->onOneServer();




/*
|--------------------------------------------------------------------------
| API Log Cleanup
|--------------------------------------------------------------------------
|
| Remove API logs older than 14 days.
|
*/

Schedule::call(function () {
    ApiLog::query()
        ->where(
            'created_at',
            '<',
            now()->subDays(14)
        )
        ->delete();
})
    ->daily();