<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>A4 RECEIPT</title>
    <style>
        @page {
            margin: 12mm 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            color: #000;
            padding: 10px 15px;
            max-width: 100%;
            overflow-x: hidden;
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
            font-size: 26px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .company-details {
            text-align: center;
            font-size: 11px;
            color: #000;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .company-details div {
            margin-bottom: 1px;
        }

        .invoice-header-section {
            padding: 8px 0;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #ddd;
        }

        .invoice-header-table {
            width: 100%;
            table-layout: fixed;
        }

        .invoice-header-table td {
            vertical-align: top;
            font-size: 12px;
            word-wrap: break-word;
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
            table-layout: fixed;
        }

        .products-table th {
            background-color: #f0f0f0;
            padding: 8px 4px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
        }

        .products-table td {
            padding: 6px 4px;
            font-size: 12px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .product-name-row {
            border-top: 1px solid #ddd;
        }

        .product-name-row td {
            font-size: 13px;
        }

        .product-details-row td {
            padding-top: 2px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            font-size: 11px;
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
            border-top: 2px solid #000;
            padding-top: 10px;
            table-layout: fixed;
        }

        .totals-table td {
            padding: 5px 0;
            font-size: 13px;
            word-wrap: break-word;
        }

        .totals-table .label {
            text-align: right;
            font-weight: bold;
            padding-right: 15px;
            width: 70%;
        }

        .totals-table .value {
            text-align: right;
            width: 30%;
            font-weight: bold;
            white-space: nowrap;
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
            margin: 15px 0;
            padding: 12px 0;
            border-top: 2px solid #000;
            background-color: #f9f9f9;
        }

        .stat-item {
            flex: 1;
            border-right: 1px solid #ddd;
            padding: 0 8px;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        .stat-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .payment-info {
            text-align: center;
            margin: 12px 0;
            font-size: 12px;
        }

        .payment-info strong {
            text-transform: uppercase;
        }

        .footer {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .footer p {
            margin: 4px 0;
        }

        .price-legend {
            font-size: 10px;
            color: #666;
            margin: 8px 0;
            padding: 8px;
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
            <div class="company-name" style="margin-top: 8px;">{{ $location->name ?? 'COMPANY NAME' }}</div>
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
                    @if($customer->mobile_no)
                        <div style="font-size: 11px; color: #333;">{{ $customer->mobile_no }}</div>
                    @endif
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
                <th style="width: 4%;">#</th>
                <th style="width: 42%;">ITEMS</th>
                <th style="width: 16%; text-align: right;">RATE</th>
                <th style="width: 13%; text-align: right;">QTY</th>
                <th style="width: 25%; text-align: right;">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            {{-- Load IMEI data and batch data for all products --}}
            @php
                $products->load(['imeis', 'batch', 'product']);
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
                    <td colspan="4" style="padding-right: 10px;">
                        <strong>{{ $item['product']->product->product_name }}</strong>
                        @if ($item['type'] == 'imei')
                            <span style="font-size: 10px; color: #666; display: inline-block;">({{ $item['imei'] }})</span>
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
                    <td style="padding-right: 5px;">
                        @php
                            // Get MRP from batch first, then fallback to product
                            $mrp = 0;
                            if ($item['product']->batch && $item['product']->batch->max_retail_price) {
                                $mrp = $item['product']->batch->max_retail_price;
                            } elseif ($item['product']->product && $item['product']->product->max_retail_price) {
                                $mrp = $item['product']->product->max_retail_price;
                            }

                            $selling_price = $item['product']->price;
                            $per_unit_discount = $mrp - $selling_price;
                        @endphp
                        @if($mrp > 0 && $mrp > $selling_price)
                            <span class="strikethrough" style="white-space: nowrap; color: #333;">
                                Rs. {{ number_format($mrp, 2) }}
                            </span>
                            <span class="discount-amount" style="white-space: nowrap; color: #666;">
                                (Disc: Rs. {{ number_format($per_unit_discount, 2) }})
                            </span>
                        @endif
                    </td>
                    <td class="text-right" style="white-space: nowrap;">
                        <strong>Rs. {{ number_format($item['product']->price, 2) }}</strong>
                    </td>
                    <td class="text-right" style="white-space: nowrap;">
                        <strong>&times; {{ number_format($item['quantity'], 2) }} pcs</strong>
                    </td>
                    <td class="text-right" style="white-space: nowrap;">
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

        @if (!is_null($sale->shipping_charges) && $sale->shipping_charges > 0)
            <tr>
                <td class="label">SHIPPING CHARGES</td>
                <td class="value">Rs. {{ number_format($sale->shipping_charges, 2) }}</td>
            </tr>
        @endif

        <tr class="grand-total">
            <td class="label">TOTAL</td>
            <td class="value">Rs. {{ number_format($sale->final_total, 2) }}</td>
        </tr>

        {{-- Only show payment details for final sales, not for quotations, drafts, or sale orders --}}
        @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
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
        @endif
    </table>

    @php
        $total_discount = $products->sum(function ($product) {
            // Get MRP from batch first, then fallback to product
            $mrp = 0;
            if ($product->batch && $product->batch->max_retail_price) {
                $mrp = $product->batch->max_retail_price;
            } elseif ($product->product && $product->product->max_retail_price) {
                $mrp = $product->product->max_retail_price;
            }

            $price = $product->price;
            // Only count as discount if MRP exists and is greater than selling price
            if ($mrp > 0 && $mrp > $price) {
                return ($mrp - $price) * $product->quantity;
            }
            return 0;
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
            <div class="stat-number">Rs. {{ number_format(\App\Helpers\BalanceHelper::getCustomerBalance($customer->id), 2) }}</div>
            <div class="stat-label">Outstanding Balance</div>
        </div>
    </div>

    {{-- Payment Method - Only show for final sales, not for quotations, drafts, or sale orders --}}
    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
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
    @else
        <div class="payment-info" style="text-align: center; font-weight: bold; color: #555;">
            @if ($sale->status === 'quotation')
                *** QUOTATION - PRICE ESTIMATE ONLY ***
            @elseif ($sale->status === 'draft')
                *** DRAFT - PRICE ESTIMATE ONLY ***
            @elseif (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order')
                *** SALE ORDER - CONFIRMED ORDER ***
            @endif
        </div>
    @endif

    {{-- Sale Notes Section --}}
    @if ($sale->sale_notes)
        <div style="font-size: 12px; color: #000; text-align: center; margin: 15px 0; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
            <strong>NOTES:</strong><br>
            {{ $sale->sale_notes }}
        </div>
    @endif

    {{-- Signature Section --}}
    <div style="margin-top: 30px; margin-bottom: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33.33%; text-align: center; padding: 0 10px;">
                    <div style="border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-size: 11px; font-weight: bold;">
                        Checked By
                    </div>
                </td>
                <td style="width: 33.33%; text-align: center; padding: 0 10px;">
                    <div style="border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-size: 11px; font-weight: bold;">
                        Received By
                    </div>
                </td>
                <td style="width: 33.33%; text-align: center; padding: 0 10px;">
                    <div style="border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-size: 11px; font-weight: bold;">
                        Approved By
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
        @if ($location && $location->footer_note)
            <p><strong>{{ $location->footer_note }}</strong></p>
        @else
            <p><strong>Come again! Thank you for your business!</strong></p>
        @endif
        <p style="text-transform: uppercase;">SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959</p>
    </div>
</body>
</html>
