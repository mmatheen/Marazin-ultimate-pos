<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Batch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_no',
        'product_id',
        'unit_cost',
        'qty',
        'free_qty',
        'wholesale_price',
        'special_price',
        'retail_price',
        'max_retail_price',
        'expiry_date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseProducts()
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    public function salesProducts()
    {
        return $this->hasMany(SalesProduct::class, 'batch_id');
    }

    public function purchaseReturns()
    {
        // IMPORTANT: purchase_return_products.batch_no stores the Batch ID (not batch_no string)
        // So we match: purchase_return_products.batch_no = batches.id
        return $this->hasMany(PurchaseReturnProduct::class, 'batch_no', 'id');
    }

    public function saleReturns()
    {
        return $this->hasMany(SalesReturnProduct::class, 'batch_id', 'id');
    }

    public function stockAdjustments()
    {
        return $this->hasMany(AdjustmentProduct::class);
    }

    public function stockTransfers()
    {
        return $this->hasMany(StockTransferProduct::class);
    }

    public function stockHistories()
    {
        return $this->hasManyThrough(StockHistory::class, LocationBatch::class, 'batch_id', 'loc_batch_id', 'id', 'id');
    }

    public function locationBatches()
    {
        return $this->hasMany(LocationBatch::class);
    }

    /**
     * Calculate total free quantity across all locations
     * Sums free_qty from all location_batches (already maintained during transactions)
     */
    public function calculateFreeQty()
    {
        return (float) $this->locationBatches()->sum('free_qty');
    }

    /**
     * Calculate free quantity for a specific location
     * Reads directly from location_batches.free_qty which is maintained during transactions
     */
    public function calculateFreeQtyForLocation($locationId)
    {
        $locationBatch = $this->locationBatches()->where('location_id', $locationId)->first();
        return $locationBatch ? (float) $locationBatch->free_qty : 0;
    }

    /**
     * Get free quantity breakdown for debugging
     */
    public function getFreeQtyBreakdown()
    {
        $purchased = $this->purchaseProducts()->sum('free_quantity') ?? 0;
        $sold = $this->salesProducts()->sum('free_quantity') ?? 0;
        $returnedToSupplier = $this->purchaseReturns()->sum('free_quantity') ?? 0;
        $returnedByCustomer = $this->saleReturns()->sum('free_quantity') ?? 0;
        $adjusted = $this->stockAdjustments()->sum('free_quantity') ?? 0;

        return [
            'batch_no' => $this->batch_no,
            'total_qty' => $this->qty,
            'free_purchased' => $purchased,
            'free_sold' => $sold,
            'free_returned_to_supplier' => $returnedToSupplier,
            'free_returned_by_customer' => $returnedByCustomer,
            'free_adjusted' => $adjusted,
            'free_qty_remaining' => $this->calculateFreeQty(),
            'paid_qty' => $this->qty - $this->calculateFreeQty(),
        ];
    }

    public static function generateNextBatchNo()
    {
        // Fetch the last valid batch number in the 'BATCH' format
        $lastBatch = self::where('batch_no', 'like', 'BATCH%')
            ->whereRaw("batch_no REGEXP '^BATCH[0-9]+$'") // Ensure the format matches 'BATCH' followed by numbers
            ->orderBy('id', 'desc')
            ->first();

        // Extract the numeric part and increment it
        $nextNumber = $lastBatch
            ? (int)str_replace('BATCH', '', $lastBatch->batch_no) + 1
            : 1;

        // Format the number with leading zeros (e.g., BATCH005)
        return 'BATCH' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // If the primary key is different from 'id', specify it
    protected $primaryKey = 'id'; // Change 'id' to your actual primary key column if different
}
