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
    Schema::create('user_streaks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->integer('current_streak')->default(0);
        $table->integer('best_streak')->default(0);
        $table->integer('freeze_shields')->default(1); // Give them 1 for free!
        $table->timestamp('last_played_at')->nullable();
        $table->string('timezone')->default('Africa/Addis_Ababa');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};
