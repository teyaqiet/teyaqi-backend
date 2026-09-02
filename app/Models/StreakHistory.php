<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreakHistory extends Model
{
    protected $fillable = [
        'user_id',
        'activity_date',
        'streak_count',
        'status',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'streak_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}