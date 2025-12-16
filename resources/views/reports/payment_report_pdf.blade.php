<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 10px 15px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .summary {
            margin-bottom: 10px;
            padding: 6px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 4px;
        }
        .summary-label {
            font-weight: bold;
            color: #666;
            font-size: 8px;
        }
        .summary-value {
            color: #000;
            font-size: 9px;
            font-weight: bold;
        }

        /* Collection Group Styles */
        .collection-group {
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            page-break-inside: avoid;
        }
        .collection-header {
            background-color: #4a5568;
            color: white;
            padding: 5px 8px;
            font-weight: bold;
            border-bottom: 2px solid #2d3748;
        }
        .collection-header h3 {
            margin: 0;
            font-size: 10px;
            display: inline-block;
        }
        .collection-header .amount-badge {
            float: right;
            background-color: #22c55e;
            padding: 3px 8px;
            border-radius: 2px;
            font-size: 9px;
        }
        .collection-header .payment-count {
            float: right;
            margin-right: 8px;
            background-color: white;
            color: #333;
            padding: 3px 6px;
            border-radius: 2px;
            font-size: 8px;
        }
        .collection-header .notes {
            font-size: 8px;
            font-weight: normal;
            color: #e0e0e0;
            font-style: italic;
            margin-top: 2px;
        }
        .collection-info {
            background-color: #f8f9fa;
            padding: 5px 8px;
            border-bottom: 1px solid #e9ecef;
            font-size: 8px;
        }
        .collection-info-row {
            margin-bottom: 2px;
        }
        .collection-info-row strong {
            color: #495057;
            display: inline-block;
            width: 80px;
        }
        .collection-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .collection-table th {
            background-color: #f1f3f5;
            color: #495057;
            padding: 4px 6px;
            text-align: left;
            font-size: 8px;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        .collection-table td {
            padding: 3px 6px;
            border-bottom: 1px solid #e9ecef;
            font-size: 8px;
        }
        .collection-table .text-right {
            text-align: right;
        }
        .collection-table .text-center {
            text-align: center;
        }
        .collection-table tbody tr:nth-child(odd) {
            background-color: #f9fafb;
        }
        .collection-footer {
            background-color: #f8f9fa;
            padding: 5px 8px;
            border-top: 2px solid #dee2e6;
            text-align: right;
            font-weight: bold;
        }
        .collection-footer .total-label {
            font-size: 9px;
            color: #2d3748;
        }
        .collection-footer .total-amount {
            font-size: 10px;
            color: #16a34a;
            margin-left: 8px;
        }

        /* Payment Method Badges */
        .badge {
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-cash {
            background-color: #22c55e;
            color: white;
        }
        .badge-card {
            background-color: #6f42c1;
            color: white;
        }
        .badge-cheque {
            background-color: #fd7e14;
            color: white;
        }
        .badge-bank {
            background-color: #17a2b8;
            color: white;
        }
        .badge-other {
            background-color: #6c757d;
            color: white;
        }

        /* Single Payment Group */
        .single-payment-group {
            margin-bottom: 10px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            page-break-inside: avoid;
        }

        .filters-info {
            margin-bottom: 8px;
            padding: 5px;
            background-color: #e7f3ff;
            border-left: 2px solid #0d6efd;
            font-size: 8px;
        }
        .filters-info h4 {
            margin: 0 0 3px 0;
            color: #0d6efd;
            font-size: 9px;
        }

        .report-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
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
    <h1>Payment Report - Grouped by Collection</h1>

    @if(isset($request) && ($request->start_date || $request->end_date || $request->customer_id || $request->supplier_id || $request->location_id || $request->payment_method || $request->payment_type))
    <div class="filters-info">
        <h4>Applied Filters:</h4>
        @if($request->start_date && $request->end_date)
            <strong>Date Range:</strong> {{ $request->start_date }} to {{ $request->end_date }} &nbsp; | &nbsp;
        @endif
        @if($request->customer_id)
            <strong>Customer ID:</strong> {{ $request->customer_id }} &nbsp; | &nbsp;
        @endif
        @if($request->supplier_id)
            <strong>Supplier ID:</strong> {{ $request->supplier_id }} &nbsp; | &nbsp;
        @endif
        @if($request->payment_method)
            <strong>Method:</strong> {{ ucfirst($request->payment_method) }} &nbsp; | &nbsp;
        @endif
        @if($request->payment_type)
            <strong>Type:</strong> {{ ucfirst($request->payment_type) }}
        @endif
    </div>
    @endif

    @if(isset($summaryData))
    <div class="summary">
        <div class="summary-item">
            <div class="summary-label">Total Payments:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['total_amount'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Cash:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['cash_total'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Card:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['card_total'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Cheque:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['cheque_total'], 2) }}</div>
        </div>
        @if(isset($summaryData['bank_transfer_total']) && $summaryData['bank_transfer_total'] > 0)
        <div class="summary-item">
            <div class="summary-label">Bank Transfer:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['bank_transfer_total'], 2) }}</div>
        </div>
        @endif
        <div class="summary-item">
            <div class="summary-label">Sale Payments:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['sale_payments'], 2) }}</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Purchase Payments:</div>
            <div class="summary-value">Rs {{ number_format($summaryData['purchase_payments'], 2) }}</div>
        </div>
    </div>
    @endif

    @if(isset($collections) && count($collections) > 0)
        @foreach($collections as $collection)
            @if($collection['is_bulk'])
                {{-- Bulk Collection Group --}}
                <div class="collection-group">
                    <div class="collection-header">
                        <h3>Collection Receipt: {{ $collection['reference_no'] }}</h3>
                        <span class="amount-badge">Rs {{ number_format($collection['total_amount'], 2) }}</span>
                        <span class="payment-count">{{ count($collection['payments']) }} Payment{{ count($collection['payments']) > 1 ? 's' : '' }}</span>
                        <div style="clear: both;"></div>
                        <div style="font-size: 8px; margin-top: 2px; font-weight: normal;">
                            {{ $collection['payment_date'] }} | {{ $collection['customer_name'] ?: $collection['supplier_name'] ?: 'N/A' }}
                        </div>
                        @if(!empty($collection['payments'][0]['notes']))
                        <div class="notes">
                            <strong>Notes:</strong> {{ $collection['payments'][0]['notes'] }}
                        </div>
                        @endif
                    </div>

                    <div class="collection-info">
                        <div class="collection-info-row">
                            <strong>Customer:</strong> {{ $collection['customer_name'] ?: 'N/A' }}
                        </div>
                        <div class="collection-info-row">
                            <strong>Address:</strong> {{ $collection['customer_address'] ?: 'N/A' }}
                        </div>
                        <div class="collection-info-row">
                            <strong>Collection Date:</strong> {{ $collection['payment_date'] }}
                        </div>
                        <div class="collection-info-row">
                            <strong>Location:</strong> {{ $collection['location'] ?: 'N/A' }}
                        </div>
                    </div>

                    <table class="collection-table">
                        <thead>
                            <tr>
                                <th>Invoice Date</th>
                                <th>Invoice No.</th>
                                <th class="text-right">Invoice Value</th>
                                <th>Payment Method</th>
                                <th>Cheque No.</th>
                                <th>Bank & Branch</th>
                                <th>Due Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collection['payments'] as $payment)
                                <tr>
                                    <td>{{ $payment['invoice_date'] ?: $payment['payment_date'] }}</td>
                                    <td>{{ $payment['invoice_no'] ?: '-' }}</td>
                                    <td class="text-right">Rs {{ number_format($payment['invoice_value'], 2) }}</td>
                                    <td>
                                        @php
                                            $method = strtolower($payment['payment_method']);
                                            $badgeClass = 'badge-other';
                                            if ($method === 'cash') $badgeClass = 'badge-cash';
                                            elseif ($method === 'card') $badgeClass = 'badge-card';
                                            elseif ($method === 'cheque') $badgeClass = 'badge-cheque';
                                            elseif ($method === 'bank_transfer') $badgeClass = 'badge-bank';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($payment['payment_method']) }}</span>
                                    </td>
                                    <td>{{ $payment['cheque_number'] ?: '-' }}</td>
                                    <td>{{ $payment['cheque_bank_branch'] ?: '-' }}</td>
                                    <td>{{ $payment['cheque_valid_date'] ?: '-' }}</td>
                                    <td class="text-right" style="color: #16a34a; font-weight: bold;">Rs {{ number_format($payment['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="collection-footer">
                        <span class="total-label">Total Collection Amount:</span>
                        <span class="total-amount">Rs {{ number_format($collection['total_amount'], 2) }}</span>
                    </div>
                </div>
            @else
                {{-- Single Payment (Not Bulk Collection) --}}
                <div class="single-payment-group">
                    <table class="collection-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Date</th>
                                <th>Customer/Supplier</th>
                                <th>Invoice No.</th>
                                <th>Payment Method</th>
                                <th>Payment Type</th>
                                <th>Reference No</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($collection['payments'] as $payment)
                                <tr>
                                    <td>{{ $payment['id'] }}</td>
                                    <td>{{ $payment['payment_date'] }}</td>
                                    <td>{{ $collection['customer_name'] ?: $collection['supplier_name'] ?: '-' }}</td>
                                    <td>{{ $payment['invoice_no'] ?: '-' }}</td>
                                    <td>
                                        @php
                                            $method = strtolower($payment['payment_method']);
                                            $badgeClass = 'badge-other';
                                            if ($method === 'cash') $badgeClass = 'badge-cash';
                                            elseif ($method === 'card') $badgeClass = 'badge-card';
                                            elseif ($method === 'cheque') $badgeClass = 'badge-cheque';
                                            elseif ($method === 'bank_transfer') $badgeClass = 'badge-bank';
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($payment['payment_method']) }}</span>
                                    </td>
                                    <td>{{ ucfirst($payment['payment_type']) }}</td>
                                    <td>{{ $collection['reference_no'] }}</td>
                                    <td class="text-right" style="color: #16a34a; font-weight: bold;">Rs {{ number_format($payment['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    @else
        <div style="text-align: center; padding: 30px; color: #666;">
            <p>No payment collections found for the selected filters.</p>
        </div>
    @endif

    <div class="report-footer">
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
        @if(isset($collections))
            <p>Total Collections: {{ count($collections) }}</p>
        @endif
    </div>
</body>
</html>
