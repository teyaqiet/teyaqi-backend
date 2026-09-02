<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationNode extends Model
{
    protected $fillable = [
        'automation_id',
        'node_id',
        'name',
        'type',
        'component',
        'config',
        'position_x',
        'position_y',
        'metadata',
        'enabled',
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'enabled' => 'boolean',
        'position_x' => 'float',
        'position_y' => 'float',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationNodeExecution::class, 'node_id');
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            AutomationConnection::class,
            'source_node_id'
        );
    }

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(
            AutomationConnection::class,
            'target_node_id'
        );
    }
}