<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>RECEIPT</title>
<style>
    @page {
        margin: 8px;
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
            vertical-align: top; /* Ensure consistent vertical alignment */
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
        .logo-container {
            text-align: center;
            font-weight: bold; /* Make text content bold */
        }
        .logo-container img {
            max-width: 100%;
            height: auto;
            width: 100%;
        }
        .billAddress div {
            margin-bottom: 4px; 
            text-align: center; /* Center align the content */
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

    <div class="logo-container" style="margin-top: 12px; margin-bottom: 12px;">
        <img src="{{ asset('assets/img/ARB_Fashion.png') }}" alt="ARB Distribution Logo" class="logo" />
    </div>
    <div class="billAddress" style="font-size: 12px; color: #000; margin-bottom: 12px;">
        <div> {{ Auth::user()->location->address }}</div>
        <div>{{ Auth::user()->location->mobile }}</div>
        <div>{{ Auth::user()->location->email }}</div>
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
                <th>#</th>
                <th>ITEMS</th>
                <th>RATE</th>
                <th>QTY</th>
                <th>AMOUNT</th>
            </tr>
            <tr>
                <th colspan="5">
                    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
                </th>
            </tr>
            @foreach ($products as $index => $product)
            <tr>
                <td>{{ $index + 1 }}</td>
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
                <td valign="top">&nbsp;</td>
                <td valign="top">
                    <span style="text-decoration: line-through">
                        {{ number_format($product->product->max_retail_price, 0, '.', ',') }}
                    </span>
                    ({{ number_format($product->product->max_retail_price - $product->price, 0, '.', ',') }})
                </td>
                <td align="left" valign="top">
                    <span>{{ number_format($product->price, 0, '.', ',') }}</span>
                </td>
                <td align="left" valign="top" class="quantity-with-pcs">
                    <span>&times; {{ $product->quantity }} pcs</span> <!-- Align "pcs" next to quantity -->
                </td>
                <td valign="top" align="right">
                    <span style="font-weight: bold;">{{ number_format($product->price * $product->quantity, 0, '.', ',') }}</span>
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
              <td width="80" align="right" style="font-weight: bold;">{{ number_format($sale->final_total, 0, '.', ',') }}</td>
            </tr>
            @if(!is_null($amount_given) && $amount_given > 0)
            <tr>
              <td align="right"><strong>AMOUNT GIVEN</strong></td>
              <td width="80" align="right">{{ number_format($amount_given, 0, '.', ',') }}</td>
            </tr>
            @endif
            <tr>
              <td align="right"><strong>PAID</strong></td>
              <td width="80" align="right">{{ number_format($sale->total_paid, 0, '.', ',') }}</td>
            </tr>
            @if(!is_null($balance_amount) && $balance_amount > 0)
            <tr>
              <td align="right"><strong>BALANCE GIVEN</strong></td>
              <td width="80" align="right">{{ number_format($balance_amount, 0, '.', ',') }}</td>
            </tr>
            @endif
            @if(!is_null($sale->total_due) && $sale->total_due > 0)
            <tr>
                <td align="right"><strong>BALANCE DUE</strong></td>
                <td width="80" align="right">
                    <div style="padding: 4px; display: inline-block; min-width: 60px; text-align: right;">
                        ({{ number_format($sale->total_due, 0, '.', ',') }})
                    </div>
                </td>
            </tr>
            @endif
          </tbody>
        </table>

        @php
        $total_discount = $products->sum(function($product) {
            return ($product->product->max_retail_price - $product->price) * ($product->quantity > 1 ? 1 : $product->quantity);
        });
      @endphp
    </div>
    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
        <div style="display: flex; text-align: center;">
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong>{{ count($products) }}</strong><br>
                TOTAL ITEMS
            </div>
            <div style="flex: 1; border-right: 2px dashed black; padding: 4px;">
                <strong>{{ $products->sum('quantity') }}</strong><br>
                TOTAL QTY
            </div>
            <div style="flex: 1; padding: 4px;">
                <strong>{{ number_format($total_discount, 0, '.', ',') }}</strong><br>
                TOTAL DISCOUNT
            </div>
        </div>


    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
    <div style="font-size: 12px; display: block; text-align: center; color: #000; margin-bottom: 8px;">
        <p><strong>PAYMENT METHOD:</strong> {{ $payments->first()->payment_method ?? 'N/A' }}</p>
    </div>

    <hr style="margin: 8px 0; border-top-style: dashed; border-width: 1px;">
    <div class="attribute" style="font-size: 12px; color: #000; font-weight: normal !important; text-align: center;">
        SOFTWARE: MARAZIN PVT.LTD | WWW.MARAZIN.LK | +94 70 123 0959
    </div>
</div>

</body>
</html>