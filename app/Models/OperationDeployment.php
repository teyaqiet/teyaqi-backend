<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationDeployment extends Model
{
    protected $fillable = [
        'environment',
        'branch',
        'commit_hash',
        'commit_message',
        'status',
        'triggered_by',
        'started_at',
        'completed_at',
        'duration_seconds',
        'output',
        'error',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'triggered_by'
        );
    }
}