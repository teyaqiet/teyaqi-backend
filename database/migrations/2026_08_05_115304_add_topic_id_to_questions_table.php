<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {

            $table->foreignId('topic_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();

        });
    }


    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {

            $table->dropForeign(['topic_id']);
            $table->dropColumn('topic_id');

        });
    }
};
