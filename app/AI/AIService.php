<?php

namespace App\AI;

class AIService
{

    protected $manager;


    public function __construct(AIManager $manager)
    {
        $this->manager = $manager;
    }



    public function chat(array $messages)
    {

        $provider = $this->manager->provider();


        return $provider->chat($messages);

    }

}