<?php
namespace App\Services\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentReportService
{
    /** Build grouped collections (screen + PDF export). */
    public function getCollections(Request $request): array
    {
        $payments = $this->fetchPayments($request);
        return $this->groupIntoCollections($payments, includeDelivery: true);
    }

    /** Build grouped collections for export (slightly different field set). */
    public function getCollectionsForExport(Request $request): array
    {
        $payments = $this->fetchPayments($request);
        return $this->groupIntoCollections($payments, includeDelivery: false);
    }

    /** Flat payment collection for Excel export. */
    public function getDataForExport(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = \App\Models\Payment::with(['customer', 'supplier', 'sale', 'purchase', 'purchaseReturn'])
            ->select('payments.*');

        $this->applyFilters($query, $request);

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /** Summary totals by payment method / payment type. */
    public function getSummary(Request $request): array
    {
        $query = \App\Models\Payment::query();
        $this->applyFilters($query, $request);

        $totalAmount       = $query->sum('amount');
        $cashTotal         = (clone $query)->where('payment_method', 'cash')->sum('amount');
        $cardTotal         = (clone $query)->where('payment_method', 'card')->sum('amount');
        $chequeTotal       = (clone $query)->where('payment_method', 'cheque')->sum('amount');
        $bankTransferTotal = (clone $query)->where('payment_method', 'bank_transfer')->sum('amount');
        $otherTotal        = (clone $query)->whereNotIn('payment_method', ['cash','card','cheque','bank_transfer'])->sum('amount');
        $salePayments      = (clone $query)->where('payment_type', 'sale')->sum('amount');
        $purchasePayments  = (clone $query)->where('payment_type', 'purchase')->sum('amount');

        return compact(
            'totalAmount','cashTotal','cardTotal','chequeTotal',
            'bankTransferTotal','otherTotal','salePayments','purchasePayments'
        ) + [
            'total_amount'          => $totalAmount,
            'cash_total'            => $cashTotal,
            'card_total'            => $cardTotal,
            'cheque_total'          => $chequeTotal,
            'bank_transfer_total'   => $bankTransferTotal,
            'other_total'           => $otherTotal,
            'sale_payments'         => $salePayments,
            'purchase_payments'     => $purchasePayments,
        ];
    }

    /** Count payments by method (for PDF header). */
    public function getPaymentCounts(Request $request): array
    {
        $query = \App\Models\Payment::query();

        if (filled($request->start_date))     $query->whereDate('payment_date', '>=', $request->start_date);
        if (filled($request->end_date))       $query->whereDate('payment_date', '<=', $request->end_date);
        if (filled($request->customer_id))    $query->where('customer_id', $request->customer_id);
        if (filled($request->supplier_id))    $query->where('supplier_id', $request->supplier_id);
        if (filled($request->payment_method)) $query->where('payment_method', $request->payment_method);
        if (filled($request->payment_type))   $query->where('payment_type', $request->payment_type);

        return [
            'cash'          => (clone $query)->where('payment_method', 'cash')->count(),
            'cheque'        => (clone $query)->where('payment_method', 'cheque')->count(),
            'card'          => (clone $query)->where('payment_method', 'card')->count(),
            'bank_transfer' => (clone $query)->where('payment_method', 'bank_transfer')->count(),
            'total'         => (clone $query)->count(),
        ];
    }

    public function getLocationName($payment): string
    {
        if ($payment->sale && $payment->sale->location)               return $payment->sale->location->name;
        if ($payment->purchase && $payment->purchase->location)       return $payment->purchase->location->name;
        if ($payment->purchaseReturn && $payment->purchaseReturn->location) return $payment->purchaseReturn->location->name;
        return '';
    }

    public function getInvoiceNo($payment): string
    {
        if ($payment->sale)           return $payment->sale->invoice_no ?? '';
        if ($payment->purchase)       return $payment->purchase->invoice_no ?? '';
        if ($payment->purchaseReturn) return $payment->purchaseReturn->invoice_no ?? '';
        return '';
    }

    //  Private helpers 

    private function fetchPayments(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $query = \App\Models\Payment::with(['customer','supplier','sale','purchase','purchaseReturn'])
            ->select('payments.*');

        $this->applyFilters($query, $request);

        return $query->orderBy('payment_date','desc')
                     ->orderBy('reference_no','desc')
                     ->orderBy('id','desc')
                     ->get();
    }

    private function applyFilters($query, Request $request): void
    {
        if (filled($request->customer_id))    $query->where('customer_id', $request->customer_id);
        if (filled($request->supplier_id))    $query->where('supplier_id', $request->supplier_id);
        if (filled($request->payment_method)) $query->where('payment_method', $request->payment_method);
        if (filled($request->payment_type))   $query->where('payment_type', $request->payment_type);
        if (filled($request->start_date))     $query->whereDate('payment_date', '>=', $request->start_date);
        if (filled($request->end_date))       $query->whereDate('payment_date', '<=', $request->end_date);

        if (filled($request->location_id)) {
            $locId = $request->location_id;
            $query->where(function ($q) use ($locId) {
                $q->whereHas('sale',           fn($s) => $s->where('location_id', $locId))
                  ->orWhereHas('purchase',     fn($p) => $p->where('location_id', $locId))
                  ->orWhereHas('purchaseReturn', fn($r) => $r->where('location_id', $locId));
            });
        }
    }

    private function groupIntoCollections(
        \Illuminate\Database\Eloquent\Collection $payments,
        bool $includeDelivery
    ): array {
        $collections = [];

        foreach ($payments->groupBy('reference_no') as $referenceNo => $group) {
            $first        = $group->first();
            $locationName = $this->resolveLocationFromPayment($first);

            $paymentsData = $group->map(function ($payment) use ($includeDelivery) {
                return $this->mapPaymentDetails($payment, $includeDelivery);
            });

            $entry = [
                'reference_no'     => $referenceNo,
                'payment_date'     => $first->payment_date
                    ? Carbon::parse($first->payment_date)->format('Y-m-d') : '',
                'customer_name'    => $first->customer
                    ? $first->customer->full_name
                    : ($first->supplier ? $first->supplier->full_name : ''),
                'customer_address' => $first->customer
                    ? ($first->customer->address ?? '')
                    : ($first->supplier ? ($first->supplier->address ?? '') : ''),
                'location'         => $locationName,
                'total_amount'     => (float) $group->sum('amount'),
                'payments'         => $paymentsData->toArray(),
            ];

            if (!$includeDelivery) {
                // export variant adds is_bulk + plain payment_date
                $entry['payment_date']  = $first->payment_date;
                $entry['supplier_name'] = $first->supplier ? $first->supplier->full_name : '';
                $entry['is_bulk']       = (str_starts_with($referenceNo, 'BLK-') || str_starts_with($referenceNo, 'BULK-'))
                    && $paymentsData->count() > 1;
            }

            $collections[] = $entry;
        }

        return $collections;
    }

    private function mapPaymentDetails($payment, bool $includeDelivery): array
    {
        $invoiceNo    = '';
        $invoiceValue = 0.0;
        $invoiceDate  = '';
        $deliveryDate = '';

        if ($payment->payment_type === 'sale' && $payment->sale) {
            $invoiceNo    = $payment->sale->invoice_no ?? '';
            $invoiceValue = (float) ($payment->sale->final_total ?? 0);
            $invoiceDate  = $payment->sale->sales_date
                ? Carbon::parse($payment->sale->sales_date)->format('Y-m-d') : '';
            $deliveryDate = $payment->sale->expected_delivery_date
                ? Carbon::parse($payment->sale->expected_delivery_date)->format('Y-m-d') : '';
        } elseif ($payment->payment_type === 'purchase' && $payment->purchase) {
            $invoiceNo    = $payment->purchase->invoice_no ?? '';
            $invoiceValue = (float) ($payment->purchase->grand_total ?? 0);
            $invoiceDate  = $payment->purchase->purchase_date
                ? Carbon::parse($payment->purchase->purchase_date)->format('Y-m-d') : '';
        } elseif ($payment->purchaseReturn) {
            $invoiceNo    = $payment->purchaseReturn->invoice_no ?? '';
            $invoiceValue = (float) ($payment->purchaseReturn->grand_total ?? 0);
            $invoiceDate  = $payment->purchaseReturn->return_date
                ? Carbon::parse($payment->purchaseReturn->return_date)->format('Y-m-d') : '';
        }

        // Fallback via reference_id
        if ($invoiceValue == 0 && $payment->reference_id) {
            if ($payment->payment_type === 'sale') {
                $sale = \App\Models\Sale::withoutGlobalScope(\App\Scopes\LocationScope::class)
                    ->find($payment->reference_id);
                if ($sale) {
                    $invoiceNo    = $sale->invoice_no ?? '';
                    $invoiceValue = (float) ($sale->final_total ?? 0);
                    $invoiceDate  = $sale->sales_date
                        ? Carbon::parse($sale->sales_date)->format('Y-m-d') : '';
                    $deliveryDate = $sale->expected_delivery_date
                        ? Carbon::parse($sale->expected_delivery_date)->format('Y-m-d') : '';
                }
            } elseif ($payment->payment_type === 'purchase') {
                $purchase = \App\Models\Purchase::find($payment->reference_id);
                if ($purchase) {
                    $invoiceNo    = $purchase->invoice_no ?? '';
                    $invoiceValue = (float) ($purchase->grand_total ?? 0);
                    $invoiceDate  = $purchase->purchase_date
                        ? Carbon::parse($purchase->purchase_date)->format('Y-m-d') : '';
                }
            }
        }

        $row = [
            'id'                  => $payment->id,
            'payment_date'        => $payment->payment_date
                ? ($includeDelivery ? Carbon::parse($payment->payment_date)->format('Y-m-d') : $payment->payment_date)
                : '',
            'invoice_date'        => $invoiceDate,
            'invoice_no'          => $invoiceNo,
            'invoice_value'       => $invoiceValue,
            'amount'              => (float) $payment->amount,
            'payment_method'      => $includeDelivery ? ucfirst($payment->payment_method) : $payment->payment_method,
            'payment_type'        => $includeDelivery ? ucfirst($payment->payment_type)   : $payment->payment_type,
            'reference_no'        => $payment->reference_no ?? '',
            'cheque_number'       => $payment->cheque_number ?? '',
            'cheque_bank_branch'  => $payment->cheque_bank_branch ?? '',
            'cheque_valid_date'   => $payment->cheque_valid_date
                ? ($includeDelivery ? Carbon::parse($payment->cheque_valid_date)->format('Y-m-d') : $payment->cheque_valid_date)
                : '',
            'cheque_status'       => $payment->cheque_status
                ? ($includeDelivery ? ucfirst($payment->cheque_status) : $payment->cheque_status)
                : '',
            'notes'               => $payment->notes ?? '',
        ];

        if ($includeDelivery) {
            $row['amount_formatted'] = number_format($payment->amount, 2);
            $row['delivery_date']    = $deliveryDate;
            $row['customer_name']    = $payment->customer ? $payment->customer->full_name : '';
            $row['supplier_name']    = $payment->supplier ? $payment->supplier->full_name : '';
        }

        return $row;
    }

    private function resolveLocationFromPayment($payment): string
    {
        if ($payment->sale)               return optional($payment->sale->location)->name ?? '';
        if ($payment->purchase)           return optional($payment->purchase->location)->name ?? '';
        if ($payment->purchaseReturn)     return optional($payment->purchaseReturn->location)->name ?? '';
        return '';
    }
}