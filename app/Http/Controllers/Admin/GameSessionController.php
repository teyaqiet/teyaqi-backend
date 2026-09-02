<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\Request;

class GameSessionController extends Controller
{
    /**
     * Display all game sessions
     */
    public function index(Request $request)
{
    $query = GameSession::with('user');


    // Search user
    if($request->filled('search')){

        $search = $request->search;

        $query->whereHas('user', function($q) use ($search){

            $q->where('name','like',"%{$search}%")
              ->orWhere('username','like',"%{$search}%")
              ->orWhere('telegram_id','like',"%{$search}%");

        });

    }



    // Date filter
    if($request->filled('from')){

        $query->whereDate(
            'created_at',
            '>=',
            $request->from
        );

    }


    if($request->filled('to')){

        $query->whereDate(
            'created_at',
            '<=',
            $request->to
        );

    }





    // Status filter
    if($request->filled('status')){


        if($request->status == 'finished'){

            $query->where('is_completed',true);

        }


        elseif($request->status == 'stopped'){

            $query->where('is_completed',false);

        }


    }





    $sessions = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();




    $totalSessions = GameSession::count();


    $completedSessions = GameSession::where(
        'is_completed',
        true
    )->count();


    $totalXp = GameSession::sum('xp_earned');



    $averageAccuracy = GameSession::selectRaw(
        'AVG(correct_answers / total_questions * 100) as avg'
    )
    ->value('avg');




    return view(
        'admin.game-sessions.index',
        compact(
            'sessions',
            'totalSessions',
            'completedSessions',
            'totalXp',
            'averageAccuracy'
        )
    );
}



    /**
     * Display single game session
     */
    public function show(GameSession $gameSession)
    {
        $gameSession->load([
            'user',
            'quizResponses' => function ($query) {
                $query->latest();
            },
            'quizResponses.question'
        ]);

        return view('admin.game-sessions.show', compact('gameSession'));
    }
    
}