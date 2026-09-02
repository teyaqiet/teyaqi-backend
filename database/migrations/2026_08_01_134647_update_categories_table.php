<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {

        // Convert old category names
        DB::statement("
            UPDATE categories
            SET name = JSON_OBJECT(
                'en', name,
                'am', ''
            )
        ");


        Schema::table('categories', function (Blueprint $table) {

            $table->json('name')->change();

            $table->string('slug')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->string('icon')
                ->nullable();

            $table->string('color')
                ->nullable();

            $table->string('image_url')
                ->nullable();

            $table->integer('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

        });

    }


    public function down(): void
    {

        Schema::table('categories', function (Blueprint $table) {

            $columns = [
                'slug',
                'description',
                'icon',
                'color',
                'image_url',
                'sort_order',
                'is_active'
            ];


            foreach($columns as $column){

                if(Schema::hasColumn('categories',$column)){
                    $table->dropColumn($column);
                }

            }

        });

    }
};