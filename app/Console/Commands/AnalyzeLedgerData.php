<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use App\Models\Customer;
use App\Helpers\BalanceHelper;
use Illuminate\Support\Facades\DB;

class AnalyzeLedgerData extends Command
{
    protected $signature = 'ledger:analyze';
    protected $description = 'Analyze ledger data for duplicates and integrity issues';

    public function handle()
    {
        $this->info('🔍 ANALYZING LEDGER DATA...');
        $this->newLine();

        // 1. Basic counts
        $this->checkBasicCounts();
        
        // 2. Check for duplicates
        $this->checkForDuplicates();
        
        // 3. Check status distribution
        $this->checkStatusDistribution();
        
        // 4. Check for data integrity issues
        $this->checkDataIntegrity();
        
        // 5. Sample balance verification
        $this->checkBalanceAccuracy();
        
        $this->newLine();
        $this->info('✅ ANALYSIS COMPLETE!');
    }

    private function checkBasicCounts()
    {
        $this->info('📊 BASIC COUNTS:');
        $this->line('==================');
        
        $totalLedgers = Ledger::count();
        $totalCustomers = Customer::count();
        
        $this->line("Total Ledger Records: {$totalLedgers}");
        $this->line("Total Customers: {$totalCustomers}");
        $this->newLine();
    }

    private function checkForDuplicates()
    {
        $this->info('🔍 CHECKING FOR DUPLICATES:');
        $this->line('============================');
        
        // Check for exact duplicates (same reference, date, contact, amount)
        $exactDuplicates = DB::select("
            SELECT reference_no, transaction_date, contact_id, transaction_type, 
                   debit, credit, COUNT(*) as count
            FROM ledgers 
            GROUP BY reference_no, transaction_date, contact_id, transaction_type, debit, credit
            HAVING COUNT(*) > 1
            ORDER BY count DESC
            LIMIT 20
        ");
        
        if (count($exactDuplicates) > 0) {
            $this->error("❌ Found " . count($exactDuplicates) . " exact duplicate groups:");
            foreach ($exactDuplicates as $dup) {
                $this->line("  • Ref: {$dup->reference_no} | Contact: {$dup->contact_id} | Count: {$dup->count} | Debit: {$dup->debit} | Credit: {$dup->credit}");
            }
        } else {
            $this->info("✅ No exact duplicates found");
        }
        
        // Check for reference number duplicates (same ref with different amounts)
        $refDuplicates = DB::select("
            SELECT reference_no, COUNT(*) as count,
                   MIN(debit) as min_debit, MAX(debit) as max_debit,
                   MIN(credit) as min_credit, MAX(credit) as max_credit
            FROM ledgers 
            WHERE reference_no IS NOT NULL AND reference_no != ''
            GROUP BY reference_no
            HAVING COUNT(*) > 2
            ORDER BY count DESC
            LIMIT 10
        ");
        
        if (count($refDuplicates) > 0) {
            $this->warn("⚠️  Found reference numbers with multiple entries:");
            foreach ($refDuplicates as $ref) {
                $this->line("  • Ref: {$ref->reference_no} | Count: {$ref->count} | Debit Range: {$ref->min_debit}-{$ref->max_debit}");
            }
        } else {
            $this->info("✅ No suspicious reference duplicates found");
        }
        
        $this->newLine();
    }

    private function checkStatusDistribution()
    {
        $this->info('📈 STATUS DISTRIBUTION:');
        $this->line('=======================');
        
        $statusCounts = Ledger::select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->get();
            
        foreach ($statusCounts as $status) {
            $percentage = round(($status->count / Ledger::count()) * 100, 2);
            $this->line("  • {$status->status}: {$status->count} records ({$percentage}%)");
        }
        
        // Check for NULL status (should not exist after migration)
        $nullStatus = Ledger::whereNull('status')->count();
        if ($nullStatus > 0) {
            $this->error("❌ Found {$nullStatus} records with NULL status!");
        } else {
            $this->info("✅ All records have valid status");
        }
        
        $this->newLine();
    }

    private function checkDataIntegrity()
    {
        $this->info('🔧 DATA INTEGRITY CHECKS:');
        $this->line('==========================');
        
        // Check for records with both debit and credit
        $bothDebitCredit = Ledger::where('debit', '>', 0)
            ->where('credit', '>', 0)
            ->count();
            
        if ($bothDebitCredit > 0) {
            $this->warn("⚠️  Found {$bothDebitCredit} records with both debit AND credit");
        } else {
            $this->info("✅ No records with both debit and credit");
        }
        
        // Check for records with neither debit nor credit
        $neitherDebitCredit = Ledger::where('debit', '=', 0)
            ->where('credit', '=', 0)
            ->count();
            
        if ($neitherDebitCredit > 0) {
            $this->warn("⚠️  Found {$neitherDebitCredit} records with zero debit AND credit");
        } else {
            $this->info("✅ All records have either debit or credit");
        }
        
        // Check for missing contact_id
        $missingContactId = Ledger::whereNull('contact_id')->count();
        if ($missingContactId > 0) {
            $this->error("❌ Found {$missingContactId} records with missing contact_id");
        } else {
            $this->info("✅ All records have contact_id");
        }
        
        // Check transaction type distribution
        $transactionTypes = Ledger::select('transaction_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('transaction_type')
            ->orderBy('count', 'desc')
            ->get();
            
        $this->line("\nTransaction Types:");
        foreach ($transactionTypes as $type) {
            $this->line("  • {$type->transaction_type}: {$type->count}");
        }
        
        $this->newLine();
    }

    private function checkBalanceAccuracy()
    {
        $this->info('⚖️  BALANCE ACCURACY CHECK:');
        $this->line('===========================');
        
        // Get a sample of customers for balance verification
        $sampleCustomers = Customer::where('id', '>', 1) // Exclude walk-in
            ->inRandomOrder()
            ->take(5)
            ->get();
            
        $discrepancies = 0;
        
        foreach ($sampleCustomers as $customer) {
            // Calculate balance using BalanceHelper
            $balanceHelperAmount = BalanceHelper::getCustomerBalance($customer->id);
            
            // Calculate balance manually from ledger
            $manualCalculation = Ledger::where('contact_id', $customer->id)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->sum(DB::raw('debit - credit'));
                
            $difference = abs($balanceHelperAmount - $manualCalculation);
            
            if ($difference > 0.01) { // Allow for small rounding differences
                $this->error("❌ Customer {$customer->id} ({$customer->first_name}): BalanceHelper={$balanceHelperAmount}, Manual={$manualCalculation}");
                $discrepancies++;
            } else {
                $this->info("✅ Customer {$customer->id} ({$customer->first_name}): {$balanceHelperAmount} (accurate)");
            }
        }
        
        if ($discrepancies == 0) {
            $this->info("✅ All sample balances are accurate!");
        } else {
            $this->error("❌ Found {$discrepancies} balance discrepancies!");
        }
        
        $this->newLine();
    }
}