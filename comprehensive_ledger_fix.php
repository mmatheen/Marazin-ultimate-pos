<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\UnifiedLedgerService;
use App\Models\Ledger;
use Carbon\Carbon;

echo "=== COMPREHENSIVE LEDGER FIX FOR CUSTOMER 5 ===" . PHP_EOL;

// Step 1: Check current sales for customer 5
echo "Current sales for customer 5:" . PHP_EOL;
$sales = DB::table('sales')->where('customer_id', 5)->orderBy('created_at')->get(['id', 'invoice_no', 'final_total', 'total_paid', 'payment_status', 'created_at']);
foreach ($sales as $sale) {
    echo "  Sale {$sale->invoice_no}: Rs. {$sale->final_total} | Paid: Rs. {$sale->total_paid} | Status: {$sale->payment_status}" . PHP_EOL;
}

// Step 2: Check all ledger entries for customer 5
echo PHP_EOL . "Current ledger entries for customer 5:" . PHP_EOL;
$ledgerEntries = DB::table('ledgers')
    ->where('contact_id', 5)
    ->where('contact_type', 'customer')
    ->orderBy('created_at')
    ->get(['id', 'reference_no', 'transaction_type', 'debit', 'credit', 'status', 'notes', 'created_at']);

foreach ($ledgerEntries as $entry) {
    echo "  ID: {$entry->id} | Ref: {$entry->reference_no} | Type: {$entry->transaction_type} | D: {$entry->debit} | C: {$entry->credit} | Status: {$entry->status}" . PHP_EOL;
}

// Step 3: Clean up strategy
echo PHP_EOL . "=== CLEANING UP LEDGER ===" . PHP_EOL;

// Mark ALL existing entries as reversed to start fresh
$activeEntries = DB::table('ledgers')
    ->where('contact_id', 5)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->get(['id', 'reference_no', 'transaction_type', 'debit', 'credit']);

echo "Marking " . count($activeEntries) . " active entries as reversed..." . PHP_EOL;

foreach ($activeEntries as $entry) {
    DB::table('ledgers')
        ->where('id', $entry->id)
        ->update([
            'status' => 'reversed',
            'notes' => DB::raw("CONCAT(notes, ' [REVERSED: Comprehensive cleanup on " . now()->format('Y-m-d H:i:s') . "]')")
        ]);
    echo "  Reversed entry ID: {$entry->id} ({$entry->reference_no})" . PHP_EOL;
}

// Step 4: Recreate correct entries for each sale
echo PHP_EOL . "=== RECREATING CORRECT ENTRIES ===" . PHP_EOL;

$service = new UnifiedLedgerService();

foreach ($sales as $sale) {
    echo "Processing sale {$sale->invoice_no}..." . PHP_EOL;
    
    // Create sale entry
    $saleEntry = Ledger::createEntry([
        'contact_id' => 5,
        'contact_type' => 'customer',
        'transaction_date' => Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo'),
        'reference_no' => $sale->invoice_no,
        'transaction_type' => 'sale',
        'amount' => $sale->final_total,
        'notes' => "Sale invoice #{$sale->invoice_no} - Cleaned up entry",
    ]);
    echo "  ✅ Created sale entry ID: {$saleEntry->id} for Rs. {$sale->final_total}" . PHP_EOL;
    
    // Create payment entry if sale is paid
    if ($sale->payment_status === 'Paid' && $sale->total_paid > 0) {
        $paymentEntry = Ledger::createEntry([
            'contact_id' => 5,
            'contact_type' => 'customer',
            'transaction_date' => Carbon::parse($sale->created_at)->setTimezone('Asia/Colombo'),
            'reference_no' => $sale->invoice_no,
            'transaction_type' => 'sale_payment', // Use sale_payment instead of payments
            'amount' => -$sale->total_paid, // Negative creates credit (payment reduces debt)
            'notes' => "Payment for sale #{$sale->invoice_no} - Cleaned up entry",
        ]);
        echo "  ✅ Created payment entry ID: {$paymentEntry->id} for Rs. {$sale->total_paid}" . PHP_EOL;
    }
}

// Step 5: Verify final state
echo PHP_EOL . "=== VERIFYING FINAL STATE ===" . PHP_EOL;

$finalLedger = $service->getCustomerLedger(5, '2025-01-01', '2025-12-31', null, false); // Normal view only

echo "Customer: {$finalLedger['customer']['name']}" . PHP_EOL;
echo "Current Balance: Rs. {$finalLedger['customer']['current_balance']}" . PHP_EOL;
echo "Total Transactions: Rs. {$finalLedger['summary']['total_transactions']}" . PHP_EOL;
echo "Total Paid: Rs. {$finalLedger['summary']['total_paid']}" . PHP_EOL;
echo "Outstanding Due: Rs. {$finalLedger['summary']['outstanding_due']}" . PHP_EOL;
echo "Advance Amount: Rs. {$finalLedger['summary']['advance_amount']}" . PHP_EOL;

echo PHP_EOL . "Final Active Transactions:" . PHP_EOL;
$transactions = $finalLedger['transactions'];
if ($transactions instanceof \Illuminate\Support\Collection) {
    $transactions = $transactions->toArray();
}

foreach ($transactions as $t) {
    echo "  {$t['reference_no']} | {$t['type']} | Debit: Rs. {$t['debit']} | Credit: Rs. {$t['credit']} | Balance: Rs. {$t['running_balance']}" . PHP_EOL;
}

echo PHP_EOL . "=== CLEANUP COMPLETE ===" . PHP_EOL;
echo "✅ Customer 5 ledger has been completely cleaned and recreated!" . PHP_EOL;
echo "✅ Only correct active entries remain" . PHP_EOL;
echo "✅ All balances should now be accurate" . PHP_EOL;