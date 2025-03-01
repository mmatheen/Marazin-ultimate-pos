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
                                <li class="breadcrumb-item"><a href={{ route('list-sale') }}>Sell</a></li>
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
                                                <a href={{ route('add-sale') }}><button type="button"
                                                        class="btn btn-outline-info">
                                                        <i class="fas fa-plus px-2"> </i>Add
                                                    </button></a>
                                            </div>
                                        </div>
                                    </div>



                                    <div class="table-responsive">
                                        <div class="mb-3">
                                            <button id="bulkPaymentBtn" class="btn btn-primary">
                                                Add Bulk Payment
                                            </button>
                                        </div>
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


        <div class="modal fade" id="bulkPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="bulkPaymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkPaymentModalLabel">Customer Bulk Payments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="bulkPaymentForm">
                            <div class="card mb-4 shadow-sm rounded">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="customerSelect">Select Customer</label>
                                        <select id="customerSelect" class="form-control">
                                            <option value="">Select Customer</option>
                                        </select>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-3">
                                            <div class="card bg-light p-3 rounded text-center shadow-sm">
                                                <strong>Opening Balance:</strong>
                                                <span id="openingBalance" class="d-block mt-2">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light p-3 rounded text-center shadow-sm">
                                                <strong>Total Sales:</strong>
                                                <span id="totalSalesAmount" class="d-block mt-2">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light p-3 rounded text-center shadow-sm">
                                                <strong>Total Paid:</strong>
                                                <span id="totalPaidAmount" class="d-block mt-2">$0.00</span>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-light p-3 rounded text-center shadow-sm">
                                                <strong>Total Due:</strong>
                                                <span id="totalDueAmount" class="d-block mt-2">$0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="salesListContainer" class="mt-4">
                                        <table id="salesList" class="table table-striped" style="margin-bottom: 70px; margin-top: 30px">
                                            <thead>
                                                <tr>
                                                    <th>Sale ID</th>
                                                    <th>Final Total</th>
                                                    <th>Total Paid</th>
                                                    <th>Total Due</th>
                                                    <th>Payment Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                                <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
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
                                                <input class="form-control datetimepicker" type="text" name="payment_date" id="paidOn" placeholder="DD-MM-YYYY">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="payAmount" class="form-label">Amount</label>
                                                <input type="text" class="form-control" id="globalPaymentAmount" name="amount">
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
                                                <input type="text" class="form-control" id="cardHolderName" name="card_holder_name">
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
                                                <input type="text" class="form-control" id="expiryMonth" name="card_expiry_month">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="expiryYear" class="form-label">Expiry Year</label>
                                                <input type="text" class="form-control" id="expiryYear" name="card_expiry_year">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="securityCode" class="form-label">Security Code</label>
                                                <input type="text" class="form-control" id="securityCode" name="card_security_code">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="chequeFields" class="row mb-3 d-none">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="chequeNumber" class="form-label">Cheque Number</label>
                                                <input type="text" class="form-control" id="chequeNumber" name="cheque_number">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="bankBranch" class="form-label">Bank Branch</label>
                                                <input type="text" class="form-control" id="bankBranch" name="cheque_bank_branch">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cheque_received_date" class="form-label">Check Received Date</label>
                                                <input type="text" class="form-control datetimepicker" id="cheque_received_date" name="cheque_received_date">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                                                <input type="text" class="form-control datetimepicker" id="cheque_valid_date" name="cheque_valid_date">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="cheque_given_by" class="form-label">Check Given by</label>
                                                <input type="text" class="form-control" id="cheque_given_by" name="cheque_given_by">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="bankTransferFields" class="row mb-3 d-none">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label for="bankAccountNumber" class="form-label">Bank Account Number</label>
                                                <input type="text" class="form-control" id="bankAccountNumber" name="bank_account_number">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="submitBulkPayment" class="btn btn-success">Submit Payment</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
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

    // Toggle payment fields based on selected payment method
$('#bulkPaymentModal #paymentMethod').change(function() {
    togglePaymentFields('bulkPaymentModal');
});

// Toggle payment fields based on selected payment method for individual payments
$('#paymentMethod').change(function() {
    togglePaymentFields('paymentModal');
});// Function to toggle payment fields based on payment method
function togglePaymentFields(modalId) {
    const paymentMethod = $(`#${modalId} #paymentMethod`).val();
    if (paymentMethod === 'card') {
        $(`#${modalId} #creditCardFields`).removeClass('d-none');
        $(`#${modalId} #chequeFields`).addClass('d-none');
        $(`#${modalId} #bankTransferFields`).addClass('d-none');
    } else if (paymentMethod === 'cheque') {
        $(`#${modalId} #creditCardFields`).addClass('d-none');
        $(`#${modalId} #chequeFields`).removeClass('d-none');
        $(`#${modalId} #bankTransferFields`).addClass('d-none');
    } else if (paymentMethod === 'bank_transfer') {
        $(`#${modalId} #creditCardFields`).addClass('d-none');
        $(`#${modalId} #chequeFields`).addClass('d-none');
        $(`#${modalId} #bankTransferFields`).removeClass('d-none');
    } else {
        $(`#${modalId} #creditCardFields`).addClass('d-none');
        $(`#${modalId} #chequeFields`).addClass('d-none');
        $(`#${modalId} #bankTransferFields`).addClass('d-none');
    }
}

        </script>


        @include('sell.sales_ajax')
    @endsection
