@extends('layout.layout')

{{-- @section('head')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        /* Fix Select2 dropdown alignment and search in modal */
        #bulkPaymentModal .select2-container {
            width: 100% !important;
        }

        #bulkPaymentModal .select2-container--default .select2-selection--single {
            height: 38px;
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        #bulkPaymentModal .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px;
            padding-left: 0;
        }

        #bulkPaymentModal .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        /* Ensure dropdown appears above modal backdrop */
        .select2-container--open {
            z-index: 9999 !important;
        }

        .select2-dropdown {
            z-index: 9999 !important;
        }

        /* Fix search input focus */
        .select2-search__field {
            outline: none;
            border: 1px solid #ced4da !important;
            padding: 4px !important;
        }

        .select2-search__field:focus {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }
    </style>
@endsection --}}

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('list-sale') }}">Sell</a></li>
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
                                        <label>Business Location</label>
                                        <select class="form-control select" id="locationFilter" name="location">
                                            <option value="">All</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer</label>
                                        <select class="form-control select" id="customerFilter" name="customer">
                                            <option value="">All</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ trim($customer->first_name . ' ' . $customer->last_name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status</label>
                                        <select class="form-control select" id="paymentStatusFilter" name="payment_status">
                                            <option value="">All</option>
                                            <option value="paid">Paid</option>
                                            <option value="due">Due</option>
                                            <option value="partial">Partial</option>
                                            <option value="overdue">Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range</label>
                                        <input class="form-control" type="text" placeholder="Select date range" id="dateRangeFilter" name="date_range">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>User</label>
                                        <select class="form-control select" id="userFilter" name="user">
                                            <option value="">All</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status</label>
                                        <select class="form-control select" id="shippingStatusFilter" name="shipping_status">
                                            <option value="">All</option>
                                            <option value="order">Order</option>
                                            <option value="packed">Packed</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method</label>
                                        <select class="form-control select" id="paymentMethodFilter" name="payment_method">
                                            <option value="">All</option>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
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
                                                <a href="/pos-create"><button type="button"
                                                        class="btn btn-outline-info">
                                                        <i class="fas fa-plus px-2"> </i>Add
                                                    </button></a>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="table-responsive">

                                        <table id="salesTable" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Action</th>
                                                    <th>Date</th>
                                                    <th>Invoice No.</th>
                                                    <th>Customer Name</th>
                                                    <th>Contact Number</th>
                                                    <th>Location</th>
                                                    <th>Payment Status</th>
                                                    <th>Payment Method</th>
                                                    <th>Total Amount</th>
                                                    <th>Total Paid</th>
                                                    <th>Sell Due</th>
                                                    <th>Shipping Status</th>
                                                    <th>Total Items</th>
                                                    <th>Added By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Rows will be dynamically added here -->
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

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="paymentForm">
                            <input type="hidden" id="paymentId" name="payment_id">
                            <input type="hidden" id="saleId" name="reference_id">
                            <input type="hidden" id="payment_type" name="payment_type">
                            <input type="hidden" id="customer_id" name="customer_id">
                            <input type="hidden" id="reference_no" name="reference_no">

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                        <div class="card-body" style="padding: 0.75rem;">
                                            <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">
                                                Customer</h5>
                                            <p id="paymentCustomerDetail" style="margin: 0;"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                        <div class="card-body" style="padding: 0.75rem;">
                                            <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">
                                                Reference No</h5>
                                            <p id="referenceNo" style="margin: 0;"></p>
                                            <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">
                                                Location</h5>
                                            <p id="paymentLocationDetails" style="margin: 0;"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                        <div class="card-body" style="padding: 0.75rem;">
                                            <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total
                                                Amount</h5>
                                            <p id="totalAmount" style="margin: 0;"></p>
                                            <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total
                                                Paid Amount</h5>
                                            <p id="totalPaidAmount" style="margin: 0;"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Additional form elements -->
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="advanceBalance" class="form-label">Advance Balance : Rs. 0.00</label>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="paymentMethod" class="form-label">Payment Method</label>
                                        <select class="form-select" id="paymentMethod" name="payment_method"
                                            onchange="togglePaymentFields()">
                                            <option value="cash" selected>Cash</option>
                                            <option value="card">Credit Card</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="paidOn" class="form-label">Paid On</label>
                                        <input class="form-control datetimepicker" type="text" name="payment_date"
                                            id="paidOn" placeholder="DD-MM-YYYY">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="payAmount" class="form-label">Amount</label>
                                        <input type="text" class="form-control" id="payAmount" name="amount"
                                            oninput="validateAmount()">
                                        <div id="amountError" class="text-danger" style="display:none;"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditional Payment Fields -->
                            <div id="creditCardFields" class="row mb-3 d-none">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="cardNumber" class="form-label">Card Number</label>
                                        <input type="text" class="form-control" id="cardNumber" name="card_number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="cardHolderName" class="form-label">Card Holder Name</label>
                                        <input type="text" class="form-control" id="cardHolderName"
                                            name="card_holder_name">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="cardType" class="form-label">Card Type</label>
                                        <select class="form-select" id="cardType" name="card_type">
                                            <option value="visa">Visa</option>
                                            <option value="mastercard">MasterCard</option>
                                            <option value="amex">American Express</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="expiryMonth" class="form-label">Expiry Month</label>
                                        <input type="text" class="form-control" id="expiryMonth"
                                            name="card_expiry_month">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="expiryYear" class="form-label">Expiry Year</label>
                                        <input type="text" class="form-control" id="expiryYear"
                                            name="card_expiry_year">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="securityCode" class="form-label">Security Code</label>
                                        <input type="text" class="form-control" id="securityCode"
                                            name="card_security_code">
                                    </div>
                                </div>
                            </div>

                            <div id="chequeFields" class="row mb-3 d-none">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="chequeNumber" class="form-label">Cheque Number</label>
                                        <input type="text" class="form-control" id="chequeNumber"
                                            name="cheque_number">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="bankBranch" class="form-label">Bank Branch</label>
                                        <input type="text" class="form-control" id="bankBranch"
                                            name="cheque_bank_branch">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cheque_received_date" class="form-label">Check Received Date</label>
                                        <input type="text" class="form-control datetimepicker"
                                            id="cheque_received_date" name="cheque_received_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                                        <input type="text" class="form-control datetimepicker" id="cheque_valid_date"
                                            name="cheque_valid_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cheque_given_by" class="form-label">Check Given by</label>
                                        <input type="text" class="form-control" id="cheque_given_by"
                                            name="cheque_given_by">
                                    </div>
                                </div>
                            </div>

                            <div id="bankTransferFields" class="row mb-3 d-none">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="bankAccountNumber" class="form-label">Bank Account Number</label>
                                        <input type="text" class="form-control" id="bankAccountNumber"
                                            name="bank_account_number">
                                    </div>
                                </div>
                            </div>

                            <!-- Remaining Form Elements -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="paymentAccount" class="form-label">Payment Account</label>
                                        <select class="form-select" id="paymentAccount" name="payment_account">
                                            <option selected>None</option>
                                            <option value="1">Account 1</option>
                                            <option value="2">Account 2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="attachDocument" class="form-label">Attach Document</label>
                                        <input type="file" class="form-control" id="attachDocument"
                                            name="attach_document" accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="paymentNote" class="form-label">Payment Note</label>
                                <textarea class="form-control" id="paymentNotes" name="payment_note"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="savePayment">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Payment Modal -->
        <div class="modal fade" id="viewPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewPaymentModalLabel">View Payments ( Reference No: <span
                                id="viewReferenceNo">PO2018/0002</span> )</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Customer:</strong>
                                <p id="viewCustomerDetail"></p>
                            </div>
                            <div class="col-md-6">
                                <strong>Business:</strong>
                                <p id="viewBusinessDetail"></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Reference No:</strong> <span id="viewReferenceNo"></span><br>
                                <strong>Date:</strong> <span id="viewDate"></span><br>
                            </div>
                            <div class="col-md-6">
                                <strong>Purchase Status:</strong> <span id="viewPurchaseStatus"></span><br>
                                <strong>Payment Status:</strong> <span id="viewPaymentStatus"></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <button class="btn btn-info"> <i class="fas fa-envelope"></i> Payment Paid
                                Notification</button>
                            <button class="btn btn-outline-primary"
                                onclick="openPaymentModal(event, $('#viewPaymentModal').data('sale-id'))"> <i
                                    class="fas fa-plus-circle"></i> Add payment</button>
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference No</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Note</th>
                                    <th>Payment Account</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center">No records found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary"> <i class="fas fa-print"></i> Print</button>
                        <button class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                // Ensure bulk payment modal uses the same functionality as the separate page
                // The sales_ajax.blade.php file included at the end provides all the functionality

                // Override the local togglePaymentFields function to use the global one from sales_ajax.blade.php
                window.togglePaymentFields = window.togglePaymentFields || togglePaymentFieldsLocal;
            });

            // Define the openPaymentModal function
            function openPaymentModal(event, saleId) {
                // Implement the logic to open the payment modal
                $('#paymentModal').modal('show');
                fetchSaleDetailsForPayment(saleId);
            }

            // Function to fetch sale details for payment modal
            function fetchSaleDetailsForPayment(saleId) {
                $.ajax({
                    url: '/sales_details/' + saleId,
                    type: 'GET',
                    success: function(response) {
                        if (response.salesDetails) {
                            const saleDetails = response.salesDetails;
                            const customer = saleDetails.customer;
                            const location = saleDetails.location;

                            // Populate payment modal fields
                            $('#paymentModalLabel').text('Add Payment - Invoice No: ' + saleDetails.invoice_no);
                            $('#paymentCustomerDetail').text(customer.first_name + ' ' + customer.last_name);
                            $('#paymentLocationDetails').text(location.name);
                            $('#totalAmount').text(saleDetails.final_total);
                            $('#totalPaidAmount').text(saleDetails.total_paid);

                            $('#saleId').val(saleDetails.id);
                            $('#payment_type').val('sale');
                            $('#customer_id').val(customer.id);
                            $('#reference_no').val(saleDetails.invoice_no);
                            // Set default date to today
                            $('#paidOn').val(new Date().toISOString().split('T')[0]);

                            // Set the amount field to the total due amount
                            $('#payAmount').val(saleDetails.total_due);

                            // Ensure the Add Payment modal is brought to the front
                            $('#viewPaymentModal').modal('hide');
                            $('#paymentModal').modal('show');

                            // Validate the amount input
                            $('#payAmount').off('input').on('input', function() {
                                let amount = parseFloat($(this).val());
                                let totalDue = parseFloat(saleDetails.total_due);
                                if (amount > totalDue) {
                                    $('#amountError').text('The given amount exceeds the total due amount.').show();
                                    $(this).val(totalDue);
                                } else {
                                    $('#amountError').hide();
                                }
                            });

                            $('#paymentModal').modal('show');
                        } else {
                            console.error('Sales details data is not in the expected format.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching sales details:', error);
                    }
                });
            }


            // Toggle payment fields based on selected payment method for individual payments
            $('#paymentMethod').change(function() {
                togglePaymentFields('paymentModal');
            });

            // Function to toggle payment fields based on payment method (local version for sale.blade.php)
            function togglePaymentFieldsLocal(modalId) {
                const paymentMethod = $(`#${modalId} #paymentMethod`).val();

                // Hide all payment method specific fields
                $(`#${modalId} #cardFields`).hide();
                $(`#${modalId} #chequeFields`).hide();
                $(`#${modalId} #bankTransferFields`).hide();

                // Show relevant fields based on payment method
                if (paymentMethod === 'card') {
                    $(`#${modalId} #cardFields`).show();
                } else if (paymentMethod === 'cheque') {
                    $(`#${modalId} #chequeFields`).show();
                } else if (paymentMethod === 'bank_transfer') {
                    $(`#${modalId} #bankTransferFields`).show();
                }
            }
        </script>

        @include('sell.sales_ajax')
@endsection
