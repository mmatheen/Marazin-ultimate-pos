<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

class FixMissingLedgerEntries extends Command
{
    protected $signature = 'ledger:fix-missing 
                            {--check : Only check for missing entries without fixing}
                            {--customer= : Fix only for specific customer ID}';

    protected $description = 'Find and fix missing ledger entries for sales with credit/due amounts';

    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        parent::__construct();
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    public function handle()
    {
        $checkOnly = $this->option('check');
        $customerId = $this->option('customer');

        $this->info('🔍 Scanning for sales with missing ledger entries...');
        $this->newLine();

        // Get all sales that should have ledger entries
        // Use withoutGlobalScopes to bypass LocationScope and get all sales
        $query = Sale::withoutGlobalScopes()
            ->whereNotNull('customer_id')
            ->where('customer_id', '!=', 1) // Exclude Walk-In customer
            ->whereNotIn('status', ['draft', 'quotation']) // Exclude drafts and quotations
            ->with(['customer' => function ($q) {
                $q->withoutGlobalScopes(); // Also bypass scope for customer relation
            }]);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $sales = $query->get();

        $this->info("📊 Found {$sales->count()} sales to check");
        $this->newLine();

        $missingCount = 0;
        $fixedCount = 0;
        $errorCount = 0;

        $bar = $this->output->createProgressBar($sales->count());
        $bar->start();

        foreach ($sales as $sale) {
            $bar->advance();

            // Check if ledger entry exists for this sale
            $referenceNo = $sale->invoice_no ?: 'INV-' . $sale->id;
            
            $ledgerExists = Ledger::where('user_id', $sale->customer_id)
                ->where('contact_type', 'customer')
                ->where('transaction_type', 'sale')
                ->where('reference_no', $referenceNo)
                ->exists();

            if (!$ledgerExists) {
                $missingCount++;

                $customerName = $sale->customer ? $sale->customer->name : 'Unknown';
                
                if (!$checkOnly) {
                    try {
                        // Create the missing ledger entry
                        $this->unifiedLedgerService->recordSale($sale);
                        $fixedCount++;
                        
                        $this->newLine();
                        $this->line("  ✅ Fixed: Sale #{$sale->invoice_no} | Customer: {$customerName} (ID: {$sale->customer_id}) | Amount: " . number_format($sale->final_total, 2));
                    } catch (\Exception $e) {
                        $errorCount++;
                        $this->newLine();
                        $this->error("  ❌ Error fixing Sale #{$sale->invoice_no}: " . $e->getMessage());
                    }
                } else {
                    $this->newLine();
                    $this->warn("  ⚠️  Missing: Sale #{$sale->invoice_no} | Customer: {$customerName} (ID: {$sale->customer_id}) | Amount: " . number_format($sale->final_total, 2));
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('═══════════════════════════════════════');
        $this->info('📋 SUMMARY');
        $this->info('═══════════════════════════════════════');
        $this->line("Total Sales Checked: {$sales->count()}");
        $this->line("Missing Ledger Entries: {$missingCount}");
        
        if (!$checkOnly) {
            $this->info("✅ Fixed Entries: {$fixedCount}");
            if ($errorCount > 0) {
                $this->error("❌ Errors: {$errorCount}");
            }
        } else {
            $this->warn("ℹ️  Run without --check flag to fix these entries");
        }
        
        $this->newLine();

        // Recalculate balances for affected customers
        if ($fixedCount > 0) {
            $this->info('🔄 Recalculating customer balances...');
            
            // Use withoutGlobalScopes to get all customers who had sales
            $affectedCustomers = Sale::withoutGlobalScopes()
                ->whereNotNull('customer_id')
                ->where('customer_id', '!=', 1)
                ->whereNotIn('status', ['draft', 'quotation'])
                ->distinct()
                ->pluck('customer_id');

            foreach ($affectedCustomers as $customerId) {
                Ledger::calculateBalance($customerId, 'customer');
            }
            
            $this->info("✅ Recalculated balances for {$affectedCustomers->count()} customers");
        }

        return 0;
    }
}
