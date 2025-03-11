@extends('layout.layout')

@section('content')
<div class="container-fluid">
    <form id="bulkPaymentForm">
        <input id="sale_id" name="sale_id" type="hidden">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Purchase Payment</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Bulk payments</a></li>
                                <li class="breadcrumb-item active">Add Puchase Pyaments</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        <button type="button" id="submitBulkPayment" class="btn btn-primary">Submit Payment</button>
    </form>
</div>
@include('purchase.purchase_ajax')
@endsection