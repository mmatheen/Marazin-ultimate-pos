<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SupplierBalanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'expense_id', 
        'payment_id',
        'transaction_type',
        'amount',
        'debit_credit',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
        'created_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function payment()
    {
        return $this->belongsTo(ExpensePayment::class, 'payment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'Rs.' . number_format($this->amount, 2);
    }

    public function getFormattedBalanceBeforeAttribute()
    {
        return 'Rs.' . number_format($this->balance_before, 2);
    }

    public function getFormattedBalanceAfterAttribute()
    {
        return 'Rs.' . number_format($this->balance_after, 2);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d-m-Y H:i:s');
    }

    // Scopes
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeForExpense($query, $expenseId)
    {
        return $query->where('expense_id', $expenseId);
    }

    public function scopeForPayment($query, $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeCredits($query)
    {
        return $query->where('debit_credit', 'credit');
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_credit', 'debit');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Helper methods
    public function getTransactionTypeColorAttribute()
    {
        $colors = [
            'expense_overpayment' => 'success',
            'expense_edit' => 'warning',
            'payment_adjustment' => 'info',
            'payment_edit' => 'primary',
            'payment_delete' => 'danger',
            'manual_adjustment' => 'secondary'
        ];

        return $colors[$this->transaction_type] ?? 'dark';
    }

    public function getTransactionTypeTextAttribute()
    {
        $texts = [
            'expense_overpayment' => 'Expense Overpayment',
            'expense_edit' => 'Expense Edit',
            'payment_adjustment' => 'Payment Adjustment',
            'payment_edit' => 'Payment Edit',
            'payment_delete' => 'Payment Delete',
            'manual_adjustment' => 'Manual Adjustment'
        ];

        return $texts[$this->transaction_type] ?? ucfirst(str_replace('_', ' ', $this->transaction_type));
    }
}