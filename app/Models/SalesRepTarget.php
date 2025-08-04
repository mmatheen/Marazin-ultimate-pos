<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRepTarget extends Model
{
    use HasFactory;

    protected $table = 'sales_rep_targets';

    protected $fillable = [
        'sales_rep_id',
        'target_amount',
        'achieved_amount',
        'target_month',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'achieved_amount' => 'decimal:2',
        'target_month' => 'date',
    ];

    public function salesRep()
    {
        return $this->belongsTo(SalesRep::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
