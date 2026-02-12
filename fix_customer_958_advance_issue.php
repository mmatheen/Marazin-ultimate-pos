<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX CUSTOMER 958 ADVANCE BALANCE ISSUE (-Rs 57,390) ===\n\n";

// Step 1: Identify where the -57,390 comes from
echo "STEP 1: ANALYZING THE -57,390 ADVANCE BALANCE\n";
echo str_repeat("=", 100) . "\n\n";

$ledgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->orderBy('id')
    ->get();

echo "Tracing the ledger to find where -57,390 appears:\n\n";

$runningBalance = 0;
$negativeBalancePoints = [];

foreach ($ledgers as $ledger) {
    $runningBalance += ($ledger->debit - $ledger->credit);

    if (round($runningBalance) == -57390 || round($runningBalance) == -52920 || $runningBalance < -50000) {
        $negativeBalancePoints[] = [
            'id' => $ledger->id,
            'date' => $ledger->transaction_date,
            'reference' => $ledger->reference_no,
            'type' => $ledger->transaction_type,
            'debit' => $ledger->debit,
            'credit' => $ledger->credit,
            'balance' => $runningBalance,
            'status' => $ledger->status,
            'notes' => $ledger->notes
        ];
    }
}

if (empty($negativeBalancePoints)) {
    echo "✓ No significant negative balance found in current active ledger.\n";
    echo "  The -57,390 might be from reversed/historical entries.\n\n";
} else {
    echo "Found " . count($negativeBalancePoints) . " points with large negative balance:\n\n";
    foreach ($negativeBalancePoints as $point) {
        printf("ID %-6d | %s | %-20s | %-15s | Debit: %10.2f | Credit: %10.2f | Balance: %12.2f | [%s]\n",
            $point['id'],
            $point['date'],
            $point['reference'],
            $point['type'],
            $point['debit'],
            $point['credit'],
            $point['balance'],
            $point['status']
        );
        echo "  Notes: " . substr($point['notes'], 0, 80) . "\n\n";
    }
}

// Step 2: Check BLK-S0026 payments specifically
echo "\nSTEP 2: ANALYZING BLK-S0026 PAYMENTS\n";
echo str_repeat("=", 100) . "\n\n";

$blkPayments = DB::table('payments')
    ->where('customer_id', 958)
    ->where('reference_no', 'BLK-S0026')
    ->get();

echo "BLK-S0026 Payments:\n";
foreach ($blkPayments as $payment) {
    echo "  Payment ID: {$payment->id}\n";
    echo "  Amount: Rs " . number_format($payment->amount, 2) . "\n";
    echo "  Date: {$payment->payment_date}\n";
    echo "  Reference ID (Sale): {$payment->reference_id}\n";
    echo "  Status: {$payment->status}\n";

    // Check if the sale exists
    if ($payment->reference_id) {
        $sale = DB::table('sales')->where('id', $payment->reference_id)->first();
        if ($sale) {
            echo "  Sale: {$sale->invoice_no} | Final Total: Rs " . number_format($sale->final_total, 2) .
                 " | Total Paid: Rs " . number_format($sale->total_paid, 2) .
                 " | Due: Rs " . number_format($sale->total_due, 2) . "\n";
        } else {
            echo "  ❌ Sale not found!\n";
        }
    }
    echo "\n";
}

$totalBlkPayment = $blkPayments->sum('amount');
echo "Total BLK-S0026 payments: Rs " . number_format($totalBlkPayment, 2) . "\n\n";

// Step 3: Check what the customer owed at the time of BLK-S0026
echo "\nSTEP 3: WHAT DID CUSTOMER OWE ON 2025-12-29 (BLK-S0026 date)?\n";
echo str_repeat("=", 100) . "\n\n";

$salesBeforePayment = DB::table('sales')
    ->where('customer_id', 958)
    ->where('sales_date', '<=', '2025-12-29')
    ->where('transaction_type', 'invoice')
    ->get();

echo "Sales before or on 2025-12-29:\n";
$totalDueBefore = 0;
foreach ($salesBeforePayment as $sale) {
    printf("%-15s | Rs %10.2f | Paid: Rs %10.2f | Due: Rs %10.2f | [%s]\n",
        $sale->invoice_no,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due,
        $sale->payment_status
    );
    // Calculate what was due at that time (need to check payment dates)
}

// Check what balance would be correct
$paymentsBeforeBulk = DB::table('payments')
    ->where('customer_id', 958)
    ->where('payment_date', '<', '2025-12-29')
    ->where('status', 'active')
    ->sum('amount');

$salesTotalBefore = $salesBeforePayment->sum('final_total');

echo "\nCalculation:\n";
echo "  Sales total (up to 2025-12-29): Rs " . number_format($salesTotalBefore, 2) . "\n";
echo "  Payments before BLK-S0026: Rs " . number_format($paymentsBeforeBulk, 2) . "\n";
echo "  Balance before BLK-S0026: Rs " . number_format($salesTotalBefore - $paymentsBeforeBulk, 2) . "\n";
echo "  BLK-S0026 amount paid: Rs " . number_format($totalBlkPayment, 2) . "\n";
echo "  Balance after BLK-S0026: Rs " . number_format($salesTotalBefore - $paymentsBeforeBulk - $totalBlkPayment, 2) . "\n\n";

