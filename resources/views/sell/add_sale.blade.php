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
                            <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                data-bs-target="#addModal" id="button-addon1"><i class="fas fa-plus-circle"></i>
                            </button>
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
                                            <p>(-) Rs. 0.00</p>
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
                           </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- Additional Details --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row">


                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Details</label>
                                            <textarea class="form-control" id="shipping_details" name="shipping_details" placeholder="Shipping Details"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Address</label>
                                            <textarea class="form-control" id="shipping_address" name="shipping_address" placeholder="Shipping Address"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Charges</label>
                                            <input class="form-control" id="shipping_charges" name="shipping_charges" type="text" placeholder="Shipping Charges">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Status</label>
                                            <select class="form-control form-select" id="shipping_status" name="shipping_status">
                                                <option selected disabled>Please Select </option>
                                                <option value="Ordered">Ordered</option>
                                                <option value="Packed">Packed</option>
                                                <option value="Shipped">Shipped</option>
                                                <option value="Delivered">Delivered</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Delivered To</label>
                                            <input class="form-control" id="delivered_to" name="delivered_to" type="text" placeholder="Delivered To">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Delivery Person</label>
                                            <select class="form-control form-select select2" id="delivery_person" name="delivery_person">
                                                <option selected disabled>Please Select </option>
                                                <option value="Mr Admin">Mr Admin</option>
                                                <option value="Mr Demo Cashier">Mr Demo Cashier</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Attach Document</label>
                                            <div class="invoices-upload-btn">
                                                <input type="file" accept="image/*" name="image" id="file" class="hide-input">
                                                <label for="file" class="upload"><i class="far fa-folder-open">&nbsp;</i> Browse..</label>
                                            </div>
                                            <span>Max File size: 5MB Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</span>
                                        </div>
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
                                <h5>Add Payment</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group local-forms">
                                            <label>Advance Balance</label>
                                            <input type="text" class="form-control" id="advance_balance" name="advance_balance" placeholder="Advance Balance">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group local-forms calendar-icon">
                                            <label>Purchase Date<span class="login-danger">*</span></label>
                                            <input class="form-control datetimepicker" id="purchase_date" name="purchase_date" type="text" placeholder="DD-MM-YYYY">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group local-forms">
                                            <label>Payment Method</label>
                                            <select class="form-control form-select" id="payment_method" name="payment_method">
                                                <option selected disabled>Payment Method</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Advance">Advance</option>
                                                <option value="Cheque">Cheque</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="Other">Other</option>
                                                <option value="Custom Payment 1">Custom Payment 1</option>
                                                <option value="Custom Payment 2">Custom Payment 2</option>
                                                <option value="Custom Payment 3">Custom Payment 3</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group local-forms">
                                            <label>Payment Account</label>
                                            <select class="form-control form-select" id="payment_account" name="payment_account">
                                                <option selected disabled>Payment Account</option>
                                                <option value="None">None</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group local-forms">
                                            <label>Payment Note</label>
                                            <textarea class="form-control" id="payment_note" name="payment_note" placeholder="Payment Note"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row justify-content-start">
                                    <div class="col-4">
                                        <b>Change Return</b>
                                        <p>0.00</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row justify-content-end">
                                    <div class="col-4">
                                        <b>Balance</b>
                                        <p>0.00</p>
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

    @include('sell.sales_ajax')
@endsection
