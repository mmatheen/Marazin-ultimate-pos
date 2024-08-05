<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sub_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subCategoryname');
            $table->integer('main_category_id')->unsigned();
            $table->string('subCategoryCode')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

              // ForeignKey
              $table->foreign('main_category_id')->references('id')->on('main_categories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_categories');
    }
};
