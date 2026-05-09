<?php

namespace App\Services\Sale;

use App\Models\Payment;
use App\Models\Sale;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SafeSaleUpdateService
 *
 * Provides safe update paths for different sale scenarios:
 * - updateCashSale(): Cash sale with full payment — updates amounts + payment table directly
 * - updateCreditSale(): Credit sale (partial/full) — protects existing payments, recalculates due
 *
 * CRITICAL RULE: Never delete customer payments — only update sale amounts and recalculate ledger.
 */
class SafeSaleUpdateService
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Update a CASH SALE where customer paid full amount immediately.
     *
     * Usage: Sale marked as 'final', customer_id != 1 (has ledger), payment exists and is active.
     * Logic: Update sale amounts + payment amounts together, then ledger reversal + new entry.
     *
     * @param Sale   $sale
     * @param float  $newFinalTotal
     * @param string $updatedNotes
     * @return void
     */
    public function updateCashSale(Sale $sale, float $newFinalTotal, string $updatedNotes = ''): void
    {
        DB::transaction(function () use ($sale, $newFinalTotal, $updatedNotes) {
            $oldFinalTotal = $sale->final_total;

            // Step 1: Get the cash payment record
            $payment = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('status', '!=', 'deleted')
                ->first();

            if (!$payment) {
                Log::warning('Cash sale update: no active payment found', ['sale_id' => $sale->id]);
                return;
            }

            $oldPaymentAmount = $payment->amount;

            // ✅ STEP 2: Use PaymentService.editSalePayment() to properly handle ledger reversal
            // This ensures:
            // 1. Old payment ledger entry is marked as REVERSED
            // 2. Reversal entry is created
            // 3. New payment ledger entry is created
            $this->paymentService->editSalePayment(
                $payment,
                [
                    'amount' => $newFinalTotal,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'reference_no' => $payment->reference_no,
                    'notes' => ($payment->notes ?? '') . " | [UPDATED: Sale amount changed from Rs.{$oldFinalTotal} to Rs.{$newFinalTotal}]",
                    'payment_status' => 'completed',
                    'card_number' => $payment->card_number,
                    'card_holder_name' => $payment->card_holder_name,
                    'cheque_number' => $payment->cheque_number,
                    'cheque_bank_branch' => $payment->cheque_bank_branch,
                    'cheque_received_date' => $payment->cheque_received_date,
                    'cheque_valid_date' => $payment->cheque_valid_date,
                    'cheque_given_by' => $payment->cheque_given_by,
                    'cheque_status' => $payment->cheque_status ?? 'pending',
                ]
            );

            // Step 3: Update sale totals
            $sale->update([
                'final_total' => $newFinalTotal,
                'total_paid'  => $newFinalTotal,  // Cash sale, paid in full
                'total_due'   => 0,
            ]);

            // Ledger entries are handled by both:
            // 1. PaymentService.editSalePayment() → creates payment ledger reversals + new entries
            // 2. SaleLedgerManager → creates sale ledger reversals + new entries

            Log::info('✅ SAFE CASH SALE UPDATE (using PaymentService)', [
                'sale_id'             => $sale->id,
                'invoice_no'          => $sale->invoice_no,
                'old_final_total'     => $oldFinalTotal,
                'new_final_total'     => $newFinalTotal,
                'payment_id'          => $payment->id,
                'old_payment_amount'  => $oldPaymentAmount,
                'new_payment_amount'  => $newFinalTotal,
                'customer_id'         => $sale->customer_id,
            ]);
        });
    }

    /**
     * Update a CREDIT SALE where customer paid partially or not at all.
     *
     * Usage: Sale is 'final', customer paid some amount (or zero), may have ledger entries.
     * Logic: Update sale amount ONLY; keep payments intact; recalculate total_due.
     * CRITICAL: Never delete customer payments!
     * IMPORTANT: Ledger entries are handled exclusively by SaleLedgerManager to prevent duplication.
     *
     * @param Sale   $sale
     * @param float  $newFinalTotal
     * @param string $updatedNotes
     * @return void
     */
    public function updateCreditSale(Sale $sale, float $newFinalTotal, string $updatedNotes = ''): void
    {
        DB::transaction(function () use ($sale, $newFinalTotal, $updatedNotes) {
            $oldFinalTotal = $sale->final_total;

            // Step 1: Calculate existing payments (DO NOT DELETE THEM)
            $existingTotalPaid = Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('status', '!=', 'deleted')
                ->sum('amount');

            // Step 2: Determine new total_due
            $newTotalDue = max(0.0, $newFinalTotal - $existingTotalPaid);

            // Step 3: Update sale amounts (payments stay as-is)
            $sale->update([
                'final_total' => $newFinalTotal,
                'total_paid'  => $existingTotalPaid,  // Keep existing payments
                'total_due'   => $newTotalDue,
            ]);

            // NOTE: Ledger entries for sale are handled ONLY by SaleLedgerManager to prevent duplication.
            // This service updates payments and sale totals only.

            Log::info('✅ SAFE CREDIT SALE UPDATE', [
                'sale_id'             => $sale->id,
                'invoice_no'          => $sale->invoice_no,
                'old_final_total'     => $oldFinalTotal,
                'new_final_total'     => $newFinalTotal,
                'existing_total_paid' => $existingTotalPaid,
                'new_total_due'       => $newTotalDue,
                'customer_id'         => $sale->customer_id,
                'note'                => 'PAYMENTS PRESERVED - no deletion; Ledger handled by SaleLedgerManager'
            ]);
        });
    }

    /**
     * Check if a sale is cash-paid (full payment received) or credit.
     *
     * @param Sale $sale
     * @return bool True if cash sale, false if credit
     */
    public function isCashSale(Sale $sale): bool
    {
        $existingPayments = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', '!=', 'deleted')
            ->get();

        if ($existingPayments->isEmpty()) {
            return false;  // No payment = credit sale
        }

        $totalPaid = $existingPayments->sum('amount');
        return (float) $totalPaid >= (float) $sale->final_total;
    }
}
