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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id(); // auto-incrementing ID
            $table->dateTime('transaction_date'); // Date and Time of the transaction
            $table->string('reference_no')->nullable(); // Reference number (e.g., PO2025/0008)
            $table->enum('transaction_type', [
                'opening_balance', // Opening balance for a supplier/customer
                'purchase', // Regular purchase
                'purchase_return', // Purchase return
                'sale', // Regular sale
                'sale_return_with_bill', // Sale return with bill
                'sale_return_without_bill', // Sale return without bill
                'payments', // Payment
            ])->nullable(); // Type of transaction
              $table->decimal('debit', 10, 2)->default(0); // Debit amount
            $table->decimal('credit', 10, 2)->default(0); // Credit amount
            $table->decimal('balance', 10, 2); // Current balance after the transaction
            $table->enum('contact_type', ['customer', 'supplier'])->nullable(); // Type of contact: customer or supplier
            $table->bigInteger('user_id')->unsigned()->nullable(); // ID of the customer or supplier (foreign key)
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
