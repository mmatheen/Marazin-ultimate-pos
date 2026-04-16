<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Recover over-restored stock caused by historical sale-order cancellation bug.
 *
 * Usage:
 *   php tools/recover_cancelled_sale_order_backorder_overrestore.php <sale_id|invoice_no|order_number>
 *   php tools/recover_cancelled_sale_order_backorder_overrestore.php <sale_id|invoice_no|order_number> --apply
 *
 * Dry-run is default. Use --apply to persist changes.
 */

$identifier = $argv[1] ?? null;
$apply = in_array('--apply', $argv, true);

if (!$identifier) {
    echo "Usage:\n";
    echo "  php tools/recover_cancelled_sale_order_backorder_overrestore.php <sale_id|invoice_no|order_number> [--apply]\n\n";
    echo "Example dry-run:\n";
    echo "  php tools/recover_cancelled_sale_order_backorder_overrestore.php 152\n";
    echo "  php tools/recover_cancelled_sale_order_backorder_overrestore.php SO-152\n\n";
    echo "Example apply:\n";
    echo "  php tools/recover_cancelled_sale_order_backorder_overrestore.php 152 --apply\n";
    exit(1);
}

echo "=================================================================\n";
echo " CANCELLED SALE-ORDER OVER-RESTORE RECOVERY\n";
echo "=================================================================\n\n";
echo "Mode: " . ($apply ? 'APPLY (write changes)' : 'DRY-RUN (no changes)') . "\n";
echo "Identifier: {$identifier}\n\n";

$sale = DB::table('sales')
    ->when(is_numeric($identifier), function ($q) use ($identifier) {
        $q->where('id', (int) $identifier);
    }, function ($q) use ($identifier) {
        $q->where('invoice_no', $identifier)
          ->orWhere('order_number', $identifier);
    })
    ->select('id', 'invoice_no', 'order_number', 'transaction_type', 'order_status', 'status', 'location_id')
    ->first();

if (!$sale) {
    echo "Result: Sale not found\n";
    exit(1);
}

echo "Sale:\n";
echo "  id={$sale->id}\n";
echo "  invoice_no=" . ($sale->invoice_no ?? 'null') . "\n";
echo "  order_number=" . ($sale->order_number ?? 'null') . "\n";
echo "  transaction_type=" . ($sale->transaction_type ?? 'null') . "\n";
echo "  order_status=" . ($sale->order_status ?? 'null') . "\n";
echo "  status=" . ($sale->status ?? 'null') . "\n";
echo "  location_id=" . ($sale->location_id ?? 'null') . "\n\n";

if (($sale->transaction_type ?? null) !== 'sale_order' || ($sale->order_status ?? null) !== 'cancelled') {
    echo "Safety check failed: only cancelled sale_order records are eligible.\n";
    exit(2);
}

$backorders = DB::table('stock_backorders as sb')
    ->join('sales_products as sp', 'sp.id', '=', 'sb.sale_product_id')
    ->where('sp.sale_id', $sale->id)
    ->whereNotNull('sb.deleted_at')
    ->where('sb.status', 'cancelled')
    ->select('sb.id')
    ->get();

if ($backorders->isEmpty()) {
    echo "No cancelled+deleted backorders found for this sale. Nothing to recover.\n";
    exit(0);
}

$backorderIds = $backorders->pluck('id')->all();

$allocationBuckets = DB::table('stock_backorder_allocations')
    ->whereIn('stock_backorder_id', $backorderIds)
    ->where('allocation_type', 'purchase_reservation')
    ->groupBy('batch_id', 'location_id')
    ->select(
        'batch_id',
        'location_id',
        DB::raw('SUM(allocated_paid_qty) as paid_qty'),
        DB::raw('SUM(allocated_free_qty) as free_qty'),
        DB::raw('SUM(allocated_paid_qty + allocated_free_qty) as total_qty'),
        DB::raw('COUNT(*) as row_count')
    )
    ->get();

if ($allocationBuckets->isEmpty()) {
    echo "No purchase_reservation allocations left for cancelled backorders. Nothing to recover.\n";
    exit(0);
}

