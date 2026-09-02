<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStreak extends Model
{
    protected $fillable = [
        'user_id',
        'current_streak',
        'best_streak',
        'freeze_shields',
        'last_played_at',
        'timezone',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'best_streak' => 'integer',
        'freeze_shields' => 'integer',
        'last_played_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}