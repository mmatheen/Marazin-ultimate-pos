<?php

/**
 * Quick Fix Script: Update Sales for Customer 852
 * Run with: php tests/FixCustomer852Sales.php
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;
use App\Models\Payment;

$saleIds = [106, 292, 354, 438, 501, 536];

echo "🔧 FIXING SALES FOR CUSTOMER 852\n";
echo str_repeat("=", 50) . "\n\n";

foreach ($saleIds as $saleId) {
    $sale = Sale::withoutGlobalScopes()->find($saleId);
    
    if (!$sale) {
        echo "⚠️ Sale ID {$saleId} not found\n";
        continue;
    }
    
    // Calculate total paid from active payments only
    $totalPaid = Payment::where('reference_id', $sale->id)
        ->where('payment_type', 'sale')
        ->where('status', 'active')
        ->sum('amount');
    
    $oldPaid = $sale->total_paid;
    $oldStatus = $sale->payment_status;
    
    // Update total_paid
    $sale->total_paid = $totalPaid;
    $sale->save();
    
    // Refresh to get calculated total_due
    $sale->refresh();
    
    // Update payment_status
    if ($sale->total_due <= 0) {
        $sale->payment_status = 'Paid';
    } elseif ($sale->total_paid > 0) {
        $sale->payment_status = 'Partial';
    } else {
        $sale->payment_status = 'Due';
    }
    $sale->save();
    
    echo "✅ Sale {$saleId} ({$sale->invoice_no}):\n";
    echo "   Paid: " . number_format($oldPaid, 2) . " → " . number_format($sale->total_paid, 2) . "\n";
    echo "   Due: " . number_format($sale->total_due, 2) . "\n";
    echo "   Status: {$oldStatus} → {$sale->payment_status}\n\n";
}

echo str_repeat("=", 50) . "\n";
echo "✅ DONE!\n";
