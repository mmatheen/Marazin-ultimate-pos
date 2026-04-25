<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Services\CashRegisterService;
use App\Services\UnifiedLedgerService;
use App\Helpers\BalanceHelper;
use App\Traits\BulkPaymentHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FlexibleBulkSalePaymentService
{
    use BulkPaymentHelpers;

    public function __construct(
        private readonly UnifiedLedgerService $unifiedLedgerService,
        private readonly CashRegisterService $cashRegisterService
    ) {}

    /**
     * Process a flexible, multi-method bulk sale payment for a customer.
     *
     * @return array{bulk_reference: string, total_amount: float, created_payment_ids: array<int>, affected_sale_ids: array<int>}
     * @throws \Exception
     */
    public function process(Request $request): array
    {
        $bulkReference = null;
        $totalAmount   = 0.0;
        $createdPaymentIds = [];
        $affectedSaleIdsList = [];

        DB::transaction(function () use ($request, &$bulkReference, &$totalAmount, &$createdPaymentIds, &$affectedSaleIdsList) {

            $bulkReference  = $this->generateMeaningfulBulkReference($request);
            $totalAmount    = 0.0;
            $processedGroups = [];

            $paymentGroupsInput = $request->input('payment_groups', []);
            if (! is_array($paymentGroupsInput)) {
                $paymentGroupsInput = [];
            }

            // ── Step 1: tally OB vs sale payment totals ──────────────────────────
            $totalOBPayment      = 0.0;
            $totalSalePayment    = 0.0;
            $totalPaymentAmount  = 0.0;

            foreach ($paymentGroupsInput as $paymentGroup) {
                if (isset($paymentGroup['bills']) && is_array($paymentGroup['bills'])) {
                    foreach ($paymentGroup['bills'] as $bill) {
                        $totalPaymentAmount += $bill['amount'];
                    }
                }
                if ($request->payment_type === 'both' && isset($paymentGroup['ob_amount'])) {
                    $totalOBPayment += floatval($paymentGroup['ob_amount']);
                }
            }

            if ($request->payment_type === 'both') {
                $totalSalePayment = $totalPaymentAmount;
            }

            $this->validatePaymentAmounts(
                'customer',
                (int) $request->customer_id,
                $request->payment_type,
                $totalOBPayment,
                $totalSalePayment
            );

            // ── Step 2: opening-balance pre-payments (for 'both' type) ───────────
            $obAlreadyAllocated = 0.0;

            if ($request->payment_type === 'both' && $totalOBPayment > 0) {
                if (Payment::where('customer_id', $request->customer_id)
                           ->where('payment_type', 'opening_balance')
                           ->where('status', 'active')
                           ->exists()) {
                    throw new \Exception('Opening balance payment already exists for this customer. Cannot create duplicate opening balance payments.');
                }

                foreach ($paymentGroupsInput as $paymentGroup) {
                    $obAmount = floatval($paymentGroup['ob_amount'] ?? 0);
                    if ($obAmount <= 0) continue;

                    $paymentData = [
                        'payment_date'   => $request->payment_date,
                        'amount'         => $obAmount,
                        'payment_method' => $paymentGroup['method'],
                        'payment_type'   => 'opening_balance',
                        'reference_id'   => null,
                        'reference_no'   => $bulkReference,
                        'customer_id'    => $request->customer_id,
                        'notes'          => $request->notes ?? null,
                    ];
                    $this->addMethodSpecificFields($paymentData, $paymentGroup);

                    $payment = Payment::create($paymentData);
                    $createdPaymentIds[] = (int) $payment->id;
                    $this->unifiedLedgerService->recordOpeningBalancePayment($payment, 'customer');

                    $obAlreadyAllocated += $obAmount;
                    $totalAmount        += $obAmount;
                }
            }

            // ── Step 3: process return credits ───────────────────────────────────
            $affectedSaleIds = [];

            if ($request->has('selected_returns') && is_array($request->selected_returns)) {
                foreach ($request->selected_returns as $returnData) {
                    $salesReturn = SalesReturn::findOrFail($returnData['return_id']);

                    if ($salesReturn->customer_id != $request->customer_id) {
                        throw new \Exception("Return {$salesReturn->invoice_number} does not belong to this customer");
                    }
                    if ($salesReturn->total_due <= 0 || $salesReturn->payment_status === 'Paid') {
                        throw new \Exception("Return {$salesReturn->invoice_number} has already been fully applied or paid. Remaining due: Rs.{$salesReturn->total_due}");
                    }
                    if ($returnData['amount'] > $salesReturn->total_due) {
                        throw new \Exception("Return {$salesReturn->invoice_number} refund amount Rs.{$returnData['amount']} exceeds pending refund Rs.{$salesReturn->total_due}");
                    }

                    if ($returnData['action'] === 'apply_to_sales') {
                        $salesReturn->total_paid = $salesReturn->total_paid + $returnData['amount'];
                        $salesReturn->save();
                        $salesReturn->refresh();

                        if ($salesReturn->sale_id) {
                            $affectedSaleIds[$salesReturn->sale_id] = true;
                        }

                    } elseif ($returnData['action'] === 'cash_refund') {
                        $locationId = (int) $salesReturn->location_id;
                        $userId = (int) Auth::id();
                        $register = $this->cashRegisterService->getCurrentOpenRegister($locationId, $userId);
                        $cashRegisterId = $register?->id;

                        $returnPayment = Payment::create([
                            'payment_date'   => $request->payment_date,
                            'amount'         => $returnData['amount'],
                            'payment_method' => 'cash',
                            'payment_type'   => $salesReturn->sale_id
                                ? 'sale_return_with_bill'
                                : 'sale_return_without_bill',
                            'reference_id'   => $salesReturn->id,
                            'cash_register_id' => $cashRegisterId,
                            'reference_no'   => $bulkReference,
                            'customer_id'    => $request->customer_id,
                            'notes'          => 'Cash refund for return: ' . $salesReturn->invoice_number,
                        ]);
                        $createdPaymentIds[] = (int) $returnPayment->id;

                        if ($register) {
                            $this->cashRegisterService->recordRefundCash(
                                $register,
                                (int) $salesReturn->id,
                                abs($returnData['amount']),
                                (int) $returnPayment->id
                            );
                        }

                        $salesReturn->increment('total_paid', $returnData['amount']);
                        $salesReturn->refresh();
                        $salesReturn->save();

                        $this->unifiedLedgerService->recordReturnRefund($returnPayment, 'customer');
                    }
                }
            }

            // Sale IDs for which updateSaleTable() already ran with bill_return_allocations (Step 5).
            $saleIdsReturnCreditAppliedInGroups = [];

            // ── Step 5: process payment groups ───────────────────────────────────
            foreach ($paymentGroupsInput as $groupIndex => $paymentGroup) {
                $groupTotal    = 0.0;
                $groupPayments = [];

                if ($request->payment_type === 'opening_balance') {
                    // Prevent duplicate opening-balance payments
                    if (Payment::where('customer_id', $request->customer_id)
                               ->where('payment_type', 'opening_balance')
                               ->where('status', 'active')
                               ->exists()) {
                        throw new \Exception('Opening balance payment already exists for this customer. Cannot create duplicate opening balance payments.');
                    }

                    $paymentData = [
                        'payment_date'   => $request->payment_date,
                        'amount'         => $paymentGroup['totalAmount'],
                        'payment_method' => $paymentGroup['method'],
                        'payment_type'   => 'opening_balance',
                        'reference_id'   => null,
                        'reference_no'   => $bulkReference,
                        'customer_id'    => $request->customer_id,
                        'notes'          => $request->notes ?? null,
                    ];
                    $this->addMethodSpecificFields($paymentData, $paymentGroup);

                    $payment = Payment::create($paymentData);
                    $this->unifiedLedgerService->recordOpeningBalancePayment($payment, 'customer');

                    $groupTotal      = $paymentGroup['totalAmount'];
                    $groupPayments[] = ['payment_id' => $payment->id, 'type' => 'opening_balance', 'amount' => $paymentGroup['totalAmount']];

                } else {
                    // Sale payments – iterate bills
                    foreach ($paymentGroup['bills'] as $bill) {
                        $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                                   ->where('id', $bill['sale_id'])
                                   ->where('customer_id', $request->customer_id)
                                   ->first();

                        if (!$sale) {
                            throw new \Exception("Sale {$bill['sale_id']} not found for customer");
                        }
                        if ($bill['amount'] < 0) {
                            throw new \Exception("Invalid payment amount Rs.{$bill['amount']} for invoice {$sale->invoice_no}");
                        }
                        if ($bill['amount'] <= 0) continue;

                        $paymentData = [
                            'payment_date'   => $request->payment_date,
                            'amount'         => $bill['amount'],
                            'payment_method' => $paymentGroup['method'],
                            'payment_type'   => 'sale',
                            'reference_id'   => $bill['sale_id'],
                            'reference_no'   => $bulkReference,
                            'customer_id'    => $request->customer_id,
                            'notes'          => $request->notes ?? null,
                        ];
                        $this->addMethodSpecificFields($paymentData, $paymentGroup);

                        $payment = Payment::create($paymentData);
                        $createdPaymentIds[] = (int) $payment->id;
                        $this->unifiedLedgerService->recordSalePayment($payment);

                        // Apply any return credit allocation for this bill
                        $returnCreditForBill = 0.0;
                        if ($request->has('bill_return_allocations') && isset($request->bill_return_allocations[$bill['sale_id']])) {
                            $returnCreditForBill = floatval($request->bill_return_allocations[$bill['sale_id']]);
                        }

                        $this->updateSaleTable($bill['sale_id'], $returnCreditForBill);
                        $affectedSaleIds[$bill['sale_id']] = true;
                        $saleIdsReturnCreditAppliedInGroups[(int) $bill['sale_id']] = true;

                        $groupTotal      += $bill['amount'];
                        $groupPayments[] = [
                            'payment_id' => $payment->id,
                            'sale_id'    => $bill['sale_id'],
                            'invoice_no' => $sale->invoice_no,
                            'amount'     => $bill['amount'],
                        ];
                    }

                    // Excess amount → advance payment
                    $advanceAmount = floatval($paymentGroup['advance_amount'] ?? 0);
                    if ($advanceAmount > 0.01) {
                        $advanceData = [
                            'payment_date'   => $request->payment_date,
                            'amount'         => $advanceAmount,
                            'payment_method' => $paymentGroup['method'],
                            'payment_type'   => 'advance',
                            'reference_id'   => null,
                            'reference_no'   => $bulkReference,
                            'customer_id'    => $request->customer_id,
                            'notes'          => ($request->notes ?? '') . ' [Advance Payment]',
                        ];
                        $this->addMethodSpecificFields($advanceData, $paymentGroup);

                        $advancePayment = Payment::create($advanceData);
                        $createdPaymentIds[] = (int) $advancePayment->id;
                        $this->unifiedLedgerService->recordAdvancePayment($advancePayment, 'customer');

                        $groupTotal      += $advanceAmount;
                        $groupPayments[] = ['payment_id' => $advancePayment->id, 'type' => 'advance', 'amount' => $advanceAmount];
                    }
                }

                $processedGroups[] = [
                    'method'         => $paymentGroup['method'],
                    'total_amount'   => $groupTotal,
                    'payments_count' => count($groupPayments),
                    'payments'       => $groupPayments,
                    'cheque_number'  => $paymentGroup['cheque_number'] ?? null,
                ];

                $totalAmount += $groupTotal;
            }

            // ── Step 6: apply credit (advance/unallocated) to sales ───────────────
            // This links existing customer credit to invoices WITHOUT creating a new ledger credit entry
            // (ledger already contains that credit as previous payment/overpayment).
            if ($request->has('advance_credit_applied') && $request->advance_credit_applied > 0) {
                $creditToApply = floatval($request->advance_credit_applied);

                // Available credit is when ledger/account due is lower than invoice due OR customer has negative balance.
                // Use: availableCredit = max(0, saleDue - ledgerBalance)
                $ledgerBalance = (float) BalanceHelper::getCustomerBalance((int) $request->customer_id);
                $saleDue = (float) Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                    ->where('customer_id', (int) $request->customer_id)
                    ->whereIn('status', ['final', 'suspend'])
                    ->sum('total_due');
                $availableCredit = max(0.0, $saleDue - max(0.0, $ledgerBalance)) + max(0.0, abs(min(0.0, $ledgerBalance)));

                if ($availableCredit <= 0.01) {
                    throw new \Exception('Customer does not have any credit available to apply.');
                }
                if ($creditToApply > $availableCredit + 0.02) {
                    throw new \Exception("Credit amount Rs.{$creditToApply} exceeds available credit Rs." . number_format($availableCredit, 2));
                }

                // Allocate credit FIFO across outstanding sales (after cash and return credits)
                $sales = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                    ->where('customer_id', (int) $request->customer_id)
                    ->whereIn('status', ['final', 'suspend'])
                    ->where('total_due', '>', 0)
                    ->orderBy('sales_date')
                    ->orderBy('id')
                    ->get(['id', 'invoice_no', 'total_due']);

                $remainingCredit = $creditToApply;
                foreach ($sales as $sale) {
                    if ($remainingCredit <= 0.01) break;

                    $cashPaid = (float) Payment::where('reference_id', $sale->id)
                        ->where('payment_type', 'sale')
                        ->sum('amount');
                    $creditAppliedAlready = (float) Payment::where('reference_id', $sale->id)
                        ->where('payment_type', 'advance_credit_usage')
                        ->sum('amount');
                    $returnCredit = (float) $this->getAppliedReturnCreditForSale((int) $sale->id);

                    // IMPORTANT:
                    // In this codebase, Sale::saving typically keeps total_due in sync with final_total - total_paid.
                    // That means $sale->total_due is already net of cash payments we just created in Step 5.
                    // Do not subtract $cashPaid again here (double-deduct), otherwise allocation can become impossible
                    // and we'd throw "Credit allocation incomplete" even though credit is valid.
                    $remainingDue = (float) $sale->total_due - $creditAppliedAlready - $returnCredit;
                    if ($remainingDue <= 0.01) continue;

                    $apply = min($remainingCredit, $remainingDue);
                    if ($apply <= 0.01) continue;

                    $creditPayment = Payment::create([
                        'payment_date'   => $request->payment_date,
                        'amount'         => $apply,
                        'payment_method' => 'advance_credit',
                        'payment_type'   => 'advance_credit_usage',
                        'reference_id'   => $sale->id,
                        'reference_no'   => $bulkReference,
                        'customer_id'    => $request->customer_id,
                        'notes'          => 'Credit applied to invoice ' . $sale->invoice_no,
                    ]);
                    $createdPaymentIds[] = (int) $creditPayment->id;

                    // IMPORTANT: no ledger entry here (credit already exists in ledger)
                    $this->updateSaleTable((int) $sale->id, 0.0);
                    $affectedSaleIds[(int) $sale->id] = true;

                    $remainingCredit -= $apply;
                }

                if ($remainingCredit > 0.02) {
                    throw new \Exception('Credit allocation incomplete. Please try again or contact support.');
                }
            }

            // ── Step 7: apply return-credit allocations for sales not updated in Step 5 ───
            // (Return-only settlement: no cash payment_groups; sale.total_paid must include return credit.)
            $billReturnAllocations = $request->input('bill_return_allocations', []);
            if (! is_array($billReturnAllocations)) {
                $billReturnAllocations = [];
            }
            foreach ($billReturnAllocations as $saleId => $creditAmount) {
                $creditAmount = floatval($creditAmount);
                if ($creditAmount <= 0.01) {
                    continue;
                }

                $saleId = (int) $saleId;
                $sale   = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                    ->where('id', $saleId)
                    ->where('customer_id', $request->customer_id)
                    ->first();

                if (! $sale) {
                    throw new \Exception("Sale {$saleId} not found for customer");
                }

                if (isset($saleIdsReturnCreditAppliedInGroups[$saleId])) {
                    continue;
                }

                if ($creditAmount > (float) $sale->total_due + 0.02) {
                    throw new \Exception(
                        "Return credit Rs.".number_format($creditAmount, 2)." for invoice {$sale->invoice_no} exceeds sale due Rs.".number_format((float) $sale->total_due, 2)
                    );
                }

                $this->updateSaleTable($saleId, $creditAmount);
                $affectedSaleIds[$saleId] = true;
            }

            // ── Step 8: excess amount → advance payments for 'both' type ─────────
            if ($request->payment_type === 'both') {
                $totalEntered = array_sum(array_column(
                    array_map(fn ($pg) => ['t' => floatval($pg['totalAmount'] ?? 0)], $paymentGroupsInput),
                    't'
                ));

                $advanceAmount = $totalEntered - ($totalOBPayment + $totalSalePayment);

                if ($advanceAmount > 0.01) {
                    foreach ($paymentGroupsInput as $paymentGroup) {
                        $groupTotal = floatval($paymentGroup['totalAmount'] ?? 0);
                        if ($groupTotal <= 0 || $totalEntered <= 0) continue;

                        $advanceForMethod = $advanceAmount * ($groupTotal / $totalEntered);
                        if ($advanceForMethod <= 0.01) continue;

                        $advanceData = [
                            'payment_date'   => $request->payment_date,
                            'amount'         => $advanceForMethod,
                            'payment_method' => $paymentGroup['method'],
                            'payment_type'   => 'advance',
                            'reference_id'   => null,
                            'reference_no'   => $bulkReference,
                            'customer_id'    => $request->customer_id,
                            'notes'          => ($request->notes ?? '') . ' [Advance Payment]',
                        ];
                        $this->addMethodSpecificFields($advanceData, $paymentGroup);

                        $advancePayment = Payment::create($advanceData);
                        $createdPaymentIds[] = (int) $advancePayment->id;
                        $this->unifiedLedgerService->recordAdvancePayment($advancePayment, 'customer');

                        $totalAmount += $advanceForMethod;
                    }
                }
            }

            $affectedSaleIdsList = array_values(array_map('intval', array_keys($affectedSaleIds)));
        });

        return [
            'bulk_reference' => $bulkReference,
            'total_amount'   => $totalAmount,
            'created_payment_ids' => array_values(array_unique(array_map('intval', $createdPaymentIds))),
            'affected_sale_ids' => $affectedSaleIdsList,
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────────

    private function updateSaleTable(int $saleId, float $returnCreditForThisSale = 0.0): void
    {
        $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)->find($saleId);
        if (!$sale) return;

        $totalCashPayments = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'sale')
            ->sum('amount');

        $totalAppliedCredit = Payment::where('reference_id', $sale->id)
            ->where('payment_type', 'advance_credit_usage')
            ->sum('amount');
        $linkedReturnCredit = $this->getAppliedReturnCreditForSale((int) $sale->id);

        // total_paid reflects: cash payments + linked return credits + applied credit (advance/unallocated)
        // returnCreditForThisSale is handled via SalesReturn total_paid; keep it only for backward compatibility.
        $sale->total_paid = $totalCashPayments + $totalAppliedCredit + max($linkedReturnCredit, (float) $returnCreditForThisSale);
        $sale->touch();
        // Sale::saving syncs payment_status from final_total / total_paid
        $sale->save();
    }

    private function getAppliedReturnCreditForSale(int $saleId): float
    {
        $returns = SalesReturn::where('sale_id', $saleId)->get(['id', 'total_paid']);
        if ($returns->isEmpty()) {
            return 0.0;
        }

        $totalReturnPaid = (float) $returns->sum(function ($return) {
            return (float) $return->total_paid;
        });

        $cashRefunded = Payment::whereIn('reference_id', $returns->pluck('id')->all())
            ->where('payment_type', 'sale_return_with_bill')
            ->sum('amount');

        return max(0.0, $totalReturnPaid - (float) $cashRefunded);
    }

    /** Customer balance is maintained automatically by the ledger system. */
    private function updateCustomerBalance(int $customerId): void {}
}
