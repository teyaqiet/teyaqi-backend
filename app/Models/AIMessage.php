<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;


class AIMessage extends Model
{


    protected $fillable=[
        'role',
        'message'
    ];



    public function conversation()
    {

        return $this->belongsTo(
            AIConversation::class
        );

    }


}