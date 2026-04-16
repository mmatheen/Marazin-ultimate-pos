<?php

declare(strict_types=1);

use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Setting;
use App\Models\StockBackorder;
use App\Models\StockBackorderAllocation;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productId = isset($argv[1]) ? (int) $argv[1] : 24;
$take = isset($argv[2]) ? max(1, (int) $argv[2]) : 8;

echo "=== Backorder Reserve Debug ===\n";
echo "product_id={$productId}\n\n";

echo "Setting:\n";
$enabled = (bool) (Setting::value('enable_backorders') ?? 0);
echo "  enable_backorders=" . ($enabled ? '1' : '0') . "\n\n";

$latest = Purchase::orderByDesc('id')->take($take)->get(['id', 'reference_no', 'purchase_type', 'location_id', 'created_at']);

echo "Latest Purchases:\n";
foreach ($latest as $p) {
    echo "  id={$p->id}, ref={$p->reference_no}, type=" . ($p->purchase_type ?? 'null') . ", loc={$p->location_id}, at={$p->created_at}\n";
}
echo "\n";

$purchaseIds = $latest->pluck('id')->all();
if (empty($purchaseIds)) {
    echo "No purchases found.\n";
    exit(0);
}

$pps = PurchaseProduct::whereIn('purchase_id', $purchaseIds)
    ->where('product_id', $productId)
    ->orderByDesc('id')
    ->get(['id', 'purchase_id', 'product_id', 'batch_id', 'quantity', 'free_quantity', 'created_at']);

echo "Purchase Products for product {$productId}:\n";
if ($pps->isEmpty()) {
    echo "  none in latest {$take} purchases\n";
    echo "=== End ===\n";
    exit(0);
}

foreach ($pps as $pp) {
    $purchase = $latest->firstWhere('id', $pp->purchase_id);
    $reserveRows = StockBackorderAllocation::where('purchase_id', $pp->purchase_id)
        ->where('purchase_product_id', $pp->id)
        ->where('allocation_type', 'purchase_reservation')
        ->get();

    $reservePaid = (float) $reserveRows->sum('allocated_paid_qty');
    $reserveFree = (float) $reserveRows->sum('allocated_free_qty');

    echo "  pp_id={$pp->id}, purchase_id={$pp->purchase_id}, ref=" . ($purchase->reference_no ?? 'unknown') . ", qty={$pp->quantity}, free={$pp->free_quantity}, batch={$pp->batch_id}\n";
    echo "    reservation_rows={$reserveRows->count()}, reserved_paid={$reservePaid}, reserved_free={$reserveFree}\n";
}

$locations = $latest->pluck('location_id')->filter()->unique()->values();
foreach ($locations as $locId) {
    $backorders = StockBackorder::query()
        ->join('sales_products', 'stock_backorders.sale_product_id', '=', 'sales_products.id')
        ->where('sales_products.product_id', $productId)
        ->where('stock_backorders.location_id', $locId)
        ->orderByDesc('stock_backorders.id')
        ->select([
            'stock_backorders.id',
            'stock_backorders.location_id',
            'stock_backorders.status',
            'stock_backorders.ordered_paid_qty',
            'stock_backorders.ordered_free_qty',
            'stock_backorders.fulfilled_paid_qty',
            'stock_backorders.fulfilled_free_qty',
        ])
        ->limit(10)
        ->get();

    echo "\nBackorders for product {$productId} at location {$locId}:\n";
    if ($backorders->isEmpty()) {
        echo "  none\n";
        continue;
    }

    foreach ($backorders as $bo) {
        echo "  id={$bo->id}, status={$bo->status}, ordered_paid={$bo->ordered_paid_qty}, fulfilled_paid={$bo->fulfilled_paid_qty}, ordered_free={$bo->ordered_free_qty}, fulfilled_free={$bo->fulfilled_free_qty}\n";
    }
}

echo "\n=== End ===\n";
