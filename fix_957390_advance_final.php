<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PINPOINT AND FIX THE -57,390 ADVANCE ===\n\n";

echo "STEP 1: EXACT MOMENT THE ADVANCE WAS CREATED\n";
echo str_repeat("=", 100) . "\n\n";

// Get ledger entries up to and including the -57,390 point
$ledgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->where('id', '<=', 1599)
    ->orderBy('id')
    ->get();

$runningBalance = 0;
echo "Ledger entries leading to -57,390:\n\n";

foreach ($ledgers as $ledger) {
    $prevBalance = $runningBalance;
    $runningBalance += ($ledger->debit - $ledger->credit);

    printf("ID %-6d | %s | %-20s | D: %10.2f | C: %10.2f | Balance: %12.2f -> %12.2f\n",
        $ledger->id,
        substr($ledger->transaction_date, 0, 10),
        substr($ledger->reference_no, 0, 20),
        $ledger->debit,
        $ledger->credit,
        $prevBalance,
        $runningBalance
    );

    if ($runningBalance < -50000) {
        echo "  ^^^^^^^ ADVANCE CREATED HERE ^^^^^^^\n";
    }
}

echo "\n\nSTEP 2: WHAT CAUSED THE ADVANCE AT ENTRY 1599?\n";
echo str_repeat("=", 100) . "\n\n";

// Entry 1599 is the BLK-S0079 payment of Rs 78,600
$entry1599 = DB::table('ledgers')->where('id', 1599)->first();
echo "Ledger Entry #1599:\n";
echo "  Date: {$entry1599->transaction_date}\n";
echo "  Reference: {$entry1599->reference_no}\n";
echo "  Credit: Rs " . number_format($entry1599->credit, 2) . "\n";
echo "  Notes: {$entry1599->notes}\n\n";

// Find the payment record
$paymentForEntry = DB::table('payments')
    ->where('reference_no', $entry1599->reference_no)
    ->where('customer_id', 958)
    ->where('amount', $entry1599->credit)
    ->first();

if ($paymentForEntry) {
    echo "This ledger entry is from Payment #{$paymentForEntry->id}:\n";
    echo "  Amount: Rs " . number_format($paymentForEntry->amount, 2) . "\n";
    echo "  For Sale ID: {$paymentForEntry->reference_id}\n";

    $sale = DB::table('sales')->find($paymentForEntry->reference_id);
    if ($sale) {
        echo "  Sale: {$sale->invoice_no}\n";
        echo "  Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";
        echo "  Sale Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
        echo "  Sale Due: Rs " . number_format($sale->total_due, 2) . "\n\n";

        // Check all payments for this sale
        $allPaymentsForSale = DB::table('payments')
            ->where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', 'active')
            ->get();

        echo "ALL Payments for {$sale->invoice_no}:\n";
        $totalPaidToSale = 0;
        foreach ($allPaymentsForSale as $p) {
            echo "  Payment #{$p->id}: Rs " . number_format($p->amount, 2) . " on {$p->payment_date}\n";
            $totalPaidToSale += $p->amount;
        }
        echo "  Total: Rs " . number_format($totalPaidToSale, 2) . "\n";
        echo "  Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";
        $overpayment = $totalPaidToSale - $sale->final_total;
        echo "  Overpayment: Rs " . number_format($overpayment, 2) . "\n\n";

        if ($overpayment > 0.01) {
            echo "⚠️  THIS SALE WAS OVERPAID BY Rs " . number_format($overpayment, 2) . "!\n\n";
        }
    }
}

echo "\nSTEP 3: WHAT WAS THE BALANCE BEFORE BLK-S0079?\n";
echo str_repeat("=", 100) . "\n\n";

// Calculate balance just before BLK-S0079
$balanceBeforeBLK79 = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->where('id', '<', 1597) // Before the first BLK-S0079 entry
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first();

echo "Balance before BLK-S0079: Rs " . number_format($balanceBeforeBLK79->balance ?? 0, 2) . "\n\n";

// Get all BLK-S0079 entries
$blk79Entries = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('reference_no', 'BLK-S0079')
    ->where('status', 'active')
    ->get();

echo "ALL BLK-S0079 Ledger Entries:\n";
$totalBlk79Credits = 0;
foreach ($blk79Entries as $entry) {
    printf("  ID %-6d | Credit: Rs %10.2f | %s\n", $entry->id, $entry->credit, $entry->notes);
    $totalBlk79Credits += $entry->credit;
}
echo "  Total credits in BLK-S0079: Rs " . number_format($totalBlk79Credits, 2) . "\n\n";

$balanceAfterBLK79 = $balanceBeforeBLK79->balance - $totalBlk79Credits;
echo "Balance after BLK-S0079: Rs " . number_format($balanceAfterBLK79, 2) . "\n\n";

if ($balanceAfterBLK79 < 0) {
    echo "⚠️  BLK-S0079 created an ADVANCE of Rs " . number_format(abs($balanceAfterBLK79), 2) . "\n\n";

    echo "This means customer paid MORE than they owed.\n";
    echo "Customer did NOT intend to give advance, so this is an ERROR.\n\n";
}

