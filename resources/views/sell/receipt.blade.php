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
            font-size: 16px !important;
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
            padding: 5px !important;
            font-size: 14px !important;
        }
        .text-end {
            text-align: right !important;
        }
        hr {
            border: 0 !important;
            border-top: 1px dashed #000 !important;
            margin: 5px 0 !important;
            width: 100% !important;
            display: block !important;
            opacity: 0.6 !important;
        }
        .attribute {
            font-size: 16px !important;
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
    }
</style>
</head>

<body class="billBody" style="font-family: Arial, sans-serif; font-size: 14px;">

<div id="printArea">
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="/assets/img/ARB Logo.png" alt="Company Logo" class="logo">
    </div>
    <h2 class="receipt-title">ARB Distribution</h2>
    <div class="billAddress" style="font-size: 14px; color: #000;">
        Address: {{ Auth::user()->location->address }}<br>
        Phone: {{ Auth::user()->location->mobile }}<br>
        Email: {{ Auth::user()->location->email }}
    </div>

    <div style="font-size: 14px; margin-bottom: 5px; border-bottom: 1px dashed #000; border-top: 1px dashed #000; color: #000;">
        <table width="100%" border="0">
          <tbody>
            <tr>
              <td>
                <div style="font-size: 16px; color: #000;">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                <div style="font-size: 14px; color: #000;">{{ date('d-m-Y h:i A', strtotime($sale->sales_date)) }}</div>
              </td>
              <td>&nbsp;</td>
              <td width="120" align="center">
                    <div style="font-size: 16px; color: #000;">{{ $sale->invoice_no }}</div>
                    <div style="font-size: 12px; color: #000;">{{ Auth::user()->role_name }}</div>
              </td>
            </tr>
          </tbody>
        </table>
    </div>

    <table width="100%" border="0" style="color: #000;">
      <tbody>
        <tr>
          <th width="20" align="left" valign="top" scope="col">#</th>
          <th align="left" valign="top" scope="col">Items</th>
          <th align="right" valign="top" scope="col"><span style="display:table;">Rate × Qty</span></th>
          <th width="70" align="right" valign="top" scope="col"><span style="display:table;">Amt</span></th>
        </tr>
        <tr>
          <th colspan="4" align="left" valign="top" scope="col"><hr style="margin: 5px 0; border-top-style: dashed; border-width: 1px;"></th>
        </tr>
        @foreach ($products as $index => $product)
        <tr>
          <td width="20" valign="top">{{ $index + 1 }}</td>
          <td colspan="3" valign="top">{{ $product->product->product_name }}
            @if ($product->price_type == 'retail')
                <span>*</span>
            @elseif($product->price_type == 'wholesale')
                <span>**</span>
            @elseif($product->price_type == 'special')
                <span>***</span>
            @endif
          </td>
        </tr>
        <tr style="display: table-row">
          <td width="20" valign="top">&nbsp;</td>
          <td valign="top">
            <span style="text-decoration: line-through">{{ number_format($product->product->max_retail_price, 2) }}</span> ({{ number_format($product->product->max_retail_price - $product->price, 2) }})
          </td>
          <td align="right" valign="top">
            <span>{{ number_format($product->price, 2) }}</span> × {{ $product->quantity }}pcs
          </td>
          <td width="70" align="right" valign="top">
            <span>{{ number_format($product->price * $product->quantity, 2) }}</span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div style="margin: 5px 0; border-top-style: dashed; border-width: 1px; border-color: #000;"></div>

    <div style="position: relative">
        <table width="100%" border="0" style="color: #000;">
          <tbody>
            <tr>
              <td align="right"><strong>TOTAL</strong></td>
              <td width="80" align="right"><strong>{{ number_format($sale->final_total, 2) }}</strong></td>
            </tr>
            <tr>
              <td align="right"><strong>PAID</strong></td>
              <td width="80" align="right"><strong>{{ number_format($sale->total_paid, 2) }}</strong></td>
            </tr>
            <tr>
              <td align="right"><strong>BALANCE</strong></td>
              <td width="80" align="right"><strong>{{ number_format($sale->total_due, 2) }}</strong></td>
            </tr>
          </tbody>
        </table>

        <div style="position: absolute; left: 0; bottom: 0; display: block;">
            <div style="border-right: 1px dashed #000; padding-right: 10px; padding-bottom: 5px; font-size: 12px; color: #000;">
                <strong>Total Items: {{ count($products) }}</strong><br>
                <strong>Total Qty: {{ $products->sum('quantity') }}</strong>
            </div>
        </div>
    </div>

    <hr style="margin: 5px 0; border-top-style: dashed; border-width: 1px;">

    @php
            $total_discount = $products->sum(function($product) {
                return ($product->product->max_retail_price - $product->price) * ($product->quantity > 1 ? 1 : $product->quantity);
            });
        @endphp

    <div style="font-size: 18px; display: block; text-align: center; color: #000;">
        Total Discount : Rs. {{ number_format($total_discount, 2) }}
    </div>
    <hr>
    <div class="billFooter" style="padding-bottom: 4px;"></div>

    <div class="attribute" style="font-size: 16px; color: #000; font-weight: bolder !important;">
        Software: Marzin Pvt.Ltd | www.marazin.lk
    </div>
</div>

</body>
</html>
