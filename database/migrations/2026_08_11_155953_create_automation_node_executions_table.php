<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_node_executions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('execution_id')
                ->constrained('automation_executions')
                ->cascadeOnDelete();

            $table->foreignId('node_id')
                ->constrained('automation_nodes')
                ->cascadeOnDelete();

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
                'skipped',
            ])->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Input / Output
            |--------------------------------------------------------------------------
            */

            $table->json('input')->nullable();

            $table->json('output')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Error
            |--------------------------------------------------------------------------
            */

            $table->text('error_message')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timing
            |--------------------------------------------------------------------------
            */

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('duration_ms')->nullable();

            $table->timestamps();

            $table->index([
                'execution_id',
                'node_id',
            ]);

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_node_executions');
    }
};