<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Report</title>
    <style>
        @page { margin: 30px 28px 45px 28px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #1a1a2e; margin: 0; padding: 0; line-height: 1.35; }

        /* HEADER */
        .hdr { width: 100%; margin-bottom: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .hdr table { width: 100%; border-collapse: collapse; }
        .hdr td { vertical-align: top; padding: 0; }
        .logo { display: inline-block; width: 34px; height: 34px; background: #1e293b; color: #fff; text-align: center; line-height: 34px; font-size: 17px; font-weight: 700; border-radius: 5px; float: left; margin-right: 10px; }
        .co-name { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0; padding-top: 1px; letter-spacing: 0.3px; }
        .co-div { font-size: 8.5px; color: #64748b; margin: 2px 0 0 0; letter-spacing: 0.3px; }
        .rpt-title { font-size: 15px; font-weight: 700; color: #0f172a; text-align: right; margin: 0; letter-spacing: 0.2px; }
        .rpt-meta { font-size: 8px; color: #64748b; text-align: right; margin-top: 5px; line-height: 1.5; }
        .rpt-meta strong { color: #475569; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* SUMMARY BAR */
        .sum-bar { width: 100%; margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
        .sum-bar table { width: 100%; border-collapse: collapse; }
        .sum-bar td { padding: 7px 10px; vertical-align: middle; border-right: 1px solid #e2e8f0; }
        .sum-bar td:last-child { border-right: none; }
        .sum-total-cell { background: #f8fafc; }
        .sum-lbl { font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #64748b; margin-bottom: 2px; }
        .sum-lbl-sales { color: #2563eb; }
        .sum-lbl-cost { color: #dc2626; }
        .sum-lbl-profit { color: #16a34a; }
        .sum-val { font-size: 14px; font-weight: 700; color: #0f172a; }
        .sum-val-profit { color: #16a34a; }
        .sum-val-loss { color: #dc2626; }
        .sum-mval { font-size: 10.5px; font-weight: 700; color: #334155; text-align: center; }
        .sum-pct { font-size: 7px; color: #94a3b8; font-weight: 600; }

        /* METRICS ROW */
        .metrics { width: 100%; margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
        .metrics table { width: 100%; border-collapse: collapse; }
        .metrics td { padding: 6px 10px; vertical-align: middle; border-right: 1px solid #e2e8f0; text-align: center; }
        .metrics td:last-child { border-right: none; }
        .met-lbl { font-size: 6.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; margin-bottom: 1px; }
        .met-val { font-size: 10px; font-weight: 700; color: #334155; }

        /* SECTION HEADERS */
        .sec-hdr { background: #f1f5f9; padding: 6px 12px; margin-top: 14px; margin-bottom: 0; border: 1px solid #e2e8f0; border-bottom: none; border-radius: 4px 4px 0 0; }
        .sec-hdr table { width: 100%; border-collapse: collapse; }
        .sec-hdr td { padding: 0; vertical-align: middle; }
        .sec-title { font-size: 10px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .sec-count { font-size: 8px; color: #64748b; text-align: right; }

        /* PROFIT/LOSS INDICATORS */
        .profit { color: #16a34a; font-weight: 700; }
        .loss { color: #dc2626; font-weight: 700; }

        /* P&L STATEMENT */
        .pl-stmt { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; margin-bottom: 12px; }
        .pl-stmt td { padding: 6px 12px; font-size: 9px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .pl-stmt .pl-label { font-weight: 600; width: 65%; }
        .pl-stmt .pl-amount { text-align: right; font-weight: 700; width: 35%; font-size: 10px; }
        .pl-stmt .pl-main { background: #f8fafc; }
        .pl-stmt .pl-sub { padding-left: 28px; font-size: 8.5px; color: #64748b; }
        .pl-stmt .pl-total { background: #f1f5f9; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1; }
        .pl-stmt .pl-total td { font-size: 10px; font-weight: 700; color: #0f172a; padding: 8px 12px; }
        .pl-stmt .pl-grand { background: #1e293b; }
        .pl-stmt .pl-grand td { color: #fff; font-size: 11px; font-weight: 700; padding: 8px 12px; }

        /* SIGNATURE */
        .sig { margin-top: 30px; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .sig table { width: 100%; border-collapse: collapse; }
        .sig td { width: 33.33%; text-align: center; padding: 0 14px; vertical-align: top; }
        .sig-line { border-top: 1px solid #64748b; margin-bottom: 5px; margin-top: 30px; }
        .sig-title { font-size: 8.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.3px; }
        .sig-sub { font-size: 7px; color: #94a3b8; margin-top: 1px; font-style: italic; }

        /* FOOTER */
        .pg-foot { margin-top: 20px; text-align: center; padding-top: 10px; }
        .pg-foot-txt { font-size: 7.5px; color: #64748b; letter-spacing: 0.8px; font-weight: 600; }
        .pg-foot-sub { font-size: 6.5px; color: #94a3b8; margin-top: 2px; }

        /* PAGE BREAK */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

@php
    $companyName = isset($setting) && $setting ? ($setting->app_name ?? 'ENTERPRISE ERP SYSTEMS') : 'ENTERPRISE ERP SYSTEMS';
    $companyDiv = $locationLabel ?? 'All Locations';
    $companyInitial = strtoupper(substr($companyName, 0, 1));
    $adminId = auth()->check() ? (auth()->user()->full_name ?? auth()->user()->name ?? 'N/A') : 'N/A';
    $dateFrom = \Carbon\Carbon::parse($filters['start_date'])->format('M d');
    $dateTo = \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y');
    $printedDate = \Carbon\Carbon::now()->format('M d, Y');
    $reportType = $filters['report_type'] ?? 'overall';

    $summary = $reportData['overall_summary'] ?? [];

    $totalSales = $summary['total_sales'] ?? 0;
    $totalCost = $summary['total_cost'] ?? 0;
    $grossProfit = $summary['gross_profit'] ?? 0;
    $profitMargin = $summary['profit_margin'] ?? 0;
    $netProfit = $summary['net_profit'] ?? 0;
    $netProfitMargin = $summary['net_profit_margin'] ?? 0;
    $totalTransactions = $summary['total_transactions'] ?? 0;
    $avgOrderValue = $summary['average_order_value'] ?? 0;
    $totalPaidQty = $summary['total_paid_quantity'] ?? 0;
    $totalFreeQty = $summary['total_free_quantity'] ?? 0;
    $totalReturns = $summary['total_returns'] ?? 0;
    $returnAmount = $summary['total_return_amount'] ?? 0;
    $returnPct = $summary['return_percentage'] ?? 0;
    $avgProfitPerOrder = $summary['average_profit_per_order'] ?? 0;

    $isProfit = $grossProfit >= 0;

    if (!function_exists('fmtAmt')) {
        function fmtAmt($a) { return number_format($a, 2); }
    }
    if (!function_exists('plClass')) {
        function plClass($v) { return $v >= 0 ? 'profit' : 'loss'; }
    }
@endphp

{{-- ===== HEADER ===== --}}
<div class="hdr">
    <table>
        <tr>
            <td style="width:50%;">
                <div class="logo">{{ $companyInitial }}</div>
                <div style="overflow:hidden;">
                    <p class="co-name">{{ strtoupper($companyName) }}</p>
                    <p class="co-div">PROFIT & LOSS STATEMENT</p>
                </div>
            </td>
            <td style="width:50%;">
                <p class="rpt-title">PROFIT & LOSS REPORT</p>
                <div class="rpt-meta">
                    <strong>Range:</strong> {{ $dateFrom }} - {{ $dateTo }} &nbsp;&nbsp;
                    <strong>Location:</strong> {{ $companyDiv }} &nbsp;&nbsp;
                    <strong>Admin:</strong> {{ $adminId }} &nbsp;&nbsp;
                    <strong>Printed:</strong> {{ $printedDate }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== FINANCIAL SUMMARY BAR ===== --}}
<div class="sum-bar">
    <table>
        <tr>
            <td class="sum-total-cell" style="width:25%;">
                <div class="sum-lbl sum-lbl-sales">TOTAL SALES (NET)</div>
                <div class="sum-val">Rs. {{ fmtAmt($totalSales) }}</div>
            </td>
            <td style="width:25%; text-align:center;">
                <div class="sum-lbl sum-lbl-cost">TOTAL COST (FIFO)</div>
                <div class="sum-mval">Rs. {{ fmtAmt($totalCost) }}</div>
            </td>
            <td style="width:25%; text-align:center;">
                <div class="sum-lbl sum-lbl-profit">GROSS PROFIT</div>
                <div class="sum-mval {{ $isProfit ? 'sum-val-profit' : 'sum-val-loss' }}">Rs. {{ fmtAmt($grossProfit) }}</div>
            </td>
            <td style="width:25%; text-align:center;">
                <div class="sum-lbl">PROFIT MARGIN</div>
                <div class="sum-mval {{ $isProfit ? 'sum-val-profit' : 'sum-val-loss' }}">{{ fmtAmt($profitMargin) }}%</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== KEY METRICS ROW ===== --}}
<div class="metrics">
    <table>
        <tr>
            <td style="width:14.28%;">
                <div class="met-lbl">TRANSACTIONS</div>
                <div class="met-val">{{ number_format($totalTransactions) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">AVG ORDER</div>
                <div class="met-val">Rs. {{ fmtAmt($avgOrderValue) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">PAID QTY</div>
                <div class="met-val">{{ number_format($totalPaidQty) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">FREE QTY</div>
                <div class="met-val">{{ number_format($totalFreeQty) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">RETURNS</div>
                <div class="met-val">{{ number_format($totalReturns) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">RETURN AMT</div>
                <div class="met-val" style="color:#dc2626;">{{ fmtAmt($returnAmount) }}</div>
            </td>
            <td style="width:14.28%;">
                <div class="met-lbl">AVG PROFIT/ORDER</div>
                <div class="met-val {{ plClass($avgProfitPerOrder) }}">{{ fmtAmt($avgProfitPerOrder) }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== P&L STATEMENT ===== --}}
<div class="sec-hdr">
    <table><tr>
        <td><span class="sec-title">Income Statement</span></td>
        <td style="text-align:right;"><span class="sec-count">{{ $dateFrom }} - {{ $dateTo }}</span></td>
    </tr></table>
</div>
<table class="pl-stmt">
    <tr class="pl-main">
        <td class="pl-label">Revenue / Total Sales</td>
        <td class="pl-amount">Rs. {{ fmtAmt($totalSales + $returnAmount) }}</td>
    </tr>
    <tr>
        <td class="pl-label pl-sub">Less: Sales Returns & Refunds</td>
        <td class="pl-amount" style="color:#dc2626;">(Rs. {{ fmtAmt($returnAmount) }})</td>
    </tr>
    <tr class="pl-total">
        <td class="pl-label">Net Sales</td>
        <td class="pl-amount">Rs. {{ fmtAmt($totalSales) }}</td>
    </tr>
    <tr class="pl-main">
        <td class="pl-label">Cost of Goods Sold (FIFO)</td>
        <td class="pl-amount">Rs. {{ fmtAmt($totalCost) }}</td>
    </tr>
    <tr class="pl-total">
        <td class="pl-label">Gross Profit</td>
        <td class="pl-amount {{ plClass($grossProfit) }}">Rs. {{ fmtAmt($grossProfit) }}</td>
    </tr>
    @if(($summary['total_expenses'] ?? 0) > 0)
    <tr class="pl-main">
        <td class="pl-label">Operating Expenses</td>
        <td class="pl-amount">Rs. {{ fmtAmt($summary['total_expenses']) }}</td>
    </tr>
    @endif
    <tr class="pl-grand">
        <td class="pl-label">NET {{ $isProfit ? 'PROFIT' : 'LOSS' }} &nbsp; <span style="font-size:8px; opacity:0.7;">({{ fmtAmt($netProfitMargin) }}% margin)</span></td>
        <td class="pl-amount">Rs. {{ fmtAmt(abs($netProfit)) }}</td>
    </tr>
</table>


{{-- ===== SIGNATURE SECTION ===== --}}
<div class="sig">
    <table>
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">ACCOUNTANT SIGNATURE</div>
                <div class="sig-sub">Prepared By: {{ $adminId }}</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">MANAGEMENT AUTHORIZATION</div>
                <div class="sig-sub">Seal & Signature</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">SYSTEM VERIFICATION</div>
                <div class="sig-sub">Auth Code: {{ substr(md5(date('YmdHis')), 0, 5) }}-PNL-RPT</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== PAGE FOOTER ===== --}}
<div class="pg-foot">
    <div class="pg-foot-txt">
        --- END OF PROFIT & LOSS REPORT ---
    </div>
    <div class="pg-foot-sub">
        Generated on {{ \Carbon\Carbon::now()->format('M d, Y H:i:s') }} | FIFO Cost Method | All amounts in Rs.
    </div>
</div>

</body>
</html>
