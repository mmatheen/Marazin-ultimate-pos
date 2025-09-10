<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Sale;

echo "Testing bounced cheque fix...\n";

// Get payment and sale before update
$payment = Payment::find(9);
$sale = Sale::find(9);

echo "Before update:\n";
echo "Payment Status: " . $payment->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";

// Update cheque status
$payment->updateChequeStatus('bounced', 'Testing bounced status update', 0, 1);

// Get updated sale
$sale = Sale::find(9);

echo "\nAfter update:\n";
echo "Payment Status: " . $payment->fresh()->cheque_status . "\n";
echo "Sale total_paid: " . $sale->total_paid . "\n";
echo "Sale total_due: " . $sale->total_due . "\n";
echo "Sale payment_status: " . $sale->payment_status . "\n";
