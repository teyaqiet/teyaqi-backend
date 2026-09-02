<?php

use App\Http\Controllers\Admin\Operations\AuditLogController;
use App\Http\Controllers\Admin\Operations\OperationsController;
use App\Http\Controllers\Admin\Operations\QueueController;
use App\Http\Controllers\Admin\Operations\CacheController;
use App\Http\Controllers\Admin\Operations\DatabaseController;
use App\Http\Controllers\Admin\Operations\BackupController;
use App\Http\Controllers\Admin\Operations\BackupDiagnosticController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'web',
    'auth:admin',
    \App\Http\Middleware\EnsureAdminIsActive::class,
])
    ->prefix('api/admin/operations')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Operations Overview
        |--------------------------------------------------------------------------
        */

        Route::get('/health', [
            OperationsController::class,
            'health',
        ])->name('operations.health');

        /*
        |--------------------------------------------------------------------------
        | Environment
        |--------------------------------------------------------------------------
        */

        Route::get('/environment', [
            OperationsController::class,
            'environment',
        ])->name('operations.environment');

        /*
        |--------------------------------------------------------------------------
        | System Information
        |--------------------------------------------------------------------------
        */

        Route::get('/system', [
            OperationsController::class,
            'system',
        ])->name('operations.system');

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        Route::get('/audit-logs', [
            AuditLogController::class,
            'index',
        ])->name('operations.audit-logs.index');

        Route::get('/audit-logs/{id}', [
            AuditLogController::class,
            'show',
        ])
            ->whereNumber('id')
            ->name('operations.audit-logs.show');

        /*
        |--------------------------------------------------------------------------
        | Queue Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('queue')
            ->name('operations.queue.')
            ->group(function () {

                Route::get('/', [
                    QueueController::class,
                    'overview',
                ])->name('overview');

                Route::get('/failed', [
                    QueueController::class,
                    'failed',
                ])->name('failed');

                Route::get('/failed/{id}', [
                    QueueController::class,
                    'showFailed',
                ])
                    ->whereNumber('id')
                    ->name('failed.show');

                Route::post('/failed/{id}/retry', [
                    QueueController::class,
                    'retry',
                ])
                    ->whereNumber('id')
                    ->name('failed.retry');

                Route::delete('/failed/{id}', [
                    QueueController::class,
                    'delete',
                ])->name('failed.delete');
            });

        /*
        |--------------------------------------------------------------------------
        | Cache Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('cache')
            ->name('operations.cache.')
            ->group(function () {

                Route::get('/', [
                    CacheController::class,
                    'overview',
                ])->name('overview');

                Route::post('/clear', [
                    CacheController::class,
                    'clear',
                ])->name('clear');

                Route::post('/clear-config', [
                    CacheController::class,
                    'clearConfig',
                ])->name('clear-config');

                Route::post('/clear-routes', [
                    CacheController::class,
                    'clearRoutes',
                ])->name('clear-routes');

                Route::post('/clear-views', [
                    CacheController::class,
                    'clearViews',
                ])->name('clear-views');

                Route::post('/clear-all', [
                    CacheController::class,
                    'clearAll',
                ])->name('clear-all');
            });

        /*
        |--------------------------------------------------------------------------
        | Database Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('database')
            ->name('operations.database.')
            ->group(function () {

                Route::get('/', [
                    DatabaseController::class,
                    'overview',
                ])->name('overview');

                Route::get('/tables', [
                    DatabaseController::class,
                    'tables',
                ])->name('tables');

                Route::get('/migrations', [
                    DatabaseController::class,
                    'migrations',
                ])->name('migrations');
            });

        /*
        |--------------------------------------------------------------------------
        | Backup Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('backups')
            ->name('operations.backups.')
            ->group(function () {

                Route::get('/', [
                    BackupController::class,
                    'overview',
                ])->name('overview');

                Route::get('/list', [
                    BackupController::class,
                    'index',
                ])->name('index');

                Route::post('/create', [
                    BackupController::class,
                    'create',
                ])->name('create');

                Route::get('/{id}', [
                    BackupController::class,
                    'show',
                ])
                    ->whereNumber('id')
                    ->name('show');

                Route::get('/{id}/download', [
                    BackupController::class,
                    'download',
                ])
                    ->whereNumber('id')
                    ->name('download');

                Route::delete('/{id}', [
                    BackupController::class,
                    'delete',
                ])->name('delete');
            });
    });