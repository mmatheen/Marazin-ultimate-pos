<?php
/**
 * Supplier 4 (Classic Bulb Israth Boss) - Production Fix Script
 * This script automatically fixes all ledger discrepancies
 * Safe to run multiple times - checks before fixing
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n========================================\n";
echo "SUPPLIER 4 LEDGER FIX - PRODUCTION SCRIPT\n";
echo "========================================\n\n";

// ============================================================================
// STEP 1: Load Database Configuration
// ============================================================================
echo "STEP 1: Loading database configuration...\n";

// Try to load from .env file (Laravel style)
$envFile = __DIR__ . '/.env';
$dbConfig = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (strpos($key, 'DB_') === 0) {
                $dbConfig[$key] = $value;
            }
        }
    }
}

// Get database credentials
$host = $dbConfig['DB_HOST'] ?? '127.0.0.1';
$port = $dbConfig['DB_PORT'] ?? '3306';
$database = $dbConfig['DB_DATABASE'] ?? 'ctc-db';
$username = $dbConfig['DB_USERNAME'] ?? 'root';
$password = $dbConfig['DB_PASSWORD'] ?? '';

echo "✓ Database: {$database}\n";
echo "✓ Host: {$host}\n";
echo "✓ User: {$username}\n\n";

// ============================================================================
// STEP 2: Connect to Database
// ============================================================================
echo "STEP 2: Connecting to database...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✓ Connected successfully\n\n";
} catch (PDOException $e) {
    die("✗ Connection failed: " . $e->getMessage() . "\n");
}

// ============================================================================
// STEP 3: Check Current State
// ============================================================================
echo "========================================\n";
echo "STEP 3: CHECKING CURRENT STATE\n";
echo "========================================\n\n";

$supplier_id = 4;

// Check current balance
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status='active' THEN (credit-debit) ELSE 0 END) as current_balance
    FROM ledgers 
    WHERE contact_id={$supplier_id} AND contact_type='supplier'
");
$current = $stmt->fetch();
$currentBalance = $current['current_balance'];
$expectedBalance = 1064720.00;

echo "Current Balance: " . number_format($currentBalance, 2) . "\n";
echo "Expected Balance: " . number_format($expectedBalance, 2) . "\n";
echo "Difference: " . number_format($currentBalance - $expectedBalance, 2) . "\n";

if (abs($currentBalance - $expectedBalance) < 1) {
    echo "✓ Status: ALREADY CORRECT - No fixes needed!\n\n";
    echo "========================================\n";
    echo "Outstanding Purchases:\n";
    $stmt = $pdo->query("SELECT id, reference_no, total_due FROM purchases WHERE supplier_id={$supplier_id} AND payment_status IN ('Due','Partial')");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['reference_no']}: " . number_format($row['total_due'], 2) . "\n";
    }
    echo "========================================\n";
    exit(0);
}

echo "✗ Status: NEEDS FIX\n\n";

// Check individual issues
echo "Checking specific issues:\n";

$issues = [];

// Issue 1: PUR044
$stmt = $pdo->query("SELECT credit FROM ledgers WHERE id=1160 AND contact_id={$supplier_id}");
$row = $stmt->fetch();
if ($row && $row['credit'] != 60000.00) {
    echo "  ✗ Issue 1: PUR044 ledger is " . number_format($row['credit'], 2) . " (should be 60,000.00)\n";
    $issues[] = 'PUR044';
} else {
    echo "  ✓ Issue 1: PUR044 is correct\n";
}

// Issue 2: PUR041
$stmt = $pdo->query("SELECT credit FROM ledgers WHERE id=1157 AND contact_id={$supplier_id}");
$row = $stmt->fetch();
if ($row && $row['credit'] != 72150.00) {
    echo "  ✗ Issue 2: PUR041 ledger is " . number_format($row['credit'], 2) . " (should be 72,150.00)\n";
    $issues[] = 'PUR041';
} else {
    echo "  ✓ Issue 2: PUR041 is correct\n";
}

// Issue 3: Missing payment ledgers
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM ledgers WHERE contact_id={$supplier_id} AND reference_no='PUR023' AND transaction_type='payments' AND debit=565000.00");
$row = $stmt->fetch();
if ($row['cnt'] == 0) {
    echo "  ✗ Issue 3: PUR023 payment ledger is missing\n";
    $issues[] = 'PUR023_payment';
} else {
    echo "  ✓ Issue 3: PUR023 payment ledger exists\n";
}

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM ledgers WHERE contact_id={$supplier_id} AND reference_no='PUR052' AND transaction_type='payments' AND debit=242000.00");
$row = $stmt->fetch();
if ($row['cnt'] == 0) {
    echo "  ✗ Issue 4: PUR052 payment ledger is missing\n";
    $issues[] = 'PUR052_payment';
} else {
    echo "  ✓ Issue 4: PUR052 payment ledger exists\n";
}

// Issue 5: PUR007
$stmt = $pdo->query("SELECT credit FROM ledgers WHERE id=71 AND contact_id={$supplier_id}");
$row = $stmt->fetch();
if ($row && $row['credit'] != 12500.00) {
    echo "  ✗ Issue 5: PUR007 ledger is " . number_format($row['credit'], 2) . " (should be 12,500.00)\n";
    $issues[] = 'PUR007_amount';
} else {
    echo "  ✓ Issue 5: PUR007 amount is correct\n";
}

// Issue 6: Duplicate entries
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM ledgers WHERE id IN (52, 106) AND status='active'");
$row = $stmt->fetch();
if ($row['cnt'] > 0) {
    echo "  ✗ Issue 6: Found {$row['cnt']} duplicate ledger entries\n";
    $issues[] = 'duplicates';
} else {
    echo "  ✓ Issue 6: No duplicate ledgers\n";
}

echo "\nTotal issues found: " . count($issues) . "\n\n";

// ============================================================================
// STEP 4: Apply Fixes
// ============================================================================
if (count($issues) > 0) {
    echo "========================================\n";
    echo "STEP 4: APPLYING FIXES\n";
    echo "========================================\n\n";
    
    try {
        $pdo->beginTransaction();
        
        $fixCount = 0;
        
        // Fix 1: PUR044
        $stmt = $pdo->prepare("UPDATE ledgers SET credit=60000.00, notes='Purchase invoice #PUR044 [CORRECTED]', updated_at=NOW() WHERE id=1160 AND contact_id=?");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 1: PUR044 corrected\n";
            $fixCount++;
        }
        
        // Fix 2: PUR041
        $stmt = $pdo->prepare("UPDATE ledgers SET credit=72150.00, notes='Purchase invoice #PUR041 [CORRECTED]', updated_at=NOW() WHERE id=1157 AND contact_id=?");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 2: PUR041 corrected\n";
            $fixCount++;
        }
        
        // Fix 3: Add PUR023 payment ledger
        $stmt = $pdo->prepare("
            INSERT INTO ledgers (contact_id, transaction_date, reference_no, transaction_type, debit, credit, status, contact_type, notes, created_by, created_at, updated_at)
            SELECT ?, '2025-12-11 21:48:42', 'PUR023', 'payments', 565000.00, 0.00, 'active', 'supplier', 'Payment for #PUR023 [SYSTEM FIX]', 2, '2025-12-11 21:48:42', NOW()
            WHERE NOT EXISTS (SELECT 1 FROM ledgers WHERE contact_id=? AND reference_no='PUR023' AND transaction_type='payments' AND debit=565000.00)
        ");
        $stmt->execute([$supplier_id, $supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 3: PUR023 payment ledger added\n";
            $fixCount++;
        }
        
        // Fix 4: Add PUR052 payment ledger
        $stmt = $pdo->prepare("
            INSERT INTO ledgers (contact_id, transaction_date, reference_no, transaction_type, debit, credit, status, contact_type, notes, created_by, created_at, updated_at)
            SELECT ?, '2025-12-29 09:44:55', 'PUR052', 'payments', 242000.00, 0.00, 'active', 'supplier', 'W [SYSTEM FIX]', 2, '2025-12-29 09:44:55', NOW()
            WHERE NOT EXISTS (SELECT 1 FROM ledgers WHERE contact_id=? AND reference_no='PUR052' AND transaction_type='payments' AND debit=242000.00)
        ");
        $stmt->execute([$supplier_id, $supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 4: PUR052 payment ledger added\n";
            $fixCount++;
        }
        
        // Fix 5: PUR007 amount
        $stmt = $pdo->prepare("UPDATE ledgers SET credit=12500.00, notes='Purchase invoice #PUR007 [CORRECTED]', updated_at=NOW() WHERE id=71 AND contact_id=?");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 5: PUR007 amount corrected\n";
            $fixCount++;
        }
        
        // Fix 6: Delete duplicate payments
        $stmt = $pdo->prepare("UPDATE payments SET status='deleted', notes=CONCAT(COALESCE(notes,''), ' [SYSTEM FIX]'), updated_at=NOW() WHERE id IN (116, 154) AND supplier_id=? AND status='active'");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 6: Duplicate payments removed\n";
            $fixCount++;
        }
        
        // Fix 7: Update PUR007 purchase
        $stmt = $pdo->prepare("UPDATE purchases SET total_paid=12500.00, payment_status='Paid', updated_at=NOW() WHERE id=7 AND supplier_id=?");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 7: PUR007 purchase updated\n";
            $fixCount++;
        }
        
        // Fix 8 & 9: Reverse duplicate ledgers
        $stmt = $pdo->prepare("UPDATE ledgers SET status='reversed', notes=CONCAT(notes, ' [SYSTEM FIX]'), updated_at=NOW() WHERE id IN (52, 106) AND contact_id=? AND status='active'");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 8-9: Duplicate ledgers reversed\n";
            $fixCount++;
        }
        
        // Fix 10: Clear opening balance
        $stmt = $pdo->prepare("UPDATE ledgers SET status='reversed', notes=CONCAT(notes, ' [SYSTEM FIX]'), updated_at=NOW() WHERE contact_id=? AND contact_type='supplier' AND transaction_type='opening_balance' AND status='active'");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 10: Opening balance cleared\n";
            $fixCount++;
        }
        
        // Fix 11: Misplaced entry
        $stmt = $pdo->prepare("UPDATE ledgers SET status='reversed', notes='Misplaced entry [SYSTEM FIX]', updated_at=NOW() WHERE id=203 AND contact_id=? AND status='active'");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 11: Misplaced entry fixed\n";
            $fixCount++;
        }
        
        // Fix 12: Purchase return
        $stmt = $pdo->prepare("UPDATE ledgers SET status='reversed', notes=CONCAT(notes, ' [SYSTEM FIX]'), updated_at=NOW() WHERE id=72 AND contact_id=? AND status='active'");
        $stmt->execute([$supplier_id]);
        if ($stmt->rowCount() > 0) {
            echo "✓ Fix 12: Purchase return corrected\n";
            $fixCount++;
        }
        
        $pdo->commit();
        
        echo "\n✓ Transaction committed - {$fixCount} fixes applied\n\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        die("✗ Error applying fixes: " . $e->getMessage() . "\n");
    }
}

// ============================================================================
// STEP 5: Verify Results
// ============================================================================
echo "========================================\n";
echo "STEP 5: VERIFICATION\n";
echo "========================================\n\n";

// Check final balance
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status='active' THEN (credit-debit) ELSE 0 END) as final_balance
    FROM ledgers 
    WHERE contact_id={$supplier_id} AND contact_type='supplier'
");
$result = $stmt->fetch();
$finalBalance = $result['final_balance'];

echo "Final Balance: " . number_format($finalBalance, 2) . "\n";
echo "Expected Balance: " . number_format($expectedBalance, 2) . "\n";
echo "Difference: " . number_format(abs($finalBalance - $expectedBalance), 2) . "\n";

if (abs($finalBalance - $expectedBalance) < 1) {
    echo "✓ Status: PERFECT - PRODUCTION READY!\n\n";
    $success = true;
} else {
    echo "✗ Status: ERROR - Balance mismatch!\n\n";
    $success = false;
}

// Show outstanding purchases
echo "Outstanding Purchases:\n";
$stmt = $pdo->query("
    SELECT id, reference_no, final_total, total_paid, total_due, payment_status 
    FROM purchases 
    WHERE supplier_id={$supplier_id} AND payment_status IN ('Due','Partial')
    ORDER BY id
");

$totalDue = 0;
while ($row = $stmt->fetch()) {
    echo "  - {$row['reference_no']} (ID {$row['id']}): " . number_format($row['total_due'], 2) . " ({$row['payment_status']})\n";
    $totalDue += $row['total_due'];
}
echo "\nTotal Due: " . number_format($totalDue, 2) . "\n";

// Show summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM purchases WHERE supplier_id={$supplier_id}");
$total = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as paid FROM purchases WHERE supplier_id={$supplier_id} AND payment_status='Paid'");
$paid = $stmt->fetch()['paid'];

$stmt = $pdo->query("SELECT COUNT(*) as due FROM purchases WHERE supplier_id={$supplier_id} AND payment_status IN ('Due','Partial')");
$due = $stmt->fetch()['due'];

echo "Total Purchases: {$total}\n";
echo "Paid Purchases: {$paid}\n";
echo "Due Purchases: {$due}\n";

if ($success) {
    echo "\n✓ ✓ ✓ FIX COMPLETE - SUPPLIER 4 BALANCED! ✓ ✓ ✓\n";
} else {
    echo "\n✗ ✗ ✗ ERROR - CONTACT SUPPORT! ✗ ✗ ✗\n";
}

echo "========================================\n\n";
