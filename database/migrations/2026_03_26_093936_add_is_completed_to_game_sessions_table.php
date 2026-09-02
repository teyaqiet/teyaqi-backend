<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            // Adding the columns your previous migration was missing
            $table->integer('lives_lost')->default(0)->after('xp_earned');
            $table->boolean('is_completed')->default(false)->after('lives_lost');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['lives_lost', 'is_completed']);
        });
    }
};