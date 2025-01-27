@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Sell</a></li>
                                <li class="breadcrumb-item active">All Sales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Bussiness Location <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="location">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="customer">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status<span class="login-danger">*</span></label>
                                        <select class="form-control select" name="payment_status">
                                            <option value="" disabled selected>All</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Due">Due</option>
                                            <option value="Partial">Partial</option>
                                            <option value="Overdue">Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range<span class="login-danger"></span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY"
                                            name="date_range">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>User<span class="login-danger">*</span></label>
                                        <select class="form-control select" name="user">
                                            <option value="" disabled selected>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="shipping_status">
                                            <option value="" disabled selected>All</option>
                                            <option value="Order">Order</option>
                                            <option value="Packed">Packed</option>
                                            <option value="Shipped">Shipped</option>
                                            <option value="Delivered">Delivered</option>
                                            <option value="Cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="payment_method">
                                            <option value="" disabled selected>All</option>
                                            <option value="Advance">Advance</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Sources <span class="login-danger">*</span></label>
                                        <select class="form-control select" name="sources">
                                            <option value="" disabled selected>All</option>
                                            <option value="Woocommerce">Woocommerce</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- table row --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-table">
                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-justified-tab1">
                                <div class="card-body">
                                    <div class="page-header">
                                        <div class="row align-items-center">
                                            <div class="col-auto text-end float-end ms-auto download-grp">
                                                <!-- Button trigger modal -->
                                                <a href="#"><button type="button" class="btn btn-outline-info">
                                                        <i class="fas fa-plus px-2"> </i>Add
                                                    </button></a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="datatable table table-stripped" style="width:100%" id="salesTable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Date</th>
                                                    <th>Invoice No</th>
                                                    <th>Customer Name</th>
                                                    <th>Location</th>
                                                    <th>Payment Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be populated by DataTables -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal to show sale details -->
    <div class="modal fade" id="saleDetailsModal" tabindex="-1" aria-labelledby="saleDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalTitle"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Customer:</h5>
                                <p id="customerDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Location:</h5>
                                <p id="locationDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Sales Details:</h5>
                                <p id="salesDetails"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12  mt-4">
                                <h5>Products:</h5>
                                <table class="table table-bordered" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Product rows will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-4">
                                <h5>Payment Info:</h5>
                                <table class="table table-bordered" id="paymentInfoTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference No</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Payment Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Payment info will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mt-4">
                                <h5>Amount Details:</h5>
                                <table class="table" id="amountDetailsTable">
                                    <tbody>
                                        <!-- Amount details will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Activities:</h5>
                                <table class="table table-bordered" id="activitiesTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>By</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">No records found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-secondary" onclick="printModal()">Print</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    {{-- <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#salesTable').DataTable();

            // Function to fetch and update data
            function fetchData(filters = {}) {
                $.ajax({
                    url: '/sales',
                    type: 'GET',
                    data: filters,
                    dataType: 'json',
                    success: function(response) {
                        table.clear().draw();
                        if (response.sales && Array.isArray(response.sales)) {
                            var counter = 1;
                            response.sales.forEach(function(item) {
                                let row = $('<tr>');
                                row.append('<td>' + counter + '</td>');
                                row.append('<td>' + item.sales_date + '</td>');
                                row.append('<td>' + item.invoice_no + '</td>');
                                row.append('<td>' + item.customer.first_name + ' ' + item
                                    .customer.last_name + '</td>');
                                row.append('<td>' + item.location.name + '</td>');
                                row.append('<td>' + item.status + '</td>');
                                row.append('<td><button type="button" value="' + item.id +
                                    '" class="view-details btn btn-outline-info btn-sm me-2"><i class="feather-eye text-info"></i> View</button><button type="button" value="' +
                                    item.id +
                                    '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' +
                                    item.id +
                                    '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>'
                                );
                                table.row.add(row).draw(false);
                                counter++;
                            });
                        } else {
                            console.error('Sales data is not in the expected format.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching sales data:', error);
                    }
                });
            }

                        // Event listener for edit button
                $('#salesTable tbody').on('click', 'button.edit_btn', function() {
                    var saleId = $(this).val();
                    fetchSaleData(saleId);
                });


            // Function to fetch filter options and populate dropdowns
            function fetchFilterOptions() {
                // Fetch locations
                $.ajax({
                    url: '/location-get-all',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Location Data:', response); // Log location data
                        if (response.status === 200) {
                            const locationSelect = $('select[name="location"]');
                            locationSelect.html('<option value="" disabled selected>All</option>');

                            response.message.forEach(function(location) {
                                const option = $('<option></option>').val(location.id).text(
                                    location.name);
                                locationSelect.append(option);
                            });
                        } else {
                            console.error('Failed to fetch location data:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching location data:', error);
                    }
                });

                // Fetch customers
                $.ajax({
                    url: '/customer-get-all',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Customer Data:', response); // Log customer data
                        if (response.status === 200) {
                            const customerSelect = $('select[name="customer"]');
                            customerSelect.html('<option value="" disabled selected>All</option>');

                            response.message.forEach(function(customer) {
                                const option = $('<option></option>').val(customer.id).text(
                                    `${customer.first_name} ${customer.last_name}`);
                                customerSelect.append(option);
                            });
                        } else {
                            console.error('Failed to fetch customer data:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching customer data:', error);
                    }
                });

                // Fetch users
                $.ajax({
                    url: '/user-get-all',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('User Data:', response); // Log user data
                        if (response.status === 200) {
                            const userSelect = $('select[name="user"]');
                            userSelect.html('<option value="" disabled selected>All</option>');

                            response.message.forEach(function(user) {
                                const option = $('<option></option>').val(user.id).text(user
                                    .name);
                                userSelect.append(option);
                            });
                        } else {
                            console.error('Failed to fetch user data:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching user data:', error);
                    }
                });
            }

            // Initial fetch for filter options
            fetchFilterOptions();

            // Initial fetch for sales data
            fetchData();

            // Event listener for filter inputs
            $('select[name="location"], select[name="customer"], select[name="payment_status"], input[name="date_range"], select[name="user"], select[name="shipping_status"], select[name="payment_method"], select[name="sources"]')
                .change(function() {
                    var filters = {
                        location: $('select[name="location"]').val(),
                        customer: $('select[name="customer"]').val(),
                        payment_status: $('select[name="payment_status"]').val(),
                        date_range: $('input[name="date_range"]').val(),
                        user: $('select[name="user"]').val(),
                        shipping_status: $('select[name="shipping_status"]').val(),
                        payment_method: $('select[name="payment_method"]').val(),
                        sources: $('select[name="sources"]').val(),
                    };
                    fetchData(filters);
                });

            // Event listener for view details button
            $('#salesTable tbody').on('click', 'button.view-details', function() {
                var saleId = $(this).val();
                $.ajax({
                    url: '/sales_details/' + saleId,
                    type: 'GET',
                    success: function(response) {
                        if (response.salesDetails) {
                            const saleDetails = response.salesDetails;
                            const customer = saleDetails.customer;
                            const location = saleDetails.location;
                            const products = saleDetails.products;

                            // Populate modal fields
                            $('#modalTitle').text('Sale Details - Invoice No: ' + saleDetails
                                .invoice_no);
                            $('#customerDetails').text(customer.first_name + ' ' + customer
                                .last_name);
                            $('#locationDetails').text(location.name);
                            $('#salesDetails').text('Date: ' + saleDetails.sales_date +
                                ', Status: ' + saleDetails.status);

                            // Populate products table
                            const productsTableBody = $('#productsTable tbody');
                            productsTableBody.empty();
                            if (products && Array.isArray(products)) {
                                products.forEach((product, index) => {
                                    const productRow = $('<tr>');
                                    productRow.append('<td>' + (index + 1) + '</td>');
                                    productRow.append('<td>' + product.product
                                        .product_name + '</td>');
                                    productRow.append('<td>' + product.product.sku +
                                        '</td>');
                                    productRow.append('<td>' + product.quantity +
                                        '</td>');
                                    productRow.append('<td>' + product.price + '</td>');
                                    productRow.append('<td>' + (product.quantity *
                                        product.price).toFixed(2) + '</td>');
                                    productsTableBody.append(productRow);
                                });
                            }

                            // Populate payment info table
                            const paymentInfoTableBody = $('#paymentInfoTable tbody');
                            paymentInfoTableBody.empty();
                            if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                                saleDetails.payments.forEach((payment) => {
                                    const paymentRow = $('<tr>');
                                    paymentRow.append('<td>' + payment.date + '</td>');
                                    paymentRow.append('<td>' + payment.reference_no +
                                        '</td>');
                                    paymentRow.append('<td>' + payment.amount +
                                        '</td>');
                                    paymentRow.append('<td>' + payment.payment_mode +
                                        '</td>');
                                    paymentRow.append('<td>' + payment.payment_note +
                                        '</td>');
                                    paymentInfoTableBody.append(paymentRow);
                                });
                            }

                            // Populate amount details table
                            const amountDetailsTableBody = $('#amountDetailsTable tbody');
                            amountDetailsTableBody.empty();
                            amountDetailsTableBody.append('<tr><td>Total Amount</td><td>' +
                                saleDetails.total_amount + '</td></tr>');
                            amountDetailsTableBody.append('<tr><td>Paid Amount</td><td>' +
                                saleDetails.paid_amount + '</td></tr>');
                            amountDetailsTableBody.append('<tr><td>Due Amount</td><td>' +
                                saleDetails.due_amount + '</td></tr>');

                            // Populate activities table
                            const activitiesTableBody = $('#activitiesTable tbody');
                            activitiesTableBody.empty();
                            if (saleDetails.activities && Array.isArray(saleDetails
                                    .activities)) {
                                saleDetails.activities.forEach((activity) => {
                                    const activityRow = $('<tr>');
                                    activityRow.append('<td>' + activity.date +
                                        '</td>');
                                    activityRow.append('<td>' + activity.action +
                                        '</td>');
                                    activityRow.append('<td>' + activity.by + '</td>');
                                    activityRow.append('<td>' + activity.note +
                                        '</td>');
                                    activitiesTableBody.append(activityRow);
                                });
                            }

                            $('#saleDetailsModal').modal('show');
                        } else {
                            console.error('Sales details data is not in the expected format.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching sales details:', error);
                    }
                });
            });

            // Function to print the modal content
            window.printModal = function() {
                var printContents = document.getElementById('saleDetailsModal').innerHTML;
                var originalContents = document.body.innerHTML;
                document.body.innerHTML = printContents;
                window.print();
                document.body.innerHTML = originalContents;
                // location.reload();  // Reload the page to restore the original content and bindings
            };
        });
    </script> --}}

    @include('sell.sales_ajax')
@endsection
