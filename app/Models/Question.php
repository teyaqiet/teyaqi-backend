<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 
        'question_text',
        'image_url', 
        'option_a', 
        'option_b', 
        'option_c', 
        'option_d', 
        'correct_answer', 
        'difficulty', 
        'difficulty_score',
        'explanation', 
        'is_active',
    ];

    protected $casts = [
        'question_text'    => 'array',
        'option_a'         => 'array',
        'option_b'         => 'array',
        'option_c'         => 'array',
        'option_d'         => 'array',
        'explanation'      => 'array', 
        'difficulty_score' => 'float',
        'times_shown'      => 'integer',
        'times_correct'    => 'integer',
        'is_active'        => 'boolean',
    ];

    /**
     * Helper to get translated text easily in your Bot / App
     * Usage: $question->translate('question_text', 'am')
     */
    public function translate(string $column, ?string $lang = null): string
    {
        $lang = $lang ?? app()->getLocale();
        $data = $this->{$column};

        if (!is_array($data)) {
            return (string) ($data ?? '');
        }

        return $data[$lang] ?? $data['en'] ?? '';
    }

    /**
     * Alias for Blade compatibility (replaces Spatie's method)
     * Usage in Blade: $question->getTranslation('question_text', 'en')
     */
    public function getTranslation(string $column, string $locale): string
    {
        return $this->translate($column, $locale);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Accessor for full image URL formatting
     */
    public function getImageUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (request()->is('admin*') || request()->is('filament*')) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $tunnel = 'https://subconjunctive-tabetha-lotic.ngrok-free.dev';
        return $tunnel . '/storage/' . ltrim($value, '/');
    }

    /**
     * Cleanup files upon deletion
     */
    protected static function booted()
    {
        static::deleting(function ($question) {
            $rawPath = $question->getRawOriginal('image_url');
            if ($rawPath && !filter_var($rawPath, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($rawPath);
            }
        });
    }

    /**
     * Virtual Accessor to bundle option columns into a single associative array.
     * Accessible via $question->options
     */
    public function getOptionsAttribute(): array
    {
        return array_filter([
            'a' => $this->option_a,
            'b' => $this->option_b,
            'c' => $this->option_c,
            'd' => $this->option_d,
        ]);
    }
}