echo "Detected recovery buckets:\n";
$grandPaid = 0.0;
$grandFree = 0.0;
$grandTotal = 0.0;
foreach ($allocationBuckets as $b) {
    $paid = round((float) ($b->paid_qty ?? 0), 4);
    $free = round((float) ($b->free_qty ?? 0), 4);
    $total = round((float) ($b->total_qty ?? 0), 4);

    $grandPaid += $paid;
    $grandFree += $free;
    $grandTotal += $total;

    echo "  batch_id=" . ($b->batch_id ?? 'null')
        . ", location_id=" . ($b->location_id ?? 'null')
        . ", paid={$paid}, free={$free}, total={$total}, rows={$b->row_count}\n";
}

echo "\nTotals to deduct:\n";
echo "  paid={$grandPaid}\n";
echo "  free={$grandFree}\n";
echo "  total={$grandTotal}\n\n";

$validationErrors = [];

foreach ($allocationBuckets as $b) {
    if (empty($b->batch_id) || empty($b->location_id)) {
        $validationErrors[] = "Invalid allocation bucket with null batch/location (batch_id=" . ($b->batch_id ?? 'null') . ", location_id=" . ($b->location_id ?? 'null') . ")";
        continue;
    }

    $lb = DB::table('location_batches')
        ->where('batch_id', $b->batch_id)
        ->where('location_id', $b->location_id)
        ->select('id', 'qty', 'free_qty')
        ->first();

    if (!$lb) {
        $validationErrors[] = "Missing location_batches row for batch_id={$b->batch_id}, location_id={$b->location_id}";
        continue;
    }

    $needPaid = round((float) $b->paid_qty, 4);
    $needFree = round((float) $b->free_qty, 4);
    $availPaid = round((float) ($lb->qty ?? 0), 4);
    $availFree = round((float) ($lb->free_qty ?? 0), 4);

    if ($needPaid > $availPaid + 0.0001) {
        $validationErrors[] = "Insufficient paid qty for loc_batch_id={$lb->id}: need {$needPaid}, available {$availPaid}";
    }
    if ($needFree > $availFree + 0.0001) {
        $validationErrors[] = "Insufficient free qty for loc_batch_id={$lb->id}: need {$needFree}, available {$availFree}";
    }
}

if (!empty($validationErrors)) {
    echo "Validation failed. No changes applied.\n";
    foreach ($validationErrors as $e) {
        echo "  - {$e}\n";
    }
    exit(3);
}

if (!$apply) {
    echo "Dry-run complete. Use --apply to execute recovery.\n";
    exit(0);
}

try {
    DB::transaction(function () use ($allocationBuckets, $backorderIds) {
        foreach ($allocationBuckets as $b) {
            $paid = round((float) ($b->paid_qty ?? 0), 4);
            $free = round((float) ($b->free_qty ?? 0), 4);
            $total = round((float) ($b->total_qty ?? 0), 4);

            if ($total <= 0) {
                continue;
            }

            $lb = DB::table('location_batches')
                ->where('batch_id', $b->batch_id)
                ->where('location_id', $b->location_id)
                ->lockForUpdate()
                ->select('id', 'qty', 'free_qty')
                ->first();

            if (!$lb) {
                throw new RuntimeException("Missing location_batches row for batch_id={$b->batch_id}, location_id={$b->location_id}");
            }

            DB::table('location_batches')
                ->where('id', $lb->id)
                ->update([
                    'qty' => DB::raw('qty - ' . $paid),
                    'free_qty' => DB::raw('free_qty - ' . $free),
                ]);

            DB::table('stock_histories')->insert([
                'loc_batch_id' => $lb->id,
                'quantity' => -$total,
                'stock_type' => 'adjustment',
                'paid_qty' => -$paid,
                'free_qty' => -$free,
                'source_pool' => 'backorder_cancel_recovery',
                'movement_type' => 'deduction',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Prevent re-running deduction for same cancelled backorders.
        DB::table('stock_backorder_allocations')
            ->whereIn('stock_backorder_id', $backorderIds)
            ->where('allocation_type', 'purchase_reservation')
            ->delete();
    });

    echo "Recovery applied successfully.\n";
    echo "- location_batches deducted by cancelled backorder reservation totals\n";
    echo "- stock_histories correction rows inserted (stock_type=adjustment)\n";
    echo "- old purchase_reservation rows for those cancelled backorders deleted\n";
    exit(0);

} catch (Throwable $e) {
    echo "Recovery failed: " . $e->getMessage() . "\n";
    exit(4);
}
