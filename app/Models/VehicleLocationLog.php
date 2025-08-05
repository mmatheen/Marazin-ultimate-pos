<?php
// app/Models/VehicleLocationLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLocationLog extends Model
{
    protected $fillable = [
        'vehicle_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'recorded_at',
        'raw_data',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'raw_data' => 'array',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
