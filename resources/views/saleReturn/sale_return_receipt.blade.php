<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>RETURN RECEIPT</title>
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
            }

            .logo-container img {
                max-width: 100%;
                height: auto;
                width: 100%;
            }

            .billAddress div {
                margin-bottom: 4px;
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
        <div class="logo-container" style="margin-top: 8px; margin-bottom: 8px;">
            <img src="{{ asset('assets/img/arb-fashion.png') }}" alt="ARB Distribution Logo" class="logo" />
        </div>

        <div class="billAddress" style="font-size: 12px; color: #000; margin-bottom: 12px;">
            @if ($location)
                @if ($location->address)
                    <div>{{ $location->address }}</div>
                @endif
                @if ($location->mobile)
                    <div>{{ $location->mobile }}</div>
                @endif
                @if ($location->email)
                    <div>{{ $location->email }}</div>
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
                                [{{ date('d-m-Y', strtotime($saleReturn->return_date)) }}]
                                {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
                            </div>
                        </td>
                        <td>&nbsp;</td>
                        <td width="180" align="center" style="vertical-align: top;">
                            <div
                                style="border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; background: #f9f9f9; margin-bottom: 4px;">
                                <div style="font-size: 11px; color: #333; font-weight: bold; margin-bottom: 2px;">
                                    SALE DETAILS
                                </div>
                                @php
                                    $sale = $saleReturn->sale ?? null;
                                    $stockType = $sale->stock_type ?? 'with_bill';
                                    $hasInvoice = isset($sale->invoice_no);
                                @endphp

                                @if (!$sale)
                                    <div style="font-size: 10px; color: #888;">
                                        SALE DETAILS NOT AVAILABLE
                                    </div>
                                @elseif ($stockType === 'with_bill' && $hasInvoice)
                                    <div style="font-size: 10px; color: #000;">
                                        <span style="font-weight: 600;">INV #:</span>
                                        {{ $sale->invoice_no }}
                                    </div>
                                    <div style="font-size: 10px; color: #000;">
                                        <span style="font-weight: 600;">Date:</span>
                                        {{ date('d-m-Y', strtotime($sale->sales_date)) }}
                                    </div>
                                @elseif ($stockType === 'without_bill')
                                    <div style="font-size: 10px; color: #000;">
                                        <span style="font-weight: 600;">Type:</span> Without Bill
                                    </div>
                                    <div style="font-size: 10px; color: #888;">
                                        No Invoice Assigned
                                    </div>
                                @else
                                    <div style="font-size: 10px; color: #888;">
                                        SALE INVOICE #: N/A
                                    </div>
                                @endif
                            </div>
                            <div
                                style="border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; background: #f1f7fa;">
                                <div style="font-size: 11px; color: #333; font-weight: bold; margin-bottom: 2px;">
                                    RETURN DETAILS
                                </div>
                                <div style="font-size: 10px; color: #000;">
                                    <span style="font-weight: 600;">RETURN #:</span> {{ $saleReturn->invoice_number }}
                                </div>
                                <div style="font-size: 10px; color: #000;">
                                    <span style="font-weight: 600;">User:</span>
                                    {{ $user->user_name ?? ($user->name ?? 'N/A') }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>


        <table width="100%" border="0" style="color: #000; margin-bottom: 12px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ITEMS</th>
                    <th>RATE</th>
                    <th>QTY</th>
                    <th>AMOUNT</th>
                </tr>
                <tr>
                    <th colspan="5">
                        <hr>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($saleReturn->returnProducts as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product->product_name }}</td>
                        <td>{{ number_format($item->return_price, 0, '.', ',') }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td align="right">{{ number_format($item->subtotal, 0, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No products returned</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div style="margin: 8px 0; border-top-style: dashed; border-width: 1px;"></div>
        <div style="position: relative; margin-bottom: 12px;">
            <table width="100%" border="0" style="color: #000;">
                <tbody>
                    <tr>
                        <td align="right"><strong>TOTAL RETURN AMOUNT</strong></td>
                        <td width="80" align="right" style="font-weight: bold;">
                            {{ number_format($saleReturn->return_total, 0, '.', ',') }}
                        </td>
                    </tr>
                    @if ($saleReturn->total_paid > 0)
                        <tr>
                            <td align="right"><strong>Paid</strong></td>
                            <td width="80" align="right">{{ number_format($saleReturn->total_paid, 0, '.', ',') }}
                            </td>
                        </tr>
                    @endif
                    @if ($saleReturn->total_due > 0)
                        <tr>
                            <td align="right"><strong>Balance Due</strong></td>
                            <td width="80" align="right">
                                ({{ number_format($saleReturn->total_due, 0, '.', ',') }})</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <hr>
        @php
            $products = $saleReturn->returnProducts ?? collect([]);
            $totalItems = $products->count();
            $totalQty = $products->sum('quantity');
        @endphp

        <div style="display: flex; text-align: center;">
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong style="font-size: 16px;">{{ $totalItems }}</strong><br>
                <span style="font-size: 10px;">TOTAL ITEMS</span>
            </div>
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong style="font-size: 16px;">{{ $totalQty }}</strong><br>
                <span style="font-size: 10px;">TOTAL QTY</span>
            </div>
            <div style="flex: 1; padding: 4px;">
                <strong style="font-size: 16px;">0</strong><br>
                <span style="font-size: 10px;">TOTAL DISCOUNT</span>
            </div>
        </div>
        <hr>

        <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
            <p><strong>NOTES:</strong> {{ $saleReturn->notes ?? 'N/A' }}</p>
        </div>

        <hr>
        <div class="attribute" style="font-size: 8px; color: #000; font-weight: normal !important; text-align: center;">
            SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959
        </div>
    </div>
</body>

</html>
