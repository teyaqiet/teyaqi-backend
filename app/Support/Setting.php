<?php

namespace App\Support;

use App\Services\SettingService;

class Setting
{

    public static function get($key,$default=null)
    {
        return app(SettingService::class)
            ->get($key,$default);
    }


    public static function set($key,$value)
    {
        return app(SettingService::class)
            ->set($key,$value);
    }


    public static function boolean($key,$default=false)
    {
        return app(SettingService::class)
            ->boolean($key,$default);
    }


    public static function integer($key,$default=0)
    {
        return app(SettingService::class)
            ->integer($key,$default);
    }

}