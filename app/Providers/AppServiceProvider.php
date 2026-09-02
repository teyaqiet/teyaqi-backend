<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

use App\AI\AIService;
use App\AI\AIManager;
use App\AI\Memory\ConversationMemory;
use App\AI\Context\AdminContext;
use App\Services\SettingService;

use App\View\Components\Admin\Settings\Field;


class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {


        /*
        |--------------------------------------------------------------------------
        | AI Service
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            AIService::class,
            function(){

                return new AIService(
                    new AIManager()
                );

            }
        );





        /*
        |--------------------------------------------------------------------------
        | AI Conversation Memory
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            ConversationMemory::class,
            function(){

                return new ConversationMemory();

            }
        );





        /*
        |--------------------------------------------------------------------------
        | Admin AI Context
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            AdminContext::class,
            function($app){

                return new AdminContext(
                    $app->make(
                        ConversationMemory::class
                    )
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Setting Service
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
        SettingService::class,
        function(){

            return new SettingService();

        }
    );



    }





    public function boot(): void
{

    Blade::anonymousComponentPath(
        resource_path('views/admin/components'),
        'admin'
    );


    Blade::component(
        'settings.field',
        Field::class
    );


        


    }


}