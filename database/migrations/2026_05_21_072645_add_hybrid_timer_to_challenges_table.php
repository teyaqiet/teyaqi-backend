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
        Schema::table('challenges', function (Blueprint $table) {
            // Define the strategy type: either a global clock or per-question clock
            $table->string('time_mode')->default('per_session')->after('type'); 
            
            // The duration setting in seconds (e.g., 30 for per-question, 300 for per-session)
            $table->unsignedInteger('time_limit')->default(60)->after('time_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn(['time_mode', 'time_limit']);
        });
    }
};