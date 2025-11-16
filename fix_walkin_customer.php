<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING WALK-IN CUSTOMER (ID 1) - CASH SALES ONLY ===\n\n";

DB::beginTransaction();

try {
    $customer = DB::table('customers')->where('id', 1)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "Type: DEFAULT CASH CUSTOMER - No ledger needed\n\n";
        
        echo "BEFORE CLEANUP:\n";
        echo "Customer Balance: {$customer->current_balance}\n";
        
        // Check current data
        $sales = DB::table('sales')->where('customer_id', 1)->get();
        $payments = DB::table('payments')->where('customer_id', 1)->get();
        $ledgers = DB::table('ledgers')->where('user_id', 1)->where('contact_type', 'customer')->get();
        
        echo "Sales: {$sales->count()}\n";
        echo "Payments: {$payments->count()}\n";
        echo "Ledger Entries: {$ledgers->count()}\n";
        
        $totalSales = $sales->sum('final_total');
        $totalPayments = $payments->sum('amount');
        echo "Total Sales Amount: {$totalSales}\n";
        echo "Total Payments: {$totalPayments}\n\n";
        
        echo "PERFORMING CLEANUP FOR CASH CUSTOMER...\n\n";
        
        // 1. Delete ALL ledger entries (walk-in customer doesn't need ledger)
        $deletedLedger = DB::table('ledgers')
                          ->where('user_id', 1)
                          ->where('contact_type', 'customer')
                          ->delete();
        echo "✓ Deleted {$deletedLedger} ledger entries (walk-in doesn't need ledger)\n";
        
        // 2. Delete ALL payments (walk-in is cash only, payments are immediate)
        $deletedPayments = DB::table('payments')->where('customer_id', 1)->delete();
        echo "✓ Deleted {$deletedPayments} payment records (cash sales only)\n";
        
        // 3. Update ALL sales to show FULL PAYMENT (cash sales)
        $updatedSales = 0;
        foreach ($sales as $sale) {
            DB::table('sales')
              ->where('id', $sale->id)
              ->update([
                  'total_paid' => $sale->final_total,    // Full amount paid
                  'total_due' => 0,                      // Nothing due
                  'payment_status' => 'Paid'             // Fully paid
              ]);
            $updatedSales++;
        }
        echo "✓ Updated {$updatedSales} sales to show full cash payment\n";
        
        // 4. Set customer balance to ZERO (no outstanding amounts)
        DB::table('customers')
          ->where('id', 1)
          ->update([
              'current_balance' => 0,
              'opening_balance' => 0  // Cash customer has no opening balance
          ]);
        echo "✓ Set customer balance to 0 (cash customer)\n\n";
        
        // Verify results
        echo "AFTER CLEANUP:\n";
        $customerAfter = DB::table('customers')->where('id', 1)->first();
        echo "Customer Balance: {$customerAfter->current_balance}\n";
        echo "Opening Balance: {$customerAfter->opening_balance}\n";
        
        $salesAfter = DB::table('sales')->where('customer_id', 1)->get();
        $paymentsAfter = DB::table('payments')->where('customer_id', 1)->count();
        $ledgerAfter = DB::table('ledgers')->where('user_id', 1)->where('contact_type', 'customer')->count();
        
        echo "Sales Records: {$salesAfter->count()}\n";
        echo "Payment Records: {$paymentsAfter}\n";
        echo "Ledger Entries: {$ledgerAfter}\n\n";
        
        // Check some sample sales
        echo "SAMPLE SALES VERIFICATION:\n";
        $sampleSales = $salesAfter->take(5);
        foreach ($sampleSales as $sale) {
            $status = ($sale->total_paid == $sale->final_total && $sale->total_due == 0) ? '✅' : '❌';
            echo "  Sale {$sale->id}: Final={$sale->final_total}, Paid={$sale->total_paid}, Due={$sale->total_due}, Status={$sale->payment_status} {$status}\n";
        }
        
        // Final verification
        $totalPaidSales = $salesAfter->sum('total_paid');
        $totalDueSales = $salesAfter->sum('total_due');
        $unpaidSales = $salesAfter->where('payment_status', '!=', 'Paid')->count();
        
        echo "\n✅ VERIFICATION:\n";
        echo "Total Sales Amount: {$totalSales}\n";
        echo "Total Paid Amount: {$totalPaidSales}\n";
        echo "Total Due Amount: {$totalDueSales}\n";
        echo "Unpaid Sales Count: {$unpaidSales}\n";
        echo "Customer Balance: {$customerAfter->current_balance}\n";
        
        $allPaid = ($totalPaidSales == $totalSales && $totalDueSales == 0 && $unpaidSales == 0 && $customerAfter->current_balance == 0);
        echo "All Cash Sales Properly Set: " . ($allPaid ? '✅' : '❌') . "\n";
        
        if ($allPaid) {
            DB::commit();
            echo "\n🎉 WALK-IN CUSTOMER successfully cleaned as CASH-ONLY customer!\n";
            echo "📝 Summary: {$salesAfter->count()} sales, all showing as fully paid cash transactions\n";
            echo "💰 No ledger entries (not needed for cash customer)\n";
            echo "🚫 No payment records (cash is immediate)\n";
            echo "✅ Zero outstanding balance\n";
        } else {
            DB::rollback();
            echo "\n❌ Verification failed - rolling back changes\n";
        }
        
    } else {
        echo "Customer ID 1 not found!\n";
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}