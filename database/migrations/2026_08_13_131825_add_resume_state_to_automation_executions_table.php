<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_executions', function (Blueprint $table) {
            $table->foreignId('waiting_node_id')
                ->nullable()
                ->after('context')
                ->constrained('automation_nodes')
                ->nullOnDelete();

            $table->timestamp('resume_at')
                ->nullable()
                ->after('completed_at');

            $table->index('resume_at');
        });
    }

    public function down(): void
    {
        Schema::table('automation_executions', function (Blueprint $table) {
            $table->dropForeign(['waiting_node_id']);
            $table->dropIndex(['resume_at']);
            $table->dropColumn([
                'waiting_node_id',
                'resume_at',
            ]);
        });
    }
};