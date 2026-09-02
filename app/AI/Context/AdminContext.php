<?php

namespace App\AI\Context;
use App\AI\Memory\ConversationMemory;


class AdminContext
{

    public function __construct(
        protected ConversationMemory $memory
    )
    {}



    public function memory()
    {
        return $this->memory;
    }



    public function setLastIntent($intent)
    {

        $this->memory->remember(
            'last_intent',
            $intent
        );

    }




    public function getLastIntent()
    {

        return $this->memory->get(
            'last_intent'
        );

    }




    public function setPlayer(array $player)
    {

        $this->memory->remember(
            'current_player',
            $player
        );

    }



    public function getPlayer()
    {

        return $this->memory->get(
            'current_player'
        );

    }

}