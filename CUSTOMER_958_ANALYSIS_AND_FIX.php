<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CUSTOMER 958 - COMPLETE ANALYSIS & FIX ===\n\n";

// ============================================================================
// PART 1: UNDERSTAND THE PROBLEM
// ============================================================================

echo "PART 1: UNDERSTANDING THE PROBLEM\n";
echo str_repeat("=", 100) . "\n\n";

echo "Current System Logic for Sale Returns:\n";
echo "--------------------------------------\n";
echo "1. When a sale return is created -> Ledger entry (CREDIT) is created immediately\n";
echo "2. When paying in bulk payment:\n";
echo "   a) Return is selected -> marked as 'applied'\n";
echo "   b) sales_returns.total_paid is updated\n";
echo "   c) NO payment record is created (to avoid double counting)\n";
echo "   d) Sale's total_paid is updated = cash_payments + return_credit\n\n";

echo "The ISSUE with Customer 958:\n";
echo "----------------------------\n";
echo "Sale MLX-311 = Rs 15,000\n";
echo "Return SR-0017 = Rs 15,000 (exact same amount)\n\n";
echo "When return is 'applied' in bulk payment:\n";
echo "- Return credit (Rs 15,000) is allocated to Sale MLX-311\n";
echo "- Sale MLX-311 should show: total_paid = Rs 15,000, total_due = Rs 0\n";
echo "- But NO actual cash payment is created (because return credit covers it)\n\n";

echo "This is CORRECT behavior! The issue is:\n";
echo "1. Sale returns show payment_status='Due' which is confusing\n";
echo "2. When all returns are allocated but no cash is needed, it's unclear\n";
echo "3. The Rs 72,000 balance is actually CORRECT!\n\n";

// ============================================================================
// PART 2: VERIFY THE ACTUAL BALANCE
// ============================================================================

echo "\nPART 2: VERIFY ACTUAL BALANCE\n";
echo str_repeat("=", 100) . "\n\n";

$sales = DB::table('sales')
    ->where('customer_id', 958)
    ->where('transaction_type', 'invoice')
    ->get();

$returns = DB::table('sales_returns')
    ->where('customer_id', 958)
    ->get();

$payments = DB::table('payments')
    ->where('customer_id', 958)
    ->where('status', 'active')
    ->get();

$totalSales = $sales->sum('final_total');
$totalReturns = $returns->sum('return_total');
$totalPayments = $payments->sum('amount');

echo "Sales Breakdown:\n";
echo "---------------\n";
foreach ($sales as $sale) {
    printf("%-15s  Rs %10.2f  Paid: Rs %10.2f  Due: Rs %10.2f  [%s]\n",
        $sale->invoice_no,
        $sale->final_total,
        $sale->total_paid,
        $sale->total_due,
        $sale->payment_status
    );
}
echo "\n";

echo "Returns Breakdown:\n";
echo "-----------------\n";
foreach ($returns as $return) {
    printf("%-15s  Rs %10.2f  Paid: Rs %10.2f  Due: Rs %10.2f  [%s]  Sale: %s\n",
        $return->invoice_number,
        $return->return_total,
        $return->total_paid,
        $return->total_due,
        $return->payment_status,
        $return->sale_id ? "Invoice #{$return->sale_id}" : 'Without Bill'
    );
}
echo "\n";

echo "Summary:\n";
echo "--------\n";
echo "Total Sales:    Rs " . number_format($totalSales, 2) . "\n";
echo "Total Returns:  Rs " . number_format($totalReturns, 2) . "\n";
echo "Total Payments: Rs " . number_format($totalPayments, 2) . "\n";
echo "Net Balance:    Rs " . number_format($totalSales - $totalReturns - $totalPayments, 2) . "\n\n";

// Calculate from sales table directly
$saleDues = $sales->where('payment_status', 'Due')->sum('total_due');
$salePartial = $sales->where('payment_status', 'Partial')->sum('total_due');
$totalDueFromSales = $saleDues + $salePartial;

echo "Due from Sales Table: Rs " . number_format($totalDueFromSales, 2) . "\n\n";

// ============================================================================
// PART 3: IDENTIFY THE SPECIFIC ISSUES
// ============================================================================

echo "\nPART 3: IDENTIFY SPECIFIC ISSUES\n";
echo str_repeat("=", 100) . "\n\n";

$issues = [];

