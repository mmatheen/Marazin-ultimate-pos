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
        Schema::create('opening_stocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sku')->nullable();
            $table->integer('location_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->string('quantity');
            $table->string('unit_cost');
            $table->string('lot_no');
            $table->string('expiry_date');
            $table->timestamps();
            // ForeignKey
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_stocks');
    }
};
