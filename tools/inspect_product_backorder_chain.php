<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = (int) ($argv[1] ?? 0);
$locationId = (int) ($argv[2] ?? 0);

if ($productId <= 0 || $locationId <= 0) {
    echo "Usage: php tools/inspect_product_backorder_chain.php <product_id> <location_id>\n";
    exit(1);
}

echo "Product ID: {$productId}\n";
echo "Location ID: {$locationId}\n\n";

$rows = DB::table('stock_backorders as sb')
    ->join('sales_products as sp', 'sb.sale_product_id', '=', 'sp.id')
    ->join('sales as s', 'sp.sale_id', '=', 's.id')
    ->leftJoin('stock_backorder_allocations as sba', 'sba.stock_backorder_id', '=', 'sb.id')
    ->where('sp.product_id', $productId)
    ->where('sb.location_id', $locationId)
    ->selectRaw('sb.id as backorder_id, sb.status, sb.ordered_paid_qty, sb.ordered_free_qty, sb.fulfilled_paid_qty, sb.fulfilled_free_qty, sb.created_at as bo_created, sp.id as sale_product_id, s.id as sale_id, s.order_number, s.transaction_type, s.order_status, s.sales_date, COALESCE(SUM(CASE WHEN sba.allocation_type = "purchase_reservation" THEN sba.allocated_paid_qty ELSE 0 END),0) as reserved_paid, COALESCE(SUM(CASE WHEN sba.allocation_type = "purchase_reservation" THEN sba.allocated_free_qty ELSE 0 END),0) as reserved_free, COUNT(sba.id) as alloc_rows')
    ->groupBy('sb.id', 'sb.status', 'sb.ordered_paid_qty', 'sb.ordered_free_qty', 'sb.fulfilled_paid_qty', 'sb.fulfilled_free_qty', 'sb.created_at', 'sp.id', 's.id', 's.order_number', 's.transaction_type', 's.order_status', 's.sales_date')
    ->orderBy('sb.created_at')
    ->get();

if ($rows->isEmpty()) {
    echo "No backorders found.\n";
    exit(0);
}

foreach ($rows as $row) {
    echo "backorder_id={$row->backorder_id} sale_product_id={$row->sale_product_id} sale_id={$row->sale_id} order_number={$row->order_number} tx={$row->transaction_type} order_status={$row->order_status} status={$row->status} ordered_paid={$row->ordered_paid_qty} fulfilled_paid={$row->fulfilled_paid_qty} reserved_paid={$row->reserved_paid} alloc_rows={$row->alloc_rows} created={$row->bo_created}\n";
}
