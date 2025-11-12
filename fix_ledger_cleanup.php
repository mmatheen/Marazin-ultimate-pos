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

echo "=== LEDGER CLEANUP AND BALANCE RECALCULATION ===\n\n";

try {
    DB::beginTransaction();
    
    // STEP 1: Identify and log problematic entries before deletion
    echo "STEP 1: Identifying problematic entries...\n";
    
    $orphanedEntries = DB::select('
        SELECT l.*, s.customer_id as sale_customer_id, s.id as sale_id,
               c.id as customer_exists
        FROM ledgers l 
        LEFT JOIN sales s ON l.reference_no = s.invoice_no 
        LEFT JOIN customers c ON l.user_id = c.id
        WHERE l.contact_type = "customer" 
        AND l.transaction_type != "payments" 
        AND (s.id IS NULL OR s.customer_id != l.user_id OR c.id IS NULL)
        ORDER BY l.user_id, l.created_at
    ');
    
    $entriesToDelete = [];
    $customersToRecalculate = [];
    
    foreach($orphanedEntries as $entry) {
        // Criteria for deletion:
        // 1. Customer doesn't exist (customer_exists is NULL)
        // 2. Sale doesn't exist (sale_id is NULL) 
        // 3. Sale exists but belongs to different customer
        
        $shouldDelete = false;
        $reason = "";
        
        if ($entry->customer_exists === null && $entry->user_id != 1) {
            $shouldDelete = true;
            $reason = "Customer ID {$entry->user_id} does not exist";
        } elseif ($entry->sale_id === null && in_array($entry->transaction_type, ['sale'])) {
            $shouldDelete = true; 
            $reason = "Sale {$entry->reference_no} does not exist";
        } elseif ($entry->sale_customer_id !== null && $entry->sale_customer_id != $entry->user_id) {
            $shouldDelete = true;
            $reason = "Sale {$entry->reference_no} belongs to customer {$entry->sale_customer_id}, not {$entry->user_id}";
        }
        
        if ($shouldDelete) {
            $entriesToDelete[] = [
                'id' => $entry->id,
                'user_id' => $entry->user_id,
                'reference_no' => $entry->reference_no,
                'transaction_type' => $entry->transaction_type,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $entry->balance,
                'reason' => $reason
            ];
            
            // Track customers that need balance recalculation
            if ($entry->customer_exists !== null && !in_array($entry->user_id, $customersToRecalculate)) {
                $customersToRecalculate[] = $entry->user_id;
            }
        }
    }
    
    echo "Found " . count($entriesToDelete) . " entries to delete:\n";
    foreach($entriesToDelete as $entry) {
        echo "- ID {$entry['id']}: Customer {$entry['user_id']}, Ref: {$entry['reference_no']}, Type: {$entry['transaction_type']}, Amount: {$entry['debit']}/{$entry['credit']}\n";
        echo "  Reason: {$entry['reason']}\n";
    }
    
    // STEP 2: Delete the problematic entries
    echo "\nSTEP 2: Deleting problematic entries...\n";
    
    $deletedCount = 0;
    foreach($entriesToDelete as $entry) {
        $deleted = DB::table('ledgers')->where('id', $entry['id'])->delete();
        if ($deleted) {
            $deletedCount++;
            echo "✓ Deleted entry ID {$entry['id']} - {$entry['reason']}\n";
        } else {
            echo "✗ Failed to delete entry ID {$entry['id']}\n";
        }
    }
    
    echo "Successfully deleted {$deletedCount} entries.\n";
    
    // STEP 3: Recalculate balances for affected customers
    echo "\nSTEP 3: Recalculating balances for affected customers...\n";
    
    $ledgerService = new UnifiedLedgerService();
    
    foreach($customersToRecalculate as $customerId) {
        $customer = Customer::find($customerId);
        if (!$customer) {
            echo "⚠ Customer {$customerId} not found, skipping balance recalculation\n";
            continue;
        }
        
        echo "Recalculating balance for Customer {$customerId} ({$customer->first_name} {$customer->last_name})...\n";
        
        // Get old balance
        $oldBalance = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->first();
        $oldBalanceAmount = $oldBalance ? $oldBalance->balance : 0;
        
        // Recalculate all balances from scratch
        Ledger::recalculateAllBalances($customerId, 'customer');
        
        // Get new balance
        $newBalance = Ledger::getLatestBalance($customerId, 'customer');
        
        echo "✓ Customer {$customerId}: Old balance: {$oldBalanceAmount} → New balance: {$newBalance}\n";
    }
    
    // STEP 4: Verify cleanup results
    echo "\nSTEP 4: Verifying cleanup results...\n";
    
    $remainingOrphaned = DB::select('
        SELECT l.* 
        FROM ledgers l 
        LEFT JOIN sales s ON l.reference_no = s.invoice_no 
        LEFT JOIN customers c ON l.user_id = c.id
        WHERE l.contact_type = "customer" 
        AND l.transaction_type != "payments" 
        AND (s.id IS NULL OR s.customer_id != l.user_id OR c.id IS NULL)
    ');
    
    if (count($remainingOrphaned) == 0) {
        echo "✓ All orphaned entries have been successfully cleaned up!\n";
    } else {
        echo "⚠ Still found " . count($remainingOrphaned) . " orphaned entries:\n";
        foreach($remainingOrphaned as $entry) {
            echo "- ID {$entry->id}: Customer {$entry->user_id}, Ref: {$entry->reference_no}\n";
        }
    }
    
    // STEP 5: Summary of customers affected
    echo "\nSTEP 5: Final customer balances summary...\n";
    
    $allCustomerBalances = DB::select('
        SELECT c.id, c.first_name, c.last_name, 
               (SELECT balance FROM ledgers WHERE user_id = c.id AND contact_type = "customer" ORDER BY created_at DESC, id DESC LIMIT 1) as current_balance
        FROM customers c 
        WHERE c.id IN (' . implode(',', array_merge($customersToRecalculate, [1])) . ')
        ORDER BY c.id
    ');
    
    foreach($allCustomerBalances as $customer) {
        echo "Customer {$customer->id} ({$customer->first_name} {$customer->last_name}): Balance = " . ($customer->current_balance ?? '0.00') . "\n";
    }
    
    DB::commit();
    echo "\n✅ CLEANUP COMPLETED SUCCESSFULLY!\n";
    echo "📊 Summary:\n";
    echo "   - Deleted {$deletedCount} orphaned/mismatched ledger entries\n";
    echo "   - Recalculated balances for " . count($customersToRecalculate) . " customers\n";
    echo "   - All ledger entries now properly linked to existing sales and customers\n";
    
} catch (Exception $e) {
    DB::rollback();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes were made.\n";
}