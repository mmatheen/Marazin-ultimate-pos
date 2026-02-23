<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * Shared helpers for FlexibleBulkSalePaymentService and FlexibleBulkPurchasePaymentService.
 */
trait BulkPaymentHelpers
{
    /**
     * Merge cheque / card / bank-transfer specific fields into a payment data array.
     */
    private function addMethodSpecificFields(array &$paymentData, array $paymentGroup): void
    {
        if ($paymentGroup['method'] === 'cheque') {
            $paymentData = array_merge($paymentData, [
                'cheque_number'        => $paymentGroup['cheque_number']        ?? null,
                'cheque_bank_branch'   => $paymentGroup['cheque_bank_branch']   ?? null,
                'cheque_valid_date'    => $paymentGroup['cheque_valid_date']    ?? null,
                'cheque_received_date' => $paymentData['payment_date'],
                'cheque_status'        => 'pending',
                'cheque_given_by'      => $paymentGroup['cheque_given_by']      ?? null,
            ]);
        } elseif ($paymentGroup['method'] === 'card') {
            $paymentData = array_merge($paymentData, [
                'card_number'      => $paymentGroup['card_number']  ?? null,
                'card_holder_name' => $paymentGroup['card_holder']  ?? null,
            ]);
        } elseif ($paymentGroup['method'] === 'bank_transfer') {
            $paymentData['bank_account_number'] = $paymentGroup['bank_account_number'] ?? null;
        }
    }

    /**
     * Generate a sequential, human-readable bulk payment reference.
     * Examples: BLK-S0001, BLK-P0001, BLK-SOB0001, BLK-POB0001
     */
    private function generateMeaningfulBulkReference(Request $request): string
    {
        $paymentType = $request->payment_type;

        $category = isset($request->customer_id)
            ? ($paymentType === 'opening_balance' ? 'SOB' : 'S')
            : ($paymentType === 'opening_balance' ? 'POB' : 'P');

        $last = Payment::whereRaw("reference_no REGEXP '^BLK-{$category}[0-9]{4}'")
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($last?->reference_no) {
            preg_match('/-' . $category . '(\d{4})$/', $last->reference_no, $matches);
            if (!empty($matches[1])) {
                $seq = (int) $matches[1] + 1;
            }
        }

        return 'BLK-' . $category . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validate that OB and reference payment totals don't exceed available balances.
     *
     * @throws \Exception
     */
    private function validatePaymentAmounts(
        string $contactType,
        int    $contactId,
        string $paymentType,
        float  $totalOBPayment,
        float  $totalRefPayment
    ): void {
        $entity = $contactType === 'customer'
            ? Customer::findOrFail($contactId)
            : Supplier::findOrFail($contactId);

        if ($totalOBPayment > 0) {
            if ($paymentType === 'opening_balance') {
                $refDue = $contactType === 'customer'
                    ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                          ->where('customer_id', $contactId)->where('total_due', '>', 0)->sum('total_due')
                    : Purchase::where('supplier_id', $contactId)->where('total_due', '>', 0)->sum('total_due');

                $maxOB = max(0.0, (float) $entity->current_balance - $refDue);
                if ($totalOBPayment > $maxOB) {
                    throw new \Exception(
                        'Opening balance payment amount Rs.' . number_format($totalOBPayment, 2) .
                        ' exceeds available opening balance Rs.' . number_format($maxOB, 2)
                    );
                }
            } else {
                if ($totalOBPayment > $entity->current_balance) {
                    throw new \Exception(
                        'Opening balance payment amount Rs.' . number_format($totalOBPayment, 2) .
                        " exceeds {$contactType}'s current balance Rs." . number_format($entity->current_balance, 2)
                    );
                }
            }
        }

        if ($totalRefPayment > 0 && in_array($paymentType, ['both', 'sale_dues', 'purchase_dues'])) {
            $totalRefDue = $contactType === 'customer'
                ? Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                      ->where('customer_id', $contactId)->where('total_due', '>', 0)->sum('total_due')
                : Purchase::where('supplier_id', $contactId)->where('total_due', '>', 0)->sum('total_due');

            if ($totalRefPayment > $totalRefDue) {
                $refType = $contactType === 'customer' ? 'Sale' : 'Purchase';
                throw new \Exception(
                    "{$refType} payment amount Rs." . number_format($totalRefPayment, 2) .
                    " exceeds total {$refType} due Rs." . number_format($totalRefDue, 2)
                );
            }
        }
    }
}
