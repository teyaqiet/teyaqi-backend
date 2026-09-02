<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('causer'); // Tracks which admin/user performed the action
            $table->string('action'); // e.g., 'created', 'updated', 'deleted', 'login'
            $table->string('description'); // Human-readable description
            $table->nullableMorphs('subject'); // The model being affected (e.g., Question, AdminUser)
            $table->text('properties')->nullable(); // JSON payload for old/new data
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
