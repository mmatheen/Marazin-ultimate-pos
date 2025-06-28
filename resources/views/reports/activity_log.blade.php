@extends('layout.layout')
@section('title', 'Activity Log')
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
    </style>
    <div class="container-fluid content">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Activity Log</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                                <li class="breadcrumb-item active">Activity Log</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Filters Section -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-body">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Advanced Filters -->
        <div class="row mb-3 collapse" id="collapseExample">
            <div class="col-12">
                <div class="card card-body">
                    <form class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group local-forms">
                                <label>By:</label>
                                <select class="form-control select" id="byFilter">
                                    <option value="">All</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group local-forms">
                                <label>Subject Type:</label>
                                <select class="form-control select" id="subjectTypeFilter">
                                    <option value="">All</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group local-forms">
                                <label>Date Range:</label>
                                <div id="reportrange"
                                    style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                    <i class="fa fa-calendar"></i>&nbsp;
                                    <span></span> <i class="fa fa-caret-down"></i>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Table Section -->
        <div class="row">
            <div class="col-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="datatable table table-striped" style="width:100%" id="salesTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Subject Type</th>
                                        <th>Action</th>
                                        <th>By</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
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

                            // Filter data based on selected filters (including "All")
                            let filteredData = response.data.filter(function(item) {
                                // Only filter if a value is selected (not "All")
                                let typeMatch = !selectedType || item.subject_type ===
                                    selectedType;
                                let causerMatch = !selectedCauser || String(item.causer_id) ===
                                    String(selectedCauser);
                                return typeMatch && causerMatch;
                            });

                            filteredData.forEach(function(item) {
                                // Format subject type
                                let subjectTypeLabel = item.subject_type;
                                if (item.subject_type === 'App\\Models\\User') {
                                    subjectTypeLabel = 'User';
                                } else if (item.subject_type === 'App\\Models\\Sale') {
                                    subjectTypeLabel = 'Sale';
                                } else if (item.subject_type === 'App\\Models\\SalesReturn') {
                                    subjectTypeLabel = 'Sale Return';
                                } else if (item.subject_type === 'App\\Models\\Purchase') {
                                    subjectTypeLabel = 'Purchase';
                                } else if (item.subject_type === 'App\\Models\\StockTransfer') {
                                    subjectTypeLabel = 'Stock Transfer';
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
                                // Format Note
                                let note = '';
                                // Sale Return Note
                                if (subjectTypeLabel === 'Sale Return' && item.event ===
                                    'created' && item.properties && item.properties.attributes
                                    ) {
                                    let attr = item.properties.attributes;
                                    note += `<div>`;
                                    if (attr.invoice_number) note +=
                                        `<b>Return Invoice:</b> ${attr.invoice_number}<br>`;
                                    // Find related sale invoice number
                                    let saleInvoice = '';
                                    if (attr.sale_id && salesReturnSaleIdToInvoice[attr
                                        .sale_id]) {
                                        saleInvoice = salesReturnSaleIdToInvoice[attr.sale_id];
                                    }
                                    if (saleInvoice) note +=
                                        `<b>Sale Invoice:</b> ${saleInvoice}<br>`;
                                    if (attr.return_total) note +=
                                        `<b>Total:</b> <span class="badge bg-warning text-dark">${parseFloat(attr.return_total).toLocaleString()}</span><br>`;
                                    if (attr.payment_status) note +=
                                        `<b>Payment Status:</b> ${attr.payment_status}<br>`;
                                    note += `</div>`;
                                }
                                // Sale Note
                                else if (subjectTypeLabel === 'Sale' && item.event ===
                                    'updated' && item.properties && item.properties.attributes
                                    ) {
                                    let attr = item.properties.attributes;
                                    let old = item.properties.old || {};
                                    let invoiceNo = attr.invoice_no || saleInvoiceMap[item
                                        .subject_id] || '';
                                    note += `<div>`;
                                    if (invoiceNo) note +=
                                    `<b>Invoice No:</b> ${invoiceNo}<br>`;
                                    if (typeof attr.subtotal !== 'undefined' && typeof old
                                        .subtotal !== 'undefined' && attr.subtotal !== old
                                        .subtotal) {
                                        note +=
                                            `<b>Total:</b> <span class="badge bg-secondary">${parseFloat(old.subtotal).toLocaleString()}</span> &rarr; <span class="badge bg-warning text-dark">${parseFloat(attr.subtotal).toLocaleString()}</span><br>`;
                                    }
                                    if (typeof attr.payment_status !== 'undefined' && attr
                                        .payment_status !== old.payment_status) {
                                        note +=
                                            `<b>Payment Status:</b> <span class="badge bg-secondary">${old.payment_status ?? ''}</span> &rarr; <span class="badge bg-warning text-dark">${attr.payment_status}</span><br>`;
                                    }
                                    note += `</div>`;
                                } else if (subjectTypeLabel === 'Sale' && item.event ===
                                    'created' && item.properties && item.properties.attributes
                                    ) {
                                    let attr = item.properties.attributes;
                                    note += `<div>`;
                                    if (attr.invoice_no) note +=
                                        `<b>Invoice No:</b> ${attr.invoice_no}<br>`;
                                    if (attr.status) note +=
                                    `<b>Status:</b> ${attr.status}<br>`;
                                    if (attr.subtotal) note +=
                                        `<b>Total:</b> <span class="badge bg-warning text-dark">${parseFloat(attr.subtotal).toLocaleString()}</span><br>`;
                                    note += `</div>`;
                                }
                                // Purchase Note (Arrived Purchase)
                                else if (subjectTypeLabel === 'Purchase' && item.event === 'created' && item.properties && item.properties.attributes) {
                                    let attr = item.properties.attributes;
                                    note += `<div>`;
                                    if (attr.reference_no) note += `<b>Reference No:</b> ${attr.reference_no}<br>`;
                                    if (attr.purchasing_status) note += `<b>Status:</b> ${attr.purchasing_status}<br>`;
                                    if (attr.final_total) note += `<b>Total:</b> <span class="badge bg-warning text-dark">${parseFloat(attr.final_total).toLocaleString()}</span><br>`;
                                    if (attr.payment_status) note += `<b>Payment Status:</b> ${attr.payment_status}<br>`;
                                    note += `</div>`;
                                }
                                // Stock Transfer Note
                                else if (subjectTypeLabel === 'Stock Transfer' && item.properties && item.properties.attributes) {
                                    let attr = item.properties.attributes;
                                    note += `<div>`;
                                    if (attr.reference_no) note += `<b>Reference No:</b> ${attr.reference_no}<br>`;
                                    if (attr.from_warehouse_name) note += `<b>From:</b> ${attr.from_warehouse_name}<br>`;
                                    if (attr.to_warehouse_name) note += `<b>To:</b> ${attr.to_warehouse_name}<br>`;
                                    if (attr.status) note += `<b>Status:</b> ${attr.status}<br>`;
                                    if (attr.total) note += `<b>Total:</b> <span class="badge bg-warning text-dark">${parseFloat(attr.total).toLocaleString()}</span><br>`;
                                    note += `</div>`;
                                }
                                else if (item.description) {
                                    note = item.description;
                                }
                                tbody += `<tr>
                                    <td>${date}</td>
                                    <td>${subjectTypeLabel ?? ''}</td>
                                    <td>${action}</td>
                                    <td>${by}</td>
                                    <td>${note}</td>
                                </tr>`;
                            });
                            // If no rows after filtering
                            if (!tbody) {
                                tbody =
                                    '<tr><td colspan="5" class="text-center">No data found</td></tr>';
                            }
                        } else {
                            tbody = '<tr><td colspan="5" class="text-center">No data found</td></tr>';
                            // Clear dropdowns if no data
                            $('#subjectTypeFilter').html('<option value="">All</option>');
                            $('#byFilter').html('<option value="">All</option>');
                        }
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
            // Initial fetch
            fetchActivityLog();
            // Refetch on filter change
            $('#subjectTypeFilter, #byFilter').on('change', fetchActivityLog);
            $('#reportrange').on('apply.daterangepicker', fetchActivityLog);
        });
    </script>
@endsection
