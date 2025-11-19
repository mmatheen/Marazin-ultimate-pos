<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== RUNNING BALANCE FIX TEST ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

echo "1. Testing BalanceHelper (Business Logic):\n";
$businessBalance = BalanceHelper::getCustomerBalance($customerId);
echo "   Business Balance: $businessBalance\n\n";

echo "2. Testing Audit Trail (Full History):\n";
try {
    $auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
    
    echo "   Effective Due: " . $auditData['summary']['effective_due'] . "\n\n";
    
    // Show ALL transactions with running balance
    $transactions = $auditData['transactions'];
    echo "   All transactions with running balance:\n";
    echo "   " . str_repeat("-", 100) . "\n";
    
    foreach ($transactions as $i => $transaction) {
        $balance = $transaction['running_balance'] ?? 'N/A';
        $type = $transaction['transaction_type'] ?? 'N/A';
        $debit = $transaction['debit'] ?? 0;
        $credit = $transaction['credit'] ?? 0;
        $ref = $transaction['reference_no'] ?? 'N/A';
        
        // Get status from original data - need to check if available
        $status = 'N/A'; // Status might not be in transformed data
        
        // Check if this looks like a reversal
        $isReversal = (
            strpos(strtolower($ref), 'rev') !== false ||
            strpos(strtolower($ref), 'edit') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), 'reversal') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), '[reversed') !== false ||
            $status === 'reversed'
        );
        
        $marker = $isReversal ? '🔄' : '✅';
        
        echo sprintf("   %s %d. Type: %-15s | Debit: %8.2f | Credit: %8.2f | Balance: %8.2f | Ref: %s\n", 
            $marker, $i+1, $type, $debit, $credit, $balance, $ref);
    }
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. Expected Results:\n";
echo "   - Business Balance should be: 7000\n";
echo "   - Effective Due should be: 7000\n";
echo "   - Final running balance should be: 7000\n";
echo "   - Only opening balance (7000) should contribute to running balance\n";
echo "   - Reversal entries (🔄) should be visible but not affect running balance\n";