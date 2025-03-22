@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <style>
            /* Compact and Neat Table Styles */
            .table-container {
                width: 100%;
                overflow-x: auto;
                margin-bottom: 2px;
                font-family: Arial, sans-serif;
            }

            .datatable {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #ddd;
                font-size: 12px;
                /* Reduced font size for compactness */
            }

            .datatable th,
            .datatable td {
                padding: 2px 2px;
                /* Reduced padding for compactness */
                text-align: center;
                vertical-align: middle;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .datatable th {
                background-color: #4CAF50;
                color: white;
                font-weight: bold;
                min-width: 50px;
                font-size: 12px;
                /* Reduced font size for header */
                line-height: 1;
                /* Reduced line height for compactness */
            }

            .datatable tbody tr {
                border-bottom: 1px solid #ddd;
                height: 20px;
                /* Reduced row height */
            }

            .datatable tbody tr:last-child {
                border-bottom: none;
            }

            .datatable tbody tr:hover {
                background-color: #f9f9f9;
            }

            .datatable input[type="number"],
            .datatable input[type="text"],
            .datatable select {
                width: 80px;
                /* Adjusted width for inputs */
                height: 30px;
                /* Reduced input height */
                text-align: center;
                padding: 1px;
                /* Reduced padding */
                border: 1px solid #ccc;
                border-radius: 2px;
                font-size: 12px;
                /* Reduced font size for inputs */
            }

            .datatable input[type="date"] {
                width: 90px;
                /* Adjusted width for inputs */
                height: 30px;
                /* Reduced input height */
                text-align: center;
                padding: 1px;
                /* Reduced padding */
                border: 1px solid #ccc;
                border-radius: 2px;
                font-size: 12px;
                /* Reduced font size for inputs */
            }

            .datatable .btn-danger {
                padding: 1px 3px;
                /* Reduced padding for buttons */
                background-color: #e74c3c;
                border: none;
                color: white;
                border-radius: 2px;
                font-size: 9px;
                /* Reduced font size for buttons */
                cursor: pointer;
                /* Added cursor pointer for better UX */
            }

            .datatable .btn-danger:hover {
                background-color: #c0392b;
            }

            .table-footer {
                text-align: right;
                margin-top: 5px;
                font-weight: bold;
                font-size: 12px;
                /* Reduced font size for footer */
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
                vertical-align: middle;
                /* To ensure text alignment doesn't look cramped */
            }

            th {
                background-color: #4CAF50;
                color: white;
            }

            .input-field {
                width: 100%;
                box-sizing: border-box;
            }

            .total {
                font-weight: bold;
                margin-top: 10px;
            }

            .fa-trash-alt {
                color: #ff0000;
                cursor: pointer;
            }

            .table-primary {
                background-color: #f2f2f2;
            }

            #purchase_product tbody td {
                vertical-align: middle;
            }

            #purchase_product input[type="number"],
            #purchase_product input[type="date"],
            #purchase_product input[type="text"] {
                width: 100%;
                box-sizing: border-box;
                padding: 5px;
            }

            #purchase_product .form-control {
                margin-bottom: 10px;
            }

            #purchase_product .text-muted {
                display: block;
                margin-top: 5px;
                font-size: 0.875em;
            }
        </style>

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Purchase</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                                <li class="breadcrumb-item active">Add Purchase</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <form id="purchaseForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <div class="row mb-4">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="supplier-id">Supplier <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-select selectBox" id="supplier-id" name="supplier_id">
                                                </select>
                                                <button type="button" class="btn btn-outline-info" id="addSupplierButton">
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                            </div>
                                            <span class="text-danger small" id="supplier_id_error"></span>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="reference-no">Reference No <i class="fas fa-exclamation-circle"
                                                    title="Keep blank, this will be auto-generated"></i></label>
                                            <input class="form-control" type="text" placeholder="Reference No"
                                                id="reference-no" name="reference_no">
                                            <span class="text-danger small" id="reference_no_error"></span>
                                        </div>

                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="purchase-date">Purchase Date <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text"
                                                placeholder="DD-MM-YYYY" id="purchase-date" name="purchase_date"
                                                value="{{ \Carbon\Carbon::now() }}">
                                            <span class="text-danger small" id="purchase_date_error"></span>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="purchase-status">Purchase Status <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-select" id="purchase-status" name="purchasing_status">
                                                    <option disabled>Please Select</option>
                                                    <option value="Received" selected>Received</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Ordered">Ordered</option>
                                                </select>
                                            </div>
                                            <span class="text-danger small" id="purchasing_status_error"></span>
                                        </div>
                                        
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="supplier-info p-3 border rounded">
                                                <h6 class="mb-2">Supplier Details</h6>
                                                <p class="mb-0">
                                                    <span id="supplier-name"></span><br>
                                                    <span id="supplier-phone"></span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="services">Business Location <span
                                                    class="login-danger">*</span></label>
                                            <div class="input-group">
                                                <select class="form-control form-select" data-role="tagsinput"
                                                    id="services" name="location_id">
                                                    <option selected disabled></option>
                                                </select>
                                            </div>
                                            <span class="text-danger" id="location_id_error"></span>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="duration">Duration</label>
                                            <input class="form-control" id="duration" name="pay_term" type="number"
                                                placeholder="Enter Duration">
                                            <span class="text-danger" id="duration_error"></span>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="period">Period</label>
                                            <div class="input-group">
                                                <select class="form-control" id="period" name="pay_term_type">
                                                    <option selected disabled>Please Select</option>
                                                    <option value="days">days</option>
                                                    <option value="months">months</option>
                                                </select>
                                            </div>
                                            <span class="text-danger" id="duration_type_error"></span>
                                        </div>
                                    </div>

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
                                        <div class="ui-widget col-md-8">
                                            <div class="mb-3">
                                                <div class="input-group">
                                                    <span class="input-group-text" id="basic-addon1"><i
                                                            class="fas fa-search"></i></span>
                                                    <input type="text" id="productSearchInput" class="form-control"
                                                        placeholder="Enter Product Name / SKU / Scan bar code"
                                                        aria-label="Search">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#new_purchase_product">Add New Product</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive table-container">
                                    <table class="datatable no-footer table table-hover table-striped"
                                        id="purchase_product" role="grid" style="width:100%">
                                        <thead>
                                            <tr class="table-primary">
                                                <th>#</th>
                                                <th>Product<br>Name</th>
                                                <th>Purchase<br>Quantity</th>
                                                <th>Unit Cost<br>(Before Discount)</th>
                                                <th>Discount<br>Percent</th>
                                                <th>Unit<br>Cost</th>
                                                <th>Sub<br>Total</th>
                                                <th>Special<br>Price</th>
                                                <th>Wholesale<br>Price</th>
                                                <th>Max Retail<br>Price</th>
                                                <th>Profit<br>Margin%</th>
                                                <th>Retail<br>Price</th>
                                                <th>Expiry<br>Date</th>
                                                <th>Batch</th>
                                                <th><i class="fas fa-trash-alt"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="table-footer">
                                    <p>Total Items: <span id="total-items">0.00</span></p>
                                    <p>Net Total Amount: Rs<span id="net-total-amount">0.00</span></p>
                                    <input class="form-control" type="hidden" id="total" name="total"
                                        placeholder="Total">
                                    <input class="form-control" type="hidden" id="final-total" name="final_total"
                                        placeholder="Final Total">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6 col-md-12">
                                        <div class="document-upload p-3 border rounded">
                                            <label class="mb-2">Attach document</label>
                                            <div class="invoices-upload-btn mb-2">
                                                <input type="file" accept=".pdf,image/*" name="attached_document"
                                                    id="purchase_attach_document" class="hide-input show-file">
                                                <label for="purchase_attach_document"
                                                    class="upload btn btn-outline-secondary">
                                                    <i class="far fa-folder-open"></i> Browse..
                                                </label>
                                            </div>
                                            <small class="text-muted d-block">
                                                Max File size: 5MB<br>
                                                Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12">
                                        <div class="preview-container p-3 border rounded">
                                            <img id="selectedImage" src="/assets/img/No Product Image Available.png"
                                                alt="Selected Image" class="img-thumbnail mb-2"
                                                style="max-width: 200px; display: block;">
                                            <iframe id="pdfViewer" width="100%" height="200px"
                                                style="display: none;"></iframe>
                                        </div>
                                    </div>
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
                                                <div class="form-group local-forms days">
                                                    <label>Discount Type</label>
                                                    <select class="form-control form-select select" id="discount-type"
                                                        name="discount_type">
                                                        <option selected value="">None</option>
                                                        <option value="fixed">Fixed</option>
                                                        <option value="percent">Percentage</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Discount Amount<span class="login-danger"></span></label>
                                                    <input class="form-control" type="text" id="discount-amount"
                                                        name="discount_amount" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <b>Discount:</b>
                                                <p id="discount-display">(-) Rs 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mt-3">
                                                <div class="form-group local-forms days">
                                                    <label>Purchase Tax<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select" id="tax-type"
                                                        name="tax_type">
                                                        <option selected value="none">None</option>
                                                        <option value="vat10">VAT@10%</< /option>
                                                        <option value="cgst10">CGST@10%</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mt-3">
                                                <b>Purchase Tax:</b>
                                                <p id="tax-display">(+) Rs 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row d-flex justify-content-end">
                                        <div class="col-md-3">
                                            <div class="mt-3">
                                                <b>Purchase Total:</b>
                                                <p id="purchase-total">Rs 0.00</p>
                                                <input class="form-control" type="hidden" id="final-total"
                                                    name="final_total" placeholder="Final Total">
                                            </div>
                                        </div>
                                    </div>
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
                                    <h5 class="mb-4">Add Payment</h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="advance-payment">Paid Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-money-bill-alt"></i></span>
                                                    <input type="text" class="form-control" placeholder="Paid Amount"
                                                        id="paid-amount" name="paid_amount">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment-date">Paid Date <span
                                                        class="text-danger">*</span></label>
                                                <input class="form-control datetimepicker" type="text"
                                                    name="paid_date" id="payment-date" placeholder="DD-MM-YYYY">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment-method">Payment Method</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <select class="form-control form-select" id="payment-method"
                                                        name="payment_method"
                                                        onchange="togglePaymentFields('purchaseForm')">
                                                        <option value="cash" selected>Cash</option>
                                                        <option value="card">Credit Card</option>
                                                        <option value="cheque">Cheque</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="payment-account">Payment Account</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <select class="form-control form-select" id="payment-account"
                                                        name="payment_account">
                                                        <option selected disabled>Payment Account</option>
                                                        <option>None</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Conditional Payment Fields -->
                                        <div id="creditCardFields" class="row mb-3 d-none">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="cardNumber" class="form-label">Card Number</label>
                                                    <input type="text" class="form-control" id="cardNumber"
                                                        name="card_number">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="cardHolderName" class="form-label">Card Holder
                                                        Name</label>
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
                                                    <label for="cheque_received_date" class="form-label">Check Received
                                                        Date</label>
                                                    <input type="text" class="form-control datetimepicker"
                                                        id="cheque_received_date" name="cheque_received_date">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cheque_valid_date" class="form-label">Cheque Valid
                                                        Date</label>
                                                    <input type="text" class="form-control datetimepicker"
                                                        id="cheque_valid_date" name="cheque_valid_date">
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
                                                    <label for="bankAccountNumber" class="form-label">Bank Account
                                                        Number</label>
                                                    <input type="text" class="form-control" id="bankAccountNumber"
                                                        name="bank_account_number">
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
                                            <p class="payment-due"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center mt-2">
                    <button class="btn btn-primary" type="submit" id="purchaseButton"
                        style="width: auto;">Save</button>
                </div>
            </form>
        </div>

        @include('product.add_product_modal')
        @include('product.product_ajax')
        @include('purchase.purchase_ajax')
        @include('contact.supplier.supplier_ajax')
        @include('contact.supplier.add_supplier_modal')
        @include('brand.brand_modal')
        @include('brand.brand_ajax')
        @include('unit.unit_modal')
        @include('unit.unit_ajax')
        @include('category.main_category.main_category_ajax')
        @include('category.main_category.main_category_modal')
        @include('category.sub_category.sub_category_modal')
        @include('category.sub_category.sub_category_ajax')

        <script>
              $(document).ready(function() {
        $('#payment-method').on('change', function() {
            togglePaymentFields();
        });

        function togglePaymentFields() {
            const paymentMethod = $('#payment-method').val();
            if (paymentMethod === 'card') {
                $('#creditCardFields').removeClass('d-none');
                $('#chequeFields').addClass('d-none');
                $('#bankTransferFields').addClass('d-none');
            } else if (paymentMethod === 'cheque') {
                $('#creditCardFields').addClass('d-none');
                $('#chequeFields').removeClass('d-none');
                $('#bankTransferFields').addClass('d-none');
            } else if (paymentMethod === 'bank_transfer') {
                $('#creditCardFields').addClass('d-none');
                $('#chequeFields').addClass('d-none');
                $('#bankTransferFields').removeClass('d-none');
            } else {
                $('#creditCardFields').addClass('d-none');
                $('#chequeFields').addClass('d-none');
                $('#bankTransferFields').addClass('d-none');
            }
        }
    });
        </script>
    @endsection
