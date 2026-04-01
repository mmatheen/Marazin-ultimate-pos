@extends('layout.layout')

@push('styles')
<!-- DataTables RowGroup CSS - Local -->
<link rel="stylesheet" href="{{ asset('vendor/datatables-rowgroup/css/rowGroup.dataTables.min.css') }}">
<style>
    /* DataTables Row Grouping Styles */
    .group-row {
        background: #fff !important;
        color: #5f6368 !important;
        font-weight: 600;
        font-size: 12px;
        border-left: none !important;
        border-top: 1px solid #dadce0 !important;
        border-bottom: 1px solid #eceff1 !important;
    }

    .group-row td {
        background: transparent !important;
        color: inherit !important;
        padding: 7px 10px !important;
        vertical-align: middle !important;
    }

    .group-row td:first-child {
        border-left: none !important;
    }

    /* Customer group header — one full-width cell, light panel */
    .cheque-mgmt-page table.dataTable tbody tr.cheque-dtrg-row td.cheque-dtrg-cell {
        background: #f0f3f7 !important;
        border-top: 1px solid #dadce0 !important;
        border-bottom: 1px solid #e2e6ec !important;
        padding: 10px 12px !important;
        vertical-align: middle !important;
    }

    .cheque-mgmt-page .cheque-dtrg-inner {
        max-width: 100%;
    }

    .cheque-mgmt-page .cheque-dtrg-customer {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 14px;
        font-weight: 700;
        color: #111827;
        line-height: 1.35;
        margin-bottom: 6px;
    }

    .cheque-mgmt-page .cheque-dtrg-icon {
        color: #5f6368;
        font-size: 13px;
        width: 1.1rem;
        text-align: center;
        flex-shrink: 0;
    }

    .cheque-mgmt-page .cheque-dtrg-customer-name {
        word-break: break-word;
    }

    .cheque-mgmt-page .cheque-dtrg-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.85rem;
        font-size: 13px;
        color: #374151;
        line-height: 1.4;
    }

    .cheque-mgmt-page .cheque-dtrg-meta-label {
        font-weight: 600;
        color: #5f6368;
        margin-right: 0.2rem;
    }

    .cheque-mgmt-page .cheque-dtrg-total-amount {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: #111827;
    }

    .cheque-mgmt-page .cheque-dtrg-sep {
        color: #c5cad3;
        font-weight: 400;
        user-select: none;
        padding: 0 0.1rem;
    }

    .cheque-mgmt-page .cheque-dtrg-count {
        font-variant-numeric: tabular-nums;
    }

    .cheque-mgmt-page .cheque-dtrg-count-word {
        font-weight: 600;
        color: #5f6368;
    }

    /* Status: only Overdue / Pending / Cleared / Default (all other states) */
    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--pending {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    /* Overdue severity: 1–7d mild, 8–30d medium, 31+d severe */
    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--overdue-1 {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--overdue-2 {
        background: #fff7ed;
        color: #9a3412;
        border: 1px solid #fdba74;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--overdue-3 {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--cleared {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-ui-status--default {
        background: #fff;
        color: #5f6368;
        border: 1px solid #dadce0;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody tr.cheque-row--overdue-1 td:first-child {
        box-shadow: inset 3px 0 0 #ca8a04;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody tr.cheque-row--overdue-2 td:first-child {
        box-shadow: inset 3px 0 0 #ea580c;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody tr.cheque-row--overdue-3 td:first-child {
        box-shadow: inset 3px 0 0 #c5221f;
    }

    .cheque-mgmt-page #chequesTable .cheque-ui-due.cheque-due-today {
        font-weight: 700;
        color: #b45309;
        background: #fffbeb;
        border-radius: 4px;
        padding: 0.1rem 0.35rem;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table thead th.sorting_asc,
    .cheque-mgmt-page #chequesTable.cheque-ui-table thead th.sorting_desc {
        background: #e8f0fe !important;
        color: #1967d2 !important;
        box-shadow: inset 0 -2px 0 #1967d2;
    }

    .cheque-mgmt-page .cheque-bulk-toolbar .cheque-bulk-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        filter: grayscale(0.35);
        box-shadow: none;
    }

    .cheque-mgmt-page .cheque-bulk-toolbar.cheque-bulk-has-selection .cheque-bulk-btn:not(:disabled) {
        opacity: 1;
        filter: none;
    }

    .cheque-mgmt-page .cheque-legend-bar {
        display: inline-block;
        width: 4px;
        height: 14px;
        border-radius: 2px;
        vertical-align: middle;
    }

    .cheque-mgmt-page .cheque-legend-bar--sev1 { background: #ca8a04; }
    .cheque-mgmt-page .cheque-legend-bar--sev2 { background: #ea580c; }
    .cheque-mgmt-page .cheque-legend-bar--sev3 { background: #c5221f; }

    .cheque-mgmt-page .cheque-page-jump input[type="number"]::-webkit-outer-spin-button,
    .cheque-mgmt-page .cheque-page-jump input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Avoid flash of ungrouped table before DataTables + RowGroup finishes */
    #chequeTableResponsiveWrapper.cheque-dt-pending {
        visibility: hidden;
        opacity: 0;
        min-height: 220px;
    }

    #chequeTableResponsiveWrapper.cheque-dt-ready {
        visibility: visible;
        opacity: 1;
        min-height: 0;
        transition: opacity 0.15s ease-out;
    }

    #chequeTableLoadingHint {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ========== Cheque Management — fintech dashboard shell ========== */
    .cheque-mgmt-page {
        --cm-pad: clamp(16px, 2vw, 24px);
        --cm-title: clamp(18px, 1.25vw, 20px);
        --cm-body: 13px;
        --cm-label: 12px;
        --cm-row-h: 36px;
        --cm-border: #e8eaed;
        --cm-muted: #5f6368;
        padding-left: var(--cm-pad) !important;
        padding-right: var(--cm-pad) !important;
        padding-bottom: var(--cm-pad);
    }

    .cheque-mgmt-page .page-sub-header {
        margin-bottom: 1rem;
    }

    .cheque-mgmt-page .cheque-mgmt-title {
        font-size: var(--cm-title);
        font-weight: 600;
        color: #202124;
        letter-spacing: -0.02em;
        margin-bottom: 0.25rem;
    }

    .cheque-mgmt-page .breadcrumb {
        font-size: var(--cm-label);
        margin-bottom: 0;
    }

    .cheque-mgmt-page .cheque-filters-card .card-body {
        padding: 14px var(--cm-pad);
    }

    .cheque-mgmt-page .cheque-stat-card {
        border: 1px solid var(--cm-border);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(60, 64, 67, 0.1), 0 1px 2px rgba(60, 64, 67, 0.06);
        background: #fff;
        min-height: 118px;
        height: 100%;
        transition: box-shadow 0.15s ease, transform 0.12s ease;
    }

    .cheque-mgmt-page .cheque-stat-card:hover {
        box-shadow: 0 4px 12px rgba(60, 64, 67, 0.12);
        transform: translateY(-1px);
    }

    .cheque-mgmt-page .cheque-stat-card .card-body {
        padding: 16px 18px;
        justify-content: flex-start;
    }

    .cheque-mgmt-page .cheque-stat-card .stat-label {
        font-size: 12px;
        font-weight: 700;
        color: #3c4043;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 10px;
    }

    /* Primary figure — amounts & counts (high contrast, large) */
    .cheque-mgmt-page .cheque-stat-card .stat-value {
        font-size: clamp(1.125rem, 2.2vw, 1.5rem);
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: #111827;
        line-height: 1.2;
        letter-spacing: -0.03em;
    }

    .cheque-mgmt-page .cheque-stat-card .stat-value-suffix {
        font-weight: 600;
        color: #5f6368;
        letter-spacing: 0;
    }

    /* Secondary line under the main figure */
    .cheque-mgmt-page .cheque-stat-card .stat-sub {
        font-size: 13px;
        font-weight: 600;
        color: #4b5563;
        margin-top: 12px;
        line-height: 1.4;
        border-top: 1px solid #e8eaed;
        padding-top: 10px;
    }

    .cheque-mgmt-page .cheque-stat-card .stat-sub .stat-sub-muted {
        font-weight: 500;
        color: #6b7280;
        font-size: 12px;
    }

    .cheque-mgmt-page .cheque-stat-card.stat-overdue { border-top: 3px solid #c5221f; }
    .cheque-mgmt-page .cheque-stat-card.stat-due-soon { border-top: 3px solid #e37400; }
    .cheque-mgmt-page .cheque-stat-card.stat-bounced { border-top: 3px solid #b06000; }
    .cheque-mgmt-page .cheque-stat-card.stat-cleared { border-top: 3px solid #137333; }

    .cheque-mgmt-page .cheque-list-card {
        border: 1px solid var(--cm-border);
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(60, 64, 67, 0.08);
        overflow: hidden;
    }

    .cheque-mgmt-page .cheque-list-card > .card-header {
        background: #fafbfc;
        border-bottom: 1px solid var(--cm-border);
        padding: 14px var(--cm-pad);
    }

    .cheque-mgmt-page .cheque-list-card .card-title {
        font-size: 15px;
        font-weight: 600;
        color: #202124;
    }

    .cheque-mgmt-page .cheque-toolbar .btn {
        font-size: 12px;
        padding: 0.35rem 0.65rem;
    }

    .cheque-mgmt-page .cheque-toolbar .btn i {
        font-size: 12px;
    }

    .cheque-mgmt-page #chequeTableResponsiveWrapper {
        padding: 0 var(--cm-pad) var(--cm-pad);
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table thead th {
        position: sticky;
        top: 0;
        z-index: 6;
        background: #fafafa !important;
        color: #5f6368 !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #dadce0 !important;
        padding: 7px 10px !important;
        box-shadow: none !important;
        height: 34px;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table thead th:first-child {
        padding: 7px 6px 7px 10px !important;
        text-align: center;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody td {
        padding: 6px 10px !important;
        font-size: var(--cm-body) !important;
        color: #3c4043 !important;
        border-bottom: 1px solid #eceff1 !important;
        vertical-align: middle !important;
        line-height: 1.35;
        height: var(--cm-row-h);
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody td:first-child {
        padding: 4px 6px 4px 10px !important;
        width: 40px;
        text-align: center;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table .cheque-checkbox,
    .cheque-mgmt-page #chequesTable.cheque-ui-table #selectAll {
        width: 0.95em;
        height: 0.95em;
        margin-top: 0;
        vertical-align: middle;
    }

    .cheque-mgmt-page #chequesTable.cheque-ui-table tbody tr:not(.dtrg-group):hover td {
        background: #f5f6f7 !important;
    }

    .cheque-mgmt-page #chequesTable .cheque-ui-cheque-no {
        font-size: var(--cm-body);
        font-weight: 500;
        color: #1a73e8;
        font-variant-numeric: tabular-nums;
    }

    .cheque-mgmt-page #chequesTable .cheque-ui-amount {
        font-size: 13px;
        font-weight: 700;
        color: #202124;
        font-variant-numeric: tabular-nums;
    }

    .cheque-mgmt-page #chequesTable .cheque-ui-due {
        font-size: var(--cm-body);
        font-variant-numeric: tabular-nums;
        color: #3c4043;
    }

    .cheque-mgmt-page #chequesTable .cheque-ui-status {
        display: inline-flex;
        align-items: center;
        font-size: 10px;
        font-weight: 600;
        padding: 0.12rem 0.38rem;
        border-radius: 3px;
        line-height: 1.2;
        white-space: nowrap;
    }

    .cheque-mgmt-page #chequesTable .cheque-actions .btn-icon {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        border: 1px solid #dadce0;
        background: #fff;
        color: #5f6368;
        font-size: 12px;
    }

    .cheque-mgmt-page #chequesTable .cheque-actions .btn-icon:hover {
        background: #f1f3f4;
        color: #202124;
    }

    .cheque-mgmt-page #chequesTable .cheque-actions .btn-icon:disabled {
        opacity: 0.45;
    }

    /* Legacy RowGroup rows without .cheque-dtrg-row (fallback) */
    .cheque-mgmt-page table.dataTable tbody tr.dtrg-group:not(.cheque-dtrg-row) td {
        background: #f0f3f7 !important;
        border-left: none !important;
        border-top: 1px solid #dadce0 !important;
        border-bottom: 1px solid #e2e6ec !important;
        font-size: 12px !important;
        padding: 7px 10px !important;
        font-weight: 500;
        color: #5f6368;
    }

    /* DataTables pagination — compact */
    .cheque-mgmt-page .dataTables_wrapper .dataTables_info,
    .cheque-mgmt-page .dataTables_wrapper .dataTables_length,
    .cheque-mgmt-page .dataTables_wrapper .dataTables_filter label {
        font-size: 12px;
        color: var(--cm-muted);
        padding-top: 0.5rem;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_filter input {
        font-size: 13px;
        padding: 0.35rem 0.6rem;
        border-radius: 6px;
        border: 1px solid var(--cm-border);
        margin-left: 0.5rem;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate {
        padding-top: 0.5rem;
    }

    /* Match brand.blade.php / layout DataTables: Bootstrap ul.pagination (joined group, not separate boxes) */
    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate ul.pagination {
        margin: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate ul.pagination .page-item {
        margin: 0 !important;
    }

    /* Reset LI — padding/border live on .page-link only (same as project .pagination in style.css) */
    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate .paginate_button.page-item {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        float: none;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate .page-link {
        font-size: 12px;
        padding: 0.35rem 0.65rem;
        min-width: 2.1rem;
        text-align: center;
        color: #3d5ee1;
        border-color: #dee2e6;
        line-height: 1.35;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #3d5ee1 !important;
        border-color: #3d5ee1 !important;
        color: #fff !important;
        font-weight: 600;
        z-index: 1;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
        color: #6c757d !important;
        background-color: #fff;
        pointer-events: none;
    }

    .cheque-mgmt-page .dataTables_wrapper .dataTables_paginate .page-item:not(.disabled):not(.active) .page-link:hover {
        color: #3d5ee1;
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }

    .cheque-mgmt-page .dataTables_wrapper .row:last-child {
        align-items: center;
    }

    /* Cheque details modal */
    #chequeDetailsModal .cheque-details-modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 12px 40px rgba(32, 33, 36, 0.18), 0 4px 12px rgba(32, 33, 36, 0.08);
    }

    #chequeDetailsModal .cheque-details-modal-body {
        padding: 16px !important;
    }

    #chequeDetailsModal .cheque-details-modal-footer {
        padding: 12px 16px !important;
        background: #fafbfc;
    }

    #chequeDetailsModal .cheque-details-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #5f6368;
        margin-bottom: 12px;
    }

    #chequeDetailsModal .cheque-details-dl dt {
        font-weight: 600;
        color: #5f6368;
        font-size: 13px;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    #chequeDetailsModal .cheque-details-dl dd {
        font-size: 14px;
        color: #202124;
        margin-bottom: 0;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #f1f3f4;
    }

    #chequeDetailsModal .cheque-details-dl dd:last-of-type,
    #chequeDetailsModal .cheque-details-dl dt:last-of-type + dd {
        border-bottom: none;
    }

    #chequeDetailsModal .cheque-details-status-badge {
        font-size: 0.8rem;
        font-weight: 700;
        padding: 0.4em 0.65em;
        letter-spacing: 0.02em;
    }

    #chequeDetailsModal .cheque-details-invoice-divider {
        border-top: 1px solid #dee2e6;
        margin: 8px 0 0;
    }

    #chequeDetailsModal .table-cheque-invoices tbody tr {
        transition: background-color 0.12s ease;
    }

    #chequeDetailsModal .table-cheque-invoices tbody tr:hover {
        background-color: #f4f7fb !important;
    }

    #chequeDetailsModal .table-cheque-invoices tfoot th {
        font-weight: 800 !important;
        color: #137333 !important;
        font-size: 15px;
        border-top: 2px solid #dee2e6 !important;
        background: #f8faf8 !important;
    }

    @media (max-width: 991.98px) {
        #chequeDetailsModal .modal-dialog {
            margin: 0.5rem;
        }
    }

    /* Bulk Recovery modal: wider on desktop */
    @media (min-width: 1200px) {
        #bulkRecoveryModal .modal-dialog {
            max-width: 1100px;
        }
    }

    /* Bulk Recovery modal: ensure inner scrolling works */
    #bulkRecoveryModal .modal-content {
        max-height: calc(100vh - 2rem);
    }

    #bulkRecoveryModal .modal-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        max-height: calc(100vh - 180px); /* header + footer reserve */
    }
