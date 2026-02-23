<?php

namespace App\Services\Sale;

use App\Models\JobTicket;
use App\Models\Payment;
use App\Models\Sale;
use App\Services\PaymentService;
use App\Services\UnifiedLedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SalePaymentProcessor
 *
 * Handles all payment create / delete / recalculate logic for a sale save.
 * Called AFTER ledger entries have been written (inside the DB transaction).
 *
 * Entry point: process()
 *
 * Four internal paths:
 *   A – Sale Order          → zero payment, set status = Due
 *   B – Job Ticket          → advance payment already recorded earlier; skip
 *   C – Walk-In with pays   → soft-delete old, recreate via PaymentService
 *   D – Credit customer with pays → delete old (with ledger reversal), recreate
 *   E – Update, no payments → clear any old payments, recalculate from DB
 */
class SalePaymentProcessor
{
    public function __construct(
        protected PaymentService       $paymentService,
        protected UnifiedLedgerService $ledger
    ) {}

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * @param Sale    $sale
     * @param Request $request
     * @param bool    $isUpdate
     * @param bool    $customerChanged
     * @param string  $transactionType
     */
    public function process(
        Sale    $sale,
        Request $request,
        bool    $isUpdate,
        bool    $customerChanged,
        string  $transactionType,
        array   $amounts = []
    ): void {
        // ── Path A: Sale Order ───────────────────────────────────────────────
        if ($transactionType === 'sale_order') {
            $sale->update([
                'payment_status' => 'Due',
                'total_paid'     => 0,
                'amount_given'   => 0,
                'balance_amount' => 0,
            ]);
            return;
        }

        // ── Path B: Job Ticket ───────────────────────────────────────────────
        // Create/update the JobTicket record and record advance payment if provided.
        if ($sale->status === 'jobticket') {
            $advanceAmount = $amounts['advance_amount'] ?? 0;
            $balanceAmount = $amounts['balance_amount'] ?? 0;
            $totalPaid     = $amounts['total_paid']     ?? 0;
            $totalDue      = $amounts['total_due']      ?? 0;
            $amountGiven   = $amounts['amount_given']   ?? 0;
            $finalTotal    = $amounts['final_total']    ?? 0;

            JobTicket::updateOrCreate(
                ['sale_id' => $sale->id],
                [
                    'customer_id'     => $sale->customer_id,
                    'description'     => $request->jobticket_description ?? null,
                    'job_ticket_date' => Carbon::now('Asia/Colombo'),
                    'status'          => 'open',
                    'advance_amount'  => $advanceAmount,
                    'balance_amount'  => $balanceAmount,
                ]
            );
            $sale->update([
                'total_paid'     => $totalPaid,
                'total_due'      => $totalDue,
                'amount_given'   => $amountGiven,
                'balance_amount' => $balanceAmount,
            ]);
            if ($advanceAmount > 0) {
                $paymentAmount = min($advanceAmount, $finalTotal);
                $paymentData = [
                    'payment_date'   => $request->sales_date ?? Carbon::now('Asia/Colombo')->format('Y-m-d'),
                    'amount'         => $paymentAmount,
                    'payment_method' => 'cash',
                    'reference_no'   => $sale->invoice_no,
                    'notes'          => 'Advance payment for job ticket',
                ];
                $this->paymentService->recordSalePayment($paymentData, $sale);
            }
            return;
        }

        // ── Paths C / D / E ──────────────────────────────────────────────────
        if ($request->customer_id == 1 && !empty($request->payments)) {
            $this->processWalkInPayments($sale, $request, $isUpdate);
        } elseif (!empty($request->payments)) {
            $this->processCreditCustomerPayments($sale, $request, $isUpdate, $customerChanged);
        } else {
            // Explicit empty or absent payments on an update
            if ($isUpdate) {
                $this->processNoPaymentUpdate($sale, $request);
            }
        }
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Path C — Walk-In customer, payments provided.
     * Walk-In has no ledger; soft-delete old rows then recreate.
     */
    private function processWalkInPayments(Sale $sale, Request $request, bool $isUpdate): void
    {
        if ($isUpdate) {
            Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('status', '!=', 'deleted')
                ->get()
                ->each(fn($p) => $p->update([
                    'status'         => 'deleted',
                    'payment_status' => 'cancelled',
                    'notes'          => ($p->notes ?? '') . ' | DELETED: Sale edited - payment recreated',
                ]));
        }

        $totalPaid = 0;

        foreach ($request->payments as $paymentData) {
            if (empty($paymentData['amount']) || $paymentData['amount'] <= 0) {
                continue;
            }

            $serviceData = [
                'payment_date'   => $this->parseDate($paymentData['payment_date'] ?? null),
                'amount'         => $paymentData['amount'],
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'reference_no'   => $sale->invoice_no,
                'payment_status' => 'completed',
                'notes'          => $paymentData['notes'] ?? '',
            ];

            $this->paymentService->recordSalePayment($serviceData, $sale);

            // Cheque in-hand counts as received regardless of bank-clearing status.
            // pending/cleared only tracks whether the bank has processed it.
            $totalPaid += $paymentData['amount'];
        }

        $totalDue_walkIn = max(0, $sale->final_total - $totalPaid);

        $sale->update([
            'total_paid'     => $totalPaid,
            'total_due'      => $totalDue_walkIn,
            'payment_status' => $totalPaid >= $sale->final_total ? 'Paid' : 'Partial',
        ]);
    }

    /**
     * Path D — Credit customer, payments provided.
     * Delete old payments (with ledger reversal) then create new ones.
     */
    private function processCreditCustomerPayments(
        Sale    $sale,
        Request $request,
        bool    $isUpdate,
        bool    $customerChanged
    ): void {
        if ($isUpdate) {
            $oldPayments = Payment::where('reference_id', $sale->id)
                ->where('status', '!=', 'deleted')
                ->get();

            if ($oldPayments->count() > 0) {
                if ($customerChanged) {
                    // Ledger already transferred by SaleLedgerManager; soft-delete only
                    Payment::where('reference_id', $sale->id)
                        ->where('status', '!=', 'deleted')
                        ->update([
                            'status'         => 'deleted',
                            'payment_status' => 'cancelled',
                            'notes'          => DB::raw("CONCAT(COALESCE(notes, ''), ' | DELETED: Customer changed during sale edit')"),
                        ]);
                } else {
                    // Same customer — PaymentService handles ledger reversal
                    foreach ($oldPayments as $oldPayment) {
                        $this->paymentService->deleteSalePayment($oldPayment, 'Payment updated during sale edit');
                    }
                }
            }
        }

        $paymentsToCreate = collect($request->payments)
            ->filter(fn($p) => !empty($p['amount']) && $p['amount'] > 0);

        foreach ($paymentsToCreate as $paymentData) {
            $this->paymentService->recordSalePayment(
                $this->buildServicePaymentData($paymentData, $sale),
                $sale
            );
        }

        // Cheque in-hand counts as received regardless of bank-clearing status.
        // pending/cleared only tracks whether the bank has processed it.
        $totalPaid = $paymentsToCreate->sum(fn($p) => $p['amount']);

        // Floating balance adjustment (debit from customer's credit float)
        if ($request->use_floating_balance && $request->floating_balance_amount > 0) {
            $this->processFloatingBalanceAdjustment($sale, $request->floating_balance_amount);
            $totalPaid += $request->floating_balance_amount;
        }

        // Excess payment saved as customer advance
        if ($request->save_excess_as_advance && $request->excess_amount > 0 && $sale->customer_id != 1) {
            $excessAmount = floatval($request->excess_amount);

            $this->paymentService->recordCustomerAdvancePayment([
                'payment_date'   => now(),
                'amount'         => $excessAmount,
                'payment_method' => 'cash',
                'reference_no'   => 'ADV-' . $sale->invoice_no,
                'notes'          => 'Customer advance from excess payment on invoice ' . $sale->invoice_no,
                'payment_status' => 'completed',
                'customer_id'    => $sale->customer_id,
                'location_id'    => $sale->location_id,
            ]);
        }

        $totalDue      = max(0, $sale->final_total - $totalPaid);
        $paymentStatus = $totalDue <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Due');

        $sale->update([
            'total_paid'     => $totalPaid,
            'total_due'      => $totalDue,
            'payment_status' => $paymentStatus,
        ]);
    }

    /**
     * Path E — Update with no payments array (credit sale, no cash collected).
     * If payments were previously recorded, clear them and recalculate from DB.
     */
    private function processNoPaymentUpdate(Sale $sale, Request $request): void
    {
        if ($request->has('payments') && empty($request->payments)) {
            Payment::where('reference_id', $sale->id)
                ->where('payment_type', 'sale')
                ->where('status', '!=', 'deleted')
                ->get()
                ->each(fn($p) => $this->paymentService->deleteSalePayment(
                    $p, 'Payment cleared: sale changed to credit (no payment)'
                ));
        }

        $actualTotalPaid = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        $totalDue      = max(0, $sale->final_total - $actualTotalPaid);
        $paymentStatus = $totalDue <= 0 ? 'Paid' : ($actualTotalPaid > 0 ? 'Partial' : 'Due');
        $amountGiven   = floatval($request->amount_given ?? $sale->final_total);

        $sale->update([
            'total_paid'     => $actualTotalPaid,
            'total_due'      => $totalDue,
            'payment_status' => $paymentStatus,
            'amount_given'   => $amountGiven,
            'balance_amount' => max(0, $amountGiven - $sale->final_total),
        ]);
    }

    /**
     * Build the $servicePaymentData array for PaymentService based on payment method.
     */
    private function buildServicePaymentData(array $paymentData, Sale $sale): array
    {
        $base = [
            'payment_date'   => $this->parseDate($paymentData['payment_date'] ?? null),
            'amount'         => $paymentData['amount'],
            'payment_method' => $paymentData['payment_method'],
            'reference_no'   => $sale->invoice_no,
            'notes'          => $paymentData['notes'] ?? '',
        ];

        return match ($paymentData['payment_method']) {
            'card'   => array_merge($base, [
                'card_number'       => $paymentData['card_number']       ?? null,
                'card_holder_name'  => $paymentData['card_holder_name']  ?? null,
                'card_expiry_month' => $paymentData['card_expiry_month'] ?? null,
                'card_expiry_year'  => $paymentData['card_expiry_year']  ?? null,
                'card_security_code'=> $paymentData['card_security_code']?? null,
                'payment_status'    => 'completed',
            ]),
            'cheque' => array_merge($base, [
                'cheque_number'        => $paymentData['cheque_number']        ?? null,
                'cheque_bank_branch'   => $paymentData['cheque_bank_branch']   ?? null,
                'cheque_received_date' => $paymentData['cheque_received_date'] ?? null,
                'cheque_valid_date'    => $paymentData['cheque_valid_date']    ?? null,
                'cheque_given_by'      => $paymentData['cheque_given_by']      ?? null,
                'cheque_status'        => $paymentData['cheque_status'] ?? 'pending',
                'payment_status'       => ($paymentData['cheque_status'] ?? 'pending') === 'cleared'
                                            ? 'completed' : 'pending',
            ]),
            default  => array_merge($base, ['payment_status' => 'completed']),
        };
    }

    /**
     * Record a floating-balance adjustment payment and update the ledger.
     */
    private function processFloatingBalanceAdjustment(Sale $sale, float $adjustmentAmount): void
    {
        $payment = new Payment([
            'customer_id'    => $sale->customer_id,
            'reference_id'   => $sale->id,
            'payment_type'   => 'sale',
            'payment_method' => 'floating_balance_adjustment',
            'amount'         => $adjustmentAmount,
            'payment_date'   => now(),
            'notes'          => 'Floating balance adjustment against sale #' . $sale->invoice_no,
            'payment_status' => 'completed',
            'created_by'     => auth()->id(),
        ]);

        $payment->save();

        $this->ledger->recordFloatingBalanceRecovery(
            $sale->customer_id,
            -$adjustmentAmount,
            'floating_balance_adjustment',
            'Floating balance adjustment against sale #' . $sale->invoice_no
        );
    }

    /**
     * Parse a payment_date value to Carbon, defaulting to now() on failure.
     */
    private function parseDate(mixed $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        if (is_string($date) && $date !== '') {
            try {
                return Carbon::parse($date);
            } catch (\Exception) {
                // fall through
            }
        }

        return Carbon::now();
    }
}
