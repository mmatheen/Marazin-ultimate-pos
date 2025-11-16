<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Ledger;

echo "=== CUSTOMER 26 INVESTIGATION ===\n";

// Get customer details
$customer = Customer::find(26);
if (!$customer) {
    echo "❌ Customer ID 26 not found!\n";
    exit;
}

echo "Customer Name: " . $customer->first_name . " " . $customer->last_name . "\n";
echo "Mobile: " . $customer->mobile_no . "\n"; 
echo "Customer Type: " . $customer->customer_type . "\n";
echo "Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
echo "Credit Limit: Rs " . number_format($customer->credit_limit, 2) . "\n";
echo "===========================================\n";

// Get recent sales (last 10)
echo "\n📋 RECENT SALES (Last 10):\n";
$sales = Sale::where('customer_id', 26)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($sales as $sale) {
    echo sprintf(
        "Sale ID: %d | Invoice: %s | Date: %s | Total: Rs %s | Paid: Rs %s | Due: Rs %s | Status: %s | Sale Status: %s\n",
        $sale->id,
        $sale->invoice_no,
        $sale->sales_date,
        number_format($sale->final_total, 2),
        number_format($sale->total_paid, 2),
        number_format($sale->total_due, 2),
        $sale->payment_status,
        $sale->status
    );
}

echo "\n===========================================\n";

// Get payments for this customer's sales
echo "\n💰 PAYMENTS FOR CUSTOMER 26:\n";
$saleIds = $sales->pluck('id')->toArray();

if (!empty($saleIds)) {
    $payments = Payment::whereIn('reference_id', $saleIds)
        ->where('payment_type', 'sale')
        ->orderBy('created_at', 'desc')
        ->limit(15)
        ->get();
        
    if ($payments->count() > 0) {
        foreach ($payments as $payment) {
            echo sprintf(
                "Payment ID: %d | Sale ID: %d | Amount: Rs %s | Method: %s | Status: %s | Date: %s | Ref: %s\n",
                $payment->id,
                $payment->reference_id,
                number_format($payment->amount, 2),
                $payment->payment_method,
                $payment->payment_status ?? 'N/A',
                $payment->payment_date,
                $payment->reference_no ?? 'N/A'
            );
        }
    } else {
        echo "No payments found for customer 26's sales\n";
    }
}

echo "\n===========================================\n";

// Check ledger entries
echo "\n📊 LEDGER ENTRIES (Last 15):\n";
$ledgerEntries = Ledger::where('customer_id', 26)
    ->orderBy('created_at', 'desc')
    ->limit(15)
    ->get();

if ($ledgerEntries->count() > 0) {
    foreach ($ledgerEntries as $entry) {
        $type = $entry->debit > 0 ? 'DEBIT' : 'CREDIT';
        $amount = $entry->debit > 0 ? $entry->debit : $entry->credit;
        echo sprintf(
            "Ledger ID: %d | %s: Rs %s | Balance: Rs %s | Type: %s | Ref: %s | Date: %s | Notes: %s\n",
            $entry->id,
            $type,
            number_format($amount, 2),
            number_format($entry->balance, 2),
            $entry->transaction_type ?? 'N/A',
            $entry->reference_no ?? 'N/A',
            $entry->created_at->format('Y-m-d H:i:s'),
            substr($entry->notes ?? '', 0, 50)
        );
    }
} else {
    echo "No ledger entries found for customer 26\n";
}

echo "\n===========================================\n";
echo "\n🔍 SUMMARY:\n";
echo "Total Sales: " . $sales->count() . "\n";
echo "Total Payments: " . ($payments->count() ?? 0) . "\n";
echo "Total Ledger Entries: " . $ledgerEntries->count() . "\n";

// Calculate totals
$totalSaleAmount = $sales->sum('final_total');
$totalPaidAmount = $sales->sum('total_paid');
$totalDueAmount = $sales->sum('total_due');

echo "Total Sale Amount: Rs " . number_format($totalSaleAmount, 2) . "\n";
echo "Total Paid Amount: Rs " . number_format($totalPaidAmount, 2) . "\n";
echo "Total Due Amount: Rs " . number_format($totalDueAmount, 2) . "\n";

echo "\n=== END INVESTIGATION ===\n";