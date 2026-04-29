<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Ledger;
use App\Helpers\BalanceHelper;
use Carbon\Carbon;

class AnalyzeCustomer852 extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'debug:customer-852';

    /**
     * The console command description.
     */
    protected $description = 'Analyze customer 852 ledger and sales data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = 852;

        $this->line("\n╔════════════════════════════════════════════════════════════════════════╗");
        $this->line("║                   CUSTOMER #852 - DETAILED ANALYSIS                     ║");
        $this->line("╚════════════════════════════════════════════════════════════════════════╝\n");

        // 1. Get customer basic info - WITHOUT GLOBAL SCOPE
        $customer = Customer::withoutLocationScope()->find($customerId);
        if (!$customer) {
            $this->error("❌ Customer ID 852 not found!");
            return 1;
        }

        $this->info("👤 CUSTOMER INFO:");
        $this->line("   Name: {$customer->first_name} {$customer->last_name}");
        $this->line("   Mobile: {$customer->mobile_no}");
        $this->line("   Opening Balance: Rs. " . number_format($customer->opening_balance, 2) . "\n");

        // 2. Get ALL ledger entries for this customer
        $ledgerEntries = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('transaction_date', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $this->info("📊 LEDGER ENTRIES (Total: " . $ledgerEntries->count() . ")");
        $this->line("─────────────────────────────────────────────────────────────────────────");
        $this->line(sprintf("%-12s | %-22s | %-10s | %-10s | %-8s | %s", "Date", "Type", "Debit", "Credit", "Status", "Ref"));
        $this->line("─────────────────────────────────────────────────────────────────────────");

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($ledgerEntries as $entry) {
            if ($entry->status === 'active') {
                $totalDebit += $entry->debit;
                $totalCredit += $entry->credit;
            }

            $statusIcon = $entry->status === 'active' ? '✓' : '✗';
            $this->line(sprintf(
                "%s | %-22s | %10.2f | %10.2f | %-8s | %s",
                $entry->transaction_date->format('Y-m-d'),
                substr($entry->transaction_type, 0, 22),
                $entry->debit,
                $entry->credit,
                $statusIcon,
                substr($entry->reference_no ?? 'N/A', 0, 15)
            ));
        }

        $this->line("─────────────────────────────────────────────────────────────────────────");
        $this->line(sprintf("ACTIVE TOTALS: Debit: %10.2f | Credit: %10.2f", $totalDebit, $totalCredit));

        $ledgerBalance = $totalDebit - $totalCredit;
        $this->info("\n🎯 LEDGER BALANCE: Rs. " . number_format($ledgerBalance, 2));
        if ($ledgerBalance > 0) {
            $this->line("   (Customer OWES this much)\n");
        } else {
            $this->line("   (Customer has ADVANCE/CREDIT of Rs. " . number_format(abs($ledgerBalance), 2) . ")\n");
        }

        // 3. Get Sales data for this customer
        $sales = Sale::withoutLocationScope()->where('customer_id', $customerId)
            ->whereIn('status', ['final', 'suspend'])
            ->orderBy('sales_date', 'ASC')
            ->get(['id', 'invoice_no', 'sales_date', 'final_total', 'total_paid', 'total_due', 'payment_status']);

        $this->info("📋 SALES INVOICES (Total: " . $sales->count() . ")");
        $this->line("─────────────────────────────────────────────────────────────────────────");
        $this->line(sprintf("%-15s | %-12s | %-12s | %-12s | %-12s | %s", "Invoice", "Total", "Paid", "Due", "Status", "Date"));
        $this->line("─────────────────────────────────────────────────────────────────────────");

        $totalSalesAmount = 0;
        $totalSalesPaid = 0;
        $totalSalesDue = 0;

        foreach ($sales as $sale) {
            $totalSalesAmount += $sale->final_total;
            $totalSalesPaid += $sale->total_paid;
            $totalSalesDue += $sale->total_due;

            $this->line(sprintf(
                "%-15s | %12.2f | %12.2f | %12.2f | %-12s | %s",
                $sale->invoice_no,
                $sale->final_total,
                $sale->total_paid,
                $sale->total_due,
                $sale->payment_status,
                Carbon::parse($sale->sales_date)->format('Y-m-d')
            ));
        }

        $this->line("─────────────────────────────────────────────────────────────────────────");
        $this->line(sprintf("TOTALS: Amount: %12.2f | Paid: %12.2f | Due: %12.2f\n", $totalSalesAmount, $totalSalesPaid, $totalSalesDue));

        // 4. Calculate advance
        $advanceAmount = BalanceHelper::getCustomerAdvance($customerId);

        $this->info("╔════════════════════════════════════════════════════════════════════════╗");
        $this->info("║                            SUMMARY ANALYSIS                            ║");
        $this->info("╚════════════════════════════════════════════════════════════════════════╝\n");

        $this->info("📊 KEY NUMBERS:");
        $this->line("   Ledger Balance (SUM debit - credit):        Rs. " . number_format($ledgerBalance, 2));
        $this->line("   Sales Total Due (SUM invoice due):          Rs. " . number_format($totalSalesDue, 2));
        $this->line("   Customer Advance (when credit > debit):     Rs. " . number_format($advanceAmount, 2) . "\n");

        // 5. Calculate the gap
        $gap = $totalSalesDue - max(0, $ledgerBalance);
        $this->info("📈 RECONCILIATION:");
        $this->line("   Sales Due vs Ledger Due Gap:                Rs. " . number_format(abs($gap), 2));
        if ($gap > 0) {
            $this->line("   → Ledger balance is LESS than Sales Due - means some credit is unallocated\n");
        } elseif ($gap < 0) {
            $this->line("   → Ledger balance is MORE than Sales Due - accounting issue?\n");
        } else {
            $this->line("   → PERFECTLY ALIGNED - no gap\n");
        }

        // 6. Available credit calculation
        $ledgerBalanceCalculated = max(0, $ledgerBalance);
        $saleDueCalculated = $totalSalesDue;
        $availableCredit = max(0.0, $saleDueCalculated - $ledgerBalanceCalculated) + max(0.0, abs(min(0.0, $ledgerBalance)));

        $this->info("💚 AVAILABLE CREDIT FOR APPLICATION:");
        $this->line("   Formula: max(0, SalesDue - LedgerBalance) + max(0, |min(0, LedgerBalance)|)");
        $this->line("   = max(0, " . number_format($totalSalesDue, 2) . " - " . number_format($ledgerBalanceCalculated, 2) . ") + max(0, |" . number_format(min(0.0, $ledgerBalance), 2) . "|)");
        $this->line("   Available Credit to Apply: Rs. " . number_format($availableCredit, 2) . "\n");

        $this->info("✅ THIS IS THE AMOUNT SHOWN IN 'Apply credit' OPTION\n");

        return 0;
    }
}
