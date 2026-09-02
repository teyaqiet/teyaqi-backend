<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('causer'); // Admin or User making/triggering the request
            $table->string('method', 10); // GET, POST, PUT, DELETE, etc.
            $table->text('url');
            $table->ipAddress('ip_address')->nullable();
            $table->unsignedBigInteger('status_code')->nullable(); // HTTP Status Code (200, 422, 500, etc.)
            $table->longText('request_headers')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_body')->nullable();
            $table->decimal('response_time', 8, 2)->nullable(); // Execution time in milliseconds
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};