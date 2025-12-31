<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteCustomerPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:delete-payments 
                            {customer_id : The customer ID to delete payments for}
                            {--dry-run : Run without making actual changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all payments for a customer and update related ledgers and sales';

    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        parent::__construct();
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('');
        $this->info(str_repeat('=', 80));
        $this->info("🔧 DELETE PAYMENTS FOR CUSTOMER ID: {$customerId}");
        $this->info(str_repeat('=', 80));
        $this->info("Mode: " . ($dryRun ? "🔍 DRY RUN (No changes will be made)" : "⚠️ LIVE EXECUTION"));
        $this->info('');

        // Verify customer exists (without global scopes to include soft-deleted)
        $customer = Customer::withoutGlobalScopes()->find($customerId);
        if (!$customer) {
            $this->error("❌ Customer ID {$customerId} not found!");
            return 1;
        }

        $this->info("Customer: {$customer->name} (ID: {$customerId})");
        $this->info('');

        // Analyze current state
        $analysis = $this->analyzeCurrentState($customerId);

        if ($analysis['payments']->count() === 0) {
            $this->warn('No active payments found for this customer.');
            return 0;
        }

        // Confirm execution
        if (!$dryRun && !$force) {
            $this->warn('');
            $this->warn("⚠️ WARNING: This will delete {$analysis['payments']->count()} payments");
            $this->warn("   Total amount: Rs. " . number_format($analysis['payments']->sum('amount'), 2));
            $this->warn("   Affected sales: " . $analysis['saleIds']->count());
            $this->warn('');

            if (!$this->confirm('Do you want to proceed with deletion?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        if ($dryRun) {
            $this->info('');
            $this->warn("🔍 DRY RUN MODE: No changes were made.");
            $this->info("To execute, run without --dry-run flag:");
            $this->info("   php artisan customer:delete-payments {$customerId}");
            return 0;
        }

        // Execute deletion
        return $this->executeFullDeletion($customerId, $analysis);
    }

    /**
     * Analyze current state of payments, ledgers, and sales
     */
    private function analyzeCurrentState($customerId)
    {
        $this->info("📊 CURRENT STATE ANALYSIS");
        $this->info(str_repeat('-', 50));

        // Get all payments
        $allPayments = Payment::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->get();

        $activePayments = $allPayments->where('status', 'active');
        $deletedPayments = $allPayments->where('status', 'deleted');

        $this->info("📋 PAYMENTS SUMMARY:");
        $this->info("   Total payments found: " . $allPayments->count());
        $this->info("   Active payments: " . $activePayments->count());
        $this->info("   Already deleted: " . $deletedPayments->count());
        $this->info("   Total amount (active): Rs. " . number_format($activePayments->sum('amount'), 2));

        // List active payments in table
        if ($activePayments->count() > 0) {
            $this->info('');
            $this->info("📝 ACTIVE PAYMENTS TO DELETE:");
            
            $tableData = [];
            foreach ($activePayments as $payment) {
                $tableData[] = [
                    'ID' => $payment->id,
                    'Date' => $payment->payment_date,
                    'Reference' => $payment->reference_no ?? '-',
                    'Amount' => number_format($payment->amount, 2),
                    'Method' => $payment->payment_method,
                    'Sale ID' => $payment->reference_id ?? '-',
                    'Notes' => substr($payment->notes ?? '-', 0, 30)
                ];
            }
            
            $this->table(
                ['ID', 'Date', 'Reference', 'Amount', 'Method', 'Sale ID', 'Notes'],
                $tableData
            );
        }

        // Get ledger entries
        $allLedgerEntries = Ledger::withoutGlobalScopes()
            ->where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();

        $activeLedgerEntries = $allLedgerEntries->where('status', 'active');
        $paymentLedgerEntries = $activeLedgerEntries->where('transaction_type', 'payments');

        $this->info('');
        $this->info("📒 LEDGER ENTRIES SUMMARY:");
        $this->info("   Total entries: " . $allLedgerEntries->count());
        $this->info("   Active entries: " . $activeLedgerEntries->count());
        $this->info("   Active payment entries: " . $paymentLedgerEntries->count());
        $this->info("   Total debit (active): Rs. " . number_format($activeLedgerEntries->sum('debit'), 2));
        $this->info("   Total credit (active): Rs. " . number_format($activeLedgerEntries->sum('credit'), 2));

        // Get affected sales (without global scopes to bypass LocationScope)
        $saleIds = $activePayments->pluck('reference_id')->filter()->unique();
        $affectedSales = Sale::withoutGlobalScopes()->whereIn('id', $saleIds)->get();

        $this->info('');
        $this->info("🛒 AFFECTED SALES:");
        
        if ($affectedSales->count() > 0) {
            $salesTableData = [];
            foreach ($affectedSales as $sale) {
                $salesTableData[] = [
                    'ID' => $sale->id,
                    'Invoice' => $sale->invoice_no ?? '-',
                    'Date' => $sale->created_at->format('Y-m-d'),
                    'Final Total' => number_format($sale->final_total, 2),
                    'Paid' => number_format($sale->total_paid, 2),
                    'Due' => number_format($sale->total_due, 2),
                    'Status' => $sale->payment_status
                ];
            }
            
            $this->table(
                ['ID', 'Invoice', 'Date', 'Final Total', 'Paid', 'Due', 'Status'],
                $salesTableData
            );
        }

        return [
            'payments' => $activePayments,
            'ledgerEntries' => $paymentLedgerEntries,
            'sales' => $affectedSales,
            'saleIds' => $saleIds
        ];
    }

    /**
     * Execute the full deletion process
     */
    private function executeFullDeletion($customerId, $analysis)
    {
        $this->info('');
        $this->info("🚀 EXECUTING FULL DELETION...");
        $this->info(str_repeat('=', 50));

        try {
            DB::beginTransaction();

            $payments = $analysis['payments'];
            $deletedCount = 0;
            $totalAmount = 0;
            $affectedSaleIds = [];

            $progressBar = $this->output->createProgressBar($payments->count());
            $progressBar->start();

            foreach ($payments as $payment) {
                // Step 1: Handle ledger reversal
                $result = $this->unifiedLedgerService->deletePayment(
                    $payment,
                    'Bulk deletion via artisan command - Customer ' . $customerId,
                    null
                );

                // Step 2: Mark payment as deleted (soft delete via status)
                $payment->update([
                    'status' => 'deleted',
                    'notes' => ($payment->notes ?? '') . ' | [BULK DELETED: Artisan command - ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                // Track affected sales
                if ($payment->reference_id && $payment->payment_type === 'sale') {
                    $affectedSaleIds[] = $payment->reference_id;
                }

                $deletedCount++;
                $totalAmount += $payment->amount;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->info('');
            $this->info('');

            // Step 3: Update all affected sales
            $affectedSaleIds = array_unique($affectedSaleIds);
            $this->info("📊 UPDATING AFFECTED SALES (" . count($affectedSaleIds) . " sales)...");

            foreach ($affectedSaleIds as $saleId) {
                $this->updateSalePaymentTotals($saleId);
            }

            // Step 4: Cleanup remaining ledger entries
            $this->cleanupDirectLedgerEntries($customerId);

            DB::commit();

            $this->info('');
            $this->info(str_repeat('=', 50));
            $this->info("✅ DELETION COMPLETED SUCCESSFULLY!");
            $this->info("   Payments deleted: {$deletedCount}");
            $this->info("   Total amount: Rs. " . number_format($totalAmount, 2));
            $this->info("   Sales updated: " . count($affectedSaleIds));
            $this->info(str_repeat('=', 50));

            // Show final state
            $this->showFinalState($customerId);

            // Verify integrity
            $this->verifyIntegrity($customerId);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('');
            $this->error("❌ ERROR: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " Line: " . $e->getLine());
            $this->error("All changes have been rolled back.");

            Log::error('Failed to delete customer payments', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Update sale payment totals
     */
    private function updateSalePaymentTotals($saleId)
    {
        // Use withoutGlobalScopes to bypass LocationScope
        $sale = Sale::withoutGlobalScopes()->find($saleId);
        if (!$sale) {
            $this->warn("   ⚠️ Sale ID {$saleId} not found");
            return;
        }

        $totalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', 'active')
            ->sum('amount');

        $oldTotalPaid = $sale->total_paid;
        $oldPaymentStatus = $sale->payment_status;

        $sale->total_paid = $totalPaid;
        $sale->save();
        $sale->refresh();

        if ($sale->total_due <= 0) {
            $sale->payment_status = 'Paid';
        } elseif ($sale->total_paid > 0) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }
        $sale->save();

        $this->line("   ✅ Sale {$saleId} ({$sale->invoice_no}): Paid: " . 
            number_format($oldTotalPaid, 2) . " → " . number_format($sale->total_paid, 2) .
            ", Status: {$oldPaymentStatus} → {$sale->payment_status}");
    }

    /**
     * Cleanup remaining ledger entries
     */
    private function cleanupDirectLedgerEntries($customerId)
    {
        $this->info('');
        $this->info("🧹 CLEANING UP REMAINING LEDGER ENTRIES...");

        $remainingPaymentLedgers = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->get();

        if ($remainingPaymentLedgers->count() > 0) {
            $this->warn("   Found {$remainingPaymentLedgers->count()} remaining payment ledger entries");

            foreach ($remainingPaymentLedgers as $ledger) {
                $ledger->update([
                    'status' => 'reversed',
                    'notes' => $ledger->notes . ' [CLEANUP REVERSED: Bulk deletion - ' . now()->format('Y-m-d H:i:s') . ']'
                ]);
                $this->line("   ✅ Ledger ID {$ledger->id} ({$ledger->reference_no}) marked as reversed");
            }
        } else {
            $this->info("   ✅ No remaining payment ledger entries found");
        }
    }

    /**
     * Show final state
     */
    private function showFinalState($customerId)
    {
        $this->info('');
        $this->info("📊 FINAL STATE AFTER DELETION");
        $this->info(str_repeat('-', 50));

        $remainingPayments = Payment::where('customer_id', $customerId)
            ->where('status', 'active')
            ->get();

        $this->info("Remaining active payments: " . $remainingPayments->count());

        $activeLedgers = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('status', 'active')
            ->get();

        $this->info("Active ledger entries: " . $activeLedgers->count());
        $this->info("Total debit: Rs. " . number_format($activeLedgers->sum('debit'), 2));
        $this->info("Total credit: Rs. " . number_format($activeLedgers->sum('credit'), 2));

        // Show updated sales (without global scopes)
        $sales = Sale::withoutGlobalScopes()
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->info('');
        $this->info("🛒 UPDATED SALES (Last 10):");
        
        if ($sales->count() > 0) {
            $salesTableData = [];
            foreach ($sales as $sale) {
                $salesTableData[] = [
                    'ID' => $sale->id,
                    'Invoice' => $sale->invoice_no ?? '-',
                    'Final Total' => number_format($sale->final_total, 2),
                    'Paid' => number_format($sale->total_paid, 2),
                    'Due' => number_format($sale->total_due, 2),
                    'Status' => $sale->payment_status
                ];
            }
            
            $this->table(
                ['ID', 'Invoice', 'Final Total', 'Paid', 'Due', 'Status'],
                $salesTableData
            );
        }
    }

    /**
     * Verify data integrity
     */
    private function verifyIntegrity($customerId)
    {
        $this->info('');
        $this->info("🔍 VERIFYING DATA INTEGRITY");
        $this->info(str_repeat('-', 50));

        $issues = [];

        // Check 1: No orphan ledger entries
        $orphanPaymentLedgers = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->count();

        if ($orphanPaymentLedgers > 0) {
            $issues[] = "⚠️ Found {$orphanPaymentLedgers} active payment ledger entries that should be reversed";
        }

        // Check 2: Sale totals match payments
        $sales = Sale::withoutGlobalScopes()->where('customer_id', $customerId)->get();
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
            $this->info("✅ All integrity checks passed!");
        } else {
            $this->warn("❌ Found " . count($issues) . " issues:");
            foreach ($issues as $issue) {
                $this->warn("   {$issue}");
            }
        }
    }
}
