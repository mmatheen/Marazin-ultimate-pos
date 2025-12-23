<?php

/**
 * Fix Incorrect Ledger Entries - Cleanup Script
 * Run this ONCE to fix existing ledger data issues
 * 
 * Usage: php fix_ledger_entries.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Ledger Cleanup Script ===\n\n";

try {
    DB::beginTransaction();
    
    // ========================================
    // STEP 1: Fix duplicate payment_reversal entries with wrong CREDIT values
    // ========================================
    echo "Step 1: Fixing incorrect payment_reversal entries...\n";
    
    $wrongReversalEntries = Ledger::where('transaction_type', 'payment_reversal')
        ->where('credit', '>', 0) // These should be DEBIT, not CREDIT
        ->where('status', 'reversed')
        ->get();
    
    echo "Found {$wrongReversalEntries->count()} incorrect payment_reversal entries.\n";
    
    foreach ($wrongReversalEntries as $entry) {
        // Check if there's a corresponding correct payment_adjustment entry
        $correctEntry = Ledger::where('contact_id', $entry->contact_id)
            ->where('transaction_type', 'payment_adjustment')
            ->where('debit', $entry->credit) // Should have the same amount as DEBIT
            ->whereBetween('created_at', [
                $entry->created_at, 
                $entry->created_at->copy()->addSeconds(5)
            ])
            ->where('status', 'reversed')
            ->first();
        
        if ($correctEntry) {
            // We have a correct entry, just delete the wrong one
            echo "  - Deleting duplicate incorrect entry ID: {$entry->id}\n";
            $entry->update(['status' => 'deleted']);
        } else {
            // No correct entry exists, fix this one
            echo "  - Fixing entry ID: {$entry->id} - Converting CREDIT {$entry->credit} to DEBIT\n";
            $entry->update([
                'transaction_type' => 'payment_adjustment',
                'debit' => $entry->credit,
                'credit' => 0,
                'notes' => $entry->notes . ' [FIXED: Converted from incorrect payment_reversal]'
            ]);
        }
    }
    
    // ========================================
    // STEP 2: Find and mark duplicate payment entries
    // ========================================
    echo "\nStep 2: Finding duplicate payment entries...\n";
    
    // Get all reference numbers that have duplicate entries
    $allPaymentEntries = Ledger::where('transaction_type', 'payments')
        ->where('status', 'active')
        ->select('reference_no', 'contact_id')
        ->groupBy('reference_no', 'contact_id')
        ->havingRaw('COUNT(*) > 1')
        ->get();
    
    echo "Found {$allPaymentEntries->count()} reference numbers with duplicate payment entries.\n";
    
    foreach ($allPaymentEntries as $duplicate) {
        // Get all duplicates for this reference
        $entries = Ledger::where('reference_no', $duplicate->reference_no)
            ->where('contact_id', $duplicate->contact_id)
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get();
        
        echo "  - Reference: {$duplicate->reference_no} has {$entries->count()} entries\n";
        
        // Check if there's a corresponding active payment
        $activePayment = Payment::where('reference_no', $duplicate->reference_no)
            ->where('customer_id', $duplicate->contact_id)
            ->where('status', 'active')
            ->first();
        
        if ($activePayment) {
            // Keep only the entry that matches the active payment amount
            foreach ($entries as $index => $entry) {
                if (abs($entry->credit - $activePayment->amount) < 0.01) {
                    echo "    - Keeping entry ID: {$entry->id} (amount matches active payment)\n";
                } else {
                    echo "    - Marking duplicate entry ID: {$entry->id} as deleted\n";
                    $entry->update(['status' => 'deleted']);
                }
            }
        } else {
            // No active payment, keep the latest one and mark rest as deleted
            $latestEntry = $entries->last();
            foreach ($entries as $entry) {
                if ($entry->id === $latestEntry->id) {
                    echo "    - Keeping latest entry ID: {$entry->id}\n";
                } else {
                    echo "    - Marking duplicate entry ID: {$entry->id} as deleted\n";
                    $entry->update(['status' => 'deleted']);
                }
            }
        }
    }
    
    // ========================================
    // STEP 3: Find orphaned payment ledger entries (payment deleted but ledger active)
    // ========================================
    echo "\nStep 3: Finding orphaned payment ledger entries...\n";
    
    $paymentLedgerEntries = Ledger::where('transaction_type', 'payments')
        ->where('status', 'active')
        ->get();
    
    $orphanedCount = 0;
    foreach ($paymentLedgerEntries as $entry) {
        // Check if corresponding payment exists and is active
        $payment = Payment::where('reference_no', $entry->reference_no)
            ->where('customer_id', $entry->contact_id)
            ->where('status', 'active')
            ->first();
        
        if (!$payment) {
            echo "  - Marking orphaned entry ID: {$entry->id} as deleted\n";
            $entry->update([
                'status' => 'deleted',
                'notes' => $entry->notes . ' [ORPHANED: No active payment exists]'
            ]);
            $orphanedCount++;
        }
    }
    
    echo "Found and cleaned {$orphanedCount} orphaned payment entries.\n";
    
    // ========================================
    // STEP 4: Recalculate customer balances
    // ========================================
    echo "\nStep 4: Recalculating customer balances...\n";
    
    $affectedCustomers = Ledger::where('contact_type', 'customer')
        ->where('contact_id', '!=', 1) // Skip Walk-In
        ->distinct()
        ->pluck('contact_id');
    
    echo "Recalculating balances for {$affectedCustomers->count()} customers...\n";
    
    foreach ($affectedCustomers as $customerId) {
        $totalDebit = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->sum('debit');
        
        $totalCredit = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->sum('credit');
        
        $balance = $totalDebit - $totalCredit;
        
        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->balance = $balance;
            $customer->save();
            
            echo "  - Customer ID: {$customerId} - New balance: Rs. " . number_format($balance, 2) . "\n";
        }
    }
    
    DB::commit();
    
    echo "\n=== Cleanup Complete! ===\n";
    echo "Summary:\n";
    echo "- Fixed incorrect payment_reversal entries\n";
    echo "- Removed duplicate payment ledger entries\n";
    echo "- Cleaned orphaned payment entries\n";
    echo "- Recalculated customer balances\n";
    echo "\nPlease verify the data in your ledger table.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n\n=== ERROR ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTransaction rolled back. No changes were made.\n";
}

echo "\nDone!\n";
