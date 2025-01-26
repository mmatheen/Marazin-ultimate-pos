<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'date',
        'location_id',
        'adjustment_type',
        'total_amount_recovered',
        'reason',
    ];

    // Relationship with AdjustmentProduct
    public function adjustmentProducts()
    {
        return $this->hasMany(AdjustmentProduct::class);
    }

    // Relationship with Location
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
