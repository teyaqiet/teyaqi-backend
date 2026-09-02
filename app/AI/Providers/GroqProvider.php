<?php

namespace App\AI\Providers;


use App\AI\Contracts\LLMProvider;
use Illuminate\Support\Facades\Http;


class GroqProvider implements LLMProvider
{


    public function chat(array $messages): array
    {


        $apiKey = config(
            'ai.providers.groq.key'
        );


        $model = config(
            'ai.providers.groq.model'
        );



        $response = Http::withHeaders([

            'Authorization' =>
                'Bearer '.$apiKey,

            'Content-Type' =>
                'application/json',

        ])
        ->post(

            'https://api.groq.com/openai/v1/chat/completions',

            [

                'model'=>$model,


                'messages'=>$messages,


                'temperature'=>0.7,


            ]

        );



        $data = $response->json();



        if(!$response->successful()){


            return [

                'text'=>
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                )

            ];

        }



        return [

            'text'=>
            $data['choices'][0]['message']['content']
            ??
            'No response from Groq'

        ];


    }


}