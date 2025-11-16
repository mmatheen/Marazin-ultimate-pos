<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;

echo "=== INVESTIGATING PAYMENT TABLE ISSUE FOR SALE 184 ===\n";

// Get the sale details
$sale = Sale::withoutGlobalScopes()->find(184);
if (!$sale) {
    echo "❌ Sale 184 not found!\n";
    exit;
}

echo "🔍 Current Sale Status:\n";
echo "Sale ID: {$sale->id}\n";
echo "Invoice: {$sale->invoice_no}\n";
echo "Customer ID: {$sale->customer_id}\n";
echo "Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
echo "Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
echo "Payment Status: {$sale->payment_status}\n";

// Check all payments for this sale
echo "\n💰 PAYMENTS IN PAYMENTS TABLE:\n";
$payments = Payment::where('reference_id', 184)
    ->where('payment_type', 'sale')
    ->get();

if ($payments->count() === 0) {
    echo "No payments found for this sale.\n";
} else {
    foreach ($payments as $payment) {
        echo sprintf(
            "Payment ID: %d | Amount: Rs %s | Method: %s | Status: %s | Date: %s | Ref: %s\n",
            $payment->id,
            number_format($payment->amount, 2),
            $payment->payment_method,
            $payment->payment_status ?? 'N/A',
            $payment->payment_date,
            $payment->reference_no ?? 'N/A'
        );
    }
}

echo "\n🤔 ANALYSIS:\n";

// Determine what SHOULD be the correct state
if ($sale->payment_status === 'Paid' && $sale->total_paid > 0 && $sale->total_due == 0) {
    echo "Sale status indicates: CASH SALE (fully paid)\n";
    echo "Payments should exist: YES\n";
    echo "Expected payment amount: Rs " . number_format($sale->total_paid, 2) . "\n";
} elseif ($sale->payment_status === 'Due' && $sale->total_paid == 0 && $sale->total_due > 0) {
    echo "Sale status indicates: CREDIT SALE (no payment)\n";
    echo "Payments should exist: NO\n";
    echo "Expected payment amount: Rs 0.00\n";
} elseif ($sale->payment_status === 'Partial' && $sale->total_paid > 0 && $sale->total_due > 0) {
    echo "Sale status indicates: PARTIAL PAYMENT\n";
    echo "Payments should exist: YES (partial)\n";
    echo "Expected payment amount: Rs " . number_format($sale->total_paid, 2) . "\n";
} else {
    echo "⚠️ INCONSISTENT SALE STATE DETECTED!\n";
    echo "Payment status: {$sale->payment_status}\n";
    echo "Total paid: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "Total due: Rs " . number_format($sale->total_due, 2) . "\n";
}

// Check for inconsistencies
$actualPaymentAmount = $payments->where('payment_status', 'completed')->sum('amount');
echo "\nActual payment amount in table: Rs " . number_format($actualPaymentAmount, 2) . "\n";
echo "Sale record total_paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "Difference: Rs " . number_format($actualPaymentAmount - $sale->total_paid, 2) . "\n";

if (abs($actualPaymentAmount - $sale->total_paid) > 0.01) {
    echo "\n🚨 PAYMENT MISMATCH DETECTED!\n";
    
    echo "\n📋 DETERMINE CORRECT SCENARIO:\n";
    echo "Please tell me what this sale SHOULD be:\n";
    echo "1. CASH SALE - Customer paid Rs 7,000 in cash (keep payment, sale = paid)\n";
    echo "2. CREDIT SALE - Customer owes Rs 7,000 (remove payment, sale = due)\n";
    
    // For now, let's check the timestamps to see if we can determine what happened
    $saleUpdated = $sale->updated_at;
    $paymentCreated = $payments->first()->created_at ?? null;
    
    echo "\nTimestamp analysis:\n";
    echo "Sale last updated: {$saleUpdated}\n";
    echo "Payment created: {$paymentCreated}\n";
    
    if ($paymentCreated && $saleUpdated > $paymentCreated) {
        echo "⏰ Sale was updated AFTER payment was created\n";
        echo "This suggests sale was edited from CASH to CREDIT\n";
        echo "Recommended action: REMOVE the cash payment and make it a credit sale\n";
        
        echo "\n🔧 PROPOSED CREDIT SALE FIX:\n";
        echo "1. Delete/void the cash payment record\n";
        echo "2. Update sale: total_paid = 0, total_due = 7000, payment_status = 'Due'\n";
        echo "3. Update customer balance to reflect the new due amount\n";
    } else {
        echo "⏰ Payment was created AFTER sale update or simultaneously\n";
        echo "This suggests it might be a legitimate cash sale\n";
        echo "Recommended action: KEEP the cash payment\n";
    }
    
} else {
    echo "\n✅ Payment amounts match. No discrepancy in payment records.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "DECISION NEEDED: What should this sale be?\n";
echo "Current state: Sale shows as PAID with Rs 7,000 cash payment\n";
echo "If this should be a CREDIT SALE, I can fix it now.\n";
echo str_repeat("=", 60) . "\n";

// Ask for user decision (in a real scenario, this would be interactive)
// For now, let's provide both fix options

echo "\n🛠️ FIX OPTION 1: CONVERT TO CREDIT SALE\n";
echo "This will:\n";
echo "- Remove the cash payment record\n";
echo "- Set sale as Due (total_paid = 0, total_due = 7000)\n";
echo "- Update customer balance to +Rs 7,000\n";

echo "\n🛠️ FIX OPTION 2: KEEP AS CASH SALE\n";  
echo "This will:\n";
echo "- Keep the cash payment record\n";
echo "- Ensure sale stays as Paid\n";
echo "- Customer balance remains 0\n";

echo "\n=== INVESTIGATION COMPLETE ===\n";
echo "Review the analysis above and let me know which option to apply.\n";