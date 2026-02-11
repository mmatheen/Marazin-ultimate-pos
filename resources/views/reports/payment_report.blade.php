@extends('layout.layout')
@section('title', 'Payment Report')

@section('content')
<style>
/* =====================================
   🎨 MODERN ENTERPRISE PAYMENT REPORT
   Professional ERP/POS Design System
   ===================================== */

/* Print Styles */
@media print {
    body { font-size: 12px !important; color: #000 !important; }
    .page-header, .dt-buttons, .btn-group, #advancedFilters, .no-print { display: none !important; }
    .collection-group, .summary-card { page-break-inside: avoid; }
    .collection-main-header {
        background: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    @page { margin: 0.5in; }
}

/* Summary Cards - Top Level Statistics */
.summary-section { margin-bottom: 24px; }
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
}
.summary-card {
    background: white;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid;
}
.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}
.summary-card.total { border-left-color: #2563eb; }
.summary-card.cash { border-left-color: #16a34a; }
.summary-card.card { border-left-color: #9333ea; }
.summary-card.cheque { border-left-color: #ea580c; }
.summary-card.bank { border-left-color: #8b5cf6; }
.summary-card.other { border-left-color: #f59e0b; }

.summary-card-title {
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.summary-card-value { font-size: 22px; font-weight: 700; color: #1f2937; }
.summary-card.total .summary-card-value { color: #2563eb; }
.summary-card.cash .summary-card-value { color: #16a34a; }
.summary-card.card .summary-card-value { color: #9333ea; }
.summary-card.cheque .summary-card-value { color: #ea580c; }
.summary-card.bank .summary-card-value { color: #8b5cf6; }
.summary-card.other .summary-card-value { color: #f59e0b; }
.summary-card-percentage {
    font-size: 11px;
    color: #16a34a;
    font-weight: 600;
    margin-top: 4px;
}

/* Filters Card */
.filters-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    margin-bottom: 24px;
}
.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.filters-title { font-size: 16px; font-weight: 700; color: #1f2937; }
.form-group.local-forms { margin-bottom: 16px; }
.form-group.local-forms label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

/* Select2 Styling */
.select2-container .select2-selection--single {
    height: 42px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    background-color: #fff !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px !important;
    padding-left: 14px !important;
    color: #1f2937 !important;
    font-size: 14px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

/* Date Range Picker */
#reportrange {
    background: #fff;
    cursor: pointer;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 42px;
    transition: all 0.2s ease;
}
#reportrange:hover { border-color: #667eea; }
#reportrange i { color: #6b7280; }
#reportrange span {
    flex: 1;
    margin: 0 10px;
    color: #1f2937;
    font-size: 14px;
}

/* =====================================
   LEVEL 1: Collection Group Header
   ===================================== */
.collections-container { margin-top: 24px; }
.collection-group {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    transition: all 0.3s ease;
}
.collection-group:hover { box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); }

.collection-main-header {
    background: #f8f9fa;
    color: #1f2937;
    padding: 18px 24px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.3s ease;
    border-bottom: 2px solid #e5e7eb;
}
.collection-main-header:hover {
    background: #e9ecef;
}
.collection-main-header.active {
    background: #ffffff;
    border-bottom: 2px solid #3b82f6;
}
.collection-header-left { flex: 1; }
.collection-reference {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1f2937;
}
.collection-reference i { font-size: 20px; color: #4b5563; }
.collection-meta {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 8px;
}
.collection-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6b7280;
}
.collection-meta-item i { font-size: 14px; color: #9ca3af; }
.collection-header-right { text-align: right; }
.collection-total-amount {
    font-size: 26px;
    font-weight: 700;
    color: #10b981;
    margin-bottom: 4px;
}
.collection-payment-count {
    font-size: 12px;
    background: #e0e7ff;
    color: #3730a3;
    padding: 4px 12px;
    border-radius: 20px;
    display: inline-block;
    font-weight: 600;
}
.collapse-icon {
    font-size: 20px;
    transition: transform 0.3s ease;
    margin-left: 12px;
}
.collection-main-header.active .collapse-icon { transform: rotate(180deg); }

/* =====================================
   LEVEL 2: Payment Method Tabs
   ===================================== */
.payment-method-tabs {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
}
.payment-method-tab {
    flex: 1;
    min-width: 150px;
    padding: 14px 20px;
    text-align: center;
    cursor: pointer;
    background: #f3f4f6;
    border-right: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    position: relative;
}
.payment-method-tab:last-child { border-right: none; }
.payment-method-tab:hover { background: #e5e7eb; }
.payment-method-tab.active {
    background: #ffffff;
    border-bottom: 3px solid;
}
.payment-method-tab.active.cheque { border-bottom-color: #ea580c; }
.payment-method-tab.active.cash { border-bottom-color: #16a34a; }
.payment-method-tab.active.card { border-bottom-color: #9333ea; }
.payment-method-tab.active.bank_transfer { border-bottom-color: #0891b2; }

.tab-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.payment-method-tab.active .tab-label { color: #1f2937; }
.tab-icon { font-size: 18px; }
.tab-count { font-size: 11px; color: #6b7280; margin-top: 4px; }
.tab-amount { font-size: 15px; font-weight: 700; margin-top: 4px; }
.payment-method-tab.cheque .tab-amount { color: #ea580c; }
.payment-method-tab.cash .tab-amount { color: #16a34a; }
.payment-method-tab.card .tab-amount { color: #9333ea; }
.payment-method-tab.bank_transfer .tab-amount { color: #0891b2; }

/* =====================================
   LEVEL 3: Payment Reference Cards
   ===================================== */
.payment-references-container { padding: 20px; background: #fafbfc; }
.payment-reference-card {
    background: white;
    border-radius: 10px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    border-left: 4px solid;
    transition: all 0.3s ease;
}
.payment-reference-card.cheque { border-left-color: #ea580c; }
.payment-reference-card.cash { border-left-color: #16a34a; }
.payment-reference-card.card { border-left-color: #9333ea; }
.payment-reference-card.bank_transfer { border-left-color: #0891b2; }
.payment-reference-card:hover {
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    transform: translateX(4px);
}

.reference-header {
    padding: 16px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
    transition: all 0.2s ease;
}
.reference-header:hover { background: #f3f4f6; }
.reference-header.active { background: #eff6ff; }
.reference-left { flex: 1; }
.reference-number {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.reference-badge {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.reference-badge.cheque { background: #fed7aa; color: #9a3412; }
.reference-badge.cash { background: #bbf7d0; color: #166534; }
.reference-badge.card { background: #e9d5ff; color: #6b21a8; }
.reference-badge.bank_transfer { background: #bfdbfe; color: #1e40af; }

.reference-details {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}
.reference-detail-item { display: flex; align-items: center; gap: 6px; }
.reference-detail-item i { font-size: 13px; opacity: 0.7; }
.reference-right { text-align: right; }
.reference-amount { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
.payment-reference-card.cheque .reference-amount { color: #ea580c; }
.payment-reference-card.cash .reference-amount { color: #16a34a; }
.payment-reference-card.card .reference-amount { color: #9333ea; }
.payment-reference-card.bank_transfer .reference-amount { color: #0891b2; }
.reference-invoice-count { font-size: 12px; color: #6b7280; }
.reference-expand-icon {
    font-size: 16px;
    color: #9ca3af;
    transition: transform 0.3s ease;
    margin-left: 12px;
}
.reference-header.active .reference-expand-icon { transform: rotate(180deg); }

/* =====================================
   LEVEL 4: Invoice Settlement Table
   ===================================== */
.invoice-settlement-table { padding: 0; }
.invoice-settlement-table table {
    width: 100%;
    margin: 0;
    background: white;
    font-size: 13px;
}
.invoice-settlement-table thead th {
    background: #f9fafb;
    color: #374151;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px;
    border-bottom: 2px solid #e5e7eb;
}
.invoice-settlement-table tbody td {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.invoice-settlement-table tbody td small.text-muted {
    color: #6b7280;
    font-size: 12px;
    display: block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.invoice-settlement-table tbody tr:hover { background: #f9fafb; }
.invoice-settlement-table tbody tr:last-child td { border-bottom: none; }
.invoice-link {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}
.invoice-link:hover { color: #1e40af; text-decoration: underline; }

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.cleared { background: #d1fae5; color: #065f46; }
.status-badge.bounced { background: #fee2e2; color: #991b1b; }

.reference-footer {
    background: #f9fafb;
    padding: 14px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px solid #e5e7eb;
}
.reference-footer-label { font-size: 14px; font-weight: 600; color: #374151; }
.reference-footer-amount { font-size: 20px; font-weight: 700; color: #16a34a; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}
.empty-state-icon { font-size: 64px; color: #d1d5db; margin-bottom: 16px; }
.empty-state-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}
.empty-state-text { font-size: 14px; color: #6b7280; }

/* Responsive Design */
@media (max-width: 1200px) {
    .summary-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
}
@media (max-width: 768px) {
    .collection-main-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .collection-header-right { width: 100%; text-align: left; }
    .collection-meta { flex-direction: column; gap: 8px; }
    .payment-method-tabs { flex-direction: column; }
    .payment-method-tab { border-right: none; border-bottom: 1px solid #e5e7eb; }
    .reference-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .reference-right { width: 100%; text-align: left; }
    .invoice-settlement-table { overflow-x: auto; }
}
</style>

<div class="content container-fluid">
    <div class="row">
        <!-- Page Header -->
        <div class="page-header no-print">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="page-title">💳 Payment Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#">Reports</a></li>
                        <li class="breadcrumb-item active">Payment Report</li>
                    </ul>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-primary" id="exportPdf">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <button class="btn btn-success" id="exportExcel">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </button>
                        <button class="btn btn-info" id="printReport">
                            <i class="fas fa-print me-2"></i>Print A4 Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics Cards -->
        <div class="summary-section no-print">
            <div class="summary-grid">
                <div class="summary-card total">
                    <div class="summary-card-title">Total Payments</div>
                    <div class="summary-card-value" id="totalPayments">Rs {{ number_format($summaryData['total_amount'], 2) }}</div>
                    <div class="summary-card-percentage">100% of total</div>
                </div>
                <div class="summary-card cash">
                    <div class="summary-card-title">Cash Payments</div>
                    <div class="summary-card-value" id="cashPayments">Rs {{ number_format($summaryData['cash_total'], 2) }}</div>
                    <div class="summary-card-percentage" id="cashPercentage">{{ $summaryData['total_amount'] > 0 ? round(($summaryData['cash_total'] / $summaryData['total_amount']) * 100, 1) : 0 }}% of total</div>
                </div>
                <div class="summary-card card">
                    <div class="summary-card-title">Card Payments</div>
                    <div class="summary-card-value" id="cardPayments">Rs {{ number_format($summaryData['card_total'], 2) }}</div>
                    <div class="summary-card-percentage" id="cardPercentage">{{ $summaryData['total_amount'] > 0 ? round(($summaryData['card_total'] / $summaryData['total_amount']) * 100, 1) : 0 }}% of total</div>
                </div>
                <div class="summary-card cheque">
                    <div class="summary-card-title">Cheque Payments</div>
                    <div class="summary-card-value" id="chequePayments">Rs {{ number_format($summaryData['cheque_total'], 2) }}</div>
                    <div class="summary-card-percentage" id="chequePercentage">{{ $summaryData['total_amount'] > 0 ? round(($summaryData['cheque_total'] / $summaryData['total_amount']) * 100, 1) : 0 }}% of total</div>
                </div>
                <div class="summary-card bank">
                    <div class="summary-card-title">Bank Transfers</div>
                    <div class="summary-card-value" id="bankPayments">Rs {{ number_format($summaryData['bank_transfer_total'], 2) }}</div>
                    <div class="summary-card-percentage" id="bankPercentage">{{ $summaryData['total_amount'] > 0 ? round(($summaryData['bank_transfer_total'] / $summaryData['total_amount']) * 100, 1) : 0 }}% of total</div>
                </div>
                @if($summaryData['other_total'] > 0)
                <div class="summary-card other">
                    <div class="summary-card-title">Other Methods</div>
                    <div class="summary-card-value" id="otherPayments">Rs {{ number_format($summaryData['other_total'], 2) }}</div>
                    <div class="summary-card-percentage" id="otherPercentage">{{ $summaryData['total_amount'] > 0 ? round(($summaryData['other_total'] / $summaryData['total_amount']) * 100, 1) : 0 }}% of total</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Filters Card -->
        <div class="filters-card no-print">
            <div class="filters-header">
                <div class="filters-title">
                    <i class="fas fa-filter me-2"></i>Filters
                </div>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
                    <i class="fas fa-sliders-h me-1"></i>Advanced Filters
                </button>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="form-group local-forms">
                        <label>Date Range</label>
                        <div id="reportrange">
                            <i class="far fa-calendar-alt"></i>
                            <span></span>
                            <i class="fas fa-caret-down"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="form-group local-forms">
                        <label>Contact Type</label>
                        <select class="form-control selectBox" id="contactTypeFilter">
                            <option value="">All Contacts</option>
                            <option value="customer">Customers</option>
                            <option value="supplier">Suppliers</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="form-group local-forms">
                        <label>Location</label>
                        <select class="form-control selectBox" id="locationFilter">
                            <option value="">All Locations</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="form-group local-forms">
                        <label>Payment Method</label>
                        <select class="form-control selectBox" id="paymentMethodFilter">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters (Collapsible) -->
            <div class="collapse" id="advancedFilters">
                <hr class="my-3">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group local-forms" id="customerFilterGroup">
                            <label>Customer</label>
                            <select class="form-control selectBox" id="customerFilter">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group local-forms d-none" id="supplierFilterGroup">
                            <label>Supplier</label>
                            <select class="form-control selectBox" id="supplierFilter">
                                <option value="">All Suppliers</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->first_name }} {{ $supplier->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <div class="form-group local-forms">
                            <label>Payment Type</label>
                            <select class="form-control selectBox" id="paymentTypeFilter">
                                <option value="">All Types</option>
                                <option value="sale">Sale Payment</option>
                                <option value="purchase">Purchase Payment</option>
                                <option value="return">Return Payment</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Collections Container -->
        <div class="collections-container" id="collectionsContainer">
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <p class="mt-3 text-muted">Loading payment data...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Configuration Constants
const PAYMENT_METHOD_CONFIG = {
    cash: { icon: 'fas fa-money-bill-wave', label: 'Cash', color: '#16a34a' },
    card: { icon: 'fas fa-credit-card', label: 'Card', color: '#9333ea' },
    cheque: { icon: 'fas fa-money-check', label: 'Cheque', color: '#ea580c' },
    bank_transfer: { icon: 'fas fa-university', label: 'Bank Transfer', color: '#0891b2' }
};

// Utility Functions
function formatCurrency(amount, decimals = 2) {
    return 'Rs ' + parseFloat(amount).toLocaleString('en-IN', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
        useGrouping: true
    });
}

function getPaymentMethodConfig(method) {
    const methodLower = method.toLowerCase();
    return PAYMENT_METHOD_CONFIG[methodLower] || {
        icon: 'fas fa-money-bill',
        label: method,
        color: '#6b7280'
    };
}

$(document).ready(function() {
    // Initialize Select2
    $('.selectBox').select2({
        placeholder: "Select an option",
        allowClear: true
    });

    // Initialize Date Range Picker
    var start = moment().startOf('month');
    var end = moment().endOf('month');

    function cb(start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
    }

    $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

    $('#reportrange').daterangepicker({
        startDate: start,
        endDate: end,
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Last 1 Year': [moment().subtract(1, 'year'), moment()],
            'Last 2 Years': [moment().subtract(2, 'years'), moment()],
            'All Time': [moment('2020-01-01'), moment()]
        }
    }, cb);

    cb(start, end);

    // Load payment collections
    function loadPaymentCollections() {
        var dateRange = $('#reportrange').data('daterangepicker');
        var filters = {
            customer_id: $('#customerFilter').val(),
            supplier_id: $('#supplierFilter').val(),
            location_id: $('#locationFilter').val(),
            payment_method: $('#paymentMethodFilter').val(),
            payment_type: $('#paymentTypeFilter').val(),
            start_date: dateRange.startDate.format('YYYY-MM-DD'),
            end_date: dateRange.endDate.format('YYYY-MM-DD'),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("payment.report.data") }}',
            type: 'POST',
            data: filters,
            success: function(response) {
                renderCollections(response.collections);
            },
            error: function(xhr) {
                console.error('Error loading payment data:', xhr);
                $('#collectionsContainer').html(
                    '<div class="empty-state">' +
                    '<i class="fas fa-exclamation-triangle empty-state-icon"></i>' +
                    '<div class="empty-state-title">Error Loading Data</div>' +
                    '<div class="empty-state-text">Please try again later</div>' +
                    '</div>'
                );
            }
        });
    }

    function renderCollections(collections) {
        if (!collections || collections.length === 0) {
            $('#collectionsContainer').html(
                '<div class="empty-state">' +
                '<i class="fas fa-inbox empty-state-icon"></i>' +
                '<div class="empty-state-title">No Payments Found</div>' +
                '<div class="empty-state-text">Try adjusting your filters to see more results</div>' +
                '</div>'
            );
            return;
        }

        let html = '';

        collections.forEach(function(collection, collectionIndex) {
            // Group payments by payment method
            const paymentsByMethod = {};
            collection.payments.forEach(function(payment) {
                const method = payment.payment_method.toLowerCase();
                if (!paymentsByMethod[method]) {
                    paymentsByMethod[method] = [];
                }
                paymentsByMethod[method].push(payment);
            });

            const collectionId = 'collection-' + collectionIndex;
            const totalPayments = collection.payments.length;

            // Get notes from first payment if available
            const collectionNotes = collection.payments[0]?.notes || '';

            // LEVEL 1: Collection Group Header
            html += `
                <div class="collection-group">
                    <div class="collection-main-header" data-bs-toggle="collapse" data-bs-target="#${collectionId}" aria-expanded="false">
                        <div class="collection-header-left">
                            <div class="collection-reference">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>${collection.reference_no || 'N/A'}</span>
                            </div>
                            <div class="collection-meta">
                                <div class="collection-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span>${collection.customer_name || 'N/A'}</span>
                                </div>
                                <div class="collection-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>${collection.payment_date}</span>
                                </div>
                                ${collection.location ? `
                                <div class="collection-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${collection.location}</span>
                                </div>
                                ` : ''}
                                ${collectionNotes ? `
                                <div class="collection-meta-item" style="flex-basis: 100%; margin-top: 6px;">
                                    <i class="fas fa-sticky-note"></i>
                                    <span style="color: #3b82f6; font-weight: 600;">${collectionNotes}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="collection-header-right">
                            <div class="collection-total-amount">${formatCurrency(collection.total_amount)}</div>
                            <div class="collection-payment-count">
                                <i class="fas fa-receipt me-1"></i>${totalPayments} Payment${totalPayments > 1 ? 's' : ''}
                            </div>
                        </div>
                        <i class="fas fa-chevron-down collapse-icon"></i>
                    </div>

                    <div class="collapse" id="${collectionId}">
                        <!-- LEVEL 2: Payment Method Tabs -->
                        <div class="payment-method-tabs">
            `;

            let methodIndex = 0;
            Object.keys(paymentsByMethod).forEach(function(method) {
                const payments = paymentsByMethod[method];
                const config = getPaymentMethodConfig(method);
                const methodTotal = payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
                const isActive = methodIndex === 0 ? 'active' : '';
                const methodTabId = `${collectionId}-${method}`;

                html += `
                    <div class="payment-method-tab ${method} ${isActive}" data-bs-toggle="tab" data-bs-target="#${methodTabId}">
                        <div class="tab-label">
                            <i class="${config.icon} tab-icon"></i>
                            <span>${config.label}</span>
                        </div>
                        <div class="tab-count">${payments.length} payment${payments.length > 1 ? 's' : ''}</div>
                        <div class="tab-amount">${formatCurrency(methodTotal)}</div>
                    </div>
                `;
                methodIndex++;
            });

            html += `
                        </div>

                        <div class="tab-content">
            `;

            // LEVEL 3 & 4: Payment References and Invoice Settlement
            methodIndex = 0;
            Object.keys(paymentsByMethod).forEach(function(method) {
                const payments = paymentsByMethod[method];
                const isActive = methodIndex === 0 ? 'show active' : '';
                const methodTabId = `${collectionId}-${method}`;

                html += `
                    <div class="tab-pane fade ${isActive}" id="${methodTabId}">
                        <div class="payment-references-container">
                `;

                // Group by reference number (cheque no, transaction id, etc.)
                const paymentsByReference = {};
                payments.forEach(function(payment) {
                    let refKey = 'single';
                    if (method === 'cheque' && payment.cheque_number) {
                        refKey = payment.cheque_number;
                    } else if (method === 'bank_transfer' && payment.reference_no) {
                        refKey = payment.reference_no;
                    } else if (payment.reference_no) {
                        refKey = payment.reference_no;
                    }

                    if (!paymentsByReference[refKey]) {
                        paymentsByReference[refKey] = [];
                    }
                    paymentsByReference[refKey].push(payment);
                });

                let refIndex = 0;
                Object.keys(paymentsByReference).forEach(function(refKey) {
                    const refPayments = paymentsByReference[refKey];
                    const refTotal = refPayments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
                    const firstPayment = refPayments[0];
                    const refCardId = `${methodTabId}-ref-${refIndex}`;

                    html += `
                        <div class="payment-reference-card ${method}">
                            <div class="reference-header" data-bs-toggle="collapse" data-bs-target="#${refCardId}">
                                <div class="reference-left">
                                    <div class="reference-number">
                    `;

                    if (method === 'cheque' && firstPayment.cheque_number) {
                        html += `
                            <span>Cheque No: ${firstPayment.cheque_number}</span>
                            <span class="reference-badge cheque">CHEQUE</span>
                        `;
                    } else if (method === 'bank_transfer') {
                        html += `
                            <span>Transfer Ref: ${refKey}</span>
                            <span class="reference-badge bank_transfer">BANK</span>
                        `;
                    } else if (method === 'cash') {
                        html += `
                            <span>Cash Payment</span>
                            <span class="reference-badge cash">CASH</span>
                        `;
                    } else if (method === 'card') {
                        html += `
                            <span>Card Payment</span>
                            <span class="reference-badge card">CARD</span>
                        `;
                    }

                    html += `
                                    </div>
                                    <div class="reference-details">
                    `;

                    if (method === 'cheque') {
                        if (firstPayment.cheque_bank_branch) {
                            html += `
                                <div class="reference-detail-item">
                                    <i class="fas fa-university"></i>
                                    <span>${firstPayment.cheque_bank_branch}</span>
                                </div>
                            `;
                        }
                        if (firstPayment.cheque_valid_date) {
                            html += `
                                <div class="reference-detail-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Due: ${firstPayment.cheque_valid_date}</span>
                                </div>
                            `;
                        }
                        if (firstPayment.cheque_status) {
                            let statusClass = 'pending';
                            if (firstPayment.cheque_status.toLowerCase() === 'cleared') {
                                statusClass = 'cleared';
                            } else if (firstPayment.cheque_status.toLowerCase() === 'bounced') {
                                statusClass = 'bounced';
                            }
                            html += `
                                <div class="reference-detail-item">
                                    <span class="status-badge ${statusClass}">${firstPayment.cheque_status}</span>
                                </div>
                            `;
                        }
                    }

                    html += `
                                        <div class="reference-detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>${firstPayment.payment_date}</span>
                                        </div>
                    `;

                    // Display notes globally if available
                    if (firstPayment.notes && firstPayment.notes.trim() !== '') {
                        html += `
                                        <div class="reference-detail-item" style="flex-basis: 100%; margin-top: 4px;">
                                            <i class="fas fa-sticky-note"></i>
                                            <span style="color: #3b82f6; font-weight: 500;">${firstPayment.notes}</span>
                                        </div>
                        `;
                    }

                    html += `
                                    </div>
                                </div>
                                <div class="reference-right">
                                    <div class="reference-amount">${formatCurrency(refTotal)}</div>
                                    <div class="reference-invoice-count">${refPayments.length} invoice${refPayments.length > 1 ? 's' : ''}</div>
                                </div>
                                <i class="fas fa-chevron-down reference-expand-icon"></i>
                            </div>

                            <!-- LEVEL 4: Invoice Settlement Table -->
                            <div class="collapse" id="${refCardId}">
                                <div class="invoice-settlement-table">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Invoice Date</th>
                                                <th>Invoice No.</th>
                                                <th>Invoice Value</th>
                                                <th>Settled Amount</th>
                                                <th>Type</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    refPayments.forEach(function(payment) {
                        html += `
                            <tr>
                                <td>${payment.invoice_date || payment.payment_date}</td>
                                <td><a href="#" class="invoice-link">${payment.invoice_no || 'N/A'}</a></td>
                                <td>${formatCurrency(payment.invoice_value)}</td>
                                <td class="fw-bold text-success">${formatCurrency(payment.amount)}</td>
                                <td><span class="badge bg-info">${payment.payment_type}</span></td>
                                <td><small class="text-muted">${payment.notes || '-'}</small></td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                                <div class="reference-footer">
                                    <span class="reference-footer-label">Reference Total:</span>
                                    <span class="reference-footer-amount">${formatCurrency(refTotal)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    refIndex++;
                });

                html += `
                        </div>
                    </div>
                `;
                methodIndex++;
            });

            html += `
                        </div>
                    </div>
                </div>
            `;
        });

        $('#collectionsContainer').html(html);

        // Initialize Bootstrap collapse events
        $('.collection-main-header').on('click', function() {
            $(this).toggleClass('active');
        });

        $('.reference-header').on('click', function() {
            $(this).toggleClass('active');
        });

        // Initialize tab functionality
        $('.payment-method-tab').on('click', function() {
            const target = $(this).data('bs-target');
            const parent = $(this).closest('.collection-group');

            parent.find('.payment-method-tab').removeClass('active');
            $(this).addClass('active');

            parent.find('.tab-pane').removeClass('show active');
            parent.find(target).addClass('show active');
        });
    }

    // Initial load
    loadPaymentCollections();

    // Contact Type onChange event
    $('#contactTypeFilter').change(function() {
        var contactType = $(this).val();

        if (contactType === 'customer') {
            $('#customerFilterGroup').removeClass('d-none');
            $('#supplierFilterGroup').addClass('d-none');
            $('#supplierFilter').val('').trigger('change');
            // Reinitialize Select2 for customer filter
            $('#customerFilter').select2('destroy').select2();
            updatePaymentTypeOptions('sale');
        } else if (contactType === 'supplier') {
            $('#supplierFilterGroup').removeClass('d-none');
            $('#customerFilterGroup').addClass('d-none');
            $('#customerFilter').val('').trigger('change');
            // Reinitialize Select2 for supplier filter
            $('#supplierFilter').select2('destroy').select2();
            updatePaymentTypeOptions('purchase');
        } else {
            $('#customerFilterGroup').removeClass('d-none');
            $('#supplierFilterGroup').addClass('d-none');
            $('#supplierFilter').val('').trigger('change');
            // Reinitialize Select2 for customer filter
            $('#customerFilter').select2('destroy').select2();
            updatePaymentTypeOptions('all');
        }

        loadPaymentCollections();
        updateSummary();
    });

    // Function to update payment type options
    function updatePaymentTypeOptions(type) {
        var paymentTypeFilter = $('#paymentTypeFilter');
        paymentTypeFilter.empty();

        if (type === 'sale') {
            paymentTypeFilter.append('<option value="">All Types</option>');
            paymentTypeFilter.append('<option value="sale">Sale Payment</option>');
            paymentTypeFilter.append('<option value="return">Return Payment</option>');
        } else if (type === 'purchase') {
            paymentTypeFilter.append('<option value="">All Types</option>');
            paymentTypeFilter.append('<option value="purchase">Purchase Payment</option>');
            paymentTypeFilter.append('<option value="return">Return Payment</option>');
        } else {
            paymentTypeFilter.append('<option value="">All Types</option>');
            paymentTypeFilter.append('<option value="sale">Sale Payment</option>');
            paymentTypeFilter.append('<option value="purchase">Purchase Payment</option>');
            paymentTypeFilter.append('<option value="return">Return Payment</option>');
        }

        paymentTypeFilter.trigger('change');
    }

    // onChange events for all filters
    $('#customerFilter, #supplierFilter, #locationFilter, #paymentMethodFilter, #paymentTypeFilter').change(function() {
        loadPaymentCollections();
        updateSummary();
    });

    // Date range onChange event
    $('#reportrange').on('apply.daterangepicker', function() {
        loadPaymentCollections();
        updateSummary();
    });

    // Update summary function
    function updateSummary() {
        var dateRange = $('#reportrange').data('daterangepicker');
        var filterData = {
            customer_id: $('#customerFilter').val(),
            supplier_id: $('#supplierFilter').val(),
            location_id: $('#locationFilter').val(),
            payment_method: $('#paymentMethodFilter').val(),
            payment_type: $('#paymentTypeFilter').val(),
            start_date: dateRange.startDate.format('YYYY-MM-DD'),
            end_date: dateRange.endDate.format('YYYY-MM-DD'),
            ajax_summary: 1
        };

        $.ajax({
            url: '{{ route("payment.report") }}',
            type: 'GET',
            data: filterData,
            success: function(response) {
                updateSummaryCards(response.summaryData);
            },
            error: function(xhr) {
                console.error('Error updating summary:', xhr);
            }
        });
    }

    function updateSummaryCards(summaryData) {
        $('#totalPayments').text(formatCurrency(summaryData.total_amount));
        $('#cashPayments').text(formatCurrency(summaryData.cash_total));
        $('#cardPayments').text(formatCurrency(summaryData.card_total));
        $('#chequePayments').text(formatCurrency(summaryData.cheque_total));
        $('#bankPayments').text(formatCurrency(summaryData.bank_transfer_total));

        if (summaryData.total_amount > 0) {
            $('#cashPercentage').text(Math.round((summaryData.cash_total / summaryData.total_amount) * 100 * 10) / 10 + '% of total');
            $('#cardPercentage').text(Math.round((summaryData.card_total / summaryData.total_amount) * 100 * 10) / 10 + '% of total');
            $('#chequePercentage').text(Math.round((summaryData.cheque_total / summaryData.total_amount) * 100 * 10) / 10 + '% of total');
            $('#bankPercentage').text(Math.round((summaryData.bank_transfer_total / summaryData.total_amount) * 100 * 10) / 10 + '% of total');
        }

        if (summaryData.other_total && summaryData.other_total > 0) {
            $('#otherPayments').text(formatCurrency(summaryData.other_total));
            if (summaryData.total_amount > 0) {
                $('#otherPercentage').text(Math.round((summaryData.other_total / summaryData.total_amount) * 100 * 10) / 10 + '% of total');
            }
        }
    }

    // Export functions
    $('#exportPdf').click(function(e) {
        e.preventDefault();
        exportReport('pdf');
    });

    $('#exportExcel').click(function(e) {
        e.preventDefault();
        exportReport('excel');
    });

    function exportReport(format) {
        try {
            var dateRange = $('#reportrange').data('daterangepicker');
            var params = new URLSearchParams({
                customer_id: $('#customerFilter').val() || '',
                supplier_id: $('#supplierFilter').val() || '',
                location_id: $('#locationFilter').val() || '',
                payment_method: $('#paymentMethodFilter').val() || '',
                payment_type: $('#paymentTypeFilter').val() || '',
                start_date: dateRange.startDate.format('YYYY-MM-DD'),
                end_date: dateRange.endDate.format('YYYY-MM-DD')
            });

            var url = format === 'pdf'
                ? '{{ route("payment.report.export.pdf") }}?' + params.toString()
                : '{{ route("payment.report.export.excel") }}?' + params.toString();

            window.location.href = url;
        } catch (error) {
            console.error('Export error:', error);
            alert('Error exporting report. Please try again.');
        }
    }

    // ===== PRINT A4 REPORT =====
    // Clicking "Print A4 Report" triggers PDF download via the same export route
    $('#printReport').click(function(e) {
        e.preventDefault();
        generatePrintReport();
    });

    function generatePrintReport() {
        try {
            var dateRange = $('#reportrange').data('daterangepicker');
            var params = new URLSearchParams({
                customer_id: $('#customerFilter').val() || '',
                supplier_id: $('#supplierFilter').val() || '',
                location_id: $('#locationFilter').val() || '',
                payment_method: $('#paymentMethodFilter').val() || '',
                payment_type: $('#paymentTypeFilter').val() || '',
                start_date: dateRange.startDate.format('YYYY-MM-DD'),
                end_date: dateRange.endDate.format('YYYY-MM-DD')
            });

            // Use the PDF export route to generate and download the A4 PDF
            var url = '{{ route("payment.report.export.pdf") }}?' + params.toString();
            window.location.href = url;
        } catch (error) {
            console.error('Print report error:', error);
            alert('Error generating print report. Please try again.');
        }
    }
});
</script>
@endsection
