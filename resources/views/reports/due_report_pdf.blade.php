<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($reportType) }} Due Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 30px;
            margin-bottom: 10px;
        }
        .summary-label {
            font-weight: bold;
            color: #666;
        }
        .summary-value {
            color: #000;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #0d6efd;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-paid { background-color: #28a745; color: white; }
        .badge-partial { background-color: #ffc107; color: black; }
        .badge-due { background-color: #dc3545; color: white; }
        .badge-recent { background-color: #28a745; color: white; }
        .badge-medium { background-color: #ffc107; color: black; }
        .badge-old { background-color: #fd7e14; color: white; }
        .badge-critical { background-color: #dc3545; color: white; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>{{ ucfirst($reportType) }} Due Report</h1>
    
    <div class="summary">
        <div class="summary-item">
            <span class="summary-label">Total Due Amount:</span>
            <span class="summary-value">Rs. {{ number_format($summaryData['total_due'], 2) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Bills:</span>
            <span class="summary-value">{{ number_format($summaryData['total_bills']) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total {{ ucfirst($reportType) }}s:</span>
            <span class="summary-value">{{ number_format($summaryData['total_parties']) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Average Due:</span>
            <span class="summary-value">Rs. {{ number_format($summaryData['avg_due_per_bill'], 2) }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @if($reportType === 'customer')
                    <th>Invoice No</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Sale Date</th>
                @else
                    <th>Reference No</th>
                    <th>Supplier Name</th>
                    <th>Mobile</th>
                    <th>Purchase Date</th>
                @endif
                <th>Location</th>
                <th class="text-right">Final Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Due</th>
                <th class="text-center">Status</th>
                <th class="text-center">Due Days</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    @if($reportType === 'customer')
                        <td>{{ $row->invoice_no ?? 'N/A' }}</td>
                        <td>{{ $row->customer_name ?? 'N/A' }}</td>
                        <td>{{ $row->customer_mobile ?? 'N/A' }}</td>
                        <td>{{ $row->sales_date ?? 'N/A' }}</td>
                    @else
                        <td>{{ $row->reference_no ?? 'N/A' }}</td>
                        <td>{{ $row->supplier_name ?? 'N/A' }}</td>
                        <td>{{ $row->supplier_mobile ?? 'N/A' }}</td>
                        <td>{{ $row->purchase_date ?? 'N/A' }}</td>
                    @endif
                    <td>{{ $row->location ?? 'N/A' }}</td>
                    <td class="text-right">Rs. {{ number_format($row->final_total ?? 0, 2) }}</td>
                    <td class="text-right">Rs. {{ number_format($row->total_paid ?? 0, 2) }}</td>
                    <td class="text-right">Rs. {{ number_format($row->total_due ?? 0, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $row->payment_status === 'paid' ? 'paid' : ($row->payment_status === 'partial' ? 'partial' : 'due') }}">
                            {{ strtoupper($row->payment_status ?? 'N/A') }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $row->due_status ?? 'recent' }}">
                            {{ $row->due_days ?? 0 }} days
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on {{ date('d-M-Y H:i:s') }}</p>
        <p>Due Status: <strong>Recent</strong> (0-7 days) | <strong>Medium</strong> (8-30 days) | <strong>Old</strong> (31-90 days) | <strong>Critical</strong> (90+ days)</p>
    </div>
</body>
</html>
