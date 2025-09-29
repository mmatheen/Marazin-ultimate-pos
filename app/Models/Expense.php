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
        'supplier_id', // Added supplier_id
        'paid_to',
        'payment_status',
        'payment_method',
        'total_amount',
        'paid_amount',
        'due_amount',
        'tax_amount',
        'discount_type',
        'discount_amount',
        'shipping_charges',
        'note',
        'attachment',
        'created_by',
        'updated_by',
        'location_id',
        'status'
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_charges' => 'decimal:2'
    ];

    protected $appends = ['formatted_date', 'supplier_name'];

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
        return $query->where('payment_status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
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

    // Methods
    public function updatePaymentStatus()
    {
        if ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = 'paid';
            $this->due_amount = 0;
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'partial';
            $this->due_amount = $this->total_amount - $this->paid_amount;
        } else {
            $this->payment_status = 'pending';
            $this->due_amount = $this->total_amount;
        }
        $this->save();
    }

    public function calculateTotal()
    {
        $items_total = $this->expenseItems()->sum('total');
        $total = $items_total + $this->tax_amount + $this->shipping_charges;
        
        if ($this->discount_type == 'percentage') {
            $total = $total - ($total * $this->discount_amount / 100);
        } else {
            $total = $total - $this->discount_amount;
        }
        
        $this->total_amount = $total;
        $this->updatePaymentStatus();
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
        $oldPaidAmount = $this->paid_amount;
        $newPaidAmount = $oldPaidAmount + $paymentAmount;
        
        // Check for overpayment
        if ($newPaidAmount > $this->total_amount) {
            $overpaidAmount = $newPaidAmount - $this->total_amount;
            $this->paid_amount = $this->total_amount;
            $this->due_amount = 0;
            $this->payment_status = 'paid';
            
            // Handle overpayment
            $this->handleOverPayment($overpaidAmount);
            
            // Return the actual payment amount (without overpayment)
            return $this->total_amount - $oldPaidAmount;
        } else {
            $this->paid_amount = $newPaidAmount;
            $this->due_amount = $this->total_amount - $newPaidAmount;
            $this->updatePaymentStatus();
            
            return $paymentAmount;
        }
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