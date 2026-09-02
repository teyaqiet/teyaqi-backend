<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_type',
        'threshold',
        'message_template',
        'button_text',
        'button_url',
        'is_active',
    ];

    /**
     * Optional: Cast is_active to boolean
     */
    protected $casts = [
        'is_active' => 'boolean',
        'threshold' => 'integer',
    ];
}