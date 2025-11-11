<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\ChequeStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Centralized Cheque Management Service
 * 
 * This service handles all cheque-related operations including:
 * - Status updates (pending, deposited, cleared, bounced, cancelled)
 * - Bounce processing with ledger entries
 * - Recovery payment processing
 * - Floating balance calculations
 * - Status history tracking
 */
class ChequeService
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    /**
     * Update cheque status with proper ledger handling
     * 
     * @param int $paymentId
     * @param string $newStatus
     * @param string|null $remarks
     * @param float $bankCharges
     * @param int|null $userId
     * @return array
     */
    public function updateChequeStatus($paymentId, $newStatus, $remarks = null, $bankCharges = 0, $userId = null)
    {
        try {
            return DB::transaction(function () use ($paymentId, $newStatus, $remarks, $bankCharges, $userId) {
                $payment = Payment::where('id', $paymentId)
                                 ->where('payment_method', 'cheque')
                                 ->firstOrFail();

                $oldStatus = $payment->cheque_status;
                $statusDate = Carbon::now();

                // Validate status transition
                $this->validateStatusTransition($oldStatus, $newStatus);

                // Update payment record
                $updateData = $this->preparePaymentUpdate($newStatus, $statusDate, $remarks, $bankCharges);
                $payment->update($updateData);

                // Create status history
                $this->createStatusHistory($payment, $oldStatus, $newStatus, $statusDate, $remarks, $bankCharges, $userId);

                // Handle ledger entries based on new status
                $ledgerResult = $this->handleLedgerEntries($payment, $oldStatus, $newStatus, $bankCharges, $userId);

                // Update sale total_paid when cheque status changes
                $this->updateSaleTotalPaid($payment);

                // Prepare response data
                $responseData = [
                    'payment' => $payment->fresh(['sale', 'customer', 'chequeStatusHistory']),
                    'status_change' => [
                        'from' => $oldStatus,
                        'to' => $newStatus,
                        'processed_by' => $userId ? auth()->user()->name ?? 'System' : 'System',
                        'processed_at' => $statusDate
                    ],
                    'ledger_entries' => $ledgerResult
                ];

                // Add customer impact info for bounced cheques
                if ($newStatus === 'bounced' && $payment->customer_id) {
                    $customer = Customer::find($payment->customer_id);
                    if ($customer) {
                        $responseData['customer_impact'] = [
                            'customer_name' => $customer->full_name,
                            'floating_balance' => $customer->getFloatingBalance(),
                            'total_outstanding' => $customer->getTotalOutstanding(),
                            'bill_status' => 'Bill remains PAID (floating balance created)',
                            'follow_up_required' => true
                        ];
                    }
                }

                return $responseData;
            });

        } catch (Exception $e) {
            Log::error('Cheque status update failed', [
                'payment_id' => $paymentId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk update cheque status
     * 
     * @param array $paymentIds
     * @param string $status
     * @param string|null $remarks
     * @param int|null $userId
     * @return array
     */
    public function bulkUpdateChequeStatus(array $paymentIds, $status, $remarks = null, $userId = null)
    {
        $results = ['success' => [], 'failed' => []];
        
        foreach ($paymentIds as $paymentId) {
            try {
                $result = $this->updateChequeStatus($paymentId, $status, $remarks, 0, $userId);
                $results['success'][] = [
                    'payment_id' => $paymentId,
                    'data' => $result
                ];
            } catch (Exception $e) {
                $results['failed'][] = [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Record recovery payment for bounced cheques
     * 
     * @param int $customerId
     * @param float $amount
     * @param string $paymentMethod
     * @param string $paymentDate
     * @param string|null $notes
     * @param string|null $referenceNo
     * @return array
     */
    public function recordRecoveryPayment($customerId, $amount, $paymentMethod, $paymentDate, $notes = null, $referenceNo = null)
    {
        try {
            return DB::transaction(function () use ($customerId, $amount, $paymentMethod, $paymentDate, $notes, $referenceNo) {
                $customer = Customer::withoutGlobalScopes()->findOrFail($customerId);
                
                // Check if customer is walk-in customer
                if (strtolower($customer->full_name) === 'walk-in customer' || 
                    $customer->customer_type === 'walk_in' ||
                    $customer->id === 1) { // Assuming ID 1 is walk-in customer
                    throw new Exception('Recovery payments cannot be processed for walk-in customers');
                }
                
                // Check if customer has bounced cheques
                $bouncedCheques = Payment::where('customer_id', $customerId)
                    ->where('payment_method', 'cheque')
                    ->where('cheque_status', 'bounced')
                    ->count();
                    
                if ($bouncedCheques === 0) {
                    throw new Exception('This customer has no bounced cheques to recover');
                }
                
                $floatingBalance = $customer->getFloatingBalance();

                if ($floatingBalance <= 0) {
                    throw new Exception('Customer has no floating balance to recover');
                }

                if ($amount > $floatingBalance) {
                    throw new Exception("Payment amount (₹{$amount}) exceeds floating balance (₹{$floatingBalance})");
                }

                // Create recovery ledger entry
                $ledgerEntry = $this->unifiedLedgerService->recordFloatingBalanceRecovery(
                    $customerId,
                    $amount,
                    $paymentMethod,
                    $notes ?? "Recovery payment via {$paymentMethod}"
                );

                // Create payment record for tracking
                $payment = Payment::create([
                    'payment_date' => $paymentDate,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'reference_no' => $referenceNo ?? 'RECOVERY-' . time(),
                    'notes' => $notes ?? "Floating balance recovery payment",
                    'payment_type' => 'recovery',
                    'reference_id' => null,
                    'customer_id' => $customerId,
                    'payment_status' => 'completed'
                ]);

                $newFloatingBalance = $customer->getFloatingBalance();
                $totalOutstanding = $customer->getTotalOutstanding();

                return [
                    'payment' => $payment,
                    'ledger_entry' => $ledgerEntry,
                    'balance_update' => [
                        'old_floating_balance' => $floatingBalance,
                        'new_floating_balance' => $newFloatingBalance,
                        'total_outstanding' => $totalOutstanding,
                        'payment_amount' => $amount
                    ]
                ];
            });

        } catch (Exception $e) {
            Log::error('Recovery payment failed', [
                'customer_id' => $customerId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get cheque status history
     * 
     * @param int $paymentId
     * @return array
     */
    public function getChequeStatusHistory($paymentId)
    {
        $payment = Payment::with(['chequeStatusHistory.user'])
                          ->findOrFail($paymentId);

        return [
            'payment' => $payment,
            'history' => $payment->chequeStatusHistory->map(function ($history) {
                return [
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'status_date' => $history->status_date,
                    'remarks' => $history->remarks,
                    'bank_charges' => $history->bank_charges,
                    'changed_by' => $history->user->name ?? 'System',
                    'created_at' => $history->created_at
                ];
            })
        ];
    }

    /**
     * Get customers with floating balance from bounced cheques
     * 
     * @return array
     */
    public function getCustomersWithFloatingBalance()
    {
        try {
            $customersWithFloatingBalance = Customer::whereHas('ledgerEntries', function ($query) {
                $query->whereIn('transaction_type', ['cheque_bounce', 'bank_charges'])
                      ->havingRaw('SUM(debit) > SUM(credit)');
            })->get()->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->full_name,
                    'mobile' => $customer->mobile_no,
                    'floating_balance' => $customer->getFloatingBalance(),
                    'bounced_cheques_count' => $customer->getBouncedChequeSummary()['count'],
                    'total_outstanding' => $customer->getTotalOutstanding(),
                    'risk_score' => $customer->getRiskScore(),
                    'cheque_blocked' => $customer->shouldBlockChequePayments()
                ];
            })->filter(function ($customer) {
                return $customer['floating_balance'] > 0;
            });

            return [
                'customers' => $customersWithFloatingBalance->values(),
                'summary' => [
                    'total_customers' => $customersWithFloatingBalance->count(),
                    'total_floating_amount' => $customersWithFloatingBalance->sum('floating_balance'),
                    'high_risk_customers' => $customersWithFloatingBalance->where('risk_score', '>', 70)->count()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to get customers with floating balance', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Private helper methods

    private function validateStatusTransition($oldStatus, $newStatus)
    {
        $validStatuses = ['pending', 'deposited', 'cleared', 'bounced', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Invalid cheque status: {$newStatus}");
        }

        // Define valid status transitions
        $validTransitions = [
            'pending' => ['deposited', 'cancelled'],
            'deposited' => ['cleared', 'bounced', 'cancelled'],
            'cleared' => [], // No further transitions allowed (final state)
            'bounced' => [], // No further transitions allowed (final state)
            'cancelled' => [] // No further transitions allowed (final state)
        ];

        // Get current status (default to pending if null)
        $currentStatus = $oldStatus ?? 'pending';
        
        // Check if transition is allowed
        if (!isset($validTransitions[$currentStatus])) {
            throw new Exception("Unknown current status: {$currentStatus}");
        }
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            $allowedStatuses = implode(', ', $validTransitions[$currentStatus]);
            throw new Exception("Cannot change status from '{$currentStatus}' to '{$newStatus}'. Allowed transitions: {$allowedStatuses}");
        }
    }

    private function preparePaymentUpdate($newStatus, $statusDate, $remarks, $bankCharges)
    {
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

        return $updateData;
    }

    private function createStatusHistory($payment, $oldStatus, $newStatus, $statusDate, $remarks, $bankCharges, $userId)
    {
        ChequeStatusHistory::create([
            'payment_id' => $payment->id,
            'old_status' => (string)$oldStatus,
            'new_status' => (string)$newStatus,
            'status_date' => $statusDate->toDateString(),
            'remarks' => $remarks,
            'bank_charges' => (float)$bankCharges,
            'changed_by' => $userId,
        ]);
    }

    private function handleLedgerEntries($payment, $oldStatus, $newStatus, $bankCharges, $userId)
    {
        $ledgerEntries = [];

        // Handle bounced cheques - create floating balance
        if ($newStatus === 'bounced') {
            $ledgerEntries = $this->handleBouncedChequeLedger($payment, $bankCharges, $userId);
        }
        // Handle cleared cheques
        elseif ($newStatus === 'cleared') {
            // Check if this is a recovery cheque (has recovery_for_payment_id)
            if ($payment->recovery_for_payment_id && $payment->payment_type === 'recovery') {
                // This is a recovery cheque being cleared - create ledger entry to reduce floating balance
                $ledgerEntries = $this->handleRecoveryChequeClearedLedger($payment, $userId);
            } else {
                // Regular cheque cleared - original payment entry in ledger is valid
                Log::info('Regular cheque cleared successfully', [
                    'payment_id' => $payment->id,
                    'cheque_number' => $payment->cheque_number
                ]);
            }
        }

        return $ledgerEntries;
    }

    private function handleBouncedChequeLedger($payment, $bankCharges, $userId)
    {
        if (!$payment->sale || !$payment->customer_id) {
            return [];
        }

        $referenceNo = "BOUNCE-{$payment->cheque_number}-{$payment->id}";
        $transactionDate = Carbon::now('Asia/Colombo');
        $ledgerEntries = [];

        // Check for duplicate bounce processing
        $existingBounce = Ledger::where('user_id', $payment->customer_id)
            ->where('contact_type', 'customer')
            ->where('reference_no', $referenceNo)
            ->where('transaction_type', 'cheque_bounce')
            ->exists();

        if ($existingBounce) {
            Log::warning('Cheque bounce already processed - skipping duplicate', [
                'payment_id' => $payment->id,
                'reference_no' => $referenceNo
            ]);
            return [];
        }

        // 1. Create bounced cheque debit entry (increases customer floating balance)
        $bounceEntry = Ledger::createEntry([
            'user_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => 'cheque_bounce',
            'amount' => $payment->amount,
            'notes' => "Cheque bounce - {$payment->cheque_number} (Bill {$payment->sale->invoice_no} remains settled)"
        ]);
        $ledgerEntries[] = $bounceEntry;

        // 2. Add bank charges as separate debit entry
        if ($bankCharges > 0) {
            $chargesEntry = Ledger::createEntry([
                'user_id' => $payment->customer_id,
                'contact_type' => 'customer',
                'transaction_date' => $transactionDate,
                'reference_no' => $referenceNo . '-CHARGES',
                'transaction_type' => 'bank_charges',
                'amount' => $bankCharges,
                'notes' => "Bank charges for bounced cheque - {$payment->cheque_number}"
            ]);
            $ledgerEntries[] = $chargesEntry;
        }

        Log::info('Cheque bounce processed successfully', [
            'payment_id' => $payment->id,
            'cheque_number' => $payment->cheque_number,
            'bounce_amount' => $payment->amount,
            'bank_charges' => $bankCharges,
            'sale_id' => $payment->sale->id,
            'customer_id' => $payment->customer_id,
            'ledger_entries_created' => count($ledgerEntries)
        ]);

        return $ledgerEntries;
    }

    /**
     * Handle ledger entries when a recovery cheque is cleared
     */
    private function handleRecoveryChequeClearedLedger($payment, $userId)
    {
        if (!$payment->customer_id || $payment->payment_type !== 'recovery') {
            return [];
        }

        $referenceNo = "RECOVERY-CLEARED-{$payment->cheque_number}-{$payment->id}";
        $transactionDate = Carbon::now('Asia/Colombo');
        $ledgerEntries = [];

        // Check for duplicate processing
        $existingEntry = Ledger::where('user_id', $payment->customer_id)
            ->where('contact_type', 'customer')
            ->where('reference_no', $referenceNo)
            ->where('transaction_type', 'bounce_recovery')
            ->exists();

        if ($existingEntry) {
            Log::warning('Recovery cheque clearing already processed - skipping duplicate', [
                'payment_id' => $payment->id,
                'reference_no' => $referenceNo
            ]);
            return [];
        }

        // Create recovery entry to reduce floating balance
        $recoveryEntry = Ledger::createEntry([
            'user_id' => $payment->customer_id,
            'contact_type' => 'customer',
            'transaction_date' => $transactionDate,
            'reference_no' => $referenceNo,
            'transaction_type' => 'bounce_recovery',
            'amount' => $payment->amount,
            'notes' => "Recovery cheque cleared - {$payment->cheque_number} (reduces floating balance)"
        ]);
        $ledgerEntries[] = $recoveryEntry;

        Log::info('Recovery cheque cleared and ledger entry created', [
            'payment_id' => $payment->id,
            'cheque_number' => $payment->cheque_number,
            'recovery_amount' => $payment->amount,
            'customer_id' => $payment->customer_id,
            'original_bounced_payment_id' => $payment->recovery_for_payment_id
        ]);

        return $ledgerEntries;
    }

    /**
     * Update sale's total_paid amount based on completed payments (excluding pending cheques)
     * 
     * @param Payment $payment
     * @return bool
     */
    private function updateSaleTotalPaid($payment)
    {
        if ($payment->payment_type !== 'sale' || !$payment->reference_id) {
            return false;
        }

        try {
            // Calculate total paid amount excluding pending cheques
            $totalPaid = DB::table('payments')
                ->where('reference_id', $payment->reference_id)
                ->where('payment_type', 'sale')
                ->where(function($query) {
                    $query->where('payment_method', '!=', 'cheque')
                          ->orWhere(function($subQuery) {
                              $subQuery->where('payment_method', 'cheque')
                                       ->whereIn('cheque_status', ['cleared', 'deposited']);
                          });
                })
                ->sum('amount');

            // Get the sale
            $sale = DB::table('sales')->where('id', $payment->reference_id)->first();
            
            if ($sale) {
                $totalDue = $sale->final_total - $totalPaid;
                
                // Update sale with correct amounts
                DB::table('sales')
                    ->where('id', $payment->reference_id)
                    ->update([
                        'total_paid' => $totalPaid,
                        'total_due' => max(0, $totalDue),
                        'updated_at' => now()
                    ]);

                Log::info('Updated sale total_paid after cheque status change', [
                    'sale_id' => $payment->reference_id,
                    'payment_id' => $payment->id,
                    'cheque_status' => $payment->cheque_status,
                    'total_paid' => $totalPaid,
                    'total_due' => max(0, $totalDue)
                ]);

                return true;
            }

        } catch (Exception $e) {
            Log::error('Failed to update sale total_paid after cheque status change', [
                'payment_id' => $payment->id,
                'sale_id' => $payment->reference_id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }
}