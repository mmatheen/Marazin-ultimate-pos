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
        Schema::create('warranties', function (Blueprint $table) {
            $table->increments('id');
            // $table->integer('business_id')->unsigned();
            $table->string('name');
            $table->integer('duration');
            $table->enum('duration_type', ['days', 'months','years']);
            $table->text('description');
            $table->timestamps();

            // ForeignKey
            // $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
