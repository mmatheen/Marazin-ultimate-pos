<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteCity extends Model
{
    use HasFactory;

    protected $table = 'route_cities';

    protected $fillable = [
        'route_id',
        'city_id',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
