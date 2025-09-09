<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'name',
        'district',
        'province',
    ];

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'route_cities');
    }

    public function routeCities()
    {
        return $this->hasMany(RouteCity::class);
    }
}
