<?php

namespace App\AI\Tools;

use App\Models\User;

class PlayerInsightTool
{


    public function execute(): array
    {


        $totalPlayers = User::count();


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



        $active = User::where(
            'last_played_date',
            '>=',
            now()->subDays(7)
        )->count();



        $averageXP =
        round(
            User::avg('total_xp'),
            2
        );



        $averageAnswers =
        round(
            User::avg('total_answers_count'),
            2
        );



        $insights=[];



        /*
        |--------------------------------------------------------------------------
        | Retention Analysis
        |--------------------------------------------------------------------------
        */


        if($totalPlayers > 0){

            $neverPlayedPercentage =
            round(
                ($neverPlayed/$totalPlayers)*100
            );


            if($neverPlayedPercentage > 40){

                $insights[]=[

                    "type"=>"warning",

                    "title"=>"Activation Problem",

                    "message"=>
                    "$neverPlayedPercentage% of users never started playing.",


                    "action"=>
                    "Improve onboarding and create a first-game reward."

                ];

            }

        }



        /*
        |--------------------------------------------------------------------------
        | Inactive Users
        |--------------------------------------------------------------------------
        */


        if($inactive > 0){


            $insights[]=[

                "type"=>"warning",

                "title"=>"Retention Risk",

                "message"=>
                "$inactive players have been inactive for more than 14 days.",


                "action"=>
                "Create comeback challenges or notifications."

            ];

        }



        /*
        |--------------------------------------------------------------------------
        | Engagement
        |--------------------------------------------------------------------------
        */


        if($averageAnswers > 100){


            $insights[]=[

                "type"=>"success",

                "title"=>"Strong Engagement",

                "message"=>
                "Players answered an average of $averageAnswers questions.",


                "action"=>
                "Focus on retention and social features."

            ];

        }



        return [

            "summary"=>[

                "players"=>$totalPlayers,

                "active"=>$active,

                "inactive"=>$inactive,

                "average_xp"=>$averageXP,

                "average_answers"=>$averageAnswers

            ],


            "insights"=>$insights

        ];


    }


}