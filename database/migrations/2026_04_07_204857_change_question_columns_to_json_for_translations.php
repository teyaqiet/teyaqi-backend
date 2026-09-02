<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    $columns = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'explanation'];

    // 1. Manually update existing rows to valid JSON if they aren't already
    foreach ($columns as $column) {
        DB::statement("UPDATE questions SET {$column} = JSON_OBJECT('en', {$column}) WHERE {$column} IS NOT NULL AND {$column} NOT LIKE '{%'");
    }

    // 2. Change column types AND set character set to utf8mb4
    Schema::table('questions', function (Blueprint $table) {
        // We use charset and collation here to ensure Amharic is supported
        $table->json('question_text')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        $table->json('option_a')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        $table->json('option_b')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        $table->json('option_c')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        $table->json('option_d')->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        $table->json('explanation')->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        
        $table->json('available_locales')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            //
        });
    }
};
