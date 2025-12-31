<?php

/**
 * ============================================================================
 * COMPREHENSIVE CUSTOMER PAYMENT CLEANUP SCRIPT
 * ============================================================================
 *
 * This script will:
 * 1. ANALYZE - Show all payments, ledger entries, and sales for a customer
 * 2. CONFIRM - Ask for confirmation before making changes
 * 3. FIX ALL - Delete payments, reverse ledgers, update sales
 *
 * Usage:
 *   php tests/CustomerPaymentCleanup.php [customer_id] [--execute]
 *
 * Examples:
 *   php tests/CustomerPaymentCleanup.php 852           # Analyze only
 *   php tests/CustomerPaymentCleanup.php 852 --execute # Analyze + Fix
 *
 * ============================================================================
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Payment;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Customer;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerPaymentCleanup
{
    protected $customerId;
    protected $executeMode = false;
    protected $unifiedLedgerService;

    // Analysis results
    protected $customer;
    protected $payments;
    protected $ledgerEntries;
    protected $sales;
    protected $paymentLedgers;

    public function __construct($customerId, $executeMode = false)
    {
        $this->customerId = $customerId;
        $this->executeMode = $executeMode;
        $this->unifiedLedgerService = app(UnifiedLedgerService::class);
    }

    /**
     * Main execution method
     */
    public function run()
    {
        $this->printHeader();

        // Step 1: Validate customer
        if (!$this->validateCustomer()) {
            return false;
        }

        // Step 2: Analyze current state
        $this->analyze();

        // Step 3: Show analysis results
        $this->showAnalysisResults();

        // Step 4: Execute fixes if in execute mode
        if ($this->executeMode) {
            $this->printSection("🚀 EXECUTING FIXES");

            if ($this->payments->count() === 0) {
                echo "No active payments to delete.\n";
                return true;
            }

            return $this->executeFixes();
        } else {
            $this->printSection("📋 ANALYSIS COMPLETE");
            echo "\n";
            echo "┌─────────────────────────────────────────────────────────────────┐\n";
            echo "│  To execute the cleanup, run with --execute flag:              │\n";
            echo "│                                                                 │\n";
            echo "│  php tests/CustomerPaymentCleanup.php {$this->customerId} --execute        │\n";
            echo "└─────────────────────────────────────────────────────────────────┘\n";
        }

        return true;
    }

    /**
     * Validate customer exists
     */
    private function validateCustomer()
    {
        $this->customer = Customer::withoutGlobalScopes()->find($this->customerId);

        if (!$this->customer) {
            echo "❌ ERROR: Customer ID {$this->customerId} not found!\n";
            return false;
        }

        echo "✅ Customer Found: {$this->customer->name} (ID: {$this->customerId})\n";
        echo "   Contact: " . ($this->customer->contact_number ?? 'N/A') . "\n";
        echo "   Email: " . ($this->customer->email ?? 'N/A') . "\n";

        return true;
    }

    /**
     * Analyze all data
     */
    private function analyze()
    {
        // Get all payments (including deleted for full picture)
        $allPayments = Payment::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->payments = $allPayments->where('status', 'active');

        // Get all ledger entries
        $this->ledgerEntries = Ledger::withoutGlobalScopes()
            ->where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->paymentLedgers = $this->ledgerEntries
            ->where('transaction_type', 'payments')
            ->where('status', 'active');

        // Get all sales
        $this->sales = Sale::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Show analysis results
     */
    private function showAnalysisResults()
    {
        $this->showPaymentsAnalysis();
        $this->showLedgerAnalysis();
        $this->showSalesAnalysis();
        $this->showSummary();
    }

    /**
     * Show payments analysis
     */
    private function showPaymentsAnalysis()
    {
        $this->printSection("📋 PAYMENTS ANALYSIS");

        $allPayments = Payment::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->get();

        $activePayments = $allPayments->where('status', 'active');
        $deletedPayments = $allPayments->where('status', 'deleted');

        echo "Total payments in database: " . $allPayments->count() . "\n";
        echo "├── Active payments: " . $activePayments->count() . "\n";
        echo "├── Deleted payments: " . $deletedPayments->count() . "\n";
        echo "└── Total active amount: Rs. " . number_format($activePayments->sum('amount'), 2) . "\n";

        if ($activePayments->count() > 0) {
            echo "\n┌" . str_repeat("─", 118) . "┐\n";
            echo sprintf("│ %-5s │ %-12s │ %-15s │ %12s │ %-8s │ %-8s │ %-8s │ %-20s │\n",
                "ID", "Date", "Reference", "Amount", "Method", "Sale ID", "Status", "Notes");
            echo "├" . str_repeat("─", 118) . "┤\n";

            foreach ($activePayments as $payment) {
                $notes = substr($payment->notes ?? '-', 0, 18);
                echo sprintf("│ %-5s │ %-12s │ %-15s │ %12s │ %-8s │ %-8s │ %-8s │ %-20s │\n",
                    $payment->id,
                    substr($payment->payment_date, 0, 10),
                    $payment->reference_no ?? '-',
                    number_format($payment->amount, 2),
                    $payment->payment_method,
                    $payment->reference_id ?? '-',
                    $payment->status,
                    $notes
                );
            }
            echo "└" . str_repeat("─", 118) . "┘\n";
        }
    }

    /**
     * Show ledger analysis
     */
    private function showLedgerAnalysis()
    {
        $this->printSection("📒 LEDGER ENTRIES ANALYSIS");

        $activeLedgers = $this->ledgerEntries->where('status', 'active');
        $reversedLedgers = $this->ledgerEntries->where('status', 'reversed');

        echo "Total ledger entries: " . $this->ledgerEntries->count() . "\n";
        echo "├── Active entries: " . $activeLedgers->count() . "\n";
        echo "├── Reversed entries: " . $reversedLedgers->count() . "\n";
        echo "├── Total Debit (active): Rs. " . number_format($activeLedgers->sum('debit'), 2) . "\n";
        echo "└── Total Credit (active): Rs. " . number_format($activeLedgers->sum('credit'), 2) . "\n";

        // Group by transaction type
        echo "\nBy Transaction Type (Active Only):\n";
        $byType = $activeLedgers->groupBy('transaction_type');
        foreach ($byType as $type => $entries) {
            $debit = $entries->sum('debit');
            $credit = $entries->sum('credit');
            echo "├── {$type}: " . $entries->count() . " entries";
            echo " (Debit: " . number_format($debit, 2);
            echo ", Credit: " . number_format($credit, 2) . ")\n";
        }

        // Show payment ledger entries
        if ($this->paymentLedgers->count() > 0) {
            echo "\n┌" . str_repeat("─", 110) . "┐\n";
            echo sprintf("│ %-5s │ %-12s │ %-15s │ %12s │ %12s │ %-8s │ %-25s │\n",
                "ID", "Date", "Reference", "Debit", "Credit", "Status", "Notes");
            echo "├" . str_repeat("─", 110) . "┤\n";

            foreach ($this->paymentLedgers as $ledger) {
                $notes = substr($ledger->notes ?? '-', 0, 23);
                echo sprintf("│ %-5s │ %-12s │ %-15s │ %12s │ %12s │ %-8s │ %-25s │\n",
                    $ledger->id,
                    substr($ledger->transaction_date, 0, 10),
                    $ledger->reference_no ?? '-',
                    number_format($ledger->debit, 2),
                    number_format($ledger->credit, 2),
                    $ledger->status,
                    $notes
                );
            }
            echo "└" . str_repeat("─", 110) . "┘\n";
        }
    }

    /**
     * Show sales analysis
     */
    private function showSalesAnalysis()
    {
        $this->printSection("🛒 SALES ANALYSIS");

        echo "Total sales: " . $this->sales->count() . "\n";
        echo "├── Total Final Amount: Rs. " . number_format($this->sales->sum('final_total'), 2) . "\n";
        echo "├── Total Paid: Rs. " . number_format($this->sales->sum('total_paid'), 2) . "\n";
        echo "└── Total Due: Rs. " . number_format($this->sales->sum('total_due'), 2) . "\n";

        // Payment status breakdown
        echo "\nBy Payment Status:\n";
        $byStatus = $this->sales->groupBy('payment_status');
        foreach ($byStatus as $status => $salesGroup) {
            echo "├── {$status}: " . $salesGroup->count() . " sales\n";
        }

        if ($this->sales->count() > 0) {
            echo "\n┌" . str_repeat("─", 115) . "┐\n";
            echo sprintf("│ %-5s │ %-12s │ %-12s │ %14s │ %14s │ %14s │ %-10s │\n",
                "ID", "Invoice", "Date", "Final Total", "Paid", "Due", "Status");
            echo "├" . str_repeat("─", 115) . "┤\n";

            foreach ($this->sales as $sale) {
                echo sprintf("│ %-5s │ %-12s │ %-12s │ %14s │ %14s │ %14s │ %-10s │\n",
                    $sale->id,
                    $sale->invoice_no ?? '-',
                    substr($sale->created_at, 0, 10),
                    number_format($sale->final_total, 2),
                    number_format($sale->total_paid, 2),
                    number_format($sale->total_due, 2),
                    $sale->payment_status
                );
            }
            echo "└" . str_repeat("─", 115) . "┘\n";
        }
    }

    /**
     * Show summary
     */
    private function showSummary()
    {
        $this->printSection("📊 SUMMARY - WHAT WILL BE FIXED");

        $activePaymentCount = $this->payments->count();
        $totalPaymentAmount = $this->payments->sum('amount');
        $paymentLedgerCount = $this->paymentLedgers->count();
        $affectedSaleIds = $this->payments->pluck('reference_id')->filter()->unique();

        echo "┌" . str_repeat("─", 60) . "┐\n";
        echo sprintf("│ %-40s %17s │\n", "Payments to delete:", $activePaymentCount);
        echo sprintf("│ %-40s %17s │\n", "Total payment amount:", "Rs. " . number_format($totalPaymentAmount, 2));
        echo sprintf("│ %-40s %17s │\n", "Ledger entries to reverse:", $paymentLedgerCount);
        echo sprintf("│ %-40s %17s │\n", "Sales to update:", $affectedSaleIds->count());
        echo "└" . str_repeat("─", 60) . "┘\n";

        if ($affectedSaleIds->count() > 0) {
            echo "\nAffected Sale IDs: " . $affectedSaleIds->implode(', ') . "\n";
        }
    }

    /**
     * Execute all fixes
     */
    private function executeFixes()
    {
        echo "\n";
        echo "Starting cleanup process...\n";
        echo str_repeat("-", 60) . "\n\n";

        try {
            DB::beginTransaction();

            $results = [
                'payments_deleted' => 0,
                'ledgers_reversed' => 0,
                'sales_updated' => 0,
                'total_amount' => 0,
            ];

            // Step 1: Delete all payments and reverse ledgers
            echo "STEP 1: Deleting payments and reversing ledgers...\n";
            foreach ($this->payments as $payment) {
                echo "  → Payment #{$payment->id} ({$payment->reference_no}): Rs. " . number_format($payment->amount, 2) . "\n";

                // Use UnifiedLedgerService to reverse ledger entries
                $ledgerResult = $this->unifiedLedgerService->deletePayment(
                    $payment,
                    'Bulk cleanup via CustomerPaymentCleanup script',
                    null
                );

                if ($ledgerResult) {
                    $results['ledgers_reversed']++;
                    echo "    ✅ Ledger entry reversed\n";
                }

                // Mark payment as deleted
                $payment->update([
                    'status' => 'deleted',
                    'notes' => ($payment->notes ?? '') . ' | [CLEANUP: ' . now()->format('Y-m-d H:i:s') . ']'
                ]);

                $results['payments_deleted']++;
                $results['total_amount'] += $payment->amount;
                echo "    ✅ Payment marked as deleted\n";
            }

            // Step 2: Cleanup any remaining active payment ledger entries
            echo "\nSTEP 2: Cleaning up remaining payment ledger entries...\n";
            $remainingPaymentLedgers = Ledger::where('contact_id', $this->customerId)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'payments')
                ->where('status', 'active')
                ->get();

            if ($remainingPaymentLedgers->count() > 0) {
                foreach ($remainingPaymentLedgers as $ledger) {
                    $ledger->update([
                        'status' => 'reversed',
                        'notes' => $ledger->notes . ' [CLEANUP REVERSED: ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);
                    $results['ledgers_reversed']++;
                    echo "  → Ledger #{$ledger->id} ({$ledger->reference_no}) reversed\n";
                }
            } else {
                echo "  ✅ No remaining payment ledgers to clean\n";
            }

            // Step 3: Update all sales
            echo "\nSTEP 3: Updating sales totals...\n";
            foreach ($this->sales as $sale) {
                $oldPaid = $sale->total_paid;
                $oldStatus = $sale->payment_status;

                // Calculate new total from active payments
                $totalPaid = Payment::where('reference_id', $sale->id)
                    ->where('payment_type', 'sale')
                    ->where('status', 'active')
                    ->sum('amount');

                // Update sale
                $sale->total_paid = $totalPaid;
                $sale->save();
                $sale->refresh();

                // Update payment status
                if ($sale->total_due <= 0) {
                    $sale->payment_status = 'Paid';
                } elseif ($sale->total_paid > 0) {
                    $sale->payment_status = 'Partial';
                } else {
                    $sale->payment_status = 'Due';
                }
                $sale->save();

                $results['sales_updated']++;
                echo "  → Sale #{$sale->id} ({$sale->invoice_no}): ";
                echo "Paid: " . number_format($oldPaid, 2) . " → " . number_format($sale->total_paid, 2);
                echo ", Status: {$oldStatus} → {$sale->payment_status}\n";
            }

            DB::commit();

            // Show final results
            $this->showFinalResults($results);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            echo "\n";
            echo "❌ ERROR OCCURRED!\n";
            echo str_repeat("=", 60) . "\n";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            echo "\n⚠️ All changes have been rolled back.\n";

            return false;
        }
    }

    /**
     * Show final results
     */
    private function showFinalResults($results)
    {
        $this->printSection("✅ CLEANUP COMPLETED SUCCESSFULLY");

        echo "┌" . str_repeat("─", 50) . "┐\n";
        echo sprintf("│ %-35s %12s │\n", "Payments deleted:", $results['payments_deleted']);
        echo sprintf("│ %-35s %12s │\n", "Ledger entries reversed:", $results['ledgers_reversed']);
        echo sprintf("│ %-35s %12s │\n", "Sales updated:", $results['sales_updated']);
        echo sprintf("│ %-35s %12s │\n", "Total amount:", "Rs. " . number_format($results['total_amount'], 2));
        echo "└" . str_repeat("─", 50) . "┘\n";

        // Verify final state
        echo "\n";
        $this->verifyFinalState();
    }

    /**
     * Verify final state
     */
    private function verifyFinalState()
    {
        echo "🔍 VERIFYING FINAL STATE:\n";
        echo str_repeat("-", 40) . "\n";

        // Check payments
        $remainingPayments = Payment::where('customer_id', $this->customerId)
            ->where('status', 'active')
            ->count();
        echo "Remaining active payments: {$remainingPayments}\n";

        // Check ledgers
        $activePaymentLedgers = Ledger::where('contact_id', $this->customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'payments')
            ->where('status', 'active')
            ->count();
        echo "Remaining active payment ledgers: {$activePaymentLedgers}\n";

        // Check sales
        $salesWithPayments = Sale::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->where('total_paid', '>', 0)
            ->count();
        echo "Sales with payments > 0: {$salesWithPayments}\n";

        // Final verdict
        if ($remainingPayments == 0 && $activePaymentLedgers == 0 && $salesWithPayments == 0) {
            echo "\n✅ All checks passed! Customer {$this->customerId} has been fully cleaned.\n";
        } else {
            echo "\n⚠️ Some items may need manual review.\n";
        }

        // Show updated sales table
        echo "\n📋 UPDATED SALES TABLE:\n";
        $updatedSales = Sale::withoutGlobalScopes()
            ->where('customer_id', $this->customerId)
            ->orderBy('id')
            ->get();

        echo "┌" . str_repeat("─", 95) . "┐\n";
        echo sprintf("│ %-5s │ %-12s │ %14s │ %14s │ %14s │ %-10s │\n",
            "ID", "Invoice", "Final Total", "Paid", "Due", "Status");
        echo "├" . str_repeat("─", 95) . "┤\n";

        foreach ($updatedSales as $sale) {
            echo sprintf("│ %-5s │ %-12s │ %14s │ %14s │ %14s │ %-10s │\n",
                $sale->id,
                $sale->invoice_no ?? '-',
                number_format($sale->final_total, 2),
                number_format($sale->total_paid, 2),
                number_format($sale->total_due, 2),
                $sale->payment_status
            );
        }
        echo "└" . str_repeat("─", 95) . "┘\n";
    }

    /**
     * Print header
     */
    private function printHeader()
    {
        echo "\n";
        echo "╔" . str_repeat("═", 70) . "╗\n";
        echo "║" . str_pad("CUSTOMER PAYMENT CLEANUP SCRIPT", 70, " ", STR_PAD_BOTH) . "║\n";
        echo "╠" . str_repeat("═", 70) . "╣\n";
        echo "║" . str_pad("Customer ID: {$this->customerId}", 70, " ", STR_PAD_BOTH) . "║\n";
        echo "║" . str_pad("Mode: " . ($this->executeMode ? "⚠️ EXECUTE (Changes will be made)" : "🔍 ANALYZE ONLY"), 70, " ", STR_PAD_BOTH) . "║\n";
        echo "║" . str_pad("Date: " . date('Y-m-d H:i:s'), 70, " ", STR_PAD_BOTH) . "║\n";
        echo "╚" . str_repeat("═", 70) . "╝\n\n";
    }

    /**
     * Print section header
     */
    private function printSection($title)
    {
        echo "\n";
        echo "┏" . str_repeat("━", 68) . "┓\n";
        echo "┃ " . str_pad($title, 66) . " ┃\n";
        echo "┗" . str_repeat("━", 68) . "┛\n\n";
    }
}

// =============================================================================
// MAIN EXECUTION
// =============================================================================

// Parse command line arguments
$customerId = $argv[1] ?? null;
$executeMode = in_array('--execute', $argv);

if (!$customerId) {
    echo "\n";
    echo "Usage: php tests/CustomerPaymentCleanup.php [customer_id] [--execute]\n\n";
    echo "Examples:\n";
    echo "  php tests/CustomerPaymentCleanup.php 852           # Analyze only\n";
    echo "  php tests/CustomerPaymentCleanup.php 852 --execute # Analyze + Fix\n\n";
    exit(1);
}

// Run the cleanup
$cleanup = new CustomerPaymentCleanup($customerId, $executeMode);
$result = $cleanup->run();

exit($result ? 0 : 1);
