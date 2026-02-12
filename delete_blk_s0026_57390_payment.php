<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DELETE BLK-S0026 PAYMENT (Rs 57,390) ===\n\n";

// Find the payment
$payment = DB::table('payments')
    ->where('reference_no', 'BLK-S0026')
    ->where('customer_id', 958)
    ->where('amount', 57390)
    ->first();

if (!$payment) {
    echo "❌ Payment not found!\n";
    exit;
}

echo "PAYMENT FOUND:\n";
echo str_repeat("=", 100) . "\n";
echo "Payment ID: {$payment->id}\n";
echo "Amount: Rs " . number_format($payment->amount, 2) . "\n";
echo "Date: {$payment->payment_date}\n";
echo "Reference: {$payment->reference_no}\n";
echo "For Sale ID: {$payment->reference_id}\n";
echo "Status: {$payment->status}\n\n";

// Get the sale
$sale = DB::table('sales')->find($payment->reference_id);
if ($sale) {
    echo "LINKED TO SALE:\n";
    echo "  Invoice: {$sale->invoice_no}\n";
    echo "  Sale Amount: Rs " . number_format($sale->final_total, 2) . "\n";
    echo "  Currently Paid: Rs " . number_format($sale->total_paid, 2) . "\n";
    echo "  Currently Due: Rs " . number_format($sale->total_due, 2) . "\n\n";
}

// Find the ledger entry
$ledgerEntry = DB::table('ledgers')
    ->where('reference_no', 'BLK-S0026')
    ->where('contact_id', 958)
    ->where('credit', 57390)
    ->first();

if ($ledgerEntry) {
    echo "LEDGER ENTRY FOUND:\n";
    echo "  Ledger ID: {$ledgerEntry->id}\n";
    echo "  Date: {$ledgerEntry->transaction_date}\n";
    echo "  Credit: Rs " . number_format($ledgerEntry->credit, 2) . "\n";
    echo "  Status: {$ledgerEntry->status}\n\n";
}

echo "IMPACT OF DELETION:\n";
echo str_repeat("=", 100) . "\n";
echo "1. Payment record will be deleted\n";
echo "2. Ledger entry will be deleted\n";
if ($sale) {
    $newPaid = $sale->total_paid - $payment->amount;
    $newDue = $sale->final_total - $newPaid;
    echo "3. Sale {$sale->invoice_no} will be updated:\n";
    echo "   - Total Paid: Rs " . number_format($sale->total_paid, 2) . " → Rs " . number_format($newPaid, 2) . "\n";
    echo "   - Total Due: Rs " . number_format($sale->total_due, 2) . " → Rs " . number_format($newDue, 2) . "\n";
    echo "   - Status: {$sale->payment_status} → " . ($newDue <= 0 ? 'Paid' : ($newPaid > 0 ? 'Partial' : 'Due')) . "\n";
}
echo "\n";

// Calculate balance impact
$currentBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first()->balance ?? 0;

$newBalance = $currentBalance + $payment->amount;

echo "4. Customer balance:\n";
echo "   - Current: Rs " . number_format($currentBalance, 2) . "\n";
echo "   - After deletion: Rs " . number_format($newBalance, 2) . "\n\n";

echo str_repeat("=", 100) . "\n";
echo "\n⚠️  WARNING: This will remove the Rs 57,390 payment!\n";
echo "The customer will owe Rs " . number_format($newBalance, 2) . " after this deletion.\n\n";

echo "Do you want to proceed? (YES/NO): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if (strtoupper($answer) !== 'YES') {
    echo "\nDeletion cancelled. No changes made.\n";
    exit;
}

echo "\nDeleting payment...\n\n";

DB::beginTransaction();

try {
    // Delete the payment
    DB::table('payments')->where('id', $payment->id)->delete();
    echo "✓ Payment #{$payment->id} deleted\n";

    // Delete the ledger entry
    if ($ledgerEntry) {
        DB::table('ledgers')->where('id', $ledgerEntry->id)->delete();
        echo "✓ Ledger entry #{$ledgerEntry->id} deleted\n";
    }

    // Update the sale
    if ($sale) {
        $totalPaid = DB::table('payments')
            ->where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', 'active')
            ->sum('amount');

        $newStatus = $totalPaid >= $sale->final_total ? 'Paid' :
                    ($totalPaid > 0 ? 'Partial' : 'Due');

        DB::table('sales')
            ->where('id', $sale->id)
            ->update([
                'total_paid' => $totalPaid,
                'payment_status' => $newStatus
            ]);

        echo "✓ Sale {$sale->invoice_no} updated: total_paid=Rs " . number_format($totalPaid, 2) .
             ", status={$newStatus}\n";
    }

    DB::commit();

    echo "\n" . str_repeat("=", 100) . "\n";
    echo "✅ DELETION COMPLETE!\n\n";

    // Show new balance
    $finalBalance = DB::table('ledgers')
        ->where('contact_id', 958)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->selectRaw('SUM(debit) - SUM(credit) as balance')
        ->first()->balance ?? 0;

    echo "Customer 958 new balance: Rs " . number_format($finalBalance, 2) . " DUE\n";
    echo "The Rs 57,390 payment has been removed from the system.\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back. No changes made.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
