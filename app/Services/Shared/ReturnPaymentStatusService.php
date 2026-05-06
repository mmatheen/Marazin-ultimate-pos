<?php

namespace App\Services\Shared;

class ReturnPaymentStatusService
{
    public function derive(float $returnTotal, float $totalPaid): string
    {
        $due = $returnTotal - $totalPaid;

        if ($totalPaid <= 0) {
            return 'Due';
        }

        if ($due <= 0.01) {
            return 'Paid';
        }

        return 'Partial';
    }
}
