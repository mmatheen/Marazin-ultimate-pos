<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpensePayment extends Model
{
    use HasFactory, LocationTrait;
    
    protected $table = 'expense_payments';
    
    protected $fillable = [
        'expense_id',
        'payment_date',
        'payment_method',
        'amount',
        'reference_no',
        'note',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];

    // Relationships
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}