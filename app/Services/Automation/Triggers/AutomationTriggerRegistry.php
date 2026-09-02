<?php

namespace App\Services\Automation\Triggers;

class AutomationTriggerRegistry
{
    public function all(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Game / Progress
            |--------------------------------------------------------------------------
            */

            'streak_reached' => [
                'label' => 'Streak Reached',
                'description' => 'Triggered when a player reaches a streak milestone.',
            ],

            'level_up' => [
                'label' => 'Level Up',
                'description' => 'Triggered when a player reaches a new level.',
            ],

            'xp_milestone' => [
                'label' => 'XP Milestone',
                'description' => 'Triggered when a player reaches an XP milestone.',
            ],

            'game_completed' => [
                'label' => 'Game Completed',
                'description' => 'Triggered when a player completes a game.',
            ],


            /*
            |--------------------------------------------------------------------------
            | Lives
            |--------------------------------------------------------------------------
            */

            'lives_low' => [
                'label' => 'Lives Low',
                'description' => 'Triggered when a player has low remaining lives.',
            ],


            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            'player_registered' => [
                'label' => 'Player Registered',
                'description' => 'Triggered when a new player registers.',
            ],


            /*
            |--------------------------------------------------------------------------
            | Daily / Retention
            |--------------------------------------------------------------------------
            */

            'player_inactive' => [
                'label' => 'Player Inactive',
                'description' => 'Triggered when a player has been inactive for a defined period.',
            ],

            'player_returned' => [
                'label' => 'Player Returned',
                'description' => 'Triggered when an inactive player returns and becomes active again.',
            ],

            'streak_at_risk' => [
                'label' => 'Streak At Risk',
                'description' => 'Triggered when a player is approaching the end of their streak window without activity.',
            ],

        ];
    }


    public function get(string $type): ?array
    {
        return $this->all()[$type] ?? null;
    }


    public function exists(string $type): bool
    {
        return array_key_exists(
            $type,
            $this->all()
        );
    }
}