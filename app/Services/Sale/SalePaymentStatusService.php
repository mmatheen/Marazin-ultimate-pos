<?php

namespace App\Services\Sale;

class SalePaymentStatusService
{
    public function deriveForInvoice(float $finalTotal, ?float $totalPaid): string
    {
        if ($totalPaid === null) {
            $due = $finalTotal;
            $paid = 0.0;
        } else {
            $paid = (float) $totalPaid;
            $due = max(0.0, $finalTotal - $paid);
        }

        if ($due <= 0.005) {
            return 'Paid';
        }

        if ($paid > 0.005) {
            return 'Partial';
        }

        return 'Due';
    }
}
