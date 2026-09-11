<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_alerts', function (Blueprint $table) {
            if (! Schema::hasColumn(
                'operation_alerts',
                'resolution_type'
            )) {
                $table->string('resolution_type', 30)
                    ->nullable()
                    ->after('resolved_by');

                $table->index('resolution_type');
            }

            if (! Schema::hasColumn(
                'operation_alerts',
                'resolved_reason'
            )) {
                $table->string('resolved_reason')
                    ->nullable()
                    ->after('resolution_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operation_alerts', function (Blueprint $table) {
            if (Schema::hasColumn(
                'operation_alerts',
                'resolved_reason'
            )) {
                $table->dropColumn('resolved_reason');
            }

            if (Schema::hasColumn(
                'operation_alerts',
                'resolution_type'
            )) {
                $table->dropIndex([
                    'operation_alerts_resolution_type_index',
                ]);

                $table->dropColumn('resolution_type');
            }
        });
    }
};
