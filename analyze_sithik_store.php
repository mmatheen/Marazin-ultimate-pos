<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANALYZING SITHIK STORE (ID 2) ===\n\n";

try {
    $customer = DB::table('customers')->where('id', 2)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "Current Balance: {$customer->current_balance}\n";
        echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n\n";
        
        // Get all data
        $sales = DB::table('sales')->where('customer_id', 2)->orderBy('created_at')->get();
        $payments = DB::table('payments')->where('customer_id', 2)->orderBy('created_at')->get();
        $ledgers = DB::table('ledgers')->where('user_id', 2)->where('contact_type', 'customer')->orderBy('created_at')->get();
        
        echo "=== SALES DATA ===\n";
        foreach ($sales as $sale) {
            echo "Sale ID {$sale->id}: {$sale->invoice_no} - Rs.{$sale->final_total} (Paid: {$sale->total_paid}, Due: {$sale->total_due}, Status: {$sale->payment_status})\n";
        }
        
        echo "\n=== PAYMENT DATA ===\n";
        if ($payments->count() > 0) {
            foreach ($payments as $payment) {
                echo "Payment ID {$payment->id}: Rs.{$payment->amount} (Ref: {$payment->reference_no}, Type: {$payment->payment_type})\n";
            }
        } else {
            echo "No payment records\n";
        }
        
        echo "\n=== LEDGER ENTRIES ===\n";
        foreach ($ledgers as $ledger) {
            echo "Ledger ID {$ledger->id}: {$ledger->reference_no} - Debit: {$ledger->debit}, Credit: {$ledger->credit}, Balance: {$ledger->balance} ({$ledger->transaction_type})\n";
        }
        
        // Check for duplicates
        echo "\n=== DUPLICATE ANALYSIS ===\n";
        $ledgerGroups = $ledgers->groupBy('reference_no');
        $duplicates = [];
        
        foreach ($ledgerGroups as $refNo => $entries) {
            if ($entries->count() > 1) {
                echo "DUPLICATE: {$refNo} has {$entries->count()} entries:\n";
                foreach ($entries as $entry) {
                    echo "  - ID {$entry->id}: Debit {$entry->debit}, Credit {$entry->credit}, Balance {$entry->balance}\n";
                }
                $duplicates[] = $refNo;
            }
        }
        
        // Calculate correct totals
        echo "\n=== CALCULATIONS ===\n";
        $totalSales = $sales->sum('final_total');
        $totalPayments = $payments->sum('amount');
        $expectedBalance = ($customer->opening_balance ?? 0) + $totalSales - $totalPayments;
        
        echo "Total Sales: {$totalSales}\n";
        echo "Total Payments: {$totalPayments}\n";
        echo "Expected Balance: {$expectedBalance}\n";
        echo "Current DB Balance: {$customer->current_balance}\n";
        echo "Difference: " . ($customer->current_balance - $expectedBalance) . "\n";
        
        // Check ledger balance (last entry)
        $lastLedger = $ledgers->last();
        $ledgerBalance = $lastLedger ? $lastLedger->balance : 0;
        echo "Ledger Final Balance: {$ledgerBalance}\n";
        
        echo "\n=== RECOMMENDED FIX ===\n";
        echo "1. Remove duplicate ledger entries\n";
        echo "2. Rebuild ledger from scratch\n";
        echo "3. Update customer balance to match ledger\n";
        
    } else {
        echo "Customer ID 2 not found!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}