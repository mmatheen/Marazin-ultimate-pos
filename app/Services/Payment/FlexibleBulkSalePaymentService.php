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

            // ── Step 4: advance credit application ───────────────────────────────
            if ($request->has('advance_credit_applied') && $request->advance_credit_applied > 0) {
                $advanceCreditAmount = floatval($request->advance_credit_applied);

                $customerBalance = BalanceHelper::getCustomerBalance($request->customer_id);
                if ($customerBalance >= 0) {
                    throw new \Exception('Customer does not have any advance credit available.');
                }

                $availableAdvanceCredit = abs($customerBalance);
                if ($advanceCreditAmount > $availableAdvanceCredit) {
                    throw new \Exception("Advance credit amount Rs.{$advanceCreditAmount} exceeds available advance credit Rs.{$availableAdvanceCredit}");
                }

                $advancePayment = Payment::create([
                    'payment_date'   => $request->payment_date,
                    'amount'         => $advanceCreditAmount,
                    'payment_method' => 'advance_credit',
                    'payment_type'   => 'advance_credit_usage',
                    'reference_id'   => null,
                    'reference_no'   => $bulkReference,
                    'customer_id'    => $request->customer_id,
                    'notes'          => 'Advance credit applied to bills (from previous overpayments)',
                ]);
                $createdPaymentIds[] = (int) $advancePayment->id;

                $this->unifiedLedgerService->recordAdvanceCreditUsage($advancePayment, 'customer', auth()->id());
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

            // ── Step 6: apply return-credit allocations for sales not updated in Step 5 ───
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

            // ── Step 7: excess amount → advance payments for 'both' type ─────────
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

        $existingNonCashCredit = max(0, (float) $sale->total_paid - (float) $totalCashPayments);
        $linkedReturnCredit = $this->getAppliedReturnCreditForSale((int) $sale->id);
        $effectiveReturnCredit = max(
            $existingNonCashCredit,
            (float) $returnCreditForThisSale,
            $linkedReturnCredit
        );

        $sale->total_paid = $totalCashPayments + $effectiveReturnCredit;
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
