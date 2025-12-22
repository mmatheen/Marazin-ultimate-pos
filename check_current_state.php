<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "Current Sale Records:\n";
echo "===================\n\n";

$sales = Sale::whereIn('id', [494, 495, 496])->get();

foreach ($sales as $sale) {
    echo "ID: {$sale->id}\n";
    echo "  Transaction Type: {$sale->transaction_type}\n";
    echo "  Order Number: {$sale->order_number}\n";
    echo "  Invoice No: {$sale->invoice_no}\n";
    echo "  Order Status: {$sale->order_status}\n";
    echo "  Converted To: {$sale->converted_to_sale_id}\n";
    echo "  Created: {$sale->created_at}\n";
    echo "  Updated: {$sale->updated_at}\n";
    echo "\n";
}

// Check sale order list query
echo "\nSale Order List Query (transaction_type = 'sale_order'):\n";
echo "========================================================\n";
$saleOrders = Sale::where('transaction_type', 'sale_order')->get();
echo "Found: " . $saleOrders->count() . " sale orders\n\n";
foreach ($saleOrders as $order) {
    echo "  - ID: {$order->id}, Order: {$order->order_number}, Status: {$order->order_status}\n";
}

// Check invoice list query
echo "\nInvoice List Query (transaction_type = 'invoice'):\n";
echo "===================================================\n";
$invoices = Sale::where('transaction_type', 'invoice')->get();
echo "Found: " . $invoices->count() . " invoices\n\n";
foreach ($invoices as $invoice) {
    echo "  - ID: {$invoice->id}, Invoice: {$invoice->invoice_no}, Order: {$invoice->order_number}\n";
}
