<?php

/**
 * Fix Missing Ledger Entries for Bulk Payments
 *
 * Issue: When multiple payments are created in a bulk transaction with the same reference_no,
 * the duplicate detection logic in Ledger::createEntry() was incorrectly treating them as duplicates
 * and skipping ledger entry creation for subsequent payments.
 *
 * This script:
 * 1. Finds all payments that don't have corresponding ledger entries
 * 2. Creates missing ledger entries with unique reference numbers (appends payment ID)
 * 3. Focuses on bulk payments (reference starts with BLK-)
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Ledger;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking for missing ledger entries in bulk payments...\n\n";

// Find all payments that don't have corresponding ledger entries
$paymentsWithoutLedger = DB::table('payments')
    ->leftJoin('ledgers', function($join) {
        $join->on('payments.customer_id', '=', 'ledgers.contact_id')
             ->where('ledgers.contact_type', '=', 'customer')
             ->on(DB::raw("CONCAT(payments.reference_no, '-PAY', payments.id)"), '=', 'ledgers.reference_no')
             ->where('ledgers.status', '=', 'active');
    })
    ->leftJoin('ledgers as ledgers2', function($join) {
        $join->on('payments.customer_id', '=', 'ledgers2.contact_id')
             ->where('ledgers2.contact_type', '=', 'customer')
             ->on('payments.reference_no', '=', 'ledgers2.reference_no')
             ->where('ledgers2.status', '=', 'active');
    })
    ->leftJoin('ledgers as ledgers3', function($join) {
        $join->on('payments.supplier_id', '=', 'ledgers3.contact_id')
             ->where('ledgers3.contact_type', '=', 'supplier')
             ->on(DB::raw("CONCAT(payments.reference_no, '-PAY', payments.id)"), '=', 'ledgers3.reference_no')
             ->where('ledgers3.status', '=', 'active');
    })
    ->leftJoin('ledgers as ledgers4', function($join) {
        $join->on('payments.supplier_id', '=', 'ledgers4.contact_id')
             ->where('ledgers4.contact_type', '=', 'supplier')
             ->on('payments.reference_no', '=', 'ledgers4.reference_no')
             ->where('ledgers4.status', '=', 'active');
    })
    ->whereNull('ledgers.id')
    ->whereNull('ledgers2.id')
    ->whereNull('ledgers3.id')
    ->whereNull('ledgers4.id')
    ->where('payments.status', 'active')
    ->where('payments.reference_no', 'LIKE', 'BLK-%')
    ->select('payments.*')
    ->orderBy('payments.id')
    ->get();

echo "Found " . count($paymentsWithoutLedger) . " payments without ledger entries\n\n";

if (count($paymentsWithoutLedger) == 0) {
    echo "✅ No missing ledger entries found. All payments have corresponding ledger entries.\n";
    exit(0);
}

echo "Missing ledger entries:\n";
echo str_repeat('-', 100) . "\n";
printf("%-8s %-15s %-15s %-12s %-15s %-12s %s\n",
    "Pay ID", "Reference", "Type", "Amount", "Date", "Contact ID", "Contact Type");
echo str_repeat('-', 100) . "\n";

foreach ($paymentsWithoutLedger as $payment) {
    $contactType = $payment->customer_id ? 'customer' : 'supplier';
    $contactId = $payment->customer_id ?: $payment->supplier_id;

    printf("%-8s %-15s %-15s %-12s %-15s %-12s %s\n",
        $payment->id,
        substr($payment->reference_no, 0, 15),
        substr($payment->payment_type, 0, 15),
        number_format($payment->amount, 2),
        substr($payment->payment_date, 0, 15),
        $contactId,
        $contactType
    );
}

echo str_repeat('-', 100) . "\n\n";

echo "Do you want to create missing ledger entries? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

echo "\n🔧 Creating missing ledger entries...\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

DB::transaction(function() use ($paymentsWithoutLedger, &$successCount, &$errorCount, &$errors) {
    foreach ($paymentsWithoutLedger as $paymentData) {
        try {
            // Load the actual Payment model
            $payment = Payment::find($paymentData->id);

            if (!$payment) {
                $errorCount++;
                $errors[] = "Payment ID {$paymentData->id}: Not found";
                continue;
            }

            // Determine contact type and ID
            $contactType = $payment->customer_id ? 'customer' : 'supplier';
            $contactId = $payment->customer_id ?: $payment->supplier_id;

            if (!$contactId) {
                $errorCount++;
                $errors[] = "Payment ID {$payment->id}: No customer_id or supplier_id";
                continue;
            }

            // Create unique reference number for bulk payments
            $baseReferenceNo = $payment->reference_no;
            $uniqueReferenceNo = $baseReferenceNo;

            if (strpos($baseReferenceNo, 'BLK-') === 0) {
                $uniqueReferenceNo = $baseReferenceNo . '-PAY' . $payment->id;
            }

            // Determine transaction type
            $transactionType = 'payments';
            if ($contactType === 'supplier') {
                if ($payment->payment_type === 'purchase') {
                    $transactionType = 'purchase_payment';
                } else {
                    $transactionType = 'payments';
                }
            } else {
                if ($payment->payment_type === 'sale') {
                    $transactionType = 'payments';
                } else {
                    $transactionType = 'payments';
                }
            }

            // Use payment's actual creation date
            $transactionDate = Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo');

            // Create ledger entry
            $ledgerEntry = Ledger::createEntry([
                'contact_id' => $contactId,
                'contact_type' => $contactType,
                'transaction_date' => $transactionDate,
                'reference_no' => $uniqueReferenceNo,
                'transaction_type' => $transactionType,
                'amount' => $payment->amount,
                'notes' => $payment->notes ?: "Payment #{$baseReferenceNo} [RECOVERED ENTRY]",
                'created_by' => $payment->created_by ?? 1
            ]);

            if ($ledgerEntry) {
                $successCount++;
                echo "✅ Created ledger entry for Payment ID {$payment->id} (Ref: {$uniqueReferenceNo})\n";
            } else {
                $errorCount++;
                $errors[] = "Payment ID {$payment->id}: Ledger entry creation returned null";
            }

        } catch (\Exception $e) {
            $errorCount++;
            $errors[] = "Payment ID {$paymentData->id}: " . $e->getMessage();
            echo "❌ Error creating ledger for Payment ID {$paymentData->id}: " . $e->getMessage() . "\n";
        }
    }
});

echo "\n" . str_repeat('=', 100) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 100) . "\n";
echo "✅ Successfully created: {$successCount} ledger entries\n";
echo "❌ Errors: {$errorCount}\n";

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n✨ Fix completed!\n";
echo "\n📊 Next steps:\n";
echo "1. Verify the ledger entries in the database\n";
echo "2. Check customer/supplier balance reports to ensure accuracy\n";
echo "3. Test new bulk payments to ensure they create ledger entries correctly\n";
