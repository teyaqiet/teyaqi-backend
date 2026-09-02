<?php

use App\Services\SettingService;


function settings(
    string $key,
    mixed $default=null
){

    return app(
        SettingService::class
    )->get(
        $key,
        $default
    );

}