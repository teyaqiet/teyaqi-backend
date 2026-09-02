<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

class ChallengeController extends Controller
{

    /**
     * List Challenges
     */
    public function index(Request $request)
    {
        $query = Challenge::with('category');

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('daily')) {
            $query->where('is_daily', true);
        }


        $challenges = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();


        $categories = Category::orderBy('name')->get();


        return view(
            'admin.challenges.index',
            compact(
                'challenges',
                'categories'
            )
        );
    }


    /**
     * Show Challenge Details
     */
    public function show(Challenge $challenge)
    {
        $challenge->load([
            'category',
            'topic',
            'rules',
            'manualQuestions'
        ]);


        $rawStats = $challenge->attempts()
            ->selectRaw('
                COUNT(*) as total_attempts,
                COUNT(DISTINCT user_id) as unique_players,
                SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passed_count,
                AVG(score) as avg_score,
                MAX(score) as high_score
            ')
            ->first();


        $totalAttempts = (int) ($rawStats->total_attempts ?? 0);


        $stats = [

            'total_attempts' =>
                $totalAttempts,

            'unique_players' =>
                (int) ($rawStats->unique_players ?? 0),

            'completed_count' =>
                (int) ($rawStats->completed_count ?? 0),

            'pass_rate' =>
                $totalAttempts > 0
                ? round(
                    (($rawStats->passed_count ?? 0)
                    / $totalAttempts) * 100,
                    1
                )
                : 0,

            'avg_score' =>
                round(
                    (float) ($rawStats->avg_score ?? 0),
                    1
                ),

            'high_score' =>
                (int) ($rawStats->high_score ?? 0),

        ];


        $recentAttempts = $challenge->attempts()
            ->with('user')
            ->latest()
            ->paginate(10);


        return view(
            'admin.challenges.show',
            compact(
                'challenge',
                'stats',
                'recentAttempts'
            )
        );
    }



    /**
     * CREATE PAGE
     */
    public function create()
    {
        return view(
            'admin.challenges.create',
            [

                'categories' =>
                    Category::orderBy('name')->get(),

                'topics' =>
                    Topic::orderBy('name')->get(),

                'questions' =>
                    Question::latest()->get(),

            ]
        );
    }



    /**
     * STORE CHALLENGE
     */
    public function store(Request $request)
    {

        $validated = $this->validateChallenge($request);


        DB::transaction(function () use ($request, $validated, &$challenge) {


            $challenge = Challenge::create([

                'title' =>
                    $validated['title'],

                'slug' =>
                    $validated['slug']
                    ??
                    Str::slug($validated['title']),


                'description' =>
                    $validated['description'] ?? null,


                'thumbnail' =>
                    $this->uploadThumbnail($request),


                'status' =>
                    $validated['status'],


                'type' =>
                    $validated['type'],


                'visibility' =>
                    $validated['visibility'],


                'category_id' =>
                    $validated['category_id'] ?? null,


                'topic_id' =>
                    $validated['topic_id'] ?? null,


                'is_daily' =>
                    $request->boolean('is_daily'),


                'is_featured' =>
                    $request->boolean('is_featured'),


                'is_ranked' =>
                    $request->boolean('is_ranked'),


                'allow_retry' =>
                    $request->boolean('allow_retry'),


                'difficulty' =>
                    $validated['difficulty'] ?? null,


                'question_count' =>
                    $validated['question_count'] ?? 5,


                'passing_score' =>
                    $validated['passing_score'] ?? 70,


                'time_mode' =>
                    $validated['time_mode'] ?? 'per_session',


                'time_limit' =>
                    $validated['time_limit'] ?? 60,


                'level_min' =>
                    $validated['level_min'] ?? 1,


                'level_max' =>
                    $validated['level_max'] ?? null,


                'reward_xp' =>
                    $validated['reward_xp'] ?? 0,


                'reward_coins' =>
                    $validated['reward_coins'] ?? 0,


                'start_at' =>
                    $validated['start_at'] ?? null,


                'end_at' =>
                    $validated['end_at'] ?? null,

            ]);



            $this->syncRules(
                $challenge,
                $request->rules ?? []
            );



            $challenge
                ->manualQuestions()
                ->sync(
                    $request->manual_questions ?? []
                );


        });

        // Log activity
        ActivityLogger::log('created', 'Created new challenge: ' . $challenge->title, $challenge);


        return redirect()
            ->route(
                'admin.challenges.show',
                $challenge
            )
            ->with(
                'success',
                'Challenge created successfully.'
            );
    }




    /**
     * Show the form for editing the specified challenge.
     */
    public function edit(Challenge $challenge)
    {
        $challenge->load([
            'rules',
            'manualQuestions',
            'category',
            'topic'
        ]);

        $categories = Category::orderBy('name')->get();

        $topics = Topic::orderBy('name')->get();

        $questions = Question::latest()->get();


        return view(
            'admin.challenges.edit',
            compact(
                'challenge',
                'categories',
                'topics',
                'questions'
            )
        );
    }




    /**
     * UPDATE
     */
    public function update(Request $request, Challenge $challenge)
    {
        $validated = $request->validate([

            // General
            'title' => [
                'required',
                'string',
                'max:255'
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'max:5120'
            ],


            // Settings
            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'active',
                    'archived',
                    'published'
                ])
            ],

            'type' => [
                'required',
                Rule::in([
                    'daily',
                    'topic',
                    'timed',
                    'ranked'
                ])
            ],

            'visibility' => [
                'required',
                Rule::in([
                    'public',
                    'private',
                    'hidden'
                ])
            ],


            'category_id' => [
                'nullable',
                'exists:categories,id'
            ],

            'topic_id' => [
                'nullable',
                'exists:topics,id'
            ],


            // Rules
            'difficulty' => [
                'nullable',
                Rule::in([
                    'easy',
                    'medium',
                    'hard'
                ])
            ],

            'question_count' => [
                'nullable',
                'integer'
            ],

            'passing_score' => [
                'nullable',
                'integer'
            ],

            'time_mode' => [
                'nullable'
            ],

            'time_limit' => [
                'nullable',
                'integer'
            ],

            'level_min' => [
                'nullable',
                'integer'
            ],

            'level_max' => [
                'nullable',
                'integer'
            ],


            // Rewards

            'reward_xp' => [
                'nullable',
                'integer'
            ],

            'reward_coins' => [
                'nullable',
                'integer'
            ],


            // Scheduling

            'start_at' => [
                'nullable',
                'date'
            ],

            'end_at' => [
                'nullable',
                'date'
            ],


            // Manual questions

            'manual_questions' => [
                'nullable',
                'array'
            ],

            'manual_questions.*' => [
                'exists:questions,id'
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Thumbnail Upload
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('thumbnail')) {

            $validated['thumbnail'] =
                $request
                ->file('thumbnail')
                ->store(
                    'challenges/thumbnails',
                    'public'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Boolean Fields
        |--------------------------------------------------------------------------
        */

        $validated['is_daily'] =
            $request->has('is_daily');

        $validated['is_featured'] =
            $request->has('is_featured');

        $validated['is_ranked'] =
            $request->has('is_ranked');

        $validated['allow_retry'] =
            $request->has('allow_retry');



        /*
        |--------------------------------------------------------------------------
        | Update Challenge
        |--------------------------------------------------------------------------
        */

        $challenge->update($validated);



        /*
        |--------------------------------------------------------------------------
        | Sync Manual Questions
        |--------------------------------------------------------------------------
        */

        if ($request->has('manual_questions')) {

            $challenge
                ->manualQuestions()
                ->sync(
                    $request->manual_questions
                );

        } else {

            $challenge
                ->manualQuestions()
                ->detach();

        }



        /*
        |--------------------------------------------------------------------------
        | Update Rules
        |--------------------------------------------------------------------------
        */

        if ($request->has('rules')) {

            $challenge->rules()->delete();


            foreach ($request->rules as $rule) {


                $challenge->rules()->create([

                    'selection_type' =>
                        $rule['selection_type'] ?? 'random',

                    'category_id' =>
                        $rule['category_id'] ?? null,

                    'topic_id' =>
                        $rule['topic_id'] ?? null,

                    'difficulty' =>
                        $rule['difficulty'] ?? null,

                    'tags' =>
                        isset($rule['tags'])
                        ? explode(',', $rule['tags'])
                        : null,


                    'question_count' =>
                        $rule['question_count'] ?? 5,

                ]);

            }

        }

        // Log activity
        ActivityLogger::log('updated', 'Updated challenge: ' . $challenge->title, $challenge);


        return redirect()
            ->route(
                'admin.challenges.show',
                $challenge
            )
            ->with(
                'success',
                'Challenge updated successfully.'
            );
    }





    /**
     * VALIDATION
     */
    private function validateChallenge(Request $request)
    {

        return $request->validate([


            'title'=>
                [
                    'required',
                    'string',
                    'max:255'
                ],


            'slug'=>
                [
                    'nullable',
                    'string'
                ],


            'description'=>
                [
                    'nullable'
                ],


            'status'=>
                [
                    'required',
                    Rule::in([
                        'draft',
                        'active',
                        'archived',
                        'published'
                    ])
                ],


            'type'=>
                [
                    'required'
                ],


            'visibility'=>
                [
                    'required'
                ],


            'category_id'=>
                [
                    'nullable',
                    'exists:categories,id'
                ],


            'topic_id'=>
                [
                    'nullable',
                    'exists:topics,id'
                ],


            'question_count'=>
                [
                    'nullable',
                    'integer'
                ],


            'passing_score'=>
                [
                    'nullable',
                    'integer'
                ],

        ]);
    }





    /**
     * Upload Thumbnail
     */
    private function uploadThumbnail(Request $request)
    {

        if(!$request->hasFile('thumbnail')){
            return null;
        }


        return $request
            ->file('thumbnail')
            ->store(
                'challenges/thumbnails',
                'public'
            );
    }





    /**
     * Save Dynamic Rules
     */
    private function syncRules(
        Challenge $challenge,
        array $rules
    )
    {

        $challenge
            ->rules()
            ->delete();


        foreach($rules as $rule)
        {

            if(empty($rule)){
                continue;
            }


            $challenge
                ->rules()
                ->create([

                    'category_id'=>
                        $rule['category_id'] ?? null,


                    'topic_id'=>
                        $rule['topic_id'] ?? null,


                    'selection_type'=>
                        $rule['selection_type']
                        ??
                        'random',


                    'difficulty'=>
                        $rule['difficulty']
                        ??
                        null,


                    'question_count'=>
                        $rule['question_count']
                        ??
                        5,


                    'tags'=>
                        $rule['tags']
                        ?? 
                        null,

                ]);

        }

    }


}