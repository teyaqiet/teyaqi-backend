<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE broadcasts
            MODIFY status ENUM(
                'draft',
                'prepared',
                'scheduled',
                'sending',
                'completed',
                'failed'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE broadcasts
            MODIFY status ENUM(
                'draft',
                'scheduled',
                'sending',
                'completed',
                'failed'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};