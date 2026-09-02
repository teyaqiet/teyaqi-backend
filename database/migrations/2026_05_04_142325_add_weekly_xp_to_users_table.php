<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('weekly_xp')->default(0)->after('total_xp');
            $table->timestamp('weekly_reset_at')->nullable()->after('weekly_xp');

            $table->index('total_xp');
            $table->index('weekly_xp');
            $table->index('best_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['users_total_xp_index']);
            $table->dropIndex(['users_weekly_xp_index']);
            $table->dropIndex(['users_best_streak_index']);

            $table->dropColumn(['weekly_xp', 'weekly_reset_at']);
        });
    }
};