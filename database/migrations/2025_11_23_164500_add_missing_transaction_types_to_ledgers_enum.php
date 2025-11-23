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
                // Existing transaction types
                'opening_balance', // Opening balance for a supplier/customer
                'purchase', // Regular purchase
                'purchase_return', // Purchase return
                'sale', // Regular sale
                'sale_return_with_bill', // Sale return with bill
                'sale_return_without_bill', // Sale return without bill
                'payments', // Payment (existing)
                'payment', // Payment (used in code)
                'return', // Return (used in code)
                'opening_balance_payment', // Opening balance payment
                'cheque_bounce', // Cheque bounce transactions
                'bank_charges', // Bank charges for bounced cheques
                'penalty', // Penalty charges
                'adjustment_debit', // Manual adjustment - debit
                'adjustment_credit', // Manual adjustment - credit
                'bounce_recovery', // Recovery of bounced cheque amount
                'invoice', // Invoice transactions
                'sale_payment', // Sale payment
                'purchase_payment', // Purchase payment
                'return_payment', // Return payment
                
                // Missing transaction types found in codebase
                'sale_order', // Sale order transactions
                'sale_return', // Sale return (used in UnifiedLedgerService)
                'opening_balance_adjustment', // Opening balance adjustment
                'sale_adjustment', // Sale adjustment
                'purchase_adjustment', // Purchase adjustment  
                'payment_adjustment', // Payment adjustment
                'floating_balance_adjustment', // Floating balance adjustment
                
                // Reversal types (for transaction reversals)
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
                'payments', // Payment (existing)
                'payment', // Payment (used in code)
                'return', // Return (used in code)
                'opening_balance_payment', // Opening balance payment
                'cheque_bounce', // Cheque bounce transactions
                'bank_charges', // Bank charges for bounced cheques
                'penalty', // Penalty charges
                'adjustment_debit', // Manual adjustment - debit
                'adjustment_credit', // Manual adjustment - credit
                'bounce_recovery', // Recovery of bounced cheque amount
                'invoice', // Invoice transactions
                'sale_payment', // Sale payment
                'purchase_payment', // Purchase payment
                'return_payment', // Return payment
            ])->nullable()->change();
        });
    }
};