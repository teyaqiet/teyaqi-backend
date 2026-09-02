<?php

namespace App\Services;

use App\Models\{Challenge, ChallengeAttempt, GameSession, Question, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChallengeEngineService
{
    public function __construct(
        protected ChallengeQuestionGeneratorService $questionGenerator,
        protected ScoringEngineService $scoringEngine
    ) {}

    /**
     * Start a new challenge session attempt.
     */
    public function startSession(Challenge $challenge, int $userId): ChallengeAttempt
    {
        if (!$challenge->allow_retry) {
            $hasAttempted = ChallengeAttempt::where('challenge_id', $challenge->id)
                ->where('user_id', $userId)
                ->exists();
            if ($hasAttempted) {
                throw new \Exception("Retries are disabled for this challenge.");
            }
        }

        return ChallengeAttempt::create([
            'challenge_id'     => $challenge->id,
            'user_id'          => $userId,
            'started_at'       => now(),
            'completed'        => false,
            'passed'           => false,
            'reward_claimed'   => false,
            'score'            => 0,
            'correct_answers'  => 0,
            'wrong_answers'    => 0,
            'time_spent'       => 0,
        ]);
    }

    /**
     * Finalize the challenge session via high-performance batch operations.
     */
    public function submitSession(ChallengeAttempt $attempt, array $payload): ChallengeAttempt
    {
        // Prevent double submission exploits
        if ($attempt->completed == 1 || $attempt->status === 'completed' || $attempt->is_completed) {
            throw new \Exception("This challenge session was already submitted.");
        }

        $challenge = $attempt->challenge;
        $config = $challenge->config ?? [];

        // 1. Run core logic math through your ScoringEngineService
        $metrics = $this->scoringEngine->calculate($attempt, $payload, $config);

        DB::transaction(function () use ($attempt, $metrics, $challenge, $payload) {
            $user = User::findOrFail($attempt->user_id);
            $responses = $payload['responses'] ?? [];
            
            $correctCount = (int) ($metrics['correct_answers'] ?? 0);
            $wrongCount = (int) ($metrics['wrong_answers'] ?? 0);
            $finalScore = (int) ($metrics['score'] ?? 0);
            
            // Safe fallback evaluation
            $isPassed = ($wrongCount < 3) ? true : (bool) ($metrics['passed'] ?? false);

            // --- TABLE 1: UPDATE CHALLENGE_ATTEMPTS ---
            $updateAttemptData = [
                'score'           => $finalScore,
                'correct_answers' => $correctCount,
                'wrong_answers'   => $wrongCount,
                'time_spent'      => (int) ($payload['time_spent'] ?? 0),
                'passed'          => $isPassed,
                'finished_at'     => now(),
                'reward_claimed'  => $isPassed,
                'completed'       => true
            ];

            if (Schema::hasColumn('challenge_attempts', 'status')) {
                $updateAttemptData['status'] = 'completed';
            }
            if (Schema::hasColumn('challenge_attempts', 'is_completed')) {
                $updateAttemptData['is_completed'] = true;
            }

            $attempt->update($updateAttemptData);

            // --- TABLE 2: GENERATE GENERAL GAME_SESSIONS METRICS ---
            // ⚡ FIXED: Fallback formula now accurately matches your new scoring rules architecture
            $challengeBaseXp = (int) ($challenge->reward_xp ?? 10);
            $maxStreak       = (int) ($payload['max_streak'] ?? 0);
            
            $fallbackBaseXp  = $correctCount * $challengeBaseXp;
            $fallbackBonusXp = $maxStreak >= 3 ? 30 + (($maxStreak - 3) * 10) : 0;
            
            // Prioritize the incoming calculated live state value from your engine payload
            $xpEarned = (int) ($payload['total_xp_earned'] ?? ($fallbackBaseXp + $fallbackBonusXp));

            $gameSession = GameSession::create([
                'user_id'         => $user->id,
                'total_questions' => count($responses) ?: 10, 
                'correct_answers' => $correctCount,
                'xp_earned'       => $xpEarned,
                'base_xp'         => $fallbackBaseXp,
                'bonus_xp'        => $fallbackBonusXp,
                'lives_lost'      => $wrongCount,
                'current_streak'  => 0, 
                'max_streak'      => $maxStreak,
                'is_completed'    => true,
                'created_at'      => now(),
                'updated_at'      => now()
            ]);

            // --- TABLE 3: HIGH-PERFORMANCE BULK INSERT FOR QUIZ_RESPONSES ---
            if (!empty($responses)) {
                $bulkResponses = [];
                $timestamp = now();
                $timeLimit = (int) ($challenge->time_limit ?? 15);

                foreach ($responses as $resp) {
                    // ⚡ FIXED: Log true elapsed time spent per question instead of remaining time
                    $remainingTime = (int) ($resp['remaining_time'] ?? 0);
                    $actualTimeSpentSeconds = max(0, $timeLimit - $remainingTime);

                    $bulkResponses[] = [
                        'user_id'         => $user->id,
                        'session_id'      => $gameSession->id, 
                        'question_id'     => $resp['question_id'],
                        'selected_option' => strtolower($resp['selected_option'] ?? 'a'),
                        'is_correct'      => (bool) ($resp['is_correct'] ?? false),
                        'time_taken_ms'   => $actualTimeSpentSeconds * 1000, 
                        'created_at'      => $timestamp,
                        'updated_at'      => $timestamp,
                    ];
                }

                foreach (array_chunk($bulkResponses, 100) as $chunk) {
                    DB::table('quiz_responses')->insert($chunk);
                }
            }

            // --- TABLE 4: UPDATE USER SYSTEM ACCUMULATED PROFILE STATS ---
            // ⚡ FIXED: Coin distribution and performance XP sync cleanly to your incoming payload values
            $coinsEarned = (int) ($payload['total_coins_earned'] ?? 0);

            if ($xpEarned > 0) {
                $user->increment('total_xp', $xpEarned);
                
                if (Schema::hasColumn('users', 'weekly_xp')) {
                    $user->increment('weekly_xp', $xpEarned);
                }
            }

            if ($coinsEarned > 0) {
                if (Schema::hasColumn('users', 'total_coins')) {
                    $user->increment('total_coins', $coinsEarned);
                } elseif (Schema::hasColumn('users', 'coins')) {
                    $user->increment('coins', $coinsEarned);
                }
            }

            if (Schema::hasColumn('users', 'total_answers_count')) {
                $user->increment('total_answers_count', count($responses));
            }

            $user->update([
                'last_played_date' => now()->toDateString()
            ]);
        });

        return $attempt;
    }
}