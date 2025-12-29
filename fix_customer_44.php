<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIXING CUSTOMER 44 LEDGER ===\n\n";

DB::beginTransaction();

try {
    $customerId = 44;

    echo "Step 1: Deleting incorrect opening balance payments...\n";

    // Delete all opening balance payments
    $obPaymentIds = [346, 791, 984];
    foreach ($obPaymentIds as $paymentId) {
        $payment = DB::table('payments')->where('id', $paymentId)->first();
        if ($payment) {
            echo "  - Deleting payment ID $paymentId (Amount: {$payment->amount}, Ref: {$payment->reference_no})\n";
            DB::table('payments')->where('id', $paymentId)->delete();
        }
    }

    // Delete corresponding ledger entries for opening balance payments
    echo "\nStep 2: Deleting opening balance payment ledger entries...\n";
    $deletedLedgers = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('transaction_type', 'opening_balance_payment')
        ->where('status', 'active')
        ->delete();
    echo "  - Deleted $deletedLedgers ledger entries\n";

    echo "\nStep 3: Creating sale payments for unpaid invoices...\n";

    $unpaidSales = [
        ['id' => 302, 'invoice_no' => 'CSX-302', 'amount' => 19000.00],
        ['id' => 459, 'invoice_no' => 'CSX-459', 'amount' => 15500.00],
        ['id' => 702, 'invoice_no' => 'CSX-702', 'amount' => 38000.00],
    ];

    $paymentDate = date('Y-m-d H:i:s');

    foreach ($unpaidSales as $sale) {
        echo "  - Creating payment for {$sale['invoice_no']} (Amount: {$sale['amount']})\n";

        // Create payment record
        $paymentId = DB::table('payments')->insertGetId([
            'payment_date' => $paymentDate,
            'amount' => $sale['amount'],
            'payment_method' => 'cash',
            'payment_type' => 'sale',
            'reference_id' => $sale['id'],
            'reference_no' => 'SALE-FIX-' . $sale['invoice_no'],
            'customer_id' => $customerId,
            'notes' => 'Fixed sale payment - all sales should be paid',
            'payment_status' => 'completed',
            'status' => 'active',
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);

        // Create ledger entry for payment
        DB::table('ledgers')->insert([
            'contact_type' => 'customer',
            'contact_id' => $customerId,
            'transaction_type' => 'payments',
            'transaction_date' => $paymentDate,
            'reference_no' => 'SALE-FIX-' . $sale['invoice_no'],
            'debit' => 0,
            'credit' => $sale['amount'],
            'notes' => 'Payment for sale ' . $sale['invoice_no'],
            'status' => 'active',
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);

        // Update sale record
        DB::table('sales')->where('id', $sale['id'])->update([
            'total_paid' => $sale['amount'],
            'total_due' => 0,
            'payment_status' => 'paid',
            'updated_at' => $paymentDate,
        ]);
    }

    echo "\nStep 4: Calculating correct customer balance...\n";

    // Calculate balance from active ledger entries
    $ledgerBalance = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->selectRaw('SUM(debit) - SUM(credit) as balance')
        ->first()
        ->balance;

    echo "  - Calculated ledger balance: $ledgerBalance\n";

    // Update customer balance
    DB::table('customers')->where('id', $customerId)->update([
        'current_balance' => $ledgerBalance,
        'updated_at' => $paymentDate,
    ]);

    echo "\nStep 5: Verification...\n";

    // Verify customer balance
    $customer = DB::table('customers')->where('id', $customerId)->first();
    echo "  - Customer opening balance: {$customer->opening_balance}\n";
    echo "  - Customer current balance: {$customer->current_balance}\n";

    // Verify sales
    $sales = DB::table('sales')->where('customer_id', $customerId)->get(['invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status']);
    echo "\n  Sales status:\n";
    foreach ($sales as $sale) {
        echo "    {$sale->invoice_no}: Total={$sale->final_total}, Paid={$sale->total_paid}, Due={$sale->total_due}, Status={$sale->payment_status}\n";
    }

    // Verify ledger balance
    $activeLedgers = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->get(['transaction_type', 'debit', 'credit']);

    echo "\n  Active ledger entries:\n";
    $runningBalance = 0;
    foreach ($activeLedgers as $ledger) {
        $runningBalance += $ledger->debit - $ledger->credit;
        echo "    {$ledger->transaction_type}: Debit={$ledger->debit}, Credit={$ledger->credit}\n";
    }
    echo "  Running balance: $runningBalance\n";

    if ($customer->current_balance == $customer->opening_balance && $runningBalance == $customer->opening_balance) {
        echo "\n✓ SUCCESS: Customer balance is correct! Current balance ({$customer->current_balance}) equals opening balance ({$customer->opening_balance})\n";
        echo "✓ All sales are now paid\n";
        echo "✓ Only opening balance remains as due\n";

        DB::commit();
        echo "\nChanges committed to database.\n";
    } else {
        echo "\n✗ ERROR: Balance mismatch!\n";
        echo "  Expected: {$customer->opening_balance}\n";
        echo "  Current: {$customer->current_balance}\n";
        echo "  Ledger: $runningBalance\n";
        DB::rollBack();
        echo "\nChanges rolled back.\n";
    }

} catch (Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Changes rolled back.\n";
}
