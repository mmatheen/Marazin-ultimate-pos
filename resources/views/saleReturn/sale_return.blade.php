@extends('layout.layout')
@section('content')
    <style>
        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            display: none;
            float: left;
            min-width: 160px;
            padding: 5px 0;
            margin: 2px 0 0;
            font-size: 14px;
            text-align: left;
            list-style: none;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        }
    </style>
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales Returns</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href={{ route('sale-return/list') }}>Sell Return</a></li>
                                <li class="breadcrumb-item active">All Sales Returns</li>
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
                                        <label>Business Location <span class="login-danger">*</span></label>
                                        <select class="form-control select selectBox" name="location">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer <span class="login-danger">*</span></label>
                                        <select class="form-control select selectBox" name="customer">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status<span class="login-danger">*</span></label>
                                        <select class="form-control select selectBox" name="payment_status">
                                            <option value="">All</option>
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
                                        <select class="form-control select selectBox" name="user">
                                            <option value="">All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status <span class="login-danger">*</span></label>
                                        <select class="form-control select selectBox" name="shipping_status">
                                            <option value="">All</option>
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
                                        <select class="form-control select selectBox" name="payment_method">
                                            <option value="" disabled selected>All</option>
                                            <option value="Advance">Advance</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Card">Card</option>
                                            <option value="Cheque">Cheque</option>
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
                                                    <a href={{ route('sale-return/add') }}><button type="button"
                                                            class="btn btn-outline-info">
                                                            <i class="fas fa-plus px-2"> </i>Add
                                                        </button></a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="salesReturnTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Date</th>
                                                        <th>Invoice No.</th>
                                                        <th>Parent Sale</th>
                                                        <th>Customer Name</th>
                                                        <th>User</th>
                                                        <th>Location</th>
                                                        <th>Payment Status</th>
                                                        <th>Total Amount</th>
                                                        <th>Payment Due</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data will be populated by DataTables -->
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="6"></td>
                                                        <td>Total:</td>
                                                        <td>0</td>
                                                        <td>0</td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
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
        aria-hidden="true" data-sale-return-id="">
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
                            <div class="col-md-12 mt-4">
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
                    <button type="button" class="btn btn-secondary print-return-receipt">Print</button>
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
                    <h5 class="modal-title" id="paymentModalLabel">Add payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="saleReturnId" name="reference_id">
                        <input type="hidden" id="payment_type" name="payment_type">
                        <input type="hidden" id="customer_id" name="customer_id">
                        <input type="hidden" id="reference_no" name="reference_no">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Customer
                                        </h5>
                                        <p id="paymentCustomerDetail" style="margin: 0;"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Reference
                                            No</h5>
                                        <p id="paymentReferenceNo" style="margin: 0;"></p>
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Location
                                        </h5>
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
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total Paid
                                            Amount</h5>
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
                                    <input type="text" class="form-control" id="payAmount" name="amount">
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
                                    <input type="text" class="form-control" id="expiryYear" name="card_expiry_year">
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
                                    <input type="text" class="form-control" id="chequeNumber" name="cheque_number">
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
                                    <label for="cheque_received_date" class="form-label">Cheque Received Date</label>
                                    <input type="text" class="form-control datetimepicker" id="cheque_received_date"
                                        name="cheque_received_date">
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
                                    <label for="cheque_given_by" class="form-label">Cheque Given By</label>
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
                            <textarea class="form-control" id="paymentNote" name="payment_note"></textarea>
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
                            <strong>Sale Status:</strong> <span id="viewSaleStatus"></span><br>
                            <strong>Payment Status:</strong> <span id="viewPaymentStatus"></span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <button id="addPayment" class="btn btn-outline-primary">
                            <i class="fas fa-plus-circle"></i> Add payment
                        </button>
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

    @include('saleReturn.sale_return_ajax')
    <script>
        function togglePaymentFields() {
            const paymentMethod = document.getElementById('paymentMethod').value;
            const creditCardFields = document.getElementById('creditCardFields');
            const chequeFields = document.getElementById('chequeFields');
            const bankTransferFields = document.getElementById('bankTransferFields');

            creditCardFields.classList.add('d-none');
            chequeFields.classList.add('d-none');
            bankTransferFields.classList.add('d-none');

            // Clear all fields when changing payment method
            $('#cardNumber, #cardHolderName, #expiryMonth, #expiryYear, #securityCode, #chequeNumber, #bankBranch, #cheque_received_date, #cheque_valid_date, #cheque_given_by, #bankAccountNumber')
                .val('');
            $('.error').remove(); // Remove any existing error messages

            if (paymentMethod === 'card') {
                creditCardFields.classList.remove('d-none');
            } else if (paymentMethod === 'cheque') {
                chequeFields.classList.remove('d-none');
            } else if (paymentMethod === 'bank_transfer') {
                bankTransferFields.classList.remove('d-none');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const cardNumberInput = document.getElementById("cardNumber");
            const expiryMonthInput = document.getElementById("expiryMonth");
            const expiryYearInput = document.getElementById("expiryYear");
            const securityCodeInput = document.getElementById("securityCode");
            const chequeNumberInput = document.getElementById("chequeNumber");

            // Format card number (max 16 digits, spaced every 4)
            cardNumberInput.addEventListener("input", function(e) {
                let value = this.value.replace(/\D/g, "").substring(0, 16);
                value = value.replace(/(\d{4})/g, "$1 ").trim();
                this.value = value;
            });

            // Validate expiry month (only 1-12 allowed)
            expiryMonthInput.addEventListener("input", function() {
                let value = this.value.replace(/\D/g, "").substring(0, 2);
                let month = parseInt(value);
                if (month < 1 || month > 12) {
                    alert("Invalid month! Please enter a value between 1 and 12.");
                    this.value = "";
                } else {
                    this.value = value;
                }
            });

            // Validate security code (3 digits only)
            securityCodeInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, "").substring(0, 3);
            });

            // Validate cheque number (max 12 digits)
            chequeNumberInput.addEventListener("input", function() {
                this.value = this.value.replace(/\D/g, "").substring(0, 12);
            });
        });
    </script>
@endsection
