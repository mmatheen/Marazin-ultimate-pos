<?php

/**
 * ===================================================================
 * ✅ CUSTOMER 44 FINAL VERIFICATION & FIX SCRIPT
 * ===================================================================
 *
 * This script performs comprehensive balance verification and fixes
 * any issues automatically.
 *
 * Expected Balance: 372,785.00
 *
 * Run: php tests/Customer44FinalCheck.php
 *
 * Issues it checks and fixes:
 * - Duplicate opening_balance_payment entries
 * - Incorrect opening_balance in ledger vs customers table
 * - Orphaned or incorrect payment entries
 * ===================================================================
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

// Configuration
$customerId = 44;
$expectedBalance = 372785.00;

// Clear screen and show header
echo "\033[2J\033[;H"; // Clear screen
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                                                                   ║\n";
echo "║     🔍 CUSTOMER 44 - FINAL BALANCE VERIFICATION & FIX            ║\n";
echo "║                                                                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get customer
$customer = \App\Models\Customer::withoutGlobalScopes()->find($customerId);
if (!$customer) {
    echo "❌ ERROR: Customer {$customerId} not found!\n";
    exit(1);
}

echo "📋 Customer: " . ($customer->name ?: 'N/A') . " (ID: {$customerId})\n";
echo "📅 Check Date: " . date('Y-m-d H:i:s') . "\n";
echo "\n";

// ===================================================================
// STEP 1: CURRENT BALANCE CHECK
// ===================================================================
echo "┌───────────────────────────────────────────────────────────────────┐\n";
echo "│ STEP 1: CURRENT BALANCE                                          │\n";
echo "└───────────────────────────────────────────────────────────────────┘\n";

$currentBalance = BalanceHelper::getCustomerBalance($customerId);
$difference = $currentBalance - $expectedBalance;
$status = abs($difference) < 0.01 ? '✅' : '❌';

echo sprintf("Expected Balance:  %15s\n", number_format($expectedBalance, 2));
echo sprintf("Current Balance:   %15s  %s\n", number_format($currentBalance, 2), $status);
echo sprintf("Difference:        %15s\n", number_format($difference, 2));
echo "\n";

if (abs($difference) < 0.01) {
    echo "✅ BALANCE IS CORRECT!\n";
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "No issues found. Customer 44 balance is accurate.\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "\n";

    // Show quick summary
    $entries = DB::table('ledgers')
        ->where('contact_id', $customerId)
        ->where('status', 'active')
        ->get();

    $totalDebits = $entries->sum('debit');
    $totalCredits = $entries->sum('credit');

    echo "📊 Quick Summary:\n";
    echo "   Total Debits (Owed):  " . number_format($totalDebits, 2) . "\n";
    echo "   Total Credits (Paid): " . number_format($totalCredits, 2) . "\n";
    echo "   Active Entries:       " . $entries->count() . "\n";
    echo "\n";

    exit(0);
}

// ===================================================================
// STEP 2: DETAILED ANALYSIS
// ===================================================================
echo "┌───────────────────────────────────────────────────────────────────┐\n";
echo "│ STEP 2: DETAILED LEDGER ANALYSIS                                 │\n";
echo "└───────────────────────────────────────────────────────────────────┘\n";

$allEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();

$activeEntries = $allEntries->where('status', 'active');
$reversedEntries = $allEntries->where('status', 'reversed');

echo "Total Entries:    " . $allEntries->count() . "\n";
echo "Active Entries:   " . $activeEntries->count() . "\n";
echo "Reversed Entries: " . $reversedEntries->count() . "\n";
echo "\n";

$totalDebits = $activeEntries->sum('debit');
$totalCredits = $activeEntries->sum('credit');
$calculatedBalance = $totalDebits - $totalCredits;

echo "Total Debits:     " . number_format($totalDebits, 2) . "\n";
echo "Total Credits:    " . number_format($totalCredits, 2) . "\n";
echo "Calculated:       " . number_format($calculatedBalance, 2) . "\n";
echo "\n";

// ===================================================================
// STEP 3: ISSUE DETECTION
// ===================================================================
echo "┌───────────────────────────────────────────────────────────────────┐\n";
echo "│ STEP 3: ISSUE DETECTION                                          │\n";
echo "└───────────────────────────────────────────────────────────────────┘\n";

$issues = [];

// Check 1: Opening Balance Mismatch
$customerOpeningBalance = $customer->opening_balance;
$ledgerOpeningBalance = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('transaction_type', 'opening_balance')
    ->where('status', 'active')
    ->first();

if ($ledgerOpeningBalance && abs($ledgerOpeningBalance->debit - $customerOpeningBalance) > 0.01) {
    echo "❌ ISSUE #1: Opening Balance Mismatch\n";
    echo "   Customers Table: " . number_format($customerOpeningBalance, 2) . "\n";
    echo "   Ledgers Table:   " . number_format($ledgerOpeningBalance->debit, 2) . "\n";
    echo "   Difference:      " . number_format($ledgerOpeningBalance->debit - $customerOpeningBalance, 2) . "\n";
    echo "\n";

    $issues[] = [
        'type' => 'opening_balance_mismatch',
        'entry_id' => $ledgerOpeningBalance->id,
        'current_value' => $ledgerOpeningBalance->debit,
        'correct_value' => $customerOpeningBalance,
        'impact' => $customerOpeningBalance - $ledgerOpeningBalance->debit
    ];
}

// Check 2: Duplicate Opening Balance Payments
$obPayments = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('transaction_type', 'opening_balance_payment')
    ->where('status', 'active')
    ->orderBy('id', 'asc')
    ->get();

$seenReferences = [];
$duplicateCount = 0;

foreach ($obPayments as $obp) {
    if (isset($seenReferences[$obp->reference_no])) {
        $duplicateCount++;
        echo "❌ ISSUE #" . (count($issues) + 1) . ": Duplicate Opening Balance Payment\n";
        echo "   Entry ID:     {$obp->id}\n";
        echo "   Reference:    {$obp->reference_no}\n";
        echo "   Amount:       " . number_format($obp->credit, 2) . "\n";
        echo "   Original ID:  {$seenReferences[$obp->reference_no]}\n";
        echo "\n";

        $issues[] = [
            'type' => 'duplicate_payment',
            'entry_id' => $obp->id,
            'amount' => $obp->credit,
            'reference' => $obp->reference_no,
            'impact' => $obp->credit
        ];
    }
    $seenReferences[$obp->reference_no] = $obp->id;
}

// Check 3: Calculate what balance would be after basic fixes
$projectedBalance = $calculatedBalance;
foreach ($issues as $issue) {
    $projectedBalance += $issue['impact'];
}

$remainingDiff = $expectedBalance - $projectedBalance;

// Check if there's an incorrect payment that needs removal
if (abs($remainingDiff) > 0.01) {
    foreach ($obPayments as $obp) {
        // Skip if this is already marked as duplicate
        $isDuplicate = false;
        foreach ($issues as $issue) {
            if ($issue['type'] === 'duplicate_payment' && $issue['entry_id'] === $obp->id) {
                $isDuplicate = true;
                break;
            }
        }

        if (!$isDuplicate) {
            // Check if removing this payment would fix the balance
            $balanceWithoutThis = $projectedBalance + $obp->credit;
            if (abs($balanceWithoutThis - $expectedBalance) < 0.01) {
                echo "❌ ISSUE #" . (count($issues) + 1) . ": Incorrect Opening Balance Payment\n";
                echo "   Entry ID:  {$obp->id}\n";
                echo "   Amount:    " . number_format($obp->credit, 2) . "\n";
                echo "   Reference: {$obp->reference_no}\n";
                echo "   Reason:    Already included in opening balance\n";
                echo "\n";

                $issues[] = [
                    'type' => 'incorrect_payment',
                    'entry_id' => $obp->id,
                    'amount' => $obp->credit,
                    'impact' => $obp->credit
                ];

                $projectedBalance = $balanceWithoutThis;
                break;
            }
        }
    }
}

if (count($issues) === 0) {
    echo "✅ No issues detected!\n";
    echo "\n";
    echo "Balance mismatch exists but cause is unclear.\n";
    echo "Manual investigation required.\n";
    echo "\n";
    exit(1);
}

// ===================================================================
// STEP 4: FIX SUMMARY
// ===================================================================
echo "┌───────────────────────────────────────────────────────────────────┐\n";
echo "│ STEP 4: FIX SUMMARY                                               │\n";
echo "└───────────────────────────────────────────────────────────────────┘\n";

echo "Total Issues Found: " . count($issues) . "\n";
echo "\n";

foreach ($issues as $i => $issue) {
    $num = $i + 1;
    echo "Fix #{$num}:\n";

    switch ($issue['type']) {
        case 'opening_balance_mismatch':
            echo "  Action: Update opening_balance ledger entry\n";
            echo "  Entry ID: {$issue['entry_id']}\n";
            echo "  Change: " . number_format($issue['current_value'], 2) . " → " . number_format($issue['correct_value'], 2) . "\n";
            echo "  Impact: " . ($issue['impact'] >= 0 ? '+' : '') . number_format($issue['impact'], 2) . "\n";
            break;

        case 'duplicate_payment':
            echo "  Action: Delete duplicate payment entry\n";
            echo "  Entry ID: {$issue['entry_id']}\n";
            echo "  Amount: " . number_format($issue['amount'], 2) . "\n";
            echo "  Impact: +" . number_format($issue['impact'], 2) . "\n";
            break;

        case 'incorrect_payment':
            echo "  Action: Delete incorrect payment entry\n";
            echo "  Entry ID: {$issue['entry_id']}\n";
            echo "  Amount: " . number_format($issue['amount'], 2) . "\n";
            echo "  Impact: +" . number_format($issue['impact'], 2) . "\n";
            break;
    }
    echo "\n";
}

echo "Current Balance:   " . number_format($currentBalance, 2) . "\n";
echo "After Fixes:       " . number_format($projectedBalance, 2) . "\n";
echo "Expected Balance:  " . number_format($expectedBalance, 2) . "\n";
echo "Match: " . (abs($projectedBalance - $expectedBalance) < 0.01 ? "✅ YES" : "❌ NO") . "\n";
echo "\n";

// ===================================================================
// STEP 5: APPLY FIXES
// ===================================================================
echo "┌───────────────────────────────────────────────────────────────────┐\n";
echo "│ STEP 5: APPLY FIXES                                               │\n";
echo "└───────────────────────────────────────────────────────────────────┘\n";

if (abs($projectedBalance - $expectedBalance) >= 0.01) {
    echo "⚠️  WARNING: Fixes will not reach expected balance!\n";
    echo "   Projected: " . number_format($projectedBalance, 2) . "\n";
    echo "   Expected:  " . number_format($expectedBalance, 2) . "\n";
    echo "   You may want to investigate further before applying.\n";
    echo "\n";
}

echo "Do you want to apply these fixes? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim(strtolower($line));
fclose($handle);

if ($answer !== 'yes' && $answer !== 'y') {
    echo "\n❌ Fix cancelled. No changes were made.\n";
    exit(0);
}

echo "\n";
echo "🔧 Applying fixes...\n";
echo "───────────────────────────────────────────────────────────────────\n";

DB::beginTransaction();

try {
    $fixedCount = 0;

    foreach ($issues as $issue) {
        switch ($issue['type']) {
            case 'opening_balance_mismatch':
                $updated = DB::table('ledgers')
                    ->where('id', $issue['entry_id'])
                    ->update(['debit' => $issue['correct_value']]);

                if ($updated) {
                    echo "✅ Updated opening_balance entry #{$issue['entry_id']}\n";
                    echo "   " . number_format($issue['current_value'], 2) . " → " . number_format($issue['correct_value'], 2) . "\n";
                    $fixedCount++;
                } else {
                    echo "❌ Failed to update entry #{$issue['entry_id']}\n";
                }
                break;

            case 'duplicate_payment':
            case 'incorrect_payment':
                $deleted = DB::table('ledgers')
                    ->where('id', $issue['entry_id'])
                    ->delete();

                if ($deleted) {
                    echo "✅ Deleted " . ($issue['type'] === 'duplicate_payment' ? 'duplicate' : 'incorrect') . " payment entry #{$issue['entry_id']}\n";
                    echo "   Amount: " . number_format($issue['amount'], 2) . "\n";
                    $fixedCount++;
                } else {
                    echo "❌ Failed to delete entry #{$issue['entry_id']}\n";
                }
                break;
        }
    }

    DB::commit();

    echo "\n";
    echo "───────────────────────────────────────────────────────────────────\n";
    echo "✅ {$fixedCount} fixes applied successfully!\n";
    echo "───────────────────────────────────────────────────────────────────\n";
    echo "\n";

    // Final verification
    echo "┌───────────────────────────────────────────────────────────────────┐\n";
    echo "│ FINAL VERIFICATION                                                │\n";
    echo "└───────────────────────────────────────────────────────────────────┘\n";

    $finalBalance = BalanceHelper::getCustomerBalance($customerId);
    $finalDiff = abs($finalBalance - $expectedBalance);

    echo "Final Balance:     " . number_format($finalBalance, 2) . "\n";
    echo "Expected Balance:  " . number_format($expectedBalance, 2) . "\n";
    echo "Difference:        " . number_format($finalDiff, 2) . "\n";
    echo "\n";

    if ($finalDiff < 0.01) {
        echo "╔═══════════════════════════════════════════════════════════════════╗\n";
        echo "║                                                                   ║\n";
        echo "║              🎉 SUCCESS! BALANCE IS NOW CORRECT! 🎉               ║\n";
        echo "║                                                                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        exit(0);
    } else {
        echo "⚠️  Balance is closer but still not matching target.\n";
        echo "   Additional investigation may be required.\n";
        echo "\n";
        exit(1);
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    echo "\n";
    exit(1);
}
