<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "🔍 FINDING & FIXING DUPLICATE OPENING BALANCE PAYMENTS\n";
echo "========================================\n\n";

// Find customers with duplicate opening balance payments
$duplicates = DB::table('payments')
    ->select('customer_id', DB::raw('COUNT(*) as payment_count'), DB::raw('SUM(amount) as total_amount'))
    ->where('payment_type', 'opening_balance')
    ->where('status', 'active')
    ->groupBy('customer_id')
    ->having('payment_count', '>', 1)
    ->get();

if ($duplicates->count() === 0) {
    echo "✅ No duplicate opening balance payments found.\n";
    exit;
}

echo "⚠️ FOUND " . $duplicates->count() . " CUSTOMERS WITH DUPLICATE OPENING BALANCE PAYMENTS:\n\n";

$totalFixed = 0;

foreach ($duplicates as $duplicate) {
    $customer = DB::table('customers')->find($duplicate->customer_id);
    $customerName = $customer ? trim($customer->first_name . ' ' . $customer->last_name) : 'Unknown';

    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Customer ID: {$duplicate->customer_id} | Name: {$customerName}\n";
    echo "Number of opening balance payments: {$duplicate->payment_count}\n";
    echo "Total amount paid: Rs. {$duplicate->total_amount}\n";
    echo str_repeat("-", 80) . "\n";

    // Get all opening balance payments for this customer (sorted by date)
    $payments = DB::table('payments')
        ->where('customer_id', $duplicate->customer_id)
        ->where('payment_type', 'opening_balance')
        ->where('status', 'active')
        ->orderBy('payment_date', 'asc')
        ->orderBy('id', 'asc')
        ->get();

    echo "\nOpening Balance Payments:\n";
    foreach ($payments as $idx => $payment) {
        $marker = ($idx === 0) ? "✅ KEEP" : "❌ DUPLICATE";
        echo "  [{$marker}] Payment ID: {$payment->id} | Date: {$payment->payment_date} | Amount: Rs. {$payment->amount}\n";
    }

    // Keep the first payment, mark others as reversed
    $firstPayment = $payments->first();
    $duplicatePayments = $payments->slice(1);

    if ($duplicatePayments->count() > 0) {
        echo "\n🔧 ACTION PLAN:\n";
        echo "  • Keep Payment ID {$firstPayment->id} (earliest payment)\n";
        echo "  • Mark " . $duplicatePayments->count() . " duplicate payment(s) as 'deleted'\n";

        foreach ($duplicatePayments as $dupPayment) {
            // Find corresponding ledger entry
            $ledgerEntry = DB::table('ledgers')
                ->where('contact_id', $duplicate->customer_id)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'opening_balance_payment')
                ->where('credit', $dupPayment->amount)
                ->where('status', 'active')
                ->where('reference_no', $dupPayment->reference_no)
                ->first();

            if ($ledgerEntry) {
                echo "  • Found ledger entry ID {$ledgerEntry->id} for payment {$dupPayment->id}\n";

                // Mark payment as deleted (payments table uses active/deleted, not reversed)
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
    }
}

echo "\n\n========================================\n";
echo "✅ FIXED {$totalFixed} DUPLICATE OPENING BALANCE PAYMENTS\n";
echo "========================================\n";
echo "\n💡 TIP: Run the balance check script again to verify the fix.\n";
