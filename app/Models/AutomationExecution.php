<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class AutomationExecution extends Model
{
    protected $fillable = [
        'automation_id',
        'execution_id',
        'trigger_type',
        'trigger_data',
        'status',
        'context',
        'result',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
        'waiting_node_id',
        'resume_at',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'context' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'resume_at' => 'datetime',
    ];

    public function automation(): BelongsTo
{
    return $this->belongsTo(
        Automation::class,
        'automation_id'
    );
}

public function nodeExecutions(): HasMany
{
    return $this->hasMany(
        AutomationNodeExecution::class,
        'execution_id'
    );
}

public function waitingNode(): BelongsTo
{
    return $this->belongsTo(
        AutomationNode::class,
        'waiting_node_id'
    );
}


}