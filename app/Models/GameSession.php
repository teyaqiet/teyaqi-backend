<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_questions',
        'correct_answers',
        'xp_earned',
        'base_xp',
        'bonus_xp',
        'lives_lost',
        'current_streak',
        'max_streak',
        'is_completed',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_completed' => 'boolean',
        'total_questions' => 'integer',
        'correct_answers' => 'integer',
        'xp_earned' => 'integer',
        'base_xp' => 'integer',
        'bonus_xp' => 'integer',
        'lives_lost' => 'integer',
        'current_streak' => 'integer',
        'max_streak' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }






/*
    |--------------------------------------------------------------------------
    | Quiz Responses
    |--------------------------------------------------------------------------
    */

    public function quizResponses()
    {
        return $this->hasMany(
            QuizResponse::class,
            'session_id'
        );
    }

}