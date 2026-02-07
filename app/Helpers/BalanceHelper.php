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

        // Simple and correct: Sum all active ledger entries
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
     * Get only customer advance amount (when credits exceed debits)
     * This represents overpayments that can be used for future purchases
     */
    public static function getCustomerAdvance($customerId)
    {
        if ($customerId == 1) {
            return 0.0; // Walk-in customer never has advance
        }

        $result = DB::selectOne("
            SELECT
                COALESCE(SUM(credit), 0) as total_credits,
                COALESCE(SUM(debit), 0) as total_debits,
                COALESCE(SUM(credit) - SUM(debit), 0) as advance
            FROM ledgers
            WHERE contact_id = ?
                AND contact_type = 'customer'
                AND status = 'active'
        ", [$customerId]);

        // Return advance only if credits exceed debits (customer has overpaid)
        return $result && $result->advance > 0 ? (float) $result->advance : 0.0;
    }

    /**
     * Get multiple customer balances at once (for reports)
     * Uses the same simple logic as getCustomerBalance() - just sum all active ledger entries
     */
    public static function getBulkCustomerBalances($customerIds)
    {
        if (empty($customerIds)) {
            return collect();
        }

        // Remove walk-in customer (ID = 1) as they always have 0 balance
        $customerIds = array_values(array_filter($customerIds, fn($id) => $id != 1));

        if (empty($customerIds)) {
            return collect();
        }

        // Simple calculation: Sum all active ledger entries for all customers
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

        $balances = collect();
        foreach ($results as $result) {
            $balances->put((int) $result->contact_id, (float) $result->balance);
        }

        // Ensure all requested customer IDs are in the result (fill missing with 0)
        foreach ($customerIds as $customerId) {
            if (!$balances->has($customerId)) {
                $balances->put($customerId, 0.0);
            }
        }

        return $balances;
    }

    /**
     * Get multiple customer advance amounts at once (negative balances only)
     * Returns collection with customer_id => advance_amount
     */
    public static function getBulkCustomerAdvances($customerIds)
    {
        if (empty($customerIds)) {
            return collect();
        }

        // Remove walk-in customer (ID = 1) as they always have 0 balance
        $customerIds = array_values(array_filter($customerIds, fn($id) => $id != 1));

        if (empty($customerIds)) {
            return collect();
        }

        // Query for customers with negative balances (credits > debits = advance payment)
        $placeholders = str_repeat('?,', count($customerIds) - 1) . '?';
        $results = DB::select("
            SELECT
                contact_id,
                COALESCE(SUM(credit) - SUM(debit), 0) as advance_amount
            FROM ledgers
            WHERE contact_id IN ({$placeholders})
                AND contact_type = 'customer'
                AND status = 'active'
            GROUP BY contact_id
            HAVING SUM(credit) > SUM(debit)
        ", $customerIds);

        $advances = collect();
        foreach ($results as $result) {
            $advances->put((int) $result->contact_id, (float) $result->advance_amount);
        }

        // Ensure all requested customer IDs are in the result (fill missing with 0)
        foreach ($customerIds as $customerId) {
            if (!$advances->has($customerId)) {
                $advances->put($customerId, 0.0);
            }
        }

        return $advances;
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
