<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>A4 RECEIPT</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .company-details {
            font-size: 12px;
            color: #666;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .customer-details, .invoice-info {
            width: 48%;
        }
        
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals-section {
            margin-top: 20px;
            border-top: 2px solid #333;
            padding-top: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }

        .stats-section {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .stat-item {
            flex: 1;
        }

        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($location && $location->logo_image)
            <img src="{{ asset($location->logo_image) }}" alt="{{ $location->name }} Logo" class="logo" />
        @endif
        <div class="company-name">{{ $location->name ?? 'COMPANY NAME' }}</div>
        <div class="company-details">
            @if ($location)
                @if ($location->address)
                    <div>{{ $location->address }}</div>
                @endif
                @if ($location->mobile)
                    <div>Mobile: {{ $location->mobile }}</div>
                @endif
                @if ($location->email)
                    <div>Email: {{ $location->email }}</div>
                @endif
            @endif
        </div>
    </div>

    <div class="invoice-details">
        <div class="customer-details">
            <div class="section-title">Customer Details</div>
            <div><strong>Name:</strong> {{ $customer->first_name }} {{ $customer->last_name }}</div>
            @if($customer->mobile_no)
                <div><strong>Mobile:</strong> {{ $customer->mobile_no }}</div>
            @endif
            @if($customer->email)
                <div><strong>Email:</strong> {{ $customer->email }}</div>
            @endif
            @if($customer->address)
                <div><strong>Address:</strong> {{ $customer->address }}</div>
            @endif
        </div>
        
        <div class="invoice-info">
            <div class="section-title">Invoice Details</div>
            <div><strong>Invoice No:</strong> {{ $sale->invoice_no }}</div>
            <div><strong>Date:</strong> {{ date('d-m-Y', strtotime($sale->sales_date)) }}</div>
            <div><strong>Time:</strong> {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}</div>
            @if($user)
                <div><strong>Cashier:</strong> {{ $user->user_name ?? $user->name }}</div>
            @endif
        </div>
    </div>

    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">Product</th>
                <th style="width: 15%" class="text-right">Unit Price</th>
                <th style="width: 10%" class="text-right">Qty</th>
                <th style="width: 15%" class="text-right">Discount</th>
                <th style="width: 15%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @forelse ($products->groupBy(function($item) {
                return $item->product_id . '-' . ($item->batch_id ?? '0');
            }) as $groupKey => $group)
                @php
                    $firstProduct = $group->first();
                    $totalQuantity = $group->sum('quantity');
                    $totalAmount = $group->sum(fn($p) => $p->price * $p->quantity);
                    $productDiscount = $group->sum(fn($p) => ($p->product->max_retail_price - $p->price) * $p->quantity);
                @endphp
                <tr>
                    <td>{{ $index++ }}</td>
                    <td>
                        {{ $firstProduct->product->product_name }}
                        @if ($firstProduct->price_type == 'retail')
                            <span style="color: blue;">*</span>
                        @elseif($firstProduct->price_type == 'wholesale')
                            <span style="color: green;">**</span>
                        @elseif($firstProduct->price_type == 'special')
                            <span style="color: red;">***</span>
                        @endif
                    </td>
                    <td class="text-right">Rs. {{ number_format($firstProduct->price, 2) }}</td>
                    <td class="text-right">{{ number_format($totalQuantity, 2) }}</td>
                    <td class="text-right">Rs. {{ number_format($productDiscount, 2) }}</td>
                    <td class="text-right">Rs. {{ number_format($totalAmount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No products found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @php
        $total_discount = $products->sum(function ($product) {
            return ($product->product->max_retail_price - $product->price) * $product->quantity;
        });

        $bill_discount = 0;
        if ($sale->discount_amount > 0) {
            if ($sale->discount_type == 'percentage') {
                $bill_discount = ($sale->subtotal * $sale->discount_amount) / 100;
            } else {
                $bill_discount = $sale->discount_amount;
            }
        }

        $total_all_discounts = $total_discount + $bill_discount;
    @endphp

    <div class="stats-section">
        <div class="stat-item">
            <div class="stat-number">{{ count($products) }}</div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">{{ $products->sum('quantity') }}</div>
            <div class="stat-label">Total Qty</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">Rs. {{ number_format($total_all_discounts, 2) }}</div>
            <div class="stat-label">Total Discount</div>
        </div>
    </div>

    <div class="totals-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>Rs. {{ number_format($sale->subtotal, 2) }}</span>
        </div>
        
        @if($sale->discount_amount > 0)
            <div class="total-row">
                <span>Bill Discount ({{ $sale->discount_type === 'percentage' ? $sale->discount_amount . '%' : 'Rs. ' . number_format($sale->discount_amount, 2) }}):</span>
                <span>Rs. {{ number_format($sale->discount_type === 'percentage' ? ($sale->subtotal * $sale->discount_amount / 100) : $sale->discount_amount, 2) }}</span>
            </div>
        @endif
        
        <div class="total-row grand-total">
            <span>GRAND TOTAL:</span>
            <span>Rs. {{ number_format($sale->final_total, 2) }}</span>
        </div>
        
        @if($amount_given > 0)
            <div class="total-row">
                <span>Amount Paid:</span>
                <span>Rs. {{ number_format($amount_given, 2) }}</span>
            </div>
        @endif
        
        @if($balance_amount > 0)
            <div class="total-row">
                <span>Change:</span>
                <span>Rs. {{ number_format($balance_amount, 2) }}</span>
            </div>
        @endif
        
        @if($sale->total_due > 0)
            <div class="total-row" style="color: red;">
                <span>Balance Due:</span>
                <span>Rs. {{ number_format($sale->total_due, 2) }}</span>
            </div>
        @endif
    </div>

    <div class="footer">
        <p><strong>Payment Method(s):</strong>
            @if($payments && $payments->count() > 0)
                @if($payments->count() > 1)
                    Multiple ({{ $payments->pluck('payment_method')->join(', ') }})
                @else
                    {{ ucfirst($payments->first()->payment_method) }}
                @endif
            @else
                Cash
            @endif
        </p>
        
        <p>Thank you for your business!</p>
        <p style="font-size: 10px; text-transform: uppercase;">SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959</p>
    </div>
</body>
</html>