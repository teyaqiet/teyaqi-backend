<?php

namespace App\Models;

use App\Enums\{ChallengeType, ChallengeStatus, ChallengeVisibility};
use Illuminate\Database\Eloquent\{Model, Builder};
use Illuminate\Database\Eloquent\Relations\{HasMany, BelongsToMany, BelongsTo};
use Illuminate\Support\Str;

class Challenge extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'type', 'status', 'visibility', 'thumbnail',
        'category_id', 'topic_id', 'difficulty', 'level_min', 'level_max',
        'question_count', 'time_limit_seconds', 'passing_score', 'reward_xp',
        'reward_coins', 'is_featured', 'is_daily', 'is_ranked', 'allow_retry',
        'time_mode','time_limit',
        'start_at', 'end_at', 'config', 'created_by'
    ];

    protected $casts = [
        'type' => ChallengeType::class,
        'status' => ChallengeStatus::class,
        'visibility' => ChallengeVisibility::class,
        'is_featured' => 'boolean',
        'is_daily' => 'boolean',
        'is_ranked' => 'boolean',
        'allow_retry' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'config' => 'array',
    ];

    protected static function boot()
{
    parent::boot();
    static::creating(function ($challenge) {
        if (! $challenge->slug) {
            $challenge->slug = Str::slug($challenge->title);
        }
    });
}

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ChallengeStatus::ACTIVE)
            ->where('visibility', ChallengeVisibility::PUBLIC)
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()));
    }

    // Relationships
    public function rules(): HasMany 
    {
        return $this->hasMany(ChallengeQuestionRule::class);
    }
    
    public function manualQuestions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'challenge_questions')
            ->withPivot('order')
            ->orderByPivot('order', 'asc');
    }

    public function attempts(): HasMany 
    {
        return $this->hasMany(ChallengeAttempt::class);
    }

    public function category(): BelongsTo 
    {
        return $this->belongsTo(Category::class);
    }
public function topic(): BelongsTo 
{
    return $this->belongsTo(Topic::class);
}

public function questions()
{
    // Ensure the table name 'challenge_questions' matches your database pivot table name
    return $this->belongsToMany(Question::class, 'challenge_questions', 'challenge_id', 'question_id');
}


}