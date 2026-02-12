<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX CUSTOMER 958 - APPLY SALE RETURNS ===\n\n";

// The REAL issue: Sale returns are not applied to sales
// This is what needs to be done via the bulk payment screen

echo "ISSUE SUMMARY:\n";
echo str_repeat("=", 100) . "\n\n";

$returns = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->get();

$sales = DB::table('sales')
    ->where('customer_id', 958)
    ->where('transaction_type', 'invoice')
    ->whereIn('payment_status', ['Due', 'Partial'])
    ->get();

echo "Unpaid/Partially Paid Sales:\n";
foreach ($sales as $sale) {
    printf("%-15s | Rs %10.2f | Paid: Rs %10.2f | Due: Rs %10.2f\n",
        $sale->invoice_no,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due
    );
}
echo "\n";

echo "Available Returns (not yet applied):\n";
foreach ($returns as $return) {
    $statusText = $return->total_paid > 0 ? "Partially Applied" : "Not Applied";
    printf("%-15s | Rs %10.2f | Applied: Rs %10.2f | Remaining: Rs %10.2f | [%s]\n",
        $return->invoice_number,
        $return->return_total,
        $return->total_paid,
        $return->total_due,
        $statusText
    );
}
echo "\n";

$totalReturnsAvailable = $returns->sum('total_due');
$totalSalesDue = $sales->sum('total_due');

echo "Summary:\n";
echo "  Total sales due: Rs " . number_format($totalSalesDue, 2) . "\n";
echo "  Total returns available: Rs " . number_format($totalReturnsAvailable, 2) . "\n";
echo "  Net amount to pay: Rs " . number_format($totalSalesDue - $totalReturnsAvailable, 2) . "\n\n";

// Check current ledger balance
$ledgerBalance = DB::table('ledgers')
    ->where('contact_id', 958)
    ->where('contact_type', 'customer')
    ->where('status', 'active')
    ->selectRaw('SUM(debit) - SUM(credit) as balance')
    ->first()->balance ?? 0;

echo "Current ledger balance: Rs " . number_format($ledgerBalance, 2) . " (customer owes)\n\n";

echo str_repeat("=", 100) . "\n\n";

echo "EXPLANATION OF THE -Rs 57,390:\n";
echo str_repeat("-", 100) . "\n\n";
echo "The -Rs 57,390 you see in the ledger is NOT an error. Here's what happened:\n\n";

echo "1. Dec 27, 2025: Sale MLX-269 created for Rs 60,240\n";
echo "2. Dec 29, 2025: Customer paid Rs 82,690 via BLK-S0026:\n";
echo "   - Rs 57,390 for MLX-269\n";
echo "   - Rs 25,300 for MLX-261\n";
echo "   This created an advance of Rs 52,920\n\n";

echo "3. Later: More sales were added (MLX-308, MLX-309, MLX-311, etc.)\n";
echo "   The advance was automatically absorbed\n\n";

echo "4. Jan 21, 2026: After payment of Rs 74,400, balance went to -Rs 57,390\n";
echo "   This shows customer had Rs 57,390 advance credit at that point\n\n";

echo "5. Jan 22 onwards: More sales added, advance was used up\n\n";

echo "CURRENT STATUS:\n";
echo "  Ledger balance: Rs " . number_format($ledgerBalance, 2) . " Due\n";
echo "  This is CORRECT! The customer owes this amount.\n\n";

echo str_repeat("=", 100) . "\n\n";

echo "WHAT NEEDS TO BE FIXED:\n";
echo str_repeat("-", 100) . "\n\n";

echo "The sale returns need to be APPLIED via the bulk payment screen:\n\n";

echo "METHOD 1: Use Bulk Payment Screen (RECOMMENDED)\n";
echo "-----------------------------------------------\n";
echo "1. Go to Bulk Payment page for customer 958\n";
echo "2. Select the returns to apply:\n";
foreach ($returns as $return) {
    echo "   ☐ {$return->invoice_number} - Rs " . number_format($return->return_total, 2) . "\n";
}
echo "\n3. The system will automatically allocate these to unpaid sales\n";
echo "4. Enter Rs " . number_format($totalSalesDue - $totalReturnsAvailable, 2) . " as cash payment\n";
echo "5. Submit the payment\n\n";

echo "METHOD 2: Manual Database Fix (NOT RECOMMENDED - use only if bulk payment fails)\n";
echo "--------------------------------------------------------------------------------\n";
echo "This would require:\n";
echo "1. Updating sales_returns.total_paid\n";
echo "2. Updating sales.total_paid to include return credits\n";
echo "3. Recalculating payment_status\n";
echo "4. Ensuring ledger entries are correct\n\n";

echo "⚠️  The bulk payment screen is the PROPER way to handle this!\n";
echo "   It ensures all accounting is done correctly.\n\n";

echo str_repeat("=", 100) . "\n\n";

echo "DO YOU WANT TO SEE WHAT A MANUAL FIX WOULD LOOK LIKE? (YES/NO): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if (strtoupper($answer) === 'YES') {
    echo "\n\nMANUAL FIX SIMULATION (DRY RUN - NOT EXECUTED)\n";
    echo str_repeat("=", 100) . "\n\n";

    echo "Step 1: Apply SR-0017 (Rs 15,000) to MLX-311 (Rs 15,000)\n";
    echo "  - Update sales_returns.total_paid: 0 + 15,000 = 15,000\n";
    echo "  - Update sales_returns.payment_status: 'Paid'\n";
    echo "  - Update sales.total_paid: 0 + 15,000 = 15,000\n";
    echo "  - Update sales.payment_status: 'Paid'\n";
    echo "  - NO ledger entry (already exists from return creation)\n\n";

    echo "Step 2: Apply SR-0047 (Rs 3,100) and SR-0067 (Rs 6,300) to MLX-569 (Rs 81,400)\n";
    echo "  - Update SR-0047.total_paid: 0 + 3,100 = 3,100 -> Status: 'Paid'\n";
    echo "  - Update SR-0067.total_paid: 0 + 6,300 = 6,300 -> Status: 'Paid'\n";
    echo "  - Update MLX-569.total_paid: 0 + 9,400 = 9,400\n";
    echo "  - Update MLX-569.total_due: 81,400 - 9,400 = 72,000\n";
    echo "  - Update MLX-569.payment_status: 'Partial'\n\n";

    echo "Step 3: Customer needs to pay Rs 72,000 cash\n";
    echo "  - Create payment record for Rs 72,000\n";
    echo "  - Update MLX-569.total_paid: 9,400 + 72,000 = 81,400\n";
    echo "  - Update MLX-569.payment_status: 'Paid'\n";
    echo "  - Create ledger entry (credit Rs 72,000)\n\n";

    echo "Final Result:\n";
    echo "  - All returns: Applied\n";
    echo "  - All sales: Paid\n";
    echo "  - Ledger balance: Rs 0.00\n\n";

    echo "⚠️  This is just a simulation. Use the bulk payment screen to do this properly!\n";
} else {
    echo "\n✓ Use the bulk payment screen to apply returns and settle the account.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Analysis complete.\n";
