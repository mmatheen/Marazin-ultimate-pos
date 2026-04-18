<?php

namespace App\Services\Product;

use App\Exports\ExportProductTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ProductExportService
{
    public function downloadBlankImportTemplate(): Response
    {
        return Excel::download(new ExportProductTemplate(true), 'Import_Product_Template.xlsx');
    }

    public function downloadProductsExport(): Response
    {
        return Excel::download(new ExportProductTemplate(), 'Products_Export_' . date('Y-m-d') . '.xlsx');
    }
}
