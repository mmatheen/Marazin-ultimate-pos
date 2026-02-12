<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Collection Receipt Summary</title>
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
        .sum-lbl-main { color: #2563eb; }
        .sum-val { font-size: 14px; font-weight: 700; color: #0f172a; }
        .sum-mval { font-size: 10.5px; font-weight: 700; color: #334155; text-align: center; }
        .sum-pct { font-size: 7px; color: #94a3b8; font-weight: 600; }

        /* COLLECTION CARD */
        .coll { border: 1px solid #e2e8f0; border-radius: 5px; margin-bottom: 10px; overflow: hidden; page-break-inside: avoid; }
        .coll-hdr { padding: 7px 12px; border-bottom: 1px solid #e9ecef; }
        .coll-hdr table { width: 100%; border-collapse: collapse; }
        .coll-hdr td { padding: 0; vertical-align: middle; }
        .coll-ref { font-size: 12px; font-weight: 700; color: #0f172a; }
        .coll-cust { font-size: 9px; color: #475569; display: inline; margin-left: 8px; }
        .coll-date { font-size: 9px; color: #64748b; display: inline; margin-left: 6px; }
        .coll-loc { font-size: 8.5px; color: #16a34a; display: inline; margin-left: 6px; }
        .coll-loc:before { content: "● "; font-size: 6px; }
        .coll-amt { font-size: 14px; font-weight: 700; color: #16a34a; text-align: right; }

        /* METHOD SUB-HEADER */
        .meth-hdr { background: #f9fafb; padding: 4px 12px; border-bottom: 1px solid #f1f5f9; }
        .meth-hdr table { width: 100%; border-collapse: collapse; }
        .meth-hdr td { padding: 0; vertical-align: middle; }
        .meth-label { font-size: 8px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
        .meth-count { font-size: 7.5px; color: #94a3b8; text-align: right; }
        .meth-subtotal { font-size: 8px; font-weight: 600; text-align: right; }
        .meth-subtotal-cheque { color: #ea580c; }
        .meth-subtotal-cash { color: #16a34a; }
        .meth-subtotal-card { color: #9333ea; }
        .meth-subtotal-bank { color: #0891b2; }

        /* PAYMENT ROWS */
        .pay-row { padding: 3px 12px 3px 20px; border-bottom: 1px solid #f8f9fa; }
        .pay-row table { width: 100%; border-collapse: collapse; }
        .pay-row td { padding: 2px 0; vertical-align: middle; font-size: 8.5px; color: #334155; }
        .pay-ref { font-weight: 600; color: #1e293b; }
        .pay-bank { color: #64748b; }
        .pay-due { color: #64748b; }
        .pay-amt { font-weight: 700; color: #0f172a; text-align: right; font-size: 9px; }

        /* STATUS BADGES */
        .st { padding: 1.5px 6px; border-radius: 3px; font-size: 6.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
        .st-pending { background: #fef3c7; color: #92400e; }
        .st-cleared { background: #d1fae5; color: #065f46; }
        .st-settled { background: #d1fae5; color: #065f46; }
        .st-bounced { background: #fee2e2; color: #991b1b; }

        /* CONTINUE NOTE */
        .cont-note { text-align: center; padding: 14px 0; font-size: 8px; color: #94a3b8; letter-spacing: 2px; font-style: italic; }

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
    </style>
</head>
<body>

@php
    $companyName = isset($mainLocation) && $mainLocation ? ($mainLocation->name ?? 'ENTERPRISE ERP SYSTEMS') : 'ENTERPRISE ERP SYSTEMS';
    $companyDiv = isset($mainLocation) && $mainLocation ? ($mainLocation->address ?? 'Commercial Division • Regional HQ') : 'Commercial Division • Regional HQ';
    $companyInitial = strtoupper(substr($companyName, 0, 1));
    $adminId = auth()->check() ? (auth()->user()->id ?? 'N/A') : 'N/A';
    $dateFrom = isset($request) && $request->start_date ? \Carbon\Carbon::parse($request->start_date)->format('M d') : 'All';
    $dateTo = isset($request) && $request->end_date ? \Carbon\Carbon::parse($request->end_date)->format('M d, Y') : 'All';
    $dateYear = isset($request) && $request->start_date ? \Carbon\Carbon::parse($request->start_date)->format(', Y') : '';
    $printedDate = \Carbon\Carbon::now()->format('M d, Y');
    $totalAmt = $summaryData['total_amount'] ?? 0;
    $cashT = $summaryData['cash_total'] ?? 0;
    $chequeT = $summaryData['cheque_total'] ?? 0;
    $cardT = $summaryData['card_total'] ?? 0;
    $bankT = $summaryData['bank_transfer_total'] ?? 0;
    $cCash = isset($paymentCounts) ? ($paymentCounts['cash'] ?? 0) : 0;
    $cCheque = isset($paymentCounts) ? ($paymentCounts['cheque'] ?? 0) : 0;
    $cCard = isset($paymentCounts) ? ($paymentCounts['card'] ?? 0) : 0;
    $cTransfer = isset($paymentCounts) ? ($paymentCounts['bank_transfer'] ?? 0) : 0;
    $pCash = $totalAmt > 0 ? round(($cashT / $totalAmt) * 100) : 0;
    $pCheque = $totalAmt > 0 ? round(($chequeT / $totalAmt) * 100) : 0;
    $pCard = $totalAmt > 0 ? round(($cardT / $totalAmt) * 100) : 0;
    $pTransfer = $totalAmt > 0 ? round(($bankT / $totalAmt) * 100) : 0;

    if (!function_exists('fmtShort')) {
        function fmtShort($a) {
            return number_format($a, 2);
        }
    }

    // Format date to Y-m-d only (strip time)
    if (!function_exists('fmtDate')) {
        function fmtDate($d) {
            if (empty($d)) return '';
            try {
                return \Carbon\Carbon::parse($d)->format('Y-m-d');
            } catch (\Exception $e) {
                // If parsing fails, try to strip time manually
                return substr(trim($d), 0, 10);
            }
        }
    }

    $collList = isset($collections) ? $collections : [];
    $totalColls = count($collList);

    // Items per page for high-density mode
    $maxPerPage = 50;
    $shownCount = 0;
@endphp

{{-- ===== HEADER ===== --}}
<div class="hdr">
    <table>
        <tr>
            <td style="width:50%;">
                <div class="logo">{{ $companyInitial }}</div>
                <div style="overflow:hidden;">
                    <p class="co-name">{{ strtoupper($companyName) }}</p>
                    <p class="co-div">{{ strtoupper($companyDiv) }}</p>
                </div>
            </td>
            <td style="width:50%;">
                <p class="rpt-title">COLLECTION RECEIPT SUMMARY</p>
                <div class="rpt-meta">
                    <strong>Range:</strong> {{ $dateFrom }} - {{ $dateTo }} &nbsp;&nbsp;
                    <strong>Admin:</strong> {{ $adminId }} &nbsp;&nbsp;
                    <strong>Printed:</strong> {{ $printedDate }}
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== SUMMARY BAR ===== --}}
<div class="sum-bar">
    <table>
        <tr>
            <td class="sum-total-cell" style="width:26%;">
                <div class="sum-lbl sum-lbl-main">TOTAL COLLECTION</div>
                <div class="sum-val">Rs. {{ number_format($totalAmt, 2) }}</div>
            </td>
            <td style="width:18.5%;text-align:center;">
                <div class="sum-lbl">CASH ({{ $cCash }})</div>
                <div class="sum-mval">{{ number_format($cashT, 2) }} <span class="sum-pct">({{ $pCash }}%)</span></div>
            </td>
            <td style="width:18.5%;text-align:center;">
                <div class="sum-lbl">CHEQUE ({{ $cCheque }})</div>
                <div class="sum-mval">{{ number_format($chequeT, 2) }} <span class="sum-pct">({{ $pCheque }}%)</span></div>
            </td>
            <td style="width:18.5%;text-align:center;">
                <div class="sum-lbl">CARD ({{ $cCard }})</div>
                <div class="sum-mval">{{ number_format($cardT, 2) }} <span class="sum-pct">({{ $pCard }}%)</span></div>
            </td>
            <td style="width:18.5%;text-align:center;">
                <div class="sum-lbl">TRANSFER ({{ $cTransfer }})</div>
                <div class="sum-mval">{{ number_format($bankT, 2) }} <span class="sum-pct">({{ $pTransfer }}%)</span></div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== COLLECTION CARDS ===== --}}
@if($totalColls > 0)
    @foreach($collList as $cIdx => $collection)
    @php
        $contactName = $collection['customer_name'] ?: ($collection['supplier_name'] ?: 'N/A');
        $collDate = fmtDate($collection['payment_date'] ?? '');
        $collLoc = $collection['location'] ?? '';
        $collTotal = (float) $collection['total_amount'];
        $collNotes = !empty($collection['payments'][0]['notes']) ? $collection['payments'][0]['notes'] : '';

        // Group payments by method
        $methodGroups = [];
        foreach ($collection['payments'] as $p) {
            $m = strtolower($p['payment_method']);
            if (!isset($methodGroups[$m])) {
                $methodGroups[$m] = ['total' => 0, 'payments' => []];
            }
            $methodGroups[$m]['total'] += (float) $p['amount'];
            $methodGroups[$m]['payments'][] = $p;
        }

        $shownCount++;
    @endphp

    <div class="coll">
        {{-- Collection Header --}}
        <div class="coll-hdr">
            <table>
                <tr>
                    <td style="width:70%;">
                        <span class="coll-ref">{{ $collection['reference_no'] ?? 'N/A' }}</span>
                        <span class="coll-cust">{{ $contactName }}</span>
                        <span class="coll-date">| &nbsp;{{ $collDate }}</span>
                        @if($collLoc)
                        <span class="coll-loc">{{ $collLoc }}</span>
                        @endif
                        @if($collNotes)
                        <div style="font-size: 8px; color: #3b82f6; font-weight: 600; margin-top: 3px;">
                            <strong>Note:</strong> {{ $collNotes }}
                        </div>
                        @endif
                    </td>
                    <td style="width:30%;">
                        <div class="coll-amt">Rs. {{ number_format($collTotal, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Method Groups --}}
        @foreach($methodGroups as $methodKey => $group)
        @php
            $methodLabel = strtoupper($methodKey);
            if ($methodKey === 'bank_transfer') $methodLabel = 'BANK TRANSFER';
            $methodCount = count($group['payments']);
            $methodTotal = $group['total'];
            $subtotalClass = 'meth-subtotal-cash';
            if ($methodKey === 'cheque') $subtotalClass = 'meth-subtotal-cheque';
            elseif ($methodKey === 'card') $subtotalClass = 'meth-subtotal-card';
            elseif ($methodKey === 'bank_transfer') $subtotalClass = 'meth-subtotal-bank';
        @endphp

        {{-- Method Sub-header --}}
        <div class="meth-hdr">
            <table>
                <tr>
                    <td style="width:30%;"><span class="meth-label">METHOD: {{ $methodLabel }}</span></td>
                    <td style="width:35%;"><span class="meth-count">{{ $methodCount }} PAYMENT{{ $methodCount > 1 ? 'S' : '' }}</span></td>
                    <td style="width:35%;"><span class="meth-subtotal {{ $subtotalClass }}">Subtotal: Rs. {{ number_format($methodTotal, 2) }}</span></td>
                </tr>
            </table>
        </div>

        {{-- Payment Rows - GROUPED by cheque number / reference --}}
        @php
            // Group payments within this method by a grouping key
            $groupedRows = [];
            foreach ($group['payments'] as $p) {
                if ($methodKey === 'cheque') {
                    $gKey = $p['cheque_number'] ?: ('cheque_' . $p['id']);
                } elseif ($methodKey === 'cash') {
                    // Group all cash into one row per collection, or by reference
                    $gKey = $p['cheque_number'] ?: ('CASH-' . $p['id']);
                } elseif ($methodKey === 'card') {
                    $gKey = $p['cheque_number'] ?: ('card_' . $p['id']);
                } elseif ($methodKey === 'bank_transfer') {
                    $gKey = $p['cheque_number'] ?: ('txn_' . $p['id']);
                } else {
                    $gKey = 'other_' . $p['id'];
                }
                if (!isset($groupedRows[$gKey])) {
                    $groupedRows[$gKey] = [
                        'first' => $p,
                        'total' => 0,
                        'count' => 0,
                    ];
                }
                $groupedRows[$gKey]['total'] += (float) $p['amount'];
                $groupedRows[$gKey]['count']++;
            }
        @endphp

        @foreach($groupedRows as $gKey => $gRow)
        @php
            $fp = $gRow['first'];
            $gAmt = $gRow['total'];
            $payRef = '';
            $payBank = '';
            $payDueLabel = '';
            $payDueVal = '';
            $payStatus = '';
            $statusClass = 'st-pending';

            if ($methodKey === 'cheque') {
                $payRef = $fp['cheque_number'] ? 'Chq: ' . $fp['cheque_number'] : 'Cheque';
                $payBank = $fp['cheque_bank_branch'] ?? '';
                $payDueLabel = 'Due:';
                $payDueVal = fmtDate($fp['cheque_valid_date'] ?? '');
                $payStatus = isset($fp['cheque_status']) ? $fp['cheque_status'] : 'Pending';
                $sl = strtolower($payStatus);
                if ($sl === 'cleared' || $sl === 'settled') $statusClass = 'st-cleared';
                elseif ($sl === 'bounced') $statusClass = 'st-bounced';
                else $statusClass = 'st-pending';
            } elseif ($methodKey === 'cash') {
                $payRef = 'Ref: ' . ($fp['cheque_number'] ?: ('CASH-' . $fp['id']));
                $payBank = 'CASH';
                $payDueLabel = 'Paid:';
                $payDueVal = fmtDate($fp['payment_date'] ?? '');
                $payStatus = 'Settled';
                $statusClass = 'st-settled';
            } elseif ($methodKey === 'card') {
                $payRef = $fp['cheque_number'] ? 'Card: *' . substr($fp['cheque_number'], -4) : 'Card Payment';
                $payBank = $fp['cheque_bank_branch'] ?? 'Terminal';
                $payDueLabel = 'Paid:';
                $payDueVal = fmtDate($fp['payment_date'] ?? '');
                $payStatus = 'Settled';
                $statusClass = 'st-settled';
            } elseif ($methodKey === 'bank_transfer') {
                $payRef = $fp['cheque_number'] ? 'TXN: ' . $fp['cheque_number'] : 'Bank Transfer';
                $payBank = $fp['cheque_bank_branch'] ?? 'Bank';
                $payDueLabel = 'Paid:';
                $payDueVal = fmtDate($fp['payment_date'] ?? '');
                $payStatus = 'Settled';
                $statusClass = 'st-settled';
            } else {
                $payRef = ucfirst($methodKey);
                $payBank = '-';
                $payDueLabel = 'Date:';
                $payDueVal = fmtDate($fp['payment_date'] ?? '');
                $payStatus = 'Pending';
            }
        @endphp
        <div class="pay-row">
            <table>
                <tr>
                    <td style="width:18%;"><span class="pay-ref">{{ $payRef }}</span></td>
                    <td style="width:14%;"><span class="pay-bank">{{ $payBank }}</span></td>
                    <td style="width:24%;"><span class="pay-due">{{ $payDueLabel }} {{ $payDueVal }}</span></td>
                    <td style="width:14%;"><span class="st {{ $statusClass }}">{{ strtoupper($payStatus) }}</span></td>
                    <td style="width:30%;"><span class="pay-amt">Rs. {{ number_format($gAmt, 2) }}</span></td>
                </tr>
            </table>
        </div>
        @endforeach
        @endforeach
    </div>

    @if($shownCount >= $maxPerPage && $cIdx < $totalColls - 1)
        @php $remaining = $totalColls - $shownCount; @endphp
        <div class="cont-note">
            ... CONTINUE HIGH-DENSITY LIST: {{ $remaining }} MORE COLLECTION RECEIPTS ...
        </div>
        @break
    @endif
    @endforeach

    {{-- If all shown --}}
    @if($shownCount >= $totalColls && $totalColls > 5)
    <div class="cont-note">
        --- ALL {{ $totalColls }} COLLECTION RECEIPTS DISPLAYED ---
    </div>
    @endif

@else
    <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
        <p style="font-size:12px;font-weight:700;color:#64748b;">No Payment Records Found</p>
        <p>No payment collections found for the selected filters.</p>
    </div>
@endif

{{-- ===== SIGNATURE SECTION ===== --}}
<div class="sig">
    <table>
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">ACCOUNTANT SIGNATURE</div>
                <div class="sig-sub">Prepared By: Admin {{ $adminId }}</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">BRANCH AUTHORIZATION</div>
                <div class="sig-sub">Seal & Signature</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-title">SYSTEM VERIFICATION</div>
                <div class="sig-sub">Auth Code: {{ substr(md5(date('YmdHis')), 0, 5) }}-BLK-CONT</div>
            </td>
        </tr>
    </table>
</div>

{{-- ===== PAGE FOOTER ===== --}}
<div class="pg-foot">
    <div class="pg-foot-txt">
        --- END OF PAYMENT COLLECTION RECEIPT SUMMARY REPORT (PAGE 1 OF 1) ---
    </div>
</div>

</body>
</html>
