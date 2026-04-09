<?php

namespace App\Services\Sale;

use App\Models\Sale;

class SaleCalculationService
{
    public function calculateFinalTotal(Sale $sale): float
    {
        $subtotal = (float) ($sale->subtotal ?? 0);
        $discountAmount = (float) ($sale->discount_amount ?? 0);
        $shippingCharges = (float) ($sale->shipping_charges ?? 0);

        if ($sale->discount_type === 'percentage') {
            $discountAmount = $subtotal * $discountAmount / 100;
        }

        $baseTotal = max(0.0, $subtotal - $discountAmount);

        return $baseTotal + $shippingCharges;
    }
}
