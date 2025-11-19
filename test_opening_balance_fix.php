<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;

echo "=== TESTING CORRECTED OPENING BALANCE EDIT LOGIC ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

echo "Expected Logic:\n";
echo "1. Old opening balance 5000 → Running: 5000\n";
echo "2. Reverse old opening balance -5000 → Running: 0\n";
echo "3. Sales and reversals → Running: 0 (net effect)\n";
echo "4. New opening balance 7000 → Running: 7000\n\n";

echo "Business Balance (should be 7000): ";
$businessBalance = BalanceHelper::getCustomerBalance($customerId);
echo "$businessBalance\n\n";

echo "Audit Trail Running Balance:\n";
echo str_repeat("-", 120) . "\n";

try {
    $auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
    
    $transactions = $auditData['transactions'];
    
    foreach ($transactions as $i => $transaction) {
        $balance = $transaction['running_balance'] ?? 'N/A';
        $type = $transaction['transaction_type'] ?? 'N/A';
        $debit = $transaction['debit'] ?? 0;
        $credit = $transaction['credit'] ?? 0;
        $ref = $transaction['reference_no'] ?? 'N/A';
        
        // Determine transaction status
        $status = 'UNKNOWN';
        if ($type === 'opening_balance') {
            if (strpos(strtolower($transaction['notes'] ?? ''), 'reversed') !== false) {
                $status = 'REVERSED OB';
            } else {
                $status = 'ACTIVE OB';
            }
        } elseif (strpos(strtolower($ref), 'rev') !== false || 
                 strpos(strtolower($transaction['notes'] ?? ''), 'reversal') !== false) {
            $status = 'REVERSAL';
        } else {
            $status = 'NORMAL';
        }
        
        $marker = match($status) {
            'REVERSED OB' => '❌',
            'ACTIVE OB' => '✅',
            'REVERSAL' => '🔄',
            'NORMAL' => '📝',
            default => '❓'
        };
        
        echo sprintf("%s %d. %-15s | D: %8.2f | C: %8.2f | Running: %8.2f | %s | %s\n", 
            $marker, $i+1, $type, $debit, $credit, $balance, $status, $ref);
    }
    
    $finalBalance = $transactions->last()['running_balance'] ?? 'N/A';
    echo str_repeat("-", 120) . "\n";
    echo "Final Running Balance: $finalBalance\n";
    echo "Business Balance: $businessBalance\n";
    echo "Effective Due: " . $auditData['summary']['effective_due'] . "\n\n";
    
    if ($finalBalance == $businessBalance && $businessBalance == 7000) {
        echo "✅ SUCCESS! Running balance now matches business balance (7000)\n";
    } else {
        echo "❌ Still incorrect. Final running balance should be 7000\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}