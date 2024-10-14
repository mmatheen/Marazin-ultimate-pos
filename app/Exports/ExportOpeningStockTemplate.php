<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportOpeningStockTemplate implements  FromCollection,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        return [
            'ID',
            'SKU',
            'Location',
            'Product',
            'Quantity',
            'Unit Cost',
            'Lot Number',
            'Expiry Date',
        ];
    }

    public function collection()
    {
        // Return an empty collection
        return collect([]);
    }
}
