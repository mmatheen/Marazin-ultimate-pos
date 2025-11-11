<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ledger;
use App\Models\Customer;
use Carbon\Carbon;

echo "SAFE LEDGER CLEANUP GENERATOR\n";
echo "=============================\n\n";

// Get problematic references
$problematicRefs = ['ATF-017', 'ATF-020', 'ATF-027', 'MLX-050'];

$cleanupSQL = "-- SAFE LEDGER CLEANUP SQL\n";
$cleanupSQL .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
$cleanupSQL .= "-- Purpose: Fix orphaned ledger entries from deleted sales\n\n";

$cleanupSQL .= "-- Step 1: Backup current state\n";
$cleanupSQL .= "CREATE TABLE ledgers_backup_" . date('Ymd_His') . " AS SELECT * FROM ledgers;\n\n";

$totalImpact = 0;
$affectedCustomers = [];

foreach ($problematicRefs as $refNo) {
    $entries = Ledger::where('reference_no', $refNo)->where('contact_type', 'customer')->get();
    
    if ($entries->isEmpty()) continue;
    
    $cleanupSQL .= "-- Fixing orphaned entries for: $refNo\n";
    
    foreach ($entries as $entry) {
        $customer = Customer::find($entry->user_id);
        $customerName = $customer ? $customer->full_name : "Customer {$entry->user_id}";
        
        // Create reversal entry to cancel out the orphaned transaction
        $reversalDebit = $entry->credit;
        $reversalCredit = $entry->debit;
        $notes = "CLEANUP: Reversal of orphaned entry for deleted sale {$refNo}";
        
        $cleanupSQL .= "-- Reversal for Customer {$entry->user_id} ({$customerName})\n";
        $cleanupSQL .= "INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (\n";
        $cleanupSQL .= "  NOW(),\n";
        $cleanupSQL .= "  'CLEANUP-REV-{$refNo}-{$entry->id}',\n";
        $cleanupSQL .= "  'adjustment_credit',\n";
        $cleanupSQL .= "  {$reversalDebit},\n";
        $cleanupSQL .= "  {$reversalCredit},\n";
        $cleanupSQL .= "  0,\n";
        $cleanupSQL .= "  'customer',\n";
        $cleanupSQL .= "  {$entry->user_id},\n";
        $cleanupSQL .= "  '{$notes}',\n";
        $cleanupSQL .= "  NOW(),\n";
        $cleanupSQL .= "  NOW()\n";
        $cleanupSQL .= ");\n\n";
        
        // Track impact
        $impact = $entry->debit - $entry->credit;
        $totalImpact += abs($impact);
        $affectedCustomers[$entry->user_id] = ($affectedCustomers[$entry->user_id] ?? 0) + $impact;
    }
}

$cleanupSQL .= "-- Step 2: Recalculate balances for affected customers\n";
foreach (array_keys($affectedCustomers) as $customerId) {
    $cleanupSQL .= "-- You'll need to run: Ledger::recalculateAllBalances($customerId, 'customer');\n";
}

$cleanupSQL .= "\n-- IMPACT SUMMARY:\n";
$cleanupSQL .= "-- Total correction amount: Rs " . number_format($totalImpact, 2) . "\n";
$cleanupSQL .= "-- Affected customers: " . count($affectedCustomers) . "\n\n";

foreach ($affectedCustomers as $customerId => $incorrectAmount) {
    $customer = Customer::find($customerId);
    $name = $customer ? $customer->full_name : "Customer $customerId";
    $currentBalance = Ledger::getLatestBalance($customerId, 'customer');
    $correctBalance = $currentBalance - $incorrectAmount;
    
    $cleanupSQL .= "-- Customer $customerId ($name):\n";
    $cleanupSQL .= "-- Current (incorrect) balance: Rs " . number_format($currentBalance, 2) . "\n";
    $cleanupSQL .= "-- Corrected balance will be: Rs " . number_format($correctBalance, 2) . "\n\n";
}

// Save to file
file_put_contents('safe_ledger_cleanup.sql', $cleanupSQL);

echo "✅ CLEANUP SQL GENERATED: safe_ledger_cleanup.sql\n\n";

echo "📊 SUMMARY:\n";
echo "- Found orphaned ledger entries for deleted sales\n";
echo "- Total impact: Rs " . number_format($totalImpact, 2) . "\n";
echo "- Affected customers: " . count($affectedCustomers) . "\n\n";

echo "🔧 TO FIX:\n";
echo "1. Backup database\n";
echo "2. Review safe_ledger_cleanup.sql\n";
echo "3. Execute the SQL\n";
echo "4. Run balance recalculation\n\n";

echo "⚠️  This creates REVERSAL ENTRIES (safe) instead of deleting (risky)\n";
echo "Your audit trail remains intact!\n";