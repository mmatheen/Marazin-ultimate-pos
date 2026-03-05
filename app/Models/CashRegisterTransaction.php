<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterTransaction extends Model
{
    use HasFactory;

    protected $table = 'cash_register_transactions';

    protected $fillable = [
        'cash_register_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const TYPE_PAY_IN = 'pay_in';
    public const TYPE_PAY_OUT = 'pay_out';
    public const TYPE_EXPENSE = 'expense';
    public const TYPE_SALE_CASH = 'sale_cash';
    public const TYPE_REFUND_CASH = 'refund_cash';

    // Relationships
    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Resolve reference model by type (e.g. sale -> Sale, sales_return -> SalesReturn, expense -> Expense).
     *
     * @return Sale|SalesReturn|Expense|Model|null
     */
    public function getReferenceModel()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }
        $map = [
            'sale'         => Sale::class,
            'sales_return' => SalesReturn::class,
            'expense'      => Expense::class,
        ];
        $class = $map[$this->reference_type] ?? null;

        return $class ? $class::find($this->reference_id) : null;
    }

    // Scopes
    public function scopePayIn($query)
    {
        return $query->where('type', self::TYPE_PAY_IN);
    }

    public function scopePayOut($query)
    {
        return $query->where('type', self::TYPE_PAY_OUT);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeSaleCash($query)
    {
        return $query->where('type', self::TYPE_SALE_CASH);
    }

    public function scopeRefundCash($query)
    {
        return $query->where('type', self::TYPE_REFUND_CASH);
    }

    public function scopeOutflows($query)
    {
        return $query->whereIn('type', [self::TYPE_PAY_OUT, self::TYPE_EXPENSE, self::TYPE_REFUND_CASH]);
    }

    public function scopeInflows($query)
    {
        return $query->whereIn('type', [self::TYPE_PAY_IN, self::TYPE_SALE_CASH]);
    }
}
