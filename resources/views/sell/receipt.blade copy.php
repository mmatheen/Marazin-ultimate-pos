<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt</title>
<style>
    @page {
        margin: 10px;
    }
    @media print {
        * {
            visibility: hidden !important;
        }
        #printArea, #printArea * {
            visibility: visible !important;
        }
        #printArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            font-size: 12px !important;
            font-family: Arial, sans-serif !important;
        }
        .receipt-title, .receipt-header, .d-flex, .table, .total-section, .receipt-footer {
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
        .table th, .table td {
            padding: 6px !important;
            font-size: 10px !important;
            font-weight: bold !important;
        }
        .table th {
            font-weight: bold !important;
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
            font-size: 12px !important;
            color: #000 !important;
            font-weight: normal !important;
        }
        .printArea .logo {
            max-width: 100%;
            height: auto;
            image-rendering: -webkit-optimize-contrast; /* Enhance image rendering */
            image-rendering: crisp-edges; /* For better clarity on some browsers */
            image-rendering: pixelated; /* For better clarity on some browsers */
            filter: contrast(120%); /* Increase contrast for better boldness */
            filter: brightness(110%); /* Increase brightness for better clarity */
        }
         /* Adjustments for thermal printer */
         .logo-container {
            text-align: center;
            margin-bottom: 8px;
        }
        .logo-container img {
            max-width: 100%;
            height: auto;
            width: 120px; /* Adjusted for thermal printer width */
        }
    }
</style>
</head>

<body class="billBody" style="font-family: Arial, sans-serif; font-size: 12px; padding: 8px;">

<div id="printArea">

    <div class="logo-container">
        <img src="{{ asset('assets/img/ARB Logo.png') }}" alt="ARB Distribution Logo" class="logo" />
    </div>

    <h2 class="receipt-title" style="font-size: 18px; margin-bottom: 8px;">ARB Fashion</h2>

    <div class="billAddress" style="font-size: 12px; color: #000; margin-bottom: 12px;">
        <div>Address: {{ Auth::user()->location->address }}</div>
        <div>Phone: {{ Auth::user()->location->mobile }}</div>
        <div>Email: {{ Auth::user()->location->email }}</div>
    </div>

    <div style="font-size: 12px; margin-bottom: 8px; border-bottom: 1px dashed #000; border-top: 1px dashed #000; color: #000;">
        <table width="100%" border="0">
          <tbody>
            <tr>
              <td>
                <div style="font-size: 12px; color: #000; margin-bottom: 4px;">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                <div style="font-size: 10px; color: #000;">
                    [{{ date('d-m-Y ', strtotime($sale->sales_date)) }}]
                    {{ \Carbon\Carbon::now('Asia/Colombo')->format('h:i A') }}
                    </div>

              </td>
              <td>&nbsp;</td>
              <td width="120" align="center">
                    <div style="font-size: 12px; color: #000;">{{ $sale->invoice_no }}</div>
                    <div style="font-size: 10px; color: #000;">({{ Auth::user()->name }})</div>
                </td>
            </tr>
          </tbody>
        </table>
    </div>

    <table width="100%" border="0" style="color: #000; margin-bottom: 12px;">
        <tbody>
            <tr>
                <th width="20" align="left" valign="top" scope="col">#</th>
                <th align="left" valign="top" scope="col">Items</th>
                <th align="left" valign="top" scope="col">Rate</th>
                <th align="center" valign="top" scope="col">Qty</th>
                <th width="80" align="right" valign="top" scope="col">Amount</th>
            </tr>
            <tr>
                <th colspan="5" align="left" valign="top" scope="col">
                    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
                </th>
            </tr>
            @foreach ($products as $index => $product)
            <tr>
                <td width="20" valign="top">{{ $index + 1 }}</td>
                <td colspan="4" valign="top">
                    {{ $product->product->product_name }}
                    @if ($product->price_type == 'retail')
                        <span style="font-weight: bold;">*</span>
                    @elseif($product->price_type == 'wholesale')
                        <span style="font-weight: bold;">**</span>
                    @elseif($product->price_type == 'special')
                        <span style="font-weight: bold;">***</span>
                    @endif
                </td>
            </tr>

            <tr>
                <td width="20" valign="top">&nbsp;</td>
                <td valign="top">
                    <span style="text-decoration: line-through">
                        {{ number_format($product->product->max_retail_price, 0, '.', ',') }}
                    </span>
                    ({{ number_format($product->product->max_retail_price - $product->price, 0, '.', ',') }})
                </td>
                <td align="left" valign="top">
                    <span>{{ number_format($product->price, 0, '.', ',') }}</span>
                </td>
                <td align="center" valign="top">
                    <span>&times; {{ $product->quantity }} pcs</span>
                </td>
                <td width="80" align="right" valign="top">
                    <span>{{ number_format($product->price * $product->quantity, 0, '.', ',') }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin: 8px 0; border-top-style: dashed; border-width: 1px; border-color: #000;"></div>

    <div style="position: relative; margin-bottom: 12px;">
        <table width="100%" border="0" style="color: #000;">
          <tbody>
            <tr>
              <td align="right"><strong>TOTAL</strong></td>
              <td width="80" align="right">{{ number_format($sale->final_total, 0, '.', ',') }}</td>
            </tr>
            <tr>
              <td align="right"><strong>PAID</strong></td>
              <td width="80" align="right">{{ number_format($sale->total_paid, 0, '.', ',') }}</td>
            </tr>
            <tr>
              <td align="right"><strong>BALANCE</strong></td>
              <td width="80" align="right">
                <div style="border: 0.5px solid #000; padding: 4px; display: inline-block; min-width: 60px; text-align: right;">
                    {{ number_format($sale->total_due, 0, '.', ',') }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div style="position: absolute; left: 0; bottom: 0; display: block;">
            <div style="border-right: 1px dashed #000; padding-right: 8px; padding-bottom: 4px; font-size: 10px; color: #000;">
                <strong>Total Items: {{ count($products) }}</strong><br>
                <strong>Total Qty: {{ $products->sum('quantity') }}</strong>
            </div>
        </div>
    </div>

    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
    <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
        <p><strong>Payment Method:</strong> {{ $payments->first()->payment_method ?? 'N/A' }}</p>
    </div>

    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">

    @php
        $total_discount = $products->sum(function($product) {
            return ($product->product->max_retail_price - $product->price) * ($product->quantity > 1 ? 1 : $product->quantity);
        });
    @endphp

    <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
        Total Discount : Rs. {{ number_format($total_discount, 0, '.', ',') }}
    </div>

    <hr style="margin-top: 8px; border-top-style: dashed; border-width: 1px;">

    <div class="attribute" style="font-size: 12px; color: #000; font-weight: normal !important; text-align: center;">
        Software: Marazin Pvt.Ltd | www.marazin.lk | +94 75 757 1411
    </div>
</div>

</body>
</html>
