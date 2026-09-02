<?php

namespace App\Services;

use App\Models\ChallengeAttempt;
use App\Models\Question;

class ScoringEngineService
{
    /**
     * Compute results metrics securely matching frontend gameplay logic engines.
     */
    public function calculate(ChallengeAttempt $attempt, array $payload, array $config): array
    {
        $responses = $payload['responses'] ?? []; 
        $timeSpent = $payload['time_spent'] ?? 0;
        
        $correct = 0;
        $wrong = 0;
        $calculatedXp = 0;

        $challenge = $attempt->challenge;
        // Fetch dynamic challenge parameters from the database tables layout
        $challengeBaseXp = (int) ($challenge->reward_xp ?? 10);
        $timeLimit       = (int) ($challenge->time_limit ?? 15);
        $timeMode        = $config['time_mode'] ?? 'per_question'; 

        if (!empty($responses)) {
            $questionIds = collect($responses)->pluck('question_id')->toArray();
            $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');

            // Sequential tracking parameters to accurately replicate streak curves
            $runningStreak = 0;

            foreach ($responses as $response) {
                $question = $questions->get($response['question_id']);
                if ($question) {
                    // Check lowercase normalized option strings securely
                    $userAnswer = strtolower($response['selected_option'] ?? '');
                    $correctAnswer = strtolower($question->correct_answer ?? '');

                    if ($userAnswer === $correctAnswer) {
                        $correct++;
                        $runningStreak++;

                        // ⏱️ Rule A: Speed Bonus (The 3-Second Rule)
                        $remainingTime = (int) ($response['remaining_time'] ?? 0);
                        $elapsedTime = max(0, $timeLimit - $remainingTime);
                        $speedBonus = ($timeMode === 'per_question' && $elapsedTime <= 3) ? 5 : 0;

                        // 🔥 Rule B: Tiered Streak Milestone Bonus
                        $streakBonus = 0;
                        if ($runningStreak === 3) {
                            $streakBonus = 30; // Milestone jump
                        } elseif ($runningStreak > 3) {
                            $streakBonus = 30 + (($runningStreak - 3) * 10); // +30 baseline + 10 increments
                        }

                        // Add question total to runtime score container
                        $calculatedXp += ($challengeBaseXp + $speedBonus + $streakBonus);
                    } else {
                        $wrong++;
                        $runningStreak = 0; // Break streak instantly
                    }
                }
            }
        } else {
            // Fallback tracking vectors if processed inside testing seeding runs
            $correct = $payload['correct_answers'] ?? 0;
            $wrong = $payload['wrong_answers'] ?? 0;
            $calculatedXp = (int) ($payload['total_xp_earned'] ?? ($correct * $challengeBaseXp));
        }
        
        // Final score evaluations mapped directly against the configured challenge definitions
        $finalScore = max(0, $calculatedXp);
        $passed = $finalScore >= ($challenge->passing_score ?? 0);

        return [
            'score'           => $finalScore, // This fields acts as total_xp_earned inside transactions handlers
            'passed'          => $passed,
            'correct_answers' => $correct,
            'wrong_answers'   => $wrong,
            'time_spent'      => $timeSpent
        ];
    }
}