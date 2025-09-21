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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'opening_balance', // Opening balance for a supplier/customer
                'purchase', // Regular purchase
                'purchase_return', // Purchase return
                'sale', // Regular sale
                'sale_return_with_bill', // Sale return with bill
                'sale_return_without_bill', // Sale return without bill
                'payments', // Payment (existing)
                'payment', // Payment (used in code)
                'return', // Return (used in code)
                'opening_balance_payment', // Opening balance payment (new)
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('transaction_type', [
                'opening_balance', // Opening balance for a supplier/customer
                'purchase', // Regular purchase
                'purchase_return', // Purchase return
                'sale', // Regular sale
                'sale_return_with_bill', // Sale return with bill
                'sale_return_without_bill', // Sale return without bill
                'payments', // Payment
            ])->nullable()->change();
        });
    }
};
