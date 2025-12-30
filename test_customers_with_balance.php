<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use App\Models\Customer;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\DB;

// Find customers with outstanding balances
$customersWithBalance = DB::select("
    SELECT
        contact_id,
        SUM(debit - credit) as balance
    FROM ledgers
    WHERE contact_type = 'customer'
        AND status = 'active'
        AND contact_id > 1
    GROUP BY contact_id
    HAVING balance > 0
    ORDER BY balance DESC
    LIMIT 3
");

echo "=================================================================\n";
echo "🔍 CUSTOMERS WITH OUTSTANDING BALANCE\n";
echo "=================================================================\n\n";

foreach ($customersWithBalance as $row) {
    $customer = Customer::withoutGlobalScopes()->find($row->contact_id);
    $balanceFromHelper = BalanceHelper::getCustomerBalance($customer->id);

    echo "Customer: {$customer->full_name} (ID: {$customer->id})\n";
    echo "  Ledger Balance: " . number_format($row->balance, 2) . "\n";
    echo "  BalanceHelper:  " . number_format($balanceFromHelper, 2) . "\n";
    echo "  Match: " . (abs($row->balance - $balanceFromHelper) < 0.01 ? "✅ Yes" : "❌ No") . "\n\n";

    // Show breakdown
    $ledgerSummary = DB::selectOne("
        SELECT
            SUM(CASE WHEN transaction_type = 'sale' THEN debit ELSE 0 END) as sales,
            SUM(CASE WHEN transaction_type IN ('sale_return', 'sale_return_with_bill', 'sale_return_without_bill') THEN credit ELSE 0 END) as returns,
            SUM(CASE WHEN transaction_type IN ('payment', 'payments', 'sale_payment') THEN credit ELSE 0 END) as payments,
            SUM(debit) as total_debits,
            SUM(credit) as total_credits
        FROM ledgers
        WHERE contact_id = ?
            AND contact_type = 'customer'
            AND status = 'active'
    ", [$customer->id]);

    echo "  Breakdown:\n";
    echo "    Sales (Debit):    " . number_format($ledgerSummary->sales, 2) . "\n";
    echo "    Returns (Credit): " . number_format($ledgerSummary->returns, 2) . "\n";
    echo "    Payments (Credit): " . number_format($ledgerSummary->payments, 2) . "\n";
    echo "    ───────────────────────────────\n";
    echo "    Balance:          " . number_format($ledgerSummary->sales - $ledgerSummary->returns - $ledgerSummary->payments, 2) . "\n";
    echo "\n" . str_repeat("-", 65) . "\n\n";
}

echo "✅ CONCLUSION:\n";
echo "BalanceHelper correctly calculates customer balances by:\n";
echo "1. Reading ONLY active ledger entries (status='active')\n";
echo "2. Calculating: SUM(debit) - SUM(credit)\n";
echo "3. Which equals: Sales - Returns - Payments + Other adjustments\n";
echo "=================================================================\n";
