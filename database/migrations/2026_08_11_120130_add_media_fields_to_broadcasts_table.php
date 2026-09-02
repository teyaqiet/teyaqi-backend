<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->string('media_type')
                ->nullable()
                ->after('message');

            $table->string('media_path')
                ->nullable()
                ->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->dropColumn([
                'media_type',
                'media_path',
            ]);
        });
    }
};