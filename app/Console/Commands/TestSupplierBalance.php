<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Helpers\BalanceHelper;

class TestSupplierBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:supplier-balance {supplier_id? : The supplier ID to test (optional - tests all if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test and verify supplier balance calculations including purchases, payments, returns, and opening balance';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $supplierId = $this->argument('supplier_id');

        $this->info('');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('🧪 SUPPLIER BALANCE VERIFICATION TEST');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->info('');

        if ($supplierId) {
            $result = $this->testSupplier($supplierId);
            return $result['passed'] ? 0 : 1;
        } else {
            // Test all suppliers with transactions (get from ledger since suppliers table might not have all records)
            $supplierIds = Ledger::where('contact_type', 'supplier')
                ->where('status', 'active')
                ->distinct()
                ->pluck('contact_id')
                ->sort()
                ->values();

            if ($supplierIds->isEmpty()) {
                $this->error('❌ No suppliers found with ledger transactions.');
                return 1;
            }

            $this->info("Found {$supplierIds->count()} suppliers with transactions.");
            $this->info("Testing all...\n");

            $results = [];
            foreach ($supplierIds as $supplierId) {
                $result = $this->testSupplier($supplierId, false);
                $results[] = $result;
                $this->line("\n" . str_repeat('─', 63) . "\n");
            }

            // Summary
            $this->info('');
            $this->line('═══════════════════════════════════════════════════════════════');
            
            $passed = collect($results)->where('passed', true)->count();
            $failed = collect($results)->where('passed', false)->count();
            
            if ($failed === 0) {
                $this->info("✅ ALL {$passed} SUPPLIERS PASSED - Balance calculations are correct!");
            } else {
                $this->error("❌ {$failed} SUPPLIERS FAILED - {$passed} passed");
                $this->line("\nFailed suppliers:");
                foreach ($results as $result) {
                    if (!$result['passed']) {
                        $this->line("  • ID {$result['supplier_id']}: {$result['supplier_name']}");
                    }
                }
            }
            
            $this->line('═══════════════════════════════════════════════════════════════');
            $this->info('');

            return $failed > 0 ? 1 : 0;
        }
    }

