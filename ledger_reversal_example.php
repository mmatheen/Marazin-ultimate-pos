<?php
/**
 * LEDGER REVERSAL LOGIC EXAMPLE - 10 RECORDS
 * This demonstrates the correct reversal accounting when records are edited/deleted
 */

echo "=== PERFECT REVERSAL ACCOUNTING EXAMPLE ===\n\n";

// CUSTOMER EXAMPLE (Customer ID: 5)
echo "📋 CUSTOMER LEDGER EXAMPLE:\n";
echo "Customer: John Doe (ID: 5)\n\n";

$customerLedger = [
    // Initial transactions
    ['id' => 1, 'date' => '2024-01-01', 'type' => 'opening_balance', 'ref' => 'OB-CUSTOMER-5', 'debit' => 1000, 'credit' => 0, 'status' => 'active', 'notes' => 'Opening balance'],
    ['id' => 2, 'date' => '2024-01-05', 'type' => 'sale', 'ref' => 'INV-001', 'debit' => 5000, 'credit' => 0, 'status' => 'active', 'notes' => 'Sale invoice INV-001'],
    ['id' => 3, 'date' => '2024-01-10', 'type' => 'payment', 'ref' => 'PAY-001', 'debit' => 0, 'credit' => 3000, 'status' => 'active', 'notes' => 'Payment received'],
    
    // EDIT HAPPENS: INV-001 amount changed from 5000 to 4500
    // Step 1: Mark original sale as reversed
    ['id' => 2, 'date' => '2024-01-05', 'type' => 'sale', 'ref' => 'INV-001', 'debit' => 5000, 'credit' => 0, 'status' => 'reversed', 'notes' => 'Sale invoice INV-001 [REVERSED: Sale edited on 2024-01-15]'],
    
    // Step 2: Create reversal entry (should be status='reversed' to not affect balance)
    ['id' => 4, 'date' => '2024-01-15', 'type' => 'sale_adjustment', 'ref' => 'INV-001-REV-123', 'debit' => 0, 'credit' => 5000, 'status' => 'reversed', 'notes' => 'REVERSAL: Sale Edit - Cancel previous amount Rs.5,000.00'],
    
    // Step 3: Create new sale entry with correct amount
    ['id' => 5, 'date' => '2024-01-15', 'type' => 'sale', 'ref' => 'INV-001', 'debit' => 4500, 'credit' => 0, 'status' => 'active', 'notes' => 'Sale Edit - New Amount Rs.4,500.00 | Decrease: Rs500.00'],
    
    // More transactions
    ['id' => 6, 'date' => '2024-01-20', 'type' => 'sale', 'ref' => 'INV-002', 'debit' => 2000, 'credit' => 0, 'status' => 'active', 'notes' => 'Sale invoice INV-002'],
    ['id' => 7, 'date' => '2024-01-25', 'type' => 'payment', 'ref' => 'PAY-002', 'debit' => 0, 'credit' => 1500, 'status' => 'active', 'notes' => 'Payment received'],
];

echo "CUSTOMER LEDGER ENTRIES:\n";
echo str_pad('ID', 4) . str_pad('Date', 12) . str_pad('Type', 20) . str_pad('Reference', 15) . str_pad('Debit', 8) . str_pad('Credit', 8) . str_pad('Status', 10) . "Notes\n";
echo str_repeat('-', 100) . "\n";

$activeBalance = 0;
$fullAuditBalance = 0;

foreach ($customerLedger as $entry) {
    echo str_pad($entry['id'], 4) . 
         str_pad($entry['date'], 12) . 
         str_pad($entry['type'], 20) . 
         str_pad($entry['ref'], 15) . 
         str_pad($entry['debit'], 8) . 
         str_pad($entry['credit'], 8) . 
         str_pad($entry['status'], 10) . 
         $entry['notes'] . "\n";
    
    // Calculate running balances
    if ($entry['status'] === 'active') {
        $activeBalance += ($entry['debit'] - $entry['credit']);
    }
    $fullAuditBalance += ($entry['debit'] - $entry['credit']); // All entries for audit
}

echo str_repeat('-', 100) . "\n";
echo "💰 BALANCE CALCULATIONS:\n";
echo "   Active Balance (Business Logic): Rs " . number_format($activeBalance, 2) . "\n";
echo "   Full Audit Balance (All entries): Rs " . number_format($fullAuditBalance, 2) . "\n\n";

