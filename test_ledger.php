<?php
/**
 * ===================================================================
 * 🔍 QUICK LEDGER CHECKER - Test Script
 * ===================================================================
 * 
 * Quick script to check for duplicate ledger entries
 * 
 * USAGE: php test_ledger.php
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 QUICK LEDGER DUPLICATE CHECK\n";
echo "===============================\n\n";

// 1. Check for exact duplicates in ledger
echo "1. Checking for exact duplicate ledger entries...\n";
$exactDuplicates = DB::select("
    SELECT 
        reference_no, 
        contact_id, 
        contact_type,
        transaction_type,
        debit,
        credit,
        COUNT(*) as count
    FROM ledgers 
    WHERE status = 'active'
    GROUP BY reference_no, contact_id, contact_type, transaction_type, debit, credit
    HAVING COUNT(*) > 1
    ORDER BY count DESC
    LIMIT 10
");

if (!empty($exactDuplicates)) {
    echo "❌ FOUND EXACT DUPLICATES:\n";
    foreach ($exactDuplicates as $dup) {
        echo "   Ref: {$dup->reference_no} | Customer: {$dup->contact_id} | Type: {$dup->transaction_type}\n";
        echo "   Amount: D:{$dup->debit} C:{$dup->credit} | Duplicates: {$dup->count}\n\n";
    }
} else {
    echo "✅ No exact duplicates found\n";
}

echo "\n";

// 2. Check for reference number duplicates
echo "2. Checking for reference number duplicates...\n";
$refDuplicates = DB::select("
    SELECT 
        reference_no, 
        contact_id, 
        contact_type,
        COUNT(*) as count,
        GROUP_CONCAT(id ORDER BY created_at) as ledger_ids,
        GROUP_CONCAT(CONCAT(transaction_type, ':', debit, '-', credit) SEPARATOR ' | ') as details
    FROM ledgers 
    WHERE status = 'active' 
        AND contact_type = 'customer'
        AND reference_no NOT LIKE '%-REV%'
        AND reference_no NOT LIKE '%-OLD%'
    GROUP BY reference_no, contact_id, contact_type
    HAVING COUNT(*) > 1
    ORDER BY count DESC
    LIMIT 10
");

if (!empty($refDuplicates)) {
    echo "❌ FOUND REFERENCE DUPLICATES:\n";
    foreach ($refDuplicates as $dup) {
        echo "   Ref: {$dup->reference_no} | Customer: {$dup->contact_id}\n";
        echo "   Ledger IDs: {$dup->ledger_ids}\n";
        echo "   Details: {$dup->details}\n";
        echo "   Duplicates: {$dup->count}\n\n";
    }
} else {
    echo "✅ No reference duplicates found\n";
}

echo "\n";

// 3. Check overall ledger health
echo "3. Ledger health check...\n";
$stats = DB::select("
    SELECT 
        COUNT(*) as total_entries,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_entries,
        COUNT(CASE WHEN status = 'reversed' THEN 1 END) as reversed_entries,
        COUNT(CASE WHEN transaction_type = 'sale' THEN 1 END) as sale_entries,
        COUNT(CASE WHEN transaction_type IN ('payment', 'payments') THEN 1 END) as payment_entries
    FROM ledgers
")[0];

echo "📊 Ledger Statistics:\n";
echo "   Total entries: {$stats->total_entries}\n";
echo "   Active entries: {$stats->active_entries}\n";
echo "   Reversed entries: {$stats->reversed_entries}\n";
echo "   Sale entries: {$stats->sale_entries}\n";
echo "   Payment entries: {$stats->payment_entries}\n\n";

// 4. Check recent problematic entries
echo "4. Recent potentially problematic entries (last 7 days)...\n";
$problems = DB::select("
    SELECT 
        id,
        reference_no,
        transaction_type,
        contact_id,
        debit,
        credit,
        status,
        SUBSTRING(notes, 1, 50) as short_notes,
        created_at
    FROM ledgers 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND (
            notes LIKE '%REVERSAL%' OR 
            notes LIKE '%DUPLICATE%' OR 
            notes LIKE '%REVERSED%' OR
            status = 'reversed'
        )
    ORDER BY created_at DESC
    LIMIT 10
");

if (!empty($problems)) {
    echo "⚠️  RECENT PROBLEMATIC ENTRIES:\n";
    foreach ($problems as $problem) {
        echo "   ID: {$problem->id} | Ref: {$problem->reference_no} | Status: {$problem->status}\n";
        echo "   Type: {$problem->transaction_type} | Customer: {$problem->contact_id}\n";
        echo "   Amount: D:{$problem->debit} C:{$problem->credit}\n";
        echo "   Notes: {$problem->short_notes}...\n";
        echo "   Date: {$problem->created_at}\n\n";
    }
} else {
    echo "✅ No recent problematic entries\n";
}

echo "\n";

// 5. Quick balance check for top 5 customers
echo "5. Quick balance check for top 5 active customers...\n";
$customerBalances = DB::select("
    SELECT 
        c.id,
        c.first_name,
        c.last_name,
        c.opening_balance,
        COALESCE(SUM(l.debit - l.credit), 0) as calculated_balance,
        COUNT(l.id) as ledger_count
    FROM customers c
    LEFT JOIN ledgers l ON c.id = l.contact_id 
        AND l.contact_type = 'customer' 
        AND l.status = 'active'
    WHERE c.id > 1
    GROUP BY c.id, c.first_name, c.last_name, c.opening_balance
    ORDER BY ledger_count DESC
    LIMIT 5
");

if (!empty($customerBalances)) {
    echo "👥 TOP CUSTOMERS BY ACTIVITY:\n";
    foreach ($customerBalances as $customer) {
        $diff = abs($customer->opening_balance - $customer->calculated_balance);
        $status = $diff > 0.01 ? "⚠️" : "✅";
        
        echo "   {$status} {$customer->first_name} {$customer->last_name} (ID: {$customer->id})\n";
        echo "      Opening: {$customer->opening_balance} | Calculated: {$customer->calculated_balance}\n";
        echo "      Ledger entries: {$customer->ledger_count}\n\n";
    }
}

echo "✅ Quick check completed!\n";
echo "\nTo run a full analysis and fix duplicates, use:\n";
echo "php fix_duplicate_ledger.php --check\n";
echo "php fix_duplicate_ledger.php --fix\n\n";