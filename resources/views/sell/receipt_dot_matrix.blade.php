<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $location->name ?? 'Hardware' }} Invoice</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================
       PRINT SETTINGS
       ========================= */
        @media print {
            @page {
                size: 8.0in 5.5in;
                /* Actual printable width of dot matrix paper */
                margin: 0in;
                /* Remove extra white borders */
            }

            html,
            body {
                width: 8.0in;
                height: 5.5in;
                background: white !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                margin: 0;
                padding: 0;
                font-family: 'Roboto Mono', monospace;
                font-size: 12px;
                line-height: 1.3;
            }

            .invoice-page {
                width: 8.0in;
                margin: 0.2 auto;
                padding: 0.5in 0.5in 0.05in 0.5in;
                background: white !important;
                box-shadow: none;
                page-break-inside: avoid;
            }

            /* Avoid page cutting */
            .invoice-page,
            .items-table,
            .summary-section {
                page-break-after: avoid;
            }
        }

        /* =========================
       SCREEN + PRINT COMMON STYLES
       ========================= */
        body {
            font-family: 'Roboto Mono', monospace;
            background-color: white;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .invoice-page {
            max-width: 8.0in;
            margin: 0 auto;
            background-color: white;
            position: relative;
            padding: 0.2in 0.2in 0.05in 0.2in;
        }

        .perforation-top,
        .perforation-bottom {
            display: none;
        }

        .reg-no {
            position: absolute;
            right: 0.05in;
            top: 0.25in;
            font-size: 10px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.1in;
            margin-top: 0.1in;
            padding: 0;
        }

        .company-info {
            flex: 1;
            padding-right: 0.1in;
        }

        .company-logo {
            font-family: 'Roboto Mono', monospace;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .company-address {
            font-size: 11px;
            line-height: 1.35;
        }

        .customer-box {
            margin: 0.1in 0 0 0;
            border: 2px dashed #333;
            padding: 4px 8px;
            width: 3.5in;
            /* Reduced width */
            background-color: white;
            border-radius: 10px;

        }

        .customer-line {
            display: flex;
            font-size: 11px;
            margin: 2.5px 0;
        }

        .customer-line label {
            width: 0.8in;
            /* Reduced width */
        }

        .customer-line span {
            flex: 1;
        }

        .type-credit {
            color: #c00;
            font-weight: bold;
            margin-left: 12px;
        }

        .delivered-badge {
            position: absolute;
            right: 0.1in;
            top: 1.45in;
            border: 2px solid #333;
            padding: 3px 14px;
            font-size: 10px;
            font-weight: bold;
            background-color: white;
        }

        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 0.2in 0;
        }

        .items-table thead {
            background-color: white;
        }

        .items-table th {
            padding: 6px 4px;
            /* Reduced padding */
            font-size: 14px;
            /* Increased font size by 2px */
            font-weight: bold;
            border-bottom: 1px solid #666;
        }

        .items-table th:first-child {
            text-align: center;
            width: 0.3in;
            /* Reduced width */
        }

        .items-table th:nth-child(2) {
            text-align: left;
        }

        .items-table th:nth-child(3),
        .items-table th:nth-child(4),
        .items-table th:nth-child(5),
        .items-table th:nth-child(6),
        .items-table th:nth-child(7) {
            text-align: right;
            width: 0.8in;
            /* Reduced width */
        }

        .items-table td {
            padding: 4px;
            /* Reduced padding */
            font-size: 12px;
            /* Increased font size by 2px */
        }

        .items-table tbody tr {
            border-bottom: 1px solid #999;
        }

        .items-table td:first-child {
            text-align: center;
            width: 0.3in;
        }

        .items-table td:nth-child(2) {
            text-align: left;
        }

        .items-table td:nth-child(3),
        .items-table td:nth-child(4),
        .items-table td:nth-child(5),
        .items-table td:nth-child(6),
        .items-table td:nth-child(7) {
            text-align: right;
            width: 0.8in;
        }

        .summary-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.1in;
            /* Reduced gap */
            margin-top: 0.22in;
            font-size: 11px;
            /* Reduced font size */
            padding: 0;
        }

        .summary-column {
            display: flex;
            flex-direction: column;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            /* Reduced padding */
            line-height: 1.3;
        }

        .summary-row.bold {
            font-weight: bold;
            font-size: 10px;
            margin-top: 3px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 11px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1.5px solid #666;
        }

        .footer-line {
            border-top: 1px solid #666;
            margin-top: 0.28in;
            padding: 0.12in 0 0 0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }

        .software-info {
            text-align: center;
            font-size: 8.5px;
            margin-top: 0.1in;
        }
    </style>
</head>

