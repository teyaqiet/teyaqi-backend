<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\GameSession;
use App\Models\User;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\AdaptiveRatingService;
use App\Models\UserCategoryRating;
use App\Services\XpMilestoneService;
use Carbon\Carbon;

class GameController extends Controller
{

    protected $streakService;
    protected $adaptiveRatingService;
    protected $xpMilestoneService;

    public function __construct(
    StreakService $streakService,
    AdaptiveRatingService $adaptiveRatingService,
    XpMilestoneService $xpMilestoneService
) {
    $this->streakService = $streakService;
    $this->adaptiveRatingService = $adaptiveRatingService;
    $this->xpMilestoneService = $xpMilestoneService;
}

public function getDailyQuestions(Request $request)
{
    try {
        /** @var User $user */
        $user = Auth::user();

        $maxLives = 5;
        $regenMinutes = 30;
        $limit = 10;
        $lang = $request->header('X-Language', 'en');
        $now = now();

        // ============================================================
        // 1. DYNAMIC LIFE REGENERATION
        // ============================================================

        if ($user->daily_lives < $maxLives) {

            $lastUpdate = $user->lives_updated_at
                ? Carbon::parse($user->lives_updated_at)
                : $now;

            $minutesPassed = $lastUpdate->diffInMinutes($now);

            if ($minutesPassed >= $regenMinutes) {

                $livesToGain = floor(
                    $minutesPassed / $regenMinutes
                );

                $newLives = min(
                    $maxLives,
                    $user->daily_lives + $livesToGain
                );

                $user->daily_lives = $newLives;

                if ($user->daily_lives >= $maxLives) {

                    $user->lives_updated_at = null;

                } else {

                    $user->lives_updated_at =
                        $lastUpdate->addMinutes(
                            $livesToGain * $regenMinutes
                        );
                }

                $user->save();
            }
        }

        // ============================================================
        // 2. BLOCK IF NO LIVES
        // ============================================================

        if ($user->daily_lives <= 0) {

            if (!$user->lives_updated_at) {

                $user->lives_updated_at = now();
                $user->save();
            }

            $nextLifeAt =
                Carbon::parse(
                    $user->lives_updated_at
                )->addMinutes($regenMinutes);

            return response()->json([
                'status' => 'error',

                'next_reset_at' =>
                    $nextLifeAt->toIso8601String(),

                'lives' => 0,
            ], 403);
        }

        // ============================================================
        // 3. ADAPTIVE + CATEGORY-AWARE QUESTION SELECTION
        // ============================================================

        /*
        |--------------------------------------------------------------------------
        | GLOBAL USER SR
        |--------------------------------------------------------------------------
        |
        | Used as a fallback when a question has no category.
        |
        */

        $userSR = $this->adaptiveRatingService->clamp(
            (float) ($user->current_sr ?? 50),
            0,
            100
        );

        /*
        |--------------------------------------------------------------------------
        | FAVORITE CATEGORIES
        |--------------------------------------------------------------------------
        |
        | Selected by the user during onboarding.
        |
        | user_categories:
        |   user_id
        |   category_id
        |
        */

        $favoriteCategoryIds = DB::table('user_categories')
            ->where('user_id', $user->id)
            ->pluck('category_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | USER CATEGORY SR
        |--------------------------------------------------------------------------
        */

        $categoryRatings = UserCategoryRating::where(
            'user_id',
            $user->id
        )
            ->get()
            ->keyBy('category_id');

        /*
        |--------------------------------------------------------------------------
        | BASE QUESTION POOL
        |--------------------------------------------------------------------------
        */

        $baseQuery = Question::where(
            'is_active',
            true
        );

        /*
        |--------------------------------------------------------------------------
        | LANGUAGE FILTER
        |--------------------------------------------------------------------------
        */

        if ($lang === 'am') {

            $baseQuery->whereNotNull(
                'question_text->am'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | QUESTIONS ALREADY SEEN
        |--------------------------------------------------------------------------
        |
        | We intentionally DO NOT use:
        |
        | created_at > now()->subDays(2)
        |
        | A question stays excluded until the entire question
        | pool has been rotated.
        |
        */

        $seenQuestionIds = DB::table('quiz_responses')
            ->where('user_id', $user->id)
            ->pluck('question_id')
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | GET UNSEEN QUESTIONS
        |--------------------------------------------------------------------------
        */

        $unseenQuery = clone $baseQuery;

        if (!empty($seenQuestionIds)) {

            $unseenQuery->whereNotIn(
                'id',
                $seenQuestionIds
            );
        }

        $unseenQuestions = $unseenQuery->get();

        /*
        |--------------------------------------------------------------------------
        | ROTATION RESET
        |--------------------------------------------------------------------------
        |
        | If all active questions have already been seen,
        | start a new rotation.
        |
        */

        if ($unseenQuestions->isEmpty()) {

            Log::info(
                '🔄 QUESTION ROTATION RESET',
                [
                    'user_id' => $user->id,
                    'reason' => 'all_questions_seen',
                ]
            );

            $seenQuestionIds = [];

            $unseenQuestions =
                $baseQuery->get();
        }

        /*
        |--------------------------------------------------------------------------
        | SCORE AVAILABLE QUESTIONS
        |--------------------------------------------------------------------------
        |
        | Priority:
        |
        | 1. Favorite category
        | 2. Category SR match
        | 3. Randomness
        |
        */

        $scoredQuestions =
            $unseenQuestions->map(
                function ($question) use (
                    $favoriteCategoryIds,
                    $categoryRatings,
                    $userSR
                ) {

                    /*
                     * Is this one of the user's
                     * favorite categories?
                     */

                    $isFavorite =
                        in_array(
                            (int) $question->category_id,
                            $favoriteCategoryIds,
                            true
                        );

                    /*
                     * Category SR.
                     *
                     * New category = 50.
                     */

                    $categoryRating =
                        $categoryRatings->get(
                            $question->category_id
                        );

                    $categorySr =
                        $categoryRating
                            ? (float) $categoryRating->sr
                            : $userSR;

                    /*
                     * Question difficulty.
                     */

                    $questionDifficulty =
                        (float) (
                            $question->difficulty_score
                            ?? 50
                        );

                    /*
                     * Distance between question
                     * and user's category skill.
                     */

                    $difficultyDistance =
                        abs(
                            $questionDifficulty
                            - $categorySr
                        );

                    /*
                     * Difficulty match score.
                     *
                     * Perfect match = 100.
                     */

                    $difficultyScore =
                        max(
                            0,
                            100 - $difficultyDistance
                        );

                    /*
                     * Favorite category gets
                     * a strong priority.
                     */

                    $favoriteBonus =
                        $isFavorite
                            ? 100
                            : 0;

                    /*
                     * Randomness prevents
                     * identical ordering.
                     */

                    $randomScore =
                        mt_rand(0, 30);

                    /*
                     * Final score.
                     */

                    $selectionScore =
                        $favoriteBonus
                        + $difficultyScore
                        + $randomScore;

                    return [
                        'question' =>
                            $question,

                        'score' =>
                            $selectionScore,

                        'is_favorite' =>
                            $isFavorite,

                        'category_sr' =>
                            $categorySr,
                    ];
                }
            );

        /*
        |--------------------------------------------------------------------------
        | SORT BY SCORE
        |--------------------------------------------------------------------------
        */

        $selectedQuestions =
            $scoredQuestions
                ->sortByDesc('score')
                ->values();

        /*
        |--------------------------------------------------------------------------
        | SEPARATE FAVORITES / OTHER CATEGORIES
        |--------------------------------------------------------------------------
        */

        $favoriteQuestions =
            $selectedQuestions
                ->filter(
                    fn ($item) =>
                        $item['is_favorite']
                )
                ->values();

        $otherQuestions =
            $selectedQuestions
                ->filter(
                    fn ($item) =>
                        !$item['is_favorite']
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | FAVORITE / OTHER MIX
        |--------------------------------------------------------------------------
        |
        | Target:
        |
        | 7 favorite
        | 3 other
        |
        | This is approximately 70/30.
        |
        */

        $favoriteTarget =
            min(
                7,
                $favoriteQuestions->count()
            );

        $otherTarget =
            min(
                $limit - $favoriteTarget,
                $otherQuestions->count()
            );

        $questions =
            $favoriteQuestions
                ->take($favoriteTarget)
                ->concat(
                    $otherQuestions
                        ->take($otherTarget)
                );

        /*
        |--------------------------------------------------------------------------
        | FILL REMAINING QUESTIONS
        |--------------------------------------------------------------------------
        */

        if ($questions->count() < $limit) {

            $selectedIds =
                $questions
                    ->pluck('question.id')
                    ->toArray();

            $remaining =
                $scoredQuestions
                    ->reject(
                        function ($item) use (
                            $selectedIds
                        ) {

                            return in_array(
                                $item['question']->id,
                                $selectedIds
                            );
                        }
                    )
                    ->sortByDesc('score')
                    ->take(
                        $limit -
                        $questions->count()
                    );

            $questions =
                $questions->concat(
                    $remaining
                );
        }

        /*
        |--------------------------------------------------------------------------
        | FINAL RANDOMIZATION
        |--------------------------------------------------------------------------
        */

        $questions =
            $questions
                ->map(
                    fn ($item) =>
                        $item['question']
                )
                ->shuffle()
                ->values();

        /*
        |--------------------------------------------------------------------------
        | SAFETY FALLBACK
        |--------------------------------------------------------------------------
        |
        | Only used if the database doesn't have enough
        | active questions.
        |
        */

        if ($questions->count() < $limit) {

            $selectedIds =
                $questions
                    ->pluck('id')
                    ->toArray();

            $extraQuery =
                clone $baseQuery;

            if (!empty($selectedIds)) {

                $extraQuery->whereNotIn(
                    'id',
                    $selectedIds
                );
            }

            $extra =
                $extraQuery
                    ->inRandomOrder()
                    ->limit(
                        $limit -
                        $questions->count()
                    )
                    ->get();

            $questions =
                $questions
                    ->concat($extra)
                    ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | FORMAT QUESTIONS
        |--------------------------------------------------------------------------
        */

        $formattedQuestions =
            $questions->map(
                function ($q) {

                    return [

                        'id' =>
                            (int) $q->id,

                        'question_text' => [

                            'en' =>
                                $q->getTranslation(
                                    'question_text',
                                    'en'
                                ) ?? '',

                            'am' =>
                                $q->getTranslation(
                                    'question_text',
                                    'am'
                                ) ?? '',
                        ],

                        'options' => [

                            'a' => [
                                'en' =>
                                    $q->option_a['en']
                                    ?? '',

                                'am' =>
                                    $q->option_a['am']
                                    ?? '',
                            ],

                            'b' => [
                                'en' =>
                                    $q->option_b['en']
                                    ?? '',

                                'am' =>
                                    $q->option_b['am']
                                    ?? '',
                            ],

                            'c' => [
                                'en' =>
                                    $q->option_c['en']
                                    ?? '',

                                'am' =>
                                    $q->option_c['am']
                                    ?? '',
                            ],

                            'd' => [
                                'en' =>
                                    $q->option_d['en']
                                    ?? '',

                                'am' =>
                                    $q->option_d['am']
                                    ?? '',
                            ],
                        ],

                        'correct_answer' =>
                            strtolower(
                                $q->correct_answer
                            ),

                        'image_url' =>
                            $q->image_url,
                    ];
                }
            );

        // ============================================================
        // 4. STREAK STATUS
        // ============================================================

        $streakStatus =
            $this->streakService
                ->passiveStreakCheck($user);

        // ============================================================
        // 5. CREATE GAME SESSION
        // ============================================================

        $session = GameSession::create([

            'user_id' =>
                $user->id,

            'total_questions' =>
                $formattedQuestions->count(),

            'correct_answers' =>
                0,

            'lives_lost' =>
                0,

            'current_streak' =>
                0,

            'base_xp' =>
                0,

            'bonus_xp' =>
                0,

            'xp_earned' =>
                0,

            'is_completed' =>
                false,
        ]);

        // ============================================================
        // 6. RESPONSE
        // ============================================================

        return response()->json([

            'status' =>
                'success',

            'data' => [

                'session_id' =>
                    $session->id,

                'questions' =>
                    $formattedQuestions,

                'lives' =>
                    (int) $user->daily_lives,

                'user_sr' =>
                    (int) round($userSR),

                'streak_status' =>
                    $streakStatus,

                'current_streak' =>
                    (int) $user->current_streak,

                'next_reset_at' =>
                    $user->lives_updated_at
                        ? Carbon::parse(
                            $user->lives_updated_at
                        )
                            ->addMinutes(
                                $regenMinutes
                            )
                            ->toIso8601String()
                        : null,
            ],
        ]);

    } catch (\Exception $e) {

        Log::error(
            'GetDailyQuestions Error: '
            . $e->getMessage(),
            [
                'user_id' =>
                    Auth::id(),

                'trace' =>
                    $e->getTraceAsString(),
            ]
        );

        return response()->json([
            'status' =>
                'error',

            'message' =>
                'Failed to load quiz',
        ], 500);
    }
}


    public function submitAnswer(Request $request)
{
    $request->validate([
        'session_id'      => 'required|integer',
        'question_id'     => 'required|integer',
        'selected_option' => 'nullable|string',
        'time_taken_ms'   => 'nullable|integer|min:0',
    ]);

    return DB::transaction(function () use ($request) {

        /** @var User $user */
        $user = Auth::user();

        Log::info('🎮 QUIZ SUBMIT RECEIVED', [
            'user_id'        => $user->id,
            'session_id'     => $request->session_id,
            'question_id'    => $request->question_id,
            'selected_option'=> $request->selected_option,
            'time_taken_ms'  => $request->time_taken_ms,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 1. LOCK GAME SESSION
        |--------------------------------------------------------------------------
        */

        $session = GameSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($session->is_completed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session already finished',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. LOCK QUESTION
        |--------------------------------------------------------------------------
        */

        $question = Question::lockForUpdate()
            ->findOrFail($request->question_id);

        /*
        |--------------------------------------------------------------------------
        | 3. PREVENT DOUBLE SUBMISSION
        |--------------------------------------------------------------------------
        */

        $alreadyAnswered = DB::table('quiz_responses')
            ->where('user_id', $user->id)
            ->where('session_id', $session->id)
            ->where('question_id', $question->id)
            ->exists();

        if ($alreadyAnswered) {
            return response()->json([
                'status' => 'error',
                'message' => 'Question already answered in this session.',
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. DETERMINE TIMEOUT
        |--------------------------------------------------------------------------
        */

        $rawOption = $request->input('selected_option');

        $selectedOption = strtolower(
            trim((string) $rawOption)
        );

        $isTimeout =
            $selectedOption === '' ||
            $selectedOption === 'timeout' ||
            $selectedOption === 'none';

        if ($isTimeout) {
            $selectedOption = 'timeout';
        }

        /*
        |--------------------------------------------------------------------------
        | 5. DETERMINE CORRECTNESS
        |--------------------------------------------------------------------------
        */

        $correctAnswer = strtolower(
            trim((string) $question->correct_answer)
        );

        $isCorrect =
            !$isTimeout &&
            $selectedOption === $correctAnswer;

        /*
        |--------------------------------------------------------------------------
        | 6. TIME
        |--------------------------------------------------------------------------
        */

        $timeMs = max(
            0,
            (int) $request->input('time_taken_ms', 0)
        );

        /*
        |--------------------------------------------------------------------------
        | 7. RECORD ANSWER
        |--------------------------------------------------------------------------
        */

        DB::table('quiz_responses')->insert([
            'user_id'         => $user->id,
            'session_id'      => $session->id,
            'question_id'     => $question->id,
            'selected_option' => $selectedOption,
            'is_correct'      => $isCorrect,
            'time_taken_ms'   => $timeMs,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        Log::info('📝 QUIZ RESPONSE SAVED', [
            'user_id'         => $user->id,
            'session_id'      => $session->id,
            'question_id'     => $question->id,
            'selected_option' => $selectedOption,
            'is_correct'      => $isCorrect,
            'time_taken_ms'   => $timeMs,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 8. QUESTION STATISTICS
        |--------------------------------------------------------------------------
        */

        $question->times_shown =
            (int) ($question->times_shown ?? 0) + 1;

        if ($isCorrect) {
            $question->times_correct =
                (int) ($question->times_correct ?? 0) + 1;
        }

        /*
        |--------------------------------------------------------------------------
        | 9. XP / STREAK / LIFE
        |--------------------------------------------------------------------------
        */

        $currentActionBaseXp = 0;
        $currentActionBonusXp = 0;

        if ($isCorrect) {

            $session->correct_answers =
                (int) $session->correct_answers + 1;

            $session->current_streak =
                (int) $session->current_streak + 1;

            $currentActionBaseXp = 10;

            if ($session->current_streak === 3) {
                $currentActionBonusXp = 20;
            } elseif ($session->current_streak > 3) {
                $currentActionBonusXp = 10;
            }

            $session->base_xp =
                (int) $session->base_xp +
                $currentActionBaseXp;

            $session->bonus_xp =
                (int) $session->bonus_xp +
                $currentActionBonusXp;

            $session->xp_earned =
                (int) $session->xp_earned +
                $currentActionBaseXp +
                $currentActionBonusXp;

        } else {

            /*
            |--------------------------------------------------------------------------
            | TIMEOUT / WRONG ANSWER
            |--------------------------------------------------------------------------
            |
            | Both are incorrect answers.
            | Both must consume one life.
            |
            */

            $session->lives_lost =
                (int) $session->lives_lost + 1;

            $session->current_streak = 0;

            if ((int) $user->daily_lives > 0) {

                $user->daily_lives =
                    (int) $user->daily_lives - 1;

                /*
                |--------------------------------------------------------------------------
                | Start regeneration timer
                |--------------------------------------------------------------------------
                */

                if (!$user->lives_updated_at) {
                    $user->lives_updated_at = now();
                }
            }

            Log::info('❤️ LIFE LOST', [
                'user_id' => $user->id,
                'question_id' => $question->id,
                'reason' => $isTimeout
                    ? 'timeout'
                    : 'wrong_answer',
                'remaining_lives' => $user->daily_lives,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 10. ADAPTIVE RATING
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Timeout MUST reach AdaptiveRatingService.
        |
        | false = incorrect
        |
        */

        Log::info('🧠 PROCESSING ADAPTIVE RATING', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'is_correct' => $isCorrect,
            'time_ms' => $timeMs,
            'user_sr_before' => $user->current_sr,
            'question_difficulty_before' =>
                $question->difficulty_score,
        ]);

        $rating = $this->adaptiveRatingService->processAnswer(
            $user,
            $question,
            $isCorrect,
            $timeMs
        );

        /*
        |--------------------------------------------------------------------------
        | 11. TOTAL ANSWER COUNT
        |--------------------------------------------------------------------------
        */

        $user->total_answers_count =
            (int) ($user->total_answers_count ?? 0) + 1;

        /*
        |--------------------------------------------------------------------------
        | 12. GAME OVER
        |--------------------------------------------------------------------------
        */

        $processed =
            (int) $session->correct_answers +
            (int) $session->lives_lost;

        $isGameOver =
            $processed >= (int) $session->total_questions ||
            (int) $user->daily_lives <= 0;

        /*
        |--------------------------------------------------------------------------
        | 13. SAVE
        |--------------------------------------------------------------------------
        */

        $question->save();
        $session->save();
        $user->save();

        /*
        |--------------------------------------------------------------------------
        | 14. VERIFY SAVED VALUES
        |--------------------------------------------------------------------------
        */

        Log::info('✅ QUIZ SUBMIT DATABASE SAVED', [

    'user_id' =>
        $user->id,

    'question_id' =>
        $question->id,

    'category_id' =>
        $question->category_id,

    'daily_lives' =>
        $user->daily_lives,

    /*
     * GLOBAL SR
     */
    'user_sr' =>
        $user->current_sr,

    'best_sr' =>
        $user->best_sr,

    /*
     * CATEGORY SR
     */
    'category_sr_before' =>
        $rating['previous_category_sr'] ?? null,

    'category_sr_after' =>
        $rating['new_category_sr'] ?? null,

    'category_sr_change' =>
        $rating['category_sr_change'] ?? null,

    /*
     * QUESTION
     */
    'question_difficulty_score' =>
        $question->difficulty_score,

    'question_difficulty' =>
        $question->difficulty,

    'question_score_change' =>
        $rating['question_score_change'] ?? null,

    /*
     * USER GLOBAL CHANGE
     */
    'user_sr_change' =>
        $rating['user_sr_change'] ?? null,

    'is_timeout' =>
        $isTimeout,
]);

        /*
        |--------------------------------------------------------------------------
        | 15. RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'status' => 'success',

            'data' => [

                'is_correct' =>
                    $isCorrect,

                'correct_answer' =>
                    $correctAnswer,

                'is_timeout' =>
                    $isTimeout,

                'is_game_over' =>
                    $isGameOver,

                'xp_gained' =>
                    $currentActionBaseXp +
                    $currentActionBonusXp,

                'streak_count' =>
                    (int) $session->current_streak,

                'daily_streak' =>
                    null,

                'base_xp' =>
                    (int) $session->base_xp,

                'bonus_xp' =>
                    (int) $session->bonus_xp,

                'xp_earned' =>
                    (int) $session->xp_earned,

                'correct_count' =>
                    (int) $session->correct_answers,

                'lives_remaining' =>
                    (int) $user->daily_lives,

                /*
                |--------------------------------------------------------------------------
                | USER SR
                |--------------------------------------------------------------------------
                */

                'new_sr' =>
                    (int) round(
                        $user->current_sr
                    ),

                'previous_sr' =>
                    (int) round(
                        $rating['previous_user_sr'] ?? 0
                    ),

                'sr_change' =>
                    round(
                        $rating['user_sr_change'] ?? 0,
                        2
                    ),

                'best_sr' =>
                    (int) round(
                        $user->best_sr ?? 0
                    ),

                /*
                |--------------------------------------------------------------------------
                | QUESTION DIFFICULTY
                |--------------------------------------------------------------------------
                */

                'question_difficulty_score' =>
                    round(
                        $question->difficulty_score,
                        2
                    ),

                'question_difficulty' =>
                    $question->difficulty,

                'question_score_change' =>
                    round(
                        $rating['question_score_change'] ?? 0,
                        2
                    ),

                /*
                |--------------------------------------------------------------------------
                | DIAGNOSTICS
                |--------------------------------------------------------------------------
                */

                'expected_probability' =>
                    $rating['expected_probability'] ?? null,
            ],
        ]);
    });
}

    public function syncFinalScore(Request $request)
{
    $request->validate([
        'session_id' => 'required|integer',
    ]);

    return DB::transaction(function () use ($request) {

        /** @var User $user */
        $user = Auth::user();

        /*
         * ============================================================
         * LOCK SESSION
         * ============================================================
         *
         * Prevent two finish requests from awarding XP twice.
         */
        $session = GameSession::where('id', $request->session_id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();

        /*
         * ============================================================
         * ALREADY FINALIZED
         * ============================================================
         *
         * If the session was already finalized, NEVER award XP or
         * update streak again.
         */
        if ($session->is_completed) {

            return response()->json([
                'status' => 'success',
                'already_completed' => true,
                'data' => [
                    'session_id' =>
                        (int) $session->id,

                    'is_completed' =>
                        true,

                    'correct_answers' =>
                        (int) $session->correct_answers,

                    'total_questions' =>
                        (int) $session->total_questions,

                    'lives_lost' =>
                        (int) $session->lives_lost,

                    'xp_earned' =>
                        (int) $session->xp_earned,

                    'current_streak' =>
                        (int) $session->current_streak,

                    'user_sr' =>
                        (int) round($user->current_sr ?? 0),

                    'best_sr' =>
                        (int) round($user->best_sr ?? 0),

                    'daily_lives' =>
                        (int) $user->daily_lives,

                    'total_xp' =>
                        (int) $user->total_xp,
                ],
            ]);
        }

        /*
         * ============================================================
         * REBUILD SESSION FROM DATABASE
         * ============================================================
         *
         * Never trust frontend counters.
         */
        $responses = DB::table('quiz_responses')
            ->where('user_id', $user->id)
            ->where('session_id', $session->id)
            ->get();

        $correctAnswers = $responses
            ->where('is_correct', true)
            ->count();

        $answeredCount = $responses->count();

        $livesLost = $responses
            ->where('is_correct', false)
            ->count();

        /*
         * ============================================================
         * RECONCILE SESSION COUNTERS
         * ============================================================
         */
        $session->correct_answers = $correctAnswers;
        $session->lives_lost = $livesLost;

        /*
         * ============================================================
         * CHECK WHETHER SESSION IS REALLY FINISHED
         * ============================================================
         */
        $isFinished =
            $answeredCount >= (int) $session->total_questions ||
            (int) $user->daily_lives <= 0;

        /*
         * If the frontend calls /finish too early, do NOT finalize.
         */
        if (!$isFinished) {

            $session->save();

            return response()->json([
                'status' => 'success',
                'already_completed' => false,
                'is_completed' => false,

                'data' => [
                    'session_id' =>
                        (int) $session->id,

                    'correct_answers' =>
                        (int) $session->correct_answers,

                    'total_questions' =>
                        (int) $session->total_questions,

                    'lives_lost' =>
                        (int) $session->lives_lost,

                    'xp_earned' =>
                        (int) $session->xp_earned,

                    'current_streak' =>
                        (int) $session->current_streak,

                    'user_sr' =>
                        (int) round($user->current_sr ?? 0),

                    'best_sr' =>
                        (int) round($user->best_sr ?? 0),

                    'daily_lives' =>
                        (int) $user->daily_lives,

                    'total_xp' =>
                        (int) $user->total_xp,
                ],
            ]);
        }

        /*
         * ============================================================
         * PERFECT GAME BONUS
         * ============================================================
         *
         * Apply the bonus ONLY here.
         *
         * This guarantees:
         *
         * - bonus is applied once
         * - bonus is included in session XP
         * - bonus is included in total user XP
         */
        $perfectBonus = 0;

        if (
            (int) $session->correct_answers ===
                (int) $session->total_questions
            &&
            (int) $session->lives_lost === 0
        ) {
            $perfectBonus = 100;

            $session->bonus_xp += $perfectBonus;
            $session->xp_earned += $perfectBonus;
        }

        /*
         * ============================================================
         * FINAL SERVER XP
         * ============================================================
         *
         * This is now the complete XP amount, including
         * the perfect-game bonus if applicable.
         */
        $serverXp = (int) $session->xp_earned;

        /*
         * ============================================================
         * FINALIZE SESSION
         * ============================================================
         */
        $session->is_completed = true;

        /*
        * ============================================================
        * LEVEL UP DETECTION — BEFORE XP
        * ============================================================
        */

        $previousLevelData = $user->getLevelDataAttribute();

        $previousLevel = (int) (
            $previousLevelData['level'] ?? 1
        );

        /*
        * ============================================================
        * TRANSFER XP TO USER
        * ============================================================
        */

        $previousXp = (int) ($user->total_xp ?? 0);

            $user->total_xp =
                $previousXp + $serverXp;

            $this->xpMilestoneService->check(
                $user,
                $previousXp,
                (int) $user->total_xp
            );

        /*
         * ============================================================
         * UPDATE LAST PLAYED DATE
         * ============================================================
         */
       $user->last_played_date = now();

        /*
        * ============================================================
        * LEVEL UP DETECTION — AFTER XP
        * ============================================================
        */

        $newLevelData = $user->getLevelDataAttribute();

        $newLevel = (int) (
            $newLevelData['level'] ?? 1
        );

        $levelUp = $newLevel > $previousLevel;

        /*
        * ============================================================
        * UPDATE DAILY STREAK
        * ============================================================
        */

        $streakData = $this->streakService->updateStreak($user);

       /*
        * ============================================================
        * SAVE EVERYTHING
        * ============================================================
        */

        $session->save();
        $user->save();

        /*
        * LEVEL UP AUTOMATION EVENT
        */

        if ($levelUp) {

            event(
                new \App\Events\Player\LevelUp(
                    $user->fresh(),
                    $previousLevel,
                    $newLevel
                )
            );
        }

        /*
        * GAME COMPLETED AUTOMATION EVENT
        */

        event(
            new \App\Events\Player\GameCompleted(
                $user->fresh(),
                $session->fresh()
            )
        );

        /*
         * ============================================================
         * FINAL RESPONSE
         * ============================================================
         */
        return response()->json([
            'status' => 'success',
            'already_completed' => false,

            'data' => [
                'session_id' =>
                    (int) $session->id,

                'is_completed' =>
                    true,

                'correct_answers' =>
                    (int) $session->correct_answers,

                'total_questions' =>
                    (int) $session->total_questions,

                'lives_lost' =>
                    (int) $session->lives_lost,

                'xp_earned' =>
                    (int) $session->xp_earned,

                'perfect_bonus' =>
                    (int) $perfectBonus,

                'base_xp' =>
                    (int) $session->base_xp,

                'bonus_xp' =>
                    (int) $session->bonus_xp,

                'current_streak' =>
                    (int) $session->current_streak,

                'user_sr' =>
                    (int) round($user->current_sr ?? 0),

                'best_sr' =>
                    (int) round($user->best_sr ?? 0),

                'daily_lives' =>
                    (int) $user->daily_lives,

                'total_xp' =>
                    (int) $user->total_xp,

                'streak' =>
                    $streakData['count'] ??
                    $user->current_streak,
            ],
        ]);
    });
}

    public function uploadQuestionImage(Request $request, $id)
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048']);

        $question = Question::findOrFail($id);
        $old = $question->getRawOriginal('image_url');

        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('image')->store('questions', 'public');
        $question->update(['image_url' => $path]);

        return response()->json([
            'status' => 'success',
            'data' => ['full_url' => asset('storage/' . $path)]
        ]);
    }

    public function getLeaderboard(Request $request)
    {
        try {
            $leaders = User::orderBy('total_xp', 'desc')
                ->limit(10)
                ->get(['id', 'username', 'name', 'total_xp', 'best_streak']);

            $user = Auth::user();
            $userRank = User::where('total_xp', '>', $user->total_xp)->count() + 1;

            return response()->json([
                'status' => 'success',
                'leaders' => $leaders,
                'current_user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'total_xp' => (int)$user->total_xp,
                    'rank' => $userRank
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Leaderboard unavailable'], 500);
        }
    }
}