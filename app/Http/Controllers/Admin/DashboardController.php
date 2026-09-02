<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Question;
use App\Models\Category;
use App\Models\Challenge;
use App\Models\GameSession;
use App\Models\QuizResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::today()->subDays(6);

        /*
        |--------------------------------------------------------------------------
        | Main Statistics
        |--------------------------------------------------------------------------
        */

        $stats = [
            'users' => User::count(),

            'active_today' => User::whereDate(
                'last_played_date',
                $today
            )->count(),

            'questions' => Question::count(),

            'categories' => Category::count(),

            'challenges' => Challenge::count(),

            'games_today' => GameSession::whereDate(
                'created_at',
                $today
            )->count(),

            'answers_today' => QuizResponse::whereDate(
                'created_at',
                $today
            )->count(),

            'new_users_today' => User::whereDate(
                'created_at',
                $today
            )->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | Answer Performance
        |--------------------------------------------------------------------------
        */

        $answersToday = QuizResponse::whereDate(
            'created_at',
            $today
        );

        $totalAnswersToday = (clone $answersToday)->count();

        $correctAnswersToday = (clone $answersToday)
            ->where('is_correct', true)
            ->count();

        $stats['correct_answers_today'] = $correctAnswersToday;

        $stats['accuracy_today'] = $totalAnswersToday > 0
            ? round(($correctAnswersToday / $totalAnswersToday) * 100, 1)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Last 7 Days — Games
        |--------------------------------------------------------------------------
        */

        $gamesLast7Days = GameSession::query()
            ->whereBetween('created_at', [
                $sevenDaysAgo->startOfDay(),
                $today->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $gameChart = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $sevenDaysAgo->copy()->addDays($i);

            $gameChart[] = [
                'date' => $date->format('M d'),
                'value' => (int) ($gamesLast7Days[$date->format('Y-m-d')] ?? 0),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Last 7 Days — New Users
        |--------------------------------------------------------------------------
        */

        $usersLast7Days = User::query()
            ->whereBetween('created_at', [
                $sevenDaysAgo->startOfDay(),
                $today->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $userChart = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $sevenDaysAgo->copy()->addDays($i);

            $userChart[] = [
                'date' => $date->format('M d'),
                'value' => (int) ($usersLast7Days[$date->format('Y-m-d')] ?? 0),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Render Dashboard
        |--------------------------------------------------------------------------
        */

        return view('admin.dashboard', compact(
            'stats',
            'gameChart',
            'userChart'
        ));
    }
}