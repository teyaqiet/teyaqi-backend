<?php

namespace App\AI\Router;


class IntentRouter
{


    public function detect(string $message): string
    {

        $text = strtolower($message);



        /*
        |--------------------------------------------------------------------------
        | PLAYER INTENTS
        |--------------------------------------------------------------------------
        */


        if(
            str_contains($text,'compare')
            ||
            str_contains($text,' vs ')
        ){

            return 'player_compare';

        }



        if(
            str_contains($text,'list')
            ||
            str_contains($text,'all players')
            ||
            str_contains($text,'player report')
        ){

            return 'player_report';

        }



        if(
            str_contains($text,'health')
            ||
            str_contains($text,'error')
        ){

            return 'system_health';

        }



        if(
                str_contains($text,'about')
                ||
                str_contains($text,'who is')
                ||
                str_contains($text,'profile')
                ||
                str_contains($text,'stats')
                ||
                str_contains($text,'show me')
                ||
                str_contains($text,'tell me')
            )
            {

                return 'player_lookup';

            }





        /*
        |--------------------------------------------------------------------------
        | KNOWLEDGE
        |--------------------------------------------------------------------------
        */


        if(
            str_contains($text,'how')
            ||
            str_contains($text,'what')
            ||
            str_contains($text,'explain')
        ){

            return 'knowledge';

        }





        return 'unknown';

    }


}