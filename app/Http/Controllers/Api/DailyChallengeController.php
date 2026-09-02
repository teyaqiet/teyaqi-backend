<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\DailyChallenge;
use App\Services\StreakService; // ⚡ INJECT ENGINE
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyChallengeController extends Controller
{
    protected $streakService;

    // Typehint our global streak tracking service
    public function __construct(StreakService $streakService)
    {
        $this->streakService = $streakService;
    }

    public function fetch(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $categoryIds = $user->selectedCategories()->pluck('categories.id')->toArray();

        if (empty($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select your onboarding categories first.',
                'code' => 'ONBOARDING_REQUIRED'
            ], 403);
        }

        if ($user->daily_lives <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'You are out of lives! Wait for a reset or use coins.',
                'code' => 'OUT_OF_LIVES'
            ], 403);
        }

        $questions = Question::whereIn('category_id', $categoryIds)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $session = DailyChallenge::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'score' => 0
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'questions' => $questions,
                'current_streak' => (int) $user->current_streak,
                'current_island' => $user->current_streak === 0 ? 1 : $user->current_streak + 1,
                'lives_remaining' => (int) $user->daily_lives
            ]
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->daily_lives <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No lives remaining to submit this session.',
            ], 403);
        }

        $streakData = null;

        DB::transaction(function () use ($user, $request, &$streakData) {
            // 1. Deduct life penalty safely
            $user->decrement('daily_lives');

            // 2. Run clean centralized metric updating mechanics
            $streakData = $this->streakService->updateStreak($user);

            // 3. Log historical session tracking trace rows
            DailyChallenge::create([
                'user_id' => $user->id,
                'score' => $request->score,
                'advanced_streak' => ($streakData['status'] === 'advanced'),
            ]);
        });

        $user->refresh();

        return response()->json([
            'success' => true,
            'data' => [
                'score' => (int) $request->score,
                'advanced_streak' => ($streakData['status'] === 'advanced'),
                'current_streak' => (int) $user->current_streak,
                'current_island' => $user->current_streak === 0 ? 1 : $user->current_streak,
                'lives_remaining' => (int) $user->daily_lives,
            ]
        ]);
    }

    public function status(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $hasCompletedOnboarding = $user->selectedCategories()->exists();
        
        // Safely determine if user played inside their local calendar timezone map today
        $streakCheck = $this->streakService->passiveStreakCheck($user);
        $playedToday = ($streakCheck['status'] === 'completed' || $streakCheck['status'] === 'advanced');

        return response()->json([
            'success' => true,
            'data' => [
                'has_onboarded' => $hasCompletedOnboarding,
                'current_streak' => (int) $user->current_streak,
                'current_island' => $user->current_streak === 0 ? 1 : $user->current_streak,
                'played_today' => $playedToday,
                'lives_remaining' => (int) $user->daily_lives,
            ]
        ]);
    }
}