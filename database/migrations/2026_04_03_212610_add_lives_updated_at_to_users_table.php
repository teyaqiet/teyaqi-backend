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
        Schema::table('users', function (Blueprint $table) {
            // 1. Reset Timer Column
            if (!Schema::hasColumn('users', 'lives_updated_at')) {
                $table->timestamp('lives_updated_at')->nullable();
            }

            // 2. Daily Lives (Set default to 5)
            if (!Schema::hasColumn('users', 'daily_lives')) {
                $table->integer('daily_lives')->default(5);
            }

            // 3. Total Wins
            if (!Schema::hasColumn('users', 'total_wins')) {
                $table->integer('total_wins')->default(0);
            }
            
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['lives_updated_at', 'daily_lives', 'total_wins'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};