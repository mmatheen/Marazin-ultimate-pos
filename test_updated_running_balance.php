<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== TESTING UPDATED RUNNING BALANCE DISPLAY ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

echo "1. Business Balance (BalanceHelper - should be 7000):\n";
$businessBalance = BalanceHelper::getCustomerBalance($customerId);
echo "   Balance: $businessBalance\n\n";

echo "2. Audit Trail with Running Balance:\n";
try {
    $auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
    
    echo "   Effective Due: " . $auditData['summary']['effective_due'] . " (should be 7000)\n\n";
    
    // Show ALL transactions with running balance
    $transactions = $auditData['transactions'];
    echo "   Progressive Running Balance (All Entries):\n";
    echo "   " . str_repeat("-", 120) . "\n";
    
    foreach ($transactions as $i => $transaction) {
        $balance = $transaction['running_balance'] ?? 'N/A';
        $type = $transaction['transaction_type'] ?? 'N/A';
        $debit = $transaction['debit'] ?? 0;
        $credit = $transaction['credit'] ?? 0;
        $ref = $transaction['reference_no'] ?? 'N/A';
        
        // Check if this looks like a reversal for visual indication
        $isReversal = (
            strpos(strtolower($ref), 'rev') !== false ||
            strpos(strtolower($ref), 'edit') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), 'reversal') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), '[reversed') !== false
        );
        
        $marker = $isReversal ? '🔄' : '✅';
        $status = $isReversal ? 'REVERSAL/EDIT' : 'VALID';
        
        echo sprintf("   %s %d. %-15s | Debit: %8.2f | Credit: %8.2f | Running Bal: %8.2f | %s\n", 
            $marker, $i+1, $type, $debit, $credit, $balance, $status);
    }
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. Expected Behavior:\n";
echo "   - Running balance should show progression through ALL entries\n";
echo "   - Business balance (BalanceHelper) should still be 7000\n";
echo "   - Effective Due should be 7000\n";
echo "   - Audit trail shows complete history including reversals\n";