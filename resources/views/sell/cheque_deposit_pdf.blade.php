<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cheque deposit {{ $printedAt }}</title>
    <style>
        /* A4 portrait — content width ~186mm @ 11mm side margins */
        @page { margin: 9mm 11mm 10mm 11mm; }
        * { box-sizing: border-box; }
        html, body { width: 100%; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 7px;
            color: #111;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        .hdr {
            width: 100%;
            border-bottom: 1.5px solid #1e3a5f;
            padding-bottom: 6px;
            margin-bottom: 7px;
        }
        .hdr-co {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 4px 0;
            padding: 0;
            line-height: 1.2;
            word-wrap: break-word;
        }
        .hdr-row {
            width: 100%;
            border-collapse: collapse;
        }
        .hdr-row td { vertical-align: middle; padding: 0; }
        .hdr-title {
            font-size: 9.5px;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .hdr-meta {
            font-size: 6.5px;
            color: #444;
            text-align: right;
            line-height: 1.35;
        }

        .sum {
            width: 100%;
            margin-bottom: 6px;
            padding: 5px 7px;
            background: #f4f6f8;
            border: 1px solid #dde1e6;
            font-size: 7px;
            line-height: 1.35;
            word-wrap: break-word;
        }
        .sum strong { color: #1e40af; }

        .t {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .t th {
            background: #e8ecf0;
            font-size: 6.2px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            padding: 4px 3px;
            border: 1px solid #b8c0cc;
            text-align: left;
            vertical-align: bottom;
        }
        .t th.r, .t td.r { text-align: right; }
        .t td {
            padding: 3px 3px;
            border: 1px solid #cfd6df;
            font-size: 6.5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .t td.inv {
            font-size: 6.2px;
            line-height: 1.3;
        }
        .t td.bank {
            font-size: 6.3px;
            line-height: 1.25;
        }
        tr.gb td {
            background: #e8ecff;
            border-color: #a8b8e0;
            font-weight: 700;
            font-size: 7.5px;
            color: #0f172a;
            padding: 5px 4px;
            line-height: 1.4;
        }
        /* Phone + chq count — readable black (not small blue) */
        .gb-sub {
            font-weight: 600;
            color: #111;
            font-size: 7px;
        }
        /* Per-customer cheque total — prominent black */
        .gb-total {
            font-weight: 800;
            color: #000;
            font-size: 9px;
            font-variant-numeric: tabular-nums;
        }
        .num { font-weight: 700; color: #1d4ed8; font-variant-numeric: tabular-nums; }
        .st {
            font-size: 5.8px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 1px 2px;
            white-space: nowrap;
        }
        .st-p { background: #fef3c7; color: #92400e; }
        .st-d { background: #dbeafe; color: #1e40af; }
        .st-c { background: #d1fae5; color: #065f46; }
        .st-b { background: #fee2e2; color: #991b1b; }
        .st-o { background: #f1f5f9; color: #475569; }
        .sig { margin-top: 8px; width: 100%; border-collapse: collapse; }
        .sig td { width: 33.33%; text-align: center; font-size: 6px; color: #555; padding: 0 3px; }
        .sig .ln { border-top: 1px solid #888; margin: 14px 6px 3px 6px; }
    </style>
</head>
<body>
    <div class="hdr">
        <p class="hdr-co">{{ $setting?->app_name ?? config('app.name') }}</p>
        <table class="hdr-row">
            <tr>
                <td style="width:55%;" class="hdr-title">Cheque deposit slip</td>
                <td style="width:45%;" class="hdr-meta">
                    Printed: {{ $printedAt }}<br>
                    By: {{ $preparedBy }}
                </td>
            </tr>
        </table>
    </div>

    <div class="sum">
        <strong>Parties:</strong> {{ $customerCount }}
        &nbsp;·&nbsp; <strong>Cheques:</strong> {{ $chequeCount }}
        &nbsp;·&nbsp; <strong>Total Rs.</strong> {{ number_format($grandTotal, 2) }}
    </div>

    {{-- Column % tuned for A4 portrait (narrow width); invoice column widest --}}
    <table class="t">
        <colgroup>
            <col style="width:8%;">
            <col style="width:15%;">
            <col style="width:9%;">
            <col style="width:9%;">
            <col style="width:11%;">
            <col style="width:40%;">
            <col style="width:8%;">
        </colgroup>
        <thead>
            <tr>
                <th>Chq no.</th>
                <th>Bank / branch</th>
                <th>Rcvd</th>
                <th>Valid</th>
                <th class="r">Amt (Rs.)</th>
                <th>Invoice / ref.</th>
                <th>Sts</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customerGroups as $cg)
                <tr class="gb">
                    <td colspan="7">
                        {{ $cg['party_label'] }}
                        @if(!empty($cg['party_phone']))
                            <span class="gb-sub"> · {{ $cg['party_phone'] }}</span>
                        @endif
                        <span class="gb-sub"> · {{ $cg['cheque_count'] }} chq · </span>
                        <span class="gb-total">Rs. {{ number_format($cg['subtotal'], 2) }}</span>
                    </td>
                </tr>
                @foreach($cg['rows'] as $row)
                    @php
                        $st = strtolower((string) ($row['status'] ?? ''));
                        $sc = 'st-o';
                        if ($st === 'pending') { $sc = 'st-p'; }
                        elseif ($st === 'deposited') { $sc = 'st-d'; }
                        elseif ($st === 'cleared') { $sc = 'st-c'; }
                        elseif ($st === 'bounced') { $sc = 'st-b'; }
                    @endphp
                    <tr>
                        <td class="num">{{ $row['cheque_number'] }}</td>
                        <td class="bank">{{ $row['bank_branch'] }}</td>
                        <td>{{ $row['received_date'] }}</td>
                        <td>{{ $row['valid_date'] }}</td>
                        <td class="r">{{ number_format($row['total_amount'], 2) }}</td>
                        <td class="inv">{{ $row['invoice_refs'] }}</td>
                        <td><span class="st {{ $sc }}">{{ $st }}</span></td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:700;border-top:1.5px solid #222;padding-top:4px;">Grand total Rs.</td>
                <td class="r" style="font-weight:700;border-top:1.5px solid #222;padding-top:4px;">{{ number_format($grandTotal, 2) }}</td>
                <td colspan="2" style="border-top:1.5px solid #222;"></td>
            </tr>
        </tfoot>
    </table>

    <table class="sig">
        <tr>
            <td><div class="ln"></div>Prepared</td>
            <td><div class="ln"></div>Checked</td>
            <td><div class="ln"></div>Bank</td>
        </tr>
    </table>
</body>
</html>
