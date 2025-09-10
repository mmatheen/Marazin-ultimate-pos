<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "Testing direct database update...\n";

$payment = Payment::find(9);
$sale = Sale::find(9);

echo "Before direct update:\n";
echo "- Total Paid: " . $sale->total_paid . "\n";
echo "- Total Due: " . $sale->total_due . "\n";

// Calculate correct values
$totalReceived = $sale->payments()->sum('amount');
$bouncedCheques = $sale->payments()
    ->where('payment_method', 'cheque')
    ->where('cheque_status', 'bounced')
    ->sum('amount');
$newTotalPaid = $totalReceived - $bouncedCheques;

echo "\nCalculated values:\n";
echo "- New Total Paid: " . $newTotalPaid . "\n";

// Direct database update
DB::table('sales')
    ->where('id', $sale->id)
    ->update([
        'total_paid' => $newTotalPaid,
        'payment_status' => 'Due',
        'updated_at' => now()
    ]);

echo "\nAfter direct update:\n";
$sale = Sale::find(9); // Refresh from database
echo "- Total Paid: " . $sale->total_paid . "\n";
echo "- Total Due: " . $sale->total_due . "\n";
echo "- Payment Status: " . $sale->payment_status . "\n";
