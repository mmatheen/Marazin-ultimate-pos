<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;
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
        private readonly UnifiedLedgerService $unifiedLedgerService
    ) {}

    /**
     * Process a flexible, multi-method bulk sale payment for a customer.
     *
     * @return array{bulk_reference: string, total_amount: float}
     * @throws \Exception
     */
    public function process(Request $request): array
    {
        $bulkReference = null;
        $totalAmount   = 0.0;

        DB::transaction(function () use ($request, &$bulkReference, &$totalAmount) {

            $bulkReference  = $this->generateMeaningfulBulkReference($request);
            $totalAmount    = 0.0;
            $processedGroups = [];

            // ── Step 1: tally OB vs sale payment totals ──────────────────────────
            $totalOBPayment      = 0.0;
            $totalSalePayment    = 0.0;
            $totalPaymentAmount  = 0.0;

            foreach ($request->payment_groups as $paymentGroup) {
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

                foreach ($request->payment_groups as $paymentGroup) {
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
                        $returnPayment = Payment::create([
                            'payment_date'   => $request->payment_date,
                            'amount'         => $returnData['amount'],
                            'payment_method' => 'cash',
                            'payment_type'   => $salesReturn->sale_id
                                ? 'sale_return_with_bill'
                                : 'sale_return_without_bill',
                            'reference_id'   => $salesReturn->id,
                            'reference_no'   => $bulkReference,
                            'customer_id'    => $request->customer_id,
                            'notes'          => 'Cash refund for return: ' . $salesReturn->invoice_number,
                        ]);

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

                $this->unifiedLedgerService->recordAdvanceCreditUsage($advancePayment, 'customer', auth()->id());
            }

            // ── Step 5: process payment groups ───────────────────────────────────
            foreach ($request->payment_groups as $groupIndex => $paymentGroup) {
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
                        $this->unifiedLedgerService->recordSalePayment($payment);

                        // Apply any return credit allocation for this bill
                        $returnCreditForBill = 0.0;
                        if ($request->has('bill_return_allocations') && isset($request->bill_return_allocations[$bill['sale_id']])) {
                            $returnCreditForBill = floatval($request->bill_return_allocations[$bill['sale_id']]);
                        }

                        $this->updateSaleTable($bill['sale_id'], $returnCreditForBill);
                        $affectedSaleIds[$bill['sale_id']] = true;

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

            // ── Step 6: apply any remaining return-credit allocations to sales ───
            if ($request->has('bill_return_allocations') && !empty($request->bill_return_allocations)) {
                foreach ($request->bill_return_allocations as $saleId => $creditAmount) {
                    if ($creditAmount > 0) {
                        $sale = Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                                   ->where('id', $saleId)
                                   ->where('customer_id', $request->customer_id)
                                   ->first();

                        if (!$sale) {
                            throw new \Exception("Sale {$saleId} not found for customer");
                        }

                        $affectedSaleIds[$saleId] = true;
                    }
                }
            }

            // ── Step 7: excess amount → advance payments for 'both' type ─────────
            if ($request->payment_type === 'both') {
                $totalEntered = array_sum(array_column(
                    array_map(fn ($pg) => ['t' => floatval($pg['totalAmount'] ?? 0)], $request->payment_groups),
                    't'
                ));

                $advanceAmount = $totalEntered - ($totalOBPayment + $totalSalePayment);

                if ($advanceAmount > 0.01) {
                    foreach ($request->payment_groups as $paymentGroup) {
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
                        $this->unifiedLedgerService->recordAdvancePayment($advancePayment, 'customer');

                        $totalAmount += $advanceForMethod;
                    }
                }
            }
        });

        return [
            'bulk_reference' => $bulkReference,
            'total_amount'   => $totalAmount,
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

        $sale->total_paid = $totalCashPayments + $returnCreditForThisSale;
        $sale->touch();
        $sale->save();
        $sale->refresh();

        $sale->payment_status = match (true) {
            $sale->total_due <= 0 => 'Paid',
            $sale->total_paid > 0 => 'Partial',
            default               => 'Due',
        };
        $sale->save();
    }

    /** Customer balance is maintained automatically by the ledger system. */
    private function updateCustomerBalance(int $customerId): void {}
}
