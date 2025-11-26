<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

echo "=== LEDGER UPDATE TEST ===" . PHP_EOL;

// Find the CSX-384 sale
$sale = DB::table('sales')->where('invoice_no', 'CSX-384')->first();

if (!$sale) {
    echo "Sale CSX-384 not found!" . PHP_EOL;
    exit;
}

echo "Found Sale: {$sale->invoice_no}" . PHP_EOL;
echo "Customer ID: {$sale->customer_id}" . PHP_EOL; 
echo "Final Total: {$sale->final_total}" . PHP_EOL;
echo "Created At: {$sale->created_at}" . PHP_EOL;

// Check current ledger entries
echo PHP_EOL . "=== CURRENT LEDGER ENTRIES ===" . PHP_EOL;
$entries = DB::table('ledgers')
    ->where('reference_no', 'CSX-384')
    ->where('status', 'active')
    ->orderBy('created_at')
    ->get(['id', 'transaction_type', 'debit', 'credit', 'transaction_date', 'created_at']);

foreach ($entries as $entry) {
    echo "ID: {$entry->id} | Type: {$entry->transaction_type} | Debit: {$entry->debit} | Credit: {$entry->credit}" . PHP_EOL;
    echo "  Transaction Date: {$entry->transaction_date}" . PHP_EOL;
    echo "  Created At: {$entry->created_at}" . PHP_EOL;
}

// Check customer balance
echo PHP_EOL . "=== CUSTOMER BALANCE CHECK ===" . PHP_EOL;
$service = new UnifiedLedgerService();

try {
    $ledgerData = $service->getCustomerLedger($sale->customer_id, '2025-01-01', '2025-12-31');
    echo "Customer: {$ledgerData['customer']['name']}" . PHP_EOL;
    echo "Current Balance: {$ledgerData['customer']['current_balance']}" . PHP_EOL;
    echo "Transaction Count: " . count($ledgerData['transactions']) . PHP_EOL;
    
    echo PHP_EOL . "All Transactions:" . PHP_EOL;
    $transactions = $ledgerData['transactions'];
    if ($transactions instanceof \Illuminate\Support\Collection) {
        $transactions = $transactions->toArray();
    }
    
    foreach ($transactions as $t) {
        echo "  {$t['reference_no']} | {$t['type']} | Debit: {$t['debit']} | Credit: {$t['credit']} | Balance: {$t['running_balance']}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;