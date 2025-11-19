<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\BalanceHelper;

class BalanceTestAnalyzer {
    
    public static function analyzeCustomerBalance($customerId) {
        echo "=== DETAILED BALANCE ANALYSIS FOR CUSTOMER ID: $customerId ===\n\n";
        
        // Step 1: Get all ledger entries
        $entries = self::getAllLedgerEntries($customerId);
        
        // Step 2: Categorize entries
        $categorized = self::categorizeEntries($entries);
        
        // Step 3: Calculate different scenarios
        self::calculateBalanceScenarios($categorized, $customerId);
        
        // Step 4: Show recommendations
        self::showRecommendations($categorized);
    }
    
    private static function getAllLedgerEntries($customerId) {
        echo "📋 STEP 1: Getting all ledger entries...\n";
        
        $entries = DB::select('
            SELECT id, transaction_date, transaction_type, debit, credit, status, reference_no, notes, created_at
            FROM ledgers 
            WHERE contact_id = ? AND contact_type = "customer" 
            ORDER BY id ASC
        ', [$customerId]);
        
        echo "Total entries found: " . count($entries) . "\n\n";
        return $entries;
    }
    
    private static function categorizeEntries($entries) {
        echo "🔍 STEP 2: Categorizing entries...\n";
        
        $categories = [
            'active_normal' => [],
            'active_reversal' => [],
            'reversed' => [],
            'cancelled' => [],
            'other' => []
        ];
        
        foreach ($entries as $entry) {
            // Check if this looks like a reversal
            $isReversal = self::isReversalEntry($entry);
            
            if ($entry->status === 'active') {
                if ($isReversal) {
                    $categories['active_reversal'][] = $entry;
                } else {
                    $categories['active_normal'][] = $entry;
                }
            } elseif ($entry->status === 'reversed') {
                $categories['reversed'][] = $entry;
            } elseif ($entry->status === 'cancelled') {
                $categories['cancelled'][] = $entry;
            } else {
                $categories['other'][] = $entry;
            }
        }
        
        // Display categorization
        foreach ($categories as $category => $items) {
            echo "- " . strtoupper($category) . ": " . count($items) . " entries\n";
        }
        echo "\n";
        
        // Show details for each category
        self::showCategoryDetails($categories);
        
        return $categories;
    }
    
    private static function isReversalEntry($entry) {
        $indicators = [
            'reference_no' => ['rev', 'edit', 'reversal'],
            'notes' => ['reversal', '[reversed', 'edit', 'removed', 'correction']
        ];
        
        foreach ($indicators as $field => $patterns) {
            $value = strtolower($entry->$field ?? '');
            foreach ($patterns as $pattern) {
                if (strpos($value, $pattern) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private static function showCategoryDetails($categories) {
        foreach ($categories as $category => $entries) {
            if (empty($entries)) continue;
            
            $icon = [
                'active_normal' => '✅',
                'active_reversal' => '🔄',
                'reversed' => '❌',
                'cancelled' => '🚫',
                'other' => '❓'
            ][$category] ?? '?';
            
            echo "$icon " . strtoupper($category) . " ENTRIES:\n";
            foreach ($entries as $entry) {
                printf("   ID: %d | Date: %s | Type: %s | Debit: %.2f | Credit: %.2f | Ref: %s | Notes: %s\n",
                    $entry->id, $entry->transaction_date, $entry->transaction_type,
                    $entry->debit, $entry->credit, $entry->reference_no, $entry->notes ?? '');
            }
            echo "\n";
        }
    }
    
    private static function calculateBalanceScenarios($categorized, $customerId) {
        echo "💰 STEP 3: Balance calculation scenarios...\n";
        
        // Scenario 1: Current BalanceHelper method (status = 'active')
        $currentBalance = BalanceHelper::getCustomerBalance($customerId);
        echo "1. Current BalanceHelper result: $currentBalance\n";
        
        // Scenario 2: Only active_normal entries
        $activeNormalDebits = array_sum(array_map(fn($e) => $e->debit, $categorized['active_normal']));
        $activeNormalCredits = array_sum(array_map(fn($e) => $e->credit, $categorized['active_normal']));
        $activeNormalBalance = $activeNormalDebits - $activeNormalCredits;
        echo "2. Active normal entries only: $activeNormalBalance (Debits: $activeNormalDebits - Credits: $activeNormalCredits)\n";
        
        // Scenario 3: All active entries (including reversals)
        $allActiveEntries = array_merge($categorized['active_normal'], $categorized['active_reversal']);
        $allActiveDebits = array_sum(array_map(fn($e) => $e->debit, $allActiveEntries));
        $allActiveCredits = array_sum(array_map(fn($e) => $e->credit, $allActiveEntries));
        $allActiveBalance = $allActiveDebits - $allActiveCredits;
        echo "3. All active entries (with reversals): $allActiveBalance (Debits: $allActiveDebits - Credits: $allActiveCredits)\n";
        
        // Scenario 4: Manual calculation excluding specific problematic entries
        echo "\n🎯 ANALYSIS:\n";
        if (count($categorized['active_reversal']) > 0) {
            echo "⚠️  Found " . count($categorized['active_reversal']) . " active reversal entries that might be causing issues!\n";
            echo "   These are marked as 'active' but appear to be reversals/corrections.\n";
            echo "   If excluded, balance would be: $activeNormalBalance instead of $currentBalance\n";
        }
        
        if ($activeNormalBalance == 7000) {
            echo "✅ Expected balance (7000) matches 'active normal only' calculation!\n";
            echo "   This confirms that active reversal entries should be excluded.\n";
        }
        
        echo "\n";
    }
    
    private static function showRecommendations($categorized) {
        echo "💡 STEP 4: Recommendations...\n";
        
        if (count($categorized['active_reversal']) > 0) {
            echo "🔧 SOLUTION NEEDED:\n";
            echo "   The BalanceHelper should exclude reversal entries even if they are marked as 'active'.\n";
            echo "   Current filter: status = 'active'\n";
            echo "   Recommended filter: status = 'active' AND NOT (reversal patterns)\n\n";
            
            echo "📝 Suggested BalanceHelper improvement:\n";
            echo "   Add conditions to exclude entries with:\n";
            echo "   - Reference numbers containing: 'rev', 'edit', 'reversal'\n";
            echo "   - Notes containing: 'reversal', '[reversed', 'edit', 'correction'\n";
        } else {
            echo "✅ No active reversal entries found. Current logic should be working correctly.\n";
        }
        
        echo "\n";
    }
    
    public static function testCurrentBalanceHelper($customerId) {
        echo "🧪 TESTING CURRENT BALANCE HELPER...\n";
        
        $balance = BalanceHelper::getCustomerBalance($customerId);
        $due = BalanceHelper::getCustomerDue($customerId);
        $advance = BalanceHelper::getCustomerAdvance($customerId);
        
        echo "Customer Balance: $balance\n";
        echo "Customer Due: $due\n";
        echo "Customer Advance: $advance\n\n";
    }
    
    public static function suggestImprovedBalanceMethod($customerId) {
        echo "🚀 TESTING IMPROVED BALANCE CALCULATION...\n";
        
        $result = DB::selectOne("
            SELECT 
                COALESCE(SUM(debit), 0) as total_debits,
                COALESCE(SUM(credit), 0) as total_credits,
                COALESCE(SUM(debit) - SUM(credit), 0) as balance
            FROM ledgers 
            WHERE contact_id = ? 
                AND contact_type = 'customer'
                AND status = 'active'
                AND reference_no NOT LIKE '%rev%'
                AND reference_no NOT LIKE '%edit%'
                AND reference_no NOT LIKE '%reversal%'
                AND (notes IS NULL OR (
                    notes NOT LIKE '%reversal%' 
                    AND notes NOT LIKE '%[reversed%'
                    AND notes NOT LIKE '%edit%'
                    AND notes NOT LIKE '%correction%'
                ))
        ", [$customerId]);
        
        $improvedBalance = $result ? (float) $result->balance : 0.0;
        echo "Improved calculation result: $improvedBalance\n";
        echo "Total debits: " . ($result->total_debits ?? 0) . "\n";
        echo "Total credits: " . ($result->total_credits ?? 0) . "\n\n";
        
        return $improvedBalance;
    }
}

// Run the analysis
echo "🔬 COMPREHENSIVE BALANCE ANALYSIS\n";
echo str_repeat("=", 60) . "\n\n";

$customerId = 2; // Aasath

BalanceTestAnalyzer::analyzeCustomerBalance($customerId);
BalanceTestAnalyzer::testCurrentBalanceHelper($customerId);
BalanceTestAnalyzer::suggestImprovedBalanceMethod($customerId);

echo "🎯 EXPECTED RESULT: Customer balance should be 7000 (opening balance)\n";
echo "💡 If the 'Improved calculation result' shows 7000, then the solution is to update BalanceHelper!\n";