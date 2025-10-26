<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>A4 RECEIPT</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #000;
            padding: 20px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .logo-container img {
            max-width: 120px;
            height: auto;
        }
        
        .company-name {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .company-details {
            text-align: center;
            font-size: 12px;
            color: #000;
            margin-bottom: 15px;
        }
        
        .company-details div {
            margin-bottom: 2px;
        }
        
        hr.dashed {
            border: 0;
            border-top: 2px dashed #000;
            margin: 10px 0;
        }
        
        .invoice-header-section {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 10px 0;
            margin-bottom: 15px;
        }
        
        .invoice-header-table {
            width: 100%;
        }
        
        .invoice-header-table td {
            vertical-align: top;
            font-size: 13px;
        }
        
        .customer-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .date-time {
            font-size: 11px;
            color: #333;
        }
        
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }
        
        .cashier-name {
            font-size: 11px;
            text-align: center;
            color: #333;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .products-table th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
        }
        
        .products-table td {
            padding: 6px 8px;
            font-size: 13px;
            vertical-align: top;
        }
        
        .product-name-row {
            border-top: 1px solid #ddd;
        }
        
        .product-details-row td {
            padding-top: 2px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .strikethrough {
            text-decoration: line-through;
            color: #999;
        }
        
        .discount-amount {
            color: #666;
            font-size: 12px;
        }
        
        .totals-table {
            width: 100%;
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        
        .totals-table td {
            padding: 5px 0;
            font-size: 14px;
        }
        
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            padding-right: 20px;
        }
        
        .totals-table .value {
            text-align: right;
            width: 150px;
            font-weight: bold;
        }
        
        .grand-total {
            font-size: 16px !important;
            border-top: 2px solid #000;
            padding-top: 10px !important;
        }
        
        .stats-section {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
        }
        
        .stat-item {
            flex: 1;
            border-right: 2px dashed #000;
            padding: 0 10px;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-top: 3px;
        }
        
        .payment-info {
            text-align: center;
            margin: 15px 0;
            font-size: 13px;
        }
        
        .payment-info strong {
            text-transform: uppercase;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #000;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        .price-legend {
            font-size: 11px;
            color: #666;
            margin: 10px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 3px solid #333;
            text-align: left;
        }
        
        @media print {
            body { 
                margin: 0;
                padding: 15px;
            }
            .no-print { 
                display: none; 
            }
        }
    </style>
</head>
<body>
    {{-- Logo Section --}}
    <div class="logo-container">
        @if ($location && $location->logo_image)
            <img src="{{ asset($location->logo_image) }}" alt="{{ $location->name }} Logo" class="logo" />
        @else
            <div class="company-name">{{ $location->name ?? 'COMPANY NAME' }}</div>
        @endif
    </div>
    
    {{-- Company Details --}}
    <div class="company-details">
        @if ($location)
            @if ($location->address)
                <div>{{ $location->address }}</div>
            @endif
            @if ($location->mobile)
                <div>{{ $location->mobile }}</div>
            @endif
            @if ($location->email)
                <div style="text-transform: lowercase;">{{ $location->email }}</div>
            @endif
        @endif
    </div>
    
    {{-- Invoice Header Section --}}
    <div class="invoice-header-section">
        <table class="invoice-header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="customer-name">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                    <div class="date-time">
                        [{{ date('d-m-Y', strtotime($sale->sales_date)) }}] 
                        {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td style="width: 40%; text-align: center;">
                    <div class="invoice-number">{{ $sale->invoice_no }}</div>
                    <div class="cashier-name">({{ $user->user_name ?? $user->name ?? 'System User' }})</div>
                </td>
            </tr>
        </table>
    </div>
    
    {{-- Products Table --}}
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">ITEMS</th>
                <th style="width: 15%; text-align: right;">RATE</th>
                <th style="width: 15%; text-align: right;">QTY</th>
                <th style="width: 25%; text-align: right;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            {{-- Load IMEI data for all products --}}
            @php
                $products->load('imeis');
            @endphp
            
            {{-- Process products: separate IMEI products, group non-IMEI products --}}
            @php
                $displayItems = [];
                $nonImeiGroups = [];
                
                foreach ($products as $product) {
                    // Check if product has IMEIs
                    if ($product->imeis && $product->imeis->count() > 0) {
                        // For IMEI products, create separate rows for each IMEI
                        foreach ($product->imeis as $imei) {
                            $displayItems[] = [
                                'type' => 'imei',
                                'product' => $product,
                                'imei' => $imei->imei_number,
                                'quantity' => 1,
                                'amount' => $product->price * 1,
                            ];
                        }
                    } else {
                        // Group non-IMEI products by product_id and batch_id
                        $groupKey = $product->product_id . '-' . ($product->batch_id ?? '0');
                        if (!isset($nonImeiGroups[$groupKey])) {
                            $nonImeiGroups[$groupKey] = [
                                'type' => 'grouped',
                                'product' => $product,
                                'quantity' => 0,
                                'amount' => 0,
                            ];
                        }
                        $nonImeiGroups[$groupKey]['quantity'] += $product->quantity;
                        $nonImeiGroups[$groupKey]['amount'] += $product->price * $product->quantity;
                    }
                }
                
                // Merge grouped items with IMEI items
                $displayItems = array_merge($displayItems, array_values($nonImeiGroups));
            @endphp

            @foreach ($displayItems as $index => $item)
                {{-- Product Name Row --}}
                <tr class="product-name-row">
                    <td>{{ $loop->iteration }}</td>
                    <td colspan="4">
                        <strong>{{ $item['product']->product->product_name }}</strong>
                        @if ($item['type'] == 'imei')
                            <span style="font-size: 11px; color: #666;">({{ $item['imei'] }})</span>
                        @endif
                        @if ($item['product']->price_type == 'retail')
                            <span style="color: blue; font-weight: bold;">*</span>
                        @elseif($item['product']->price_type == 'wholesale')
                            <span style="color: green; font-weight: bold;">**</span>
                        @elseif($item['product']->price_type == 'special')
                            <span style="color: red; font-weight: bold;">***</span>
                        @endif
                    </td>
                </tr>
                
                {{-- Product Details Row --}}
                <tr class="product-details-row">
                    <td></td>
                    <td>
                        <span class="strikethrough">
                            Rs. {{ number_format($item['product']->product->max_retail_price, 2) }}
                        </span>
                        <span class="discount-amount">
                            (Disc: Rs. {{ number_format($item['product']->product->max_retail_price - $item['product']->price, 2) }})
                        </span>
                    </td>
                    <td class="text-right">
                        <strong>Rs. {{ number_format($item['product']->price, 2) }}</strong>
                    </td>
                    <td class="text-right">
                        <strong>&times; {{ number_format($item['quantity'], 2) }} pcs</strong>
                    </td>
                    <td class="text-right">
                        <strong>Rs. {{ number_format($item['amount'], 2) }}</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    {{-- Totals Section --}}
    <table class="totals-table">
        <tr>
            <td class="label">SUBTOTAL</td>
            <td class="value">Rs. {{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        
        @if ($sale->discount_amount > 0)
            <tr>
                <td class="label">
                    DISCOUNT
                    @if ($sale->discount_type == 'percentage')
                        ({{ $sale->discount_amount }}%)
                    @else
                        (RS)
                    @endif
                </td>
                <td class="value">
                    @if ($sale->discount_type == 'percentage')
                        -Rs. {{ number_format(($sale->subtotal * $sale->discount_amount) / 100, 2) }}
                    @else
                        -Rs. {{ number_format($sale->discount_amount, 2) }}
                    @endif
                </td>
            </tr>
        @endif
        
        <tr class="grand-total">
            <td class="label">TOTAL</td>
            <td class="value">Rs. {{ number_format($sale->final_total, 2) }}</td>
        </tr>
        
        @if (!is_null($amount_given) && $amount_given > 0)
            <tr>
                <td class="label">AMOUNT GIVEN</td>
                <td class="value">Rs. {{ number_format($amount_given, 2) }}</td>
            </tr>
        @endif
        
        <tr>
            <td class="label">PAID</td>
            <td class="value">Rs. {{ number_format($sale->total_paid, 2) }}</td>
        </tr>
        
        @if (!is_null($balance_amount) && $balance_amount > 0)
            <tr>
                <td class="label">BALANCE GIVEN</td>
                <td class="value">Rs. {{ number_format($balance_amount, 2) }}</td>
            </tr>
        @endif
        
        @if (!is_null($sale->total_due) && $sale->total_due > 0)
            <tr>
                <td class="label">BALANCE DUE</td>
                <td class="value" style="color: red;">
                    (Rs. {{ number_format($sale->total_due, 2) }})
                </td>
            </tr>
        @endif
    </table>
    
    @php
        $total_discount = $products->sum(function ($product) {
            return ($product->product->max_retail_price - $product->price) * $product->quantity;
        });

        // Calculate bill discount if exists
        $bill_discount = 0;
        if ($sale->discount_amount > 0) {
            if ($sale->discount_type == 'percentage') {
                $bill_discount = ($sale->subtotal * $sale->discount_amount) / 100;
            } else {
                $bill_discount = $sale->discount_amount;
            }
        }

        // Total all discounts (product discounts + bill discount)
        $total_all_discounts = $total_discount + $bill_discount;
    @endphp
    
    {{-- Stats Section --}}
    <div class="stats-section">
        <div class="stat-item">
            <div class="stat-number">{{ count($displayItems) }}</div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ array_sum(array_column($displayItems, 'quantity')) }}</div>
            <div class="stat-label">Total Qty</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">Rs. {{ number_format($total_all_discounts, 2) }}</div>
            <div class="stat-label">Total Discount</div>
        </div>
    </div>
    
    {{-- Payment Method --}}
    <div class="payment-info">
        <strong>Payment Method:</strong>
        @if ($payments && $payments->count() > 0)
            @if ($payments->count() > 1)
                Multiple 
                <br><span style="font-size: 11px;">({{ $payments->pluck('payment_method')->join(', ') }})</span>
            @else
                {{ ucfirst($payments->first()->payment_method) }}
            @endif
        @else
            Cash
        @endif
    </div>
    
    {{-- Footer --}}
    <div class="footer">
        <p><strong>Come again! Thank you for your business!</strong></p>
        <p style="text-transform: uppercase;">SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959</p>
    </div>
</body>
</html>