</style>
@endpush

@section('content')
<div class="content container-fluid cheque-mgmt-page">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h1 class="cheque-mgmt-title">Cheque Management</h1>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Cheque Management</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Filters Card with Accordion -->
    <div class="row g-3 mb-1">
        <div class="col-12">
            <div class="card mb-0 cheque-filters-card border shadow-sm">
                <div class="card-body">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters"
                        title="Open filters: status, due date range, customer, cheque number">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                    <span class="small text-muted ms-2 d-none d-md-inline">Status · dates · customer · cheque no.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="collapse" id="collapseFilters">
                <div class="card card-body mb-4">
                    <form id="filterForm" class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="statusFilter" class="form-label small text-muted">Status</label>
                            <select class="form-control" id="statusFilter" name="status">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="deposited">Deposited</option>
                                <option value="cleared">Cleared</option>
                                <option value="bounced">Bounced</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="fromDate" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="fromDate" name="from_date">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="toDate" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="toDate" name="to_date">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <label for="customerFilter" class="form-label">Customer</label>
                            <select class="form-control selectBox" id="customerFilter" name="customer_id">
                                <option value="">All Customers</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <label for="chequeNumberFilter" class="form-label">Cheque Number</label>
                            <input type="text" class="form-control" id="chequeNumberFilter" name="cheque_number" placeholder="Search cheque...">
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="button" class="btn btn-secondary" id="clearFiltersBtn">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary (4 KPIs) — full width row (no max-width; matches filters / list) -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card cheque-stat-card stat-overdue h-100">
                <div class="card-body d-flex flex-column">
                    <div class="stat-label">Overdue</div>
                    <div class="stat-value">Rs. {{ number_format($stats['overdue_amount'] ?? 0, 2) }}</div>
                    <div class="stat-sub">{{ $stats['overdue_count'] ?? 0 }} <span class="stat-value-suffix">cheques</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card cheque-stat-card stat-due-soon h-100">
                <div class="card-body d-flex flex-column">
                    <div class="stat-label">Due soon</div>
                    <div class="stat-value">Rs. {{ number_format($stats['due_soon_amount'] ?? 0, 2) }}</div>
                    <div class="stat-sub">{{ $stats['due_soon_count'] ?? 0 }} <span class="stat-value-suffix">cheques</span> <span class="stat-sub-muted">· next 7 days</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card cheque-stat-card stat-bounced h-100">
                <div class="card-body d-flex flex-column">
                    <div class="stat-label">Bounced</div>
                    <div class="stat-value">Rs. {{ number_format($stats['total_bounced'] ?? 0, 2) }}</div>
                    <div class="stat-sub">{{ $stats['bounced_count'] ?? 0 }} <span class="stat-value-suffix">cheques</span> <span class="stat-sub-muted">· follow-up</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card cheque-stat-card stat-cleared h-100">
                <div class="card-body d-flex flex-column">
                    <div class="stat-label">Cleared</div>
                    <div class="stat-value">Rs. {{ number_format($stats['total_cleared'] ?? 0, 2) }}</div>
                    <div class="stat-sub">{{ $stats['cleared_count'] ?? 0 }} <span class="stat-value-suffix">cheques</span> <span class="stat-sub-muted">· settled</span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cheque list -->
    <div class="row g-3 mt-0">
        <div class="col-12">
            <div class="card cheque-list-card mb-0">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h2 class="card-title mb-0">Cheque list</h2>
                        <div class="d-flex flex-wrap align-items-center gap-1 cheque-toolbar cheque-bulk-toolbar">
                            @php
                                $showRecoveredRows = request()->boolean('show_recovered');
                            @endphp
                            <button type="button" class="btn btn-outline-secondary btn-sm cheque-bulk-btn" id="bulkClear" disabled title="Select deposited cheques on this page, then mark as cleared"><i class="fas fa-check"></i><span class="d-none d-md-inline ms-1">Clear</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm cheque-bulk-btn" id="bulkDeposit" disabled title="Select pending cheques on this page, then mark as deposited"><i class="fas fa-university"></i><span class="d-none d-md-inline ms-1">Deposit</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm cheque-bulk-btn" id="bulkBounce" disabled title="Select deposited cheques on this page, then mark as bounced"><i class="fas fa-times"></i><span class="d-none d-md-inline ms-1">Bounce</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm cheque-bulk-btn" id="bulkRecoveryPayment" disabled title="Select bounced cheques on this page for recovery"><i class="fas fa-money-bill-wave"></i><span class="d-none d-lg-inline ms-1">Recovery</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm {{ $showRecoveredRows ? 'active' : '' }}" id="toggleRecoveredRows" title="Toggle recovered bounced">
                                <i class="fas fa-{{ $showRecoveredRows ? 'eye' : 'eye-slash' }}"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshData" title="Refresh"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="mt-2 small text-muted">
                        <div><span id="selectedCount">0</span> selected <span class="d-none d-md-inline">· applies to <strong>this page</strong> only</span></div>
                        <div class="mt-1 cheque-bulk-hint"><i class="fas fa-info-circle me-1" aria-hidden="true"></i>Tick rows, then enable Clear / Deposit / Bounce / Recovery based on status.</div>
                        <div class="mt-2 d-flex flex-wrap align-items-center gap-3 cheque-overdue-legend" title="Left edge of a row matches overdue severity">
                            <span class="fw-semibold text-secondary me-1">Row bar:</span>
                            <span><span class="cheque-legend-bar cheque-legend-bar--sev1" aria-hidden="true"></span> 1–7d overdue</span>
                            <span><span class="cheque-legend-bar cheque-legend-bar--sev2" aria-hidden="true"></span> 8–30d</span>
                            <span><span class="cheque-legend-bar cheque-legend-bar--sev3" aria-hidden="true"></span> 31d+</span>
                            <span class="d-md-none small text-muted ms-1">· Swipe table sideways if needed</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="chequeTableLoadingHint" class="text-muted small">
                        <span><i class="fas fa-spinner fa-spin me-2"></i>Loading cheque list…</span>
                    </div>
                    <div class="table-responsive cheque-dt-pending" id="chequeTableResponsiveWrapper">
                        <table class="table mb-0 cheque-ui-table" id="chequesTable" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="selectAll" class="form-check-input form-check-input-sm" title="Select all cheques on this page (not all pages)">
                                    </th>
                                    <th class="d-none" aria-hidden="true">Customer</th>
                                    <th>Cheque no.</th>
                                    <th class="text-end">Amount</th>
                                    <th title="Click to sort by due date">Due date</th>
                                    <th>Status</th>
                                    <th width="108" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="chequesTableBody">
                                @php
                                    $rows = isset($chequeGroups) ? $chequeGroups : collect();
                                @endphp
                                @forelse($rows as $row)
                                @php
                                    $payment = $row['payment'];
                                    $paymentIds = $row['payment_ids'] ?? collect();
                                    $paymentIdsCsv = $paymentIds instanceof \Illuminate\Support\Collection ? $paymentIds->implode(',') : implode(',', (array) $paymentIds);
                                    $groupId = $row['group_id'] ?? '';
                                    $currentStatus = $row['status'] ?? ($payment->cheque_status ?? 'pending');
                                    $isSelectable = (bool)($row['is_selectable'] ?? true);
                                    $canUpdate = (bool)($row['can_update'] ?? false);
                                    $valid = $payment->cheque_valid_date ? \Carbon\Carbon::parse($payment->cheque_valid_date)->startOfDay() : null;
                                    $today = \Carbon\Carbon::today();
                                    $isOverdueUi = ($currentStatus === 'pending') && $valid && $valid->lt($today);
                                    $overdueDays = ($isOverdueUi && $valid) ? (int) $valid->diffInDays($today) : 0;
                                    $overdueSev = 0;
                                    if ($isOverdueUi && $overdueDays > 0) {
                                        $overdueSev = $overdueDays <= 7 ? 1 : ($overdueDays <= 30 ? 2 : 3);
                                    }
                                    $isDueToday = ($currentStatus === 'pending') && $valid && $valid->equalTo($today);
                                    if ($currentStatus === 'mixed') {
                                        $uiStatusLabel = 'Mixed';
                                        $uiStatusClass = 'default';
                                    } elseif ($currentStatus === 'cancelled') {
                                        $uiStatusLabel = 'Cancelled';
                                        $uiStatusClass = 'default';
                                    } elseif ($currentStatus === 'cleared') {
                                        $uiStatusLabel = 'Cleared';
                                        $uiStatusClass = 'cleared';
                                    } elseif ($currentStatus === 'bounced') {
                                        $uiStatusLabel = 'Bounced';
                                        $uiStatusClass = 'default';
                                    } elseif ($currentStatus === 'deposited') {
                                        $uiStatusLabel = 'Deposited';
                                        $uiStatusClass = 'default';
                                    } elseif ($isOverdueUi) {
                                        $uiStatusLabel = 'Overdue ' . $overdueDays . 'd';
                                        $uiStatusClass = 'overdue-' . $overdueSev;
                                    } elseif ($currentStatus === 'pending') {
                                        $uiStatusLabel = 'Pending';
                                        $uiStatusClass = 'pending';
                                    } else {
                                        $uiStatusLabel = ucfirst((string) $currentStatus);
                                        $uiStatusClass = 'default';
                                    }
                                @endphp
                                <tr class="{{ $overdueSev ? 'cheque-row--overdue cheque-row--overdue-' . $overdueSev : '' }}"
                                    data-status="{{ $currentStatus }}"
                                    data-overdue-days="{{ $overdueDays }}"
                                    data-amount="{{ $row['total_amount'] ?? $payment->amount }}"
                                    data-bank-charges="{{ $payment->bank_charges ?? 0 }}"
                                    data-customer-id="{{ $payment->customer_id }}"
                                    data-customer-name="{{ $payment->customer->full_name ?? 'Unknown' }}"
                                    data-has-recovery="{{ ($row['has_recovery'] ?? false) ? 1 : 0 }}"
                                    data-payment-ids="{{ $paymentIdsCsv }}"
                                    data-group-id="{{ $groupId }}">
                                    <td>
                                        @if($isSelectable)
                                        <input type="checkbox" class="form-check-input cheque-checkbox" value="{{ $payment->id }}" data-payment-ids="{{ $paymentIdsCsv }}">
                                        @else
                                        <i class="fas fa-lock text-muted" title="{{ $currentStatus === 'mixed' ? 'Mixed status - selection disabled' : 'Already recovered - selection disabled' }}"></i>
                                        @endif
                                    </td>
                                    <td class="d-none" aria-hidden="true">{{ $payment->customer->full_name ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="cheque-ui-cheque-no">{{ $payment->cheque_number ?? '—' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="cheque-ui-amount">Rs. {{ number_format($row['total_amount'] ?? $payment->amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="cheque-ui-due{{ $isDueToday ? ' cheque-due-today' : '' }}" @if($isDueToday) title="Due today" @endif>{{ $payment->cheque_valid_date ? \Carbon\Carbon::parse($payment->cheque_valid_date)->format('d-m-Y') : '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="cheque-ui-status cheque-ui-status--{{ $uiStatusClass }}">{{ $uiStatusLabel }}</span>@if($currentStatus === 'bounced' && ($payment->bank_charges ?? 0) > 0)<span class="small text-muted ms-1" style="font-size:10px;white-space:nowrap;">· chg. {{ number_format($payment->bank_charges ?? 0, 2) }}</span>@endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-1 cheque-actions" role="group">
                                            <button type="button" class="btn btn-icon js-view-cheque-details" data-cheque-tooltip="1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" title="View details — cheque number, bank, and all linked invoices" aria-label="View cheque details and linked invoices" data-payment-ids="{{ $paymentIdsCsv }}"><i class="fas fa-eye" aria-hidden="true"></i></button>
                                            @if($canUpdate)
                                            <button type="button" class="btn btn-icon" data-cheque-tooltip="1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" title="Edit status — deposit, clear, bounce, or cancel this cheque" aria-label="Change cheque status" onclick="updateChequeStatus('{{ $groupId }}', '{{ $currentStatus }}')"><i class="fas fa-pen" aria-hidden="true"></i></button>
                                            @else
                                            <button type="button" class="btn btn-icon" disabled data-cheque-tooltip="1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="body" title="Locked — this row cannot be edited (mixed status or recovered)" aria-label="Cheque locked for editing"><i class="fas fa-lock" aria-hidden="true"></i></button>
                                            @endif
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-icon border-0" data-bs-toggle="dropdown" aria-expanded="false" title="More: Status history; if bounced — Recovery chain" aria-label="Open more actions menu"><i class="fas fa-ellipsis-v" aria-hidden="true"></i></button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                    <li><button type="button" class="dropdown-item py-2" onclick="viewStatusHistory({{ $payment->id }})">Status history</button></li>
                                                    @if($currentStatus === 'bounced' && $payment->customer_id)
                                                    <li><button type="button" class="dropdown-item py-2" onclick="viewRecoveryChain({{ $payment->id }})">Recovery chain</button></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <h5>No cheques found</h5>
                                            <p>Try adjusting your filters or check back later.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Cheque Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="paymentIds">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-control" id="newStatus" name="status" required>
                            <!-- Options will be populated dynamically based on current status -->
                        </select>
                        <small class="form-text text-muted">
                            Status transitions: Pending → Deposited/Cancelled → Cleared/Bounced/Cancelled
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add any notes about this status change..."></textarea>
                    </div>
                    <div class="mb-3" id="bankChargesGroup" style="display: none;">
                        <label for="bankCharges" class="form-label">Bank Charges</label>
                        <input type="number" class="form-control" id="bankCharges" name="bank_charges" step="0.01" min="0" placeholder="0.00">
                        <small class="form-text text-muted">Any charges imposed by the bank for this cheque</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cheque Details Modal -->
<div class="modal fade" id="chequeDetailsModal" tabindex="-1" aria-labelledby="chequeDetailsModalLabel" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content cheque-details-modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="chequeDetailsModalLabel">Cheque details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="chequeDetailsContent" class="cheque-details-modal-body">
                    <!-- Loaded via AJAX -->
                </div>
            </div>
            <div class="modal-footer cheque-details-modal-footer border-top d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="chequeDetailsDepositBtn">
                        <i class="fas fa-university me-1" aria-hidden="true"></i>Deposit
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="chequeDetailsEditBtn">
                        <i class="fas fa-pen me-1" aria-hidden="true"></i>Edit
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="chequeDetailsPrintBtn" title="Print this summary">
                        <i class="fas fa-print me-1" aria-hidden="true"></i>Print
                    </button>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Status History Modal -->
<div class="modal fade" id="statusHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cheque Status History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statusHistoryContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Bulk Bounce Modal -->
<div class="modal fade" id="bulkBounceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Bounce Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkBounceForm">
                <div class="modal-body">
                    <div class="alert alert-warning py-2">
                        <small><i class="fas fa-exclamation-triangle"></i> Bank charges will be applied to each selected cheque.</small>
                    </div>
                    <div class="mb-3">
                        <label for="bulkBounceBankCharges" class="form-label">Bank Charges <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="bulkBounceBankCharges" step="0.01" min="0" required placeholder="Enter bank charges">
                    </div>
                    <div class="mb-3">
                        <label for="bulkBounceRemarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="bulkBounceRemarks" rows="3" placeholder="Reason for cheque bounce"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Bounce</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Recovery Payment Modal -->
<div class="modal fade" id="bulkRecoveryModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recovery Payment for Multiple Bounced Cheques</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkRecoveryForm">
                <div class="modal-body">
                    <!-- Selected Cheques Summary -->
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle"></i> Selected Bounced Cheques
                        </h6>
                        <div id="selectedChequesInfo">
                            <!-- Will be populated with selected cheques details -->
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total Bounced Amount:</strong>
                                <span id="totalBouncedAmount" class="text-danger">Rs. 0.00</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Bank Charges:</strong>
                                <span id="totalBankCharges" class="text-warning">Rs. 0.00</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <strong>Total Recovery Required:</strong>
                                <span id="totalRecoveryAmount" class="text-primary fs-5">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Payment Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <label for="recoveryMethod" class="form-label fw-bold">Recovery Payment Method</label>
                            <select class="form-control" id="recoveryMethod" name="recovery_method" required>
                                <option value="">Select Recovery Method</option>
                                <option value="cash">Cash Payment</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card Payment</option>
                                <option value="new_cheque">New Cheque</option>
                                <option value="multiple_cheques">Multiple Cheques (Split)</option>
                                <option value="partial_cash_cheque">Partial Cash + New Cheque</option>
                                <option value="partial_cash_multiple_cheques">Partial Cash + Multiple Cheques</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recoveryDate" class="form-label fw-bold">Recovery Date</label>
                            <input type="date" class="form-control" id="recoveryDate" name="recovery_date" required>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="recoveryReferenceNo" class="form-label fw-bold">Reference No</label>
                            <input type="text" class="form-control" id="recoveryReferenceNo" name="reference_number" placeholder="Enter reference no (optional)">
                        </div>
                    </div>

                    <!-- Dynamic Payment Fields -->
                    <div id="recoveryPaymentFields" class="mt-3">
                        <!-- Will be populated based on selected method -->
                    </div>

                    <!-- Recovery Notes -->
                    <div class="mt-3">
                        <label for="recoveryNotes" class="form-label fw-bold">Recovery Notes</label>
                        <textarea class="form-control" id="recoveryNotes" name="recovery_notes" rows="3"
                                  placeholder="Notes about this recovery payment..."></textarea>
                    </div>

                    <!-- Payment Summary -->
                    <div id="paymentSummary" class="mt-3" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Payment Summary</h6>
                                <div id="summaryContent">
                                    <!-- Will be populated with payment breakdown -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Process Recovery Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).on('click', '.js-view-cheque-details', function(e) {
        e.preventDefault();
        viewChequeDetailsFromRow(this);
    });

    // Load customers for filter dropdown
    loadCustomers();

    // Pre-populate filters from URL parameters
    populateFiltersFromURL();

    // Initialize filter status indicators
    updateFilterIndicators();

    // Select all checkbox functionality
    $('#selectAll').on('change', function() {
        $('.cheque-checkbox:not(:disabled)').prop('checked', this.checked);
        updateBulkActionButtons();
    });

    // Individual checkbox change
    $(document).on('change', '.cheque-checkbox', function() {
        updateBulkActionButtons();
        updateSelectAllCheckbox();
    });

    // Initialize DataTable
    initializeDataTable();

    function initChequeActionTooltips() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
            return;
        }
        document.querySelectorAll('#chequesTable [data-cheque-tooltip="1"]').forEach(function (el) {
            bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus' });
        });
    }
    initChequeActionTooltips();

    // Auto-apply filters on change without page refresh
    $('#statusFilter, #customerFilter, #fromDate, #toDate, #chequeNumberFilter').on('change input', function() {
        if (chequesDataTable) {
            applyClientSideFilters();
        }
    });

    // Clear Filters button
    $('#clearFiltersBtn').on('click', function() {
        $('#statusFilter').val('').trigger('change');
        $('#customerFilter').val('').trigger('change');
        $('#fromDate').val('');
        $('#toDate').val('');
        $('#chequeNumberFilter').val('');

        // Safely clear DataTable filters
        if (chequesDataTable) {
            try {
                chequesDataTable.search('').columns().search('').draw();
            } catch (e) {
                console.warn('Clear filters warning:', e);
            }
        }
    });

    // Allow Enter key to apply filters from text inputs
    $('#chequeNumberFilter, #fromDate, #toDate').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            applyClientSideFilters();
        }
    });

    // Client-side filtering function
    function applyClientSideFilters() {
        if (!chequesDataTable) return;

        const status = $('#statusFilter').val();
        const chequeNumber = $('#chequeNumberFilter').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        // Trigger DataTable redraw which will use the custom search function
        chequesDataTable.draw();
    }

    // Custom search function for DataTable
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            // Only apply to our specific table
            if (settings.nTable.id !== 'chequesTable') {
                return true;
            }

            const status = $('#statusFilter').val();
            const customerFilter = $('#customerFilter').val();
            const chequeNumber = $('#chequeNumberFilter').val().toLowerCase();
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();

            // Get row element to access data attributes
            const row = $('#chequesTable').DataTable().row(dataIndex).node();

            // Get row data - data array contains plain text from columns
            const rowChequeNumber = data[2] ? data[2].toLowerCase().replace(/<[^>]*>/g, '').trim() : ''; // Cheque # (strip HTML)
            const rowDate = data[4] ? data[4].replace(/<[^>]*>/g, '').trim() : ''; // Due date column
            const rowStatus = String($(row).data('status') || '').toLowerCase();
            const rowHasRecovery = String($(row).attr('data-has-recovery') || '0') === '1';

            // Hide recovered bounced rows by default; show only when explicitly requested.
            if (!showRecoveredBouncedRows && rowStatus === 'bounced' && rowHasRecovery) {
                return false;
            }

            // Filter by status
            if (status && status !== '' && status !== 'all') {
                if (!rowStatus.includes(status.toLowerCase())) {
                    return false;
                }
            }

            // Filter by customer - USE CUSTOMER ID from data attribute
            if (customerFilter && customerFilter !== '' && customerFilter !== '0') {
                const rowCustomerId = $(row).data('customer-id') || $(row).attr('data-customer-id');
                const filterCustomerId = String(customerFilter).trim();
                const rowCustomerIdStr = String(rowCustomerId).trim();

                if (rowCustomerIdStr !== filterCustomerId) {
                    return false;
                }
            }

            // Filter by cheque number
            if (chequeNumber && chequeNumber !== '') {
                if (!rowChequeNumber.includes(chequeNumber)) {
                    return false;
                }
            }

            // Filter by date range
            if ((fromDate && fromDate !== '') || (toDate && toDate !== '')) {
                if (rowDate && rowDate !== 'n/a') {
                    // Parse date from dd-mm-yyyy format
                    const dateParts = rowDate.split('-');
                    if (dateParts.length === 3) {
                        const rowDateObj = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);

                        if (fromDate && fromDate !== '') {
                            const fromDateObj = new Date(fromDate);
                            if (rowDateObj < fromDateObj) return false;
                        }

                        if (toDate && toDate !== '') {
                            const toDateObj = new Date(toDate);
                            if (rowDateObj > toDateObj) return false;
                        }
                    } else {
                        // If date can't be parsed and we have filters, exclude it
                        return false;
                    }
                } else if (fromDate || toDate) {
                    // No valid date but filters are set, exclude this row
                    return false;
                }
            }

            return true;
        }
    );

    // Refresh data
    $('#refreshData').on('click', function() {
        location.reload();
    });

    // Show/hide recovered bounced rows on demand.
    $('#toggleRecoveredRows').on('click', function() {
        const url = new URL(window.location.href);
        const currentlyShowingRecovered = url.searchParams.get('show_recovered') === '1';

        if (currentlyShowingRecovered) {
            url.searchParams.delete('show_recovered');
        } else {
            url.searchParams.set('show_recovered', '1');
        }

        window.location.href = url.toString();
    });

    // Enhanced table scrolling functionality
    initializeTableScrolling();

    // Update status form
    $('#updateStatusForm').on('submit', function(e) {
        e.preventDefault();
        updateChequeStatusSubmit();
    });

    // Show/hide bank charges field based on status
    $('#newStatus').on('change', function() {
        if (this.value === 'bounced') {
            $('#bankChargesGroup').show();
        } else {
            $('#bankChargesGroup').hide();
            $('#bankCharges').val('');
        }
    });

    // Bulk actions
    $('#bulkClear').on('click', function() {
        bulkUpdateStatus('cleared');
    });

    $('#bulkDeposit').on('click', function() {
        bulkUpdateStatus('deposited');
    });

    $('#bulkBounce').on('click', function() {
        openBulkBounceModal();
    });

    $('#bulkBounceForm').on('submit', function(e) {
        e.preventDefault();
        submitBulkBounce();
    });

    // Bulk Recovery Payment
    $('#bulkRecoveryPayment').on('click', function() {
        openBulkRecoveryModal();
    });

    // Recovery method change
    $('#recoveryMethod').on('change', function() {
        updateRecoveryPaymentFields();
    });

    // Bulk recovery form submission
    $('#bulkRecoveryForm').on('submit', function(e) {
        e.preventDefault();
        processBulkRecoveryPayment();
    });

    $('#chequeDetailsDepositBtn').on('click', function() {
        const ctx = $('#chequeDetailsModal').data('ctx');
        if (!ctx || !ctx.paymentIdsCsv) {
            return;
        }
        hideChequeDetailsModal();
        setTimeout(function () {
            prepareChequeStatusModal(ctx.paymentIdsCsv, ctx.currentStatus, 'deposited');
        }, 200);
    });

    $('#chequeDetailsEditBtn').on('click', function() {
        const ctx = $('#chequeDetailsModal').data('ctx');
        if (!ctx || !ctx.paymentIdsCsv) {
            return;
        }
        hideChequeDetailsModal();
        setTimeout(function () {
            prepareChequeStatusModal(ctx.paymentIdsCsv, ctx.currentStatus, null);
        }, 200);
    });

    $('#chequeDetailsPrintBtn').on('click', function() {
        printChequeDetailsModal();
    });

    $('#chequeDetailsModal').on('hidden.bs.modal', function () {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            $(this).find('[data-bs-toggle="tooltip"]').each(function () {
                const inst = bootstrap.Tooltip.getInstance(this);
                if (inst) {
                    inst.dispose();
                }
            });
        }
    });

});

