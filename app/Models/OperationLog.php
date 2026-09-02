<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationLog extends Model
{
    protected $fillable = [
        'admin_user_id',
        'action',
        'module',
        'environment',
        'status',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(
            AdminUser::class,
            'admin_user_id'
        );
    }
}