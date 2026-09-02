<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\StreakService;
use App\Models\Category;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $streakService;

    public function __construct(StreakService $streakService)
    {
        $this->streakService = $streakService;
    }

    /**
     * Get all active categories for onboarding dropdowns/grids
     */
    public function getCategories()
    {
        $categories = Category::select('id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Handle the user onboarding process
     */
    public function onboard(Request $request)
    {
        $request->validate([
            'avatar' => 'required|string',
            'gender' => 'required|string|in:male,female',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'avatar' => $request->avatar,
                'gender' => $request->gender,
                'has_onboarded' => true,
            ]);

            $user->categories()->sync($request->category_ids);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding complete!'
        ]);
    }

    /**
     * Get the authenticated user's profile data
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();
            $now = now();

            // --- ⚡ SAFE LIFE REGENERATION ENGINE ---
            $maxLives = 5;
            $regenMins = 30;

            if (isset($user->daily_lives)) {
                if ($user->daily_lives >= $maxLives) {
                    $user->daily_lives = $maxLives;
                    $user->lives_updated_at = $now;
                    $user->save();
                } elseif ($user->lives_updated_at) {
                    $lastUpdate = Carbon::parse($user->lives_updated_at);
                    $diffInMinutes = $lastUpdate->diffInMinutes($now);

                    if ($diffInMinutes >= $regenMins) {
                        $gained = floor($diffInMinutes / $regenMins);
                        $user->daily_lives = min($maxLives, $user->daily_lives + $gained);
                        $user->lives_updated_at = $lastUpdate->addMinutes($gained * $regenMins);
                        $user->save();
                    }
                } else {
                    $user->lives_updated_at = $now;
                    $user->save();
                }
            }

            // --- 🕒 HIGH SPEED STREAK WINDOW CHECK ---
            $streakStatus = 'dead';
            $nextResetAt = null;

            if ($this->streakService) {
                // Returns calculated status payload array
                $streakCheck = $this->streakService->passiveStreakCheck($user);
                $streakStatus = $streakCheck['status'] ?? 'dead';
                $nextResetAt = $streakCheck['next_reset_at'] ?? null;
            }

            // Fetch current active context engines safely
            $activeChallenges = [];
            if (class_exists('\App\Models\Challenge')) {
                $activeChallenges = \App\Models\Challenge::where('status', 'active')->get();
            }

            $user->refresh();
            
            // Safe relationship loading
            $relations = [];
            if (method_exists($user, 'streak')) $relations[] = 'streak';
            if (method_exists($user, 'categories')) $relations[] = 'categories';
            if (!empty($relations)) $user->load($relations);

            $data = $user->toArray();
            
            // Safe assignment for level data structure attributes
            $data['level_data'] = method_exists($user, 'getLevelDataAttribute') || isset($user->level_data) 
                ? $user->level_data 
                : ['current_level' => 1, 'current_xp' => 0, 'next_level_xp' => 100];
                
            $data['challenges'] = $activeChallenges;
            
            // Safe map parameter assignment fallbacks
            $data['current_streak'] = (int) ($user->current_streak ?? 0);
            $data['current_island'] = $data['current_streak'] === 0 ? 1 : $data['current_streak'];
            $data['is_streak_frozen'] = ($streakStatus === 'frozen');
            
            // 🎯 Inject precise timer properties directly into the user data payload context
            $data['next_reset_at'] = $nextResetAt;

            // Force explicit boolean casting data integrity flags
            $data['has_onboarded'] = filter_var($user->has_onboarded, FILTER_VALIDATE_BOOLEAN);

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'streak_status' => $streakStatus // Feeds your direct hook components string evaluation parameters
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AppGuard /user Crash: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Backend Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        // 1. Validate fields safely. Making avatar and category_ids optional using 'sometimes'
        $request->validate([
            'username'       => 'required|string|min:3|max:20',
            'avatar'         => 'sometimes|required|string',
            'category_ids'   => 'sometimes|required|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // 2. Wrap everything inside a transaction to ensure data safety
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $request) {
            // Update flat model columns
            $user->username = $request->input('username');
            
            if ($request->has('avatar')) {
                $user->avatar = $request->input('avatar');
            }
            
            $user->save();

            // Sync pivot table data if category_ids array is passed
            if ($request->has('category_ids')) {
                $user->categories()->sync($request->input('category_ids'));
            }
        });

        // Load fresh category values to send back to Next.js
        $user->load('categories');

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }

    /**
     * Manually consumes a streak freeze shield item via frontend user modal prompt confirmation.
     */
    public function freezeStreak(Request $request)
    {
        try {
            $user = $request->user();

            if (!$this->streakService) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Streak service engine unavailable.'
                ], 500);
            }

            // Execute manual deduction engine operations
            $results = $this->streakService->manuallyConsumeFreezeShield($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Streak preserved using a freeze shield item.',
                'data' => $results
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }
}