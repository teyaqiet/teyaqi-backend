<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_logs', function (Blueprint $table) {
            $table->dropForeign([
                'user_id',
            ]);

            $table->dropColumn('user_id');

            $table->foreignId('admin_user_id')
                ->nullable()
                ->after('id')
                ->constrained('admin_users')
                ->nullOnDelete();

            $table->index('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('operation_logs', function (Blueprint $table) {
            $table->dropForeign([
                'admin_user_id',
            ]);

            $table->dropIndex([
                'admin_user_id',
            ]);

            $table->dropColumn('admin_user_id');

            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }
};