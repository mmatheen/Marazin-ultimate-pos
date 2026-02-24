<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'custom_name',
        'batch_id',
        'location_id',
        'quantity',
        'free_quantity',     // Free items given to customer (promotions)
        'price_type',
        'price', // Unit price (quantity × price = subtotal)
        'discount_amount',
        'discount_type', // 'fixed' or 'percentage'
        'tax',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function imeis()
    {
        return $this->hasMany(SaleImei::class, 'sale_product_id');
    }
}
