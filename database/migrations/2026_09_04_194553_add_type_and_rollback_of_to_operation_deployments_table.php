<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_deployments', function (Blueprint $table) {
            $table->string('type')
                ->default('deployment')
                ->after('status')
                ->index();

            $table->unsignedBigInteger('rollback_of')
                ->nullable()
                ->after('triggered_by')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('operation_deployments', function (Blueprint $table) {
            $table->dropIndex([
                'operation_deployments_type_index',
            ]);

            $table->dropIndex([
                'operation_deployments_rollback_of_index',
            ]);

            $table->dropColumn([
                'type',
                'rollback_of',
            ]);
        });
    }
};