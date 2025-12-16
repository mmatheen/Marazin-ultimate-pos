<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = collect($data);
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Payment ID',
            'Payment Date',
            'Invoice Date',
            'Invoice No',
            'Invoice Value',
            'Amount',
            'Payment Method',
            'Payment Type',
            'Reference No',
            'Customer Name',
            'Supplier Name',
            'Location',
            'Cheque Number',
            'Bank & Branch',
            'Due Date',
            'Cheque Status',
            'Notes',
        ];
    }

    public function map($payment): array
    {
        $locationName = '';
        $invoiceNo = '';
        $invoiceValue = 0;
        $invoiceDate = '';

        if ($payment->sale) {
            $locationName = optional($payment->sale->location)->name ?? '';
            $invoiceNo = $payment->sale->invoice_no ?? '';
            $invoiceValue = $payment->sale->final_total ?? 0;
            $invoiceDate = $payment->sale->sales_date ? \Carbon\Carbon::parse($payment->sale->sales_date)->format('Y-m-d') : '';
        } elseif ($payment->purchase) {
            $locationName = optional($payment->purchase->location)->name ?? '';
            $invoiceNo = $payment->purchase->invoice_no ?? '';
            $invoiceValue = $payment->purchase->grand_total ?? 0;
            $invoiceDate = $payment->purchase->purchase_date ? \Carbon\Carbon::parse($payment->purchase->purchase_date)->format('Y-m-d') : '';
        } elseif ($payment->purchaseReturn) {
            $locationName = optional($payment->purchaseReturn->location)->name ?? '';
            $invoiceNo = $payment->purchaseReturn->invoice_no ?? '';
            $invoiceValue = $payment->purchaseReturn->grand_total ?? 0;
            $invoiceDate = $payment->purchaseReturn->return_date ? \Carbon\Carbon::parse($payment->purchaseReturn->return_date)->format('Y-m-d') : '';
        }

        return [
            $payment->id,
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '',
            $invoiceDate,
            $invoiceNo,
            $invoiceValue,
            $payment->amount,
            ucfirst($payment->payment_method),
            ucfirst($payment->payment_type),
            $payment->reference_no ?? '',
            $payment->customer ? $payment->customer->full_name : '',
            $payment->supplier ? $payment->supplier->full_name : '',
            $locationName,
            $payment->cheque_number ?? '',
            $payment->cheque_bank_branch ?? '',
            $payment->cheque_valid_date ? \Carbon\Carbon::parse($payment->cheque_valid_date)->format('Y-m-d') : '',
            $payment->cheque_status ? ucfirst($payment->cheque_status) : '',
            $payment->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => ['font' => ['bold' => true, 'size' => 12]],

            // Auto-size columns
            'A:N' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return 'Payment Report';
    }
}
