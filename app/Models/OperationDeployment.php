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
        'type',
        'rollback_of',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_seconds' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Admin user who triggered the deployment.
     */
    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'triggered_by'
        );
    }

    /**
     * Determine whether this deployment completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Determine whether this deployment can be rolled back to.
     */
    public function canRollback(): bool
    {
        return $this->isSuccessful()
            && !empty($this->commit_hash);
    }

    /**
     * Determine whether this deployment is a rollback.
     */
    public function isRollback(): bool
    {
        return data_get(
            $this->metadata,
            'rollback.is_rollback',
            false
        ) === true;
    }

    /**
     * Get rollback metadata.
     */
    public function rollbackMetadata(): array
    {
        return data_get(
            $this->metadata,
            'rollback',
            []
        );
    }

    public function rollbackOf()
{
    return $this->belongsTo(
        OperationDeployment::class,
        'rollback_of'
    );
}

public function rollbacks()
{
    return $this->hasMany(
        OperationDeployment::class,
        'rollback_of'
    );
}

}