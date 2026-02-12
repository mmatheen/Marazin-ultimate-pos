<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Customer ID 958 Balance Analysis ===\n\n";

// Get all active ledger entries
$ledgers = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

echo "Active Ledger Entries:\n";
echo str_repeat("-", 120) . "\n";
printf("%-6s %-20s %-20s %-15s %-12s %-12s %-12s %s\n",
    "ID", "Date", "Reference", "Type", "Debit", "Credit", "Running Bal", "Notes");
echo str_repeat("-", 120) . "\n";

$totalDebit = 0;
$totalCredit = 0;
$runningBalance = 0;

foreach ($ledgers as $ledger) {
    $totalDebit += $ledger->debit;
    $totalCredit += $ledger->credit;
    $runningBalance += ($ledger->debit - $ledger->credit);

    printf("%-6d %-20s %-20s %-15s %12.2f %12.2f %12.2f %s\n",
        $ledger->id,
        $ledger->transaction_date,
        $ledger->reference_no,
        $ledger->transaction_type,
        $ledger->debit,
        $ledger->credit,
        $runningBalance,
        substr($ledger->notes ?? '', 0, 40)
    );
}

echo str_repeat("-", 120) . "\n";
printf("%-57s %12.2f %12.2f %12.2f\n", "TOTALS:", $totalDebit, $totalCredit, $runningBalance);
echo str_repeat("=", 120) . "\n\n";

// Get all sales
$sales = DB::table('sales')
    ->where('customer_id', 958)
    ->where('transaction_type', 'invoice')
    ->orderBy('id')
    ->get();

echo "\nSales Summary:\n";
echo str_repeat("-", 100) . "\n";
printf("%-6s %-15s %-15s %-12s %-12s %-12s %-15s\n",
    "ID", "Invoice", "Date", "Final Total", "Paid", "Due", "Status");
echo str_repeat("-", 100) . "\n";

$totalSales = 0;
$totalPaidInSales = 0;
$totalDueInSales = 0;

foreach ($sales as $sale) {
    $totalSales += $sale->final_total;
    $totalPaidInSales += $sale->total_paid;
    $totalDueInSales += $sale->total_due;

    printf("%-6d %-15s %-15s %12.2f %12.2f %12.2f %-15s\n",
        $sale->id,
        $sale->invoice_no,
        $sale->sales_date,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due,
        $sale->payment_status
    );
}

echo str_repeat("-", 100) . "\n";
printf("%-33s %12.2f %12.2f %12.2f\n", "TOTALS:", $totalSales, $totalPaidInSales, $totalDueInSales);
echo str_repeat("=", 100) . "\n\n";

// Get all payments
$payments = DB::table('payments')
    ->where('customer_id', 958)
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

echo "\nPayments Summary:\n";
echo str_repeat("-", 100) . "\n";
printf("%-6s %-15s %-15s %-12s %-15s %-10s\n",
    "ID", "Reference", "Date", "Amount", "Method", "Ref ID");
echo str_repeat("-", 100) . "\n";

$totalPayments = 0;

foreach ($payments as $payment) {
    $totalPayments += $payment->amount;

    printf("%-6d %-15s %-15s %12.2f %-15s %-10s\n",
        $payment->id,
        $payment->reference_no,
        $payment->payment_date,
        $payment->amount,
        $payment->payment_method,
        $payment->reference_id ?? 'N/A'
    );
}

echo str_repeat("-", 100) . "\n";
printf("%-33s %12.2f\n", "TOTAL PAYMENTS:", $totalPayments);
echo str_repeat("=", 100) . "\n\n";

// Get sale returns
$returns = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->orderBy('id')
    ->get();

echo "\nSale Returns:\n";
echo str_repeat("-", 100) . "\n";
printf("%-6s %-15s %-15s %-12s %-12s %-12s %-15s\n",
    "ID", "Return No", "Date", "Total", "Paid", "Due", "Status");
echo str_repeat("-", 100) . "\n";

$totalReturns = 0;
$totalReturnsPaid = 0;
$totalReturnsDue = 0;

foreach ($returns as $return) {
    $totalReturns += $return->return_total;
    $totalReturnsPaid += $return->total_paid;
    $totalReturnsDue += $return->total_due;

    printf("%-6d %-15s %-15s %12.2f %12.2f %12.2f %-15s\n",
        $return->id,
        $return->invoice_number,
        $return->return_date,
        $return->return_total,
        $return->total_paid,
        $return->total_due,
        $return->payment_status
    );
}

echo str_repeat("-", 100) . "\n";
printf("%-33s %12.2f %12.2f %12.2f\n", "TOTALS:", $totalReturns, $totalReturnsPaid, $totalReturnsDue);
echo str_repeat("=", 100) . "\n\n";

// Final Analysis
echo "\n=== BALANCE ANALYSIS ===\n\n";
echo "From Ledger:\n";
echo "  Total Debits (Sales):     " . number_format($totalDebit, 2) . "\n";
echo "  Total Credits (Payments): " . number_format($totalCredit, 2) . "\n";
echo "  Ledger Balance:           " . number_format($runningBalance, 2) . "\n\n";

echo "From Sales Table:\n";
echo "  Total Sales:              " . number_format($totalSales, 2) . "\n";
echo "  Total Paid:               " . number_format($totalPaidInSales, 2) . "\n";
echo "  Total Due:                " . number_format($totalDueInSales, 2) . "\n\n";

echo "From Payments Table:\n";
echo "  Total Payments:           " . number_format($totalPayments, 2) . "\n\n";

echo "From Returns Table:\n";
echo "  Total Returns:            " . number_format($totalReturns, 2) . "\n";
echo "  Returns Paid:             " . number_format($totalReturnsPaid, 2) . "\n";
echo "  Returns Due:              " . number_format($totalReturnsDue, 2) . "\n\n";

$expectedBalance = $totalSales - $totalReturns - $totalPayments;
echo "Expected Balance (Sales - Returns - Payments): " . number_format($expectedBalance, 2) . "\n";
echo "Actual Ledger Balance: " . number_format($runningBalance, 2) . "\n";

if (abs($expectedBalance - $runningBalance) > 0.01) {
    echo "\n⚠️  DISCREPANCY FOUND: " . number_format($expectedBalance - $runningBalance, 2) . "\n";
} else {
    echo "\n✓ Ledger balance matches expected calculation\n";
}

// Check if balance is negative (advance)
if ($runningBalance < 0) {
    echo "\n⚠️  Customer has ADVANCE BALANCE of Rs" . number_format(abs($runningBalance), 2) . "\n";
} elseif ($runningBalance > 0) {
    echo "\n⚠️  Customer has DUE BALANCE of Rs" . number_format($runningBalance, 2) . "\n";
} else {
    echo "\n✓ Customer account is settled (zero balance)\n";
}

echo "\n" . str_repeat("=", 120) . "\n";
