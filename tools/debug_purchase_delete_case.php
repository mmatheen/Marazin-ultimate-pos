<?php

declare(strict_types=1);

use App\Models\LocationBatch;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\StockBackorderAllocation;
use App\Models\StockHistory;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$referenceNo = $argv[1] ?? 'PUR155';
$productArg = $argv[2] ?? 'all';
$productId = is_numeric($productArg) ? (int) $productArg : null;

echo "=== Purchase Delete Debug ===\n";
echo "reference_no={$referenceNo}, product_filter=" . ($productId === null ? 'all' : (string) $productId) . "\n\n";

$purchase = Purchase::where('reference_no', $referenceNo)->first();
if (!$purchase) {
    echo "RESULT: Purchase not found\n";
    exit(1);
}

echo "Purchase:\n";
echo "  id={$purchase->id}\n";
echo "  location_id={$purchase->location_id}\n";
echo "  type=" . ($purchase->purchase_type ?? 'null') . "\n";
echo "  status=" . ($purchase->purchasing_status ?? 'null') . "\n";
echo "  payment_status=" . ($purchase->payment_status ?? 'null') . "\n\n";

$query = PurchaseProduct::where('purchase_id', $purchase->id);
if ($productId !== null) {
    $query->where('product_id', $productId);
}
$purchaseProducts = $query->orderBy('id')->get();

if ($purchaseProducts->isEmpty()) {
    echo "RESULT: No purchase product rows found for filter\n";
    exit(2);
}

$overallBlock = false;

foreach ($purchaseProducts as $pp) {
    echo "Purchase Product:\n";
    echo "  product_id={$pp->product_id}\n";
    echo "  purchase_product_id={$pp->id}\n";
    echo "  batch_id=" . ($pp->batch_id ?? 'null') . "\n";
    echo "  qty=" . (float) ($pp->quantity ?? 0) . "\n";
    echo "  free_qty=" . (float) ($pp->free_quantity ?? 0) . "\n\n";

    $locationBatch = null;
    if ($pp->batch_id) {
        $locationBatch = LocationBatch::where('batch_id', $pp->batch_id)
            ->where('location_id', $purchase->location_id)
            ->first();
    }

    echo "Location Batch:\n";
    if (!$locationBatch) {
        echo "  not found\n\n";
    } else {
        echo "  id={$locationBatch->id}\n";
        echo "  qty=" . (float) ($locationBatch->qty ?? 0) . "\n";
        echo "  free_qty=" . (float) ($locationBatch->free_qty ?? 0) . "\n\n";
    }

    $reservations = StockBackorderAllocation::where('purchase_id', $purchase->id)
        ->where('purchase_product_id', $pp->id)
        ->where('location_id', $purchase->location_id)
        ->where('allocation_type', 'purchase_reservation')
        ->get();

    $releasedEntries = StockBackorderAllocation::where('purchase_id', $purchase->id)
        ->where('purchase_product_id', $pp->id)
        ->where('location_id', $purchase->location_id)
        ->where('allocation_type', 'reservation_release')
        ->get();

    $reservedPaid = (float) $reservations->sum('allocated_paid_qty');
    $reservedFree = (float) $reservations->sum('allocated_free_qty');
    $releasedPaid = (float) $releasedEntries->sum('allocated_paid_qty');
    $releasedFree = (float) $releasedEntries->sum('allocated_free_qty');

    echo "Backorder Allocations:\n";
    echo "  active purchase_reservation rows=" . $reservations->count() . "\n";
    echo "  reserved_paid_total={$reservedPaid}\n";
    echo "  reserved_free_total={$reservedFree}\n";
    echo "  reservation_release rows=" . $releasedEntries->count() . "\n";
    echo "  released_paid_total={$releasedPaid}\n";
    echo "  released_free_total={$releasedFree}\n\n";

    if ($reservations->count() > 0) {
        echo "  reservation rows:\n";
        foreach ($reservations as $r) {
            echo "    id={$r->id}, backorder_id={$r->stock_backorder_id}, paid={$r->allocated_paid_qty}, free={$r->allocated_free_qty}, batch_id=" . ($r->batch_id ?? 'null') . "\n";
        }
        echo "\n";
    }

    $paidToRemoveAfterRelease = max(0.0, (float) ($pp->quantity ?? 0) - $reservedPaid);
    $freeToRemoveAfterRelease = max(0.0, (float) ($pp->free_quantity ?? 0) - $reservedFree);

    $availablePaid = (float) ($locationBatch->qty ?? 0);
    $availableFree = (float) ($locationBatch->free_qty ?? 0);

    echo "Delete Simulation (current controller logic):\n";
    echo "  net_paid_to_remove_after_release={$paidToRemoveAfterRelease}\n";
    echo "  net_free_to_remove_after_release={$freeToRemoveAfterRelease}\n";
    echo "  available_paid_in_location_batch={$availablePaid}\n";
    echo "  available_free_in_location_batch={$availableFree}\n";

    $wouldBlock = ($paidToRemoveAfterRelease > $availablePaid + 0.0001)
        || ($freeToRemoveAfterRelease > $availableFree + 0.0001);
    $overallBlock = $overallBlock || $wouldBlock;

    echo "  decision=" . ($wouldBlock ? 'BLOCK_DELETE' : 'ALLOW_DELETE') . "\n\n";

    if (!$locationBatch) {
        continue;
    }

    $optionalColumns = ['source_pool', 'movement_type', 'reference_id', 'reference_type'];
    $selectColumns = ['id', 'quantity', 'stock_type', 'created_at'];
    foreach ($optionalColumns as $column) {
        if (Schema::hasColumn('stock_histories', $column)) {
            $selectColumns[] = $column;
        }
    }

    $history = StockHistory::where('loc_batch_id', $locationBatch->id)
        ->orderByDesc('id')
        ->limit(12)
        ->get($selectColumns);

    echo "Recent Stock History (latest 12 on this loc_batch):\n";
    if ($history->isEmpty()) {
        echo "  none\n";
    } else {
        foreach ($history as $h) {
            echo "  id={$h->id}, qty={$h->quantity}, type={$h->stock_type}, source=" . ($h->source_pool ?? 'null')
                . ", move=" . ($h->movement_type ?? 'null')
                . ", ref_id=" . ($h->reference_id ?? 'null')
                . ", ref_type=" . ($h->reference_type ?? 'null')
                . ", at={$h->created_at}\n";
        }
    }
    echo "\n";
}

echo "Overall purchase delete decision: " . ($overallBlock ? 'BLOCK_DELETE' : 'ALLOW_DELETE') . "\n\n";

echo "=== End ===\n";
