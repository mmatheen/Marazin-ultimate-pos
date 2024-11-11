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
            $table->id();
            $table->string('subCategoryname');
            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('subCategoryCode')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            // ForeignKey
            $table->foreign('main_category_id')->references('id')->on('main_categories')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
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
