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
        Schema::create('quiz_responses', function (Blueprint $table) {
            $table->id();
            
            // Link to the user
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Link to the game session
            $table->foreignId('session_id')
                  ->constrained('game_sessions')
                  ->onDelete('cascade');

            // Link to the specific question
            $table->foreignId('question_id')
                  ->constrained()
                  ->onDelete('cascade');

            // Data tracking
            $table->string('selected_option', 1); // 'a', 'b', 'c', or 'd'
            $table->boolean('is_correct')->default(false);
            
            // Performance tracking
            // We use integer to store milliseconds (ms) for high precision
            $table->integer('time_taken_ms')->nullable(); 

            $table->timestamps();

            // Indexing for faster "Seen Questions" queries
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_responses');
    }
};