<?php

namespace App\AI\Providers;


use App\AI\Contracts\LLMProvider;
use Illuminate\Support\Facades\Http;


class GeminiProvider implements LLMProvider
{


    public function chat(array $messages): array
    {


        $apiKey = config('ai.providers.gemini.key');

        $model = config('ai.providers.gemini.model');



        $response = Http::post(

            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",

            [

                'contents' => [

                    [

                        'role' => 'user',

                        'parts' => [

                            [

                                'text' => $this->convertMessages($messages)

                            ]

                        ]

                    ]

                ]

            ]

        );



        // Get Gemini response

        $data = $response->json();



        // Debug if Gemini fails

        if (!$response->successful()) {

    return [

        'text' => json_encode(
            $data,
            JSON_PRETTY_PRINT
        )

    ];

}



        return [

            'text' => 
                $data['candidates'][0]['content']['parts'][0]['text']
                ?? 'No response from Gemini'

        ];


    }





    private function convertMessages($messages)
    {

        $text = '';



        foreach ($messages as $message) {


            $text .= 
                $message['role']
                . ": "
                . $message['content']
                . "\n";


        }


        return $text;


    }


}