<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary {
            margin-bottom: 20px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Profit & Loss Report</h1>
        <p>Generated on: {{ date('d M Y, H:i:s') }}</p>
    </div>

    <div class="filters">
        <h3>Report Filters</h3>
        <p><strong>Date Range:</strong> {{ $filters['start_date'] }} to {{ $filters['end_date'] }}</p>
        @if(!empty($filters['location_ids']))
            <p><strong>Locations:</strong> Selected locations</p>
        @else
            <p><strong>Locations:</strong> All locations</p>
        @endif
        <p><strong>Report Type:</strong> {{ ucwords(str_replace('_', ' ', $filters['report_type'])) }}</p>
    </div>

    @if(isset($reportData['summary']))
    <div class="summary">
        <h3>Summary</h3>
        <div class="summary-row">
            <span><strong>Total Sales:</strong></span>
            <span>₹ {{ number_format($reportData['summary']['total_sales'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Total Cost:</strong></span>
            <span>₹ {{ number_format($reportData['summary']['total_cost'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Gross Profit:</strong></span>
            <span>₹ {{ number_format($reportData['summary']['gross_profit'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span><strong>Profit Margin:</strong></span>
            <span>{{ number_format($reportData['summary']['profit_margin'] ?? 0, 2) }}%</span>
        </div>
    </div>
    @endif

    @if(isset($reportData['data']) && count($reportData['data']) > 0)
    <div class="report-data">
        <h3>{{ ucwords(str_replace('_', ' ', $filters['report_type'])) }} Report</h3>
        
        @if($filters['report_type'] == 'product')
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Total Cost</th>
                    <th class="text-right">Gross Profit</th>
                    <th class="text-center">Profit Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] as $item)
                <tr>
                    <td>{{ $item['product_name'] ?? 'N/A' }}</td>
                    <td>{{ $item['sku'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['quantity_sold'] ?? 0 }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_sales'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_cost'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['gross_profit'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ number_format($item['profit_margin'] ?? 0, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @elseif($filters['report_type'] == 'brand')
        <table>
            <thead>
                <tr>
                    <th>Brand Name</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Total Cost</th>
                    <th class="text-right">Gross Profit</th>
                    <th class="text-center">Profit Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] as $item)
                <tr>
                    <td>{{ $item['brand_name'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['quantity_sold'] ?? 0 }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_sales'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_cost'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['gross_profit'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ number_format($item['profit_margin'] ?? 0, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @elseif($filters['report_type'] == 'location')
        <table>
            <thead>
                <tr>
                    <th>Location</th>
                    <th class="text-center">Qty Sold</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Total Cost</th>
                    <th class="text-right">Gross Profit</th>
                    <th class="text-center">Profit Margin</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] as $item)
                <tr>
                    <td>{{ $item['location_name'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $item['quantity_sold'] ?? 0 }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_sales'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['total_cost'] ?? 0, 2) }}</td>
                    <td class="text-right">₹ {{ number_format($item['gross_profit'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ number_format($item['profit_margin'] ?? 0, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        @else
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'N/A' }}</td>
                    <td class="text-right">₹ {{ number_format($item['amount'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif
</body>
</html>