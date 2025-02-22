@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <style>
        .login-fields1, .login-fields2, .login-fields3, .hidden+.hidden2, .hiddenway_two_action {
            display: none;
        }
        /* Table Footer */
        .table-footer {
            text-align: right;
            margin-top: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .input-group-text, .form-select, .form-control {
            border-radius: 0;
        }
        .datatable {
            margin-top: 1rem;
        }
        .datatable th, .datatable td {
            text-align: center;
            vertical-align: middle;
        }
        .btn-outline-primary {
            border-radius: 0;
        }
        .container {
            padding: 2rem;
        }
        .card {
            margin-bottom: 1rem;
        }
        .card-header {
            background-color: #f8f9fa;
        }
        .card-body {
            padding: 1.5rem;
        }
        .page-header {
            margin-bottom: 1rem;
        }
        .page-title {
            margin: 0;
        }
    </style>

    <div class="container">
        <form id="addSalesForm">
            <input id="sale_id" name="sale_id" type="hidden">
            <div class="row">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-sm-12">
                            <div class="page-sub-header">
                                <h3 class="page-title">Add Sale</h3>
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="students.html">Sell</a></li>
                                    <li class="breadcrumb-item active">Add Sale</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <div class="input-group local-forms">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                    <select class="form-control form-select" id="location" name="location_id"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Customer and Sales Date --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <div class="input-group local-forms d-flex">
                            <select class="form-control form-select select2Box" id="customer-id" name="customer_id">
                                <option selected disabled>Customer*</option>
                            </select>
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addModal" id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group local-forms calendar-icon">
                        <label>Sales Date<span class="login-danger">*</span></label>
                        <input class="form-control datetimepicker" id="sales_date" name="sales_date" type="text" placeholder="DD-MM-YYYY">
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group local-forms">
                        <label>Status<span class="login-danger">*</span></label>
                        <select class="form-control form-select select" id="status" name="status">
                            <option selected disabled>Please Select </option>
                            <option value="Final">Final</option>
                            <option value="Draft">Draft</option>
                            <option value="Quotation">Quotation</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group local-forms">
                        <label>Invoice No<span class="login-danger"></span></label>
                        <input class="form-control" id="invoice_no" name="invoice_no" type="text" placeholder="Invoice No">
                        <input name="sale_type" type="text" value="Normal">
                    </div>
                </div>
            </div>

            {{-- Customer Details --}}
            <div class="row">
                <div class="col-lg-3 col-md-4 mb-3">
                    <div class="supplier-info p-3 border rounded">
                        <h6 class="mb-2">Customer Details</h6>
                        <p class="mb-0">
                            <span id="customer-name"></span><br>
                            <span id="customer-phone"></span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Product Search and Table --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row d-flex justify-content-center">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-search"></i></span>
                                                <input type="text" class="form-control" id="productSearchInput" placeholder="Enter Product Name / SKU / Scan bar code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="datatable table table-stripped" id="addSaleProduct">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Discount</th>
                                            <th>Price inc.Dis</th>
                                            <th>Subtotal</th>
                                            <th><i class="fas fa-window-close"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Products will be dynamically added here -->
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                            <div class="table-footer">
                                <p>Total Items: <span id="total-items">0</span></p>
                                <p>Net Total Amount: Rs.<span id="net-total-amount">0.00</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="input-group local-forms">
                                                <label>Discount Type<span class="login-danger">*</span></label>
                                                <select class="form-control form-select" id="discount_type" name="discount_type">
                                                    <option selected disabled>Discount Type*</option>
                                                    <option value="percentage">Percentage</option>
                                                    <option value="fixed">Fixed</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-exclamation"></i></span>
                                                <label>Discount Amount</label>
                                                <input class="form-control" id="discount_amount" name="discount_amount" type="text" placeholder="Discount Amount">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <b>Discount Amount:</b>
                                            <p id="discount_display">(-) Rs. 0.00</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Additional Notes</label>
                                            <textarea class="form-control" id="additional_notes" name="additional_notes" placeholder="Additional Notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row justify-content-end">
                                    <div class="col-4">
                                        <b>After Discount Total Amount</b>
                                        <p id="discount-net-total-amount">Rs. 0.00</p>
                                    </div>
                                </div>
                           </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Details --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <h5 class="mb-4">Add Payment</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="advance-payment">Paid Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-money-bill-alt"></i></span>
                                                <input type="text" class="form-control" placeholder="Paid Amount" id="paid-amount" name="total_paid">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment-date">Paid Date <span class="text-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text" name="payment_date" id="payment-date" placeholder="DD-MM-YYYY">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment-method">Payment Method</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <select class="form-control form-select" id="payment-method" name="payment_mode">
                                                    <option selected disabled>Payment Method</option>
                                                    <option value="Cash">Cash</option>
                                                    <option value="Advance">Advance</option>
                                                    <option value="Cheque">Cheque</option>
                                                    <option value="Bank Transfer">Bank Transfer</option>
                                                    <option value="Card">Card</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="payment-account">Payment Account</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                <select class="form-control form-select" id="payment-account" name="payment_account">
                                                    <option selected disabled>Payment Account</option>
                                                    <option>None</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                     <!-- Cheque Details -->
                            <div class="payment-fields" id="cheque-details" style="display: none;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cheque-no">Cheque No.</label>
                                        <input type="text" class="form-control" id="cheque-no" name="cheque_number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cheque-date">Cheque Date</label>
                                        <input type="text" class="form-control" id="cheque-date" name="cheque_received_date">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="branch-name">Branch Name</label>
                                        <input type="text" class="form-control" id="branch-name" name="cheque_bank_branch">
                                    </div>
                                </div>
                            </div>

                            <!-- Card Details -->
                            <div class="payment-fields" id="card-details" style="display: none;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="card-number">Card Number</label>
                                        <input type="text" class="form-control" id="card-number" name="card_number">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="card-expiry">Card Expiry</label>
                                        <input type="text" class="form-control" id="card-expiry" name="card_expiry_month">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="card-expiry-year">Card Expiry Year</label>
                                        <input type="text" class="form-control" id="card-expiry-year" name="card_expiry_year">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="card-security-code">Card Security Code</label>
                                        <input type="text" class="form-control" id="card-security-code" name="card_security_code">
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Details -->
                            <div class="payment-fields" id="bank-details" style="display: none;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank-account">Bank Account No.</label>
                                        <input type="text" class="form-control" id="bank-account" name="bank_account">
                                    </div>
                                </div>
                            </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="payment-note">Payment Note</label>
                                            <textarea class="form-control" id="payment-note" name="payment_note" placeholder="Payment note" rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-4">
                                <div class="row justify-content-end">
                                    <div class="col-md-4 text-end">
                                        <b>Payment Due:</b>
                                        <p class="payment-due">Rs. 0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
    <!-- Include this modal in your HTML -->
    <div class="modal fade" id="confirmRemoveModal" tabindex="-1" aria-labelledby="confirmRemoveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmRemoveModalLabel">Confirm Remove</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove this product?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemoveButton">Remove</button>
                </div>
            </div>
        </div>
    </div>

    @include('sell.sales_ajax')
    @endsection

