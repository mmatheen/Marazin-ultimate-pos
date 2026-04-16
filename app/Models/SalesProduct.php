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
        'fulfilled_quantity',
        'fulfilled_free_quantity',
        'backordered_quantity',
        'backordered_free_quantity',
        'fulfillment_status',
        'price_type',
        'price', // Unit price (quantity × price = subtotal)
        'discount_amount',
        'discount_type', // 'fixed' or 'percentage'
        'tax',
        'tax_percent',
        'vat_per_unit',
        'vat_total',
        'sale_excl_vat_per_unit',
        'profit_per_unit',
        'profit_total',
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

    public function backorders()
    {
        return $this->hasMany(StockBackorder::class, 'sale_product_id');
    }
}
