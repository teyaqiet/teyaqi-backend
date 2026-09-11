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
        Schema::create('operation_alerts', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Alert Identity
            |--------------------------------------------------------------------------
            */

            $table->string('type', 100);

            $table->string('severity', 20)
                ->default('warning');

            $table->string('title');

            $table->text('message');


            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            |
            | Examples:
            | queue
            | backup
            | deployment
            | database
            | health
            | application
            |
            */

            $table->string('source', 100);


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | active
            | acknowledged
            | resolved
            |
            */

            $table->string('status', 30)
                ->default('active');


            /*
            |--------------------------------------------------------------------------
            | Alert Data
            |--------------------------------------------------------------------------
            |
            | Stores additional contextual information.
            |
            */

            $table->json('data')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Detection
            |--------------------------------------------------------------------------
            */

            $table->timestamp('first_detected_at')
                ->nullable();

            $table->timestamp('last_detected_at')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Acknowledgement
            |--------------------------------------------------------------------------
            */

            $table->timestamp('acknowledged_at')
                ->nullable();

            $table->unsignedBigInteger('acknowledged_by')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Resolution
            |--------------------------------------------------------------------------
            */

            $table->timestamp('resolved_at')
                ->nullable();

            $table->unsignedBigInteger('resolved_by')
                ->nullable();


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

            $table->index('severity');

            $table->index('source');

            $table->index('status');

            $table->index('last_detected_at');

            $table->index([
                'type',
                'status',
            ]);

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_alerts');
    }
};