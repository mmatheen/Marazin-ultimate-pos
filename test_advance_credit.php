<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\DB;

echo "=== TESTING ADVANCE CREDIT CALCULATION ===\n\n";

// Get first 10 customers (excluding walk-in)
$customers = Customer::where('id', '!=', 1)->take(10)->get();

echo "Testing " . $customers->count() . " customers:\n";
echo str_repeat("-", 80) . "\n";

foreach ($customers as $customer) {
    echo "\nCustomer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";

    // Get raw ledger data
    $ledgerData = DB::selectOne("
        SELECT
            COALESCE(SUM(debit), 0) as total_debits,
            COALESCE(SUM(credit), 0) as total_credits,
            COALESCE(SUM(debit) - SUM(credit), 0) as balance,
            COALESCE(SUM(credit) - SUM(debit), 0) as potential_advance
        FROM ledgers
        WHERE contact_id = ?
            AND contact_type = 'customer'
            AND status = 'active'
    ", [$customer->id]);

    if ($ledgerData) {
        echo "  Ledger Summary:\n";
        echo "    - Total Debits (What they owe): Rs. {$ledgerData->total_debits}\n";
        echo "    - Total Credits (What they paid): Rs. {$ledgerData->total_credits}\n";
        echo "    - Balance (Debits - Credits): Rs. {$ledgerData->balance}\n";
        echo "    - Potential Advance (Credits - Debits): Rs. {$ledgerData->potential_advance}\n";
    } else {
        echo "  No ledger entries found.\n";
    }

    // Test helper methods
    $balance = BalanceHelper::getCustomerBalance($customer->id);
    $advance = BalanceHelper::getCustomerAdvance($customer->id);

    echo "  BalanceHelper Results:\n";
    echo "    - getCustomerBalance(): Rs. {$balance}\n";
    echo "    - getCustomerAdvance(): Rs. {$advance}\n";

    if ($advance > 0) {
        echo "  ✅ HAS ADVANCE CREDIT: Rs. {$advance}\n";
    }
}

echo "\n" . str_repeat("-", 80) . "\n";

// Test bulk method
echo "\nTesting Bulk Advance Calculation:\n";
$customerIds = $customers->pluck('id')->toArray();
$bulkAdvances = BalanceHelper::getBulkCustomerAdvances($customerIds);

echo "Customers with advance credit:\n";
$hasAdvance = false;
foreach ($bulkAdvances as $customerId => $advanceAmount) {
    if ($advanceAmount > 0) {
        $cust = $customers->firstWhere('id', $customerId);
        echo "  - {$cust->first_name} (ID: {$customerId}): Rs. {$advanceAmount}\n";
        $hasAdvance = true;
    }
}

if (!$hasAdvance) {
    echo "  No customers with advance credit found in this sample.\n";
    echo "\n  💡 To create test advance credit:\n";
    echo "  1. Record a payment for a customer\n";
    echo "  2. Make sure the payment (credit) exceeds their invoices (debits)\n";
    echo "  3. The difference will appear as advance credit\n";
}

echo "\n=== TEST COMPLETE ===\n";
