<?php
/**
 * ===================================================================
 * 👤 CUSTOMER 2 (SITHIK STORE) SPECIFIC FIXER
 * ===================================================================
 * 
 * Check and fix Customer 2 ledger
 * Should only have opening balance 720 if all bills are settled
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "👤 CUSTOMER 2 (SITHIK STORE) ANALYSIS\n";
echo "=====================================\n\n";

$customerId = 2;

// Get customer details
$customer = DB::table('customers')->where('id', $customerId)->first();
if (!$customer) {
    echo "❌ Customer not found!\n";
    exit(1);
}

echo "Customer: {$customer->first_name} {$customer->last_name}\n";
echo "Expected Opening Balance: {$customer->opening_balance}\n";
echo "Status: All bills settled (should only show opening balance)\n\n";

// Check actual sales in sales table
echo "📋 ACTUAL SALES IN SALES TABLE:\n";
$actualSales = DB::table('sales')->where('customer_id', $customerId)->orderBy('created_at', 'asc')->get();
echo "Total Sales Records: " . $actualSales->count() . "\n";

if ($actualSales->count() > 0) {
    $totalSalesAmount = 0;
    foreach ($actualSales as $i => $sale) {
        $totalSalesAmount += $sale->final_total;
        echo "   " . ($i + 1) . ". Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | Amount: {$sale->final_total} | Date: {$sale->created_at}\n";
    }
    echo "   📊 Total Sales Amount: {$totalSalesAmount}\n";
} else {
    echo "   ✅ No sales found\n";
}
echo "\n";

// Check actual payments in payments table
echo "💰 ACTUAL PAYMENTS IN PAYMENTS TABLE:\n";
$actualPayments = DB::table('payments')->where('customer_id', $customerId)->orderBy('created_at', 'asc')->get();
echo "Total Payment Records: " . $actualPayments->count() . "\n";

if ($actualPayments->count() > 0) {
    $totalPaymentsAmount = 0;
    foreach ($actualPayments as $i => $payment) {
        $totalPaymentsAmount += $payment->amount;
        echo "   " . ($i + 1) . ". Payment ID: {$payment->id} | Ref: {$payment->reference_no} | Amount: {$payment->amount} | Date: {$payment->created_at}\n";
    }
    echo "   📊 Total Payments Amount: {$totalPaymentsAmount}\n";
} else {
    echo "   ✅ No payments found\n";
}
echo "\n";

// Check if bills are actually settled
$totalSalesAmount = $actualSales->sum('final_total');
$totalPaymentsAmount = $actualPayments->sum('amount');
$netAmount = $totalSalesAmount - $totalPaymentsAmount;

echo "📊 SALES vs PAYMENTS ANALYSIS:\n";
echo "Total Sales: {$totalSalesAmount}\n";
echo "Total Payments: {$totalPaymentsAmount}\n";
echo "Net Amount (Sales - Payments): {$netAmount}\n";

if (abs($netAmount) < 1) {
    echo "✅ Bills are settled! Customer should only have opening balance.\n";
    $expectedBalance = $customer->opening_balance;
} else {
    echo "⚠️  Bills not fully settled. Outstanding: {$netAmount}\n";
    $expectedBalance = $customer->opening_balance + $netAmount;
}
echo "Expected final balance: {$expectedBalance}\n\n";

// Check current ledger entries
echo "📋 CURRENT LEDGER ENTRIES:\n";
$allLedgerEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->orderBy('created_at', 'asc')
    ->get();

echo "Total Ledger Entries: " . $allLedgerEntries->count() . "\n";
echo "Active Entries: " . $allLedgerEntries->where('status', 'active')->count() . "\n";
echo "Reversed Entries: " . $allLedgerEntries->where('status', 'reversed')->count() . "\n\n";

// Calculate current balance
$currentBalance = $allLedgerEntries->where('status', 'active')->sum(function($entry) {
    return $entry->debit - $entry->credit;
});

echo "💰 CURRENT BALANCE: {$currentBalance}\n";
echo "💰 EXPECTED BALANCE: {$expectedBalance}\n";
echo "💰 DIFFERENCE: " . ($currentBalance - $expectedBalance) . "\n\n";

// Show all ledger entries
$salesCount = 0;
$paymentsCount = 0;
$openingCount = 0;
$otherCount = 0;

echo "📝 DETAILED LEDGER ENTRIES:\n";
foreach ($allLedgerEntries as $i => $entry) {
    $status = $entry->status === 'active' ? '✅' : '❌';
    echo "   {$status} " . ($i + 1) . ". ID: {$entry->id} | Type: {$entry->transaction_type} | Status: {$entry->status}\n";
    echo "      Reference: {$entry->reference_no}\n";
    echo "      Amount: D: {$entry->debit} | C: {$entry->credit}\n";
    echo "      Date: {$entry->created_at}\n";
    echo "      Notes: " . substr($entry->notes, 0, 100) . "...\n\n";
    
    if ($entry->status === 'active') {
        if ($entry->transaction_type === 'sale') {
            $salesCount++;
        } elseif (in_array($entry->transaction_type, ['payment', 'payments', 'sale_payment'])) {
            $paymentsCount++;
        } elseif ($entry->transaction_type === 'opening_balance') {
            $openingCount++;
        } else {
            $otherCount++;
        }
    }
}

echo "📊 ACTIVE ENTRIES BREAKDOWN:\n";
echo "Sales: {$salesCount} (should be " . $actualSales->count() . ")\n";
echo "Payments: {$paymentsCount} (should be " . $actualPayments->count() . ")\n";
echo "Opening Balance: {$openingCount} (should be 1)\n";
echo "Other: {$otherCount}\n\n";

// Check for duplicates
$duplicates = [];

// Check sales duplicates
$activeSales = $allLedgerEntries->where('status', 'active')->where('transaction_type', 'sale');
$salesByRef = $activeSales->groupBy('reference_no');
foreach ($salesByRef as $ref => $entries) {
    if ($entries->count() > 1) {
        $duplicates['sales'][$ref] = $entries->pluck('id')->toArray();
    }
}

// Check payment duplicates
$activePayments = $allLedgerEntries->where('status', 'active')->whereIn('transaction_type', ['payment', 'payments', 'sale_payment']);
$paymentsByRef = $activePayments->groupBy('reference_no');
foreach ($paymentsByRef as $ref => $entries) {
    if ($entries->count() > 1) {
        $duplicates['payments'][$ref] = $entries->pluck('id')->toArray();
    }
}

// Check opening balance duplicates
$activeOpening = $allLedgerEntries->where('status', 'active')->where('transaction_type', 'opening_balance');
if ($activeOpening->count() > 1) {
    $duplicates['opening'] = $activeOpening->pluck('id')->toArray();
}

// Show duplicates
if (!empty($duplicates)) {
    echo "🔴 DUPLICATES FOUND:\n";
    foreach ($duplicates as $type => $refs) {
        echo "\n{$type} duplicates:\n";
        if ($type === 'opening') {
            echo "   Opening balance: " . count($refs) . " entries (IDs: " . implode(', ', $refs) . ")\n";
        } else {
            foreach ($refs as $ref => $ids) {
                echo "   {$ref}: " . count($ids) . " entries (IDs: " . implode(', ', $ids) . ")\n";
            }
        }
    }
    echo "\n";
}

// If customer should only have opening balance and bills are settled
if (abs($netAmount) < 1 && $currentBalance != $customer->opening_balance) {
    echo "🚨 CRITICAL ISSUE: Bills are settled but balance is wrong!\n";
    echo "Expected: Only opening balance of {$customer->opening_balance}\n";
    echo "Current: {$currentBalance}\n\n";
    
    echo "🔧 RECOMMENDED ACTIONS:\n";
    echo "1. Remove all sale/payment duplicates\n";
    echo "2. Keep only one opening balance entry\n";
    echo "3. If bills are truly settled, only opening balance should remain\n\n";
}

// Offer to fix
if (!empty($duplicates) || abs($currentBalance - $expectedBalance) > 1) {
    echo "Do you want to clean up the duplicates and fix the balance? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation === 'yes') {
        echo "\n🔧 FIXING CUSTOMER 2 LEDGER...\n\n";
        
        DB::beginTransaction();
        try {
            $fixedCount = 0;
            
            // Fix sales duplicates
            if (isset($duplicates['sales'])) {
                echo "Fixing sales duplicates...\n";
                foreach ($duplicates['sales'] as $ref => $ids) {
                    // Keep the latest entry
                    $entries = $activeSales->where('reference_no', $ref)->sortByDesc('created_at');
                    $keepId = $entries->first()->id;
                    $reverseIds = array_filter($ids, fn($id) => $id != $keepId);
                    
                    foreach ($reverseIds as $id) {
                        DB::table('ledgers')->where('id', $id)->update([
                            'status' => 'reversed',
                            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                        ]);
                        $fixedCount++;
                        echo "   ✅ Reversed sale entry ID {$id}\n";
                    }
                }
            }
            
            // Fix payment duplicates
            if (isset($duplicates['payments'])) {
                echo "Fixing payment duplicates...\n";
                foreach ($duplicates['payments'] as $ref => $ids) {
                    // Keep the latest entry
                    $entries = $activePayments->where('reference_no', $ref)->sortByDesc('created_at');
                    $keepId = $entries->first()->id;
                    $reverseIds = array_filter($ids, fn($id) => $id != $keepId);
                    
                    foreach ($reverseIds as $id) {
                        DB::table('ledgers')->where('id', $id)->update([
                            'status' => 'reversed',
                            'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                        ]);
                        $fixedCount++;
                        echo "   ✅ Reversed payment entry ID {$id}\n";
                    }
                }
            }
            
            // Fix opening balance duplicates
            if (isset($duplicates['opening'])) {
                echo "Fixing opening balance duplicates...\n";
                $entries = $activeOpening->sortByDesc('created_at');
                $keepId = $entries->first()->id;
                $reverseIds = array_filter($duplicates['opening'], fn($id) => $id != $keepId);
                
                foreach ($reverseIds as $id) {
                    DB::table('ledgers')->where('id', $id)->update([
                        'status' => 'reversed',
                        'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                    ]);
                    $fixedCount++;
                    echo "   ✅ Reversed opening balance entry ID {$id}\n";
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
                
            $newActiveCount = DB::table('ledgers')
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->count();
                
            echo "📊 NEW CUSTOMER 2 STATUS:\n";
            echo "Active ledger entries: {$newActiveCount}\n";
            echo "New calculated balance: {$newBalance->balance}\n";
            echo "Expected balance: {$expectedBalance}\n";
            
            if (abs($newBalance->balance - $expectedBalance) < 1) {
                echo "✅ Balance is now correct!\n";
            } else {
                echo "⚠️  Balance still needs adjustment\n";
            }
            
            echo "\n🎉 Customer 2 ledger has been cleaned up!\n";
            
        } catch (Exception $e) {
            DB::rollback();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "Changes rolled back.\n";
        }
    } else {
        echo "❌ Fix cancelled.\n";
    }
} else {
    echo "✅ Customer 2 ledger appears to be correct!\n";
}

echo "\n✅ Analysis completed at " . date('Y-m-d H:i:s') . "\n";