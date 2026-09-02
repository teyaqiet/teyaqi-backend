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
        // Fixed: Changed Route::table to Schema::table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('total_coins')->default(0)->after('total_xp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Clean Rollback parameter strategy
            $table->dropColumn('total_coins');
        });
    }
};