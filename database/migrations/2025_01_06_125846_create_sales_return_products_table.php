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
        Schema::create('sales_return_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sales_return_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('batch_id')->nullable(); // Track stock by batch if applicable
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 15, 4); // Quantity returned
            $table->enum('price_type', ['retail', 'wholesale', 'special']);
            $table->decimal('original_price', 8, 2); // Price at the time of sale
            $table->decimal('return_price', 8, 2); // Price for the return (may differ for special customers)
            $table->decimal('discount', 8, 2)->nullable(); // Any return-specific discounts
            $table->decimal('tax', 8, 2)->nullable();
            $table->decimal('subtotal', 12, 2); // Return subtotal (quantity * return_price - discount + tax)
            $table->timestamps();

            // Foreign keys
            $table->foreign('sales_return_id')->references('id')->on('sales_returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_products');
    }
};
