<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ChallengeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GameSessionController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\Questions\QuestionController;
use App\Http\Controllers\Admin\Questions\QuestionImportController;
use App\Http\Controllers\Admin\Questions\QuestionExportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ApiLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\BroadcastController;
use App\Http\Controllers\Admin\AutomationController;
use App\Http\Controllers\Admin\AutomationExecutionController;

use App\Models\Automation;

use App\Services\Automation\AutomationEngine;
use App\Services\Automation\Triggers\AutomationTriggerDispatcher;

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Guest Redirect
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->as('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Guest Admin Authentication
        |--------------------------------------------------------------------------
        */

        Route::middleware('guest:admin')->group(function () {

            Route::get(
                '/login',
                [AdminAuthController::class, 'showLoginForm']
            )->name('login');

            Route::post(
                '/login',
                [AdminAuthController::class, 'login']
            )->name('login.post');

        });


        /*
        |--------------------------------------------------------------------------
        | Authenticated Admin Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware([
            'auth:admin',
            \App\Http\Middleware\EnsureAdminIsActive::class,
        ])->group(function () {

/*
|--------------------------------------------------------------------------
| Operations Center
|--------------------------------------------------------------------------
*/

Route::prefix('operations')
    ->name('operations.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Operations Overview
        |--------------------------------------------------------------------------
        */

        Route::view('/', 'admin.operations.index')
            ->name('index');

        /*
        |--------------------------------------------------------------------------
        | System
        |--------------------------------------------------------------------------
        */

        Route::view('/system', 'admin.operations.system')
            ->name('system');

        Route::view('/queue', 'admin.operations.queue')
            ->name('queue');

        Route::view('/cache', 'admin.operations.cache')
            ->name('cache');

        Route::view('/database', 'admin.operations.database')
    ->name('database');

    Route::view('/backups', 'admin.operations.backups')
    ->name('backups');

    Route::view('/deployments', 'admin.operations.deployments')
    ->name('deployments');

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        Route::view('/audit-logs', 'admin.operations.audit-logs')
            ->name('audit-logs');
    });
            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/logout',
                [AdminAuthController::class, 'logout']
            )->name('logout');


            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                [DashboardController::class, 'index']
            )->name('dashboard');


            /*
            |--------------------------------------------------------------------------
            | AI Assistant
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/ai/chat',
                [AiAssistantController::class, 'chat']
            )->name('ai.chat');


            /*
            |--------------------------------------------------------------------------
            | Admin Users
            |--------------------------------------------------------------------------
            */

            Route::patch(
                '/admin-users/{admin_user}/toggle-status',
                [AdminUserController::class, 'toggleStatus']
            )->name('admin-users.toggle-status');

            Route::resource(
                'admin-users',
                AdminUserController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Profile
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/profile',
                [AdminUserController::class, 'profile']
            )->name('profile.index');

            Route::put(
                '/profile',
                [AdminUserController::class, 'updateProfile']
            )->name('profile.update');


            /*
            |--------------------------------------------------------------------------
            | Activity Logs
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/activity-logs',
                [ActivityLogController::class, 'index']
            )->name('activity-logs.index');


            /*
            |--------------------------------------------------------------------------
            | System API Logs
            |--------------------------------------------------------------------------
            */

            Route::prefix('system')
                ->name('system.')
                ->group(function () {

                    Route::get(
                        '/api-logs',
                        [ApiLogController::class, 'index']
                    )->name('api-logs.index');

                    Route::get(
                        '/api-logs/{apiLog}',
                        [ApiLogController::class, 'show']
                    )->name('api-logs.show');

                    Route::get(
                        '/api-logs/export',
                        [ApiLogController::class, 'export']
                    )->name('api-logs.export');

                });


            /*
            |--------------------------------------------------------------------------
            | Settings
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/settings',
                [SettingsController::class, 'index']
            )->name('settings.index');

            Route::post(
                '/settings',
                [SettingsController::class, 'update']
            )->name('settings.update');


            /*
            |--------------------------------------------------------------------------
            | Broadcasts
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'broadcasts',
                BroadcastController::class
            )->only([
                'index',
                'create',
                'store',
                'show',
                'edit',
                'update',
                'destroy',
            ]);

            Route::post(
                '/broadcasts/{broadcast}/prepare',
                [BroadcastController::class, 'prepare']
            )->name('broadcasts.prepare');

            Route::post(
                '/broadcasts/preview',
                [BroadcastController::class, 'preview']
            )->name('broadcasts.preview');

            Route::post(
                '/broadcasts/audience-preview',
                [BroadcastController::class, 'audiencePreview']
            )->name('broadcasts.audience-preview');

            Route::post(
                '/broadcasts/{broadcast}/send',
                [BroadcastController::class, 'send']
            )->name('broadcasts.send');

            Route::post(
                '/broadcasts/{broadcast}/retry-failed',
                [BroadcastController::class, 'retryFailed']
            )->name('broadcasts.retry-failed');

            Route::get(
                '/broadcasts/{broadcast}/progress',
                [BroadcastController::class, 'progress']
            )->name('broadcasts.progress');


            /*
            |--------------------------------------------------------------------------
            | Platform Users
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/users',
                [UserController::class, 'index']
            )->name('users.index');

            Route::get(
                '/users/{user}',
                [UserController::class, 'show']
            )->name('users.show');

            Route::get(
                '/users/{user}/edit',
                [UserController::class, 'edit']
            )->name('users.edit');

            Route::put(
                '/users/{user}',
                [UserController::class, 'update']
            )->name('users.update');

            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->name('users.destroy');

            
            /*
            |--------------------------------------------------------------------------
            | Bulk User Actions
            |--------------------------------------------------------------------------
            */
            
            Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete'])
                ->name('users.bulk-delete');

            Route::post(
                '/users/bulk-delete',
                [UserController::class, 'bulkDelete']
            )->name('users.bulk-delete');

            Route::post(
                '/users/bulk-reset-lives',
                [UserController::class, 'bulkResetLives']
            )->name('users.bulk-reset-lives');

            Route::post(
                '/users/bulk-reset-streak',
                [UserController::class, 'bulkResetStreak']
            )->name('users.bulk-reset-streak');

            Route::post(
                '/users/bulk-activate',
                [UserController::class, 'bulkActivate']
            )->name('users.bulk-activate');

            Route::post(
                '/users/bulk-disable',
                [UserController::class, 'bulkDisable']
            )->name('users.bulk-disable');

            Route::post(
                '/users/bulk-adjust-xp',
                [UserController::class, 'bulkAdjustXp']
            )->name('users.bulk-adjust-xp');

            Route::post(
                '/users/bulk-adjust-coins',
                [UserController::class, 'bulkAdjustCoins']
            )->name('users.bulk-adjust-coins');


            /*
            |--------------------------------------------------------------------------
            | User Actions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/users/{user}/adjust-xp',
                [UserController::class, 'adjustXp']
            )->name('users.adjust-xp');

            Route::post(
                '/users/{user}/adjust-coins',
                [UserController::class, 'adjustCoins']
            )->name('users.adjust-coins');

            Route::post(
                '/users/{user}/reset-streak',
                [UserController::class, 'resetStreak']
            )->name('users.reset-streak');

            Route::post(
                '/users/{user}/reset-lives',
                [UserController::class, 'resetLives']
            )->name('users.reset-lives');


            /*
            |--------------------------------------------------------------------------
            | Questions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/questions/bulk-delete',
                [QuestionController::class, 'bulkDelete']
            )->name('questions.bulk-delete');


            /*
            |--------------------------------------------------------------------------
            | Question Import
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/questions/import',
                [QuestionImportController::class, 'create']
            )->name('questions.import');

            Route::post(
                '/questions/import/preview',
                [QuestionImportController::class, 'preview']
            )->name('questions.import.preview');

            Route::post(
                '/questions/import',
                [QuestionImportController::class, 'store']
            )->name('questions.import.store');

            Route::get(
                '/questions/import/template',
                [QuestionExportController::class, 'template']
            )->name('questions.import.template');


            /*
            |--------------------------------------------------------------------------
            | Question Export
            |--------------------------------------------------------------------------
            */

            // Export page
            Route::get(
                '/questions/export',
                [QuestionExportController::class, 'create']
            )->name('questions.export');

            // Export using filters
            Route::post(
                '/questions/export',
                [QuestionExportController::class, 'export']
            )->name('questions.export.download');

            // Export selected questions
            Route::post(
                '/questions/export-selected',
                [QuestionExportController::class, 'exportSelected']
            )->name('questions.export.selected');


            /*
            |--------------------------------------------------------------------------
            | Question Resource
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'questions',
                QuestionController::class
            );




            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/categories/bulk-delete',
                [CategoryController::class, 'bulkDelete']
            )->name('categories.bulk-delete');

            Route::resource(
                'categories',
                CategoryController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Challenges
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/challenges/bulk-delete',
                [ChallengeController::class, 'bulkDelete']
            )->name('challenges.bulk-delete');

            Route::resource(
                'challenges',
                ChallengeController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Topics
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/topics/bulk-delete',
                [TopicController::class, 'bulkDelete']
            )->name('topics.bulk-delete');

            Route::resource(
                'topics',
                TopicController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Analytics
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/analytics',
                [AnalyticsController::class, 'index']
            )->name('analytics.index');


            /*
            |--------------------------------------------------------------------------
            | Game Sessions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/game-sessions/{gameSession}/recalculate',
                [GameSessionController::class, 'recalculate']
            )->name('game-sessions.recalculate');

            Route::resource(
                'game-sessions',
                GameSessionController::class
            )->only([
                'index',
                'show',
                'destroy',
            ]);

            


            /*
            |--------------------------------------------------------------------------
            | Roles & Permissions
            |--------------------------------------------------------------------------
            */

            Route::resource(
                'roles',
                RoleController::class
            );

            Route::resource(
                'permissions',
                PermissionController::class
            )->only([
                'index',
                'destroy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Temporary Automation Test Route
            |--------------------------------------------------------------------------
            |
            | Keep this only while developing. It can be removed once the
            | builder Test Run is confirmed working.
            |
            */

            Route::get(
                '/test-automation/{automation}',
                function (
                    Automation $automation,
                    AutomationEngine $engine
                ) {

                    $context = [
                        'name' => 'Test Player',
                        'xp' => 1500,
                        'streak' => 7,
                        'sr' => 1200,
                    ];

                    $triggerData = [
                        'source' => 'test',
                    ];

                    $execution = $engine->run(
                        $automation,
                        context: $context,
                        triggerType: 'manual',
                        triggerData: $triggerData
                    );

                    return response()->json([
                        'success' => true,
                        'execution_id' =>
                            $execution->execution_id,
                        'status' =>
                            $execution->status,
                        'context' =>
                            $execution->context,
                        'message' =>
                            'Automation executed successfully.',
                    ]);
                }
            )->name('test-automation');


            /*
            |--------------------------------------------------------------------------
            | Automations
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/automations',
                [AutomationController::class, 'index']
            )->name('automations.index');


            Route::get(
                '/automations/create',
                [AutomationController::class, 'create']
            )->name('automations.create');


            Route::post(
                '/automations',
                [AutomationController::class, 'store']
            )->name('automations.store');


            Route::get(
                '/automations/{automation}/edit',
                [AutomationController::class, 'edit']
            )->name('automations.edit');


            Route::put(
                '/automations/{automation}/details',
                [AutomationController::class, 'updateDetails']
            )->name('automations.details.update');


            /*
            |--------------------------------------------------------------------------
            | Automation Validation
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/automations/{automation}/validate',
                [AutomationController::class, 'validate']
            )->name('automations.validate');


            /*
            |--------------------------------------------------------------------------
            | Automation Builder
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/automations/{automation}/builder',
                [AutomationController::class, 'builder']
            )->name('automations.builder');


            /*
            |--------------------------------------------------------------------------
            | Automation Workflow Save
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/automations/{automation}',
                [AutomationController::class, 'update']
            )->name('automations.update');


            /*
            |--------------------------------------------------------------------------
            | Automation State Actions
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/automations/{automation}/activate',
                [AutomationController::class, 'activate']
            )->name('automations.activate');


            Route::post(
                '/automations/{automation}/pause',
                [AutomationController::class, 'pause']
            )->name('automations.pause');


            /*
            |--------------------------------------------------------------------------
            | Automation Test Run
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/automations/{automation}/test-run',
                [AutomationController::class, 'testRun']
            )->name('automations.test-run');


            /*
            |--------------------------------------------------------------------------
            | Automation Executions
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/automations/{automation}/executions',
                [AutomationExecutionController::class, 'index']
            )->name('automations.executions.index');


            Route::get(
                '/automations/{automation}/executions/{execution}',
                [AutomationExecutionController::class, 'show']
            )->name('automations.executions.show');


            Route::post(
                '/automations/{automation}/executions/{execution}/retry',
                [AutomationExecutionController::class, 'retry']
            )->name('automations.executions.retry');


            /*
            |--------------------------------------------------------------------------
            | Automation Trigger Testing
            |--------------------------------------------------------------------------
            |
            | Temporary development route for testing real trigger dispatch.
            |
            */

            Route::get(
                '/test-automation-trigger/{type}',
                function (
                    string $type,
                    AutomationTriggerDispatcher $dispatcher
                ) {

                    $executions = $dispatcher->dispatch(
                        $type,

                        data: [
                            'source' => 'admin_test',
                            'streak' => 7,
                        ],

                        context: [
                            'name' => 'Test Player',
                            'user_id' => 1,
                            'xp' => 1500,
                            'streak' => 7,
                            'sr' => 1200,
                        ],
                    );

                    return response()->json([
                        'success' => true,

                        'trigger' =>
                            $type,

                        'execution_ids' =>
                            $executions
                                ->map(
                                    fn ($execution) =>
                                        $execution->execution_id
                                )
                                ->values(),

                        'count' =>
                            $executions->count(),
                    ]);
                }
            )->name('test-automation-trigger');

        });
    });