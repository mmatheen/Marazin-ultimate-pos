<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\Ledger;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
    }

    /**
     * Parse flexible date formats for cheque dates
     *
     * @param string|null $dateString
     * @return string|null
     */
    private function parseFlexibleDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            // Handle different date formats
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateString)) {
                // DD-MM-YYYY format
                return Carbon::createFromFormat('d-m-Y', $dateString)->format('Y-m-d');
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                // YYYY-MM-DD format (already correct)
                return $dateString;
            } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateString)) {
                // DD/MM/YYYY format
                return Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
            } else {
                // Try to parse with Carbon (fallback)
                return Carbon::parse($dateString)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If date parsing fails, return null (no validation error)
            Log::warning('Failed to parse date: ' . $dateString . ' - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Record a sale payment
     *
     * @param array $paymentData
     * @param Sale $sale
     * @return Payment
     */
    public function recordSalePayment(array $paymentData, Sale $sale): Payment
    {
        return DB::transaction(function () use ($paymentData, $sale) {
            $paymentDate = isset($paymentData['payment_date'])
                ? Carbon::parse($paymentData['payment_date'])
                : now();

            $payment = Payment::create([
                'payment_date' => $paymentDate,
                'amount' => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'],
                'reference_no' => $paymentData['reference_no'] ?? $sale->invoice_no,
                'notes' => $paymentData['notes'] ?? null,
                'payment_type' => 'sale',
                'reference_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'card_number' => $paymentData['card_number'] ?? null,
                'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                'card_security_code' => $paymentData['card_security_code'] ?? null,
                'cheque_number' => $paymentData['cheque_number'] ?? null,
                'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                'cheque_received_date' => $this->parseFlexibleDate($paymentData['cheque_received_date'] ?? null),
                'cheque_valid_date' => $this->parseFlexibleDate($paymentData['cheque_valid_date'] ?? null),
                'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                'cheque_status' => $paymentData['cheque_status'] ?? 'pending',
                'payment_status' => $paymentData['payment_status'] ?? 'completed',
            ]);

            // Record payment in ledger (Enhanced: Include all customers for proper tracking)
            // Skip only if explicitly requested or for specific statuses
            $skipLedger = ($paymentData['skip_ledger'] ?? false) ||
                          ($sale->status === 'draft') ||
                          ($sale->status === 'quotation');

            if (!$skipLedger) {
                $this->unifiedLedgerService->recordSalePayment($payment, $sale);
            }

            // Update sale payment status
            $this->updateSalePaymentStatus($sale);

            return $payment;
        });
    }

    /**
     * Edit existing payment with proper ledger management
     * ✅ ENHANCED: Includes reversal entries, new data, and status-based handling
     *
     * @param Payment $payment
     * @param array $newPaymentData
     * @return Payment
     */
    public function editSalePayment(Payment $payment, array $newPaymentData): Payment
    {
        return DB::transaction(function () use ($payment, $newPaymentData) {
            $oldAmount = $payment->amount;
            $oldStatus = $payment->status;
            $oldPaymentStatus = $payment->payment_status;
            $newAmount = $newPaymentData['amount'];
            $newPaymentStatus = $newPaymentData['payment_status'] ?? 'completed';

            $sale = Sale::findOrFail($payment->reference_id);

            // ✅ STEP 1: Mark original payment as deleted (soft delete for audit trail)
            $payment->update([
                'status' => 'deleted',
                'notes' => ($payment->notes ?? '') . ' | [DELETED: Payment edited on ' . now()->format('Y-m-d H:i:s') . ']'
            ]);

            // ✅ STEP 2: Create ledger reversal entry for old payment (Skip Walk-In customers)
            if ($sale->customer_id != 1) {
                // Mark original ledger entry as reversed
                $originalLedgerEntry = Ledger::where('reference_no', $payment->reference_no)
                    ->where('transaction_type', 'payments')
                    ->where('contact_id', $payment->customer_id)
                    ->where('status', 'active')
                    ->where('credit', '>', 0) // Original payment was credit
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($originalLedgerEntry) {
                    $originalLedgerEntry->update([
                        'status' => 'reversed',
                        'notes' => $originalLedgerEntry->notes . ' [REVERSED: Payment edited on ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);

                    // Create reversal entry
                    Ledger::createEntry([
                        'contact_id' => $payment->customer_id,
                        'contact_type' => 'customer',
                        'transaction_date' => now(),
                        'reference_no' => $payment->reference_no . '-EDIT-REV-' . time(),
                        'transaction_type' => 'payment_adjustment',
                        'amount' => $oldAmount, // Positive creates DEBIT to reverse CREDIT
                        'status' => 'reversed',
                        'notes' => "REVERSAL: Payment Edit - Cancel payment Rs." . number_format($oldAmount, 2) . " [Cancels Entry ID: {$originalLedgerEntry->id}]"
                    ]);
                }
            }

            // ✅ STEP 3: Create new payment record with updated data
            $newPayment = Payment::create([
                'payment_date' => Carbon::parse($newPaymentData['payment_date']),
                'amount' => $newAmount,
                'payment_method' => $newPaymentData['payment_method'],
                'reference_no' => $newPaymentData['reference_no'] ?? $payment->reference_no,
                'notes' => ($newPaymentData['notes'] ?? '') . ' [EDITED FROM: Payment ID ' . $payment->id . ']',
                'payment_status' => $newPaymentStatus,
                'status' => 'active', // New payment is active
                'payment_type' => 'sale',
                'reference_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                // Payment method specific fields
                'card_number' => $newPaymentData['card_number'] ?? null,
                'card_holder_name' => $newPaymentData['card_holder_name'] ?? null,
                'card_expiry_month' => $newPaymentData['card_expiry_month'] ?? null,
                'card_expiry_year' => $newPaymentData['card_expiry_year'] ?? null,
                'card_security_code' => $newPaymentData['card_security_code'] ?? null,
                'cheque_number' => $newPaymentData['cheque_number'] ?? null,
                'cheque_bank_branch' => $newPaymentData['cheque_bank_branch'] ?? null,
                'cheque_received_date' => $this->parseFlexibleDate($newPaymentData['cheque_received_date'] ?? null),
                'cheque_valid_date' => $this->parseFlexibleDate($newPaymentData['cheque_valid_date'] ?? null),
                'cheque_given_by' => $newPaymentData['cheque_given_by'] ?? null,
                'cheque_status' => $newPaymentData['cheque_status'] ?? 'pending',
            ]);

            // ✅ STEP 4: Create new ledger entry for updated payment (Skip Walk-In customers)
            // Only create ledger entry if payment is completed (not pending cheques)
            if ($sale->customer_id != 1) {
                $skipLedger = ($newPaymentData['skip_ledger'] ?? false) ||
                              ($newPaymentStatus === 'pending') ||
                              ($sale->status === 'draft') ||
                              ($sale->status === 'quotation');

                if (!$skipLedger) {
                    $this->unifiedLedgerService->recordSalePayment($newPayment, $sale);
                }
            }

            // ✅ STEP 5: Update sale payment status and totals
            $this->updateSalePaymentStatus($sale);

            Log::info('Payment edited successfully', [
                'old_payment_id' => $payment->id,
                'new_payment_id' => $newPayment->id,
                'sale_id' => $sale->id,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'customer_id' => $sale->customer_id
            ]);

            return $newPayment->fresh();
        });
    }

    /**
     * Delete payment with proper ledger reversal
     *
     * @param Payment $payment
     * @param string $reason
     * @return bool
     */
    public function deleteSalePayment(Payment $payment, string $reason = 'Payment deleted'): bool
    {
        return DB::transaction(function () use ($payment, $reason) {
            $sale = Sale::findOrFail($payment->reference_id);

            // Create reverse entry for the payment (if ledger tracking is enabled)
            if ($sale->customer_id != 1) { // Skip Walk-In customers
                // Mark original payment ledger entry as reversed
                $originalLedgerEntry = Ledger::where('reference_no', $payment->reference_no)
                    ->where('contact_id', $payment->customer_id)
                    ->where('contact_type', 'customer')
                    ->where('transaction_type', 'payments')
                    ->where('status', 'active')
                    ->where('credit', '>', 0) // Original payment was credit
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($originalLedgerEntry) {
                    $originalLedgerEntry->update([
                        'status' => 'reversed',
                        'notes' => $originalLedgerEntry->notes . ' | [REVERSED: ' . $reason . ' on ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);

                    // Create reversal entry using payment_adjustment (DEBIT to cancel the CREDIT from payment)
                    Ledger::createEntry([
                        'contact_id' => $payment->customer_id,
                        'contact_type' => 'customer',
                        'transaction_date' => now(),
                        'reference_no' => $payment->reference_no . '-DEL-REV-' . time(),
                        'transaction_type' => 'payment_adjustment',
                        'amount' => $payment->amount, // Positive amount creates DEBIT to reverse payment CREDIT
                        'status' => 'reversed',
                        'notes' => "REVERSAL: Payment Deletion - Cancel payment Rs." . number_format($payment->amount, 2) . " | {$reason} [Cancels Entry ID: {$originalLedgerEntry->id}]"
                    ]);
                }
            }

            // ✅ CRITICAL FIX: Mark payment as deleted instead of hard delete
            $payment->update([
                'status' => 'deleted',
                'notes' => ($payment->notes ?? '') . " | DELETED: {$reason}",
                'payment_status' => 'cancelled'
            ]);

            Log::info('Payment marked as deleted', [
                'payment_id' => $payment->id,
                'reference_no' => $payment->reference_no,
                'amount' => $payment->amount,
                'reason' => $reason,
                'deleted_by' => auth()->id()
            ]);

            // Update sale payment status
            $this->updateSalePaymentStatus($sale);

            return true;
        });
    }

    /**
     * Update sale payment status based on total paid
     *
     * @param Sale $sale
     * @return void
     */
    public function updateSalePaymentStatus(Sale $sale): void
    {
        // Calculate total paid excluding cancelled/bounced/deleted/pending payments
        // ✅ FIX: EXCLUDE pending cheque payments from total_paid calculation
        // Pending cheques should not count as paid until they are cleared/deposited
        $totalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', '!=', 'deleted') // Exclude deleted payments
            ->where(function($query) {
                $query->where('payment_status', '!=', 'bounced')
                      ->where('payment_status', '!=', 'cancelled')
                      ->where('payment_status', '!=', 'pending') // EXCLUDE pending (cheque) payments
                      ->orWhereNull('payment_status')
                      ->orWhere('payment_status', 'completed') // Only include completed payments
                      ->orWhere('payment_status', 'cleared'); // Include cleared cheques
            })
            ->sum('amount');

        // Update sale totals
        $sale->total_paid = $totalPaid;
        $sale->total_due = $sale->final_total - $totalPaid;

        // Determine payment status
        if ($sale->total_due <= 0.01) {
            $sale->payment_status = 'Paid';
        } elseif ($totalPaid > 0) {
            $sale->payment_status = 'Partial';
        } else {
            $sale->payment_status = 'Due';
        }

        $sale->save();
    }

    /**
     * Record a purchase payment
     *
     * @param array $paymentData
     * @param Purchase $purchase
     * @return Payment
     */
    public function recordPurchasePayment(array $paymentData, Purchase $purchase): Payment
    {
        return DB::transaction(function () use ($paymentData, $purchase) {
            // Calculate the total due and total paid for the purchase
            $totalPaid = Payment::where('reference_id', $purchase->id)
                ->where('payment_type', 'purchase')
                ->sum('amount');
            $totalDue = $purchase->final_total - $totalPaid;

            // If the paid amount exceeds total due, adjust it
            $paidAmount = min($paymentData['amount'], $totalDue);

            $paymentDate = isset($paymentData['payment_date'])
                ? Carbon::parse($paymentData['payment_date'])
                : now();

            $payment = Payment::create([
                'payment_date' => $paymentDate,
                'amount' => $paidAmount,
                'payment_method' => $paymentData['payment_method'],
                'reference_no' => $purchase->reference_no,
                'notes' => $paymentData['notes'] ?? null,
                'payment_type' => 'purchase',
                'reference_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'card_number' => $paymentData['card_number'] ?? null,
                'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                'card_security_code' => $paymentData['card_security_code'] ?? null,
                'cheque_number' => $paymentData['cheque_number'] ?? null,
                'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                'cheque_valid_date' => $paymentData['cheque_valid_date'] ?? null,
                'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
            ]);

            // Record payment in ledger
            $this->unifiedLedgerService->recordPurchasePayment($payment, $purchase);

            // Update purchase payment status
            $this->updatePurchasePaymentStatus($purchase);

            return $payment;
        });
    }

    /**
     * Record a purchase return payment
     *
     * @param array $paymentData
     * @param PurchaseReturn $purchaseReturn
     * @return Payment
     */
    public function recordPurchaseReturnPayment(array $paymentData, PurchaseReturn $purchaseReturn): Payment
    {
        return DB::transaction(function () use ($paymentData, $purchaseReturn) {
            // Calculate the total due and total paid for the purchase return
            $totalPaid = Payment::where('reference_id', $purchaseReturn->id)
                ->where('payment_type', 'purchase_return')
                ->sum('amount');
            $totalDue = $purchaseReturn->return_total - $totalPaid;

            // If the paid amount exceeds total due, adjust it
            $paidAmount = min($paymentData['amount'], $totalDue);

            $paymentDate = isset($paymentData['payment_date'])
                ? Carbon::parse($paymentData['payment_date'])
                : now();

            $payment = Payment::create([
                'payment_date' => $paymentDate,
                'amount' => $paidAmount,
                'payment_method' => $paymentData['payment_method'],
                'reference_no' => $purchaseReturn->reference_no,
                'notes' => $paymentData['notes'] ?? null,
                'payment_type' => 'purchase_return',
                'reference_id' => $purchaseReturn->id,
                'supplier_id' => $purchaseReturn->supplier_id,
                'card_number' => $paymentData['card_number'] ?? null,
                'card_holder_name' => $paymentData['card_holder_name'] ?? null,
                'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
                'card_security_code' => $paymentData['card_security_code'] ?? null,
                'cheque_number' => $paymentData['cheque_number'] ?? null,
                'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
                'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                'cheque_valid_date' => $paymentData['cheque_valid_date'] ?? null,
                'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
            ]);

            // Record payment in ledger
            $this->unifiedLedgerService->recordReturnPayment($payment, 'supplier');

            // Update purchase return payment status
            $this->updatePurchaseReturnPaymentStatus($purchaseReturn);

            return $payment;
        });
    }

    /**
     * Handle purchase payment with smart create/update/delete logic
     * Eliminates duplication from PurchaseController
     *
     * @param array $paymentData
     * @param Purchase $purchase
     * @return Payment|null
     */
    public function handlePurchasePayment(array $paymentData, Purchase $purchase): ?Payment
    {
        return DB::transaction(function () use ($paymentData, $purchase) {
            // Skip if no payment amount provided
            if (empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
                return null;
            }

            Log::info('PaymentService: Handling purchase payment', [
                'purchase_id' => $purchase->id,
                'amount' => $paymentData['amount'],
                'method' => __METHOD__
            ]);

            // Check for ACTIVE existing payments (exclude deleted ones)
            $activePayments = Payment::where('reference_id', $purchase->id)
                ->where('payment_type', 'purchase')
                ->where('status', '!=', 'deleted')
                ->get();

            // Check for ANY existing payments (including deleted) for audit trail
            $allPayments = Payment::where('reference_id', $purchase->id)
                ->where('payment_type', 'purchase')
                ->get();

            $hasActivePayments = $activePayments->count() > 0;
            $hasAnyPayments = $allPayments->count() > 0;

            Log::info('PaymentService: Payment analysis', [
                'has_active_payments' => $hasActivePayments,
                'has_any_payments' => $hasAnyPayments,
                'active_count' => $activePayments->count(),
                'total_count' => $allPayments->count()
            ]);

            if ($hasActivePayments) {
                // ✅ SCENARIO 1: CREATE NEW PAYMENT (mark zold as deleted for audit)
                // Better for audit trail - preserve payment history
                Log::info('PaymentService: Creating new payment, marking old as deleted', [
                    'existing_payments' => $activePayments->count(),
                    'new_amount' => $paymentData['amount']
                ]);

                // Mark existing active payments as deleted (for audit trail)
                foreach ($activePayments as $oldPayment) {
                    // ✅ CRITICAL FIX: Reverse the old payment in ledger before marking as deleted
                    $this->unifiedLedgerService->deletePayment($oldPayment, 'Payment replaced during purchase edit', auth()->id());

                    $oldPayment->update([
                        'status' => 'deleted',
                        'notes' => ($oldPayment->notes ?? '') . ' [REPLACED BY NEW PAYMENT: ' . now()->format('Y-m-d H:i:s') . ']'
                    ]);
                }

                // Create new payment entry
                $paymentData['notes'] = ($paymentData['notes'] ?? '') . ' [NEW PAYMENT: ' . now()->format('Y-m-d H:i:s') . ']';
                return $this->createFirstPurchasePayment($paymentData, $purchase);

            } elseif ($hasAnyPayments) {
                // ✅ SCENARIO 2: CREATE NEW PAYMENT (previous were deleted)
                return $this->createPurchasePaymentAfterDeletion($paymentData, $purchase, $allPayments);

            } else {
                // ✅ SCENARIO 3: CREATE FIRST PAYMENT
                return $this->createFirstPurchasePayment($paymentData, $purchase);
            }
        });
    }

    /**
     * Update existing active purchase payment
     *
     * @param Payment $payment
     * @param array $newData
     * @param Purchase $purchase
     * @return Payment
     */
    private function updateExistingPurchasePayment(Payment $payment, array $newData, Purchase $purchase): Payment
    {
        Log::info('PaymentService: Updating existing payment', [
            'payment_id' => $payment->id,
            'old_amount' => $payment->amount,
            'new_amount' => $newData['amount'],
            'strategy' => 'update_existing'
        ]);

        // Parse dates with flexible format support
        $paymentDate = isset($newData['payment_date'])
            ? $this->parseFlexibleDate($newData['payment_date'])
            : $payment->payment_date;

        // Update payment record
        $payment->update([
            'payment_date' => $paymentDate ? Carbon::parse($paymentDate) : $payment->payment_date,
            'amount' => $newData['amount'],
            'payment_method' => $newData['payment_method'] ?? $payment->payment_method,
            'notes' => ($newData['notes'] ?? $payment->notes) . ' [UPDATED: ' . now()->format('Y-m-d H:i:s') . ']',
            'status' => 'edited', // Mark as edited for audit trail
            // Update payment method specific fields
            'card_number' => $newData['card_number'] ?? $payment->card_number,
            'card_holder_name' => $newData['card_holder_name'] ?? $payment->card_holder_name,
            'card_expiry_month' => $newData['card_expiry_month'] ?? $payment->card_expiry_month,
            'card_expiry_year' => $newData['card_expiry_year'] ?? $payment->card_expiry_year,
            'card_security_code' => $newData['card_security_code'] ?? $payment->card_security_code,
            'cheque_number' => $newData['cheque_number'] ?? $payment->cheque_number,
            'cheque_bank_branch' => $newData['cheque_bank_branch'] ?? $payment->cheque_bank_branch,
            'cheque_received_date' => $this->parseFlexibleDate($newData['cheque_received_date'] ?? null) ?? $payment->cheque_received_date,
            'cheque_valid_date' => $this->parseFlexibleDate($newData['cheque_valid_date'] ?? null) ?? $payment->cheque_valid_date,
            'cheque_given_by' => $newData['cheque_given_by'] ?? $payment->cheque_given_by,
        ]);

        // Update payment in ledger using unified service
        $this->unifiedLedgerService->updatePurchasePayment($payment, $purchase);

        // Update purchase payment status
        $this->updatePurchasePaymentStatus($purchase);

        return $payment->fresh();
    }

    /**
     * Create new payment after previous payments were deleted
     *
     * @param array $paymentData
     * @param Purchase $purchase
     * @param \Illuminate\Support\Collection $deletedPayments
     * @return Payment
     */
    private function createPurchasePaymentAfterDeletion(array $paymentData, Purchase $purchase, $deletedPayments): Payment
    {
        Log::info('PaymentService: Creating payment after deletion', [
            'deleted_count' => $deletedPayments->where('status', 'deleted')->count(),
            'new_amount' => $paymentData['amount'],
            'strategy' => 'create_after_deletion'
        ]);

        // Add audit notes to deleted payments
        foreach ($deletedPayments->where('status', 'deleted') as $deletedPayment) {
            $deletedPayment->update([
                'notes' => ($deletedPayment->notes ?? '') . ' [REPLACED BY NEW PAYMENT: ' . now()->format('Y-m-d H:i:s') . ']'
            ]);
        }

        $paymentData['notes'] = ($paymentData['notes'] ?? '') . ' [NEW PAYMENT AFTER EDIT: ' . now()->format('Y-m-d H:i:s') . ']';

        return $this->createFirstPurchasePayment($paymentData, $purchase);
    }

    /**
     * Create first payment for purchase
     *
     * @param array $paymentData
     * @param Purchase $purchase
     * @return Payment
     */
    private function createFirstPurchasePayment(array $paymentData, Purchase $purchase): Payment
    {
        Log::info('PaymentService: Creating first payment', [
            'amount' => $paymentData['amount'],
            'strategy' => 'create_first_payment'
        ]);

        // Prepare payment data with proper date parsing
        $processedData = [
            'amount' => $paymentData['amount'],
            'payment_method' => $paymentData['payment_method'] ?? 'cash',
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'notes' => $paymentData['notes'] ?? null,
            'card_number' => $paymentData['card_number'] ?? null,
            'card_holder_name' => $paymentData['card_holder_name'] ?? null,
            'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
            'card_expiry_year' => $paymentData['card_expiry_year'] ?? null,
            'card_security_code' => $paymentData['card_security_code'] ?? null,
            'cheque_number' => $paymentData['cheque_number'] ?? null,
            'cheque_bank_branch' => $paymentData['cheque_bank_branch'] ?? null,
            'cheque_received_date' => $this->parseFlexibleDate($paymentData['cheque_received_date'] ?? null),
            'cheque_valid_date' => $this->parseFlexibleDate($paymentData['cheque_valid_date'] ?? null),
            'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
        ];

        return $this->recordPurchasePayment($processedData, $purchase);
    }

    /**
     * Delete purchase payment with proper audit trail
     *
     * @param Payment $payment
     * @param string $reason
     * @return bool
     */
    public function deletePurchasePayment(Payment $payment, string $reason = 'Payment deleted'): bool
    {
        return DB::transaction(function () use ($payment, $reason) {
            $purchase = Purchase::findOrFail($payment->reference_id);

            Log::info('PaymentService: Deleting purchase payment', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'reason' => $reason
            ]);

            // Mark as deleted with audit trail (don't hard delete)
            $payment->update([
                'status' => 'deleted',
                'notes' => ($payment->notes ?? '') . ' [DELETED: ' . $reason . ' on ' . now()->format('Y-m-d H:i:s') . ']'
            ]);

            // Update purchase payment status
            $this->updatePurchasePaymentStatus($purchase);

            return true;
        });
    }

    /**
     * Update purchase payment status based on active payments (excluding deleted ones)
     *
     * @param Purchase $purchase
     * @return void
     */
    public function updatePurchasePaymentStatus(Purchase $purchase): void
    {
        // Calculate total paid excluding deleted payments
        $totalPaid = Payment::where('reference_id', $purchase->id)
            ->where('payment_type', 'purchase')
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        // ✅ CRITICAL FIX: Update the total_paid field in purchase table
        $purchase->total_paid = $totalPaid;

        // Use small tolerance for floating point comparison
        $tolerance = 0.01;

        if (($purchase->final_total - $totalPaid) <= $tolerance) {
            $purchase->payment_status = 'Paid';
        } elseif ($totalPaid > $tolerance) {
            $purchase->payment_status = 'Partial';
        } else {
            $purchase->payment_status = 'Due';
        }

        $purchase->save();

        Log::info('PaymentService: Updated purchase payment status', [
            'purchase_id' => $purchase->id,
            'total_paid' => $totalPaid,
            'final_total' => $purchase->final_total,
            'status' => $purchase->payment_status
        ]);
    }

    /**
     * Update purchase return payment status based on total paid
     *
     * @param PurchaseReturn $purchaseReturn
     * @return void
     */
    private function updatePurchaseReturnPaymentStatus(PurchaseReturn $purchaseReturn): void
    {
        $totalPaid = Payment::where('reference_id', $purchaseReturn->id)
            ->where('payment_type', 'purchase_return')
            ->sum('amount');

        if ($purchaseReturn->return_total - $totalPaid <= 0.01) {
            $purchaseReturn->payment_status = 'Paid';
        } elseif ($totalPaid > 0) {
            $purchaseReturn->payment_status = 'Partial';
        } else {
            $purchaseReturn->payment_status = 'Due';
        }

        $purchaseReturn->save();
    }

    /**
     * Delete a payment and update related records
     *
     * @param Payment $payment
     * @return void
     */
    public function deletePayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            // Remove ledger entries for this payment
            $this->unifiedLedgerService->deleteLedgerEntries($payment->reference_no, $payment->supplier_id, 'supplier');

            // ✅ CRITICAL FIX: Mark payment as deleted instead of hard delete
            $payment->update([
                'status' => 'deleted',
                'notes' => ($payment->notes ?? '') . ' | [DELETED: ' . now()->format('Y-m-d H:i:s') . ']'
            ]);

            Log::info('Payment marked as deleted', [
                'payment_id' => $payment->id,
                'reference_no' => $payment->reference_no,
                'amount' => $payment->amount,
                'payment_type' => $payment->payment_type,
                'deleted_by' => auth()->id()
            ]);

            // Update payment status based on type
            if ($payment->payment_type === 'purchase') {
                $purchase = Purchase::find($payment->reference_id);
                if ($purchase) {
                    $this->updatePurchasePaymentStatus($purchase);
                    $this->unifiedLedgerService->recalculateSupplierBalance($purchase->supplier_id);
                }
            } elseif ($payment->payment_type === 'purchase_return') {
                $purchaseReturn = PurchaseReturn::find($payment->reference_id);
                if ($purchaseReturn) {
                    $this->updatePurchaseReturnPaymentStatus($purchaseReturn);
                    $this->unifiedLedgerService->recalculateSupplierBalance($purchaseReturn->supplier_id);
                }
            }
        });
    }

    /**
     * Update a payment and recalculate ledger
     *
     * @param Payment $payment
     * @param array $updateData
     * @return Payment
     */
    public function updatePayment(Payment $payment, array $updateData): Payment
    {
        return DB::transaction(function () use ($payment, $updateData) {
            // Store old supplier ID for balance recalculation
            $oldSupplierId = $payment->supplier_id;

            // Update payment
            $payment->update($updateData);

            // Recalculate ledger balance for the supplier
            $this->unifiedLedgerService->recalculateSupplierBalance($payment->supplier_id);

            // If supplier changed, recalculate old supplier balance too
            if ($oldSupplierId !== $payment->supplier_id) {
                $this->unifiedLedgerService->recalculateSupplierBalance($oldSupplierId);
            }

            // Update payment status based on type
            if ($payment->payment_type === 'purchase') {
                $purchase = Purchase::find($payment->reference_id);
                if ($purchase) {
                    $this->updatePurchasePaymentStatus($purchase);
                }
            } elseif ($payment->payment_type === 'purchase_return') {
                $purchaseReturn = PurchaseReturn::find($payment->reference_id);
                if ($purchaseReturn) {
                    $this->updatePurchaseReturnPaymentStatus($purchaseReturn);
                }
            }

            return $payment;
        });
    }

    /**
     * Get payment summary for a supplier
     *
     * @param int $supplierId
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return array
     */
    public function getSupplierPaymentSummary(int $supplierId, Carbon $fromDate = null, Carbon $toDate = null): array
    {
        $query = Payment::where('supplier_id', $supplierId);

        if ($fromDate) {
            $query->where('payment_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('payment_date', '<=', $toDate);
        }

        $payments = $query->get();

        return [
            'total_purchase_payments' => $payments->where('payment_type', 'purchase')->sum('amount'),
            'total_return_payments' => $payments->where('payment_type', 'purchase_return')->sum('amount'),
            'total_payments' => $payments->sum('amount'),
            'payment_count' => $payments->count(),
            'payments' => $payments
        ];
    }
}
