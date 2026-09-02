<?php

namespace App\AI\Tools;

use App\Models\User;
use Carbon\Carbon;

class PlayerHealthTool
{

    public function execute(): array
    {

        $totalPlayers = User::count();


        $activeToday = User::whereDate(
            'last_played_date',
            today()
        )->count();


        $activeWeek = User::where(
            'last_played_date',
            '>=',
            now()->subDays(7)
        )->count();



        $neverPlayed = User::whereNull(
            'last_played_date'
        )->count();



        $inactive = User::where(
            'last_played_date',
            '<',
            now()->subDays(14)
        )
        ->whereNotNull('last_played_date')
        ->count();



        /*
        |--------------------------------------------------------------------------
        | Health Calculation
        |--------------------------------------------------------------------------
        */


        $activityScore = 0;


        if($totalPlayers > 0){

            $activityScore =
            ($activeWeek / $totalPlayers) * 40;

        }



        $retentionScore = 0;


        if($totalPlayers > 0){

            $retentionScore =
            (($totalPlayers-$inactive)
            /
            $totalPlayers)
            * 30;

        }



        $engagementScore =
        min(
            User::sum('total_answers_count') / 100,
            30
        );



        $health =
        round(
            $activityScore
            +
            $retentionScore
            +
            $engagementScore
        );



        return [

            "health_score"=>$health,


            "status"=>$this->status($health),


            "players"=>[

                "total"=>$totalPlayers,

                "active_today"=>$activeToday,

                "active_week"=>$activeWeek,

                "never_played"=>$neverPlayed,

                "inactive"=>$inactive,

            ],


            "recommendations"=>$this->recommendations(
                $neverPlayed,
                $inactive
            )

        ];


    }



    private function status($score)
    {

        if($score >=80)
            return "Excellent";


        if($score >=60)
            return "Healthy";


        if($score >=40)
            return "Needs Attention";


        return "Critical";

    }



    private function recommendations(
        $neverPlayed,
        $inactive
    )
    {

        $items=[];


        if($neverPlayed > 0){

            $items[] =
            "$neverPlayed players never started playing. Improve onboarding.";

        }



        if($inactive > 0){

            $items[] =
            "$inactive players are inactive. Create comeback challenges.";

        }



        return $items;

    }

}