function initializeTableScrolling() {
    const tableWrapper = $('#chequeTableResponsiveWrapper');
    const table = $('#chequesTable');

    if (tableWrapper.length === 0 || table.length === 0) return;

    // Add scroll shadow indicators
    function updateScrollShadows() {
        const scrollLeft = tableWrapper.scrollLeft();
        const scrollWidth = tableWrapper[0].scrollWidth;
        const clientWidth = tableWrapper[0].clientWidth;
        const maxScrollLeft = scrollWidth - clientWidth;

        // Remove existing shadows
        tableWrapper.removeClass('scroll-left scroll-right');

        // Add shadows based on scroll position
        if (scrollLeft > 0) {
            tableWrapper.addClass('scroll-left');
        }
        if (scrollLeft < maxScrollLeft - 1) {
            tableWrapper.addClass('scroll-right');
        }
    }

    // Check if table needs horizontal scrolling
    function checkScrollNeeded() {
        const tableWidth = table[0].scrollWidth;
        const containerWidth = tableWrapper[0].clientWidth;

        if (tableWidth > containerWidth) {
            tableWrapper.addClass('needs-scroll');
            updateScrollShadows();
        } else {
            tableWrapper.removeClass('needs-scroll scroll-left scroll-right');
        }
    }

    // Event listeners
    tableWrapper.on('scroll', updateScrollShadows);
    $(window).on('resize', checkScrollNeeded);

    // Initial check
    setTimeout(checkScrollNeeded, 100);

    // Add keyboard navigation
    tableWrapper.on('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            tableWrapper.scrollLeft(tableWrapper.scrollLeft() - 100);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            tableWrapper.scrollLeft(tableWrapper.scrollLeft() + 100);
        }
    });

    // Make table focusable for keyboard navigation
    tableWrapper.attr('tabindex', '0');
}

function populateFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    // Populate each filter field from URL parameters
    if (urlParams.has('status')) {
        $('#statusFilter').val(urlParams.get('status'));
    }
    if (urlParams.has('customer_id')) {
        $('#customerFilter').val(urlParams.get('customer_id'));
    }
    if (urlParams.has('from_date')) {
        $('#fromDate').val(urlParams.get('from_date'));
    }
    if (urlParams.has('to_date')) {
        $('#toDate').val(urlParams.get('to_date'));
    }
    if (urlParams.has('cheque_number')) {
        $('#chequeNumberFilter').val(urlParams.get('cheque_number'));
    }

}

function updateFilterIndicators() {
    const hasActiveFilters = $('#statusFilter').val() !== '' ||
                           $('#customerFilter').val() !== '' ||
                           $('#fromDate').val() !== '' ||
                           $('#toDate').val() !== '' ||
                           $('#chequeNumberFilter').val() !== '';

    // Add or remove active filter indicator
    const filterHeader = $('.card:has(#filterForm) .card-header h5');
    filterHeader.find('.filter-indicator').remove();

    if (hasActiveFilters) {
        filterHeader.append(' <span class="badge bg-primary filter-indicator">Filters Active</span>');

        // Add clear filters button if not exists
        if ($('#clearFilters').length === 0) {
            filterHeader.parent().append(`
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            `);
        }
    } else {
        $('#clearFilters').remove();
    }
}

function clearAllFilters() {
    $('#statusFilter').val('');
    $('#customerFilter').val('').trigger('change');
    $('#fromDate').val('');
    $('#toDate').val('');
    $('#chequeNumberFilter').val('');

    // Safely clear all DataTable custom filters
    try {
        $.fn.dataTable.ext.search = [];
    } catch (e) {
        console.warn('Clear search filters warning:', e);
    }

    updateFilterIndicators();

    // Redraw table without filters
    if (chequesDataTable) {
        try {
            chequesDataTable.draw();
        } catch (e) {
            console.warn('DataTable draw warning:', e);
        }
    }
}

function loadCustomers() {
    $.ajax({
        url: '/customer-get-all',
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            const customerSelect = $('#customerFilter');
            customerSelect.empty().append('<option value="">All Customers</option>');

            if (response && response.status === 200 && response.message && Array.isArray(response.message)) {
                const customers = response.message;

                if (customers.length > 0) {
                    customers.forEach(function(customer) {
                        customerSelect.append(`<option value="${customer.id}">${customer.full_name}</option>`);
                    });

                    // Reinitialize Select2 after populating options
                    if (customerSelect.hasClass('select2-hidden-accessible')) {
                        customerSelect.select2('destroy');
                    }
                    customerSelect.select2({
                        placeholder: 'All Customers',
                        allowClear: true
                    });

                } else {
                    console.warn('No customers found in the response');
                }
            } else {
                console.warn('Invalid response format for customers:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load customers for cheque management:', error);
            console.error('Status:', status);
            console.error('XHR Status:', xhr.status);
            console.error('Response:', xhr.responseText);

            // Show specific error message based on status
            const customerSelect = $('#customerFilter');
            let errorMessage = 'Error loading customers';

            if (xhr.status === 401) {
                errorMessage = 'Authentication required';
            } else if (xhr.status === 403) {
                errorMessage = 'Access denied';
            } else if (xhr.status === 404) {
                errorMessage = 'Endpoint not found';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error';
            }

            customerSelect.empty().append(`<option value="">All Customers (${errorMessage})</option>`);

        }
    });
}

function loadCustomersWithBouncedCheques(selectElementId = '#recoveryCustomerSelect') {
    console.log('Loading customers with bounced cheques for recovery payment...');

    $.ajax({
        url: '/customers-with-bounced-cheques',
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Customers with bounced cheques response:', response);
            const customerSelect = $(selectElementId);
            customerSelect.empty().append('<option value="">Select Customer with Bounced Cheques</option>');

            if (response && response.status === 200 && response.message && Array.isArray(response.message)) {
                const customers = response.message;

                if (customers.length > 0) {
                    customers.forEach(function(customer) {
                        const displayText = `${customer.full_name} (${customer.bounced_cheques_count} bounced, ₹${customer.floating_balance.toLocaleString()} floating)`;
                        customerSelect.append(`<option value="${customer.id}">${displayText}</option>`);
                    });

                    // Reinitialize Select2 if it exists
                    if (customerSelect.hasClass('select2-hidden-accessible')) {
                        customerSelect.select2('destroy');
                    }
                    customerSelect.select2({
                        placeholder: 'Select customer with bounced cheques...',
                        allowClear: true
                    });

                    console.log(`Successfully loaded ${customers.length} customers with bounced cheques`);
                } else {
                    customerSelect.append('<option value="" disabled>No customers with bounced cheques found</option>');
                    console.warn('No customers with bounced cheques found');
                }
            } else {
                console.warn('Invalid response format for customers with bounced cheques:', response);
                customerSelect.append('<option value="" disabled>Error loading customers</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load customers with bounced cheques:', error);
            const customerSelect = $(selectElementId);
            customerSelect.empty().append('<option value="" disabled>Error loading customers</option>');
        }
    });
}

function updateBulkActionButtons() {
    const selectedCount = $('.cheque-checkbox:checked').length;
    const $tb = $('.cheque-bulk-toolbar');
    $('#selectedCount').text(selectedCount);

    // Disable all bulk actions initially
    $('#bulkClear, #bulkDeposit, #bulkBounce, #bulkRecoveryPayment').prop('disabled', true);
    $tb.removeClass('cheque-bulk-has-selection');

    if (selectedCount > 0) {
        $tb.addClass('cheque-bulk-has-selection');
        // Get statuses of selected cheques
        const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
            return $(this).closest('tr').data('status');
        }).get();

        // Check if all selected cheques can be cleared (must be deposited)
        const canClear = selectedStatuses.every(status => status === 'deposited');

        // Check if all selected cheques can be deposited (must be pending)
        const canDeposit = selectedStatuses.every(status => status === 'pending');

        // Check if all selected cheques can be bounced (must be deposited)
        const canBounce = selectedStatuses.every(status => status === 'deposited');

        // Check if all selected cheques are bounced (for recovery payment)
        const canRecovery = selectedStatuses.every(status => status === 'bounced');

        // Enable appropriate buttons
        if (canClear) {
            $('#bulkClear').prop('disabled', false);
        }
        if (canDeposit) {
            $('#bulkDeposit').prop('disabled', false);
        }
        if (canBounce) {
            $('#bulkBounce').prop('disabled', false);
        }
        if (canRecovery) {
            $('#bulkRecoveryPayment').prop('disabled', false);
        }

        // If no valid actions available, show a message
        if (!canClear && !canDeposit && !canBounce && !canRecovery) {
            $('#selectedCount').html(selectedCount + ' <small class="text-muted">(no valid bulk actions for current selection)</small>');
        } else {
            // Show what actions are available
            const availableActions = [];
            if (canClear) availableActions.push('Clear');
            if (canDeposit) availableActions.push('Deposit');
            if (canBounce) availableActions.push('Bounce');
            if (canRecovery) availableActions.push('Recovery');

            if (availableActions.length > 0) {
                $('#selectedCount').html(selectedCount + ' <small class="text-success">(can: ' + availableActions.join(', ') + ')</small>');
            }
        }
    }
}

function updateSelectAllCheckbox() {
    const totalCheckboxes = $('.cheque-checkbox:not(:disabled)').length;
    const checkedCheckboxes = $('.cheque-checkbox:checked').length;

    if (totalCheckboxes === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
        return;
    }

    if (checkedCheckboxes === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
        $('#selectAll').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#selectAll').prop('indeterminate', true);
    }
}

// Initialize DataTables with row grouping by cheque number
let chequesDataTable;
let showRecoveredBouncedRows = new URLSearchParams(window.location.search).get('show_recovered') === '1';

