<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;

echo "=== FIXING CUSTOMER 26 PAYMENT INCONSISTENCY ===\n";

// Get the problematic sale
$sale = Sale::withoutGlobalScopes()->find(184);
if (!$sale) {
    echo "❌ Sale 184 not found!\n";
    exit;
}

echo "🔍 Current Sale Status:\n";
echo "Sale ID: {$sale->id}\n";
echo "Invoice: {$sale->invoice_no}\n";
echo "Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
echo "Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
echo "Payment Status: {$sale->payment_status}\n";

// Get payments for this sale
$payments = Payment::where('reference_id', 184)
    ->where('payment_type', 'sale')
    ->get();

echo "\n💰 Payments for this sale:\n";
$totalPaymentAmount = 0;
foreach ($payments as $payment) {
    echo "Payment ID: {$payment->id} | Amount: Rs " . number_format($payment->amount, 2) . " | Method: {$payment->payment_method} | Status: {$payment->payment_status}\n";
    if ($payment->payment_status === 'completed') {
        $totalPaymentAmount += $payment->amount;
    }
}

echo "\nTotal completed payments: Rs " . number_format($totalPaymentAmount, 2) . "\n";

// Check if there's a discrepancy
$discrepancy = $totalPaymentAmount - $sale->total_paid;
echo "Discrepancy: Rs " . number_format($discrepancy, 2) . "\n";

if (abs($discrepancy) > 0.01) {
    echo "\n🔧 FIXING PAYMENT DISCREPANCY...\n";
    
    // Update the sale record
    $newTotalDue = max(0, $sale->final_total - $totalPaymentAmount);
    $newPaymentStatus = $totalPaymentAmount >= $sale->final_total ? 'Paid' : ($totalPaymentAmount > 0 ? 'Partial' : 'Due');
    
    $sale->update([
        'total_paid' => $totalPaymentAmount,
        'total_due' => $newTotalDue,
        'payment_status' => $newPaymentStatus
    ]);
    
    echo "✅ Sale updated:\n";
    echo "New Total Paid: Rs " . number_format($totalPaymentAmount, 2) . "\n";
    echo "New Total Due: Rs " . number_format($newTotalDue, 2) . "\n";
    echo "New Payment Status: {$newPaymentStatus}\n";
    
    // Update customer balance if needed
    $customer = Customer::withoutGlobalScopes()->find(26);
    if ($customer) {
        echo "\n👤 Customer Balance Before: Rs " . number_format($customer->current_balance, 2) . "\n";
        
        // Recalculate customer balance based on all their sales
        $totalDue = Sale::withoutGlobalScopes()
            ->where('customer_id', 26)
            ->where('status', 'final')
            ->sum('total_due');
            
        $customer->update(['current_balance' => $totalDue]);
        
        echo "👤 Customer Balance After: Rs " . number_format($totalDue, 2) . "\n";
    }
    
} else {
    echo "\n✅ No discrepancy found. Sale payment status is correct.\n";
}

// Final verification
echo "\n=== FINAL STATUS ===\n";
$sale->refresh();
$customer = Customer::withoutGlobalScopes()->find(26);

echo "Sale Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
echo "Sale Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "Sale Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
echo "Sale Payment Status: {$sale->payment_status}\n";
echo "Customer Balance: Rs " . number_format($customer->current_balance, 2) . "\n";

echo "\n=== FIX COMPLETED ===\n";