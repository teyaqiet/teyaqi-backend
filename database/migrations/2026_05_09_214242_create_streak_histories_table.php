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
    Schema::create('streak_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->date('activity_date'); 
        $table->enum('status', ['played', 'frozen', 'missed'])->default('played');
        $table->timestamps();
        
        // Prevent duplicate entries for the same day/user
        $table->unique(['user_id', 'activity_date']); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streak_histories');
    }
};
