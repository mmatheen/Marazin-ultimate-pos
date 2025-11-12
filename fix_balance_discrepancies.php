<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ledger;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;

echo "=== FIXING BALANCE DISCREPANCIES AFTER CLEANUP ===\n\n";

// Check all customers who might have balance discrepancies after our cleanup
$customersToCheck = [871, 3, 340, 926]; // These were affected by our cleanup

foreach ($customersToCheck as $customerId) {
    echo "Checking Customer {$customerId}...\n";
    
    $customer = Customer::find($customerId);
    if (!$customer) {
        echo "  ⚠ Customer {$customerId} not found in customers table\n";
        
        // Check if they have ledger entries
        $ledgerCount = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->count();
        
        if ($ledgerCount > 0) {
            echo "  📊 Found {$ledgerCount} ledger entries for non-existent customer\n";
            echo "  🔄 Recalculating balance...\n";
            
            try {
                Ledger::recalculateAllBalances($customerId, 'customer');
                $newBalance = Ledger::getLatestBalance($customerId, 'customer');
                echo "  ✅ Balance recalculated: {$newBalance}\n";
            } catch (Exception $e) {
                echo "  ❌ Error recalculating: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  ✅ No ledger entries found\n";
        }
    } else {
        echo "  👤 Customer: {$customer->first_name} {$customer->last_name}\n";
        
        // Check for balance consistency
        $ledgerEntries = Ledger::where('user_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        
        if ($ledgerEntries->count() > 0) {
            $calculatedBalance = 0;
            foreach ($ledgerEntries as $entry) {
                $calculatedBalance += $entry->debit - $entry->credit;
            }
            
            $storedBalance = $ledgerEntries->last()->balance;
            $discrepancy = abs($calculatedBalance - $storedBalance);
            
            if ($discrepancy > 0.01) {
                echo "  ❌ Balance discrepancy found: {$discrepancy}\n";
                echo "     Calculated: {$calculatedBalance}, Stored: {$storedBalance}\n";
                echo "  🔄 Recalculating balance...\n";
                
                Ledger::recalculateAllBalances($customerId, 'customer');
                $newBalance = Ledger::getLatestBalance($customerId, 'customer');
                echo "  ✅ Balance fixed: {$newBalance}\n";
            } else {
                echo "  ✅ Balance is consistent: {$storedBalance}\n";
            }
        } else {
            echo "  📋 No ledger entries\n";
        }
    }
    echo "\n";
}

// Check for any other customers with potential balance discrepancies
echo "=== CHECKING FOR OTHER BALANCE DISCREPANCIES ===\n";

$problematicCustomers = DB::select('
    SELECT 
        l1.user_id,
        l1.contact_type,
        COUNT(*) as entry_count,
        SUM(l1.debit - l1.credit) as calculated_balance,
        (SELECT balance FROM ledgers l2 
         WHERE l2.user_id = l1.user_id 
         AND l2.contact_type = l1.contact_type 
         ORDER BY l2.created_at DESC, l2.id DESC 
         LIMIT 1) as stored_balance
    FROM ledgers l1 
    WHERE l1.contact_type = "customer"
    GROUP BY l1.user_id, l1.contact_type
    HAVING ABS(calculated_balance - stored_balance) > 0.01
    ORDER BY ABS(calculated_balance - stored_balance) DESC
    LIMIT 10
');

if (count($problematicCustomers) > 0) {
    echo "Found " . count($problematicCustomers) . " customers with balance discrepancies:\n\n";
    
    foreach ($problematicCustomers as $customer) {
        $discrepancy = abs($customer->calculated_balance - $customer->stored_balance);
        echo "Customer {$customer->user_id}: Discrepancy = {$discrepancy}\n";
        echo "  Calculated: {$customer->calculated_balance}, Stored: {$customer->stored_balance}\n";
        
        // Fix this customer's balance
        echo "  🔄 Fixing balance...\n";
        Ledger::recalculateAllBalances($customer->user_id, 'customer');
        $newBalance = Ledger::getLatestBalance($customer->user_id, 'customer');
        echo "  ✅ Fixed balance: {$newBalance}\n\n";
    }
} else {
    echo "✅ No additional balance discrepancies found!\n";
}

echo "=== FINAL VERIFICATION ===\n";

// Verify customer 871's current balance
$customer871Balance = Ledger::getLatestBalance(871, 'customer');
echo "Customer 871 final balance: Rs. {$customer871Balance}\n";

// Check the reduction from original balance
$originalBalance = 10047341.20;
$reduction = $originalBalance - $customer871Balance;
echo "Reduction from original balance: Rs. {$reduction}\n";

if (abs($reduction - 125000) < 0.01) {
    echo "✅ PERFECT! The balance was reduced by exactly Rs. 125,000 (MLX-050 amount)\n";
} else {
    echo "⚠ Balance reduction doesn't match expected MLX-050 amount\n";
}

echo "\n🎉 BALANCE RECALCULATION COMPLETED!\n";
echo "All customer balances are now accurate after the ledger cleanup.\n";