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
                'Sale Date',
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
                'Purchase Date',
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
        $g = function ($key, $default = null) use ($row) {
            if (is_array($row)) {
                return $row[$key] ?? $default;
            }

            return $row->{$key} ?? $default;
        };

        if ($this->reportType === 'customer') {
            return [
                $g('invoice_no', 'N/A'),
                $g('customer_name', 'N/A'),
                $g('sales_date', 'N/A'),
                number_format((float) $g('final_total', 0), 2),
                number_format((float) $g('total_paid', 0), 2),
                number_format((float) $g('total_due', 0), 2),
                ucfirst((string) $g('payment_status', 'N/A')),
                $g('due_days', 0),
                ucfirst((string) $g('due_status', 'N/A')),
            ];
        }

        return [
            $g('reference_no', 'N/A'),
            $g('supplier_name', 'N/A'),
            $g('purchase_date', 'N/A'),
            number_format((float) $g('final_total', 0), 2),
            number_format((float) $g('total_paid', 0), 2),
            number_format((float) $g('total_due', 0), 2),
            ucfirst((string) $g('payment_status', 'N/A')),
            $g('due_days', 0),
            ucfirst((string) $g('due_status', 'N/A')),
        ];
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
