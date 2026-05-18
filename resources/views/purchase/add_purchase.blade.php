@extends('layout.layout')
@section('content')
@php
    // Use values passed by controller; fall back to own computation if accessed directly.
    $canUseFreeQty            = $canUseFreeQty ?? ((bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && (auth()->user()?->can('create supplier claims') ?? false));
    $canReceiveSupplierClaims = $canReceiveSupplierClaims ?? ($canUseFreeQty && (auth()->user()?->can('receive supplier claims') ?? false));
    $canCreateSupplierClaims  = $canCreateSupplierClaims ?? $canUseFreeQty;
    $purchaseTaxRates         = \Illuminate\Support\Facades\Schema::hasTable('tax_rates')
        ? \App\Models\TaxRate::active()->orderBy('name')->get(['id', 'name', 'rate'])
        : collect();
    $canManageTax             = $canManageTax ?? \App\Support\TaxSettingsAccess::canManage();
    $purchaseTableColumns     = $purchaseTableColumns ?? \App\Support\PurchaseTableColumns::fromSetting($canUseFreeQty, $canManageTax);
@endphp
    <div class="content container-fluid">
        <style>
            /* Compact and Neat Table Styles */
            .table-container {
                width: 100%;
                overflow-x: auto;
                margin-bottom: 2px;
                font-family: Arial, sans-serif;
            }

            /* DataTables Scroll Container */
            .dataTables_wrapper {
                width: 100%;
                overflow-x: auto !important;
            }

            .dataTables_scroll {
                overflow-x: auto !important;
                width: 100% !important;
            }

            .dataTables_scrollBody {
                overflow-x: auto !important;
            }

            .datatable {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #ddd;
                font-size: 12px;
                /* Reduced font size for compactness */
            }

            #purchase_product th.purchase-col-hidden,
            #purchase_product td.purchase-col-hidden {
                display: none !important;
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

            /* IMEI Badge Styling */
            .badge {
                display: inline-block;
                padding: 0.25em 0.4em;
                font-size: 75%;
                font-weight: 700;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
                vertical-align: baseline;
                border-radius: 0.25rem;
            }

            .badge-info {
                color: #fff;
                background-color: #17a2b8;
            }

            .purchase-imei-input.is-invalid {
                border-color: #dc3545;
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

            /* Compact Add Purchase — less vertical space */
            .purchase-compact-card .card-body {
                padding: 0.75rem 1rem;
            }
            .purchase-compact-card .page-header {
                margin-bottom: 0;
            }
            .purchase-header-fields .mb-3,
            .purchase-header-fields .mb-4 {
                margin-bottom: 0.5rem !important;
            }
            .purchase-supplier-info {
                padding: 0.5rem 0.75rem !important;
            }
            .purchase-supplier-info h6 {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
            }
            .purchase-supplier-info p {
                font-size: 0.85rem;
            }
            #purchase-attachment-panel .preview-container {
                min-height: 0;
                padding: 0.5rem;
            }
            #purchase-preview-wrap {
                display: none;
            }
            #purchase-preview-wrap.has-preview {
                display: block;
            }
            #purchase-selectedImage {
                max-width: 100%;
                max-height: 100px;
                object-fit: contain;
            }
            #purchase-pdfViewer {
                height: 120px !important;
                min-height: 0;
            }
            .purchase-totals-inline .form-group label,
            .purchase-totals-inline label {
                font-size: 0.8rem;
                margin-bottom: 0.15rem;
            }
            .purchase-totals-inline .form-control,
            .purchase-totals-inline .form-select {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
            .purchase-summary-box {
                background: #f8f9fa;
                border-radius: 6px;
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            .purchase-summary-box p {
                margin-bottom: 0.15rem;
            }
        </style>

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Purchase</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Purchase</a></li>
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
                                                value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}">
                                            <span class="text-danger small" id="purchase_date_error"></span>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label for="purchase-status">Purchase Status <span
                                                    class="text-danger">*</span></label>
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
                                                <select class="form-select select2" id="services" name="location_id" style="width: 100%;">
                                                    <option value="" selected disabled>Select Location</option>
                                                </select>
                                            </div>
                                            <span class="text-danger" id="location_id_error"></span>
                                        </div>
                                        {{-- <div class="col-lg-3 col-md-6 mb-3">
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
                                        </div> --}}
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
                                        <div class="col-md-4 d-flex flex-wrap gap-2 align-items-start">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#new_purchase_product">Add New Product</button>
                                            @can('view settings')
                                                <a href="{{ route('settings.index') }}#purchase" class="btn btn-outline-secondary"
                                                    title="Configure which columns appear on this table (Business Settings)">
                                                    <i class="fas fa-columns me-1"></i> Column Settings
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive table-container">
                                    <table class="datatable no-footer table table-hover table-striped"
                                        id="purchase_product" data-dt-disable="true" role="grid" style="width:100%">
                                        <thead>
                                            <tr class="table-primary">
                                                <th class="purchase-col purchase-col-index">#</th>
                                                <th class="purchase-col purchase-col-product_name">Product<br>Name</th>
                                                <th class="purchase-col purchase-col-purchase_quantity">Purchase<br>Quantity</th>
                                                @if($canUseFreeQty)<th class="purchase-col purchase-col-free_qty">Free<br>Qty</th>@endif
                                                @if($canUseFreeQty)<th class="purchase-col purchase-col-claim_free_qty">Claim<br>Free Qty</th>@endif
                                                <th class="purchase-col purchase-col-unit_cost_before_discount">Unit Cost<br>(Before Discount)</th>
                                                <th class="purchase-col purchase-col-discount_percent">Discount<br>Percent</th>
                                                <th class="purchase-col purchase-col-unit_cost_after_discount">Unit Cost<br>(After Discount)</th>
                                                @if($canManageTax)
                                                <th class="purchase-col purchase-col-product_tax">Product<br>Tax</th>
                                                <th class="purchase-col purchase-col-tax_amount">Tax<br>Amount</th>
                                                @endif
                                                <th class="purchase-col purchase-col-line_total">Line Total<br>(Incl Tax)</th>
                                                <th class="purchase-col purchase-col-special_price">Special<br>Price</th>
                                                <th class="purchase-col purchase-col-wholesale_price">Wholesale<br>Price</th>
                                                <th class="purchase-col purchase-col-max_retail_price">Max Retail<br>Price</th>
                                                <th class="purchase-col purchase-col-profit_margin">Profit<br>Margin%</th>
                                                <th class="purchase-col purchase-col-retail_price">Retail<br>Price</th>
                                                <th class="purchase-col purchase-col-expiry_date">Expiry<br>Date</th>
                                                <th class="purchase-col purchase-col-batch">Batch</th>
                                                <th class="purchase-col purchase-col-actions"><i class="fas fa-trash-alt"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="table-footer">
                                    <p>Total Items: <span id="total-items">0.00</span></p>
                                    <p>{{ $canManageTax ? 'Subtotal (Before Tax):' : 'Subtotal:' }} Rs<span id="subtotal-amount">0.00</span></p>
                                    @if($canManageTax)
                                    <p>Total Tax: Rs<span id="total-tax-amount">0.00</span></p>
                                    @endif
                                    <p><strong>Net Total Amount: Rs<span id="net-total-amount">0.00</span></strong></p>
                                    <input class="form-control" type="hidden" id="total" name="total" placeholder="Total">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Compact: attachment (collapsed) + discount/tax/total --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table purchase-compact-card">
                            <div class="card-body py-2">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <span class="text-muted small mb-0">Discount, tax &amp; totals</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        id="purchase-toggle-attachment" aria-expanded="false"
                                        aria-controls="purchase-attachment-panel">
                                        <i class="fas fa-paperclip me-1"></i> Attach document
                                    </button>
                                </div>
                                <div id="purchase-attachment-panel" class="d-none border rounded p-2 mb-2 bg-light">
                                    <div class="row g-2 align-items-start">
                                        <div class="col-md-7">
                                            <input type="file"
                                                accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png,.gif"
                                                name="attached_document" id="purchase_attach_document"
                                                class="hide-input show-file">
                                            <label for="purchase_attach_document"
                                                class="upload btn btn-outline-secondary btn-sm me-1 mb-0">
                                                <i class="far fa-folder-open"></i> Browse
                                            </label>
                                            <button type="button" class="btn btn-outline-danger btn-sm clear-file-upload">
                                                <i class="fas fa-times"></i> Clear
                                            </button>
                                            <small class="text-muted d-block mt-1">Max 5MB — pdf, csv, zip, doc, images</small>
                                        </div>
                                        <div class="col-md-5" id="purchase-preview-wrap">
                                            <div class="preview-container text-center">
                                                <img id="purchase-selectedImage" src="" alt="Preview"
                                                    class="img-thumbnail d-none">
                                                <iframe id="purchase-pdfViewer" width="100%" height="120"
                                                    style="display: none; border: 1px solid #dee2e6; border-radius: 4px;"
                                                    frameborder="0"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 align-items-end purchase-totals-inline">
                                    <div class="col-6 col-md-2">
                                        <label class="small mb-0">Discount Type</label>
                                        <select class="form-select form-select-sm" id="discount-type" name="discount_type">
                                            <option selected value="">None</option>
                                            <option value="fixed">Fixed</option>
                                            <option value="percent">%</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="small mb-0">Discount Amount</label>
                                        <input class="form-control form-control-sm" type="text" id="discount-amount"
                                            name="discount_amount" placeholder="0">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <span class="small text-muted">Discount</span>
                                        <p class="mb-0 fw-semibold" id="discount-display">(-) Rs 0.00</p>
                                    </div>
                                    @if($canManageTax)
                                    <div class="col-6 col-md-2">
                                        <label class="small mb-0">Purchase Tax</label>
                                        <select class="form-select form-select-sm" id="tax-type" name="tax_type">
                                            <option selected value="">None</option>
                                            @foreach($purchaseTaxRates as $taxRate)
                                                <option value="{{ $taxRate->id }}" data-rate="{{ number_format((float)$taxRate->rate, 2, '.', '') }}">{{ $taxRate->name }}@{{ number_format((float)$taxRate->rate, 2) }}%</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <span class="small text-muted">Tax</span>
                                        <p class="mb-0" id="tax-display">(+) Rs 0.00</p>
                                    </div>
                                    @endif
                                    <div class="col-12 col-md-{{ $canManageTax ? '2' : '6' }} text-md-end">
                                        <div class="purchase-summary-box">
                                            <span class="small text-muted d-block">Purchase Total</span>
                                            <strong class="fs-5" id="purchase-total">Rs 0.00</strong>
                                            <input type="hidden" id="final-total" name="final_total">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table purchase-compact-card">
                            <div class="card-body py-2">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <span class="text-muted small mb-0">Payment (optional)</span>
                                    <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0"
                                        id="purchase-toggle-inline-payment" aria-expanded="false"
                                        aria-controls="purchase-inline-payment-fields">
                                        Show payment fields
                                    </button>
                                </div>
                                    <div id="purchase-inline-payment-fields" class="d-none">
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
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cardNumber" class="form-label">Card number</label>
                                                    <input type="text" class="form-control" id="cardNumber"
                                                        name="card_number" maxlength="23" autocomplete="off"
                                                        placeholder="Last 4 digits or full number">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="cardHolderName" class="form-label">Card holder name</label>
                                                    <input type="text" class="form-control" id="cardHolderName"
                                                        name="card_holder_name" autocomplete="name">
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



        {{-- Batch / expiry prompt when those columns are enabled in Purchase settings --}}
        <div class="modal fade" id="purchaseBatchExpiryModal" tabindex="-1" aria-labelledby="purchaseBatchExpiryModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="purchaseBatchExpiryModalLabel">Batch details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2"><strong id="purchaseBatchExpiryProductName"></strong></p>
                        <p class="text-muted small mb-3">Same batch + expiry → quantity increases on existing row. Different batch or expiry → new row (prices from that batch if it already exists).</p>
                        <div id="purchaseBatchExpiryExpiryWrap" class="mb-3">
                            <label for="purchaseBatchExpiryInput" class="form-label">Expiry date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="purchaseBatchExpiryInput">
                        </div>
                        <div id="purchaseBatchExpiryBatchWrap" class="mb-2">
                            <label for="purchaseBatchExpiryBatchInput" class="form-label">Batch no <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="purchaseBatchExpiryBatchInput" list="purchaseBatchExpiryBatchList"
                                placeholder="Type or pick existing batch" autocomplete="off">
                            <datalist id="purchaseBatchExpiryBatchList"></datalist>
                        </div>
                        <small id="purchaseBatchExpiryPriceHint" class="text-muted d-block"></small>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary btn-sm" id="purchaseBatchExpiryConfirm">Add to purchase</button>
                    </div>
                </div>
            </div>
        </div>

        @include('purchase.purchase_ajax')
        @include('contact.supplier.supplier_ajax')
        @include('contact.supplier.add_supplier_modal')
        @include('product.product_ajax')
        @include('product.add_product_modal')
        @include('brand.brand_modal')
        @include('brand.brand_ajax')
        @include('unit.unit_modal')
        @include('unit.unit_ajax')
        @include('category.main_category.main_category_ajax')
        @include('category.main_category.main_category_modal')
        @include('category.sub_category.sub_category_modal')
        @include('category.sub_category.sub_category_ajax')

        {{-- <!-- Purchase IMEI Entry Modal -->
        <div class="modal fade" id="purchaseImeiModal" tabindex="-1" aria-labelledby="purchaseImeiModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="purchaseImeiModalLabel">Enter IMEI Numbers for Purchase</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
                        <p><strong>Product:</strong> <span id="purchaseImeiProductName"></span></p>
                        <p>Total required: <strong><span id="purchaseImeiTotalCount"></span></strong></p>

                        <!-- Textarea for Paste -->
                        <textarea id="purchaseImeiInput" rows="6" class="form-control mb-2" placeholder="Paste or type one IMEI per line..."></textarea>
                        <button type="button" class="btn btn-sm btn-info float-end text-white" id="purchaseAutoFillImeis">Auto Fill Rows</button>

                        <!-- Table for IMEIs -->
                        <table class="table table-bordered mt-3" id="purchaseImeiTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>IMEI Number</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <!-- Buttons -->
                        <button type="button" class="btn btn-sm btn-success mb-3" id="purchaseAddImeiRow">+ Add Row</button>
                        <span id="purchaseImeiCountDisplay" class="ms-2 text-info"></span>
                        <div id="purchaseImeiError" class="text-danger mt-2 d-none"></div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="purchaseSkipImeiButton">Skip</button>
                        <button type="button" class="btn btn-primary" id="purchaseSaveImeiButton">Save IMEIs</button>
                    </div>
                </div>
            </div>
        </div> --}}

        <!-- Purchase Time IMEI Entry Modal -->
        <div class="modal fade" id="purchaseImeiModal" tabindex="-1" aria-labelledby="purchaseImeiModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="purchaseImeiModalLabel">Enter IMEI Numbers for Purchase</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Product Selection Section -->
                        <div id="purchaseImeiProductSelection" class="mb-4">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> <strong>Select products to enter IMEI numbers.</strong>
                                You can work on multiple products at once or one at a time.
                            </div>

                            <div class="mb-3">
                                <label for="purchaseImeiProductSelect" class="form-label">
                                    <i class="fas fa-mobile-alt"></i> Select Products for IMEI Entry
                                </label>
                                <select class="form-select" id="purchaseImeiProductSelect" multiple>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                <div class="form-text">Select one or more products to enter their IMEI numbers</div>
                            </div>

                            <div class="alert alert-secondary">
                                <span id="purchaseImeiCountDisplay">0/0 complete</span>
                            </div>
                        </div>

                        <!-- IMEI Entry Section (hidden initially) -->
                        <div id="purchaseImeiEntrySection" style="display: none;">
                            <div class="alert alert-danger d-none" id="purchaseImeiError"></div>

                            <div class="mb-3">
                                <label for="purchaseImeiInput" class="form-label">Bulk IMEI Entry</label>
                                <textarea class="form-control" id="purchaseImeiInput" rows="4"
                                    placeholder="Enter IMEI numbers, one per line (optional - you can also use the table below)"></textarea>
                                <div class="form-text">You can paste multiple IMEI numbers here, then click "Auto Fill" to
                                    populate the table below.</div>
                                <button type="button" class="btn btn-sm btn-info mt-2" id="purchaseAutoFillImeis">
                                    <i class="fas fa-magic"></i> Auto Fill from Text Above
                                </button>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Individual IMEI Entry</h6>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm" id="purchaseImeiTable">
                                    <thead>
                                        <tr>
                                            <th width="10%">#</th>
                                            <th width="70%">IMEI Number</th>
                                            <th width="20%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- IMEI input rows will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" id="purchaseSkipImeiButton">
                            <i class="fas fa-forward"></i> Skip IMEI & Complete Purchase
                        </button>
                        <button type="button" class="btn btn-primary" id="purchaseSaveImeiButton">
                            <i class="fas fa-check-circle"></i> Save IMEI & Complete Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function() {


                // Prevent form submission on Enter keypress in the product search input
                $('#productSearchInput').on('keydown', function(e) {
                    if (e.key === 'Enter' || e.keyCode === 13) {
                        e.preventDefault(); // Prevent the default action of the Enter key
                    }
                });

                $('#payment-method').on('change', function() {
                    togglePaymentFields();
                });

                window.setPurchaseInlinePaymentVisible = function (show) {
                    var $panel = $('#purchase-inline-payment-fields');
                    var $btn = $('#purchase-toggle-inline-payment');
                    if (!$panel.length || !$btn.length) {
                        return;
                    }
                    if (show) {
                        $panel.removeClass('d-none');
                        $btn.attr('aria-expanded', 'true').text('Hide payment fields');
                    } else {
                        $panel.addClass('d-none');
                        $btn.attr('aria-expanded', 'false').text('Show payment fields');
                    }
                };

                $('#purchase-toggle-inline-payment').on('click', function () {
                    var willShow = $('#purchase-inline-payment-fields').hasClass('d-none');
                    window.setPurchaseInlinePaymentVisible(willShow);
                });

                window.setPurchaseAttachmentVisible = function (show) {
                    var $panel = $('#purchase-attachment-panel');
                    var $btn = $('#purchase-toggle-attachment');
                    if (!$panel.length || !$btn.length) {
                        return;
                    }
                    if (show) {
                        $panel.removeClass('d-none');
                        $btn.attr('aria-expanded', 'true').html('<i class="fas fa-paperclip me-1"></i> Hide attachment');
                    } else {
                        $panel.addClass('d-none');
                        $btn.attr('aria-expanded', 'false').html('<i class="fas fa-paperclip me-1"></i> Attach document');
                    }
                };

                $('#purchase-toggle-attachment').on('click', function () {
                    var willShow = $('#purchase-attachment-panel').hasClass('d-none');
                    window.setPurchaseAttachmentVisible(willShow);
                });

                function togglePaymentFields() {
                    const paymentMethod = $('#payment-method').val();
                    console.log('Toggling payment fields for method:', paymentMethod);

                    // Add class to all payment field containers for easier management
                    $('#creditCardFields, #chequeFields, #bankTransferFields').addClass('payment-fields d-none');

                    if (paymentMethod === 'card') {
                        $('#creditCardFields').removeClass('d-none');
                    } else if (paymentMethod === 'cheque') {
                        $('#chequeFields').removeClass('d-none');
                    } else if (paymentMethod === 'bank_transfer') {
                        $('#bankTransferFields').removeClass('d-none');
                    }
                }

                // Make togglePaymentFields globally accessible for purchase_ajax
                window.togglePaymentFields = togglePaymentFields;
            });
        </script>
    @endsection
