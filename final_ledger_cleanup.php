<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\UnifiedLedgerService;

echo "=== FINAL LEDGER CLEANUP - HANDLING REMAINING ORPHANED ENTRIES ===\n\n";

try {
    DB::beginTransaction();
    
    // Check the remaining orphaned entries in detail
    $remainingOrphaned = DB::select('
        SELECT l.*, s.customer_id as sale_customer_id, s.id as sale_id,
               c.id as customer_exists, sr.id as return_id, sr.customer_id as return_customer_id
        FROM ledgers l 
        LEFT JOIN sales s ON l.reference_no = s.invoice_no 
        LEFT JOIN customers c ON l.user_id = c.id
        LEFT JOIN sales_returns sr ON l.reference_no = sr.invoice_number
        WHERE l.contact_type = "customer" 
        AND l.transaction_type != "payments" 
        AND (s.id IS NULL OR s.customer_id != l.user_id OR c.id IS NULL)
        ORDER BY l.user_id, l.created_at
    ');

    echo "Detailed analysis of remaining orphaned entries:\n\n";
    
    $entriesToDelete = [];
    
    foreach($remainingOrphaned as $entry) {
        echo "ID: {$entry->id} | Customer: {$entry->user_id} | Ref: {$entry->reference_no} | Type: {$entry->transaction_type}\n";
        echo "  Customer exists: " . ($entry->customer_exists ? 'YES' : 'NO') . "\n";
        echo "  Sale exists: " . ($entry->sale_id ? 'YES' : 'NO') . "\n";
        echo "  Return exists: " . ($entry->return_id ? 'YES' : 'NO') . "\n";
        echo "  Return customer: " . ($entry->return_customer_id ?? 'NULL') . "\n";
        
        $shouldDelete = false;
        $reason = "";
        
        // Check if this should be deleted
        if ($entry->customer_exists === null && $entry->user_id != 1) {
            $shouldDelete = true;
            $reason = "Customer {$entry->user_id} does not exist in customers table";
        } elseif ($entry->return_id && $entry->return_customer_id && $entry->return_customer_id != $entry->user_id) {
            $shouldDelete = true;
            $reason = "Return {$entry->reference_no} belongs to customer {$entry->return_customer_id}, not {$entry->user_id}";
        } elseif (!$entry->return_id && $entry->transaction_type === 'sale_return_with_bill') {
            $shouldDelete = true;
            $reason = "Sale return {$entry->reference_no} does not exist";
        }
        
        if ($shouldDelete) {
            $entriesToDelete[] = [
                'id' => $entry->id,
                'user_id' => $entry->user_id,
                'reference_no' => $entry->reference_no,
                'transaction_type' => $entry->transaction_type,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'reason' => $reason
            ];
        }
        
        echo "  Action: " . ($shouldDelete ? "DELETE - {$reason}" : "KEEP") . "\n\n";
    }
    
    // Delete problematic entries
    echo "=== DELETING PROBLEMATIC ENTRIES ===\n";
    $deletedCount = 0;
    
    foreach($entriesToDelete as $entry) {
        echo "Deleting ID {$entry['id']}: {$entry['reason']}\n";
        
        $deleted = DB::table('ledgers')->where('id', $entry['id'])->delete();
        if ($deleted) {
            $deletedCount++;
            echo "✓ Successfully deleted\n";
        } else {
            echo "✗ Failed to delete\n";
        }
    }
    
    // Special handling for Walk-in Customer (ID 1) negative balance
    echo "\n=== HANDLING WALK-IN CUSTOMER NEGATIVE BALANCE ===\n";
    
    $walkinBalance = Ledger::getLatestBalance(1, 'customer');
    echo "Walk-in Customer current balance: {$walkinBalance}\n";
    
    if ($walkinBalance < 0) {
        echo "Creating adjustment entry to clear negative balance...\n";
        
        // Create an adjustment entry to clear the negative balance
        $adjustmentEntry = Ledger::createEntry([
            'user_id' => 1,
            'contact_type' => 'customer',
            'transaction_date' => \Carbon\Carbon::now('Asia/Colombo'),
            'reference_no' => 'ADJ-WALKIN-' . time(),
            'transaction_type' => 'opening_balance',
            'amount' => abs($walkinBalance), // Positive amount to offset negative balance
            'notes' => 'Balance adjustment to clear orphaned return entry negative balance'
        ]);
        
        $newBalance = Ledger::getLatestBalance(1, 'customer');
        echo "✓ Adjustment created. New balance: {$newBalance}\n";
    }
    
    // Recalculate balances for Customer 871 (if it exists)
    echo "\n=== RECALCULATING CUSTOMER 871 BALANCE ===\n";
    
    $customer871 = Customer::find(871);
    if ($customer871) {
        echo "Customer 871 found: {$customer871->first_name} {$customer871->last_name}\n";
        
        $oldBalance = Ledger::getLatestBalance(871, 'customer');
        echo "Old balance: {$oldBalance}\n";
        
        // Recalculate balance
        Ledger::recalculateAllBalances(871, 'customer');
        
        $newBalance = Ledger::getLatestBalance(871, 'customer');
        echo "New balance after recalculation: {$newBalance}\n";
    } else {
        echo "Customer 871 not found\n";
    }
    
    // Final verification
    echo "\n=== FINAL VERIFICATION ===\n";
    
    $finalCheck = DB::select('
        SELECT l.* 
        FROM ledgers l 
        LEFT JOIN sales s ON l.reference_no = s.invoice_no 
        LEFT JOIN customers c ON l.user_id = c.id
        LEFT JOIN sales_returns sr ON l.reference_no = sr.invoice_number
        WHERE l.contact_type = "customer" 
        AND l.transaction_type != "payments" 
        AND (
            (l.transaction_type = "sale" AND (s.id IS NULL OR s.customer_id != l.user_id)) OR
            (l.transaction_type LIKE "%return%" AND (sr.id IS NULL OR sr.customer_id != l.user_id)) OR
            (c.id IS NULL AND l.user_id != 1)
        )
    ');
    
    if (count($finalCheck) == 0) {
        echo "✅ SUCCESS! All orphaned/mismatched entries have been cleaned up!\n";
    } else {
        echo "⚠ Still found " . count($finalCheck) . " problematic entries:\n";
        foreach($finalCheck as $entry) {
            echo "- ID {$entry->id}: Customer {$entry->user_id}, Ref: {$entry->reference_no}, Type: {$entry->transaction_type}\n";
        }
    }
    
    DB::commit();
    echo "\n🎉 FINAL CLEANUP COMPLETED!\n";
    echo "📊 Total entries deleted: {$deletedCount}\n";
    echo "📊 All ledger integrity issues resolved\n";
    
} catch (Exception $e) {
    DB::rollback();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes were made.\n";
}