<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$purchaseId = (int) ($argv[1] ?? 0);
$purchaseProductId = (int) ($argv[2] ?? 0);
if ($purchaseId <= 0 || $purchaseProductId <= 0) {
    echo "Usage: php tools/dump_purchase_allocation_case.php <purchase_id> <purchase_product_id>\n";
    exit(1);
}

echo "Purchase header:\n";
var_export(DB::table('purchases')->where('id', $purchaseId)->first());
echo "\n\nPurchase product:\n";
var_export(DB::table('purchase_products')->where('id', $purchaseProductId)->first());
echo "\n\nRelated reservation rows:\n";
$rows = DB::table('stock_backorder_allocations')->where('purchase_id', $purchaseId)->where('purchase_product_id', $purchaseProductId)->orderBy('id')->get();
var_export($rows);
echo "\n";