function initializeDataTable() {
    // Safely destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#chequesTable')) {
        try {
            $('#chequesTable').DataTable().destroy(true); // true = remove DataTable from DOM
        } catch (e) {
            console.warn('DataTable destroy warning:', e);
        }
    }

    let chequeTableRevealDone = false;
    function revealChequeTableAfterInit() {
        if (chequeTableRevealDone) {
            return;
        }
        chequeTableRevealDone = true;
        $('#chequeTableLoadingHint').remove();
        $('#chequeTableResponsiveWrapper').removeClass('cheque-dt-pending').addClass('cheque-dt-ready');
    }

    try {
    chequesDataTable = $('#chequesTable').DataTable({
        // Customer-wise grouping; columns: 0 chk, 1 customer (hidden), 2 chq, 3 amt, 4 due, 5 status, 6 bank, 7 actions
        order: [[1, 'asc'], [4, 'asc'], [2, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        pagingType: "full_numbers",
        orderClasses: true,
        columnDefs: [
            {
                targets: [0, 6], // Checkbox and Actions
                orderable: false,
                searchable: false
            },
            {
                targets: [1], // Customer — hidden; RowGroup + search
                visible: false,
                searchable: true
            }
        ],
        rowGroup: {
            dataSrc: 1, // Customer (hidden column)
            startRender: function (rows, group) {
                let totalAmount = 0;
                const chequeCount = rows.count();
                let groupOverdue = 0;
                let groupPending = 0;
                let groupDeposited = 0;

                rows.every(function() {
                    const rowNode = this.node();
                    const $row = $(rowNode);
                    let raw = $row.data('amount');
                    if (raw === undefined || raw === '') {
                        raw = $row.attr('data-amount');
                    }
                    const amount = parseFloat(raw, 10);
                    if (!isNaN(amount)) {
                        totalAmount += Math.abs(amount);
                    }
                    const st = String($row.data('status') || '').toLowerCase();
                    const od = parseInt($row.attr('data-overdue-days') || '0', 10) || 0;
                    if (st === 'pending' && od > 0) {
                        groupOverdue++;
                    } else if (st === 'pending') {
                        groupPending++;
                    } else if (st === 'deposited') {
                        groupDeposited++;
                    }
                });

                const formattedTotal = totalAmount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                const rawName = String(group || '').trim();
                const nameParts = rawName.split(/\s+-\s+/);
                const displayName = nameParts.length > 1 ? nameParts[0].trim() : rawName;

                const $tr = $('<tr class="dtrg-group cheque-dtrg-row"></tr>');
                const $td = $('<td colspan="7" class="cheque-dtrg-cell"></td>');
                const $inner = $('<div class="cheque-dtrg-inner"></div>');
                if (displayName !== rawName) {
                    $inner.attr('title', rawName);
                }

                const $customerLine = $('<div class="cheque-dtrg-customer"></div>');
                $customerLine.append($('<i class="fas fa-user cheque-dtrg-icon" aria-hidden="true"></i>'));
                $customerLine.append($('<span class="cheque-dtrg-customer-name"></span>').text(displayName));

                const $metaLine = $('<div class="cheque-dtrg-meta"></div>');
                const $totalSpan = $('<span class="cheque-dtrg-total"></span>');
                $totalSpan.append($('<span class="cheque-dtrg-meta-label"></span>').text('Total:'));
                $totalSpan.append(document.createTextNode(' '));
                $totalSpan.append($('<strong class="cheque-dtrg-total-amount"></strong>').text('Rs. ' + formattedTotal));

                const $sep = $('<span class="cheque-dtrg-sep"></span>').text('|');

                const $countSpan = $('<span class="cheque-dtrg-count"></span>');
                $countSpan.append($('<strong></strong>').text(String(chequeCount)));
                $countSpan.append(document.createTextNode(' '));
                $countSpan.append($('<span class="cheque-dtrg-count-word"></span>').text(chequeCount === 1 ? 'Cheque' : 'Cheques'));

                const summaryBits = [];
                if (groupOverdue > 0) {
                    summaryBits.push(groupOverdue + ' overdue');
                }
                if (groupPending > 0) {
                    summaryBits.push(groupPending + ' pending');
                }
                if (groupDeposited > 0) {
                    summaryBits.push(groupDeposited + ' deposited');
                }
                $metaLine.append($totalSpan, $sep, $countSpan);
                if (summaryBits.length) {
                    const $sum = $('<span class="cheque-dtrg-group-status small text-muted ms-2"></span>');
                    $sum.text('· ' + summaryBits.join(' · '));
                    $metaLine.append($sum);
                }
                $inner.append($customerLine, $metaLine);
                $td.append($inner);
                $tr.append($td);
                return $tr;
            }
        },
        initComplete: function() {
            revealChequeTableAfterInit();
            const wrap = $('#chequesTable').closest('.dataTables_wrapper');
            if (!wrap.find('.cheque-page-jump').length) {
                const len = wrap.find('.dataTables_length');
                const jump = $('<div class="cheque-page-jump d-flex align-items-center gap-1 ms-lg-3 mt-2 mt-lg-0 flex-wrap"></div>');
                jump.append('<label class="small text-muted mb-0" for="chequePageJumpInput">Go to page</label>');
                jump.append('<input type="number" min="1" class="form-control form-control-sm" id="chequePageJumpInput" style="width:4rem" title="Enter page number, then Go or Enter">');
                jump.append('<button type="button" class="btn btn-sm btn-outline-secondary" id="chequePageJumpBtn">Go</button>');
                len.parent().addClass('d-flex flex-wrap align-items-center');
                len.after(jump);
                $('#chequePageJumpBtn').on('click', function() {
                    const tbl = $('#chequesTable').DataTable();
                    const info = tbl.page.info();
                    let p = parseInt($('#chequePageJumpInput').val(), 10);
                    if (isNaN(p) || p < 1) {
                        p = 1;
                    }
                    if (p > info.pages) {
                        p = info.pages;
                    }
                    tbl.page(p - 1).draw(false);
                    $('#chequePageJumpInput').val('');
                });
                $('#chequePageJumpInput').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        $('#chequePageJumpBtn').trigger('click');
                    }
                });
            }
        },
        drawCallback: function(settings) {
            revealChequeTableAfterInit();
            updateSelectAllCheckbox();
            updateBulkActionButtons();
            if ($.fn.DataTable.isDataTable('#chequesTable') && $('#chequePageJumpInput').length) {
                const info = $('#chequesTable').DataTable().page.info();
                $('#chequePageJumpInput').attr('max', info.pages).attr('placeholder', '1–' + info.pages);
            }
            $('.cheque-mgmt-page .dataTables_paginate ul.pagination').addClass('pagination-sm');
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                document.querySelectorAll('#chequesTable [data-cheque-tooltip="1"]').forEach(function (el) {
                    bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover focus' });
                });
            }
        },
        language: {
            search: "Search cheques:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ cheques",
            infoEmpty: "No cheques available",
            infoFiltered: "(filtered from _MAX_ total cheques)",
            zeroRecords: "No matching cheques found",
            emptyTable: "No cheques available",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
    } catch (err) {
        console.warn('DataTable init failed:', err);
        revealChequeTableAfterInit();
    }
}

