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
            <div>
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
                            <ul class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" id="columnVisibilityDropdown" style="width: 400px;">
                                <div class="row">
                             
                                    <div class="col-md-6">
                                        <li><a class="dropdown-item" href="#" data-value="hide all">1. Hide All Columns</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="show all">2. Show All Columns</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="0">3. Invoice No</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="1">4. Customer</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="2">5. Date</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="3">7. Line Total</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="4">6. Line Discount</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="5">8. Sub Total</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="6">9. Bill Discount</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="7">10. Net Bill Total</a></li>
                                    </div>
                                    <div class="col-md-6">
                                        <li><a class="dropdown-item" href="#" data-value="8">11. Cash</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="9">12. Online</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="10">13. Bank Transfer</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="11">14. Cheque</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="12">15. Card</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="13">16. Return</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="14">17. Credit</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="15">18. Sales Return</a></li>
                                    </div>
                                </div>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
            <div class="collapse" id="collapseExample">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Customer:</label>
                                    <select class="form-control select" id="customerFilter">
                                        <option value="">Select Customer</option>
                                        <!-- Populate with customer options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Payment Method:</label>
                                    <select class="form-control select" id="paymentMethodFilter">
                                        <option value="">Select Payment Method</option>
                                        <!-- Populate with payment method options -->
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Table Section --}}

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
                                        <th>Date</th>
                                        <th>Line Total</th>
                                        <th>Line Discount</th>
                                        <th>Sub Total</th>
                                        <th>Bill Discount</th>
                                        <th>Net Bill Total</th>
                                        <th>Cash</th>
                                        <th>Online</th>
                                        <th>Bank Transfer</th>
                                        <th>Cheque</th>
                                        <th>Card</th>
                                        <th>Return</th>
                                        <th>Credit</th>
                                        <th>Sales Return</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>

                                <tfoot style="border-top: 2px solid #dee2e6;">
                                    <tr>
                                        <th colspan="4">Total</th>
                                        <th id="totalLineTotal"></th>
                                        <th id="totalLineDiscount"></th>
                                        <th id="totalSubTotal"></th>
                                        <th id="totalBillDiscount"></th>
                                        <th id="totalNetBillTotal"></th>
                                        <th id="totalCash"></th>
                                        <th id="totalOnline"></th>
                                        <th id="totalBankTransfer"></th>
                                        <th id="totalCheque"></th>
                                        <th id="totalCard"></th>
                                        <th id="totalReturn"></th>
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
        <div class="row">
            <div class="col-md-4">
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
                                <td>Cheque Payments</td>
                                <td id="chequePayments">0.00</td>
                            </tr>
                            <tr>
                                <td>Online Payments</td>
                                <td id="onlinePayments">0.00</td>
                            </tr>
                            <tr>
                                <td>Bank Transfer</td>
                                <td id="bankTransfer">0.00</td>
                            </tr>
                            <tr>
                                <td>Card Payments</td>
                                <td id="cardPayments">0.00</td>
                            </tr>
                            <tr>
                                <td>Sales Returns</td>
                                <td id="salesReturns">0.00</td>
                            </tr>
                            <tr>
                                <td>Payment Total</td>
                                <td id="paymentTotal">0.00</td>
                            </tr>
                            <tr>
                                <td>Credit Total</td>
                                <td id="creditTotal">0.00</td>
                            </tr>
                            <tr>
                                <td>Sales Returns Total</td>
                                <td id="salesReturnsTotal">0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td>Payment Total</td>
                                <td id="paymentTotalSummary">0.00</td>
                            </tr>
                            <tr>
                                <td>Past Sales Returns</td>
                                <td id="pastSalesReturns">0.00</td>
                            </tr>
                            <tr>
                                <td>Expense</td>
                                <td id="expense">0.00</td>
                            </tr>
                            {{-- <tr>
                                <td>Credit Collection (New Bills)</td>
                                <td id="creditCollectionNew">0.00</td>
                            </tr>
                            <tr>
                                <td>Credit Collection (Old Bills)</td>
                                <td id="creditCollectionOld">0.00</td>
                            </tr> --}}
                            <tr>
                                <td>Net Income</td>
                                <td id="netIncome">0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td>Cash Payments</td>
                                <td id="cashPaymentsSummary">0.00</td>
                            </tr>
                            {{-- <tr>
                                <td>Credit Collection (New Bills)</td>
                                <td id="creditCollectionNewSummary">0.00</td>
                            </tr>
                            <tr>
                                <td>Credit Collection (Old Bills)</td>
                                <td id="creditCollectionOldSummary">0.00</td>
                            </tr> --}}
                            <tr>
                                <td>Expense</td>
                                <td id="expenseSummary">0.00</td>
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

        
    <script>
        $(function() {
            var start = moment(); // Default start date is today
            var end = moment();   // Default end date is also today

            function cb(start, end) {
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
            }, cb);

            cb(start, end);
        });

        document.addEventListener('DOMContentLoaded', () => {
            const customerFilter = document.getElementById('customerFilter');
            const paymentMethodFilter = document.getElementById('paymentMethodFilter');

            if (!customerFilter || !paymentMethodFilter) {
                console.error('Customer filter or payment method filter element not found.');
                return;
            }

            const filters = {
                customer_id: customerFilter.value,
                payment_method: paymentMethodFilter.value,
                start_date: moment().format('YYYY-MM-DD'), // Default to today's date
                end_date: moment().format('YYYY-MM-DD')    // Default to today's date
            };

            fetchSalesData(filters); // Fetch sales data for today's date by default

            $('#reportrange').on('apply.daterangepicker', (ev, picker) => {
                filters.start_date = picker.startDate.format('YYYY-MM-DD');
                filters.end_date = picker.endDate.format('YYYY-MM-DD');
                fetchSalesData(filters);
            });

            async function fetchSalesData(filters = {}) {
                const params = new URLSearchParams(filters);
                const url = `/daily-sales-report?${params.toString()}`;
                console.log('Fetching data from:', url); // Debugging line
                try {
                    const response = await fetch(url);

                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }

                    const data = await response.json();
                    const filteredSales = data.sales || []; // Default to an empty array if no sales data
                    const filteredSalesReturns = data.salesReturns || []; // Default to an empty array if no sales return data

                    // Filter data on the frontend based on selected filters
                    const filteredSummaries = calculateSummaries(filteredSales, filteredSalesReturns);

                    populateTable(filteredSales, filteredSalesReturns);
                    updateSummaries(filteredSummaries);
                } catch (error) {
                    console.error('Error fetching sales data:', error);
                    populateTable([], []); // Populate the table with no data on error
                }
            }

            function filterSalesData(sales, filters) {
                return sales.filter(sale => {
                    const customerMatch = filters.customer_id ? sale.customer_id == filters.customer_id : true;
                    const paymentMethodMatch = filters.payment_method ? sale.payments.some(payment => payment.payment_method === filters.payment_method) : true;
                    const dateMatch = filters.start_date && filters.end_date ? isDateInRange(sale.sales_date, filters.start_date, filters.end_date) : true;

                    return customerMatch && paymentMethodMatch && dateMatch;
                });
            }

            function filterSalesReturnsData(salesReturns, filters) {
                return salesReturns.filter(saleReturn => {
                    const dateMatch = filters.start_date && filters.end_date ? isDateInRange(saleReturn.return_date, filters.start_date, filters.end_date) : true;

                    return dateMatch;
                });
            }

            function isDateInRange(date, startDate, endDate) {
                const saleDate = moment(date, 'YYYY-MM-DD');
                return saleDate.isBetween(startDate, endDate, 'day', '[]');
            }

            function calculateSummaries(sales, salesReturns) {
                const summaries = {
                    billTotal: 0,
                    discounts: 0,
                    cashPayments: 0,
                    chequePayments: 0,
                    onlinePayments: 0,
                    bankTransfer: 0,
                    cardPayments: 0,
                    salesReturns: 0,
                    paymentTotal: 0,
                    creditTotal: 0,
                    salesReturnsTotal: 0,
                    paymentTotalSummary: 0,
                    pastSalesReturns: 0,
                    expense: 0,
                    // creditCollectionNew: 0,
                    // creditCollectionOld: 0,
                    netIncome: 0,
                    cashPaymentsSummary: 0,
                    // creditCollectionNewSummary: 0,
                    // creditCollectionOldSummary: 0,
                    expenseSummary: 0,
                    cashInHand: 0
                };

                sales.forEach(sale => {
                    summaries.billTotal += parseFloat(sale.subtotal);
                    summaries.discounts += parseFloat(sale.discount_amount);
                    sale.payments.forEach(payment => {
                        switch (payment.payment_method) {
                            case 'cash':
                                summaries.cashPayments += parseFloat(payment.amount);
                                break;
                            case 'online':
                                summaries.onlinePayments += parseFloat(payment.amount);
                                break;
                            case 'bank_transfer':
                                summaries.bankTransfer += parseFloat(payment.amount);
                                break;
                            case 'cheque':
                                summaries.chequePayments += parseFloat(payment.amount);
                                break;
                            case 'card':
                                summaries.cardPayments += parseFloat(payment.amount);
                                break;
                            case 'credit':
                                summaries.creditTotal += parseFloat(payment.amount);
                                break;
                        }
                    });

                    summaries.paymentTotal += parseFloat(sale.final_total);
                });

                salesReturns.forEach(saleReturn => {
                    summaries.salesReturns += parseFloat(saleReturn.return_total);
                    summaries.salesReturnsTotal += parseFloat(saleReturn.return_total);
                });

                summaries.netIncome = summaries.paymentTotal - summaries.salesReturns;
                summaries.cashPaymentsSummary = summaries.cashPayments;
                // summaries.creditCollectionNewSummary = summaries.creditCollectionNew;
                // summaries.creditCollectionOldSummary = summaries.creditCollectionOld;
                // summaries.expenseSummary = summaries.expense;
                summaries.cashInHand = summaries.cashPayments - summaries.expense;

                return summaries;
            }

            function populateTable(sales, salesReturns) {
                const table = $('#salesTable').DataTable({
                    // dom: 'Bfrtip',
                    // buttons: ['copy'],
                    lengthMenu: [
                        [10, 20, 50, 75, 100, -1],
                        [10, 20, 50, 75, 100, "All"]
                    ],
                    destroy: true,         // Reinitialize the table if it already exists
                    deferRender: true,     // Render rows only when needed
                    pageLength: 10,        // Number of rows per page
                    dom: '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',

                    buttons: [
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="fa fa-file-pdf"></i> PDF',
                            filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0, 10),
                            exportOptions: { columns: ':visible' }
                        },
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0, 10),
                            exportOptions: { columns: ':visible' }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa fa-print"></i> Print',
                            title: 'Daily Sales Report',
                            exportOptions: { columns: ':visible' },
                            customize: function (win) {
                                $(win.document.body).find('h1').remove();
                                $(win.document.body).find('table').addClass('table table-bordered');
                            }
                        }
                        ],
                    columns: [
                        { title: "#" },
                        { title: "Invoice No" },
                        { title: "Customer" },
                        { title: "Date" },
                        { title: "Line Total" },
                        { title: "Line Discount" },
                        { title: "Sub Total" },
                        { title: "Bill Discount" },
                        { title: "Net Bill Total" },
                        { title: "Cash" },
                        { title: "Online" },
                        { title: "Bank Transfer" },
                        { title: "Cheque" },
                        { title: "Card" },
                        { title: "Return" },
                        { title: "Credit" },
                        { title: "Sales Return" }
                    ]
                    
                });

               //  Column visibility dropdown DataTable code start

                 function updateDropdownHighlights() {
                    $('#columnVisibilityDropdown a').each(function () {
                        const value = $(this).data('value');

                        if (value === "hide all") {
                            $(this).removeClass('selected-column');
                        } else if (value === "show all") {
                            // Highlight only if all columns are visible
                            let allVisible = true;
                            table.columns().every(function () {
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

                $('#columnVisibilityDropdown a').on('click', function (e) {
                    e.preventDefault();
                    const selectedValue = $(this).data('value');

                    if (selectedValue === "hide all") {
                        table.columns().visible(false);

                        // Remove all highlights
                        $('#columnVisibilityDropdown a').removeClass('selected-column');

                        // Highlight only "Hide All"
                        $(this).addClass('selected-column');
                    } 
                    else if (selectedValue === "show all") {
                        table.columns().visible(true);

                        // Highlight all column items and also "Show All"
                        $('#columnVisibilityDropdown a').each(function () {
                            const val = $(this).data('value');
                            if (!isNaN(val) || val === "show all") {
                                $(this).addClass('selected-column');
                            } else {
                                $(this).removeClass('selected-column');
                            }
                        });
                    } 
                    else {
                        const column = table.column(selectedValue);
                        column.visible(!column.visible());

                        // Always remove highlight from hide all
                        $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass('selected-column');

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
                document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(function (item) {
                    item.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                });

                // On page load — show all columns and highlight all column items and "Show All"
                $(document).ready(function () {
                    table.columns().visible(true);
                    $('#columnVisibilityDropdown a').each(function () {
                        const value = $(this).data('value');
                        if (!isNaN(value) || value === "show all") {
                            $(this).addClass('selected-column');
                        }
                    });
                });


                //  Column visibility dropdown DataTable code end


                let tableIndex = 0;
                const tableData = sales.map(sale => {
                    // Initialize payment amounts
                    let cash = 0,
                        online = 0,
                        bankTransfer = 0,
                        cheque = 0,
                        card = 0,
                        returnAmount = 0,
                        credit = 0;

                    // Map payments to their respective columns
                    sale.payments.forEach(payment => {
                        switch (payment.payment_method) {
                            case 'cash':
                                cash += parseFloat(payment.amount);
                                break;
                            case 'online':
                                online += parseFloat(payment.amount);
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
                            case 'return':
                                returnAmount += parseFloat(payment.amount);
                                break;
                            case 'credit':
                                credit += parseFloat(payment.amount);
                                break;
                        }
                    });

                    // Find sales return for the current sale
                    const saleReturn = salesReturns.find(returnItem => returnItem.sale_id === sale.id);
                    const salesReturnAmount = saleReturn ? parseFloat(saleReturn.return_total).toFixed(2) : '0.00';
                    return [
                        ++tableIndex,
                        sale.invoice_no,
                        `${sale.customer?.first_name} ${sale.customer?.last_name || ''}`,
                        new Date(sale.sales_date).toLocaleDateString(),
                        parseFloat(sale.subtotal).toFixed(2),
                        sale.discount_amount || '0.00',
                        parseFloat(sale.final_total).toFixed(2),
                        parseFloat(sale.total_paid).toFixed(2),
                        parseFloat(sale.total_due).toFixed(2),
                        cash.toFixed(2),
                        online.toFixed(2),
                        bankTransfer.toFixed(2),
                        cheque.toFixed(2),
                        card.toFixed(2),
                        returnAmount.toFixed(2),
                        credit.toFixed(2),
                        salesReturnAmount
                    ];
                });

                table.clear().rows.add(tableData).draw();

                if (tableData.length === 0) {
                    $('#salesTable tbody').html('<tr><td colspan="16" class="text-center">No records found</td></tr>');
                }

                // Calculate totals for each column
                const totals = tableData.reduce((acc, row) => {
                    acc.lineTotal += parseFloat(row[4]);
                    acc.lineDiscount += parseFloat(row[5]);
                    acc.subTotal += parseFloat(row[6]);
                    acc.billDiscount += parseFloat(row[7]);
                    acc.netBillTotal += parseFloat(row[8]);
                    acc.cash += parseFloat(row[9]);
                    acc.online += parseFloat(row[10]);
                    acc.bankTransfer += parseFloat(row[11]);
                    acc.cheque += parseFloat(row[12]);
                    acc.card += parseFloat(row[13]);
                    acc.return += parseFloat(row[14]);
                    acc.credit += parseFloat(row[15]);
                    acc.salesReturn += parseFloat(row[16]);
                    return acc;
                }, {
                    lineTotal: 0,
                    lineDiscount: 0,
                    subTotal: 0,
                    billDiscount: 0,
                    netBillTotal: 0,
                    cash: 0,
                    online: 0,
                    bankTransfer: 0,
                    cheque: 0,
                    card: 0,
                    return: 0,
                    credit: 0,
                    salesReturn: 0
                });

                // Update footer with totals
                $('#totalLineTotal').text(totals.lineTotal.toFixed(2));
                $('#totalLineDiscount').text(totals.lineDiscount.toFixed(2));
                $('#totalSubTotal').text(totals.subTotal.toFixed(2));
                $('#totalBillDiscount').text(totals.billDiscount.toFixed(2));
                $('#totalNetBillTotal').text(totals.netBillTotal.toFixed(2));
                $('#totalCash').text(totals.cash.toFixed(2));
                $('#totalOnline').text(totals.online.toFixed(2));
                $('#totalBankTransfer').text(totals.bankTransfer.toFixed(2));
                $('#totalCheque').text(totals.cheque.toFixed(2));
                $('#totalCard').text(totals.card.toFixed(2));
                $('#totalReturn').text(totals.return.toFixed(2));
                $('#totalCredit').text(totals.credit.toFixed(2));
                $('#totalSalesReturn').text(totals.salesReturn.toFixed(2));
            }

            function updateSummaries(summaries) {
                Object.keys(summaries).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        element.textContent = parseFloat(summaries[key]).toFixed(2);
                    } else {
                        console.error(`Element with id '${key}' not found.`);
                    }
                });
            }
        });
    </script>

@endsection
