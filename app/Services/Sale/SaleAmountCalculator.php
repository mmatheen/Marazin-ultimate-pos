<?php

namespace App\Services\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleAmountCalculator
{
    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Quick final-total estimate used BEFORE the DB transaction for the early
     * credit-limit check. Works from raw (uncorrected) product data.
     *
     * Returns: float $finalTotal
     */
    public function estimateFinalTotal(Request $request): float
    {
        $subtotal = 0.0;

        if (!empty($request->products)) {
            foreach ($request->products as $product) {
                $subtotal += floatval($product['quantity']   ?? 0)
                           * floatval($product['unit_price'] ?? 0);
            }
        }

        return $this->applyDiscountAndShipping(
            $subtotal,
            floatval($request->discount_amount  ?? 0),
            $request->discount_type ?? 'fixed',
            floatval($request->shipping_charges ?? 0)
        );
    }

    /**
     * Full calculation used INSIDE the DB transaction.
     *
     * 1. Corrects every product's subtotal server-side (qty × unit_price).
     * 2. Calculates subtotal / discount / shipping / finalTotal.
     * 3. Calculates payment amounts (jobticket vs normal sale logic).
     *
     * Returns an array with all values the controller needs:
     *
     *   corrected_products  — array  (each item has its subtotal fixed)
     *   subtotal            — float
     *   discount            — float
     *   shipping_charges    — float
     *   total_after_discount— float
     *   final_total         — float
     *   advance_amount      — float
     *   total_paid          — float
     *   total_due           — float
     *   amount_given        — float
     *   balance_amount      — float
     */
    public function calculate(array $products, Request $request, string $newStatus): array
    {
        // Step 1 — Correct product subtotals
        $correctedProducts = $this->correctProductSubtotals($products, $request);

        // Step 2 — Monetary totals
        $subtotal        = array_reduce($correctedProducts, fn($c, $p) => $c + $p['subtotal'], 0.0);
        $discount        = floatval($request->discount_amount  ?? 0);
        $shippingCharges = floatval($request->shipping_charges ?? 0);
        $discountType    = $request->discount_type ?? 'fixed';

        $totalAfterDiscount = $discountType === 'percentage'
            ? $subtotal - ($subtotal * $discount / 100)
            : $subtotal - $discount;

        $finalTotal = $totalAfterDiscount + $shippingCharges;

        // Step 3 — Payment amounts
        $paymentAmounts = $this->computePaymentAmounts($finalTotal, $request, $newStatus);

        return array_merge(
            [
                'corrected_products'   => $correctedProducts,
                'subtotal'             => $subtotal,
                'discount'             => $discount,
                'shipping_charges'     => $shippingCharges,
                'total_after_discount' => $totalAfterDiscount,
                'final_total'          => $finalTotal,
            ],
            $paymentAmounts
        );
    }

    // -------------------------------------------------------------------------
    // PRIVATE HELPERS
    // -------------------------------------------------------------------------

    /**
     * Correct each product's subtotal to qty × unit_price (prevents frontend
     * calculation errors from reaching the database).
     * Logs a warning when a mismatch is detected.
     */
    private function correctProductSubtotals(array $products, Request $request): array
    {
        $corrected = [];

        foreach ($products as $productData) {
            $quantity         = floatval($productData['quantity']   ?? 0);
            $unitPrice        = floatval($productData['unit_price'] ?? 0);
            $frontendSubtotal = floatval($productData['subtotal']   ?? 0);
            $correctSubtotal  = $quantity * $unitPrice;

            if (abs($correctSubtotal - $frontendSubtotal) > 0.01) {
                Log::warning('⚠️ SUBTOTAL MISMATCH — server corrected', [
                    'product_id'         => $productData['product_id'] ?? 'unknown',
                    'quantity'           => $quantity,
                    'unit_price'         => $unitPrice,
                    'frontend_subtotal'  => $frontendSubtotal,
                    'corrected_subtotal' => $correctSubtotal,
                    'difference'         => $correctSubtotal - $frontendSubtotal,
                ]);
            }

            $productData['subtotal'] = $correctSubtotal;
            $corrected[]             = $productData;
        }

        return $corrected;
    }

    /**
     * Apply discount (fixed or percentage) and add shipping charges.
     * Used by estimateFinalTotal() and internally by calculate().
     */
    private function applyDiscountAndShipping(
        float  $subtotal,
        float  $discount,
        string $discountType,
        float  $shippingCharges
    ): float {
        $totalAfterDiscount = $discountType === 'percentage'
            ? $subtotal - ($subtotal * $discount / 100)
            : $subtotal - $discount;

        return $totalAfterDiscount + $shippingCharges;
    }

    /**
     * Compute total_paid / total_due / amount_given / balance_amount.
     *
     * Job ticket logic:
     *   - advance >= finalTotal → fully paid, surplus returned as balance
     *   - advance < finalTotal  → partial paid, remainder is due
     *
     * Normal sale logic:
     *   - amount_given drives total_paid (capped at finalTotal)
     *   - change (balance_amount) = amount_given − finalTotal  (if positive)
     */
    private function computePaymentAmounts(float $finalTotal, Request $request, string $newStatus): array
    {
        $advanceAmount = floatval($request->advance_amount ?? 0);

        if ($newStatus === 'jobticket') {
            if ($advanceAmount >= $finalTotal) {
                return [
                    'advance_amount' => $advanceAmount,
                    'total_paid'     => $finalTotal,
                    'total_due'      => 0.0,
                    'amount_given'   => $advanceAmount,
                    'balance_amount' => $advanceAmount - $finalTotal,
                ];
            }

            return [
                'advance_amount' => $advanceAmount,
                'total_paid'     => $advanceAmount,
                'total_due'      => $finalTotal - $advanceAmount,
                'amount_given'   => $advanceAmount,
                'balance_amount' => 0.0,
            ];
        }

        // Normal sale
        $amountGiven   = floatval($request->amount_given ?? $finalTotal);
        $totalPaid     = min($amountGiven, $finalTotal);
        $totalDue      = max(0.0, $finalTotal - $totalPaid);
        $balanceAmount = max(0.0, $amountGiven - $finalTotal);

        return [
            'advance_amount' => $advanceAmount,
            'total_paid'     => $totalPaid,
            'total_due'      => $totalDue,
            'amount_given'   => $amountGiven,
            'balance_amount' => $balanceAmount,
        ];
    }
}
