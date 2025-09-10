<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\ChequeStatusHistory;

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
    ];

    protected $dates = [
        'payment_date',
        'cheque_received_date',
        'cheque_valid_date',
        'cheque_clearance_date',
        'cheque_bounce_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'bank_charges' => 'decimal:2',
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

    public function chequeStatusHistory()
    {
        return $this->hasMany(ChequeStatusHistory::class);
    }

    public function chequeReminders()
    {
        return $this->hasMany(ChequeReminder::class);
    }

    // Scopes for cheque management
    public function scopeChequePayments($query)
    {
        return $query->where('payment_method', 'cheque');
    }

    public function scopePendingCheques($query)
    {
        return $query->chequePayments()->where('cheque_status', 'pending');
    }

    public function scopeClearedCheques($query)
    {
        return $query->chequePayments()->where('cheque_status', 'cleared');
    }

    public function scopeBouncedCheques($query)
    {
        return $query->chequePayments()->where('cheque_status', 'bounced');
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

    // Helper methods
    public function updateChequeStatus($newStatus, $remarks = null, $bankCharges = 0, $userId = null)
    {
        $oldStatus = $this->cheque_status;
        $statusDate = Carbon::now();

        // Update payment based on status
        $updateData = [
            'cheque_status' => $newStatus,
            'bank_charges' => $bankCharges,
        ];

        switch ($newStatus) {
            case 'cleared':
                $updateData['cheque_clearance_date'] = $statusDate;
                $updateData['payment_status'] = 'completed';
                break;
            case 'bounced':
                $updateData['cheque_bounce_date'] = $statusDate;
                $updateData['cheque_bounce_reason'] = $remarks;
                $updateData['payment_status'] = 'failed';
                break;
            case 'deposited':
                $updateData['payment_status'] = 'pending';
                break;
            case 'cancelled':
                $updateData['payment_status'] = 'cancelled';
                break;
        }

        $this->update($updateData);

        // Create status history
        ChequeStatusHistory::create([
            'payment_id' => $this->id,
            'old_status' => (string)$oldStatus,
            'new_status' => (string)$newStatus,
            'status_date' => $statusDate->toDateString(),
            'remarks' => $remarks,
            'bank_charges' => (float)$bankCharges,
            'changed_by' => $userId,
        ]);

        // Update related sale totals
        if ($this->sale) {
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
            // This bypasses the generated column issue
            DB::table('sales')
                ->where('id', $sale->id)
                ->update([
                    'total_paid' => $newTotalPaid,
                    'payment_status' => $paymentStatus
                ]);
        }

        return $this;
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
}
