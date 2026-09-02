<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('automation_id')
                ->constrained('automations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Execution Identity
            |--------------------------------------------------------------------------
            */

            $table->uuid('execution_id')->unique();

            /*
            |--------------------------------------------------------------------------
            | Trigger Information
            |--------------------------------------------------------------------------
            */

            $table->string('trigger_type')->nullable();

            $table->json('trigger_data')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Execution Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status', [
                'pending',
                'running',
                'completed',
                'failed',
                'cancelled',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Execution Data
            |--------------------------------------------------------------------------
            */

            $table->json('context')->nullable();

            $table->json('result')->nullable();

            $table->text('error_message')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timing
            |--------------------------------------------------------------------------
            */

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Performance
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index([
                'automation_id',
                'status',
            ]);

            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_executions');
    }
};