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
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group local-forms">
                                <label>Invoice No:</label>
                                <input type="text" class="form-control" id="invoiceNoFilter" placeholder="Search by invoice no...">
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
                                        <th>Full Record</th>
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
    <!-- Modal: Full record for super admin -->
    <div class="modal fade" id="fullRecordModal" tabindex="-1" aria-labelledby="fullRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullRecordModalLabel">Full Record — Sale Edit Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Complete change details for this sale edit are shown below.</p>
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
                                } else if (item.subject_type === 'App\\Models\\Customer') {
                                    subjectTypeLabel = 'Customer';
                                } else if (item.subject_type === 'App\\Models\\Supplier') {
                                    subjectTypeLabel = 'Supplier';
                                } else if (item.subject_type === 'App\\Models\\StockAdjustment') {
                                    subjectTypeLabel = 'Stock Adjustment';
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
                                // Sale Note — FULL record: all customer changes and every field change
                                else if (subjectTypeLabel === 'Sale' && item.event ===
                                    'updated' && item.properties && item.properties.attributes
                                    ) {
                                    let attr = item.properties.attributes;
                                    let old = item.properties.old || {};
                                    let invoiceNo = attr.invoice_no || saleInvoiceMap[item.subject_id] || '';
                                    let allKeys = {};
                                    Object.keys(attr).forEach(function(k) { allKeys[k] = true; });
                                    Object.keys(old).forEach(function(k) { allKeys[k] = true; });
                                    note += `<div class="sale-edit-details">`;
                                    if (invoiceNo) note += `<b>Invoice No:</b> ${invoiceNo}<br>`;
                                    note += `<table class="table table-sm table-bordered mb-0 mt-1" style="font-size:0.9em;"><thead><tr><th>Field</th><th>Old</th><th>New</th></tr></thead><tbody>`;
                                    Object.keys(allKeys).sort().forEach(function(key) {
                                        let ov = old[key];
                                        let nv = attr[key];
                                        if (ov === nv && ov !== undefined) return;
                                        let oldStr = formatVal(key, ov);
                                        let newStr = formatVal(key, nv);
                                        note += `<tr><td>${getFieldLabel(key)}</td><td><span class="badge bg-secondary">${escapeHtml(String(oldStr))}</span></td><td><span class="badge bg-success">${escapeHtml(String(newStr))}</span></td></tr>`;
                                    });
                                    note += `</tbody></table></div>`;
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
                                // Customer Note
                                else if (subjectTypeLabel === 'Customer' && item.properties && item.properties.attributes) {
                                    let attr = item.properties.attributes;
                                    let old = item.properties.old || {};
                                    let name = (attr.first_name || '') + ' ' + (attr.last_name || '');
                                    note += `<div>`;
                                    if (name.trim()) note += `<b>Name:</b> ${escapeHtml(name.trim())}<br>`;
                                    if (attr.mobile_no) note += `<b>Mobile:</b> ${escapeHtml(attr.mobile_no)}<br>`;
                                    if (attr.email) note += `<b>Email:</b> ${escapeHtml(attr.email)}<br>`;
                                    if (item.event === 'updated' && Object.keys(old).length) {
                                        note += `<small class="text-muted">Changed fields: ${Object.keys(attr).filter(function(k) { return old[k] !== attr[k]; }).join(', ')}</small>`;
                                    }
                                    if (typeof attr.opening_balance !== 'undefined') note += `<b>Opening Balance:</b> ${parseFloat(attr.opening_balance).toLocaleString()}<br>`;
                                    if (typeof attr.credit_limit !== 'undefined') note += `<b>Credit Limit:</b> ${parseFloat(attr.credit_limit).toLocaleString()}<br>`;
                                    note += `</div>`;
                                }
                                // Supplier Note
                                else if (subjectTypeLabel === 'Supplier' && item.properties && item.properties.attributes) {
                                    let attr = item.properties.attributes;
                                    let old = item.properties.old || {};
                                    let name = (attr.first_name || '') + ' ' + (attr.last_name || '');
                                    note += `<div>`;
                                    if (name.trim()) note += `<b>Name:</b> ${escapeHtml(name.trim())}<br>`;
                                    if (attr.mobile_no) note += `<b>Mobile:</b> ${escapeHtml(attr.mobile_no)}<br>`;
                                    if (attr.email) note += `<b>Email:</b> ${escapeHtml(attr.email)}<br>`;
                                    if (item.event === 'updated' && Object.keys(old).length) {
                                        note += `<small class="text-muted">Changed: ${Object.keys(attr).filter(function(k) { return old[k] !== attr[k]; }).join(', ')}</small>`;
                                    }
                                    if (typeof attr.opening_balance !== 'undefined') note += `<b>Opening Balance:</b> ${parseFloat(attr.opening_balance).toLocaleString()}<br>`;
                                    note += `</div>`;
                                }
                                // Stock Adjustment Note
                                else if (subjectTypeLabel === 'Stock Adjustment' && item.properties && item.properties.attributes) {
                                    let attr = item.properties.attributes;
                                    note += `<div>`;
                                    if (attr.reference_no) note += `<b>Reference No:</b> ${attr.reference_no}<br>`;
                                    if (attr.adjustment_type) note += `<b>Type:</b> <span class="badge bg-info">${attr.adjustment_type}</span><br>`;
                                    if (attr.date) note += `<b>Date:</b> ${attr.date}<br>`;
                                    if (typeof attr.total_amount_recovered !== 'undefined') note += `<b>Amount Recovered:</b> ${parseFloat(attr.total_amount_recovered).toLocaleString()}<br>`;
                                    if (attr.reason) note += `<b>Reason:</b> ${escapeHtml(attr.reason)}<br>`;
                                    note += `</div>`;
                                }
                                else if (item.description) {
                                    note = item.description;
                                }
                                // Full record button: show modal with full properties (for Sale updated especially)
                                let fullRecordBtn = '';
                                if (item.properties && (item.subject_type === 'App\\Models\\Sale' && item.event === 'updated')) {
                                    let propsEnc = encodeURIComponent(JSON.stringify(item.properties));
                                    let namesEnc = encodeURIComponent(JSON.stringify(customerNames));
                                    fullRecordBtn = `<button type="button" class="btn btn-sm btn-outline-primary full-record-btn" data-properties-enc="${propsEnc}" data-names-enc="${namesEnc}" title="Full record">Full record</button>`;
                                } else {
                                    fullRecordBtn = '—';
                                }
                                tbody += `<tr>
                                    <td>${date}</td>
                                    <td>${subjectTypeLabel ?? ''}</td>
                                    <td>${action}</td>
                                    <td>${by}</td>
                                    <td>${note}</td>
                                    <td>${fullRecordBtn}</td>
                                </tr>`;
                            });
                            // If no rows after filtering
                            if (!tbody) {
                                tbody =
                                    '<tr><td colspan="6" class="text-center">No data found</td></tr>';
                            }
                        } else {
                            tbody = '<tr><td colspan="6" class="text-center">No data found</td></tr>';
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
