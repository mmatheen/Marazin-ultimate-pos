<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DETAILED CHECK FOR ALM RIYATH (ID: 7) ===\n\n";

// Get customer details
$customer = DB::table('customers')->where('id', 7)->first();

if ($customer) {
    echo "👤 CUSTOMER DETAILS:\n";
    echo "ID: {$customer->id}\n";
    echo "Name: {$customer->first_name} {$customer->last_name}\n";
    echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n";
    echo "Current Balance (DB): {$customer->current_balance}\n";
    echo "Created: {$customer->created_at}\n\n";
    
    // Get all sales for this customer
    $sales = DB::table('sales')
             ->where('customer_id', 7)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status', 'status', 'created_at')
             ->orderBy('created_at')
             ->get();
    
    echo "📈 SALES RECORDS ({$sales->count()} records):\n";
    echo "=========================================\n";
    $totalSalesAmount = 0;
    $totalPaidInSales = 0;
    
    foreach ($sales as $sale) {
        echo "Sale ID: {$sale->id}\n";
        echo "Invoice: {$sale->invoice_no}\n";
        echo "Final Total: Rs. {$sale->final_total}\n";
        echo "Total Paid: Rs. {$sale->total_paid}\n";
        echo "Total Due: Rs. {$sale->total_due}\n";
        echo "Payment Status: {$sale->payment_status}\n";
        echo "Sale Status: {$sale->status}\n";
        echo "Date: {$sale->created_at}\n";
        
        // Check if total_due is correct
        $expectedDue = $sale->final_total - $sale->total_paid;
        $dueCorrect = abs($sale->total_due - $expectedDue) < 0.01 ? '✅' : '❌';
        echo "Due Calculation: {$dueCorrect} (Expected: {$expectedDue})\n";
        echo "---\n";
        
        if ($sale->status == 'final' || $sale->status == 'suspend') {
            $totalSalesAmount += $sale->final_total;
        }
        $totalPaidInSales += $sale->total_paid;
    }
    
    echo "SALES SUMMARY:\n";
    echo "Total Sales (final/suspend): Rs. {$totalSalesAmount}\n";
    echo "Total Paid (in sales table): Rs. {$totalPaidInSales}\n\n";
    
    // Get all payments for this customer
    $payments = DB::table('payments')
                 ->where('customer_id', 7)
                 ->select('id', 'amount', 'payment_date', 'payment_type', 'reference_no', 'created_at')
                 ->orderBy('created_at')
                 ->get();
    
    echo "💳 PAYMENT RECORDS ({$payments->count()} records):\n";
    echo "==========================================\n";
    $totalPayments = 0;
    
    foreach ($payments as $payment) {
        echo "Payment ID: {$payment->id}\n";
        echo "Amount: Rs. {$payment->amount}\n";
        echo "Type: {$payment->payment_type}\n";
        echo "Reference: {$payment->reference_no}\n";
        echo "Payment Date: {$payment->payment_date}\n";
        echo "Created: {$payment->created_at}\n";
        echo "---\n";
        
        if ($payment->payment_type == 'sale') {
            $totalPayments += $payment->amount;
        }
    }
    
    echo "PAYMENT SUMMARY:\n";
    echo "Total Sale Payments: Rs. {$totalPayments}\n\n";
    
    // Get all returns for this customer
    $returns = DB::table('sales_returns')
                ->where('customer_id', 7)
                ->select('id', 'return_total', 'return_date', 'invoice_number', 'stock_type', 'created_at')
                ->orderBy('created_at')
                ->get();
    
    echo "🔄 RETURN RECORDS ({$returns->count()} records):\n";
    echo "========================================\n";
    $totalReturns = 0;
    
    foreach ($returns as $return) {
        echo "Return ID: {$return->id}\n";
        echo "Amount: Rs. {$return->return_total}\n";
        echo "Type: {$return->stock_type}\n";
        echo "Invoice: {$return->invoice_number}\n";
        echo "Return Date: {$return->return_date}\n";
        echo "Created: {$return->created_at}\n";
        echo "---\n";
        
        $totalReturns += $return->return_total;
    }
    
    echo "RETURN SUMMARY:\n";
    echo "Total Returns: Rs. {$totalReturns}\n\n";
    
    // Get ledger entries
    $ledgerEntries = DB::table('ledgers')
                      ->where('user_id', 7)
                      ->where('contact_type', 'customer')
                      ->select('id', 'transaction_type', 'debit', 'credit', 'balance', 'reference_no', 'transaction_date')
                      ->orderBy('transaction_date')
                      ->orderBy('id')
                      ->get();
    
    echo "📖 LEDGER ENTRIES ({$ledgerEntries->count()} records):\n";
    echo "=======================================\n";
    $totalDebit = 0;
    $totalCredit = 0;
    
    foreach ($ledgerEntries as $entry) {
        echo "Entry ID: {$entry->id}\n";
        echo "Type: {$entry->transaction_type}\n";
        echo "Debit: {$entry->debit}\n";
        echo "Credit: {$entry->credit}\n";
        echo "Balance: {$entry->balance}\n";
        echo "Reference: {$entry->reference_no}\n";
        echo "Date: {$entry->transaction_date}\n";
        echo "---\n";
        
        $totalDebit += $entry->debit;
        $totalCredit += $entry->credit;
    }
    
    echo "LEDGER SUMMARY:\n";
    echo "Total Debit: {$totalDebit}\n";
    echo "Total Credit: {$totalCredit}\n";
    echo "Ledger Balance: " . ($totalDebit - $totalCredit) . "\n";
    echo "Last Entry Balance: " . ($ledgerEntries->count() > 0 ? $ledgerEntries->last()->balance : 0) . "\n\n";
    
    // Calculate correct balance
    $calculatedBalance = ($customer->opening_balance ?? 0) + $totalSalesAmount - $totalPayments - $totalReturns;
    
    echo "🧮 BALANCE CALCULATION:\n";
    echo "=======================\n";
    echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n";
    echo "+ Total Sales: {$totalSalesAmount}\n";
    echo "- Total Payments: {$totalPayments}\n";
    echo "- Total Returns: {$totalReturns}\n";
    echo "= Calculated Balance: {$calculatedBalance}\n\n";
    
    echo "⚖️ COMPARISON:\n";
    echo "==============\n";
    echo "Customer DB Balance: {$customer->current_balance}\n";
    echo "Calculated Balance: {$calculatedBalance}\n";
    echo "Ledger Balance: " . ($ledgerEntries->count() > 0 ? $ledgerEntries->last()->balance : 0) . "\n";
    echo "DB vs Calculated Diff: " . ($customer->current_balance - $calculatedBalance) . "\n";
    
    // Check for issues
    echo "\n🔍 ISSUES FOUND:\n";
    echo "================\n";
    
    if (abs($customer->current_balance - $calculatedBalance) > 0.01) {
        echo "❌ Customer balance doesn't match calculated balance\n";
    }
    
    if ($totalPaidInSales != $totalPayments) {
        echo "❌ Sales table total_paid ({$totalPaidInSales}) doesn't match actual payments ({$totalPayments})\n";
    }
    
    // Check total_due in sales
    $incorrectDueCount = 0;
    foreach ($sales as $sale) {
        $expectedDue = $sale->final_total - $sale->total_paid;
        if (abs($sale->total_due - $expectedDue) > 0.01) {
            $incorrectDueCount++;
        }
    }
    
    if ($incorrectDueCount > 0) {
        echo "❌ {$incorrectDueCount} sales have incorrect total_due calculation\n";
    }
    
    if ($incorrectDueCount == 0 && 
        abs($customer->current_balance - $calculatedBalance) < 0.01 && 
        $totalPaidInSales == $totalPayments) {
        echo "✅ All data is consistent!\n";
    }
    
} else {
    echo "Customer ID 7 not found!\n";
}