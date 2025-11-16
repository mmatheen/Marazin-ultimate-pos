<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Ledger;

echo "=== CUSTOMER 26 INVESTIGATION (Without Global Scopes) ===\n";

// Check customer without global scopes
$customer = Customer::withoutGlobalScopes()->find(26);

if (!$customer) {
    echo "❌ Customer ID 26 not found even without global scopes!\n";
    
    // Let's check what customers exist around ID 26
    echo "\n🔍 Checking customers around ID 26:\n";
    $nearbyCustomers = Customer::withoutGlobalScopes()
        ->whereBetween('id', [20, 30])
        ->orderBy('id')
        ->get(['id', 'first_name', 'last_name', 'customer_type', 'current_balance']);
        
    foreach ($nearbyCustomers as $c) {
        echo "ID: {$c->id} | Name: {$c->first_name} {$c->last_name} | Type: {$c->customer_type} | Balance: Rs {$c->current_balance}\n";
    }
    exit;
}

echo "✅ Customer Found!\n";
echo "Name: " . $customer->first_name . " " . $customer->last_name . "\n";
echo "Mobile: " . $customer->mobile_no . "\n"; 
echo "Customer Type: " . $customer->customer_type . "\n";
echo "Current Balance: Rs " . number_format($customer->current_balance, 2) . "\n";
echo "Credit Limit: Rs " . number_format($customer->credit_limit, 2) . "\n";
echo "===========================================\n";

// Get sales without global scopes
echo "\n📋 SALES FOR CUSTOMER 26 (Without Global Scopes):\n";
$sales = Sale::withoutGlobalScopes()
    ->where('customer_id', 26)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "Total sales found: " . $sales->count() . "\n";

foreach ($sales as $sale) {
    echo sprintf(
        "Sale ID: %d | Invoice: %s | Date: %s | Total: Rs %s | Paid: Rs %s | Due: Rs %s | Payment Status: %s | Sale Status: %s | User ID: %d\n",
        $sale->id,
        $sale->invoice_no,
        $sale->sales_date,
        number_format($sale->final_total, 2),
        number_format($sale->total_paid, 2),
        number_format($sale->total_due, 2),
        $sale->payment_status,
        $sale->status,
        $sale->user_id ?? 0
    );
}

if ($sales->count() > 0) {
    echo "\n💰 PAYMENTS FOR CUSTOMER 26'S SALES:\n";
    $saleIds = $sales->pluck('id')->toArray();
    
    $payments = Payment::whereIn('reference_id', $saleIds)
        ->where('payment_type', 'sale')
        ->orderBy('created_at', 'desc')
        ->get();
        
    echo "Total payments found: " . $payments->count() . "\n";
    
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
    
    echo "\n📊 LEDGER ENTRIES FOR CUSTOMER 26:\n";
    $ledgerEntries = Ledger::where('customer_id', 26)
        ->orderBy('created_at', 'desc')
        ->limit(15)
        ->get();
        
    echo "Total ledger entries found: " . $ledgerEntries->count() . "\n";
    
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
            substr($entry->notes ?? '', 0, 60) . (strlen($entry->notes ?? '') > 60 ? '...' : '')
        );
    }
}

echo "\n=== END INVESTIGATION ===\n";