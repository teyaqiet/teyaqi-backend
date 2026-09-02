<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApiLog extends Model
{
    protected $fillable = [
        'causer_type',
        'causer_id',
        'method',
        'url',
        'ip_address',
        'status_code',
        'request_headers',
        'request_payload',
        'response_body',
        'response_time',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_payload' => 'array',
        'response_body' => 'array',
    ];

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}