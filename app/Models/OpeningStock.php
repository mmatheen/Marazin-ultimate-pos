<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningStock extends Model
{
    use HasFactory;

    protected $table = 'opening_stocks';

    protected $fillable = [
        'sku',
        'location_id',
        'product_id',
        'quantity',
        'unit_cost',
        'batch_id',
        'expiry_date',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    // Setter for the 'expiry_date' field to ensure it's saved in Y-m-d format
    public function setExpiryDateAttribute($value)
    {
        $this->attributes['expiry_date'] = Carbon::parse($value)->format('Y-m-d');
    }

    // Getter for the 'expiry_date' field to ensure it's returned in Y-m-d format
    public function getExpiryDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }
}
