<?php

namespace App\Models;

use App\Models\AutomationNode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Automation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'version',
        'settings',
        'total_runs',
        'successful_runs',
        'failed_runs',
        'last_run_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function nodes(): HasMany
    {
        return $this->hasMany(AutomationNode::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(AutomationConnection::class);
    }

 

    public function executions(): HasMany
{
    return $this->hasMany(
        AutomationExecution::class,
        'automation_id'
    );
}
}