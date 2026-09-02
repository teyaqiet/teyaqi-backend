<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('broadcast_id')
                ->constrained('broadcasts')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', [
                'pending',
                'sending',
                'delivered',
                'failed',
                'blocked',
            ])->default('pending');

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('sent_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->text('error_message')->nullable();

            $table->json('response')->nullable();

            $table->timestamps();

            $table->unique([
                'broadcast_id',
                'user_id',
            ]);

            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_recipients');
    }
};