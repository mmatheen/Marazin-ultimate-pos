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
            $table->morphs('payable'); // Supports Sales, Purchases, Returns
            $table->unsignedBigInteger('entity_id')->nullable(); // Customer or Supplier ID
            $table->enum('entity_type', ['customer', 'supplier'])->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('due_amount', 15, 2);
            $table->enum('payment_method', ['cash', 'cheque', 'bank_transfer', 'card']);
            $table->string('transaction_no')->nullable(); // For cheques, bank transfers, and card payments
            $table->date('payment_date');

            // Fields for cheque payments
            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('bank_branch')->nullable();

            // Fields for bank transfers
            $table->string('bank_account_number')->nullable();

            // Fields for card payments
            $table->string('card_number')->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_type')->nullable();
            $table->integer('expiry_month')->nullable();
            $table->integer('expiry_year')->nullable();
            $table->string('security_code')->nullable();

            $table->timestamps();
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
