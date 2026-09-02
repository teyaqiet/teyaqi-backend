<?php

namespace App\AI\Providers;


use App\AI\Contracts\LLMProvider;
use Illuminate\Support\Facades\Http;


class OpenAIProvider implements LLMProvider
{


public function chat(array $messages): array
{

$response = Http::withToken(
    config('ai.openai.key')
)
->post(
'https://api.openai.com/v1/chat/completions',
[
'model'=>'gpt-5-mini',
'messages'=>$messages
]
);


return $response->json();


}



}