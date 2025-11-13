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
            'Amount',
            'Payment Method',
            'Payment Type', 
            'Reference No',
            'Invoice No',
            'Customer Name',
            'Supplier Name',
            'Location',
            'Cheque Number',
            'Cheque Status',
            'Notes',
            'Created At'
        ];
    }

    public function map($payment): array
    {
        $locationName = '';
        $invoiceNo = '';
        
        if ($payment->sale) {
            $locationName = optional($payment->sale->location)->name ?? '';
            $invoiceNo = $payment->sale->invoice_no ?? '';
        } elseif ($payment->purchase) {
            $locationName = optional($payment->purchase->location)->name ?? '';
            $invoiceNo = $payment->purchase->invoice_no ?? '';
        } elseif ($payment->purchaseReturn) {
            $locationName = optional($payment->purchaseReturn->location)->name ?? '';
            $invoiceNo = $payment->purchaseReturn->invoice_no ?? '';
        }

        return [
            $payment->id,
            $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '',
            $payment->amount,
            ucfirst($payment->payment_method),
            ucfirst($payment->payment_type),
            $payment->reference_no ?? '',
            $invoiceNo,
            $payment->customer ? $payment->customer->full_name : '',
            $payment->supplier ? $payment->supplier->full_name : '',
            $locationName,
            $payment->cheque_number ?? '',
            $payment->cheque_status ? ucfirst($payment->cheque_status) : '',
            $payment->notes ?? '',
            $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : '',
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