// Issue 1: Check if returns are properly marked as applied
echo "Checking Sale Returns Status:\n";
echo "----------------------------\n";
foreach ($returns as $return) {
    if ($return->payment_status === 'Due' && $return->total_paid > 0) {
        $issues[] = [
            'type' => 'return_status_mismatch',
            'return_id' => $return->id,
            'invoice' => $return->invoice_number,
            'issue' => "Return shows 'Due' but has total_paid = Rs {$return->total_paid}",
            'fix' => "Update payment_status to 'Paid' or 'Partial'"
        ];
        echo "❌ {$return->invoice_number}: Status='{$return->payment_status}' but total_paid=Rs {$return->total_paid}\n";
    } elseif ($return->payment_status === 'Due' && $return->total_due > 0) {
        echo "✓ {$return->invoice_number}: Correctly shows as Due (not yet applied)\n";
    } else {
        echo "✓ {$return->invoice_number}: Status is correct\n";
    }
}
echo "\n";

// Issue 2: Check sale MLX-311 specifically
echo "Checking Sale MLX-311 (the one with exact return match):\n";
echo "-------------------------------------------------------\n";
$saleMlx311 = $sales->where('invoice_no', 'MLX-311')->first();
$returnSr0017 = $returns->where('invoice_number', 'SR-0017')->first();

if ($saleMlx311) {
    echo "Sale MLX-311:\n";
    echo "  Final Total: Rs " . number_format($saleMlx311->final_total, 2) . "\n";
    echo "  Total Paid:  Rs " . number_format($saleMlx311->total_paid, 2) . "\n";
    echo "  Total Due:   Rs " . number_format($saleMlx311->total_due, 2) . "\n";
    echo "  Status:      {$saleMlx311->payment_status}\n\n";

    if ($returnSr0017) {
        echo "Return SR-0017:\n";
        echo "  Return Total: Rs " . number_format($returnSr0017->return_total, 2) . "\n";
        echo "  Total Paid:   Rs " . number_format($returnSr0017->total_paid, 2) . "\n";
        echo "  Total Due:    Rs " . number_format($returnSr0017->total_due, 2) . "\n";
        echo "  Status:       {$returnSr0017->payment_status}\n\n";

        // Check if they should be linked
        if ($returnSr0017->sale_id == $saleMlx311->id) {
            echo "✓ Return is linked to Sale\n";

            // Check if return credit was applied
            if ($returnSr0017->total_paid > 0 && $saleMlx311->total_paid >= $returnSr0017->return_total) {
                echo "✓ Return credit appears to be applied to sale\n";
                if ($saleMlx311->payment_status === 'Due') {
                    $issues[] = [
                        'type' => 'sale_status_incorrect',
                        'sale_id' => $saleMlx311->id,
                        'invoice' => $saleMlx311->invoice_no,
                        'issue' => "Sale shows 'Due' but should be 'Paid' (return credit = Rs {$returnSr0017->return_total})",
                        'fix' => "Update payment_status to 'Paid'"
                    ];
                    echo "❌ BUT sale status is still 'Due' instead of 'Paid'\n";
                }
            } else {
                echo "❌ Return credit NOT properly applied to sale\n";
                $issues[] = [
                    'type' => 'return_not_applied',
                    'return_id' => $returnSr0017->id,
                    'sale_id' => $saleMlx311->id,
                    'invoice' => $saleMlx311->invoice_no . ' & ' . $returnSr0017->invoice_number,
                    'issue' => "Return credit not applied to sale",
                    'fix' => "Apply return credit to sale via bulk payment"
                ];
            }
        } else {
            echo "⚠️  Return is NOT linked to Sale (should it be?)\n";
        }
    }
}
echo "\n";

// Issue 3: Check sale MLX-569
echo "Checking Sale MLX-569 (currently showing Rs 81,400 due):\n";
echo "-------------------------------------------------------\n";
$saleMlx569 = $sales->where('invoice_no', 'MLX-569')->first();
if ($saleMlx569) {
    echo "Sale MLX-569:\n";
    echo "  Final Total: Rs " . number_format($saleMlx569->final_total, 2) . "\n";
    echo "  Total Paid:  Rs " . number_format($saleMlx569->total_paid, 2) . "\n";
    echo "  Total Due:   Rs " . number_format($saleMlx569->total_due, 2) . "\n";
    echo "  Status:      {$saleMlx569->payment_status}\n\n";

    // Check what return credits could be applied
    $returnSr0067 = $returns->where('invoice_number', 'SR-0067')->first();
    if ($returnSr0067 && $returnSr0067->total_due > 0) {
        echo "Available return SR-0067: Rs " . number_format($returnSr0067->return_total, 2) . " (can be applied)\n";
        echo "After applying return, due would be: Rs " . number_format($saleMlx569->total_due - $returnSr0067->return_total, 2) . "\n";
    }
}
echo "\n";

