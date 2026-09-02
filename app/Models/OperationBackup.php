<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationBackup extends Model
{
    protected $fillable = [
        'type',
        'disk',
        'path',
        'filename',
        'size',
        'checksum',
        'status',
        'error',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'completed_at' => 'datetime',
    ];
}