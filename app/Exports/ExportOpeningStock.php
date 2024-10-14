<?php

namespace App\Exports;
use App\Models\OpeningStock;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportOpeningStock implements FromCollection,WithHeadings, WithMapping
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
        // Eager load the batch and course relationships
        return OpeningStock::with(['location'])->get();
        return OpeningStock::with(['product'])->get();
    }

    /**
     * Map the data for each row
     */

    public function map($openingStock): array
{
    return [
        $openingStock->id,
        $openingStock->sku,
        $openingStock->location ? $openingStock->location->name : null, // Export location name instead of location_id
        $openingStock->product ? $openingStock->product->product_name : null, // Export product name instead of product_id
        $openingStock->quantity,
        $openingStock->unit_cost,
        $openingStock->lot_no,
        $openingStock->expiry_date,
    ];
}

}
