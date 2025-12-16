@extends('layout.layout')
@section('title', 'Payment Report')

@section('content')
    <style>
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

        /* Select2 Styling for Perfect Alignment */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 44px !important;
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 12px !important;
            padding-right: 30px !important;
            color: #333 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #999 !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .select2-dropdown {
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        .form-group.local-forms {
            margin-bottom: 1rem;
        }

        .form-group.local-forms label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        /* Date Range Picker Styling */
        #reportrange {
            background: #fff;
            cursor: pointer;
            padding: 11px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 44px;
        }

        #reportrange:hover {
            border-color: #80bdff;
        }

        #reportrange i {
            color: #666;
        }

        #reportrange span {
            flex: 1;
            margin: 0 10px;
            color: #333;
            font-size: 14px;
        }

        .summary-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            background: white;
        }

        .summary-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .summary-item .card-body {
            padding: 0.75rem 0.5rem;
        }

        .summary-item .card-title {
            font-weight: bold;
            font-size: 1.25rem;
            line-height: 1.3;
            margin-bottom: 0.25rem;
        }

        .summary-item .card-text {
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0;
        }

        /* Responsive font sizes */
        @media (max-width: 1400px) {
            .summary-item .card-title {
                font-size: 1.15rem;
            }
            .summary-item .card-text {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 992px) {
            .summary-item .card-title {
                font-size: 1.1rem;
            }
            .summary-item .card-text {
                font-size: 0.68rem;
            }
        }

        @media (max-width: 768px) {
            .summary-item .card-title {
                font-size: 1.2rem;
            }
            .summary-item .card-text {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 576px) {
            .summary-item .card-title {
                font-size: 1rem;
            }
            .summary-item .card-text {
                font-size: 0.65rem;
            }
        }

        /* Individual card value colors */
        .card-total .card-title {
            color: #2563eb; /* Clean Blue */
        }

        .card-cash .card-title {
            color: #16a34a; /* Fresh Green */
        }

        .card-card .card-title {
            color: #9333ea; /* Modern Purple */
        }

        .card-cheque .card-title {
            color: #ea580c; /* Warm Orange */
        }

        .card-sale .card-title {
            color: #0891b2; /* Professional Cyan */
        }

        .card-purchase .card-title {
            color: #dc2626; /* Clean Red */
        }

        .card-bank .card-title {
            color: #8b5cf6; /* Purple */
        }

        .card-other .card-title {
            color: #f59e0b; /* Amber */
        }

        .payment-method-card {
            background: #fff;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .payment-method-card h6 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .payment-method-card .amount {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
        }

        /* Collection Group Styling */
        .collection-group {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .collection-header {
            background: #4a5568;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 3px solid #2d3748;
        }

        .collection-header:hover {
            background: #2d3748;
        }

        .collection-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 16px;
            color: #ffffff;
        }

        .collection-header h6 i {
            color: #ffffff;
        }

        .collection-header .badge {
            font-size: 13px;
            padding: 6px 12px;
            font-weight: 500;
        }

        .collection-header small {
            opacity: 0.9;
            font-size: 13px;
        }

        .collection-details {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .collection-info {
            flex: 1;
            min-width: 200px;
        }

        .collection-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .collection-info strong {
            color: #495057;
        }

        .nested-table {
            padding: 0 20px 15px 20px;
        }

        .nested-table table {
            background: white;
            font-size: 13px;
            margin-bottom: 0;
        }

        .nested-table thead th {
            background: #f1f3f5;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 10px;
        }

        .nested-table tbody td {
            padding: 10px;
            vertical-align: middle;
        }

        .nested-table .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.02);
        }

        .collection-footer {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 0 0 8px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            border-top: 2px solid #dee2e6;
        }

        .collection-footer .total-label {
            font-size: 16px;
            color: #2d3748;
            font-weight: 600;
        }

        .collection-footer .total-amount {
            font-size: 20px;
            color: #16a34a;
            font-weight: 700;
        }

        .collapse-icon {
            transition: transform 0.3s ease;
        }

        .collapsed .collapse-icon {
            transform: rotate(-180deg);
        }

        .no-collection-group {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        @media print {
            .collection-header {
                background: #4a5568 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .collection-group {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }

            .btn-group, .student-group-form {
                display: none !important;
            }
        }
    </style>

    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Payment Report</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                                <li class="breadcrumb-item active">Payment Report</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-total h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="totalAmount" class="card-title mb-1">Rs {{ number_format($summaryData['total_amount'], 2) }}</h4>
                            <p class="card-text mb-0">Total Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-cash h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="cashTotal" class="card-title mb-1">Rs {{ number_format($summaryData['cash_total'], 2) }}</h4>
                            <p class="card-text mb-0">Cash Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-card h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="cardTotal" class="card-title mb-1">Rs {{ number_format($summaryData['card_total'], 2) }}</h4>
                            <p class="card-text mb-0">Card Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-cheque h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="chequeTotal" class="card-title mb-1">Rs {{ number_format($summaryData['cheque_total'], 2) }}</h4>
                            <p class="card-text mb-0">Cheque Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-bank h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="bankTransferTotal" class="card-title mb-1">Rs {{ number_format($summaryData['bank_transfer_total'] ?? 0, 2) }}</h4>
                            <p class="card-text mb-0">Bank Transfer</p>
                        </div>
                    </div>
                </div>
                @if(isset($summaryData['other_total']) && $summaryData['other_total'] > 0)
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-other h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="otherTotal" class="card-title mb-1">Rs {{ number_format($summaryData['other_total'], 2) }}</h4>
                            <p class="card-text mb-0">Other Methods</p>
                        </div>
                    </div>
                </div>
                @endif
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-sale h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="salePayments" class="card-title mb-1">Rs {{ number_format($summaryData['sale_payments'], 2) }}</h4>
                            <p class="card-text mb-0">Sale Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-purchase h-100">
                        <div class="card-body text-center p-2">
                            <h4 id="purchasePayments" class="card-title mb-1">Rs {{ number_format($summaryData['purchase_payments'], 2) }}</h4>
                            <p class="card-text mb-0">Purchase Payments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card card-body mb-4">
                <div class="student-group-form d-flex align-items-start flex-wrap gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters">
                        <i class="fas fa-filter"></i> &nbsp; Filters
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-success dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Columns Visibility
                        </button>
                        <ul class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" id="columnVisibilityDropdown"
                            style="width: 400px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="hide all">Hide All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="show all">Show All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="0">Payment ID</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="1">Date</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="2">Amount</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="3">Payment Method</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="4">Payment Type</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="5">Reference No</a></li>
                                </div>
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="6">Invoice No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="7">Customer</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="8">Supplier</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="9">Location</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="10">Cheque No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="11">Status</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="12">Actions</a></li>
                                </div>
                            </div>
                        </ul>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-info dropdown-toggle" type="button" id="exportDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="exportPdf"><i class="fas fa-file-pdf"></i> Export PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel"></i> Export Excel</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="collapse" id="collapseFilters">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <div class="row">
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Contact Type:</label>
                                    <select class="form-control selectBox" id="contactTypeFilter">
                                        <option value="">All Contacts</option>
                                        <option value="customer">Customer</option>
                                        <option value="supplier">Supplier</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6" id="customerFilterContainer">
                                <div class="form-group local-forms">
                                    <label>Customer:</label>
                                    <select class="form-control selectBox" id="customerFilter">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->first_name }} {{ $customer->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6" id="supplierFilterContainer">
                                <div class="form-group local-forms">
                                    <label>Supplier:</label>
                                    <select class="form-control selectBox" id="supplierFilter">
                                        <option value="">All Suppliers</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->first_name }} {{ $supplier->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Location:</label>
                                    <select class="form-control selectBox" id="locationFilter">
                                        <option value="">All Locations</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Payment Method:</label>
                                    <select class="form-control selectBox" id="paymentMethodFilter">
                                        <option value="">All Methods</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Payment Type:</label>
                                    <select class="form-control selectBox" id="paymentTypeFilter">
                                        <option value="">All Types</option>
                                        <option value="sale">Sale Payment</option>
                                        <option value="purchase">Purchase Payment</option>
                                        <option value="return">Return Payment</option>
                                        <option value="recovery">Recovery Payment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Date Range:</label>
                                    <div id="reportrange">
                                        <i class="fa fa-calendar"></i>
                                        <span></span>
                                        <i class="fa fa-caret-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Payment Details - Grouped by Collection</h5>
                        </div>
                        <div class="card-body" id="paymentCollectionsContainer">
                            <!-- Collections will be loaded here via AJAX -->
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3">Loading payment collections...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Detail Modal -->
    <div class="modal fade" id="paymentDetailModal" tabindex="-1" role="dialog" aria-labelledby="paymentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentDetailModalLabel">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="paymentDetailContent">
                    <!-- Payment details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
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

        // Set initial text before initialization
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
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        // Ensure callback is called after initialization
        cb(start, end);

        // Load payment collections
        function loadPaymentCollections() {
            var filters = {
                _token: "{{ csrf_token() }}",
                customer_id: $('#customerFilter').val(),
                supplier_id: $('#supplierFilter').val(),
                location_id: $('#locationFilter').val(),
                payment_method: $('#paymentMethodFilter').val(),
                payment_type: $('#paymentTypeFilter').val(),
            };

            var dateRange = $('#reportrange').data('daterangepicker');
            if (dateRange) {
                filters.start_date = dateRange.startDate.format('YYYY-MM-DD');
                filters.end_date = dateRange.endDate.format('YYYY-MM-DD');
            }

            $.ajax({
                url: "{{ route('payment.report.data') }}",
                type: "POST",
                data: filters,
                success: function(response) {
                    renderCollections(response.collections);
                },
                error: function(xhr) {
                    $('#paymentCollectionsContainer').html(
                        '<div class="alert alert-danger">Error loading payment collections. Please try again.</div>'
                    );
                }
            });
        }

        function renderCollections(collections) {
            if (!collections || collections.length === 0) {
                $('#paymentCollectionsContainer').html(
                    '<div class="alert alert-info">No payment collections found for the selected filters.</div>'
                );
                return;
            }

            let html = '';

            collections.forEach(function(collection, index) {
                const collectionId = collection.reference_no ? collection.reference_no.replace(/[^a-zA-Z0-9]/g, '-') : 'single-' + index;
                const isCollectionGroup = collection.reference_no && (collection.reference_no.startsWith('BLK-') || collection.reference_no.startsWith('BULK-')) && collection.payments.length > 1;

                if (isCollectionGroup) {
                    // Bulk collection group
                    html += `
                        <div class="collection-group">
                            <div class="collection-header" data-bs-toggle="collapse" data-bs-target="#collection-${collectionId}" aria-expanded="true">
                                <div>
                                    <h6>
                                        <i class="fas fa-receipt me-2"></i>
                                        Collection Receipt: ${collection.reference_no}
                                    </h6>
                                    <small>${collection.payment_date} | ${collection.customer_name || collection.supplier_name || 'N/A'}</small>
                                </div>
                                <div>
                                    <span class="badge bg-white text-dark me-2">${collection.payments.length} Payment${collection.payments.length > 1 ? 's' : ''}</span>
                                    <span class="badge bg-success">Rs ${parseFloat(collection.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    <i class="fas fa-chevron-up collapse-icon ms-2"></i>
                                </div>
                            </div>

                            <div class="collapse show" id="collection-${collectionId}">
                                <div class="collection-details">
                                    <div class="collection-info">
                                        <p><strong>Customer:</strong> ${collection.customer_name || 'N/A'}</p>
                                        <p><strong>Address:</strong> ${collection.customer_address || 'N/A'}</p>
                                    </div>
                                    <div class="collection-info">
                                        <p><strong>Collection Date:</strong> ${collection.payment_date}</p>
                                        <p><strong>Location:</strong> ${collection.location || 'N/A'}</p>
                                    </div>
                                </div>

                                <div class="nested-table">
                                    <table class="table table-striped table-bordered table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Invoice Date</th>
                                                <th>Invoice No.</th>
                                                <th>Invoice Value</th>
                                                <th>Payment Method</th>
                                                <th>Payment Details</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;

                    collection.payments.forEach(function(payment) {
                        // Determine payment method badge color
                        let paymentMethodBadge = '';
                        if (payment.payment_method.toLowerCase() === 'cash') {
                            paymentMethodBadge = '<span class="badge bg-success">Cash</span>';
                        } else if (payment.payment_method.toLowerCase() === 'card') {
                            paymentMethodBadge = '<span class="badge bg-primary">Card</span>';
                        } else if (payment.payment_method.toLowerCase() === 'cheque') {
                            paymentMethodBadge = '<span class="badge bg-warning">Cheque</span>';
                        } else if (payment.payment_method.toLowerCase() === 'bank_transfer') {
                            paymentMethodBadge = '<span class="badge bg-info">Bank Transfer</span>';
                        } else {
                            paymentMethodBadge = '<span class="badge bg-secondary">' + payment.payment_method + '</span>';
                        }

                        // Build cheque details only for cheque payments
                        let chequeDetails = '-';
                        if (payment.payment_method.toLowerCase() === 'cheque' && payment.cheque_number) {
                            chequeDetails = `<strong>Cheque No:</strong> ${payment.cheque_number}<br>`;
                            if (payment.cheque_bank_branch) {
                                chequeDetails += `<strong>Bank:</strong> ${payment.cheque_bank_branch}<br>`;
                            }
                            if (payment.cheque_valid_date) {
                                chequeDetails += `<strong>Due:</strong> ${payment.cheque_valid_date}`;
                            }
                        }

                        html += `
                            <tr>
                                <td>${payment.invoice_date || payment.payment_date}</td>
                                <td>${payment.invoice_no || '-'}</td>
                                <td class="text-end">Rs ${parseFloat(payment.invoice_value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td>${paymentMethodBadge}</td>
                                <td style="font-size: 12px;">${chequeDetails}</td>
                                <td class="text-end text-success fw-bold">Rs ${parseFloat(payment.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            </tr>`;
                    });

                    html += `
                                        </tbody>
                                    </table>
                                </div>

                                <div class="collection-footer">
                                    <span class="total-label">Total Collection Amount:</span>
                                    <span class="total-amount">Rs ${parseFloat(collection.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                </div>
                            </div>
                        </div>`;
                } else {
                    // Single payment (not part of bulk collection)
                    html += `
                        <div class="no-collection-group">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Customer/Supplier</th>
                                            <th>Invoice No.</th>
                                            <th>Payment Method</th>
                                            <th>Payment Type</th>
                                            <th>Reference No</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;

                    collection.payments.forEach(function(payment) {
                        html += `
                            <tr>
                                <td>${payment.id}</td>
                                <td>${payment.payment_date}</td>
                                <td>${payment.customer_name || payment.supplier_name || '-'}</td>
                                <td>${payment.invoice_no || '-'}</td>
                                <td>${payment.payment_method}</td>
                                <td><span class="badge bg-info">${payment.payment_type}</span></td>
                                <td>${payment.reference_no || '-'}</td>
                                <td class="text-end text-success fw-bold">Rs ${parseFloat(payment.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                            </tr>`;
                    });

                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
                }
            });

            $('#paymentCollectionsContainer').html(html);
        }

        // Initial load
        loadPaymentCollections();

        // Contact Type onChange event
        $('#contactTypeFilter').change(function() {
            var contactType = $(this).val();

            if (contactType === 'customer') {
                // Show customer dropdown, hide supplier dropdown
                $('#customerFilterContainer').show();
                $('#supplierFilterContainer').hide();
                // Clear supplier filter
                $('#supplierFilter').val('').trigger('change');
                // Auto-update payment type filter to show sale payments
                updatePaymentTypeOptions('sale');
            } else if (contactType === 'supplier') {
                // Show supplier dropdown, hide customer dropdown
                $('#supplierFilterContainer').show();
                $('#customerFilterContainer').hide();
                // Clear customer filter
                $('#customerFilter').val('').trigger('change');
                // Auto-update payment type filter to show purchase payments
                updatePaymentTypeOptions('purchase');
            } else {
                // Show both dropdowns
                $('#customerFilterContainer').show();
                $('#supplierFilterContainer').show();
                // Reset payment type filter
                updatePaymentTypeOptions('all');
            }

            // Reload data
            loadPaymentCollections();
            updateSummary();
        });

        // Function to update payment type options based on contact type
        function updatePaymentTypeOptions(type) {
            var paymentTypeFilter = $('#paymentTypeFilter');
            paymentTypeFilter.empty();

            if (type === 'sale') {
                paymentTypeFilter.append('<option value="">All Sale Types</option>');
                paymentTypeFilter.append('<option value="sale">Sale Payment</option>');
                paymentTypeFilter.append('<option value="return">Return Payment</option>');
            } else if (type === 'purchase') {
                paymentTypeFilter.append('<option value="">All Purchase Types</option>');
                paymentTypeFilter.append('<option value="purchase">Purchase Payment</option>');
                paymentTypeFilter.append('<option value="return">Return Payment</option>');
            } else {
                paymentTypeFilter.append('<option value="">All Types</option>');
                paymentTypeFilter.append('<option value="sale">Sale Payment</option>');
                paymentTypeFilter.append('<option value="purchase">Purchase Payment</option>');
                paymentTypeFilter.append('<option value="return">Return Payment</option>');
                paymentTypeFilter.append('<option value="recovery">Recovery Payment</option>');
            }

            paymentTypeFilter.trigger('change');
        }

        // Initialize Contact Type functionality on page load
        $('#contactTypeFilter').trigger('change');

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

        // Column visibility - Not applicable for grouped view
        $('#columnVisibilityDropdown').hide();

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
            var form = $('<form method="POST" action="' +
                "{{ route('payment.report.export.pdf') }}" + '">');

            if (format === 'excel') {
                form.attr('action', "{{ route('payment.report.export.excel') }}");
            }

            form.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
            form.append('<input type="hidden" name="customer_id" value="' + $('#customerFilter').val() + '">');
            form.append('<input type="hidden" name="supplier_id" value="' + $('#supplierFilter').val() + '">');
            form.append('<input type="hidden" name="location_id" value="' + $('#locationFilter').val() + '">');
            form.append('<input type="hidden" name="payment_method" value="' + $('#paymentMethodFilter').val() + '">');
            form.append('<input type="hidden" name="payment_type" value="' + $('#paymentTypeFilter').val() + '">');

            var dateRange = $('#reportrange').data('daterangepicker');
            if (dateRange) {
                form.append('<input type="hidden" name="start_date" value="' + dateRange.startDate.format('YYYY-MM-DD') + '">');
                form.append('<input type="hidden" name="end_date" value="' + dateRange.endDate.format('YYYY-MM-DD') + '">');
            }

            $('body').append(form);
            form.submit();
            form.remove();
        }

        function updateSummary() {
            $.ajax({
                url: "{{ route('payment.report') }}",
                type: 'GET',
                data: {
                    customer_id: $('#customerFilter').val(),
                    supplier_id: $('#supplierFilter').val(),
                    location_id: $('#locationFilter').val(),
                    payment_method: $('#paymentMethodFilter').val(),
                    payment_type: $('#paymentTypeFilter').val(),
                    start_date: $('#reportrange').data('daterangepicker').startDate.format('YYYY-MM-DD'),
                    end_date: $('#reportrange').data('daterangepicker').endDate.format('YYYY-MM-DD'),
                    ajax_summary: true
                },
                success: function(response) {
                    if (response.summaryData) {
                        $('#totalAmount').text('Rs ' + parseFloat(response.summaryData.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#cashTotal').text('Rs ' + parseFloat(response.summaryData.cash_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#cardTotal').text('Rs ' + parseFloat(response.summaryData.card_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#chequeTotal').text('Rs ' + parseFloat(response.summaryData.cheque_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#bankTransferTotal').text('Rs ' + parseFloat(response.summaryData.bank_transfer_total || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        if (response.summaryData.other_total && response.summaryData.other_total > 0) {
                            $('#otherTotal').text('Rs ' + parseFloat(response.summaryData.other_total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        }
                        $('#salePayments').text('Rs ' + parseFloat(response.summaryData.sale_payments).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#purchasePayments').text('Rs ' + parseFloat(response.summaryData.purchase_payments).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    }
                }
            });
        }
    });
</script>
@endsection


