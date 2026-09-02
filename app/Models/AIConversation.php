<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class AIConversation extends Model
{


    protected $fillable=[
        'session_id',
        'context'
    ];



    protected $casts=[
        'context'=>'array'
    ];



    public function messages()
    {

        return $this->hasMany(
            AIMessage::class
        );

    }


}