    /**
     * Test a single supplier's balance calculation
     */
    protected function testSupplier($supplierId, $showHeader = true)
    {
        if ($showHeader) {
            $this->info("Testing Supplier ID: {$supplierId}\n");
        }

        // Get supplier details (may not exist in supplier table if deleted)
        $supplier = Supplier::find($supplierId);
        $supplierName = $supplier ? $supplier->name : "Supplier ID {$supplierId} (record not found)";
        $openingBalance = $supplier ? $supplier->opening_balance : 0;

        $this->line("📋 Supplier: {$supplierName}");
        $this->line("   Opening Balance: Rs. " . number_format($openingBalance, 2) . "\n");

        // Get all active ledger entries
        $ledgers = Ledger::where('contact_id', $supplierId)
            ->where('contact_type', 'supplier')
            ->where('status', 'active')
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($ledgers->isEmpty()) {
            $this->warn('⚠️  No active ledger entries found for this supplier.');
            return ['passed' => true, 'supplier_id' => $supplierId, 'supplier_name' => $supplierName];
        }

        // Display ledger entries in table format
        $this->info('📊 LEDGER ENTRIES BREAKDOWN:');
        $this->line(str_repeat('─', 63));

        $tableData = [];
        $totalDebits = 0;
        $totalCredits = 0;
        $transactionCounts = [
            'opening_balance' => 0,
            'purchase' => 0,
            'payments' => 0,
            'purchase_return' => 0,
            'other' => 0
        ];

        foreach ($ledgers as $ledger) {
            $debit = (float) $ledger->debit;
            $credit = (float) $ledger->credit;

            $totalDebits += $debit;
            $totalCredits += $credit;

            // Count transaction types
            $type = $ledger->transaction_type;
            if (in_array($type, array_keys($transactionCounts))) {
                $transactionCounts[$type]++;
            } else {
                $transactionCounts['other']++;
            }

            $tableData[] = [
                'date' => $ledger->transaction_date->format('Y-m-d'),
                'type' => substr($type, 0, 20),
                'debit' => $debit > 0 ? number_format($debit, 2) : '-',
                'credit' => $credit > 0 ? number_format($credit, 2) : '-',
            ];
        }

        $this->table(
            ['Date', 'Type', 'Debit', 'Credit'],
            $tableData
        );

        $this->line("TOTALS: Debit: " . number_format($totalDebits, 2) . " | Credit: " . number_format($totalCredits, 2));
        $this->line(str_repeat('─', 63) . "\n");

        // Calculate balance
        $calculatedBalance = $totalCredits - $totalDebits;
        $helperBalance = BalanceHelper::getSupplierBalance($supplierId);

        // Transaction summary
        $this->info('📈 TRANSACTION SUMMARY:');
        $this->line("   Opening Balances:  {$transactionCounts['opening_balance']}");
        $this->line("   Purchases:         {$transactionCounts['purchase']}");
        $this->line("   Payments:          {$transactionCounts['payments']}");
        $this->line("   Purchase Returns:  {$transactionCounts['purchase_return']}");
        if ($transactionCounts['other'] > 0) {
            $this->line("   Other:             {$transactionCounts['other']}");
        }
        $this->info('');

        // Balance calculation
        $this->info('💰 BALANCE CALCULATION:');
        $this->line('   Total CREDITS (we owe):    Rs. ' . number_format($totalCredits, 2));
        $this->line('   Total DEBITS (we paid):    Rs. ' . number_format($totalDebits, 2));
        $this->line('   ────────────────────────────────────');
        $this->line('   Balance = Credits - Debits');
        $this->line('   Balance = ' . number_format($totalCredits, 2) . ' - ' . number_format($totalDebits, 2));
        $this->line('   Balance = Rs. ' . number_format($calculatedBalance, 2) . "\n");

        // Verification
        $this->info('✓ VERIFICATION:');
        $this->line('   Calculated Balance:              Rs. ' . number_format($calculatedBalance, 2));
        $this->line('   BalanceHelper::getSupplierBalance(): Rs. ' . number_format($helperBalance, 2));

        $balanceMatch = abs($calculatedBalance - $helperBalance) < 0.01;

        if ($balanceMatch) {
            $this->info('   ✅ MATCH - Balance calculation is CORRECT!');
        } else {
            $this->error('   ❌ MISMATCH - Balance calculation has ERROR!');
            $this->line('   Difference: Rs. ' . number_format(abs($calculatedBalance - $helperBalance), 2));
        }
        $this->info('');

        // Verify debit/credit logic
        $this->info('🔍 LOGIC VERIFICATION:');
        $logicCorrect = true;
        $issues = [];

        foreach ($ledgers as $ledger) {
            $type = $ledger->transaction_type;
            $debit = (float) $ledger->debit;
            $credit = (float) $ledger->credit;

            // Check opening balance
            if ($type === 'opening_balance' && $credit > 0 && $debit == 0) {
                // Correct
            } elseif ($type === 'opening_balance' && $credit == 0 && $debit > 0) {
                $issues[] = "Opening balance should be CREDIT, but found DEBIT. Entry ID: {$ledger->id}";
                $logicCorrect = false;
            }

            // Check purchase
            if ($type === 'purchase' && $credit > 0 && $debit == 0) {
                // Correct
            } elseif ($type === 'purchase' && $credit == 0 && $debit > 0) {
                $issues[] = "Purchase should be CREDIT, but found DEBIT. Entry ID: {$ledger->id}";
                $logicCorrect = false;
            }

            // Check payment
            if (in_array($type, ['payments', 'payment', 'purchase_payment']) && $debit > 0 && $credit == 0) {
                // Correct
            } elseif (in_array($type, ['payments', 'payment', 'purchase_payment']) && $debit == 0 && $credit > 0) {
                $issues[] = "Payment should be DEBIT, but found CREDIT. Entry ID: {$ledger->id}";
                $logicCorrect = false;
            }

            // Check purchase return
            if ($type === 'purchase_return' && $debit > 0 && $credit == 0) {
                // Correct
            } elseif ($type === 'purchase_return' && $debit == 0 && $credit > 0) {
                $issues[] = "Purchase return should be DEBIT, but found CREDIT. Entry ID: {$ledger->id}";
                $logicCorrect = false;
            }
        }

        if ($logicCorrect) {
            $this->info('   ✅ All debit/credit entries are CORRECT!');
            $this->line('      - Opening balance (we owe): CREDIT ✓');
            $this->line('      - Purchases (we owe): CREDIT ✓');
            $this->line('      - Payments (we paid): DEBIT ✓');
            $this->line('      - Purchase returns (reduce debt): DEBIT ✓');
        } else {
            $this->error('   ❌ Found issues with debit/credit logic:');
            foreach ($issues as $issue) {
                $this->line("      • {$issue}");
            }
        }
        $this->info('');

        // Overall result
        $passed = $balanceMatch && $logicCorrect;

        $this->line('═══════════════════════════════════════════════════════════════');
        if ($passed) {
            $this->info("✅ SUPPLIER {$supplierId} ({$supplierName}): ALL TESTS PASSED");
            $this->line("   Current balance we owe: Rs. " . number_format($helperBalance, 2));
        } else {
            $this->error("❌ SUPPLIER {$supplierId} ({$supplierName}): TESTS FAILED");
            if (!$balanceMatch) {
                $this->line('   ⚠️  Balance calculation mismatch');
            }
            if (!$logicCorrect) {
                $this->line('   ⚠️  Debit/Credit logic errors found');
            }
        }
        $this->line('═══════════════════════════════════════════════════════════════');

        return [
            'passed' => $passed,
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'calculated_balance' => $calculatedBalance,
            'helper_balance' => $helperBalance,
            'balance_match' => $balanceMatch,
            'logic_correct' => $logicCorrect,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'transaction_counts' => $transactionCounts
        ];
    }
}
