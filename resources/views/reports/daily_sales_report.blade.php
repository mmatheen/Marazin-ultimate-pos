@extends('layout.layout')
@section('title', 'Daily Sales Report')
@section('content')
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

        {{-- <script>
            $(function() {
                // Initialize date range picker
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
            });

            document.addEventListener('DOMContentLoaded', () => {
                const customerFilter = document.getElementById('customerFilter');
                const userFilter = document.getElementById('userFilter');
                const locationFilter = document.getElementById('locationFilter');

                let allSales = [];
                let allSalesReturns = [];
                let allSummaries = {};
                let filterTimeout;

                // Debounce function to prevent rapid firing of filters
                const debounceFilter = () => {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(filterAndRender, 300);
                };

                // Fetch all data once
                fetchAllSalesData();

                async function fetchAllSalesData() {
                    try {
                        const response = await fetch(`/daily-sales-report`);
                        if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                        const data = await response.json();
                        allSales = data.sales || [];
                        allSalesReturns = data.salesReturns || [];
                        allSummaries = data.summaries || {};
                        populateDropdowns(allSales);
                        filterAndRender();
                    } catch (error) {
                        console.error('Error fetching sales data:', error);
                        populateDropdowns([]);
                        populateTable([], []);
                        updateSummaries({});
                    }
                }

                // Populate dropdowns
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
                    selectEl.innerHTML = `<option value="">Select ${label}</option>`;
                    map.forEach((name, id) => {
                        const option = document.createElement('option');
                        option.value = id;
                        option.textContent = name;
                        selectEl.appendChild(option);
                    });
                }

                // Filtering logic with immediate response
                function filterAndRender() {
                    const customerId = customerFilter?.value || '';
                    const userId = userFilter?.value || '';
                    const locationId = locationFilter?.value || '';
                    const dateRange = $('#reportrange span').text().split(' - ');

                    let startDate = null,
                        endDate = null;
                    if (dateRange.length === 2) {
                        startDate = moment(dateRange[0], 'MMMM D, YYYY').startOf('day');
                        endDate = moment(dateRange[1], 'MMMM D, YYYY').endOf('day');
                    }

                    let filteredSales = allSales.filter(sale => {
                        // Customer filter
                        if (customerId && (!sale.customer || String(sale.customer.id) !== customerId)) {
                            return false;
                        }

                        // User filter
                        if (userId && (!sale.user || String(sale.user.id) !== userId)) {
                            return false;
                        }

                        // Location filter
                        if (locationId && (!sale.location || String(sale.location.id) !== locationId)) {
                            return false;
                        }

                        // Date range filter - only apply if dates are selected
                        if (startDate && endDate) {
                            const saleDate = moment(sale.sales_date);
                            if (!saleDate.isBetween(startDate, endDate, null, '[]')) {
                                return false;
                            }
                        }

                        return true;
                    });

                    // Filter salesReturns to only those matching filtered sales
                    const filteredSaleIds = filteredSales.map(s => s.id);
                    let filteredSalesReturns = allSalesReturns.filter(r => filteredSaleIds.includes(r.sale_id));

                    populateTable(filteredSales, filteredSalesReturns);
                    updateSummaries(calcSummaries(filteredSales, filteredSalesReturns));
                }

                // Event listeners for filters with debounce
                $('#reportrange').on('apply.daterangepicker', debounceFilter);
                $(customerFilter).on('change', debounceFilter);
                $(userFilter).on('change', debounceFilter);
                $(locationFilter).on('change', debounceFilter);

                // Table rendering with optimized DataTable handling
                function populateTable(sales, salesReturns) {
                    let table = $('#salesTable').DataTable();

                    // If DataTable already exists, just update the data
                    if ($.fn.DataTable.isDataTable('#salesTable')) {
                        table.clear();
                    } else {
                        // Initialize new DataTable if it doesn't exist
                        table = $('#salesTable').DataTable({
                            lengthMenu: [
                                [10, 20, 50, -1],
                                [10, 20, 50, "All"]
                            ],
                            pageLength: 10,
                            dom: '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',
                            buttons: [{
                                    extend: 'pdfHtml5',
                                    text: '<i class="fa fa-file-pdf"></i> PDF'
                                },
                                {
                                    extend: 'excelHtml5',
                                    text: '<i class="fa fa-file-excel"></i> Excel'
                                },
                                {
                                    extend: 'print',
                                    text: '<i class="fa fa-print"></i> Print'
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
                    }

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
                            new Date(sale.sales_date).toLocaleDateString(),
                            parseFloat(sale.subtotal).toFixed(2),
                            parseFloat(sale.discount_amount || 0).toFixed(2),
                            parseFloat(sale.final_total).toFixed(2),
                            cash.toFixed(2),
                            bankTransfer.toFixed(2),
                            cheque.toFixed(2),
                            card.toFixed(2),
                            parseFloat(sale.total_due).toFixed(2),
                            salesReturnAmount.toFixed(2),

                        ];
                    });

                    table.clear().rows.add(tableData).draw();

                    if (tableData.length > 0) {
                        const totals = tableData.reduce((acc, row) => ({
                            subTotal: acc.subTotal + parseFloat(row[6]),
                            billDiscount: acc.billDiscount + parseFloat(row[7]),
                            netBillTotal: acc.netBillTotal + parseFloat(row[8]),
                            cash: acc.cash + parseFloat(row[9]),
                            bankTransfer: acc.bankTransfer + parseFloat(row[10]),
                            cheque: acc.cheque + parseFloat(row[11]),
                            card: acc.card + parseFloat(row[12]),
                            credit: acc.credit + parseFloat(row[13]),
                            salesReturn: acc.salesReturn + parseFloat(row[14])
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

                        $('#totalSubTotal').text(totals.subTotal.toFixed(2));
                        $('#totalBillDiscount').text(totals.billDiscount.toFixed(2));
                        $('#totalNetBillTotal').text(totals.netBillTotal.toFixed(2));
                        $('#totalCash').text(totals.cash.toFixed(2));
                        $('#totalBankTransfer').text(totals.bankTransfer.toFixed(2));
                        $('#totalCheque').text(totals.cheque.toFixed(2));
                        $('#totalCard').text(totals.card.toFixed(2));
                        $('#totalCredit').text(totals.credit.toFixed(2));
                        $('#totalSalesReturn').text(totals.salesReturn.toFixed(2));
                    } else {


                        $('#salesTable tbody').html(
                            '<tr><td colspan="15" class="text-center">No records found</td></tr>');
                        $('tfoot th:not(:first-child)').text('0.00');
                    }

                    //  Column visibility dropdown DataTable code start

                    function updateDropdownHighlights() {
                        $('#columnVisibilityDropdown a').each(function() {
                            const value = $(this).data('value');

                            if (value === "hide all") {
                                $(this).removeClass('selected-column');
                            } else if (value === "show all") {
                                // Highlight only if all columns are visible
                                let allVisible = true;
                                table.columns().every(function() {
                                    if (!this.visible()) {
                                        allVisible = false;
                                    }
                                });
                                if (allVisible) {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
                            } else if (!isNaN(value)) {
                                if (table.column(value).visible()) {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
                            }
                        });
                    }

                    $('#columnVisibilityDropdown a').on('click', function(e) {
                        e.preventDefault();
                        const selectedValue = $(this).data('value');

                        if (selectedValue === "hide all") {
                            table.columns().visible(false);

                            // Remove all highlights
                            $('#columnVisibilityDropdown a').removeClass('selected-column');

                            // Highlight only "Hide All"
                            $(this).addClass('selected-column');
                        } else if (selectedValue === "show all") {
                            table.columns().visible(true);

                            // Highlight all column items and also "Show All"
                            $('#columnVisibilityDropdown a').each(function() {
                                const val = $(this).data('value');
                                if (!isNaN(val) || val === "show all") {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
                            });
                        } else {
                            const column = table.column(selectedValue);
                            column.visible(!column.visible());

                            // Always remove highlight from hide all
                            $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass(
                                'selected-column');

                            // Toggle selected column's highlight
                            if (column.visible()) {
                                $(this).addClass('selected-column');
                            } else {
                                $(this).removeClass('selected-column');
                            }

                            // Re-check and update "Show All" highlight if all are now visible
                            updateDropdownHighlights();
                        }
                    });

                    // Prevent dropdown from closing
                    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function(item) {
                        item.addEventListener('click', function(e) {
                            e.stopPropagation();
                        });
                    });

                    // On page load — show all columns and highlight all column items and "Show All"
                    $(document).ready(function() {
                        table.columns().visible(true);
                        $('#columnVisibilityDropdown a').each(function() {
                            const value = $(this).data('value');
                            if (!isNaN(value) || value === "show all") {
                                $(this).addClass('selected-column');
                            }
                        });
                    });
                }

                //  Column visibility dropdown DataTable code end


                // Calculate summaries based on filtered data
                function calcSummaries(sales, salesReturns) {
                    let billTotal = 0,
                        discounts = 0,
                        cashPayments = 0,
                        cardPayments = 0,
                        chequePayments = 0,
                        bankTransfer = 0,
                        paymentTotal = 0,
                        creditTotal = 0,
                        salesReturnsTotal = 0,
                        netIncome = 0,
                        cashInHand = 0;

                    sales.forEach(sale => {
                        billTotal += parseFloat(sale.final_total || 0);
                        discounts += parseFloat(sale.discount_amount || 0);

                        sale.payments.forEach(payment => {
                            switch (payment.payment_method) {
                                case 'cash':
                                    cashPayments += parseFloat(payment.amount);
                                    break;
                                case 'card':
                                    cardPayments += parseFloat(payment.amount);
                                    break;
                                case 'cheque':
                                    chequePayments += parseFloat(payment.amount);
                                    break;
                                case 'bank_transfer':
                                    bankTransfer += parseFloat(payment.amount);
                                    break;
                            }
                            paymentTotal += parseFloat(payment.amount);
                        });

                        creditTotal += parseFloat(sale.total_due || 0);
                    });

                    salesReturns.forEach(r => {
                        salesReturnsTotal += parseFloat(r.return_total || 0);
                    });

                    netIncome = billTotal - discounts - salesReturnsTotal;
                    cashInHand = cashPayments - salesReturnsTotal;

                    return {
                        billTotal,
                        discounts,
                        cashPayments,
                        cardPayments,
                        chequePayments,
                        bankTransfer,
                        paymentTotal,
                        creditTotal,
                        salesReturns: salesReturnsTotal,
                        netIncome,
                        cashInHand
                    };
                }

                function updateSummaries(summaries) {
                    $('#billTotal').text(parseFloat(summaries.billTotal || 0).toFixed(2));
                    $('#discounts').text(parseFloat(summaries.discounts || 0).toFixed(2));
                    $('#cashPayments').text(parseFloat(summaries.cashPayments || 0).toFixed(2));
                    $('#cardPayments').text(parseFloat(summaries.cardPayments || 0).toFixed(2));
                    $('#chequePayments').text(parseFloat(summaries.chequePayments || 0).toFixed(2));
                    $('#bankTransfer').text(parseFloat(summaries.bankTransfer || 0).toFixed(2));
                    $('#paymentTotal').text(parseFloat(summaries.paymentTotal || 0).toFixed(2));
                    $('#creditTotal').text(parseFloat(summaries.creditTotal || 0).toFixed(2));
                    $('#salesReturns').text(parseFloat(summaries.salesReturns || 0).toFixed(2));
                    $('#netIncome').text(parseFloat(summaries.netIncome || 0).toFixed(2));
                    $('#cashInHand').text(parseFloat(summaries.cashInHand || 0).toFixed(2));
                }
            });
        </script> --}}
        <script>
            $(function() {
                // Initialize date range picker
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
            });

            document.addEventListener('DOMContentLoaded', () => {
                const customerFilter = document.getElementById('customerFilter');
                const userFilter = document.getElementById('userFilter');
                const locationFilter = document.getElementById('locationFilter');

                let allSales = [];
                let allSalesReturns = [];
                let allSummaries = {};
                let filterTimeout;

                // Debounce function to prevent rapid firing of filters
                const debounceFilter = () => {
                    clearTimeout(filterTimeout);
                    filterTimeout = setTimeout(filterAndRender, 300);
                };

                // Fetch all data once
                fetchAllSalesData();

                async function fetchAllSalesData() {
                    try {
                        // Get date range from picker
                        const dateRange = $('#reportrange span').text().split(' - ');
                        let startDate = null,
                            endDate = null;
                        if (dateRange.length === 2) {
                            startDate = moment(dateRange[0], 'MMMM D, YYYY').format('YYYY-MM-DD');
                            endDate = moment(dateRange[1], 'MMMM D, YYYY').format('YYYY-MM-DD');
                        }

                        // Build query params
                        const params = new URLSearchParams();
                        if (startDate) params.append('start_date', startDate);
                        if (endDate) params.append('end_date', endDate);

                        const customerId = customerFilter?.value;
                        if (customerId) params.append('customer_id', customerId);

                        const userId = userFilter?.value;
                        if (userId) params.append('user_id', userId);

                        const locationId = locationFilter?.value;
                        if (locationId) params.append('location_id', locationId);

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

                // Populate dropdowns
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
                    selectEl.innerHTML = `<option value="">Select ${label}</option>`;
                    map.forEach((name, id) => {
                        const option = document.createElement('option');
                        option.value = id;
                        option.textContent = name;
                        selectEl.appendChild(option);
                    });
                }

                // Event listeners for filters
                $('#reportrange').on('apply.daterangepicker', fetchAllSalesData);
                $(customerFilter).on('change', fetchAllSalesData);
                $(userFilter).on('change', fetchAllSalesData);
                $(locationFilter).on('change', fetchAllSalesData);

                // Table rendering with DataTable
                function populateTable(sales, salesReturns) {
                    let table = $('#salesTable').DataTable();

                    // If DataTable already exists, just update the data
                    if ($.fn.DataTable.isDataTable('#salesTable')) {
                        table.clear();
                    } else {
                        // Initialize new DataTable if it doesn't exist
                        table = $('#salesTable').DataTable({
                            lengthMenu: [
                                [10, 20, 50, -1],
                                [10, 20, 50, "All"]
                            ],
                            pageLength: 10,
                            dom: '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',
                            buttons: [{
                                    extend: 'pdfHtml5',
                                    text: '<i class="fa fa-file-pdf"></i> PDF'
                                },
                                {
                                    extend: 'excelHtml5',
                                    text: '<i class="fa fa-file-excel"></i> Excel'
                                },
                                {
                                    extend: 'print',
                                    text: '<i class="fa fa-print"></i> Print'
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
                    }

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
                            new Date(sale.sales_date).toLocaleDateString(),
                            parseFloat(sale.subtotal).toFixed(2),
                            parseFloat(sale.discount_amount || 0).toFixed(2),
                            parseFloat(sale.final_total).toFixed(2),
                            cash.toFixed(2),
                            bankTransfer.toFixed(2),
                            cheque.toFixed(2),
                            card.toFixed(2),
                            parseFloat(sale.total_due).toFixed(2),
                            salesReturnAmount.toFixed(2),
                        ];
                    });

                    table.clear().rows.add(tableData).draw();

                    if (tableData.length > 0) {
                        const totals = tableData.reduce((acc, row) => ({
                            subTotal: acc.subTotal + parseFloat(row[6]),
                            billDiscount: acc.billDiscount + parseFloat(row[7]),
                            netBillTotal: acc.netBillTotal + parseFloat(row[8]),
                            cash: acc.cash + parseFloat(row[9]),
                            bankTransfer: acc.bankTransfer + parseFloat(row[10]),
                            cheque: acc.cheque + parseFloat(row[11]),
                            card: acc.card + parseFloat(row[12]),
                            credit: acc.credit + parseFloat(row[13]),
                            salesReturn: acc.salesReturn + parseFloat(row[14])
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

                        $('#totalSubTotal').text(totals.subTotal.toFixed(2));
                        $('#totalBillDiscount').text(totals.billDiscount.toFixed(2));
                        $('#totalNetBillTotal').text(totals.netBillTotal.toFixed(2));
                        $('#totalCash').text(totals.cash.toFixed(2));
                        $('#totalBankTransfer').text(totals.bankTransfer.toFixed(2));
                        $('#totalCheque').text(totals.cheque.toFixed(2));
                        $('#totalCard').text(totals.card.toFixed(2));
                        $('#totalCredit').text(totals.credit.toFixed(2));
                        $('#totalSalesReturn').text(totals.salesReturn.toFixed(2));
                    } else {
                        $('#salesTable tbody').html(
                            '<tr><td colspan="15" class="text-center">No records found</td></tr>');
                        $('tfoot th:not(:first-child)').text('0.00');
                    }

                    // Column visibility dropdown
                    function updateDropdownHighlights() {
                        $('#columnVisibilityDropdown a').each(function() {
                            const value = $(this).data('value');

                            if (value === "hide all") {
                                $(this).removeClass('selected-column');
                            } else if (value === "show all") {
                                let allVisible = true;
                                table.columns().every(function() {
                                    if (!this.visible()) {
                                        allVisible = false;
                                    }
                                });
                                if (allVisible) {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
                            } else if (!isNaN(value)) {
                                if (table.column(value).visible()) {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
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
                                if (!isNaN(val) || val === "show all") {
                                    $(this).addClass('selected-column');
                                } else {
                                    $(this).removeClass('selected-column');
                                }
                            });
                        } else {
                            const column = table.column(selectedValue);
                            column.visible(!column.visible());
                            $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass(
                                'selected-column');
                            if (column.visible()) {
                                $(this).addClass('selected-column');
                            } else {
                                $(this).removeClass('selected-column');
                            }
                            updateDropdownHighlights();
                        }
                    });

                    // Prevent dropdown from closing
                    document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function(item) {
                        item.addEventListener('click', function(e) {
                            e.stopPropagation();
                        });
                    });

                    // On page load - show all columns and highlight all column items and "Show All"
                    $(document).ready(function() {
                        table.columns().visible(true);
                        $('#columnVisibilityDropdown a').each(function() {
                            const value = $(this).data('value');
                            if (!isNaN(value) || value === "show all") {
                                $(this).addClass('selected-column');
                            }
                        });
                    });
                }

                function updateSummaries(summaries) {
                    $('#billTotal').text(parseFloat(summaries.billTotal || 0).toFixed(2));
                    $('#discounts').text(parseFloat(summaries.discounts || 0).toFixed(2));
                    $('#cashPayments').text(parseFloat(summaries.cashPayments || 0).toFixed(2));
                    $('#cardPayments').text(parseFloat(summaries.cardPayments || 0).toFixed(2));
                    $('#chequePayments').text(parseFloat(summaries.chequePayments || 0).toFixed(2));
                    $('#bankTransfer').text(parseFloat(summaries.bankTransfer || 0).toFixed(2));
                    $('#paymentTotal').text(parseFloat(summaries.paymentTotal || 0).toFixed(2));
                    $('#creditTotal').text(parseFloat(summaries.creditTotal || 0).toFixed(2));
                    $('#salesReturns').text(parseFloat(summaries.salesReturns || 0).toFixed(2));
                    $('#netIncome').text(parseFloat(summaries.netIncome || 0).toFixed(2));
                    $('#cashInHand').text(parseFloat(summaries.cashInHand || 0).toFixed(2));
                }
            });
        </script>
    @endsection
