<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING SITHIK STORE - REMOVING DUPLICATE LEDGER ENTRIES ===\n\n";

DB::beginTransaction();

try {
    $customer = DB::table('customers')->where('id', 2)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "Current Balance: {$customer->current_balance}\n\n";
        
        echo "BEFORE CLEANUP:\n";
        $ledgersBefore = DB::table('ledgers')->where('user_id', 2)->where('contact_type', 'customer')->count();
        echo "Total Ledger Entries: {$ledgersBefore}\n\n";
        
        // Get all ledger entries ordered by creation time
        $ledgers = DB::table('ledgers')
                    ->where('user_id', 2)
                    ->where('contact_type', 'customer')
                    ->orderBy('id')
                    ->get();
        
        echo "IDENTIFYING DUPLICATES TO REMOVE:\n";
        
        // Group by reference_no and keep only the FIRST occurrence
        $ledgerGroups = $ledgers->groupBy('reference_no');
        $toDelete = [];
        $toKeep = [];
        
        foreach ($ledgerGroups as $refNo => $entries) {
            if ($entries->count() > 1) {
                // Keep the first entry (lowest ID), delete the rest
                $firstEntry = $entries->first();
                $toKeep[] = $firstEntry;
                
                echo "Reference {$refNo}:\n";
                echo "  ✅ KEEPING: ID {$firstEntry->id} (first entry)\n";
                
                foreach ($entries->skip(1) as $duplicate) {
                    echo "  ❌ DELETING: ID {$duplicate->id} (duplicate)\n";
                    $toDelete[] = $duplicate->id;
                }
            } else {
                // Single entry, keep it
                $toKeep[] = $entries->first();
                echo "Reference {$refNo}: ✅ Single entry - keeping ID {$entries->first()->id}\n";
            }
        }
        
        echo "\nSUMMARY:\n";
        echo "Entries to keep: " . count($toKeep) . "\n";
        echo "Entries to delete: " . count($toDelete) . "\n\n";
        
        // Delete duplicate entries
        if (count($toDelete) > 0) {
            $deleted = DB::table('ledgers')->whereIn('id', $toDelete)->delete();
            echo "✓ Deleted {$deleted} duplicate ledger entries\n";
        } else {
            echo "No duplicates found to delete\n";
        }
        
        // Rebuild balance progression for remaining entries
        echo "\nREBUILDING LEDGER BALANCE PROGRESSION:\n";
        
        $remainingLedgers = DB::table('ledgers')
                             ->where('user_id', 2)
                             ->where('contact_type', 'customer')
                             ->orderBy('transaction_date')
                             ->orderBy('id')
                             ->get();
        
        $balance = 0;
        foreach ($remainingLedgers as $ledger) {
            $balance += $ledger->debit - $ledger->credit;
            
            DB::table('ledgers')
              ->where('id', $ledger->id)
              ->update(['balance' => $balance]);
            
            echo "✓ Updated Ledger ID {$ledger->id}: {$ledger->reference_no} -> Balance: {$balance}\n";
        }
        
        // Verify final balance matches customer balance
        $finalBalance = $balance;
        echo "\nVERIFICATION:\n";
        echo "Calculated Final Balance: {$finalBalance}\n";
        echo "Customer DB Balance: {$customer->current_balance}\n";
        
        if ($finalBalance == $customer->current_balance) {
            echo "✅ Balances match perfectly!\n";
        } else {
            echo "⚠️ Balance mismatch - updating customer balance\n";
            DB::table('customers')
              ->where('id', 2)
              ->update(['current_balance' => $finalBalance]);
            echo "✓ Updated customer balance to: {$finalBalance}\n";
        }
        
        // Final verification
        echo "\nAFTER CLEANUP:\n";
        $ledgersAfter = DB::table('ledgers')->where('user_id', 2)->where('contact_type', 'customer')->count();
        $customerAfter = DB::table('customers')->where('id', 2)->first();
        
        echo "Total Ledger Entries: {$ledgersAfter} (was {$ledgersBefore})\n";
        echo "Customer Balance: {$customerAfter->current_balance}\n";
        echo "Removed: " . ($ledgersBefore - $ledgersAfter) . " duplicate entries\n";
        
        // Check for any remaining duplicates
        $duplicateCheck = DB::table('ledgers')
                           ->select('reference_no', DB::raw('COUNT(*) as count'))
                           ->where('user_id', 2)
                           ->where('contact_type', 'customer')
                           ->groupBy('reference_no')
                           ->having('count', '>', 1)
                           ->get();
        
        if ($duplicateCheck->count() == 0) {
            echo "✅ No remaining duplicates found\n";
            
            DB::commit();
            echo "\n🎉 SITHIK STORE successfully cleaned!\n";
            echo "📝 Summary: Removed " . count($toDelete) . " duplicate ledger entries, balance verified\n";
        } else {
            echo "❌ Still has duplicates:\n";
            foreach ($duplicateCheck as $dup) {
                echo "  - {$dup->reference_no}: {$dup->count} entries\n";
            }
            DB::rollback();
            echo "\n❌ Cleanup failed - rolling back\n";
        }
        
    } else {
        echo "Customer ID 2 not found!\n";
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}