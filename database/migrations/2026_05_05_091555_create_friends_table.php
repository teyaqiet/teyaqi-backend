<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friends', function (Blueprint $table) {
            $table->id();
            
            // The person who initiated the request
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // The person receiving the request
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            
            // Status: pending (requested), accepted (friends), blocked (optional)
            $table->enum('status', ['pending', 'accepted', 'blocked'])->default('pending');

            // Prevent duplicate rows for the same pair
            $table->unique(['user_id', 'friend_id']);
            
            $table->timestamps();
            
            // Indexing for performance on status lookups
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friends');
    }
};