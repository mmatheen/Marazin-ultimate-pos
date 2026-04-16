<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$saleId = (int) ($argv[1] ?? 0);
$saleProductId = (int) ($argv[2] ?? 0);
$backorderId = (int) ($argv[3] ?? 0);

if ($saleId <= 0 || $saleProductId <= 0 || $backorderId <= 0) {
    echo "Usage: php tools/dump_backorder_case.php <sale_id> <sale_product_id> <backorder_id>\n";
    exit(1);
}

echo "Sale header:\n";
var_export(DB::table('sales')->where('id', $saleId)->first());
echo "\n\nSale product:\n";
var_export(DB::table('sales_products')->where('id', $saleProductId)->first());
echo "\n\nBackorder:\n";
var_export(DB::table('stock_backorders')->where('id', $backorderId)->first());
echo "\n\nAllocations:\n";
$allocs = DB::table('stock_backorder_allocations')->where('stock_backorder_id', $backorderId)->get();
var_export($allocs);
echo "\n";
