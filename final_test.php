<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

// Force fresh model instances
$payment = Payment::find(9);
$sale = Sale::find(9);

echo "Fresh Laravel models after cache clear:\n";
echo "Payment Status: " . $payment->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";

// Test the cheque status update one more time
echo "\n--- Testing updateChequeStatus method ---\n";
$payment->updateChequeStatus('bounced', 'Final test of bounced cheque', 0, 1);

// Get fresh model instance 
$sale = Sale::find(9);

echo "After updateChequeStatus:\n";
echo "Payment Status: " . $payment->fresh()->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";
