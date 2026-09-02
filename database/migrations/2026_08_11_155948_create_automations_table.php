<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Workflow Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'draft',
                'active',
                'paused',
                'archived',
            ])->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Workflow Version
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('version')->default(1);

            /*
            |--------------------------------------------------------------------------
            | Workflow Configuration
            |--------------------------------------------------------------------------
            */

            $table->json('settings')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Statistics
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('total_runs')->default(0);
            $table->unsignedBigInteger('successful_runs')->default(0);
            $table->unsignedBigInteger('failed_runs')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Last Execution
            |--------------------------------------------------------------------------
            */

            $table->timestamp('last_run_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('last_run_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};