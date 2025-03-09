<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARB Receipt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
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
        .receipt-title, .receipt-header, .d-flex, .table, .total-section, .receipt-footer {
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
        .table th, .table td {
            border-bottom: 1px solid #000;
            padding: 2px;
            text-align: left;
        }
        .text-end {
            text-align: right;
        }
        .print-btn {
            display: block;
            width: 100%;
            font-size: 12px;
            padding: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .print-btn:hover {
            background-color: #0056b3;
        }
        hr {
            border: 0;
            height: 1px;
            background: #000;
            margin: 5px 0;
            border-top: 1px dotted #000;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .receipt-box, .receipt-box * {
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
        <p class="receipt-header">123, Supermarket Street, City, Country</p>
        <p class="receipt-header">Phone: +94 123 456 789</p>
        <hr>
        <p class="d-flex"><span><strong>Date:</strong> {{ $sale->sales_date }}</span><span><strong>Invoice:</strong> {{ $sale->invoice_no }}</span></p>
        <p><strong>Customer:</strong> {{ $customer->first_name }} {{ $customer->last_name }}</p>
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
                @foreach($products as $index => $product)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $product->product->product_name }}
                        @if($product->price_type == 'retail') <span>*</span> 
                        @elseif($product->price_type == 'wholesale') <span>**</span>
                        @elseif($product->price_type == 'special') <span>***</span>
                        @endif
                        <br>
                        <del>{{ $product->price }}</del> ({{ $product->quantity }})
                    </td>
                    <td>{{ $product->price }} × {{ $product->quantity }}pcs</td>
                    <td>{{ $product->price * $product->quantity }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <div class="d-flex">
            <p><strong>Payment Method:</strong> {{ $payments->first()->payment_method ?? 'N/A' }}</p>
            <div class="text-end">
                <p><strong>Total:</strong> Rs. {{ $sale->final_total }}</p>
                <p><strong>Advance Paid:</strong> Rs. {{ $sale->total_paid }}</p>
                <p><strong>Balance:</strong> Rs. {{ $sale->total_due }}</p>
            </div>
        </div>
        <hr>
        <p class="total-section">Total Discount: Rs. {{ $total_discount }}</p>
        <hr>
        <p class="receipt-footer">Thank you for shopping with us!</p>
        <p class="receipt-footer">Visit again!</p>
        <p class="receipt-footer">Software: Marzin Pvt.Ltd | www.marazin.lk</p>
        <hr>
        <button class="print-btn" onclick="window.print()">Print This Bill</button>
    </div>
</body>
</html>