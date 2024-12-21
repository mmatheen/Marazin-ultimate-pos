<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    protected $fillable = ['sell_detail_id', 'product_id', 'quantity', 'unit_price', 'discount', 'subtotal', 'location_id'];


            // In ProductOrder model
        public function product()
        {
            return $this->belongsTo(Product::class, 'product_id');
        }

        public function location()
        {
            return $this->belongsTo(Location::class, 'location_id');
        }

}
