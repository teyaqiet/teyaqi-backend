<?php

namespace App\AI\Router;


class ToolRouter
{


    public function resolve(string $intent): ?string
    {


        return match($intent)
        {


            'player_lookup'
                =>
                'player_lookup',


            'player_report'
                =>
                'player_stats',



            'system_health'
                =>
                'player_health',



            default
                =>
                null,


        };


    }


}