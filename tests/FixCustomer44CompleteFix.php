<?php

/**
 * ===================================================================
 * 🔧 CUSTOMER 44 COMPLETE BALANCE FIX SCRIPT
 * ===================================================================
 *
 * REMAINING ISSUES:
 * 1. Wrong opening_balance ledger entry: 373,885 (should be 350,085)
 * 2. Possibly incorrect opening_balance_payment of 15,000
 *
 * CURRENT BALANCE: 381,585
 * TARGET BALANCE: 372,785
 *
 * Run: php tests/FixCustomer44CompleteFix.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🔧 CUSTOMER 44 COMPLETE FIX SCRIPT                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$customerId = 44;
$targetBalance = 372785.00;

// Get current state
$customer = \App\Models\Customer::withoutGlobalScopes()->find($customerId);
$currentBalance = BalanceHelper::getCustomerBalance($customerId);

echo "📊 CURRENT STATE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Customer: {$customer->name}\n";
echo "Current Balance: " . number_format($currentBalance, 2) . "\n";
echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
echo "Difference: " . number_format($targetBalance - $currentBalance, 2) . "\n";
echo "\n";

// Check opening balance
$correctOpeningBalance = $customer->opening_balance;
$ledgerOpeningBalance = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('transaction_type', 'opening_balance')
    ->where('status', 'active')
    ->first();

echo "🔍 OPENING BALANCE ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Opening Balance in customers table: " . number_format($correctOpeningBalance, 2) . "\n";
if ($ledgerOpeningBalance) {
    echo "Opening Balance in ledgers table: " . number_format($ledgerOpeningBalance->debit, 2) . "\n";
    echo "Ledger Entry ID: {$ledgerOpeningBalance->id}\n";
    echo "Difference: " . number_format($ledgerOpeningBalance->debit - $correctOpeningBalance, 2) . "\n";
} else {
    echo "No active opening_balance entry found in ledgers!\n";
}
echo "\n";

// Calculate what the balance should be
$allLedgerEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('status', 'active')
    ->get();

$openingBalanceSum = $allLedgerEntries
    ->where('transaction_type', 'opening_balance')
    ->sum(function($e) { return $e->debit - $e->credit; });

$nonOpeningBalanceSum = $allLedgerEntries
    ->where('transaction_type', '!=', 'opening_balance')
    ->sum(function($e) { return $e->debit - $e->credit; });

echo "📊 LEDGER BREAKDOWN\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Opening Balance Entries: " . number_format($openingBalanceSum, 2) . "\n";
echo "All Other Transactions: " . number_format($nonOpeningBalanceSum, 2) . "\n";
echo "Total: " . number_format($openingBalanceSum + $nonOpeningBalanceSum, 2) . "\n";
echo "\n";
echo "If we use correct opening balance:\n";
echo "  {$correctOpeningBalance} + {$nonOpeningBalanceSum} = " . number_format($correctOpeningBalance + $nonOpeningBalanceSum, 2) . "\n";
echo "\n";

// Proposed fixes
$fixes = [];

// Fix 1: Update opening balance if wrong
if ($ledgerOpeningBalance && abs($ledgerOpeningBalance->debit - $correctOpeningBalance) > 0.01) {
    $fixes[] = [
        'type' => 'update_opening_balance',
        'entry_id' => $ledgerOpeningBalance->id,
        'old_value' => $ledgerOpeningBalance->debit,
        'new_value' => $correctOpeningBalance,
        'impact' => $correctOpeningBalance - $ledgerOpeningBalance->debit
    ];
}

// Calculate balance after fixing opening balance
$balanceAfterOBFix = $correctOpeningBalance + $nonOpeningBalanceSum;
$remainingDiff = $targetBalance - $balanceAfterOBFix;

echo "🧮 CALCULATION\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "After fixing opening balance: " . number_format($balanceAfterOBFix, 2) . "\n";
echo "Target: " . number_format($targetBalance, 2) . "\n";
echo "Remaining difference: " . number_format($remainingDiff, 2) . "\n";
echo "\n";

// Check if there's a 15,000 opening_balance_payment that shouldn't be there
$obPayments = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('transaction_type', 'opening_balance_payment')
    ->where('status', 'active')
    ->get();

echo "📋 OPENING BALANCE PAYMENTS\n";
echo "═══════════════════════════════════════════════════════════════\n";
foreach ($obPayments as $obp) {
    echo "ID: {$obp->id} | Credit: " . number_format($obp->credit, 2) . " | Date: {$obp->transaction_date} | Ref: {$obp->reference_no}\n";

    // If removing this payment gets us to target, mark it
    $balanceWithoutThis = $balanceAfterOBFix + $obp->credit;
    if (abs($balanceWithoutThis - $targetBalance) < 0.01) {
        echo "   ⚠️  Removing this payment would reach target balance!\n";
        $fixes[] = [
            'type' => 'remove_incorrect_payment',
            'entry_id' => $obp->id,
            'amount' => $obp->credit,
            'reason' => 'This payment appears to be incorrectly recorded or already included in opening balance'
        ];
    }
}
echo "\n";

// Show proposed fixes
if (count($fixes) > 0) {
    echo "💡 PROPOSED FIXES\n";
    echo "═══════════════════════════════════════════════════════════════\n";

    $fixNum = 1;
    foreach ($fixes as $fix) {
        echo "Fix #{$fixNum}:\n";
        if ($fix['type'] === 'update_opening_balance') {
            echo "  Action: Update opening_balance ledger entry\n";
            echo "  Entry ID: {$fix['entry_id']}\n";
            echo "  Change: " . number_format($fix['old_value'], 2) . " → " . number_format($fix['new_value'], 2) . "\n";
            echo "  Impact on balance: " . number_format($fix['impact'], 2) . "\n";
        } elseif ($fix['type'] === 'remove_incorrect_payment') {
            echo "  Action: Remove incorrect opening_balance_payment\n";
            echo "  Entry ID: {$fix['entry_id']}\n";
            echo "  Amount: " . number_format($fix['amount'], 2) . "\n";
            echo "  Reason: {$fix['reason']}\n";
        }
        echo "\n";
        $fixNum++;
    }

    // Calculate expected final balance
    $expectedBalance = $currentBalance;
    foreach ($fixes as $fix) {
        if ($fix['type'] === 'update_opening_balance') {
            $expectedBalance += $fix['impact'];
        } elseif ($fix['type'] === 'remove_incorrect_payment') {
            $expectedBalance += $fix['amount'];
        }
    }

    echo "Expected Final Balance: " . number_format($expectedBalance, 2) . "\n";
    echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
    echo "Match: " . (abs($expectedBalance - $targetBalance) < 0.01 ? "✅ YES" : "❌ NO") . "\n";
    echo "\n";

    // Ask for confirmation
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "⚠️  Apply these fixes? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $answer = trim(strtolower($line));
    fclose($handle);

    if ($answer === 'yes' || $answer === 'y') {
        echo "\n";
        echo "🔧 APPLYING FIXES...\n";
        echo "═══════════════════════════════════════════════════════════════\n";

        DB::beginTransaction();

        try {
            foreach ($fixes as $fix) {
                if ($fix['type'] === 'update_opening_balance') {
                    // Update the opening balance entry
                    $updated = DB::table('ledgers')
                        ->where('id', $fix['entry_id'])
                        ->update(['debit' => $fix['new_value']]);

                    if ($updated) {
                        echo "✅ Updated opening_balance entry ID {$fix['entry_id']}: ";
                        echo number_format($fix['old_value'], 2) . " → " . number_format($fix['new_value'], 2) . "\n";
                    } else {
                        echo "❌ Failed to update entry ID {$fix['entry_id']}\n";
                    }

                } elseif ($fix['type'] === 'remove_incorrect_payment') {
                    // Delete the incorrect payment
                    $deleted = DB::table('ledgers')
                        ->where('id', $fix['entry_id'])
                        ->delete();

                    if ($deleted) {
                        echo "✅ Deleted incorrect payment ID {$fix['entry_id']} (Amount: " . number_format($fix['amount'], 2) . ")\n";
                    } else {
                        echo "❌ Failed to delete entry ID {$fix['entry_id']}\n";
                    }
                }
            }

            DB::commit();

            echo "\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "✅ ALL FIXES APPLIED SUCCESSFULLY!\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "\n";

            // Final verification
            echo "📊 FINAL VERIFICATION\n";
            echo "═══════════════════════════════════════════════════════════════\n";

            $finalBalance = BalanceHelper::getCustomerBalance($customerId);
            echo "Final Balance: " . number_format($finalBalance, 2) . "\n";
            echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
            echo "Difference: " . number_format(abs($finalBalance - $targetBalance), 2) . "\n";

            if (abs($finalBalance - $targetBalance) < 0.01) {
                echo "\n";
                echo "🎉🎉🎉 SUCCESS! Balance matches target perfectly! 🎉🎉🎉\n";
            } else {
                echo "\n";
                echo "⚠️  Balance does not match target. Further investigation needed.\n";
            }

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n";
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "All changes rolled back.\n";
        }

    } else {
        echo "\n❌ Fix cancelled. No changes made.\n";
    }

} else {
    echo "✅ No issues detected!\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ DIAGNOSTIC COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
