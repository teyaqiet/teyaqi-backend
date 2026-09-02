<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcast_recipients', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'sending',
                'sent',
                'failed',
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_recipients', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'sent',
                'failed',
            ])->default('pending')->change();
        });
    }
};