<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .header, .footer {
            text-align: right;
            font-weight: bold;
        }
        .totals {
            text-align: right;
        }
        @media print {
    body * {
        visibility: hidden;
    }

    #invoicePrint, #invoicePrint * {
        visibility: visible;
    }

    #invoicePrint {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 10px;
    }

    table {
        width: 100%;
        page-break-inside: auto;
    }

    /* Prevent page breaks inside tables */
    tr, td, th {
        page-break-inside: avoid;
    }

    /* Ensure the content fits on one page */
    body {
        margin: 5;
        padding: 10;
    }

    /* Optional: Limit the content to fit within one page */
    @page {
        size: A4; /* Or 'letter' based on your preference */
        margin: 0;
    }
}


    </style>
</head>
<body>

    <div id="invoicePrint">
        <h2>Invoice</h2>
        <p><strong>Invoice No:</strong> {{ $invoice->invoice_no }}</p>
        <p><strong>Customer:</strong> {{ $customer->full_name }}</p>
        <p><strong>Date:</strong> {{ $invoice->created_at->format('Y-m-d') }}</p>
        <hr>

        <h3>Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Free Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td><img src="{{ asset('assets/images/' . $item->product->product_image) }}" alt="Product Image" width="50"></td>
                    <td>{{ $item->product->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->free_quantity ?? 0 }}</td>
                    <td>{{ $item->unit_price }}</td>
                    <td>{{ $item->subtotal }}</td>
                </tr>
            @endforeach

            </tbody>
        </table>

        <hr>

        <h3>Payment</h3>
        <p><strong>Payment Method:</strong> {{ $payment_mode }}</p>
        <div class="totals">
            <p><strong>Subtotal:</strong> {{ $amount }}</p>
            <p><strong>Total:</strong> {{ $amount }}</p>
        </div>
    </div>

</body>
</html>
