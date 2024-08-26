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
           $table->increments('id');
            $table->string('variation_value');
            $table->integer('variation_title_id')->unsigned();
            $table->timestamps();

             // ForeignKey
             $table->foreign('variation_title_id')->references('id')->on('variation_titles');
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

