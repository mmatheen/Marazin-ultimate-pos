<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no, email=no, address=no">
    <title>{{ $location->name ?? 'Hardware' }} Invoice</title>
    @php
        $config = $receiptConfig ?? [];
        $fontFamily = $config['font_family'] ?? 'Arial';
        $fontSizeBase = $config['font_size_base'] ?? 12;
        $lineSpacing = $config['line_spacing'] ?? 5;
        $spacingMode = $config['spacing_mode'] ?? 'compact';
        $spacingMultiplier = $spacingMode === 'spacious' ? 1.5 : 1.0;
        $lineSpacingFactor = $lineSpacing / 5;
        $finalLineHeight = round(1.2 * $spacingMultiplier * $lineSpacingFactor, 2);
        $fontFamilyCss = "'" . $fontFamily . "', Arial, 'Courier New', monospace";

        // ── Dynamic page height ──────────────────────────────────────────
        // Count unique display items (same logic used in the table below)
        $productCount = 0;
        $_seenKeys = [];
        foreach (($products ?? []) as $_p) {
            if ($_p->imeis && $_p->imeis->count() > 0) {
                $productCount += $_p->imeis->count();
            } else {
                $_key = $_p->product_id . '-' . ($_p->batch_id ?? 'null') . '-' . $_p->price;
                if (!isset($_seenKeys[$_key])) {
                    $_seenKeys[$_key] = true;
                    $productCount++;
                }
            }
        }
        // ≤7 → half page (5.5in)  |  ≥8 → full page (11in)
        $pageHeight    = $productCount >= 8 ? '11in'  : '5.5in';
        $minHeight     = $productCount >= 8 ? '10.8in': '5.3in';
    @endphp
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* =========================
       PRINT SETTINGS
       ========================= */
        @media print {
            @page {
                size: 8.0in {{ $pageHeight }};
                margin: 0.15in 0.25in;
            }

            html,
            body {
                width: 8.0in;
                background: white !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: {{ $fontSizeBase }}px;
                line-height: {{ $finalLineHeight }};
            }

            .invoice-page {
                width: 8.0in;
                min-height: {{ $minHeight }};
                margin: 0;
                padding: 0.1in 0.25in 0.1in 0.25in;
                background: white !important;
                box-shadow: none;
                display: flex;
                flex-direction: column;
            }
        }

        /* =========================
       SCREEN + PRINT COMMON STYLES
       ========================= */
        body {
            font-family: Arial, sans-serif;
            background-color: white;
            width: 100%;
            padding: 0;
            margin: 0;
            text-transform: uppercase;
        }

        .invoice-page {
            max-width: 8.0in;
            min-height: {{ $minHeight }};
            margin: 0 auto;
            background-color: white;
            position: relative;
            padding: 0.1in 0.25in 0.08in 0.25in;
            display: flex;
            flex-direction: column;
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
            display: block;
            margin-bottom: 0.04in;
            margin-top: 0.02in;
        }

        .company-info {
            display: none;
        }

        .company-email {
            font-size: 10px;
            margin-bottom: 3px;
            font-weight: normal;
        }

        .company-logo {
            font-family: Arial, sans-serif;
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 4px;
            text-transform: uppercase;
            text-align: center;
        }

        .company-address {
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
            margin-bottom: 0.05in;
        }

        .customer-box {
            flex: 1;
            min-width: 0;
            border: none;
            padding: 0;
            background-color: white;
        }

        .customer-name-bold {
            font-weight: bold;
        }

        .invoice-no-bold {
            font-weight: bold;
        }

        .outstanding-highlight {
            font-size: 11px;
            font-weight: bold;
            border-top: 1px solid #333;
            padding-top: 3px;
            margin-top: 3px;
        }

        .customer-line {
            display: inline;
            font-size: 13px;
        }

        .customer-line label {
            display: inline;
            font-weight: bold;
            white-space: nowrap;
        }

        .customer-line span {
            display: inline;
            white-space: nowrap;
        }

        .customer-sep {
            display: inline;
            margin: 0 8px;
            color: #666;
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
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
            flex-shrink: 0;
            padding-left: 0.15in;
            align-self: center;
        }

        .customer-invoice-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 0.04in 0 0.06in 0;
            flex-wrap: nowrap;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 0.06in 0;
        }

        .items-table thead {
            background-color: white;
        }

        .items-table th {
            padding: 3px 3px;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #aaa;
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
        .items-table th:nth-child(4) {
            text-align: left;
            width: 0.85in;
        }

        .items-table th:nth-child(5) {
            text-align: right;
            width: 0.45in;
        }

        .items-table th:nth-child(6),
        .items-table th:nth-child(7),
        .items-table th:nth-child(8),
        .items-table th:nth-child(9),
        .items-table th:nth-child(10) {
            text-align: right;
            width: 0.65in;
        }

        .items-table td {
            padding: 3px 3px;
            font-size: 12px;
        }

        .items-table tbody tr {
            border-bottom: none;
        }

        .items-table td:first-child {
            text-align: center;
            width: 0.3in;
        }

        .items-table td:nth-child(2) {
            text-align: left;
        }

        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: left;
            width: 0.85in;
        }

        .items-table td:nth-child(5) {
            text-align: right;
            width: 0.45in;
        }

        .items-table td:nth-child(6),
        .items-table td:nth-child(7),
        .items-table td:nth-child(8),
        .items-table td:nth-child(9),
        .items-table td:nth-child(10) {
            text-align: right;
            width: 0.65in;
        }

        .summary-section {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #aaa;
            border-bottom: 1px solid #aaa;
            padding: 4px 0;
            margin-top: 4px;
            font-size: 12px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .summary-column {
            display: flex;
            flex-direction: column;
            min-width: 2in;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            line-height: 1.3;
            gap: 0.15in;
        }

        .summary-row span:last-child {
            text-align: right;
            white-space: nowrap;
        }

        .summary-row.bold {
            font-weight: bold;
            font-size: 13px;
            margin-top: 2px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 11px;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #666;
        }

        .credit-box {
            border: 1px solid #333;
            padding: 3px 4px;
            margin: 3px 0 2px 0;
            background-color: white;
        }

        .outstanding-box {
            border: 1px dashed #000;
            padding: 2px 4px;
            margin-top: 3px;
            background-color: white;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .total-due-bar {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: bold;
            border-top: 1.5px solid #333;
            border-bottom: 1.5px solid #333;
            padding: 3px 4px;
            margin: 4px 0;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .footer-line {
            border-top: 1px solid #666;
            margin-top: auto;
            padding: 0.06in 0 0 0;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            break-before: avoid;
            page-break-before: avoid;
        }

        .software-info {
            text-align: center;
            font-size: 8px;
            margin-top: 0.04in;
        }
    </style>
</head>

<body>
    <div class="invoice-page">
        <div class="perforation-top"></div>

        <div class="company-logo">{{ strtoupper($location->name ?? '') }}</div>

        <div class="company-address">
            @php
                $addrParts = [];
                if ($location && $location->address) $addrParts[] = strtoupper($location->address);
                if ($location && $location->mobile)  $addrParts[] = 'Mobile: ' . $location->mobile . '/  ' .($location->telephone_no ?? '');
                if ($location && $location->email)   $addrParts[] = 'Email: ' . $location->email;
            @endphp
            {{ implode(' | ', $addrParts) }}
        </div>

        <div class="customer-invoice-row">
            <div class="customer-box">
                <div style="font-size:13px; line-height:1.8; white-space:nowrap;">
                    <span class="customer-line"><label>Customer</label>: <strong class="customer-name-bold">{{ strtoupper($customer->first_name . ' ' . $customer->last_name) }}</strong></span>
                    <span class="customer-sep">|</span>
                    <span class="customer-line"><label>Phone</label>: {{ $customer->mobile_no ?? 'N/A' }}</span>
                    <span class="customer-sep">|</span>
                    <span class="customer-line"><label>Date</label>: {{ \Carbon\Carbon::parse($sale->sales_date)->format('Y-m-d h:i A') }}</span>
                </div>
            </div>
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
                &nbsp;-&nbsp;{{ $sale->invoice_no }}
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Item</th>
                    <th>Batch No</th>
                    <th>Expiry</th>
                    <th>Qty</th>
                    <th>Free</th>
                    <th>Unit Price</th>
                    <th>Disc</th>
                    <th>Net Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                {{-- Batch, IMEI, and product data already loaded from controller --}}

                {{-- Process products: separate IMEI products, group non-IMEI products --}}
                @php
                    $displayItems = [];
                    $nonImeiGroups = [];

                    foreach ($products as $product) {
                        // Get MRP from batch first, then fallback to product
                        $mrp = 0;
                        if ($product->batch && $product->batch->max_retail_price) {
                            $mrp = $product->batch->max_retail_price;
                        } elseif ($product->product && $product->product->max_retail_price) {
                            $mrp = $product->product->max_retail_price;
                        }

                        // Check if product has IMEIs
                        if ($product->imeis && $product->imeis->count() > 0) {
                            // For IMEI products, create separate rows for each IMEI
                            foreach ($product->imeis as $imei) {
                                $displayItems[] = [
                                    'type' => 'imei',
                                    'product' => $product,
                                    'imei' => $imei->imei_number,
                                    'quantity' => 1,
                                    'free_quantity' => 0,
                                    'amount' => $product->price * 1,
                                    'discount' => ($mrp - $product->price) * 1,
                                    'unitPrice' => $mrp,
                                    'rate' => $product->price,
                                    'batch_no' => $product->batch ? $product->batch->batch_no : null,
                                    'expiry_date' => $product->batch ? $product->batch->expiry_date : null,
                                ];
                            }
                        } else {
                            // Group non-IMEI products by product_id and batch_id
                            // Group by product_id + batch_id + price so different batches always show as
                            // separate lines. FIFO rows for the exact same batch still merge correctly.
                            $groupKey = $product->product_id . '-' . ($product->batch_id ?? 'null') . '-' . $product->price . '-' . ($product->custom_name ?? '');
                            if (!isset($nonImeiGroups[$groupKey])) {
                                $nonImeiGroups[$groupKey] = [
                                    'type' => 'grouped',
                                    'product' => $product,
                                    'quantity' => 0,
                                    'free_quantity' => 0,
                                    'amount' => 0,
                                    'discount' => ($mrp - $product->price),
                                    'unitPrice' => $mrp,
                                    'rate' => $product->price,
                                    'batch_no' => $product->batch ? $product->batch->batch_no : null,
                                    'expiry_date' => $product->batch ? $product->batch->expiry_date : null,
                                ];
                            }
                            $nonImeiGroups[$groupKey]['quantity'] += $product->quantity;
                            $nonImeiGroups[$groupKey]['free_quantity'] += ($product->free_quantity ?? 0);
                            $nonImeiGroups[$groupKey]['amount'] += $product->price * $product->quantity;
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
                            {{ substr($item['product']->custom_name ?? $item['product']->product->product_name, 0, 35) }}
                            @if ($item['type'] == 'imei')
                                <span style="font-size: 10px;">({{ $item['imei'] }})</span>
                            @elseif($item['product']->product->product_variation ?? false)
                                ({{ substr($item['product']->product->product_variation, 0, 8) }})
                            @endif
                        </td>
                        <td>{{ $item['batch_no'] ?? '-' }}</td>
                        <td>
                            @if(!empty($item['expiry_date']))
                                {{ \Carbon\Carbon::parse($item['expiry_date'])->format('d/m/y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php $fmtQty = fn($v) => rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.'); @endphp
                            {{ $fmtQty($item['quantity']) }}
                        </td>
                        <td>
                            @if(isset($item['free_quantity']) && $item['free_quantity'] > 0)
                                {{ $fmtQty($item['free_quantity']) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ number_format($item['unitPrice'], 2) }}</td>
                        <td>{{ number_format($item['discount'], 2) }}</td>
                        <td>{{ number_format($item['rate'], 2) }}</td>
                        <td>{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @empty
                        <tr>
                    <td colspan="10" style="text-align: center;">NO PRODUCTS FOUND</td>
                        </tr>
                    @endforelse
                </tbody>
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
                    return ($mrp - $product->price) * $product->quantity;
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

                // Calculate previous outstanding (before this bill)
                $previous_outstanding = 0;
                if (isset($customer_outstanding_balance) && $customer_outstanding_balance > 0) {
                    $previous_outstanding = $customer_outstanding_balance - $sale->total_due;
                }
            @endphp

            <div class="summary-section">
                {{-- LEFT: items count & qty --}}
                <div class="summary-column">
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span>{{ count($products) }}</span>
                    </div>
                    <div class="summary-row">
                        @php
                            $totalQty = $products->sum('quantity');
                            $totalFreeQty = $products->sum('free_quantity');
                            $fmtQ = fn($v) => rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.');
                        @endphp
                        <span>Total Qty:</span>
                        <span>{{ $fmtQ($totalQty + $totalFreeQty) }}@if($totalFreeQty > 0) ({{ $fmtQ($totalFreeQty) }}F)@endif</span>
                    </div>
                </div>

                {{-- RIGHT: discount & totals --}}
                <div class="summary-column" style="text-align:right;">
                    @if ($total_all_discounts > 0)
                        <div class="summary-row">
                            <span>Discount:</span>
                            <span>{{ number_format($total_all_discounts, 2) }}</span>
                        </div>
                    @endif
                    @if (!is_null($sale->shipping_charges) && $sale->shipping_charges > 0)
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span>{{ number_format($sale->shipping_charges, 2) }}</span>
                        </div>
                    @endif
                    <div class="summary-row bold">
                        <span>{{ in_array($sale->status, ['quotation', 'draft']) || (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order') ? 'Estimated Total:' : 'Bill Total:' }}</span>
                        <span>{{ number_format($sale->final_total, 2) }}</span>
                    </div>
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        @if ($sale->total_due == 0)
                            <div class="summary-row">
                                <span>Amount Paid:</span>
                                <span>{{ number_format($amount_paid, 2) }}</span>
                            </div>
                            <div class="summary-row">
                                <span>Balance:</span>
                                <span>{{ number_format($balance, 2) }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Outstanding balance block (credit customers only) --}}
            @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                @if (isset($customer_outstanding_balance) && $customer_outstanding_balance > 0)
                    @php
                        $unpaidReturnAmount = $sale->customer_id && $sale->customer_id != 1
                            ? \App\Models\SalesReturn::where('customer_id', $sale->customer_id)
                                ->where('total_due', '>', 0)
                                ->sum('total_due')
                            : 0;
                    @endphp
                    <div style="font-size:10px; margin-top:3px;">
                        <div class="summary-row">
                            <span>Prev. Balance:</span>
                            <span>{{ number_format($previous_outstanding, 2) }}</span>
                        </div>
                        @if ($unpaidReturnAmount > 0)
                            <div class="summary-row">
                                <span>Return:</span>
                                <span>{{ number_format($unpaidReturnAmount, 2) }}</span>
                            </div>
                        @endif
                        @if ($sale->total_due > 0)
                            <div class="summary-row">
                                <span>Current Credit:</span>
                                <span>{{ number_format($sale->total_due, 2) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="total-due-bar">
                        <span>TOTAL DUE:</span>
                        <span>{{ number_format($customer_outstanding_balance, 2) }}</span>
                    </div>
                @endif
            @endif

            {{-- Only show payment method for final sales, not for quotations, drafts, or sale orders --}}
            @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                <div style="text-align: center; font-size: 11px; font-weight: bold;">
                    Payment: @if ($sale->total_due > 0)
                        CREDIT
                    @elseif ($payments && $payments->count() > 0)
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
                @if ($location && $location->footer_note)
                    {{ $location->footer_note }}
                @else
                    Thank you for your business!
                @endif
            </div>

            <div class="perforation-bottom"></div>
        </div>
    </body>

    </html>
