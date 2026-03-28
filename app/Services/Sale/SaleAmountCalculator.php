<?php

namespace App\Services\Sale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleAmountCalculator
{
    private const MONEY_SCALE = 100;
    private const QTY_SCALE = 10000;
    private const PERCENT_SCALE = 10000;

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
        $subtotalCents = 0;

        if (!empty($request->products)) {
            foreach ($request->products as $product) {
                $quantity = (float) ($product['quantity'] ?? 0);
                $unitPrice = (float) ($product['unit_price'] ?? 0);
                $taxPercent = (float) ($product['tax_percent'] ?? 0);
                $sellingPriceTaxType = strtolower((string) ($product['selling_price_tax_type'] ?? 'inclusive'));

                [$lineSubtotalCents, ] = $this->computeLineSubtotalCents($quantity, $unitPrice, $taxPercent, $sellingPriceTaxType);
                $subtotalCents += $lineSubtotalCents;
            }
        }

        $finalTotalCents = $this->applyDiscountAndShippingCents(
            $subtotalCents,
            (float) ($request->discount_amount  ?? 0),
            $request->discount_type ?? 'fixed',
            (float) ($request->shipping_charges ?? 0)
        );

        return $this->fromCents($finalTotalCents);
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
        $subtotalCents   = array_reduce($correctedProducts, fn($c, $p) => $c + $this->toCents((float) ($p['subtotal'] ?? 0)), 0);
        $discount        = (float) ($request->discount_amount  ?? 0);
        $shippingCharges = (float) ($request->shipping_charges ?? 0);
        $discountType    = $request->discount_type ?? 'fixed';

        $totalAfterDiscountCents = $this->applyDiscountOnlyCents($subtotalCents, $discount, $discountType);
        $finalTotalCents = $this->applyDiscountAndShippingCents($subtotalCents, $discount, $discountType, $shippingCharges);

        $subtotal = $this->fromCents($subtotalCents);
        $totalAfterDiscount = $this->fromCents($totalAfterDiscountCents);
        $finalTotal = $this->fromCents($finalTotalCents);

        // Step 3 — Payment amounts
        $paymentAmounts = $this->computePaymentAmounts($finalTotalCents, $request, $newStatus);

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
            $quantity         = (float) ($productData['quantity']   ?? 0);
            $unitPrice        = (float) ($productData['unit_price'] ?? 0);
            $taxPercent       = (float) ($productData['tax_percent'] ?? 0);
            $sellingPriceTaxType = strtolower((string) ($productData['selling_price_tax_type'] ?? 'inclusive'));
            $frontendSubtotal = (float) ($productData['subtotal']   ?? 0);

            [$correctSubtotalCents, $lineTaxCents] = $this->computeLineSubtotalCents(
                $quantity,
                $unitPrice,
                $taxPercent,
                $sellingPriceTaxType
            );

            $frontendSubtotalCents = $this->toCents($frontendSubtotal);

            $productData['tax'] = $this->fromCents($lineTaxCents);

            if ($correctSubtotalCents !== $frontendSubtotalCents) {
                Log::warning('⚠️ SUBTOTAL MISMATCH — server corrected', [
                    'product_id'         => $productData['product_id'] ?? 'unknown',
                    'quantity'           => $quantity,
                    'unit_price'         => $unitPrice,
                    'tax_percent'        => $taxPercent,
                    'selling_price_tax_type' => $sellingPriceTaxType,
                    'frontend_subtotal'  => $this->fromCents($frontendSubtotalCents),
                    'corrected_subtotal' => $this->fromCents($correctSubtotalCents),
                    'difference'         => $this->fromCents($correctSubtotalCents - $frontendSubtotalCents),
                ]);
            }

