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
       PRINT SETTINGS - FULL SIZE
       ========================= */
        @media print {
            @page {
                size: 8.0in 11.0in;
                /* Full paper size for dot matrix */
                margin: 0in;
            }

            html,
            body {
                width: 8.0in;
                height: 11.0in;
                background: white !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                margin: 0;
                padding: 0;
                font-family: Arial, 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.3;
            }

            .invoice-page {
                width: 8.0in;
                margin: 0.2in auto;
                padding: 0.3in 0.2in 0.3in 0.2in;
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
            font-family: Arial, 'Courier New', monospace;
            background-color: white;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .invoice-page {
            max-width: 8.0in;
            min-height: 11.0in;
            margin: 0 auto;
            background-color: white;
            position: relative;
            padding: 0.3in 0.2in 0.3in 0.2in;
            display: flex;
            flex-direction: column;
        }

        .perforation-top,
        .perforation-bottom {
            display: none;
        }

        .reg-no {
            position: absolute;
            right: 0.1in;
            top: 0.3in;
            font-size: 11px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.15in;
            margin-top: 0.08in;
            padding: 0;
        }

        .company-info {
            flex: 1;
            padding-right: 0.15in;
        }

        .company-email {
            font-size: 11px;
            margin-bottom: 4px;
            font-weight: normal;
        }

        .company-logo {
            font-family: Arial, 'Courier New', monospace;
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-transform: uppercase;
            text-align: center;
        }

        .company-address {
            font-size: 12px;
            line-height: 1.5;
        }

        .customer-box {
            margin: 0;
            border: 2px dashed #333;
            padding: 6px 10px;
            width: 4.3in;
            background-color: white;
            border-radius: 8px;
        }

        .customer-name-bold {
            font-weight: bold;
        }

        .invoice-no-bold {
            font-weight: bold;
        }

        .outstanding-highlight {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 8px;
            margin-top: 8px;
        }

        .customer-line {
            display: flex;
            font-size: 12px;
            margin: 4px 0;
        }

        .customer-line label {
            width: 1.0in;
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
            right: 0.2in;
            top: 1.6in;
            border: 2px solid #333;
            padding: 5px 16px;
            font-size: 11px;
            font-weight: bold;
            background-color: white;
        }

        .invoice-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0.1in 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 0.3in 0;
        }

        .items-table thead {
            background-color: white;
        }

        .items-table th {
            padding: 8px 6px;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 2px solid #666;
        }

        .items-table th:first-child {
            text-align: center;
            width: 0.4in;
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
            width: 0.9in;
        }

        .items-table td {
            padding: 6px;
            font-size: 12px;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #999;
        }

        .items-table td:first-child {
            text-align: center;
            width: 0.4in;
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
            width: 0.9in;
        }

        .summary-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.2in;
            font-size: 12px;
            padding: 0;
            flex-grow: 1;
        }

        .summary-column {
            display: flex;
            flex-direction: column;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            line-height: 1.4;
        }

        .summary-row.bold {
            font-weight: bold;
            font-size: 11px;
            margin-top: 4px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 12px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #666;
        }

        .credit-box {
            border: 2px solid #333;
            padding: 8px 6px;
            margin: 10px 0 6px 0;
            background-color: white;
        }

        .outstanding-box {
            border: 1px dashed #000;
            padding: 6px 8px;
            margin-top: 8px;
            background-color: white;
        }

        .sale-notes {
            margin-top: 0.2in;
            padding: 8px 10px;
            border: 2px solid #333;
            background-color: white;
            font-size: 11px;
            line-height: 1.5;
        }

        .sale-notes-title {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 12px;
            text-decoration: underline;
        }

        .footer-line {
            border-top: 1px solid #666;
            margin-top: auto;
            padding: 0.5in 0 0 0;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .software-info {
            text-align: center;
            font-size: 9px;
            margin-top: 0.15in;
        }
    </style>
</head>

<body>
    <div class="invoice-page">
        <div class="perforation-top"></div>

        <div class="company-logo">{{ strtoupper($location->name ?? '') }}</div>

        <div class="header-section">
            <div class="company-info">
                <div class="company-address">
                    @if ($location && $location->address)
                        {{ strtoupper($location->address) }}<br>
                    @endif
                    @if ($location && $location->mobile)
                        Mobile: {{ $location->mobile }}<br>
                    @endif
                    @if ($location && $location->email)
                        Email: {{ $location->email }}<br>
                    @endif
                    @if ($location && $location->mobile)
                        Phone: {{ $location->mobile }}
                    @endif
                </div>
            </div>

            <div class="customer-box">
                <div class="customer-line">
                    <label>Customer</label>
                    <span>: <strong class="customer-name-bold">{{ strtoupper($customer->first_name . ' ' . $customer->last_name) }}</strong></span>
                </div>
                <div class="customer-line">
                    <label>Address</label>
                    <span>: <strong class="customer-name-bold">{{ strtoupper($customer->address ?? 'N/A') }}</strong></span>
                </div>
                <div class="customer-line">
                    <label>Phone</label>
                    <span>: {{ $customer->mobile_no ?? 'N/A' }}</span>
                </div>
                <div class="customer-line">
                    <label>Date</label>
                    <span>: {{ \Carbon\Carbon::parse($sale->sales_date)->format('Y-m-d h:i:s A') }}</span>
                </div>
                <div class="customer-line">
                    <label>Invoice No</label>
                    <span>: <strong class="invoice-no-bold">{{ $sale->invoice_no }}</strong>

                    </span>
                </div>
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
                INVOICE  - {{$sale->invoice_no}}
            @endif
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Discount</th>
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
                                    'free_quantity' => 0,
                                    'amount' => 0,
                                    'discount' => ($mrp - $product->price),
                                    'unitPrice' => $mrp,
                                    'rate' => $product->price,
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
                            {{($item['product']->product->product_name) }}
                            @if ($item['type'] == 'imei')
                                <span style="font-size: 10px;">({{ $item['imei'] }})</span>
                            @elseif($item['product']->product->product_variation ?? false)
                                ({{ substr($item['product']->product->product_variation, 0, 8) }})
                            @endif
                            {{-- Show batch number and expiry date if available --}}
                            @if($item['product']->batch)
                                <br><span style="font-size: 9px;">
                                    @if($item['product']->batch->batch_no)
                                        Batch: {{ $item['product']->batch->batch_no }}
                                    @endif
                                    @if($item['product']->batch->expiry_date)
                                        @if($item['product']->batch->batch_no) | @endif
                                        Exp: {{ \Carbon\Carbon::parse($item['product']->batch->expiry_date)->format('d/m/Y') }}
                                    @endif
                                </span>
                            @endif
                        </td>
                        <td>
                            {{ number_format($item['quantity'], 0) }}
                            @if(isset($item['free_quantity']) && $item['free_quantity'] > 0)
                                +{{ $item['free_quantity'] }}F
                            @endif
                        </td>
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
                <div class="summary-column">
                    <div class="summary-row">
                        <span>Total Items:</span>
                        <span>{{ count($products) }}</span>
                    </div>
                    <div class="summary-row">
                        @php
                            $totalQty = $products->sum('quantity');
                            $totalFreeQty = $products->sum('free_quantity');
                        @endphp
                        <span>Total Quantity:</span>
                        <span>{{ $totalQty + $totalFreeQty }}@if($totalFreeQty > 0) ({{ $totalFreeQty }}F)@endif</span>
                    </div>
                    @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                        @if (isset($customer_outstanding_balance) && $customer_outstanding_balance > 0)
                            {{-- @php
                                // Calculate total unpaid return amount for this customer
                                $unpaidReturnAmount = $sale->customer_id && $sale->customer_id != 1
                                    ? \App\Models\SalesReturn::where('customer_id', $sale->customer_id)
                                        ->where('total_due', '>', 0)
                                        ->sum('total_due')
                                    : 0;
                            @endphp --}}
                            <div class="outstanding-box">
                                <div class="summary-row" style="font-size: 12px; margin: 0 0 3px 0;">
                                    <span>Previous Balance:</span>
                                    <span>{{ number_format($previous_outstanding, 2) }}</span>
                                </div>
                                @if ($sale->total_due > 0)
                                    <div class="summary-row" style="font-size: 12px; margin: 0 0 6px 0; padding-bottom: 5px; border-bottom: 1px solid #999;">
                                        <span>*Current Credit:</span>
                                        <span>{{ number_format($sale->total_due, 2) }}</span>
                                    </div>
                                @endif
                                <div class="summary-row" style="font-size: 15px; font-weight: bold; letter-spacing: 0.5px; margin: 0;">
                                    <span>TOTAL DUE:</span>
                                    <span>{{ number_format($customer_outstanding_balance, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="summary-column">
                    <div class="summary-row">
                        <span>Total Discounts:</span>
                        <span>{{ number_format($total_all_discounts, 2) }}</span>
                    </div>
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

            {{-- Only show payment method for final sales, not for quotations, drafts, or sale orders --}}
            @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                <div style="text-align: center; font-size: 12px; font-weight: bold; margin-top: 0.15in;">
                    Payment: @if ($sale->total_due > 0)
                        CREDIT
                    @elseif ($payments && $payments->count() > 0)
                        {{ strtoupper($payments->first()->payment_method) }}
                    @else
                        CASH
                    @endif | <span>{{ number_format($sale->total_paid ?? 0, 2) }}</span>
                </div>
            @endif

            {{-- Sale Notes Section --}}
            @if (isset($sale->sale_notes) && $sale->sale_notes)
                <div class="sale-notes">
                    <div class="sale-notes-title">Notes:</div>
                    <div>{{ $sale->sale_notes }}</div>
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
