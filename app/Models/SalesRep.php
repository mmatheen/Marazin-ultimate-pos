<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRep extends Model
{
    use HasFactory;

    protected $table = 'sales_reps';

    protected $fillable = [
        'user_id',
        'vehicle_location_id',
        'route_id',
        'assigned_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleLocation()
    {
        return $this->belongsTo(VehicleLocation::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function targets()
    {
        return $this->hasMany(SalesRepTarget::class);
    }

    // Helper method to get vehicle through vehicle_location
    public function vehicle()
    {
        return $this->hasOneThrough(
            Vehicle::class,
            VehicleLocation::class,
            'id',
            'id',
            'vehicle_location_id',
            'vehicle_id'
        );
    }

    // Helper method to get location through vehicle_location
    public function location()
    {
        return $this->hasOneThrough(
            Location::class,
            VehicleLocation::class,
            'id',
            'id',
            'vehicle_location_id',
            'location_id'
        );
    }
}
