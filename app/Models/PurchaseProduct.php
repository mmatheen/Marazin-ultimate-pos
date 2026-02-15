<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'batch_id',
        'location_id',
        'quantity',
        'free_quantity',      // Free/bonus items received from supplier
        'price',              // Original price before discount
        'discount_percent',   // Product-level discount percentage
        'unit_cost',          // Final unit cost after discount
        'wholesale_price',
        'special_price',
        'retail_price',
        'max_retail_price',
        'total'
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

}