echo "\nSTEP 4: IDENTIFY WHICH SALE IN BLK-S0079 WAS OVERPAID\n";
echo str_repeat("=", 100) . "\n\n";

$blk79Payments = DB::table('payments')
    ->where('reference_no', 'BLK-S0079')
    ->where('customer_id', 958)
    ->get();

echo "BLK-S0079 Payment Breakdown:\n";
echo "----------------------------\n";
$issues = [];
foreach ($blk79Payments as $payment) {
    $sale = DB::table('sales')->find($payment->reference_id);

    // Get what customer owed on this sale at the time
    $priorPayments = DB::table('payments')
        ->where('reference_id', $sale->id)
        ->where('payment_type', 'sale')
        ->where('status', 'active')
        ->where('payment_date', '<', $payment->payment_date)
        ->sum('amount');

    $owedAtTime = $sale->final_total - $priorPayments;
    $thisPayment = $payment->amount;
    $overpaid = $thisPayment - $owedAtTime;

    echo "\nPayment #{$payment->id} for {$sale->invoice_no}:\n";
    echo "  Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  Already Paid: Rs " . number_format($priorPayments, 2) . "\n";
    echo "  Still Owed: Rs " . number_format($owedAtTime, 2) . "\n";
    echo "  This Payment: Rs " . number_format($thisPayment, 2) . "\n";

    if ($overpaid > 0.01) {
        echo "  ⚠️  OVERPAID by Rs " . number_format($overpaid, 2) . " !\n";
        $issues[] = [
            'payment_id' => $payment->id,
            'sale_id' => $sale->id,
            'sale_invoice' => $sale->invoice_no,
            'wrong_amount' => $thisPayment,
            'correct_amount' => $owedAtTime,
            'excess' => $overpaid,
            'ledger_id' => $blk79Entries->where('credit', $thisPayment)->first()->id ?? null
        ];
    } elseif ($overpaid < -0.01) {
        echo "  ⚠️  UNDERPAID by Rs " . number_format(abs($overpaid), 2) . "\n";
    } else {
        echo "  ✓ Correct amount\n";
    }
}

echo "\n\nSTEP 5: APPLY FIX\n";
echo str_repeat("=", 100) . "\n\n";

if (count($issues) > 0) {
    echo "Found " . count($issues) . " issue(s) to fix:\n\n";

    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". Payment #{$issue['payment_id']} for {$issue['sale_invoice']}:\n";
        echo "   Wrong: Rs " . number_format($issue['wrong_amount'], 2) . "\n";
        echo "   Correct: Rs " . number_format($issue['correct_amount'], 2) . "\n";
        echo "   Excess: Rs " . number_format($issue['excess'], 2) . "\n\n";
    }

    echo "Apply fix? (YES/NO): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $answer = trim($line);
    fclose($handle);

    if (strtoupper($answer) === 'YES') {
        echo "\nApplying fixes...\n\n";

        DB::beginTransaction();
        try {
            foreach ($issues as $issue) {
                echo "Fixing Payment #{$issue['payment_id']}...\n";

                // Update payment amount
                DB::table('payments')
                    ->where('id', $issue['payment_id'])
                    ->update(['amount' => $issue['correct_amount']]);
                echo "  ✓ Payment amount: Rs " . number_format($issue['wrong_amount'], 2) .
                     " -> Rs " . number_format($issue['correct_amount'], 2) . "\n";

                // Update ledger entry
                if ($issue['ledger_id']) {
                    DB::table('ledgers')
                        ->where('id', $issue['ledger_id'])
                        ->update(['credit' => $issue['correct_amount']]);
                    echo "  ✓ Ledger entry #{$issue['ledger_id']} updated\n";
                }

                // Recalculate sale's total_paid
                $totalPaid = DB::table('payments')
                    ->where('reference_id', $issue['sale_id'])
                    ->where('payment_type', 'sale')
                    ->where('status', 'active')
                    ->sum('amount');

                $sale = DB::table('sales')->find($issue['sale_id']);
                $newStatus = $totalPaid >= $sale->final_total ? 'Paid' :
                            ($totalPaid > 0 ? 'Partial' : 'Due');

                DB::table('sales')
                    ->where('id', $issue['sale_id'])
                    ->update([
                        'total_paid' => $totalPaid,
                        'payment_status' => $newStatus
                    ]);

                echo "  ✓ Sale {$issue['sale_invoice']} updated: total_paid=Rs " . number_format($totalPaid, 2) .
                     ", status={$newStatus}\n\n";
            }

            DB::commit();

            echo "✅ ALL FIXES APPLIED SUCCESSFULLY!\n\n";
            echo "The false advance of Rs " . number_format(array_sum(array_column($issues, 'excess')), 2) .
                 " has been removed.\n\n";

            // Recalculate final balance
            $newBalance = DB::table('ledgers')
                ->where('contact_id', 958)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->first();

            echo "New customer balance: Rs " . number_format($newBalance->balance ?? 0, 2) . "\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "All changes rolled back.\n";
        }
    } else {
        echo "Fix cancelled.\n";
    }
} else {
    echo "✓ No overpayment issues found. The balance might be correct.\n";
    echo "The -57,390 might be a legitimate advance that was later used.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Complete.\n";
