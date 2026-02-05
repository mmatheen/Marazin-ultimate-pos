<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$customerId = 12;

echo "========================================\n";
echo "🔧 FIX DUPLICATE OPENING BALANCE PAYMENTS - CUSTOMER {$customerId} ONLY\n";
echo "========================================\n\n";

// Get customer info
$customer = DB::table('customers')->find($customerId);
$customerName = $customer ? trim($customer->first_name . ' ' . $customer->last_name) : 'Unknown';

echo "👤 Customer: {$customerName} (ID: {$customerId})\n";
echo str_repeat("-", 80) . "\n\n";

// Get all opening balance payments for this customer
$payments = DB::table('payments')
    ->where('customer_id', $customerId)
    ->where('payment_type', 'opening_balance')
    ->where('status', 'active')
    ->orderBy('payment_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();

if ($payments->count() === 0) {
    echo "❌ No active opening balance payments found for this customer.\n";
    exit;
}

if ($payments->count() === 1) {
    echo "✅ Only ONE opening balance payment found - no duplicates to fix!\n";
    echo "   Payment ID: {$payments[0]->id} | Amount: Rs. {$payments[0]->amount} | Date: {$payments[0]->payment_date}\n";
    exit;
}

echo "⚠️ FOUND {$payments->count()} OPENING BALANCE PAYMENTS:\n\n";

foreach ($payments as $idx => $payment) {
    $marker = ($idx === 0) ? "✅ KEEP" : "❌ DUPLICATE";
    echo "  [{$marker}] Payment ID: {$payment->id} | Date: {$payment->payment_date} | Amount: Rs. {$payment->amount}\n";
}

// Keep the first payment, mark others as deleted
$firstPayment = $payments->first();
$duplicatePayments = $payments->slice(1);

echo "\n🔧 ACTION PLAN:\n";
echo "  • Keep Payment ID {$firstPayment->id} (earliest payment)\n";
echo "  • Delete " . $duplicatePayments->count() . " duplicate payment(s)\n\n";

$totalFixed = 0;

foreach ($duplicatePayments as $dupPayment) {
    // Find corresponding ledger entry
    $ledgerEntry = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('transaction_type', 'opening_balance_payment')
        ->where('credit', $dupPayment->amount)
        ->where('status', 'active')
        ->where('reference_no', $dupPayment->reference_no)
        ->first();
    
    if ($ledgerEntry) {
        echo "  • Found ledger entry ID {$ledgerEntry->id} for payment {$dupPayment->id}\n";
        
        // Mark payment as deleted (payments table uses active/deleted)
        DB::table('payments')
            ->where('id', $dupPayment->id)
            ->update([
                'status' => 'deleted',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DELETED: Duplicate opening balance payment - " . date('Y-m-d H:i:s') . "]')")
            ]);
        
        // Reverse the ledger entry (ledgers use active/reversed)
        DB::table('ledgers')
            ->where('id', $ledgerEntry->id)
            ->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [REVERSED: Duplicate opening balance payment - " . date('Y-m-d H:i:s') . "]')")
            ]);
        
        echo "  ✅ Deleted payment ID {$dupPayment->id} and reversed ledger ID {$ledgerEntry->id}\n";
        $totalFixed++;
    } else {
        echo "  ⚠️ No matching ledger entry found for payment {$dupPayment->id}\n";
    }
}

echo "\n\n========================================\n";
echo "✅ FIXED {$totalFixed} DUPLICATE OPENING BALANCE PAYMENT(S) FOR CUSTOMER {$customerId}\n";
echo "========================================\n\n";

// Show final balance
$balance = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->value('balance');

echo "💰 Final Ledger Balance: Rs. {$balance}\n\n";
echo "💡 TIP: Run 'php test_customer_69_balance.php' to verify the fix.\n";
