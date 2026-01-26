<?php

/**
 * Fix Sale Orders that were converted to final sales without proper invoice generation
 *
 * This script identifies sale orders that:
 * - Have transaction_type = 'sale_order'
 * - Have status = 'final' (indicating they were finalized)
 * - Have payments (indicating customer paid)
 * - Missing invoice_no (the bug)
 *
 * And corrects them by:
 * - Generating proper invoice numbers
 * - Updating transaction_type to 'invoice'
 * - Updating order_status to 'completed'
 * - Creating missing ledger entries
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\Payment;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check for dry-run mode
$dryRun = in_array('--dry-run', $argv ?? []);
$force = in_array('--force', $argv ?? []);

// Check for specific sale ID
$specificSaleId = null;
foreach (($argv ?? []) as $arg) {
    if (strpos($arg, '--sale-id=') === 0) {
        $specificSaleId = (int)substr($arg, 10);
    }
}

echo "\n=== Sale Order to Invoice Correction Script ===\n";
echo "Started at: " . now()->format('Y-m-d H:i:s') . "\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes will be made)" : "LIVE") . "\n";
if ($specificSaleId) {
    echo "Target: Sale ID {$specificSaleId} only\n";
}
echo "\n";

if (!$dryRun && !$force) {
    echo "⚠️  WARNING: This will modify your database!\n";
    echo "Run with --dry-run to preview changes first\n";
    echo "Run with --force to apply changes\n";
    echo "Use --sale-id=1274 to fix a specific sale only\n\n";
    exit(0);
}

try {
    // Find problematic records - records that need invoice numbers
    // Case 1: transaction_type = 'sale_order' AND status = 'final' AND invoice_no is NULL
    // Case 2: transaction_type = 'invoice' AND invoice_no is NULL (already converted but invoice number missing)
    $query = Sale::where('status', 'final')
        ->whereNull('invoice_no')
        ->whereIn('transaction_type', ['sale_order', 'invoice'])
        ->with(['customer', 'location', 'payments']);

    // Filter by specific sale ID if provided
    if ($specificSaleId) {
        $query->where('id', $specificSaleId);
    }

    $problematicSales = $query->get();

    if ($problematicSales->isEmpty()) {
        if ($specificSaleId) {
            echo "❌ Sale ID {$specificSaleId} not found or doesn't need fixing.\n";
            echo "Checking if record exists...\n\n";
            
            $sale = Sale::find($specificSaleId);
            if ($sale) {
                echo "Record found:\n";
                echo "  - ID: {$sale->id}\n";
                echo "  - Transaction Type: {$sale->transaction_type}\n";
                echo "  - Status: {$sale->status}\n";
                echo "  - Invoice No: " . ($sale->invoice_no ?: 'NULL') . "\n";
                echo "  - Customer ID: {$sale->customer_id}\n";
                echo "\n";
                
                if ($sale->invoice_no) {
                    echo "✅ This sale already has an invoice number: {$sale->invoice_no}\n";
                } elseif ($sale->status !== 'final') {
                    echo "⚠️  This sale status is '{$sale->status}' (not 'final'). Only final sales need invoices.\n";
                } else {
                    echo "⚠️  This record matches criteria but query didn't find it. Check transaction_type.\n";
                }
            } else {
                echo "❌ Sale ID {$specificSaleId} does not exist in database.\n";
            }
        } else {
            echo "✅ No problematic records found. All sales are properly configured.\n";
        }
        exit(0);
    }

    echo "Found {$problematicSales->count()} sale(s) that need correction:\n\n";

    $fixed = 0;
    $failed = 0;
    $errors = [];

    foreach ($problematicSales as $sale) {
        echo "Processing Sale ID: {$sale->id}\n";
        echo "  - Order Number: {$sale->order_number}\n";
        echo "  - Customer: {$sale->customer->first_name} {$sale->customer->last_name} (ID: {$sale->customer_id})\n";
        echo "  - Transaction Type: {$sale->transaction_type}\n";
        echo "  - Final Total: Rs {$sale->final_total}\n";
        echo "  - Payments: {$sale->payments->count()} payment(s)\n";
        echo "  - Created: {$sale->created_at->format('Y-m-d H:i:s')}\n";

        if ($dryRun) {
            // Dry run - just show what would be done
            $proposedInvoiceNo = "INV-PREVIEW-" . $sale->id;
            echo "  [DRY RUN] Would generate invoice number\n";
            
            // Only update transaction_type if it's still sale_order
            if ($sale->transaction_type === 'sale_order') {
                echo "  [DRY RUN] Would update: transaction_type => 'invoice', order_status => 'completed'\n";
            } else {
                echo "  [DRY RUN] Would update: invoice_no (transaction_type already 'invoice')\n";
            }

            if ($sale->customer_id != 1) {
                $existingLedger = DB::table('ledgers')
                    ->where('customer_id', $sale->customer_id)
                    ->where('reference_id', $sale->id)
                    ->where('type', 'sale')
                    ->exists();

                if (!$existingLedger) {
                    echo "  [DRY RUN] Would create ledger entry for customer\n";
                } else {
                    echo "  [DRY RUN] Ledger entry already exists\n";
                }
            }

            if ($sale->payments->isNotEmpty()) {
                echo "  [DRY RUN] Would update {$sale->payments->count()} payment reference(s)\n";
            }

            echo "  ✅ [DRY RUN] Would fix Sale ID: {$sale->id}\n\n";
            $fixed++;
            continue;
        }

        try {
            DB::transaction(function () use ($sale) {
                // Generate invoice number
                $invoiceNo = Sale::generateInvoiceNo($sale->location_id);

                echo "  ✅ Generated Invoice No: {$invoiceNo}\n";

                // Prepare update data
                $updateData = [
                    'invoice_no' => $invoiceNo,
                ];

                // Only update transaction_type if it's still sale_order
                if ($sale->transaction_type === 'sale_order') {
                    $updateData['transaction_type'] = 'invoice';
                    $updateData['order_status'] = 'completed';
                    echo "  ✅ Converting sale_order to invoice\n";
                }

                // Update the sale record
                $sale->update($updateData);

                echo "  ✅ Updated sale record\n";

                // Create ledger entry if customer is not Walk-In (ID != 1)
                if ($sale->customer_id != 1) {
                    $unifiedLedgerService = app(UnifiedLedgerService::class);

                    // Check if ledger entry already exists
                    $existingLedger = DB::table('ledgers')
                        ->where('customer_id', $sale->customer_id)
                        ->where('reference_id', $sale->id)
                        ->where('type', 'sale')
                        ->exists();

                    if (!$existingLedger) {
                        $unifiedLedgerService->recordNewSaleEntry($sale);
                        echo "  ✅ Created ledger entry\n";
                    } else {
                        echo "  ℹ️  Ledger entry already exists\n";
                    }
                } else {
                    echo "  ℹ️  Walk-In Customer - skipped ledger entry\n";
                }

                // Update payment reference_no to use invoice number
                if ($sale->payments->isNotEmpty()) {
                    foreach ($sale->payments as $payment) {
                        $payment->update(['reference_no' => $invoiceNo]);
                    }
                    echo "  ✅ Updated {$sale->payments->count()} payment reference(s)\n";
                }
            });

            echo "  ✅ Successfully fixed Sale ID: {$sale->id}\n\n";
            $fixed++;

        } catch (\Exception $e) {
            echo "  ❌ Error fixing Sale ID: {$sale->id}\n";
            echo "     Error: {$e->getMessage()}\n\n";
            $errors[] = [
                'sale_id' => $sale->id,
                'order_number' => $sale->order_number,
                'error' => $e->getMessage()
            ];
            $failed++;
        }
    }

    echo "\n=== Summary ===\n";
    echo "Total found: {$problematicSales->count()}\n";
    echo "Successfully fixed: {$fixed}\n";
    echo "Failed: {$failed}\n";

    if (!empty($errors)) {
        echo "\n=== Errors ===\n";
        foreach ($errors as $error) {
            echo "Sale ID {$error['sale_id']} (Order: {$error['order_number']}): {$error['error']}\n";
        }
    }

    echo "\nCompleted at: " . now()->format('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    echo "\n❌ Fatal Error: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
