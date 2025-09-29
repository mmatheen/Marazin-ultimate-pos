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
        'item_name',
        'description',
        'quantity',
        'unit_price',
        'total',
        'tax_rate',
        'tax_amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2'
    ];

    // Relationships
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    // Mutators
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = $this->quantity * $this->unit_price;
    }

    // Methods
    public function calculateTotal()
    {
        $total = $this->quantity * $this->unit_price;
        $tax_amount = $total * ($this->tax_rate / 100);
        
        $this->attributes['tax_amount'] = $tax_amount;
        $this->attributes['total'] = $total + $tax_amount;
        
        return $this->attributes['total'];
    }
}