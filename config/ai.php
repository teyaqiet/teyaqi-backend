<?php

return [

    'default' => env('AI_PROVIDER', 'gemini'),


    'providers' => [


        'gemini' => [

            'key' => env('GEMINI_API_KEY'),

            'model' => env(
                'GEMINI_MODEL',
                'gemini-2.5-flash'
            ),

        ],


        'openai' => [

            'key' => env('OPENAI_API_KEY'),

            'model' => env(
                'OPENAI_MODEL',
                'gpt-4.1-mini'
            ),

        ],

        'groq' => [

            'key' => env('GROQ_API_KEY'),

            'model' => env(
                'GROQ_MODEL',
                'llama-3.3-70b-versatile'
            ),

        ],


    ],


];