$overpayment = ($paymentsBeforeBulk + $totalBlkPayment) - $salesTotalBefore;
if ($overpayment > 0) {
    echo "  ⚠️  OVERPAYMENT: Rs " . number_format($overpayment, 2) . "\n";
    echo "  This created an advance credit.\n\n";
}

// Step 4: Check the ledger entries for BLK-S0026
echo "\nSTEP 4: LEDGER ENTRIES FOR BLK-S0026\n";
echo str_repeat("=", 100) . "\n\n";

$blkLedgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'BLK-S0026')
    ->get();

echo "Ledger entries for BLK-S0026:\n";
foreach ($blkLedgers as $ledger) {
    printf("ID %-6d | %s | %-15s | Debit: %10.2f | Credit: %10.2f | [%s] | %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $ledger->status,
        substr($ledger->notes, 0, 60)
    );
}

// Step 5: Identify the root cause
echo "\n\nSTEP 5: ROOT CAUSE ANALYSIS\n";
echo str_repeat("=", 100) . "\n\n";

// Check Sale MLX-269 which had Rs 57,390 payment
$saleMlx269 = DB::table('sales')->where('invoice_no', 'MLX-269')->first();
if ($saleMlx269) {
    echo "Sale MLX-269 Analysis:\n";
    echo "  ID: {$saleMlx269->id}\n";
    echo "  Date: {$saleMlx269->sales_date}\n";
    echo "  Final Total: Rs " . number_format($saleMlx269->final_total, 2) . "\n";
    echo "  Total Paid: Rs " . number_format($saleMlx269->total_paid, 2) . "\n";
    echo "  Total Due: Rs " . number_format($saleMlx269->total_due, 2) . "\n";
    echo "  Status: {$saleMlx269->payment_status}\n\n";

    // Check its ledger entries
    $mlx269Ledgers = DB::table('ledgers')
        ->where('contact_id', 958)
        ->where('reference_no', 'LIKE', '%MLX-269%')
        ->orderBy('id')
        ->get();

    echo "Ledger entries for MLX-269:\n";
    foreach ($mlx269Ledgers as $ledger) {
        printf("ID %-6d | %s | %-25s | %-15s | Debit: %10.2f | Credit: %10.2f | [%s]\n",
            $ledger->id,
            $ledger->transaction_date,
            $ledger->reference_no,
            $ledger->transaction_type,
            $ledger->debit,
            $ledger->credit,
            $ledger->status
        );
        echo "  Notes: " . $ledger->notes . "\n\n";
    }

    // Check if there are REVERSED entries
    $reversedMlx269 = $mlx269Ledgers->where('status', 'reversed');
    if ($reversedMlx269->count() > 0) {
        echo "⚠️  Found REVERSED entries for MLX-269!\n";
        echo "This sale was edited, which created reversal entries.\n\n";

        echo "The issue is:\n";
        echo "1. Original sale was Rs 60,240\n";
        echo "2. Customer paid Rs 57,390 against it\n";
        echo "3. Sale was later edited to a different amount\n";
        echo "4. This created confusion in the ledger\n\n";
    }
}

// Step 6: Proposed Fix
echo "\nSTEP 6: PROPOSED FIX OPTIONS\n";
echo str_repeat("=", 100) . "\n\n";

echo "Based on the analysis, here are the fix options:\n\n";

echo "OPTION 1: Clean Up Reversed Entries\n";
echo "-----------------------------------\n";
echo "If there are reversed/inactive ledger entries causing confusion:\n";
echo "- Keep only ACTIVE ledger entries\n";
echo "- Recalculate the balance from scratch\n";
echo "- This should eliminate the -57,390 phantom balance\n\n";

echo "OPTION 2: Verify Payment Allocations\n";
echo "------------------------------------\n";
echo "Check if payments in BLK-S0026 were allocated to correct sales:\n";
echo "- Payment 216 (Rs 57,390) -> Sale 683 (MLX-269)\n";
echo "- Payment 217 (Rs 25,300) -> Sale 674 (MLX-261)\n";
echo "- Verify these sales exist and amounts match\n\n";

echo "OPTION 3: Check Current Active Balance\n";
echo "--------------------------------------\n";
$currentBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first();

echo "Current ACTIVE ledger balance: Rs " . number_format($currentBalance->balance ?? 0, 2) . "\n\n";

if ($currentBalance && $currentBalance->balance > 0) {
    echo "✓ The actual balance is POSITIVE (customer owes money)\n";
    echo "✓ The -57,390 was likely a TEMPORARY advance that has been used up\n";
    echo "✓ No fix needed - this is historical data\n\n";
} elseif ($currentBalance && $currentBalance->balance < 0) {
    echo "⚠️  Customer has ADVANCE credit of Rs " . number_format(abs($currentBalance->balance), 2) . "\n";
    echo "This needs investigation:\n";
    echo "- Is this a real overpayment?\n";
    echo "- Should it be refunded?\n";
    echo "- Or applied to future invoices?\n\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Analysis complete. Review the findings above to determine the appropriate fix.\n";