<body>
    <div class="invoice-page">
        <div class="perforation-top"></div>

        {{-- <div class="reg-no">Reg No: {{ $sale->invoice_no }}</div> --}}

        <div class="header-section">
            <div class="company-info">
                <div class="company-logo">{{ strtoupper($location->name ?? 'HARDWARE STORE') }}</div>
                <div class="company-address">
                    @if ($location && $location->address)
                        {{ strtoupper($location->address) }}<br>
                    @endif
                    @if ($location && $location->mobile)
                        Mobile: {{ $location->mobile }}@if ($location->email)
                            , Email: {{ $location->email }}
                        @endif
                        <br>
                    @endif
                    @if ($location && $location->mobile)
                        Phone: {{ $location->mobile }}
                    @endif
                </div>
            </div>

            <div class="customer-box">
                <div class="customer-line">
                    <label>Customer</label>
                    <span>: {{ strtoupper($customer->first_name . ' ' . $customer->last_name) }}</span>
                </div>
                <div class="customer-line">
                    <label>Phone</label>
                    <span>: {{ $customer->mobile_no ?? 'N/A' }}</span>
                </div>
                <div class="customer-line">
                    <label>Date</label>
                    <span>: {{ date('Y-m-d H:i:s', strtotime($sale->sales_date)) }}</span>
                </div>
                <div class="customer-line">
                    <label>Invoice No</label>
                    <span>: {{ $sale->invoice_no }}@if ($sale->total_due > 0)
                            <span class="type-credit">Type: Credit</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if ($sale->total_due <= 0)
            {{-- <div class="delivered-badge">Delivered</div> --}}
        @endif

        <div class="invoice-title">
            @if ($sale->status === 'quotation')
                QUOTATION
            @elseif ($sale->status === 'draft')
                DRAFT
            @elseif (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order')
                SALE ORDER
            @else
                INVOICE
            @endif
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Discount</th>
                    <th>Rate</th>
                    <th>Amount</th>
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
                                    'discount' => ($product->product->max_retail_price - $product->price) * 1,
                                    'unitPrice' => $product->product->max_retail_price,
                                    'rate' => $product->price,
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
                                    'discount' => 0,
                                    'unitPrice' => $product->product->max_retail_price,
                                    'rate' => $product->price,
                                ];
                            }
                            $nonImeiGroups[$groupKey]['quantity'] += $product->quantity;
                            $nonImeiGroups[$groupKey]['amount'] += $product->price * $product->quantity;
                            $nonImeiGroups[$groupKey]['discount'] += ($product->product->max_retail_price - $product->price) * $product->quantity;
                        }
                    }

                    // Merge grouped items with IMEI items
                    $displayItems = array_merge($displayItems, array_values($nonImeiGroups));
                    $index = 1;
                @endphp

                @forelse ($displayItems as $item)
                    <tr>
                        <td>{{ $index++ }}</td>
                        <td>
                            {{ substr($item['product']->product->product_name, 0, 35) }}
                            @if ($item['type'] == 'imei')
                                <span style="font-size: 10px;">({{ $item['imei'] }})</span>
                            @elseif($item['product']->product->product_variation ?? false)
                                ({{ substr($item['product']->product->product_variation, 0, 8) }})
                            @endif
                        </td>
                        <td>{{ number_format($item['quantity'], 0) }}</td>
                        <td>{{ number_format($item['unitPrice'], 2) }}</td>
                        <td>{{ number_format($item['discount'], 2) }}</td>
                        <td>{{ number_format($item['rate'], 2) }}</td>
                        <td>{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center;">NO PRODUCTS FOUND</td>
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
                $amount_paid = $sale->total_paid ?? 0;
                $balance = $sale->total_due ?? 0;
            @endphp

            <div class="summary-section">
                <div class="summary-column">
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span>{{ count($products) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Quantity:</span>
                        <span>{{ $products->sum('quantity') }}</span>
                    </div>
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        <div class="summary-row">
                            <span>Previous Outstanding:</span>
                            <span>0.00</span>
                        </div>
                    @endif
                </div>

                <div class="summary-column">
                    <div class="summary-row">
                        <span>Total Discounts:</span>
                        <span>{{ number_format($total_all_discounts, 2) }}</span>
                    </div>
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        <div class="summary-row">
                            <span>Return Amount:</span>
                            <span>0.00</span>
                        </div>
                    @endif
                    @if (!is_null($sale->shipping_charges) && $sale->shipping_charges > 0)
                        <div class="summary-row">
                            <span>Shipping Charges:</span>
                            <span>{{ number_format($sale->shipping_charges, 2) }}</span>
                        </div>
                    @endif
                    <div class="summary-row">
                        <span>{{ in_array($sale->status, ['quotation', 'draft']) || (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order') ? 'Estimated Total:' : 'Amount Payable:' }}</span>
                        <span>{{ number_format($sale->final_total, 2) }}</span>
                    </div>
                </div>

                <div class="summary-column">
                    <div class="summary-row">
                        <span>Discount:</span>
                        <span>{{ number_format($total_all_discounts, 2) }}</span>
                    </div>
                    <div class="summary-row bold">
                        <span>{{ in_array($sale->status, ['quotation', 'draft']) || (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order') ? 'Estimated Total:' : 'Bill Total:' }}</span>
                        <span>{{ number_format($sale->final_total, 2) }}</span>
                    </div>
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        <div class="summary-row">
                            <span>Amount Paid:</span>
                            <span>{{ number_format($amount_paid, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Balance:</span>
                            <span>{{ number_format($balance, 2) }}</span>
                        </div>
                        @if ($sale->total_due > 0)
                            <div class="summary-row total">
                                <span>*Current Credit:</span>
                                <span>{{ number_format($sale->total_due, 2) }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Only show payment method for final sales, not for quotations, drafts, or sale orders --}}
            @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                <div style="text-align: center; font-size: 11px; font-weight: bold;">
                    Payment: @if ($payments && $payments->count() > 0)
                        {{ strtoupper($payments->first()->payment_method) }}
                    @else
                        CASH
                    @endif | <span>{{ number_format($sale->total_paid ?? 0, 2) }}</span>
                </div>
            @endif


            <div class="footer-line">
                <div>Prepared By: {{ strtoupper($user->user_name ?? ($user->name ?? 'CASHIER')) }}</div>
                <div>Checked By:</div>
                <div>Customer Acceptance:</div>
            </div>

            <div class="software-info">
                Software by Marazin Pvt.Ltd |
                Thank you for your business!
            </div>

            <div class="perforation-bottom"></div>
        </div>
    </body>

    </html>
