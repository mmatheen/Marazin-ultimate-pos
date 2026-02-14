<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Payment;

echo "===========================================\n";
echo "Checking MLX-598 Payment Issue\n";
echo "===========================================\n\n";

$sale = Sale::withoutGlobalScopes()->find(1619);
if (!$sale) {
    echo "Sale not found!\n";
    // Try to find by invoice
    $sale = Sale::withoutGlobalScopes()->where('invoice_no', 'MLX-598')->first();
    if (!$sale) {
        echo "Also not found by invoice number MLX-598!\n";
        exit;
    }
    echo "Found by invoice number: Sale ID {$sale->id}\n\n";
}

echo "Sale Details:\n";
echo "  Invoice: {$sale->invoice_no}\n";
echo "  Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
echo "  Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
echo "  Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
echo "  Payment Status: {$sale->payment_status}\n";
echo "\n";

echo "Payments for this sale:\n";
$payments = Payment::where('reference_id', $sale->id)
    ->where('payment_type', 'sale')
    ->get();

if ($payments->isEmpty()) {
    echo "  No payments found!\n";
} else {
    foreach ($payments as $payment) {
        echo "  Payment ID {$payment->id}: Rs " . number_format($payment->amount, 2) .
             " ({$payment->payment_method}) - Status: {$payment->status}, Payment Status: {$payment->payment_status}\n";
    }
}

$totalPaid = Payment::where('reference_id', $sale->id)
    ->where('payment_type', 'sale')
    ->sum('amount');

echo "\n";
echo "Total from payments query: Rs " . number_format($totalPaid, 2) . "\n";
echo "Expected total_paid: Rs " . number_format($totalPaid, 2) . "\n";
echo "Expected total_due: Rs " . number_format($sale->final_total - $totalPaid, 2) . "\n";
echo "\n";

if ($sale->total_paid != $totalPaid) {
    echo "❌ ISSUE CONFIRMED: Sale total_paid doesn't match payments sum!\n";
    echo "   Sale table shows: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "   Actual payments: Rs " . number_format($totalPaid, 2) . "\n";
    echo "   Difference: Rs " . number_format($totalPaid - $sale->total_paid, 2) . "\n";
} else {
    echo "✅ Sale total_paid matches payments sum.\n";
}

echo "\n===========================================\n";
