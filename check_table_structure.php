<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 CHECKING TABLE STRUCTURES\n";
echo "=============================\n\n";

echo "1. SALES TABLE STRUCTURE:\n";
try {
    $salesColumns = DB::select("DESCRIBE sales");
    foreach ($salesColumns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n2. PAYMENTS TABLE STRUCTURE:\n";
try {
    $paymentsColumns = DB::select("DESCRIBE payments");
    foreach ($paymentsColumns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n3. SAMPLE SALES DATA:\n";
try {
    $sampleSales = DB::select("SELECT * FROM sales WHERE customer_id != 1 ORDER BY created_at DESC LIMIT 3");
    foreach ($sampleSales as $sale) {
        echo "Sale ID: {$sale->id} | Invoice: {$sale->invoice_no} | Customer: {$sale->customer_id} | Total: {$sale->final_total}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n4. SAMPLE PAYMENTS DATA:\n";
try {
    $samplePayments = DB::select("SELECT * FROM payments ORDER BY created_at DESC LIMIT 3");
    foreach ($samplePayments as $payment) {
        echo "Payment ID: {$payment->id} | Sale ID: {$payment->sale_id} | Amount: {$payment->amount}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n✅ TABLE STRUCTURE CHECK COMPLETE\n";