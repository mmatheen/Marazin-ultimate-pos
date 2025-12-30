<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use App\Helpers\BalanceHelper;

$customerIds = [219, 90, 101];

echo "=================================================================\n";
echo "🔍 CHECKING CUSTOMER OPENING BALANCES\n";
echo "=================================================================\n\n";

foreach ($customerIds as $customerId) {
    $customer = Customer::withoutGlobalScopes()->find($customerId);

    echo "Customer ID {$customerId}: {$customer->full_name}\n";
    echo "  opening_balance field in customers table: " . $customer->opening_balance . "\n";

    // Check for opening balance in ledger
    $obLedger = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('transaction_type', 'opening_balance')
        ->where('status', 'active')
        ->first();

    if ($obLedger) {
        echo "  opening_balance in ledger: Debit=" . $obLedger->debit . ", Credit=" . $obLedger->credit . "\n";
    } else {
        echo "  No opening_balance entry in ledger\n";
    }

    // Check for adjustments
    $hasAdjustment = Ledger::where('contact_id', $customerId)
        ->where('contact_type', 'customer')
        ->where('transaction_type', 'opening_balance_adjustment')
        ->exists();

    echo "  Has opening_balance_adjustment: " . ($hasAdjustment ? "Yes" : "No") . "\n";

    $balance = BalanceHelper::getCustomerBalance($customerId);
    echo "  BalanceHelper result: " . $balance . "\n";
    echo "\n";
}
