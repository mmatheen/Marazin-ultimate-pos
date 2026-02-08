<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get invoice number from command line argument
$invoiceNo = $argv[1] ?? null;

if (!$invoiceNo) {
    echo "Usage: php check_and_fix_invoice.php <INVOICE_NO>\n";
    echo "Example: php check_and_fix_invoice.php MLX-598\n";
    exit(1);
}

echo "=================================================================\n";
echo "   CHECKING INVOICE: {$invoiceNo}\n";
echo "=================================================================\n\n";

// Find Sale
$sale = DB::selectOne("
    SELECT
        s.id,
        s.invoice_no,
        s.subtotal,
        s.discount_amount,
        s.discount_type,
        s.shipping_charges,
        s.final_total,
        s.total_paid,
        s.total_due,
        s.customer_id,
        s.payment_status
    FROM sales s
    WHERE s.invoice_no = ?
", [$invoiceNo]);

if (!$sale) {
    echo "❌ Invoice {$invoiceNo} not found!\n";
    exit(1);
}

// Get products
$products = DB::select("
    SELECT
        sp.id,
        sp.product_id,
        p.product_name,
        sp.quantity,
        sp.price,
        (sp.quantity * sp.price) as line_total,
        sp.discount_amount,
        sp.discount_type
    FROM sales_products sp
    LEFT JOIN products p ON sp.product_id = p.id
    WHERE sp.sale_id = ?
    ORDER BY sp.id
", [$sale->id]);

// Get ledger
$ledger = DB::selectOne("
    SELECT
        id,
        reference_no,
        transaction_date,
        debit,
        credit,
        created_by,
        contact_type
    FROM ledgers
    WHERE reference_no = ? AND transaction_type = 'sale'
", [$invoiceNo]);

// Display current status
echo "=================================================================\n";
echo "   CURRENT STATUS\n";
echo "=================================================================\n\n";

echo "1. SALE TABLE RECORD:\n";
echo "───────────────────────────────────────────────────────────────────\n";
echo "  Sale ID: {$sale->id}\n";
echo "  Invoice: {$sale->invoice_no}\n";
echo "  Customer ID: {$sale->customer_id}\n";
echo "  Payment Status: {$sale->payment_status}\n\n";

echo "  Current Subtotal:     Rs " . number_format($sale->subtotal, 2) . "\n";
echo "  Discount ({$sale->discount_type}): Rs " . number_format($sale->discount_amount ?? 0, 2) . "\n";
echo "  Shipping Charges:     Rs " . number_format($sale->shipping_charges ?? 0, 2) . "\n";
echo "  Final Total:          Rs " . number_format($sale->final_total, 2) . "\n";
echo "  Total Paid:           Rs " . number_format($sale->total_paid, 2) . "\n";
echo "  Total Due:            Rs " . number_format($sale->total_due, 2) . "\n\n";

echo "2. SALES_PRODUCTS TABLE RECORDS:\n";
echo "───────────────────────────────────────────────────────────────────\n";
if (empty($products)) {
    echo "  ❌ NO PRODUCTS FOUND!\n\n";
    $calculatedSubtotal = 0;
} else {
    $calculatedSubtotal = 0;
    foreach ($products as $index => $product) {
        echo "  Product " . ($index + 1) . ":\n";
        echo "    Name: {$product->product_name}\n";
        echo "    Quantity: " . number_format($product->quantity, 4) . "\n";
        echo "    Price: Rs " . number_format($product->price, 2) . "\n";
        echo "    Line Total: Rs " . number_format($product->line_total, 2) . "\n\n";
        $calculatedSubtotal += $product->line_total;
    }
    echo "  CALCULATED SUBTOTAL FROM PRODUCTS: Rs " . number_format($calculatedSubtotal, 2) . "\n\n";
}

echo "3. LEDGER TABLE RECORD:\n";
echo "───────────────────────────────────────────────────────────────────\n";
if ($ledger) {
    echo "  Ledger ID: {$ledger->id}\n";
    echo "  Reference: {$ledger->reference_no}\n";
    echo "  Transaction Date: {$ledger->transaction_date}\n";
    echo "  Contact Type: {$ledger->contact_type}\n";
    echo "  Created By: {$ledger->created_by}\n";
    echo "  Debit (Amount Owed): Rs " . number_format($ledger->debit, 2) . "\n";
    echo "  Credit (Payment):    Rs " . number_format($ledger->credit, 2) . "\n\n";
} else {
    echo "  ❌ NO LEDGER ENTRY FOUND!\n\n";
}

// Calculate correct values
$discountAmount = $sale->discount_amount ?? 0;
if (strtolower($sale->discount_type) === 'percentage') {
    $discountAmount = ($calculatedSubtotal * $discountAmount) / 100;
}

$shippingCharges = $sale->shipping_charges ?? 0;
$correctFinalTotal = $calculatedSubtotal - $discountAmount + $shippingCharges;
$correctTotalDue = $correctFinalTotal - $sale->total_paid;

// Check for differences
echo "=================================================================\n";
echo "   CALCULATION VERIFICATION\n";
echo "=================================================================\n\n";

echo "RECORDED IN DATABASE:\n";
echo "  Subtotal:     Rs " . number_format($sale->subtotal, 2) . "\n";
echo "  Final Total:  Rs " . number_format($sale->final_total, 2) . "\n";
echo "  Total Due:    Rs " . number_format($sale->total_due, 2) . "\n";
if ($ledger) {
    echo "  Ledger Debit: Rs " . number_format($ledger->debit, 2) . "\n";
}
echo "\n";

echo "CORRECT CALCULATION:\n";
echo "  Subtotal (from products):  Rs " . number_format($calculatedSubtotal, 2) . "\n";
echo "  - Discount:                Rs " . number_format($discountAmount, 2) . "\n";
echo "  + Shipping:                Rs " . number_format($shippingCharges, 2) . "\n";
echo "  = Final Total:             Rs " . number_format($correctFinalTotal, 2) . "\n";
echo "  - Total Paid:              Rs " . number_format($sale->total_paid, 2) . "\n";
echo "  = Total Due:               Rs " . number_format($correctTotalDue, 2) . "\n\n";

$subtotalDiff = abs($sale->subtotal - $calculatedSubtotal);
$finalTotalDiff = abs($sale->final_total - $correctFinalTotal);
$totalDueDiff = abs($sale->total_due - $correctTotalDue);
$ledgerDiff = $ledger ? abs($ledger->debit - $correctFinalTotal) : 0;

$hasIssues = false;

echo "DIFFERENCES:\n";
if ($subtotalDiff > 0.01) {
    echo "  ❌ Subtotal: Incorrect (Diff: Rs " . number_format($subtotalDiff, 2) . ")\n";
    $hasIssues = true;
} else {
    echo "  ✅ Subtotal: Correct\n";
}

if ($finalTotalDiff > 0.01) {
    echo "  ❌ Final Total: Incorrect (Diff: Rs " . number_format($finalTotalDiff, 2) . ")\n";
    $hasIssues = true;
} else {
    echo "  ✅ Final Total: Correct\n";
}

if ($totalDueDiff > 0.01) {
    echo "  ❌ Total Due: Incorrect (Diff: Rs " . number_format($totalDueDiff, 2) . ")\n";
    $hasIssues = true;
} else {
    echo "  ✅ Total Due: Correct\n";
}

if ($ledger) {
    if ($ledgerDiff > 0.01) {
        echo "  ❌ Ledger: Incorrect (Diff: Rs " . number_format($ledgerDiff, 2) . ")\n";
        $hasIssues = true;
    } else {
        echo "  ✅ Ledger: Correct\n";
    }
}
echo "\n";

if (!$hasIssues) {
    echo "=================================================================\n";
    echo "✅ INVOICE {$invoiceNo} IS ALREADY CORRECT - NO CHANGES NEEDED\n";
    echo "=================================================================\n";
    exit(0);
}

// Issues found - ask for confirmation to fix
echo "=================================================================\n";
echo "⚠️  ISSUES FOUND! FIX REQUIRED\n";
echo "=================================================================\n\n";

echo "Do you want to fix these issues? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ Fix cancelled by user.\n";
    exit(0);
}

echo "\n";
echo "=================================================================\n";
echo "   APPLYING FIXES...\n";
echo "=================================================================\n\n";

try {
    DB::beginTransaction();

    // Fix sales table
    if ($subtotalDiff > 0.01 || $finalTotalDiff > 0.01) {
        DB::table('sales')
            ->where('id', $sale->id)
            ->update([
                'subtotal' => $calculatedSubtotal,
                'final_total' => $correctFinalTotal,
                'updated_at' => now()
            ]);

        echo "✅ SALES TABLE UPDATED:\n";
        echo "  Subtotal:    Rs " . number_format($sale->subtotal, 2) . " → Rs " . number_format($calculatedSubtotal, 2) . "\n";
        echo "  Final Total: Rs " . number_format($sale->final_total, 2) . " → Rs " . number_format($correctFinalTotal, 2) . "\n\n";
    }

    // Fix total_due if needed (generated column might not auto-update)
    if ($totalDueDiff > 0.01) {
        DB::statement("
            UPDATE sales
            SET total_due = (final_total - total_paid)
            WHERE id = ?
        ", [$sale->id]);

        echo "✅ TOTAL_DUE RECALCULATED:\n";
        echo "  Total Due: Rs " . number_format($sale->total_due, 2) . " → Rs " . number_format($correctTotalDue, 2) . "\n\n";
    }

    // Fix ledger if needed
    if ($ledger && $ledgerDiff > 0.01) {
        DB::table('ledgers')
            ->where('id', $ledger->id)
            ->update([
                'debit' => $correctFinalTotal,
                'updated_at' => now()
            ]);

        echo "✅ LEDGER TABLE UPDATED:\n";
        echo "  Debit: Rs " . number_format($ledger->debit, 2) . " → Rs " . number_format($correctFinalTotal, 2) . "\n\n";
    }

    DB::commit();

    echo "=================================================================\n";
    echo "   VERIFICATION AFTER UPDATE\n";
    echo "=================================================================\n\n";

    // Re-fetch updated records
    $updatedSale = DB::selectOne("SELECT subtotal, final_total, total_due, total_paid FROM sales WHERE id = ?", [$sale->id]);
    $updatedLedger = DB::selectOne("SELECT debit FROM ledgers WHERE reference_no = ? AND transaction_type = 'sale'", [$invoiceNo]);

    echo "UPDATED SALES TABLE:\n";
    echo "  Subtotal:    Rs " . number_format($updatedSale->subtotal, 2) . " ✅\n";
    echo "  Final Total: Rs " . number_format($updatedSale->final_total, 2) . " ✅\n";
    echo "  Total Paid:  Rs " . number_format($updatedSale->total_paid, 2) . "\n";
    echo "  Total Due:   Rs " . number_format($updatedSale->total_due, 2) . " ✅\n\n";

    if ($updatedLedger) {
        echo "UPDATED LEDGER TABLE:\n";
        echo "  Debit: Rs " . number_format($updatedLedger->debit, 2) . " ✅\n\n";
    }

    echo "=================================================================\n";
    echo "✅ INVOICE {$invoiceNo} SUCCESSFULLY FIXED!\n";
    echo "=================================================================\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
