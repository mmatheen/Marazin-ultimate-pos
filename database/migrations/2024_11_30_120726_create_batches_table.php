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
        Schema::create('batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('batch_no')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('qty');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('wholesale_price', 10, 2);
            $table->decimal('special_price', 10, 2);
            $table->decimal('retail_price', 10, 2);
            $table->decimal('max_retail_price', 10, 2);
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
