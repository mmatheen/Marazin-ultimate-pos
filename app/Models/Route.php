<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $table = 'routes';

    protected $fillable = [
        'name',
        'description',
        'status',

    ];

    public function salesReps()
    {
        return $this->hasMany(SalesRep::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'route_cities');
    }

    public function routeCities()
    {
        return $this->hasMany(RouteCity::class);
    }
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_route', 'route_id', 'vehicle_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
