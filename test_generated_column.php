<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING SALES TABLE TOTAL_DUE GENERATION ===\n\n";

// Find DUBAIWORLD customer
$customer = DB::table('customers')->where('first_name', 'DUBAIWORLD')->first();

if ($customer) {
    echo "Testing with DUBAIWORLD customer (ID: {$customer->id})\n\n";
    
    // Get current sales
    $sales = DB::table('sales')
             ->where('customer_id', $customer->id)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
             ->get();
    
    echo "BEFORE any changes:\n";
    foreach ($sales as $sale) {
        echo "Sale {$sale->id}: final_total={$sale->final_total}, total_paid={$sale->total_paid}, total_due={$sale->total_due}, status={$sale->payment_status}\n";
    }
    
    // Test: Set total_paid to a test value
    echo "\nSetting total_paid to 5000 for testing...\n";
    DB::table('sales')
      ->where('customer_id', $customer->id)
      ->update(['total_paid' => 5000]);
    
    // Check if total_due updated automatically
    $salesAfter = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
                   ->get();
    
    echo "\nAFTER setting total_paid to 5000:\n";
    foreach ($salesAfter as $sale) {
        $expectedDue = $sale->final_total - $sale->total_paid;
        $correct = abs($sale->total_due - $expectedDue) < 0.01 ? '✅' : '❌';
        echo "Sale {$sale->id}: final_total={$sale->final_total}, total_paid={$sale->total_paid}, total_due={$sale->total_due} (expected: {$expectedDue}) {$correct}\n";
    }
    
    // Reset back to 0
    echo "\nResetting total_paid back to 0...\n";
    DB::table('sales')
      ->where('customer_id', $customer->id)
      ->update(['total_paid' => 0, 'payment_status' => 'Due']);
    
    $salesReset = DB::table('sales')
                   ->where('customer_id', $customer->id)
                   ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
                   ->get();
    
    echo "\nAFTER resetting to 0:\n";
    foreach ($salesReset as $sale) {
        $expectedDue = $sale->final_total - $sale->total_paid;
        $correct = abs($sale->total_due - $expectedDue) < 0.01 ? '✅' : '❌';
        echo "Sale {$sale->id}: final_total={$sale->final_total}, total_paid={$sale->total_paid}, total_due={$sale->total_due} (expected: {$expectedDue}) {$correct}, status={$sale->payment_status}\n";
    }
    
} else {
    echo "DUBAIWORLD customer not found!\n";
}