<?php

namespace App\Models;

use App\Traits\CustomLogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class StockAdjustment extends Model
{
    use HasFactory, LogsActivity, CustomLogsActivity;

    protected string $customLogName = 'stock_adjustment';

    protected $fillable = [
        'reference_no',
        'date',
        'location_id',
        'adjustment_type',
        'total_amount_recovered',
        'reason',
        'user_id',
    ];

    // Relationship with AdjustmentProduct
    public function adjustmentProducts()
    {
        return $this->hasMany(AdjustmentProduct::class);
    }

    // Relationship with Location
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}