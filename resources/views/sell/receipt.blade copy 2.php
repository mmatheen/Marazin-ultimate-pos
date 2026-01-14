<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 5px;
        }

        body {
            font-family: 'Courier New Bold', 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            text-transform: uppercase;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .receipt-container {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
        }

        /* Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 4px;
        }

        .logo-section img {
            width: 180px;
            max-height: 90px;
            display: block;
            margin: 0 auto;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .logo-section .text-logo {
            font-size: 24px;
            font-weight: bold;
        }

        /* Business Info Section */
        .business-info {
            text-align: center;
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .business-info .business-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 1px;
            letter-spacing: 0.5px;
        }

        .business-info .contact-line {
            font-size: 12px;
            margin-bottom: 0;
            letter-spacing: 0.2px;
        }

        .business-info .email {
            text-transform: lowercase;
        }

        .business-info .date-time {
            font-size: 12px;
            font-weight: bold;
            margin-top: 4px;
        }

        /* Header Section */
        .receipt-header {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            margin: 3px 0;
        }

        .customer-info {
            flex: 1;
            text-align: left;
        }

        .customer-info .customer-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .customer-info .customer-phone {
            font-size: 12px;
            letter-spacing: 0.2px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-info .invoice-number {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }

        .invoice-info .cashier {
            font-size: 11px;
            letter-spacing: 0.2px;
        }

        /* Products Table */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .products-table th {
            font-size: 13px;
            font-weight: 700;
            padding: 3px 2px;
            text-align: left;
            letter-spacing: 0.3px;
        }

        .products-table th:first-child {
            width: 7%;
        }

        .products-table th:nth-child(2) {
            width: 43%;
        }

        .products-table th:nth-child(3) {
            width: 16%;
            text-align: center;
        }

        .products-table th:nth-child(4) {
            width: 16%;
            text-align: left;
        }

        .products-table th:last-child {
            width: 18%;
            text-align: right;
        }

        .products-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .product-name-row td {
            padding-top: 2px;
        }

        .product-details-row td {
            padding-bottom: 2px;
        }

        .product-name {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            word-wrap: break-word;
            line-height: 1.3;
        }

        .price-badge {
            font-weight: bold;
        }

        .mrp-price {
            font-size: 13px;
            font-weight: 700;
            color: #000;
            letter-spacing: 0.3px;
            position: relative;
            display: inline-block;
            white-space: nowrap;
        }

        .mrp-price::after {
            content: '';
            position: absolute;
            left: 8%;
            right: 8%;
            top: 40%;
            border-top: 1.5px solid #4e4d4d;
        }

        .discount-amount {
            font-size: 10px;
            color: #000;
            font-weight: 600;
            letter-spacing: 0.2px;
            display: inline-block;
            white-space: nowrap;
            margin-left: 2px;
        }

        .quantity {
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            text-align: center;
            letter-spacing: 0.2px;
        }

        .rate {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .amount {
            font-size: 14px;
            font-weight: 700;
            text-align: right;
            letter-spacing: 0.3px;
        }

        .multiply-symbol {
            font-size: 12px;
            font-weight: 700;
        }

        .pcs-text {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        /* Dividers */
        .divider {
            border: 0;
            border-top: 1px dashed #000;
            margin: 1px 0;
        }

        .divider-light {
            border-top: 1px dashed #ddd;
        }

        .divider-section {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        /* Totals Section */
        .totals-section {
            margin-bottom: 8px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 1px 0;
        }

        .totals-table .label {
            text-align: right;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.3px;
        }

        .totals-table .value {
            text-align: right;
            font-size: 16px;
            font-weight: 700;
            width: 100px;
            white-space: nowrap;
            letter-spacing: 0.4px;
        }

        .totals-table .discount-value {
            font-size: 15px;
            letter-spacing: 0.3px;
        }

        .outstanding-due {
            font-size: 19px !important;
            letter-spacing: 0.5px;
        }

        /* Stats Section */
        .stats-section {
            display: flex;
            text-align: center;
            margin: 5px 0;
        }

        .stat-box {
            flex: 1;
            padding: 2px;
            border-right: 2px dashed #000;
        }

        .stat-box:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 13px;
            font-weight: 700;
            display: block;
            letter-spacing: 0.3px;
        }

        .stat-label {
            font-size: 9px;
            display: block;
            letter-spacing: 0.2px;
        }

        /* Payment & Notes */
        .payment-method {
            font-size: 13px;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .payment-method strong {
            font-weight: bold;
        }

        .payment-method .multiple-payments {
            font-size: 11px;
            letter-spacing: 0.2px;
        }

        .status-message {
            font-size: 13px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .sale-notes {
            font-size: 12px;
            text-align: center;
            margin-bottom: 8px;
            padding: 5px;
            background-color: #f9f9f9;
            border: 1px dashed #ccc;
            letter-spacing: 0.2px;
        }

        .sale-notes strong {
            display: block;
            margin-bottom: 2px;
        }

        /* Footer */
        .footer-note {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }

        .software-info {
            font-size: 10px;
            text-align: center;
            color: #000;
            line-height: 1.3;
            margin-top: 5px;
            letter-spacing: 0.2px;
        }

        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .receipt-container {
                width: 100%;
            }

            .logo-section img {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">

        {{-- Logo Section --}}
        <section class="logo-section">
            @if ($location && $location->logo_image)
                <img src="{{ asset($location->logo_image) }}" alt="{{ $location->name }} Logo">
            @else
                <div class="text-logo">{{ $location->name ?? 'LOCATION NAME' }}</div>
            @endif
        </section>

        {{-- Business Info Section --}}
        <section class="business-info">
            @if ($location)
                @if ($location->logo_image && $location->name)
                    <div class="business-name">{{ $location->name }}</div>
                @endif
                @if ($location->address)
                    <div class="contact-line">{{ $location->address }}</div>
                @endif
                @if ($location->mobile)
                    <div class="contact-line">{{ $location->mobile }}</div>
                @endif
                @if ($location->email)
                    <div class="contact-line email">{{ $location->email }}</div>
                @endif
            @else
                <div class="contact-line">LOCATION DETAILS NOT AVAILABLE.</div>
            @endif
            <div class="date-time">
                [{{ date('d-m-Y', strtotime($sale->sales_date)) }}] {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
            </div>
        </section>

        {{-- Receipt Header --}}
        <header class="receipt-header">
            <div class="customer-info">
                <div class="customer-name">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                @if($customer->mobile_no)
                    <div class="customer-phone">{{ $customer->mobile_no }}</div>
                @endif
            </div>
            <div class="invoice-info">
                <div class="invoice-number">{{ $sale->invoice_no }}</div>
                <div class="cashier">({{ $user->user_name ?? 'ADMIN' }})</div>
            </div>
        </header>

        {{-- Products Table --}}
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>ITEMS</th>
                    <th>QTY</th>
                    <th>RATE</th>
                    <th>AMOUNT</th>
                </tr>
                <tr>
                    <td colspan="5"><hr class="divider"></td>
                </tr>
            </thead>
            <tbody>
                {{-- Load IMEI data and batch data for all products --}}
                @php
                    $products->load(['imeis', 'batch']);
                @endphp

                {{-- Process products: separate IMEI products, group non-IMEI products --}}
                @php
                    $displayItems = [];
                    $nonImeiGroups = [];

                    foreach ($products as $product) {
                        if ($product->imeis && $product->imeis->count() > 0) {
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

                    $displayItems = array_merge($displayItems, array_values($nonImeiGroups));
                @endphp

                @foreach ($displayItems as $index => $item)
                    <tr class="product-name-row">
                        <td>{{ $loop->iteration }}</td>
                        <td colspan="4" class="product-name">
                            {{ $item['product']->product->product_name }}
                            @if ($item['type'] == 'imei')
                                <span style="font-size: 10px;">({{ $item['imei'] }})</span>
                            @endif
                            @if ($item['product']->price_type == 'retail')
                                <span class="price-badge">*</span>
                            @elseif($item['product']->price_type == 'wholesale')
                                <span class="price-badge">**</span>
                            @elseif($item['product']->price_type == 'special')
                                <span class="price-badge">***</span>
                            @endif
                        </td>
                    </tr>

                    <tr class="product-details-row">
                        <td>&nbsp;</td>
                        <td>
                            @php
                                // Check if product has batch_id and batch data
                                $mrp = 0;
                                if (!empty($item['product']->batch_id) && isset($item['product']->batch)) {
                                    // Get MRP from batch table for batch-managed products
                                    $mrp = $item['product']->batch->max_retail_price ?? 0;
                                } else {
                                    // Get MRP from product table for non-batch products
                                    $mrp = $item['product']->product->max_retail_price ?? 0;
                                }
                                $selling_price = $item['product']->price;
                                $line_discount = ($mrp - $selling_price) * $item['quantity'];
                            @endphp
                            @if($mrp > 0 && $line_discount > 0)
                                <span class="mrp-price">{{ number_format($mrp, 0, '.', ',') }}</span>
                                <span class="discount-amount">({{ number_format($line_discount, 0, '.', ',') }})</span>
                            @endif
                        </td>
                        <td class="quantity">
                            <span>{{ $item['quantity'] }}</span><small class="pcs-text">PCS</small>
                            <span class="multiply-symbol">&times;</span>
                        </td>
                        <td class="rate">{{ number_format($item['product']->price, 0, '.', ',') }}</td>
                        <td class="amount">{{ number_format($item['amount'], 0, '.', ',') }}</td>
                    </tr>

                    @if (!$loop->last)
                        <tr>
                            <td colspan="5"><hr class="divider divider-light"></td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <hr class="divider-section">

        {{-- Totals Section --}}
        <section class="totals-section">
            <table class="totals-table">
                @if ($sale->discount_amount > 0)
                    <tr>
                        <td class="label">SUBTOTAL</td>
                        <td class="value">{{ number_format($sale->subtotal, 0, '.', ',') }}</td>
                    </tr>

                    <tr>
                        <td class="label">
                            DISCOUNT
                            @if ($sale->discount_type == 'percentage')
                                ({{ $sale->discount_amount }}%)
                            @else
                                (RS)
                            @endif
                        </td>
                        <td class="value discount-value">
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
                        <td class="label">SHIPPING</td>
                        <td class="value">{{ number_format($sale->shipping_charges, 0, '.', ',') }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="label">TOTAL</td>
                    <td class="value">{{ number_format($sale->final_total, 0, '.', ',') }}</td>
                </tr>

                {{-- Only show payment details for final sales --}}
                @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
                    @if (!is_null($amount_given) && $amount_given > 0)
                        <tr>
                            <td class="label">AMOUNT GIVEN</td>
                            <td class="value">{{ number_format($amount_given, 0, '.', ',') }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="label">PAID</td>
                        <td class="value">{{ number_format($sale->total_paid, 0, '.', ',') }}</td>
                    </tr>

                    @if (!is_null($balance_amount) && $balance_amount > 0)
                        <tr>
                            <td class="label">BALANCE GIVEN</td>
                            <td class="value">{{ number_format($balance_amount, 0, '.', ',') }}</td>
                        </tr>
                    @endif

                    @if (!is_null($sale->total_due) && $sale->total_due > 0)
                        <tr>
                            <td class="label">BALANCE DUE</td>
                            <td class="value">({{ number_format($sale->total_due, 0, '.', ',') }})</td>
                        </tr>
                    @endif

                    {{-- Total Outstanding Balance --}}
                    @php
                        $customer_outstanding = $customer ? \App\Helpers\BalanceHelper::getCustomerBalance($customer->id) : 0;
                    @endphp
                    @if ($customer && $customer_outstanding > 0)
                        <tr>
                            <td colspan="2"><hr class="divider"></td>
                        </tr>
                        <tr>
                            <td class="label">TOTAL OUTSTANDING DUE</td>
                            <td class="value outstanding-due">RS {{ number_format($customer_outstanding, 0, '.', ',') }}</td>
                        </tr>
                    @endif
                @endif
            </table>

            @php
                $total_discount = $products->sum(function ($product) {
                    $mrp = $product->product->max_retail_price ?? 0;
                    $price = $product->price;
                    if ($mrp > 0 && $mrp > $price) {
                        return ($mrp - $price) * $product->quantity;
                    }
                    return 0;
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
        </section>

        <hr class="divider-section">

        {{-- Stats Section --}}
        <section class="stats-section">
            <div class="stat-box">
                <span class="stat-number">{{ count($products) }}</span>
                <span class="stat-label">TOTAL ITEMS</span>
            </div>
            <div class="stat-box">
                <span class="stat-number">{{ $products->sum('quantity') }}</span>
                <span class="stat-label">TOTAL QTY</span>
            </div>
            <div class="stat-box">
                <span class="stat-number">{{ number_format($total_all_discounts, 0, '.', ',') }}</span>
                <span class="stat-label">TOTAL DISCOUNT</span>
            </div>
        </section>

        {{-- Payment Method / Status --}}
        @if (!in_array($sale->status, ['quotation', 'draft']) && (!isset($sale->transaction_type) || $sale->transaction_type !== 'sale_order'))
            @if ($payments->count() > 0)
                <hr class="divider-section">
                <div class="payment-method">
                    <p><strong>PAYMENT METHOD:</strong>
                        @if ($payments->count() > 1)
                            Multiple<br>
                            <span class="multiple-payments">({{ $payments->pluck('payment_method')->join(', ') }})</span>
                        @else
                            {{ $payments->first()->payment_method ?? 'N/A' }}
                        @endif
                    </p>
                </div>
            @endif
        @else
            <hr class="divider-section">
            <div class="status-message">
                @if ($sale->status === 'quotation')
                    <p>*** QUOTATION - PRICE ESTIMATE ONLY ***</p>
                @elseif ($sale->status === 'draft')
                    <p>*** DRAFT - PRICE ESTIMATE ONLY ***</p>
                @elseif (isset($sale->transaction_type) && $sale->transaction_type === 'sale_order')
                    <p>*** SALE ORDER - CONFIRMED ORDER ***</p>
                @endif
            </div>
        @endif

        {{-- Sale Notes --}}
        @if ($sale->sale_notes)
            <hr class="divider-section">
            <div class="sale-notes">
                <strong>NOTES:</strong>
                {{ $sale->sale_notes }}
            </div>
        @endif

        {{-- Footer --}}
        @if ($location && $location->footer_note)
            <hr class="divider-section">
            <div class="footer-note">{{ $location->footer_note }}</div>
        @endif

        <hr class="divider-section">

        <div class="software-info">
            SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK<br>
            Tel: +94 70 123 0959
        </div>

    </div>
</body>
</html>
