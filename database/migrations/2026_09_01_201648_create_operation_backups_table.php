
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_backups', function (Blueprint $table) {

            $table->id();

            $table->string('type')
                ->default('database');

            $table->string('disk');

            $table->string('path');

            $table->string('filename');

            $table->unsignedBigInteger('size')
                ->nullable();

            $table->string('checksum')
                ->nullable();

            $table->string('status')
                ->default('pending');

            $table->text('error')
                ->nullable();

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index('created_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_backups');
    }
};

