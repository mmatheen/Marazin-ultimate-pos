<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportProductTemplate implements  FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        return [
            'ID',
            'Product Name',
            'SKU',
            'Unit Name',
            'Brand Name',
            'Main Category Name',
            'Sub Category Name',
            'Stock Alert',
            'Stock Alert Quantity',
            'Product Image Name',
            'Description',
            'Is IMEI/Serial No',
            'Is For Selling',
            'Product Type',
            'Pax',
            'Original Price',
            'Retail Price',
            'Whole Sale Price',
            'Special Price',
            'Max Retail Price',
            'Expiry Date',
            'Qty',
            'Batch No',
        ];
    }

    public function collection()
    {
        // Return an empty collection
        return collect([]);
    }
}
