<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('operation_alert_rules', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Rule Identity
            |--------------------------------------------------------------------------
            */

            $table->string('name');

            $table->string('type', 100);


            /*
            |--------------------------------------------------------------------------
            | Rule Configuration
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | {
            |     "threshold": 10
            | }
            |
            */

            $table->json('configuration')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Severity
            |--------------------------------------------------------------------------
            */

            $table->string('severity', 20)
                ->default('warning');


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('enabled')
                ->default(true);


            /*
            |--------------------------------------------------------------------------
            | Cooldown
            |--------------------------------------------------------------------------
            |
            | Prevents the same alert from being generated repeatedly.
            |
            */

            $table->unsignedInteger('cooldown_minutes')
                ->default(60);


            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('type');

            $table->index('enabled');

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_alert_rules');
    }
};
