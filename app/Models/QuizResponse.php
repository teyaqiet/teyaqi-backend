<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'question_id',
        'selected_option',
        'is_correct',
        'time_taken_ms', // 👈 This allows the time to be saved
    ];

    // Optional: Relationships to make your life easier later
    public function user() {
        return $this->belongsTo(User::class);
    }

     public function question()
    {
        return $this->belongsTo(
            Question::class,
            'question_id'
        );
    }



    public function session()
    {
        return $this->belongsTo(
            GameSession::class,
            'session_id'
        );
    }



   
}