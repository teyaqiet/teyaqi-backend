<?php

namespace App\Models;

use App\Models\Challenge;
use App\Enums\QuestionSelectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeQuestionRule extends Model
{
    protected $fillable = [
        'challenge_id', 'selection_type', 'category_id', 'topic_id', 'difficulty', 'tags', 'question_count'
    ];

    protected $casts = [
        'selection_type' => QuestionSelectionType::class,
        'tags' => 'array',
    ];

public function challenge(): BelongsTo
{
    return $this->belongsTo(Challenge::class);
}}