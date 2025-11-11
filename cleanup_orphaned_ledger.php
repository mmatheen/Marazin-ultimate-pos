 <?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Sale;
use App\Models\Ledger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "🧹 ORPHANED LEDGER ENTRIES CLEANUP\n";
echo "==================================\n\n";

// Get the problematic references
$problematicRefs = ['ATF-017', 'ATF-020', 'ATF-027', 'MLX-050'];

echo "⚠️  CRITICAL ISSUE DETECTED:\n";
echo "These ledger entries exist but their corresponding sales have been deleted!\n";
echo "This causes incorrect customer balances.\n\n";

$totalOrphanedAmount = 0;
$affectedCustomers = [];

foreach ($problematicRefs as $refNo) {
    echo "🔍 CHECKING INVOICE: $refNo\n";
    echo str_repeat("-", 40) . "\n";
    
    // Verify sale doesn't exist
    $sale = Sale::where('invoice_no', $refNo)->first();
    echo "Sale exists: " . ($sale ? "YES" : "NO") . "\n";
    
    // Get all ledger entries for this reference
    $ledgerEntries = Ledger::where('reference_no', $refNo)
        ->where('contact_type', 'customer')
        ->get();
    
    echo "Orphaned Ledger Entries: {$ledgerEntries->count()}\n";
    
    foreach ($ledgerEntries as $entry) {
        $customer = Customer::find($entry->user_id);
        $customerName = $customer ? $customer->full_name : "Unknown Customer";
        $amount = $entry->debit + $entry->credit;
        
        echo "  - Customer: {$entry->user_id} ($customerName)\n";
        echo "    Amount: Rs " . number_format($amount, 2) . "\n";
        echo "    Type: {$entry->transaction_type}\n";
        echo "    Date: " . $entry->created_at->format('Y-m-d H:i:s') . "\n";
        
        $totalOrphanedAmount += $amount;
        $affectedCustomers[$entry->user_id] = ($affectedCustomers[$entry->user_id] ?? 0) + $entry->debit - $entry->credit;
    }
    
    echo "\n";
}

echo "💰 FINANCIAL IMPACT:\n";
echo "Total Orphaned Amount: Rs " . number_format($totalOrphanedAmount, 2) . "\n\n";

echo "👥 AFFECTED CUSTOMERS:\n";
foreach ($affectedCustomers as $customerId => $incorrectBalance) {
    $customer = Customer::find($customerId);
    $customerName = $customer ? $customer->full_name : "Unknown Customer";
    $currentLedgerBalance = Ledger::getLatestBalance($customerId, 'customer');
    
    echo "Customer $customerId ($customerName):\n";
    echo "  Current Ledger Balance: Rs " . number_format($currentLedgerBalance, 2) . "\n";
    echo "  Incorrect Amount: Rs " . number_format($incorrectBalance, 2) . "\n";
    echo "  Should be: Rs " . number_format($currentLedgerBalance - $incorrectBalance, 2) . "\n\n";
}

echo "🔧 RECOMMENDED ACTIONS:\n";
echo "1. BACKUP your database before any cleanup\n";
echo "2. Remove orphaned ledger entries for deleted sales\n";  
echo "3. Recalculate customer balances\n";
echo "4. Verify customer balance accuracy\n\n";

echo "Would you like to proceed with the SAFE CLEANUP? This will:\n";
echo "✅ Create reversal entries to cancel out orphaned transactions\n";
echo "✅ Maintain complete audit trail\n";
echo "✅ Fix customer balances safely\n\n";

// Create the cleanup SQL for manual review
$cleanupSQL = "-- SAFE CLEANUP SQL FOR ORPHANED LEDGER ENTRIES\n";
$cleanupSQL .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($problematicRefs as $refNo) {
    $entries = Ledger::where('reference_no', $refNo)
        ->where('contact_type', 'customer')
        ->get();
    
    $cleanupSQL .= "-- Cleanup for invoice: $refNo\n";
    foreach ($entries as $entry) {
        // Create reversal entry SQL
        $reversalDebit = $entry->credit; // Swap to reverse
        $reversalCredit = $entry->debit; // Swap to reverse
        $notes = "CLEANUP: Reversal of orphaned entry for deleted sale $refNo";
        
        $cleanupSQL .= "INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (\n";
        $cleanupSQL .= "  '" . Carbon::now()->format('Y-m-d H:i:s') . "',\n";
        $cleanupSQL .= "  'CLEANUP-REV-$refNo',\n";
        $cleanupSQL .= "  'adjustment_credit',\n";
        $cleanupSQL .= "  $reversalDebit,\n";
        $cleanupSQL .= "  $reversalCredit,\n";
        $cleanupSQL .= "  0,  -- Will be recalculated\n";
        $cleanupSQL .= "  'customer',\n";
        $cleanupSQL .= "  {$entry->user_id},\n";
        $cleanupSQL .= "  '$notes',\n";
        $cleanupSQL .= "  '" . Carbon::now()->format('Y-m-d H:i:s') . "',\n";
        $cleanupSQL .= "  '" . Carbon::now()->format('Y-m-d H:i:s') . "'\n";
        $cleanupSQL .= ");\n\n";
    }
}

// Save cleanup SQL to file
file_put_contents('ledger_cleanup.sql', $cleanupSQL);

echo "📄 CLEANUP SQL GENERATED: ledger_cleanup.sql\n";
echo "Review this file before executing to ensure safety.\n\n";

echo "🎯 NEXT STEPS:\n";
echo "1. Backup your database\n";
echo "2. Review ledger_cleanup.sql\n";
echo "3. Execute the SQL to fix customer balances\n";
echo "4. Run recalculateAllBalances for each affected customer\n";