<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_deployments', function (Blueprint $table) {
            $table->id();

            $table->string('environment')->default('staging');

            $table->string('branch')->nullable();
            $table->string('commit_hash', 100)->nullable();
            $table->text('commit_message')->nullable();

            $table->string('status')->default('pending');

            $table->unsignedBigInteger('triggered_by')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable();

            $table->longText('output')->nullable();
            $table->longText('error')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('environment');
            $table->index('status');
            $table->index('branch');
            $table->index('commit_hash');
            $table->index('triggered_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_deployments');
    }
};