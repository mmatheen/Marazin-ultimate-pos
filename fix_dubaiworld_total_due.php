<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING DUBAIWORLD TOTAL_DUE ===\n\n";

// Find DUBAIWORLD customer
$customer = DB::table('customers')->where('first_name', 'DUBAIWORLD')->first();

if ($customer) {
    echo "Customer: DUBAIWORLD (ID: {$customer->id})\n\n";
    
    // Get sales before fix
    $sales = DB::table('sales')
             ->where('customer_id', $customer->id)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
             ->get();
    
    echo "BEFORE FIX:\n";
    foreach ($sales as $sale) {
        echo "Sale ID: {$sale->id}, Final: {$sale->final_total}, Paid: {$sale->total_paid}, Due: {$sale->total_due}, Status: {$sale->payment_status}\n";
    }
    
    echo "\nFIXING...\n";
    
    // Fix each sale record
    foreach ($sales as $sale) {
        $correctTotalDue = $sale->final_total - $sale->total_paid;
        
        DB::table('sales')
          ->where('id', $sale->id)
          ->update([
              'total_due' => $correctTotalDue
          ]);
        
        echo "Fixed Sale ID {$sale->id}: total_due set to {$correctTotalDue}\n";
    }
    
    // Get sales after fix
    $salesAfter = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
                   ->get();
    
    echo "\nAFTER FIX:\n";
    foreach ($salesAfter as $sale) {
        $expectedDue = $sale->final_total - $sale->total_paid;
        $correct = abs($sale->total_due - $expectedDue) < 0.01 ? '✅' : '❌';
        echo "Sale ID: {$sale->id}, Final: {$sale->final_total}, Paid: {$sale->total_paid}, Due: {$sale->total_due} (Expected: {$expectedDue}) {$correct}, Status: {$sale->payment_status}\n";
    }
    
    echo "\n✅ DUBAIWORLD total_due has been fixed!\n";
    
} else {
    echo "DUBAIWORLD customer not found!\n";
}