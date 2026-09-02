<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Operations Center
    |--------------------------------------------------------------------------
    */

    'enabled' => env('OPERATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    'environment' => env(
        'OPERATIONS_ENVIRONMENT',
        env('APP_ENV', 'local')
    ),

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'application' => [
        'name' => env('APP_NAME', 'Teyaqi'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    */

    'health' => [
        'database' => true,
        'cache' => true,
        'storage' => true,
        'queue' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'enabled' => true,
        'retention_days' => 90,
    ],


   
'backups' => [

    'enabled' => env('OPERATIONS_BACKUPS_ENABLED', true),

    'disk' => env(
        'OPERATIONS_BACKUP_DISK',
        'local'
    ),

    'path' => env(
        'OPERATIONS_BACKUP_PATH',
        'operations/backups'
    ),

    'retention_days' => (int) env(
        'OPERATIONS_BACKUP_RETENTION_DAYS',
        30
    ),

    'mysql' => [

        'binary' => env(
            'MYSQLDUMP_BINARY',
            'mysqldump'
        ),

        'timeout' => (int) env(
            'MYSQLDUMP_TIMEOUT',
            300
        ),
    ],
],

'deployments' => [
    'enabled' => env(
        'OPERATIONS_DEPLOYMENTS_ENABLED',
        true
    ),

    'environment' => env(
        'OPERATIONS_DEPLOYMENT_ENVIRONMENT',
        'staging'
    ),

    'branch' => env(
        'OPERATIONS_DEPLOYMENT_BRANCH',
        'main'
    ),

    'path' => env(
        'OPERATIONS_DEPLOYMENT_PATH',
        base_path()
    ),

    'timeout' => (int) env(
        'OPERATIONS_DEPLOYMENT_TIMEOUT',
        600
    ),

    'remote' => env(
        'OPERATIONS_DEPLOYMENT_REMOTE',
        'origin'
    ),

    /*
    |--------------------------------------------------------------------------
    | Deployment Pipeline
    |--------------------------------------------------------------------------
    */

    'pipeline' => [
        'composer' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_COMPOSER_ENABLED',
                true
            ),

            'command' => [
                'composer',
                'install',
                '--no-interaction',
                '--prefer-dist',
                '--optimize-autoloader',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_COMPOSER_TIMEOUT',
                600
            ),
        ],

        'npm' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_NPM_ENABLED',
                true
            ),

            'command' => [
                'npm',
                'ci',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_NPM_TIMEOUT',
                600
            ),
        ],

        'build' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_BUILD_ENABLED',
                true
            ),

            'command' => [
                'npm',
                'run',
                'build',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_BUILD_TIMEOUT',
                900
            ),
        ],

        'migrations' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_MIGRATIONS_ENABLED',
                true
            ),

            'command' => [
                'php',
                'artisan',
                'migrate',
                '--force',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_MIGRATIONS_TIMEOUT',
                300
            ),
        ],

        'optimize' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_OPTIMIZE_ENABLED',
                true
            ),

            'command' => [
                'php',
                'artisan',
                'optimize',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_OPTIMIZE_TIMEOUT',
                300
            ),
        ],

        'queue_restart' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_QUEUE_RESTART_ENABLED',
                true
            ),

            'command' => [
                'php',
                'artisan',
                'queue:restart',
            ],

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_QUEUE_RESTART_TIMEOUT',
                60
            ),
        ],

        'health_check' => [
            'enabled' => env(
                'OPERATIONS_DEPLOYMENT_HEALTH_CHECK_ENABLED',
                true
            ),

            'timeout' => (int) env(
                'OPERATIONS_DEPLOYMENT_HEALTH_CHECK_TIMEOUT',
                60
            ),
        ],
    ],
],

];