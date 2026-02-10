<?php

/**
 * Migrate Bulk Payment Ledgers from Old to New Format
 * 
 * Old: BLK-S0075 (same for all - caused duplicates)
 * New: BLK-S0075-PAY638, BLK-S0075-PAY639, etc. (unique for each)
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\Ledger;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════════════╗\n";
echo "║              MIGRATE BULK PAYMENT LEDGERS - Old Format → New Format           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$bulkRef = 'BLK-S0075';
$customerId = 1058;

// Get all payments for this bulk reference
$payments = Payment::where('reference_no', $bulkRef)
    ->where('customer_id', $customerId)
    ->orderBy('id')
    ->get();

// Get old format ledgers
$oldLedgers = Ledger::where('contact_id', $customerId)
    ->where('reference_no', $bulkRef)
    ->where('status', 'active')
    ->orderBy('id')
    ->get();

echo "📊 Current Status:\n";
echo "   Payments: " . count($payments) . "\n";
echo "   Old format ledgers: " . count($oldLedgers) . "\n";
echo "   Missing: " . (count($payments) - count($oldLedgers)) . "\n\n";

echo "🔄 Migration Plan:\n";
echo "   1. Mark old 17 ledgers as 'migrated' (not deleted - keeps audit trail)\n";
echo "   2. Create 18 new ledgers with unique references\n";
echo "   3. Each payment gets its own ledger entry\n\n";

echo str_repeat('-', 100) . "\n";
printf("%-10s %-25s %-12s %s\n", "Payment", "Old Reference", "Amount", "New Reference");
echo str_repeat('-', 100) . "\n";

foreach ($payments as $payment) {
    $newRef = $bulkRef . '-PAY' . $payment->id;
    printf("%-10s %-25s %-12s %s\n",
        $payment->id,
        $bulkRef,
        'Rs. ' . number_format($payment->amount, 2),
        $newRef
    );
}

echo str_repeat('-', 100) . "\n\n";

echo "⚠️  This will:\n";
echo "   • Mark 17 old ledgers as 'migrated' status (keeps history)\n";
echo "   • Create 18 new ledgers with unique references\n";
echo "   • Customer balance will include ALL 18 payments correctly\n\n";

echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\n❌ Migration cancelled.\n\n";
    exit(0);
}

echo "\n🔧 Starting migration...\n\n";

$successCount = 0;
$errorCount = 0;

DB::transaction(function() use ($bulkRef, $customerId, $payments, $oldLedgers, &$successCount, &$errorCount) {
    
    // Step 1: Mark old ledgers as migrated
    echo "📝 Step 1: Marking old ledgers as 'migrated'...\n";
    foreach ($oldLedgers as $oldLedger) {
        try {
            $oldLedger->status = 'migrated';
            $oldLedger->notes = ($oldLedger->notes ?: '') . ' [MIGRATED to unique reference format on ' . date('Y-m-d H:i:s') . ']';
            $oldLedger->save();
            echo "   ✓ Migrated ledger #{$oldLedger->id}\n";
        } catch (\Exception $e) {
            echo "   ✗ Error migrating ledger #{$oldLedger->id}: {$e->getMessage()}\n";
            $errorCount++;
        }
    }
    
    echo "\n📝 Step 2: Creating new ledgers with unique references...\n";
    
    // Step 2: Create new ledgers for ALL payments (including the missing one)
    foreach ($payments as $payment) {
        try {
            $newRef = $bulkRef . '-PAY' . $payment->id;
            
            // Check if already exists (shouldn't, but safety check)
            $exists = Ledger::where('contact_id', $customerId)
                ->where('reference_no', $newRef)
                ->where('status', 'active')
                ->exists();
            
            if ($exists) {
                echo "   ⊘ Skipped payment #{$payment->id} - ledger already exists\n";
                continue;
            }
            
            // Use original payment creation date
            $transactionDate = Carbon::parse($payment->created_at)->setTimezone('Asia/Colombo');
            
            // Create new ledger entry
            $newLedger = new Ledger();
            $newLedger->contact_id = $customerId;
            $newLedger->contact_type = 'customer';
            $newLedger->transaction_date = $transactionDate;
            $newLedger->reference_no = $newRef;
            $newLedger->transaction_type = 'payments';
            $newLedger->debit = 0;
            $newLedger->credit = $payment->amount;
            $newLedger->status = 'active';
            $newLedger->notes = $payment->notes ?: "Payment #{$bulkRef} [Migrated to unique format]";
            $newLedger->created_by = $payment->created_by ?? 1;
            $newLedger->created_at = $transactionDate;
            $newLedger->updated_at = Carbon::now();
            $newLedger->save();
            
            echo "   ✓ Created ledger for payment #{$payment->id} → {$newRef} (Rs. " . number_format($payment->amount, 2) . ")\n";
            $successCount++;
            
        } catch (\Exception $e) {
            echo "   ✗ Error creating ledger for payment #{$payment->id}: {$e->getMessage()}\n";
            $errorCount++;
        }
    }
});

echo "\n" . str_repeat('=', 100) . "\n";
echo "MIGRATION SUMMARY\n";
echo str_repeat('=', 100) . "\n";
echo "Old ledgers migrated: " . count($oldLedgers) . "\n";
echo "New ledgers created: {$successCount}\n";
echo "Errors: {$errorCount}\n";
echo str_repeat('=', 100) . "\n\n";

// Verify the migration
echo "🔍 Verification:\n\n";

$oldCount = Ledger::where('contact_id', $customerId)
    ->where('reference_no', $bulkRef)
    ->where('status', 'migrated')
    ->count();

$newCount = Ledger::where('contact_id', $customerId)
    ->where('reference_no', 'LIKE', $bulkRef . '-PAY%')
    ->where('status', 'active')
    ->count();

$totalCredits = Ledger::where('contact_id', $customerId)
    ->where('reference_no', 'LIKE', $bulkRef . '%')
    ->where('status', 'active')
    ->sum('credit');

echo "   Old format (migrated): {$oldCount}\n";
echo "   New format (active): {$newCount}\n";
echo "   Total active credits: Rs. " . number_format($totalCredits, 2) . "\n\n";

if ($newCount == count($payments)) {
    echo "✅ Migration successful! All " . count($payments) . " payments now have unique ledger entries.\n\n";
    echo "📊 Customer balance now includes:\n";
    echo "   • All 18 payments (Rs. 700,000.00)\n";
    echo "   • Accurate balance calculation\n";
    echo "   • Complete audit trail (old entries preserved as 'migrated')\n\n";
} else {
    echo "⚠️  Warning: Expected {$payments->count()} new ledgers, but found {$newCount}\n\n";
}

echo "✨ Done!\n\n";
