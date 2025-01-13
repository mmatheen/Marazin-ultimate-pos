<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }

    public function locationBatches()
    {
        return $this->hasMany(LocationBatch::class);
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


}
