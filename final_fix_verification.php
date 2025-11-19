<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\BalanceHelper;
use App\Services\UnifiedLedgerService;

echo "=== FINAL CUSTOMER BALANCE FIX VERIFICATION ===\n\n";

$customerId = 2; // Aasath

echo "ISSUE SUMMARY:\n";
echo "- Customer opening balance was changed from 5000 to 7000\n";
echo "- Effective Due was showing 12,000 instead of 7,000\n";
echo "- Running balance in audit trail was incorrect\n\n";

echo "ROOT CAUSE:\n";
echo "- Reversal entries were marked as 'active' but should be excluded from balance\n";
echo "- Both BalanceHelper and audit trail running balance included these reversals\n\n";

echo "FIXES APPLIED:\n";
echo "1. ✅ Updated BalanceHelper to exclude reversal patterns\n";
echo "2. ✅ Updated audit trail running balance calculation\n";
echo "3. ✅ Applied consistent reversal detection logic\n\n";

echo "VERIFICATION RESULTS:\n";

// Test BalanceHelper
$balance = BalanceHelper::getCustomerBalance($customerId);
echo "✅ BalanceHelper balance: $balance (Expected: 7000)\n";

// Test API response
$ledgerService = new UnifiedLedgerService();
$summary = $ledgerService->getCustomerBalanceSummary($customerId);
echo "✅ API Effective Due: " . $summary['outstanding_amount'] . " (Expected: 7000)\n";

// Test audit trail
$auditData = $ledgerService->getCustomerLedger($customerId, '2025-01-01', '2025-12-31', null, true);
$finalTransaction = $auditData['transactions']->last();
$finalRunningBalance = $finalTransaction['running_balance'] ?? 'N/A';
echo "✅ Audit trail final balance: $finalRunningBalance (Expected: 7000)\n\n";

// Check if all values match
if ($balance == 7000 && $summary['outstanding_amount'] == 7000 && $finalRunningBalance == 7000) {
    echo "🎯 SUCCESS! All balance calculations are now consistent and correct.\n";
    echo "   The customer's effective due will now show Rs. 7,000.00 in the web interface.\n";
} else {
    echo "❌ Some values are still incorrect. Please check the implementation.\n";
}

echo "\nREVERSAL DETECTION PATTERNS:\n";
echo "- Reference numbers: 'rev', 'edit', 'reversal'\n";
echo "- Notes: 'reversal', '[reversed', 'edit', 'correction', 'removed'\n";
echo "- Status: 'reversed' (traditional reversals)\n";

echo "\nNEXT STEPS:\n";
echo "1. Refresh the web page to see updated balances\n";
echo "2. Effective Due should now show Rs. 7,000.00\n";
echo "3. Running balance in audit trail should progress correctly\n";