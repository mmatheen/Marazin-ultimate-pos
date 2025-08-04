<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;
    protected $table = 'vehicles';

    protected $fillable = [
        "vehicle_number",
        "vehicle_type",
        "description",
    ];

    public function vehicleLocations()
    {
        return $this->hasMany(VehicleLocation::class);
    }

    public function salesReps()
    {
        return $this->hasMany(SalesRep::class);
    }

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'vehicle_route', 'vehicle_id', 'route_id');
    }

    public function getVehicleTypeAttribute($value)
    {
        return ucfirst($value);
    }

    public function setVehicleTypeAttribute($value)
    {
        $this->attributes['vehicle_type'] = strtolower($value);
    }
}
