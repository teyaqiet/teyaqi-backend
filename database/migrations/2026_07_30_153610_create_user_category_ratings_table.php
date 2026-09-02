<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_category_ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // Category-specific adaptive rating.
            // Same 0–100 scale as overall SR.
            $table->decimal('sr', 6, 2)
                ->default(50.00);

            // Useful for confidence calculations later.
            $table->unsignedInteger('questions_answered')
                ->default(0);

            $table->unsignedInteger('correct_answers')
                ->default(0);

            $table->timestamp('last_answered_at')
                ->nullable();

            $table->timestamps();

            // One rating per user/category pair.
            $table->unique([
                'user_id',
                'category_id',
            ]);

            // Useful when loading a user's category ratings.
            $table->index([
                'user_id',
                'sr',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_ratings');
    }
};