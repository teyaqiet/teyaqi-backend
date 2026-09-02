<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('action');
            $table->string('module')->nullable();

            $table->string('environment')->nullable();

            $table->string('status')->default('success');

            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->ipAddress('ip_address')->nullable();

            $table->string('user_agent', 1024)->nullable();

            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index('environment');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
    }
};