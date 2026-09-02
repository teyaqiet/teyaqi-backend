<?php

namespace App\AI\Contracts;


interface LLMProvider
{

    public function chat(array $messages): array;

}