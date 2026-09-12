<?php

namespace App\Services\Automation\Conditions;

class AutomationConditionRegistry
{
    /**
     * Operators available for each field type.
     */
    protected array $operators = [

        'number' => [
            [
                'value' => 'equals',
                'label' => 'Equals',
            ],
            [
                'value' => 'not_equals',
                'label' => 'Not Equals',
            ],
            [
                'value' => 'greater_than',
                'label' => 'Greater Than',
            ],
            [
                'value' => 'greater_than_or_equal',
                'label' => 'Greater Than or Equal',
            ],
            [
                'value' => 'less_than',
                'label' => 'Less Than',
            ],
            [
                'value' => 'less_than_or_equal',
                'label' => 'Less Than or Equal',
            ],
        ],

        'string' => [
            [
                'value' => 'equals',
                'label' => 'Equals',
            ],
            [
                'value' => 'not_equals',
                'label' => 'Not Equals',
            ],
            [
                'value' => 'contains',
                'label' => 'Contains',
            ],
            [
                'value' => 'not_contains',
                'label' => 'Does Not Contain',
            ],
            [
                'value' => 'is_empty',
                'label' => 'Is Empty',
            ],
            [
                'value' => 'is_not_empty',
                'label' => 'Is Not Empty',
            ],
        ],

        'boolean' => [
            [
                'value' => 'equals',
                'label' => 'Equals',
            ],
            [
                'value' => 'not_equals',
                'label' => 'Not Equals',
            ],
        ],

        'date' => [
            [
                'value' => 'equals',
                'label' => 'Is',
            ],
            [
                'value' => 'not_equals',
                'label' => 'Is Not',
            ],
            [
                'value' => 'greater_than',
                'label' => 'After',
            ],
            [
                'value' => 'less_than',
                'label' => 'Before',
            ],
        ],
    ];


