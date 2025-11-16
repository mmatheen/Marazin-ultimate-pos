<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING SALES TABLE TOTAL_DUE CALCULATION ===\n\n";

// Check DUBAIWORLD sales
$customer = DB::table('customers')->where('first_name', 'DUBAIWORLD')->first();

if ($customer) {
    echo "Customer: DUBAIWORLD (ID: {$customer->id})\n\n";
    
    $sales = DB::table('sales')
             ->where('customer_id', $customer->id)
             ->select('id', 'invoice_no', 'final_total', 'total_paid', 'total_due', 'payment_status')
             ->get();
    
    echo "Sales Records:\n";
    echo "=============\n";
    foreach ($sales as $sale) {
        $calculatedDue = $sale->final_total - $sale->total_paid;
        $isCorrect = abs($sale->total_due - $calculatedDue) < 0.01 ? '✅' : '❌';
        
        echo "Sale ID: {$sale->id}\n";
        echo "Invoice: {$sale->invoice_no}\n";
        echo "Final Total: {$sale->final_total}\n";
        echo "Total Paid: {$sale->total_paid}\n";
        echo "Total Due (DB): {$sale->total_due}\n";
        echo "Total Due (Calculated): {$calculatedDue}\n";
        echo "Status: {$sale->payment_status}\n";
        echo "Correct: {$isCorrect}\n";
        echo "---\n";
    }
    
    // Check if total_due is a generated column
    $columnInfo = DB::select("SHOW COLUMNS FROM sales WHERE Field = 'total_due'");
    if (!empty($columnInfo)) {
        $column = $columnInfo[0];
        echo "\nTotal Due Column Info:\n";
        echo "Type: {$column->Type}\n";
        echo "Extra: {$column->Extra}\n";
        
        if (strpos($column->Extra, 'GENERATED') !== false) {
            echo "✅ total_due is a generated column\n";
        } else {
            echo "❌ total_due is NOT a generated column\n";
        }
    }
    
} else {
    echo "DUBAIWORLD customer not found!\n";
}