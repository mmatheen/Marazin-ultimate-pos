<?php
/**
 * ===================================================================
 * 🔧 REMOVE INCORRECT RETURN FROM CUSTOMER 2
 * ===================================================================
 * 
 * Remove the sale return SR-0001 that belongs to another customer
 * from Customer 2's ledger
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔧 REMOVE INCORRECT RETURN FROM CUSTOMER 2 LEDGER\n";
echo "================================================\n\n";

$customerId = 2;

echo "Problem: Sale return SR-0001 belongs to 'CTC SAINTHAMARUTHU' but is in Customer 2's ledger\n";
echo "Solution: Remove this incorrect return entry from Customer 2's ledger\n\n";

// Find the incorrect return entry
$incorrectReturn = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('reference_no', 'SR-0001')
    ->where('transaction_type', 'sale_return_with_bill')
    ->first();

if ($incorrectReturn) {
    echo "🔍 FOUND INCORRECT RETURN ENTRY:\n";
    echo "Ledger ID: {$incorrectReturn->id}\n";
    echo "Reference: {$incorrectReturn->reference_no}\n";
    echo "Type: {$incorrectReturn->transaction_type}\n";
    echo "Amount: D:{$incorrectReturn->debit} C:{$incorrectReturn->credit}\n";
    echo "Status: {$incorrectReturn->status}\n";
    echo "Date: {$incorrectReturn->created_at}\n";
    echo "Notes: {$incorrectReturn->notes}\n\n";
    
    // Check if it's currently active
    if ($incorrectReturn->status === 'active') {
        echo "⚠️  This entry is currently ACTIVE and affecting Customer 2's balance\n";
        
        // Check current balance before fix
        $currentBalance = DB::table('ledgers')
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->selectRaw('SUM(debit - credit) as balance')
            ->first();
            
        echo "Current Customer 2 balance: {$currentBalance->balance}\n";
        echo "After removing this return, balance will be: " . ($currentBalance->balance + $incorrectReturn->credit - $incorrectReturn->debit) . "\n\n";
    } else {
        echo "ℹ️  This entry is already reversed/inactive\n\n";
    }
    
    // Check if this return actually belongs to another customer
    echo "🔍 CHECKING WHO THIS RETURN ACTUALLY BELONGS TO:\n";
    
    // Look for the original sale CSX-007
    $originalSale = DB::table('sales')->where('invoice_no', 'CSX-007')->first();
    if ($originalSale) {
        $actualCustomer = DB::table('customers')->where('id', $originalSale->customer_id)->first();
        echo "Original sale CSX-007 belongs to:\n";
        echo "Customer ID: {$originalSale->customer_id}\n";
        echo "Customer Name: {$actualCustomer->first_name} {$actualCustomer->last_name}\n\n";
        
        if ($originalSale->customer_id != $customerId) {
            echo "✅ CONFIRMED: This return belongs to Customer {$originalSale->customer_id}, not Customer {$customerId}\n";
            echo "This return should be removed from Customer 2's ledger\n\n";
        } else {
            echo "⚠️  WARNING: The original sale actually belongs to Customer 2\n";
            echo "Double-check this before removing\n\n";
        }
    } else {
        echo "❌ Original sale CSX-007 not found in sales table\n\n";
    }
    
    echo "Do you want to remove this incorrect return entry from Customer 2's ledger? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation === 'yes') {
        echo "\n🔧 REMOVING INCORRECT RETURN ENTRY...\n";
        
        DB::beginTransaction();
        try {
            if ($incorrectReturn->status === 'active') {
                // Mark as reversed
                DB::table('ledgers')->where('id', $incorrectReturn->id)->update([
                    'status' => 'reversed',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [REMOVED: Incorrect customer - belongs to different customer - " . date('Y-m-d H:i:s') . "]')")
                ]);
                echo "✅ Marked return entry as reversed\n";
            } else {
                echo "ℹ️  Entry was already inactive\n";
            }
            
            DB::commit();
            
            // Check new balance
            $newBalance = DB::table('ledgers')
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->selectRaw('SUM(debit - credit) as balance')
                ->first();
                
            $activeEntries = DB::table('ledgers')
                ->where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->count();
                
            echo "\n📊 CUSTOMER 2 STATUS AFTER REMOVAL:\n";
            echo "Active ledger entries: {$activeEntries}\n";
            echo "New balance: {$newBalance->balance}\n";
            
            if ($newBalance->balance == 720) {
                echo "✅ Perfect! Customer 2 now has only opening balance of 720\n";
            } else {
                echo "⚠️  Balance is not 720 - there may be other issues\n";
            }
            
            echo "\n🎉 INCORRECT RETURN ENTRY REMOVED!\n";
            echo "Customer 2's ledger is now clean\n";
            
        } catch (Exception $e) {
            DB::rollback();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            echo "Changes rolled back\n";
        }
    } else {
        echo "❌ Removal cancelled\n";
    }
} else {
    echo "✅ No incorrect return entry found for SR-0001 in Customer 2's ledger\n";
    echo "The ledger may already be clean\n";
}

// Show current ledger status
echo "\n📋 CURRENT CUSTOMER 2 LEDGER STATUS:\n";
$currentEntries = DB::table('ledgers')
    ->where('contact_id', $customerId)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->get();

echo "Active entries: " . $currentEntries->count() . "\n";
foreach ($currentEntries as $i => $entry) {
    echo "   " . ($i + 1) . ". ID:{$entry->id} | {$entry->transaction_type} | {$entry->reference_no} | D:{$entry->debit} C:{$entry->credit}\n";
}

$totalBalance = $currentEntries->sum(function($entry) {
    return $entry->debit - $entry->credit;
});
echo "Total balance: {$totalBalance}\n";

echo "\n✅ Analysis completed at " . date('Y-m-d H:i:s') . "\n";