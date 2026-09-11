<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OperationAlertRule extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'type',
        'configuration',
        'severity',
        'enabled',
        'cooldown_minutes',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'configuration' => 'array',

            'enabled' => 'boolean',

            'cooldown_minutes' => 'integer',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where(
            'enabled',
            true
        );
    }


    public function scopeForType(
        Builder $query,
        string $type
    ): Builder {
        return $query->where(
            'type',
            $type
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }


    public function getConfiguration(
        string $key,
        mixed $default = null
    ): mixed {
        return data_get(
            $this->configuration ?? [],
            $key,
            $default
        );
    }
}