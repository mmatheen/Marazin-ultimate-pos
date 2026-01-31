<?php
/**
 * COMPLETE FIX FOR SUPPLIER 4 - CLASSIC BULB ISRATH BOSS
 * This single script fixes all ledger balance issues for Supplier ID 4
 * 
 * Issues Fixed:
 * 1. Opening balance entries causing inflated balance
 * 2. Incorrect purchase return entry (PRT001)
 * 3. Duplicate payments for PUR007
 * 4. Incorrect total_paid for PUR007
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "COMPLETE FIX FOR SUPPLIER 4 - CLASSIC BULB ISRATH BOSS\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// LOAD DATABASE CONFIGURATION
// ============================================================================
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

$host = $dbConfig['DB_HOST'] ?? '127.0.0.1';
$port = $dbConfig['DB_PORT'] ?? '3306';
$database = $dbConfig['DB_DATABASE'] ?? 'ctc-db';
$username = $dbConfig['DB_USERNAME'] ?? 'root';
$password = $dbConfig['DB_PASSWORD'] ?? '';

echo "Database: {$database}\n";
echo "Host: {$host}\n\n";

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

$supplierId = 4;
$expectedBalance = 1064720.00;

// ============================================================================
// STEP 1: CHECK CURRENT STATE
// ============================================================================
echo str_repeat("-", 80) . "\n";
echo "STEP 1: CHECKING CURRENT STATE\n";
echo str_repeat("-", 80) . "\n\n";

$stmt = $pdo->prepare("
    SELECT SUM(CASE WHEN status='active' THEN (credit - debit) ELSE 0 END) as ledger_balance
    FROM ledgers 
    WHERE contact_id = ?
");
$stmt->execute([$supplierId]);
$currentBalance = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT SUM(total_due) FROM purchases WHERE supplier_id = ?");
$stmt->execute([$supplierId]);
$purchasesDue = $stmt->fetchColumn() ?? 0;

$difference = abs($currentBalance - $expectedBalance);

echo "Current Ledger Balance:  " . number_format($currentBalance, 2) . "\n";
echo "Expected Balance:        " . number_format($expectedBalance, 2) . "\n";
echo "Purchases Due:           " . number_format($purchasesDue, 2) . "\n";
echo "Difference from Expected: " . number_format($difference, 2) . "\n\n";

if ($difference <= 1) {
    echo "✓ ✓ ✓ ALREADY CORRECT! ✓ ✓ ✓\n\n";
    echo "Supplier 4 balance is already at the expected " . number_format($expectedBalance, 2) . "\n\n";
    exit(0);
}

// ============================================================================
// STEP 2: IDENTIFY ISSUES
// ============================================================================
echo str_repeat("-", 80) . "\n";
echo "STEP 2: IDENTIFYING ISSUES\n";
echo str_repeat("-", 80) . "\n\n";

$issues = [];

// Issue 1: Opening balance entries
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt, 
           SUM(CASE WHEN status='active' THEN credit ELSE 0 END) as total_credit,
           SUM(CASE WHEN status='active' THEN debit ELSE 0 END) as total_debit
    FROM ledgers 
    WHERE contact_id = ?
      AND transaction_type IN ('opening_balance', 'opening_balance_adjustment')
      AND status = 'active'
");
$stmt->execute([$supplierId]);
$openingBalance = $stmt->fetch();

if ($openingBalance['cnt'] > 0) {
    $netEffect = $openingBalance['total_credit'] - $openingBalance['total_debit'];
    $issues[] = [
        'name' => 'Opening Balance Entries',
        'count' => $openingBalance['cnt'],
        'effect' => $netEffect,
        'description' => "Active opening balance entries inflating balance by " . number_format($netEffect, 2)
    ];
    echo "✗ Issue 1: {$openingBalance['cnt']} active opening balance entries\n";
    echo "   Effect: " . number_format($netEffect, 2) . "\n\n";
}

// Issue 2: Purchase return (PRT001)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt, SUM(debit) as total_debit
    FROM ledgers 
    WHERE contact_id = ?
      AND reference_no = 'PRT001'
      AND transaction_type = 'purchase_return'
      AND status = 'active'
      AND debit > 0
");
$stmt->execute([$supplierId]);
$purchaseReturn = $stmt->fetch();

if ($purchaseReturn['cnt'] > 0) {
    $issues[] = [
        'name' => 'Incorrect Purchase Return',
        'count' => $purchaseReturn['cnt'],
        'effect' => $purchaseReturn['total_debit'],
        'description' => "Purchase return PRT001 has wrong debit (should be credit or reversed)"
    ];
    echo "✗ Issue 2: Purchase return PRT001 with incorrect debit\n";
    echo "   Effect: " . number_format($purchaseReturn['total_debit'], 2) . "\n\n";
}

// Issue 3: Duplicate PUR007 payments
$stmt = $pdo->prepare("
    SELECT COUNT(*) as cnt, SUM(amount) as total_amount
    FROM payments 
    WHERE supplier_id = ?
      AND reference_no = 'PUR007'
      AND status = 'active'
");
$stmt->execute([$supplierId]);
$pur007Payments = $stmt->fetch();

$stmt = $pdo->query("SELECT final_total FROM purchases WHERE supplier_id=4 AND reference_no='PUR007'");
$pur007Total = $stmt->fetchColumn();

if ($pur007Payments['cnt'] > 1 || $pur007Payments['total_amount'] != $pur007Total) {
    $issues[] = [
        'name' => 'PUR007 Payment Issues',
        'count' => $pur007Payments['cnt'],
        'effect' => $pur007Payments['total_amount'] - $pur007Total,
        'description' => "PUR007 has {$pur007Payments['cnt']} active payments totaling " . 
                        number_format($pur007Payments['total_amount'], 2) . 
                        " but purchase is only " . number_format($pur007Total, 2)
    ];
    echo "✗ Issue 3: PUR007 has {$pur007Payments['cnt']} active payments\n";
    echo "   Total payments: " . number_format($pur007Payments['total_amount'], 2) . "\n";
    echo "   Purchase total: " . number_format($pur007Total, 2) . "\n";
    echo "   Overpayment: " . number_format($pur007Payments['total_amount'] - $pur007Total, 2) . "\n\n";
}

if (count($issues) == 0) {
    echo "No specific fixable issues identified.\n";
    echo "Manual investigation may be required.\n\n";
    exit(0);
}

echo "Found " . count($issues) . " issues to fix\n\n";

// ============================================================================
// STEP 3: CONFIRM FIX
// ============================================================================
echo str_repeat("-", 80) . "\n";
echo "FIX PLAN\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($issues as $idx => $issue) {
    $num = $idx + 1;
    echo "Fix {$num}: {$issue['name']}\n";
    echo "  {$issue['description']}\n\n";
}

echo "Expected result: Ledger balance = " . number_format($expectedBalance, 2) . "\n\n";

echo "Do you want to proceed with these fixes? (yes/no): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
fclose($handle);

if (strtolower($input) !== 'yes' && strtolower($input) !== 'y') {
    echo "\nNo changes made. Exiting.\n\n";
    exit(0);
}

// ============================================================================
// STEP 4: APPLY FIXES
// ============================================================================
echo "\n" . str_repeat("-", 80) . "\n";
echo "APPLYING FIXES\n";
echo str_repeat("-", 80) . "\n\n";

try {
    $pdo->beginTransaction();
    
    $fixCount = 0;
    
    // Fix 1: Reverse opening balance entries
    if ($openingBalance['cnt'] > 0) {
        echo "Fix 1: Reversing opening balance entries...\n";
        $stmt = $pdo->prepare("
            UPDATE ledgers 
            SET 
                status = 'reversed',
                notes = CONCAT(COALESCE(notes, ''), ' [AUTO-FIX: ', NOW(), ']'),
                updated_at = NOW()
            WHERE contact_id = ?
              AND transaction_type IN ('opening_balance', 'opening_balance_adjustment')
              AND status = 'active'
        ");
        $stmt->execute([$supplierId]);
        $count = $stmt->rowCount();
        echo "  ✓ Reversed {$count} opening balance entries\n\n";
        $fixCount += $count;
    }
    
    // Fix 2: Reverse purchase return PRT001
    if ($purchaseReturn['cnt'] > 0) {
        echo "Fix 2: Reversing incorrect purchase return...\n";
        $stmt = $pdo->prepare("
            UPDATE ledgers 
            SET 
                status = 'reversed',
                notes = CONCAT(COALESCE(notes, ''), ' [AUTO-FIX: Incorrect entry]'),
                updated_at = NOW()
            WHERE contact_id = ?
              AND reference_no = 'PRT001'
              AND transaction_type = 'purchase_return'
              AND status = 'active'
        ");
        $stmt->execute([$supplierId]);
        $count = $stmt->rowCount();
        echo "  ✓ Reversed {$count} purchase return entry\n\n";
        $fixCount += $count;
    }
    
    // Fix 3: Fix PUR007 duplicate payments
    if ($pur007Payments['cnt'] > 1) {
        echo "Fix 3: Fixing PUR007 payments...\n";
        
        // Get all payment IDs for PUR007
        $stmt = $pdo->prepare("
            SELECT id FROM payments 
            WHERE supplier_id = ? 
              AND reference_no = 'PUR007' 
              AND status = 'active'
            ORDER BY id
        ");
        $stmt->execute([$supplierId]);
        $paymentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($paymentIds) > 1) {
            // Keep the first one, delete the rest
            $keepId = $paymentIds[0];
            $deleteIds = array_slice($paymentIds, 1);
            
            $placeholders = str_repeat('?,', count($deleteIds) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'deleted', updated_at = NOW()
                WHERE id IN ({$placeholders})
            ");
            $stmt->execute($deleteIds);
            $count = $stmt->rowCount();
            echo "  ✓ Deleted {$count} duplicate payments (kept ID {$keepId})\n";
            $fixCount += $count;
        }
        
        // Update total_paid in purchases table
        $stmt = $pdo->prepare("
            UPDATE purchases 
            SET total_paid = ?
            WHERE supplier_id = ? AND reference_no = 'PUR007'
        ");
        $stmt->execute([$pur007Total, $supplierId]);
        echo "  ✓ Updated PUR007 total_paid to " . number_format($pur007Total, 2) . "\n\n";
        $fixCount++;
    }
    
    $pdo->commit();
    
    echo "✓ Successfully applied {$fixCount} fixes\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n✗ Error applying fixes: " . $e->getMessage() . "\n\n";
    exit(1);
}

// ============================================================================
// STEP 5: VERIFICATION
// ============================================================================
echo str_repeat("-", 80) . "\n";
echo "VERIFICATION\n";
echo str_repeat("-", 80) . "\n\n";

$stmt = $pdo->prepare("
    SELECT SUM(CASE WHEN status='active' THEN (credit - debit) ELSE 0 END) as ledger_balance
    FROM ledgers 
    WHERE contact_id = ?
");
$stmt->execute([$supplierId]);
$newBalance = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT SUM(total_due) FROM purchases WHERE supplier_id = ?");
$stmt->execute([$supplierId]);
$newPurchasesDue = $stmt->fetchColumn() ?? 0;

$newDifference = abs($newBalance - $expectedBalance);

echo "After Fix:\n";
echo "  Ledger Balance:  " . number_format($newBalance, 2);
if ($newDifference <= 1) echo " ✓";
echo "\n";
echo "  Expected:        " . number_format($expectedBalance, 2) . "\n";
echo "  Purchases Due:   " . number_format($newPurchasesDue, 2) . "\n";
echo "  Difference:      " . number_format($newDifference, 2);
if ($newDifference <= 1) echo " ✓";
echo "\n\n";

if ($newDifference <= 1) {
    echo str_repeat("=", 80) . "\n";
    echo "✓ ✓ ✓ SUCCESS! SUPPLIER 4 IS NOW CORRECTLY BALANCED! ✓ ✓ ✓\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Show outstanding purchases
    $stmt = $pdo->prepare("
        SELECT reference_no, final_total, total_paid, total_due, payment_status
        FROM purchases 
        WHERE supplier_id = ? 
          AND payment_status IN ('Due', 'Partial')
        ORDER BY purchase_date
    ");
    $stmt->execute([$supplierId]);
    $outstanding = $stmt->fetchAll();
    
    if (count($outstanding) > 0) {
        echo "Outstanding Purchases:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-12s %12s %12s %12s %-10s\n", "Ref No", "Total", "Paid", "Due", "Status");
        echo str_repeat("-", 80) . "\n";
        
        $totalDue = 0;
        foreach ($outstanding as $p) {
            printf("%-12s %12s %12s %12s %-10s\n",
                $p['reference_no'],
                number_format($p['final_total'], 2),
                number_format($p['total_paid'], 2),
                number_format($p['total_due'], 2),
                $p['payment_status']
            );
            $totalDue += $p['total_due'];
        }
        
        echo str_repeat("-", 80) . "\n";
        echo "Total Outstanding: " . number_format($totalDue, 2) . "\n\n";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE supplier_id = ?");
    $stmt->execute([$supplierId]);
    $totalPurchases = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE supplier_id = ? AND payment_status = 'Paid'");
    $stmt->execute([$supplierId]);
    $paidPurchases = $stmt->fetchColumn();
    
    echo "Summary:\n";
    echo "  Total Purchases: {$totalPurchases}\n";
    echo "  Paid: {$paidPurchases}\n";
    echo "  Due/Partial: " . (count($outstanding)) . "\n\n";
    
} else {
    echo "⚠ Balance still has a difference of " . number_format($newDifference, 2) . "\n";
    echo "Additional manual investigation may be required.\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "FIX COMPLETE\n";
echo str_repeat("=", 80) . "\n\n";