            $productData['subtotal'] = $this->fromCents($correctSubtotalCents);
            $corrected[]             = $productData;
        }

        return $corrected;
    }

    /**
     * Apply discount (fixed or percentage) and add shipping charges.
     * Used by estimateFinalTotal() and internally by calculate().
     */
    private function applyDiscountAndShippingCents(
        int    $subtotalCents,
        float  $discount,
        string $discountType,
        float  $shippingCharges
    ): int {
        $totalAfterDiscountCents = $this->applyDiscountOnlyCents($subtotalCents, $discount, $discountType);
        $shippingCents = $this->toCents($shippingCharges);

        return max(0, $totalAfterDiscountCents + $shippingCents);
    }

    private function applyDiscountOnlyCents(int $subtotalCents, float $discount, string $discountType): int
    {
        if ($discountType === 'percentage') {
            $discountBps = $this->toPercentScaled($discount);
            $discountCents = $this->roundDiv($subtotalCents * $discountBps, self::PERCENT_SCALE);

            return max(0, $subtotalCents - $discountCents);
        }

        $discountCents = $this->toCents($discount);

        return max(0, $subtotalCents - $discountCents);
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
    private function computePaymentAmounts(int $finalTotalCents, Request $request, string $newStatus): array
    {
        $advanceAmountCents = $this->toCents((float) ($request->advance_amount ?? 0));

        if ($newStatus === 'jobticket') {
            if ($advanceAmountCents >= $finalTotalCents) {
                return [
                    'advance_amount' => $this->fromCents($advanceAmountCents),
                    'total_paid'     => $this->fromCents($finalTotalCents),
                    'total_due'      => 0.0,
                    'amount_given'   => $this->fromCents($advanceAmountCents),
                    'balance_amount' => $this->fromCents($advanceAmountCents - $finalTotalCents),
                ];
            }

            return [
                'advance_amount' => $this->fromCents($advanceAmountCents),
                'total_paid'     => $this->fromCents($advanceAmountCents),
                'total_due'      => $this->fromCents($finalTotalCents - $advanceAmountCents),
                'amount_given'   => $this->fromCents($advanceAmountCents),
                'balance_amount' => 0.0,
            ];
        }

        // Normal sale
        $amountGivenCents = $this->toCents((float) ($request->amount_given ?? $this->fromCents($finalTotalCents)));
        $totalPaidCents = min($amountGivenCents, $finalTotalCents);
        $totalDueCents = max(0, $finalTotalCents - $totalPaidCents);
        $balanceAmountCents = max(0, $amountGivenCents - $finalTotalCents);

        return [
            'advance_amount' => $this->fromCents($advanceAmountCents),
            'total_paid'     => $this->fromCents($totalPaidCents),
            'total_due'      => $this->fromCents($totalDueCents),
            'amount_given'   => $this->fromCents($amountGivenCents),
            'balance_amount' => $this->fromCents($balanceAmountCents),
        ];
    }

    private function computeLineSubtotalCents(float $quantity, float $unitPrice, float $taxPercent, string $sellingPriceTaxType): array
    {
        $qtyUnits = $this->toQtyUnits($quantity);
        $unitPriceCents = $this->toCents($unitPrice);
        $baseSubtotalCents = $this->roundDiv($qtyUnits * $unitPriceCents, self::QTY_SCALE);

        $lineTaxCents = 0;
        if ($sellingPriceTaxType === 'exclusive' && $taxPercent > 0) {
            $taxBps = $this->toPercentScaled($taxPercent);
            $lineTaxCents = $this->roundDiv($baseSubtotalCents * $taxBps, self::PERCENT_SCALE);
        }

        return [$baseSubtotalCents + $lineTaxCents, $lineTaxCents];
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * self::MONEY_SCALE);
    }

    private function fromCents(int $cents): float
    {
        return round($cents / self::MONEY_SCALE, 2);
    }

    private function toQtyUnits(float $qty): int
    {
        return (int) round($qty * self::QTY_SCALE);
    }

    private function toPercentScaled(float $percent): int
    {
        return (int) round($percent * 100);
    }

    private function roundDiv(int $numerator, int $denominator): int
    {
        if ($denominator === 0) {
            return 0;
        }

        $half = intdiv($denominator, 2);
        if ($numerator >= 0) {
            return intdiv($numerator + $half, $denominator);
        }

        return -intdiv(abs($numerator) + $half, $denominator);
    }
}
