<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX INCORRECT ADVANCE - CUSTOMER 958 ===\n\n";

// The customer did NOT give advance, so where did this come from?

echo "STEP 1: TRACE THE FALSE ADVANCE\n";
echo str_repeat("=", 100) . "\n\n";

// Check the BLK-S0026 bulk payment that created the issue
$blkPayments = DB::table('payments')
    ->where('reference_no', 'BLK-S0026')
    ->where('customer_id', 958)
    ->get();

echo "BLK-S0026 Payment Details:\n";
echo "-------------------------\n";
$totalPaid = 0;
foreach ($blkPayments as $payment) {
    $sale = DB::table('sales')->find($payment->reference_id);
    echo "Payment #{$payment->id}:\n";
    echo "  Amount paid: Rs " . number_format($payment->amount, 2) . "\n";
    echo "  For Sale: {$sale->invoice_no} (ID: {$sale->id})\n";
    echo "  Sale amount: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  Difference: Rs " . number_format($sale->final_total - $payment->amount, 2) . "\n\n";
    $totalPaid += $payment->amount;
}
echo "Total paid in BLK-S0026: Rs " . number_format($totalPaid, 2) . "\n\n";

// Check what was owed at that time
$salesAtTime = DB::table('sales')
    ->where('customer_id', 958)
    ->where('sales_date', '<=', '2025-12-29')
    ->where('transaction_type', 'invoice')
    ->get();

echo "Sales that existed on Dec 29, 2025:\n";
echo "-----------------------------------\n";
$totalOwed = 0;
foreach ($salesAtTime as $sale) {
    echo "  {$sale->invoice_no}: Rs " . number_format($sale->final_total, 2) . "\n";
    $totalOwed += $sale->final_total;
}
echo "\nTotal owed: Rs " . number_format($totalOwed, 2) . "\n";
echo "Total paid: Rs " . number_format($totalPaid, 2) . "\n";
echo "Difference: Rs " . number_format($totalOwed - $totalPaid, 2) . "\n\n";

if ($totalPaid > $totalOwed) {
    echo "⚠️  OVERPAYMENT of Rs " . number_format($totalPaid - $totalOwed, 2) . " was recorded!\n";
    echo "This created the false 'advance' balance.\n\n";
} else {
    echo "✓ Payment matches what was owed.\n\n";
}

// Check Sale MLX-269 specifically - this is the problematic one
echo "\nSTEP 2: ANALYZE SALE MLX-269 (The Problem Sale)\n";
echo str_repeat("=", 100) . "\n\n";

$mlx269 = DB::table('sales')->where('invoice_no', 'MLX-269')->first();
$mlx269Payment = DB::table('payments')
    ->where('reference_id', $mlx269->id)
    ->where('payment_type', 'sale')
    ->get();

echo "Sale MLX-269 Current Status:\n";
echo "  ID: {$mlx269->id}\n";
echo "  Invoice: {$mlx269->invoice_no}\n";
echo "  Date: {$mlx269->sales_date}\n";
echo "  Final Total: Rs " . number_format($mlx269->final_total, 2) . "\n";
echo "  Total Paid: Rs " . number_format($mlx269->total_paid, 2) . "\n";
echo "  Total Due: Rs " . number_format($mlx269->total_due, 2) . "\n";
echo "  Status: {$mlx269->payment_status}\n\n";

echo "Payments for MLX-269:\n";
$totalPaymentsForThis = 0;
foreach ($mlx269Payment as $p) {
    echo "  Payment #{$p->id}: Rs " . number_format($p->amount, 2) . " on {$p->payment_date} [{$p->status}]\n";
    if ($p->status === 'active') {
        $totalPaymentsForThis += $p->amount;
    }
}
echo "  Total active payments: Rs " . number_format($totalPaymentsForThis, 2) . "\n\n";

// Check ledger history for MLX-269
$mlx269Ledgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('reference_no', 'LIKE', '%MLX-269%')
    ->orderBy('id')
    ->get();

echo "Ledger History for MLX-269:\n";
echo "---------------------------\n";
foreach ($mlx269Ledgers as $ledger) {
    printf("ID %-6d | %s | %-30s | Debit: %10.2f | Credit: %10.2f | [%-8s]\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->reference_no,
        $ledger->debit,
        $ledger->credit,
        $ledger->status
    );
}
echo "\n";

// Find the issue
echo "\nSTEP 3: IDENTIFY THE ROOT CAUSE\n";
echo str_repeat("=", 100) . "\n\n";

