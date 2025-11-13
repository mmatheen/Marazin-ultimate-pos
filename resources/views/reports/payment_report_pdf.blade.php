<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Report</title>
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
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-cleared {
            background-color: #d1edff;
            color: #0c5460;
        }
        .status-bounced {
            background-color: #f8d7da;
            color: #721c24;
        }
        .filters-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
        }
        .filters-info h4 {
            margin: 0 0 10px 0;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <h1>Payment Report</h1>
    
    @if(isset($request) && ($request->start_date || $request->end_date || $request->customer_id || $request->supplier_id || $request->location_id || $request->payment_method || $request->payment_type))
    <div class="filters-info">
        <h4>Applied Filters:</h4>
        @if($request->start_date && $request->end_date)
            <p><strong>Date Range:</strong> {{ $request->start_date }} to {{ $request->end_date }}</p>
        @endif
        @if($request->customer_id)
            <p><strong>Customer ID:</strong> {{ $request->customer_id }}</p>
        @endif
        @if($request->supplier_id)
            <p><strong>Supplier ID:</strong> {{ $request->supplier_id }}</p>
        @endif
        @if($request->location_id)
            <p><strong>Location ID:</strong> {{ $request->location_id }}</p>
        @endif
        @if($request->payment_method)
            <p><strong>Payment Method:</strong> {{ ucfirst($request->payment_method) }}</p>
        @endif
        @if($request->payment_type)
            <p><strong>Payment Type:</strong> {{ ucfirst($request->payment_type) }}</p>
        @endif
    </div>
    @endif
    
    @if(isset($summaryData))
    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Total Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['total_amount'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Cash Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['cash_total'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Card Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['card_total'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Cheque Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['cheque_total'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Sale Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['sale_payments'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Purchase Payments:</div>
            <div class="summary-value">${{ number_format($summaryData['purchase_payments'], 2) }}</div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Supplier</th>
                <th>Location</th>
                <th>Cheque No</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $payment)
                <tr>
                    <td class="text-center">{{ $payment->id }}</td>
                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : '' }}</td>
                    <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                    <td>{{ ucfirst($payment->payment_type) }}</td>
                    <td>{{ $payment->reference_no ?? '' }}</td>
                    <td>
                        @if($payment->sale)
                            {{ $payment->sale->invoice_no ?? '' }}
                        @elseif($payment->purchase)
                            {{ $payment->purchase->invoice_no ?? '' }}
                        @elseif($payment->purchaseReturn)
                            {{ $payment->purchaseReturn->invoice_no ?? '' }}
                        @endif
                    </td>
                    <td>{{ $payment->customer ? $payment->customer->full_name : '' }}</td>
                    <td>{{ $payment->supplier ? $payment->supplier->full_name : '' }}</td>
                    <td>
                        @if($payment->sale && $payment->sale->location)
                            {{ $payment->sale->location->name }}
                        @elseif($payment->purchase && $payment->purchase->location)
                            {{ $payment->purchase->location->name }}
                        @elseif($payment->purchaseReturn && $payment->purchaseReturn->location)
                            {{ $payment->purchaseReturn->location->name }}
                        @endif
                    </td>
                    <td>{{ $payment->cheque_number ?? '' }}</td>
                    <td>
                        @if($payment->payment_method == 'cheque' && $payment->cheque_status)
                            <span class="status-badge status-{{ strtolower($payment->cheque_status) }}">
                                {{ ucfirst($payment->cheque_status) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
        <p>Total Records: {{ $data->count() }}</p>
    </div>
</body>
</html>