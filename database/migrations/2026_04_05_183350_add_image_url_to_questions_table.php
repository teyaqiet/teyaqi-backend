<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('questions', function (Blueprint $table) {
        // Use text or string depending on if you're using external URLs or local paths
        $table->string('image_url')->nullable()->after('question_text');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('questions', function ($table) {
        $table->dropColumn('image_url');
    });
}
};
