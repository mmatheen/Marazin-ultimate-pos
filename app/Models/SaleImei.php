<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleImei extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'sale_product_id',
        'product_id',
        'batch_id',
        'location_id',
        'imei_number'
    ];

    // Relationships (Optional)
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

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
