<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

$payment = Payment::find(9);
$sale = Sale::find(9);

echo "Payment 9 details:\n";
echo "- Amount: " . $payment->amount . "\n";
echo "- Method: " . $payment->payment_method . "\n";
echo "- Status: " . $payment->cheque_status . "\n";

echo "\nSale 9 details:\n";
echo "- Final Total: " . $sale->final_total . "\n";
echo "- Total Paid: " . $sale->total_paid . "\n";
echo "- Total Due: " . $sale->total_due . "\n";

echo "\nAll payments for Sale 9:\n";
foreach ($sale->payments as $p) {
    echo "- Payment {$p->id}: {$p->amount} ({$p->payment_method}";
    if ($p->payment_method === 'cheque') {
        echo ", {$p->cheque_status}";
    }
    echo ")\n";
}

// Calculate what total_paid should be
$totalReceived = $sale->payments()->sum('amount');
$bouncedCheques = $sale->payments()
    ->where('payment_method', 'cheque')
    ->where('cheque_status', 'bounced')
    ->sum('amount');
$correctTotalPaid = $totalReceived - $bouncedCheques;

echo "\nCalculations:\n";
echo "- Total Received: " . $totalReceived . "\n";
echo "- Bounced Cheques: " . $bouncedCheques . "\n";
echo "- Correct Total Paid: " . $correctTotalPaid . "\n";
echo "- Expected Total Due: " . ($sale->final_total - $correctTotalPaid) . "\n";
