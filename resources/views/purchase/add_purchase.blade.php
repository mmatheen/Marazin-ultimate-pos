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

            /* Responsive Table for Small Screens */
            @media (max-width: 768px) {

                .datatable th,
                .datatable td {
                    padding: 2px 3px;
                    /* Further reduced padding for smaller screens */
                }

                .datatable th {
                    font-size: 10px;
                    /* Smaller font for compact headers */
                    min-width: 50px;
                    /* Reduced minimum width for tighter fit */
                }

                .datatable td {
                    font-size: 11px;
                    /* Ensure data is still readable */
                }

                .datatable input[type="number"],
                .datatable select {
                    width: 45px;
                    /* Smaller inputs for mobile screens */
                    font-size: 10px;
                }

                .table-footer {
                    font-size: 11px;
                }
            }

            /* Smaller footer text for compact view */
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
                                        <div class="input-group">
                                            {{-- <span class="input-group-text"><i class="fas fa-user"></i></span> --}}
                                            <select class="form-select select2Box text-truncate"
                                                style="max-width: calc(100% - 50px);" id="supplier-id" name="supplier_id">
                                            </select>
                                            <button
                                                class="btn btn-outline-primary d-flex align-items-center justify-content-center"
                                                type="button" data-bs-toggle="modal" data-bs-target="#addAndEditSupplierModal"
                                                style="width: 40px;">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </div>
                                        <span class="text-danger small" id="supplier_id_error"></span>
                                    </div>

                                    <!-- Reference No Field -->
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="reference-no">Reference No <span class="text-danger">*</span></label>
                                        <input class="form-control" type="text" placeholder="Reference No"
                                            id="reference-no" name="reference_no">
                                        <span class="text-danger small" id="reference_no_error"></span>
                                    </div>

                                    <!-- Purchase Date Field -->
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="purchase-date">Purchase Date <span class="text-danger">*</span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY"
                                            id="purchase-date" name="purchase_date">
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
                                        <label for="services">Business Location <span class="login-danger">*</span></label>
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
                                                <input type="file" accept=".pdf,image/*" name="attach_document"
                                                    id="attach_document" class="hide-input show-file">
                                                <label for="file" class="upload btn btn-outline-secondary">
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

                                    <!-- Add other elements if needed -->
                                </div>
                            </div>


                            <div class="table-responsive table-container">

                                <table class="datatable no-footer table table-hover table-striped" id="purchase_product"
                                    role="grid" style="width:100%">
                                    <thead>
                                        <tr class="table-primary">
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>Purchase <br>Quantity</th>
                                            <th>Unit Cost <br> (Before <br> Discount)</th>
                                            <th>Discount <br>Percent</th>
                                            <th>Unit Cost <br>(Before Tax)</th>
                                            <th>Sub Total <br>(Before Tax)</th>
                                            <th>Net Cost</th>
                                            <th>Line Total</th>
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
                                                <select class="form-control form-select select" id="discount-type">
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
                                                    placeholder="0">
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
                                                <select class="form-control form-select select" id="tax-type">
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
                                                id="advance-payment" aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group local-forms calendar-icon">
                                            <label>Purchase Date<span class="login-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text" id="payment-date"
                                                placeholder="DD-MM-YYYY">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-user"></i></span>
                                                <select class="form-control form-select" id="payment-method"
                                                    aria-label="Example text with button addon"
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
                                                aria-label="Example text with button addon"
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
                                                <textarea class="form-control" id="payment-note" name="description" type="text" placeholder="Payment note"></textarea>
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

            <div class="row">

                <button class="btn btn-primary btn-lg" type="submit" id="purchaseButton">Save</button>

            </div>

        </form>
{{-- Add/Edit modal row --}}
<div class="row">
    <div id="addAndEditSupplierModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center mt-2 mb-4">
                        <h5 id="modalTitle"></h5>
                    </div>
                    <form id="addAndUpdateForm">

                        <div class="row">
                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Prefix<span class="login-danger">*</span></label>
                                        <select class="form-control form-select" id="edit_prefix" name="prefix">
                                            <option selected disabled>Mr / Mrs / Miss</option>
                                            <option>Mr</option>
                                            <option>Mrs</option>
                                            <option>Ms</option>
                                            <option>Miss</option>
                                        </select>
                                        <span class="text-danger" id="prefix_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>First Name<span class="login-danger">*</span></label>
                                        <input class="form-control" id="edit_first_name" name="first_name" type="text" placeholder="First Name">
                                        <span class="text-danger" id="first_name_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Last Name<span class="login-danger"></span></label>
                                        <input class="form-control" id="edit_last_name" name="last_name" type="text" placeholder="Last Name">
                                        <span class="text-danger" id="last_name_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-mobile-alt"></i></span>
                                        <input type="text" class="form-control" id="edit_email" name="email" placeholder="Email" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="email_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-envelope"></i></span>
                                        <input type="text" class="form-control" id="edit_mobile_no" name="mobile_no" placeholder="Mobile" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="mobile_no_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" id="edit_contact_id" name="contact_id" placeholder="Contact ID" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="contact_id_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                        <select class="form-control form-select" id="edit_contact_type" name="contact_type" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                            <option selected disabled>Contact type</option>
                                            <option>Suppliers</option>
                                            <option>Customers</option>
                                            <option>Both (Supplier & Customer)</option>
                                        </select>
                                        <span class="text-danger" id="contact_type_error"></span>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date <span class="login-danger">*</span></label>
                                        <input class="form-control datetimepicker" id="edit_date" name="date" type="text" placeholder="DD-MM-YYYY">
                                        <span class="text-danger" id="date_error"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="mb-3 mt-3">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                        <select class="form-control form-select" id="edit_assign_to" name="assign_to" aria-label="Example text with button addon" aria-describedby="basic-addon1">
                                            <option selected disabled>Assigned to</option>
                                            <option>Mr SuperUser</option>
                                            <option>Mr Ahshan</option>
                                            <option>Mr Afshan</option>
                                        </select>
                                        <span class="text-danger" id="assign_to_error"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" id="edit_opening_balance" name="opening_balance" placeholder="Opening Balance" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                        <span class="text-danger" id="opening_balance_error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
        {{-- Edit modal row --}}

        {{-- Add modal row --}}
        <div class="row">
            <div class="modal fade" id="new_purchase_product" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add new product</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addPurchaseProductForm">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-table">
                                            <div class="card-body">
                                                <div class="page-header">
                                                    <div class="row align-items-center">

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="form-group local-forms">
                                                                        <label>Product Name <span
                                                                                class="login-danger">*</span></label>
                                                                        <input class="form-control" id="edit_product_name"
                                                                            name="product_name" type="text"
                                                                            placeholder="Product Name">
                                                                        <span class="text-danger"
                                                                            id="product_name_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="form-group local-forms">
                                                                        <label>Product SKU <span
                                                                                class="login-danger">*</span></label>
                                                                        <input class="form-control" id="edit_sku"
                                                                            name="sku" type="text"
                                                                            placeholder="Product SKU">
                                                                        <span class="text-danger" id="sku_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="input-group local-forms">
                                                                        <label>Product Unit <span
                                                                                class="login-danger">*</span></label>
                                                                        <select class="form-control form-select"
                                                                            id="edit_unit_id" name="unit_id">

                                                                        </select>
                                                                        <span class="text-danger"
                                                                            id="unit_id_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="input-group local-forms">
                                                                        <label>Product Brand <span
                                                                                class="login-danger">*</span></label>
                                                                        <select class="form-control form-select"
                                                                            id="edit_brand_id" name="brand_id">

                                                                        </select>
                                                                        <span class="text-danger"
                                                                            id="brand_id_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="input-group local-forms">
                                                                        <label>Main Category <span
                                                                                class="login-danger">*</span></label>
                                                                        <select class="form-control form-select"
                                                                            id="edit_main_category_id"
                                                                            name="main_category_id">

                                                                        </select>
                                                                        <span class="text-danger"
                                                                            id="main_category_id_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="mb-3">
                                                                    <div class="input-group local-forms">
                                                                        <label>Sub Category <span
                                                                                class="login-danger">*</span></label>
                                                                        <select class="form-control form-select"
                                                                            id="edit_sub_category_id"
                                                                            name="sub_category_id">

                                                                        </select>
                                                                        <span class="text-danger"
                                                                            id="sub_category_id_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4">
                                                                <div class="mb-3 mt-4">
                                                                    <div class="input-group local-forms">
                                                                        <label>Business Locations<span
                                                                                class="login-danger">*</span></label>
                                                                        <select class="form-control form-select multiple-location"
                                                                            id="edit_location_id" name="location_id[]" multiple>
                                                                            {{-- it will load dynamcaly using ajax --}}
                                                                        </select>
                                                                        <span class="text-danger"
                                                                            id="location_id_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <script>
                                                                $(document).ready(function() {
                                                                        $('#edit_location_id').select2({
                                                                            placeholder: 'Select Business Locations',
                                                                            allowClear: true,
                                                                            width: '100%' // Ensures the dropdown takes the full width of the container
                                                                        });
                                                                    });
                                                            </script>



                                                        </div>

                                                        <div class="row mt-3">
                                                            <div class="col-md-4">
                                                                <div class="mb-5">
                                                                    <div class="form-check">
                                                                        <input type="hidden" name="stock_alert"
                                                                            value="0">
                                                                        <input class="form-check-input" name="stock_alert"
                                                                            type="checkbox" id="edit_stock_alert" checked
                                                                            value="1">
                                                                        <label class="form-check-label"
                                                                            for="edit_stock_alert" id="stock_alert_label">
                                                                            Hide Alert Stock
                                                                        </label>
                                                                        <span class="text-danger"
                                                                            id="stock_alert_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-4" id="alert_quantity_container">
                                                                <div class="mb-5">
                                                                    <div class="form-group local-forms">
                                                                        <label>Alert Quantity<span
                                                                                class="login-danger"></span></label>
                                                                        <input class="form-control" type="number"
                                                                            id="edit_alert_quantity" name="alert_quantity"
                                                                            placeholder="0">
                                                                        <span class="text-danger"
                                                                            id="alert_quantity_error"></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <script>
                                                                $(document).ready(function() {
                                                                    // Set initial state when the page loads
                                                                    toggleAlertQuantity();

                                                                    // Event listener for the checkbox change
                                                                    $('#edit_stock_alert').change(function() {
                                                                        toggleAlertQuantity();
                                                                    });

                                                                    // Function to toggle the alert quantity field based on checkbox status
                                                                    function toggleAlertQuantity() {
                                                                        if ($('#edit_stock_alert').is(':checked')) {
                                                                            $('#alert_quantity_container').show(); // Show the alert quantity field when checkbox is checked
                                                                            $('#stock_alert_label').text('Show Alert Stock'); // Change label to "Hide Alert Stock"
                                                                        } else {
                                                                            $('#alert_quantity_container').hide(); // Hide the alert quantity field when unchecked
                                                                            $('#stock_alert_label').text('Hide Alert Stock'); // Change label to "Show Alert Stock"
                                                                        }
                                                                    }
                                                                });
                                                            </script>
                                                        </div>
                                                        <div class="row">

                                                            <div class="col-md-8">
                                                                <div id="summernote" name="description"></div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <label>Product image</label>
                                                                        <div class="invoices-upload-btn">
                                                                            <input type="file" accept="image/*"
                                                                                name="product_image" id="file"
                                                                                class="hide-input show-picture">
                                                                            <label for="file" class="upload"><i
                                                                                    class="far fa-folder-open">
                                                                                    &nbsp;</i> Browse..</label>
                                                                        </div>
                                                                        <span>Max File size: 5MB </span>
                                                                    </div>

                                                                    <div
                                                                        class="col-md-12 my-4 d-flex justify-content-center">
                                                                        <img id="selectedImage"
                                                                            src="/assets/img/No Product Image Available.png"
                                                                            alt="Selected Image" width="200px"
                                                                            class="img-thumbnail" height="200px">
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

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-table">
                                            <div class="card-body">
                                                <div class="page-header">
                                                    <div class="row align-items-center">
                                                        <div class="row mt-2">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <div class="form-check ms-3">
                                                                        <input type="hidden" name="is_imei_or_serial_no"
                                                                            value="0">
                                                                        <input class="form-check-input"
                                                                            name="is_imei_or_serial_no" type="checkbox"
                                                                            value="1"
                                                                            id="Enable_Product_description">
                                                                        <label class="form-check-label"
                                                                            for="Enable_Product_description">
                                                                            Enable Product description, IMEI or Serial
                                                                            Number
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <div class="form-check ms-3">
                                                                        <input type="hidden" name="is_for_selling"
                                                                            value="0">
                                                                        <input class="form-check-input"
                                                                            name="is_for_selling" type="checkbox"
                                                                            value="1" id="Not_for_selling">
                                                                        <label class="form-check-label"
                                                                            for="Not_for_selling">
                                                                            Not for selling
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-4">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <div class="input-group local-forms">
                                                                        <select class="form-control form-select select"
                                                                            name="product_type"
                                                                            aria-label="Example text with button addon"
                                                                            aria-describedby="button-addon1">
                                                                            <option selected disabled>Product Type</option>
                                                                            <option value="Box">Box</option>
                                                                            <option value="Bundle">Bundle</option>
                                                                            <option value="Case">Case</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Pax<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" name="pax"
                                                                                type="number" placeholder="0">
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
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-table">
                                            <div class="card-body">
                                                <div class="page-header">
                                                    <div class="row align-items-center">

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered">
                                                                        <thead class="table-success">
                                                                            <tr>
                                                                                <th scope="col">Cost Price</th>
                                                                                <th scope="col">Retail Price</th>
                                                                                <th scope="col">Whole Sale Price</th>
                                                                                <th scope="col">Special Price</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <div class="row">
                                                                                        <div class="col-md-12">
                                                                                            <input type="number"
                                                                                                id="edit_original_price"
                                                                                                name="original_price"
                                                                                                class="form-control"
                                                                                                placeholder="Rs .00">
                                                                                            <span class="text-danger"
                                                                                                id="original_price_error"></span>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>

                                                                                <td>
                                                                                    <div class="form-group">
                                                                                        <input type="number"
                                                                                            id="edit_retail_price"
                                                                                            name="retail_price"
                                                                                            class="form-control"
                                                                                            placeholder="Rs .00">
                                                                                        <span class="text-danger"
                                                                                            id="retail_price_error"></span>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="form-group">
                                                                                        <input type="number"
                                                                                            id="edit_whole_sale_price"
                                                                                            name="whole_sale_price"
                                                                                            class="form-control"
                                                                                            placeholder="Rs .00">
                                                                                        <span class="text-danger"
                                                                                            id="whole_sale_price_error"></span>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    <div class="form-group">
                                                                                        <input type="text"
                                                                                            id="edit_special_price"
                                                                                            name="special_price"
                                                                                            class="form-control"
                                                                                            placeholder="Rs .00">
                                                                                        <span class="text-danger"
                                                                                            id="special_price_error"></span>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
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


                                <div class="row mb-4">
                                    <div class="gap-4 d-flex justify-content-center">
                                        <div>
                                            <button type="submit" class="btn btn-outline-primary"
                                                id="onlySaveProductButton">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit modal row --}}

            {{-- modal --}}
            {{-- <div class="row">
                <div class="modal fade" id="ImportProduct" tabindex="-1" aria-labelledby="exampleModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="exampleModalLabel">Import Products</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Product image</label>
                                        <div class="invoices-upload-btn">
                                            <input type="file" accept="image/*" name="image" id="file"
                                                class="hide-input">
                                            <label for="file" class="upload"><i class="far fa-folder-open">
                                                    &nbsp;</i> Browse..</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-success mt-2"><i
                                                    class="fas fa-download"></i> &nbsp; Download template file</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card card-table">
                                            <div class="card-body">
                                                <div class="page-header">
                                                    <div class="row align-items-center">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <h5>Instructions</h5>
                                                                    <b>Follow the instructions carefully before importing
                                                                        the
                                                                        file.</b>
                                                                    <p>The columns of the file should be in the following
                                                                        order.
                                                                    </p>
                                                                </div>
                                                            </div>

                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless table-hover">
                                                                        <thead>
                                                                            <tr>
                                                                                <th scope="col">Column Number</th>
                                                                                <th scope="col">Column Name</th>
                                                                                <th scope="col">Instruction</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <th scope="row">1</th>
                                                                                <td>SKU (Required)</td>
                                                                                <td></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">2</th>
                                                                                <td>Purchase Quantity (Required)</td>
                                                                                <td></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">3</th>
                                                                                <td>Unit Cost (Before Discount) (Optional)
                                                                                </td>
                                                                                <td></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">4</th>
                                                                                <td>Discount Percent (Optional)</td>
                                                                                <td></td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">5</th>
                                                                                <td>Product Tax (Optional)</td>
                                                                                <td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">6</th>
                                                                                <td>Lot Number (Optional)</td>
                                                                                <td>Only if Lot number is enabled. You can
                                                                                    enable Lot number from
                                                                                    Business Settings > Purchases > Enable
                                                                                    Lot
                                                                                    number</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">7</th>
                                                                                <td>MFG Date (Optional)</td>
                                                                                <td>Only if Product Expiry is enabled. You
                                                                                    can
                                                                                    enable Product expiry from
                                                                                    Business Settings > Product > Enable
                                                                                    Product
                                                                                    Expiry
                                                                                    Format: yyyy-mm-dd; Ex: 2021-11-25</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th scope="row">8</th>
                                                                                <td>EXP Date (Optional)</td>
                                                                                <td>Only if Product Expiry is enabled. You
                                                                                    can
                                                                                    enable Product expiry from
                                                                                    Business Settings > Product > Enable
                                                                                    Product
                                                                                    Expiry
                                                                                    Format: yyyy-mm-dd; Ex: 2021-11-25</td>
                                                                            </tr>
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

                                <div class="row">

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Import</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> --}}
            <script>
                function toggleLoginFields(propertyId, actionClass, HidingClass) {
                    var checkBox = document.getElementById(propertyId);
                    var loginFields = document.querySelectorAll(actionClass);
                    var hideclassFiled = document.querySelectorAll(HidingClass);

                    loginFields.forEach(function(field) {
                        field.style.display = checkBox.checked ? "block" : "none";

                    });

                    hideclassFiled.forEach(function(field) {
                        fiel.style.display = 'none';
                    })
                }

                function toggleLoginFields2(propertyId, actionClass, displayClass) {
                    var checkBox = document.getElementById(propertyId);
                    var loginFields = document.querySelectorAll(actionClass);
                    var specificFieldVisible = document.querySelectorAll(displayClass);

                    loginFields.forEach(function(field) {
                        field.style.display = checkBox.checked ? "none" : "block";
                    });

                    specificFieldVisible.forEach(function(field) {
                        field.style.display = checkBox.checked ? "block" : "none";
                    })
                }
            </script>




            @include('purchase.purchase_ajax')

            @include('contact.supplier.supplier_ajax')
        @endsection
