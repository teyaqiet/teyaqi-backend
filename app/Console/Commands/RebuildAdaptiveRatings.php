<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildAdaptiveRatings extends Command
{
    protected $signature = 'quiz:rebuild-ratings';

    protected $description =
        'Rebuild user SR, question difficulty, and quiz statistics from quiz_responses history.';

    public function handle()
    {
        $this->info('Starting adaptive rating rebuild...');

        /*
         * ============================================================
         * STEP 1
         * RESET ADAPTIVE VALUES
         * ============================================================
         *
         * Everyone starts from the neutral SR of 50.
         *
         * This allows us to replay the complete history from scratch.
         */
        User::query()->update([
            'current_sr' => 50,
            'best_sr' => 50,
            'total_answers_count' => 0,
        ]);

        Question::query()->update([
            'difficulty_score' => 50,
            'difficulty' => 'medium',
            'times_shown' => 0,
            'times_correct' => 0,
        ]);

        /*
         * ============================================================
         * STEP 2
         * LOAD RESPONSE HISTORY
         * ============================================================
         *
         * quiz_responses is the source of truth.
         */
        $responses = DB::table('quiz_responses')
            ->select([
                'id',
                'user_id',
                'question_id',
                'is_correct',
                'time_taken_ms',
                'created_at',
            ])
            ->orderBy('id')
            ->get();

        $totalResponses = $responses->count();

        $this->info(
            "Replaying {$totalResponses} responses..."
        );

        /*
         * ============================================================
         * STEP 3
         * IN-MEMORY RATING STATE
         * ============================================================
         */
        $userRatings = [];
        $questionRatings = [];
        $userBestRatings = [];

        /*
         * Statistics rebuilt directly from responses.
         */
        $userAnswerCounts = [];
        $questionShownCounts = [];
        $questionCorrectCounts = [];

        $processed = 0;

        /*
         * ============================================================
         * STEP 4
         * REPLAY EVERY RESPONSE
         * ============================================================
         */
        foreach ($responses as $response) {

            $userId = (int) $response->user_id;
            $questionId = (int) $response->question_id;

            /*
             * --------------------------------------------------------
             * Initialize user state
             * --------------------------------------------------------
             */
            if (!isset($userRatings[$userId])) {
                $userRatings[$userId] = 50.0;
                $userBestRatings[$userId] = 50.0;
                $userAnswerCounts[$userId] = 0;
            }

            /*
             * --------------------------------------------------------
             * Initialize question state
             * --------------------------------------------------------
             */
            if (!isset($questionRatings[$questionId])) {
                $questionRatings[$questionId] = 50.0;
                $questionShownCounts[$questionId] = 0;
                $questionCorrectCounts[$questionId] = 0;
            }

            /*
             * --------------------------------------------------------
             * Rebuild raw statistics
             * --------------------------------------------------------
             */
            $userAnswerCounts[$userId]++;

            $questionShownCounts[$questionId]++;

            $isCorrect = (bool) $response->is_correct;

            if ($isCorrect) {
                $questionCorrectCounts[$questionId]++;
            }

            /*
             * --------------------------------------------------------
             * Get current ratings
             * --------------------------------------------------------
             */
            $userSr = $userRatings[$userId];

            $questionScore = $questionRatings[$questionId];

            /*
             * --------------------------------------------------------
             * Run the SAME adaptive algorithm used during live play.
             *
             * IMPORTANT:
             *
             * We do NOT duplicate the SR math here.
             *
             * AdaptiveRatingService is the single source of truth.
             * --------------------------------------------------------
             */
            $ratingService = app(
                \App\Services\AdaptiveRatingService::class
            );

            $rating = $ratingService->calculateRatingChange(
                $userSr,
                $questionScore,
                $isCorrect,
                (int) ($response->time_taken_ms ?? 0)
            );

            /*
             * --------------------------------------------------------
             * Update in-memory ratings
             * --------------------------------------------------------
             */
            $userRatings[$userId] =
                (float) $rating['new_user_sr'];

            $questionRatings[$questionId] =
                (float) $rating['new_question_score'];

            /*
             * --------------------------------------------------------
             * Track best SR
             * --------------------------------------------------------
             */
            if (
                $userRatings[$userId]
                > $userBestRatings[$userId]
            ) {
                $userBestRatings[$userId] =
                    $userRatings[$userId];
            }

            $processed++;

            /*
             * Progress indicator.
             */
            if ($processed % 1000 === 0) {
                $this->info(
                    "Processed {$processed} responses..."
                );
            }
        }

        /*
         * ============================================================
         * STEP 5
         * PERSIST USERS
         * ============================================================
         */
        foreach ($userRatings as $userId => $rating) {

            User::where('id', $userId)->update([
                'current_sr' => round($rating, 2),

                'best_sr' => round(
                    $userBestRatings[$userId] ?? $rating,
                    2
                ),

                'total_answers_count' =>
                    $userAnswerCounts[$userId] ?? 0,
            ]);
        }

        /*
         * ============================================================
         * STEP 6
         * PERSIST QUESTIONS
         * ============================================================
         */
        foreach ($questionRatings as $questionId => $rating) {

            $shown =
                $questionShownCounts[$questionId] ?? 0;

            $correct =
                $questionCorrectCounts[$questionId] ?? 0;

            Question::where('id', $questionId)->update([
                'difficulty_score' => round(
                    $rating,
                    2
                ),

                'difficulty' =>
                    $ratingService->difficultyCategory(
                        $rating
                    ),

                'times_shown' => $shown,

                'times_correct' => $correct,
            ]);
        }

        /*
         * ============================================================
         * STEP 7
         * VERIFY REBUILD TOTALS
         * ============================================================
         */
        $rebuiltUserAnswers =
            array_sum($userAnswerCounts);

        $rebuiltQuestionShown =
            array_sum($questionShownCounts);

        $rebuiltQuestionCorrect =
            array_sum($questionCorrectCounts);

        /*
         * ============================================================
         * FINAL REPORT
         * ============================================================
         */
        $this->info('');
        $this->info(
            'Adaptive rating rebuild completed.'
        );

        $this->info(
            "Responses replayed: {$processed}"
        );

        $this->info(
            "Users updated: " .
            count($userRatings)
        );

        $this->info(
            "Questions updated: " .
            count($questionRatings)
        );

        $this->info('');
        $this->info('Rebuilt statistics:');

        $this->info(
            "User answers: {$rebuiltUserAnswers}"
        );

        $this->info(
            "Question impressions: {$rebuiltQuestionShown}"
        );

        $this->info(
            "Question correct answers: {$rebuiltQuestionCorrect}"
        );

        return self::SUCCESS;
    }
}
