<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_deployment_locks', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('deployment_id')->nullable();

            $table->string('environment')->default('staging');

            $table->timestamp('locked_at')->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index('deployment_id');
            $table->index('environment');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_deployment_locks');
    }
};