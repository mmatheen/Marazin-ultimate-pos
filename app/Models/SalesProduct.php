<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesProduct extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'sale_id', 'product_id', 'batch_id', 'location_id','quantity', 'unit_price', 'discount', 'tax',
    // ];
    protected $fillable = [
        'sale_id',
        'product_id',
        'batch_id',
        'location_id',
        'quantity',
        'price_type',
        'price',
        'discount',
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
}
