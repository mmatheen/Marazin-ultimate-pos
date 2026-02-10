<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Reference Number Format for Customer 1058 ===\n\n";

// Check BLK-S0075 ledgers
$ledgers = DB::table('ledgers')
    ->where('contact_id', 1058)
    ->where('reference_no', 'like', 'BLK-S0075%')
    ->select('id', 'reference_no', 'debit', 'credit', 'status')
    ->orderBy('id')
    ->get();

echo "Found " . $ledgers->count() . " ledger entries for BLK-S0075:\n\n";

foreach ($ledgers as $ledger) {
    echo sprintf(
        "ID: %d | Ref: %-25s | Debit: %10s | Credit: %10s | Status: %s\n",
        $ledger->id,
        $ledger->reference_no,
        number_format($ledger->debit, 2),
        number_format($ledger->credit, 2),
        $ledger->status
    );
}

echo "\n=== Reference Format Analysis ===\n";

// Group by reference pattern
$withPayId = $ledgers->filter(function($l) {
    return preg_match('/^BLK-S\d+-PAY\d+$/', $l->reference_no);
});

$withoutPayId = $ledgers->filter(function($l) {
    return !preg_match('/^BLK-S\d+-PAY\d+$/', $l->reference_no);
});

echo "With Payment ID format (BLK-S####-PAY###): " . $withPayId->count() . "\n";
echo "Without Payment ID format (BLK-S####): " . $withoutPayId->count() . "\n";

if ($withPayId->count() > 0) {
    echo "\n✅ New format is being used!\n";
    echo "Example: " . $withPayId->first()->reference_no . "\n";
} else {
    echo "\n❌ Old format still in use!\n";
    echo "Example: " . $ledgers->first()->reference_no . "\n";
}

echo "\n=== Checking Payments Table ===\n";

$payments = DB::table('payments')
    ->where('reference_no', 'like', 'BLK-S0075%')
    ->select('id', 'reference_no', 'amount')
    ->orderBy('id')
    ->get();

echo "Found " . $payments->count() . " payment entries:\n\n";

foreach ($payments->take(5) as $payment) {
    echo sprintf(
        "Payment ID: %d | Ref: %-25s | Amount: %10s\n",
        $payment->id,
        $payment->reference_no,
        number_format($payment->amount, 2)
    );
}

echo "\n";
