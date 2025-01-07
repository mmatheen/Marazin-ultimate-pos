<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'loc_batch_id',
        'quantity',
        'stock_type',
    ];

    public function locationBatch()
    {
        return $this->belongsTo(LocationBatch::class);
    }
}
