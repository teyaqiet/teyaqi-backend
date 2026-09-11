<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_alert_notifications', function (
            Blueprint $table
        ) {
            $table->id();

            $table->foreignId('alert_id')
                ->nullable()
                ->constrained('operation_alerts')
                ->nullOnDelete();

            $table->string('channel', 30);
            $table->string('recipient')->nullable();

            $table->string('event', 30);

            $table->string('status', 30)
                ->default('pending');

            $table->text('message')->nullable();

            $table->text('error')->nullable();

            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index('alert_id');
            $table->index('channel');
            $table->index('event');
            $table->index('status');
            $table->index('sent_at');

            $table->index([
                'alert_id',
                'channel',
                'event',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'operation_alert_notifications'
        );
    }
};