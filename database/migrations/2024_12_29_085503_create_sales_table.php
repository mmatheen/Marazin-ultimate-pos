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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->unsignedBigInteger('location_id');
            $table->date('sales_date');
            $table->string('status');
            $table->string('invoice_no')->nullable();
            // $table->text('additional_notes')->nullable();
            // $table->text('shipping_details')->nullable();
            // $table->text('shipping_address')->nullable();
            // $table->decimal('shipping_charges', 8, 2)->nullable();
            // $table->string('shipping_status')->nullable();
            // $table->string('delivered_to')->nullable();
            // $table->string('delivery_person')->nullable();
            $table->timestamps();


            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
