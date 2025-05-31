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
                padding: 10px 12px;
                font-size: 14px !important;
            }

            .dt-buttons {
                display: none;
            }
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
                                    <select class="form-control select" id="customerFilter">
                                        <option value="">Select Customer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group local-forms">
                                    <label>User:</label>
                                    <select class="form-control select" id="userFilter">
                                        <option value="">Select User</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Location:</label>
                                    <select class="form-control select" id="locationFilter">
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

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card card-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Bill Total</td>
                                    <td id="billTotal">0.00</td>
                                </tr>
                                <tr>
                                    <td>Discounts</td>
                                    <td id="discounts">0.00</td>
                                </tr>
                                <tr>
                                    <td>Cash Payments</td>
                                    <td id="cashPayments">0.00</td>
                                </tr>
                                <tr>
                                    <td>Card Payments</td>
                                    <td id="cardPayments">0.00</td>
                                </tr>
                                <tr>
                                    <td>Cheque Payments</td>
                                    <td id="chequePayments">0.00</td>
                                </tr>
                                <tr>
                                    <td>Bank Transfer</td>
                                    <td id="bankTransfer">0.00</td>
                                </tr>
                                <tr>
                                    <td>Payment Total</td>
                                    <td id="paymentTotal">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Credit Total</td>
                                    <td id="creditTotal">0.00</td>
                                </tr>
                                <tr>
                                    <td>Sales Returns</td>
                                    <td id="salesReturns">0.00</td>
                                </tr>
                                <tr>
                                    <td>Net Income</td>
                                    <td id="netIncome">0.00</td>
                                </tr>
                                <tr>
                                    <td>Cash in Hand</td>
                                    <td id="cashInHand">0.00</td>
                                </tr>
                            </tbody>
                        </table>
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
                    allSales = data.sales || [];
                    allSalesReturns = data.salesReturns || [];
                    allSummaries = data.summaries || {};

                    populateDropdowns(allSales);
                    populateTable(allSales, allSalesReturns);
                    updateSummaries(allSummaries);
                } catch (error) {
                    console.error('Error fetching sales data:', error);
                    populateDropdowns([]);
                    populateTable([], []);
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
                        userMap.set(sale.user.id, sale.user.full_name);
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
                                const summaryRows = [];
                                $('.card-body .table-bordered tbody tr').each(function() {
                                    const cells = [];
                                    $(this).find('td').each(function() {
                                        cells.push($(this).text());
                                    });
                                    summaryRows.push(cells);
                                });

                                doc.content.push({
                                    table: {
                                        headerRows: 1,
                                        widths: ['*', '*'],
                                        body: [
                                            [{
                                                    text: 'Summary Report',
                                                    style: 'tableHeader',
                                                    colSpan: 2,
                                                    alignment: 'center'
                                                },
                                                {}
                                            ],
                                            ...summaryRows
                                        ]
                                    },
                                    margin: [0, 20, 0, 0]
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
                            title: 'ARB Fashion Daily Sales Report',
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

                                // Styling adjustments for the main table
                                $(win.document.body).find('table').addClass('table table-bordered');
                                $(win.document.body).find('h1').remove();

                                // Summaries
                                const summaryRows = [];
                                $('.card-body .table-bordered tbody tr').each(function() {
                                    const cells = [];
                                    $(this).find('td').each(function() {
                                        cells.push($(this).text());
                                    });
                                    summaryRows.push(cells);
                                });

                                // Create summary table with proper alignment and smaller width
                                const summaryTable = $(`
                                                <div style="width: 60%; margin-top: 30px; margin-left: auto; margin-right: auto;">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th colspan="2" style="text-align: center; font-size: 20px;">Summary Report</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            `);
                                // Populate summary table with rows
                                summaryRows.forEach(row => {
                                    const tr = $('<tr></tr>');
                                    tr.append(
                                        $('<td></td>').text(row[0]).css({
                                            'text-align': 'left',
                                            'font-size': '16px',
                                            'font-weight': 'bold'
                                        })
                                    );
                                    tr.append(
                                        $('<td></td>').text(row[1]).css({
                                            'text-align': 'right',
                                            'font-size': '16px',
                                            'font-weight': 'bold'
                                        })
                                    );
                                    summaryTable.find('tbody').append(tr);
                                });

                                // Append the summary table to the document
                                $(win.document.body).append(summaryTable);

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
                                $(win.document.body).find('h1').css({
                                    'text-align': 'center',
                                    'font-size': '24px',
                                    'margin-bottom': '20px'
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

                    const saleReturn = salesReturns.find(r => r.sale_id === sale.id);
                    const salesReturnAmount = saleReturn ? parseFloat(saleReturn.return_total) : 0;

                    return [
                        ++tableIndex,
                        sale.invoice_no,
                        `${sale.customer?.first_name || ''} ${sale.customer?.last_name || ''}`.trim(),
                        sale.location?.name || '',
                        sale.user?.full_name || '',
                        new Date(sale.sales_date).toLocaleString(),
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
                        subTotal: acc.subTotal + parseFloat(row[6].replace(/,/g, '')),
                        billDiscount: acc.billDiscount + parseFloat(row[7].replace(/,/g, '')),
                        netBillTotal: acc.netBillTotal + parseFloat(row[8].replace(/,/g, '')),
                        cash: acc.cash + parseFloat(row[9].replace(/,/g, '')),
                        bankTransfer: acc.bankTransfer + parseFloat(row[10].replace(/,/g, '')),
                        cheque: acc.cheque + parseFloat(row[11].replace(/,/g, '')),
                        card: acc.card + parseFloat(row[12].replace(/,/g, '')),
                        credit: acc.credit + parseFloat(row[13].replace(/,/g, '')),
                        salesReturn: acc.salesReturn + parseFloat(row[14].replace(/,/g, '')),
                    }), {
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
                        '<tr><td colspan="15" class="text-center">No records found</td></tr>');
                    $('tfoot th:not(:first-child)').text('0.00');
                }
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
            }
        });
    </script>

@endsection
