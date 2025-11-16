<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING ALM RIYATH CUSTOMER ===\n\n";

// Find ALM RIYATH customer
$customer = DB::table('customers')
             ->where('first_name', 'like', '%ALM%')
             ->orWhere('last_name', 'like', '%RIYATH%')
             ->orWhere('first_name', 'like', '%RIYATH%')
             ->first();

if ($customer) {
    echo "Customer Found: {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
    echo "Current Balance: {$customer->current_balance}\n";
    echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n\n";
    
    // Get ledger balance
    $ledgerBalance = DB::table('ledgers')
                      ->where('user_id', $customer->id)
                      ->where('contact_type', 'customer')
                      ->orderBy('transaction_date', 'desc')
                      ->orderBy('id', 'desc')
                      ->value('balance');
    
    echo "Ledger Balance: " . ($ledgerBalance ?? 0) . "\n";
    
    // Calculate what balance should be
    $totalSales = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->whereIn('status', ['final', 'suspend'])
                   ->sum('final_total');

    $totalPayments = DB::table('payments')
                      ->where('customer_id', $customer->id)
                      ->where('payment_type', 'sale')
                      ->sum('amount');

    $totalReturns = DB::table('sales_returns')
                     ->where('customer_id', $customer->id)
                     ->sum('return_total');

    $calculatedBalance = ($customer->opening_balance ?? 0) + $totalSales - $totalPayments - $totalReturns;
    
    echo "Calculated Balance: {$calculatedBalance}\n\n";
    
    echo "=== TRANSACTION SUMMARY ===\n";
    echo "Opening Balance: " . ($customer->opening_balance ?? 0) . "\n";
    echo "Total Sales: {$totalSales}\n";
    echo "Total Payments: {$totalPayments}\n";
    echo "Total Returns: {$totalReturns}\n\n";
    
    // Check for differences
    $customerVsLedger = abs($customer->current_balance - ($ledgerBalance ?? 0));
    $ledgerVsCalculated = abs(($ledgerBalance ?? 0) - $calculatedBalance);
    
    echo "=== DIFFERENCES ===\n";
    echo "Customer DB vs Ledger: {$customerVsLedger}\n";
    echo "Ledger vs Calculated: {$ledgerVsCalculated}\n\n";
    
    // Get detailed transaction counts
    $salesCount = DB::table('sales')->where('customer_id', $customer->id)->count();
    $paymentsCount = DB::table('payments')->where('customer_id', $customer->id)->count();
    $returnsCount = DB::table('sales_returns')->where('customer_id', $customer->id)->count();
    $ledgerCount = DB::table('ledgers')->where('user_id', $customer->id)->where('contact_type', 'customer')->count();
    
    echo "=== RECORD COUNTS ===\n";
    echo "Sales Records: {$salesCount}\n";
    echo "Payment Records: {$paymentsCount}\n";
    echo "Return Records: {$returnsCount}\n";
    echo "Ledger Records: {$ledgerCount}\n\n";
    
    // Check sales table consistency
    $sales = DB::table('sales')
             ->where('customer_id', $customer->id)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
             ->get();
    
    echo "=== SALES TABLE DETAILS ===\n";
    foreach ($sales as $sale) {
        $expectedDue = $sale->final_total - $sale->total_paid;
        $dueCorrect = abs($sale->total_due - $expectedDue) < 0.01 ? '✅' : '❌';
        echo "Sale ID: {$sale->id}, Final: {$sale->final_total}, Paid: {$sale->total_paid}, Due: {$sale->total_due} (Expected: {$expectedDue}) {$dueCorrect}, Status: {$sale->payment_status}\n";
    }
    
} else {
    echo "ALM RIYATH customer not found!\n";
}