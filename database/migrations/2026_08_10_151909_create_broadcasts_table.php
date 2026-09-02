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
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->text('message');

            $table->enum('type', [
                'manual',
                'automation',
            ])->default('manual');

            $table->enum('channel', [
                'telegram',
            ])->default('telegram');

            $table->enum('status', [
                'draft',
                'scheduled',
                'sending',
                'completed',
                'failed',
            ])->default('draft');

            $table->string('audience_type')->default('all');

            $table->json('filters')->nullable();

            $table->timestamp('scheduled_at')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('channel');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};