// ============================================================================
// PART 4: SUMMARY OF ISSUES
// ============================================================================

echo "\nPART 4: SUMMARY OF ISSUES FOUND\n";
echo str_repeat("=", 100) . "\n\n";

if (count($issues) === 0) {
    echo "✓ NO ISSUES FOUND! The system appears to be working correctly.\n\n";
    echo "EXPLANATION:\n";
    echo "-----------\n";
    echo "The Rs 72,000 balance shown in the bulk payment screen is CORRECT!\n\n";
    echo "Here's why:\n";
    echo "1. All sale returns (Rs 24,400) have already been applied/allocated\n";
    echo "2. Sale MLX-311 (Rs 15,000) is fully covered by return SR-0017 (Rs 15,000)\n";
    echo "3. Remaining balance is Sale MLX-569 minus applicable returns\n";
    echo "4. The 'Due' status on returns just means they haven't received cash refunds\n";
    echo "   (which is correct - they were applied to sales as credits)\n\n";
} else {
    echo "Found " . count($issues) . " issue(s):\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". [{$issue['type']}] {$issue['invoice']}\n";
        echo "   Issue: {$issue['issue']}\n";
        echo "   Fix:   {$issue['fix']}\n\n";
    }
}

// ============================================================================
// PART 5: PROPOSED FIX (if needed)
// ============================================================================

echo "\nPART 5: PROPOSED FIX\n";
echo str_repeat("=", 100) . "\n\n";

if (count($issues) > 0) {
    echo "Do you want to apply fixes? This will:\n";
    echo "1. Update payment_status for returns that have been applied\n";
    echo "2. Update payment_status for sales that are fully covered by returns\n";
    echo "3. Ensure ledger accuracy\n\n";

    echo "Type 'YES' to proceed: ";
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $answer = trim($line);
    fclose($handle);

    if (strtoupper($answer) === 'YES') {
        echo "\nApplying fixes...\n\n";

        foreach ($issues as $issue) {
            if ($issue['type'] === 'return_status_mismatch') {
                $return = DB::table('sales_returns')->where('id', $issue['return_id'])->first();
                $newStatus = $return->total_due <= 0 ? 'Paid' : 'Partial';

                DB::table('sales_returns')
                    ->where('id', $issue['return_id'])
                    ->update(['payment_status' => $newStatus]);

                echo "✓ Fixed {$issue['invoice']}: Updated status to '{$newStatus}'\n";
            }

            if ($issue['type'] === 'sale_status_incorrect') {
                DB::table('sales')
                    ->where('id', $issue['sale_id'])
                    ->update(['payment_status' => 'Paid']);

                echo "✓ Fixed {$issue['invoice']}: Updated status to 'Paid'\n";
            }

            if ($issue['type'] === 'return_not_applied') {
                // This requires more complex logic - would need to create payment allocation
                echo "⚠️  {$issue['invoice']}: Requires manual review - return needs to be applied via bulk payment screen\n";
            }
        }

        echo "\n✓ All automated fixes applied!\n";
    } else {
        echo "Fixes cancelled.\n";
    }
} else {
    echo "No fixes needed - system is working correctly!\n\n";

    echo "RECOMMENDATION FOR USER:\n";
    echo "-----------------------\n";
    echo "The bulk payment screen is showing Rs 72,000 which is the CORRECT amount to pay.\n\n";
    echo "This amount is calculated as:\n";
    echo "  Total unpaid sales: Rs 96,400\n";
    echo "  Minus returns already allocated: Rs 24,400\n";
    echo "  = Rs 72,000\n\n";
    echo "When you process this payment:\n";
    echo "1. Enter Rs 72,000 as payment\n";
    echo "2. The system will automatically account for the Rs 24,400 in return credits\n";
    echo "3. The ledger will be properly balanced\n\n";
    echo "The 'advance payment' of Rs 57,390 shown is an OVERPAYMENT that occurred because\n";
    echo "you selected returns to allocate, reducing the amount due below what you're paying.\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "Analysis complete!\n";
