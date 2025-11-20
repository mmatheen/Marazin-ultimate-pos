<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * 🎯 CENTRALIZED BALANCE CALCULATION - SINGLE SOURCE OF TRUTH
 * 
 * This class provides the ONLY methods for balance calculations.
 * All other services should use these methods to ensure consistency.
 */
class CentralizedBalanceHelper
{
    /**
     * 🔧 UNIFIED REVERSAL DETECTION LOGIC
     * 
     * This is the single source of truth for determining if an entry should be excluded
     * from balance calculations. Used by ALL balance-related methods.
     */
    public static function isReversalEntry($ledgerEntry): bool
    {
        // Check status first
        if ($ledgerEntry->status !== 'active') {
            return true;
        }
        
        // Check reference number patterns
        $refLower = strtolower($ledgerEntry->reference_no ?? '');
        if (strpos($refLower, 'rev') !== false ||
            strpos($refLower, 'edit') !== false ||
            strpos($refLower, 'reversal') !== false) {
            return true;
        }
        
        // Check notes patterns
        $notesLower = strtolower($ledgerEntry->notes ?? '');
        if (strpos($notesLower, 'reversal') !== false ||
            strpos($notesLower, '[reversed') !== false ||
            strpos($notesLower, 'edit') !== false ||
            strpos($notesLower, 'correction') !== false ||
            strpos($notesLower, 'removed') !== false ||
            strpos($notesLower, 'customer changed') !== false ||
            strpos($notesLower, 'added to customer') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 💰 CENTRALIZED CUSTOMER BALANCE CALCULATION
     * 
     * This replaces the old BalanceHelper::getCustomerBalance() method
     * with improved reversal detection.
     */
    public static function getCustomerBalance($customerId): float
    {
        if ($customerId == 1) {
            return 0.0; // Walk-in customer always 0
        }
        
        // Get all ledger entries for this customer
        $entries = DB::select('
            SELECT debit, credit, status, reference_no, notes
            FROM ledgers 
            WHERE contact_id = ? 
                AND contact_type = "customer"
            ORDER BY created_at ASC, id ASC
        ', [$customerId]);
        
        $balance = 0.0;
        
        foreach ($entries as $entry) {
            if (!self::isReversalEntry($entry)) {
                $balance += ($entry->debit - $entry->credit);
            }
        }
        
        return $balance;
    }
    
    /**
     * 📊 CENTRALIZED RUNNING BALANCE CALCULATION FOR AUDIT TRAIL
     * 
     * Returns array of entries with calculated running balances
     */
    public static function calculateRunningBalances($customerId): array
    {
        $entries = DB::select('
            SELECT *
            FROM ledgers 
            WHERE contact_id = ? 
                AND contact_type = "customer"
            ORDER BY created_at ASC, id ASC
        ', [$customerId]);
        
        $runningBalance = 0.0;
        $results = [];
        
        foreach ($entries as $entry) {
            // Only include non-reversal entries in running balance calculation
            if (!self::isReversalEntry($entry)) {
                $runningBalance += ($entry->debit - $entry->credit);
            }
            
            // Store the entry with its running balance
            $entryArray = (array) $entry;
            $entryArray['calculated_running_balance'] = $runningBalance;
            $entryArray['is_reversal'] = self::isReversalEntry($entry);
            $results[] = $entryArray;
        }
        
        return $results;
    }
    
    /**
     * 🧪 VALIDATION METHOD - CHECK ALL CUSTOMERS FOR CONSISTENCY
     */
    public static function validateAllCustomerBalances(): array
    {
        $issues = [];
        
        // Get all customers with ledger entries
        $customerIds = DB::select('
            SELECT DISTINCT contact_id 
            FROM ledgers 
            WHERE contact_type = "customer"
        ');
        
        foreach ($customerIds as $customer) {
            $customerId = $customer->contact_id;
            
            // Calculate using our centralized method
            $centralizedBalance = self::getCustomerBalance($customerId);
            
            // Compare with old BalanceHelper
            $oldBalance = \App\Helpers\BalanceHelper::getCustomerBalance($customerId);
            
            if (abs($centralizedBalance - $oldBalance) > 0.01) {
                $issues[] = [
                    'customer_id' => $customerId,
                    'centralized_balance' => $centralizedBalance,
                    'old_balance' => $oldBalance,
                    'difference' => $centralizedBalance - $oldBalance
                ];
            }
        }
        
        return $issues;
    }
}

// Test function to demonstrate the fix
function demonstrateFix() {
    echo "=== CENTRALIZED BALANCE FIX DEMONSTRATION ===\n\n";
    
    $customerId = 3; // Ahshan
    
    echo "Customer ID: $customerId\n";
    echo "Old BalanceHelper result: " . \App\Helpers\BalanceHelper::getCustomerBalance($customerId) . "\n";
    echo "New Centralized result: " . CentralizedBalanceHelper::getCustomerBalance($customerId) . "\n\n";
    
    // Show running balance progression
    $runningBalances = CentralizedBalanceHelper::calculateRunningBalances($customerId);
    echo "Running Balance Progression:\n";
    foreach ($runningBalances as $i => $entry) {
        $marker = $entry['is_reversal'] ? '🔄' : '✅';
        echo sprintf("%s %d. ID:%d | %s | D:%8.2f C:%8.2f | Running:%8.2f\n",
            $marker, $i+1, $entry['id'], $entry['transaction_type'],
            $entry['debit'], $entry['credit'], $entry['calculated_running_balance']);
    }
    
    echo "\n✅ This centralized approach ensures ALL balance calculations are consistent!\n";
}

// Run demonstration if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    require '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    demonstrateFix();
}