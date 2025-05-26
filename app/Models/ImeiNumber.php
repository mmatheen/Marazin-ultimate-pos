<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImeiNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'batch_id',
        'imei_number',
        'status'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public static function isDuplicate($imei)
    {
        return self::where('imei_number', $imei)->exists();
    }
}
