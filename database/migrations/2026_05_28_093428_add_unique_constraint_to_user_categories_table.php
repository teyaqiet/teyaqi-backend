<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ⚡ SURGICAL CLEANUP: Delete duplicate data tracking rows while preserving constraints
        DB::statement("
            DELETE c1 FROM user_categories c1
            INNER JOIN user_categories c2 
            WHERE c1.id > c2.id 
            AND c1.user_id = c2.user_id 
            AND c1.category_id = c2.category_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No schema modification needed here
    }
};