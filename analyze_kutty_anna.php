<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DETAILED ANALYSIS: KUTTY ANNA (ID 3) ===\n\n";

try {
    $customer = DB::table('customers')->where('id', 3)->first();
    
    if ($customer) {
        echo "Customer: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "Current Balance: {$customer->current_balance}\n";
        echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n\n";
        
        // Get all transaction data
        $sales = DB::table('sales')->where('customer_id', 3)->orderBy('created_at')->get();
        $payments = DB::table('payments')->where('customer_id', 3)->orderBy('created_at')->get();
        $ledgers = DB::table('ledgers')->where('user_id', 3)->where('contact_type', 'customer')->orderBy('created_at')->get();
        $returns = DB::table('sales_returns')->where('customer_id', 3)->get();
        
        echo "=== SALES DATA ===\n";
        foreach ($sales as $sale) {
            echo "Sale ID {$sale->id}: {$sale->invoice_no} - Rs.{$sale->final_total}\n";
            echo "  Created: {$sale->created_at}\n";
            echo "  Paid: {$sale->total_paid}, Due: {$sale->total_due}, Status: {$sale->payment_status}\n\n";
        }
        
        echo "=== PAYMENT DATA ===\n";
        foreach ($payments as $payment) {
            echo "Payment ID {$payment->id}: Rs.{$payment->amount}\n";
            echo "  Reference: {$payment->reference_no}\n";
            echo "  Type: {$payment->payment_type}\n";
            echo "  Created: {$payment->created_at}\n\n";
        }
        
        echo "=== RETURNS DATA ===\n";
        if ($returns->count() > 0) {
            foreach ($returns as $return) {
                echo "Return ID {$return->id}: Rs.{$return->final_total}\n";
                echo "  Created: {$return->created_at}\n\n";
            }
        } else {
            echo "No returns found\n\n";
        }
        
        echo "=== CURRENT LEDGER ENTRIES ===\n";
        foreach ($ledgers as $ledger) {
            echo "Ledger ID {$ledger->id}: {$ledger->reference_no}\n";
            echo "  Debit: {$ledger->debit}, Credit: {$ledger->credit}, Balance: {$ledger->balance}\n";
            echo "  Type: {$ledger->transaction_type}, Date: {$ledger->transaction_date}\n\n";
        }
        
        // Calculate step by step
        echo "=== STEP-BY-STEP CALCULATION ===\n";
        
        $totalSales = $sales->sum('final_total');
        $totalPayments = $payments->sum('amount');
        $totalReturns = $returns->sum('final_total');
        $openingBalance = $customer->opening_balance ?? 0;
        
        echo "Opening Balance: {$openingBalance}\n";
        echo "Total Sales: {$totalSales}\n";
        echo "Total Payments: {$totalPayments}\n";
        echo "Total Returns: {$totalReturns}\n\n";
        
        echo "CALCULATION:\n";
        echo "Opening Balance: {$openingBalance}\n";
        echo "+ Sales: {$totalSales}\n";
        echo "- Payments: {$totalPayments}\n";
        echo "- Returns: {$totalReturns}\n";
        echo "= Expected Balance: " . ($openingBalance + $totalSales - $totalPayments - $totalReturns) . "\n\n";
        
        echo "CURRENT DB BALANCE: {$customer->current_balance}\n";
        echo "LEDGER FINAL BALANCE: " . ($ledgers->count() > 0 ? $ledgers->last()->balance : 0) . "\n\n";
        
        // Check if 59175 is mentioned anywhere
        echo "=== LOOKING FOR 59175 VALUE ===\n";
        
        // Check if there were previous ledger entries or other data
        $allLedgers = DB::table('ledgers')
                       ->where('user_id', 3)
                       ->where('contact_type', 'customer')
                       ->orderBy('id')
                       ->get();
        
        echo "Found {$allLedgers->count()} total ledger entries for this customer\n";
        
        // Check for any balance that equals 59175
        $found59175 = false;
        foreach ($allLedgers as $ledger) {
            if ($ledger->balance == 59175) {
                echo "FOUND 59175 in Ledger ID {$ledger->id}: {$ledger->reference_no}\n";
                $found59175 = true;
            }
        }
        
        if (!$found59175) {
            echo "No ledger entry with balance 59175 found\n";
        }
        
        // Manual recalculation with proper order
        echo "\n=== MANUAL RECALCULATION ===\n";
        $balance = $openingBalance;
        echo "Starting balance: {$balance}\n";
        
        // Process transactions in chronological order
        $allTransactions = collect();
        
        // Add sales
        foreach ($sales as $sale) {
            $allTransactions->push([
                'type' => 'sale',
                'date' => $sale->created_at,
                'amount' => $sale->final_total,
                'ref' => $sale->invoice_no,
                'id' => $sale->id
            ]);
        }
        
        // Add payments
        foreach ($payments as $payment) {
            $allTransactions->push([
                'type' => 'payment',
                'date' => $payment->created_at,
                'amount' => $payment->amount,
                'ref' => $payment->reference_no,
                'id' => $payment->id
            ]);
        }
        
        // Add returns
        foreach ($returns as $return) {
            $allTransactions->push([
                'type' => 'return',
                'date' => $return->created_at,
                'amount' => $return->final_total,
                'ref' => 'RET-' . $return->id,
                'id' => $return->id
            ]);
        }
        
        // Sort by date
        $sortedTransactions = $allTransactions->sortBy('date');
        
        foreach ($sortedTransactions as $transaction) {
            if ($transaction['type'] === 'sale') {
                $balance += $transaction['amount'];
                echo "Sale {$transaction['ref']}: +{$transaction['amount']} = {$balance}\n";
            } elseif ($transaction['type'] === 'payment') {
                $balance -= $transaction['amount'];
                echo "Payment {$transaction['ref']}: -{$transaction['amount']} = {$balance}\n";
            } elseif ($transaction['type'] === 'return') {
                $balance -= $transaction['amount'];
                echo "Return {$transaction['ref']}: -{$transaction['amount']} = {$balance}\n";
            }
        }
        
        echo "\nFINAL CALCULATED BALANCE: {$balance}\n";
        
        // Check what should be the correct balance
        echo "\n=== RECOMMENDATION ===\n";
        if ($balance == 59175) {
            echo "✅ The correct balance should be 59175\n";
            echo "The current balance of {$customer->current_balance} is wrong\n";
            echo "Need to update customer balance to 59175\n";
        } elseif ($customer->current_balance == 59175) {
            echo "✅ The current balance 59175 is correct\n";
            echo "The master cleanup miscalculated\n";
        } else {
            echo "Neither {$customer->current_balance} nor 59175 matches our calculation of {$balance}\n";
            echo "Need further investigation\n";
        }
        
    } else {
        echo "Customer ID 3 not found!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}