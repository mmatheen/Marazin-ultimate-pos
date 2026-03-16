<?php

namespace App\Models;

use App\Traits\LocationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory, LocationTrait;

    protected $table = 'expenses';

    protected $fillable = [
        'expense_no',
        'date',
        'reference_no',
        'expense_parent_category_id',
        'expense_sub_category_id',
        'supplier_id',
        'paid_to',
        'total_amount',
        'note',
        'attachment',
        'created_by',
        'location_id',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2'
    ];

    protected $appends = ['formatted_date', 'supplier_name', 'paid_amount', 'due_amount', 'payment_status'];

    /**
     * Get formatted date for display
     */
    public function getFormattedDateAttribute()
    {
        return $this->date ? $this->date->format('d-m-Y') : '';
    }

    // Relationships
    public function expenseParentCategory()
    {
        return $this->belongsTo(ExpenseParentCategory::class, 'expense_parent_category_id');
    }

    public function expenseSubCategory()
    {
        return $this->belongsTo(ExpenseSubCategory::class, 'expense_sub_category_id');
    }

    public function expenseItems()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function balanceLogs()
    {
        return $this->hasMany(SupplierBalanceLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->whereRaw('(SELECT COALESCE(SUM(ep.amount), 0) FROM expense_payments ep WHERE ep.expense_id = expenses.id) = 0');
    }

    public function scopePartial($query)
    {
        return $query->whereRaw('(SELECT COALESCE(SUM(ep.amount), 0) FROM expense_payments ep WHERE ep.expense_id = expenses.id) > 0')
            ->whereRaw('(SELECT COALESCE(SUM(ep.amount), 0) FROM expense_payments ep WHERE ep.expense_id = expenses.id) < expenses.total_amount');
    }

    public function scopePaid($query)
    {
        return $query->whereRaw('(SELECT COALESCE(SUM(ep.amount), 0) FROM expense_payments ep WHERE ep.expense_id = expenses.id) >= expenses.total_amount');
    }

    public function scopeByDateRange($query, $start_date, $end_date)
    {
        return $query->whereBetween('date', [$start_date, $end_date]);
    }

    public function scopeByCategory($query, $category_id)
    {
        return $query->where('expense_parent_category_id', $category_id);
    }

    public function scopeBySubCategory($query, $sub_category_id)
    {
        return $query->where('expense_sub_category_id', $sub_category_id);
    }

    // Accessors & Mutators
    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getPaymentStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->payment_status));
    }

    public function getPaidAmountAttribute()
    {
        if (array_key_exists('paid_amount_sum', $this->attributes)) {
            return (float) $this->attributes['paid_amount_sum'];
        }

        return (float) $this->payments()->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        $due = (float) $this->total_amount - (float) $this->paid_amount;
        return $due > 0 ? $due : 0.0;
    }

    public function getPaymentStatusAttribute()
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->total_amount;

        if ($paid <= 0) {
            return 'pending';
        }

        if ($paid >= $total) {
            return 'paid';
        }

        return 'partial';
    }

    // Methods
    public function updatePaymentStatus()
    {
        return $this->payment_status;
    }

    public function calculateTotal()
    {
        $total = (float) $this->expenseItems()->sum('amount');
        $this->total_amount = $total;
        $this->save();
        return $total;
    }

    // ==================== SUPPLIER BALANCE TRACKING METHODS ====================

    /**
     * Handle expense amount changes and update supplier balance
     */
    public function handleExpenseAmountChange($oldAmount, $newAmount, $reason = 'Expense amount updated')
    {
        if ($this->supplier_id && $oldAmount != $newAmount) {
            $supplier = $this->supplier;
            if ($supplier) {
                return $supplier->handleExpenseAmountChange($oldAmount, $newAmount, $this->id, $this->expense_no);
            }
        }
        return null;
    }

    /**
     * Handle overpayment scenarios
     */
    public function handleOverPayment($overpaidAmount)
    {
        if ($this->supplier_id && $overpaidAmount > 0) {
            $supplier = $this->supplier;
            if ($supplier) {
                return $supplier->handleOverpayment($overpaidAmount, $this->id, $this->expense_no);
            }
        }
        return null;
    }

    /**
     * Process payment and handle balance changes
     */
    public function processPayment($paymentAmount, $paymentData = [])
    {
        return $paymentAmount;
    }

    /**
     * Handle payment edit
     */
    public function handlePaymentEdit($paymentId, $oldAmount, $newAmount)
    {
        if ($this->supplier_id && $oldAmount != $newAmount) {
            $supplier = $this->supplier;
            if ($supplier) {
                return $supplier->handlePaymentEdit($oldAmount, $newAmount, $paymentId, $this->expense_no);
            }
        }
        return null;
    }

    /**
     * Handle payment deletion
     */
    public function handlePaymentDeletion($paymentId, $deletedAmount)
    {
        if ($this->supplier_id && $deletedAmount > 0) {
            $supplier = $this->supplier;
            if ($supplier) {
                return $supplier->handlePaymentDeletion($deletedAmount, $paymentId, $this->expense_no);
            }
        }
        return null;
    }

    /**
     * Get supplier name for display
     */
    public function getSupplierNameAttribute()
    {
        return $this->supplier ? $this->supplier->full_name : ($this->paid_to ?? 'N/A');
    }

    /**
     * Check if expense has overpayments
     */
    public function hasOverpayments()
    {
        if (!$this->supplier_id) return false;

        return $this->balanceLogs()->where('transaction_type', 'expense_overpayment')->exists();
    }

    /**
     * Get total overpaid amount for this expense
     */
    public function getTotalOverpaidAmount()
    {
        if (!$this->supplier_id) return 0;

        return $this->balanceLogs()
            ->where('transaction_type', 'expense_overpayment')
            ->sum('amount');
    }

    /**
     * Get expense balance summary including all related transactions
     */
    public function getBalanceSummary()
    {
        $summary = [
            'expense_no' => $this->expense_no,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'payment_status' => $this->payment_status,
            'supplier_name' => $this->supplier_name,
            'overpaid_amount' => $this->getTotalOverpaidAmount(),
            'has_overpayments' => $this->hasOverpayments(),
            'balance_transactions' => []
        ];

        if ($this->supplier_id) {
            $summary['balance_transactions'] = $this->balanceLogs()
                ->with(['creator'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'type' => $log->transaction_type_text,
                        'amount' => $log->formatted_amount,
                        'description' => $log->description,
                        'date' => $log->formatted_date,
                        'created_by' => $log->creator ? $log->creator->name : 'System'
                    ];
                });
        }

        return $summary;
    }
}
