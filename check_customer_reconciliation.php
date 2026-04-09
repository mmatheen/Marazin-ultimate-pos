<?php

/**
 * Reconcile one customer: customer + sales (all locations, no LocationScope on CLI).
 * In console there is no auth user; LocationScope would otherwise restrict to location_id IS NULL only.
 *
 * Usage: php check_customer_reconciliation.php [customer_id]
 * Default customer_id: 1156
 *
 * Example: php check_customer_reconciliation.php 1156
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\BalanceHelper;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
use Illuminate\Support\Facades\DB;

$customerId = isset($argv[1]) ? (int) $argv[1] : 1156;

if ($customerId < 1) {
    fwrite(STDERR, "Invalid customer id.\n");
    exit(1);
}

echo str_repeat('=', 72) . "\n";
echo " CUSTOMER RECONCILIATION (Customer + Sales without LocationScope)\n";
echo " Customer ID: {$customerId}\n";
echo str_repeat('=', 72) . "\n\n";

$customer = Customer::withoutGlobalScopes()->find($customerId);
if (! $customer) {
    echo "❌ Customer not found.\n";
    exit(1);
}

$name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
echo "Name: {$name}\n";
echo "Opening balance (customers.opening_balance): " . number_format((float) $customer->opening_balance, 2) . "\n\n";

// --- Ledger (BalanceHelper = source of truth for current_due in lists) ---
$ledgerBalance = BalanceHelper::getCustomerBalance($customerId);
$ledgerRow = DB::selectOne("
    SELECT
        COALESCE(SUM(debit), 0) AS total_debits,
        COALESCE(SUM(credit), 0) AS total_credits,
        COALESCE(SUM(debit) - SUM(credit), 0) AS balance
    FROM ledgers
    WHERE contact_id = ?
      AND contact_type = 'customer'
      AND status = 'active'
", [$customerId]);

echo "--- LEDGERS (active) ---\n";
echo "Total debits:  " . number_format((float) $ledgerRow->total_debits, 2) . "\n";
echo "Total credits: " . number_format((float) $ledgerRow->total_credits, 2) . "\n";
echo "Net (debit - credit): " . number_format((float) $ledgerRow->balance, 2) . "\n";
echo "BalanceHelper::getCustomerBalance: " . number_format($ledgerBalance, 2) . "\n\n";

// --- Sales: WITHOUT global scope (all locations) ---
$salesQuery = Sale::withoutGlobalScopes()
    ->where('customer_id', $customerId)
    ->whereIn('status', ['final', 'suspend'])
    ->orderBy('id');

$sales = $salesQuery->get(['id', 'invoice_no', 'location_id', 'final_total', 'total_paid', 'total_due', 'payment_status', 'status']);

$sumFinal = 0.0;
$sumPaidFromSaleTable = 0.0;
$sumDue = 0.0;

echo "--- SALES (final + suspend, ALL locations) ---\n";
echo sprintf("%-6s %-14s %4s %12s %12s %12s %-10s\n", 'ID', 'Invoice', 'Loc', 'final_total', 'total_paid', 'total_due', 'pay_stat');
echo str_repeat('-', 72) . "\n";

foreach ($sales as $s) {
    $sumFinal += (float) $s->final_total;
    $sumPaidFromSaleTable += (float) $s->total_paid;
    $sumDue += (float) $s->total_due;
    echo sprintf(
        "%-6d %-14s %4s %12s %12s %12s %-10s\n",
        $s->id,
        $s->invoice_no,
        (string) $s->location_id,
        number_format((float) $s->final_total, 2),
        number_format((float) $s->total_paid, 2),
        number_format((float) $s->total_due, 2),
        $s->payment_status ?? ''
    );
}

echo str_repeat('-', 72) . "\n";
echo "Rows: " . $sales->count() . "\n";
echo "SUM(final_total): " . number_format($sumFinal, 2) . "\n";
echo "SUM(total_paid):  " . number_format($sumPaidFromSaleTable, 2) . " (on sale rows)\n";
echo "SUM(total_due):   " . number_format($sumDue, 2) . " (unpaid invoice remainder)\n\n";

// Match CustomerController bulk: total_sale_due
$totalSaleDueSql = (float) DB::table('sales')
    ->where('customer_id', $customerId)
    ->whereIn('status', ['final', 'suspend'])
    ->sum('total_due');
echo "SUM(total_due) [same as UI 'Sales unpaid']: " . number_format($totalSaleDueSql, 2) . "\n\n";

// --- Payments linked to this customer ---
echo "--- PAYMENTS (customer_id = {$customerId}, non-deleted) ---\n";

// Payment model excludes status=deleted via global scope
$payments = Payment::query()
    ->where('customer_id', $customerId)
    ->orderBy('id')
    ->get();

$byType = [];
$sumSalePayments = 0.0;

foreach ($payments as $p) {
    $type = $p->payment_type ?? 'null';
    if (! isset($byType[$type])) {
        $byType[$type] = ['count' => 0, 'amount' => 0.0];
    }
    $byType[$type]['count']++;
    $byType[$type]['amount'] += (float) $p->amount;
    if ($type === 'sale' && $p->reference_id) {
        $sumSalePayments += (float) $p->amount;
    }
}

foreach ($byType as $type => $info) {
    echo sprintf(
        "  %-28s  count=%3d  amount=%s\n",
        $type,
        $info['count'],
        number_format($info['amount'], 2)
    );
}
echo "Sum of payment_type='sale' (cash/collected): " . number_format($sumSalePayments, 2) . "\n\n";

// Compare: sale.total_paid should equal sum(payments sale) + return credit in FlexibleBulkSalePaymentService
// For each sale, sum payments
$saleIds = $sales->pluck('id')->all();
if (! empty($saleIds)) {
    $paySumBySale = DB::table('payments')
        ->where('customer_id', $customerId)
        ->where('payment_type', 'sale')
        ->whereIn('reference_id', $saleIds)
        ->select('reference_id', DB::raw('SUM(amount) as amt'))
        ->groupBy('reference_id')
        ->pluck('amt', 'reference_id');

    echo "--- Cross-check: payments(sale) per invoice vs sale.total_paid ---\n";
    $anyDiff = false;
    foreach ($sales as $s) {
        $fromPayments = (float) ($paySumBySale[$s->id] ?? 0);
        $diff = (float) $s->total_paid - $fromPayments;
        if (abs($diff) > 0.02) {
            $anyDiff = true;
            echo sprintf(
                "  %s: total_paid=%s  sum(payments)=%s  diff (return credit or adjustment)=%s\n",
                $s->invoice_no,
                number_format((float) $s->total_paid, 2),
                number_format($fromPayments, 2),
                number_format($diff, 2)
            );
        }
    }
    if (! $anyDiff) {
        echo "  All invoices: sale.total_paid equals sum of payment rows (type=sale) for that invoice.\n";
    }
    echo "\n";
}

// --- Returns ---
echo "--- SALES RETURNS ---\n";
$returns = SalesReturn::where('customer_id', $customerId)
    ->orderBy('id')
    ->get(['id', 'invoice_number', 'sale_id', 'return_total', 'total_paid', 'total_due', 'payment_status']);

foreach ($returns as $r) {
    echo sprintf(
        "  %-6d %-12s sale_id=%-6s ret_tot=%s paid=%s due=%s %s\n",
        $r->id,
        $r->invoice_number,
        (string) ($r->sale_id ?? ''),
        number_format((float) $r->return_total, 2),
        number_format((float) $r->total_paid, 2),
        number_format((float) $r->total_due, 2),
        $r->payment_status ?? ''
    );
}
$sumReturnDue = (float) DB::table('sales_returns')
    ->where('customer_id', $customerId)
    ->sum('total_due');
echo "SUM(sales_returns.total_due) outstanding: " . number_format($sumReturnDue, 2) . "\n\n";

// --- Ledger lines by transaction_type (summary) ---
echo "--- LEDGER BY transaction_type (active) ---\n";
$ledgerTypes = DB::select("
    SELECT transaction_type,
           COUNT(*) AS n,
           COALESCE(SUM(debit), 0) AS deb,
           COALESCE(SUM(credit), 0) AS cred
    FROM ledgers
    WHERE contact_id = ?
      AND contact_type = 'customer'
      AND status = 'active'
    GROUP BY transaction_type
    ORDER BY transaction_type
", [$customerId]);

foreach ($ledgerTypes as $row) {
    echo sprintf(
        "  %-28s  n=%4d  debit=%s  credit=%s\n",
        $row->transaction_type ?? '(null)',
        (int) $row->n,
        number_format((float) $row->deb, 2),
        number_format((float) $row->cred, 2)
    );
}
echo "\n";

// --- Summary ---
echo str_repeat('=', 72) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 72) . "\n";
echo "Ledger net (customer owes if positive):     " . number_format($ledgerBalance, 2) . "\n";
echo "Total unpaid on sales (sum total_due):      " . number_format($totalSaleDueSql, 2) . "\n";
echo "Outstanding return refunds (sum ret due): " . number_format($sumReturnDue, 2) . "\n";
echo "\nIf ledger net ≈ SUM(sales.total_due) + other postings, data is consistent.\n";
echo "Large gap: run BalanceHelper::debugCustomerBalance({$customerId}) in tinker for line detail.\n";
echo "Done.\n";
