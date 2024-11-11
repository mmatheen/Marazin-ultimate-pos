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
        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->string('variation_value');
            $table->unsignedBigInteger('variation_title_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();

             // ForeignKey
             $table->foreign('variation_title_id')->references('id')->on('variation_titles');
             $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variations');

    }
};

