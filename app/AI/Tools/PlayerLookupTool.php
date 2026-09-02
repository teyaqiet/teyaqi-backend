<?php

namespace App\AI\Tools;

use App\Models\User;

class PlayerLookupTool
{


    public function execute(string $query): array
    {


        $query = trim($query);


$player = User::where(function($q) use ($query){

    $q->where('name','LIKE',"%{$query}%")
      ->orWhere('username','LIKE',"%{$query}%");

})
->first();



        if(!$player){

            return [

                "found"=>false,

                "message"=>"Player not found."

            ];

        }



        return [

            "found"=>true,


            "player"=>[

                "id"=>$player->id,

                "name"=>$player->name,

                "username"=>$player->username,


                "xp"=>$player->total_xp,


                "level"=>$this->getLevel(
                    $player->total_xp
                ),


                "current_sr"=>$player->current_sr,

                "best_sr"=>$player->best_sr,


                "current_streak"=>$player->current_streak,

                "best_streak"=>$player->best_streak,


                "total_answers"=>$player->total_answers_count,


                "total_wins"=>$player->total_wins,


                "joined"=>$player->created_at
                    ->format('Y-m-d'),


                "last_played"=>$player->last_played_date,

            ],



            "analysis"=>$this->analyze($player)

        ];


    }




    private function getLevel($xp)
    {


        return match(true){

            $xp >= 50000 =>
            "Legend",


            $xp >= 25000 =>
            "Master",


            $xp >= 10000 =>
            "Expert",


            $xp >= 5000 =>
            "Knowledgeable",


            $xp >= 1000 =>
            "Learner",


            default =>
            "Beginner"

        };


    }




    private function analyze($player)
    {


        $result=[];



        if($player->current_streak >= 7){

            $result[] =
            "Strong daily habit. Player is highly engaged.";

        }



        if($player->total_answers_count > 500){

            $result[] =
            "High activity player.";

        }



        if($player->last_played_date){

            if(
                now()->diffInDays(
                    $player->last_played_date
                ) > 14
            ){

                $result[] =
                "Player may be at risk of leaving.";

            }

        }



        if(empty($result)){

            $result[] =
            "Normal player activity.";

        }



        return $result;


    }


}