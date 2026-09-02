<?php

namespace App\Enums;

enum ChallengeType: string
{
    case DAILY = 'daily';
    case CATEGORY = 'category';
    case TOPIC = 'topic';
    case TIMED = 'timed';
    case RANKED = 'ranked';
    case EVENT = 'event';
    case PRACTICE = 'practice';
    case FRIEND = 'friend';
    case TOURNAMENT = 'tournament';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}