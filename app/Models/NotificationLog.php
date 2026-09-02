<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = ['user_id', 'message', 'type', 'is_sent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}