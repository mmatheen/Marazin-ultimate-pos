<?php

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Customer;

echo "=== INVESTIGATING CUSTOMER 26 BALANCE DISCREPANCY ===\n";

// Check ledger table structure first
echo "📋 Checking ledger table structure...\n";
$columns = DB::select('DESCRIBE ledgers');
$hasCustomerId = false;
$columnNames = [];

foreach ($columns as $column) {
    $columnNames[] = $column->Field;
    if ($column->Field === 'customer_id') {
        $hasCustomerId = true;
    }
}

echo "Ledger table columns: " . implode(', ', $columnNames) . "\n";

if ($hasCustomerId) {
    echo "\n✅ Ledgers table has customer_id column\n";
    
    $ledgers = DB::table('ledgers')
        ->where('customer_id', 26)
        ->orderBy('created_at', 'desc')
        ->limit(15)
        ->get();
        
    echo "Found " . $ledgers->count() . " ledger entries for customer 26:\n";
    
    foreach ($ledgers as $ledger) {
        $type = $ledger->debit > 0 ? 'DEBIT' : 'CREDIT';
        $amount = $ledger->debit > 0 ? $ledger->debit : $ledger->credit;
        echo sprintf(
            "%s: Rs %s | Balance: Rs %s | Type: %s | Ref: %s | Date: %s\n",
            $type,
            number_format($amount, 2),
            number_format($ledger->balance ?? 0, 2),
            $ledger->transaction_type ?? 'N/A',
            $ledger->reference_no ?? 'N/A',
            $ledger->created_at ?? 'N/A'
        );
    }
    
} else {
    echo "\n❌ Ledgers table does not have customer_id column\n";
    echo "Need to check alternative ledger structure...\n";
    
    // Check if there's a different ledger approach
    $sampleLedger = DB::table('ledgers')->first();
    if ($sampleLedger) {
        echo "Sample ledger entry structure:\n";
        foreach ($sampleLedger as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
    }
}

// Now let's check what's causing the customer balance to be Rs 12,000
echo "\n🔍 ANALYZING CUSTOMER BALANCE CALCULATION:\n";

$customer = Customer::withoutGlobalScopes()->find(26);
echo "Customer current_balance field: Rs " . number_format($customer->current_balance, 2) . "\n";

// Calculate balance from sales
$sales = DB::table('sales')
    ->where('customer_id', 26)
    ->where('status', 'final')
    ->get(['id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status', 'created_at', 'updated_at']);

echo "\nAll final sales for customer 26:\n";
$totalDueFromSales = 0;
foreach ($sales as $sale) {
    echo sprintf(
        "Sale %d (%s): Total Rs %s, Paid Rs %s, Due Rs %s, Status: %s\n",
        $sale->id,
        $sale->invoice_no,
        number_format($sale->final_total, 2),
        number_format($sale->total_paid, 2),
        number_format($sale->total_due, 2),
        $sale->payment_status
    );
    $totalDueFromSales += $sale->total_due;
}

echo "\nCalculated total due from sales: Rs " . number_format($totalDueFromSales, 2) . "\n";
echo "Customer balance field shows: Rs " . number_format($customer->current_balance, 2) . "\n";
echo "Discrepancy: Rs " . number_format($customer->current_balance - $totalDueFromSales, 2) . "\n";

if (abs($customer->current_balance - $totalDueFromSales) > 0.01) {
    echo "\n🚨 BALANCE DISCREPANCY CONFIRMED!\n";
    echo "The customer.current_balance field is not matching the sum of sales.total_due\n";
    echo "This suggests the customer balance was not updated when the sale payment status changed.\n";
    
    echo "\n🔧 FIXING CUSTOMER BALANCE...\n";
    
    // Update the customer balance to match the actual dues
    DB::table('customers')
        ->where('id', 26)
        ->update(['current_balance' => $totalDueFromSales]);
    
    echo "✅ Customer balance updated from Rs " . number_format($customer->current_balance, 2) . 
         " to Rs " . number_format($totalDueFromSales, 2) . "\n";
         
    // Verify the update
    $updatedCustomer = Customer::withoutGlobalScopes()->find(26);
    echo "Verified new balance: Rs " . number_format($updatedCustomer->current_balance, 2) . "\n";
}

echo "\n=== INVESTIGATION COMPLETE ===\n";