$originalLedger = $mlx269Ledgers->where('reference_no', 'MLX-269')->where('status', 'reversed')->first();
$reversalLedger = $mlx269Ledgers->where('reference_no', 'LIKE', 'MLX-269-REV-%')->where('status', 'reversed')->first();

if ($originalLedger && $reversalLedger) {
    echo "FOUND THE PROBLEM:\n";
    echo "-----------------\n";
    echo "1. Original sale MLX-269: Rs " . number_format($originalLedger->debit, 2) . " (REVERSED)\n";
    echo "2. Reversal entry: Rs " . number_format($reversalLedger->credit, 2) . " (REVERSED)\n";
    echo "3. Current sale amount: Rs " . number_format($mlx269->final_total, 2) . "\n";
    echo "4. Payment amount: Rs " . number_format($totalPaymentsForThis, 2) . "\n\n";

    $wrongAmount = $totalPaymentsForThis - $mlx269->final_total;

    if ($wrongAmount == 0) {
        echo "✓ Payment matches current sale amount.\n";
        echo "The issue is that REVERSED ledger entries are affecting the balance calculation.\n\n";

        echo "Solution: Clean up REVERSED ledger entries or ensure they're excluded from balance.\n";
    } else {
        echo "⚠️  Payment is Rs " . number_format(abs($wrongAmount), 2) . " " . ($wrongAmount > 0 ? "MORE" : "LESS") . " than sale amount!\n\n";

        if ($wrongAmount > 0) {
            echo "This excess payment created the false 'advance' balance.\n\n";
            echo "Solution Options:\n";
            echo "1. Reduce the payment amount to match the sale\n";
            echo "2. Increase the sale amount to match the payment\n";
            echo "3. Create a separate advance payment record\n";
        }
    }
}

echo "\n\nSTEP 4: CHECK IF REVERSED ENTRIES ARE BEING COUNTED\n";
echo str_repeat("=", 100) . "\n\n";

$activeBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first();

$allBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first();

echo "Balance Calculation:\n";
echo "  ACTIVE entries only: Rs " . number_format($activeBalance->balance ?? 0, 2) . "\n";
echo "  ALL entries (including reversed): Rs " . number_format($allBalance->balance ?? 0, 2) . "\n\n";

if (abs($activeBalance->balance - $allBalance->balance) > 1) {
    echo "⚠️  REVERSED entries are affecting the balance!\n";
    echo "Difference: Rs " . number_format(abs($activeBalance->balance - $allBalance->balance), 2) . "\n\n";
}

echo "\nSTEP 5: PROPOSED FIX\n";
echo str_repeat("=", 100) . "\n\n";

echo "Based on the analysis, here's what needs to be fixed:\n\n";

// Check payments vs sales for BLK-S0026
$payment216 = $blkPayments->firstWhere('id', 216);
$payment217 = $blkPayments->firstWhere('id', 217);

if ($payment216) {
    $sale683 = DB::table('sales')->find($payment216->reference_id);
    echo "ISSUE 1: Payment #216 (BLK-S0026)\n";
    echo "  Payment amount: Rs " . number_format($payment216->amount, 2) . "\n";
    echo "  Sale MLX-269 (#{$sale683->id}) amount: Rs " . number_format($sale683->final_total, 2) . "\n";

    if ($payment216->amount == $sale683->final_total) {
        echo "  ✓ These match - no issue here\n\n";
    } else {
        $diff = $payment216->amount - $sale683->final_total;
        echo "  ⚠️  Payment is Rs " . number_format(abs($diff), 2) . " " . ($diff > 0 ? "MORE" : "LESS") . " than sale\n\n";

        echo "  FIX: Adjust payment #216 to Rs " . number_format($sale683->final_total, 2) . "\n\n";
    }
}

// Check BLK-S0079 which created the -57,390
$blk79Payments = DB::table('payments')
    ->where('reference_no', 'BLK-S0079')
    ->where('customer_id', 958)
    ->get();

echo "\nISSUE 2: BLK-S0079 Payments (Created the -57,390 advance)\n";
echo "--------------------------------------------------------\n";
$total79 = 0;
$sales79Total = 0;
foreach ($blk79Payments as $p) {
    $sale = DB::table('sales')->find($p->reference_id);
    echo "  Payment #{$p->id}: Rs " . number_format($p->amount, 2) . " for {$sale->invoice_no} (Rs " . number_format($sale->final_total, 2) . ")\n";
    $total79 += $p->amount;
    $sales79Total += $sale->final_total;
}
echo "\n  Total paid: Rs " . number_format($total79, 2) . "\n";
echo "  Total owed: Rs " . number_format($sales79Total, 2) . "\n";
echo "  Difference: Rs " . number_format($total79 - $sales79Total, 2) . "\n\n";

