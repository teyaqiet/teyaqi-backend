<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, ?array $properties = null): void
    {
        ActivityLog::create([
            'causer_type' => auth('admin')->check() ? get_class(auth('admin')->user()) : null,
            'causer_id' => auth('admin')->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}