<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== COMPLETE CHECK - CUSTOMER 958 ===\n\n";

// Get current state
$customer = DB::table('customers')->where('id', 958)->first();
$ledgerBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first()->balance ?? 0;

echo "CURRENT STATUS:\n";
echo str_repeat("=", 100) . "\n";
echo "Customer: {$customer->name}\n";
echo "Ledger Balance (Active entries): Rs " . number_format($ledgerBalance, 2) . " DUE\n\n";

// Calculate from tables
$totalSales = DB::table('sales')
    ->where('customer_id', 958)
    ->where('transaction_type', 'invoice')
    ->sum('final_total');

$totalReturns = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->sum('return_total');

$totalPayments = DB::table('payments')
    ->where('customer_id', 958)
    ->where('status', 'active')
    ->sum('amount');

$calculatedBalance = $totalSales - $totalReturns - $totalPayments;

echo "CALCULATION FROM TABLES:\n";
echo "  Total Sales: Rs " . number_format($totalSales, 2) . "\n";
echo "  Less Returns: Rs " . number_format($totalReturns, 2) . "\n";
echo "  Less Payments: Rs " . number_format($totalPayments, 2) . "\n";
echo "  = Balance: Rs " . number_format($calculatedBalance, 2) . "\n\n";

$difference = $ledgerBalance - $calculatedBalance;
if (abs($difference) > 1) {
    echo "⚠️  MISMATCH: Rs " . number_format(abs($difference), 2) . " difference!\n";
    echo "Ledger shows: Rs " . number_format($ledgerBalance, 2) . "\n";
    echo "Tables show: Rs " . number_format($calculatedBalance, 2) . "\n\n";
} else {
    echo "✓ Ledger and tables match!\n\n";
}

// Check unpaid sales
$unpaidSales = DB::table('sales')
    ->where('customer_id', 958)
    ->where('transaction_type', 'invoice')
    ->whereIn('payment_status', ['Due', 'Partial'])
    ->get();

echo "\nUNPAID SALES:\n";
echo str_repeat("=", 100) . "\n";
if ($unpaidSales->isEmpty()) {
    echo "✓ No unpaid sales\n\n";
} else {
    foreach ($unpaidSales as $sale) {
        printf("%-15s | Final: Rs %10.2f | Paid: Rs %10.2f | Due: Rs %10.2f | [%s]\n",
            $sale->invoice_no,
            $sale->final_total,
            $sale->total_paid,
            $sale->total_due,
            $sale->payment_status
        );
    }
    echo "\nTotal unpaid: Rs " . number_format($unpaidSales->sum('total_due'), 2) . "\n\n";
}

// Check unapplied returns
$unappliedReturns = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->where('total_due', '>', 0)
    ->get();

echo "\nUNAPPLIED RETURNS (Available as credit):\n";
echo str_repeat("=", 100) . "\n";
if ($unappliedReturns->isEmpty()) {
    echo "✓ No unapplied returns\n\n";
} else {
    foreach ($unappliedReturns as $return) {
        printf("%-15s | Total: Rs %10.2f | Applied: Rs %10.2f | Remaining: Rs %10.2f\n",
            $return->invoice_number,
            $return->return_total,
            $return->total_paid,
            $return->total_due
        );
    }
    echo "\nTotal available credit: Rs " . number_format($unappliedReturns->sum('total_due'), 2) . "\n\n";
}

// Net amount customer needs to pay
$netToPay = $unpaidSales->sum('total_due') - $unappliedReturns->sum('total_due');
echo "NET AMOUNT TO PAY: Rs " . number_format(max(0, $netToPay), 2) . "\n";
if ($netToPay < 0) {
    echo "  (Returns exceed dues by Rs " . number_format(abs($netToPay), 2) . " - would create advance)\n";
}
echo "\n";

// Check for the -57,390 issue in ledger history
echo "\nLEDGER HISTORY - POINTS WHERE BALANCE WENT NEGATIVE:\n";
echo str_repeat("=", 100) . "\n";

$ledgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

$runningBalance = 0;
$negativePoints = [];

foreach ($ledgers as $ledger) {
    $runningBalance += ($ledger->debit - $ledger->credit);
    if ($runningBalance < -1) {
        $negativePoints[] = [
            'id' => $ledger->id,
            'date' => $ledger->transaction_date,
            'ref' => $ledger->reference_no,
            'type' => $ledger->transaction_type,
            'debit' => $ledger->debit,
            'credit' => $ledger->credit,
            'balance' => $runningBalance
        ];
    }
}

