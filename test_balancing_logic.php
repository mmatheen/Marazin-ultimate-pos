<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\UnifiedLedgerService;
use App\Models\Customer;

echo "=== TESTING NEW BALANCING OPENING BALANCE LOGIC ===\n\n";

$customerId = 2; // Aasath
$ledgerService = new UnifiedLedgerService();

// Get current customer info
$customer = Customer::find($customerId);
echo "Customer: {$customer->name}\n";
echo "Current Opening Balance in Customer Table: {$customer->opening_balance}\n\n";

echo "SCENARIO: Simulating opening balance edit from 5,000 to 9,000\n";
echo "This will demonstrate the new balancing entries:\n\n";

echo "Expected Ledger Entries:\n";
echo "1. ✅ Original opening balance: 5,000.00 (debit) - keeps original entry\n";
echo "2. 🔄 Balancing entry: 5,000.00 (credit) - cancels original\n";
echo "3. ✅ New opening balance: 9,000.00 (debit) - new correct amount\n";
echo "4. Net Effect: 9,000.00 debit (correct final balance)\n\n";

// Test the new method (without actually executing to avoid real data changes)
echo "=== NEW METHOD LOGIC EXPLANATION ===\n";
echo "recordOpeningBalanceAdjustment(contactId: {$customerId}, contactType: 'customer', oldAmount: 5000, newAmount: 9000)\n\n";

echo "Step 1: Create balancing credit entry\n";
echo "  - Reference: OB-CUSTOMER-{$customerId}-BAL-[timestamp]\n";
echo "  - Type: opening_balance_adjustment\n";
echo "  - Amount: -5000 (creates credit to cancel old debit)\n";
echo "  - Notes: 'Opening Balance Correction - Balancing entry for previous opening balance Rs.5,000.00'\n\n";

echo "Step 2: Create new opening balance entry\n";
echo "  - Reference: OB-CUSTOMER-{$customerId}\n";
echo "  - Type: opening_balance\n";
echo "  - Amount: 9000 (creates new debit)\n";
echo "  - Notes: 'Opening Balance for Customer: {$customer->name}'\n\n";

echo "Step 3: Update customer table\n";
echo "  - customers.opening_balance = 9000\n\n";

echo "BENEFITS:\n";
echo "✅ Clean audit trail - all entries remain active and visible\n";
echo "✅ Balanced books - total debits and credits balance correctly\n";
echo "✅ Clear transaction history - users can see exactly what happened\n";
echo "✅ No 'reversed' status - all entries are legitimate accounting entries\n";
echo "✅ Proper accounting practice - follows double-entry principles\n\n";

echo "AUDIT TRAIL VISIBILITY:\n";
echo "Users will see:\n";
echo "- Original opening balance (5,000 debit)\n";
echo "- Correction entry (5,000 credit) - clearly marked as balancing\n";
echo "- New opening balance (9,000 debit)\n";
echo "- Running balance progression: 5000 → 0 → 9000\n";
echo "- Final balance: 9,000 (correct)\n\n";

echo "This approach provides complete transparency while maintaining proper accounting principles!\n";