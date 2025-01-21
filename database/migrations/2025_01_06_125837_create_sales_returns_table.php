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
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('sale_id')->nullable(); // If returning with a bill (invoice)
            $table->unsignedBigInteger('customer_id')->nullable(); // Allow null for walk-in returns
            $table->unsignedBigInteger('location_id');
            $table->date('return_date');
            $table->decimal('return_total', 12, 2); // Total value of the return
            $table->text('notes')->nullable(); // Reason or additional details
            $table->boolean('is_defective')->default(false); // Indicates defective items
            $table->enum('stock_type', ['with_bill', 'without_bill']);
            $table->timestamps();

            // Foreign keys
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
