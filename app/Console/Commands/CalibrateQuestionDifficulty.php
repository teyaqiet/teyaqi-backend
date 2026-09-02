<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalibrateQuestionDifficulty extends Command
{
    protected $signature = 'quiz:calibrate-difficulty
                            {--min-attempts=20 : Minimum responses required}
                            {--lookback= : Optional number of days to use}';

    protected $description =
        'Recalibrates question difficulty from actual quiz response performance.';

    public function handle()
    {
        $minimumAttempts = max(
            1,
            (int) $this->option('min-attempts')
        );

        $lookback = $this->option('lookback');

        $questions = Question::where('is_active', true)
            ->where('times_shown', '>=', $minimumAttempts)
            ->get();

        $updatedCount = 0;

        foreach ($questions as $question) {

            $responses = DB::table('quiz_responses')
                ->where('question_id', $question->id);

            if ($lookback) {
                $responses->where(
                    'created_at',
                    '>=',
                    now()->subDays((int) $lookback)
                );
            }

            $total = (int) $responses->count();

            if ($total < $minimumAttempts) {
                continue;
            }

            $correct = (int) $responses
                ->where('is_correct', true)
                ->count();

            $successRate = ($correct / $total) * 100;

            /*
             * We don't want batch calibration to completely
             * override the live rating system.
             *
             * Instead, move toward the empirical target.
             */
            $targetScore = $this->scoreFromSuccessRate(
                $successRate
            );

            $oldScore = (float) (
                $question->difficulty_score ?? 50
            );

            /*
             * Only move 15% toward the statistical target
             * per calibration run.
             */
            $newScore = $oldScore +
                (($targetScore - $oldScore) * 0.15);

            $newScore = max(
                0,
                min(100, $newScore)
            );

            $newDifficulty = $this->difficultyLabel(
                $newScore
            );

            $question->update([
                'difficulty_score' => round($newScore, 2),
                'difficulty' => $newDifficulty,
                'times_shown' => $total,
                'times_correct' => $correct,
            ]);

            if (
                abs($newScore - $oldScore) >= 0.01
            ) {
                $updatedCount++;
            }

            $this->line(
                "Question {$question->id}: " .
                "{$successRate}% success | " .
                "{$oldScore} → {$newScore} | " .
                "{$newDifficulty}"
            );
        }

        $this->info(
            "Successfully calibrated {$updatedCount} questions."
        );

        Log::info(
            "Difficulty Calibration Run",
            [
                'updated_questions' => $updatedCount,
                'minimum_attempts' => $minimumAttempts,
                'lookback' => $lookback,
            ]
        );

        return self::SUCCESS;
    }

    private function scoreFromSuccessRate(
        float $successRate
    ): float {

        /*
         * High success = easy = low rating.
         * Low success = hard = high rating.
         *
         * 90% → ~10
         * 75% → ~25
         * 50% → ~50
         * 25% → ~75
         * 10% → ~90
         */
        return max(
            0,
            min(
                100,
                100 - $successRate
            )
        );
    }

    private function difficultyLabel(
        float $score
    ): string {

        if ($score < 40) {
            return 'easy';
        }

        if ($score < 70) {
            return 'medium';
        }

        return 'hard';
    }
}