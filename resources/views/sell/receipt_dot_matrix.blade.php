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

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white !important;
            }
            .invoice-page {
                margin: 0;
                box-shadow: none;
                page-break-after: avoid;
                page-break-inside: avoid;
                background: white !important;
            }
            @page {
                margin: 0;
                size: A4;
                background: white;
            }
            /* Hide any browser print headers/footers */
            html {
                background: white;
            }
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: white;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        .invoice-page {
            width: 100%;
            max-width: 8.5in;
            height: auto;
            min-height: 11in;
            background-color: white;
            padding: 0.2in 0;
            position: relative;
            margin: 0;
        }

        /* Clean design without perforation lines */
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
            margin-bottom: 0.25in;
            padding: 0 0.05in;
        }

        .company-info {
            flex: 1;
        }

        .company-logo {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .company-address {
            font-size: 9px;
            line-height: 1.35;
        }

        .customer-box {
            border: 2px solid #333;
            padding: 6px 12px;
            width: 3.8in;
            background-color: white;
        }

        .customer-line {
            display: flex;
            font-size: 9.5px;
            margin: 2.5px 0;
        }

        .customer-line label {
            width: 0.85in;
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
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 7px;
            margin: 0.2in 0 0.25in 0;
            padding: 0 0.1in;
        }

        .items-table {
            width: calc(100% - 0.1in);
            border-collapse: collapse;
            margin: 0 0.05in 0.2in 0.05in;
        }

        .items-table thead {
            background-color: white;
        }

        .items-table th {
            padding: 6px 8px;
            font-size: 12.5px;
            font-weight: bold;
            border-bottom: 1px solid #666;
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
            width: 0.95in;
        }

        .items-table td {
            padding: 5px 8px;
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
            width: 0.95in;
        }

        .summary-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.18in;
            margin-top: 0.22in;
            font-size: 9.5px;
            padding: 0 0.05in;
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
            font-size: 10.5px;
            margin-top: 3px;
        }

        .summary-row.total {
            font-weight: bold;
            font-size: 11.5px;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1.5px solid #666;
        }

        .footer-line {
            border-top: 1px solid #666;
            margin-top: 0.28in;
            padding: 0.12in 0.05in 0 0.05in;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }

        .software-info {
            text-align: center;
            font-size: 8.5px;
            margin-top: 0.1in;
            padding: 0 0.05in;
        }
    </style>
</head>
<body>
    <div class="invoice-page">
        <div class="perforation-top"></div>
        
        <div class="reg-no">Reg No: {{ $sale->invoice_no }}</div>
        
        <div class="header-section">
            <div class="company-info">
                <div class="company-logo">{{ strtoupper($location->name ?? 'HARDWARE STORE') }}</div>
                <div class="company-address">
                    @if ($location && $location->address)
                        {{ strtoupper($location->address) }}<br>
                    @endif
                    @if ($location && $location->mobile)
                        Mobile: {{ $location->mobile }}@if($location->email), Email: {{ $location->email }}@endif<br>
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
                    <span>: {{ $sale->invoice_no }}@if($sale->total_due > 0)<span class="type-credit">Type: Credit</span>@endif</span>
                </div>
            </div>
        </div>

        @if($sale->total_due <= 0)
            <div class="delivered-badge">Delivered</div>
        @endif

        <div class="invoice-title">INVOICE</div>

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
                @php $index = 1; @endphp
                @forelse ($products->groupBy(function($item) {
                    return $item->product_id . '-' . ($item->batch_id ?? '0');
                }) as $groupKey => $group)
                    @php
                        $firstProduct = $group->first();
                        $totalQuantity = $group->sum('quantity');
                        $totalAmount = $group->sum(fn($p) => $p->price * $p->quantity);
                        $productDiscount = $group->sum(fn($p) => ($p->product->max_retail_price - $p->price) * $p->quantity);
                        $unitPrice = $firstProduct->product->max_retail_price;
                        $rate = $firstProduct->price;
                    @endphp
                    <tr>
                        <td>{{ $index++ }}</td>
                        <td>{{ substr($firstProduct->product->product_name, 0, 45) }}@if($firstProduct->product->product_variation ?? false) ({{ substr($firstProduct->product->product_variation, 0, 10) }})@endif</td>
                        <td>{{ number_format($totalQuantity, 0) }}</td>
                        <td>{{ number_format($unitPrice, 2) }}</td>
                        <td>{{ number_format($productDiscount, 2) }}</td>
                        <td>{{ number_format($rate, 2) }}</td>
                        <td>{{ number_format($totalAmount, 2) }}</td>
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
                <div class="summary-row">
                    <span>Previous Outstanding:</span>
                    <span>0.00</span>
                </div>
            </div>

            <div class="summary-column">
                <div class="summary-row">
                    <span>Total Discounts:</span>
                    <span>{{ number_format($total_all_discounts, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span>Return Amount:</span>
                    <span>0.00</span>
                </div>
                <div class="summary-row">
                    <span>Amount Payable:</span>
                    <span>{{ number_format($sale->final_total, 2) }}</span>
                </div>
            </div>

            <div class="summary-column">
                <div class="summary-row">
                    <span>Discount:</span>
                    <span>{{ number_format($total_all_discounts, 2) }}</span>
                </div>
                <div class="summary-row bold">
                    <span>Bill Total:</span>
                    <span>{{ number_format($sale->final_total, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span>Amount Paid:</span>
                    <span>{{ number_format($amount_paid, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span>Balance:</span>
                    <span>{{ number_format($balance, 2) }}</span>
                </div>
                @if($sale->total_due > 0)
                    <div class="summary-row total">
                        <span>*Current Credit:</span>
                        <span>{{ number_format($sale->total_due, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="footer-line">
            <div>Prepared By: {{ strtoupper($user->user_name ?? $user->name ?? 'CASHIER') }}</div>
            <div>Checked By:</div>
            <div>Customer Acceptance:</div>
        </div>

        <div class="software-info">
            Software by Marazin Pvt.Ltd | 
            Payment: @if($payments && $payments->count() > 0){{ strtoupper($payments->first()->payment_method) }}@else CASH @endif | 
            Thank you for your business!
        </div>

        <div class="perforation-bottom"></div>
    </div>
</body>
</html>