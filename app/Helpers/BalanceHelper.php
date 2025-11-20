<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * ===================================================================
 * 🎯 BALANCE CALCULATION - SINGLE SOURCE OF TRUTH
 * ===================================================================
 * 
 * 🚨 WHEN DEBUGGING BALANCE ISSUES, LOOK HERE FIRST!
 * 
 * This is the ONLY place for all balance calculations:
 * ✅ Customer balances
 * ✅ Supplier balances  
 * ✅ All ledger math
 * 
 * 🔍 For Debugging Balance Problems:
 * 1. Call: BalanceHelper::debugCustomerBalance($customerId)
 * 2. Check the detailed breakdown
 * 3. All balance logic is in this one file!
 * 
 * ⚠️  DO NOT create balance methods in other files!
 * ===================================================================
 */
class BalanceHelper
{
   
    public static function getCustomerBalance($customerId)
    {
        if ($customerId == 1) {
            return 0.0; // Walk-in customer always 0
        }
        
        // Get customer's current opening balance (this reflects final corrected amount)
        $customer = \App\Models\Customer::find($customerId);
        if (!$customer) {
            return 0.0;
        }
        
        // Check if there are opening balance corrections
        $hasOpeningBalanceCorrections = \App\Models\Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->where('transaction_type', 'opening_balance_adjustment')
            ->exists();
            
        if ($hasOpeningBalanceCorrections) {
            // When opening balance has been corrected, use smart calculation
            // Use customer's current opening balance + non-opening-balance transactions
            $nonOpeningBalanceSum = \App\Models\Ledger::where('contact_id', $customerId)
                ->where('contact_type', 'customer')
                ->where('status', 'active')
                ->where('transaction_type', '!=', 'opening_balance')
                ->where('transaction_type', '!=', 'opening_balance_adjustment')
                ->sum(DB::raw('debit - credit'));
                
            return (float)($customer->opening_balance + $nonOpeningBalanceSum);
        } else {
            // Normal calculation when no opening balance corrections
            $result = DB::selectOne("
                SELECT 
                    COALESCE(SUM(debit), 0) as total_debits,
                    COALESCE(SUM(credit), 0) as total_credits,
                    COALESCE(SUM(debit) - SUM(credit), 0) as balance
                FROM ledgers 
                WHERE contact_id = ? 
                    AND contact_type = 'customer'
                    AND status = 'active'
            ", [$customerId]);
            
            return $result ? (float) $result->balance : 0.0;
        }
    }
    
    /**
     * ===================================================================
     * 🏭 SUPPLIER BALANCE METHOD
     * ===================================================================
     * 
     * Logic for suppliers (opposite of customers):
     * - Credits (purchases) = We owe supplier
     * - Debits (payments) = We paid supplier
     * - Balance = Credits - Debits
     */
    public static function getSupplierBalance($supplierId)
    {
        $result = DB::selectOne("
            SELECT 
                COALESCE(SUM(credit), 0) as total_credits,
                COALESCE(SUM(debit), 0) as total_debits,
                COALESCE(SUM(credit) - SUM(debit), 0) as balance
            FROM ledgers 
            WHERE contact_id = ? 
                AND contact_type = 'supplier'
                AND status = 'active'
        ", [$supplierId]);
        
        return $result ? (float) $result->balance : 0.0;
    }
    
    /**
     * ===================================================================
     * 🔍 DEBUGGING HELPER - USE THIS WHEN BALANCE IS WRONG!
     * ===================================================================
     * 
     * Usage: BalanceHelper::debugCustomerBalance($customerId);
     * This will show you EXACTLY what's happening with the balance
     */
    public static function debugCustomerBalance($customerId)
    {
        echo "\n=== 🔍 DEBUGGING CUSTOMER BALANCE (ID: {$customerId}) ===\n";
        
        // Get all ledger entries for this customer
        $entries = DB::select("
            SELECT 
                id, transaction_date, transaction_type, 
                debit, credit, status, reference_no, notes,
                created_at
            FROM ledgers 
            WHERE contact_id = ? 
                AND contact_type = 'customer'
            ORDER BY transaction_date ASC, id ASC
        ", [$customerId]);
        
        // Separate active and reversed entries
        $activeEntries = array_filter($entries, fn($e) => $e->status === 'active');
        $reversedEntries = array_filter($entries, fn($e) => $e->status === 'reversed');
        
        // Calculate totals
        $totalDebits = array_sum(array_map(fn($e) => $e->debit, $activeEntries));
        $totalCredits = array_sum(array_map(fn($e) => $e->credit, $activeEntries));
        $finalBalance = $totalDebits - $totalCredits;
        
        // Show summary
        echo "📊 SUMMARY:\n";
        echo "Active Entries: " . count($activeEntries) . "\n";
        echo "Reversed Entries: " . count($reversedEntries) . "\n";
        echo "Total Debits (Customer Owes): {$totalDebits}\n";
        echo "Total Credits (Customer Paid): {$totalCredits}\n";
        echo "🎯 FINAL BALANCE: {$finalBalance}\n\n";
        
        // Show all active entries
        echo "📋 ACTIVE ENTRIES (These count towards balance):\n";
        foreach ($activeEntries as $entry) {
            $type = str_pad($entry->transaction_type, 20);
            $debit = str_pad($entry->debit, 10);
            $credit = str_pad($entry->credit, 10);
            echo "{$entry->transaction_date} | {$type} | Debit: {$debit} | Credit: {$credit} | Ref: {$entry->reference_no}\n";
        }
        
        // Show reversed entries if any
        if (!empty($reversedEntries)) {
            echo "\n🚫 REVERSED ENTRIES (These DON'T count):\n";
            foreach ($reversedEntries as $entry) {
                $type = str_pad($entry->transaction_type, 20);
                echo "{$entry->transaction_date} | {$type} | Debit: {$entry->debit} | Credit: {$entry->credit} | ❌ REVERSED\n";
            }
        }
        
        echo "\n===================================\n";
        
        return [
            'customer_id' => $customerId,
            'active_entries' => count($activeEntries),
            'reversed_entries' => count($reversedEntries),
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'final_balance' => $finalBalance,
            'calculation' => "Debits ({$totalDebits}) - Credits ({$totalCredits}) = {$finalBalance}"
        ];
    }
    
    /**
     * ===================================================================
     * 🎯 UTILITY METHODS FOR SPECIFIC USE CASES
     * ===================================================================
     */
    
    /**
     * Get only the amount customer owes (positive balance only)
     * Used by: POS system to show "amount due"
     */
    public static function getCustomerDue($customerId)
    {
        return max(0, self::getCustomerBalance($customerId));
    }
    
    /**
     * Get only customer advance amount (negative balance only)
     */
    public static function getCustomerAdvance($customerId)
    {
        $balance = self::getCustomerBalance($customerId);
        return $balance < 0 ? abs($balance) : 0;
    }
    
    /**
     * Get multiple customer balances at once (for reports)
     */
    public static function getBulkCustomerBalances($customerIds)
    {
        if (empty($customerIds)) {
            return collect();
        }

        $placeholders = str_repeat('?,', count($customerIds) - 1) . '?';
        
        $results = DB::select("
            SELECT 
                contact_id,
                COALESCE(SUM(debit) - SUM(credit), 0) as balance
            FROM ledgers 
            WHERE contact_id IN ({$placeholders})
                AND contact_type = 'customer'
                AND status = 'active'
            GROUP BY contact_id
        ", $customerIds);

        return collect($results)->mapWithKeys(function ($result) {
            return [(int) $result->contact_id => (float) $result->balance];
        });
    }
    
    /**
     * ===================================================================
     * 🔄 BACKWARD COMPATIBILITY METHODS
     * ===================================================================
     * These exist only for old code. NEW CODE should use the methods above!
     */
    
    /**
     * @deprecated Use getCustomerBalance() or getSupplierBalance() instead
     */
    public static function getCurrentBalance($contactId, $contactType)
    {
        if ($contactType === 'customer') {
            return self::getCustomerBalance($contactId);
        } else {
            return self::getSupplierBalance($contactId);
        }
    }
    
    /**
     * @deprecated Use getBulkCustomerBalances() instead
     */
    public static function getBulkBalances($contactIds, $contactType)
    {
        if ($contactType === 'customer') {
            return self::getBulkCustomerBalances($contactIds);
        }
        return collect(); // For suppliers, not implemented yet
    }
}