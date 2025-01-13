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

            .hidden+.hidden2 {
                display: none;
            }

            .hiddenway_two_action {
                display: none;
            }

            /* Overall Table Container */
            .table-container {
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                margin-bottom: 10px;
                /* Reduced bottom margin */
                font-family: Arial, sans-serif;
            }

            /* Table Styling */
            .datatable {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #ddd;
                font-size: 12px;
            }

            .datatable th,
            .datatable td {
                padding: 2px 4px;
                /* Minimal padding for compact rows */
                text-align: center;
                vertical-align: middle;
                white-space: nowrap;
                /* Prevent text wrapping */
                overflow: hidden;
                /* Hide overflow */
                text-overflow: ellipsis;
                /* Ellipsis for overflowed text */
            }

            .datatable th {
                background-color: #4CAF50;
                color: white;
                font-weight: bold;
                text-align: center;
                min-width: 70px;
                /* Reduce minimum width for tighter columns */
                padding: 2px 4px;
                /* Reduced padding for header cells */
                font-size: 11px;
                /* Reduced font size for compact headers */
                line-height: 1;
                /* Compact line spacing for headers */
            }

            .datatable tbody tr {
                border-bottom: 1px solid #ddd;
            }

            .datatable tbody tr:last-child {
                border-bottom: none;
            }

            /* Table Row on Hover */
            .datatable tbody tr:hover {
                background-color: #f1f1f1;
            }

            /* Input fields inside the table */
            .datatable input[type="number"],
            .datatable select {
                width: 60px;
                /* Compact input fields */
                height: 24px;
                /* Reduced height */
                text-align: center;
                padding: 2px;
                border: 1px solid #ccc;
                border-radius: 2px;
                /* Slightly rounded corners */
                font-size: 11px;
            }

            /* Delete Button */
            .datatable .btn-danger {
                padding: 2px 4px;
                background-color: #e74c3c;
                border: none;
                color: white;
                border-radius: 2px;
                font-size: 11px;
            }

            .datatable .btn-danger:hover {
                background-color: #c0392b;
            }

            /* Table Footer */
            .table-footer {
                text-align: right;
                margin-top: 10px;
                /* Reduced margin for compact layout */
                font-weight: bold;
                font-size: 12px;
                /* Reduced footer font size */
            }
        </style>
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Purchase </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                                <li class="breadcrumb-item active">Add Purchase </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}

        <div class="container-fluid">
            <form id="purchaseForm">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <!-- First Row -->
                                    <div class="row mb-4">
                                        <!-- Supplier Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="supplier-id">Supplier <span class="text-danger">*</span></label>
                                            <div class="input-group d-flex">
                                                <select class="form-select select2Box" id="supplier-id" name="supplier_id">
                                                </select>
                                                <button type="button" class="btn btn-outline-info" id="addSupplierButton">
                                                    <i class="fas fa-plus-circle"></i>
                                                </button>
                                            </div>
                                            <span class="text-danger small" id="supplier_id_error"></span>
                                        </div>

                                        <!-- Reference No Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="reference-no">Reference No <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control" type="text" placeholder="Reference No"
                                                id="reference-no" name="reference_no">
                                            <span class="text-danger small" id="reference_no_error"></span>
                                        </div>

                                        <!-- Purchase Date Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="purchase-date">Purchase Date <span
                                                    class="text-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text"
                                                placeholder="DD-MM-YYYY" id="purchase-date" name="purchase_date">
                                            <span class="text-danger small" id="purchase_date_error"></span>
                                        </div>

                                        <!-- Purchase Status Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="purchase-status">Purchase Status <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="purchase-status" name="purchase_status">
                                                <option selected disabled>Please Select</option>
                                                <option>Received</option>
                                                <option>Pending</option>
                                                <option>Ordered</option>
                                            </select>
                                            <span class="text-danger small" id="purchase_status_error"></span>
                                        </div>
                                    </div>

                                    <!-- Second Row -->
                                    <div class="row mb-4">
                                        <!-- Supplier Details -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <div class="supplier-info p-3 border rounded">
                                                <h6 class="mb-2">Supplier Details</h6>
                                                <p class="mb-0">
                                                    <span id="supplier-name"></span><br>
                                                    <span id="supplier-phone"></span>
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Business Location Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="services">Business Location <span
                                                    class="login-danger">*</span></label>
                                            <select class="form-control form-select" data-role="tagsinput" id="services"
                                                name="services">
                                                <option selected disabled></option>
                                            </select>
                                            <span class="text-danger" id="business_location_error"></span>
                                        </div>

                                        <!-- Duration Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="duration">Duration <span class="login-danger">*</span></label>
                                            <input class="form-control" id="duration" name="duration" type="number"
                                                placeholder="Enter Duration">
                                            <span class="text-danger" id="duration_error"></span>
                                        </div>

                                        <!-- Period Field -->
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="period">Period <span class="login-danger">*</span></label>
                                            <select class="form-control" id="period" name="duration_type">
                                                <option selected disabled>Please Select</option>
                                                <option>days</option>
                                                <option>months</option>
                                                <option>years</option>
                                            </select>
                                            <span class="text-danger" id="duration_type_error"></span>
                                        </div>
                                    </div>

                                    <!-- Third Row - Document Upload -->
                                    <div class="row">
                                        <div class="col-lg-6 col-md-12">
                                            <div class="document-upload p-3 border rounded">
                                                <label class="mb-2">Attach document</label>
                                                <div class="invoices-upload-btn mb-2">
                                                    <input type="file" accept=".pdf,image/*" name="purchase_attach_document"
                                                        id="purchase_attach_document" class="hide-input show-file">
                                                    <label for="purchase_attach_document" class="upload btn btn-outline-secondary">
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
                </div>

                <!-- Product Table Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#ImportProduct">Import Products</button>
                                            </div>
                                        </div>

                                        <div class="ui-widget col-md-5">
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
                                                <th>Product Name</th>
                                                <th>Purchase <br>Quantity</th>
                                                <th>Unit Cost <br> (Before <br> Discount)</th>
                                                <th>Discount <br>Percent</th>
                                                <th>Unit Cost </th>
                                                <th>reatil Cost </th>
                                                <th>wholesale Cost </th>
                                                <th>Special Cost </th>
                                                <th>Maximum Reatiol </th>
                                                <th>Sub Total </th>

                                                <th>Profit <br>Margin%</th>
                                                <th>Batch</th>
                                                <th><i class="fas fa-trash-alt"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <hr>
                                <div class="table-footer">
                                    <p>Total Items: <span id="total-items">1.00</span></p>
                                    <p>Net Total Amount: $<span id="net-total-amount">120.00</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discount and Tax Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <!-- Discount Section -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms days">
                                                    <label>Discount Type<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select select" id="discount-type"
                                                        name="discount_type">
                                                        <option selected value="none">None</option>
                                                        <option value="fixed">Fixed</option>
                                                        <option value="percentage">Percentage</option>
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

                                    <!-- Tax Section -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mt-3">
                                                <div class="form-group local-forms days">
                                                    <label>Purchase Tax<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select" id="tax-type"
                                                        name="tax_type">
                                                        <option selected value="none">None</option>
                                                        <option value="vat10">VAT@10%</option>
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

                                    <!-- Final Total Section -->
                                    <div class="row d-flex justify-content-end">
                                        <div class="col-md-3">
                                            <div class="mt-3">
                                                <b>Purchase Total:</b>
                                                <p id="purchase-total">Rs 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <h5>Add Payment</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-money-bill-alt"></i></span>
                                                <input type="text" class="form-control" placeholder="Advance Balance"
                                                    id="advance-payment" name="advance_balance"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group local-forms calendar-icon">
                                                <label>Purchase Date<span class="login-danger">*</span></label>
                                                <input class="form-control datetimepicker" type="text"
                                                    name="paid_date" id="payment-date" placeholder="DD-MM-YYYY">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <span class="input-group-text" id="basic-addon1"><i
                                                            class="fas fa-user"></i></span>
                                                    <select class="form-control form-select" id="payment-method"
                                                        name="payment_method" aria-label="Example text with button addon"
                                                        aria-describedby="button-addon1">
                                                        <option selected disabled>Payment Method</option>
                                                        <option>Cash</option>
                                                        <option>Advance</option>
                                                        <option>Cheque</option>
                                                        <option>Bank Transfer</option>
                                                        <option>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-user"></i></span>
                                                <select class="form-control form-select" id="payment-account"
                                                    name="payment_account" aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                                    <option selected disabled>Payment Account</option>
                                                    <option>None</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mt-4">
                                                <div class="form-group local-forms">
                                                    <label>Payment note<span class="login-danger"></span></label>
                                                    <textarea class="form-control" id="payment-note" name="payment_note" type="text" placeholder="Payment note"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                    <div class="row justify-content-end">
                                        <div class="col-4 text-end">
                                            <b>Payment Due:</b>
                                            <p class="payment-due">0.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row">
                    <button class="btn btn-primary btn-lg" type="submit" id="purchaseButton">Save</button>
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
    @endsection
