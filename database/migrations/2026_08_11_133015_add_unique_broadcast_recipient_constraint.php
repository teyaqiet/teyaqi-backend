<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_recipients', function (Blueprint $table) {
            $table->unique(
                ['broadcast_id', 'user_id'],
                'broadcast_recipient_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_recipients', function (Blueprint $table) {
            $table->dropUnique(
                'broadcast_recipient_unique'
            );
        });
    }
};