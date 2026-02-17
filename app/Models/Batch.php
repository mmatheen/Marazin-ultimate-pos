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
        return $this->hasMany(PurchaseReturnProduct::class, 'batch_no', 'batch_no');
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
     * Calculate free quantity remaining from transaction history (NO MIGRATION NEEDED)
     * Calculates dynamically from existing purchase_products, sales_products, etc.
     */
    public function calculateFreeQty()
    {
        $purchased = $this->purchaseProducts()->sum('free_quantity') ?? 0;
        $sold = $this->salesProducts()->sum('free_quantity') ?? 0;
        $returnedToSupplier = $this->purchaseReturns()->sum('free_quantity') ?? 0;
        $returnedByCustomer = $this->saleReturns()->sum('free_quantity') ?? 0;
        $adjusted = $this->stockAdjustments()->sum('free_quantity') ?? 0;

        $freeQty = $purchased - $sold - $returnedToSupplier + $returnedByCustomer + $adjusted;
        return max(0, min($freeQty, $this->qty));
    }

    /**
     * Calculate free quantity for a specific location (location-specific calculation)
     * This respects the location-based inventory system and actual transactions
     */
    public function calculateFreeQtyForLocation($locationId)
    {
        // Calculate based on ACTUAL transactions at this location
        $purchased = $this->purchaseProducts()->where('location_id', $locationId)->sum('free_quantity') ?? 0;
        $sold = $this->salesProducts()->where('location_id', $locationId)->sum('free_quantity') ?? 0;

        // For returns, we need to check if they have location_id
        // Purchase returns are tracked by batch_no, not batch_id
        $returnedToSupplier = DB::table('purchase_return_products as prp')
            ->join('purchase_returns as pr', 'prp.purchase_return_id', '=', 'pr.id')
            ->where('prp.batch_no', $this->batch_no)
            ->where('pr.location_id', $locationId)
            ->sum('prp.free_quantity') ?? 0;

        $returnedByCustomer = $this->saleReturns()->where('location_id', $locationId)->sum('free_quantity') ?? 0;

        // Adjustments
        $adjusted = DB::table('adjustment_products as ap')
            ->join('stock_adjustments as sa', 'ap.stock_adjustment_id', '=', 'sa.id')
            ->where('ap.batch_id', $this->id)
            ->where('sa.location_id', $locationId)
            ->sum('ap.free_quantity') ?? 0;

        $freeQty = $purchased - $sold - $returnedToSupplier + $returnedByCustomer + $adjusted;

        // Get location batch quantity
        $locationBatch = $this->locationBatches()->where('location_id', $locationId)->first();
        $maxQty = $locationBatch ? $locationBatch->qty : 0;

        return max(0, min($freeQty, $maxQty));
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