if ($total79 > $sales79Total) {
    echo "  ⚠️  This is the overpayment that created the false advance!\n\n";

    echo "  CAUSE: One of these payments is for the WRONG amount.\n";
    echo "  Most likely: Payment #331 (Rs 78,600) is incorrect.\n\n";

    // Check what MLX-308 amount should be
    $mlx308 = DB::table('sales')->where('invoice_no', 'MLX-308')->first();
    echo "  Sale MLX-308 current amount: Rs " . number_format($mlx308->final_total, 2) . "\n";

    $payment331 = $blk79Payments->firstWhere('id', 331);
    if ($payment331 && $payment331->reference_id == $mlx308->id) {
        echo "  Payment #331 amount: Rs " . number_format($payment331->amount, 2) . "\n";
        $diff = $payment331->amount - $mlx308->final_total;
        if ($diff != 0) {
            echo "  ⚠️  Payment is Rs " . number_format(abs($diff), 2) . " " . ($diff > 0 ? "TOO MUCH" : "TOO LITTLE") . "\n\n";
            echo "  FIX: Reduce payment #331 from Rs " . number_format($payment331->amount, 2) .
                 " to Rs " . number_format($mlx308->final_total, 2) . "\n";
            echo "       This will eliminate the false advance of Rs " . number_format(abs($diff), 2) . "\n\n";
        }
    }
}

echo "\nDo you want to apply the fix? (YES/NO): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if (strtoupper($answer) === 'YES') {
    echo "\n\nAPPLYING FIX...\n";
    echo str_repeat("=", 100) . "\n\n";

    DB::beginTransaction();

    try {
        // Find the overpayment in BLK-S0079
        $payment331 = DB::table('payments')->where('id', 331)->first();
        if ($payment331) {
            $sale = DB::table('sales')->find($payment331->reference_id);
            $correctAmount = $sale->final_total;
            $wrongAmount = $payment331->amount;
            $difference = $wrongAmount - $correctAmount;

            if ($difference > 0.01) {
                echo "Fixing Payment #331:\n";
                echo "  Current amount: Rs " . number_format($wrongAmount, 2) . "\n";
                echo "  Correct amount: Rs " . number_format($correctAmount, 2) . "\n";
                echo "  Removing: Rs " . number_format($difference, 2) . "\n\n";

                // Update payment amount
                DB::table('payments')
                    ->where('id', 331)
                    ->update(['amount' => $correctAmount]);

                echo "✓ Payment #331 updated\n\n";

                // Update the corresponding ledger entry
                $ledgerEntry = DB::table('ledgers')
                    ->where('reference_no', 'BLK-S0079')
                    ->where('contact_id', 958)
                    ->where('credit', $wrongAmount)
                    ->first();

                if ($ledgerEntry) {
                    DB::table('ledgers')
                        ->where('id', $ledgerEntry->id)
                        ->update(['credit' => $correctAmount]);

                    echo "✓ Ledger entry #{$ledgerEntry->id} updated\n\n";
                }

                // Recalculate the sale's total_paid
                $saleId = $payment331->reference_id;
                $totalPaymentsForSale = DB::table('payments')
                    ->where('reference_id', $saleId)
                    ->where('payment_type', 'sale')
                    ->where('status', 'active')
                    ->sum('amount');

                DB::table('sales')
                    ->where('id', $saleId)
                    ->update([
                        'total_paid' => $totalPaymentsForSale,
                        'payment_status' => $totalPaymentsForSale >= $sale->final_total ? 'Paid' :
                                          ($totalPaymentsForSale > 0 ? 'Partial' : 'Due')
                    ]);

                echo "✓ Sale #{$saleId} updated\n\n";

                DB::commit();

                echo "\n✅ FIX APPLIED SUCCESSFULLY!\n\n";
                echo "The false advance of Rs " . number_format($difference, 2) . " has been removed.\n";
                echo "Please verify the customer balance is now correct.\n";
            } else {
                echo "⚠️  Payment #331 appears to be correct. No fix needed.\n";
                DB::rollBack();
            }
        } else {
            echo "⚠️  Payment #331 not found.\n";
            DB::rollBack();
        }

    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "Transaction rolled back. No changes made.\n";
    }
} else {
    echo "\nFix cancelled. No changes made.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Analysis complete.\n";
