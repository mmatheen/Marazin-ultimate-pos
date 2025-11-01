<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected ?User $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function query()
    {
        // Start with bypassing location scope, but apply sales rep filtering if needed
        $query = Customer::withoutLocationScope()->with(['city']);
        
        // Apply sales rep route filtering if user is a sales rep
        if ($this->user && $this->user->isSalesRep()) {
            $salesRepRoutes = $this->user->salesRepRoutes->pluck('city_id');
            if ($salesRepRoutes->isNotEmpty()) {
                $query->whereIn('city_id', $salesRepRoutes);
            }
        }
        
        return $query->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Prefix',
            'First Name',
            'Last Name',
            'Mobile No',
            'Email',
            'City',
            'Customer Type',
            'Address',
            'Opening Balance',
            'Credit Limit',
            'Total Sale Due',
            'Total Return Due',
            'Current Due',
            'Created Date',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->prefix ?? '',
            $customer->first_name ?? '',
            $customer->last_name ?? '',
            $customer->mobile_no ?? '',
            $customer->email ?? '',
            $customer->city->name ?? '',
            $customer->customer_type ? ucfirst($customer->customer_type) : 'Not Set',
            $customer->address ?? '',
            number_format($customer->opening_balance ?? 0, 2),
            number_format($customer->credit_limit ?? 0, 2),
            number_format($customer->total_sale_due ?? 0, 2),
            number_format($customer->total_return_due ?? 0, 2),
            number_format($customer->current_due ?? 0, 2),
            $customer->created_at ? $customer->created_at->format('d-m-Y H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold header
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E2E2']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Customer List';
    }
}