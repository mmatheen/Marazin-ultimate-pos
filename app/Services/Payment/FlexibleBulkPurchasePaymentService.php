<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Purchase;
use App\Services\UnifiedLedgerService;
use App\Traits\BulkPaymentHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlexibleBulkPurchasePaymentService
{
    use BulkPaymentHelpers;

    public function __construct(
        private readonly UnifiedLedgerService $unifiedLedgerService
    ) {}

    /**
     * Process a flexible, multi-method bulk purchase payment for a supplier.
     *
     * @return array{bulk_reference: string, total_amount: float}
     * @throws \Exception
     */
    public function process(Request $request): array
    {
        $bulkReference = null;
        $totalAmount   = 0.0;

        DB::transaction(function () use ($request, &$bulkReference, &$totalAmount) {

            $bulkReference   = $this->generateMeaningfulBulkReference($request);
            $totalAmount     = 0.0;
            $processedGroups = [];

            // ── Step 1: tally OB vs purchase payment totals ───────────────────────
            $totalOBPayment       = 0.0;
            $totalPurchasePayment = 0.0;

            foreach ($request->payment_groups as $paymentGroup) {
                if ($request->payment_type === 'opening_balance' && isset($paymentGroup['totalAmount'])) {
                    $totalOBPayment += floatval($paymentGroup['totalAmount']);
                }

                if ($request->payment_type === 'both') {
                    if (isset($paymentGroup['ob_amount'])) {
                        $totalOBPayment += floatval($paymentGroup['ob_amount']);
                    }
                    if (isset($paymentGroup['bills']) && is_array($paymentGroup['bills'])) {
                        foreach ($paymentGroup['bills'] as $bill) {
                            $totalPurchasePayment += floatval($bill['amount']);
                        }
                    }
                }

                if ($request->payment_type === 'purchase_dues' && isset($paymentGroup['bills'])) {
                    foreach ($paymentGroup['bills'] as $bill) {
                        $totalPurchasePayment += floatval($bill['amount']);
                    }
                }
            }

            $this->validatePaymentAmounts(
                'supplier',
                (int) $request->supplier_id,
                $request->payment_type,
                $totalOBPayment,
                $totalPurchasePayment
            );

            // ── Step 2: process payment groups ────────────────────────────────────
            foreach ($request->payment_groups as $groupIndex => $paymentGroup) {
                $groupTotal    = 0.0;
                $groupPayments = [];

                // Opening-balance payment (for 'opening_balance' type OR 'both' with ob_amount)
                if ($request->payment_type === 'opening_balance' ||
                    ($request->payment_type === 'both' &&
                     isset($paymentGroup['ob_amount']) && $paymentGroup['ob_amount'] > 0)) {

                    $obAmount = $request->payment_type === 'opening_balance'
                        ? floatval($paymentGroup['totalAmount'])
                        : floatval($paymentGroup['ob_amount']);

                    $paymentData = [
                        'payment_date'   => $request->payment_date,
                        'amount'         => $obAmount,
                        'payment_method' => $paymentGroup['method'],
                        'payment_type'   => 'opening_balance',
                        'reference_id'   => null,
                        'reference_no'   => $bulkReference,
                        'supplier_id'    => $request->supplier_id,
                        'notes'          => $request->notes ?? null,
                    ];
                    $this->addMethodSpecificFields($paymentData, $paymentGroup);

                    $payment = Payment::create($paymentData);
                    $this->unifiedLedgerService->recordOpeningBalancePayment($payment, 'supplier');

                    $groupTotal      += $obAmount;
                    $groupPayments[] = ['payment_id' => $payment->id, 'type' => 'opening_balance', 'amount' => $obAmount];
                }

                // Purchase-bill payments (for 'purchase_dues' type OR 'both' with bills)
                if (in_array($request->payment_type, ['purchase_dues', 'both']) &&
                    isset($paymentGroup['bills']) && !empty($paymentGroup['bills'])) {

                    foreach ($paymentGroup['bills'] as $bill) {
                        $purchase = Purchase::where('id', $bill['purchase_id'])
                                           ->where('supplier_id', $request->supplier_id)
                                           ->first();

                        if (!$purchase) {
                            throw new \Exception("Purchase {$bill['purchase_id']} not found for supplier");
                        }
                        if ($bill['amount'] > $purchase->total_due) {
                            throw new \Exception("Payment amount Rs.{$bill['amount']} exceeds due Rs.{$purchase->total_due} for invoice {$purchase->invoice_no}");
                        }

                        $paymentData = [
                            'payment_date'   => $request->payment_date,
                            'amount'         => $bill['amount'],
                            'payment_method' => $paymentGroup['method'],
                            'payment_type'   => 'purchase',
                            'reference_id'   => $bill['purchase_id'],
                            'reference_no'   => $bulkReference,
                            'supplier_id'    => $request->supplier_id,
                            'notes'          => $request->notes ?? null,
                        ];
                        $this->addMethodSpecificFields($paymentData, $paymentGroup);

                        $payment = Payment::create($paymentData);
                        $this->unifiedLedgerService->recordPurchasePayment($payment);
                        $this->updatePurchaseTable($bill['purchase_id']);

                        $groupTotal      += floatval($bill['amount']);
                        $groupPayments[] = [
                            'payment_id'  => $payment->id,
                            'purchase_id' => $bill['purchase_id'],
                            'invoice_no'  => $purchase->invoice_no,
                            'amount'      => $bill['amount'],
                        ];
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

            // ── Step 3: excess amount → advance payments for 'both' type ─────────
            if ($request->payment_type === 'both') {
                $totalEntered = 0.0;
                foreach ($request->payment_groups as $pg) {
                    $totalEntered += floatval($pg['totalAmount'] ?? 0);
                }

                $advanceAmount = $totalEntered - ($totalOBPayment + $totalPurchasePayment);

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
                            'supplier_id'    => $request->supplier_id,
                            'notes'          => '[Advance Payment]',
                        ];
                        $this->addMethodSpecificFields($advanceData, $paymentGroup);

                        $advancePayment = Payment::create($advanceData);
                        $this->unifiedLedgerService->recordAdvancePayment($advancePayment, 'supplier');
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

    private function updatePurchaseTable(int $purchaseId): void
    {
        $purchase = Purchase::find($purchaseId);
        if (!$purchase) return;

        $totalPaid = Payment::where('reference_id', $purchase->id)
            ->where('payment_type', 'purchase')
            ->sum('amount');

        $purchase->total_paid = $totalPaid;
        $purchase->save();
        $purchase->refresh();

        $purchase->payment_status = match (true) {
            $purchase->total_due <= 0 => 'Paid',
            $purchase->total_paid > 0 => 'Partial',
            default                   => 'Due',
        };
        $purchase->save();
    }

    /** Supplier balance is maintained automatically by the ledger system. */
    private function updateSupplierBalance(int $supplierId): void {}
}
