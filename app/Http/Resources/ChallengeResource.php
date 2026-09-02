<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ChallengeResource extends JsonResource
{
    protected ?Collection $loadedQuestions = null;

    public function withQuestions(Collection $questions): self
    {
        $this->loadedQuestions = $questions;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type->value ?? $this->type,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'question_count' => $this->question_count,
            
            // 🔒 SECURE TRANSIENT ASSET ROUTE FOR TELEGRAM WEBVIEW NATIVE STREAMING
            'thumbnail_url' => $this->thumbnail 
                ? URL::temporarySignedRoute(
                    'api.challenges.thumbnail',
                    now()->addMinutes(20), // Valid for 20 minutes, completely avoiding unauthorized 401s
                    ['filename' => $this->thumbnail]
                ) 
                : null,

            // ⚡ Base-level mappings to perfectly feed Next.js frontend state requirements
            'rewardXp' => (int) ($this->reward_xp ?? 10),
            'rewardCoins' => (int) ($this->reward_coins ?? 0),
            'timeLimit' => (int) ($this->time_limit ?? 15),
            'timeMode' => $this->time_mode ?? 'per_question',
            'lives' => (int) ($this->config['lives'] ?? 3),

            // Backward compatibility placeholders
            'time_limit_seconds' => $this->time_limit, 
            
            'rewards' => [
                'xp' => $this->reward_xp,
                'coins' => $this->reward_coins,
            ],
            'config' => $this->config ?? [
                'allow_skip' => false,
                'show_timer' => true,
                'lives' => 3,
                'streak_multiplier' => true,
                'time_bonus' => true,
                'time_mode' => $this->time_mode ?? 'per_question', 
                'time_limit' => $this->time_limit ?? 15,          
            ],

            // Map the questions and extract translated text + options cleanly
            'questions' => $this->when($this->loadedQuestions !== null, function() {
                return $this->loadedQuestions->map(fn($q) => [
                    'id' => $q->id,
                    'image_url' => $q->image_url,
                    'correct_answer' => strtolower($q->correct_answer ?? 'a'), // Guard string casing consistency
                    
                    // Localized text mapping for client UI rendering
                    'question_text' => $q->getTranslation('question_text', 'en') ?? data_get($q->question_text, 'en'),
                    'text' => [
                        'en' => $q->getTranslation('question_text', 'en') ?? data_get($q->question_text, 'en'),
                        'am' => $q->getTranslation('question_text', 'am') ?? data_get($q->question_text, 'am'),
                    ], 
                    
                    // Flattened option nodes
                    'options' => [
                        'a' => [
                            'en' => data_get($q->option_a, 'en', ''),
                            'am' => data_get($q->option_a, 'am', '')
                        ],
                        'b' => [
                            'en' => data_get($q->option_b, 'en', ''),
                            'am' => data_get($q->option_b, 'am', '')
                        ],
                        'c' => [
                            'en' => data_get($q->option_c, 'en', ''),
                            'am' => data_get($q->option_c, 'am', '')
                        ],
                        'd' => [
                            'en' => data_get($q->option_d, 'en', ''),
                            'am' => data_get($q->option_d, 'am', '')
                        ],
                    ],
                ]);
            }),
        ];
    }
}