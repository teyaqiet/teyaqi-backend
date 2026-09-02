<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\DailyChallengeOnboardingController;
use App\Http\Controllers\Api\DailyChallengeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (With API Logger)
|--------------------------------------------------------------------------
*/
Route::middleware(['log.api'])->group(function () {
    Route::post('/auth/telegram', [AuthController::class, 'telegramLogin']);
    Route::get('/test-api', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API is working!'
        ]);
    });
    Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

    /*
    |--------------------------------------------------------------------------
    | PUBLIC LEADERBOARD (NO AUTH REQUIRED)
    |--------------------------------------------------------------------------
    */
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'log.api'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 1. UNRESTRICTED AUTHENTICATED ROUTES (Accessible during onboarding)
    |--------------------------------------------------------------------------
    |*/
    Route::post('/user/onboard', [UserController::class, 'onboard']);
    Route::get('/categories/list', [UserController::class, 'getCategories']);
    Route::get('/user', [UserController::class, 'show']);


    /*
    |--------------------------------------------------------------------------
    | 2. RESTRICTED CORE (Requires Onboarding Execution)
    |--------------------------------------------------------------------------
    |*/
    Route::middleware('ensure_onboarded')->group(function () {

        Route::post('/user/update', [UserController::class, 'update']);

        Route::post('/user/claim-daily', function (Request $request) {
            $user = $request->user();
            if ($user->last_reward_at && \Carbon\Carbon::parse($user->last_reward_at)->isToday()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Already claimed!'
                ], 400);
            }
            $user->total_xp += 25;
            $user->last_reward_at = now();
            $user->save();

            return response()->json([
                'status' => 'success',
                'new_xp' => $user->total_xp
            ]);
        });

        // --- STREAK MANAGEMENT SYSTEM ---
        Route::post('/user/streak/freeze', [UserController::class, 'freezeStreak']);

        // --- CHALLENGE ENGINE ROUTES ---
        Route::prefix('challenges')->group(function () {
            Route::get('/', [ChallengeController::class, 'index']);
            Route::get('/daily', [ChallengeController::class, 'daily']);
            
            // 🟢 ADDED: Secure private thumbnail streaming delivery route
            Route::get('/thumbnails/{filename}', [ChallengeController::class, 'getThumbnail']);
            
            Route::get('/{challenge}', [ChallengeController::class, 'show']);
            Route::post('/{id}/start', [ChallengeController::class, 'start']);
        });

        Route::post('/challenge-attempts/{attempt}/submit', [ChallengeController::class, 'submit']);

        // --- DAILY ISLAND JOURNEY CHALLENGE SYSTEM ---
        Route::prefix('daily-challenge')->group(function () {
            Route::get('/status', [DailyChallengeController::class, 'status']);
            Route::get('/questions', [DailyChallengeController::class, 'fetch']);
            Route::post('/submit', [DailyChallengeController::class, 'submit']);
        });

        // --- CORE QUIZ GAME ROUTES ---
        Route::get('/quiz/daily', [GameController::class, 'getDailyQuestions']);
        Route::post('/quiz/submit', [GameController::class, 'submitAnswer']);
        Route::post('/quiz/finish', [GameController::class, 'syncFinalScore']);
        
        // --- FRIEND MANAGEMENT ROUTES ---
        Route::get('/friends', [FriendController::class, 'index']);
        Route::post('/friends/request', [FriendController::class, 'sendRequest']);
        Route::post('/friends/accept', [FriendController::class, 'acceptRequest']);
        Route::delete('/friends/{id}', [FriendController::class, 'removeFriend']);
        Route::get('/friends/search', [FriendController::class, 'searchUsers']);
        Route::get('/friends/requests/pending', [FriendController::class, 'pendingRequests']);
        Route::get('/users/search', [FriendController::class, 'searchUsers']);
        Route::delete('/friends/requests/{id}', [FriendController::class, 'deleteRequest']);

    }); // End ensure_onboarded middleware block

}); // End auth:sanctum middleware block