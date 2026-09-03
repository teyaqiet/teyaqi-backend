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

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    */

    'backups' => [

        'enabled' => env(
            'OPERATIONS_BACKUPS_ENABLED',
            true
        ),

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

        /*
        |--------------------------------------------------------------------------
        | MySQL Backup
        |--------------------------------------------------------------------------
        */

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

    /*
    |--------------------------------------------------------------------------
    | Deployments
    |--------------------------------------------------------------------------
    */

    'deployments' => [

        /*
        |--------------------------------------------------------------------------
        | Deployment Settings
        |--------------------------------------------------------------------------
        */

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
        | Deployment Executables
        |--------------------------------------------------------------------------
        |
        | These paths allow the Operations Center to run deployment commands
        | consistently even when Laravel is executed by Apache/PHP with a
        | different PATH from the normal CLI environment.
        |
        */

        'binaries' => [

            'composer' => env(
                'OPERATIONS_COMPOSER_BINARY',
                'composer'
            ),

            'node' => env(
                'OPERATIONS_NODE_BINARY',
                'node'
            ),

            /*
             * Kept for reference/backward compatibility.
             *
             * The deployment pipeline does NOT use npm.cmd directly.
             * It uses node + npm_cli to avoid the Windows cmd.exe
             * environment issue.
             */
            'npm' => env(
                'OPERATIONS_NPM_BINARY',
                'npm'
            ),

            /*
             * Direct npm CLI entry point.
             *
             * Instead of:
             *
             *     npm.cmd -> cmd.exe -> node.exe
             *
             * the deployment system uses:
             *
             *     node.exe -> npm-cli.js
             */
            'npm_cli' => env(
                'OPERATIONS_NPM_CLI',
                'C:/Program Files/nodejs/node_modules/npm/bin/npm-cli.js'
            ),

            'php' => env(
                'OPERATIONS_PHP_BINARY',
                'php'
            ),
        ],

        /*
        |--------------------------------------------------------------------------
        | Deployment Pipeline
        |--------------------------------------------------------------------------
        */

        'pipeline' => [

            /*
            |--------------------------------------------------------------------------
            | Composer
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | NPM Dependencies
            |--------------------------------------------------------------------------
            */

            'npm' => [

                'enabled' => env(
                    'OPERATIONS_DEPLOYMENT_NPM_ENABLED',
                    true
                ),

                /*
                 * The first executable is replaced by
                 * resolvePipelineCommand() with:
                 *
                 * node.exe npm-cli.js
                 */
                'command' => [
                    'npm',
                    'ci',
                ],

                'timeout' => (int) env(
                    'OPERATIONS_DEPLOYMENT_NPM_TIMEOUT',
                    600
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Frontend Build
            |--------------------------------------------------------------------------
            */

            'build' => [

                'enabled' => env(
                    'OPERATIONS_DEPLOYMENT_BUILD_ENABLED',
                    true
                ),

                /*
                 * The first executable is replaced by
                 * resolvePipelineCommand() with:
                 *
                 * node.exe npm-cli.js
                 */
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

            /*
            |--------------------------------------------------------------------------
            | Database Migrations
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Framework Optimization
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Queue Restart
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Health Check
            |--------------------------------------------------------------------------
            */

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