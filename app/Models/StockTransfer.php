<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class StockTransfer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'from_location_id',
        'to_location_id',
        'transfer_date',
        'reference_no',
        'final_total',
        'note',
        'status'
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly($this->fillable)
            ->useLogName('stock_transfer')
            ->logOnlyDirty();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "Stock transfer has been {$eventName}";
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }
    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function stockTransferProducts()
    {
        return $this->hasMany(StockTransferProduct::class);
    }
}