function formatDisplayDate(dateValue) {
    if (!dateValue) {
        return 'N/A';
    }

    const parsedDate = new Date(dateValue);
    if (isNaN(parsedDate.getTime())) {
        return dateValue;
    }

    return parsedDate.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

function formatChequeModalDate(dateValue) {
    if (!dateValue || dateValue === 'N/A') {
        return '';
    }
    const parsedDate = new Date(dateValue);
    if (isNaN(parsedDate.getTime())) {
        return String(dateValue);
    }
    return parsedDate.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

function escapeHtml(str) {
    if (str === null || str === undefined) {
        return '';
    }
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function modalsDisplayValue(raw) {
    if (raw === null || raw === undefined) {
        return '<span class="text-muted fst-italic">Not provided</span>';
    }
    const s = String(raw).trim();
    if (s === '' || s.toUpperCase() === 'N/A') {
        return '<span class="text-muted fst-italic">Not provided</span>';
    }
    return escapeHtml(s);
}

function formatBankBranchDisplay(raw) {
    if (!raw || !String(raw).trim() || String(raw).trim().toUpperCase() === 'N/A') {
        return '';
    }
    const t = String(raw).trim();
    if (!/\s/.test(t) && t.length <= 10) {
        return escapeHtml(t.toUpperCase());
    }
    return escapeHtml(t.replace(/\w\S*/g, function (w) {
        return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
    }));
}

function bankFieldHtml(cheque) {
    const formatted = formatBankBranchDisplay(cheque.cheque_bank_branch);
    if (!formatted) {
        return '<span class="text-muted fst-italic">Not provided</span>';
    }
    return formatted;
}

function chequeStatusBadgeHtml(statusRaw) {
    const s = (statusRaw || '').toString().toLowerCase().trim();
    const meta = {
        pending: { cls: 'bg-warning text-dark', tip: 'Cheque is received but not yet deposited to the bank.', lab: 'Pending' },
        deposited: { cls: 'bg-primary', tip: 'Cheque has been deposited; awaiting bank clearance.', lab: 'Deposited' },
        cleared: { cls: 'bg-success', tip: 'Payment has cleared successfully.', lab: 'Cleared' },
        bounced: { cls: 'bg-danger', tip: 'Cheque was dishonoured or bounced by the bank.', lab: 'Bounced' },
        cancelled: { cls: 'bg-secondary', tip: 'Cheque was cancelled.', lab: 'Cancelled' },
        mixed: { cls: 'bg-secondary', tip: 'Linked payment rows have different statuses.', lab: 'Mixed' }
    };
    const m = meta[s] || {
        cls: 'bg-secondary',
        tip: 'Current cheque status in the system.',
        lab: statusRaw ? (statusRaw.charAt(0).toUpperCase() + statusRaw.slice(1).toLowerCase()) : 'Unknown'
    };
    return '<span class="badge cheque-details-status-badge ' + m.cls + '" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-container="#chequeDetailsModal" title="' + escapeHtml(m.tip) + '">' + escapeHtml(m.lab) + '</span>';
}

function billStatusBadgeHtml(statusRaw) {
    if (!statusRaw) {
        return '<span class="text-muted fst-italic">Not provided</span>';
    }
    const s = String(statusRaw).trim().toLowerCase();
    let cls = 'bg-secondary';
    const lab = String(statusRaw).trim();
    if (s === 'paid') {
        cls = 'bg-success';
    } else if (s === 'partial' || s.indexOf('partial') !== -1) {
        cls = 'bg-warning text-dark';
    } else if (s === 'unpaid' || s === 'due' || s.indexOf('unpaid') !== -1) {
        cls = 'bg-danger';
    }
    return '<span class="badge ' + cls + '">' + escapeHtml(lab) + '</span>';
}

function hideChequeDetailsModal() {
    const el = document.getElementById('chequeDetailsModal');
    if (!el) {
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const instance = bootstrap.Modal.getInstance(el);
        if (instance) {
            instance.hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(el).hide();
        }
        return;
    }
    $(el).modal('hide');
}

function printChequeDetailsModal() {
    const body = document.getElementById('chequeDetailsContent');
    if (!body) {
        return;
    }
    const w = window.open('', '_blank');
    const href = "{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}";
    w.document.write('<!DOCTYPE html><html><head><title>Cheque details</title>');
    w.document.write('<link rel="stylesheet" href="' + href + '">');
    w.document.write('<style>body{padding:16px;font-family:system-ui,sans-serif;font-size:14px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #dee2e6;padding:6px;font-size:13px}</style>');
    w.document.write('</head><body><h4 class="mb-3">Cheque details</h4>');
    w.document.write(body.innerHTML);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function () {
        w.print();
        w.close();
    }, 300);
}

function prepareChequeStatusModal(paymentIdsCsv, currentStatus, preselectStatus) {
    $('#updateStatusForm')[0].reset();
    $('#bankChargesGroup').hide();
    $('#paymentIds').val(paymentIdsCsv || '');

    const validTransitions = {
        'pending': ['deposited', 'cancelled'],
        'deposited': ['cleared', 'bounced', 'cancelled'],
        'cleared': [],
        'bounced': [],
        'cancelled': [],
        'mixed': ['deposited', 'cancelled']
    };

    const key = (currentStatus || 'pending').toString().toLowerCase();
    const availableOptions = validTransitions[key] || [];

    const statusSelect = $('#newStatus');
    statusSelect.empty();
    statusSelect.append('<option value="">Select status</option>');

    availableOptions.forEach(function (status) {
        const label = status.charAt(0).toUpperCase() + status.slice(1);
        statusSelect.append('<option value="' + status + '">' + label + '</option>');
    });

    if (availableOptions.length === 0) {
        statusSelect.append('<option value="" disabled>No status changes allowed</option>');
        statusSelect.prop('disabled', true);
    } else {
        statusSelect.prop('disabled', false);
        if (preselectStatus && availableOptions.indexOf(preselectStatus) !== -1) {
            statusSelect.val(preselectStatus);
        }
    }

    $('#updateStatusModal').modal('show');
}

function showChequeManagementModal(modalId) {
    const el = document.getElementById(modalId);
    if (!el) {
        return false;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
        return true;
    }
    if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery(el).modal('show');
        return true;
    }
    return false;
}

function viewChequeDetailsFromRow(btnEl) {
    if (!btnEl) {
        return;
    }
    const $btn = $(btnEl);
    const rowEl = $btn.closest('tr');
    const paymentIdsCsv = ($btn.attr('data-payment-ids') || rowEl.attr('data-payment-ids') || '').trim();
    const groupId = rowEl.attr('data-group-id') || '';
    const rowStatus = (rowEl.attr('data-status') || 'pending').toString().toLowerCase();

    if (!paymentIdsCsv) {
        toastr.error('Unable to load cheque details (missing payment IDs)', 'Error');
        return;
    }

    $.ajax({
        url: '{{ route("cheque.group-details") }}',
        method: 'GET',
        data: { payment_ids: paymentIdsCsv },
        success: function(response) {
            if (response.status === 200) {
                const cheque = response.cheque || {};
                const customer = response.customer || {};
                const invoices = response.invoices || [];
                const totalAmount = response.total_amount || 0;

                const effectiveStatus = (cheque.cheque_status || rowStatus || 'pending').toString().toLowerCase();

                $('#chequeDetailsModal').data('ctx', {
                    paymentIdsCsv: paymentIdsCsv,
                    groupId: groupId,
                    currentStatus: effectiveStatus
                });

                const customerName = customer.full_name
                    || [customer.prefix, customer.first_name, customer.last_name].filter(Boolean).join(' ').trim()
                    || '';
                const customerPhone = customer.mobile_no || '';
                const customerAddress = customer.address || '';
                const balRaw = customer.current_balance;
                let balanceLine = '';
                if (balRaw !== null && balRaw !== undefined && balRaw !== '' && !Number.isNaN(Number(balRaw))) {
                    balanceLine = 'Rs. ' + numberFormat(Math.abs(Number(balRaw)));
                    if (Number(balRaw) < 0) {
                        balanceLine += ' <span class="text-muted small">(credit)</span>';
                    }
                }

                const recvDate = formatChequeModalDate(cheque.cheque_received_date);
                const validDate = formatChequeModalDate(cheque.cheque_valid_date);

                const invoiceRows = invoices.map(function (inv) {
                    const invNo = inv.invoice_no || 'N/A';
                    const hasLink = inv.invoice_url && invNo && invNo !== 'N/A';
                    const invCell = hasLink
                        ? '<a href="' + escapeHtml(inv.invoice_url) + '" class="fw-semibold text-primary" target="_blank" rel="noopener">' + escapeHtml(invNo) + '</a>'
                        : '<span class="fw-semibold">' + escapeHtml(invNo) + '</span>';
                    const billDateDisp = inv.bill_date ? formatChequeModalDate(inv.bill_date) : '';
                    const billDateCell = billDateDisp
                        ? escapeHtml(billDateDisp)
                        : '<span class="text-muted fst-italic">Not provided</span>';
                    return '<tr><td>' + invCell + '</td><td>' + billDateCell + '</td><td>' + billStatusBadgeHtml(inv.bill_status) + '</td><td class="text-end fw-medium">Rs. ' + numberFormat(inv.amount || 0) + '</td></tr>';
                }).join('');

                const invoicesTable = invoices.length ? `
                    <hr class="cheque-details-invoice-divider my-4">
                    <h6 class="cheque-details-section-title mb-3">Invoices in this cheque</h6>
                    <div class="table-responsive rounded border">
                        <table class="table table-sm table-hover table-cheque-invoices mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th style="min-width:7rem">Bill date</th>
                                    <th>Bill status</th>
                                    <th class="text-end" style="min-width:6rem">Amount</th>
                                </tr>
                            </thead>
                            <tbody>${invoiceRows}</tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end">Rs. ${numberFormat(totalAmount)}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                ` : '<p class="text-muted mb-0 mt-3">No invoices found for this cheque.</p>';

                let bounceImpactHtml = '';
                if (cheque.cheque_status === 'bounced') {
                    const bounceWhen = formatChequeModalDate(cheque.cheque_bounce_date) || '<span class="text-muted fst-italic">Not provided</span>';
                    const bounceReason = cheque.cheque_bounce_reason
                        ? escapeHtml(String(cheque.cheque_bounce_reason))
                        : '<span class="text-muted fst-italic">Not specified</span>';
                    bounceImpactHtml = `
                        <div class="col-12 mt-4">
                            <div class="alert alert-warning mb-0">
                                <h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>Bounce impact</h6>
                                <div class="row g-2 small">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Bounce amount:</strong> Rs. ${numberFormat(totalAmount)}</p>
                                        <p class="mb-0"><strong>Bank charges:</strong> Rs. ${numberFormat(cheque.bank_charges || 0)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Total impact:</strong> Rs. ${numberFormat((totalAmount || 0) + (cheque.bank_charges || 0))}</p>
                                        <p class="mb-0"><strong>Bounce date:</strong> ${bounceWhen}</p>
                                    </div>
                                </div>
                                <p class="mb-0 mt-2 small"><strong>Reason:</strong> ${bounceReason}</p>
                            </div>
                        </div>
                    `;
                }

                const detailsHtml = `
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="cheque-details-section-title">Cheque details</div>
                            <dl class="row cheque-details-dl mb-0">
                                <dt class="col-sm-5">Total amount</dt>
                                <dd class="col-sm-7"><strong>Rs. ${numberFormat(totalAmount)}</strong></dd>
                                <dt class="col-sm-5">Cheque number</dt>
                                <dd class="col-sm-7">${modalsDisplayValue(cheque.cheque_number)}</dd>
                                <dt class="col-sm-5">Bank / branch</dt>
                                <dd class="col-sm-7">${bankFieldHtml(cheque)}</dd>
                                <dt class="col-sm-5">Given by</dt>
                                <dd class="col-sm-7">${modalsDisplayValue(cheque.cheque_given_by)}</dd>
                                <dt class="col-sm-5">Received date</dt>
                                <dd class="col-sm-7">${recvDate ? escapeHtml(recvDate) : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                                <dt class="col-sm-5">Valid date</dt>
                                <dd class="col-sm-7">${validDate ? escapeHtml(validDate) : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                                <dt class="col-sm-5">Status</dt>
                                <dd class="col-sm-7">${chequeStatusBadgeHtml(cheque.cheque_status)}</dd>
                            </dl>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="cheque-details-section-title">Customer</div>
                            <dl class="row cheque-details-dl mb-0">
                                <dt class="col-sm-5">Name</dt>
                                <dd class="col-sm-7">${customerName ? escapeHtml(customerName) : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                                <dt class="col-sm-5">Phone</dt>
                                <dd class="col-sm-7">${customerPhone ? escapeHtml(customerPhone) : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                                <dt class="col-sm-5">Address</dt>
                                <dd class="col-sm-7">${customerAddress ? escapeHtml(customerAddress) : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                                <dt class="col-sm-5">Customer balance</dt>
                                <dd class="col-sm-7">${balanceLine ? balanceLine : '<span class="text-muted fst-italic">Not provided</span>'}</dd>
                            </dl>
                        </div>
                        ${bounceImpactHtml}
                    </div>
                    ${invoicesTable}
                `;

                $('#chequeDetailsContent').html(detailsHtml);

                const depositDisabled = ['deposited', 'cleared', 'bounced', 'cancelled'].indexOf(effectiveStatus) !== -1;
                $('#chequeDetailsDepositBtn').prop('disabled', depositDisabled);

                if (!showChequeManagementModal('chequeDetailsModal')) {
                    toastr.error('Modal could not be opened. Please refresh the page.', 'UI Error');
                    return;
                }

                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    document.querySelectorAll('#chequeDetailsContent [data-bs-toggle="tooltip"]').forEach(function (el) {
                        bootstrap.Tooltip.getOrCreateInstance(el, { container: '#chequeDetailsModal' });
                    });
                }
            } else {
                toastr.error(response.message || 'Failed to load cheque details', 'Error');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to load cheque details';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMsg = 'Cheque details not found (404).';
            } else if (xhr.status === 0) {
                errorMsg = 'Network error — check your connection.';
            }
            toastr.error(errorMsg, 'Loading Error', {
                timeOut: 5000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

// Backward compatibility (in case any old onclick still calls this)
function viewChequeDetails(groupId) {
    const rowEl = $(`tr[data-group-id="${groupId}"]`);
    if (!rowEl.length) {
        toastr.error('Unable to find cheque row for details', 'Error');
        return;
    }
    return viewChequeDetailsFromRow(rowEl.find('.js-view-cheque-details').get(0) || rowEl.get(0));
}

function updateChequeStatus(groupId, currentStatus = 'pending') {
    const rowEl = $(`tr[data-group-id="${groupId}"]`);
    const paymentIdsCsv = rowEl.attr('data-payment-ids') || '';
    prepareChequeStatusModal(paymentIdsCsv, currentStatus, null);
}

function updateChequeStatusSubmit() {
    const paymentIdsCsv = $('#paymentIds').val();
    const paymentIds = String(paymentIdsCsv || '').split(',').map(s => s.trim()).filter(Boolean).map(Number).filter(n => !Number.isNaN(n));
    const newStatus = $('#newStatus').val();
    const remarks = $('#remarks').val();
    const bankCharges = $('#bankCharges').val();

    if (!paymentIds.length) {
        toastr.error('No cheque selected', 'Error');
        return;
    }

    $.ajax({
        url: '{{ route("cheque.bulk-update-status") }}',
        method: 'POST',
        data: {
            payment_ids: paymentIds,
            status: newStatus,
            remarks: remarks,
            bank_charges: bankCharges || 0
        },
        success: function(response) {
            if (response.status === 200) {
                let message = response.message;

                // Enhanced message for bounced cheques
                if (response.data && response.data.customer_impact) {
                    const impact = response.data.customer_impact;
                    const detailMessage = `Customer: ${impact.customer_name} | ${impact.bill_status} | Floating Balance: Rs. ${numberFormat(impact.floating_balance)} | Total Outstanding: Rs. ${numberFormat(impact.total_outstanding)}`;

                    // Show success with detailed info
                    toastr.success(message + '<br><small>' + detailMessage + '</small>', 'Cheque Status Updated', {
                        timeOut: 8000,
                        extendedTimeOut: 3000,
                        allowHtml: true
                    });
                } else {
                    toastr.success(message, 'Success');
                }

                $('#updateStatusModal').modal('hide');
                setTimeout(() => location.reload(), 1000); // Small delay to show toastr
            } else {
                toastr.error(response.message || 'Failed to update status', 'Error');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to update cheque status';
            toastr.error(errorMsg, 'Error');
        }
    });
}

function viewStatusHistory(paymentId) {
    $.ajax({
        url: `/cheque/status-history/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 200) {
                let historyHtml = '<div class="timeline">';

                if (response.history && response.history.length > 0) {
                    response.history.forEach(function(history) {
                        historyHtml += `
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">${history.old_status || 'New'} → ${history.new_status}</h6>
                                    <p class="timeline-text">
                                        <small>
                                            <strong>Date:</strong> ${history.status_date}<br>
                                            <strong>Changed by:</strong> ${history.user ? history.user.name : 'System'}<br>
                                            ${history.remarks ? '<strong>Remarks:</strong> ' + history.remarks + '<br>' : ''}
                                            ${history.bank_charges > 0 ? '<strong>Bank Charges:</strong> Rs. ' + history.bank_charges : ''}
                                        </small>
                                    </p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    historyHtml += '<p class="text-muted">No status history available.</p>';
                }

                historyHtml += '</div>';

                $('#statusHistoryContent').html(historyHtml);
                $('#statusHistoryModal').modal('show');
            }
        },
        error: function() {
            toastr.error('Failed to load status history', 'Loading Error', {
                timeOut: 5000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

function getSelectedPaymentIds() {
    const ids = [];
    $('.cheque-checkbox:checked').each(function() {
        const csv = $(this).attr('data-payment-ids') || $(this).closest('tr').attr('data-payment-ids') || '';
        String(csv).split(',').map(s => s.trim()).filter(Boolean).forEach(v => {
            const n = Number(v);
            if (!Number.isNaN(n)) ids.push(n);
        });
    });
    return Array.from(new Set(ids));
}

function bulkUpdateStatus(status) {
    const selectedIds = getSelectedPaymentIds();

    if (selectedIds.length === 0) {
        toastr.warning('Please select cheques to update', 'No Selection', {
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
        return;
    }

    // Validate status transitions for selected cheques
    const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
        return $(this).closest('tr').data('status');
    }).get();

    // Define valid transitions
    const validTransitions = {
        'cleared': ['deposited'],
        'deposited': ['pending'],
        'bounced': ['deposited']
    };

    // Check if all selected cheques can be updated to target status
    const requiredStatuses = validTransitions[status] || [];
    const canUpdate = selectedStatuses.every(currentStatus => requiredStatuses.includes(currentStatus));

    if (!canUpdate) {
        toastr.error(`Cannot update cheques to ${status}. Only ${requiredStatuses.join(' or ')} cheques can be marked as ${status}.`, 'Invalid Status Transition', {
            timeOut: 8000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
        return;
    }

    const prettyStatus = String(status || '').charAt(0).toUpperCase() + String(status || '').slice(1);
    const confirmText = `Are you sure you want to mark ${selectedIds.length} cheque(s) as ${prettyStatus}?`;
    const doUpdate = function () {
        $.ajax({
            url: '{{ route("cheque.bulk-update-status") }}',
            method: 'POST',
            data: {
                payment_ids: selectedIds,
                status: status,
                remarks: `Bulk update to ${status}`
            },
            success: function(response) {
                if (response.status === 200) {
                    toastr.success(response.message, 'Bulk Update Successful', {
                        timeOut: 8000,
                        progressBar: true,
                        positionClass: 'toast-top-right',
                        escapeHtml: false
                    });
                    setTimeout(() => location.reload(), 2000);
                } else {
                    toastr.error(response.message || 'Failed to update cheques', 'Update Failed', {
                        timeOut: 8000,
                        progressBar: true,
                        positionClass: 'toast-top-right'
                    });
                }
            },
            error: function() {
                toastr.error('Failed to perform bulk update', 'Network Error', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });
            }
        });
    };

    // SweetAlert (project uses vendor/sweetalert/js/sweetalert.min.js)
    if (typeof window.swal === 'function') {
        const isDanger = String(status).toLowerCase() === 'bounced';
        window.swal({
            title: "Confirm bulk update",
            text: confirmText,
            type: isDanger ? "warning" : "info",
            showCancelButton: true,
            confirmButtonColor: isDanger ? "#d33" : "#3085d6",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, continue",
            cancelButtonText: "Cancel",
            closeOnConfirm: true
        }, function(isConfirm) {
            if (isConfirm) {
                doUpdate();
            }
        });
        return;
    }

    // Fallback (shouldn't happen if SweetAlert is loaded)
    if (!confirm(confirmText)) return;

    doUpdate();
}

function openBulkBounceModal() {
    const selectedIds = getSelectedPaymentIds();

    if (selectedIds.length === 0) {
        toastr.warning('Please select cheques to update', 'No Selection', {
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
        return;
    }

    const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
        return $(this).closest('tr').data('status');
    }).get();

    const canBounce = selectedStatuses.every(status => status === 'deposited');
    if (!canBounce) {
        toastr.error('Only deposited cheques can be marked as bounced.', 'Invalid Status Transition', {
            timeOut: 8000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
        return;
    }

    $('#bulkBounceForm')[0].reset();
    $('#bulkBounceModal').data('selectedIds', selectedIds).modal('show');
}

function submitBulkBounce() {
    const selectedIds = $('#bulkBounceModal').data('selectedIds') || [];
    const bankCharges = parseFloat($('#bulkBounceBankCharges').val() || '0');
    const remarks = $('#bulkBounceRemarks').val() || 'Bulk update to bounced';

    if (selectedIds.length === 0) {
        toastr.warning('No cheques selected', 'No Selection');
        return;
    }

    if (isNaN(bankCharges) || bankCharges < 0) {
        toastr.error('Please enter valid bank charges', 'Validation Error');
        return;
    }

    $.ajax({
        url: '{{ route("cheque.bulk-update-status") }}',
        method: 'POST',
        data: {
            payment_ids: selectedIds,
            status: 'bounced',
            remarks: remarks,
            bank_charges: bankCharges
        },
        success: function(response) {
            if (response.status === 200) {
                $('#bulkBounceModal').modal('hide');
                toastr.success(response.message, 'Bulk Bounce Successful', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    escapeHtml: false
                });
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to bounce cheques', 'Update Failed', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to perform bulk bounce update';
            toastr.error(errorMsg, 'Network Error', {
                timeOut: 8000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

function numberFormat(number) {
    // Convert to number if it's a string
    const num = typeof number === 'string' ? parseFloat(number) : number;

    // Use standard international formatting (US locale) instead of Indian
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}

// ================= BULK RECOVERY PAYMENT FUNCTIONS =================

/**
 * Open bulk recovery modal for selected bounced cheques
 */
function openBulkRecoveryModal() {
    const selectedIds = getSelectedPaymentIds();

    if (selectedIds.length === 0) {
        toastr.warning('Please select bounced cheques for recovery payment', 'No Selection');
        return;
    }

    // Validate all selected are bounced
    const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
        return $(this).closest('tr').data('status');
    }).get();

    const allBounced = selectedStatuses.every(status => status === 'bounced');
    if (!allBounced) {
        toastr.error('Only bounced cheques can be selected for recovery payment', 'Invalid Selection');
        return;
    }

    const hasRecoveredCheque = $('.cheque-checkbox:checked').toArray().some(function(checkbox) {
        return $(checkbox).closest('tr').data('has-recovery') === 1 || $(checkbox).closest('tr').data('has-recovery') === '1';
    });

    if (hasRecoveredCheque) {
        toastr.error('Already recovered bounced cheques cannot be selected again.', 'Invalid Selection');
        return;
    }

    // Check for walk-in customers
    let hasWalkInCustomer = false;
    $('.cheque-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const customerName = (row.attr('data-customer-name') || '').trim();
        const customerId = row.data('customer-id');

        if (customerName.toLowerCase() === 'walk-in customer' || customerId === 1) {
            hasWalkInCustomer = true;
            return false; // Break the loop
        }
    });

    if (hasWalkInCustomer) {
        toastr.error('Recovery payments cannot be processed for walk-in customers. Please deselect walk-in customer cheques.', 'Invalid Selection');
        return;
    }

    // Collect selected cheque details and group by customer
    let totalBouncedAmount = 0;
    let totalBankCharges = 0;
    let customerGroups = {};
    let uniqueCustomers = new Set();

    $('.cheque-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const chequeNumber = row.find('.cheque-ui-cheque-no').text() || row.find('.badge').first().text() || 'N/A';
        const customerName = (row.attr('data-customer-name') || '').trim() || 'Unknown';
        const customerId = row.data('customer-id');

        // Use data attributes for accurate amounts
        const amount = parseFloat(row.data('amount')) || 0;
        const bankCharges = parseFloat(row.data('bank-charges')) || 0;

        totalBouncedAmount += amount;
        totalBankCharges += bankCharges;
        uniqueCustomers.add(customerId);

        // Group by customer
        if (!customerGroups[customerId]) {
            customerGroups[customerId] = {
                name: customerName,
                cheques: [],
                totalAmount: 0,
                totalBankCharges: 0
            };
        }

        customerGroups[customerId].cheques.push({
            number: chequeNumber,
            amount: amount,
            bankCharges: bankCharges
        });
        customerGroups[customerId].totalAmount += amount;
        customerGroups[customerId].totalBankCharges += bankCharges;
    });

    // Generate grouped display
    let chequesInfo = '';
    Object.keys(customerGroups).forEach(customerId => {
        const group = customerGroups[customerId];
        chequesInfo += `
            <div class="customer-group border rounded p-3 mb-3 bg-light">
                <h6 class="text-primary mb-2"><i class="fas fa-user"></i> ${group.name}</h6>
                ${group.cheques.map(cheque => `
                    <div class="border-bottom pb-2 mb-2">
                        <strong>Cheque #${cheque.number}</strong><br>
                        <small>Amount: Rs. ${numberFormat(cheque.amount)} | Bank Charges: Rs. ${numberFormat(cheque.bankCharges)}</small>
                    </div>
                `).join('')}
                <div class="text-end">
                    <strong class="text-success">Customer Total: Rs. ${numberFormat(group.totalAmount + group.totalBankCharges)}</strong>
                </div>
            </div>
        `;
    });

    // Update modal content
    $('#selectedChequesInfo').html(chequesInfo);
    $('#totalBouncedAmount').text('Rs. ' + numberFormat(totalBouncedAmount));
    $('#totalBankCharges').text('Rs. ' + numberFormat(totalBankCharges));
    $('#totalRecoveryAmount').text('Rs. ' + numberFormat(totalBouncedAmount + totalBankCharges));

    // Set default recovery date
    $('#recoveryDate').val(new Date().toISOString().split('T')[0]);

    // Reset form
    $('#bulkRecoveryForm')[0].reset();
    $('#recoveryDate').val(new Date().toISOString().split('T')[0]);
    $('#recoveryPaymentFields').html('');
    $('#paymentSummary').hide();

    // Store selected data for processing
    $('#bulkRecoveryModal').data('selectedIds', selectedIds);
    $('#bulkRecoveryModal').data('totalAmount', totalBouncedAmount + totalBankCharges);

    // Show modal (Bootstrap 5 compatible)
    if (!showChequeManagementModal('bulkRecoveryModal')) {
        toastr.error('Recovery modal could not be opened. Please refresh the page.', 'UI Error');
    }
}

/**
 * Update recovery payment fields based on selected method
 */
function updateRecoveryPaymentFields() {
    const method = $('#recoveryMethod').val();
    const fieldsContainer = $('#recoveryPaymentFields');
    const totalAmount = $('#bulkRecoveryModal').data('totalAmount') || 0;

    fieldsContainer.html('');
    $('#paymentSummary').hide();

    if (!method) return;

    let fieldsHtml = '';

    function renderSplitChequeRow(prefixName, idx, displayNo, amountValue) {
        const safeIdx = Number.isFinite(idx) ? idx : 0;
        const showNo = Number.isFinite(displayNo) ? displayNo : 1;
        const amt = Number.isFinite(amountValue) ? amountValue : 0;
        return `
            <div class="card mb-2 border" data-split-idx="${safeIdx}">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-semibold"><i class="fas fa-money-check-alt me-1"></i>Cheque ${showNo}</div>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 js-remove-split-cheque" title="Remove this cheque row">×</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Cheque Number</label>
                            <input type="text" class="form-control form-control-sm" name="${prefixName}[${safeIdx}][cheque_number]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Bank/Branch</label>
                            <input type="text" class="form-control form-control-sm" name="${prefixName}[${safeIdx}][cheque_bank]" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Amount</label>
                            <input type="number" class="form-control form-control-sm js-split-cheque-amount" step="0.01" min="0" name="${prefixName}[${safeIdx}][amount]" value="${amt.toFixed(2)}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Cheque Date</label>
                            <input type="date" class="form-control form-control-sm" name="${prefixName}[${safeIdx}][cheque_date]" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Valid Until Date</label>
                            <input type="date" class="form-control form-control-sm" name="${prefixName}[${safeIdx}][cheque_valid_date]" required>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renumberSplitRows() {
        $('#splitChequeRows .card').each(function (i) {
            $(this).find('.fw-semibold').first().html('<i class="fas fa-money-check-alt me-1"></i>Cheque ' + (i + 1));
        });
    }

    function autoSplitAcrossCurrentRows(totalToSplit) {
        const $rows = $('#splitChequeRows .js-split-cheque-amount');
        const n = $rows.length || 1;
        const per = totalToSplit > 0 ? (Number(totalToSplit) / n) : 0;
        $rows.each(function () {
            $(this).val(per.toFixed(2));
        });
    }

    function recalcSplitSummary(totalRequired, cashSelector) {
        const cash = cashSelector ? (parseFloat($(cashSelector).val()) || 0) : 0;
        let sumCheques = 0;
        $('.js-split-cheque-amount').each(function () {
            sumCheques += parseFloat($(this).val()) || 0;
        });
        const total = cash + sumCheques;
        const ok = Math.abs(total - totalRequired) < 0.01;
        const badge = ok
            ? '<span class="badge bg-success">OK</span>'
            : '<span class="badge bg-warning text-dark">Mismatch</span>';
        $('#splitTotalsHint').html(`${badge} Total entered: <strong>Rs. ${numberFormat(total)}</strong> (required Rs. ${numberFormat(totalRequired)})`);
        showPaymentSummary('Split Payment', cash, sumCheques);
    }

    switch(method) {
        case 'cash':
            fieldsHtml = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-money-bill-wave"></i> Cash Payment</h6>
                    <p class="mb-0">Full recovery amount will be paid in cash: <strong>Rs. ${numberFormat(totalAmount)}</strong></p>
                </div>
            `;
            showPaymentSummary('Cash Payment', totalAmount, 0);
            break;

        case 'bank_transfer':
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label">Bank Account Number</label>
                        <input type="text" class="form-control" name="bank_account" required>
                    </div>
                </div>
            `;
            showPaymentSummary('Bank Transfer', totalAmount, 0);
            break;

        case 'card':
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Card Number (Last 4 digits)</label>
                        <input type="text" class="form-control" name="card_number" maxlength="4" placeholder="****">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Card Type</label>
                        <select class="form-control" name="card_type">
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                            <option value="amex">American Express</option>
                        </select>
                    </div>
                </div>
            `;
            showPaymentSummary('Card Payment', totalAmount, 0);
            break;

        case 'new_cheque':
            fieldsHtml = `
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> New Cheque Payment</h6>
                    <p class="mb-0">Customer will provide a new cheque for the full recovery amount</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">New Cheque Number</label>
                        <input type="text" class="form-control" name="new_cheque_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank/Branch</label>
                        <input type="text" class="form-control" name="new_cheque_bank" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Cheque Date</label>
                        <input type="date" class="form-control" name="new_cheque_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valid Until Date</label>
                        <input type="date" class="form-control" name="new_cheque_valid_date" required>
                    </div>
                </div>
            `;
            showPaymentSummary('New Cheque', 0, totalAmount);
            break;

        case 'multiple_cheques':
            fieldsHtml = `
                <div class="alert alert-warning">
                    <h6><i class="fas fa-layer-group"></i> Multiple Cheques (Split)</h6>
                    <p class="mb-0">You can split <strong>Rs. ${numberFormat(totalAmount)}</strong> into multiple cheques. Amounts will auto-split equally, but you can edit.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="splitAddChequeBtn"><i class="fas fa-plus me-1"></i>Add cheque</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="splitAutoEvenBtn" title="Re-split equally">Auto split</button>
                    <div class="small text-muted" id="splitTotalsHint"></div>
                </div>
                <div id="splitChequeRows">
                    ${renderSplitChequeRow('multi_cheques', 0, 1, totalAmount / 3)}
                    ${renderSplitChequeRow('multi_cheques', 1, 2, totalAmount / 3)}
                    ${renderSplitChequeRow('multi_cheques', 2, 3, totalAmount / 3)}
                </div>
            `;
            break;

        case 'partial_cash_cheque':
            fieldsHtml = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Partial Cash + New Cheque</h6>
                    <p class="mb-0">Customer will pay part in cash and provide a new cheque for the remaining amount</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Cash Amount</label>
                        <input type="number" class="form-control" id="partialCashAmount" name="cash_amount"
                               step="0.01" min="0" max="${totalAmount}" required>
                        <small class="text-muted">Max: Rs. ${numberFormat(totalAmount)}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cheque Amount (Auto-calculated)</label>
                        <input type="number" class="form-control" id="partialChequeAmount" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">New Cheque Number</label>
                        <input type="text" class="form-control" name="new_cheque_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank/Branch</label>
                        <input type="text" class="form-control" name="new_cheque_bank" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Cheque Date</label>
                        <input type="date" class="form-control" name="new_cheque_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valid Until Date</label>
                        <input type="date" class="form-control" name="new_cheque_valid_date" required>
                    </div>
                </div>
            `;
            break;

        case 'partial_cash_multiple_cheques':
            fieldsHtml = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-random"></i> Partial Cash + Multiple Cheques</h6>
                    <p class="mb-0">Enter a cash amount, and split the remaining into multiple cheques.</p>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Cash Amount</label>
                        <input type="number" class="form-control" id="splitCashAmount" name="cash_amount" step="0.01" min="0" max="${totalAmount}" required>
                        <small class="text-muted">Max: Rs. ${numberFormat(totalAmount)}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remaining for cheques (auto)</label>
                        <input type="number" class="form-control" id="splitRemainingAmount" readonly>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-3 mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="splitAddChequeBtn"><i class="fas fa-plus me-1"></i>Add cheque</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="splitAutoEvenBtn" title="Re-split equally">Auto split</button>
                    <div class="small text-muted" id="splitTotalsHint"></div>
                </div>
                <div id="splitChequeRows">
                    ${renderSplitChequeRow('multi_cheques', 0, 1, totalAmount / 3)}
                    ${renderSplitChequeRow('multi_cheques', 1, 2, totalAmount / 3)}
                    ${renderSplitChequeRow('multi_cheques', 2, 3, totalAmount / 3)}
                </div>
            `;
            break;
    }

    fieldsContainer.html(fieldsHtml);

    // Add event listener for partial cash amount calculation
    if (method === 'partial_cash_cheque') {
        $('#partialCashAmount').on('input', function() {
            const cashAmount = parseFloat($(this).val()) || 0;
            const chequeAmount = totalAmount - cashAmount;
            $('#partialChequeAmount').val(chequeAmount.toFixed(2));

            showPaymentSummary('Cash + Cheque', cashAmount, chequeAmount);
        });
    }

    if (method === 'multiple_cheques' || method === 'partial_cash_multiple_cheques') {
        const cashSel = method === 'partial_cash_multiple_cheques' ? '#splitCashAmount' : null;
        const getRemaining = function () {
            if (!cashSel) return Number(totalAmount) || 0;
            const cash = parseFloat($(cashSel).val()) || 0;
            const remaining = Math.max(0, (Number(totalAmount) || 0) - cash);
            $('#splitRemainingAmount').val(remaining.toFixed(2));
            return remaining;
        };

        // Track next index so removed rows don't break names
        $('#splitChequeRows').data('nextIdx', $('#splitChequeRows .card').length);

        $('#splitAddChequeBtn').on('click', function () {
            const nextIdx = parseInt($('#splitChequeRows').data('nextIdx') || '0', 10) || 0;
            const remaining = getRemaining();
            $('#splitChequeRows').append(renderSplitChequeRow('multi_cheques', nextIdx, $('#splitChequeRows .card').length + 1, 0));
            $('#splitChequeRows').data('nextIdx', nextIdx + 1);
            renumberSplitRows();
            recalcSplitSummary(totalAmount, cashSel);
        });

        $('#splitAutoEvenBtn').on('click', function () {
            const remaining = getRemaining();
            autoSplitAcrossCurrentRows(remaining);
            recalcSplitSummary(totalAmount, cashSel);
        });
        fieldsContainer.on('input', '.js-split-cheque-amount', function () {
            recalcSplitSummary(totalAmount, cashSel);
        });
        fieldsContainer.on('click', '.js-remove-split-cheque', function () {
            const $card = $(this).closest('.card');
            $card.remove();
            if ($('#splitChequeRows .card').length === 0) {
                const nextIdx = parseInt($('#splitChequeRows').data('nextIdx') || '0', 10) || 0;
                $('#splitChequeRows').append(renderSplitChequeRow('multi_cheques', nextIdx, 1, 0));
                $('#splitChequeRows').data('nextIdx', nextIdx + 1);
            }
            renumberSplitRows();
            recalcSplitSummary(totalAmount, cashSel);
        });
        if (cashSel) {
            $(cashSel).on('input', function () {
                getRemaining();
                recalcSplitSummary(totalAmount, cashSel);
            });
        }

        // Initial compute
        if (cashSel) {
            $('#splitRemainingAmount').val(totalAmount.toFixed(2));
        }
        recalcSplitSummary(totalAmount, cashSel);
    }
}

/**
 * Show payment summary
 */
function showPaymentSummary(method, cashAmount, chequeAmount) {
    let summaryHtml = `
        <div class="row">
            <div class="col-md-4">
                <strong>Payment Method:</strong><br>${method}
            </div>
    `;

    if (cashAmount > 0) {
        summaryHtml += `
            <div class="col-md-4">
                <strong>Cash Amount:</strong><br>Rs. ${numberFormat(cashAmount)}
            </div>
        `;
    }

    if (chequeAmount > 0) {
        summaryHtml += `
            <div class="col-md-4">
                <strong>Cheque Amount:</strong><br>Rs. ${numberFormat(chequeAmount)}
            </div>
        `;
    }

    summaryHtml += `
        </div>
        <hr>
        <div class="text-center">
            <strong class="fs-5 text-primary">Total Recovery: Rs. ${numberFormat(cashAmount + chequeAmount)}</strong>
        </div>
    `;

    $('#summaryContent').html(summaryHtml);
    $('#paymentSummary').show();
}

/**
 * Process bulk recovery payment
 */
function processBulkRecoveryPayment() {
    const selectedIds = $('#bulkRecoveryModal').data('selectedIds');
    const formData = new FormData($('#bulkRecoveryForm')[0]);
    const method = String($('#recoveryMethod').val() || '');
    const totalRequired = Number($('#bulkRecoveryModal').data('totalAmount') || 0);

    // Add selected cheque IDs to form data
    formData.append('cheque_ids', JSON.stringify(selectedIds));

    // For split methods, validate total and send structured JSON
    if (method === 'multiple_cheques' || method === 'partial_cash_multiple_cheques') {
        const cashAmount = method === 'partial_cash_multiple_cheques' ? (parseFloat($('#splitCashAmount').val()) || 0) : 0;
        const cheques = [];
        $('#recoveryPaymentFields').find('#splitChequeRows .card').each(function () {
            const $card = $(this);
            const getByName = (suffix) => $card.find(`[name$="${suffix}"]`).val();
            const amt = parseFloat(getByName('[amount]')) || 0;
            cheques.push({
                cheque_number: getByName('[cheque_number]') || '',
                cheque_bank: getByName('[cheque_bank]') || '',
                cheque_date: getByName('[cheque_date]') || '',
                cheque_valid_date: getByName('[cheque_valid_date]') || '',
                amount: amt
            });
        });

        const sumCheques = cheques.reduce((a, c) => a + (parseFloat(c.amount) || 0), 0);
        const sumTotal = cashAmount + sumCheques;
        if (Math.abs(sumTotal - totalRequired) > 0.01) {
            toastr.error(`Split total must equal Rs. ${numberFormat(totalRequired)} (currently Rs. ${numberFormat(sumTotal)})`, 'Amount mismatch');
            return;
        }

        formData.set('cash_amount', cashAmount);
        formData.append('multi_cheques', JSON.stringify(cheques));
    }

    $.ajax({
        url: '/cheque/bulk-recovery-payment',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 200) {
                toastr.success(response.message, 'Recovery Payment Processed', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });

                $('#bulkRecoveryModal').modal('hide');
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to process recovery payment', 'Error');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to process recovery payment';
            toastr.error(errorMsg, 'Error');
        }
    });
}

// View Recovery Chain Function
function viewRecoveryChain(paymentId) {
    $.ajax({
        url: `/payment/${paymentId}/recovery-chain`,
        type: 'GET',
        success: function(response) {
            if (response.status === 200) {
                showRecoveryChainModal(response.data);
            } else {
                toastr.error('Failed to load recovery chain', 'Error');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Network error occurred';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMsg = 'Payment not found';
            }
            toastr.error(errorMsg, 'Error');
        }
    });
}

function showRecoveryChainModal(data) {
    let modalContent = `
        <div class="modal fade" id="recoveryChainModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Recovery Chain Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Original Payment Info -->
                        <div class="card mb-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Original Bounced Payment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Cheque Number:</strong><br>
                                        <span class="badge bg-info">${data.original_payment.cheque_number}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Amount:</strong><br>
                                        <span class="text-danger">Rs. ${numberFormat(Math.abs(data.original_payment.amount))}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Bank Charges:</strong><br>
                                        <span class="text-warning">Rs. ${numberFormat(data.original_payment.bank_charges)}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Due:</strong><br>
                                        <span class="text-danger"><strong>Rs. ${numberFormat(data.total_original)}</strong></span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>Bounce Date:</strong> ${data.original_payment.bounce_date || 'N/A'}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Bounce Reason:</strong> ${data.original_payment.bounce_reason || 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Summary -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6>Total Recovered</h6>
                                        <h4>Rs. ${numberFormat(data.total_recovered)}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6>Pending Recovery</h6>
                                        <h4>Rs. ${numberFormat(data.pending_recovery)}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card ${data.total_recovered >= data.total_original ? 'bg-success' : 'bg-danger'} text-white">
                                    <div class="card-body">
                                        <h6>Remaining</h6>
                                        <h4>Rs. ${numberFormat(data.total_original - data.total_recovered - data.pending_recovery)}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Payments -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-money-bill-wave"></i> Recovery Payments (${data.recoveries.length})</h6>
                            </div>
                            <div class="card-body">`;

    if (data.recoveries.length > 0) {
        modalContent += `
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Details</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

        data.recoveries.forEach(recovery => {
            let statusBadge = recovery.payment_status === 'completed' ? 'bg-success' : 'bg-warning';
            let methodDetails = '';

            if (recovery.payment_method === 'card') {
                methodDetails = `Card: ${recovery.card_type} ${recovery.card_number}`;
            } else if (recovery.payment_method === 'cheque') {
                methodDetails = `Cheque: ${recovery.cheque_number} (${recovery.cheque_status})`;
            } else if (recovery.payment_method === 'bank_transfer') {
                methodDetails = `Bank: ${recovery.bank_account}`;
            } else if (recovery.actual_payment_method === 'partial_cash_cheque') {
                methodDetails = 'Partial: Cash + Cheque';
            }

            modalContent += `
                                            <tr>
                                                <td>${recovery.payment_date}</td>
                                                <td>
                                                    <span class="badge bg-primary">${recovery.payment_method}</span>
                                                    ${recovery.actual_payment_method !== recovery.payment_method ?
                                                        `<br><small class="text-muted">(${recovery.actual_payment_method})</small>` : ''}
                                                </td>
                                                <td><strong>Rs. ${numberFormat(recovery.amount)}</strong></td>
                                                <td><span class="badge ${statusBadge}">${recovery.payment_status}</span></td>
                                                <td>
                                                    ${methodDetails}
                                                    ${recovery.reference_no ? `<br><small>Ref: ${recovery.reference_no}</small>` : ''}
                                                    ${recovery.notes ? `<br><small class="text-muted">${recovery.notes}</small>` : ''}
                                                </td>
                                                <td>
                                                    ${recovery.created_by}
                                                    <br><small class="text-muted">${recovery.created_at}</small>
                                                </td>
                                            </tr>`;
        });

        modalContent += `
                                        </tbody>
                                    </table>
                                </div>`;
    } else {
        modalContent += `
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No recovery payments recorded yet</h6>
                                    <p class="text-muted">Use bulk recovery options to record recovery payments</p>
                                </div>`;
    }

    modalContent += `
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>`;

    // Remove any existing modal
    $('#recoveryChainModal').remove();

    // Add new modal to body
    $('body').append(modalContent);

    // Show modal
    $('#recoveryChainModal').modal('show');

    // Remove modal from DOM when hidden
    $('#recoveryChainModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: bold;
}

.timeline-text {
    margin-bottom: 0;
    color: #6c757d;
}

/* Page-scoped: do not shrink all tables globally */
.cheque-mgmt-page .cheque-list-card .table th,
.cheque-mgmt-page .cheque-list-card .table td {
    vertical-align: middle;
}

.table-responsive {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 5px;
    overflow: auto !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

/* Custom scrollbar styling */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Scroll shadow indicators */
.table-responsive.needs-scroll {
    position: relative;
}

.table-responsive.scroll-left::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 20px;
    background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
    z-index: 5;
    pointer-events: none;
}

.table-responsive.scroll-right::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 20px;
    background: linear-gradient(to left, rgba(0,0,0,0.1), transparent);
    z-index: 5;
    pointer-events: none;
}

/* Enhanced scroll indicator message */
.table-responsive + .text-center {
    background: linear-gradient(45deg, #f8f9fa, #e9ecef);
    border-radius: 0 0 0.375rem 0.375rem;
    transition: all 0.3s ease;
}

.table-responsive.needs-scroll + .text-center {
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
    border-color: #ffc107;
}

.table-responsive.needs-scroll + .text-center small {
    color: #856404 !important;
    font-weight: 500;
}

@media (max-width: 768px) {
    .cheque-mgmt-page #chequeTableResponsiveWrapper {
        -webkit-overflow-scrolling: touch;
    }
}

/* Badge and status visibility improvements */
.badge {
    font-size: 9px;
    font-weight: 600;
    padding: 0.2em 0.4em;
}

.btn-group .btn {
    border-radius: 0.2rem;
    margin-right: 1px;
    padding: 0.2rem 0.35rem;
    font-size: 10px;
}

.btn-group .btn i {
    font-size: 10px;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

/* Bulk action button styling */
.btn:disabled {
    opacity: 0.3 !important;
    cursor: not-allowed !important;
}

.btn:disabled:hover {
    background-color: var(--bs-secondary) !important;
    border-color: var(--bs-secondary) !important;
}

/* Loading overlay styling */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 0.375rem;
}

.loading-overlay .text-center {
    padding: 20px;
}

/* Selection counter styling */
#selectedCount .text-muted {
    font-size: 0.85em;
    font-style: italic;
}
</style>

@push('scripts')
<!-- DataTables RowGroup JS - Local -->
<script src="{{ asset('vendor/datatables-rowgroup/js/dataTables.rowGroup.min.js') }}"></script>
@endpush

@endsection
