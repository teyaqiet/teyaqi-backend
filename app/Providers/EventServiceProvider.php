<?php

namespace App\Providers;

use App\Events\Player\StreakReached;
use App\Listeners\DispatchStreakReachedAutomation;

use App\Events\Player\LevelUp;
use App\Listeners\DispatchLevelUpAutomation;

use App\Events\Player\XpMilestoneReached;
use App\Listeners\DispatchXpMilestoneAutomation;

use App\Events\Player\GameCompleted;
use App\Listeners\DispatchGameCompletedAutomation;

// Daily / Retention Events
use App\Events\Player\PlayerInactive;
use App\Listeners\DispatchPlayerInactiveAutomation;

use App\Events\Player\PlayerReturned;
use App\Listeners\DispatchPlayerReturnedAutomation;

use App\Events\Player\StreakAtRisk;
use App\Listeners\DispatchStreakAtRiskAutomation;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /*
        |--------------------------------------------------------------------------
        | Streak Reached
        |--------------------------------------------------------------------------
        */

        StreakReached::class => [
            DispatchStreakReachedAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | Level Up
        |--------------------------------------------------------------------------
        */

        LevelUp::class => [
            DispatchLevelUpAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | XP Milestone
        |--------------------------------------------------------------------------
        */

        XpMilestoneReached::class => [
            DispatchXpMilestoneAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | Game Completed
        |--------------------------------------------------------------------------
        */

        GameCompleted::class => [
            DispatchGameCompletedAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | Player Inactive
        |--------------------------------------------------------------------------
        */

        PlayerInactive::class => [
            DispatchPlayerInactiveAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | Player Returned
        |--------------------------------------------------------------------------
        */

        PlayerReturned::class => [
            DispatchPlayerReturnedAutomation::class,
        ],


        /*
        |--------------------------------------------------------------------------
        | Streak At Risk
        |--------------------------------------------------------------------------
        */

        StreakAtRisk::class => [
            DispatchStreakAtRiskAutomation::class,
        ],

    ];


    public function boot(): void
    {
        //
    }
}