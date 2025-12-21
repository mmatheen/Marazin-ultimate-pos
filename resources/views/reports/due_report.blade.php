@extends('layout.layout')
@section('title', 'Due Report')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        .selected-column {
            background-color: #007bff !important;
            color: white !important;
        }
        .selected-column:hover {
            background-color: #0056b3 !important;
            color: white !important;
        }
        .badge-recent { background-color: #28a745; }
        .badge-medium { background-color: #ffc107; color: #000; }
        .badge-old { background-color: #fd7e14; }
        .badge-critical { background-color: #dc3545; }

        /* Select2 Alignment Fixes */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 44px !important;
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
            padding: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 12px !important;
            color: #5a5a5a !important;
            font-size: 14px !important;
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
            outline: 0 !important;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25) !important;
        }

        .select2-dropdown {
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 8px !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        /* Ensure proper spacing in form groups */
        .form-group.local-forms {
            margin-bottom: 20px;
        }

        .form-group.local-forms label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
            color: #333;
        }

        /* Row Group Styling */
        table.dataTable tbody tr.dtrg-group td {
            font-weight: 600;
            font-size: 15px;
            border-top: 2px solid #e9ecef !important;
            border-bottom: 1px solid #e9ecef !important;
        }

        table.dataTable tbody tr.dtrg-group:hover td {
            background: linear-gradient(to right, #f0f4f8 0%, #ffffff 100%) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08) !important;
        }

        /* Alternating row colors for better readability */
        #dueReportTable tbody tr:not(.dtrg-group):nth-child(even) {
            background-color: #f9f9f9;
        }

        #dueReportTable tbody tr:not(.dtrg-group):hover {
            background-color: #e3f2fd !important;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Due Report</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Reports</a></li>
                            <li class="breadcrumb-item active">Due Report</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Report Type Toggle --}}
        <div class="card card-body mb-4">
                <div class="student-group-form d-flex align-items-start flex-wrap gap-2">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="reportType" id="customerReport" value="customer" checked>
                        <label class="btn btn-outline-primary" for="customerReport">
                            <i class="fas fa-user"></i> Customer Due Report
                        </label>

                        <input type="radio" class="btn-check" name="reportType" id="supplierReport" value="supplier">
                        <label class="btn btn-outline-primary" for="supplierReport">
                            <i class="fas fa-truck"></i> Supplier Due Report
                        </label>
                    </div>

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
                                    <li><a class="dropdown-item" href="#" data-value="hide all">1. Hide All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="show all">2. Show All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="1">Invoice/Ref No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="2">Party Name</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="3">Mobile No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="4">Date</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="5">Location</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="6">Created By</a></li>
                                </div>
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="7">Final Total</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="8">Total Paid</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="9">Original Due</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="10">Return Amount</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="11">Final Due</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="12">Payment Status</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="13">Due Days</a></li>
                                </div>
                            </div>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="collapse" id="collapseFilters">
                <div class="card card-body mb-3">
                    <div class="student-group-form">
                        <form id="dueFilterForm">
                            <div class="row">
                                {{-- Customer Filter --}}
                                <div class="col-lg-3 col-md-6" id="customerFilterDiv">
                                    <div class="form-group local-forms">
                                        <label>Customer:</label>
                                        <select class="form-control selectBox" id="customerFilter" name="customer_id">
                                            <option value="">All Customers</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">
                                                    {{ $customer->full_name }} - {{ $customer->mobile_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Supplier Filter --}}
                                <div class="col-lg-3 col-md-6" id="supplierFilterDiv" style="display: none;">
                                    <div class="form-group local-forms">
                                        <label>Supplier:</label>
                                        <select class="form-control selectBox" id="supplierFilter" name="supplier_id">
                                            <option value="">All Suppliers</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">
                                                    {{ $supplier->full_name }} - {{ $supplier->mobile_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Business Location:</label>
                                        <select class="form-control selectBox" id="locationFilter" name="location_id">
                                            <option value="">All locations</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Created By User:</label>
                                        <select class="form-control selectBox" id="userFilter" name="user_id">
                                            <option value="">All Users</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Date Range:</label>
                                        <div id="reportrange"
                                            style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                            <i class="fa fa-calendar"></i>&nbsp;
                                            <span></span> <i class="fa fa-caret-down"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms" style="margin-bottom: 0;">
                                        <label style="visibility: hidden;">.</label>
                                        <button type="button" class="btn btn-secondary d-block" id="resetFiltersBtn">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card w-100" style="background-color: #dc3545;">
                    <div class="card-body">
                        <div class="text-white">
                            <h6 class="text-white mb-2">
                                <i class="fas fa-balance-scale"></i> Total Due Amount (Ledger-based)
                            </h6>
                            <h4 class="text-white mb-0">Rs. {{ number_format($summaryData['total_due'], 2) }}</h4>
                            <small class="text-white-50" style="font-size: 11px;">
                                <i class="fas fa-info-circle"></i> Includes all transactions, opening balance & adjustments
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card w-100" style="background-color: #ffc107;">
                    <div class="card-body">
                        <div class="text-dark">
                            <h6 class="text-dark mb-2">Total Due Bills</h6>
                            <h4 class="text-dark mb-0">{{ number_format($summaryData['total_bills']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card w-100" style="background-color: #17a2b8;">
                    <div class="card-body">
                        <div class="text-white">
                            <h6 class="text-white mb-2" id="partiesLabel">Total Customers</h6>
                            <h4 class="text-white mb-0">{{ number_format($summaryData['total_parties']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 col-12 d-flex">
                <div class="card w-100" style="background-color: #007bff;">
                    <div class="card-body">
                        <div class="text-white">
                            <h6 class="text-white mb-2">Average Due Per Bill</h6>
                            <h4 class="text-white mb-0">Rs. {{ number_format($summaryData['avg_due_per_bill'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info" style="background: #e7f3ff; border-left: 4px solid #0d6efd; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: start;">
                        <i class="fas fa-info-circle" style="color: #0d6efd; font-size: 20px; margin-right: 12px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #084298; display: block; margin-bottom: 4px;">Understanding Due Report Totals</strong>
                            <p style="color: #084298; margin: 0; font-size: 13px; line-height: 1.6;">
                                • <strong>Total Due Amount (top card):</strong> Shows complete ledger balance including opening balance, manual adjustments, and all transactions<br>
                                • <strong>Bills Due (in table):</strong> Shows sum of individual unpaid bills only<br>
                                • <strong>Why they differ:</strong> Customer accounts may have opening balances or ledger adjustments not tied to specific bills
                            </p>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="dueReportTable">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th id="refNoHeader">Invoice No</th>
                                <th id="partyNameHeader">Customer Name</th>
                                <th>Mobile No</th>
                                <th id="dateHeader">Sale Date</th>
                                <th>Location</th>
                                <th>Created By</th>
                                <th>Final Total</th>
                                <th>Total Paid</th>
                                <th>Original Due</th>
                                <th style="border-left: 3px solid #ff9800;">Return Amount</th>
                                <th style="border-left: 3px solid #dc3545; font-weight: 600;">Final Due</th>
                                <th>Payment Status</th>
                                <th>Due Days</th>
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

    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .text-white {
            color: #ffffff !important;
        }

        .text-dark {
            color: #212529 !important;
        }

        .btn-check:checked + .btn-outline-primary {
            background-color: #007bff;
            color: white;
        }

        @media print {
            .btn-group, .page-header, #dueFilterForm {
                display: none !important;
            }
        }
    </style>

    <script>
        $(document).ready(function() {
            let currentReportType = 'customer';
            let table;

            // Get URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const urlReportType = urlParams.get('report_type');
            const urlCustomerId = urlParams.get('customer_id');
            const urlSupplierId = urlParams.get('supplier_id');
            const urlLocationId = urlParams.get('location_id');
            const urlUserId = urlParams.get('user_id');

            // Set report type from URL
            if (urlReportType) {
                currentReportType = urlReportType;
                if (urlReportType === 'supplier') {
                    $('#supplierReport').prop('checked', true);
                    $('#customerFilterDiv').hide();
                    $('#supplierFilterDiv').show();
                    $('#refNoHeader').text('Reference No');
                    $('#partyNameHeader').text('Supplier Name');
                    $('#dateHeader').text('Purchase Date');
                    $('#partiesLabel').text('Total Suppliers');
                }
            }

            // Initialize Date Range Picker
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
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, setDateRange);

            setDateRange(start, end);

            // Set filter values from URL after Select2 initialization
            setTimeout(function() {
                if (urlCustomerId && currentReportType === 'customer') {
                    $('#customerFilter').val(urlCustomerId).trigger('change');
                }
                if (urlSupplierId && currentReportType === 'supplier') {
                    $('#supplierFilter').val(urlSupplierId).trigger('change');
                }
                if (urlLocationId) {
                    $('#locationFilter').val(urlLocationId).trigger('change');
                }
                if (urlUserId) {
                    $('#userFilter').val(urlUserId).trigger('change');
                }
            }, 500);

            // Get filter values
            function getFilters() {
                const dateRange = $('#reportrange span').text().split(' - ');
                let startDate = '', endDate = '';

                // Check if a specific customer or supplier is selected
                const customerId = $('#customerFilter').val();
                const supplierId = $('#supplierFilter').val();

                // Only apply date filter if no specific customer/supplier is selected
                if (!customerId && !supplierId && dateRange.length === 2) {
                    startDate = moment(dateRange[0], 'MMMM D, YYYY').format('YYYY-MM-DD');
                    endDate = moment(dateRange[1], 'MMMM D, YYYY').format('YYYY-MM-DD');
                }

                const filters = {
                    report_type: currentReportType,
                    customer_id: customerId || '',
                    supplier_id: supplierId || '',
                    location_id: $('#locationFilter').val() || '',
                    user_id: $('#userFilter').val() || '',
                    start_date: startDate,
                    end_date: endDate
                };

                console.log('Filters:', filters);
                return filters;
            }

            // Initialize DataTable
            function initializeDataTable() {
                if (table) {
                    table.destroy();
                }

                table = $('#dueReportTable').DataTable({
                    "processing": true,
                    "serverSide": false,
                    "ajax": {
                        "url": "{{ route('due.report') }}",
                        "type": "GET",
                        "data": function(d) {
                            return $.extend({}, d, getFilters());
                        },
                        "dataSrc": function(json) {
                            console.log('AJAX Response:', json);
                            // Update summary cards
                            if (json.summary) {
                                updateSummaryCards(json.summary);
                            }
                            return json.data;
                        },
                        "error": function(xhr, error, thrown) {
                            console.error('DataTable AJAX Error:', error, thrown);
                            console.error('Response:', xhr.responseText);
                        }
                    },
                    "rowGroup": {
                        "dataSrc": function(row) {
                            return currentReportType === 'customer' ? row.customer_name : row.supplier_name;
                        },
                        "startRender": function(rows, group) {
                            let totalDueBills = 0;
                            let totalFinal = 0;
                            let totalPaid = 0;
                            let billCount = rows.count();
                            let customerId = null;
                            let customerName = group;

                            rows.every(function() {
                                let data = this.data();
                                totalDueBills += parseFloat(data.total_due || 0);
                                totalFinal += parseFloat(data.final_total || 0);
                                totalPaid += parseFloat(data.total_paid || 0);

                                // Get customer ID from the first row
                                if (customerId === null) {
                                    customerId = data.customer_id;
                                }
                            });

                            // The totalDueBills is just the sum of individual bills shown
                            // But we need to show the actual ledger-based balance for this customer
                            // The actual ledger balance will be shown in the badge

                            return $('<tr/>')
                                .append('<td colspan="14" style="background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%); border-left: 4px solid #007bff; font-weight: 600; padding: 14px 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">' +
                                    '<i class="fas fa-user-circle" style="color: #007bff; font-size: 18px; margin-right: 8px;"></i>' +
                                    '<span style="font-size: 15px; color: #2c3e50;">' + group + '</span>' +
                                    '<span style="margin-left: 15px; color: #95a5a6; font-weight: 400; font-size: 13px;">(' + billCount + ' bill' + (billCount > 1 ? 's' : '') + ')</span>' +
                                    '<span style="float: right; background: #fff5f5; color: #dc3545; font-size: 16px; padding: 6px 16px; border-radius: 6px; border: 1px solid #ffe0e0; font-weight: 700;">' +
                                    'Bills Due: Rs. ' + totalDueBills.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                                    '</span>' +
                                    '<span style="float: right; margin-right: 15px; color: #6c757d; font-size: 12px; padding: 6px 10px; background: #fff9e6; border-radius: 4px; border: 1px solid #ffe0b3;" title="This shows the sum of individual bills displayed. The total due amount at the top includes all ledger transactions (opening balance, adjustments, etc.)">' +
                                    '<i class="fas fa-info-circle"></i> Bill-based total' +
                                    '</span>' +
                                    '</td>');
                        }
                    },
                    "columns": [
                        {
                            "data": null,
                            "orderable": false,
                            "render": function(data, type, row) {
                                let viewUrl = currentReportType === 'customer'
                                    ? "{{ url('sales') }}/" + row.id
                                    : "{{ url('purchases') }}/" + row.id;
                                return `<div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="${viewUrl}"><i class="fas fa-eye"></i> View Details</a></li>
                                    </ul>
                                </div>`;
                            }
                        },
                        {
                            "data": function(row) {
                                return currentReportType === 'customer' ? row.invoice_no : row.reference_no;
                            },
                            "render": function(data) {
                                return '<strong>' + data + '</strong>';
                            }
                        },
                        {
                            "data": function(row) {
                                return currentReportType === 'customer' ? row.customer_name : row.supplier_name;
                            },
                            "render": function(data) {
                                return '<strong>' + data + '</strong>';
                            }
                        },
                        {
                            "data": function(row) {
                                return currentReportType === 'customer' ? row.customer_mobile : row.supplier_mobile;
                            }
                        },
                        {
                            "data": function(row) {
                                return currentReportType === 'customer' ? row.sales_date : row.purchase_date;
                            }
                        },
                        { "data": "location" },
                        { "data": "user" },
                        {
                            "data": "final_total",
                            "className": "text-end",
                            "render": function(data) {
                                return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        },
                        {
                            "data": "total_paid",
                            "className": "text-end",
                            "render": function(data) {
                                return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        },
                        {
                            "data": "original_due",
                            "className": "text-end",
                            "render": function(data) {
                                return '<span style="color: #6c757d;">Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>';
                            }
                        },
                        {
                            "data": "return_amount",
                            "className": "text-end",
                            "render": function(data) {
                                if (parseFloat(data) > 0) {
                                    return '<span style="color: #ff9800; border-left: 3px solid #ff9800; padding-left: 8px; display: inline-block;"><strong>- Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></span>';
                                }
                                return '<span style="color: #adb5bd; border-left: 3px solid #e9ecef; padding-left: 8px; display: inline-block;">Rs. 0.00</span>';
                            }
                        },
                        {
                            "data": "total_due",
                            "className": "text-end",
                            "render": function(data) {
                                return '<span style="color: #dc3545; border-left: 3px solid #dc3545; padding-left: 8px; display: inline-block;"><strong>Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></span>';
                            }
                        },
                        {
                            "data": "payment_status",
                            "className": "text-center",
                            "render": function(data) {
                                let badgeClass = data === 'paid' ? 'bg-success' : (data === 'partial' ? 'bg-warning text-dark' : 'bg-danger');
                                return '<span class="badge ' + badgeClass + '">' + data.toUpperCase() + '</span>';
                            }
                        },
                        {
                            "data": "due_days",
                            "className": "text-center",
                            "render": function(data, type, row) {
                                let badgeClass = 'badge-recent';
                                if (row.due_status === 'medium') badgeClass = 'badge-medium';
                                if (row.due_status === 'old') badgeClass = 'badge-old';
                                if (row.due_status === 'critical') badgeClass = 'badge-critical';

                                return '<span class="badge ' + badgeClass + '">' + data + ' days</span>';
                            }
                        }
                    ],
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    "order": [[2, 'asc'], [4, 'desc']],
                    "orderFixed": [[2, 'asc']],
                    "dom": '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',
                    "buttons": [
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="fa fa-file-pdf"></i> PDF',
                            orientation: 'landscape',
                            pageSize: 'A4',
                            filename: () => currentReportType + '_due_report_' + new Date().toISOString().slice(0, 10),
                            exportOptions: {
                                columns: ':visible:not(:first-child)'
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            filename: () => currentReportType + '_due_report_' + new Date().toISOString().slice(0, 10),
                            exportOptions: {
                                columns: ':visible:not(:first-child)'
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa fa-print"></i> Print',
                            title: currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Due Report',
                            exportOptions: {
                                columns: ':visible:not(:first-child)'
                            }
                        }
                    ],
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "infoEmpty": "Showing 0 to 0 of 0 entries",
                        "infoFiltered": "(filtered from _MAX_ total entries)",
                        "emptyTable": "No due bills found",
                        "zeroRecords": "No matching records found",
                        "processing": '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                    }
                });
            }

            // Initialize table on page load
            initializeDataTable();

            // Function to update summary cards
            function updateSummaryCards(summary) {
                // Update the summary cards dynamically
                $('.row.mb-3 .col-xl-3:eq(0) h4').text('Rs. ' + parseFloat(summary.total_due).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('.row.mb-3 .col-xl-3:eq(1) h4').text(summary.total_bills);
                $('.row.mb-3 .col-xl-3:eq(2) h4').text(summary.total_parties);
                $('.row.mb-3 .col-xl-3:eq(3) h4').text('Rs. ' + parseFloat(summary.avg_due_per_bill).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            }

            // Report type toggle
            $('input[name="reportType"]').on('change', function() {
                currentReportType = $(this).val();

                // Update UI labels
                if (currentReportType === 'customer') {
                    $('#customerFilterDiv').show();
                    $('#supplierFilterDiv').hide();
                    $('#refNoHeader').text('Invoice No');
                    $('#partyNameHeader').text('Customer Name');
                    $('#dateHeader').text('Sale Date');
                    $('#partiesLabel').text('Total Customers');
                    // Clear supplier filter
                    $('#supplierFilter').val(null).trigger('change');
                } else {
                    $('#customerFilterDiv').hide();
                    $('#supplierFilterDiv').show();
                    $('#refNoHeader').text('Reference No');
                    $('#partyNameHeader').text('Supplier Name');
                    $('#dateHeader').text('Purchase Date');
                    $('#partiesLabel').text('Total Suppliers');
                    // Clear customer filter
                    $('#customerFilter').val(null).trigger('change');
                }

                // Reload table
                table.ajax.reload();
            });

            // Auto-reload on filter change
            $('#customerFilter, #supplierFilter, #locationFilter, #userFilter').on('change', function() {
                table.ajax.reload();
            });

            // Auto-reload on date range change
            $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFiltersBtn').on('click', function() {
                // Clear select2 selections
                $('#customerFilter').val(null).trigger('change');
                $('#supplierFilter').val(null).trigger('change');
                $('#locationFilter').val(null).trigger('change');
                $('#userFilter').val(null).trigger('change');

                // Reset date range
                $('#reportrange').data('daterangepicker').setStartDate(moment());
                $('#reportrange').data('daterangepicker').setEndDate(moment());
                setDateRange(moment(), moment());

                // Reload table
                table.ajax.reload();
            });

            // Column Visibility Dropdown
            function updateDropdownHighlights() {
                $('#columnVisibilityDropdown a').each(function() {
                    const value = $(this).data('value');
                    if (value === "hide all") {
                        $(this).removeClass('selected-column');
                    } else if (value === "show all") {
                        let allVisible = true;
                        table.columns().every(function() {
                            if (!this.visible()) allVisible = false;
                        });
                        if (allVisible) $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    } else if (!isNaN(value)) {
                        if (table.column(value).visible()) $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    }
                });
            }

            $('#columnVisibilityDropdown a').on('click', function(e) {
                e.preventDefault();
                const selectedValue = $(this).data('value');
                if (selectedValue === "hide all") {
                    table.columns().visible(false);
                    $('#columnVisibilityDropdown a').removeClass('selected-column');
                    $(this).addClass('selected-column');
                } else if (selectedValue === "show all") {
                    table.columns().visible(true);
                    $('#columnVisibilityDropdown a').each(function() {
                        const val = $(this).data('value');
                        if (!isNaN(val) || val === "show all") $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    });
                } else {
                    const column = table.column(selectedValue);
                    column.visible(!column.visible());
                    $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass('selected-column');
                    if (column.visible()) $(this).addClass('selected-column');
                    else $(this).removeClass('selected-column');
                    updateDropdownHighlights();
                }
            });

            document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
        });
    </script>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/rowgroup/1.4.0/js/dataTables.rowGroup.min.js"></script>
@endpush
