<?php
/**
 * ===================================================================
 * 👤 CUSTOMER 7 SPECIFIC FIXER
 * ===================================================================
 * 
 * Check and fix Customer 7 (ALM RIYATH) ledger properly
 * Should only have: 3 bills + 1 opening balance = 4 entries
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;

echo "👤 CUSTOMER 7 (ALM RIYATH) SPECIFIC ANALYSIS\n";
echo "===========================================\n\n";

$customerId = 7;

// Get customer details
$customer = DB::table('customers')->where('id', $customerId)->first();
if (!$customer) {
    echo "❌ Customer not found!\n";
    exit(1);
}

echo "Customer: {$customer->first_name} {$customer->last_name}\n";
echo "Opening Balance: {$customer->opening_balance}\n\n";

// Check actual sales in sales table
echo "📋 ACTUAL SALES IN SALES TABLE:\n";
$actualSales = DB::table('sales')->where('customer_id', $customerId)->orderBy('created_at', 'asc')->get();
echo "Total Sales Records: " . $actualSales->count() . "\n";

if ($actualSales->count() > 0) {
    foreach ($actualSales as $i => $sale) {
        echo "   " . ($i + 1) . ". Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | Amount: {$sale->final_total} | Date: {$sale->created_at}\n";
    }
} else {
    echo "   ❌ No sales found in sales table!\n";
}
echo "\n";

// Check actual payments in payments table
echo "💰 ACTUAL PAYMENTS IN PAYMENTS TABLE:\n";
$actualPayments = DB::table('payments')->where('customer_id', $customerId)->orderBy('created_at', 'asc')->get();
echo "Total Payment Records: " . $actualPayments->count() . "\n";

if ($actualPayments->count() > 0) {
    foreach ($actualPayments as $i => $payment) {
        echo "   " . ($i + 1) . ". Payment ID: {$payment->id} | Ref: {$payment->reference_no} | Amount: {$payment->amount} | Date: {$payment->created_at}\n";
    }
} else {
    echo "   ❌ No payments found in payments table!\n";
}
echo "\n";

// Check current ledger entries (all statuses)
echo "📋 CURRENT LEDGER ENTRIES (ALL STATUSES):\n";
$allLedgerEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('created_at', 'asc')
    ->get();

echo "Total Ledger Entries: " . $allLedgerEntries->count() . "\n\n";

$activeSales = collect();
$activePayments = collect();
$activeOpeningBalance = collect();
$reversalEntries = collect();

foreach ($allLedgerEntries as $i => $entry) {
    $status = $entry->status === 'active' ? '✅' : '❌';
    echo "   {$status} " . ($i + 1) . ". ID: {$entry->id} | Type: {$entry->transaction_type} | Status: {$entry->status}\n";
    echo "      Reference: {$entry->reference_no}\n";
    echo "      Amount: D: {$entry->debit} | C: {$entry->credit}\n";
    echo "      Date: {$entry->created_at}\n";
    echo "      Notes: " . substr($entry->notes, 0, 100) . "\n\n";
    
    // Categorize entries
    if ($entry->status === 'active') {
        if ($entry->transaction_type === 'sale') {
            $activeSales->push($entry);
        } elseif (in_array($entry->transaction_type, ['payment', 'payments', 'sale_payment'])) {
            $activePayments->push($entry);
        } elseif ($entry->transaction_type === 'opening_balance') {
            $activeOpeningBalance->push($entry);
        }
    } else {
        $reversalEntries->push($entry);
    }
}

echo "📊 LEDGER ANALYSIS:\n";
echo "Active Sales: " . $activeSales->count() . "\n";
echo "Active Payments: " . $activePayments->count() . "\n";
echo "Active Opening Balance: " . $activeOpeningBalance->count() . "\n";
echo "Reversed/Inactive: " . $reversalEntries->count() . "\n\n";

// Expected vs Actual comparison
$expectedEntries = $actualSales->count() + $actualPayments->count() + 1; // +1 for opening balance
$actualActiveEntries = $activeSales->count() + $activePayments->count() + $activeOpeningBalance->count();

echo "🎯 EXPECTED vs ACTUAL:\n";
echo "Expected total entries: {$expectedEntries} (3 bills + 1 opening balance = 4)\n";
echo "Actual active entries: {$actualActiveEntries}\n";

if ($actualActiveEntries > $expectedEntries) {
    $duplicates = $actualActiveEntries - $expectedEntries;
    echo "❌ Found {$duplicates} duplicate/extra entries!\n\n";
    
    echo "🔧 RECOMMENDED FIX:\n";
    echo "===================\n";
    
    // Check for duplicate sales
    if ($activeSales->count() > $actualSales->count()) {
        echo "1. Sales duplicates found:\n";
        $salesByRef = $activeSales->groupBy('reference_no');
        foreach ($salesByRef as $ref => $entries) {
            if ($entries->count() > 1) {
                echo "   Reference {$ref}: {$entries->count()} entries (should be 1)\n";
                $latest = $entries->sortByDesc('created_at')->first();
                $toReverse = $entries->where('id', '!=', $latest->id);
                echo "   → Keep ID {$latest->id} (latest), reverse IDs: " . $toReverse->pluck('id')->implode(', ') . "\n";
            }
        }
        echo "\n";
    }
    
    // Check for duplicate payments
    if ($activePayments->count() > $actualPayments->count()) {
        echo "2. Payment duplicates found:\n";
        $paymentsByRef = $activePayments->groupBy('reference_no');
        foreach ($paymentsByRef as $ref => $entries) {
            if ($entries->count() > 1) {
                echo "   Reference {$ref}: {$entries->count()} entries (should be 1)\n";
                $latest = $entries->sortByDesc('created_at')->first();
                $toReverse = $entries->where('id', '!=', $latest->id);
                echo "   → Keep ID {$latest->id} (latest), reverse IDs: " . $toReverse->pluck('id')->implode(', ') . "\n";
            }
        }
        echo "\n";
    }
    
    // Check for duplicate opening balance
    if ($activeOpeningBalance->count() > 1) {
        echo "3. Opening balance duplicates found:\n";
        $latest = $activeOpeningBalance->sortByDesc('created_at')->first();
        $toReverse = $activeOpeningBalance->where('id', '!=', $latest->id);
        echo "   → Keep ID {$latest->id} (latest), reverse IDs: " . $toReverse->pluck('id')->implode(', ') . "\n\n";
    }
    
    echo "Do you want to fix these duplicates automatically? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation === 'yes') {
        echo "\n🔧 FIXING DUPLICATES...\n\n";
        
        DB::beginTransaction();
        try {
            $fixedCount = 0;
            
            // Fix sales duplicates
            $salesByRef = $activeSales->groupBy('reference_no');
            foreach ($salesByRef as $ref => $entries) {
                if ($entries->count() > 1) {
                    $latest = $entries->sortByDesc('created_at')->first();
                    $toReverse = $entries->where('id', '!=', $latest->id);
                    
                    foreach ($toReverse as $entry) {
                        DB::table('ledgers')->where('id', $entry->id)->update([
                            'status' => 'reversed',
                            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                        ]);
                        $fixedCount++;
                        echo "   ✅ Reversed sale entry ID {$entry->id} (ref: {$ref})\n";
                    }
                }
            }
            
            // Fix payment duplicates
            $paymentsByRef = $activePayments->groupBy('reference_no');
            foreach ($paymentsByRef as $ref => $entries) {
                if ($entries->count() > 1) {
                    $latest = $entries->sortByDesc('created_at')->first();
                    $toReverse = $entries->where('id', '!=', $latest->id);
                    
                    foreach ($toReverse as $entry) {
                        DB::table('ledgers')->where('id', $entry->id)->update([
                            'status' => 'reversed',
                            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                        ]);
                        $fixedCount++;
                        echo "   ✅ Reversed payment entry ID {$entry->id} (ref: {$ref})\n";
                    }
                }
            }
            
            // Fix opening balance duplicates
            if ($activeOpeningBalance->count() > 1) {
                $latest = $activeOpeningBalance->sortByDesc('created_at')->first();
                $toReverse = $activeOpeningBalance->where('id', '!=', $latest->id);
                
                foreach ($toReverse as $entry) {
                    DB::table('ledgers')->where('id', $entry->id)->update([
                        'status' => 'reversed',
                        'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                    ]);
                    $fixedCount++;
                    echo "   ✅ Reversed opening balance entry ID {$entry->id}\n";
                }
            }
            
            DB::commit();
            
            echo "\n✅ FIXED {$fixedCount} DUPLICATE ENTRIES!\n\n";
            
            // Show new balance
            $newBalance = DB::table('ledgers')
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->selectRaw('SUM(debit - credit) as balance')
                ->first();
                
            echo "📊 NEW CUSTOMER 7 STATUS:\n";
            echo "Active ledger entries: " . DB::table('ledgers')
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->count() . "\n";
            echo "New calculated balance: {$newBalance->balance}\n";
            echo "Expected balance: {$customer->opening_balance} (opening balance only)\n\n";
            
            echo "🎉 Customer 7 ledger has been cleaned up!\n";
            
        } catch (Exception $e) {
            DB::rollback();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "Changes rolled back.\n";
        }
    } else {
        echo "❌ Fix cancelled.\n";
    }
} else {
    echo "✅ Ledger appears to be correct!\n";
}

echo "\n✅ Analysis completed at " . date('Y-m-d H:i:s') . "\n";