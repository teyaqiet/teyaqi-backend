<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Question;
use App\Models\Challenge;
use App\Models\ChallengeAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Parse date inputs from request or set defaults
        $fromDate = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $toDate   = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        $group    = $request->input('group', 'day');

        /*
        |--------------------------------------------------------------------------
        | KPI OVERVIEW METRICS
        |--------------------------------------------------------------------------
        */
        $totalUsers      = User::count();
        $activeToday     = User::where('updated_at', '>=', now()->subHours(24))->count();
        $totalQuestions  = Question::count();
        $totalChallenges = Challenge::count();

        /*
        |--------------------------------------------------------------------------
        | PLAYER STATS
        |--------------------------------------------------------------------------
        */
        $playerStats = User::selectRaw('
            AVG(total_xp) as average_xp,
            AVG(current_sr) as average_sr,
            MAX(best_sr) as max_best_sr,
            AVG(current_streak) as average_streak,
            MAX(best_streak) as max_best_streak,
            SUM(total_answers_count) as total_answers,
            SUM(total_wins) as total_wins
        ')->first();

        $newPlayers    = User::whereBetween('created_at', [$fromDate, $toDate])->count();
        $averageXp     = round($playerStats->average_xp ?? 0);
        $averageSr     = round($playerStats->average_sr ?? 0);
        $bestSr        = (int) ($playerStats->max_best_sr ?? 0);
        $averageStreak = round($playerStats->average_streak ?? 0, 1);
        $bestStreak    = (int) ($playerStats->max_best_streak ?? 0);
        $totalAnswers  = (int) ($playerStats->total_answers ?? 0);
        $totalWins     = (int) ($playerStats->total_wins ?? 0);

        /*
        |--------------------------------------------------------------------------
        | DYNAMIC PERIOD & USER GROWTH
        |--------------------------------------------------------------------------
        */
        $dateFormat = match ($group) {
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
            'year'  => '%Y',
            default => '%Y-%m-%d',
        };

        $rawUserGrowth = User::select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date_group"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('date_group')
            ->pluck('total', 'date_group');

        // Build periods based on grouping parameter
        $periodStep = match ($group) {
            'week'  => '1 week',
            'month' => '1 month',
            'year'  => '1 year',
            default => '1 day',
        };

        $period = CarbonPeriod::create($fromDate, $periodStep, $toDate);
        $userGrowthLabels = [];
        $userGrowthData   = [];

        foreach ($period as $date) {
            $groupKey = match ($group) {
                'week'  => $date->format('Y-W'),
                'month' => $date->format('Y-m'),
                'year'  => $date->format('Y'),
                default => $date->format('Y-m-d'),
            };

            $label = match ($group) {
                'week'  => 'Week ' . $date->format('W, Y'),
                'month' => $date->format('M Y'),
                'year'  => $date->format('Y'),
                default => $date->format('M d'),
            };

            $userGrowthLabels[] = $label;
            $userGrowthData[]   = $rawUserGrowth->get($groupKey, 0);
        }

        /*
        |--------------------------------------------------------------------------
        | QUESTION ACCURACY PERFORMANCE
        |--------------------------------------------------------------------------
        */
        $calcAccuracy = function (string $difficulty) {
            $result = Question::where('difficulty', $difficulty)
                ->selectRaw('SUM(times_correct) as correct, SUM(times_shown) as total_shown')
                ->first();

            if (!$result || !$result->total_shown || $result->total_shown == 0) {
                return 0;
            }

            return round(($result->correct / $result->total_shown) * 100, 1);
        };

        $questionAccuracy = [
            $calcAccuracy('easy'),
            $calcAccuracy('medium'),
            $calcAccuracy('hard'),
        ];

        /*
        |--------------------------------------------------------------------------
        | CHALLENGE ANALYTICS
        |--------------------------------------------------------------------------
        */
        $topChallenges = Challenge::withCount(['attempts' => function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }])
            ->orderByDesc('attempts_count')
            ->limit(8)
            ->get();

        $rawAttempts = ChallengeAttempt::select(
                DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date_group"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('date_group')
            ->pluck('total', 'date_group');

        $challengeAttemptLabels = [];
        $challengeAttemptData   = [];

        foreach ($period as $date) {
            $groupKey = match ($group) {
                'week'  => $date->format('Y-W'),
                'month' => $date->format('Y-m'),
                'year'  => $date->format('Y'),
                default => $date->format('Y-m-d'),
            };

            $label = match ($group) {
                'week'  => 'Week ' . $date->format('W, Y'),
                'month' => $date->format('M Y'),
                'year'  => $date->format('Y'),
                default => $date->format('M d'),
            };

            $challengeAttemptLabels[] = $label;
            $challengeAttemptData[]   = $rawAttempts->get($groupKey, 0);
        }

        /*
        |--------------------------------------------------------------------------
        | DISTRIBUTIONS
        |--------------------------------------------------------------------------
        */
        $xpDistribution = [
            'Beginner'     => User::where('total_xp', '<', 1000)->count(),
            'Intermediate' => User::whereBetween('total_xp', [1000, 5000])->count(),
            'Advanced'     => User::whereBetween('total_xp', [5001, 20000])->count(),
            'Master'       => User::where('total_xp', '>', 20000)->count(),
        ];

        $srDistribution = [
            'Bronze' => User::where('current_sr', '<', 1000)->count(),
            'Silver' => User::whereBetween('current_sr', [1000, 2000])->count(),
            'Gold'   => User::whereBetween('current_sr', [2001, 3000])->count(),
            'Elite'  => User::where('current_sr', '>', 3000)->count(),
        ];

        $streakDistribution = [
            'No Streak' => User::where('current_streak', 0)->count(),
            '1-7 Days'  => User::whereBetween('current_streak', [1, 7])->count(),
            '8-30 Days' => User::whereBetween('current_streak', [8, 30])->count(),
            '30+ Days'  => User::where('current_streak', '>', 30)->count(),
        ];

        // Calculate or retrieve selected days from request
$selectedDays = $request->input('days', 30);

// If using from/to date pickers, calculate the difference in days:
if ($request->filled('from') && $request->filled('to')) {
    $fromDate = Carbon::parse($request->input('from'));
    $toDate   = Carbon::parse($request->input('to'));
    $selectedDays = $fromDate->diffInDays($toDate);
}

return view('admin.analytics.index', [
    'selectedDays'           => $selectedDays, // <-- Add this line
    'totalUsers'             => $totalUsers,
    'activeToday'            => $activeToday,
    'totalQuestions'         => $totalQuestions,
    'totalChallenges'        => $totalChallenges,
    'newPlayers'             => $newPlayers,
    'averageXp'              => $averageXp,
    'averageSr'              => $averageSr,
    'bestSr'                 => $bestSr,
    'averageStreak'          => $averageStreak,
    'bestStreak'             => $bestStreak,
    'totalAnswers'           => $totalAnswers,
    'totalWins'              => $totalWins,
    'userGrowth'             => $userGrowthData,
    'userGrowthLabels'       => $userGrowthLabels,
    'questionAccuracy'       => $questionAccuracy,
    'topChallenges'          => $topChallenges,
    'challengeAttemptLabels' => $challengeAttemptLabels,
    'challengeAttemptData'   => $challengeAttemptData,
    'xpDistribution'         => $xpDistribution,
    'srDistribution'         => $srDistribution,
    'streakDistribution'     => $streakDistribution,
]);
    }
}