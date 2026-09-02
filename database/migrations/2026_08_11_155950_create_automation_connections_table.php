<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_connections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('automation_id')
                ->constrained('automations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Source
            |--------------------------------------------------------------------------
            */

            $table->foreignId('source_node_id')
                ->constrained('automation_nodes')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Destination
            |--------------------------------------------------------------------------
            */

            $table->foreignId('target_node_id')
                ->constrained('automation_nodes')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Ports
            |--------------------------------------------------------------------------
            |
            | Important for conditions.
            |
            | Example:
            |
            | true
            | false
            | success
            | failure
            |
            */

            $table->string('source_handle')->nullable();
            $table->string('target_handle')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Connection Metadata
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index([
                'automation_id',
                'source_node_id',
            ]);

            $table->index([
                'automation_id',
                'target_node_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_connections');
    }
};