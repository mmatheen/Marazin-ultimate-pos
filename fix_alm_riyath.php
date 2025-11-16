<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING ALM RIYATH - REMOVING INCORRECT PAYMENT ===\n\n";

DB::beginTransaction();

try {
    $customer = DB::table('customers')->where('id', 7)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n\n";
        
        echo "BEFORE CLEANUP:\n";
        echo "Customer Balance: {$customer->current_balance}\n";
        
        // Check current payments
        $payments = DB::table('payments')->where('customer_id', 7)->get();
        echo "Payment Records: {$payments->count()}\n";
        foreach ($payments as $payment) {
            echo "  - Payment ID {$payment->id}: Rs.{$payment->amount} ({$payment->payment_type})\n";
        }
        
        // Check current sales
        $sales = DB::table('sales')->where('customer_id', 7)->get(['id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status']);
        echo "Sales Records: {$sales->count()}\n";
        foreach ($sales as $sale) {
            echo "  - Sale ID {$sale->id}: Final:{$sale->final_total}, Paid:{$sale->total_paid}, Due:{$sale->total_due}, Status:{$sale->payment_status}\n";
        }
        
        // Check ledger entries
        $ledgerCount = DB::table('ledgers')->where('user_id', 7)->where('contact_type', 'customer')->count();
        echo "Ledger Entries: {$ledgerCount}\n\n";
        
        echo "PERFORMING CLEANUP...\n\n";
        
        // 1. Delete all payments
        $deletedPayments = DB::table('payments')->where('customer_id', 7)->delete();
        echo "✓ Deleted {$deletedPayments} payment record(s)\n";
        
        // 2. Delete all ledger entries (will rebuild)
        $deletedLedger = DB::table('ledgers')->where('user_id', 7)->where('contact_type', 'customer')->delete();
        echo "✓ Deleted {$deletedLedger} ledger entries\n";
        
        // 3. Update sales table - no payments made
        $updatedSales = 0;
        foreach ($sales as $sale) {
            $correctTotalDue = $sale->final_total; // Since no payments made
            
            DB::table('sales')
              ->where('id', $sale->id)
              ->update([
                  'total_paid' => 0,
                  'total_due' => $correctTotalDue,
                  'payment_status' => 'Due'
              ]);
            $updatedSales++;
            
            echo "✓ Updated Sale ID {$sale->id}: total_paid=0, total_due={$correctTotalDue}, status=Due\n";
        }
        
        // 4. Rebuild ledger with only sales (no payments)
        $balance = $customer->opening_balance ?? 0;
        
        // Add opening balance
        if ($balance != 0) {
            DB::table('ledgers')->insert([
                'transaction_date' => $customer->created_at ?? now(),
                'reference_no' => 'OPENING-' . $customer->id,
                'transaction_type' => 'opening_balance',
                'debit' => $balance > 0 ? $balance : 0,
                'credit' => $balance < 0 ? abs($balance) : 0,
                'balance' => $balance,
                'contact_type' => 'customer',
                'user_id' => $customer->id,
                'notes' => 'Opening balance',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            echo "✓ Added opening balance entry: {$balance}\n";
        }
        
        // Add sales entries
        $salesForLedger = DB::table('sales')
                           ->where('customer_id', 7)
                           ->whereIn('status', ['final', 'suspend'])
                           ->orderBy('created_at')
                           ->get();
        
        foreach ($salesForLedger as $sale) {
            $balance += $sale->final_total;
            
            DB::table('ledgers')->insert([
                'transaction_date' => $sale->created_at,
                'reference_no' => $sale->invoice_no ?? 'SALE-' . $sale->id,
                'transaction_type' => 'sale',
                'debit' => $sale->final_total,
                'credit' => 0,
                'balance' => $balance,
                'contact_type' => 'customer',
                'user_id' => $customer->id,
                'notes' => 'Sale transaction',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            echo "✓ Added sale ledger entry: Sale {$sale->id}, Amount: {$sale->final_total}, New Balance: {$balance}\n";
        }
        
        // 5. Update customer balance
        DB::table('customers')
          ->where('id', 7)
          ->update(['current_balance' => $balance]);
        
        echo "✓ Updated customer balance to: {$balance}\n\n";
        
        // Verify results
        echo "AFTER CLEANUP:\n";
        $customerAfter = DB::table('customers')->where('id', 7)->first();
        echo "Customer Balance: {$customerAfter->current_balance}\n";
        
        $paymentsAfter = DB::table('payments')->where('customer_id', 7)->count();
        echo "Payment Records: {$paymentsAfter}\n";
        
        $salesAfter = DB::table('sales')->where('customer_id', 7)->get(['id', 'final_total', 'total_paid', 'total_due', 'payment_status']);
        echo "Sales Records:\n";
        foreach ($salesAfter as $sale) {
            echo "  - Sale ID {$sale->id}: Final:{$sale->final_total}, Paid:{$sale->total_paid}, Due:{$sale->total_due}, Status:{$sale->payment_status}\n";
        }
        
        $ledgerAfter = DB::table('ledgers')->where('user_id', 7)->where('contact_type', 'customer')->count();
        echo "Ledger Entries: {$ledgerAfter}\n";
        
        // Final calculation check
        $totalSales = DB::table('sales')->where('customer_id', 7)->whereIn('status', ['final', 'suspend'])->sum('final_total');
        $calculatedBalance = ($customer->opening_balance ?? 0) + $totalSales;
        
        echo "\n✅ VERIFICATION:\n";
        echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n";
        echo "Total Sales: {$totalSales}\n";
        echo "Total Payments: 0 (removed)\n";
        echo "Calculated Balance: {$calculatedBalance}\n";
        echo "Customer DB Balance: {$customerAfter->current_balance}\n";
        echo "Match: " . ($calculatedBalance == $customerAfter->current_balance ? '✅' : '❌') . "\n";
        
        DB::commit();
        echo "\n🎉 ALM RIYATH customer successfully cleaned!\n";
        
    } else {
        echo "Customer ID 7 not found!\n";
    }
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ Error: " . $e->getMessage() . "\n";
}