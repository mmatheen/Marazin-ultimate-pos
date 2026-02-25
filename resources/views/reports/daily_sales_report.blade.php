@extends('layout.layout')
@section('title', 'Daily Sales Report')
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

        .summary-row {
            transition: background-color 0.15s ease;
        }

        .summary-row:hover {
            background-color: #f5f7fa !important;
        }
    </style>
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Daily Sales Report</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                                <li class="breadcrumb-item active">Daily Sales Report</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card card-body mb-4">
                <div class="student-group-form d-flex align-items-start flex-wrap gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
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
                                    <li><a class="dropdown-item" href="#" data-value="hide all">1. Hide All
                                            Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="show all">2. Show All
                                            Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="0">#</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="1">Invoice No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="2">Customer</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="13">Location</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="14">User</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="3">Date</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="4">Sub Total</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="5">Bill Discount</a></li>

                                </div>
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="6">Net Bill Total</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="7">Cash</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="8">Bank Transfer</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="9">Cheque</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="10">Card</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="11">Credit</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="12">Sales Return</a></li>

                                </div>
                            </div>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="collapse" id="collapseExample">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Customer:</label>
                                    <select class="form-control selectBox" id="customerFilter">
                                        <option value="">Select Customer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group local-forms">
                                    <label>User:</label>
                                    <select class="form-control selectBox" id="userFilter">
                                        <option value="">Select User</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Location:</label>
                                    <select class="form-control selectBox" id="locationFilter">
                                        <option value="">Select Location</option>
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
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="datatable table table-stripped" style="width:100%" id="salesTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>User</th>
                                            <th>Date</th>
                                            <th>Total Qty</th>
                                            <th>Free Qty</th>
                                            <th>Sub Total</th>
                                            <th>Bill Discount</th>
                                            <th>Net Bill Total</th>
                                            <th>Cash</th>
                                            <th>Bank Transfer</th>
                                            <th>Cheque</th>
                                            <th>Card</th>
                                            <th>Credit</th>
                                            <th>Sales Return</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot style="border-top: 2px solid #dee2e6;">
                                        <tr>
                                            <th colspan="6" class="text-center" style="font-size: 20px;">Total</th>
                                            <th id="totalQuantity"></th>
                                            <th id="totalFreeQuantity"></th>
                                            <th id="totalSubTotal"></th>
                                            <th id="totalBillDiscount"></th>
                                            <th id="totalNetBillTotal"></th>
                                            <th id="totalCash"></th>
                                            <th id="totalBankTransfer"></th>
                                            <th id="totalCheque"></th>
                                            <th id="totalCard"></th>
                                            <th id="totalCredit"></th>
                                            <th id="totalSalesReturn"></th>

                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Old Sale Returns Section -->
            <div class="row mt-4" id="oldSaleReturnsSection" style="display: none;">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <h4 class="card-title mb-3">Returns on Previous Day Sales</h4>
                            <div class="table-responsive">
                                <table id="oldSalesReturnsTable" class="datatable table table-striped"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Invoice No</th>
                                            <th>Customer</th>
                                            <th>Location</th>
                                            <th>User</th>
                                            <th>Sale Date</th>
                                            <th>Return Date</th>
                                            <th>Return Total</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mt-3">
                <!-- Payment Summary -->
                <div class="col-md-6 mb-4" id="payment-summary-col">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                        <div class="card-header d-flex justify-content-between align-items-center py-3 px-4" style="background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                            <h6 class="mb-0 fw-semibold text-dark" style="font-size: 15px; letter-spacing: 0.3px;">Payment Summary</h6>
                            <span class="text-muted" style="font-size: 18px;"><i class="fas fa-credit-card"></i></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Bill Total</span>
                                <span id="billTotal" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Discounts</span>
                                <span id="discounts" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Cash Payments</span>
                                <span id="cashPayments" class="fw-semibold" style="font-size: 14px; color: #0d6efd;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Card Payments</span>
                                <span id="cardPayments" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Cheque Payments</span>
                                <span id="chequePayments" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Bank Transfer</span>
                                <span id="bankTransfer" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="background: #f8f9fa;">
                                <span class="fw-bold text-dark" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Payment Total</span>
                                <span id="paymentTotal" class="fw-bold" style="font-size: 15px; color: #198754;">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Overview -->
                <div class="col-md-6 mb-4" id="sales-overview-col">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 10px; overflow: hidden;">
                        <div class="card-header d-flex justify-content-between align-items-center py-3 px-4" style="background: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                            <h6 class="mb-0 fw-semibold text-dark" style="font-size: 15px; letter-spacing: 0.3px;">Sales Overview</h6>
                            <span class="text-muted" style="font-size: 18px;"><i class="fas fa-chart-line"></i></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Total Paid Quantity</span>
                                <span id="totalPaidQuantity" class="fw-semibold" style="font-size: 14px; color: #0d6efd;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Total Free Quantity</span>
                                <span id="totalFreeQty" class="fw-semibold" style="font-size: 14px; color: #198754;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Credit Total</span>
                                <span id="creditTotal" class="fw-medium" style="font-size: 14px;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="text-muted" style="font-size: 14px;">Sales Returns</span>
                                <span id="salesReturns" class="fw-medium" style="font-size: 14px; color: #dc3545;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #f0f0f0;">
                                <span class="fw-semibold text-dark" style="font-size: 14px;">Net Income</span>
                                <span id="netIncome" class="fw-bold" style="font-size: 15px; color: #198754;">0.00</span>
                            </div>
                            <div class="summary-row d-flex justify-content-between align-items-center px-4 py-3" style="background: #f8f9fa;">
                                <span class="fw-bold text-dark" style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Cash In Hand</span>
                                <span id="cashInHand" class="fw-bold" style="font-size: 15px; color: #198754;">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script>
        $(function() {
            const start = moment(); // Default start date is today
            const end = moment(); // Default end date is also today

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
        });

        document.addEventListener('DOMContentLoaded', () => {
            const customerFilter = document.getElementById('customerFilter');
            const userFilter = document.getElementById('userFilter');
            const locationFilter = document.getElementById('locationFilter');
            let allSales = [];
            let allSalesReturns = [];
            let allOldSaleReturns = [];
            let allSummaries = {};
            let filterTimeout;

            const debounceFilter = () => {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(fetchAllSalesData, 300);
            };

            fetchAllSalesData();

            async function fetchAllSalesData() {
                try {
                    const dateRange = $('#reportrange span').text().split(' - ');
                    let startDate = null,
                        endDate = null;
                    if (dateRange.length === 2) {
                        startDate = moment(dateRange[0], 'MMMM D, YYYY').format('YYYY-MM-DD');
                        endDate = moment(dateRange[1], 'MMMM D, YYYY').format('YYYY-MM-DD');
                    }
                    const params = new URLSearchParams();
                    if (startDate) params.append('start_date', startDate);
                    if (endDate) params.append('end_date', endDate);
                    if (customerFilter?.value) params.append('customer_id', customerFilter.value);
                    if (userFilter?.value) params.append('user_id', userFilter.value);
                    if (locationFilter?.value) params.append('location_id', locationFilter.value);

                    const response = await fetch(`/daily-sales-report?${params.toString()}`);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    const data = await response.json();

                    // Only keep sales with status 'final'
                    allSales = (data.sales || []).filter(sale => sale.status === 'final');
                    allSalesReturns = data.todaySalesReturns || [];
                    allOldSaleReturns = Array.isArray(data.oldSaleReturns) ? data.oldSaleReturns : [];

                    allSummaries = data.summaries || {};

                    // Combine today and previous day returns for the summary
                    let oldReturnsTotal = 0;
                    if (Array.isArray(allOldSaleReturns)) {
                        oldReturnsTotal = allOldSaleReturns.reduce((sum, r) => {
                            return sum + parseFloat(r.return_total || 0);
                        }, 0);
                    }

                    allSummaries.salesReturns = (parseFloat(allSummaries.salesReturns || 0) + oldReturnsTotal);

                    populateDropdowns(allSales);
                    populateTable(allSales, allSalesReturns);
                    populateOldSaleReturnsTable(allOldSaleReturns);
                    updateSummaries(allSummaries);

                } catch (error) {
                    console.error('Error fetching sales data:', error);
                    populateDropdowns([]);
                    populateTable([], []);
                    populateOldSaleReturnsTable([]);
                    updateSummaries({});
                }
            }

            function populateDropdowns(sales) {
                const customerMap = new Map();
                const userMap = new Map();
                const locationMap = new Map();

                sales.forEach(sale => {
                    if (sale.customer) {
                        const name = `${sale.customer.first_name} ${sale.customer.last_name}`.trim();
                        customerMap.set(sale.customer.id, name);
                    }
                    if (sale.user) {
                        userMap.set(sale.user.id, sale.user.user_name || sale.user.full_name ||
                            'Unknown User');
                    }
                    if (sale.location) {
                        locationMap.set(sale.location.id, sale.location.name);
                    }
                });

                populateSelect(customerFilter, customerMap, 'Customer');
                populateSelect(userFilter, userMap, 'User');
                populateSelect(locationFilter, locationMap, 'Location');
            }

            function populateSelect(selectEl, map, label) {
                if (!selectEl) return;

                const previouslySelected = selectEl.value; // Save previous selection

                selectEl.innerHTML = `<option value="">Select ${label}</option>`;
                map.forEach((name, id) => {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = name;
                    selectEl.appendChild(option);
                });

                selectEl.value = previouslySelected; // Restore previous selection
            }
            $('#reportrange').on('apply.daterangepicker', fetchAllSalesData);
            $(customerFilter).on('change', () => {
                fetchAllSalesData();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            $(userFilter).on('change', () => {
                fetchAllSalesData();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            $(locationFilter).on('change', () => {
                fetchAllSalesData();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            function formatNumber(num) {
                return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function populateTable(sales, salesReturns) {
                const table = $('#salesTable').DataTable({
                    lengthMenu: [
                        [10, 20, 50, -1],
                        [10, 20, 50, "All"]
                    ],
                    destroy: true,
                    deferRender: true,
                    pageLength: 10,
                    dom: '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',
                    buttons: [{
                            extend: 'pdfHtml5',
                            text: '<i class="fa fa-file-pdf"></i> PDF',
                            orientation: 'landscape',
                            pageSize: 'A4',
                            filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0,
                                10),
                            exportOptions: {
                                columns: 'thead th', // Export all columns including hidden ones
                                stripHtml: false,
                                footer: true
                            },
                            customize: function(doc) {
                                // Reduce font size for headers and body
                                doc.styles.tableHeader.fontSize = 8;
                                doc.styles.tableBodyEven.fontSize = 7;
                                doc.styles.tableBodyOdd.fontSize = 7;

                                // Auto-adjust column widths
                                const colCount = doc.content[1].table.body[0].length;
                                doc.content[1].table.widths = Array(colCount).fill(
                                    '*'); // Distribute width evenly

                                // Add summary cards below main table
                                const paymentRowsPdf = [];
                                const overviewRowsPdf = [];

                                // Collect from Payment Summary card
                                $('#payment-summary-col .summary-row').each(function() {
                                    const spans = $(this).find('span');
                                    paymentRowsPdf.push([
                                        { text: spans.eq(0).text().trim(), fontSize: 8 },
                                        { text: spans.eq(1).text().trim(), fontSize: 8, alignment: 'right', bold: true }
                                    ]);
                                });

                                // Collect from Sales Overview card
                                $('#sales-overview-col .summary-row').each(function() {
                                    const spans = $(this).find('span');
                                    overviewRowsPdf.push([
                                        { text: spans.eq(0).text().trim(), fontSize: 8 },
                                        { text: spans.eq(1).text().trim(), fontSize: 8, alignment: 'right', bold: true }
                                    ]);
                                });

                                doc.content.push({
                                    margin: [0, 20, 0, 0],
                                    columns: [
                                        {
                                            width: '*',
                                            table: {
                                                headerRows: 1,
                                                widths: ['*', '*'],
                                                body: [
                                                    [{ text: 'Payment Summary', style: 'tableHeader', colSpan: 2, alignment: 'center', fontSize: 9 }, {}],
                                                    ...paymentRowsPdf
                                                ]
                                            }
                                        },
                                        { width: 10, text: '' },
                                        {
                                            width: '*',
                                            table: {
                                                headerRows: 1,
                                                widths: ['*', '*'],
                                                body: [
                                                    [{ text: 'Sales Overview', style: 'tableHeader', colSpan: 2, alignment: 'center', fontSize: 9 }, {}],
                                                    ...overviewRowsPdf
                                                ]
                                            }
                                        }
                                    ]
                                });

                                // Set page margins and alignment
                                doc.pageMargins = [10, 10, 10, 10];
                                doc.defaultStyle.alignment = 'center';
                                doc.styles.tableHeader.alignment = 'center';
                            }
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0,
                                10),
                            exportOptions: {
                                columns: ':visible',
                                footer: true // This ensures the tfoot is included in the Excel export
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa fa-print"></i> Print',
                            title: function() {
                                const locationSelect = document.getElementById('locationFilter');
                                const selectedOption = locationSelect && locationSelect.selectedIndex >= 0
                                    ? locationSelect.options[locationSelect.selectedIndex]
                                    : null;

                                // If a specific location is selected in the filter, use that
                                if (selectedOption && selectedOption.value) {
                                    return selectedOption.text + ' - Daily Sales Report';
                                }

                                // Otherwise, collect unique location names directly from allSales JSON data
                                const locationSet = new Set();
                                allSales.forEach(function(sale) {
                                    if (sale.location && sale.location.name) {
                                        locationSet.add(sale.location.name.trim());
                                    }
                                });
                                const locations = Array.from(locationSet);
                                if (locations.length === 1) {
                                    return locations[0] + ' - Daily Sales Report';
                                } else if (locations.length > 1) {
                                    return locations.join(', ') + ' - Daily Sales Report';
                                }
                                return 'Daily Sales Report';
                            },
                            exportOptions: {
                                columns: ':visible',
                                format: {
                                    body: function(data, row, column, node) {
                                        return data;
                                    },
                                    header: function(data, column, node) {
                                        return data;
                                    }
                                }
                            },
                            customize: function(win) {
                                // Remove any duplicate tables/pages
                                $(win.document.body).find('table').not(':first').remove();

                                // Manually ensure the footer is included
                                const footer = $('#salesTable tfoot').clone();
                                $(win.document.body).find('table').append(footer);

                                // Style the h1 title (dynamically set by `title` function above) instead of removing it
                                $(win.document.body).find('h1').css({
                                    'text-align': 'center',
                                    'font-size': '20px',
                                    'font-weight': 'bold',
                                    'margin-bottom': '16px',
                                    'letter-spacing': '0.5px'
                                });

                                // Styling adjustments for the main table
                                $(win.document.body).find('table').addClass('table table-bordered');

                                // Collect summary rows from both summary cards (.summary-row divs)
                                const paymentRows = [];
                                const overviewRows = [];

                                // Payment Summary card
                                $('#payment-summary-col .summary-row').each(function() {
                                    const spans = $(this).find('span');
                                    paymentRows.push([
                                        spans.eq(0).text().trim(),
                                        spans.eq(1).text().trim()
                                    ]);
                                });

                                // Sales Overview card
                                $('#sales-overview-col .summary-row').each(function() {
                                    const spans = $(this).find('span');
                                    overviewRows.push([
                                        spans.eq(0).text().trim(),
                                        spans.eq(1).text().trim()
                                    ]);
                                });

                                function buildSummaryTable(title, rows) {
                                    const table = $(`
                                        <table style="width:100%;border-collapse:collapse;margin-bottom:0;">
                                            <thead>
                                                <tr><th colspan="2" style="text-align:center;font-size:15px;padding:8px;background:#f8f9fa;border:1px solid #ddd;">${title}</th></tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    `);
                                    rows.forEach(row => {
                                        table.find('tbody').append(
                                            $('<tr></tr>').append(
                                                $('<td></td>').text(row[0]).css({'padding':'6px 10px','border':'1px solid #ddd','font-size':'13px'})
                                            ).append(
                                                $('<td></td>').text(row[1]).css({'padding':'6px 10px','border':'1px solid #ddd','font-size':'13px','text-align':'right','font-weight':'bold'})
                                            )
                                        );
                                    });
                                    return table;
                                }

                                // Two-column summary wrapper
                                const summaryWrapper = $(`
                                    <div style="display:flex;gap:20px;margin-top:30px;width:100%;">
                                        <div style="flex:1;" id="print-payment-summary"></div>
                                        <div style="flex:1;" id="print-overview-summary"></div>
                                    </div>
                                `);
                                summaryWrapper.find('#print-payment-summary').append(buildSummaryTable('Payment Summary', paymentRows));
                                summaryWrapper.find('#print-overview-summary').append(buildSummaryTable('Sales Overview', overviewRows));

                                $(win.document.body).append(summaryWrapper);

                                // General styling for the print output
                                $(win.document.body).find('table').css({
                                    'font-size': '14px',
                                    'width': '100%',
                                    'border-collapse': 'collapse'
                                });
                                $(win.document.body).find('td, th').css({
                                    'padding': '10px 12px',
                                    'font-size': '14px'
                                });
                                $(win.document.body).find('th').css({
                                    'font-weight': 'bold',
                                    'text-align': 'center'
                                });

                                // Additional styling for the summary table
                                summaryTable.find('table').css({
                                    'width': '100%'
                                });
                                summaryTable.find('td').css({
                                    'border': '1px solid #ddd'
                                });

                                // Prevent page breaks inside tables and summary cards
                                $(win.document.body).find('table').css('page-break-inside',
                                    'avoid');
                                $(win.document.body).find('.card').css('page-break-inside',
                                    'avoid');

                                // Add custom styles for print
                                $(win.document.body).append(
                                    '<style>' +
                                    '@page { size: A4 landscape; margin: 20mm; }' +
                                    // Landscape orientation
                                    'body { font-size: 12px; }' +
                                    'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }' +
                                    'table, th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 9px; }' +
                                    // Reduce font size
                                    'tfoot { display: table-row-group; }' +
                                    // Ensure footer is visible
                                    '.card { margin-top: 20px; }' +
                                    // Add spacing between DataTable and summary cards
                                    '</style>'
                                );

                                // Adjust column widths dynamically
                                const tables = $(win.document.body).find('table');
                                tables.each(function() {
                                    const $table = $(this);
                                    const colCount = $table.find('thead th').length;
                                    const widths = new Array(colCount).fill(
                                        '*'); // Distribute column widths evenly
                                    $table.css('width', '100%');
                                    $table.find('colgroup').html(widths.map(() =>
                                        '<col style="width: auto;" />').join(''));
                                });

                                // Handle multi-page scenarios
                                $(win.document.body).find('div.page-break').css('page-break-before',
                                    'always');
                            }
                        }
                    ],
                    columns: [{
                            title: "#"
                        },
                        {
                            title: "Invoice No"
                        },
                        {
                            title: "Customer"
                        },
                        {
                            title: "Location"
                        },
                        {
                            title: "User"
                        },
                        {
                            title: "Date"
                        },
                        {
                            title: "Total Qty"
                        },
                        {
                            title: "Free Qty"
                        },
                        {
                            title: "Sub Total"
                        },
                        {
                            title: "Bill Discount"
                        },
                        {
                            title: "Net Bill Total"
                        },
                        {
                            title: "Cash"
                        },
                        {
                            title: "Bank Transfer"
                        },
                        {
                            title: "Cheque"
                        },
                        {
                            title: "Card"
                        },
                        {
                            title: "Credit"
                        },
                        {
                            title: "Sales Return"
                        }
                    ]
                });



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
                            if (!isNaN(val) || val === "show all") $(this).addClass(
                                'selected-column');
                            else $(this).removeClass('selected-column');
                        });
                    } else {
                        const column = table.column(selectedValue);
                        column.visible(!column.visible());
                        $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass(
                            'selected-column');
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

                let tableIndex = 0;
                // Ensure salesReturns is always an array
                const safeSalesReturns = Array.isArray(salesReturns) ? salesReturns : [];
                const tableData = sales.map(sale => {
                    let cash = 0,
                        bankTransfer = 0,
                        cheque = 0,
                        card = 0;
                    sale.payments.forEach(payment => {
                        switch (payment.payment_method) {
                            case 'cash':
                                cash += parseFloat(payment.amount);
                                break;
                            case 'bank_transfer':
                                bankTransfer += parseFloat(payment.amount);
                                break;
                            case 'cheque':
                                cheque += parseFloat(payment.amount);
                                break;
                            case 'card':
                                card += parseFloat(payment.amount);
                                break;
                        }
                    });

                    // Calculate total quantity and free quantity for this sale
                    const totalPaidQty = sale.products.reduce((sum, p) => sum + parseFloat(p.quantity || 0), 0);
                    const totalFreeQty = sale.products.reduce((sum, p) => sum + parseFloat(p.free_quantity || 0), 0);

                    const saleReturn = safeSalesReturns.find(r => r.sale_id === sale.id);
                    const salesReturnAmount = saleReturn ? parseFloat(saleReturn.return_total) : 0;

                    return [
                        ++tableIndex,
                        sale.invoice_no,
                        `${sale.customer?.first_name || ''} ${sale.customer?.last_name || ''}`.trim(),
                        sale.location?.name || '',
                        sale.user?.user_name || '',
                        new Date(sale.sales_date).toLocaleString(),
                        totalPaidQty.toFixed(2),
                        totalFreeQty.toFixed(2),
                        formatNumber(parseFloat(sale.subtotal)),
                        formatNumber(parseFloat(sale.discount_amount || 0)),
                        formatNumber(parseFloat(sale.final_total)),
                        formatNumber(cash),
                        formatNumber(bankTransfer),
                        formatNumber(cheque),
                        formatNumber(card),
                        formatNumber(parseFloat(sale.total_due)),
                        formatNumber(salesReturnAmount)
                    ];
                });

                table.clear().rows.add(tableData).draw();

                if (tableData.length > 0) {
                    const totals = tableData.reduce((acc, row) => ({
                        totalQty: acc.totalQty + parseFloat(row[6]),
                        freeQty: acc.freeQty + parseFloat(row[7]),
                        subTotal: acc.subTotal + parseFloat(row[8].replace(/,/g, '')),
                        billDiscount: acc.billDiscount + parseFloat(row[9].replace(/,/g, '')),
                        netBillTotal: acc.netBillTotal + parseFloat(row[10].replace(/,/g, '')),
                        cash: acc.cash + parseFloat(row[11].replace(/,/g, '')),
                        bankTransfer: acc.bankTransfer + parseFloat(row[12].replace(/,/g, '')),
                        cheque: acc.cheque + parseFloat(row[13].replace(/,/g, '')),
                        card: acc.card + parseFloat(row[14].replace(/,/g, '')),
                        credit: acc.credit + parseFloat(row[15].replace(/,/g, '')),
                        salesReturn: acc.salesReturn + parseFloat(row[16].replace(/,/g, '')),
                    }), {
                        totalQty: 0,
                        freeQty: 0,
                        subTotal: 0,
                        billDiscount: 0,
                        netBillTotal: 0,
                        cash: 0,
                        bankTransfer: 0,
                        cheque: 0,
                        card: 0,
                        credit: 0,
                        salesReturn: 0
                    });

                    $('#totalQuantity').text(totals.totalQty.toFixed(2));
                    $('#totalFreeQuantity').text(totals.freeQty.toFixed(2));
                    $('#totalSubTotal').text(formatNumber(totals.subTotal));
                    $('#totalBillDiscount').text(formatNumber(totals.billDiscount));
                    $('#totalNetBillTotal').text(formatNumber(totals.netBillTotal));
                    $('#totalCash').text(formatNumber(totals.cash));
                    $('#totalBankTransfer').text(formatNumber(totals.bankTransfer));
                    $('#totalCheque').text(formatNumber(totals.cheque));
                    $('#totalCard').text(formatNumber(totals.card));
                    $('#totalCredit').text(formatNumber(totals.credit));
                    $('#totalSalesReturn').text(formatNumber(totals.salesReturn));
                } else {
                    $('#salesTable tbody').html(
                        '<tr><td colspan="17" class="text-center">No records found</td></tr>');
                    $('tfoot th:not(:first-child)').text('0.00');
                }
            }

            function populateOldSaleReturnsTable(oldReturns) {
                // Show/Hide section based on data
                const section = document.getElementById('oldSaleReturnsSection');
                if (!oldReturns || oldReturns.length === 0) {
                    // Hide section if no previous day returns
                    section.style.display = "none";
                    if ($.fn.DataTable.isDataTable('#oldSalesReturnsTable')) {
                        $('#oldSalesReturnsTable').DataTable().clear().draw();
                    }
                    // Optionally clear the table body for fallback
                    $('#oldSalesReturnsTable tbody').html(
                        '<tr><td colspan="8" class="text-center">No previous day returns found</td></tr>');
                    return;
                } else {
                    // Show section if data exists
                    section.style.display = "";
                }
                if ($.fn.DataTable.isDataTable('#oldSalesReturnsTable')) {
                    $('#oldSalesReturnsTable').DataTable().clear().destroy();
                }
                const table = $('#oldSalesReturnsTable').DataTable({
                    destroy: true,
                    pageLength: 10,
                    data: oldReturns.map((r, i) => [
                        i + 1,
                        r.sale?.invoice_no || 'N/A',
                        `${r.customer?.first_name || ''} ${r.customer?.last_name || ''}`.trim(),
                        r.location?.name || '',
                        r.sale?.user?.user_name || '',
                        r.sale?.sales_date ? new Date(r.sale.sales_date).toLocaleDateString() : '',
                        r.return_date ? new Date(r.return_date).toLocaleDateString() : '',
                        formatNumber(parseFloat(r.return_total))
                    ]),
                    columns: [{
                            title: "#"
                        },
                        {
                            title: "Invoice No"
                        },
                        {
                            title: "Customer"
                        },
                        {
                            title: "Location"
                        },
                        {
                            title: "User"
                        },
                        {
                            title: "Sale Date"
                        },
                        {
                            title: "Return Date"
                        },
                        {
                            title: "Return Total"
                        }
                    ]
                });
            }


            function updateSummaries(summaries) {
                $('#billTotal').text(formatNumber(parseFloat(summaries.billTotal || 0)));
                $('#discounts').text(formatNumber(parseFloat(summaries.discounts || 0)));
                $('#cashPayments').text(formatNumber(parseFloat(summaries.cashPayments || 0)));
                $('#cardPayments').text(formatNumber(parseFloat(summaries.cardPayments || 0)));
                $('#chequePayments').text(formatNumber(parseFloat(summaries.chequePayments || 0)));
                $('#bankTransfer').text(formatNumber(parseFloat(summaries.bankTransfer || 0)));
                $('#paymentTotal').text(formatNumber(parseFloat(summaries.paymentTotal || 0)));
                $('#creditTotal').text(formatNumber(parseFloat(summaries.creditTotal || 0)));
                $('#salesReturns').text(formatNumber(parseFloat(summaries.salesReturns || 0)));
                $('#netIncome').text(formatNumber(parseFloat(summaries.netIncome || 0)));
                $('#cashInHand').text(formatNumber(parseFloat(summaries.cashInHand || 0)));
                $('#totalPaidQuantity').text(parseFloat(summaries.totalPaidQuantity || 0).toFixed(2));
                $('#totalFreeQty').text(parseFloat(summaries.totalFreeQuantity || 0).toFixed(2));
            }



        });
    </script>

@endsection
