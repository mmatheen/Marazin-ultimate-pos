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

        /* Compact table styling for better fit */
        #dueReportTable tbody td {
            font-size: 11px;
            padding: 8px 6px;
        }

        #dueReportTable .btn-sm {
            font-size: 10px;
            padding: 4px 8px;
        }

        #dueReportTable .badge {
            font-size: 9px;
            padding: 3px 6px;
        }

        /* Compact DataTables controls */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            font-size: 12px;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            font-size: 11px;
            padding: 4px 8px;
        }

        .dt-buttons .btn {
            font-size: 11px;
            padding: 6px 12px;
        }

        /* Sticky table header */
        #dueReportTable thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa !important;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* Ensure group headers also stick */
        #dueReportTable tbody tr.dtrg-group td {
            position: sticky;
            top: 0;
            z-index: 9;
        }

        /* Smooth scrolling */
        .table-responsive {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar styling */
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
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
        <div class="card" style="border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 15px;">
            <div class="card-body" style="padding: 16px 20px; border-bottom: 1px solid #e9ecef;">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="reportType" id="customerReport" value="customer" checked>
                        <label class="btn btn-primary" for="customerReport" style="padding: 8px 20px; font-size: 13px; font-weight: 500;">
                            <i class="fas fa-user me-1"></i> Customer
                        </label>

                        <input type="radio" class="btn-check" name="reportType" id="supplierReport" value="supplier">
                        <label class="btn btn-outline-primary" for="supplierReport" style="padding: 8px 20px; font-size: 13px; font-weight: 500;">
                            <i class="fas fa-truck me-1"></i> Supplier
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters"
                            style="padding: 8px 16px; font-size: 13px;">
                            <i class="fas fa-filter me-1"></i> Filters
                        </button>

                        <div class="btn-group">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-bs-toggle="dropdown" aria-expanded="false"
                                style="padding: 8px 16px; font-size: 13px;">
                                <i class="fas fa-columns me-1"></i> Columns
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
                <div style="background: #f8f9fa; padding: 16px 20px; border-bottom: 1px solid #dee2e6;">
                    <form id="dueFilterForm">
                        <div class="row g-2 align-items-center">
                            {{-- Customer Filter --}}
                            <div class="col-lg-3 col-md-6" id="customerFilterDiv">
                                <select class="form-select selectBox" id="customerFilter" name="customer_id" style="font-size: 13px; height: 38px;">
                                    <option value="">All Customers</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">
                                            {{ $customer->full_name }} - {{ $customer->mobile_no }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Supplier Filter --}}
                            <div class="col-lg-3 col-md-6" id="supplierFilterDiv" style="display: none;">
                                <select class="form-select selectBox" id="supplierFilter" name="supplier_id" style="font-size: 13px; height: 38px;">
                                    <option value="">All Suppliers</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">
                                            {{ $supplier->full_name }} - {{ $supplier->mobile_no }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <select class="form-select selectBox" id="locationFilter" name="location_id" style="font-size: 13px; height: 38px;">
                                    <option value="">All locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <select class="form-select selectBox" id="userFilter" name="user_id" style="font-size: 13px; height: 38px;">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <div id="reportrange" style="background: #fff; cursor: pointer; padding: 0 12px; border: 1px solid #ced4da; border-radius: 0.375rem; font-size: 13px; height: 38px; display: flex; align-items: center;">
                                    <i class="fa fa-calendar me-2" style="font-size: 12px; color: #6c757d;"></i>
                                    <span style="font-size: 13px;"></span>
                                </div>
                            </div>

                            <div class="col-auto">
                                <button type="button" class="btn btn-secondary" id="resetFiltersBtn" style="font-size: 13px; padding: 8px 16px; height: 38px;">
                                    <i class="fas fa-redo me-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        {{-- Summary Cards --}}
        <div class="row g-2 mb-2">
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.15); border-radius: 10px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                    <div class="card-body" style="padding: 12px 16px;">
                        <div class="d-flex align-items-center justify-content-between text-white">
                            <div>
                                <p class="mb-1" style="font-size: 11px; opacity: 0.9; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Total Due</p>
                                <h3 class="mb-0" style="font-size: 20px; font-weight: 700;">Rs. {{ number_format($summaryData['total_due'], 2) }}</h3>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-balance-scale" style="font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.15); border-radius: 10px; background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                    <div class="card-body" style="padding: 12px 16px;">
                        <div class="d-flex align-items-center justify-content-between text-dark">
                            <div>
                                <p class="mb-1" style="font-size: 11px; opacity: 0.8; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Total Bills</p>
                                <h3 class="mb-0" style="font-size: 20px; font-weight: 700;">{{ number_format($summaryData['total_bills']) }}</h3>
                            </div>
                            <div style="background: rgba(0,0,0,0.1); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-invoice" style="font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(23, 162, 184, 0.15); border-radius: 10px; background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                    <div class="card-body" style="padding: 12px 16px;">
                        <div class="d-flex align-items-center justify-content-between text-white">
                            <div>
                                <p class="mb-1" id="partiesLabel" style="font-size: 11px; opacity: 0.9; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Customers</p>
                                <h3 class="mb-0" style="font-size: 20px; font-weight: 700;">{{ number_format($summaryData['total_parties']) }}</h3>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users" style="font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="card h-100" style="border: none; box-shadow: 0 2px 8px rgba(111, 66, 193, 0.15); border-radius: 10px; background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);">
                    <div class="card-body" style="padding: 12px 16px;">
                        <div class="d-flex align-items-center justify-content-between text-white">
                            <div>
                                <p class="mb-1" style="font-size: 11px; opacity: 0.9; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;">Max Single Due</p>
                                <h3 class="mb-0" style="font-size: 20px; font-weight: 700;">Rs. {{ number_format($summaryData['max_single_due'], 2) }}</h3>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-arrow-up" style="font-size: 20px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="card" style="border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-radius: 10px;">
            <div class="card-body" style="padding: 12px 16px;">
                <div class="alert" style="background: #f8f9fa; border: none; border-left: 3px solid #6c757d; border-radius: 4px; padding: 6px 12px; margin-bottom: 10px;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle" style="color: #6c757d; font-size: 12px; margin-right: 8px;"></i>
                        <span style="color: #495057; font-size: 11px;">
                            <strong>Total Due:</strong> Ledger balance • <strong>Bills Due:</strong> Individual bills sum
                        </span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: calc(100vh - 260px); overflow-y: auto; overflow-x: auto;">
                    <table class="table table-bordered table-striped table-hover" id="dueReportTable">
                        <thead class="table-light">
                            <tr style="font-size: 11px;">
                                <th style="padding: 8px 6px;">Action</th>
                                <th id="refNoHeader" style="padding: 8px 6px;">Invoice No</th>
                                <th id="partyNameHeader" style="padding: 8px 6px;">Customer Name</th>
                                <th style="padding: 8px 6px;">Mobile No</th>
                                <th id="dateHeader" style="padding: 8px 6px;">Sale Date</th>
                                <th style="padding: 8px 6px;">Location</th>
                                <th style="padding: 8px 6px;">Created By</th>
                                <th style="padding: 8px 6px;">Final Total</th>
                                <th style="padding: 8px 6px;">Total Paid</th>
                                <th style="padding: 8px 6px;">Original Due</th>
                                <th style="border-left: 3px solid #ff9800; padding: 8px 6px;">Return Amt</th>
                                <th style="border-left: 3px solid #dc3545; font-weight: 600; padding: 8px 6px;">Final Due</th>
                                <th style="padding: 8px 6px;">Status</th>
                                <th style="padding: 8px 6px;">Days</th>
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
                                .append('<td colspan="14" style="background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%); border-left: 4px solid #007bff; font-weight: 600; padding: 10px 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' +
                                    '<i class="fas fa-user-circle" style="color: #007bff; font-size: 14px; margin-right: 6px;"></i>' +
                                    '<span style="font-size: 12px; color: #2c3e50;">' + group + '</span>' +
                                    '<span style="margin-left: 10px; color: #95a5a6; font-weight: 400; font-size: 10px;">(' + billCount + ' bill' + (billCount > 1 ? 's' : '') + ')</span>' +
                                    '<span style="float: right; background: #fff5f5; color: #dc3545; font-size: 13px; padding: 4px 10px; border-radius: 4px; border: 1px solid #ffe0e0; font-weight: 700;">' +
                                    'Due: Rs. ' + totalDueBills.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
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
                            pageSize: 'A3',
                            title: currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Due Report',
                            filename: () => currentReportType + '_due_report_' + new Date().toISOString().slice(0, 10),
                            exportOptions: {
                                columns: ':visible:not(:first-child)',
                                format: {
                                    body: function(data, row, column, node) {
                                        // Remove HTML tags and styling
                                        data = data.replace(/<span[^>]*>/g, '');
                                        data = data.replace(/<\/span>/g, '');
                                        data = data.replace(/<strong>/g, '');
                                        data = data.replace(/<\/strong>/g, '');
                                        data = data.replace(/Rs\.\s*/g, 'Rs. ');
                                        return data;
                                    }
                                }
                            },
                            customize: function(doc) {
                                doc.pageOrientation = 'landscape';
                                doc.pageSize = 'A3';
                                doc.defaultStyle.fontSize = 8;
                                doc.styles.tableHeader.fontSize = 9;
                                doc.styles.tableHeader.bold = true;
                                doc.styles.tableHeader.fillColor = '#4472C4';
                                doc.styles.tableHeader.color = 'white';

                                // Add title and date
                                doc.content[0].text = currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Due Report';
                                doc.content[0].style = 'header';
                                doc.content[0].alignment = 'center';
                                doc.content[0].fontSize = 16;
                                doc.content[0].bold = true;
                                doc.content[0].margin = [0, 0, 0, 10];

                                // Add generated date
                                doc.content.splice(1, 0, {
                                    text: 'Generated on: ' + new Date().toLocaleString(),
                                    alignment: 'center',
                                    fontSize: 10,
                                    margin: [0, 0, 0, 10]
                                });

                                // Style the table
                                if (doc.content[2] && doc.content[2].table) {
                                    doc.content[2].table.widths = Array(doc.content[2].table.body[0].length).fill('auto');
                                    doc.content[2].layout = {
                                        hLineWidth: function(i, node) { return 0.5; },
                                        vLineWidth: function(i, node) { return 0.5; },
                                        hLineColor: function(i, node) { return '#cccccc'; },
                                        vLineColor: function(i, node) { return '#cccccc'; }
                                    };
                                }
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            title: currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Due Report',
                            filename: () => currentReportType + '_due_report_' + new Date().toISOString().slice(0, 10),
                            exportOptions: {
                                columns: ':visible:not(:first-child)',
                                format: {
                                    body: function(data, row, column, node) {
                                        // Remove HTML tags and styling
                                        data = data.replace(/<span[^>]*>/g, '');
                                        data = data.replace(/<\/span>/g, '');
                                        data = data.replace(/<strong>/g, '');
                                        data = data.replace(/<\/strong>/g, '');
                                        data = data.replace(/Rs\.\s*/g, 'Rs. ');
                                        return data;
                                    }
                                }
                            },
                            customize: function(xlsx) {
                                var sheet = xlsx.xl.worksheets['sheet1.xml'];

                                // Add header row styling
                                $('row:first c', sheet).attr('s', '2');

                                // Auto-fit columns
                                $('row c[r^="A"]', sheet).attr('s', '50');
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa fa-print"></i> Print',
                            title: '<h2 style="text-align: center; margin-bottom: 20px;">' + currentReportType.charAt(0).toUpperCase() + currentReportType.slice(1) + ' Due Report</h2>',
                            messageTop: '<p style="text-align: center; margin-bottom: 20px;">Generated on: ' + new Date().toLocaleString() + '</p>',
                            exportOptions: {
                                columns: ':visible:not(:first-child)'
                            },
                            customize: function(win) {
                                $(win.document.body).css('font-size', '10pt');
                                $(win.document.body).find('table')
                                    .addClass('compact')
                                    .css({
                                        'font-size': '9pt',
                                        'border-collapse': 'collapse',
                                        'width': '100%'
                                    });
                                $(win.document.body).find('table thead th')
                                    .css({
                                        'background-color': '#4472C4',
                                        'color': 'white',
                                        'font-weight': 'bold',
                                        'padding': '8px',
                                        'border': '1px solid #ddd'
                                    });
                                $(win.document.body).find('table tbody td')
                                    .css({
                                        'padding': '6px',
                                        'border': '1px solid #ddd'
                                    });
                                $(win.document.body).find('table tbody tr:nth-child(even)')
                                    .css('background-color', '#f9f9f9');
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
