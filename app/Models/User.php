<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable implements FilamentUser
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_id',
        'username',
        'total_xp',
        'current_streak',
        'last_reward_at',
        'best_streak',      
        'last_played_date',
        'daily_lives',
        'lives_updated_at',
        'total_wins',
        'current_sr',
        'best_sr',    
        'language', 
        // ⚡ CRITICAL ADDITIONS: Allow mass-assignment during onboarding sync
        'avatar',
        'gender',
        'has_onboarded',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['level_data'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_reward_at' => 'datetime',
            'lives_updated_at' => 'datetime',
            'last_played_date' => 'date',
            'has_onboarded' => 'boolean', // Ensure strict data type emission
        ];
    }

    public $friendship_id;

    /**
     * Automatically hash the password when it's set, safely ignoring existing hashes.
     */
    public function setPasswordAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /**
     * ⚡ ADDED RELATIONSHIP: Maps users to selected trivia interest domains
     */
    /**
     * The categories that belong to the user.
     */
    public function categories(): BelongsToMany
    {
        // Passing 'user_categories' explicitly overrides Laravel's alphabetical default
        return $this->belongsToMany(Category::class, 'user_categories', 'user_id', 'category_id')
                    ->withTimestamps();
    }

    public function categoryRatings(): HasMany
    {
        return $this->hasMany(UserCategoryRating::class);
    }

public function getLevelDataAttribute(): array
    {
        $xp = $this->total_xp ?? 0; 

        try {
            $current = $this->getCurrentLevel($xp);
            $next = $this->getNextLevel($xp);

            return [
                'level'         => $current ? ($current->level_number ?? 1) : 1,
                'title'         => $current ? ($current->title ?? 'Anbesa (አንበሳ)') : 'Anbesa (አንበሳ)',
                'next_level_xp' => $next ? ($next->min_xp ?? ($xp + 1000)) : ($xp + 1000),
                'color'         => $current ? ($current->hex_color ?? '#3b82f6') : '#3b82f6',
                'current_xp'    => $xp,
            ];
        } catch (\Exception $e) {
            // Safe fallback return layout if the levels table doesn't exist yet
            return [
                'level'         => 1,
                'title'         => 'Anbesa (አንበሳ)',
                'next_level_xp' => $xp + 1000,
                'color'         => '#3b82f6',
                'current_xp'    => $xp,
            ];
        }
    }

    public function getCurrentLevel($xp = 0)
    {
        if (!class_exists('\App\Models\Level')) return null;
        
        return \App\Models\Level::where('min_xp', '<=', $xp)
            ->orderBy('min_xp', 'desc')
            ->first();
    }

    public function getNextLevel($xp = 0)
    {
        if (!class_exists('\App\Models\Level')) return null;

        return \App\Models\Level::where('min_xp', '>', $xp)
            ->orderBy('min_xp', 'asc')
            ->first();
    }

 

    public function sentRequests()
    {
        return $this->hasMany(Friend::class, 'user_id');
    }

    public function receivedRequests()
    {
        return $this->hasMany(Friend::class, 'friend_id');
    }

    public function getFriendsAttribute()
    {
        $sent = $this->sentRequests()->where('status', 'accepted')->with('receiver')->get()->pluck('receiver');
        $received = $this->receivedRequests()->where('status', 'accepted')->with('sender')->get()->pluck('sender');

        return $sent->merge($received);
    }

    public function streak()
    {
        return $this->hasOne(UserStreak::class);
    }

    public function streakHistories()
    {
        return $this->hasMany(StreakHistory::class);
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; 
    }

    public function challenges()
    {
        return $this->hasMany(\App\Models\Challenge::class);
    }

    public function dailyChallenges()
    {
        return $this->hasMany(DailyChallenge::class, 'user_id');
    }

    public function gameSessions(): HasMany
{
    return $this->hasMany(GameSession::class);
}

public function quizResponses(): HasMany
{
    return $this->hasMany(QuizResponse::class);
}

/**
 * Broadcast deliveries for this user.
 */
public function broadcastRecipients(): HasMany
{
    return $this->hasMany(BroadcastRecipient::class);
}

}