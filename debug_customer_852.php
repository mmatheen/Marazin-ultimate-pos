<?php
/**
 * Debug script to analyze customer 852's ledger and sales data
 * Run: php debug_customer_852.php
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Ledger;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\DB;

$customerId = 852;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                   CUSTOMER #852 - DETAILED ANALYSIS                     ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

// 1. Get customer basic info
$customer = Customer::find($customerId);
if (!$customer) {
    echo "❌ Customer ID 852 not found!\n";
    exit(1);
}

echo "👤 CUSTOMER INFO:\n";
echo "   Name: {$customer->first_name} {$customer->last_name}\n";
echo "   Mobile: {$customer->mobile_no}\n";
echo "   Opening Balance: Rs. " . number_format($customer->opening_balance, 2) . "\n\n";

// 2. Get ALL ledger entries for this customer
$ledgerEntries = Ledger::where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date', 'ASC')
    ->orderBy('id', 'ASC')
    ->get();

echo "📊 LEDGER ENTRIES (Total: " . $ledgerEntries->count() . ")\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("%-12s | %-22s | %-10s | %-10s | %-8s | %s\n", "Date", "Type", "Debit", "Credit", "Status", "Ref");
echo "─────────────────────────────────────────────────────────────────────────\n";

$totalDebit = 0;
$totalCredit = 0;

foreach ($ledgerEntries as $entry) {
    if ($entry->status === 'active') {
        $totalDebit += $entry->debit;
        $totalCredit += $entry->credit;
    }

    $statusIcon = $entry->status === 'active' ? '✓' : '✗';
    echo sprintf(
        "%s | %-22s | %10.2f | %10.2f | %-8s | %s\n",
        $entry->transaction_date->format('Y-m-d'),
        substr($entry->transaction_type, 0, 22),
        $entry->debit,
        $entry->credit,
        $statusIcon,
        substr($entry->reference_no ?? 'N/A', 0, 15)
    );
}

echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("ACTIVE TOTALS: Debit: %10.2f | Credit: %10.2f\n", $totalDebit, $totalCredit);

$ledgerBalance = $totalDebit - $totalCredit;
echo "\n🎯 LEDGER BALANCE: Rs. " . number_format($ledgerBalance, 2);
if ($ledgerBalance > 0) {
    echo " (Customer OWES this much)\n\n";
} else {
    echo " (Customer has ADVANCE/CREDIT of Rs. " . number_format(abs($ledgerBalance), 2) . ")\n\n";
}

// 3. Get Sales data for this customer
$sales = Sale::where('customer_id', $customerId)
    ->whereIn('status', ['final', 'suspend'])
    ->orderBy('sales_date', 'ASC')
    ->get(['id', 'invoice_no', 'sales_date', 'final_total', 'total_paid', 'total_due', 'payment_status']);

echo "📋 SALES INVOICES (Total: " . $sales->count() . ")\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("%-15s | %-12s | %-12s | %-12s | %-12s | %s\n", "Invoice", "Total", "Paid", "Due", "Status", "Date");
echo "─────────────────────────────────────────────────────────────────────────\n";

$totalSalesAmount = 0;
$totalSalesPaid = 0;
$totalSalesDue = 0;

foreach ($sales as $sale) {
    $totalSalesAmount += $sale->final_total;
    $totalSalesPaid += $sale->total_paid;
    $totalSalesDue += $sale->total_due;

    echo sprintf(
        "%-15s | %12.2f | %12.2f | %12.2f | %-12s | %s\n",
        $sale->invoice_no,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due,
        $sale->payment_status,
        $sale->sales_date->format('Y-m-d')
    );
}

echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("TOTALS: Amount: %12.2f | Paid: %12.2f | Due: %12.2f\n\n", $totalSalesAmount, $totalSalesPaid, $totalSalesDue);

// 4. Get all payments for this customer
$payments = Payment::where('customer_id', $customerId)
    ->where('status', '!=', 'deleted')
    ->orderBy('payment_date', 'DESC')
    ->get(['id', 'payment_date', 'payment_type', 'payment_method', 'amount', 'reference_id', 'reference_no']);

echo "💳 PAYMENTS (Total: " . $payments->count() . ")\n";
echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("%-12s | %-20s | %-18s | %-12s | %s\n", "Date", "Type", "Method", "Amount", "Ref");
echo "─────────────────────────────────────────────────────────────────────────\n";

$totalPayments = 0;
foreach ($payments as $payment) {
    $totalPayments += $payment->amount;
    echo sprintf(
        "%s | %-20s | %-18s | %12.2f | %s\n",
        $payment->payment_date->format('Y-m-d'),
        substr($payment->payment_type, 0, 20),
        substr($payment->payment_method, 0, 18),
        $payment->amount,
        substr($payment->reference_no ?? 'N/A', 0, 15)
    );
}

echo "─────────────────────────────────────────────────────────────────────────\n";
echo sprintf("TOTAL PAYMENTS: Rs. %12.2f\n\n", $totalPayments);

// 5. Calculate advance
$advanceAmount = BalanceHelper::getCustomerAdvance($customerId);

echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║                            SUMMARY ANALYSIS                            ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 KEY NUMBERS:\n";
echo "   Ledger Balance (SUM debit - credit):        Rs. " . number_format($ledgerBalance, 2) . "\n";
echo "   Sales Total Due (SUM invoice due):          Rs. " . number_format($totalSalesDue, 2) . "\n";
echo "   Customer Advance (when credit > debit):     Rs. " . number_format($advanceAmount, 2) . "\n\n";

// 6. Calculate the gap
$gap = $totalSalesDue - max(0, $ledgerBalance);
echo "📈 RECONCILIATION:\n";
echo "   Sales Due vs Ledger Due Gap:                Rs. " . number_format(abs($gap), 2);
if ($gap > 0) {
    echo " (Ledger balance is LESS than Sales Due - means some credit is unallocated)\n\n";
} elseif ($gap < 0) {
    echo " (Ledger balance is MORE than Sales Due - accounting issue?)\n\n";
} else {
    echo " (PERFECTLY ALIGNED - no gap)\n\n";
}

// 7. Available credit calculation
$ledgerBalanceCalculated = max(0, $ledgerBalance);
$saleDueCalculated = $totalSalesDue;
$availableCredit = max(0.0, $saleDueCalculated - $ledgerBalanceCalculated) + max(0.0, abs(min(0.0, $ledgerBalance)));

echo "💚 AVAILABLE CREDIT FOR APPLICATION:\n";
echo "   Formula: max(0, SalesDue - LedgerBalance) + max(0, |min(0, LedgerBalance)|)\n";
echo "   = max(0, " . number_format($totalSalesDue, 2) . " - " . number_format($ledgerBalanceCalculated, 2) . ") + max(0, |" . number_format(min(0.0, $ledgerBalance), 2) . "|)\n";
echo "   Available Credit to Apply: Rs. " . number_format($availableCredit, 2) . "\n\n";

echo "✅ THIS IS THE AMOUNT SHOWN IN 'Apply credit' OPTION\n\n";

?>
