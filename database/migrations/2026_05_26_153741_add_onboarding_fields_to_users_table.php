<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Store the chosen avatar (could be an ID or a filename string)
            $table->string('avatar')->nullable();
            
            // Boolean flag to check if they've completed the onboarding wizard
            $table->boolean('has_onboarded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'has_onboarded']);
        });
    }
};