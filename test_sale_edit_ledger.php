<?php
/**
 * TEST SCRIPT: Sale Edit Ledger Creation
 *
 * This script tests if ledger entries are properly created after sale edits
 * Simulates the sale edit process to identify where ledger creation fails
 */
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "========================================================================\n";
echo "SALE EDIT LEDGER CREATION TEST\n";
echo "========================================================================\n\n";

// Get a problematic sale (MLX-230)
$saleId = 636;
$sale = Sale::find($saleId);

if (!$sale) {
    die("Sale ID {$saleId} not found!\n");
}

echo "Testing Sale: {$sale->invoice_no} (ID: {$saleId})\n";
echo "Customer: {$sale->customer_id}\n";
echo "Current Amount: Rs. {$sale->final_total}\n";
echo "Status: {$sale->status}\n\n";

// Check current ledger state
echo "========================================================================\n";
echo "CURRENT LEDGER STATE\n";
echo "========================================================================\n\n";

$ledgers = Ledger::where('reference_no', $sale->invoice_no)
    ->where('transaction_type', 'sale')
    ->orderBy('created_at', 'desc')
    ->get();

if ($ledgers->isEmpty()) {
    echo "❌ NO LEDGER ENTRIES FOUND\n\n";
} else {
    foreach ($ledgers as $ledger) {
        $amount = $ledger->debit > 0 ? "DR: {$ledger->debit}" : "CR: {$ledger->credit}";
        $statusIcon = $ledger->status === 'active' ? '✅' : '🔄';
        echo "{$statusIcon} ID: {$ledger->id} | {$amount} | Status: {$ledger->status}\n";
        echo "   Created: {$ledger->created_at}\n";
        echo "   Notes: " . substr($ledger->notes, 0, 80) . "\n\n";
    }
}

$hasActiveLedger = $ledgers->where('status', 'active')->isNotEmpty();
$hasReversedLedger = $ledgers->where('status', 'reversed')->isNotEmpty();

echo "Has Active Entry: " . ($hasActiveLedger ? "✅ YES" : "❌ NO") . "\n";
echo "Has Reversed Entry: " . ($hasReversedLedger ? "✅ YES" : "❌ NO") . "\n\n";

if ($hasReversedLedger && !$hasActiveLedger) {
    echo "🔍 DIAGNOSIS: Sale was edited, old entry reversed, but NEW entry missing!\n";
    echo "This is the exact problem we need to fix.\n\n";
}

// TEST: Manually trigger ledger creation for this sale
echo "========================================================================\n";
echo "TEST: MANUAL LEDGER ENTRY CREATION\n";
echo "========================================================================\n\n";