// SUPPLIER EXAMPLE (Supplier ID: 3)
echo "📋 SUPPLIER LEDGER EXAMPLE:\n";
echo "Supplier: ABC Supplies (ID: 3)\n\n";

$supplierLedger = [
    // Initial transactions
    ['id' => 11, 'date' => '2024-01-01', 'type' => 'opening_balance', 'ref' => 'OB-SUPPLIER-3', 'debit' => 0, 'credit' => 2000, 'status' => 'active', 'notes' => 'Opening balance'],
    ['id' => 12, 'date' => '2024-01-05', 'type' => 'purchase', 'ref' => 'PUR-001', 'debit' => 0, 'credit' => 8000, 'status' => 'active', 'notes' => 'Purchase PUR-001'],
    ['id' => 13, 'date' => '2024-01-10', 'type' => 'payments', 'ref' => 'PAY-SUP-001', 'debit' => 4000, 'credit' => 0, 'status' => 'active', 'notes' => 'Payment to supplier'],
    
    // DELETE HAPPENS: Payment PAY-SUP-001 is deleted (amount 4000)
    // Step 1: Mark original payment as reversed
    ['id' => 13, 'date' => '2024-01-10', 'type' => 'payments', 'ref' => 'PAY-SUP-001', 'debit' => 4000, 'credit' => 0, 'status' => 'reversed', 'notes' => 'Payment to supplier [REVERSED: Payment deleted on 2024-01-15]'],
    
    // Step 2: Create reversal entry (should be status='reversed' to not affect balance)
    ['id' => 14, 'date' => '2024-01-15', 'type' => 'payment_adjustment', 'ref' => 'PAY-SUP-001-REV-456', 'debit' => 0, 'credit' => 4000, 'status' => 'reversed', 'notes' => 'REVERSAL: Payment Deletion - Restore amount Rs.4,000.00'],
    
    // More transactions
    ['id' => 15, 'date' => '2024-01-20', 'type' => 'purchase', 'ref' => 'PUR-002', 'debit' => 0, 'credit' => 3000, 'status' => 'active', 'notes' => 'Purchase PUR-002'],
    ['id' => 16, 'date' => '2024-01-25', 'type' => 'payments', 'ref' => 'PAY-SUP-002', 'debit' => 2000, 'credit' => 0, 'status' => 'active', 'notes' => 'Payment to supplier'],
];

echo "SUPPLIER LEDGER ENTRIES:\n";
echo str_pad('ID', 4) . str_pad('Date', 12) . str_pad('Type', 20) . str_pad('Reference', 15) . str_pad('Debit', 8) . str_pad('Credit', 8) . str_pad('Status', 10) . "Notes\n";
echo str_repeat('-', 100) . "\n";

$activeBalanceSupplier = 0;
$fullAuditBalanceSupplier = 0;

foreach ($supplierLedger as $entry) {
    echo str_pad($entry['id'], 4) . 
         str_pad($entry['date'], 12) . 
         str_pad($entry['type'], 20) . 
         str_pad($entry['ref'], 15) . 
         str_pad($entry['debit'], 8) . 
         str_pad($entry['credit'], 8) . 
         str_pad($entry['status'], 10) . 
         $entry['notes'] . "\n";
    
    // For supplier: credit - debit = what we owe them
    if ($entry['status'] === 'active') {
        $activeBalanceSupplier += ($entry['credit'] - $entry['debit']);
    }
    $fullAuditBalanceSupplier += ($entry['credit'] - $entry['debit']); // All entries for audit
}

echo str_repeat('-', 100) . "\n";
echo "💰 BALANCE CALCULATIONS:\n";
echo "   Active Balance (Business Logic): Rs " . number_format($activeBalanceSupplier, 2) . "\n";
echo "   Full Audit Balance (All entries): Rs " . number_format($fullAuditBalanceSupplier, 2) . "\n\n";

echo "🎯 KEY POINTS:\n";
echo "1. Original record: status = 'reversed' (for audit trail)\n";
echo "2. Reversal entry: status = 'reversed' (doesn't affect business balance)\n";
echo "3. New record: status = 'active' (only this affects business balance)\n";
echo "4. Active balance = Only 'active' status entries count\n";
echo "5. Full audit = Shows all entries but only 'active' ones in balance calculation\n";
echo "\n=== END OF EXAMPLE ===\n";