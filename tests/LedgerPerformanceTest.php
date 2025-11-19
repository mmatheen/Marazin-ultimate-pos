<?php

use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Illuminate\Support\Facades\DB;

/**
 * Performance Test Demonstration for SQL Window Functions
 * 
 * This script demonstrates the performance improvements achieved by using
 * SQL window functions instead of PHP loops for balance calculations.
 */

class LedgerPerformanceTest
{
    private $ledgerService;

    public function __construct()
    {
        $this->ledgerService = new UnifiedLedgerService();
    }

    /**
     * Test single customer balance calculation
     */
    public function testSingleCustomerBalance($customerId)
    {
        echo "=== Single Customer Balance Test ===\n";
        
        $start = microtime(true);
        $balance = Ledger::calculateBalance($customerId, 'customer');
        $executionTime = (microtime(true) - $start) * 1000; // Convert to milliseconds
        
        echo "Customer ID: {$customerId}\n";
        echo "Current Balance: {$balance}\n";
        echo "Execution Time: {$executionTime}ms\n\n";
        
        return $balance;
    }

    /**
     * Test bulk balance calculation for multiple customers
     */
    public function testBulkCustomerBalances($customerIds)
    {
        echo "=== Bulk Customer Balances Test ===\n";
        echo "Testing " . count($customerIds) . " customers\n";
        
        $start = microtime(true);
        $balances = Ledger::getBulkBalances($customerIds, 'customer');
        $executionTime = (microtime(true) - $start) * 1000;
        
        echo "Execution Time: {$executionTime}ms\n";
        echo "Average per customer: " . ($executionTime / count($customerIds)) . "ms\n";
        
        // Display first 5 results
        $count = 0;
        foreach ($balances as $contactId => $balance) {
            if ($count >= 5) break;
            echo "Customer {$contactId}: {$balance->balance}\n";
            $count++;
        }
        
        if (count($balances) > 5) {
            echo "... and " . (count($balances) - 5) . " more\n";
        }
        echo "\n";
        
        return $balances;
    }

    /**
     * Test customer statement with running balance
     */
    public function testCustomerStatement($customerId, $limit = 10)
    {
        echo "=== Customer Statement with Running Balance Test ===\n";
        
        $start = microtime(true);
        $statement = Ledger::getRunningBalanceHistory($customerId, 'customer', $limit);
        $executionTime = (microtime(true) - $start) * 1000;
        
        echo "Customer ID: {$customerId}\n";
        echo "Execution Time: {$executionTime}ms\n";
        echo "Records Retrieved: " . $statement->count() . "\n";
        
        // Display sample records
        echo "Sample Records:\n";
        echo str_pad('Date', 12) . str_pad('Type', 15) . str_pad('Debit', 10) . str_pad('Credit', 10) . str_pad('Balance', 12) . "\n";
        echo str_repeat('-', 60) . "\n";
        
        foreach ($statement->take(5) as $record) {
            echo str_pad(substr($record->transaction_date, 0, 10), 12) . 
                 str_pad($record->transaction_type, 15) . 
                 str_pad(number_format($record->debit, 2), 10) . 
                 str_pad(number_format($record->credit, 2), 10) . 
                 str_pad(number_format($record->running_balance, 2), 12) . "\n";
        }
        echo "\n";
        
        return $statement;
    }

    /**
     * Test balance summary report
     */
    public function testBalanceSummary()
    {
        echo "=== Balance Summary Test ===\n";
        
        $start = microtime(true);
        $summary = Ledger::getBalanceSummary();
        $executionTime = (microtime(true) - $start) * 1000;
        
        echo "Execution Time: {$executionTime}ms\n";
        echo "Summary Data:\n";
        echo str_pad('Type', 12) . str_pad('Contacts', 10) . str_pad('Total Balance', 15) . str_pad('Positive', 12) . str_pad('Negative', 12) . "\n";
        echo str_repeat('-', 62) . "\n";
        
        foreach ($summary as $row) {
            echo str_pad($row->contact_type, 12) . 
                 str_pad($row->total_contacts, 10) . 
                 str_pad(number_format($row->total_balance, 2), 15) . 
                 str_pad(number_format($row->positive_balance, 2), 12) . 
                 str_pad(number_format($row->negative_balance, 2), 12) . "\n";
        }
        echo "\n";
        
        return $summary;
    }

    /**
     * Performance comparison: Old vs New method
     */
    public function performanceComparison($customerIds)
    {
        echo "=== Performance Comparison ===\n";
        echo "Comparing old PHP loop method vs new SQL window function method\n\n";
        
        // Test new method (SQL window functions)
        echo "New Method (SQL Window Functions):\n";
        $start = microtime(true);
        $newResults = Ledger::getBulkBalances($customerIds, 'customer');
        $newTime = (microtime(true) - $start) * 1000;
        echo "Time: {$newTime}ms\n";
        echo "Records: " . count($newResults) . "\n\n";
        
        // Simulate old method performance (for reference)
        echo "Old Method (PHP Loop - Simulated):\n";
        $start = microtime(true);
        $oldResults = [];
        foreach ($customerIds as $customerId) {
            // This would be the old way - individual queries
            $balance = DB::selectOne("SELECT SUM(debit - credit) as balance FROM ledgers WHERE contact_id = ? AND contact_type = 'customer' AND status = 'active'", [$customerId]);
            $oldResults[$customerId] = $balance->balance ?? 0;
        }
        $oldTime = (microtime(true) - $start) * 1000;
        echo "Time: {$oldTime}ms\n";
        echo "Records: " . count($oldResults) . "\n\n";
        
        $improvement = $oldTime > 0 ? round(($oldTime - $newTime) / $oldTime * 100, 2) : 0;
        echo "Performance Improvement: {$improvement}%\n";
        echo "Speed Factor: " . round($oldTime / $newTime, 2) . "x faster\n\n";
        
        return [
            'new_time' => $newTime,
            'old_time' => $oldTime,
            'improvement_percent' => $improvement,
            'speed_factor' => $oldTime / $newTime
        ];
    }

    /**
     * Run comprehensive performance tests
     */
    public function runFullTest()
    {
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║                    LEDGER PERFORMANCE TEST                       ║\n";
        echo "║                  SQL Window Functions vs PHP Loops              ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n\n";
        
        // Get sample customer IDs
        $customerIds = DB::table('customers')->pluck('id')->take(20)->toArray();
        
        if (empty($customerIds)) {
            echo "No customers found in database. Please add some test data first.\n";
            return;
        }
        
        echo "Testing with " . count($customerIds) . " customers\n\n";
        
        // Test 1: Single customer balance
        $this->testSingleCustomerBalance($customerIds[0]);
        
        // Test 2: Bulk customer balances
        $this->testBulkCustomerBalances($customerIds);
        
        // Test 3: Customer statement with running balance
        $this->testCustomerStatement($customerIds[0]);
        
        // Test 4: Balance summary
        $this->testBalanceSummary();
        
        // Test 5: Performance comparison
        $this->performanceComparison(array_slice($customerIds, 0, 10));
        
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║                         TEST COMPLETED                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
    }
}

// Usage example:
// $test = new LedgerPerformanceTest();
// $test->runFullTest();