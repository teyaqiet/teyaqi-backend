<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // App\Enums\ChallengeType
            $table->string('status')->default('draft'); // App\Enums\ChallengeStatus
            $table->string('visibility')->default('public'); // App\Enums\ChallengeVisibility
            $table->string('thumbnail')->nullable();

            // Dynamic targeting boundaries
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->string('difficulty')->nullable();
            $table->unsignedInteger('level_min')->nullable();
            $table->unsignedInteger('level_max')->nullable();

            // Structural parameters
            $table->unsignedInteger('question_count')->default(10);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->unsignedInteger('passing_score')->nullable();

            // Rewards
            $table->unsignedInteger('reward_xp')->default(0);
            $table->unsignedInteger('reward_coins')->default(0);

            // Engine Flag Toggles
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_daily')->default(false);
            $table->boolean('is_ranked')->default(false);
            $table->boolean('allow_retry')->default(true);

            // Scheduling
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            // Advanced Rules Metaconfig
            $table->json('config')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Performance Indexes
            $table->index(['status', 'visibility', 'is_daily']);
            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};