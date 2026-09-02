<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
    'name',
    'slug',
    'description',
    'icon',
    'color',
    'image_url',
    'sort_order',
    'is_active',
];


protected $casts = [
    'name' => 'array',
    'description' => 'array',
    'is_active' => 'boolean',
];

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function userRatings(): HasMany
    {
        return $this->hasMany(UserCategoryRating::class);
    }


}