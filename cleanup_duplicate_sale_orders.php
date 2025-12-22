<?php
/**
 * Cleanup Script for Duplicate Sale Order/Invoice Records
 *
 * This script handles existing duplicate records created by the old conversion method:
 * - Finds sale orders that were converted to invoices (creating 2 records)
 * - Moves products/imeis back to the original sale order
 * - Deletes the duplicate invoice record
 * - Updates the sale order to be an invoice
 *
 * Run this ONCE after deploying the new conversion logic
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\SalesProduct;
use App\Models\SaleImei;

echo "========================================\n";
echo "Sale Order Duplicate Cleanup Script\n";
echo "========================================\n\n";

try {
    DB::beginTransaction();

    // Step 1: Find all sale orders that have converted_to_sale_id (duplicates exist)
    $saleOrdersWithDuplicates = Sale::where('transaction_type', 'sale_order')
        ->whereNotNull('converted_to_sale_id')
        ->where('order_status', 'completed')
        ->get();

    echo "Found " . $saleOrdersWithDuplicates->count() . " sale orders with duplicate invoice records\n\n";

    if ($saleOrdersWithDuplicates->isEmpty()) {
        echo "✅ No duplicates found! Database is clean.\n";
        DB::commit();
        exit(0);
    }

    $cleaned = 0;
    $errors = 0;

    foreach ($saleOrdersWithDuplicates as $saleOrder) {
        try {
            $invoiceId = $saleOrder->converted_to_sale_id;
            $invoice = Sale::find($invoiceId);

            if (!$invoice) {
                echo "⚠️  Sale Order #{$saleOrder->id} - Invoice #{$invoiceId} not found, skipping\n";
                continue;
            }

            echo "Processing Sale Order #{$saleOrder->id} ({$saleOrder->order_number}) → Invoice #{$invoice->id} ({$invoice->invoice_no})\n";

            // Step 2: Move products back to sale order
            $productsMoved = 0;
            foreach ($invoice->products as $product) {
                $product->update(['sale_id' => $saleOrder->id]);
                $productsMoved++;
            }
            echo "  ├─ Moved {$productsMoved} products back to sale order\n";

            // Step 3: Move IMEIs back to sale order
            $imeisMoved = 0;
            $imeis = SaleImei::where('sale_id', $invoice->id)->get();
            foreach ($imeis as $imei) {
                $imei->update(['sale_id' => $saleOrder->id]);
                $imeisMoved++;
            }
            if ($imeisMoved > 0) {
                echo "  ├─ Moved {$imeisMoved} IMEIs back to sale order\n";
            }

            // Step 4: Copy invoice data to sale order (transform sale order to invoice)
            $saleOrder->update([
                'transaction_type' => 'invoice',
                'invoice_no' => $invoice->invoice_no,
                'sales_date' => $invoice->sales_date,
                'status' => 'final',
                'total_paid' => $invoice->total_paid,
                'total_due' => $invoice->total_due,
                'payment_status' => $invoice->payment_status,
                'order_status' => 'completed',
                'converted_to_sale_id' => null, // Clear this field
            ]);
            echo "  ├─ Updated sale order to invoice (invoice_no: {$invoice->invoice_no})\n";

            // Step 5: Move payments from invoice to sale order
            $paymentsMoved = DB::table('payments')
                ->where('reference_id', $invoice->id)
                ->where('payment_type', 'sale')
                ->update(['reference_id' => $saleOrder->id]);

            if ($paymentsMoved > 0) {
                echo "  ├─ Moved {$paymentsMoved} payments to sale order\n";
            }

            // Step 6: Update ledger entries
            $ledgersMoved = DB::table('ledgers')
                ->where('transaction_type', 'sale')
                ->where('reference_no', $invoice->invoice_no)
                ->update(['notes' => "Sale invoice #{$invoice->invoice_no} (Merged from duplicate cleanup)"]);

            // Step 7: Delete the duplicate invoice record
            $invoice->delete();
            echo "  └─ ✅ Deleted duplicate invoice record #{$invoice->id}\n\n";

            $cleaned++;

        } catch (\Exception $e) {
            echo "  └─ ❌ Error: " . $e->getMessage() . "\n\n";
            $errors++;
            Log::error("Cleanup error for sale order #{$saleOrder->id}: " . $e->getMessage());
        }
    }

    // Step 8: Summary
    echo "========================================\n";
    echo "Cleanup Summary:\n";
    echo "========================================\n";
    echo "✅ Successfully cleaned: {$cleaned} records\n";
    echo "❌ Errors: {$errors} records\n";
    echo "========================================\n\n";

    if ($errors > 0) {
        echo "⚠️  Some errors occurred. Check the logs for details.\n";
        echo "You may need to manually review the failed records.\n\n";

        // Ask for confirmation before committing
        echo "Do you want to commit these changes? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if (strtolower($line) !== 'yes') {
            echo "\n❌ Rollback initiated...\n";
            DB::rollBack();
            echo "All changes have been rolled back.\n";
            exit(1);
        }
    }

    DB::commit();
    echo "\n✅ All changes committed successfully!\n";
    echo "\n💡 Recommendations:\n";
    echo "1. Verify reports (Due Report, Daily Sales) show correct data\n";
    echo "2. Check that payments are linked correctly\n";
    echo "3. Test converting a new sale order to ensure new logic works\n";
    echo "4. Backup your database before running this script in production!\n\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    Log::error("Critical cleanup error: " . $e->getMessage());
    exit(1);
}
