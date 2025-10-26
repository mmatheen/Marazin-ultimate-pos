<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DueReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;
    protected $reportType;

    public function __construct($data, $reportType)
    {
        $this->data = collect($data);
        $this->reportType = $reportType;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->reportType === 'customer') {
            return [
                'Invoice No',
                'Customer Name',
                'Mobile No',
                'Sale Date',
                'Location',
                'Created By',
                'Final Total',
                'Total Paid',
                'Total Due',
                'Payment Status',
                'Due Days',
                'Due Status',
            ];
        } else {
            return [
                'Reference No',
                'Supplier Name',
                'Mobile No',
                'Purchase Date',
                'Location',
                'Created By',
                'Final Total',
                'Total Paid',
                'Total Due',
                'Payment Status',
                'Due Days',
                'Due Status',
            ];
        }
    }

    public function map($row): array
    {
        if ($this->reportType === 'customer') {
            return [
                $row->invoice_no ?? 'N/A',
                $row->customer_name ?? 'N/A',
                $row->customer_mobile ?? 'N/A',
                $row->sales_date ?? 'N/A',
                $row->location ?? 'N/A',
                $row->user ?? 'N/A',
                number_format($row->final_total ?? 0, 2),
                number_format($row->total_paid ?? 0, 2),
                number_format($row->total_due ?? 0, 2),
                ucfirst($row->payment_status ?? 'N/A'),
                $row->due_days ?? 0,
                ucfirst($row->due_status ?? 'N/A'),
            ];
        } else {
            return [
                $row->reference_no ?? 'N/A',
                $row->supplier_name ?? 'N/A',
                $row->supplier_mobile ?? 'N/A',
                $row->purchase_date ?? 'N/A',
                $row->location ?? 'N/A',
                $row->user ?? 'N/A',
                number_format($row->final_total ?? 0, 2),
                number_format($row->total_paid ?? 0, 2),
                number_format($row->total_due ?? 0, 2),
                ucfirst($row->payment_status ?? 'N/A'),
                $row->due_days ?? 0,
                ucfirst($row->due_status ?? 'N/A'),
            ];
        }
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return ucfirst($this->reportType) . ' Due Report';
    }
}
