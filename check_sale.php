<?php

use App\Models\Sale;

$sale = Sale::with('payments')->where('invoice_no', 'ARC-751')->first();

if ($sale) {
    echo "Invoice: " . $sale->invoice_no . PHP_EOL;
    echo "Subtotal: " . $sale->subtotal . PHP_EOL;
    echo "Discount: " . $sale->discount_amount . " (" . $sale->discount_type . ")" . PHP_EOL;
    echo "Final Total: " . $sale->final_total . PHP_EOL;
    echo "Amount Given: " . ($sale->amount_given ?? 'N/A') . PHP_EOL;
    echo "Balance: " . ($sale->balance_amount ?? 'N/A') . PHP_EOL;
    echo PHP_EOL;
    echo "Payments: " . $sale->payments->count() . PHP_EOL;
    foreach ($sale->payments as $payment) {
        echo "  Payment ID: {$payment->id} | Amount: {$payment->amount} | Method: {$payment->payment_method}" . PHP_EOL;
    }
} else {
    echo "Sale ARC-751 not found" . PHP_EOL;
}
