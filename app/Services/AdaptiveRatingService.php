<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use App\Models\UserCategoryRating;

class AdaptiveRatingService
{
    /**
     * Rating range used by Teyaqi.
     *
     * 0   = very easy
     * 50  = medium
     * 100 = very hard
     */
    private const MIN_RATING = 0.0;
    private const MAX_RATING = 100.0;

    /**
     * Global user rating K.
     */
    private const USER_K = 8.0;

    /**
     * Question difficulty K.
     */
    private const QUESTION_K = 4.0;

    /**
     * Category rating K.
     *
     * We use the same user-side movement as the
     * global rating for now.
     */
    private const CATEGORY_K = 8.0;

    /**
     * Process one answer and update:
     *
     * 1. Global user SR
     * 2. User best SR
     * 3. Category SR
     * 4. Category statistics
     * 5. Question difficulty
     * 6. Question difficulty category
     */
    public function processAnswer(
        User $user,
        Question $question,
        bool $isCorrect,
        int $timeMs = 0
    ): array {

        /*
         * ============================================================
         * 1. GLOBAL USER SR
         * ============================================================
         */

        $previousUserSr = $this->clamp(
            (float) ($user->current_sr ?? 50),
            self::MIN_RATING,
            self::MAX_RATING
        );

        /*
         * ============================================================
         * 2. QUESTION DIFFICULTY
         * ============================================================
         */

        $previousQuestionScore = $this->clamp(
            (float) ($question->difficulty_score ?? 50),
            self::MIN_RATING,
            self::MAX_RATING
        );

        /*
         * ============================================================
         * 3. CATEGORY RATING
         * ============================================================
         */

        $categoryRating = null;

        if ($question->category_id) {

            $categoryRating = UserCategoryRating::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $question->category_id,
                ],
                [
                    'sr' => 50.0,
                    'questions_answered' => 0,
                    'correct_answers' => 0,
                    'last_answered_at' => null,
                ]
            );
        }

        $previousCategorySr = $categoryRating
            ? $this->clamp(
                (float) ($categoryRating->sr ?? 50),
                self::MIN_RATING,
                self::MAX_RATING
            )
            : null;

        /*
         * ============================================================
         * 4. EXPECTED PROBABILITY
         * ============================================================
         *
         * IMPORTANT:
         *
         * Category SR is the user's skill rating for this
         * specific category.
         *
         * Therefore category SR is used against question
         * difficulty instead of global SR.
         */

        $ratingUserSr = $previousCategorySr ?? $previousUserSr;

        $rating = $this->calculateRatingChange(
            $ratingUserSr,
            $previousQuestionScore,
            $isCorrect,
            $timeMs
        );

        /*
         * ============================================================
         * 5. UPDATE GLOBAL USER SR
         * ============================================================
         */

        $globalRating = $this->calculateRatingChange(
            $previousUserSr,
            $previousQuestionScore,
            $isCorrect,
            $timeMs
        );

        $newUserSr = $globalRating['new_user_sr'];

        $user->current_sr = round($newUserSr, 2);

        $currentBestSr = (float) ($user->best_sr ?? 0);

        if ($newUserSr > $currentBestSr) {
            $user->best_sr = round($newUserSr, 2);
        }

        /*
         * ============================================================
         * 6. UPDATE CATEGORY SR
         * ============================================================
         */

        $newCategorySr = null;

        if ($categoryRating) {

            $newCategorySr = $rating['new_user_sr'];

            $categoryRating->sr = round(
                $newCategorySr,
                2
            );

            $categoryRating->questions_answered =
                (int) $categoryRating->questions_answered + 1;

            if ($isCorrect) {
                $categoryRating->correct_answers =
                    (int) $categoryRating->correct_answers + 1;
            }

            $categoryRating->last_answered_at = now();

            /*
             * We intentionally do not save here.
             *
             * GameController is already running inside a
             * database transaction and we will save below.
             */
        }

        /*
         * ============================================================
         * 7. UPDATE QUESTION DIFFICULTY
         * ============================================================
         */

        $newQuestionScore =
            $rating['new_question_score'];

        $question->difficulty_score =
            round($newQuestionScore, 2);

        $question->difficulty =
            $this->difficultyCategory(
                $newQuestionScore
            );

        /*
         * ============================================================
         * 8. SAVE CATEGORY RATING
         * ============================================================
         */

        if ($categoryRating) {
            $categoryRating->save();
        }

        /*
         * ============================================================
         * 9. RETURN EVERYTHING
         * ============================================================
         */

        return [

            /*
             * GLOBAL USER SR
             */
            'previous_user_sr' =>
                round($previousUserSr, 2),

            'new_user_sr' =>
                round($newUserSr, 2),

            'user_sr_change' =>
                round(
                    $newUserSr - $previousUserSr,
                    2
                ),

            /*
             * CATEGORY SR
             */
            'category_id' =>
                $question->category_id,

            'previous_category_sr' =>
                $previousCategorySr !== null
                    ? round($previousCategorySr, 2)
                    : null,

            'new_category_sr' =>
                $newCategorySr !== null
                    ? round($newCategorySr, 2)
                    : null,

            'category_sr_change' =>
                $newCategorySr !== null
                    ? round(
                        $newCategorySr - $previousCategorySr,
                        2
                    )
                    : null,

            /*
             * QUESTION
             */
            'previous_question_score' =>
                round(
                    $previousQuestionScore,
                    2
                ),

            'new_question_score' =>
                round(
                    $newQuestionScore,
                    2
                ),

            'question_score_change' =>
                round(
                    $newQuestionScore -
                    $previousQuestionScore,
                    2
                ),

            /*
             * PROBABILITY
             */
            'expected_probability' =>
                $rating['expected_probability'],

            'actual_result' =>
                $rating['actual_result'],

            'speed_multiplier' =>
                $rating['speed_multiplier'],

            'question_difficulty' =>
                $question->difficulty,
        ];
    }

    /**
     * Calculate rating changes without modifying the database.
     */
    public function calculateRatingChange(
        float $userSr,
        float $questionScore,
        bool $isCorrect,
        int $timeMs = 0
    ): array {

        $userSr = $this->clamp(
            $userSr,
            self::MIN_RATING,
            self::MAX_RATING
        );

        $questionScore = $this->clamp(
            $questionScore,
            self::MIN_RATING,
            self::MAX_RATING
        );

        /*
         * Expected probability.
         */
        $expectedProbability =
            $this->expectedProbability(
                $userSr,
                $questionScore
            );

        $actualResult = $isCorrect
            ? 1.0
            : 0.0;

        /*
         * Speed.
         */
        $speedMultiplier =
            $this->getSpeedMultiplier(
                $timeMs,
                $isCorrect
            );

        /*
         * User/category change.
         */
        $userDelta =
            self::CATEGORY_K
            * ($actualResult - $expectedProbability)
            * $speedMultiplier;

        /*
         * Question change.
         */
        $questionDelta =
            self::QUESTION_K
            * ($expectedProbability - $actualResult)
            * $speedMultiplier;

        /*
         * New ratings.
         */
        $newUserSr = $this->clamp(
            $userSr + $userDelta,
            self::MIN_RATING,
            self::MAX_RATING
        );

        $newQuestionScore = $this->clamp(
            $questionScore + $questionDelta,
            self::MIN_RATING,
            self::MAX_RATING
        );

        return [

            'previous_user_sr' =>
                round($userSr, 2),

            'new_user_sr' =>
                round($newUserSr, 2),

            'user_sr_change' =>
                round(
                    $newUserSr - $userSr,
                    2
                ),

            'previous_question_score' =>
                round($questionScore, 2),

            'new_question_score' =>
                round($newQuestionScore, 2),

            'question_score_change' =>
                round(
                    $newQuestionScore -
                    $questionScore,
                    2
                ),

            'expected_probability' =>
                round(
                    $expectedProbability,
                    4
                ),

            'actual_result' =>
                $actualResult,

            'speed_multiplier' =>
                $speedMultiplier,

            'question_difficulty' =>
                $this->difficultyCategory(
                    $newQuestionScore
                ),
        ];
    }

    /**
     * Expected chance of correct answer.
     */
    public function expectedProbability(
        float $userSr,
        float $questionScore
    ): float {

        $difference =
            $questionScore - $userSr;

        return 1 / (
            1 + pow(
                10,
                $difference / 40
            )
        );
    }

    /**
     * Speed multiplier.
     */
    public function getSpeedMultiplier(
        int $timeMs,
        bool $isCorrect
    ): float {

        if ($timeMs <= 0) {
            return 1.0;
        }

        if (!$isCorrect) {
            return 1.0;
        }

        if ($timeMs < 4000) {
            return 1.20;
        }

        if ($timeMs <= 8000) {
            return 1.00;
        }

        if ($timeMs <= 15000) {
            return 0.90;
        }

        return 0.80;
    }

    /**
     * Numeric difficulty → category.
     */
    public function difficultyCategory(
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

    /**
     * Clamp rating.
     */
    public function clamp(
        float $value,
        float $min = self::MIN_RATING,
        float $max = self::MAX_RATING
    ): float {

        return max(
            $min,
            min($max, $value)
        );
    }
}