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
                // Existing transaction types (from previous migrations)
                'opening_balance',
                'purchase',
                'purchase_return',
                'sale',
                'sale_return_with_bill',
                'sale_return_without_bill',
                'payments',
                'payment',
                'return',
                'opening_balance_payment',
                'cheque_bounce',
                'bank_charges',
                'penalty',
                'adjustment_debit',
                'adjustment_credit',
                'bounce_recovery',
                'invoice',
                'sale_payment',
                'purchase_payment',
                'return_payment',

                // From 2025_11_23_164500 migration
                'sale_order',
                'sale_return',
                'opening_balance_adjustment',
                'sale_adjustment',
                'purchase_adjustment',
                'payment_adjustment',
                'floating_balance_adjustment',

                // All reversal types from 2025_11_23_164500
                'opening_balance_reversal',
                'purchase_reversal',
                'purchase_return_reversal',
                'sale_reversal',
                'sale_return_reversal',
                'sale_return_with_bill_reversal',
                'sale_return_without_bill_reversal',
                'payments_reversal',
                'payment_reversal',
                'return_reversal',
                'opening_balance_payment_reversal',
                'cheque_bounce_reversal',
                'bank_charges_reversal',
                'penalty_reversal',
                'adjustment_debit_reversal',
                'adjustment_credit_reversal',
                'bounce_recovery_reversal',
                'invoice_reversal',
                'sale_payment_reversal',
                'purchase_payment_reversal',
                'return_payment_reversal',
                'sale_order_reversal',
                'opening_balance_adjustment_reversal',
                'sale_adjustment_reversal',
                'purchase_adjustment_reversal',
                'payment_adjustment_reversal',
                'floating_balance_adjustment_reversal',

                // NEW: Advance payment types
                'advance_payment',          // For customer/supplier advance payments
                'advance_payment_reversal', // For reversing advance payments
                'bill',                     // Bill transaction type (found in Ledger.php)
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            // Revert to previous enum (without advance_payment and bill)
            $table->enum('transaction_type', [
                'opening_balance',
                'purchase',
                'purchase_return',
                'sale',
                'sale_return_with_bill',
                'sale_return_without_bill',
                'payments',
                'payment',
                'return',
                'opening_balance_payment',
                'cheque_bounce',
                'bank_charges',
                'penalty',
                'adjustment_debit',
                'adjustment_credit',
                'bounce_recovery',
                'invoice',
                'sale_payment',
                'purchase_payment',
                'return_payment',
                'sale_order',
                'sale_return',
                'opening_balance_adjustment',
                'sale_adjustment',
                'purchase_adjustment',
                'payment_adjustment',
                'floating_balance_adjustment',
                'opening_balance_reversal',
                'purchase_reversal',
                'purchase_return_reversal',
                'sale_reversal',
                'sale_return_reversal',
                'sale_return_with_bill_reversal',
                'sale_return_without_bill_reversal',
                'payments_reversal',
                'payment_reversal',
                'return_reversal',
                'opening_balance_payment_reversal',
                'cheque_bounce_reversal',
                'bank_charges_reversal',
                'penalty_reversal',
                'adjustment_debit_reversal',
                'adjustment_credit_reversal',
                'bounce_recovery_reversal',
                'invoice_reversal',
                'sale_payment_reversal',
                'purchase_payment_reversal',
                'return_payment_reversal',
                'sale_order_reversal',
                'opening_balance_adjustment_reversal',
                'sale_adjustment_reversal',
                'purchase_adjustment_reversal',
                'payment_adjustment_reversal',
                'floating_balance_adjustment_reversal',
            ])->nullable()->change();
        });
    }
};
