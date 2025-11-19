<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== AUDIT TRAIL BALANCE FIX TEST ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

echo "1. Testing BalanceHelper (Business Logic):\n";
$businessBalance = BalanceHelper::getCustomerBalance($customerId);
echo "   Business Balance: $businessBalance\n\n";

echo "2. Testing Audit Trail (Full History):\n";
try {
    $auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
    
    echo "   Effective Due: " . $auditData['summary']['effective_due'] . "\n";
    
    // Show the last few transactions with running balance
    $transactions = $auditData['transactions'];
    echo "   Last few transactions with running balance:\n";
    
    $lastTransactions = $transactions->take(-3); // Get last 3 transactions
    foreach ($lastTransactions as $transaction) {
        $balance = $transaction['balance'] ?? 'N/A';
        $type = $transaction['transaction_type'] ?? 'N/A';
        $debit = $transaction['debit'] ?? 0;
        $credit = $transaction['credit'] ?? 0;
        $ref = $transaction['reference_no'] ?? 'N/A';
        
        echo "     - Type: $type | Debit: $debit | Credit: $credit | Running Balance: $balance | Ref: $ref\n";
    }
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. Expected Results:\n";
echo "   - Business Balance should be: 7000\n";
echo "   - Effective Due should be: 7000\n";
echo "   - Final running balance in audit trail should be: 7000\n";
echo "   - Reversal entries should be visible but not affect running balance\n";