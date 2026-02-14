<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "Fixing MLX-598 Payment Issue\n";
echo "===========================================\n\n";

DB::beginTransaction();

try {
    $sale = Sale::withoutGlobalScopes()->where('invoice_no', 'MLX-598')->first();

    if (!$sale) {
        echo "❌ Sale MLX-598 not found!\n";
        exit;
    }

    echo "Current Sale Status:\n";
    echo "  Invoice: {$sale->invoice_no}\n";
    echo "  Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  Total Paid: Rs " . number_format($sale->total_paid, 2) . " (WRONG)\n";
    echo "  Total Due: Rs " . number_format($sale->total_due, 2) . " (WRONG)\n";
    echo "  Payment Status: {$sale->payment_status}\n";
    echo "\n";

    // Calculate correct total_paid from payments
    $totalPaid = Payment::where('reference_id', $sale->id)
        ->where('payment_type', 'sale')
        ->sum('amount');

    echo "Payments for this sale:\n";
    $payments = Payment::where('reference_id', $sale->id)
        ->where('payment_type', 'sale')
        ->get();

    foreach ($payments as $payment) {
        echo "  Payment ID {$payment->id}: Rs " . number_format($payment->amount, 2) .
             " ({$payment->payment_method})\n";
    }

    echo "\n";
    echo "Correct Calculations:\n";
    echo "  Total Paid: Rs " . number_format($totalPaid, 2) . "\n";
    $totalDue = max($sale->final_total - $totalPaid, 0);
    echo "  Total Due: Rs " . number_format($totalDue, 2) . "\n";

    // Determine payment status
    if ($totalDue <= 0) {
        $paymentStatus = 'Paid';
    } elseif ($totalPaid > 0) {
        $paymentStatus = 'Partial';
    } else {
        $paymentStatus = 'Due';
    }
    echo "  Payment Status: {$paymentStatus}\n";
    echo "\n";

    // Update the sale
    $sale->total_paid = $totalPaid;
    $sale->total_due = $totalDue;
    $sale->payment_status = $paymentStatus;
    $sale->save();

    echo "✅ Sale MLX-598 updated successfully!\n";
    echo "\nUpdated Sale Status:\n";
    echo "  Final Total: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  Total Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "  Total Due: Rs " . number_format($sale->total_due, 2) . "\n";
    echo "  Payment Status: {$sale->payment_status}\n";

    DB::commit();
    echo "\n✅ Transaction committed successfully!\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
}

echo "\n===========================================\n";
