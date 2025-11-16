<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "=== INVESTIGATING SALE 184 EDIT ISSUE ===\n";

// Get the sale
$sale = Sale::withoutGlobalScopes()->find(184);
if (!$sale) {
    echo "❌ Sale 184 not found!\n";
    exit;
}

echo "🔍 Current Sale Details:\n";
echo "Sale ID: {$sale->id}\n";
echo "Invoice: {$sale->invoice_no}\n";
echo "Customer ID: {$sale->customer_id}\n";
echo "Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
echo "Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
echo "Payment Status: {$sale->payment_status}\n";
echo "Sale Status: {$sale->status}\n";
echo "Created: {$sale->created_at}\n";
echo "Updated: {$sale->updated_at}\n";

echo "\n💰 ALL PAYMENTS FOR THIS SALE:\n";
$payments = Payment::where('reference_id', 184)
    ->where('payment_type', 'sale')
    ->orderBy('created_at', 'asc')
    ->get();

$totalPayments = 0;
foreach ($payments as $payment) {
    echo sprintf(
        "Payment ID: %d | Amount: Rs %s | Method: %s | Status: %s | Date: %s | Ref: %s | Created: %s\n",
        $payment->id,
        number_format($payment->amount, 2),
        $payment->payment_method,
        $payment->payment_status ?? 'N/A',
        $payment->payment_date,
        $payment->reference_no ?? 'N/A',
        $payment->created_at
    );
    
    if ($payment->payment_status === 'completed') {
        $totalPayments += $payment->amount;
    }
}

echo "\nTotal Completed Payments: Rs " . number_format($totalPayments, 2) . "\n";

// Check what the payment status SHOULD be
$shouldBePaidStatus = 'Due';
if ($totalPayments >= $sale->final_total) {
    $shouldBePaidStatus = 'Paid';
} elseif ($totalPayments > 0) {
    $shouldBePaidStatus = 'Partial';
}

echo "Expected Payment Status: {$shouldBePaidStatus}\n";
echo "Actual Payment Status: {$sale->payment_status}\n";

if ($shouldBePaidStatus !== $sale->payment_status) {
    echo "\n⚠️ PAYMENT STATUS MISMATCH DETECTED!\n";
    
    // Check if this was a cash-to-credit conversion issue
    $cashPayments = $payments->where('payment_method', 'cash');
    $creditPayments = $payments->where('payment_method', 'credit');
    
    if ($cashPayments->count() > 0) {
        echo "\n💰 Cash Payments Found:\n";
        foreach ($cashPayments as $payment) {
            echo "- Payment ID: {$payment->id} | Amount: Rs " . number_format($payment->amount, 2) . " | Status: {$payment->payment_status}\n";
        }
    }
    
    if ($creditPayments->count() > 0) {
        echo "\n💳 Credit Payments Found:\n";
        foreach ($creditPayments as $payment) {
            echo "- Payment ID: {$payment->id} | Amount: Rs " . number_format($payment->amount, 2) . " | Status: {$payment->payment_status}\n";
        }
    }
    
    // Check for edit history in logs (if logged)
    echo "\n📝 Checking for edit patterns...\n";
    
    // If there are cash payments but sale is showing as paid with no actual collected amount
    if ($cashPayments->count() > 0 && $sale->total_due > 0 && $sale->payment_status === 'Paid') {
        echo "🚨 SUSPECTED ISSUE: Cash payment exists but sale shows due amount!\n";
        echo "This suggests the sale was edited from CASH to CREDIT but payment status wasn't updated properly.\n";
        
        echo "\n🔧 PROPOSED FIX:\n";
        echo "1. If this should be a CREDIT sale (no payment collected):\n";
        echo "   - Remove/void the cash payment\n";
        echo "   - Set payment_status to 'Due'\n";
        echo "   - Set total_paid to 0\n";
        echo "   - Set total_due to final_total\n";
        
        echo "\n2. If this should be a CASH sale (payment was actually collected):\n";
        echo "   - Keep payment_status as 'Paid'\n";
        echo "   - Set total_paid to payment amount\n";
        echo "   - Set total_due to 0\n";
    }
}

// Check ledger entries to understand the financial impact
echo "\n📊 Checking customer balance impact...\n";
$customerCurrentBalance = DB::table('customers')
    ->where('id', $sale->customer_id)
    ->value('current_balance');

echo "Customer Current Balance: Rs " . number_format($customerCurrentBalance, 2) . "\n";

// Calculate what customer balance should be if this sale is properly credited
$allSalesDue = Sale::withoutGlobalScopes()
    ->where('customer_id', $sale->customer_id)
    ->where('status', 'final')
    ->sum('total_due');

echo "Total due from all sales: Rs " . number_format($allSalesDue, 2) . "\n";

if (abs($customerCurrentBalance - $allSalesDue) > 0.01) {
    echo "⚠️ Customer balance discrepancy detected!\n";
    echo "Expected balance: Rs " . number_format($allSalesDue, 2) . "\n";
    echo "Actual balance: Rs " . number_format($customerCurrentBalance, 2) . "\n";
}

echo "\n=== END INVESTIGATION ===\n";