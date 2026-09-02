<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitChallengeRequest;
use App\Http\Resources\{ChallengeResource, ChallengeAttemptResource};
use App\Models\{Challenge, ChallengeAttempt};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\{ChallengeEngineService, ChallengeQuestionGeneratorService};
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChallengeController extends Controller
{
    public function __construct(
        protected ChallengeEngineService $engineService,
        protected ChallengeQuestionGeneratorService $questionService
    ) {}

    /**
     * List active challenges
     */
    public function index(): AnonymousResourceCollection
    {
        $challenges = Challenge::active()
            ->latest()
            ->paginate(15);

        return ChallengeResource::collection($challenges);
    }

    /**
     * Show single challenge
     */
    public function show(Challenge $challenge): ChallengeResource
    {
        return new ChallengeResource($challenge);
    }

    /**
     * Get daily challenge
     */
    public function daily(): ChallengeResource
    {
        $dailyChallenge = Challenge::active()
            ->where(function ($query) {
                $query->where('is_daily', true)
                      ->orWhere('type', 'daily');
            })
            ->latest('start_at')
            ->firstOrFail();

        return new ChallengeResource($dailyChallenge);
    }

    /**
     * Start challenge session
     */
    public function start($id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Eager load everything needed for the generator's resolution logic
            $challenge = Challenge::with(['rules', 'manualQuestions'])->find($id);

            if (!$challenge) {
                return response()->json([
                    'error' => 'Challenge not found'
                ], 404);
            }

            /**
             * PIPELINE STAGE 1: delegate resolution to your generator service
             */
            $questions = $this->questionService->generate($challenge);

            if ($questions->isEmpty()) {
                \Log::error('START_FAILED_NO_QUESTIONS', [
                    'challenge_id' => $id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'error' => 'No questions found matching this challenge\'s rules configuration pool.'
                ], 404);
            }

            /**
             * PIPELINE STAGE 2: Normalize JSON Dictionaries for Next.js / Telegram UI
             */
            $formattedQuestions = $questions->map(function ($q) {
                $questionText = $q->getRawOriginal('question_text') ?? $q->question_text;
                
                if (is_string($questionText)) {
                    $decoded = json_decode($questionText, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $questionText = $decoded;
                    }
                }

                $options = [];
                if (isset($q->options)) {
                    $options = is_array($q->options) ? $q->options : json_decode($q->options, true);
                } else {
                    $options = [
                        'a' => $q->getRawOriginal('option_a') ?? $q->option_a,
                        'b' => $q->getRawOriginal('option_b') ?? $q->option_b,
                        'c' => $q->getRawOriginal('option_c') ?? $q->option_c,
                        'd' => $q->getRawOriginal('option_d') ?? $q->option_d,
                    ];
                }

                $normalizedOptions = [];
                foreach (['a', 'b', 'c', 'd'] as $key) {
                    $value = $options[$key] ?? null;
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }
                    $normalizedOptions[$key] = $value;
                }

                return [
                    'id' => $q->id,
                    'question_text' => $questionText, 
                    'image_url' => $q->image_url,
                    'options' => $normalizedOptions,
                    'correct_answer' => strtolower($q->correct_answer ?? 'a'),
                    'difficulty_score' => (int) ($q->difficulty_score ?? 50),
                ];
            })->values();

            /**
             * PIPELINE STAGE 3: Track Attempt Persistence
             */
            $attempt = ChallengeAttempt::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'started_at' => now(),
                'completed' => false,
                'passed' => false,
                'reward_claimed' => false,
                'score' => 0,
                'correct_answers' => 0,
                'wrong_answers' => 0,
                'time_spent' => 0,
            ]);

            return response()->json([
                'success' => true,
                'attempt_id' => $attempt->id,
                'questions' => $formattedQuestions,
                'challenge' => [
                    'id' => $challenge->id,
                    'title' => $challenge->title,
                    'time_mode' => $challenge->time_mode ?? 'per_session',
                    'time_limit' => (int) ($challenge->time_limit ?? 60),
                    'reward_xp' => (int) ($challenge->reward_xp ?? 0),
                    'reward_coins' => (int) ($challenge->reward_coins ?? 0),
                ],
            ]);

        } catch (\Throwable $e) {
            \Log::error('START_CRITICAL_ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'error' => 'System error',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Submit challenge payload data
     */
    public function submit(Request $request, $attemptId)
    {
        try {
            $user = $request->user();
            $now = now();
            
            // 1. Fetch Challenge Attempt record from database
            $attempt = ChallengeAttempt::findOrFail($attemptId);

            // 2. Safe Time-Based Life Regeneration
            $maxLives = 5;
            $regenMins = 30;
            if ($user->daily_lives < $maxLives && $user->lives_updated_at) {
                $lastUpdate = Carbon::parse($user->lives_updated_at);
                $diffInMinutes = $lastUpdate->diffInMinutes($now);
                if ($diffInMinutes >= $regenMins) {
                    $gained = floor($diffInMinutes / $regenMins);
                    $user->daily_lives = min($maxLives, $user->daily_lives + $gained);
                    $user->lives_updated_at = $lastUpdate->addMinutes($gained * $regenMins);
                }
            }

            // 3. Count incorrect answers from frontend payload metrics
            $responses = $request->input('responses', []);
            $wrongAnswersCount = 0;

            foreach ($responses as $resp) {
                $respArray = (array) $resp;
                if (isset($respArray['is_correct']) && !$respArray['is_correct']) {
                    $wrongAnswersCount++;
                }
            }

            // 4. Process heart depletion deductions
            $oldLives = $user->daily_lives;
            $user->daily_lives = max(0, $user->daily_lives - $wrongAnswersCount);

            // Start recovery timer if user fell from max full capacity
            if ($oldLives >= $maxLives && $user->daily_lives < $maxLives) {
                $user->lives_updated_at = $now;
            }
            $user->save();

            /**
             * 5. RUN ENGINE LIFECYCLE PERSISTENCE
             * Hands execution payload data to engine service to safely update game logs, 
             * challenge stats, streaks, and award coins/XP.
             */
            $results = $this->engineService->submitSession($attempt, $request->all());

            // 6. Return the resource signature that useGameSync.ts expects to decode
            return response()->json([
                'status' => 'success',
                'results' => $results,
                'attempt' => new ChallengeAttemptResource($attempt),
                'daily_lives' => $user->daily_lives
            ]);

        } catch (\Throwable $e) {
            \Log::error('SUBMIT_CRITICAL_ERROR', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'error' => 'Submission synchronization failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve and stream secure challenge thumbnails directly from local private storage app silos.
     *
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function getThumbnail(string $filename)
    {
        // Points directly to storage/app/private/challenges/thumbnails/
        $relativePath = 'private/challenges/thumbnails/' . $filename;
        $absolutePath = storage_path('app/' . $relativePath);

        if (!file_exists($absolutePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requested thumbnail asset could not be found.'
            ], 404);
        }

        // Determine target mime type manually for non-public content delivery stream
        $mimeType = mime_content_type($absolutePath);

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=86400'
        ]);
    }
}