<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCategoryRating extends Model
{
    protected $table = 'user_category_ratings';

    protected $fillable = [
        'user_id',
        'category_id',
        'sr',
        'questions_answered',
        'correct_answers',
        'last_answered_at',
    ];

    protected $casts = [
        'sr' => 'float',
        'questions_answered' => 'integer',
        'correct_answers' => 'integer',
        'last_answered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}