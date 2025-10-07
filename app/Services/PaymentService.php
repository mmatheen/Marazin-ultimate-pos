<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected $unifiedLedgerService;

    public function __construct(UnifiedLedgerService $unifiedLedgerService)
    {
        $this->unifiedLedgerService = $unifiedLedgerService;
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
                'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                'cheque_valid_date' => $paymentData['cheque_valid_date'] ?? null,
                'cheque_given_by' => $paymentData['cheque_given_by'] ?? null,
                'payment_status' => $paymentData['payment_status'] ?? 'completed',
            ]);

            // Record payment in ledger (skip for Walk-In customers)
            if ($sale->customer_id != 1) {
                $this->unifiedLedgerService->recordSalePayment($payment, $sale);
            }

            // Update sale payment status
            $this->updateSalePaymentStatus($sale);

            return $payment;
        });
    }

    /**
     * Update sale payment status based on total paid
     * 
     * @param Sale $sale
     * @return void
     */
    private function updateSalePaymentStatus(Sale $sale): void
    {
        $totalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where(function($query) {
                $query->where('payment_status', '!=', 'bounced')
                      ->orWhereNull('payment_status');
            })
            ->sum('amount');

        if ($sale->final_total - $totalPaid <= 0.01) {
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
     * Update purchase payment status based on total paid
     * 
     * @param Purchase $purchase
     * @return void
     */
    private function updatePurchasePaymentStatus(Purchase $purchase): void
    {
        $totalPaid = Payment::where('reference_id', $purchase->id)
            ->where('payment_type', 'purchase')
            ->sum('amount');

        if ($purchase->final_total - $totalPaid <= 0.01) {
            $purchase->payment_status = 'Paid';
        } elseif ($totalPaid > 0) {
            $purchase->payment_status = 'Partial';
        } else {
            $purchase->payment_status = 'Due';
        }

        $purchase->save();
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

            // Delete the payment
            $payment->delete();

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