<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    protected $fillable = [
        'title',
        'message',
        'type',
        'channel',
        'status',
        'audience_type',
        'filters',
        'buttons',
        'media_type',
        'media_path',
        'media_url',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'buttons' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Admin who created the broadcast.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recipients of this broadcast.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(BroadcastRecipient::class);
    }
}