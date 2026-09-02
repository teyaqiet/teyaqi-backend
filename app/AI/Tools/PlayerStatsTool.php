<?php

namespace App\AI\Tools;

use App\Models\User;
use Carbon\Carbon;

class PlayerStatsTool
{
    /**
     * Retrieve global metrics and high-level analytics.
     */
    public function getStats(): array
    {
        return [
            "overview" => [
                "total_players" => User::count(),

                "new_players_today" => User::whereDate(
                    'created_at',
                    Carbon::today()
                )->count(),

                "new_players_this_week" => User::where(
                    'created_at',
                    ">=",
                    Carbon::now()->subDays(7)
                )->count(),

                "new_players_this_month" => User::where(
                    'created_at',
                    ">=",
                    Carbon::now()->subDays(30)
                )->count(),
            ],

            "activity" => [
                "active_today" => User::whereDate(
                    'last_played_date',
                    Carbon::today()
                )->count(),

                "active_this_week" => User::where(
                    'last_played_date',
                    ">=",
                    Carbon::now()->subDays(7)
                )->count(),

                "never_played" => User::whereNull(
                    'last_played_date'
                )->count(),

                "inactive_players" => User::whereNotNull(
                    'last_played_date'
                )->where(
                    'last_played_date',
                    "<",
                    Carbon::now()->subDays(30)
                )->count(),
            ],

            "retention" => [
                "played_last_7_days" => User::where(
                    'last_played_date',
                    ">=",
                    Carbon::now()->subDays(7)
                )->count(),

                "played_last_30_days" => User::where(
                    'last_played_date',
                    ">=",
                    Carbon::now()->subDays(30)
                )->count(),

                "churn_risk_players" => User::whereNotNull(
                    'last_played_date'
                )->where(
                    'last_played_date',
                    "<",
                    Carbon::now()->subDays(14)
                )->count(),
            ],

            "engagement" => [
                "total_answers" => User::sum('total_answers_count'),

                "total_wins" => User::sum('total_wins'),

                "average_xp" => round(User::avg('total_xp') ?? 0, 2),

                "average_current_streak" => round(User::avg('current_streak') ?? 0, 2),

                "best_streak" => User::max('best_streak') ?? 0,
            ],

            "progression" => [
                "average_sr" => round(User::avg('current_sr') ?? 0, 2),

                "highest_sr" => User::max('best_sr') ?? 0,

                "onboarded_players" => User::where('has_onboarded', true)->count(),
            ],

            "top_players" => User::select(
                'name',
                'username',
                'total_xp',
                'current_streak',
                'best_streak',
                'current_sr'
            )->orderBy('total_xp', 'desc')->limit(5)->get()->toArray(),
        ];
    }

    /**
 * Retrieve paginated list of players.
 */
public function getPlayers(int $perPage = 15, int $page = 1, string $sortBy = 'name', string $sortOrder = 'asc'): array
{
    $perPage = min(max($perPage, 1), 100);
    $allowedSortFields = ['name', 'username', 'email', 'total_xp', 'level', 'current_sr', 'created_at', 'last_played_date'];
    
    $field = in_array($sortBy, $allowedSortFields) ? $sortBy : 'name';
    $direction = strtolower($sortOrder) === 'desc' ? 'desc' : 'asc';

    $paginator = User::select(
        'id',
        'name',
        'username',
        'email',
        'total_xp',
        'level',
        'current_sr',
        'current_streak',
        'created_at',
        'last_played_date'
    )
    ->orderBy($field, $direction)
    ->paginate($perPage, ['*'], 'page', $page);

    return [
        'players' => collect($paginator->items())->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name ?? 'Unknown',
                'username' => $user->username ?? 'N/A',
                'email' => $user->email ?? 'N/A',
                'total_xp' => $user->total_xp ?? 0,
                'level' => $user->level ?? 1,
                'current_sr' => $user->current_sr ?? 0,
                'created_at' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                'last_played_date' => $user->last_played_date,
            ];
        })->toArray(),
        'pagination' => [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total_players' => $paginator->total(),
            'total_pages' => $paginator->lastPage(),
            'has_more' => $paginator->hasMorePages(),
        ],
    ];
}

    public function description(): string
    {
        return "Teyaqi Player Tool. Provides global analytics via getStats() and paginated player directory via getPlayers().";
    }

    public function execute($input = null): array
{
    return $this->getStats();
}

}