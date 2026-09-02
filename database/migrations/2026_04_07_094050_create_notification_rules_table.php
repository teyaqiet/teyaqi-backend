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
    Schema::create('notification_rules', function (Blueprint $table) {
        $table->id();
        $table->string('event_type'); // level_up, streak_milestone, out_of_lives
        $table->integer('threshold')->nullable(); // e.g., 1000 (XP) or 5 (Streak)
        $table->text('message_template');
        $table->string('button_text')->nullable();
        $table->string('button_url')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
