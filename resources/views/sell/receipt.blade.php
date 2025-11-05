<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>RECEIPT</title>
    <style>
        @page {
            margin: 10px;
        }

        @media print {
            #printArea {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: 100% !important;
                font-size: 12px !important;
                font-family: Arial, sans-serif !important;
            }

            .receipt-title,
            .receipt-header,
            .d-flex,
            .table,
            .total-section,
            .receipt-footer {
                font-weight: normal !important;
                margin: 0 !important;
                padding: 5px 0 !important;
                width: 100% !important;
                text-align: center !important;
            }

            .table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table th,
            .table td {
                padding: 6px !important;
                font-size: 10px !important;
                vertical-align: top;
            }

            .text-end {
                text-align: right !important;
            }

            hr {
                border: 0 !important;
                border-top: 1px dashed #000 !important;
                margin: 8px 0 !important;
                width: 100% !important;
                display: block !important;
                opacity: 0.6 !important;
            }

            .attribute {
                font-size: 10px !important;
                color: #000 !important;
                font-weight: normal !important;
            }

            .logo-container {
                text-align: center;
                font-weight: bold;
                margin: 0 !important;
                padding: 0 !important;
            }

            .logo-container img {
                max-width: 100%;
                height: auto;
                width: 50%;
                /* Removed dark filters to show colored logos properly */
                margin: 0 !important;
                padding: 0 !important;
                /* Ensure logo prints well */
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Fallback for default logo only */
            .logo-container img.default-logo {
                filter: brightness(0.3) contrast(2);
                -webkit-filter: brightness(0.3) contrast(2);
            }

            /* Print optimization for colored logos */
            @media print {
                .logo-container img {
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }

            .billAddress div {
                margin-bottom: 2px;
                text-align: center;
            }

            .table td {
                padding: 4px 6px !important;
            }

            .quantity-with-pcs {
                display: inline-block;
            }
        }
    </style>
</head>

<body class="billBody" style="font-family: Arial, sans-serif; font-size: 12px; padding: 8px; text-transform: uppercase;">

    <div id="printArea">

        <div class="logo-container" style="margin: 0 !important; padding: 0 !important;">
            @if ($location && $location->logo_image)
                <img src="{{ asset($location->logo_image) }}" alt="{{ $location->name }} Logo" class="logo" width="50px" height="50px" />
            @else
                <div style="font-size: 20px; font-weight: bold;">{{ $location->name ?? 'LOCATION NAME' }}</div>
            @endif
            {{-- <div style="font-size: 28px; font-weight: bold;">PRANY</div>
            <div style="font-size: 16px; font-weight: bold;">STORES</div> --}}
        </div>
        <div class="billAddress" style="font-size: 12px; color: #000; margin-bottom: 4px; margin-top: 2px;">
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
            @else
                <div>LOCATION DETAILS NOT AVAILABLE.</div>
            @endif
        </div>
        <div
            style="font-size: 12px; margin-bottom: 8px; border-bottom: 1px dashed #000; border-top: 1px dashed #000; color: #000;">
            <table width="100%" border="0">
                <tbody>
                    <tr>
                        <td>
                            <div style="font-size: 12px; color: #000; margin-bottom: 4px;">{{ $customer->first_name }}
                                {{ $customer->last_name }}</div>
                            <div style="font-size: 10px; color: #000;">
                                [{{ date('d-m-Y ', strtotime($sale->sales_date)) }}]
                                {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
                            </div>
                        </td>
                        <td>&nbsp;</td>
                        <td width="120" align="center">
                            <div style="font-size: 12px; color: #000;">{{ $sale->invoice_no }}</div>
                            <div style="font-size: 10px; color: #000;">({{ $user->user_name ?? 'System User' }})</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table width="100%" border="0" style="color: #000; margin-bottom: 12px;">
            <tbody>
                <tr>
                    <th>#</th>
                    <th>ITEMS</th>
                    <th>RATE</th>
                    <th>QTY</th>
                    <th>AMOUNT</th>
                </tr>
                <tr>
                    <th colspan="5">
                        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
                    </th>
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
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td colspan="4" valign="top">
                        {{ $item['product']->product->product_name }}
                        @if ($item['type'] == 'imei')
                            <span style="font-size: 10px;">({{ $item['imei'] }})</span>
                        @endif
                        @if ($item['product']->price_type == 'retail')
                            <span style="font-weight: bold;">*</span>
                        @elseif($item['product']->price_type == 'wholesale')
                            <span style="font-weight: bold;">**</span>
                        @elseif($item['product']->price_type == 'special')
                            <span style="font-weight: bold;">***</span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td valign="top">&nbsp;</td>
                    <td valign="top">
                        <span style="text-decoration: line-through">
                            {{ number_format($item['product']->product->max_retail_price, 0, '.', ',') }}
                        </span>
                        ({{ number_format($item['product']->product->max_retail_price - $item['product']->price, 0, '.', ',') }})
                    </td>
                    <td align="left" valign="top">
                        <span>{{ number_format($item['product']->price, 0, '.', ',') }}</span>
                    </td>
                    <td align="left" valign="top" class="quantity-with-pcs">
                        <span>&times; {{ $item['quantity'] }} pcs</span>
                    </td>
                    <td valign="top" align="right">
                        <span style="font-weight: bold;">
                            {{ number_format($item['amount'], 0, '.', ',') }}
                        </span>
                    </td>
                </tr>
                @endforeach

            </tbody>
        </table>

        <div style="margin: 8px 0; border-top-style: dashed; border-width: 1px; border-color: #000;"></div>

        <div style="position: relative; margin-bottom: 12px;">
            <table width="100%" border="0" style="color: #000;">
                <tbody>
                    <tr>
                        <td align="right"><strong>SUBTOTAL</strong></td>
                        <td width="80" align="right" style="font-weight: bold;">
                            {{ number_format($sale->subtotal, 0, '.', ',') }}</td>
                    </tr>
                    @if ($sale->discount_amount > 0)
                        <tr>
                            <td align="right">
                                <strong>DISCOUNT
                                    @if ($sale->discount_type == 'percentage')
                                        ({{ $sale->discount_amount }}%)
                                    @else
                                        (RS)
                                    @endif
                                </strong>
                            </td>
                            <td width="80" align="right">
                                @if ($sale->discount_type == 'percentage')
                                    -{{ number_format(($sale->subtotal * $sale->discount_amount) / 100, 0, '.', ',') }}
                                @else
                                    -{{ number_format($sale->discount_amount, 0, '.', ',') }}
                                @endif
                            </td>
                        </tr>
                    @endif
                    @if ($sale->shipping_charges > 0)
                        <tr>
                            <td align="right"><strong>SHIPPING</strong></td>
                            <td width="80" align="right">
                                {{ number_format($sale->shipping_charges, 0, '.', ',') }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td align="right"><strong>TOTAL</strong></td>
                        <td width="80" align="right" style="font-weight: bold;">
                            {{ number_format($sale->final_total, 0, '.', ',') }}</td>
                    </tr>
                    @if (!is_null($amount_given) && $amount_given > 0)
                        <tr>
                            <td align="right"><strong>AMOUNT GIVEN</strong></td>
                            <td width="80" align="right">{{ number_format($amount_given, 0, '.', ',') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td align="right"><strong>PAID</strong></td>
                        <td width="80" align="right">{{ number_format($sale->total_paid, 0, '.', ',') }}</td>
                    </tr>
                    @if (!is_null($balance_amount) && $balance_amount > 0)
                        <tr>
                            <td align="right"><strong>BALANCE GIVEN</strong></td>
                            <td width="80" align="right">{{ number_format($balance_amount, 0, '.', ',') }}</td>
                        </tr>
                    @endif
                    @if (!is_null($sale->total_due) && $sale->total_due > 0)
                        <tr>
                            <td align="right"><strong>BALANCE DUE</strong></td>
                            <td width="80" align="right">
                                <div style="padding: 4px; display: inline-block; min-width: 60px; text-align: right;">
                                    ({{ number_format($sale->total_due, 0, '.', ',') }})
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
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
        </div>
        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        <div style="display: flex; text-align: center;">
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong style="font-size: 16px;">{{ count($products) }}</strong><br>
                <span style="font-size: 10px;">TOTAL ITEMS</span>
            </div>
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong style="font-size: 16px;">{{ $products->sum('quantity') }}</strong><br>
                <span style="font-size: 10px;">TOTAL QTY</span>
            </div>
            <div style="flex: 1; padding: 4px;">
                <strong style="font-size: 16px;">{{ number_format($total_all_discounts, 0, '.', ',') }}</strong><br>
                <span style="font-size: 10px;"> TOTAL DISCOUNT</span>
            </div>
        </div>

        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
            <p><strong>PAYMENT METHOD:</strong>
                @if ($payments->count() > 1)
                    Multiple <br>
                    <span style="font-size: 10px;">({{ $payments->pluck('payment_method')->join(', ') }})</span>
                @else
                    {{ $payments->first()->payment_method ?? 'N/A' }}
                @endif
            </p>
        </div>

        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        <div class="attribute" style="font-size: 8px; color: #000; font-weight: normal !important; text-align: center;">
            SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959
        </div>
    </div>

</body>

</html>
