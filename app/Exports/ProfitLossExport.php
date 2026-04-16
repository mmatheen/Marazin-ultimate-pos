<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProfitLossExport implements WithMultipleSheets
{
    protected $data;
    protected $reportType;
    protected $filters;

    public function __construct($data, $reportType = 'overall', $filters = [])
    {
        $this->data = $data;
        $this->reportType = $reportType;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        $sheets = [];
        $normalized = $this->normalizeData($this->data);

        // Overall Summary Sheet
        if (!empty($normalized['overall'])) {
            $sheets[] = new ProfitLossOverallSheet($normalized['overall'], $this->filters);
        }

        // Product-wise Sheet
        if (!empty($normalized['products'])) {
            $sheets[] = new ProfitLossProductSheet($normalized['products']);
        }

        // Batch-wise Sheet
        if (!empty($normalized['batches'])) {
            $sheets[] = new ProfitLossBatchSheet($normalized['batches']);
        }

        // Brand-wise Sheet
        if (!empty($normalized['brands'])) {
            $sheets[] = new ProfitLossBrandSheet($normalized['brands']);
        }

        // Location-wise Sheet
        if (!empty($normalized['locations'])) {
            $sheets[] = new ProfitLossLocationSheet($normalized['locations']);
        }

        return $sheets;
    }

    private function normalizeData(array $data): array
    {
        return [
            'overall' => $data['overall_summary'] ?? $data['overall'] ?? [],
            'products' => $data['product_wise'] ?? $data['products'] ?? [],
            'batches' => $data['batch_wise'] ?? $data['batches'] ?? [],
            'brands' => $data['brand_wise'] ?? $data['brands'] ?? [],
            'locations' => $data['location_wise'] ?? $data['locations'] ?? [],
        ];
    }
}

class ProfitLossOverallSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $filters;

    public function __construct($data, $filters = [])
    {
        $this->data = $data;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return 'Overall Summary';
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Amount (Rs.)',
            'Percentage'
        ];
    }

    public function array(): array
    {
        $totalSales = (float) ($this->data['total_sales'] ?? 0);
        $totalCost = (float) ($this->data['total_cost'] ?? 0);
        $grossProfit = (float) ($this->data['gross_profit'] ?? 0);
        $profitMargin = (float) ($this->data['profit_margin'] ?? 0);
        $totalExpenses = (float) ($this->data['total_expenses'] ?? 0);
        $netProfit = (float) ($this->data['net_profit'] ?? 0);
        $netProfitMargin = (float) ($this->data['net_profit_margin'] ?? 0);
        $totalTransactions = (int) ($this->data['total_transactions'] ?? 0);
        $averageOrderValue = (float) ($this->data['average_order_value'] ?? 0);
        $averageQuantityPerOrder = (float) ($this->data['average_quantity_per_order'] ?? 0);
        $averageProfitPerOrder = (float) ($this->data['average_profit_per_order'] ?? 0);

        return [
            ['Total Sales', number_format($totalSales, 2), '100.00%'],
            ['Total Cost', number_format($totalCost, 2), $totalSales > 0 ? number_format(($totalCost / $totalSales) * 100, 2) . '%' : '0.00%'],
            ['Gross Profit', number_format($grossProfit, 2), number_format($profitMargin, 2) . '%'],
            ['Total Expenses', number_format($totalExpenses, 2), $totalSales > 0 ? number_format(($totalExpenses / $totalSales) * 100, 2) . '%' : '0.00%'],
            ['Net Profit', number_format($netProfit, 2), number_format($netProfitMargin, 2) . '%'],
            [],
            ['Transactions', $totalTransactions, ''],
            ['Average Order Value', number_format($averageOrderValue, 2), ''],
            ['Average Quantity Per Order', number_format($averageQuantityPerOrder, 2), ''],
            ['Average Profit Per Order', number_format($averageProfitPerOrder, 2), '']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '366092']]],
            3 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_DARKGREEN]]],
            5 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_DARKBLUE]]],
        ];
    }
}

class ProfitLossProductSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Product-wise Report';
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Brand',
            'Category',
            'Paid Qty',
            'Free Qty',
            'Total Quantity',
            'Total Sales (Rs.)',
            'Total Cost (Rs.)',
            'Profit/Loss (Rs.)',
            'Profit Margin (%)',
            'Avg Selling Price (Rs.)',
            'Avg Cost Price (Rs.)'
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $product) {
            $rows[] = [
                $product['product_name'],
                $product['sku'] ?? '',
                $product['brand_name'] ?? '',
                $product['category_name'] ?? '',
                $product['paid_quantity'] ?? 0,
                $product['free_quantity'] ?? 0,
                $product['total_quantity'],
                number_format($product['total_sales'], 2),
                number_format($product['total_cost'], 2),
                number_format($product['gross_profit'] ?? $product['profit_loss'] ?? 0, 2),
                number_format($product['profit_margin'], 2),
                number_format($product['avg_selling_price'] ?? 0, 2),
                number_format($product['avg_cost_price'] ?? $product['cost_per_unit'] ?? 0, 2)
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '366092']]],
        ];
    }
}

class ProfitLossBatchSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Batch-wise Report';
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Batch Number',
            'Purchase Price (Rs.)',
            'Selling Price (Rs.)',
            'Paid Qty',
            'Free Qty',
            'Total Sold',
            'Total Sales (Rs.)',
            'Total Cost (Rs.)',
            'Profit/Loss (Rs.)',
            'Profit Margin (%)',
            'Expiry Date'
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $batch) {
            $rows[] = [
                $batch['product_name'],
                $batch['batch_number'],
                number_format($batch['purchase_price'], 2),
                number_format($batch['avg_selling_price'] ?? $batch['selling_price'], 2),
                $batch['paid_quantity'] ?? 0,
                $batch['free_quantity'] ?? 0,
                $batch['total_quantity'] ?? $batch['quantity_sold'],
                number_format($batch['total_sales'], 2),
                number_format($batch['total_cost'], 2),
                number_format($batch['gross_profit'] ?? $batch['profit_loss'] ?? 0, 2),
                number_format($batch['profit_margin'], 2),
                $batch['expiry_date'] ?? 'N/A'
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '366092']]],
        ];
    }
}

class ProfitLossBrandSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Brand-wise Report';
    }

    public function headings(): array
    {
        return [
            'Brand Name',
            'Products Count',
            'Total Quantity',
            'Total Sales (Rs.)',
            'Total Cost (Rs.)',
            'Profit/Loss (Rs.)',
            'Profit Margin (%)',
            'Avg Selling Price (Rs.)',
            'Sales per Product (Rs.)'
        ];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->data as $brand) {
            $rows[] = [
                $brand['brand_name'],
                $brand['product_count'],
                $brand['total_quantity'],
                number_format($brand['total_sales'], 2),
                number_format($brand['total_cost'], 2),
                number_format($brand['gross_profit'] ?? $brand['profit_loss'] ?? 0, 2),
                number_format($brand['profit_margin'], 2),
                number_format($brand['avg_selling_price'], 2),
                number_format($brand['sales_per_product'], 2)
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '366092']]],
        ];
    }
}

class ProfitLossLocationSheet implements FromArray, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Location-wise Report';
    }

    public function headings(): array
    {
        return [
            'Location Name',
            'Products Count',
            'Total Quantity',
            'Total Sales (Rs.)',
            'Total Cost (Rs.)',
            'Profit/Loss (Rs.)',
            'Profit Margin (%)',
            'Revenue Share (%)',
            'Avg Transaction (Rs.)'
        ];
    }

    public function array(): array
    {
        $rows = [];
        $totalSales = collect($this->data)->sum('total_sales');

        foreach ($this->data as $location) {
            $revenueShare = $totalSales > 0 ? ($location['total_sales'] / $totalSales) * 100 : 0;
            $rows[] = [
                $location['location_name'],
                $location['product_count'],
                $location['total_quantity'],
                number_format($location['total_sales'], 2),
                number_format($location['total_cost'], 2),
                number_format($location['gross_profit'] ?? $location['profit_loss'] ?? 0, 2),
                number_format($location['profit_margin'], 2),
                number_format($revenueShare, 2),
                number_format($location['avg_transaction'], 2)
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['argb' => Color::COLOR_WHITE]], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '366092']]],
        ];
    }
}
