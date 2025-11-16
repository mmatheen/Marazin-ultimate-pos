<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

echo "=== CONVERTING SALE 184 FROM CASH TO CREDIT ===\n";

DB::beginTransaction();

try {
    // Get the sale
    $sale = Sale::withoutGlobalScopes()->find(184);
    if (!$sale) {
        throw new Exception("Sale 184 not found!");
    }
    
    echo "🔍 BEFORE CONVERSION:\n";
    echo "Sale Total: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "Sale Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "Sale Due: Rs " . number_format($sale->total_due, 2) . "\n";
    echo "Payment Status: {$sale->payment_status}\n";
    
    // Check existing payments
    $payments = Payment::where('reference_id', 184)
        ->where('payment_type', 'sale')
        ->get();
        
    echo "Existing payments count: " . $payments->count() . "\n";
    foreach ($payments as $payment) {
        echo "- Payment ID: {$payment->id}, Amount: Rs " . number_format($payment->amount, 2) . ", Method: {$payment->payment_method}\n";
    }
    
    // Get customer current balance
    $customer = Customer::withoutGlobalScopes()->find(26);
    echo "Customer balance before: Rs " . number_format($customer->current_balance, 2) . "\n";
    
    echo "\n🔄 PERFORMING CONVERSION...\n";
    
    // Step 1: Remove/void all cash payments for this sale
    $deletedPayments = 0;
    foreach ($payments as $payment) {
        if ($payment->payment_method === 'cash') {
            echo "Deleting cash payment ID: {$payment->id} (Rs " . number_format($payment->amount, 2) . ")\n";
            $payment->delete();
            $deletedPayments++;
        }
    }
    
    echo "Deleted {$deletedPayments} cash payment(s)\n";
    
    // Step 2: Update sale to credit status
    $sale->update([
        'total_paid' => 0.00,
        'total_due' => $sale->final_total,
        'payment_status' => 'Due'
    ]);
    
    echo "✅ Sale updated to credit status\n";
    
    // Step 3: Update customer balance to reflect the new due amount
    $newCustomerBalance = $sale->final_total; // Since this is now the only due amount
    $customer->update(['current_balance' => $newCustomerBalance]);
    
    echo "✅ Customer balance updated to Rs " . number_format($newCustomerBalance, 2) . "\n";
    
    // Verify the changes
    echo "\n✅ AFTER CONVERSION:\n";
    $sale->refresh();
    $customer->refresh();
    
    echo "Sale Total: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "Sale Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "Sale Due: Rs " . number_format($sale->total_due, 2) . "\n";
    echo "Payment Status: {$sale->payment_status}\n";
    echo "Customer Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
    
    // Verify no cash payments remain
    $remainingPayments = Payment::where('reference_id', 184)
        ->where('payment_type', 'sale')
        ->get();
        
    echo "Remaining payments: " . $remainingPayments->count() . "\n";
    if ($remainingPayments->count() > 0) {
        foreach ($remainingPayments as $payment) {
            echo "- Payment ID: {$payment->id}, Amount: Rs " . number_format($payment->amount, 2) . ", Method: {$payment->payment_method}\n";
        }
    } else {
        echo "✅ No payment records (correct for credit sale)\n";
    }
    
    // Commit the transaction
    DB::commit();
    
    echo "\n🎉 CONVERSION COMPLETED SUCCESSFULLY!\n";
    echo "Sale 184 is now properly set up as a CREDIT SALE:\n";
    echo "- Customer owes Rs " . number_format($sale->final_total, 2) . "\n";
    echo "- No cash payment records\n";
    echo "- Customer balance reflects the due amount\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR DURING CONVERSION: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
}

echo "\n=== CONVERSION PROCESS COMPLETE ===\n";