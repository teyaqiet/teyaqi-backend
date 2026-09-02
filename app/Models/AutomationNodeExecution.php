<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationNodeExecution extends Model
{
    protected $fillable = [
        'execution_id',
        'node_id',
        'status',
        'input',
        'output',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function execution(): BelongsTo
{
    return $this->belongsTo(
        AutomationExecution::class,
        'execution_id'
    );
}

public function node(): BelongsTo
{
    return $this->belongsTo(
        AutomationNode::class,
        'node_id'
    );
}

}