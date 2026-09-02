<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Adds granular 0-100 score for the engine to pick from
            $table->integer('difficulty_score')->default(50)->after('difficulty');
            
            // Analytics columns to see if questions are too hard/easy in reality
            $table->integer('times_shown')->default(0)->after('difficulty_score');
            $table->integer('times_correct')->default(0)->after('times_shown');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['difficulty_score', 'times_shown', 'times_correct']);
        });
    }
};