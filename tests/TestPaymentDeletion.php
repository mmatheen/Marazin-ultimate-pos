<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Payment;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

echo "\n=== TESTING PAYMENT DELETION FLOW ===\n";

// Create a test payment
DB::transaction(function () {
    echo "\n--- Step 1: Creating test payment ---\n";

    $payment = Payment::create([
        'customer_id' => 83,
        'payment_type' => 'sale',
        'payment_date' => now(),
        'amount' => 500.00,
        'payment_method' => 'cash',
        'reference_no' => 'TEST-PAYMENT-001',
        'status' => 'active',
        'payment_status' => 'completed'
    ]);

    echo "✅ Created payment #{$payment->id}: {$payment->reference_no}, Rs.{$payment->amount}\n";

    // Create corresponding ledger entry
    $ledger = Ledger::create([
        'contact_id' => 83,
        'contact_type' => 'customer',
        'transaction_date' => now(),
        'reference_no' => $payment->reference_no,
        'transaction_type' => 'payments',
        'debit' => 0,
        'credit' => $payment->amount,
        'status' => 'active',
        'notes' => 'Test payment for deletion'
    ]);

    echo "✅ Created ledger entry #{$ledger->id}\n";

    echo "\n--- Step 2: Testing deletePayment method ---\n";

    $unifiedLedgerService = app(UnifiedLedgerService::class);

    try {
        $result = $unifiedLedgerService->deletePayment($payment, 'Testing deletion flow');

        if ($result) {
            echo "✅ deletePayment returned result:\n";
            print_r($result);
        } else {
            echo "⚠️ deletePayment returned null\n";
        }

    } catch (\Exception $e) {
        echo "❌ Error calling deletePayment: " . $e->getMessage() . "\n";
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }

    echo "\n--- Step 3: Checking ledger status ---\n";

    $originalLedger = Ledger::find($ledger->id);
    echo "Original ledger #{$originalLedger->id}: status = {$originalLedger->status}\n";

    $reversalLedgers = Ledger::where('reference_no', 'LIKE', $payment->reference_no . '-DEL-%')->get();
    echo "Found " . $reversalLedgers->count() . " reversal entries\n";

    foreach ($reversalLedgers as $rev) {
        echo "  Reversal #{$rev->id}: {$rev->reference_no}, D:{$rev->debit} C:{$rev->credit}, Status: {$rev->status}\n";
    }

    echo "\n--- Step 4: Cleaning up ---\n";

    // Clean up test data
    Payment::withoutGlobalScopes()->where('id', $payment->id)->forceDelete();
    Ledger::where('contact_id', 83)
        ->where('reference_no', 'LIKE', 'TEST-PAYMENT-%')
        ->delete();

    echo "✅ Test data cleaned up\n";
});

echo "\n=== TEST COMPLETED ===\n";
