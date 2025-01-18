<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_return_id',  // Link to the sales return
        'product_id',       // Product being returned
        'batch_id',         // Nullable, for batch-specific stock tracking
        'location_id',      // Location where the product is returned
        'quantity',         // Quantity returned
        'price_type',       // Price type (retail, wholesale, special)
        'original_price',   // Price at the time of sale
        'return_price',     // Price for the return
        'discount',         // Discount applied during return
        'tax',              // Tax applied during return
        'subtotal',         // Calculated subtotal
    ];

    /**
     * Relationship with SalesReturn.
     */
    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class, 'sales_return_id');
    }

    /**
     * Relationship with Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relationship with Batch.
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * Relationship with Location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
