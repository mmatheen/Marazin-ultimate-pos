<?php

/**
 * ===================================================================
 * 🔍 CUSTOMER 44 LEDGER BALANCE DIAGNOSTIC AND FIX SCRIPT
 * ===================================================================
 * 
 * ISSUES IDENTIFIED:
 * 1. Duplicate opening_balance_payment entries (8800 x 2)
 * 2. Possible incorrect opening_balance_payment (15000)
 * 
 * TARGET BALANCE: 372,785.00
 * CURRENT BALANCE: 348,985.00
 * 
 * Run: php tests/FixCustomer44Balance.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 CUSTOMER 44 BALANCE DIAGNOSTIC & FIX SCRIPT                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$customerId = 44;
$targetBalance = 372785.00;

// ===================================================================
// STEP 1: GET CURRENT STATE
// ===================================================================
echo "📊 STEP 1: CURRENT STATE ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$customer = \App\Models\Customer::withoutGlobalScopes()->find($customerId);
if (!$customer) {
    die("❌ Customer {$customerId} not found!\n");
}

echo "Customer Name: {$customer->name}\n";
echo "Opening Balance (in customers table): " . number_format($customer->opening_balance, 2) . "\n";

$currentBalance = BalanceHelper::getCustomerBalance($customerId);
echo "Current Ledger Balance: " . number_format($currentBalance, 2) . "\n";
echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
echo "Difference: " . number_format($targetBalance - $currentBalance, 2) . "\n";
echo "\n";

// ===================================================================
// STEP 2: ANALYZE ALL LEDGER ENTRIES
// ===================================================================
echo "📋 STEP 2: DETAILED LEDGER ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$allEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('transaction_date', 'asc')
    ->orderBy('id', 'asc')
    ->get();

$activeEntries = $allEntries->where('status', 'active');
$reversedEntries = $allEntries->where('status', 'reversed');

echo "Total Entries: " . $allEntries->count() . "\n";
echo "Active Entries: " . $activeEntries->count() . "\n";
echo "Reversed Entries: " . $reversedEntries->count() . "\n";
echo "\n";

// ===================================================================
// STEP 3: IDENTIFY DUPLICATE OPENING BALANCE PAYMENTS
// ===================================================================
echo "🔍 STEP 3: CHECKING FOR DUPLICATE OPENING BALANCE PAYMENTS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$openingBalancePayments = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('transaction_type', 'opening_balance_payment')
    ->where('status', 'active')
    ->orderBy('id', 'asc')
    ->get();

echo "Opening Balance Payment Entries: " . $openingBalancePayments->count() . "\n\n";

$duplicates = [];
$seenReferences = [];

foreach ($openingBalancePayments as $entry) {
    echo sprintf(
        "ID: %-6s | Date: %-19s | Credit: %-10s | Ref: %s\n",
        $entry->id,
        $entry->transaction_date,
        number_format($entry->credit, 2),
        $entry->reference_no
    );
    
    // Check for duplicates by reference number
    if (isset($seenReferences[$entry->reference_no])) {
        $duplicates[] = [
            'original_id' => $seenReferences[$entry->reference_no],
            'duplicate_id' => $entry->id,
            'amount' => $entry->credit,
            'reference' => $entry->reference_no
        ];
        echo "   ⚠️  DUPLICATE FOUND! (First entry ID: {$seenReferences[$entry->reference_no]})\n";
    }
    
    $seenReferences[$entry->reference_no] = $entry->id;
}

echo "\n";

// ===================================================================
// STEP 4: CALCULATE BREAKDOWN
// ===================================================================
echo "📊 STEP 4: TRANSACTION BREAKDOWN\n";
echo "═══════════════════════════════════════════════════════════════\n";

$breakdown = [
    'opening_balance' => 0,
    'sales' => 0,
    'payments' => 0,
    'opening_balance_payment' => 0,
    'opening_balance_adjustment' => 0,
];

foreach ($activeEntries as $entry) {
    $type = $entry->transaction_type;
    if (!isset($breakdown[$type])) {
        $breakdown[$type] = 0;
    }
    $breakdown[$type] += ($entry->debit - $entry->credit);
}

foreach ($breakdown as $type => $amount) {
    echo sprintf("%-30s: %12s\n", ucwords(str_replace('_', ' ', $type)), number_format($amount, 2));
}

echo "\n";
$totalDebits = $activeEntries->sum('debit');
$totalCredits = $activeEntries->sum('credit');
$calculatedBalance = $totalDebits - $totalCredits;

echo "Total Debits (Customer Owes): " . number_format($totalDebits, 2) . "\n";
echo "Total Credits (Customer Paid): " . number_format($totalCredits, 2) . "\n";
echo "Calculated Balance: " . number_format($calculatedBalance, 2) . "\n";
echo "\n";

// ===================================================================
// STEP 5: PROBLEM DIAGNOSIS
// ===================================================================
echo "🔧 STEP 5: PROBLEM DIAGNOSIS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$issues = [];

// Check for duplicate 8800 entries
if (count($duplicates) > 0) {
    echo "❌ ISSUE FOUND: Duplicate Opening Balance Payment Entries\n";
    foreach ($duplicates as $dup) {
        echo "   - Reference: {$dup['reference']}\n";
        echo "   - Original ID: {$dup['original_id']}\n";
        echo "   - Duplicate ID: {$dup['duplicate_id']}\n";
        echo "   - Amount: " . number_format($dup['amount'], 2) . "\n";
        $issues[] = [
            'type' => 'duplicate_payment',
            'entry_id' => $dup['duplicate_id'],
            'amount' => $dup['amount']
        ];
    }
    echo "\n";
}

// Calculate what balance would be after removing duplicates
$adjustedBalance = $calculatedBalance;
$totalDuplicateAmount = 0;

foreach ($duplicates as $dup) {
    $totalDuplicateAmount += $dup['amount'];
}

if ($totalDuplicateAmount > 0) {
    $adjustedBalance = $calculatedBalance + $totalDuplicateAmount;
    echo "Balance after removing duplicates: " . number_format($adjustedBalance, 2) . "\n";
    $remainingDifference = $targetBalance - $adjustedBalance;
    echo "Remaining difference to target: " . number_format($remainingDifference, 2) . "\n";
    echo "\n";
    
    // Check if the 15000 opening_balance_payment is the issue
    $obPayment15000 = $openingBalancePayments->where('credit', 15000)->first();
    if ($obPayment15000 && abs($remainingDifference - 15000) < 0.01) {
        echo "❌ ISSUE FOUND: Incorrect Opening Balance Payment of 15,000\n";
        echo "   - Entry ID: {$obPayment15000->id}\n";
        echo "   - This payment appears to be incorrectly recorded\n";
        echo "   - Removing this would bring balance to target\n";
        $issues[] = [
            'type' => 'incorrect_payment',
            'entry_id' => $obPayment15000->id,
            'amount' => 15000
        ];
        echo "\n";
    }
}

// ===================================================================
// STEP 6: PROPOSED FIX
// ===================================================================
echo "💡 STEP 6: PROPOSED FIX\n";
echo "═══════════════════════════════════════════════════════════════\n";

if (count($issues) === 0) {
    echo "✅ No issues detected!\n";
} else {
    echo "The following fixes will be applied:\n\n";
    
    $fixNumber = 1;
    foreach ($issues as $issue) {
        echo "Fix #{$fixNumber}:\n";
        if ($issue['type'] === 'duplicate_payment') {
            echo "  - Delete duplicate opening_balance_payment (ID: {$issue['entry_id']})\n";
            echo "  - Amount: " . number_format($issue['amount'], 2) . "\n";
        } elseif ($issue['type'] === 'incorrect_payment') {
            echo "  - Delete incorrect opening_balance_payment (ID: {$issue['entry_id']})\n";
            echo "  - Amount: " . number_format($issue['amount'], 2) . "\n";
        }
        $fixNumber++;
    }
    
    echo "\n";
    
    // Calculate final balance after all fixes
    $finalBalance = $calculatedBalance;
    foreach ($issues as $issue) {
        $finalBalance += $issue['amount'];
    }
    
    echo "Expected Balance After Fixes: " . number_format($finalBalance, 2) . "\n";
    echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
    echo "Match: " . (abs($finalBalance - $targetBalance) < 0.01 ? "✅ YES" : "❌ NO") . "\n";
    echo "\n";
    
    // Ask for confirmation
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "⚠️  Do you want to apply these fixes? (yes/no): ";
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
            foreach ($issues as $issue) {
                $entryId = $issue['entry_id'];
                
                // Delete the ledger entry
                $deleted = DB::table('ledgers')->where('id', $entryId)->delete();
                
                if ($deleted) {
                    echo "✅ Deleted ledger entry ID: {$entryId} (Amount: " . number_format($issue['amount'], 2) . ")\n";
                } else {
                    echo "❌ Failed to delete ledger entry ID: {$entryId}\n";
                }
            }
            
            DB::commit();
            
            echo "\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "✅ FIXES APPLIED SUCCESSFULLY!\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "\n";
            
            // Verify final balance
            echo "📊 FINAL VERIFICATION\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            
            $newBalance = BalanceHelper::getCustomerBalance($customerId);
            echo "New Balance: " . number_format($newBalance, 2) . "\n";
            echo "Target Balance: " . number_format($targetBalance, 2) . "\n";
            echo "Difference: " . number_format(abs($newBalance - $targetBalance), 2) . "\n";
            
            if (abs($newBalance - $targetBalance) < 0.01) {
                echo "\n";
                echo "🎉 SUCCESS! Balance matches target!\n";
            } else {
                echo "\n";
                echo "⚠️  Warning: Balance does not match target exactly\n";
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n";
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "Transaction rolled back. No changes were made.\n";
        }
        
    } else {
        echo "\n❌ Fix cancelled. No changes were made.\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ DIAGNOSTIC COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
