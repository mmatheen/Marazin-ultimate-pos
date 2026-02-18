<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

echo "=== FIXING CUSTOMER ID 6 LEDGER ISSUES ===\n\n";

DB::beginTransaction();

try {
    $customer = Customer::withoutLocationScope()->find(6);

    if (!$customer) {
        echo "Customer ID 6 not found!\n";
        exit;
    }

    echo "Step 1: Checking orphaned payments for deleted sale APS-012...\n";

    // Find payments for deleted sale APS-012
    $orphanedPayments = Payment::withoutGlobalScopes()
        ->where('customer_id', 6)
        ->where('reference_no', 'APS-012')
        ->get();

    foreach ($orphanedPayments as $payment) {
        echo "  - Payment ID {$payment->id}: Rs {$payment->amount} (Status: {$payment->status})\n";

        if ($payment->status !== 'deleted') {
            echo "    → Setting status to 'deleted' (sale was deleted)\n";
            $payment->update(['status' => 'deleted']);
        }
    }

    echo "\nStep 2: Checking Sale APS-013 payment synchronization...\n";

    $sale = Sale::withoutGlobalScopes()
        ->where('customer_id', 6)
        ->where('invoice_no', 'APS-013')
        ->first();

    if ($sale) {
        echo "  - Sale ID {$sale->id}: Invoice APS-013\n";
        echo "    Current: Total={$sale->final_total}, Paid={$sale->total_paid}, Due={$sale->total_due}\n";

        // Find the actual payment
        $payment = Payment::withoutGlobalScopes()
            ->where('customer_id', 6)
            ->where('reference_no', 'APS-013')
            ->where('status', 'active')
            ->first();

        if ($payment) {
            echo "    Found Payment ID {$payment->id}: Rs {$payment->amount}\n";

            if ($sale->total_paid != $payment->amount) {
                echo "    → Updating sale payment amounts...\n";
                $sale->total_paid = $payment->amount;
                $sale->total_due = $sale->final_total - $payment->amount;

                // Update payment status
                if ($sale->total_due == 0) {
                    $sale->payment_status = 'paid';
                } elseif ($sale->total_paid > 0) {
                    $sale->payment_status = 'partial';
                } else {
                    $sale->payment_status = 'due';
                }

                $sale->save();

                echo "    Updated: Total={$sale->final_total}, Paid={$sale->total_paid}, Due={$sale->total_due}, Status={$sale->payment_status}\n";
            } else {
                echo "    ✓ Payment amounts already correct\n";
            }
        } else {
            echo "    ⚠️ No active payment found for this sale!\n";
        }
    } else {
        echo "  ⚠️ Sale APS-013 not found!\n";
    }

    echo "\nStep 3: Recalculating customer balance...\n";

    // Use BalanceHelper or calculate from ledger
    $activeEntries = Ledger::where('contact_id', 6)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->get();

    $totalDebit = $activeEntries->sum('debit');
    $totalCredit = $activeEntries->sum('credit');
    $calculatedBalance = $totalDebit - $totalCredit;

    echo "  - Total Debit (Active): {$totalDebit}\n";
    echo "  - Total Credit (Active): {$totalCredit}\n";
    echo "  - Calculated Balance: {$calculatedBalance}\n";
    echo "  - Current Cached Balance: {$customer->balance}\n";

    if ($customer->balance != $calculatedBalance) {
        echo "  → Updating cached balance...\n";
        $customer->balance = $calculatedBalance;
        $customer->save();
        echo "  ✓ Balance updated to {$calculatedBalance}\n";
    } else {
        echo "  ✓ Cached balance is correct\n";
    }

    echo "\n=== VERIFICATION ===\n";

    // Re-check everything
    $customer->refresh();
    $sale = Sale::withoutGlobalScopes()->where('customer_id', 6)->where('invoice_no', 'APS-013')->first();
    $payments = Payment::withoutGlobalScopes()->where('customer_id', 6)->where('status', 'active')->get();

    echo "Customer Balance: {$customer->balance}\n";
    echo "Active Sale APS-013: Total={$sale->final_total}, Paid={$sale->total_paid}, Due={$sale->total_due}, Status={$sale->payment_status}\n";
    echo "Active Payments: " . $payments->count() . " (Total: {$payments->sum('amount')})\n";

    $expectedBalance = $sale->total_due;
    if (abs($customer->balance - $expectedBalance) < 0.01) {
        echo "\n✅ SUCCESS: Balance is now correct!\n";

        echo "\nCommitting changes...\n";
        DB::commit();
        echo "✅ All changes committed successfully!\n";
    } else {
        echo "\n⚠️ WARNING: Balance still doesn't match!\n";
        echo "Expected: {$expectedBalance}, Got: {$customer->balance}\n";
        echo "\nRolling back changes...\n";
        DB::rollBack();
        echo "❌ Changes rolled back - manual review needed\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Changes rolled back.\n";
}

echo "\n=== DONE ===\n";
