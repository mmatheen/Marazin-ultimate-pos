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
        'free_qty',
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
        return $this->hasMany(StockHistory::class, 'loc_batch_id');
    }

    /**
     * Get free quantity for this location batch
     * Reads directly from the free_qty column which is maintained during transactions
     */
    public function calculateFreeQty()
    {
        // Return the free_qty column value (already maintained by transactions)
        return (float) $this->free_qty;
    }
}
