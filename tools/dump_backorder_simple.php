<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$backorderId = (int) ($argv[1] ?? 0);
if ($backorderId <= 0) {
    echo "Usage: php tools/dump_backorder_simple.php <backorder_id>\n";
    exit(1);
}

var_export(DB::table('stock_backorders as sb')
    ->join('sales_products as sp', 'sb.sale_product_id', '=', 'sp.id')
    ->join('sales as s', 'sp.sale_id', '=', 's.id')
    ->where('sb.id', $backorderId)
    ->select('sb.*', 'sp.product_id', 'sp.quantity as sale_qty', 'sp.fulfilled_quantity', 'sp.backordered_quantity', 's.order_number', 's.transaction_type', 's.order_status')
    ->first());
