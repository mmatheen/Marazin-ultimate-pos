<?php

namespace App\Services\Sale;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SalesReturn;

class SaleSettlementService
{
    public function calculateSettlement(Sale $sale): array
    {
        $salePayments = $this->getSalePaymentTotal((int) $sale->id);
        $advanceCreditUsage = $this->getAdvanceCreditUsageTotal((int) $sale->id);
        $returnCredit = $this->getAppliedReturnCreditForSale((int) $sale->id);

        $totalPaid = round($salePayments + $advanceCreditUsage + $returnCredit, 2);
        $totalDue = round((float) $sale->final_total - $totalPaid, 2);

        if ($totalDue < 0) {
            $totalDue = 0.0;
        }

        $paymentStatus = $this->derivePaymentStatus((float) $sale->final_total, $totalPaid);

        return [
            'sale_id' => (int) $sale->id,
            'customer_id' => (int) $sale->customer_id,
            'invoice_no' => $sale->invoice_no,
            'final_total' => (float) $sale->final_total,
            'stored_total_paid' => (float) $sale->total_paid,
            'stored_total_due' => (float) $sale->total_due,
            'stored_payment_status' => (string) $sale->payment_status,
            'sale_payments' => $salePayments,
            'advance_credit_usage' => $advanceCreditUsage,
            'return_credit' => $returnCredit,
            'calculated_total_paid' => $totalPaid,
            'calculated_total_due' => $totalDue,
            'calculated_payment_status' => $paymentStatus,
            'needs_update' =>
                abs((float) $sale->total_paid - $totalPaid) > 0.009
                || abs((float) $sale->total_due - $totalDue) > 0.009
                || $sale->payment_status !== $paymentStatus,
        ];
    }

    public function syncSale(Sale $sale): array
    {
        $settlement = $this->calculateSettlement($sale);

        $sale->total_paid = $settlement['calculated_total_paid'];
        $sale->total_due = $settlement['calculated_total_due'];
        $sale->payment_status = $settlement['calculated_payment_status'];
        $sale->save();

        $settlement['was_persisted'] = true;

        return $settlement;
    }

    public function getSalePaymentTotal(int $saleId): float
    {
        return (float) Payment::where('reference_id', $saleId)
            ->where('payment_type', 'sale')
            ->where('status', '!=', 'deleted')
            ->sum('amount');
    }

    public function getAdvanceCreditUsageTotal(int $saleId): float
    {
        return (float) Payment::where('reference_id', $saleId)
            ->where('payment_type', 'advance_credit_usage')
            ->where('status', '!=', 'deleted')
            ->sum('amount');
    }

    public function getAppliedReturnCreditForSale(int $saleId): float
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
            ->where('status', '!=', 'deleted')
            ->sum('amount');

        return max(0.0, $totalReturnPaid - (float) $cashRefunded);
    }

    private function derivePaymentStatus(float $finalTotal, float $totalPaid): string
    {
        $due = max(0.0, $finalTotal - $totalPaid);

        if ($due <= 0.005) {
            return 'Paid';
        }

        if ($totalPaid > 0.005) {
            return 'Partial';
        }

        return 'Due';
    }
}
