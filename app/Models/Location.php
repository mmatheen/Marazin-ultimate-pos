<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $table='locations';
    protected $fillable=[

              'name',
              'location_id',
              'address',
              'province',
              'district',
              'city',
              'email',
              'mobile',
              'telephone_no',
    ];

    public function openingStock()
    {
        return $this->hasMany(OpeningStock::class);
    }

  
}
