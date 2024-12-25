<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningStock extends Model
{
    use HasFactory;
    protected $table='opening_stocks';
    protected $fillable = [
        'product_id', 'location_id', 'batch_id', 'quantity', 'unit_cost', 'lot_no', 'expiry_date'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }




     // Setter for the 'expiry_date' field to ensure it's saved in Y-m-d format
     public function setExpiry_DateAttribute($value)
     {
         // Ensure 'expiry_date' is saved in the 'Y-m-d' format
         $this->attributes['expiry_date'] = Carbon::parse($value)->format('Y-m-d');
     }

     // Getter for the 'expiry_date' field to ensure it's returned in 'Y-m-d' format
     public function getExpiry_DateAttribute($value)
     {
         // Ensure the 'expiry_date' is returned in 'Y-m-d' format
         return Carbon::parse($value)->format('Y-m-d');
     }
}

