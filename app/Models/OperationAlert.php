<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OperationAlertNotification;

class OperationAlert extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const RESOLUTION_MANUAL = 'manual';

    public const RESOLUTION_AUTOMATIC = 'automatic';

    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'source',
        'status',
        'data',
        'first_detected_at',
        'last_detected_at',
        'acknowledged_at',
        'acknowledged_by',
        'resolved_at',
        'resolved_by',
        'resolution_type',
        'resolved_reason',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'acknowledged_by'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'resolved_by'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_ACKNOWLEDGED,
        ]);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_RESOLVED
        );
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where(
            'severity',
            self::SEVERITY_CRITICAL
        );
    }

    public function scopeWarning(Builder $query): Builder
    {
        return $query->where(
            'severity',
            self::SEVERITY_WARNING
        );
    }

    public function isActive(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_ACTIVE,
                self::STATUS_ACKNOWLEDGED,
            ],
            true
        );
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function wasManuallyResolved(): bool
    {
        return $this->resolution_type === self::RESOLUTION_MANUAL;
    }

    public function wasAutomaticallyResolved(): bool
    {
        return $this->resolution_type === self::RESOLUTION_AUTOMATIC;
    }

public function notifications(): HasMany
{
    return $this->hasMany(
        OperationAlertNotification::class,
        'alert_id'
    );
}
}