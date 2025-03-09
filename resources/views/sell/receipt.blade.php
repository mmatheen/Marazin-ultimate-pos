<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARB Receipt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    {{-- <style>
        body {
            background: #f8f9fa;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .receipt-box {
            width: 80mm;
            border: 1px solid #000;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
        }

        .receipt-title,
        .receipt-header,
        .d-flex,
        .table,
        .total-section,
        .receipt-footer {
            font-size: 12px;
            margin: 0;
            padding: 2px 0;
            width: 100%;
            text-align: center;
        }

        .d-flex {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border-bottom: 1px solid #000;
            padding: 2px;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        hr {
            border: 0;
            border-top: 1px solid #000;
            margin: 5px 0;
            width: 100%;
            display: block;
            opacity: 0.3;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .receipt-box,
            .receipt-box * {
                visibility: visible;
            }

            .receipt-box {
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 80mm;
                border: none;
                overflow: hidden;
            }

            .print-btn {
                display: none;
            }
        }
    </style> --}}
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
    
        .receipt-box {
            width: 80mm;
            border: 1px solid #000;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
        }
    
        .receipt-title,
        .receipt-header,
        .d-flex,
        .table,
        .total-section,
        .receipt-footer {
            font-size: 12px;
            margin: 0;
            padding: 2px 0;
            width: 100%;
            text-align: center;
        }
    
        .d-flex {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
    
        .table {
            width: 100%;
            border-collapse: collapse;
        }
    
        .table th,
        .table td {
            padding: 2px;
            text-align: left;
        }
    
        .text-end {
            text-align: right;
        }
    
        hr {
            border: 0;
            border-top: 1px dashed #000;
            margin: 5px 0;
            width: 100%;
            display: block;
            opacity: 0.3;
        }
    
        @media print {
            body * {
                visibility: hidden;
            }
    
            .receipt-box,
            .receipt-box * {
                visibility: visible;
            }
    
            .receipt-box {
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%);
                width: 80mm;
                border: none;
                overflow: hidden;
            }
    
            .print-btn {
                display: none;
            }
        }
    </style>
    
</head>

<body>
    <div class="receipt-box">
        <h2 class="receipt-title">ARB Distribution</h2>
        <hr>
        <p class="receipt-header" style="line-height: 1.5;">
            {{ Auth::user()->location->address }} <br>
            {{ Auth::user()->location->city }} <br>
            {{ Auth::user()->location->email }}
        </p>
        <p class="receipt-header">Phone: {{ Auth::user()->location->mobile }}</p>
        <hr>
        <p class="d-flex justify-content-between">
            <span><strong>Date:</strong> {{ $sale->sales_date }}</span>
            <span><strong>Invoice: </strong> {{ $sale->invoice_no }}</span>
        </p>
        <hr>
        <p style="font-size: 12px; margin-top: 5px;"><strong>Customer:</strong> {{ $customer->first_name }}
            {{ $customer->last_name }}</p>
        <hr>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Items</th>
                    <th>Rate × Qty</th>
                    <th>Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td colspan="3">
                            {{ $product->product->product_name }}
                            @if ($product->price_type == 'retail')
                                <span>*</span>
                            @elseif($product->price_type == 'wholesale')
                                <span>**</span>
                            @elseif($product->price_type == 'special')
                                <span>***</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <del>{{ number_format($product->product->max_retail_price, 2) }}</del> ({{ $product->quantity }})
                        </td>
                        <td>{{ number_format($product->price, 2) }} × {{ $product->quantity }}pcs</td>
                        <td>{{ number_format($product->price * $product->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        

        <div class="d-flex justify-content-between mt-2" style="font-size: 12px;">
            <div style="font-size: 12px;">
                <p><strong>Payment Method:</strong> {{ $payments->first()->payment_method ?? 'N/A' }}</p>
            </div>
            <div class="text-end">
                <table class="table table-sm" style="border: none;">
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td>Rs. {{ number_format($sale->final_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Advance Paid:</strong></td>
                        <td>Rs. {{ number_format($sale->total_paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Balance:</strong></td>
                        <td>Rs. {{ number_format($sale->total_due, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <hr>
        <p class="total-section" style="font-size: 14px;">Total Discount: Rs. {{ number_format($total_discount, 2) }}</p>
        <hr>
        <p class="receipt-footer">Thank you for shopping with us!</p>
        <p class="receipt-footer">Visit again!</p>
        <p class="receipt-footer">Software: Marzin Pvt.Ltd | www.marazin.lk</p>
    </div>
</body>

</html>
