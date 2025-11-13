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
            padding: 1.5rem 1rem;
        }

        .summary-item .card-title {
            font-weight: bold;
            font-size: 1.75rem;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .summary-item .card-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0;
        }

        /* Responsive font sizes */
        @media (max-width: 768px) {
            .summary-item .card-title {
                font-size: 1.5rem;
            }
            .summary-item .card-text {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .summary-item .card-title {
                font-size: 1.25rem;
            }
            .summary-item .card-text {
                font-size: 0.75rem;
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
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-total h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="totalAmount" class="card-title mb-2">Rs {{ number_format($summaryData['total_amount'], 2) }}</h4>
                            <p class="card-text mb-0">Total Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-cash h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="cashTotal" class="card-title mb-2">Rs {{ number_format($summaryData['cash_total'], 2) }}</h4>
                            <p class="card-text mb-0">Cash Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-card h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="cardTotal" class="card-title mb-2">Rs {{ number_format($summaryData['card_total'], 2) }}</h4>
                            <p class="card-text mb-0">Card Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-cheque h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="chequeTotal" class="card-title mb-2">Rs {{ number_format($summaryData['cheque_total'], 2) }}</h4>
                            <p class="card-text mb-0">Cheque Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-sale h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="salePayments" class="card-title mb-2">Rs {{ number_format($summaryData['sale_payments'], 2) }}</h4>
                            <p class="card-text mb-0">Sale Payments</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6 col-12 mb-3">
                    <div class="card summary-item card-purchase h-100">
                        <div class="card-body text-center p-3">
                            <h4 id="purchasePayments" class="card-title mb-2">Rs {{ number_format($summaryData['purchase_payments'], 2) }}</h4>
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
                            <h5 class="card-title">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="paymentTable" width="100%">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Payment Type</th>
                                            <th>Reference No</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Supplier</th>
                                            <th>Location</th>
                                            <th>Cheque No</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
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

        // Initialize DataTable
        var table = $('#paymentTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: "{{ route('payment.report.data') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.customer_id = $('#customerFilter').val();
                    d.supplier_id = $('#supplierFilter').val();
                    d.location_id = $('#locationFilter').val();
                    d.payment_method = $('#paymentMethodFilter').val();
                    d.payment_type = $('#paymentTypeFilter').val();
                    
                    var dateRange = $('#reportrange').data('daterangepicker');
                    if (dateRange) {
                        d.start_date = dateRange.startDate.format('YYYY-MM-DD');
                        d.end_date = dateRange.endDate.format('YYYY-MM-DD');
                    }
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'payment_date', name: 'payment_date' },
                { 
                    data: 'amount', 
                    name: 'amount',
                    render: function(data, type, row) {
                        return '<span class="text-success fw-bold">Rs ' + data + '</span>';
                    }
                },
                { data: 'payment_method', name: 'payment_method' },
                { data: 'payment_type', name: 'payment_type' },
                { data: 'reference_no', name: 'reference_no' },
                { data: 'invoice_no', name: 'invoice_no' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'location', name: 'location' },
                { data: 'cheque_number', name: 'cheque_number' },
                { 
                    data: 'cheque_status', 
                    name: 'cheque_status',
                    render: function(data, type, row) {
                        if (row.payment_method !== 'Cheque') return '-';
                        var badgeClass = '';
                        switch(data) {
                            case 'Pending': badgeClass = 'warning'; break;
                            case 'Cleared': badgeClass = 'success'; break;
                            case 'Bounced': badgeClass = 'danger'; break;
                            default: badgeClass = 'secondary';
                        }
                        return '<span class="badge bg-' + badgeClass + '">' + data + '</span>';
                    }
                },
                { 
                    data: 'id',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<button class="btn btn-sm btn-info view-payment" data-id="' + data + '">' +
                               '<i class="fas fa-eye"></i> View</button>';
                    }
                }
            ],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            order: [[1, 'desc']],
            responsive: true,
            scrollX: true
        });

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
            table.ajax.reload();
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
            table.ajax.reload();
            updateSummary();
        });

        // Date range onChange event
        $('#reportrange').on('apply.daterangepicker', function() {
            table.ajax.reload();
            updateSummary();
        });

        // Column visibility
        $('#columnVisibilityDropdown a').click(function(e) {
            e.preventDefault();
            var value = $(this).data('value');
            
            if (value === 'hide all') {
                for (var i = 0; i < table.columns().count(); i++) {
                    table.column(i).visible(false);
                }
            } else if (value === 'show all') {
                for (var i = 0; i < table.columns().count(); i++) {
                    table.column(i).visible(true);
                }
            } else {
                var column = table.column(value);
                column.visible(!column.visible());
            }
        });

        // View payment details
        $(document).on('click', '.view-payment', function() {
            var paymentId = $(this).data('id');
            
            $.ajax({
                url: "{{ route('payment.detail', '') }}/" + paymentId,
                type: 'GET',
                success: function(response) {
                    var html = '<div class="row">';
                    html += '<div class="col-md-6">';
                    html += '<h6>Payment Information</h6>';
                    html += '<p><strong>Payment ID:</strong> ' + response.payment.id + '</p>';
                    html += '<p><strong>Date:</strong> ' + response.formatted_date + '</p>';
                    html += '<p><strong>Amount:</strong> $' + response.formatted_amount + '</p>';
                    html += '<p><strong>Method:</strong> ' + response.payment.payment_method + '</p>';
                    html += '<p><strong>Type:</strong> ' + response.payment.payment_type + '</p>';
                    html += '<p><strong>Reference:</strong> ' + (response.payment.reference_no || 'N/A') + '</p>';
                    
                    if (response.payment.payment_method === 'cheque') {
                        html += '<h6 class="mt-3">Cheque Details</h6>';
                        html += '<p><strong>Cheque No:</strong> ' + (response.payment.cheque_number || 'N/A') + '</p>';
                        html += '<p><strong>Bank:</strong> ' + (response.payment.cheque_bank_branch || 'N/A') + '</p>';
                        html += '<p><strong>Status:</strong> ' + (response.payment.cheque_status || 'N/A') + '</p>';
                    }
                    
                    html += '</div>';
                    html += '<div class="col-md-6">';
                    
                    if (response.payment.customer) {
                        html += '<h6>Customer Details</h6>';
                        html += '<p><strong>Name:</strong> ' + response.payment.customer.first_name + ' ' + response.payment.customer.last_name + '</p>';
                        html += '<p><strong>Mobile:</strong> ' + (response.payment.customer.mobile_no || 'N/A') + '</p>';
                    }
                    
                    if (response.payment.supplier) {
                        html += '<h6>Supplier Details</h6>';
                        html += '<p><strong>Name:</strong> ' + response.payment.supplier.supplier_name + '</p>';
                    }
                    
                    html += '<p><strong>Location:</strong> ' + (response.location_name || 'N/A') + '</p>';
                    html += '<p><strong>Invoice No:</strong> ' + (response.invoice_no || 'N/A') + '</p>';
                    
                    if (response.payment.notes) {
                        html += '<h6 class="mt-3">Notes</h6>';
                        html += '<p>' + response.payment.notes + '</p>';
                    }
                    
                    html += '</div>';
                    html += '</div>';
                    
                    $('#paymentDetailContent').html(html);
                    $('#paymentDetailModal').modal('show');
                },
                error: function() {
                    alert('Error loading payment details');
                }
            });
        });

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
                        $('#salePayments').text('Rs ' + parseFloat(response.summaryData.sale_payments).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                        $('#purchasePayments').text('Rs ' + parseFloat(response.summaryData.purchase_payments).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                    }
                }
            });
        }
    });
</script>
@endsection


