<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== API RESPONSE TEST FOR CUSTOMER ID 2 ===\n\n";

// Test the UnifiedLedgerService methods that the frontend uses
$ledgerService = new UnifiedLedgerService();

try {
    $summary = $ledgerService->getCustomerBalanceSummary(2);
    echo "Balance Summary:\n";
    print_r($summary);
    echo "\n";
} catch (Exception $e) {
    echo "Error getting balance summary: " . $e->getMessage() . "\n";
}

try {
    $ledgerData = $ledgerService->getCustomerLedger(2, '2025-01-01', '2025-12-31');
    echo "Ledger Data:\n";
    echo "Effective Due: " . $ledgerData['summary']['effective_due'] . "\n";
    echo "Advance Amount: " . $ledgerData['summary']['advance_amount'] . "\n";
    echo "Outstanding Due: " . $ledgerData['summary']['outstanding_due'] . "\n";
} catch (Exception $e) {
    echo "Error getting ledger data: " . $e->getMessage() . "\n";
}

// Direct BalanceHelper test
echo "\nDirect BalanceHelper test:\n";
$balance = BalanceHelper::getCustomerBalance(2);
echo "Customer Balance: $balance\n";
echo "Customer Due: " . BalanceHelper::getCustomerDue(2) . "\n";
echo "Customer Advance: " . BalanceHelper::getCustomerAdvance(2) . "\n";