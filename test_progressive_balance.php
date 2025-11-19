<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== TESTING PROGRESSIVE RUNNING BALANCE ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

echo "Expected Progressive Balance:\n";
echo "1. Opening balance 5000 → Running: 5000\n";
echo "2. Sale +5800 → Running: 10800\n";
echo "3. Sale return -5800 → Running: 5000\n";
echo "4. Sale +4000 → Running: 9000\n";
echo "5. Sale return -4000 → Running: 5000\n";
echo "6. New opening balance +7000 → Running: 12000\n";
echo "BUT Business Balance should be 7000 (only legitimate entries)\n\n";

echo "Business Balance (filtered, should be 7000): ";
$businessBalance = BalanceHelper::getCustomerBalance($customerId);
echo "$businessBalance\n\n";

echo "Progressive Audit Trail:\n";
echo str_repeat("-", 140) . "\n";

try {
    $auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
    
    $transactions = $auditData['transactions'];
    
    foreach ($transactions as $i => $transaction) {
        $balance = $transaction['running_balance'] ?? 'N/A';
        $type = $transaction['transaction_type'] ?? 'N/A';
        $debit = $transaction['debit'] ?? 0;
        $credit = $transaction['credit'] ?? 0;
        $ref = $transaction['reference_no'] ?? 'N/A';
        
        // Determine if this is a reversal for visual indication
        $isReversal = (
            strpos(strtolower($ref), 'rev') !== false ||
            strpos(strtolower($ref), 'edit') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), 'reversal') !== false ||
            strpos(strtolower($transaction['notes'] ?? ''), '[reversed') !== false
        );
        
        $marker = $isReversal ? '🔄' : '✅';
        $status = $isReversal ? 'REVERSAL/EDIT' : 'VALID';
        
        echo sprintf("%s %d. %-15s | D: %8.2f | C: %8.2f | Running: %10.2f | %-12s | %s\n", 
            $marker, $i+1, $type, $debit, $credit, $balance, $status, $ref);
    }
    
    $finalAuditBalance = $transactions->last()['running_balance'] ?? 'N/A';
    echo str_repeat("-", 140) . "\n";
    echo "Final Audit Trail Balance: $finalAuditBalance (progressive total)\n";
    echo "Business Balance (filtered): $businessBalance (legitimate entries only)\n";
    echo "Effective Due: " . $auditData['summary']['effective_due'] . " (should match business balance)\n\n";
    
    echo "EXPLANATION:\n";
    echo "- Audit trail shows PROGRESSIVE balance (how balance changed over time)\n";
    echo "- Business balance shows CURRENT balance (filtered for legitimate entries)\n";
    echo "- This gives both transparency (audit) and accuracy (business logic)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}