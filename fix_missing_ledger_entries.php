<?php
/**
 * ========================================================================
 * COMPREHENSIVE LEDGER FIX SCRIPT
 * ========================================================================
 *
 * Purpose: Detect and fix missing active ledger entries for sales
 *
 * This script:
 * 1. Finds sales that have reversed ledger entries but no active entry
 * 2. Creates missing ledger entries with correct transaction dates
 * 3. Validates customer balances
 * 4. Provides detailed logging and verification
 *
 * Safe to run multiple times (idempotent - won't create duplicates)
 *
 * Usage: php fix_missing_ledger_entries.php [--dry-run] [--customer-id=582]
 * ========================================================================
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$customerId = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--customer-id=') === 0) {
        $customerId = (int) substr($arg, 14);
    }
}

echo "========================================================================\n";
echo "LEDGER FIX SCRIPT - FIND AND FIX MISSING ACTIVE LEDGER ENTRIES\n";
echo "========================================================================\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes will be made)" : "LIVE (will create missing entries)") . "\n";
if ($customerId) {
    echo "Filtering: Customer ID = {$customerId}\n";
}
echo "Started: " . date('Y-m-d H:i:s') . "\n";
echo "========================================================================\n\n";

// Step 1: Find sales with reversed entries but no active entry
echo "Step 1: Scanning for sales with missing active ledger entries...\n\n";

$query = "
    SELECT
        s.id as sale_id,
        s.invoice_no,
        s.customer_id,
        s.final_total,
        s.status as sale_status,
        s.sales_date,
        s.created_at,
        s.updated_at,
        COUNT(l_rev.id) as reversed_count,
        COUNT(l_active.id) as active_count
    FROM sales s
    LEFT JOIN ledgers l_rev ON (
        l_rev.reference_no = s.invoice_no
        AND l_rev.transaction_type = 'sale'
        AND l_rev.status = 'reversed'
    )
    LEFT JOIN ledgers l_active ON (
        l_active.reference_no = s.invoice_no
        AND l_active.transaction_type = 'sale'
        AND l_active.status = 'active'
    )
    WHERE s.customer_id != 1 -- Exclude Walk-In customers
        AND s.status IN ('final', 'suspend') -- Only finalized sales
        AND s.transaction_type != 'sale_order' -- Exclude sale orders
        " . ($customerId ? "AND s.customer_id = {$customerId}" : "") . "
    GROUP BY s.id, s.invoice_no, s.customer_id, s.final_total, s.status, s.sales_date, s.created_at, s.updated_at
    HAVING reversed_count > 0 AND active_count = 0
    ORDER BY s.customer_id, s.sales_date
";

$problematicSales = DB::select($query);

if (empty($problematicSales)) {
    echo "✅ No missing ledger entries found! All sales have proper active ledger entries.\n\n";
    exit(0);
}

echo "Found " . count($problematicSales) . " sales with missing active ledger entries:\n\n";

// Display problematic sales
$totalMissing = 0;
$salesByCustomer = [];

foreach ($problematicSales as $sale) {
    echo "─────────────────────────────────────────────────────────────────\n";
    echo "Sale ID: {$sale->sale_id}\n";
    echo "Invoice: {$sale->invoice_no}\n";
    echo "Customer ID: {$sale->customer_id}\n";
    echo "Amount: Rs. {$sale->final_total}\n";
    echo "Sale Date: {$sale->sales_date}\n";
    echo "Status: {$sale->sale_status}\n";
    echo "Reversed Entries: {$sale->reversed_count}\n";
    echo "Active Entries: {$sale->active_count} ❌\n";

    $totalMissing += $sale->final_total;

    if (!isset($salesByCustomer[$sale->customer_id])) {
        $salesByCustomer[$sale->customer_id] = [];
    }
    $salesByCustomer[$sale->customer_id][] = $sale;
}

echo "─────────────────────────────────────────────────────────────────\n";
echo "Total Missing Amount: Rs. " . number_format($totalMissing, 2) . "\n";
echo "Affected Customers: " . count($salesByCustomer) . "\n\n";

// Step 2: Verify and fix
if ($dryRun) {
    echo "========================================================================\n";
    echo "DRY RUN MODE - No changes made\n";
    echo "Run without --dry-run to create the missing ledger entries\n";
    echo "========================================================================\n";
    exit(0);
}

// Ask for confirmation
echo "========================================================================\n";
echo "⚠️  WARNING: This will create " . count($problematicSales) . " new ledger entries\n";
echo "========================================================================\n";
echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ Operation cancelled by user.\n";
    exit(0);
}

// Step 3: Create missing ledger entries
echo "\n========================================================================\n";
echo "Step 2: Creating missing ledger entries...\n";
echo "========================================================================\n\n";

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($problematicSales as $sale) {
    try {
        DB::beginTransaction();

        // Double-check that no active entry exists (race condition protection)
        $existingActive = DB::table('ledgers')
            ->where('reference_no', $sale->invoice_no)
            ->where('transaction_type', 'sale')
            ->where('status', 'active')
            ->exists();

        if ($existingActive) {
            echo "⚠️  Skipped {$sale->invoice_no} - Active entry already exists (created by another process)\n";
            DB::rollBack();
            continue;
        }

        // Create the missing ledger entry
        $ledgerData = [
            'contact_id' => $sale->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $sale->sales_date, // Use original sale date
            'reference_no' => $sale->invoice_no,
            'transaction_type' => 'sale',
            'debit' => $sale->final_total,
            'credit' => 0,
            'status' => 'active',
            'notes' => "Sale invoice #{$sale->invoice_no} [FIXED: Missing ledger entry created on " . date('Y-m-d H:i:s') . "]",
            'created_at' => now(),
            'updated_at' => now()
        ];

        $ledgerId = DB::table('ledgers')->insertGetId($ledgerData);

        DB::commit();

        echo "✅ Created ledger entry ID: {$ledgerId} for {$sale->invoice_no} (Rs. {$sale->final_total})\n";
        $successCount++;

    } catch (\Exception $e) {
        DB::rollBack();
        $errorMsg = "Failed to create entry for {$sale->invoice_no}: " . $e->getMessage();
        echo "❌ {$errorMsg}\n";
        $errors[] = $errorMsg;
        $errorCount++;
    }
}

echo "\n========================================================================\n";
echo "SUMMARY\n";
echo "========================================================================\n";
echo "✅ Successfully Created: {$successCount}\n";
echo "❌ Errors: {$errorCount}\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

// Step 4: Verify customer balances
echo "\n========================================================================\n";
echo "Step 3: Verifying Customer Balances\n";
echo "========================================================================\n\n";

foreach ($salesByCustomer as $custId => $sales) {
    // Calculate balance from ledger
    $result = DB::selectOne("
        SELECT
            COALESCE(SUM(debit), 0) as total_debits,
            COALESCE(SUM(credit), 0) as total_credits,
            COALESCE(SUM(debit) - SUM(credit), 0) as balance
        FROM ledgers
        WHERE contact_id = ?
            AND contact_type = 'customer'
            AND status = 'active'
    ", [$custId]);

    $ledgerBalance = $result ? (float) $result->balance : 0.0;

    // Get customer info
    $customer = DB::table('customers')->where('id', $custId)->first();
    $customerName = $customer ? ($customer->customer_name ?? 'Unknown') : 'Unknown';

    echo "Customer ID {$custId} ({$customerName}):\n";
    echo "  Total Debits: Rs. " . number_format($result->total_debits, 2) . "\n";
    echo "  Total Credits: Rs. " . number_format($result->total_credits, 2) . "\n";
    echo "  Balance: Rs. " . number_format($ledgerBalance, 2) . "\n";
    echo "  Fixed Sales: " . count($sales) . "\n\n";
}

// Final verification - check if any issues remain
echo "========================================================================\n";
echo "Step 4: Final Verification\n";
echo "========================================================================\n\n";

$remainingIssues = DB::select($query);

if (empty($remainingIssues)) {
    echo "✅ SUCCESS! No remaining issues found.\n";
    echo "✅ All sales now have proper active ledger entries.\n";
} else {
    echo "⚠️  WARNING: Still found " . count($remainingIssues) . " sales with issues.\n";
    echo "These may need manual investigation.\n";
}

echo "\n========================================================================\n";
echo "COMPLETION REPORT\n";
echo "========================================================================\n";
echo "Completed: " . date('Y-m-d H:i:s') . "\n";
echo "Entries Created: {$successCount}\n";
echo "Errors: {$errorCount}\n";
echo "Total Amount Fixed: Rs. " . number_format($totalMissing, 2) . "\n";
echo "========================================================================\n\n";

// Create audit log entry
$auditLog = [
    'script' => 'fix_missing_ledger_entries.php',
    'executed_at' => date('Y-m-d H:i:s'),
    'mode' => $dryRun ? 'dry-run' : 'live',
    'entries_created' => $successCount,
    'errors' => $errorCount,
    'total_amount' => $totalMissing,
    'affected_customers' => count($salesByCustomer)
];

file_put_contents(
    __DIR__ . '/storage/logs/ledger_fix_' . date('Y-m-d_His') . '.json',
    json_encode($auditLog, JSON_PRETTY_PRINT)
);

echo "Audit log saved to: storage/logs/ledger_fix_" . date('Y-m-d_His') . ".json\n\n";

exit($errorCount > 0 ? 1 : 0);