if (empty($negativePoints)) {
    echo "✓ No negative balance points found\n\n";
} else {
    echo "Found " . count($negativePoints) . " points where balance went negative (advance):\n\n";

    foreach ($negativePoints as $i => $point) {
        if ($i < 5 || abs($point['balance'] + 57390) < 1 || abs($point['balance'] + 52920) < 1) {
            printf("ID %-6d | %s | %-20s | D: %10.2f | C: %10.2f | Balance: %12.2f",
                $point['id'],
                substr($point['date'], 0, 10),
                substr($point['ref'], 0, 20),
                $point['debit'],
                $point['credit'],
                $point['balance']
            );

            if (abs($point['balance'] + 57390) < 1) {
                echo " ← THIS IS THE -57,390";
            }
            echo "\n";
        }
    }
    echo "\n";
}

// Check BLK-S0079 specifically
echo "\nBLK-S0079 ANALYSIS (The payment that created -57,390):\n";
echo str_repeat("=", 100) . "\n";

$blk79Payments = DB::table('payments')
    ->where('reference_no', 'BLK-S0079')
    ->where('customer_id', 958)
    ->get();

if ($blk79Payments->isEmpty()) {
    echo "✓ BLK-S0079 not found (may have been deleted)\n\n";
} else {
    echo "Payment Date: " . $blk79Payments->first()->payment_date . "\n\n";

    $totalBlk79 = 0;
    foreach ($blk79Payments as $payment) {
        $sale = DB::table('sales')->find($payment->reference_id);
        echo "Payment #{$payment->id}:\n";
        echo "  Amount: Rs " . number_format($payment->amount, 2) . "\n";
        echo "  For Sale: {$sale->invoice_no} (ID: {$sale->id})\n";
        echo "  Sale Date: {$sale->sales_date}\n";
        echo "  Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";

        // Check all payments for this sale
        $allPayments = DB::table('payments')
            ->where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', 'active')
            ->get();

        echo "  All payments for {$sale->invoice_no}:\n";
        $saleTotal = 0;
        foreach ($allPayments as $p) {
            echo "    - Payment #{$p->id}: Rs " . number_format($p->amount, 2) . " on {$p->payment_date} ({$p->reference_no})\n";
            $saleTotal += $p->amount;
        }
        echo "  Total paid: Rs " . number_format($saleTotal, 2) . "\n";

        if ($saleTotal > $sale->final_total) {
            echo "  ⚠️  OVERPAID by Rs " . number_format($saleTotal - $sale->final_total, 2) . "\n";
        }
        echo "\n";

        $totalBlk79 += $payment->amount;
    }

    echo "Total BLK-S0079: Rs " . number_format($totalBlk79, 2) . "\n\n";

    // What was owed at that time?
    $balanceBeforeBlk79 = DB::table('ledgers')
        ->where('contact_id', 958)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->where('transaction_date', '<', $blk79Payments->first()->payment_date)
        ->selectRaw('SUM(debit) - SUM(credit) as balance')
        ->first()->balance ?? 0;

    echo "Balance before BLK-S0079: Rs " . number_format($balanceBeforeBlk79, 2) . "\n";
    echo "BLK-S0079 paid: Rs " . number_format($totalBlk79, 2) . "\n";
    echo "Balance after: Rs " . number_format($balanceBeforeBlk79 - $totalBlk79, 2) . "\n\n";

    if (($balanceBeforeBlk79 - $totalBlk79) < -1) {
        $overpayment = $totalBlk79 - $balanceBeforeBlk79;
        echo "⚠️  PROBLEM: BLK-S0079 overpaid by Rs " . number_format($overpayment, 2) . "\n";
        echo "This created a false 'advance' balance.\n\n";

        echo "ROOT CAUSE: Customer owed Rs " . number_format($balanceBeforeBlk79, 2) .
             " but paid Rs " . number_format($totalBlk79, 2) . "\n\n";
    }
}

echo "\nRECOMMENDATION:\n";
echo str_repeat("=", 100) . "\n";

if ($ledgerBalance > 0 && $ledgerBalance == $netToPay) {
    echo "✓ Everything looks correct!\n";
    echo "  Customer owes: Rs " . number_format($ledgerBalance, 2) . "\n";
    echo "  This matches: Sales dues (Rs " . number_format($unpaidSales->sum('total_due'), 2) .
         ") minus returns (Rs " . number_format($unappliedReturns->sum('total_due'), 2) . ")\n\n";
    echo "  Just process payment via bulk payment screen for Rs " . number_format($netToPay, 2) . "\n";
} elseif (!empty($negativePoints) && !empty($blk79Payments)) {
    echo "⚠️  BLK-S0079 created a false advance. This needs to be fixed.\n\n";
    echo "SOLUTION: Delete BLK-S0079 payment batch and let customer pay correctly.\n";
    echo "Run: php fix_blk_s0079_overpayment.php and choose option 1\n";
} else {
    echo "System appears healthy. The -57,390 was temporary and has been absorbed.\n";
    echo "Current balance of Rs " . number_format($ledgerBalance, 2) . " is correct.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
