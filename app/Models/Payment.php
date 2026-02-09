<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ChequeStatusHistory;
use App\Models\ChequeReminder;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'payment_type',
        'reference_id',
        'supplier_id',
        'customer_id',
        'card_number',
        'card_holder_name',
        'card_expiry_month',
        'card_expiry_year',
        'card_security_code',
        'cheque_number',
        'cheque_bank_branch',
        'cheque_received_date',
        'cheque_valid_date',
        'cheque_given_by',
        // Enhanced cheque fields
        'cheque_status',
        'cheque_clearance_date',
        'cheque_bounce_date',
        'cheque_bounce_reason',
        'bank_charges',
        'payment_status',
        // Recovery system fields
        'recovery_for_payment_id',
        'bank_account_number',
        'card_type',
        'actual_payment_method',
        'created_by',
        'updated_by',
        // Status tracking fields
        'status',
        'original_amount',
        'edited_by',
        'edited_at',
        'edit_reason',
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'cheque_status' => 'pending',
        'payment_status' => 'completed',
        'bank_charges' => 0.00,
        'status' => 'active',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // ✅ CRITICAL FIX: Add global scope to exclude deleted payments by default
        static::addGlobalScope('excludeDeleted', function ($query) {
            $query->where('status', '!=', 'deleted');
        });

        static::creating(function ($payment) {
            // Don't set cheque_status to null - let default value handle it
            if ($payment->cheque_status === null) {
                unset($payment->cheque_status);
            }

            // Set appropriate cheque_status based on payment method
            if (!isset($payment->cheque_status) && isset($payment->payment_method)) {
                $payment->cheque_status = ($payment->payment_method === 'cheque') ? 'pending' : 'pending';
            }
        });
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_charges' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'payment_date' => 'date',
        'cheque_received_date' => 'date',
        'cheque_valid_date' => 'date',
        'cheque_clearance_date' => 'date',
        'cheque_bounce_date' => 'date',
        'edited_at' => 'datetime',
    ];

    // Relationships
    public function reference()
    {
        return $this->morphTo();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'reference_id', 'id');
    }

    public function purchase()
    {
        return $this->belongsTo(\App\Models\Purchase::class, 'reference_id', 'id');
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(\App\Models\PurchaseReturn::class, 'reference_id', 'id');
    }

    public function chequeStatusHistory()
    {
        return $this->hasMany(ChequeStatusHistory::class);
    }

    public function chequeReminders()
    {
        return $this->hasMany(ChequeReminder::class);
    }

    // Recovery system relationships
    public function originalPayment()
    {
        return $this->belongsTo(Payment::class, 'recovery_for_payment_id');
    }

    public function recoveryPayments()
    {
        return $this->hasMany(Payment::class, 'recovery_for_payment_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // Status tracking relationships
    public function editedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    // Status scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeEdited($query)
    {
        return $query->where('status', 'edited');
    }

    public function scopeDeleted($query)
    {
        return $query->withoutGlobalScope('excludeDeleted')->where('status', 'deleted');
    }

    public function scopeWithDeleted($query)
    {
        return $query->withoutGlobalScope('excludeDeleted');
    }

    // Scopes for cheque management
    public function scopeChequePayments($query)
    {
        return $query->where('payment_method', 'cheque');
    }

    public function scopePendingCheques($query)
    {
        return $query->chequePayments()
                    ->where('cheque_status', 'pending')
                    ->whereNull('recovery_for_payment_id') // Exclude recovery payments
                    ->where('payment_type', '!=', 'recovery'); // Exclude recovery type
    }

    public function scopeDepositedCheques($query)
    {
        return $query->chequePayments()
                    ->where('cheque_status', 'deposited')
                    ->whereNull('recovery_for_payment_id') // Exclude recovery payments
                    ->where('payment_type', '!=', 'recovery'); // Exclude recovery type
    }

    public function scopeClearedCheques($query)
    {
        return $query->chequePayments()
                    ->where('cheque_status', 'cleared')
                    ->whereNull('recovery_for_payment_id') // Exclude recovery payments
                    ->where('payment_type', '!=', 'recovery'); // Exclude recovery type
    }

    public function scopeBouncedCheques($query)
    {
        return $query->chequePayments()
                    ->where('cheque_status', 'bounced')
                    ->whereNull('recovery_for_payment_id') // Exclude recovery payments
                    ->where('payment_type', '!=', 'recovery'); // Exclude recovery type
    }

    public function scopeDueSoon($query, $days = 7)
    {
        return $query->pendingCheques()
                    ->where('cheque_valid_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->pendingCheques()
                    ->where('cheque_valid_date', '<', Carbon::now());
    }

    public function scopeRecoveryPayments($query)
    {
        return $query->where('payment_type', 'recovery');
    }

    public function scopeOriginalPayments($query)
    {
        return $query->whereNull('recovery_for_payment_id');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Helper methods

    /**
     * Update cheque status - DEPRECATED: Use ChequeService instead
     * This method is maintained for backward compatibility only
     *
     * @deprecated Use \App\Services\ChequeService::updateChequeStatus() instead
     */
    public function updateChequeStatus($newStatus, $remarks = null, $bankCharges = 0, $userId = null)
    {
        // Delegate to ChequeService for centralized handling
        $chequeService = app(\App\Services\ChequeService::class);

        $result = $chequeService->updateChequeStatus(
            $this->id,
            $newStatus,
            $remarks,
            $bankCharges,
            $userId
        );

        // Refresh the model instance to reflect changes
        $this->refresh();

        return $this;
    }

    /**
     * Update sale totals for non-bounced status changes
     */
    private function updateRelatedSaleTotals()
    {
        if (!$this->sale) return;

        $sale = $this->sale;

        // Calculate new totals excluding bounced cheques
        $totalReceived = $sale->payments()->sum('amount');
        $bouncedCheques = $sale->payments()
            ->where('payment_method', 'cheque')
            ->where('cheque_status', 'bounced')
            ->sum('amount');
        $newTotalPaid = $totalReceived - $bouncedCheques;

        // Update payment status
        $paymentStatus = 'Due';
        if ($newTotalPaid >= $sale->final_total) {
            $paymentStatus = 'Paid';
        } elseif ($newTotalPaid > 0) {
            $paymentStatus = 'Partial';
        }

        // Force update the sale using direct DB update
        DB::table('sales')
            ->where('id', $sale->id)
            ->update([
                'total_paid' => $newTotalPaid,
                'payment_status' => $paymentStatus
            ]);
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->payment_method !== 'cheque') {
            return '<span class="badge bg-success">Completed</span>';
        }

        $badges = [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'deposited' => '<span class="badge bg-info">Deposited</span>',
            'cleared' => '<span class="badge bg-success">Cleared</span>',
            'bounced' => '<span class="badge bg-danger">Bounced</span>',
            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
        ];

        return $badges[$this->cheque_status] ?? '<span class="badge bg-light">Unknown</span>';
    }

    public function getDaysUntilDueAttribute()
    {
        if ($this->payment_method !== 'cheque' || $this->cheque_status !== 'pending') {
            return null;
        }

        return Carbon::now()->diffInDays($this->cheque_valid_date, false);
    }

    public function getIsOverdueAttribute()
    {
        return $this->payment_method === 'cheque' &&
               $this->cheque_status === 'pending' &&
               $this->cheque_valid_date < Carbon::now();
    }

    public function createReminders()
    {
        if ($this->payment_method !== 'cheque' || !$this->cheque_valid_date) {
            return;
        }

        $validDate = Carbon::parse($this->cheque_valid_date);
        $reminders = [];

        // Due soon reminder (3 days before)
        if ($validDate->copy()->subDays(3)->isFuture()) {
            $reminders[] = [
                'payment_id' => $this->id,
                'reminder_type' => 'due_soon',
                'reminder_date' => $validDate->copy()->subDays(3)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Overdue reminder (1 day after due date)
        $reminders[] = [
            'payment_id' => $this->id,
            'reminder_type' => 'overdue',
            'reminder_date' => $validDate->copy()->addDay()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (!empty($reminders)) {
            DB::table('cheque_reminders')->insert($reminders);
        }
    }

    // Recovery system helper methods

    /**
     * Get recovery chain summary for bounced payment
     */
    public function getRecoveryChain()
    {
        if ($this->payment_type === 'recovery') {
            // For recovery payments, get the original payment's chain
            return $this->originalPayment ? $this->originalPayment->getRecoveryChain() : [];
        }

        $recoveries = $this->recoveryPayments()->with(['createdBy', 'updatedBy'])->get();

        return [
            'original_payment' => [
                'id' => $this->id,
                'amount' => $this->amount,
                'cheque_number' => $this->cheque_number,
                'cheque_status' => $this->cheque_status,
                'bounce_date' => $this->cheque_bounce_date,
                'bounce_reason' => $this->cheque_bounce_reason,
                'bank_charges' => $this->bank_charges ?? 0,
            ],
            'recoveries' => $recoveries->map(function ($recovery) {
                return [
                    'id' => $recovery->id,
                    'amount' => abs($recovery->amount), // Show as positive
                    'payment_method' => $recovery->payment_method,
                    'actual_payment_method' => $recovery->actual_payment_method,
                    'payment_date' => $recovery->payment_date,
                    'payment_status' => $recovery->payment_status,
                    'created_by' => $recovery->createdBy->name ?? 'Unknown',
                    'created_at' => $recovery->created_at,
                    // Method specific details
                    'card_number' => $recovery->card_number ? '****' . substr($recovery->card_number, -4) : null,
                    'card_type' => $recovery->card_type,
                    'bank_account' => $recovery->bank_account_number,
                    'cheque_number' => $recovery->cheque_number,
                    'cheque_status' => $recovery->cheque_status,
                    'reference_no' => $recovery->reference_no,
                    'notes' => $recovery->notes,
                ];
            }),
            'total_original' => $this->amount + ($this->bank_charges ?? 0),
            'total_recovered' => $recoveries->sum(function ($recovery) {
                return $recovery->payment_status === 'completed' ? abs($recovery->amount) : 0;
            }),
            'pending_recovery' => $recoveries->where('payment_status', 'pending')->sum(function ($recovery) {
                return abs($recovery->amount);
            }),
        ];
    }

    /**
     * Check if payment is fully recovered
     */
    public function isFullyRecovered()
    {
        if ($this->payment_type === 'recovery') {
            return false; // Recovery payments themselves can't be "recovered"
        }

        $chain = $this->getRecoveryChain();
        return $chain['total_recovered'] >= $chain['total_original'];
    }

    /**
     * Get remaining recovery amount needed
     */
    public function getRemainingRecoveryAmount()
    {
        if ($this->payment_type === 'recovery') {
            return 0;
        }

        $chain = $this->getRecoveryChain();
        return max(0, $chain['total_original'] - $chain['total_recovered']);
    }

    /**
     * Mark payment as edited
     */
    public function markAsEdited($newAmount, $editReason = '', $editedBy = null)
    {
        $this->update([
            'original_amount' => $this->original_amount ?: $this->amount,
            'amount' => $newAmount,
            'status' => 'edited',
            'edit_reason' => $editReason,
            'edited_by' => $editedBy,
            'edited_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark payment as deleted
     */
    public function markAsDeleted($deleteReason = '', $deletedBy = null)
    {
        $this->update([
            'status' => 'deleted',
            'payment_status' => 'cancelled',
            'notes' => ($this->notes ?? '') . ' | [DELETED by user #' . ($deletedBy ?? auth()->id()) . ': ' . ($deleteReason ?? 'No reason provided') . ' - ' . now()->format('Y-m-d H:i:s') . ']'
        ]);

        return $this;
    }

    /**
     * Check if payment is active (not edited or deleted)
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if payment has been edited
     */
    public function isEdited()
    {
        return $this->status === 'edited';
    }

    /**
     * Check if payment has been deleted
     */
    public function isDeleted()
    {
        return $this->status === 'deleted';
    }

    /**
     * Get the amount that was originally paid (before any edits)
     */
    public function getOriginalAmountAttribute($value)
    {
        return $value ?: $this->amount;
    }
}
