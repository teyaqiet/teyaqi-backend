<?php

namespace App\Services\Automation\Triggers;

class TriggerTypes
{
    /*
    |--------------------------------------------------------------------------
    | Player
    |--------------------------------------------------------------------------
    */

    public const PLAYER_REGISTERED = 'player_registered';

    public const PLAYER_LOGIN = 'player_login';

    public const PLAYER_INACTIVE = 'player_inactive';

    public const PLAYER_RETURNED = 'player_returned';


    /*
    |--------------------------------------------------------------------------
    | Streak
    |--------------------------------------------------------------------------
    */

    public const STREAK_REACHED = 'streak_reached';

    public const STREAK_AT_RISK = 'streak_at_risk';


    /*
    |--------------------------------------------------------------------------
    | XP / Progress
    |--------------------------------------------------------------------------
    */

    public const XP_EARNED = 'xp_earned';

    public const XP_MILESTONE = 'xp_milestone';

    public const LEVEL_REACHED = 'level_reached';


    /*
    |--------------------------------------------------------------------------
    | Game / Challenges
    |--------------------------------------------------------------------------
    */

    public const GAME_COMPLETED = 'game_completed';

    public const CHALLENGE_COMPLETED = 'challenge_completed';

    public const DAILY_CHALLENGE_COMPLETED =
        'daily_challenge_completed';


    /*
    |--------------------------------------------------------------------------
    | Other
    |--------------------------------------------------------------------------
    */

    public const LIVES_LOW = 'lives_low';


    /*
    |--------------------------------------------------------------------------
    | All Trigger Types
    |--------------------------------------------------------------------------
    */

    public static function all(): array
    {
        return [

            self::PLAYER_REGISTERED,
            self::PLAYER_LOGIN,
            self::PLAYER_INACTIVE,
            self::PLAYER_RETURNED,

            self::STREAK_REACHED,
            self::STREAK_AT_RISK,

            self::XP_EARNED,
            self::XP_MILESTONE,
            self::LEVEL_REACHED,

            self::GAME_COMPLETED,

            self::CHALLENGE_COMPLETED,
            self::DAILY_CHALLENGE_COMPLETED,

            self::LIVES_LOW,
        ];
    }
}