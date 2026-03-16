<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseItem extends Model
{
    use HasFactory, LocationTrait;

    protected $table = 'expense_items';

    protected $fillable = [
        'expense_id',
        'description',
        'amount',
        'location_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    // Relationships
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    // Direct expense line: one amount per row.
}
