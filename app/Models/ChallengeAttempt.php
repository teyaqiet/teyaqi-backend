<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeAttempt extends Model
{
    protected $fillable = [
        'challenge_id', 'user_id', 'score', 'correct_answers', 'wrong_answers',
        'time_spent', 'completed', 'passed', 'reward_claimed', 'started_at', 'finished_at'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'passed' => 'boolean',
        'reward_claimed' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function challenge(): BelongsTo
{
    return $this->belongsTo(Challenge::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}


    public function session()
    {
        return $this->belongsTo(
            GameSession::class,
            'session_id'
        );
    }

}