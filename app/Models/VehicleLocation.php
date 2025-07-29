<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleLocation extends Model
{
    use HasFactory;
    protected $table = 'vehicle_locations';

    protected $fillable = [
        'vehicle_id',
        'location_id',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }


    public function location()
    {
        return $this->belongsTo(Location::class);
    }   

}
