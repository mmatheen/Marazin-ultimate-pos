<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Product;

class ExportProductTemplate implements FromCollection, WithHeadings, WithMapping
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
        if ($this->blankTemplate) {
            return collect([]);
        }

        return Product::with([
            'unit:id,name',
            'brand:id,name',
            'mainCategory:id,mainCategoryName',
            'subCategory:id,subCategoryname',
            'batches' => function($query) {
                $query->with(['locationBatches'])->latest();
            }
        ])->get();
    }

    public function map($product): array
    {
        $batch = $product->batches->first();

        return [
            $product->id,
            $product->product_name,
            $product->sku,
            $product->unit->name ?? 'N/A',
            $product->brand->name ?? 'N/A',
            $product->mainCategory->mainCategoryName ?? 'N/A', // Updated to mainCategoryName
            $product->subCategory->subCategoryname ?? 'N/A',  // Updated to subCategoryname
            $product->stock_alert ? '1' : '0',
            $product->alert_quantity,
            $product->product_image,
            $product->description,
            $product->is_imei_or_serial_no ? '1' : '0',
            $product->is_for_selling ? '1' : '0',
            $product->product_type,
            $product->pax,
            $product->original_price,
            $product->retail_price,
            $product->whole_sale_price,
            $product->special_price,
            $product->max_retail_price,
            $batch->expiry_date ?? '',
            $batch ? $batch->locationBatches->sum('qty') : 0,
            $batch->batch_no ?? '',
        ];
    }
}