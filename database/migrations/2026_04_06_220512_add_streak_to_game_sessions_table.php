<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // current_streak: Tracks the active 'Heat' for bonuses
            $table->integer('current_streak')->default(0)->after('lives_lost');
            
            // max_streak: Useful for the 'How far can you go' stat on the result page
            $table->integer('max_streak')->default(0)->after('current_streak');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['current_streak', 'max_streak']);
        });
    }
};