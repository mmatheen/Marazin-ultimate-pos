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
                            <ul class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" id="columnVisibilityDropdown"
                                style="width: 400px;">
                                <div class="row">

                                    <div class="col-md-6">
                                        <li><a class="dropdown-item" href="#" data-value="hide all">1. Hide All
                                                Columns</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="show all">2. Show All
                                                Columns</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="0">3. ID</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="1">4. Invoice No</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="2">5. Customer</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="3">6. Date</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="4">7. Sub Total</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="5">8. Bill Discount</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="6">9. Net Bill Total</a>
                                        </li>
                                    </div>
                                    <div class="col-md-6">
                                        <li><a class="dropdown-item" href="#" data-value="7">10. Cash</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="8">11. Bank Transfer</a>
                                        </li>
                                        <li><a class="dropdown-item" href="#" data-value="9">12. Cheque</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="10">13. Card</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="11">14. Credit</a></li>
                                        <li><a class="dropdown-item" href="#" data-value="12">15. Sales Return</a>
                                        </li>
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
                            <div class="col-lg-6 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Customer:</label>
                                    <select class="form-control select" id="customerFilter">
                                        <option value="">Select Customer</option>
                                        <!-- Populate with customer options -->
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
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
                                <tbody>
                                </tbody>

                                <tfoot style="border-top: 2px solid #dee2e6;">
                                    <tr>
                                        <th colspan="4" class="text-center" style="font-size: 20px;">Total</th>
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

            const filters = {
                customer_id: '',
                start_date: moment().format('YYYY-MM-DD'),
                end_date: moment().format('YYYY-MM-DD')
            };

            fetchSalesData(filters);

            $('#reportrange').on('apply.daterangepicker', (ev, picker) => {
                filters.start_date = picker.startDate.format('YYYY-MM-DD');
                filters.end_date = picker.endDate.format('YYYY-MM-DD');
                fetchSalesData(filters);
            });

            if (customerFilter) {
                customerFilter.addEventListener('change', () => {
                    filters.customer_id = customerFilter.value || '';
                    fetchSalesData(filters); // Trigger fetch with updated filters
                });
            }

            async function fetchSalesData(filters) {
                const params = new URLSearchParams(filters);
                const url = `/daily-sales-report?${params.toString()}`;

                try {
                    console.log('Fetching data with filters:', filters); // Debugging filter values
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

                    const data = await response.json();
                    console.log('Data received:', data); // Debugging the returned data

                    populateCustomerDropdown(data.sales || []);
                    populateTable(data.sales || [], data.salesReturns || []);
                    updateSummaries(data.summaries || {});
                } catch (error) {
                    console.error('Error fetching sales data:', error);
                    populateCustomerDropdown([]);
                    populateTable([], []);
                }
            }

            function populateCustomerDropdown(sales) {
                if (!customerFilter) return;

                const customerMap = new Map();
                sales.forEach(sale => {
                    if (sale.customer) {
                        const customerName =
                            `${sale.customer.first_name || ''} ${sale.customer.last_name || ''}`.trim();
                        if (customerName) {
                            customerMap.set(sale.customer.id, customerName);
                        }
                    }
                });

                customerFilter.innerHTML = '<option value="">Select Customer</option>';
                customerMap.forEach((name, id) => {
                    const option = document.createElement('option');
                    option.value = id;
                    option.textContent = name;
                    customerFilter.appendChild(option);
                });
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
                    buttons: [
                        {
    extend: 'pdfHtml5',
    text: '<i class="fa fa-file-pdf"></i> PDF',
    orientation: 'landscape',
    pageSize: 'A4',
    filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0, 10),
    exportOptions: {
        columns: ':visible',
        footer: true, // <-- Important: enables footer in PDF
        format: {
            body: function(data, row, column, node) {
                return data;
            },
            header: function(data, column, node) {
                return data;
            }
        }
    },
    customize: function (doc) {
        // Auto-fit the columns by setting equal widths based on the number of columns
        const colCount = doc.content[1].table.body[0].length;
        doc.content[1].table.widths = new Array(colCount).fill('*');

        // Optional: Style adjustments
        doc.styles.tableHeader.fontSize = 10;
        doc.styles.tableBodyEven.fontSize = 9;
        doc.styles.tableBodyOdd.fontSize = 9;
    }
},
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa fa-file-excel"></i> Excel',
                            filename: () => 'daily_sales_details_' + new Date().toISOString().slice(0,
                                10),
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa fa-print"></i> Print',
                            title: 'ARB Fashion Daily Sales Report',
                            exportOptions: {
                                columns: ':visible',
                                format: {
                                    // Remove `footer: true` and use this instead:
                                    body: function(data, row, column, node) {
                                        return data;
                                    },
                                    header: function(data, column, node) {
                                        return data;
                                    }
                                }
                            },
                            customize: function(win) {
                                // Manually ensure the footer is included
                                const footer = $('#salesTable tfoot').clone();
                                $(win.document.body).find('table').append(footer);

                                // Styling adjustments
                                $(win.document.body).find('table').addClass('table table-bordered');
                                $(win.document.body).find('h1').remove();
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


                //  Column visibility dropdown DataTable code end


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

                    const saleReturn = salesReturns.find(returnItem => returnItem.sale_id === sale.id);
                    const salesReturnAmount = saleReturn ? parseFloat(saleReturn.return_total) : 0;

                    return [
                        ++tableIndex,
                        sale.invoice_no,
                        `${sale.customer?.first_name || ''} ${sale.customer?.last_name || ''}`.trim(),
                        new Date(sale.sales_date).toLocaleDateString(),
                        parseFloat(sale.subtotal).toFixed(2),
                        parseFloat(sale.discount_amount || 0).toFixed(2),
                        parseFloat(sale.final_total).toFixed(2),
                        cash.toFixed(2),
                        bankTransfer.toFixed(2),
                        cheque.toFixed(2),
                        card.toFixed(2),
                        parseFloat(sale.total_due).toFixed(2),
                        salesReturnAmount.toFixed(2)
                    ];
                });

                table.clear().rows.add(tableData).draw();

                if (tableData.length > 0) {
                    const totals = tableData.reduce((acc, row) => ({
                        subTotal: acc.subTotal + parseFloat(row[4]),
                        billDiscount: acc.billDiscount + parseFloat(row[5]),
                        netBillTotal: acc.netBillTotal + parseFloat(row[6]),
                        cash: acc.cash + parseFloat(row[7]),
                        bankTransfer: acc.bankTransfer + parseFloat(row[8]),
                        cheque: acc.cheque + parseFloat(row[9]),
                        card: acc.card + parseFloat(row[10]),
                        credit: acc.credit + parseFloat(row[11]),
                        salesReturn: acc.salesReturn + parseFloat(row[12])
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
                        '<tr><td colspan="13" class="text-center">No records found</td></tr>');
                    $('tfoot th:not(:first-child)').text('0.00');
                }
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
