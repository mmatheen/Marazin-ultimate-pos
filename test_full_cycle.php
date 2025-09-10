<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

echo "Testing bounced cheque fix - full cycle...\n";

// Get payment and sale
$payment = Payment::find(9);
$sale = Sale::find(9);

echo "Initial state:\n";
echo "Payment Status: " . $payment->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";

// First clear the cheque
echo "\n--- Clearing cheque ---\n";
$payment->updateChequeStatus('cleared', 'Clearing for test', 0, 1);
$sale = Sale::find(9);

echo "After clearing:\n";
echo "Payment Status: " . $payment->fresh()->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";

// Now bounce the cheque
echo "\n--- Bouncing cheque ---\n";
$payment->updateChequeStatus('bounced', 'Testing bounced status', 0, 1);
$sale = Sale::find(9);

echo "After bouncing:\n";
echo "Payment Status: " . $payment->fresh()->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";
