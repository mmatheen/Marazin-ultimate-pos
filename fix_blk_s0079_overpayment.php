<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX BLK-S0079 OVERPAYMENT (Rs 57,390) ===\n\n";

echo "PROBLEM IDENTIFIED:\n";
echo str_repeat("=", 100) . "\n\n";
echo "Customer balance before BLK-S0079: Rs 38,410\n";
echo "BLK-S0079 payment amount: Rs 95,800\n";
echo "Overpayment (false advance): Rs 57,390\n\n";

echo "BLK-S0079 tried to pay for:\n";
echo "- MLX-308: Rs 79,800\n";
echo "- MLX-309: Rs 16,000\n";
echo "Total: Rs 95,800\n\n";

echo "But customer only owed Rs 38,410 at that time!\n\n";

echo "SOLUTION: Delete the EXCESS payments that created the false advance.\n\n";

echo str_repeat("=", 100) . "\n\n";

// Check what the customer owed on Jan 17, 2026
$salesUpToJan17 = DB::table('sales')
    ->where('customer_id', 958)
    ->where('sales_date', '<=', '2026-01-17')
    ->where('transaction_type', 'invoice')
    ->get();

$paymentsBeforeBlk79 = DB::table('payments')
    ->where('customer_id', 958)
    ->where('payment_date', '<', '2026-01-17 16:56:47')
    ->where('status', 'active')
    ->sum('amount');

$returnsUpToJan17 = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->where('return_date', '<=', '2026-01-17')
    ->sum('return_total');

echo "Customer balance calculation on Jan 17, 2026 at 16:56:\n";
echo "------------------------------------------------------\n";
echo "Total sales: Rs " . number_format($salesUpToJan17->sum('final_total'), 2) . "\n";
echo "Less returns: Rs " . number_format($returnsUpToJan17, 2) . "\n";
echo "Less payments before BLK-S0079: Rs " . number_format($paymentsBeforeBlk79, 2) . "\n";
$actualOwed = $salesUpToJan17->sum('final_total') - $returnsUpToJan17 - $paymentsBeforeBlk79;
echo "Balance owed: Rs " . number_format($actualOwed, 2) . "\n\n";

// Check from ledger
$ledgerBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->where('id', '<', 1597)
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first();

echo "Ledger balance before BLK-S0079: Rs " . number_format($ledgerBalance->balance, 2) . "\n\n";

// Check if MLX-308 and MLX-309 existed at that time
$mlx308 = DB::table('sales')->where('invoice_no', 'MLX-308')->first();
$mlx309 = DB::table('sales')->where('invoice_no', 'MLX-309')->first();

echo "MLX-308 sale date: {$mlx308->sales_date}\n";
echo "MLX-309 sale date: {$mlx309->sales_date}\n";
echo "BLK-S0079 payment date: 2026-01-17 16:56:47\n\n";

if ($mlx308->sales_date > '2026-01-17 16:56:47' || $mlx309->sales_date > '2026-01-17 16:56:47') {
    echo "⚠️  ONE OR BOTH SALES WERE CREATED *AFTER* THE PAYMENT!\n";
    echo "This is a backdating issue.\n\n";
}

echo "\nRECOMMENDED FIX:\n";
echo str_repeat("=", 100) . "\n\n";

echo "OPTION 1: DELETE the entire BLK-S0079 payment batch\n";
echo "----------------------------------------------------\n";
echo "This will remove the Rs 95,800 overpayment.\n";
echo "Then recreate correct payments for what was actually owed (Rs 38,410).\n\n";

echo "OPTION 2: Reduce BLK-S0079 to match what was owed\n";
echo "--------------------------------------------------\n";
echo "Keep payment for what was actually due (Rs 38,410).\n";
echo "Delete the excess Rs 57,390.\n\n";

echo "Which option do you prefer?\n";
echo "1 = Delete entire BLK-S0079\n";
echo "2 = Reduce to actual amount owed\n";
echo "3 = Cancel (no changes)\n\n";
echo "Enter choice (1/2/3): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$choice = trim($line);
fclose($handle);

if ($choice == '1') {
    echo "\nDeleting entire BLK-S0079 payment batch...\n\n";

    DB::beginTransaction();
    try {
        $blk79Payments = DB::table('payments')
            ->where('reference_no', 'BLK-S0079')
            ->where('customer_id', 958)
            ->get();

        foreach ($blk79Payments as $payment) {
            echo "Deleting Payment #{$payment->id} (Rs " . number_format($payment->amount, 2) . ")...\n";

            // Delete payment
            DB::table('payments')->where('id', $payment->id)->delete();

            // Delete ledger entries
            $deleted = DB::table('ledgers')
                ->where('reference_no', 'BLK-S0079')
                ->where('contact_id', 958)
                ->where('credit', $payment->amount)
                ->delete();
            echo "  ✓ Deleted {$deleted} ledger entry(ies)\n";

            // Update sale
            if ($payment->reference_id) {
                $totalPaid = DB::table('payments')
                    ->where('reference_id', $payment->reference_id)
                    ->where('payment_type', 'sale')
                    ->where('status', 'active')
                    ->sum('amount');

                $sale = DB::table('sales')->find($payment->reference_id);
                $newStatus = $totalPaid >= $sale->final_total ? 'Paid' :
                            ($totalPaid > 0 ? 'Partial' : 'Due');

                DB::table('sales')
                    ->where('id', $payment->reference_id)
                    ->update([
                        'total_paid' => $totalPaid,
                        'payment_status' => $newStatus
                    ]);
                echo "  ✓ Updated sale status\n";
            }
        }

        DB::commit();
        echo "\n✅ BLK-S0079 deleted successfully!\n\n";
        echo "The false advance of Rs 57,390 has been removed.\n";
        echo "Customer balance should now be correct.\n";

    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }

} elseif ($choice == '2') {
    echo "\nReducing BLK-S0079 to actual amount owed...\n\n";
    echo "This option needs manual decision on which payments to keep.\n";
    echo "Customer owed Rs " . number_format($ledgerBalance->balance, 2) . "\n\n";

    echo "Current BLK-S0079 payments:\n";
    $blk79Payments = DB::table('payments')
        ->where('reference_no', 'BLK-S0079')
        ->where('customer_id', 958)
        ->get();

    foreach ($blk79Payments as $p) {
        $sale = DB::table('sales')->find($p->reference_id);
        echo "  #{$p->id}: Rs " . number_format($p->amount, 2) . " for {$sale->invoice_no}\n";
    }

    echo "\nRecommendation: Delete payments #329 and #331 (for MLX-308), keep only #330 (MLX-309).\n";
    echo "This approach is complex. Consider using Option 1 instead.\n";

} else {
    echo "\nNo changes made.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Complete.\n";
