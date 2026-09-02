<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */

    'general' => [

        [
            'key' => 'app.name',
            'name' => 'app_name',
            'label' => 'Application Name',
            'type' => 'text',
            'default' => 'Teyaqi',
        ],

        [
            'key' => 'app.description',
            'name' => 'app_description',
            'label' => 'Application Description',
            'type' => 'textarea',
            'default' => 'Learn through play',
        ],

        [
            'key' => 'app.support_email',
            'name' => 'app_support_email',
            'label' => 'Support Email',
            'type' => 'email',
            'default' => 'support@teyaqi.com',
        ],

        [
            'key' => 'app.frontend_url',
            'name' => 'app_frontend_url',
            'label' => 'Frontend URL',
            'type' => 'text',
            'default' => 'http://localhost:3000',
        ],

        [
            'key' => 'app.backend_url',
            'name' => 'app_backend_url',
            'label' => 'Backend URL',
            'type' => 'text',
            'default' => 'http://localhost:8000',
        ],

        [
            'key' => 'app.language',
            'name' => 'app_language',
            'label' => 'Language',
            'type' => 'select',
            'default' => 'en',
            'options' => [
                [
                    'value' => 'en',
                    'label' => 'English'
                ],
                [
                    'value' => 'am',
                    'label' => 'Amharic'
                ],
            ],
        ],

        [
            'key' => 'app.timezone',
            'name' => 'app_timezone',
            'label' => 'Timezone',
            'type' => 'text',
            'default' => 'Africa/Addis_Ababa',
        ],

        [
            'key' => 'app.logo',
            'name' => 'app_logo',
            'label' => 'Application Logo',
            'type' => 'file',
        ],


        [
            'key' => 'app.favicon',
            'name' => 'app_favicon',
            'label' => 'Favicon',
            'type' => 'file',
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Game Settings
    |--------------------------------------------------------------------------
    */

    'game' => [

        [
            'key' => 'game.questions_per_round',
            'name' => 'questions_per_round',
            'label' => 'Questions Per Round',
            'type' => 'number',
            'default' => 10,
        ],

        [
            'key' => 'game.answer_time',
            'name' => 'answer_time',
            'label' => 'Answer Time Seconds',
            'type' => 'number',
            'default' => 15,
        ],

        [
            'key' => 'game.allow_skip',
            'name' => 'allow_skip',
            'label' => 'Allow Skip Question',
            'type' => 'toggle',
            'default' => false,
        ],

        [
            'key' => 'game.daily_challenge_enabled',
            'name' => 'daily_challenge_enabled',
            'label' => 'Enable Daily Challenge',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'game.random_questions',
            'name' => 'random_questions',
            'label' => 'Random Questions',
            'type' => 'toggle',
            'default' => true,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Rewards Settings
    |--------------------------------------------------------------------------
    */

    'rewards' => [

        [
            'key' => 'reward.correct_answer_xp',
            'name' => 'correct_answer_xp',
            'label' => 'XP For Correct Answer',
            'type' => 'number',
            'default' => 10,
        ],

        [
            'key' => 'reward.wrong_answer_xp',
            'name' => 'wrong_answer_xp',
            'label' => 'XP Lost For Wrong Answer',
            'type' => 'number',
            'default' => 0,
        ],

        [
            'key' => 'reward.daily_bonus',
            'name' => 'daily_bonus',
            'label' => 'Daily Login Bonus XP',
            'type' => 'number',
            'default' => 50,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Lives Settings
    |--------------------------------------------------------------------------
    */

    'lives' => [

        [
            'key' => 'lives.max',
            'name' => 'max_lives',
            'label' => 'Maximum Lives',
            'type' => 'number',
            'default' => 5,
        ],

        [
            'key' => 'lives.recovery_time',
            'name' => 'recovery_time',
            'label' => 'Recovery Time Minutes',
            'type' => 'number',
            'default' => 30,
        ],

        [
            'key' => 'lives.enabled',
            'name' => 'lives_enabled',
            'label' => 'Enable Lives System',
            'type' => 'toggle',
            'default' => true,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Streak Settings
    |--------------------------------------------------------------------------
    */

    'streak' => [

        [
            'key' => 'streak.enabled',
            'name' => 'streak_enabled',
            'label' => 'Enable Streak System',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'streak.daily_requirement',
            'name' => 'daily_requirement',
            'label' => 'Daily Challenge Requirement',
            'type' => 'number',
            'default' => 1,
        ],

        [
            'key' => 'streak.reward',
            'name' => 'streak_reward',
            'label' => 'Streak Reward XP',
            'type' => 'number',
            'default' => 25,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Leaderboard Settings
    |--------------------------------------------------------------------------
    */

    'leaderboard' => [

        [
            'key' => 'leaderboard.enabled',
            'name' => 'leaderboard_enabled',
            'label' => 'Enable Leaderboard',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'leaderboard.weekly',
            'name' => 'weekly_leaderboard',
            'label' => 'Weekly Leaderboard',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'leaderboard.friends',
            'name' => 'friend_leaderboard',
            'label' => 'Friend Leaderboard',
            'type' => 'toggle',
            'default' => true,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | AI Settings
    |--------------------------------------------------------------------------
    */

    'ai' => [

        [
            'key' => 'ai.enabled',
            'name' => 'ai_enabled',
            'label' => 'Enable AI Assistant',
            'type' => 'toggle',
            'default' => false,
        ],

        [
            'key' => 'ai.provider',
            'name' => 'ai_provider',
            'label' => 'AI Provider',
            'type' => 'select',
            'default' => 'openai',
            'options' => [
                [
                    'value' => 'openai',
                    'label' => 'OpenAI'
                ],
                [
                    'value' => 'gemini',
                    'label' => 'Google Gemini'
                ],
            ],
        ],

        [
            'key' => 'ai.model',
            'name' => 'ai_model',
            'label' => 'AI Model',
            'type' => 'text',
            'default' => 'gpt-5-mini',
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Telegram Settings
    |--------------------------------------------------------------------------
    */

    'telegram' => [

        [
            'key' => 'telegram.enabled',
            'name' => 'telegram_enabled',
            'label' => 'Enable Telegram Login',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'telegram.bot_token',
            'name' => 'telegram_bot_token',
            'label' => 'Bot Token',
            'type' => 'secret',
            'default' => '',
        ],

        [
            'key' => 'telegram.bot_username',
            'name' => 'telegram_bot_username',
            'label' => 'Bot Username',
            'type' => 'text',
            'default' => '',
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [

        [
            'key' => 'notifications.email',
            'name' => 'email_notifications',
            'label' => 'Email Notifications',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'notifications.push',
            'name' => 'push_notifications',
            'label' => 'Push Notifications',
            'type' => 'toggle',
            'default' => true,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'feature_flags' => [

        [
            'key' => 'features.friends',
            'name' => 'friends_feature',
            'label' => 'Friends System',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'features.challenges',
            'name' => 'challenge_feature',
            'label' => 'Challenge System',
            'type' => 'toggle',
            'default' => true,
        ],

        [
            'key' => 'features.ai',
            'name' => 'ai_feature',
            'label' => 'AI Features',
            'type' => 'toggle',
            'default' => false,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [

        [
            'key' => 'security.max_login_attempts',
            'name' => 'max_login_attempts',
            'label' => 'Maximum Login Attempts',
            'type' => 'number',
            'default' => 5,
        ],

        [
            'key' => 'security.session_timeout',
            'name' => 'session_timeout',
            'label' => 'Session Timeout Minutes',
            'type' => 'number',
            'default' => 120,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | System
    |--------------------------------------------------------------------------
    */

    'system' => [

        [
            'key' => 'system.version',
            'name' => 'system_version',
            'label' => 'System Version',
            'type' => 'text',
            'default' => '1.0.0',
        ],

        [
            'key' => 'system.debug',
            'name' => 'debug_mode',
            'label' => 'Debug Mode',
            'type' => 'toggle',
            'default' => false,
        ],

    ],



    /*
    |--------------------------------------------------------------------------
    | About
    |--------------------------------------------------------------------------
    */

    'about' => [

        [
            'key' => 'about.company',
            'name' => 'company_name',
            'label' => 'Company Name',
            'type' => 'text',
            'default' => 'Teyaqi',
        ],

        [
            'key' => 'about.website',
            'name' => 'company_website',
            'label' => 'Website',
            'type' => 'text',
            'default' => 'https://teyaqi.com',
        ],

        [
            'key' => 'about.version',
            'name' => 'app_version',
            'label' => 'Application Version',
            'type' => 'text',
            'default' => '1.0.0',
        ],

    ],

];