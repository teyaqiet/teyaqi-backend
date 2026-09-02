<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Try to get user from standard session or API guard
        $user = Auth::user() ?? Auth::guard('api')->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'all_time' => $this->buildLeaderboard($user, 'total_xp'),
                'weekly'   => $this->buildLeaderboard($user, 'weekly_xp'),
                'streak'   => $this->buildLeaderboard($user, 'best_streak'),
            ]
        ]);
    }

    private function buildLeaderboard($user, $column)
{
    // Unique cache key per category (all_time, weekly, etc.)
    $cacheKey = "leaderboard_{$column}";

    // Cache the entire leaderboard data for 5 minutes
    $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($column) {
        $query = User::where($column, '>', 0);
        $totalCount = $query->count();
        
        $leaders = $query->select('id', 'name', 'total_xp', 'weekly_xp', 'best_streak')
            ->orderByDesc($column)
            ->get();

        return [
            'leaders' => $leaders,
            'total_count' => $totalCount,
        ];
    });

    $leaders = $data['leaders'];
    $totalCount = $data['total_count'];

    // Post-processing (User Rank & Top 3) stays outside cache to stay dynamic per user
    $currentIndex = $user ? $leaders->search(fn($u) => (string)$u->id === (string)$user->id) : false;
    $topThree = $leaders->take(3)->values()->pad(3, null);

    $currentUserData = null;
    if ($currentIndex !== false) {
        $me = $leaders[$currentIndex];
        $above = $currentIndex > 0 ? $leaders[$currentIndex - 1] : null;

        $currentUserData = [
            'id' => (string)$me->id,
            'name' => $me->name,
            'rank' => $currentIndex + 1,
            'xp_gap' => $above ? ($above->$column - $me->$column) : 0
        ];
    }

    return [
        'leaders' => $leaders,
        'top_three' => $topThree,
        'current_user' => $currentUserData,
        'total_count' => $totalCount
    ];
}


}