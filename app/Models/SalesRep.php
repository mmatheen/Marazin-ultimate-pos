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
        'vehicle_id',
        'route_id',
        'assigned_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function targets()
    {
        return $this->hasMany(SalesRepTarget::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