try {
    DB::beginTransaction();

    // Check if active entry already exists
    $existingActive = Ledger::where('reference_no', $sale->invoice_no)
        ->where('transaction_type', 'sale')
        ->where('status', 'active')
        ->first();

    if ($existingActive) {
        echo "✅ Active entry already exists (ID: {$existingActive->id})\n";
        echo "No action needed.\n\n";
        DB::rollBack();
    } else {
        echo "Creating missing ledger entry...\n\n";

        // Use UnifiedLedgerService to create the entry
        $unifiedLedgerService = app(UnifiedLedgerService::class);

        echo "Step 1: Calling recordNewSaleEntry()...\n";
        $newEntry = $unifiedLedgerService->recordNewSaleEntry($sale);

        if ($newEntry) {
            echo "✅ SUCCESS: Ledger entry created!\n";
            echo "   Entry ID: {$newEntry->id}\n";
            echo "   Amount: Rs. {$newEntry->debit}\n";
            echo "   Status: {$newEntry->status}\n";
            echo "   Transaction Date: {$newEntry->transaction_date}\n";
            echo "   Created At: {$newEntry->created_at}\n\n";

            DB::commit();

            // Verify the entry
            $verification = Ledger::find($newEntry->id);
            if ($verification && $verification->status === 'active') {
                echo "✅ VERIFIED: Entry is properly saved and active\n\n";
            } else {
                echo "❌ ERROR: Entry saved but verification failed\n\n";
            }
        } else {
            echo "❌ FAILED: recordNewSaleEntry() returned NULL\n";
            echo "This means the ledger creation is failing silently.\n\n";
            DB::rollBack();
        }
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ EXCEPTION CAUGHT:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString() . "\n\n";
}

// Check final state
echo "========================================================================\n";
echo "FINAL LEDGER STATE\n";
echo "========================================================================\n\n";

$finalLedgers = Ledger::where('reference_no', $sale->invoice_no)
    ->where('transaction_type', 'sale')
    ->orderBy('transaction_date', 'desc')
    ->get();

foreach ($finalLedgers as $ledger) {
    $amount = $ledger->debit > 0 ? "DR: {$ledger->debit}" : "CR: {$ledger->credit}";
    $statusIcon = $ledger->status === 'active' ? '✅' : '🔄';
    echo "{$statusIcon} ID: {$ledger->id} | {$amount} | Status: {$ledger->status}\n";
}

echo "\n";

// Calculate customer balance
echo "========================================================================\n";
echo "CUSTOMER BALANCE CHECK\n";
echo "========================================================================\n\n";

$balance = DB::selectOne("
    SELECT COALESCE(SUM(debit) - SUM(credit), 0) as balance
    FROM ledgers
    WHERE contact_id = ?
        AND contact_type = 'customer'
        AND status = 'active'
", [$sale->customer_id])->balance;

echo "Customer {$sale->customer_id} Balance: Rs. " . number_format($balance, 2) . "\n\n";

// Test the updateSale method specifically
echo "========================================================================\n";
echo "TEST: UnifiedLedgerService->updateSale() METHOD\n";
echo "========================================================================\n\n";

echo "This method should:\n";
echo "1. Call reverseSale() to mark old entry as reversed\n";
echo "2. Call recordNewSaleEntry() to create new active entry\n\n";

try {
    // Find a sale with reversed but no active entry
    $testSale = Sale::where('customer_id', '!=', 1)
        ->where('status', 'final')
        ->whereHas('ledgers', function($query) {
            $query->where('status', 'reversed');
        })
        ->whereDoesntHave('ledgers', function($query) {
            $query->where('status', 'active')
                  ->where('transaction_type', 'sale');
        })
        ->first();

    if ($testSale) {
        echo "Found test sale: {$testSale->invoice_no}\n";
        echo "Amount: Rs. {$testSale->final_total}\n\n";

        echo "Simulating updateSale()...\n";
        $unifiedLedgerService = app(UnifiedLedgerService::class);

        DB::beginTransaction();

        $result = $unifiedLedgerService->updateSale($testSale);

        if ($result) {
            echo "✅ updateSale() returned a result: Entry ID {$result->id}\n";
            DB::commit();
        } else {
            echo "❌ updateSale() returned NULL - this is the bug!\n";
            DB::rollBack();
        }
    } else {
        echo "No test sales available (all sales have proper entries)\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR in updateSale():\n";
    echo $e->getMessage() . "\n\n";
}

echo "\n========================================================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "========================================================================\n\n";

if (!$hasActiveLedger && $hasReversedLedger) {
    echo "🔍 ROOT CAUSE IDENTIFIED:\n";
    echo "   When sale {$sale->invoice_no} was edited:\n";
    echo "   ✅ Old ledger entry was marked as 'reversed'\n";
    echo "   ❌ NEW ledger entry creation FAILED or returned NULL\n\n";

    echo "💡 FIX APPLIED IN CODE:\n";
    echo "   File: app/Services/UnifiedLedgerService.php\n";
    echo "   Method: updateSale() and recordNewSaleEntry()\n";
    echo "   - Added null checks and exception handling\n";
    echo "   - Added comprehensive logging\n";
    echo "   - Throws exception if ledger creation fails\n\n";

    echo "🔧 TO FIX EXISTING DATA:\n";
    echo "   Run: php fix_missing_ledger_entries.php --customer-id={$sale->customer_id}\n\n";
} else if ($hasActiveLedger) {
    echo "✅ This sale has a proper active ledger entry\n";
    echo "No issues detected.\n\n";
}

echo "========================================================================\n";
