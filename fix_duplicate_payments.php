<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;

echo "=== FIXING DUPLICATE PAYMENTS ===" . PHP_EOL;

// Step 1: Check current payments for CSX-384
$payments = DB::table('payments')
    ->where('reference_id', 384)
    ->where('payment_type', 'sale')
    ->where('status', 'active')
    ->orderBy('created_at')
    ->get(['id', 'reference_no', 'customer_id', 'amount', 'created_at']);

echo "Found " . count($payments) . " active payments:" . PHP_EOL;
foreach ($payments as $payment) {
    echo "  Payment ID: {$payment->id} | Amount: {$payment->amount} | Created: {$payment->created_at}" . PHP_EOL;
}

if (count($payments) > 1) {
    echo PHP_EOL . "=== FIXING DUPLICATE PAYMENTS ===" . PHP_EOL;
    
    // Keep the latest payment (ID 458), deactivate the older one (ID 452)
    $oldPayment = $payments[0]; // First (older) payment
    $newPayment = $payments[1]; // Second (newer) payment
    
    echo "Marking older payment ID as processed: {$oldPayment->id}" . PHP_EOL;
    DB::table('payments')
        ->where('id', $oldPayment->id)
        ->update([
            'notes' => 'Duplicate payment - superseded by payment #' . $newPayment->id,
            'updated_at' => now()
        ]);
    
    echo "Keeping newer payment ID: {$newPayment->id}" . PHP_EOL;
    
    // Step 2: Create ledger entry for the active payment
    echo PHP_EOL . "Creating ledger entry for payment ID: {$newPayment->id}" . PHP_EOL;
    
    $service = new UnifiedLedgerService();
    
    // Create payment object for ledger service
    $paymentObj = (object)[
        'id' => $newPayment->id,
        'reference_no' => $newPayment->reference_no,
        'customer_id' => $newPayment->customer_id,
        'amount' => $newPayment->amount,
        'payment_method' => 'cash',
        'notes' => 'Payment for sale #CSX-384'
    ];
    
    // Find the sale for context
    $sale = DB::table('sales')->where('id', 384)->first();
    
    try {
        $ledgerEntry = $service->recordSalePayment($paymentObj, $sale);
        echo "✅ Created ledger entry ID: {$ledgerEntry->id}" . PHP_EOL;
    } catch (Exception $e) {
        echo "❌ Error creating ledger entry: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "=== VERIFYING FINAL STATE ===" . PHP_EOL;

// Check final customer balance
$service = new UnifiedLedgerService();
$ledgerData = $service->getCustomerLedger(5, '2025-01-01', '2025-12-31');

echo "Customer: {$ledgerData['customer']['name']}" . PHP_EOL;
echo "Current Balance: {$ledgerData['customer']['current_balance']}" . PHP_EOL;
echo "Transaction Count: " . count($ledgerData['transactions']) . PHP_EOL;

echo PHP_EOL . "Recent Transactions:" . PHP_EOL;
$transactions = $ledgerData['transactions'];
if ($transactions instanceof \Illuminate\Support\Collection) {
    $transactions = $transactions->toArray();
}

$recent = array_slice($transactions, -5);
foreach ($recent as $t) {
    echo "  {$t['reference_no']} | {$t['type']} | Debit: {$t['debit']} | Credit: {$t['credit']} | Balance: {$t['running_balance']}" . PHP_EOL;
}

echo PHP_EOL . "=== FIX COMPLETE ===" . PHP_EOL;