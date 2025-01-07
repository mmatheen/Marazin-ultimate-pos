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
        Schema::create('sales_return_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_return_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('payment_method');
            $table->string('payment_account')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->text('payment_note')->nullable();
            $table->timestamps();

            $table->foreign('sale_return_id')->references('id')->on('sales_returns')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_return_payments');
    }
};
