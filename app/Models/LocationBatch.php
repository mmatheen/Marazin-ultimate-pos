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
        return $this->hasMany(StockHistory::class, 'loc_batch_id');
    }

    /**
     * Calculate free quantity for this location batch (based on actual transactions)
     * NO MIGRATION NEEDED - calculates from transaction history at this location
     *
     * Since inventory is tracked per location, this calculates the actual
     * free quantity based on purchases, sales, and returns at THIS location
     */
    public function calculateFreeQty()
    {
        $batch = $this->batch;
        if (!$batch) return 0;

        // Calculate based on ACTUAL transactions at this location
        return $batch->calculateFreeQtyForLocation($this->location_id);
    }
}
