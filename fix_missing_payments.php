<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;

echo "=== CREATING MISSING PAYMENT LEDGER ENTRIES ===" . PHP_EOL;

// Get the missing payment records that don't have ledger entries
$payments = [
    ['id' => 455, 'reference_no' => 'CSX-384', 'amount' => 93180, 'customer_id' => 5],
    ['id' => 456, 'reference_no' => 'CSX-392', 'amount' => 420, 'customer_id' => 5] // This should use CSX-392, not SALE-20251126
];

foreach ($payments as $payment) {
    echo "Creating ledger entry for payment {$payment['id']} ({$payment['reference_no']})..." . PHP_EOL;
    
    // Use CSX-392 as reference instead of SALE-20251126 for consistency
    $referenceNo = $payment['reference_no'];
    if ($payment['id'] == 456) {
        $referenceNo = 'CSX-392'; // Correct reference for sale 392
    }
    
    try {
        $ledgerEntry = Ledger::createEntry([
            'contact_id' => $payment['customer_id'],
            'contact_type' => 'customer',
            'transaction_date' => Carbon::now('Asia/Colombo'),
            'reference_no' => $referenceNo,
            'transaction_type' => 'sale_payment',
            'amount' => -$payment['amount'], // Negative creates credit (payment reduces debt)
            'notes' => "Payment for sale #{$referenceNo} - Created missing ledger entry",
        ]);
        
        echo "  ✅ Created ledger entry ID: {$ledgerEntry->id} for Rs. {$payment['amount']}" . PHP_EOL;
        
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . PHP_EOL;
    }
}

// Also update the payment reference_no for consistency
echo PHP_EOL . "Updating payment reference for CSX-392..." . PHP_EOL;
DB::table('payments')->where('id', 456)->update(['reference_no' => 'CSX-392']);
echo "✅ Updated payment 456 reference to CSX-392" . PHP_EOL;

echo PHP_EOL . "=== VERIFYING FINAL STATE ===" . PHP_EOL;

// Check final state
$service = new UnifiedLedgerService();
$ledgerData = $service->getCustomerLedger(5, '2025-01-01', '2025-12-31', null, false);

echo "Customer: {$ledgerData['customer']['name']}" . PHP_EOL;
echo "Current Balance: Rs. {$ledgerData['customer']['current_balance']}" . PHP_EOL;
echo "Total Transactions: Rs. {$ledgerData['summary']['total_transactions']}" . PHP_EOL;
echo "Total Paid: Rs. {$ledgerData['summary']['total_paid']}" . PHP_EOL;
echo "Outstanding Due: Rs. {$ledgerData['summary']['outstanding_due']}" . PHP_EOL;
echo "Advance Amount: Rs. {$ledgerData['summary']['advance_amount']}" . PHP_EOL;

echo PHP_EOL . "Final Active Transactions:" . PHP_EOL;
$transactions = $ledgerData['transactions'];
if ($transactions instanceof \Illuminate\Support\Collection) {
    $transactions = $transactions->toArray();
}

foreach ($transactions as $t) {
    echo "  {$t['reference_no']} | {$t['type']} | Debit: Rs. {$t['debit']} | Credit: Rs. {$t['credit']} | Balance: Rs. {$t['running_balance']}" . PHP_EOL;
}

// Verify payment table vs ledger consistency
echo PHP_EOL . "=== PAYMENT VS LEDGER CONSISTENCY CHECK ===" . PHP_EOL;
$activePayments = DB::table('payments')
    ->where('customer_id', 5)
    ->where('status', 'active')
    ->get(['id', 'reference_no', 'amount']);

$activeLedgerPayments = DB::table('ledgers')
    ->where('contact_id', 5)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->whereIn('transaction_type', ['sale_payment', 'payments'])
    ->get(['id', 'reference_no', 'credit']);

echo "Active payments in payment table: " . count($activePayments) . PHP_EOL;
foreach ($activePayments as $p) {
    echo "  Payment {$p->id}: {$p->reference_no} - Rs. {$p->amount}" . PHP_EOL;
}

echo PHP_EOL . "Active payment ledger entries: " . count($activeLedgerPayments) . PHP_EOL;
foreach ($activeLedgerPayments as $l) {
    echo "  Ledger {$l->id}: {$l->reference_no} - Rs. {$l->credit}" . PHP_EOL;
}

echo PHP_EOL . "=== MISSING PAYMENT ENTRIES FIX COMPLETE ===" . PHP_EOL;