<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OpeningStock extends Model
{
    use HasFactory;
    protected $table='opening_stocks';
    protected $fillable=[

              'sku',
              'location_id',
              'product_id',
              'quantity',
              'unit_cost',
              'lot_no',
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

