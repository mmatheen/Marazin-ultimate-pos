@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <style>
            .login-fields1 {
                display: none;
            }

            .login-fields2 {
                display: none;
            }

            .login-fields3 {
                display: none;
            }

            .hidden {
                display: none;
            }

            .hiddenway_two_action {
                display: none;
            }

            .modal-xl {
                max-width: 90%;
            }

            .modal-body {
                height: 75vh;
                overflow-y: auto;
            }

            @media print {
                .modal-dialog {
                    max-width: 100%;
                    margin: 0;
                    padding: 0;
                }

                .modal-content {
                    border: none;
                }

                .modal-body {
                    height: auto;
                    overflow: visible;
                }

                body * {
                    visibility: hidden;
                }

                .modal-content,
                .modal-content * {
                    visibility: visible;
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
        </style>
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Purchases</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Purchases</li>
                                <li class="breadcrumb-item active">List Purchases </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <p>
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample"
                    aria-expanded="false" aria-controls="collapseExample">
                    <i class="fas fa-filter"></i> Filters
                </button>
            </p>
            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Business Location <span class="login-danger"></span></label>
                                        <select class="form-control selectBox" id="locationFilter">
                                            <option value="">All Locations</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Supplier <span class="login-danger"></span></label>
                                        <select class="form-control selectBox" id="supplierFilter">
                                            <option value="">All Suppliers</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Purchase Status<span class="login-danger"></span></label>
                                        <select class="form-control selectBox" id="purchaseStatusFilter">
                                            <option value="">All</option>
                                            <option value="received">Received</option>
                                            <option value="pending">Pending</option>
                                            <option value="ordered">Ordered</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status <span class="login-danger"></span></label>
                                        <select class="form-control selectBox" id="paymentStatusFilter">
                                            <option value="">All</option>
                                            <option value="paid">Paid</option>
                                            <option value="due">Due</option>
                                            <option value="partial">Partial</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->

                                @can('create purchase')
                                <a href="{{ route('add-purchase') }}"><button type="button"
                                    class="btn btn-outline-info">
                                    <i class="fas fa-plus px-2"> </i>Add
                                </button></a>
                                @endcan

                                </div>

                            </div>
                        </div>



                    <div class="table-responsive">
                        <div class="mb-3">
                            <button id="bulkPaymentBtn" class="btn btn-primary">
                                Add Bulk Payment
                            </button>
                        </div>
                        <table class="datatable table table-stripped" style="width:100%" id="purchase-list">
                            <thead>
                                <tr>

                                    <th>Action</th>
                                    <th>Date</th>
                                    <th>Reference No</th>
                                    <th>Location</th>
                                    <th>Supplier</th>
                                    <th>Purchase Status</th>
                                    <th>Payment Status</th>
                                    <th>Grand Total</th>
                                    <th>Payment Due</th>
                                    <th>Added By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be dynamically added here -->
                            </tbody>
                        </table>
                        <!-- Add this button above your table, it will be hidden by default -->

                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="viewPurchaseProductModal" tabindex="-1" aria-labelledby="viewPurchaseProductModalLabel"
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
                                    <h5>Supplier:</h5>
                                    <p id="supplierDetails"></p>
                                </div>
                                <div class="col-md-4">
                                    <h5>Location:</h5>
                                    <p id="locationDetails"></p>
                                </div>
                                <div class="col-md-4">
                                    <h5>Purchase Details:</h5>
                                    <p id="purchaseDetails"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mt-5">
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
                                <div class="col-md-6 mt-3">
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
                                <div class="col-md-6 mt-3">
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

    <!-- Modal -->
    <div class="modal fade" id="viewPurchaseReturnProductModal" tabindex="-1" aria-labelledby="viewPurchaseReturnProductModalLabel"
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
                                <h5>Supplier:</h5>
                                <p id="supplierDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Location:</h5>
                                <p id="locationDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Purchase Details:</h5>
                                <p id="purchaseDetails"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mt-5">
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
                            <div class="col-md-6 mt-3">
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
                            <div class="col-md-6 mt-3">
                                <h5>Amount Details:</h5>
                                <table class="table" id="amountDetailsTable">
                                    <tbody>
                                        <!-- Amount details will be inserted here dynamically -->
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
                    <h5 class="modal-title" id="paymentModalLabel">Add payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="purchaseId" name="reference_id">
                        <input type="hidden" id="payment_type" name="payment_type">
                        <input type="hidden" id="supplier_id" name="supplier_id">
                        <input type="hidden" id="reference_no" name="reference_no">

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Supplier
                                        </h5>
                                        <p id="paymentSupplierDetail" style="margin: 0;"></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                    <div class="card-body" style="padding: 0.75rem;">
                                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Reference
                                            No</h5>
                                        <p id="referenceNo" style="margin: 0;"></p>
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
                                    <label for="cheque_received_date" class="form-label">Check Recieved Date</label>
                                    <input type="text" class="form-control datetimepicker" id="cheque_received_date"
                                        name="cheque_received_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cheque_valid_date" class="form-label">cheque valid date</label>
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

    <div class="modal fade" id="viewPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPaymentModalLabel">View Payments ( Reference No: <span
                            id="referenceNo">PO2018/0002</span> )</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Supplier:</strong>
                            <p id="viewSupplierDetail"></p>
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
                        <button class="btn btn-info"> <i class="fas fa-envelope"></i> Payment Paid Notification</button>
                        <button class="btn btn-outline-primary"
                            onclick="openPaymentModal(event, $('#viewPaymentModal').data('purchase-id'))"> <i
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
                    <h5 class="modal-title" id="bulkPaymentModalLabel">Supplier Bulk Payments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkPaymentForm">
                        <div class="card mb-4 shadow-sm rounded">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="supplierSelect">Select Supplier</label>
                                    <select id="supplierSelect" class="form-control select2Box">
                                        <option value="">Select Supplier</option>
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
                                            <strong>Total Purchase:</strong>
                                            <span id="totalPurchaseAmount" class="d-block mt-2">$0.00</span>
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

                                <div id="purchaseListContainer" class="mt-4">
                                    <table id="purchaseTable" class="table table-striped" style="margin-bottom: 70px; margin-top: 30px">
                                        <thead>
                                            <tr>
                                                <th>Purchase ID</th>
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
            // Toggle payment fields based on selected payment method
            $('#bulkPaymentModal #paymentMethod').change(function() {
                togglePaymentFields('bulkPaymentModal');
            });

            // Toggle payment fields based on selected payment method for individual payments
            $('#paymentMethod').change(function() {
                togglePaymentFields('paymentModal');
            });
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

    <!-- IMEI Management Modal -->
        <div class="modal fade" id="imeiManagementModal" tabindex="-1" aria-labelledby="imeiManagementModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imeiManagementModalLabel">Manage IMEI Numbers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div id="imeiPurchaseInfo" class="alert alert-info"></div>
                        </div>
                        <div id="imeiProductList">
                            <!-- Products with IMEI will be populated here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add IMEI Modal -->
        <div class="modal fade" id="addImeiModal" tabindex="-1" aria-labelledby="addImeiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addImeiModalLabel">Add IMEI Numbers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <div id="addImeiProductInfo" class="alert alert-info"></div>
                        </div>
                        
                        <input type="hidden" id="addImeiPurchaseProductId">
                        
                        <div class="mb-3">
                            <label for="imeiInputMethod" class="form-label">Input Method</label>
                            <select class="form-select" id="imeiInputMethod">
                                <option value="individual">Individual Entry</option>
                                <option value="bulk">Bulk Entry</option>
                            </select>
                        </div>

                        <!-- Individual IMEI Entry -->
                        <div id="individualImeiContainer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Enter IMEI Numbers Individually</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="addMoreImeiRows">
                                    <i class="fas fa-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm" id="individualImeiTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>IMEI Number</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- IMEI input rows will be added here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bulk IMEI Entry -->
                        <div id="bulkImeiContainer" style="display: none;">
                            <h6>Bulk IMEI Entry</h6>
                            <div class="mb-3">
                                <label for="bulkImeiSeparator" class="form-label">Separator</label>
                                <select class="form-select" id="bulkImeiSeparator">
                                    <option value="newline">New Line</option>
                                    <option value="comma">Comma (,)</option>
                                    <option value="semicolon">Semicolon (;)</option>
                                    <option value="tab">Tab</option>
                                    <option value="space">Space</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="bulkImeiText" class="form-label">IMEI Numbers</label>
                                <textarea class="form-control" id="bulkImeiText" rows="8" placeholder="Enter IMEI numbers separated by the selected separator..."></textarea>
                                <div class="form-text">Enter multiple IMEI numbers separated by the selected separator.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveImeiNumbers">
                            <i class="fas fa-save"></i> Save IMEI Numbers
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Existing IMEI Modal -->
        <div class="modal fade" id="viewImeiModal" tabindex="-1" aria-labelledby="viewImeiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewImeiModalLabel">View IMEI Numbers</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="existingImeiTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>IMEI Number</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Existing IMEI numbers will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Update Modal -->
        <div class="modal fade" id="confirmUpdateImeiModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Confirm Update</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-3">
                <i class="fas fa-edit text-primary mb-2" style="font-size: 1.2rem;"></i>
                <p id="confirmUpdateImeiText" class="mb-0">Update IMEI number?</p>
                </div>
                <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="performImeiUpdate()">Update</button>
                </div>
            </div>
            </div>
        </div>

    @include('purchase.purchase_ajax')
@endsection
