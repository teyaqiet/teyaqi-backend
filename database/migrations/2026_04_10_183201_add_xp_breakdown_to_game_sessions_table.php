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
    Schema::table('game_sessions', function (Blueprint $table) {
        $table->integer('base_xp')->default(0)->after('xp_earned');
        $table->integer('bonus_xp')->default(0)->after('base_xp');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            //
        });
    }
};
