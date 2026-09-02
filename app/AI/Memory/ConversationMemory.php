<?php

namespace App\AI\Memory;


use App\Models\AIConversation;


class ConversationMemory
{


    protected ?AIConversation $conversation = null;




    /*
    |--------------------------------------------------------------------------
    | Load conversation session
    |--------------------------------------------------------------------------
    */

    public function load(string $session)
    {


        $this->conversation =
            AIConversation::firstOrCreate(

                [
                    'session_id'=>$session
                ],

                [
                    'context'=>[]
                ]

            );


    }






    /*
    |--------------------------------------------------------------------------
    | Store memory value
    |--------------------------------------------------------------------------
    */

    public function remember(
        string $key,
        mixed $value
    )
    {


        $this->ensureLoaded();



        $context =
        $this->conversation->context ?? [];



        $context[$key]=$value;



        $this->conversation->update([

            'context'=>$context

        ]);


    }







    /*
    |--------------------------------------------------------------------------
    | Retrieve memory
    |--------------------------------------------------------------------------
    */

    public function get(
        string $key,
        mixed $default=null
    )
    {


        $this->ensureLoaded();



        $context =
        $this->conversation->context ?? [];



        return $context[$key] ?? $default;


    }








    /*
    |--------------------------------------------------------------------------
    | Get all memory
    |--------------------------------------------------------------------------
    */

    public function all(): array
    {


        $this->ensureLoaded();



        return
        $this->conversation->context ?? [];


    }








    /*
    |--------------------------------------------------------------------------
    | Save chat messages
    |--------------------------------------------------------------------------
    */

    public function addMessage(
        string $role,
        string $message
    )
    {


        $this->ensureLoaded();



        $this->conversation
        ->messages()
        ->create([

            'role'=>$role,

            'message'=>$message

        ]);


    }








    /*
    |--------------------------------------------------------------------------
    | Get previous messages
    |--------------------------------------------------------------------------
    */

    public function messages()
    {


        $this->ensureLoaded();



        return $this->conversation
            ->messages()
            ->latest()
            ->limit(20)
            ->get();

    }








    /*
    |--------------------------------------------------------------------------
    | Safety
    |--------------------------------------------------------------------------
    */

    private function ensureLoaded()
    {


        if(!$this->conversation)
        {

            throw new \Exception(
                "Conversation memory not loaded."
            );

        }


    }


}