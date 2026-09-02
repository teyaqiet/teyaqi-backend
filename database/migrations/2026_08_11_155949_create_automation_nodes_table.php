<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_nodes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('automation_id')
                ->constrained('automations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Node Identity
            |--------------------------------------------------------------------------
            */

            $table->string('node_id');

            $table->string('name');

            /*
            |--------------------------------------------------------------------------
            | Node Type
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | trigger
            | action
            | condition
            | delay
            | filter
            |
            */

            $table->string('type');

            /*
            |--------------------------------------------------------------------------
            | Specific Node Component
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | player_registered
            | send_telegram
            | add_xp
            | check_streak
            | delay
            |
            */

            $table->string('component');

            /*
            |--------------------------------------------------------------------------
            | Node Configuration
            |--------------------------------------------------------------------------
            |
            | Stores component-specific settings.
            |
            */

            $table->json('config')->nullable();

            /*
            |--------------------------------------------------------------------------
            | UI Position
            |--------------------------------------------------------------------------
            |
            | Used by the workflow editor.
            |
            */

            $table->decimal('position_x', 10, 2)->default(0);
            $table->decimal('position_y', 10, 2)->default(0);

            /*
            |--------------------------------------------------------------------------
            | UI Metadata
            |--------------------------------------------------------------------------
            */

            $table->json('metadata')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Execution
            |--------------------------------------------------------------------------
            */

            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->unique([
                'automation_id',
                'node_id',
            ]);

            $table->index([
                'automation_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_nodes');
    }
};