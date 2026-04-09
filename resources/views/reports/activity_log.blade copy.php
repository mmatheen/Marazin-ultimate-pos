@extends('layout.layout')
@section('title', 'Activity Log')
@section('content')
    <style>
        .activity-shell {
            --activity-bg: #f7f8fc;
            --activity-card: #ffffff;
            --activity-border: rgba(15, 23, 42, 0.08);
            --activity-text: #0f172a;
            --activity-muted: #64748b;
            background: linear-gradient(180deg, #fbfcff 0%, #f7f8fc 100%);
            min-height: 100vh;
            padding-bottom: 24px;
        }

        .activity-hero {
            background: #ffffff;
            color: var(--activity-text);
            border-radius: 18px;
            padding: 22px 24px;
            border: 1px solid var(--activity-border);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        .activity-hero::before {
            content: '';
            display: block;
            width: 56px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
            margin-bottom: 14px;
        }

        .activity-hero-title {
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .activity-hero-subtitle {
            max-width: 760px;
            color: var(--activity-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .activity-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #f1f5f9;
            border: 1px solid rgba(15, 23, 42, 0.08);
            color: #334155;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .activity-chip--light {
            background: #eef2ff;
            color: #3730a3;
        }

        .activity-stat {
            background: #ffffff;
            border: 1px solid var(--activity-border);
            border-radius: 18px;
            padding: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        }

        .activity-stat__label {
            font-size: 0.78rem;
            color: var(--activity-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .activity-stat__value {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--activity-text);
        }

        .activity-stat__meta {
            font-size: 0.84rem;
            color: var(--activity-muted);
            margin-top: 4px;
        }

        .activity-card {
            border: 1px solid var(--activity-border);
            border-radius: 20px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
            background: var(--activity-card);
        }

        .activity-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            padding: 18px 22px;
        }

        .activity-card .card-body {
            padding: 20px 22px;
        }

        .activity-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--activity-text);
            margin-bottom: 4px;
        }

        .activity-section-subtitle {
            color: var(--activity-muted);
            font-size: 0.88rem;
            margin-bottom: 0;
        }

        .activity-filter-toggle {
            border-radius: 14px;
            padding: 10px 16px;
            font-weight: 700;
            box-shadow: none;
        }

        .activity-filter-grid .form-group label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--activity-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .activity-filter-grid .form-control,
        .activity-filter-grid .select,
        .activity-filter-grid #reportrange {
            border-radius: 14px !important;
            border-color: rgba(15, 23, 42, 0.12) !important;
            min-height: 46px;
            box-shadow: none !important;
        }

        .activity-filter-grid #reportrange {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 14px !important;
            background: #fff;
        }

        .activity-table-card table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
            table-layout: fixed;
        }

        .activity-table-card thead th {
            background: #f8fafc;
            color: #fff;
            border-bottom: none !important;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-top: 1px solid rgba(15, 23, 42, 0.06) !important;
        }

        .activity-table-card tbody tr {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .activity-table-card tbody tr:hover {
            background: #fafcff;
        }

        .activity-table-card td {
            vertical-align: top;
            color: #1f2937;
        }

        .activity-table-card th:nth-child(1),
        .activity-table-card td:nth-child(1) {
            width: 132px;
            white-space: nowrap;
        }

        .activity-table-card th:nth-child(2),
        .activity-table-card td:nth-child(2) {
            width: 130px;
        }

        .activity-table-card th:nth-child(3),
        .activity-table-card td:nth-child(3) {
            width: 96px;
        }

        .activity-table-card th:nth-child(4),
        .activity-table-card td:nth-child(4) {
            width: 110px;
        }

        .activity-table-card th:nth-child(6),
        .activity-table-card td:nth-child(6) {
            width: 120px;
            text-align: center;
        }

        .activity-table-card th:nth-child(5),
        .activity-table-card td:nth-child(5) {
            width: calc(100% - 588px);
        }

        .activity-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .activity-badge--created { background: #ecfdf3; color: #166534; border-color: #bbf7d0; }
        .activity-badge--updated { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .activity-badge--deleted { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .activity-badge--login,
        .activity-badge--logout { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }

        .activity-note {
            max-width: 100%;
            color: #334155;
            line-height: 1.5;
            overflow: hidden;
        }

        .activity-diff-wrap,
        .activity-created-wrap {
            display: grid;
            gap: 10px;
        }

        .activity-diff-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 12px;
        }

        .activity-diff-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
        }

        .activity-diff-subtitle {
            font-size: 0.8rem;
            color: #64748b;
        }

        .activity-note-table table {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .activity-note-table table th,
        .activity-note-table table td {
            font-size: 0.8rem;
            padding: 8px 10px;
            vertical-align: middle;
        }

        .activity-created-box,
        .activity-action-summary {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        }

        .activity-created-title {
            font-size: 0.8rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }

        .activity-created-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
            padding: 8px 0;
            font-size: 0.84rem;
        }

        .activity-created-line:first-of-type {
            border-top: none;
            padding-top: 0;
        }

        .activity-created-line span {
            color: #64748b;
        }

        .activity-created-line strong {
            color: #0f172a;
            text-align: right;
        }

        .activity-action-summary {
            display: grid;
            gap: 2px;
        }

        .activity-action-summary__label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #94a3b8;
            font-weight: 700;
        }

        .activity-action-summary__value {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }

        .activity-plain-note {
            padding: 8px 0;
            color: #334155;
        }

        .activity-note .badge {
            border-radius: 999px;
        }

        .activity-note-compact {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .activity-note-compact table,
        .activity-note-compact .activity-created-box,
        .activity-note-compact .activity-action-summary {
            max-width: 100%;
        }

        .activity-note-compact .table {
            margin-bottom: 0;
        }

        .activity-note-compact .activity-created-line {
            padding: 6px 0;
        }

        .activity-note-compact .activity-diff-head {
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .activity-note-compact .activity-note-table table th,
        .activity-note-compact .activity-note-table table td {
            padding: 6px 8px;
        }

        .activity-note-expand {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 0.78rem;
            color: #2563eb;
            cursor: pointer;
            user-select: none;
        }

        .activity-note table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        .activity-note table th {
            background: #f8fafc;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
        }

        .activity-record-btn {
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
        }

        .activity-empty {
            padding: 36px 20px;
            text-align: center;
            color: var(--activity-muted);
            background: #fcfdff;
            border: 1px dashed rgba(15, 23, 42, 0.12);
            border-radius: 16px;
        }

        @media print {
            body {
                font-size: 14px !important;
            }

            table.dataTable {
                font-size: 14px !important;
                width: 100% !important;
                border-collapse: collapse;
            }

            table.dataTable th,
            table.dataTable td {
                white-space: nowrap;
                padding: 4px 6px;
                font-size: 14px !important;
            }

            .dt-buttons {
                display: none;
            }

            @page {
                margin-left: 0.2in;
                margin-right: 0.2in;
            }
        }
    </style>
    <div class="container-fluid content activity-shell">
        <div class="activity-hero mb-4">
            <div class="row align-items-stretch g-4">
                <div class="col-xl-7">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="activity-chip"><i class="fas fa-shield-alt"></i> Audit trail</span>
                        <span class="activity-chip activity-chip--light"><i class="fas fa-bolt"></i> Live filtering</span>
                    </div>
                    <h3 class="activity-hero-title mb-2">Activity Log</h3>
                    <p class="activity-hero-subtitle mb-4">
                        Track create, update, delete and login events with a clean audit timeline.
                        Use the filters to focus on one user, one subject type, or a date range.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary activity-filter-toggle" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter me-1"></i> Filters
                        </button>
                        <a href="{{ route('payment.report') }}" class="btn btn-outline-secondary activity-filter-toggle">
                            <i class="fas fa-receipt me-1"></i> Payments Report
                        </a>
                        <a href="{{ route('due.report') }}" class="btn btn-outline-secondary activity-filter-toggle">
                            <i class="fas fa-exclamation-circle me-1"></i> Due Report
                        </a>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="activity-stat h-100">
                                <div class="activity-stat__label">Total Logs</div>
                                <div class="activity-stat__value" id="activityTotalCount">0</div>
                                <div class="activity-stat__meta">Records loaded from server</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="activity-stat h-100">
                                <div class="activity-stat__label">Visible Rows</div>
                                <div class="activity-stat__value" id="activityFilteredCount">0</div>
                                <div class="activity-stat__meta">After current filters</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="activity-stat h-100">
                                <div class="activity-stat__label">Users</div>
                                <div class="activity-stat__value" id="activityUsersCount">0</div>
                                <div class="activity-stat__meta">Users in view</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="activity-stat h-100">
                                <div class="activity-stat__label">Subject Types</div>
                                <div class="activity-stat__value" id="activityTypesCount">0</div>
                                <div class="activity-stat__meta">Sales, users, purchases</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="collapse mb-4" id="collapseExample">
            <div class="activity-card card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <div class="activity-section-title">Filters</div>
                        <p class="activity-section-subtitle">Fine tune the audit trail without leaving the page.</p>
                    </div>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                        <i class="fas fa-info-circle me-1"></i> Tip: press Enter in Invoice No to search
                    </span>
                </div>
                <div class="card-body activity-filter-grid">
                    <form class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>By</label>
                                <select class="form-control select" id="byFilter">
                                    <option value="">All</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Subject Type</label>
                                <select class="form-control select" id="subjectTypeFilter">
                                    <option value="">All</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Date Range</label>
                                <div id="reportrange" class="w-100">
                                    <span></span>
                                    <i class="fa fa-caret-down text-muted"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Invoice No</label>
                                <input type="text" class="form-control" id="invoiceNoFilter"
                                    placeholder="Search by invoice no...">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="activity-card card activity-table-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <div class="activity-section-title mb-0">Audit Entries</div>
                    <p class="activity-section-subtitle">Latest activity events with full change details where available.</p>
                </div>
                <div class="text-end">
                    <div class="small text-muted">Last refresh</div>
                    <div class="fw-semibold" id="activityLastRefresh">—</div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="datatable table table-hover align-middle mb-0" style="width:100%" id="salesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Subject Type</th>
                                <th>Action</th>
                                <th>By</th>
                                <th>Note</th>
                                <th>Full Record</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal: Full record for super admin -->
    <div class="modal fade" id="fullRecordModal" tabindex="-1" aria-labelledby="fullRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="fullRecordModalLabel">Full Record — Sale Edit Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <p class="text-muted small mb-3">Complete change details for this sale edit are shown below.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" id="fullRecordTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Old Value</th>
                                    <th>New Value</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            const start = moment();
            const end = moment();

            function setDateRange(start, end) {
                $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            }
            $('#reportrange').daterangepicker({
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            }, setDateRange);
            setDateRange(start, end);

            // Destroy DataTable if exists
            function destroyDataTable() {
                if ($.fn.DataTable.isDataTable('#salesTable')) {
                    $('#salesTable').DataTable().destroy();
                }
            }

            function fetchActivityLog() {
                let daterange = $('#reportrange span').text().split(' - ');
                let start_date = moment(daterange[0], 'MMMM D, YYYY').format('YYYY-MM-DD');
                let end_date = moment(daterange[1], 'MMMM D, YYYY').format('YYYY-MM-DD');
                let subject_type = $('#subjectTypeFilter').val();
                let causer_id = $('#byFilter').val();
                $.ajax({
                    url: "{{ route('activity-log.fetch') }}",
                    type: "POST",
                    data: {
                        start_date: start_date,
                        end_date: end_date,
                        subject_type: subject_type,
                        causer_id: causer_id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        let tbody = '';
                        let subjectTypes = new Set();
                        let causerIds = new Map();
                        let customerNames = response.customer_names || {};
                        let totalLogs = (response.data || []).length;
                        let filteredCount = 0;
                        let usersCount = 0;
                        let typesCount = 0;
                        function escapeHtml(str) {
                            if (str == null) return '';
                            var div = document.createElement('div');
                            div.textContent = str;
                            return div.innerHTML;
                        }

                        // Sale field labels for full record display
                        let saleFieldLabels = {
                            customer_id: 'Customer',
                            invoice_no: 'Invoice No',
                            sales_date: 'Sale Date',
                            subtotal: 'Subtotal',
                            total_paid: 'Total Paid',
                            total_due: 'Total Due',
                            payment_status: 'Payment Status',
                            sale_notes: 'Sale Notes',
                            discount_type: 'Discount Type',
                            discount_amount: 'Discount Amount',
                            final_total: 'Final Total',
                            status: 'Status',
                            amount_given: 'Amount Given',
                            balance_amount: 'Balance',
                            location_id: 'Location',
                            user_id: 'User',
                            updated_by: 'Updated By',
                            sale_type: 'Sale Type',
                            transaction_type: 'Transaction Type',
                            order_number: 'Order Number',
                            order_date: 'Order Date',
                            expected_delivery_date: 'Expected Delivery',
                            order_status: 'Order Status',
                            shipping_details: 'Shipping Details',
                            shipping_address: 'Shipping Address',
                            shipping_charges: 'Shipping Charges',
                            shipping_status: 'Shipping Status',
                            delivered_to: 'Delivered To',
                            delivery_person: 'Delivery Person'
                        };
                        function getFieldLabel(key) { return saleFieldLabels[key] || key; }
                        function formatVal(key, val) {
                            if (val === null || val === undefined || val === '') return '—';
                            if (key === 'customer_id') return customerNames[String(val)] || ('ID: ' + val);
                            if (['subtotal','total_paid','total_due','final_total','discount_amount','amount_given','balance_amount','shipping_charges'].indexOf(key) !== -1)
                                return parseFloat(val).toLocaleString();
                            if (key === 'sales_date' || key === 'order_date' || key === 'expected_delivery_date')
                                return moment(val).format('YYYY-MM-DD');
                            return String(val);
                        }

                        function renderDiffTable(labelMap, oldValues, newValues) {
                            let allKeys = {};
                            Object.keys(newValues || {}).forEach(function(key) { allKeys[key] = true; });
                            Object.keys(oldValues || {}).forEach(function(key) { allKeys[key] = true; });

                            let rows = '';
                            Object.keys(allKeys).sort().forEach(function(key) {
                                let oldValue = oldValues ? oldValues[key] : undefined;
                                let newValue = newValues ? newValues[key] : undefined;
                                if (oldValue === newValue && oldValue !== undefined) return;
                                let label = labelMap[key] || key;
                                let oldStr = escapeHtml(String(formatVal(key, oldValue)));
                                let newStr = escapeHtml(String(formatVal(key, newValue)));
                                rows += `<tr>
                                    <td class="text-nowrap fw-semibold">${label}</td>
                                    <td><span class="badge bg-secondary-subtle text-secondary border">${oldStr}</span></td>
                                    <td><span class="badge bg-success-subtle text-success border">${newStr}</span></td>
                                </tr>`;
                            });

                            if (!rows) {
                                rows = '<tr><td colspan="3" class="text-center text-muted py-3">No value changes</td></tr>';
                            }

                            return `<div class="activity-note-table table-responsive">
                                <table class="table table-sm table-bordered mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>Old Value</th>
                                            <th>New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>`;
                        }

                        function renderCreatedSummary(labelMap, newValues, primaryFields) {
                            let body = '';
                            primaryFields.forEach(function(key) {
                                let value = newValues ? newValues[key] : undefined;
                                if (value === undefined || value === null || value === '') return;
                                body += `<div class="activity-created-line"><span>${labelMap[key] || key}</span><strong>${escapeHtml(String(formatVal(key, value)))}</strong></div>`;
                            });

                            if (!body) {
                                body = '<div class="text-muted small">New record created.</div>';
                            }

                            return `<div class="activity-created-box">
                                <div class="activity-created-title">New Record</div>
                                ${body}
                            </div>`;
                        }

                        function renderActionSummary(actionName, subjectLabel) {
                            return `<div class="activity-action-summary">
                                <span class="activity-action-summary__label">Action</span>
                                <span class="activity-action-summary__value">${escapeHtml(String(actionName || ''))}</span>
                                <span class="activity-action-summary__label mt-2">Type</span>
                                <span class="activity-action-summary__value">${escapeHtml(String(subjectLabel || ''))}</span>
                            </div>`;
                        }

                        if (response.success && response.data.length > 0) {
                            // Collect unique subject types and causer ids for dropdowns
                            response.data.forEach(function(item) {
                                if (item.subject_type) subjectTypes.add(item.subject_type);
                                if (item.causer_id && item.user && item.user.user_name) {
                                    causerIds.set(item.causer_id, item.user.user_name);
                                }
                            });

                            // Populate Subject Type dropdown (preserve selected value)
                            let selectedType = $('#subjectTypeFilter').val();
                            let subjectTypeOptions = '<option value="">All</option>';
                            subjectTypes.forEach(function(type) {
                                let label = type;
                                if (type === 'App\\Models\\User') label = 'User';
                                else if (type === 'App\\Models\\Sale') label = 'Sale';
                                else if (type === 'App\\Models\\SalesReturn') label = 'Sale Return';
                                else if (type === 'App\\Models\\Purchase') label = 'Purchase';
                                else if (type === 'App\\Models\\StockTransfer') label = 'Stock Transfer';
                                else if (type === 'App\\Models\\Customer') label = 'Customer';
                                else if (type === 'App\\Models\\Supplier') label = 'Supplier';
                                else if (type === 'App\\Models\\StockAdjustment') label = 'Stock Adjustment';
                                subjectTypeOptions +=
                                    `<option value="${type}"${selectedType === type ? ' selected' : ''}>${label}</option>`;
                            });
                            $('#subjectTypeFilter').html(subjectTypeOptions);

                            // Populate By (causer) dropdown (preserve selected value)
                            let selectedCauser = $('#byFilter').val();
                            let causerOptions = '<option value="">All</option>';
                            causerIds.forEach(function(name, id) {
                                causerOptions +=
                                    `<option value="${id}"${selectedCauser == id ? ' selected' : ''}>${name}</option>`;
                            });
                            $('#byFilter').html(causerOptions);

                            // Build a map of latest created/updated sale logs by subject_id for invoice lookup
                            let saleInvoiceMap = {};
                            response.data.forEach(function(item) {
                                if (item.subject_type === 'App\\Models\\Sale') {
                                    if (item.event === 'created' && item.properties && item
                                        .properties.attributes && item.properties.attributes
                                        .invoice_no) {
                                        saleInvoiceMap[item.subject_id] = item.properties
                                            .attributes.invoice_no;
                                    } else if (item.event === 'updated' && item.properties &&
                                        item.properties.attributes && item.properties.attributes
                                        .invoice_no) {
                                        saleInvoiceMap[item.subject_id] = item.properties
                                            .attributes.invoice_no;
                                    }
                                }
                            });

                            // Build a map of sale_id to sale invoice_no for SalesReturn
                            let salesReturnSaleIdToInvoice = {};
                            response.data.forEach(function(item) {
                                if (item.subject_type === 'App\\Models\\Sale' && item
                                    .properties && item.properties.attributes && item.properties
                                    .attributes.invoice_no) {
                                    salesReturnSaleIdToInvoice[item.subject_id] = item
                                        .properties.attributes.invoice_no;
                                }
                            });

                            // Filter data based on selected filters (including "All") + Invoice No for quick find
                            let invoiceNoFilter = ($('#invoiceNoFilter').val() || '').trim().toLowerCase();
                            let filteredData = response.data.filter(function(item) {
                                let typeMatch = !selectedType || item.subject_type === selectedType;
                                let causerMatch = !selectedCauser || String(item.causer_id) === String(selectedCauser);
                                if (!typeMatch || !causerMatch) return false;
                                if (invoiceNoFilter && (item.subject_type === 'App\\Models\\Sale' || item.subject_type === 'App\Models\\Sale') && item.properties) {
                                    let p = item.properties;
                                    let attrs = p.attributes || {};
                                    let old = p.old || {};
                                    let inv = (attrs.invoice_no || old.invoice_no || '').toString().toLowerCase();
                                    if (inv.indexOf(invoiceNoFilter) === -1) return false;
                                }
                                return true;
                            });

                            // Collapse double record: when a sale is created (cash sale), a second "updated" log is often
                            // written immediately after (payment sync). Hide that "updated" so we show only "Sale created".
                            let saleCreatedAtBySubject = {};
                            filteredData.forEach(function(item) {
                                if ((item.subject_type === 'App\\Models\\Sale' || item.subject_type === 'App\Models\\Sale') && item.event === 'created' && item.subject_id) {
                                    saleCreatedAtBySubject[item.subject_id] = item.created_at;
                                }
                            });
                            filteredData = filteredData.filter(function(item) {
                                if ((item.subject_type === 'App\\Models\\Sale' || item.subject_type === 'App\Models\\Sale') && item.event === 'updated' && item.subject_id) {
                                    let createdAt = saleCreatedAtBySubject[item.subject_id];
                                    if (createdAt) {
                                        let secs = moment(item.created_at).diff(moment(createdAt), 'seconds');
                                        if (secs >= 0 && secs <= 90) return false; // hide immediate post-create update
                                    }
                                }
                                return true;
                            });

                            filteredData.forEach(function(item) {
                                // Format subject type
                                let subjectTypeLabel = item.subject_type;
                                let subjectBadgeClass = 'bg-secondary-subtle text-secondary';
                                if (item.subject_type === 'App\\Models\\User') {
                                    subjectTypeLabel = 'User';
                                    subjectBadgeClass = 'bg-light text-dark border';
                                } else if (item.subject_type === 'App\\Models\\Sale') {
                                    subjectTypeLabel = 'Sale';
                                    subjectBadgeClass = 'bg-primary-subtle text-primary';
                                } else if (item.subject_type === 'App\\Models\\SalesReturn') {
                                    subjectTypeLabel = 'Sale Return';
                                    subjectBadgeClass = 'bg-warning-subtle text-warning';
                                } else if (item.subject_type === 'App\\Models\\Purchase') {
                                    subjectTypeLabel = 'Purchase';
                                    subjectBadgeClass = 'bg-success-subtle text-success';
                                } else if (item.subject_type === 'App\\Models\\StockTransfer') {
                                    subjectTypeLabel = 'Stock Transfer';
                                    subjectBadgeClass = 'bg-info-subtle text-info';
                                } else if (item.subject_type === 'App\\Models\\Customer') {
                                    subjectTypeLabel = 'Customer';
                                    subjectBadgeClass = 'bg-light text-dark border';
                                } else if (item.subject_type === 'App\\Models\\Supplier') {
                                    subjectTypeLabel = 'Supplier';
                                    subjectBadgeClass = 'bg-light text-dark border';
                                } else if (item.subject_type === 'App\\Models\\StockAdjustment') {
                                    subjectTypeLabel = 'Stock Adjustment';
                                    subjectBadgeClass = 'bg-dark-subtle text-dark';
                                } else {
                                    subjectTypeLabel = item.subject_type || '';
                                }
                                // Format date
                                let date = item.created_at ? moment(item.created_at).format('MM/DD/YYYY hh:mm A') : '';
                                // Format "By" (causer)
                                let by = '';
                                if (item.user && item.user.user_name) {
                                    by = item.user.user_name;
                                } else if (item.properties && item.properties.full_name) {
                                    by = item.properties.full_name;
                                } else if (item.causer_id) {
                                    by = 'User ID: ' + item.causer_id;
                                }
                                // Format Action
                                let action = item.event ?? '';
                                if (
                                    ((item.event === 'login' || item.event === 'logout' || item
                                            .event === null) &&
                                        subjectTypeLabel === 'User')
                                ) {
                                    action = (item.event === 'login' || item.event === null) ?
                                        'Logged in' : 'Logged out';
                                }
                                let actionBadgeClass = 'activity-badge--updated';
                                if (action === 'created') actionBadgeClass = 'activity-badge--created';
                                else if (action === 'updated') actionBadgeClass = 'activity-badge--updated';
                                else if (action === 'deleted') actionBadgeClass = 'activity-badge--deleted';
                                else if (action === 'Logged in' || action === 'Logged out') actionBadgeClass = 'activity-badge--login';
                                // Format Note
                                let note = '';
                                let properties = item.properties || {};
                                let attributes = properties.attributes || {};
                                let oldValues = properties.old || {};
                                // Sale Return Note
                                if (item.subject_type === 'App\\Models\\Payment') {
                                    let paymentLabelMap = {
                                        payment_date: 'Payment Date',
                                        amount: 'Amount',
                                        payment_method: 'Payment Method',
                                        reference_no: 'Reference No',
                                        notes: 'Notes',
                                        payment_type: 'Payment Type',
                                        reference_id: 'Reference ID',
                                        cash_register_id: 'Cash Register',
                                        supplier_id: 'Supplier',
                                        customer_id: 'Customer',
                                        cheque_number: 'Cheque No',
                                        cheque_bank_branch: 'Cheque Bank Branch',
                                        cheque_received_date: 'Cheque Received',
                                        cheque_valid_date: 'Cheque Valid Date',
                                        cheque_given_by: 'Cheque Given By',
                                        cheque_status: 'Cheque Status',
                                        cheque_clearance_date: 'Cheque Clearance',
                                        cheque_bounce_date: 'Cheque Bounce Date',
                                        cheque_bounce_reason: 'Cheque Bounce Reason',
                                        bank_charges: 'Bank Charges',
                                        payment_status: 'Payment Status',
                                        status: 'Status',
                                        original_amount: 'Original Amount',
                                        edited_by: 'Edited By',
                                        edited_at: 'Edited At',
                                        edit_reason: 'Edit Reason'
                                    };

                                    if (item.event === 'updated' && Object.keys(attributes).length) {
                                        note = `<div class="activity-diff-wrap activity-note-compact">
                                            <div class="activity-diff-head">
                                                <div class="activity-diff-title">Payment Changed</div>
                                                <div class="activity-diff-subtitle">Old value vs new value</div>
                                            </div>
                                            ${renderDiffTable(paymentLabelMap, oldValues, attributes)}
                                                <div class="activity-note-expand">View full details</div>
                                        </div>`;
                                    } else if (item.event === 'created' && Object.keys(attributes).length) {
                                            note = `<div class="activity-created-wrap activity-note-compact">
                                            ${renderActionSummary(action, subjectTypeLabel)}
                                            ${renderCreatedSummary(paymentLabelMap, attributes, ['reference_no', 'payment_method', 'amount', 'payment_status', 'status'])}
                                        </div>`;
                                    } else if (item.description) {
                                        note = item.description;
                                    }
                                } else if (subjectTypeLabel === 'Sale' && item.event === 'updated' && Object.keys(attributes).length) {
                                    let invoiceNo = attributes.invoice_no || saleInvoiceMap[item.subject_id] || '';
                                    let saleLabelMap = {
                                        customer_id: 'Customer',
                                        invoice_no: 'Invoice No',
                                        sales_date: 'Sale Date',
                                        subtotal: 'Subtotal',
                                        total_paid: 'Total Paid',
                                        total_due: 'Total Due',
                                        payment_status: 'Payment Status',
                                        sale_notes: 'Sale Notes',
                                        discount_type: 'Discount Type',
                                        discount_amount: 'Discount Amount',
                                        final_total: 'Final Total',
                                        status: 'Status',
                                        amount_given: 'Amount Given',
                                        balance_amount: 'Balance',
                                        location_id: 'Location',
                                        user_id: 'User',
                                        updated_by: 'Updated By',
                                        sale_type: 'Sale Type',
                                        transaction_type: 'Transaction Type',
                                        order_number: 'Order Number',
                                        order_date: 'Order Date',
                                        expected_delivery_date: 'Expected Delivery',
                                        order_status: 'Order Status',
                                        shipping_details: 'Shipping Details',
                                        shipping_address: 'Shipping Address',
                                        shipping_charges: 'Shipping Charges',
                                        shipping_status: 'Shipping Status',
                                        delivered_to: 'Delivered To',
                                        delivery_person: 'Delivery Person'
                                    };
                                    note = `<div class="activity-diff-wrap activity-note-compact">
                                        <div class="activity-diff-head">
                                            <div class="activity-diff-title">Sale Changed</div>
                                            <div class="activity-diff-subtitle">Invoice ${escapeHtml(invoiceNo || '—')}</div>
                                        </div>
                                        ${renderDiffTable(saleLabelMap, oldValues, attributes)}
                                        <div class="activity-note-expand">View full details</div>
                                    </div>`;
                                } else if (subjectTypeLabel === 'Sale' && item.event === 'created' && Object.keys(attributes).length) {
                                    note = `<div class="activity-created-wrap activity-note-compact">
                                        ${renderActionSummary(action, subjectTypeLabel)}
                                        ${renderCreatedSummary({invoice_no: 'Invoice No', status: 'Status', subtotal: 'Total', payment_status: 'Payment Status'}, attributes, ['invoice_no', 'status', 'subtotal', 'payment_status'])}
                                    </div>`;
                                } else if (subjectTypeLabel === 'Purchase' && item.event === 'created' && Object.keys(attributes).length) {
                                    note = `<div class="activity-created-wrap activity-note-compact">
                                        ${renderActionSummary(action, subjectTypeLabel)}
                                        ${renderCreatedSummary({reference_no: 'Reference No', purchasing_status: 'Status', final_total: 'Total', payment_status: 'Payment Status'}, attributes, ['reference_no', 'purchasing_status', 'final_total', 'payment_status'])}
                                    </div>`;
                                } else if (subjectTypeLabel === 'Stock Transfer' && Object.keys(attributes).length) {
                                    note = `<div class="activity-created-wrap activity-note-compact">
                                        ${renderActionSummary(action, subjectTypeLabel)}
                                        ${renderCreatedSummary({reference_no: 'Reference No', from_warehouse_name: 'From', to_warehouse_name: 'To', status: 'Status', total: 'Total'}, attributes, ['reference_no', 'from_warehouse_name', 'to_warehouse_name', 'status', 'total'])}
                                    </div>`;
                                } else if (subjectTypeLabel === 'Sale Return' && item.event === 'created' && Object.keys(attributes).length) {
                                    let saleInvoice = '';
                                    if (attributes.sale_id && salesReturnSaleIdToInvoice[attributes.sale_id]) {
                                        saleInvoice = salesReturnSaleIdToInvoice[attributes.sale_id];
                                    }
                                    note = `<div class="activity-created-wrap activity-note-compact">
                                        ${renderActionSummary(action, subjectTypeLabel)}
                                        <div class="activity-created-box">
                                            <div class="activity-created-title">Return Details</div>
                                            ${attributes.invoice_number ? `<div class="activity-created-line"><span>Return Invoice</span><strong>${escapeHtml(String(attributes.invoice_number))}</strong></div>` : ''}
                                            ${saleInvoice ? `<div class="activity-created-line"><span>Sale Invoice</span><strong>${escapeHtml(String(saleInvoice))}</strong></div>` : ''}
                                            ${typeof attributes.return_total !== 'undefined' ? `<div class="activity-created-line"><span>Total</span><strong>${escapeHtml(String(formatVal('return_total', attributes.return_total)))}</strong></div>` : ''}
                                            ${attributes.payment_status ? `<div class="activity-created-line"><span>Payment Status</span><strong>${escapeHtml(String(attributes.payment_status))}</strong></div>` : ''}
                                        </div>
                                    </div>`;
                                } else if (subjectTypeLabel === 'Customer' && Object.keys(attributes).length) {
                                    let customerLabelMap = {
                                        first_name: 'First Name',
                                        last_name: 'Last Name',
                                        mobile_no: 'Mobile',
                                        email: 'Email',
                                        opening_balance: 'Opening Balance',
                                        credit_limit: 'Credit Limit',
                                        status: 'Status'
                                    };
                                    if (item.event === 'updated') {
                                        note = `<div class="activity-diff-wrap activity-note-compact">
                                            <div class="activity-diff-head">
                                                <div class="activity-diff-title">Customer Changed</div>
                                                <div class="activity-diff-subtitle">Changed values only</div>
                                            </div>
                                            ${renderDiffTable(customerLabelMap, oldValues, attributes)}
                                            <div class="activity-note-expand">View full details</div>
                                        </div>`;
                                    } else {
                                        note = `<div class="activity-created-wrap activity-note-compact">
                                            ${renderActionSummary(action, subjectTypeLabel)}
                                            ${renderCreatedSummary(customerLabelMap, attributes, ['first_name', 'last_name', 'mobile_no', 'email', 'opening_balance', 'credit_limit'])}
                                        </div>`;
                                    }
                                } else if (subjectTypeLabel === 'Supplier' && Object.keys(attributes).length) {
                                    let supplierLabelMap = {
                                        first_name: 'First Name',
                                        last_name: 'Last Name',
                                        mobile_no: 'Mobile',
                                        email: 'Email',
                                        opening_balance: 'Opening Balance',
                                        status: 'Status'
                                    };
                                    if (item.event === 'updated') {
                                        note = `<div class="activity-diff-wrap activity-note-compact">
                                            <div class="activity-diff-head">
                                                <div class="activity-diff-title">Supplier Changed</div>
                                                <div class="activity-diff-subtitle">Changed values only</div>
                                            </div>
                                            ${renderDiffTable(supplierLabelMap, oldValues, attributes)}
                                            <div class="activity-note-expand">View full details</div>
                                        </div>`;
                                    } else {
                                        note = `<div class="activity-created-wrap activity-note-compact">
                                            ${renderActionSummary(action, subjectTypeLabel)}
                                            ${renderCreatedSummary(supplierLabelMap, attributes, ['first_name', 'last_name', 'mobile_no', 'email', 'opening_balance'])}
                                        </div>`;
                                    }
                                } else if (subjectTypeLabel === 'Stock Adjustment' && Object.keys(attributes).length) {
                                    let adjustmentLabelMap = {
                                        reference_no: 'Reference No',
                                        adjustment_type: 'Type',
                                        date: 'Date',
                                        total_amount_recovered: 'Amount Recovered',
                                        reason: 'Reason',
                                        status: 'Status'
                                    };
                                    note = `<div class="activity-created-wrap activity-note-compact">
                                        ${renderActionSummary(action, subjectTypeLabel)}
                                        ${item.event === 'updated'
                                            ? renderDiffTable(adjustmentLabelMap, oldValues, attributes)
                                            : renderCreatedSummary(adjustmentLabelMap, attributes, ['reference_no', 'adjustment_type', 'date', 'total_amount_recovered', 'reason'])
                                        }
                                    </div>`;
                                } else if (item.description) {
                                    note = `<div class="activity-plain-note activity-note-compact">${escapeHtml(item.description)}</div>`;
                                }
                                // Full record button: show modal with full properties (for Sale updated especially)
                                let fullRecordBtn = '';
                                if (item.properties && (item.subject_type === 'App\\Models\\Sale' && item.event === 'updated')) {
                                    let propsEnc = encodeURIComponent(JSON.stringify(item.properties));
                                    let namesEnc = encodeURIComponent(JSON.stringify(customerNames));
                                    fullRecordBtn = `<button type="button" class="btn btn-sm btn-outline-primary activity-record-btn full-record-btn" data-properties-enc="${propsEnc}" data-names-enc="${namesEnc}" title="Full record">Full record</button>`;
                                } else {
                                    fullRecordBtn = '—';
                                }
                                tbody += `<tr>
                                    <td class="text-nowrap">${date}</td>
                                    <td><span class="activity-badge ${subjectBadgeClass}">${subjectTypeLabel ?? ''}</span></td>
                                    <td><span class="activity-badge ${actionBadgeClass}">${action}</span></td>
                                    <td><span class="fw-semibold text-dark">${escapeHtml(by)}</span></td>
                                    <td><div class="activity-note">${note}</div></td>
                                    <td>${fullRecordBtn}</td>
                                </tr>`;
                            });

                            filteredCount = filteredData.length;
                            usersCount = causerIds.size;
                            typesCount = subjectTypes.size;

                            // If no rows after filtering
                            if (!tbody) {
                                tbody = '<tr><td colspan="6"><div class="activity-empty"><i class="fas fa-inbox fa-2x mb-2 text-muted"></i><div class="fw-semibold">No audit records found</div><div class="small">Try changing the date range or filters.</div></div></td></tr>';
                            }
                        } else {
                            tbody = '<tr><td colspan="6"><div class="activity-empty"><i class="fas fa-inbox fa-2x mb-2 text-muted"></i><div class="fw-semibold">No audit records found</div><div class="small">Try changing the date range or filters.</div></div></td></tr>';
                            // Clear dropdowns if no data
                            $('#subjectTypeFilter').html('<option value="">All</option>');
                            $('#byFilter').html('<option value="">All</option>');
                        }

                        $('#activityTotalCount').text(totalLogs);
                        $('#activityFilteredCount').text(filteredCount);
                        $('#activityUsersCount').text(usersCount);
                        $('#activityTypesCount').text(typesCount);
                        $('#activityLastRefresh').text(moment().format('MMM D, YYYY h:mm A'));

                        // Destroy DataTable before updating
                        destroyDataTable();
                        $('#salesTable').find('tbody').empty(); // Clear tbody
                        $('#salesTable tbody').html(tbody);
                        // Re-initialize DataTable only if there is data (not just the "No data found" row)
                        if ($('#salesTable tbody tr').length > 0 && !$('#salesTable tbody tr td')
                            .hasClass('text-center')) {
                            $('#salesTable').DataTable({
                                ordering: true,
                                searching: true,
                                paging: true,
                                destroy: true,
                                autoWidth: false,
                                language: {
                                    emptyTable: "No data found"
                                }
                            });
                        }
                    }
                });
            }
            // Full record modal: show full Sale edit details
            $(document).on('click', '.full-record-btn', function() {
                let propsEnc = $(this).attr('data-properties-enc');
                let namesEnc = $(this).attr('data-names-enc') || '';
                let propsJson = null;
                let customerNames = {};
                try {
                    if (propsEnc) propsJson = JSON.parse(decodeURIComponent(propsEnc));
                    if (namesEnc) customerNames = JSON.parse(decodeURIComponent(namesEnc));
                } catch (e) { propsJson = null; }
                if (!propsJson || !propsJson.attributes) {
                    $('#fullRecordTable tbody').html('<tr><td colspan="3">No data</td></tr>');
                    new bootstrap.Modal(document.getElementById('fullRecordModal')).show();
                    return;
                }
                let attr = propsJson.attributes;
                let old = propsJson.old || {};
                let saleFieldLabels = {
                    customer_id: 'Customer',
                    invoice_no: 'Invoice No',
                    sales_date: 'Sale Date',
                    subtotal: 'Subtotal',
                    total_paid: 'Total Paid',
                    total_due: 'Total Due',
                    payment_status: 'Payment Status',
                    sale_notes: 'Sale Notes',
                    discount_type: 'Discount Type',
                    discount_amount: 'Discount Amount',
                    final_total: 'Final Total',
                    status: 'Status',
                    amount_given: 'Amount Given',
                    balance_amount: 'Balance',
                    location_id: 'Location',
                    user_id: 'User',
                    updated_by: 'Updated By',
                    sale_type: 'Sale Type',
                    transaction_type: 'Transaction Type',
                    order_number: 'Order Number',
                    order_date: 'Order Date',
                    expected_delivery_date: 'Expected Delivery',
                    order_status: 'Order Status',
                    shipping_details: 'Shipping Details',
                    shipping_address: 'Shipping Address',
                    shipping_charges: 'Shipping Charges',
                    shipping_status: 'Shipping Status',
                    delivered_to: 'Delivered To',
                    delivery_person: 'Delivery Person'
                };
                function fmtVal(key, val) {
                    if (val === null || val === undefined || val === '') return '—';
                    if (key === 'customer_id') return customerNames[String(val)] || ('ID: ' + val);
                    if (['subtotal','total_paid','total_due','final_total','discount_amount','amount_given','balance_amount','shipping_charges'].indexOf(key) !== -1)
                        return parseFloat(val).toLocaleString();
                    if (key === 'sales_date' || key === 'order_date' || key === 'expected_delivery_date')
                        return moment(val).format('YYYY-MM-DD');
                    return String(val);
                }
                let allKeys = {};
                Object.keys(attr).forEach(function(k) { allKeys[k] = true; });
                Object.keys(old).forEach(function(k) { allKeys[k] = true; });
                let rows = '';
                Object.keys(allKeys).sort().forEach(function(key) {
                    let label = saleFieldLabels[key] || key;
                    let ov = fmtVal(key, old[key]);
                    let nv = fmtVal(key, attr[key]);
                    rows += '<tr><td>' + label + '</td><td>' + ov + '</td><td>' + nv + '</td></tr>';
                });
                $('#fullRecordTable tbody').html(rows || '<tr><td colspan="3">No changes</td></tr>');
                new bootstrap.Modal(document.getElementById('fullRecordModal')).show();
            });

            // Initial fetch
            fetchActivityLog();
            // Refetch on filter change
            $('#subjectTypeFilter, #byFilter').on('change', fetchActivityLog);
            $('#reportrange').on('apply.daterangepicker', fetchActivityLog);
            $('#invoiceNoFilter').on('keyup', function(e) {
                if (e.key === 'Enter') fetchActivityLog();
            });
            $('#invoiceNoFilter').on('change', fetchActivityLog);
        });
    </script>
@endsection
