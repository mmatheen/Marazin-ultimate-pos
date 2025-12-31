<?php

/**
 * Test Script: Delete All Payments for Customer 852
 * 
 * This script will:
 * 1. Find all payments for customer_id = 852
 * 2. Properly reverse/delete ledger entries for each payment
 * 3. Mark payments as deleted (soft delete)
 * 4. Update all related sales to recalculate total_paid, total_due, and payment_status
 * 
 * Run with: php artisan tinker tests/DeleteCustomer852PaymentsTest.php
 * Or run as artisan command: php artisan customer:delete-payments 852
 */

namespace Tests;

use App\Models\Payment;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteCustomer852PaymentsTest
{
    protected $customerId = 852;
    protected $unifiedLedgerService;
    protected $dryRun = true; // Set to false to actually execute

    public function __construct($dryRun = true)
    {
        $this->dryRun = $dryRun;
        $this->unifiedLedgerService = app(UnifiedLedgerService::class);
    }

    /**
     * Main execution method
     */
    public function run()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "🔧 DELETE PAYMENTS TEST SCRIPT FOR CUSTOMER ID: {$this->customerId}\n";
        echo str_repeat("=", 80) . "\n";
        echo "Mode: " . ($this->dryRun ? "🔍 DRY RUN (No changes will be made)" : "⚠️ LIVE EXECUTION") . "\n\n";

        // Step 1: Analyze current state
        $this->analyzeCurrentState();

        // Step 2: Execute deletion (if not dry run)
        if (!$this->dryRun) {
            $this->executeFullDeletion();
        } else {
            echo "\n⚠️ DRY RUN MODE: No changes were made.\n";
            echo "To execute, call: (new DeleteCustomer852PaymentsTest(false))->run()\n";
        }

        return $this;
    }

    /**
     * Analyze current state of payments, ledgers, and sales
     */
    public function analyzeCurrentState()
    {
        echo "\n📊 CURRENT STATE ANALYSIS\n";
        echo str_repeat("-", 50) . "\n";

        // Get customer info
        $customer = Customer::find($this->customerId);
        if ($customer) {
            echo "Customer: {$customer->name} (ID: {$this->customerId})\n";
        } else {
            echo "⚠️ Customer ID {$this->customerId} not found!\n";
            return;
        }

        // Get all payments for this customer (including deleted ones for full picture)
        $allPayments = Payment::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->get();

        $activePayments = $allPayments->where('status', 'active');
        $deletedPayments = $allPayments->where('status', 'deleted');

        echo "\n📋 PAYMENTS SUMMARY:\n";
        echo "   Total payments found: " . $allPayments->count() . "\n";
        echo "   Active payments: " . $activePayments->count() . "\n";
        echo "   Already deleted: " . $deletedPayments->count() . "\n";
        echo "   Total amount (active): Rs. " . number_format($activePayments->sum('amount'), 2) . "\n";

        // List active payments
        if ($activePayments->count() > 0) {
            echo "\n📝 ACTIVE PAYMENTS TO DELETE:\n";
            echo str_repeat("-", 100) . "\n";
            echo sprintf("%-6s | %-12s | %-15s | %-12s | %-10s | %-12s | %-20s\n",
                "ID", "Date", "Reference No", "Amount", "Method", "Sale ID", "Notes");
            echo str_repeat("-", 100) . "\n";

            foreach ($activePayments as $payment) {
                $notes = substr($payment->notes ?? '-', 0, 20);
                echo sprintf("%-6s | %-12s | %-15s | %12s | %-10s | %-12s | %-20s\n",
                    $payment->id,
                    $payment->payment_date,
                    $payment->reference_no ?? '-',
                    number_format($payment->amount, 2),
                    $payment->payment_method,
                    $payment->reference_id ?? '-',
                    $notes
                );
            }
            echo str_repeat("-", 100) . "\n";
        }

        // Get all ledger entries for this customer
        $allLedgerEntries = Ledger::withoutGlobalScopes()
            ->where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeLedgerEntries = $allLedgerEntries->where('status', 'active');
        $paymentLedgerEntries = $activeLedgerEntries->where('transaction_type', 'payments');

        echo "\n📒 LEDGER ENTRIES SUMMARY:\n";
        echo "   Total entries: " . $allLedgerEntries->count() . "\n";
        echo "   Active entries: " . $activeLedgerEntries->count() . "\n";
        echo "   Active payment entries: " . $paymentLedgerEntries->count() . "\n";
        echo "   Total debit (active): Rs. " . number_format($activeLedgerEntries->sum('debit'), 2) . "\n";
        echo "   Total credit (active): Rs. " . number_format($activeLedgerEntries->sum('credit'), 2) . "\n";

        // Get affected sales
        $saleIds = $activePayments->pluck('reference_id')->filter()->unique();
        $affectedSales = Sale::whereIn('id', $saleIds)->get();

        echo "\n🛒 AFFECTED SALES:\n";
        echo str_repeat("-", 120) . "\n";
        echo sprintf("%-6s | %-15s | %-12s | %12s | %12s | %12s | %-10s\n",
            "ID", "Invoice", "Date", "Final Total", "Paid", "Due", "Status");
        echo str_repeat("-", 120) . "\n";

        foreach ($affectedSales as $sale) {
            echo sprintf("%-6s | %-15s | %-12s | %12s | %12s | %12s | %-10s\n",
                $sale->id,
                $sale->invoice_no ?? '-',
                $sale->created_at->format('Y-m-d'),
                number_format($sale->final_total, 2),
                number_format($sale->total_paid, 2),
                number_format($sale->total_due, 2),
                $sale->payment_status
            );
        }
        echo str_repeat("-", 120) . "\n";

        return [
            'payments' => $activePayments,
            'ledgerEntries' => $paymentLedgerEntries,
            'sales' => $affectedSales
        ];
    }

    /**
     * Execute the full deletion process
     */
    public function executeFullDeletion()
    {
        echo "\n🚀 EXECUTING FULL DELETION...\n";
        echo str_repeat("=", 50) . "\n";

        try {
            DB::beginTransaction();

            // Get active payments
            $payments = Payment::where('customer_id', $this->customerId)
                ->where('status', 'active')
                ->get();

            $deletedCount = 0;
            $totalAmount = 0;
            $affectedSaleIds = [];

            foreach ($payments as $payment) {
                echo "\n🗑️ Processing Payment ID: {$payment->id}\n";
                echo "   Reference: {$payment->reference_no}\n";
                echo "   Amount: Rs. " . number_format($payment->amount, 2) . "\n";
                echo "   Method: {$payment->payment_method}\n";

                // Step 1: Handle ledger reversal using UnifiedLedgerService
                $result = $this->unifiedLedgerService->deletePayment(
                    $payment,
                    'Bulk deletion via test script - Customer ' . $this->customerId,
                    null // System deletion
                );

                if ($result) {
                    echo "   ✅ Ledger entry reversed\n";
                }

                // Step 2: Mark payment as deleted (soft delete via status)
                $payment->update([
                    'status' => 'deleted',
                    'notes' => ($payment->notes ?? '') . ' | [BULK DELETED: Test script - ' . now()->format('Y-m-d H:i:s') . ']'
                ]);
                echo "   ✅ Payment marked as deleted\n";

                // Track affected sales
                if ($payment->reference_id && $payment->payment_type === 'sale') {
                    $affectedSaleIds[] = $payment->reference_id;
                }

                $deletedCount++;
                $totalAmount += $payment->amount;
            }

            // Step 3: Update all affected sales
            $affectedSaleIds = array_unique($affectedSaleIds);
            echo "\n📊 UPDATING AFFECTED SALES ({" . count($affectedSaleIds) . "} sales)...\n";

            foreach ($affectedSaleIds as $saleId) {
                $this->updateSalePaymentTotals($saleId);
            }

            // Step 4: Also handle any direct ledger entries for payments
            $this->cleanupDirectLedgerEntries();

            DB::commit();

            echo "\n" . str_repeat("=", 50) . "\n";
            echo "✅ DELETION COMPLETED SUCCESSFULLY!\n";
            echo "   Payments deleted: {$deletedCount}\n";
            echo "   Total amount: Rs. " . number_format($totalAmount, 2) . "\n";
            echo "   Sales updated: " . count($affectedSaleIds) . "\n";
            echo str_repeat("=", 50) . "\n";

            // Show final state
            $this->showFinalState();

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
            echo "All changes have been rolled back.\n";
            throw $e;
        }
    }

    /**
     * Update sale payment totals after payment deletion
     */
    private function updateSalePaymentTotals($saleId)
    {
        $sale = Sale::find($saleId);
        if (!$sale) {
            echo "   ⚠️ Sale ID {$saleId} not found\n";
            return;
        }

        // Calculate new total_paid from remaining active payments
        $totalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', 'active')
            ->sum('amount');

        $oldTotalPaid = $sale->total_paid;
        $oldPaymentStatus = $sale->payment_status;

        // Update total_paid
        $sale->total_paid = $totalPaid;
        $sale->save();

        // Refresh to get calculated total_due
        $sale->refresh();

        // Calculate payment status
        if ($sale->total_due <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($sale->total_paid > 0) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }
        $sale->save();

        echo "   ✅ Sale {$saleId} ({$sale->invoice_no}): ";
        echo "Paid: " . number_format($oldTotalPaid, 2) . " → " . number_format($sale->total_paid, 2);
        echo ", Status: {$oldPaymentStatus} → {$sale->payment_status}\n";
    }

    /**
     * Cleanup any remaining active ledger entries for payment transactions
     */
    private function cleanupDirectLedgerEntries()
    {
        echo "\n🧹 CLEANING UP REMAINING LEDGER ENTRIES...\n";

        // Find any remaining active payment ledger entries
        $remainingPaymentLedgers = Ledger::where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->get();

        if ($remainingPaymentLedgers->count() > 0) {
            echo "   Found {$remainingPaymentLedgers->count()} remaining payment ledger entries\n";

            foreach ($remainingPaymentLedgers as $ledger) {
                $ledger->update([
                    'status' => 'reversed',
                    'notes' => $ledger->notes . ' [CLEANUP REVERSED: Bulk deletion - ' . now()->format('Y-m-d H:i:s') . ']'
                ]);
                echo "   ✅ Ledger ID {$ledger->id} ({$ledger->reference_no}) marked as reversed\n";
            }
        } else {
            echo "   ✅ No remaining payment ledger entries found\n";
        }
    }

    /**
     * Show final state after deletion
     */
    private function showFinalState()
    {
        echo "\n📊 FINAL STATE AFTER DELETION\n";
        echo str_repeat("-", 50) . "\n";

        // Get remaining active payments
        $remainingPayments = Payment::where('customer_id', $this->customerId)
            ->where('status', 'active')
            ->get();

        echo "Remaining active payments: " . $remainingPayments->count() . "\n";

        // Get active ledger entries
        $activeLedgers = Ledger::where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->get();

        echo "Active ledger entries: " . $activeLedgers->count() . "\n";
        echo "Total debit: Rs. " . number_format($activeLedgers->sum('debit'), 2) . "\n";
        echo "Total credit: Rs. " . number_format($activeLedgers->sum('credit'), 2) . "\n";

        // Show updated sales status
        $sales = Sale::where('customer_id', $this->customerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        echo "\n🛒 UPDATED SALES (Last 10):\n";
        echo str_repeat("-", 100) . "\n";
        echo sprintf("%-6s | %-15s | %12s | %12s | %12s | %-10s\n",
            "ID", "Invoice", "Final Total", "Paid", "Due", "Status");
        echo str_repeat("-", 100) . "\n";

        foreach ($sales as $sale) {
            echo sprintf("%-6s | %-15s | %12s | %12s | %12s | %-10s\n",
                $sale->id,
                $sale->invoice_no ?? '-',
                number_format($sale->final_total, 2),
                number_format($sale->total_paid, 2),
                number_format($sale->total_due, 2),
                $sale->payment_status
            );
        }
        echo str_repeat("-", 100) . "\n";
    }

    /**
     * Verify data integrity after deletion
     */
    public function verifyIntegrity()
    {
        echo "\n🔍 VERIFYING DATA INTEGRITY\n";
        echo str_repeat("-", 50) . "\n";

        $issues = [];

        // Check 1: No orphan ledger entries
        $orphanPaymentLedgers = Ledger::where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->count();

        if ($orphanPaymentLedgers > 0) {
            $issues[] = "⚠️ Found {$orphanPaymentLedgers} active payment ledger entries that should be reversed";
        }

        // Check 2: Sale totals match payments
        $sales = Sale::where('customer_id', $this->customerId)->get();
        foreach ($sales as $sale) {
            $calculatedPaid = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('status', 'active')
                ->sum('amount');

            if (abs($sale->total_paid - $calculatedPaid) > 0.01) {
                $issues[] = "⚠️ Sale {$sale->id}: total_paid ({$sale->total_paid}) doesn't match sum of payments ({$calculatedPaid})";
            }
        }

        if (empty($issues)) {
            echo "✅ All integrity checks passed!\n";
        } else {
            echo "❌ Found " . count($issues) . " issues:\n";
            foreach ($issues as $issue) {
                echo "   {$issue}\n";
            }
        }

        return empty($issues);
    }
}

// =============================================================================
// USAGE INSTRUCTIONS
// =============================================================================
// 
// Option 1: Run via Artisan Tinker
// --------------------------------
// php artisan tinker
// 
// // DRY RUN (preview only, no changes):
// require 'tests/DeleteCustomer852PaymentsTest.php';
// $test = new \Tests\DeleteCustomer852PaymentsTest(true);
// $test->run();
//
// // LIVE EXECUTION (actually delete):
// require 'tests/DeleteCustomer852PaymentsTest.php';
// $test = new \Tests\DeleteCustomer852PaymentsTest(false);
// $test->run();
//
// // Verify integrity after deletion:
// $test->verifyIntegrity();
//
// =============================================================================

// Auto-run if called directly (dry run by default)
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    // Bootstrap Laravel
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    // Parse command line args
    $dryRun = !in_array('--execute', $argv);
    
    echo "Running with " . ($dryRun ? "DRY RUN" : "LIVE EXECUTION") . " mode\n";
    echo "Use --execute flag to actually perform deletion\n\n";
    
    $test = new DeleteCustomer852PaymentsTest($dryRun);
    $test->run();
    
    if (!$dryRun) {
        $test->verifyIntegrity();
    }
}
