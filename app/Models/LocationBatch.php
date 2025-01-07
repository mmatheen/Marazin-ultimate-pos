<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'location_id',
        'qty',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }
}
