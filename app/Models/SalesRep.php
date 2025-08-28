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
        'sub_location_id',
        'route_id',
        'assigned_date',
        'end_date',
        'can_sell',
        'status',
    ];

    protected $casts = [
        'assigned_date' => 'datetime',
        'end_date' => 'datetime',
        'can_sell' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subLocation()
    {
        return $this->belongsTo(Location::class, 'sub_location_id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
