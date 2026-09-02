<?php

namespace App\AI;


use App\AI\Providers\OpenAIProvider;
use App\AI\Providers\GeminiProvider;
use App\AI\Providers\GroqProvider;
use App\AI\Tools\PlayerHealthTool;
use App\AI\Tools\PlayerInsightTool;
use App\AI\Tools\PlayerLookupTool;
use App\AI\Tools\PlayerStatsTool;



class AIManager
{


    public function provider()
    {


        $provider = config('ai.default');



        return match($provider)
        {


            'gemini' => new GeminiProvider(),


            'openai' => new OpenAIProvider(),

            'groq' => new GroqProvider(),



            default => throw new \Exception(

                "AI provider [{$provider}] not supported"

            ),


        };


    }
    public function tools()
    {

        return [

                "player_health"
                =>
                new PlayerHealthTool(),

                "player_insights"
                =>
                new PlayerInsightTool(),

                "player_lookup" =>
                new PlayerLookupTool(),

                "player_stats"
                => new PlayerStatsTool(),

                ];



    }
    


    }