    /**
     * All available condition fields.
     *
     * The key represents the path that will be resolved
     * from the automation trigger context/data.
     */
    public function all(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Player
            |--------------------------------------------------------------------------
            */

            'name' => [
                'label' => 'Player Name',
                'group' => 'Player',
                'type' => 'string',
                'description' => 'The player display name.',
                'triggers' => ['*'],
            ],

            'username' => [
                'label' => 'Username',
                'group' => 'Player',
                'type' => 'string',
                'description' => 'The player username.',
                'triggers' => ['*'],
            ],

            'telegram_id' => [
                'label' => 'Telegram ID',
                'group' => 'Player',
                'type' => 'string',
                'description' => 'The player Telegram ID.',
                'triggers' => ['*'],
            ],

            'level' => [
                'label' => 'Level',
                'group' => 'Player',
                'type' => 'number',
                'description' => 'The player current level.',
                'triggers' => ['*'],
            ],

            'total_xp' => [
                'label' => 'Total XP',
                'group' => 'Player',
                'type' => 'number',
                'description' => 'The player total XP.',
                'triggers' => ['*'],
            ],

            'current_streak' => [
                'label' => 'Current Streak',
                'group' => 'Player',
                'type' => 'number',
                'description' => 'The player current streak.',
                'triggers' => ['*'],
            ],

            'best_streak' => [
                'label' => 'Best Streak',
                'group' => 'Player',
                'type' => 'number',
                'description' => 'The player best streak.',
                'triggers' => ['*'],
            ],

            'daily_lives' => [
                'label' => 'Lives',
                'group' => 'Player',
                'type' => 'number',
                'description' => 'The player remaining daily lives.',
                'triggers' => ['*'],
            ],


            /*
            |--------------------------------------------------------------------------
            | Trigger
            |--------------------------------------------------------------------------
            */

            'trigger.type' => [
                'label' => 'Trigger Type',
                'group' => 'Trigger',
                'type' => 'string',
                'description' => 'The event that started the automation.',
                'triggers' => ['*'],
            ],


            /*
            |--------------------------------------------------------------------------
            | Streak Reached
            |--------------------------------------------------------------------------
            */

            'trigger.data.streak' => [
                'label' => 'Reached Streak',
                'group' => 'Streak',
                'type' => 'number',
                'description' => 'The streak milestone that was reached.',
                'triggers' => [
                    'streak_reached',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | XP Milestone
            |--------------------------------------------------------------------------
            */

            'trigger.data.previous_xp' => [
                'label' => 'Previous XP',
                'group' => 'XP Milestone',
                'type' => 'number',
                'description' => 'The player XP before the milestone was reached.',
                'triggers' => [
                    'xp_milestone',
                ],
            ],

            'trigger.data.new_xp' => [
                'label' => 'New XP',
                'group' => 'XP Milestone',
                'type' => 'number',
                'description' => 'The player XP after the milestone was reached.',
                'triggers' => [
                    'xp_milestone',
                ],
            ],

            'trigger.data.milestone' => [
                'label' => 'Milestone',
                'group' => 'XP Milestone',
                'type' => 'number',
                'description' => 'The XP milestone that was reached.',
                'triggers' => [
                    'xp_milestone',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Game Completed
            |--------------------------------------------------------------------------
            */

            'trigger.data.session_id' => [
                'label' => 'Session ID',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'The completed game session ID.',
                'triggers' => [
                    'game_completed',
                ],
            ],

            'trigger.data.correct_answers' => [
                'label' => 'Correct Answers',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'Number of correct answers in the game.',
                'triggers' => [
                    'game_completed',
                ],
            ],

            'trigger.data.total_questions' => [
                'label' => 'Total Questions',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'Total questions in the completed game.',
                'triggers' => [
                    'game_completed',
                ],
            ],

            'trigger.data.lives_lost' => [
                'label' => 'Lives Lost',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'Number of lives lost during the game.',
                'triggers' => [
                    'game_completed',
                ],
            ],

            'trigger.data.xp_earned' => [
                'label' => 'XP Earned',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'XP earned from the completed game.',
                'triggers' => [
                    'game_completed',
                ],
            ],

            'trigger.data.perfect_bonus' => [
                'label' => 'Perfect Bonus',
                'group' => 'Game',
                'type' => 'number',
                'description' => 'Bonus XP awarded for a perfect game.',
                'triggers' => [
                    'game_completed',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Player Inactive / Player Returned
            |--------------------------------------------------------------------------
            |
            | These fields are shared because both retention events carry
            | days_inactive and previous_streak.
            |
            */

            'trigger.data.days_inactive' => [
                'label' => 'Days Inactive',
                'group' => 'Retention',
                'type' => 'number',
                'description' => 'Number of days the player was inactive.',
                'triggers' => [
                    'player_inactive',
                    'player_returned',
                ],
            ],

            'trigger.data.previous_streak' => [
                'label' => 'Previous Streak',
                'group' => 'Retention',
                'type' => 'number',
                'description' => 'The player streak before the period of inactivity.',
                'triggers' => [
                    'player_inactive',
                    'player_returned',
                ],
            ],

            'trigger.data.last_activity_at' => [
                'label' => 'Last Activity',
                'group' => 'Retention',
                'type' => 'date',
                'description' => 'The date and time of the player’s last Daily Challenge activity.',
                'triggers' => [
                    'player_inactive',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | Streak At Risk
            |--------------------------------------------------------------------------
            */

            'trigger.data.current_streak' => [
                'label' => 'Current Streak',
                'group' => 'Retention',
                'type' => 'number',
                'description' => 'The player streak that is currently at risk.',
                'triggers' => [
                    'streak_at_risk',
                ],
            ],

            'trigger.data.hours_remaining' => [
                'label' => 'Hours Remaining',
                'group' => 'Retention',
                'type' => 'number',
                'description' => 'Number of hours remaining before the player loses the streak.',
                'triggers' => [
                    'streak_at_risk',
                ],
            ],

        ];
    }


    /**
     * Get a single field.
     */
    public function get(string $field): ?array
    {
        return $this->all()[$field] ?? null;
    }


    /**
     * Check whether a field exists.
     */
    public function exists(string $field): bool
    {
        return array_key_exists($field, $this->all());
    }


    /**
     * Get operators for a field.
     */
    public function operators(string $field): array
    {
        $definition = $this->get($field);

        if (!$definition) {
            return [];
        }

        return $this->operators[
            $definition['type'] ?? 'string'
        ] ?? [];
    }


    /**
     * Get fields available for a trigger.
     */
    public function forTrigger(string $triggerType): array
    {
        return collect($this->all())
            ->filter(function (array $field) use ($triggerType) {

                $triggers = $field['triggers'] ?? [];

                return in_array('*', $triggers, true)
                    || in_array($triggerType, $triggers, true);
            })
            ->all();
    }


    /**
     * Get fields grouped for the UI.
     */
    public function groupedForTrigger(string $triggerType): array
    {
        return collect(
            $this->forTrigger($triggerType)
        )
            ->groupBy('group')
            ->map(
                fn ($fields) => $fields->all()
            )
            ->all();
    }
}