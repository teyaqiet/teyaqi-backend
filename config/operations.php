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
    'enabled' => env('OPERATIONS_DEPLOYMENTS_ENABLED', true),

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
],

];