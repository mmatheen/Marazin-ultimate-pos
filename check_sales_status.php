<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find DUBAIWORLD customer
$customer = DB::table('customers')->where('first_name', 'DUBAIWORLD')->first();

if ($customer) {
    echo "Customer: DUBAIWORLD (ID: {$customer->id})\n";
    echo "Current Balance: {$customer->current_balance}\n\n";
    
    // Get sales records
    $sales = DB::table('sales')
             ->where('customer_id', $customer->id)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'payment_status', 'created_at')
             ->get();
    
    echo "Sales Records:\n";
    echo "==============\n";
    foreach ($sales as $sale) {
        echo "Sale ID: {$sale->id}\n";
        echo "Invoice: {$sale->invoice_no}\n";
        echo "Final Total: Rs. {$sale->final_total}\n";
        echo "Total Paid: Rs. {$sale->total_paid}\n";
        echo "Payment Status: {$sale->payment_status}\n";
        echo "Date: {$sale->created_at}\n";
        echo "---\n";
    }
    
    // Calculate what total_due should be (this is a generated column)
    echo "\nSummary:\n";
    echo "========\n";
    $totalSalesAmount = $sales->sum('final_total');
    $totalPaidAmount = $sales->sum('total_paid');
    echo "Total Sales Amount: Rs. {$totalSalesAmount}\n";
    echo "Total Paid Amount: Rs. {$totalPaidAmount}\n";
    echo "Total Due Amount: Rs. " . ($totalSalesAmount - $totalPaidAmount) . "\n";
    
} else {
    echo "DUBAIWORLD customer not found!\n";
}