<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>RECEIPT</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 5px;
        }

        @media print {
            html, body {
                width: 80mm;
                height: auto;
                margin: 0;
                padding: 0;
            }

            #printArea {
                position: relative !important;
                left: 0 !important;
                top: 0 !important;
                width: 80mm !important;
                height: auto !important;
                font-size: 12px !important;
                font-family: Arial, sans-serif !important;
                page-break-inside: auto !important;
                page-break-after: auto !important;
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
                page-break-inside: auto !important;
                page-break-after: auto !important;
            }

            .table th,
            .table td {
                padding: 2px 4px !important;
                font-size: 10px !important;
                vertical-align: top;
            }

            .table tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            .table tbody {
                page-break-inside: auto !important;
                page-break-after: auto !important;
            }

            .table thead {
                display: table-header-group !important;
            }

            .text-end {
                text-align: right !important;
            }

            hr {
                border: 0 !important;
                border-top: 1px dashed #000 !important;
                margin: 1px 0 !important;
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
                line-height: 1;
            }

            .logo-container img {
                max-width: 100%;
                height: auto;
                width: 180px;
                max-height: 90px;
                display: block;
                margin: 0 auto !important;
                padding: 0 !important;
                line-height: 1;
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

<body class="billBody" style="font-family: Arial, sans-serif; font-size: 12px; padding: 0; margin: 0; text-transform: uppercase;">

    <div id="printArea" style="margin: 0; padding: 0;">

        <div class="logo-container" style="margin: 0 !important; padding: 0 !important; text-align: center; line-height: 1;">
            @if ($location && $location->logo_image)
                <img src="{{ asset($location->logo_image) }}" alt="{{ $location->name }} Logo" class="logo" style="width: 180px; max-height: 90px; display: block; margin: 0 auto; padding: 0; line-height: 1;" />
            @else
                <div style="font-size: 24px ; font-weight: bold;">{{ $location->name ?? 'LOCATION NAME' }}</div>
            @endif
            {{-- <div style="font-size: 28px; font-weight: bold;">PRANY</div>
            <div style="font-size: 16px; font-weight: bold;">STORES</div> --}}
        </div>

        <div class="billAddress" style="font-size: 12px; color: #000; margin: 0; padding: 0; text-align: center;">
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
            <div style="font-size: 12px; font-weight: bold; color: #000; margin-top: 4px;">
                [{{ date('d-m-Y', strtotime($sale->sales_date)) }}] {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
            </div>
        </div>
        <div
            style="font-size: 12px; margin: 4px 0; padding: 4px 0; border-bottom: 1px dashed #000; border-top: 1px dashed #000; color: #000;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tbody>
                    <tr>
                        <td style="vertical-align: top; width: 60%; padding: 0;">
                            <div style="font-size: 13px; font-weight: bold; color: #000; margin-bottom: 2px; word-wrap: break-word; overflow-wrap: break-word;">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                            @if($customer->mobile_no)
                                <div style="font-size: 11px; color: #000; margin-bottom: 2px;">{{ $customer->mobile_no }}</div>
                            @endif
                        </td>
                        <td style="vertical-align: top; width: 40%; text-align: right; padding: 0;">
                            <div style="font-size: 14px; font-weight: bold; color: #000; margin-bottom: 2px;">{{ $sale->invoice_no }}</div>
                            <div style="font-size: 10px; color: #000;">({{ $user->user_name ?? 'ADMIN' }})</div>
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
                    <td colspan="5" style="padding: 0 !important;">
                        <hr style="margin: 1px 0; border-top-style: dashed; border-width: 1px;">
                    </td>
                </tr>
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
                    <td style="padding: 1px 2px !important;">{{ $loop->iteration }}</td>
                    <td colspan="4" valign="top" style="padding: 1px 2px !important;">
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
                    <td valign="top" style="padding: 1px 2px !important;">&nbsp;</td>
                    <td valign="top" style="padding: 1px 2px !important;">
                        @php
                            $mrp = $item['product']->product->max_retail_price ?? 0;
                            $selling_price = $item['product']->price;
                            $line_discount = ($mrp - $selling_price) * $item['quantity'];
                        @endphp
                        @if($mrp > 0 && $mrp > $selling_price)
                            <span style="text-decoration: line-through; font-size: 11px; color: #999;">
                                {{ number_format($mrp, 0, '.', ',') }}
                            </span>
                            <span style="font-size: 11px; color: #666;">({{ number_format($line_discount, 0, '.', ',') }})</span>
                        @endif
                    </td>
                    <td align="left" valign="top" style="padding: 1px 2px !important;">
                        <span style="font-size: 12px; font-weight: 500;">{{ number_format($item['product']->price, 0, '.', ',') }}</span>
                    </td>
                    <td align="center" valign="top" style="padding: 1px 2px !important; white-space: nowrap;">
                        <span style="font-size: 9px;">&times;</span> <span style="font-size: 12px; font-weight: 500;">{{ $item['quantity'] }}</span> <small style="font-size: 8px;">PCS</small>
                    </td>
                    <td valign="top" align="right" style="padding: 1px 2px !important;">
                        <span style="font-weight: bold; font-size: 13px;">
                            {{ number_format($item['amount'], 0, '.', ',') }}
                        </span>
                    </td>
                </tr>
                @if (!$loop->last)
                <tr>
                    <td colspan="5" style="padding: 0 !important;">
                        <hr style="margin: 1px 0; border-top-style: dashed; border-width: 1px; border-color: #ddd;">
                    </td>
                </tr>
                @endif
                @endforeach

            </tbody>
        </table>

        <div style="margin: 8px 0; border-top-style: dashed; border-width: 1px; border-color: #000;"></div>

        <div style="position: relative; margin-bottom: 12px;">
            <table width="100%" border="0" style="color: #000;" cellpadding="2" cellspacing="0">
                <tbody>
                    <tr>
                        <td align="right"><strong>SUBTOTAL</strong></td>
                        <td width="100" align="right" style="font-weight: bold; font-size: 15px; white-space: nowrap;">
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
                            <td width="100" align="right" style="font-size: 14px; white-space: nowrap;">
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
                            <td width="100" align="right" style="font-size: 15px; white-space: nowrap;">
                                {{ number_format($sale->shipping_charges, 0, '.', ',') }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td align="right"><strong>TOTAL</strong></td>
                        <td width="100" align="right" style="font-weight: bold; font-size: 15px; white-space: nowrap;">
                            {{ number_format($sale->final_total, 0, '.', ',') }}</td>
                    </tr>
                    {{-- Only show payment details for final sales, not for quotations, drafts, or sale orders --}}
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        @if (!is_null($amount_given) && $amount_given > 0)
                            <tr>
                                <td align="right"><strong>AMOUNT GIVEN</strong></td>
                                <td width="100" align="right" style="font-size: 15px; white-space: nowrap;">{{ number_format($amount_given, 0, '.', ',') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td align="right"><strong>PAID</strong></td>
                            <td width="100" align="right" style="font-size: 15px; white-space: nowrap;">{{ number_format($sale->total_paid, 0, '.', ',') }}</td>
                        </tr>
                        @if (!is_null($balance_amount) && $balance_amount > 0)
                            <tr>
                                <td align="right"><strong>BALANCE GIVEN</strong></td>
                                <td width="100" align="right" style="font-size: 15px; white-space: nowrap;">{{ number_format($balance_amount, 0, '.', ',') }}</td>
                            </tr>
                        @endif
                        @if (!is_null($sale->total_due) && $sale->total_due > 0)
                            <tr>
                                <td align="right"><strong>BALANCE DUE</strong></td>
                                <td width="100" align="right" style="font-size: 15px; font-weight: bold; white-space: nowrap;">
                                    ({{ number_format($sale->total_due, 0, '.', ',') }})
                                </td>
                            </tr>
                        @endif
                        {{-- Show total outstanding balance for non-walk-in customers with credit --}}
                        @if ($customer && isset($customer_outstanding_balance) && $customer_outstanding_balance > 0)
                            <tr>
                                <td colspan="2" style="padding: 2px 0 !important;">
                                    <hr style="margin: 2px 0; border-top-style: dashed; border-width: 1px;">
                                </td>
                            </tr>
                             <tr>
                                <td align="right"><strong style="font-size: 12px;">TOTAL OUTSTANDING DUE</strong></td>
                                <td width="100" align="right" style="font-size: 18px; font-weight: bold; white-space: nowrap;">
                                       RS {{ number_format($customer_outstanding_balance, 0, '.', ',') }}
                                </td>
                            </tr>
                        @endif
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
            <div style="flex: 1; border-right: 2px dashed black; padding: 2px;">
            <strong style="font-size: 12px;">{{ count($products) }}</strong><br>
            <span style="font-size: 8px;">TOTAL ITEMS</span>
            </div>
            <div style="flex: 1; border-right: 2px dashed black; padding: 2px;">
            <strong style="font-size: 12px;">{{ $products->sum('quantity') }}</strong><br>
            <span style="font-size: 8px;">TOTAL QTY</span>
            </div>
            <div style="flex: 1; padding: 2px;">
            <strong style="font-size: 12px;">{{ number_format($total_all_discounts, 0, '.', ',') }}</strong><br>
            <span style="font-size: 8px;">TOTAL DISCOUNT</span>
            </div>
        </div>

        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        {{-- Only show payment method for final sales, not for quotations, drafts, or sale orders --}}
        @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
            @if ($payments->count() > 0)
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
            @endif
        @else
            <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
                @if ($sale->status === 'quotation')
                    <p><strong>*** QUOTATION - PRICE ESTIMATE ONLY ***</strong></p>
                @elseif ($sale->status === 'draft')
                    <p><strong>*** DRAFT - PRICE ESTIMATE ONLY ***</strong></p>
                @elseif (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order')
                    <p><strong>*** SALE ORDER - CONFIRMED ORDER ***</strong></p>
                @endif
            </div>
        @endif

        <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">

        {{-- Sale Notes Section --}}
        @if ($sale->sale_notes)
            <div style="font-size: 11px; color: #000; text-align: center; margin-bottom: 8px; padding: 5px; background-color: #f9f9f9; border: 1px dashed #ccc;">
                <strong>NOTES:</strong><br>
                {{ $sale->sale_notes }}
            </div>
            <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        @endif

        @if ($location && $location->footer_note)
            <div style="font-size: 11px; color: #000; font-weight: bold; text-align: center; margin-bottom: 5px;">
                {{ $location->footer_note }}
            </div>
        @endif
        <div class="attribute" style="font-size: 8px; color: #000; font-weight: normal !important; text-align: center;">
            SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959
        </div>
    </div>

</body>

</html>
