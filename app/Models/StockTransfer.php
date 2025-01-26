<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;
    protected $fillable = [
        'from_location_id',
        'to_location_id',
        'transfer_date',
        'reference_no',
        'final_total'
    ];

    public function stockTransferProducts()
    {
        return $this->hasMany(StockTransferProduct::class);
    }
}
