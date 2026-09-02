<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The Skill Rating (SR) that persists across sessions
            $table->integer('current_sr')->default(50)->after('total_xp');
            
            // Tracks the player's peak performance for leaderboards/badges
            $table->integer('best_sr')->default(50)->after('current_sr');
            
            // Total volume of answers to help calibrate the system
            $table->integer('total_answers_count')->default(0)->after('best_sr');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['current_sr', 'best_sr', 'total_answers_count']);
        });
    }
};