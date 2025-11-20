<?php
/**
 * ===================================================================
 * 🔧 LEDGER DUPLICATE FIXER SCRIPT
 * ===================================================================
 * 
 * This script helps identify and fix duplicate ledger entries
 * while preserving data integrity and audit trails.
 * 
 * USAGE:
 * php fix_duplicate_ledger.php --check    (Check for duplicates only)
 * php fix_duplicate_ledger.php --fix      (Fix duplicates after confirmation)
 * php fix_duplicate_ledger.php --analyze  (Detailed analysis)
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Ledger;
use App\Models\Sale;
use App\Models\Payment;
use Carbon\Carbon;

class LedgerDuplicateFixer
{
    private $dryRun = true;
    private $duplicates = [];
    private $fixes = [];
    
    public function __construct($dryRun = true)
    {
        $this->dryRun = $dryRun;
    }
    
    /**
     * Main analysis function
     */
    public function analyze()
    {
        echo "🔍 ANALYZING LEDGER FOR DUPLICATES...\n";
        echo "=====================================\n\n";
        
        $this->checkSalesLedgerDuplicates();
        $this->checkPaymentLedgerDuplicates();
        $this->checkBalanceConsistency();
        $this->generateReport();
    }
    
    /**
     * Check for duplicate sale ledger entries
     */
    private function checkSalesLedgerDuplicates()
    {
        echo "1. Checking Sales Ledger Duplicates...\n";
        
        // Find potential duplicates by reference_no and contact_id
        $salesDuplicates = DB::select("
            SELECT 
                reference_no, 
                contact_id, 
                contact_type,
                transaction_type,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id) as ledger_ids,
                GROUP_CONCAT(CONCAT('ID:', id, '|Amount:', debit, '|Date:', created_at) SEPARATOR ' || ') as details
            FROM ledgers 
            WHERE transaction_type = 'sale' 
                AND contact_type = 'customer'
                AND status = 'active'
            GROUP BY reference_no, contact_id, contact_type, transaction_type
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC, reference_no
        ");
        
        if (!empty($salesDuplicates)) {
            echo "❌ FOUND SALE DUPLICATES:\n";
            foreach ($salesDuplicates as $dup) {
                echo "   Reference: {$dup->reference_no} | Customer ID: {$dup->contact_id} | Count: {$dup->duplicate_count}\n";
                echo "   Details: {$dup->details}\n";
                echo "   Ledger IDs: {$dup->ledger_ids}\n\n";
                
                $this->duplicates['sales'][] = $dup;
            }
        } else {
            echo "✅ No sale duplicates found\n\n";
        }
    }
    
    /**
     * Check for duplicate payment ledger entries
     */
    private function checkPaymentLedgerDuplicates()
    {
        echo "2. Checking Payment Ledger Duplicates...\n";
        
        // Find potential duplicates by reference_no and contact_id
        $paymentDuplicates = DB::select("
            SELECT 
                reference_no, 
                contact_id, 
                contact_type,
                transaction_type,
                COUNT(*) as duplicate_count,
                GROUP_CONCAT(id) as ledger_ids,
                GROUP_CONCAT(CONCAT('ID:', id, '|Amount:', credit, '|Date:', created_at) SEPARATOR ' || ') as details
            FROM ledgers 
            WHERE transaction_type IN ('payment', 'payments', 'sale_payment') 
                AND contact_type = 'customer'
                AND status = 'active'
            GROUP BY reference_no, contact_id, contact_type, transaction_type
            HAVING COUNT(*) > 1
            ORDER BY duplicate_count DESC, reference_no
        ");
        
        if (!empty($paymentDuplicates)) {
            echo "❌ FOUND PAYMENT DUPLICATES:\n";
            foreach ($paymentDuplicates as $dup) {
                echo "   Reference: {$dup->reference_no} | Customer ID: {$dup->contact_id} | Count: {$dup->duplicate_count}\n";
                echo "   Details: {$dup->details}\n";
                echo "   Ledger IDs: {$dup->ledger_ids}\n\n";
                
                $this->duplicates['payments'][] = $dup;
            }
        } else {
            echo "✅ No payment duplicates found\n\n";
        }
    }
    
    /**
     * Check for balance consistency issues
     */
    private function checkBalanceConsistency()
    {
        echo "3. Checking Balance Consistency...\n";
        
        // Check if any customer has mismatched balances
        $inconsistentBalances = DB::select("
            SELECT 
                customers.id as customer_id,
                customers.first_name,
                customers.last_name,
                customers.opening_balance,
                COALESCE(SUM(ledgers.debit - ledgers.credit), 0) as calculated_balance,
                COUNT(ledgers.id) as ledger_entries_count
            FROM customers 
            LEFT JOIN ledgers ON customers.id = ledgers.contact_id 
                AND ledgers.contact_type = 'customer' 
                AND ledgers.status = 'active'
            WHERE customers.id > 1  -- Exclude walk-in customer
            GROUP BY customers.id, customers.first_name, customers.last_name, customers.opening_balance
            HAVING COUNT(ledgers.id) = 0
                OR ABS(customers.opening_balance - COALESCE(SUM(ledgers.debit - ledgers.credit), 0)) > 0.01
            ORDER BY ABS(customers.opening_balance - COALESCE(SUM(ledgers.debit - ledgers.credit), 0)) DESC
            LIMIT 20
        ");
        
        if (!empty($inconsistentBalances)) {
            echo "⚠️  BALANCE INCONSISTENCIES FOUND:\n";
            foreach ($inconsistentBalances as $balance) {
                $diff = $balance->opening_balance - $balance->calculated_balance;
                echo "   Customer: {$balance->first_name} {$balance->last_name} (ID: {$balance->customer_id})\n";
                echo "   Opening Balance: {$balance->opening_balance} | Calculated: {$balance->calculated_balance} | Diff: {$diff}\n";
                echo "   Ledger Entries: {$balance->ledger_entries_count}\n\n";
            }
        } else {
            echo "✅ All balances are consistent\n\n";
        }
    }
    
    /**
     * Fix duplicate entries safely
     */
    public function fixDuplicates()
    {
        echo "🔧 FIXING DUPLICATE LEDGER ENTRIES...\n";
        echo "====================================\n\n";
        
        if ($this->dryRun) {
            echo "⚠️  DRY RUN MODE - No changes will be made\n\n";
        }
        
        $this->analyze(); // First analyze to find duplicates
        
        $totalFixed = 0;
        
        // Fix sales duplicates
        if (isset($this->duplicates['sales'])) {
            foreach ($this->duplicates['sales'] as $duplicate) {
                $totalFixed += $this->fixSaleDuplicates($duplicate);
            }
        }
        
        // Fix payment duplicates
        if (isset($this->duplicates['payments'])) {
            foreach ($this->duplicates['payments'] as $duplicate) {
                $totalFixed += $this->fixPaymentDuplicates($duplicate);
            }
        }
        
        echo "\n📊 SUMMARY:\n";
        echo "Total duplicates fixed: {$totalFixed}\n";
        
        if ($this->dryRun) {
            echo "\n⚠️  This was a DRY RUN. To actually fix the duplicates, run with --fix parameter.\n";
        }
    }
    
    /**
     * Fix sale duplicates by keeping the latest entry
     */
    private function fixSaleDuplicates($duplicate)
    {
        $ledgerIds = explode(',', $duplicate->ledger_ids);
        
        if (count($ledgerIds) <= 1) {
            return 0;
        }
        
        echo "🔧 Fixing sale duplicates for reference: {$duplicate->reference_no}\n";
        
        // Get all duplicate entries
        $entries = Ledger::whereIn('id', $ledgerIds)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Keep the latest entry (first in the ordered list)
        $keepEntry = $entries->first();
        $removeEntries = $entries->slice(1);
        
        echo "   Keeping entry ID: {$keepEntry->id} (Latest)\n";
        echo "   Marking as reversed: " . $removeEntries->pluck('id')->implode(', ') . "\n";
        
        $fixedCount = 0;
        
        if (!$this->dryRun) {
            foreach ($removeEntries as $entry) {
                $entry->update([
                    'status' => 'reversed',
                    'notes' => ($entry->notes ? $entry->notes . ' | ' : '') . 
                              '[DUPLICATE REMOVED: ' . date('Y-m-d H:i:s') . ']'
                ]);
                $fixedCount++;
            }
        } else {
            $fixedCount = $removeEntries->count();
        }
        
        $this->fixes[] = [
            'type' => 'sale_duplicate',
            'reference' => $duplicate->reference_no,
            'kept' => $keepEntry->id,
            'removed' => $removeEntries->pluck('id')->toArray(),
            'count' => $fixedCount
        ];
        
        return $fixedCount;
    }
    
    /**
     * Fix payment duplicates by keeping the latest entry
     */
    private function fixPaymentDuplicates($duplicate)
    {
        $ledgerIds = explode(',', $duplicate->ledger_ids);
        
        if (count($ledgerIds) <= 1) {
            return 0;
        }
        
        echo "🔧 Fixing payment duplicates for reference: {$duplicate->reference_no}\n";
        
        // Get all duplicate entries
        $entries = Ledger::whereIn('id', $ledgerIds)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Keep the latest entry (first in the ordered list)
        $keepEntry = $entries->first();
        $removeEntries = $entries->slice(1);
        
        echo "   Keeping entry ID: {$keepEntry->id} (Latest)\n";
        echo "   Marking as reversed: " . $removeEntries->pluck('id')->implode(', ') . "\n";
        
        $fixedCount = 0;
        
        if (!$this->dryRun) {
            foreach ($removeEntries as $entry) {
                $entry->update([
                    'status' => 'reversed',
                    'notes' => ($entry->notes ? $entry->notes . ' | ' : '') . 
                              '[DUPLICATE REMOVED: ' . date('Y-m-d H:i:s') . ']'
                ]);
                $fixedCount++;
            }
        } else {
            $fixedCount = $removeEntries->count();
        }
        
        $this->fixes[] = [
            'type' => 'payment_duplicate',
            'reference' => $duplicate->reference_no,
            'kept' => $keepEntry->id,
            'removed' => $removeEntries->pluck('id')->toArray(),
            'count' => $fixedCount
        ];
        
        return $fixedCount;
    }
    
    /**
     * Generate detailed report
     */
    private function generateReport()
    {
        echo "📋 DETAILED REPORT\n";
        echo "=================\n\n";
        
        // Total ledger entries
        $totalLedgerEntries = Ledger::count();
        $activeLedgerEntries = Ledger::where('status', 'active')->count();
        $reversedEntries = Ledger::where('status', 'reversed')->count();
        
        echo "📊 Ledger Statistics:\n";
        echo "   Total entries: {$totalLedgerEntries}\n";
        echo "   Active entries: {$activeLedgerEntries}\n";
        echo "   Reversed entries: {$reversedEntries}\n\n";
        
        // Sales vs Ledger comparison
        $totalSales = Sale::count();
        $salesWithLedger = DB::table('sales')
            ->join('ledgers', function($join) {
                $join->on('sales.invoice_no', '=', 'ledgers.reference_no')
                     ->orOn(DB::raw("CONCAT('INV-', sales.id)"), '=', 'ledgers.reference_no');
            })
            ->where('ledgers.transaction_type', 'sale')
            ->where('ledgers.status', 'active')
            ->distinct('sales.id')
            ->count();
        
        echo "🛒 Sales vs Ledger:\n";
        echo "   Total sales: {$totalSales}\n";
        echo "   Sales with ledger entries: {$salesWithLedger}\n";
        echo "   Sales missing ledger: " . ($totalSales - $salesWithLedger) . "\n\n";
        
        // Recent problematic entries
        echo "🔍 Recent Potentially Problematic Entries:\n";
        $recentProblems = Ledger::where('created_at', '>=', Carbon::now()->subDays(7))
            ->where(function($query) {
                $query->where('notes', 'like', '%REVERSAL%')
                      ->orWhere('notes', 'like', '%DUPLICATE%')
                      ->orWhere('notes', 'like', '%REVERSED%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'reference_no', 'transaction_type', 'contact_id', 'debit', 'credit', 'notes', 'created_at']);
        
        foreach ($recentProblems as $problem) {
            echo "   ID: {$problem->id} | Ref: {$problem->reference_no} | Type: {$problem->transaction_type}\n";
            echo "   Amount: D:{$problem->debit} C:{$problem->credit} | Date: {$problem->created_at}\n";
            echo "   Notes: " . substr($problem->notes, 0, 100) . "...\n\n";
        }
    }
    
    /**
     * Check specific customer's ledger
     */
    public function checkCustomerLedger($customerId)
    {
        echo "🔍 CHECKING CUSTOMER LEDGER (ID: {$customerId})\n";
        echo "===============================================\n\n";
        
        $customer = DB::table('customers')->where('id', $customerId)->first();
        if (!$customer) {
            echo "❌ Customer not found!\n";
            return;
        }
        
        echo "Customer: {$customer->first_name} {$customer->last_name}\n";
        echo "Opening Balance: {$customer->opening_balance}\n\n";
        
        // Get all ledger entries for this customer
        $ledgerEntries = Ledger::where('contact_id', $customerId)
            ->where('contact_type', 'customer')
            ->orderBy('created_at', 'desc')
            ->get();
        
        echo "📝 Ledger Entries ({$ledgerEntries->count()}):\n";
        
        $runningBalance = 0;
        foreach ($ledgerEntries as $entry) {
            $runningBalance += ($entry->debit - $entry->credit);
            
            $status = $entry->status === 'active' ? '✅' : '❌';
            echo "   {$status} ID:{$entry->id} | {$entry->transaction_type} | Ref: {$entry->reference_no}\n";
            echo "      D: {$entry->debit} C: {$entry->credit} | Balance: {$runningBalance} | {$entry->created_at}\n";
            echo "      Notes: " . substr($entry->notes, 0, 80) . "\n\n";
        }
        
        // Check for duplicates for this customer
        $duplicates = DB::select("
            SELECT reference_no, COUNT(*) as count 
            FROM ledgers 
            WHERE contact_id = ? AND contact_type = 'customer' AND status = 'active'
            GROUP BY reference_no 
            HAVING COUNT(*) > 1
        ", [$customerId]);
        
        if (!empty($duplicates)) {
            echo "⚠️  DUPLICATES FOUND:\n";
            foreach ($duplicates as $dup) {
                echo "   Reference: {$dup->reference_no} | Count: {$dup->count}\n";
            }
        } else {
            echo "✅ No duplicates found for this customer\n";
        }
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage:\n";
    echo "  php fix_duplicate_ledger.php --check           # Check for duplicates only\n";
    echo "  php fix_duplicate_ledger.php --fix             # Fix duplicates (dry run first)\n";
    echo "  php fix_duplicate_ledger.php --fix --confirm   # Actually fix duplicates\n";
    echo "  php fix_duplicate_ledger.php --analyze         # Detailed analysis\n";
    echo "  php fix_duplicate_ledger.php --customer=ID     # Check specific customer\n";
    exit(1);
}

$command = $argv[1];
$confirm = in_array('--confirm', $argv);
$dryRun = !$confirm;

$fixer = new LedgerDuplicateFixer($dryRun);

switch ($command) {
    case '--check':
        $fixer->analyze();
        break;
        
    case '--fix':
        if ($confirm) {
            echo "⚠️  WARNING: This will modify your database!\n";
            echo "Are you sure you want to proceed? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (trim($line) !== 'yes') {
                echo "Cancelled.\n";
                exit(0);
            }
        }
        $fixer->fixDuplicates();
        break;
        
    case '--analyze':
        $fixer->analyze();
        break;
        
    default:
        if (strpos($command, '--customer=') === 0) {
            $customerId = substr($command, 11);
            if (is_numeric($customerId)) {
                $fixer->checkCustomerLedger($customerId);
            } else {
                echo "Invalid customer ID\n";
            }
        } else {
            echo "Unknown command: {$command}\n";
        }
        break;
}

echo "\n✅ Script completed at " . date('Y-m-d H:i:s') . "\n";