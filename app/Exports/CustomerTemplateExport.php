<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Customer;

class CustomerTemplateExport implements FromCollection, WithHeadings, WithMapping
{
    protected $blankTemplate;

    public function __construct($blankTemplate = false)
    {
        $this->blankTemplate = $blankTemplate;
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
            'Address',
            'Opening Balance',
            'Credit Limit',
            'City Name',
            'Customer Type',
        ];
    }

    public function collection()
    {
        if ($this->blankTemplate) {
            return collect([]);
        }

        return Customer::with('city:id,name')
            ->select([
                'id', 'prefix', 'first_name', 'last_name', 'mobile_no',
                'email', 'address', 'opening_balance', 'credit_limit',
                'city_id', 'customer_type'
            ])
            ->get();
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->prefix ?? '',
            $customer->first_name,
            $customer->last_name ?? '',
            $customer->mobile_no,
            $customer->email ?? '',
            $customer->address ?? '',
            $customer->opening_balance ?? 0,
            $customer->credit_limit ?? 0,
            $customer->city->name ?? '',
            $customer->customer_type ?? 'Regular',
        ];
    }
}
