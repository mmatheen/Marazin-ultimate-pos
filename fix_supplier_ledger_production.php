<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Supplier;
use App\Helpers\BalanceHelper;

// Check if running with --fix flag
$shouldFix = in_array('--fix', $argv);
$specificSupplierId = null;

// Check for specific supplier ID
foreach ($argv as $arg) {
    if (is_numeric($arg)) {
        $specificSupplierId = (int)$arg;
        break;
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "🔧 SUPPLIER LEDGER FIX TOOL - PRODUCTION READY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Mode: " . ($shouldFix ? "🔨 FIX MODE" : "🔍 ANALYSIS MODE (read-only)") . "\n";
if ($specificSupplierId) {
    echo "Target: Supplier ID {$specificSupplierId}\n";
} else {
    echo "Target: All suppliers\n";
}
echo "═══════════════════════════════════════════════════════════════\n\n";

// Find suppliers with ledger issues
$suppliersToCheck = $specificSupplierId 
    ? Supplier::where('id', $specificSupplierId)->get()
    : Supplier::all();

$issuesFound = [];
$fixedCount = 0;

foreach ($suppliersToCheck as $supplier) {
    $supplierId = $supplier->id;
    $supplierName = trim($supplier->first_name . ' ' . $supplier->last_name);
    
    // Get opening balance ledger entries
    $openingBalanceLedgers = DB::table('ledgers')
        ->where('contact_id', $supplierId)
        ->where('contact_type', 'supplier')
        ->whereIn('transaction_type', ['opening_balance', 'opening_balance_adjustment'])
        ->orderBy('id')
        ->get();
    
    if ($openingBalanceLedgers->isEmpty()) {
        continue; // No opening balance entries, skip
    }
    
    // Check for issues
    $activeCount = $openingBalanceLedgers->where('status', 'active')->count();
    $reversedCount = $openingBalanceLedgers->where('status', 'reversed')->count();
    
    $activeBalance = $openingBalanceLedgers
        ->where('status', 'active')
        ->sum(function($l) { return $l->credit - $l->debit; });
    
    $tableMismatch = abs($activeBalance - $supplier->opening_balance) > 0.01;
    $noActiveEntries = ($activeCount === 0 && $reversedCount > 0);
    
    // Issue detection
    if ($noActiveEntries || $tableMismatch) {
        $issue = [
            'id' => $supplierId,
            'name' => $supplierName,
            'ledger_entries' => $openingBalanceLedgers->count(),
            'active_entries' => $activeCount,
            'reversed_entries' => $reversedCount,
            'active_balance' => $activeBalance,
            'table_balance' => $supplier->opening_balance,
            'issue_type' => []
        ];
        
        if ($noActiveEntries) {
            $issue['issue_type'][] = 'NO_ACTIVE_ENTRIES';
        }
        if ($tableMismatch) {
            $issue['issue_type'][] = 'BALANCE_MISMATCH';
        }
        
        $issuesFound[] = $issue;
        
        // Display issue
        echo "❌ ISSUE FOUND - Supplier ID {$supplierId}: {$supplierName}\n";
        echo str_repeat("─", 63) . "\n";
        echo "  Total ledger entries: {$openingBalanceLedgers->count()}\n";
        echo "  Active: {$activeCount} | Reversed: {$reversedCount}\n";
        echo "  Active balance: Rs. " . number_format($activeBalance, 2) . "\n";
        echo "  Table balance: Rs. " . number_format($supplier->opening_balance, 2) . "\n";
        echo "  Issues: " . implode(', ', $issue['issue_type']) . "\n\n";
        
        // Show ledger entries
        echo "  Ledger Entries:\n";
        foreach ($openingBalanceLedgers as $idx => $ledger) {
            printf("    %d. ID %-4d | %s | Dr: %10s Cr: %10s | %-8s | %s\n",
                $idx + 1,
                $ledger->id,
                substr($ledger->transaction_date, 0, 10),
                $ledger->debit > 0 ? number_format($ledger->debit, 2) : '-',
                $ledger->credit > 0 ? number_format($ledger->credit, 2) : '-',
                strtoupper($ledger->status),
                $ledger->transaction_type === 'opening_balance_adjustment' ? 'ADJUSTMENT' : 'OPENING'
            );
        }
        echo "\n";
        
        // FIX LOGIC
        if ($shouldFix) {
            echo "  🔨 FIXING...\n";
            
            DB::beginTransaction();
            try {
                // Strategy: If no active entries, find the last entry and make it active
                if ($noActiveEntries) {
                    $lastEntry = $openingBalanceLedgers->last();
                    
                    // Update last entry to active
                    DB::table('ledgers')
                        ->where('id', $lastEntry->id)
                        ->update([
                            'status' => 'active',
                            'notes' => ($lastEntry->notes ?: '') . ' [FIXED: Made active on ' . now()->format('Y-m-d H:i:s') . ']',
                            'updated_at' => now()
                        ]);
                    
                    echo "     ✅ Updated entry ID {$lastEntry->id} to status='active'\n";
                    
                    // Recalculate balance
                    $newActiveBalance = ($lastEntry->credit - $lastEntry->debit);
                    
                    // Update supplier table
                    Supplier::where('id', $supplierId)->update([
                        'opening_balance' => $newActiveBalance
                    ]);
                    
                    echo "     ✅ Updated supplier table opening_balance to Rs. " . number_format($newActiveBalance, 2) . "\n";
                }
                // If there's a mismatch but active entries exist, sync the table
                elseif ($tableMismatch && $activeCount > 0) {
                    Supplier::where('id', $supplierId)->update([
                        'opening_balance' => $activeBalance
                    ]);
                    
                    echo "     ✅ Synced supplier table opening_balance to Rs. " . number_format($activeBalance, 2) . "\n";
                }
                
                DB::commit();
                $fixedCount++;
                echo "  ✅ FIXED SUCCESSFULLY!\n\n";
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "  ❌ FIX FAILED: " . $e->getMessage() . "\n\n";
            }
        } else {
            echo "  💡 Run with --fix flag to repair this issue\n\n";
        }
    }
}

// Get current balance using BalanceHelper (source of truth)
if ($specificSupplierId) {
    $calculatedBalance = BalanceHelper::getSupplierBalance($specificSupplierId);
    $supplier = Supplier::find($specificSupplierId);
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "🔍 FINAL VERIFICATION - Supplier {$specificSupplierId}\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    if ($supplier) {
        echo "Supplier: " . trim($supplier->first_name . ' ' . $supplier->last_name) . "\n";
        echo "Table opening_balance: Rs. " . number_format($supplier->opening_balance, 2) . "\n";
        echo "BalanceHelper calculated: Rs. " . number_format($calculatedBalance, 2) . "\n";
        
        $match = abs($calculatedBalance - $supplier->opening_balance) < 0.01;
        echo "\nStatus: " . ($match ? "✅ VERIFIED CORRECT" : "⚠️  Still has discrepancy") . "\n";
        
        if (!$match) {
            echo "\nNote: Run the full balance test to see all transactions:\n";
            echo "  php verify_supplier_detailed.php {$specificSupplierId}\n";
        }
    }
    echo "\n";
}

// Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Suppliers checked: " . $suppliersToCheck->count() . "\n";
echo "Issues found: " . count($issuesFound) . "\n";

if ($shouldFix) {
    echo "Successfully fixed: {$fixedCount}\n";
    echo "\n✅ Fix operation completed!\n";
} else {
    echo "\n💡 To fix issues, run:\n";
    if ($specificSupplierId) {
        echo "   php fix_supplier_ledger_production.php {$specificSupplierId} --fix\n";
    } else {
        echo "   php fix_supplier_ledger_production.php --fix\n";
    }
}

if (count($issuesFound) === 0) {
    echo "\n✅ All supplier opening balance ledgers are correct!\n";
}

echo "═══════════════════════════════════════════════════════════════\n\n";
