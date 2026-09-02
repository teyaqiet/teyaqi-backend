<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChallenge extends Model
{
    // Explicitly define the table name since Laravel would look for "daily_challenges"
    protected $table = 'daily_challenge_sessions';

    protected $fillable = ['user_id', 'score', 'advanced_streak'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}