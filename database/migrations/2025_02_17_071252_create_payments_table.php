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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->date('payment_date');
            $table->double('amount', 15, 2);
            $table->string('payment_method');
            $table->string('reference_no')->nullable();
            $table->string('notes')->nullable();
            $table->enum('payment_type', ['purchase', 'sale', 'purchase_return', 'sale_return_with_bill', 'sale_return_without_bill'])->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_expiry_month')->nullable();
            $table->string('card_expiry_year')->nullable();
            $table->string('card_security_code')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('cheque_bank_branch')->nullable();
            $table->date('cheque_received_date')->nullable();
            $table->date('cheque_valid_date')->nullable();
            $table->string('cheque_given_by')->nullable();
            $table->unsignedBigInteger('location_id');
            
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
