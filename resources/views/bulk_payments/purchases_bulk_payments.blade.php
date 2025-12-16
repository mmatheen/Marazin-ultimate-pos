@extends('layout.layout')

@section('head')
<style>
    /* Fix Select2 dropdown alignment and search on bulk payment page */
    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 6px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px;
        padding-left: 0;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    /* Ensure dropdown appears properly */
    .select2-container--open {
        z-index: 9999 !important;
    }

    .select2-dropdown {
        z-index: 9999 !important;
    }

    /* Fix search input focus */
    .select2-search__field {
        outline: none;
        border: 1px solid #ced4da !important;
        padding: 4px !important;
    }

    .select2-search__field:focus {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
    }

    /* Flexible Many-to-Many Payment UI Styles */
    .bill-items-container {
        height: 300px !important;
        max-height: 300px !important;
        min-height: 300px !important;
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
        position: relative;
        border: 1px solid #e9ecef;
        border-radius: 4px;
    }

    .payment-methods-container {
        height: 380px !important;
        max-height: 380px !important;
        min-height: 380px !important;
        overflow-y: scroll !important;
        overflow-x: hidden !important;
        display: block !important;
        -webkit-overflow-scrolling: touch;
        position: relative;
        border: 1px solid #e9ecef;
        border-radius: 4px;
    }

    .bill-item {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .bill-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-1px);
    }

    .payment-method-item {
        transition: all 0.3s ease;
    }

    .bill-allocation-row {
        transition: all 0.3s ease;
    }

    .bill-allocation-row:hover {
        background-color: #f8f9fa !important;
    }

    .btn-xs {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        line-height: 1.2;
        border-radius: 0.2rem;
    }

    .allocation-amount:disabled {
        background-color: #f8f9fa;
        border-color: #e9ecef;
    }

    /* Animation for adding/removing items */
    .payment-method-item, .bill-allocation-row {
        opacity: 0;
        animation: fadeInUp 0.3s ease forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Scrollbar styling */
    .bill-items-container::-webkit-scrollbar,
    .payment-methods-container::-webkit-scrollbar {
        width: 6px;
    }

    .bill-items-container::-webkit-scrollbar-track,
    .payment-methods-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .bill-items-container::-webkit-scrollbar-thumb,
    .payment-methods-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .bill-items-container::-webkit-scrollbar-thumb:hover,
    .payment-methods-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .method-group:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .form-label-sm {
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
    }

    .selected-bill {
        transition: all 0.2s ease;
    }

    .selected-bill:hover {
        background-color: #f8f9fa !important;
        border-color: #007bff !important;
    }

    .bill-selection-section {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <form id="bulkPaymentForm">
        <input id="purchase_id" name="purchase_id" type="hidden">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">

                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('manage-bulk-payments') }}">Bulk payments</a></li>
                                <li class="breadcrumb-item active">Add Purchase Payments</li>
                            </ul>
                        </div>
                        {{-- <div class="page-btn">
                            <a href="{{ route('manage-bulk-payments') }}" class="btn btn-outline-primary">
                                <i class="feather-list"></i> Manage Payments
                            </a>
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>


        <!-- Step 1: Supplier Selection -->
        <div class="card mb-4 shadow-sm rounded border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-user"></i> Step 1: Select Supplier</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="supplierSelect">Choose Supplier with Due Bills</label>
                    <select id="supplierSelect" class="form-control selectBox">
                        <option value="">🔄 Loading suppliers...</option>
                    </select>
                    <small class="form-text text-muted">Select a supplier to see their due bills and payment options</small>
                </div>

                <!-- Supplier Summary (Hidden by default) -->
                <div id="supplierSummarySection" class="row mt-3" style="display: none;">
                    <div class="col-md-2">
                        <div class="card bg-warning p-3 rounded text-center shadow-sm">
                            <strong>Opening Balance:</strong>
                            <span id="openingBalance" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-success p-3 rounded text-center shadow-sm">
                            <strong>Total Purchases:</strong>
                            <span id="totalPurchasesAmount" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-secondary p-3 rounded text-center shadow-sm">
                            <strong>Total Paid:</strong>
                            <span id="totalPaidAmount" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-primary p-3 rounded text-center shadow-sm">
                            <strong>Purchase Due Amount:</strong>
                            <span id="totalDueAmount" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-danger p-3 rounded text-center shadow-sm">
                            <strong>Total Supplier Due:</strong>
                            <span id="totalSupplierDue" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Type Selection - Compact Single Row -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="border border-primary rounded p-2 bg-light">
                            <div class="d-flex align-items-center">
                                <strong class="text-primary me-3">Payment Options:</strong>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="paymentType" id="payOpeningBalance" value="opening_balance">
                                    <label class="form-check-label" for="payOpeningBalance">Opening Balance Only</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="paymentType" id="payPurchaseDues" value="purchase_dues" checked>
                                    <label class="form-check-label" for="payPurchaseDues">Pay Purchase Dues</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="paymentType" id="payBoth" value="both">
                                    <label class="form-check-label" for="payBoth">Both (Opening + Purchase Dues)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Payment Method Selection (Hidden by default) -->
                <div id="paymentMethodSection" style="display: none;">
                    <div class="card mb-4 border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-credit-card"></i> Step 2: Choose Payment Method</h6>
                        </div>
                        <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">
                                <i class="fas fa-credit-card"></i> Payment Method
                                <span class="badge bg-success ms-2" id="methodModeIndicator">Multi Mode</span>
                            </label>
                            <div class="input-group">
                                <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
                                    <option value="cash">💵 Cash</option>
                                    <option value="card">💳 Credit Card</option>
                                    <option value="cheque">📄 Cheque</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="multiple" selected>🔄 Multiple Methods</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="paidOn" class="form-label">Paid On</label>
                            <input class="form-control" type="date" name="payment_date" id="paidOn" value="<?php echo date('Y-m-d'); ?>">
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

                <!-- FLEXIBLE Many-to-Many Payment UI -->
                <div id="multiMethodContainer" class="mb-4">
                    <div class="card border-primary">
                        <div class="card-body p-0">

                            <!-- Two Column Layout -->
                            <div class="row g-0">
                                <!-- Left Column: Available Bills -->
                                <div class="col-md-6 border-end">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-primary mb-0">
                                                <i class="fas fa-file-invoice-dollar"></i> Outstanding Bills
                                            </h6>
                                            <input type="text" id="billSearchInput" class="form-control form-control-sm" placeholder="🔍 Search reference..." style="max-width: 180px;">
                                        </div>
                                        <div id="availableBillsList" class="bill-items-container" style="height: 300px; max-height: 300px; overflow-y: scroll; overflow-x: hidden;">
                                            <!-- Bills will be populated here -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column: Payment Methods -->
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-success mb-0">
                                                <i class="fas fa-credit-card"></i> Payment Methods
                                            </h6>
                                            <button type="button" class="btn btn-success btn-sm" id="addFlexiblePayment">
                                                <i class="fas fa-plus"></i> Add Payment
                                            </button>

                                        </div>
                                        <div id="flexiblePaymentsList" class="payment-methods-container" style="height: 380px; max-height: 380px; overflow-y: scroll; overflow-x: hidden;">
                                            <!-- Payment methods will be added here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Summary -->
                            <div class="border-top p-3 bg-light">
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Bills</small>
                                        <div class="fw-bold" id="totalBillsCount">0</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Due</small>
                                        <div class="fw-bold text-danger" id="totalDueAmount">Rs. 0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Payments</small>
                                        <div class="fw-bold text-success" id="totalPaymentAmount">Rs. 0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Balance</small>
                                        <div class="fw-bold" id="balanceAmount">Rs. 0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditional Payment Fields -->
                <div id="cardFields" class="row mb-3 d-none">
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
                            <input type="date" class="form-control" id="cheque_received_date" name="cheque_received_date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                            <input type="date" class="form-control" id="cheque_valid_date" name="cheque_valid_date">
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
                </div>
            </div>
        </div>

        <!-- Payment Notes Section -->
        <div id="notesSection" class="row mb-3" style="display: none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <label for="notes" class="form-label">
                            <i class="fas fa-sticky-note"></i> Payment Notes / Description
                        </label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Enter any additional notes or description for this payment (optional)..."></textarea>
                        <small class="text-muted">This note will be saved with the payment record for future reference.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button (Hidden by default) -->
        <div id="submitButtonSection" class="text-center" style="display: none;">
            <button type="button" id="submitBulkPayment" class="btn btn-primary btn-lg" style="min-width: 250px; max-width: 400px;">
                <i class="fas fa-credit-card"></i> Submit Payment
            </button>
        </div>
    </form>
</diV>
<div class="modal fade" id="flexibleBulkPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-layer-group"></i> Flexible Multi-Method Bulk Payment
                    <small class="d-block">Different payment methods for different bills</small>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Supplier Selection Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="flexibleSupplierSelect" class="form-label">Select Supplier</label>
                        <select id="flexibleSupplierSelect" class="form-control">
                            <option value="">Select Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="flexiblePaymentDate" class="form-label">Payment Date</label>
                        <input type="date" id="flexiblePaymentDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- Supplier Summary Cards -->
                <div class="row mb-4" id="supplierSummaryCards" style="display:none;">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h6><i class="fas fa-balance-scale"></i> Opening Balance</h6>
                                <h4 id="openingBalanceAmount">Rs. 0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h6><i class="fas fa-shopping-cart"></i> Total Purchases</h6>
                                <h4 id="totalPurchasesAmount">Rs. 0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h6><i class="fas fa-exclamation-triangle"></i> Total Due</h6>
                                <h4 id="totalDueAmount">Rs. 0.00</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h6><i class="fas fa-file-invoice"></i> Available Bills</h6>
                                <h4 id="availableBillsCount">0</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Groups Container -->
                <div id="paymentGroupsContainer">
                    <!-- Dynamic payment groups will be added here -->
                </div>

                <!-- Add Payment Group Button -->
                <div class="text-center mb-4">
                    <button type="button" id="addPaymentGroup" class="btn btn-success">
                        <i class="fas fa-plus"></i> Add Payment Method Group
                    </button>
                    <small class="text-muted d-block mt-2">Create groups for different payment methods (Cash, Cheque, Card, etc.)</small>
                </div>

                <!-- Total Summary -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card bg-dark text-white">
                            <div class="card-body">
                                <h6 class="mb-3"><i class="fas fa-chart-line"></i> Payment Summary</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Total Groups: <span id="totalGroups" class="badge bg-warning">0</span></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Bills: <span id="totalBills" class="badge bg-info">0</span></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Amount: <span id="grandTotalAmount" class="badge bg-success">Rs. 0.00</span></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Remaining Due: <span id="remainingDueAmount" class="badge bg-danger">Rs. 0.00</span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="flexiblePaymentNotes" class="form-label">Payment Notes</label>
                        <textarea id="flexiblePaymentNotes" class="form-control" rows="3" placeholder="Optional notes for this payment..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="button" id="submitFlexiblePayment" class="btn btn-success">
                    <i class="fas fa-credit-card"></i> Process Multi-Method Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Receipt Modal -->
<div class="modal fade" id="paymentReceiptModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Payment Successful
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-receipt fa-3x text-success mb-3"></i>
                    <h5>Payment Reference Number</h5>
                </div>
                <div class="alert alert-info" style="font-size: 18px; font-weight: bold; font-family: monospace;">
                    <span id="receiptReferenceNo">-</span>
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyReferenceNumber()">
                        <i class="fas fa-copy"></i> Copy Reference Number
                    </button>
                </div>
                <div class="text-muted">
                    <small><strong>Total Amount:</strong> Rs. <span id="receiptTotalAmount">0.00</span></small>
                </div>
                <p class="mt-3 text-muted small">Save this reference number for future payment tracking and verification.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeReceiptAndReload()">
                    <i class="fas fa-check"></i> OK
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Define the supplier loading function directly here for the separate page
function loadSuppliersForBulkPayment() {
    console.log('Loading suppliers for bulk payment (separate page version)...');
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
    console.log('Current user authenticated:', $('meta[name="csrf-token"]').length > 0);

    $.ajax({
        url: '/supplier-get-all',
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Supplier response for bulk payment:', response);
            var supplierSelect = $('#supplierSelect');
            supplierSelect.empty();
            supplierSelect.append('<option value="" selected disabled>Select Supplier</option>');

            if (response.status === 200 && response.message && response.message.length > 0) {
                response.message.forEach(function(supplier) {

                    // Calculate total due amount
                    var openingBalance = parseFloat(supplier.opening_balance) || 0;
                    var purchaseDue = parseFloat(supplier.total_purchase_due) || 0;
                    var currentBalance = parseFloat(supplier.current_balance) || 0;

                    // Total due is the current balance (which includes opening balance + purchase dues)
                    var totalDue = currentBalance;

                    // Only show suppliers who have due amounts
                    if (totalDue > 0) {
                        var lastName = supplier.last_name ? supplier.last_name : '';
                        var fullName = supplier.first_name + (lastName ? ' ' + lastName : '');
                        var displayText = fullName + ' (Due: Rs. ' + totalDue.toFixed(2) + ')';
                        if (openingBalance > 0) {
                            displayText += ' [Opening: Rs. ' + openingBalance.toFixed(2) + ']';
                        }

                        supplierSelect.append(
                            '<option value="' + supplier.id +
                            '" data-opening-balance="' + openingBalance +
                            '" data-purchase-due="' + purchaseDue +
                            '" data-total-due="' + totalDue +
                            '">' + displayText + '</option>'
                        );
                    }
                });
            } else {
                console.error("Failed to fetch supplier data or no suppliers found.", response);
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error loading suppliers:", {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });

            // Show user-friendly error message
            var errorMessage = 'Failed to load suppliers.';
            if (xhr.status === 401) {
                errorMessage = 'Authentication required. Please refresh the page and login again.';
            } else if (xhr.status === 403) {
                errorMessage = 'Permission denied to access supplier data.';
            }

            $('#supplierSelect').append('<option value="" disabled>Error: ' + errorMessage + '</option>');
        }
    });
}

// Additional initialization for the separate bulk payment page
$(document).ready(function() {
    console.log('Bulk payment page specific initialization...');

    // Initialize select2 with proper settings for standalone page
    if (typeof $.fn.select2 !== 'undefined') {
        $('#supplierSelect').select2({
            placeholder: "Select Supplier",
            allowClear: true,
            width: '100%'
        });
        console.log('Select2 initialized on bulk payment page');

        // Add event listener to ensure search input gets focus when dropdown opens
        $('#supplierSelect').on('select2:open', function() {
            setTimeout(function() {
                var searchField = document.querySelector('.select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 100);
        });
    }

    // Set today's date as default for "Paid On" field in YYYY-MM-DD format
    var today = new Date();
    var todayFormatted = today.getFullYear() + '-' +
        String(today.getMonth() + 1).padStart(2, '0') + '-' +
        String(today.getDate()).padStart(2, '0');
    $('#paidOn').val(todayFormatted);
    $('input[name="payment_date"]').val(todayFormatted);
    console.log('Set default date to today (YYYY-MM-DD):', todayFormatted);

    // Load suppliers immediately
    setTimeout(function() {
        console.log('Loading suppliers for separate page...');
        loadSuppliersForBulkPayment();
    }, 1000);

    // Initialize Multiple Methods mode by default
    setTimeout(function() {
        console.log('Initializing Multiple Methods mode as default...');
        $('#paymentMethod').val('multiple').trigger('change');
        togglePaymentFields();
        // Show payment method section
        $('#paymentMethodSection').show();
        $('#notesSection').show();
        $('#submitButtonSection').show();
    }, 1500);

    // Handle Payment Type changes to update payment method dropdown
    $('input[name="paymentType"]').on('change', function() {
        const selectedType = $(this).val();
        const $paymentMethod = $('#paymentMethod');

        if (selectedType === 'opening_balance') {
            // Opening Balance: Show single payment methods, default to Cash
            $paymentMethod.find('option[value="multiple"]').hide();
            if ($paymentMethod.val() === 'multiple') {
                $paymentMethod.val('cash').trigger('change');
            }
        } else {
            // Pay Purchase Dues or Both: Show all including Multiple Methods
            $paymentMethod.find('option[value="multiple"]').show();
            if ($paymentMethod.val() !== 'multiple') {
                $paymentMethod.val('multiple').trigger('change');
            }
        }
    });
});

// Supplier selection change handler for separate page
$(document).on('change', '#supplierSelect', function() {
    console.log('Supplier selected on separate page...');

    var selectedOption = $(this).find(':selected');
    var supplierId = $(this).val();
    if (!supplierId) {
        console.log('No supplier selected - hiding sections');
        $('#supplierSummarySection').hide();
        $('#paymentMethodSection').hide();
        $('#notesSection').hide();
        $('#submitButtonSection').hide();
        return;
    }

    console.log('Selected supplier ID:', supplierId);

    // Show supplier summary and payment method section
    $('#supplierSummarySection').show();
    $('#paymentMethodSection').show();
    $('#notesSection').show();
    $('#submitButtonSection').show();

    // Get supplier data from the selected option
    var supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
    var purchaseDue = parseFloat(selectedOption.data('purchase-due')) || 0;
    var totalDue = parseFloat(selectedOption.data('total-due')) || 0;


    // Update balance cards
    $('#openingBalance').text('Rs. ' + supplierOpeningBalance.toFixed(2));
    $('#totalSupplierDue').text('Rs. ' + totalDue.toFixed(2));
    // Store original opening balance globally
    window.originalOpeningBalance = supplierOpeningBalance;
    window.purchaseDueAmount = purchaseDue;

    // Reset and clear previous validation errors
    $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
    $('#globalPaymentAmount').val('');

    // Load purchases for the selected supplier (for multiple methods mode)
    loadSupplierPurchases(supplierId);
    loadSupplierPurchasesForMultiMethod(supplierId);
});

// Function to load purchases for selected supplier
function loadSupplierPurchases(supplierId) {
    console.log('Loading purchases for supplier:', supplierId);

    $.ajax({
        url: '/get-all-purchases',
        method: 'GET',
        dataType: 'json',
        data: {
            supplier_id: supplierId
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Purchases response:', response);
            console.log('Looking for supplier_id:', supplierId);

            // Clear the table first
            $('#purchasesList tbody').empty();

            if (response.purchases && response.purchases.length > 0) {
                console.log('Total purchases in response:', response.purchases.length);

                // Filter purchases for this specific supplier
                var supplierPurchases = response.purchases.filter(p => p.supplier_id == supplierId);
                console.log('Purchases for supplier ' + supplierId + ':', supplierPurchases.length);

                var totalPurchasesAmount = 0,
                    totalPaidAmount = 0,
                    totalDueAmount = 0;
                var outstandingPurchases = [];

                supplierPurchases.forEach(function(purchase) {
                    var finalTotal = parseFloat(purchase.final_total) || 0;
                    var totalPaid = parseFloat(purchase.total_paid) || 0;
                    var totalDue = parseFloat(purchase.total_due) || 0;

                    // Verify calculation
                    var calculatedDue = finalTotal - totalPaid;
                    if (Math.abs(totalDue - calculatedDue) > 0.01) {
                        console.log('Due amount mismatch for purchase ' + purchase.id + ':', {
                            stored_due: totalDue,
                            calculated_due: calculatedDue,
                            final_total: finalTotal,
                            total_paid: totalPaid
                        });
                        totalDue = calculatedDue; // Use calculated value
                    }

                    console.log('Processing purchase:', {
                        id: purchase.id,
                        reference: purchase.reference_no,
                        status: purchase.purchasing_status,
                        final_total: finalTotal,
                        total_paid: totalPaid,
                        total_due: totalDue
                    });

                    // Include all purchases in totals for summary
                    totalPurchasesAmount += finalTotal;
                    totalPaidAmount += totalPaid;
                    totalDueAmount += totalDue;

                    // Only show purchases with outstanding dues in the payment table
                    if (totalDue > 0.01) {
                        var invoiceDisplay = purchase.reference_no || purchase.invoice_no || 'PUR-' + purchase.id;
                        var purchaseDate = purchase.purchase_date ? new Date(purchase.purchase_date).toLocaleDateString() : 'N/A';

                        var row = '<tr data-purchase-id="' + purchase.id + '">' +
                            '<td>' + invoiceDisplay + '</td>' +
                            '<td>' + purchaseDate + '</td>' +
                            '<td>Rs. ' + finalTotal.toFixed(2) + '</td>' +
                            '<td>Rs. ' + totalPaid.toFixed(2) + '</td>' +
                            '<td>Rs. ' + totalDue.toFixed(2) + '</td>' +
                            '<td><input type="number" class="form-control form-control-sm reference-amount" ' +
                                    'data-reference-id="' + purchase.id + '" ' +
                                    'data-reference="' + invoiceDisplay + '" ' +
                                    'data-due="' + totalDue + '" ' +
                                    'min="0" max="' + totalDue + '" step="0.01" ' +
                                    'placeholder="Enter amount" value="0"></td>' +
                            '</tr>';

                        $('#purchasesList tbody').append(row);

                        // Store for global processing
                        outstandingPurchases.push({
                            id: purchase.id,
                            reference_no: invoiceDisplay,
                            final_total: finalTotal,
                            total_paid: totalPaid,
                            total_due: totalDue,
                            purchase_date: purchase.purchase_date
                        });
                    }
                });

                console.log('Totals calculated:', {
                    totalPurchasesAmount: totalPurchasesAmount,
                    totalPaidAmount: totalPaidAmount,
                    totalDueAmount: totalDueAmount,
                    outstandingCount: outstandingPurchases.length
                });

                // Update summary cards
                $('#totalPurchasesAmount').text('Rs. ' + totalPurchasesAmount.toFixed(2));
                $('#totalPaidAmount').text('Rs. ' + totalPaidAmount.toFixed(2));
                $('#totalDueAmount').text('Rs. ' + totalDueAmount.toFixed(2));

                // Store for global payment processing
                availableSupplierPurchases = outstandingPurchases;
                window.purchaseDueAmount = totalDueAmount;

                // Update global payment amount max and placeholder
                $('#globalPaymentAmount').attr('max', totalDueAmount);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));

                // Show appropriate message if no outstanding purchases
                if (outstandingPurchases.length === 0) {
                    var message = supplierPurchases.length > 0 ?
                        '✅ All ' + supplierPurchases.length + ' purchases are fully paid for this supplier' :
                        '📋 No purchases found for this supplier';
                    $('#purchasesList tbody').append(
                        '<tr><td colspan="6" class="text-center text-success">' + message + '</td></tr>'
                    );

                    // Hide payment method section if no outstanding amounts
                    $('#paymentMethodSection').hide();
                    $('#notesSection').hide();
                    $('#submitButtonSection').hide();
                } else {
                    // Show payment method section
                    $('#paymentMethodSection').show();
                    $('#notesSection').show();
                    $('#submitButtonSection').show();
                }

            } else {
                console.log('No purchases found in response');
                $('#purchasesList tbody').append(
                    '<tr><td colspan="6" class="text-center text-info">No purchases found for this supplier</td></tr>'
                );

                // Reset summary
                $('#totalPurchasesAmount').text('Rs. 0.00');
                $('#totalPaidAmount').text('Rs. 0.00');
                $('#totalDueAmount').text('Rs. 0.00');

                // Clear global variables
                availableSupplierPurchases = [];
                window.purchaseDueAmount = 0;

                // Hide payment sections
                $('#paymentMethodSection').hide();
                $('#notesSection').hide();
                $('#submitButtonSection').hide();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading supplier purchases:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });

            $('#purchasesList tbody').empty();
            var errorMessage = 'Error loading purchases data';
            if (xhr.status === 404) {
                errorMessage = 'No purchases found for this supplier';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error loading purchases data';
            }

            $('#purchasesList tbody').append('<tr><td colspan="5" class="text-center text-danger">' + errorMessage + '</td></tr>');
        }
    });
}

// Function to update individual payment total
function updateIndividualPaymentTotal() {
    var total = 0;
    $('.reference-amount').each(function() {
        var amount = parseFloat($(this).val()) || 0;
        total += amount;
    });

    $('#individualPaymentTotal').text('Rs. ' + total.toFixed(2));
    return total;
}

// Handle individual payment input changes
$(document).on('input', '.reference-amount', function() {
    var referenceDue = parseFloat($(this).attr('max'));
    var paymentAmount = parseFloat($(this).val()) || 0;

    // Clear existing validation feedback first
    $(this).removeClass('is-invalid');
    $(this).next('.invalid-feedback').remove();

    if (paymentAmount > referenceDue) {
        $(this).addClass('is-invalid').after(
            '<span class="invalid-feedback d-block">Amount exceeds total due.</span>'
        );
    }

    // Update individual payment total whenever amount changes
    updateIndividualPaymentTotal();
});

// Handle payment type selection change - auto-apply global amount
$('input[name="paymentType"]').change(function() {
    var paymentType = $(this).val();
    var supplierId = $('#supplierSelect').val();
    console.log('Payment type changed to:', paymentType);

    if (!supplierId) {
        return;
    }

    // Show/hide purchases list based on payment type
    if (paymentType === 'opening_balance') {
        $('#purchasesListContainer').hide();
    } else {
        $('#purchasesListContainer').show();
    }

    // Update max amount and placeholder for global payment input
    var supplierOpeningBalance = window.originalOpeningBalance || 0;
    var purchaseDueAmount = window.purchaseDueAmount || 0;
    var totalDueAmount = parseFloat($('#totalSupplierDue').text().replace('Rs. ', '')) || 0;

    if (paymentType === 'opening_balance') {
        $('#globalPaymentAmount').attr('max', supplierOpeningBalance);
        $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + supplierOpeningBalance.toFixed(2));
    } else if (paymentType === 'purchase_dues') {
        $('#globalPaymentAmount').attr('max', purchaseDueAmount);
        $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + purchaseDueAmount.toFixed(2));
    } else if (paymentType === 'both') {
        $('#globalPaymentAmount').attr('max', totalDueAmount);
        $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));
    }

    // Reset and re-apply global amount if any
    var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
    if (globalAmount > 0) {
        $('#globalPaymentAmount').trigger('input');
    }
});

// Handle global payment amount input - AUTO-APPLY to purchases
$(document).on('input', '#globalPaymentAmount', function() {
    var globalAmount = parseFloat($(this).val()) || 0;
    var supplierOpeningBalance = window.originalOpeningBalance || 0;
    var remainingAmount = globalAmount;
    var paymentType = $('input[name="paymentType"]:checked').val();

    console.log('Global amount changed:', globalAmount, 'Payment type:', paymentType);

    // Validate global amount based on payment type
    var totalSupplierDue = parseFloat($('#totalSupplierDue').text().replace('Rs. ', '')) || 0;
    var maxAmount = 0;
    if (paymentType === 'opening_balance') {
        maxAmount = supplierOpeningBalance;
    } else if (paymentType === 'purchase_dues') {
        maxAmount = totalSupplierDue;
    } else if (paymentType === 'both') {
        maxAmount = totalSupplierDue;
    }

    // Clear existing validation feedback
    $(this).removeClass('is-invalid');
    $(this).next('.invalid-feedback').remove();

    if (globalAmount > maxAmount) {
        $(this).addClass('is-invalid').after(
            '<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>'
        );
        return;
    }

    // AUTO-APPLY: Distribute payment based on payment type
    if (paymentType === 'opening_balance') {
        // Only apply to opening balance
        let newOpeningBalance = Math.max(0, supplierOpeningBalance - remainingAmount);
        $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));

        // Clear all purchase payment inputs
        $('.reference-amount').val(0);

    } else if (paymentType === 'purchase_dues') {
        // Only apply to purchase dues in order
        $('.reference-amount').each(function() {
            var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text()) || 0;
            if (remainingAmount > 0 && referenceDue > 0) {
                var paymentAmount = Math.min(remainingAmount, referenceDue);
                $(this).val(paymentAmount.toFixed(2));
                remainingAmount -= paymentAmount;
            } else {
                $(this).val(0);
            }
        });

        // Don't change opening balance
        $('#openingBalance').text('Rs. ' + supplierOpeningBalance.toFixed(2));

    } else if (paymentType === 'both') {
        // First deduct from opening balance
        let newOpeningBalance = supplierOpeningBalance;
        if (newOpeningBalance > 0 && remainingAmount > 0) {
            if (remainingAmount <= newOpeningBalance) {
                newOpeningBalance -= remainingAmount;
                remainingAmount = 0;
            } else {
                remainingAmount -= newOpeningBalance;
                newOpeningBalance = 0;
            }
        }
        $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));

        // Then apply remaining amount to purchases in order
        $('.reference-amount').each(function() {
            var referenceDue = parseFloat($(this).closest('tr').find('td:eq(3)').text()) || 0;
            if (remainingAmount > 0 && referenceDue > 0) {
                var paymentAmount = Math.min(remainingAmount, referenceDue);
                $(this).val(paymentAmount.toFixed(2));
                remainingAmount -= paymentAmount;
            } else {
                $(this).val(0);
            }
        });
    }

    // Update the individual payment total display
    updateIndividualPaymentTotal();
});

// Handle bulk payment submission for separate page
$(document).on('click', '#submitBulkPayment', function() {
    console.log('Submit bulk payment button clicked (separate page)');

    var supplierId = $('#supplierSelect').val();
    var paymentMethod = $('#paymentMethod').val();

    console.log('Supplier ID:', supplierId);
    console.log('Payment Method raw:', paymentMethod);

    // Check if this is multi-method payment
    if (paymentMethod === 'multiple') {
        return submitMultiMethodPayment();
    }

    // Get payment date - check multiple possible inputs
    var paymentDate = '';
    $('#paidOn, [name="payment_date"]').each(function() {
        var val = $(this).val();
        if (val && val.trim() !== '') {
            paymentDate = val;
            console.log('Found date value in input type:', this.type, 'Value:', val);
            return false; // Break the loop
        }
    });

    // If still empty, try to get from visible date inputs or set today's date
    if (!paymentDate) {
        paymentDate = $('input[type="date"]:visible').val();
        console.log('Trying visible date input, payment_date:', paymentDate);
    }

    // If still empty, set today's date
    if (!paymentDate) {
        var today = new Date();
        paymentDate = today.getFullYear() + '-' +
            String(today.getMonth() + 1).padStart(2, '0') + '-' +
            String(today.getDate()).padStart(2, '0');
        console.log('Using today\'s date:', paymentDate);
    }

    var globalPaymentAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
    var paymentType = $('input[name="paymentType"]:checked').val();

    console.log('Global payment amount raw value:', $('#globalPaymentAmount').val());
    console.log('Global payment amount parsed:', globalPaymentAmount);
    console.log('Global amount element exists:', $('#globalPaymentAmount').length > 0);
    console.log('Global amount element is visible:', $('#globalPaymentAmount').is(':visible'));

    // Convert date format from DD-MM-YYYY to YYYY-MM-DD if needed
    if (paymentDate && paymentDate.includes('-')) {
        var dateParts = paymentDate.split('-');
        if (dateParts.length === 3 && dateParts[0].length === 2) {
            // Assume DD-MM-YYYY format, convert to YYYY-MM-DD
            paymentDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
        }
    }

    console.log('Form values collected:', {
        supplierId: supplierId,
        paymentMethod: paymentMethod,
        paymentDate: paymentDate,
        globalPaymentAmount: globalPaymentAmount,
        paymentType: paymentType
    });

    console.log('Date after conversion:', paymentDate);

    // Validate supplier selection
    if (!supplierId) {
        console.error('Validation failed: No supplier selected');
        return;
    }

    // Validate payment amount based on payment type
    var maxAmount = 0;
    var supplierOpeningBalance = parseFloat($('#supplierSelect').find(':selected').data('opening-balance')) || 0;
    var totalDueAmount = parseFloat($('#totalDueAmount').text().replace('Rs. ', '')) || 0;
    var totalSupplierDue = parseFloat($('#totalSupplierDue').text().replace('Rs. ', '')) || 0;

    console.log('Balance details:', {
        supplierOpeningBalance: supplierOpeningBalance,
        totalDueAmount: totalDueAmount, // This is purchase dues only
        totalSupplierDue: totalSupplierDue // This is total supplier due (purchases + opening balance)
    });

    if (paymentType === 'opening_balance') {
        maxAmount = supplierOpeningBalance;
    } else if (paymentType === 'purchase_dues') {
        // Allow payment up to total supplier due even when "purchase_dues" is selected
        maxAmount = totalSupplierDue;
    } else if (paymentType === 'both') {
        maxAmount = totalSupplierDue;
    }

    // Check if user is doing individual payments or global payment
    var hasIndividualPayments = false;
    var totalIndividualAmount = 0;
    $('.reference-amount').each(function() {
        var amount = parseFloat($(this).val()) || 0;
        if (amount > 0) {
            hasIndividualPayments = true;
            totalIndividualAmount += amount;
        }
    });

    console.log('Payment validation:', {
        supplierId: supplierId,
        globalPaymentAmount: globalPaymentAmount,
        hasIndividualPayments: hasIndividualPayments,
        totalIndividualAmount: totalIndividualAmount,
        paymentType: paymentType,
        paymentDate: paymentDate,
        paymentMethod: paymentMethod,
        maxAmount: maxAmount
    });

    // If no individual payments, require global payment amount
    if (!hasIndividualPayments && globalPaymentAmount <= 0) {
        console.error('Validation failed: No payment amounts entered');
        return;
    }

    // If global payment amount is provided, validate it
    if (globalPaymentAmount > 0 && globalPaymentAmount > maxAmount) {
        console.error('Validation failed: Payment amount exceeds total supplier due', {
            globalAmount: globalPaymentAmount,
            maxAmount: maxAmount,
            paymentType: paymentType,
            purchaseDues: totalDueAmount,
            totalSupplierDue: totalSupplierDue
        });
        return;
    }

    // Validate payment type
    if (!paymentType) {
        console.error('Validation failed: No payment type selected');
        return;
    }

    // Validate payment date
    if (!paymentDate) {
        console.error('Validation failed: No payment date selected');
        return;
    }

    // For purchase dues and both, collect individual purchase payments
    var purchasePayments = [];
    if (paymentType === 'purchase_dues' || paymentType === 'both') {
        $('.reference-amount').each(function() {
            var referenceId = $(this).data('reference-id');
            var paymentAmount = parseFloat($(this).val()) || 0;
            if (paymentAmount > 0) {
                purchasePayments.push({
                    reference_id: referenceId,
                    amount: paymentAmount
                });
            }
        });
    }

    var paymentData = {
        entity_type: 'supplier',
        entity_id: supplierId,
        payment_method: paymentMethod,
        payment_date: paymentDate,
        global_amount: globalPaymentAmount,
        payment_type: paymentType,
        purchase_payments: purchasePayments
    };

    // Add payment method specific fields
    if (paymentMethod === 'card') {
        paymentData.card_number = $('#cardNumber').val();
        paymentData.card_holder_name = $('#cardHolderName').val();
        paymentData.card_type = $('#cardType').val();
        paymentData.card_expiry_month = $('#expiryMonth').val();
        paymentData.card_expiry_year = $('#expiryYear').val();
        paymentData.card_security_code = $('#securityCode').val();
    } else if (paymentMethod === 'cheque') {
        paymentData.cheque_number = $('#chequeNumber').val();
        paymentData.cheque_bank_branch = $('#bankBranch').val();
        paymentData.cheque_received_date = $('#cheque_received_date').val();
        paymentData.cheque_valid_date = $('#cheque_valid_date').val();
        paymentData.cheque_given_by = $('#cheque_given_by').val();
    } else if (paymentMethod === 'bank_transfer') {
        paymentData.bank_account_number = $('#bankAccountNumber').val();
    }

    console.log('Payment data being sent:', paymentData);

    $.ajax({
        url: '/submit-bulk-purchase-payment',
        method: 'POST',
        data: paymentData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Payment submission successful:', response);

            // Show success toastr notification
            if (typeof toastr !== 'undefined') {
                toastr.success('Payment submitted successfully!', 'Success', {
                    timeOut: 3000,
                    progressBar: true,
                    closeButton: true
                });
            }

            // Reset form
            $('#bulkPaymentForm')[0].reset();
            $('#supplierSelect').val('').trigger('change');
            $('#purchasesList tbody').empty();
            $('#openingBalance').text('Rs. 0.00');
            $('#totalPurchasesAmount').text('Rs. 0.00');
            $('#totalPaidAmount').text('Rs. 0.00');
            $('#totalDueAmount').text('Rs. 0.00');
            $('#totalSupplierDue').text('Rs. 0.00');
            $('#individualPaymentTotal').text('Rs. 0.00');
            $('#globalPaymentAmount').val('');

            // Reload suppliers to refresh due amounts
            setTimeout(function() {
                loadSuppliersForBulkPayment();
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('Payment submission failed:', {
                status: status,
                error: error,
                response: xhr.responseText
            });

            // Show error toastr notification
            var errorMessage = 'Payment submission failed. Please try again.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 422) {
                errorMessage = 'Validation error. Please check your input.';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error. Please contact support.';
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage, 'Error', {
                    timeOut: 5000,
                    progressBar: true,
                    closeButton: true
                });
            } else {
                alert(errorMessage);
            }
        }
    });
});

// Multi-Method Payment Functions for Enhanced UI
function togglePaymentFields() {
    const paymentMethod = $('#paymentMethod').val();
    const isMultiMethod = paymentMethod === 'multiple';

    console.log('Payment method changed to:', paymentMethod);

    // Hide all conditional fields first
    $('#cardFields, #chequeFields, #bankTransferFields').addClass('d-none');
    $('#multiMethodContainer').toggleClass('d-none', !isMultiMethod);

    // Update mode indicator
    const modeIndicator = $('#methodModeIndicator');
    if (isMultiMethod) {
        modeIndicator.text('Multi Mode').removeClass('bg-info').addClass('bg-success');
        $('#globalPaymentAmount').prop('disabled', true).val('').attr('placeholder', 'Calculated from allocations');        // Load supplier bills for simple payment table
        const supplierId = $('#supplierSelect').val();
        if (supplierId) {
            loadSupplierPurchasesForMultiMethod(supplierId);
        } else {
            $('#billsPaymentTableBody').html('<tr><td colspan="6" class="text-center text-muted">Please select a supplier first</td></tr>');
        }
    } else {
        modeIndicator.text('Single Mode').removeClass('bg-success').addClass('bg-info');
        $('#globalPaymentAmount').prop('disabled', false).attr('placeholder', '0.00');

        // Show appropriate conditional fields for single method
        switch (paymentMethod) {
            case 'card':
                $('#cardFields').removeClass('d-none');
                break;
            case 'cheque':
                $('#chequeFields').removeClass('d-none');
                break;
            case 'bank_transfer':
                $('#bankTransferFields').removeClass('d-none');
                break;
        }
    }

    // Show submit button if payment method is selected and supplier is chosen
    if ($('#supplierSelect').val() && paymentMethod !== '') {
        $('#notesSection').show();
        $('#submitButtonSection').show();
    } else {
        $('#notesSection').hide();
        $('#submitButtonSection').hide();
    }
}

// Multi-Method Group Management
let methodGroupCounter = 0;
let availableSupplierPurchases = [];

function addNewMethodGroup() {
    const supplierId = $('#supplierSelect').val();
    if (!supplierId) {
        toastr.warning('Please select a supplier first');
        return;
    }

    // Check if bills are available
    if (!availableSupplierPurchases || availableSupplierPurchases.length === 0) {
        // Only load bills if we haven't tried loading them already
        if (!window.isLoadingSupplierPurchases) {
            window.isLoadingSupplierPurchases = true;
            toastr.info('Loading supplier bills...');
            loadSupplierPurchasesForMultiMethod(supplierId);
        } else {
            toastr.warning('No outstanding bills available for this supplier');
        }
        return;
    }

    const groupIndex = methodGroupCounter++;
    console.log('Creating group with', availableSupplierPurchases.length, 'available purchases');

    const purchasesOptions = availableSupplierPurchases.map(purchase =>
        `<option value="${purchase.id}" data-due="${purchase.total_due}" data-invoice="${purchase.reference_no}">
            ${purchase.reference_no} - Due: Rs.${parseFloat(purchase.total_due).toFixed(2)}
        </option>`
    ).join('');

    console.log('Generated purchases options for new group:', purchasesOptions);

    const groupHtml = `
        <div class="method-group mb-4 p-3 border rounded" data-group-index="${groupIndex}" style="background-color: #f8f9fa;">
            <!-- Group Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-credit-card"></i> Payment Method ${groupIndex + 1}
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-method-group">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>

            <!-- Step 1: Choose Payment Method -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">
                        <span class="badge bg-primary me-1">1</span> Choose Payment Method
                    </label>
                    <select class="form-select group-payment-method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash">💵 Cash</option>
                        <option value="cheque">📄 Cheque</option>
                        <option value="card">💳 Card</option>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-8 method-specific-fields">
                    <!-- Method-specific fields will appear here -->
                </div>
            </div>

            <!-- Step 2: Select Bills -->
            <div class="bill-selection-section" style="display: none;">
                <label class="form-label fw-bold mb-2">
                    <span class="badge bg-success me-1">2</span> Select Bills to Pay with This Method
                </label>
                <div class="bills-container border rounded p-2" style="background-color: white;">
                    <div class="bill-row row mb-2 align-items-end">
                        <div class="col-md-5">
                            <select class="form-select bill-select" required>
                                <option value="">Choose a bill...</option>
                                ${salesOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" class="form-control bill-amount"
                                   step="0.01" min="0.01" placeholder="Enter amount" required>
                            <small class="text-muted">Max: Rs. <span class="due-display">0.00</span></small>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success add-bill-to-group">
                                <i class="fas fa-plus"></i> Add Bill
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Selected Bills Summary -->
                <div class="selected-bills-list mt-2">
                    <!-- Selected bills will appear here -->
                </div>
            </div>

            <!-- Group Summary -->
            <div class="mt-3 p-2 rounded" style="background-color: #e3f2fd;">
                <div class="row text-center">
                    <div class="col-md-6">
                        <strong class="text-primary">Bills Selected: <span class="group-bills-count">0</span></strong>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-success">Total Amount: Rs. <span class="group-total-amount">0.00</span></strong>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#methodGroupsContainer').append(groupHtml);
    updateSummaryTotals();
}

// Event Handlers for Multi-Method
$(document).ready(function() {
    // Toggle multi-method button
    $('#toggleMultiMode').click(function() {
        const currentMethod = $('#paymentMethod').val();
        if (currentMethod === 'multiple') {
            $('#paymentMethod').val('cash');
        } else {
            $('#paymentMethod').val('multiple');
        }
        togglePaymentFields();
    });

    // Add method group
    $(document).on('click', '#addMethodGroup', addNewMethodGroup);

    // Remove method group
    $(document).on('click', '.remove-method-group', function() {
        $(this).closest('.method-group').remove();
        updateSummaryTotals();
    });

    // Simple Payment Table Event Handlers

    // Payment method selection for each bill
    $(document).on('change', '.payment-method-select', function() {
        const method = $(this).val();
        const billIndex = $(this).data('bill-index');
        const $row = $(this).closest('tr');
        const $detailsContainer = $row.find('.payment-details-container');
        const $amountInput = $row.find('.pay-amount-input');
        const $payButton = $row.find('.pay-bill-btn');

        if (method) {
            // Show payment method specific fields
            let fieldsHTML = getPaymentMethodFields(method, billIndex);
            $detailsContainer.html(fieldsHTML).show();

            // Enable amount input and pay button
            $amountInput.prop('disabled', false);
            $payButton.prop('disabled', false);
        } else {
            // Hide fields and disable inputs
            $detailsContainer.hide();
            $amountInput.prop('disabled', true).val('');
            $payButton.prop('disabled', true);
        }

        updateSummaryTotals();
    });

    // Amount input change
    $(document).on('input', '.pay-amount-input', function() {
        const amount = parseFloat($(this).val()) || 0;
        const maxAmount = parseFloat($(this).attr('max'));
        const $payButton = $(this).closest('tr').find('.pay-bill-btn');

        if (amount > 0 && amount <= maxAmount) {
            $payButton.prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
        } else {
            $payButton.prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
        }

        updateSummaryTotals();
    });

    // Pay bill button click
    $(document).on('click', '.pay-bill-btn', function() {
        const $row = $(this).closest('tr');
        const billId = $row.data('bill-id');
        const amount = parseFloat($row.find('.pay-amount-input').val());
        const method = $row.find('.payment-method-select').val();

        if (!method || !amount) {
            toastr.error('Please select payment method and enter amount');
            return;
        }

        // Mark bill as paid and disable row
        $row.addClass('table-success');
        $row.find('input, select, button').prop('disabled', true);
        $row.find('.pay-bill-btn').html('<i class="fas fa-check text-success"></i> Paid');

        updateSummaryTotals();
        toastr.success(`Payment of Rs.${amount.toFixed(2)} recorded for bill ${$row.find('strong').text()}`);
    });

    // Payment method change in group - IMPROVED UI (LEGACY - KEEPING FOR COMPATIBILITY)
    $(document).on('change', '.group-payment-method', function() {
        const method = $(this).val();
        const $group = $(this).closest('.method-group');
        const $fieldsContainer = $group.find('.method-specific-fields');
        const groupIndex = $group.data('group-index');

        let fieldsHtml = '';
        switch (method) {
            case 'cheque':
                fieldsHtml = `
                    <div class="p-2 border rounded" style="background-color: #e8f5e8;">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label-sm mb-1">Cheque Number</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Enter cheque number" name="cheque_number_${groupIndex}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm mb-1">Bank & Branch</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="e.g., BOC-Kalmunai" name="cheque_bank_${groupIndex}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm mb-1">Valid Date</label>
                                <input type="date" class="form-control form-control-sm"
                                       name="cheque_date_${groupIndex}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label-sm mb-1">Given By</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Person name" name="cheque_given_by_${groupIndex}">
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'card':
                fieldsHtml = `
                    <div class="p-2 border rounded" style="background-color: #e3f2fd;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label-sm mb-1">Card Number</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Enter card number" name="card_number_${groupIndex}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm mb-1">Card Holder Name</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Name on card" name="card_holder_${groupIndex}">
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'bank_transfer':
                fieldsHtml = `
                    <div class="p-2 border rounded" style="background-color: #fff3cd;">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label-sm mb-1">Account Number</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Bank account number" name="account_number_${groupIndex}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-sm mb-1">Reference Number</label>
                                <input type="text" class="form-control form-control-sm"
                                       placeholder="Transfer reference" name="transfer_ref_${groupIndex}">
                            </div>
                        </div>
                    </div>
                `;
                break;
            case 'cash':
                fieldsHtml = `
                    <div class="p-2 border rounded" style="background-color: #d4edda;">
                        <div class="text-center text-success">
                            <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                            <p class="mb-0">Cash Payment Selected</p>
                            <small class="text-muted">No additional details required</small>
                        </div>
                    </div>
                `;
                break;
            default:
                fieldsHtml = '';
        }
        $fieldsContainer.html(fieldsHtml);

        // Show bill selection section after method is chosen
        if (method) {
            $group.find('.bill-selection-section').fadeIn();
        } else {
            $group.find('.bill-selection-section').hide();
        }
    });

    // Add bill to group - IMPROVED VERSION
    $(document).on('click', '.add-bill-to-group', function() {
        const $group = $(this).closest('.method-group');
        const $billRow = $(this).closest('.bill-row');
        const selectedBillId = $billRow.find('.bill-select').val();
        const amount = parseFloat($billRow.find('.bill-amount').val());

        if (!selectedBillId || !amount || amount <= 0) {
            toastr.warning('Please select a bill and enter a valid amount');
            return;
        }

        const selectedOption = $billRow.find('.bill-select option:selected');
        const invoiceNo = selectedOption.data('invoice');
        const dueAmount = parseFloat(selectedOption.data('due'));

        if (amount > dueAmount) {
            toastr.error(`Amount Rs.${amount} exceeds due amount Rs.${dueAmount}`);
            return;
        }

        // Check if bill already added to this group
        const existingBills = $group.find('.selected-bills-list .selected-bill');
        let billExists = false;
        existingBills.each(function() {
            if ($(this).data('bill-id') == selectedBillId) {
                billExists = true;
            }
        });

        if (billExists) {
            toastr.warning('This bill is already added to this payment method');
            return;
        }

        // Add to selected bills summary
        const selectedBillHtml = `
            <div class="selected-bill d-flex justify-content-between align-items-center p-2 mb-1 border rounded bg-white" data-bill-id="${selectedBillId}">
                <div>
                    <strong>${invoiceNo}</strong>
                    <small class="text-muted d-block">Due: Rs.${dueAmount.toFixed(2)} | Paying: Rs.${amount.toFixed(2)}</small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm remove-selected-bill">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        $group.find('.selected-bills-list').append(selectedBillHtml);

        // Reset the selection form
        $billRow.find('.bill-select').val('');
        $billRow.find('.bill-amount').val('');
        $billRow.find('.due-display').text('0.00');

        updateSummaryTotals();
        toastr.success('Bill added successfully');
    });

    // Remove selected bill from group
    $(document).on('click', '.remove-selected-bill', function() {
        $(this).closest('.selected-bill').remove();
        updateSummaryTotals();
        toastr.info('Bill removed');
    });

    // Bill selection change
    $(document).on('change', '.bill-select', function() {
        const selectedOption = $(this).find('option:selected');
        const dueAmount = selectedOption.data('due') || 0;
        const $row = $(this).closest('.bill-row');

        $row.find('.due-display').text(parseFloat(dueAmount).toFixed(2));
        $row.find('.bill-amount').attr('max', dueAmount).val('');
        updateSummaryTotals();
    });

    // Amount change calculation
    $(document).on('input', '.bill-amount', function() {
        updateSummaryTotals();
    });

    // Supplier selection change - load purchases for multi-method
    $('#supplierSelect').change(function() {
        const supplierId = $(this).val();
        const paymentMethod = $('#paymentMethod').val();

        if (supplierId && paymentMethod === 'multiple') {
            // Clear existing data and reload bills for simple table
            $('#billsPaymentTableBody').empty();
            availableSupplierPurchases = [];

            loadSupplierPurchasesForMultiMethod(supplierId);
        }
    });
});

// Load supplier purchases for flexible many-to-many system
function loadSupplierPurchasesForMultiMethod(supplierId) {
    console.log('Loading bills for flexible many-to-many system:', supplierId);

    $.ajax({
        url: '/get-all-purchases',
        method: 'GET',
        data: {
            supplier_id: supplierId,
            length: 100
        },
        success: function(response) {
            if (response.purchases) {
                // Filter for outstanding bills
                availableSupplierPurchases = response.purchases.filter(purchase => {
                    const isDue = purchase.total_due > 0;
                    const isOutstanding = purchase.payment_status === 'Due' || purchase.payment_status === 'Partial';
                    return isDue && isOutstanding && purchase.supplier_id == supplierId;
                });

                console.log('Outstanding bills for flexible UI:', availableSupplierPurchases.length);

                if (availableSupplierPurchases.length === 0) {
                    toastr.warning('No outstanding bills found for this supplier');
                    populateFlexibleBillsList();
                } else {
                    populateFlexibleBillsList();
                    // Removed duplicate toastr notification
                }

                window.isLoadingSupplierPurchases = false;
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load supplier purchases:', error);
            toastr.error('Failed to load supplier purchases: ' + error);
            window.isLoadingSupplierPurchases = false;
        }
    });
}

// Global variables for flexible system
let flexiblePaymentCounter = 0;
let billPaymentAllocations = {}; // Track allocations per bill
let paymentMethodAllocations = {}; // Track allocations per payment method

// Initialize system safety check
function initializeFlexiblePaymentSystem() {
    if (typeof flexiblePaymentCounter === 'undefined') flexiblePaymentCounter = 0;
    if (typeof billPaymentAllocations === 'undefined') billPaymentAllocations = {};
    if (typeof paymentMethodAllocations === 'undefined') paymentMethodAllocations = {};
    if (typeof availableSupplierPurchases === 'undefined') availableSupplierPurchases = [];

    console.log('Flexible payment system initialized');
}

// Populate flexible bills list (left side)
function populateFlexibleBillsList() {
    let billsHTML = '';

    if (availableSupplierPurchases.length === 0) {
        billsHTML = '<div class="alert alert-warning text-center">No outstanding bills found</div>';
    }
    else {
        availableSupplierPurchases.forEach((purchase) => {
            const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
            const remainingAmount = purchase.total_due - allocatedAmount;
            const isFullyPaid = remainingAmount <= 0;

            billsHTML += `
                <div class="bill-item border rounded p-3 mb-2 ${isFullyPaid ? 'bg-light' : 'bg-white'}" data-bill-id="${purchase.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 ${isFullyPaid ? 'text-muted' : 'text-primary'}">
                                ${purchase.reference_no}
                                ${isFullyPaid ? '<span class="badge bg-success ms-2">PAID</span>' : ''}
                            </h6>
                            <div class="small text-muted">Bill #${purchase.id}</div>
                            <div class="mt-2">
                                <div class="d-flex justify-content-between">
                                    <span class="small text-muted">Total Due:</span>
                                    <span class="fw-bold">Rs. ${parseFloat(purchase.total_due).toFixed(2)}</span>
                                </div>
                                ${allocatedAmount > 0 ? `
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-success">Allocated:</span>
                                        <span class="text-success">Rs. ${allocatedAmount.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-warning">Remaining:</span>
                                        <span class="fw-bold ${isFullyPaid ? 'text-success' : 'text-warning'}">
                                            Rs. ${remainingAmount.toFixed(2)}
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="ms-3">
                            ${!isFullyPaid ? `
                                <button class="btn btn-primary btn-sm add-to-payment-btn" data-bill-id="${purchase.id}">
                                    <i class="fas fa-plus"></i>
                                </button>
                            ` : `
                                <button class="btn btn-success btn-sm" disabled>
                                    <i class="fas fa-check"></i>
                                </button>
                            `}
                        </div>
                    </div>
                </div>
            `;
        });
    }

    $('#availableBillsList').html(billsHTML);
    updateSummaryTotals();
}

// Add new flexible payment method
function addFlexiblePayment() {
    flexiblePaymentCounter++;
    const paymentId = `payment_${flexiblePaymentCounter}`;

    const paymentHTML = `
        <div class="payment-method-item border rounded p-3 mb-3 bg-light" data-payment-id="${paymentId}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="text-success mb-0">
                    <i class="fas fa-credit-card"></i> Payment Method #${flexiblePaymentCounter}
                </h6>
                <button type="button" class="btn btn-danger btn-sm remove-payment-btn" data-payment-id="${paymentId}">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Payment Method</label>
                    <select class="form-select payment-method-select" data-payment-id="${paymentId}">
                        <option value="">Choose Payment Method</option>
                        <option value="cash" selected>💵 Cash</option>
                        <option value="cheque">📄 Cheque</option>
                        <option value="card">💳 Card</option>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
                        <option value="discount">🎁 Discount</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Total Amount</label>
                    <input type="number" class="form-control payment-total-amount"
                           step="0.01" min="0.01" placeholder="0.00"
                           data-payment-id="${paymentId}">
                </div>
            </div>

            <div class="payment-details-container mb-3" style="display: none;">
                <label class="form-label small text-info mb-2">
                    <i class="fas fa-info-circle"></i> Payment Details
                </label>
                <div class="payment-fields">
                    <!-- Payment method specific fields will appear here -->
                </div>
            </div>

            <div class="bill-allocations-container">
                <h6 class="small text-muted mb-2">
                    <i class="fas fa-list"></i> Bill Allocations
                    <button type="button" class="btn btn-success btn-xs ms-2 add-bill-allocation-btn"
                            data-payment-id="${paymentId}">
                        <i class="fas fa-plus"></i> Add Bill
                    </button>
                </h6>
                <div class="bill-allocations-list" data-payment-id="${paymentId}">
                    <!-- Bill allocations will appear here -->
                </div>
            </div>
        </div>
    `;

    $('#flexiblePaymentsList').prepend(paymentHTML);

    // Initialize payment method allocations
    paymentMethodAllocations[paymentId] = {
        method: '',
        totalAmount: 0,
        billAllocations: {}
    };

    updateSummaryTotals();
}

// Add bill allocation to a payment method (ENHANCED WITH BILL STATUS TRACKING)
function addBillAllocation(paymentId) {
    // Filter bills - exclude fully paid ones and show remaining amounts
    const availableBills = availableSupplierPurchases.filter(purchase => {
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = purchase.total_due - allocatedAmount;
        return remainingAmount > 0.01; // Avoid tiny remaining amounts
    });

    if (availableBills.length === 0) {
        toastr.warning('No outstanding bills available for allocation. All bills are either fully paid or allocated.');
        return;
    }

    const allocationId = `allocation_${Date.now()}`;

    // Create enhanced bill options with status indicators
    const billOptions = availableBills.map(purchase => {
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = purchase.total_due - allocatedAmount;
        const statusIcon = allocatedAmount > 0 ? '⚠️' : '🔴'; // Warning for partial, Red for unpaid
        const statusText = allocatedAmount > 0 ? 'Partially Paid' : 'Unpaid';

        return `<option value="${purchase.id}" data-due="${purchase.total_due}" data-invoice="${purchase.reference_no}" data-remaining="${remainingAmount}">
            ${statusIcon} ${purchase.reference_no} - Remaining: Rs.${remainingAmount.toFixed(2)} (${statusText})
        </option>`;
    }).join('');

    const allocationHTML = `
        <div class="bill-allocation-row border rounded p-2 mb-2 bg-white" data-allocation-id="${allocationId}">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <select class="form-select form-select-sm bill-select" data-allocation-id="${allocationId}">
                        <option value="">💰 Select Bill to Pay...</option>
                        ${billOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control form-control-sm allocation-amount"
                           step="0.01" min="0.01" placeholder="Enter amount"
                           data-allocation-id="${allocationId}" disabled>
                    <small class="text-muted bill-amount-hint" style="display: none;"></small>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger btn-sm remove-allocation-btn"
                            data-allocation-id="${allocationId}" title="Remove this allocation">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    $(`.bill-allocations-list[data-payment-id="${paymentId}"]`).append(allocationHTML);

    // Removed excessive toastr notification
}

// Get payment method specific fields
function getPaymentMethodFields(method, paymentId) {
    switch (method) {
        case 'cheque':
            return `
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Cheque Number" name="cheque_number_${paymentId}" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Bank & Branch" name="cheque_bank_${paymentId}" required>
                    </div>
                    <div class="col-md-6">
                        <input type="date" class="form-control form-control-sm"
                               name="cheque_date_${paymentId}" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Given By" name="cheque_given_by_${paymentId}">
                    </div>
                </div>
            `;
        case 'card':
            return `
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Card Number" name="card_number_${paymentId}" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm"
                               placeholder="Card Holder" name="card_holder_${paymentId}">
                    </div>
                </div>
            `;
        case 'bank_transfer':
            return `
                <input type="text" class="form-control form-control-sm"
                       placeholder="Account Number" name="account_number_${paymentId}" required>
            `;
        case 'cash':
            return '<small class="text-success"><i class="fas fa-money-bill-wave"></i> Cash Payment - No additional details needed</small>';
        default:
            return '';
    }
}

// REMOVED AUTO-DISTRIBUTION - User must manually select bills
// This function is kept for compatibility but does nothing
function autoDistributeAmountToBills(paymentId, totalAmount) {
    console.log('Auto-distribution disabled - user must manually select bills');
    // Do nothing - let users manually select bills
}

// Legacy function compatibility - redirects to new system
function calculateSimpleTotal() {
    console.log('Legacy calculateSimpleTotal called - redirecting to updateSummaryTotals');
    updateSummaryTotals();
}

// Legacy function compatibility - redirects to new system
function calculateMultiMethodTotals() {
    console.log('Legacy calculateMultiMethodTotals called - redirecting to updateSummaryTotals');
    updateSummaryTotals();
}

// Update summary totals
function updateSummaryTotals() {
    try {
        // Calculate bill totals
        let totalBills = availableSupplierPurchases.length || 0;
        let totalDueAmount = availableSupplierPurchases.reduce((sum, purchase) => sum + parseFloat(purchase.total_due || 0), 0);

        // Calculate payment totals
        let totalPaymentAmount = 0;
        if (paymentMethodAllocations && Object.keys(paymentMethodAllocations).length > 0) {
            Object.values(paymentMethodAllocations).forEach(payment => {
                totalPaymentAmount += payment.totalAmount || 0;
            });
        }

        // Calculate balance
        let balanceAmount = totalDueAmount - totalPaymentAmount;

        // Update UI elements if they exist
        const $totalBillsCount = $('#totalBillsCount');
        const $totalDueAmountFlex = $('#totalDueAmount');
        const $totalPaymentAmount = $('#totalPaymentAmount');
        const $balanceAmount = $('#balanceAmount');

        if ($totalBillsCount.length) $totalBillsCount.text(totalBills);
        if ($totalDueAmountFlex.length) $totalDueAmountFlex.text(`Rs. ${totalDueAmount.toFixed(2)}`);

        if ($totalPaymentAmount.length) {
            $totalPaymentAmount.text(`Rs. ${totalPaymentAmount.toFixed(2)}`);
            // Update color based on amount
            if (totalPaymentAmount > 0) {
                $totalPaymentAmount.removeClass('text-muted').addClass('text-success');
            } else {
                $totalPaymentAmount.removeClass('text-success').addClass('text-muted');
            }
        }

        if ($balanceAmount.length) {
            // Enhanced balance display with better messaging
            if (balanceAmount > 0) {
                $balanceAmount.text(`Rs. ${balanceAmount.toFixed(2)}`).removeClass('text-success text-danger').addClass('text-warning');
                // Show remaining due amount
            } else if (balanceAmount < 0) {
                const excessAmount = Math.abs(balanceAmount);
                $balanceAmount.text(`Rs. ${balanceAmount.toFixed(2)}`).removeClass('text-warning text-success').addClass('text-danger');

                // Add excess amount info if not already present
                if ($('#excessInfo').length === 0) {
                    $balanceAmount.parent().append(`
                        <div id="excessInfo" class="mt-2">
                            <small class="text-info">
                                <i class="fas fa-info-circle"></i>
                                Excess Rs. ${excessAmount.toFixed(2)} will be treated as advance payment
                            </small>
                        </div>
                    `);
                } else {
                    $('#excessInfo small').text(`Excess Rs. ${excessAmount.toFixed(2)} will be treated as advance payment`);
                }
            } else {
                $balanceAmount.text(`Rs. ${balanceAmount.toFixed(2)}`).removeClass('text-warning text-danger').addClass('text-success');
                $('#excessInfo').remove(); // Remove excess info when balanced
            }
        }

        // Show/hide submit button
        const $submitSection = $('#submitButtonSection');
        if ($submitSection.length) {
            if (totalPaymentAmount > 0) {
                $submitSection.fadeIn();
            } else {
                $submitSection.fadeOut();
            }
        }

        console.log('Summary totals updated:', { totalBills, totalDueAmount, totalPaymentAmount, balanceAmount });

    } catch (error) {
        console.error('Error in updateSummaryTotals:', error);
    }
}

// Update payment method allocation totals (ENHANCED WITH SYSTEM UPDATE FLAG)
function updatePaymentMethodTotal(paymentId) {
    let total = 0;

    // Find all allocation amounts within this specific payment method
    $(`.payment-method-item[data-payment-id="${paymentId}"] .allocation-amount`).each(function() {
        total += parseFloat($(this).val()) || 0;
    });

    // Update internal tracking
    paymentMethodAllocations[paymentId].totalAmount = total;

    // Update the payment total input field (mark as system update to avoid recursion)
    const $totalInput = $(`.payment-total-amount[data-payment-id="${paymentId}"]`);
    $totalInput.data('system-update', true);
    $totalInput.val(total.toFixed(2));

    // Remove system update flag after a short delay
    setTimeout(() => {
        $totalInput.data('system-update', false);
    }, 100);

    updateSummaryTotals();
}

// Submit flexible many-to-many payment
function submitMultiMethodPayment() {
    const supplierId = $('#supplierSelect').val();
    const paymentDate = $('#paidOn').val() || $('[name="payment_date"]').val();
    const paymentType = $('input[name="paymentType"]:checked').val();

    if (!supplierId) {
        toastr.error('Please select a supplier');
        return false;
    }

    if (!paymentDate) {
        toastr.error('Please select payment date');
        return false;
    }

    // Collect flexible payment groups
    const paymentGroups = [];
    let hasValidPayments = false;
    let groupIndex = 0;

    $('.payment-method-item').each(function() {
        const $payment = $(this);
        const paymentId = $payment.data('payment-id');
        const method = $payment.find('.payment-method-select').val();
        const totalAmount = parseFloat($payment.find('.payment-total-amount').val()) || 0;

        if (!method || totalAmount <= 0) return;

        groupIndex++; // Increment counter for validation messages

        const groupData = {
            method: method,
            totalAmount: totalAmount,
            bills: [],
            details: {}
        };

        // Collect method-specific details (WITH DEBUG LOGGING)
        switch (method) {
            case 'cheque':
                const $chequeNumberField = $payment.find(`[name="cheque_number_${paymentId}"]`);
                const $chequeBankField = $payment.find(`[name="cheque_bank_${paymentId}"]`);
                const $chequeDateField = $payment.find(`[name="cheque_date_${paymentId}"]`);
                const $chequeGivenByField = $payment.find(`[name="cheque_given_by_${paymentId}"]`);

                console.log('Cheque field elements found for payment', paymentId, ':', {
                    cheque_number_element: $chequeNumberField.length,
                    cheque_bank_element: $chequeBankField.length,
                    cheque_date_element: $chequeDateField.length,
                    cheque_given_by_element: $chequeGivenByField.length
                });

                const chequeNumber = $chequeNumberField.val() || '';
                const chequeBank = $chequeBankField.val() || '';
                const chequeDate = $chequeDateField.val() || '';
                const chequeGivenBy = $chequeGivenByField.val() || '';

                console.log('Cheque field values for payment', paymentId, ':', {
                    cheque_number: chequeNumber,
                    cheque_bank: chequeBank,
                    cheque_date: chequeDate,
                    cheque_given_by: chequeGivenBy
                });

                // Validate required cheque fields
                if (!chequeNumber || chequeNumber.trim() === '') {
                    toastr.error(`Payment ${groupIndex}: Cheque Number is required`);
                    $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
                    return false;
                }
                if (!chequeBank || chequeBank.trim() === '') {
                    toastr.error(`Payment ${groupIndex}: Bank & Branch is required for cheque payments`);
                    $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
                    return false;
                }
                if (!chequeDate || chequeDate.trim() === '') {
                    toastr.error(`Payment ${groupIndex}: Cheque Date is required`);
                    $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
                    return false;
                }

                groupData.cheque_number = chequeNumber;
                groupData.cheque_bank_branch = chequeBank;
                groupData.cheque_valid_date = chequeDate;
                groupData.cheque_given_by = chequeGivenBy;
                break;
            case 'card':
                groupData.card_number = $payment.find(`[name="card_number_${paymentId}"]`).val();
                groupData.card_holder = $payment.find(`[name="card_holder_${paymentId}"]`).val();
                break;
            case 'bank_transfer':
                groupData.bank_account_number = $payment.find(`[name="account_number_${paymentId}"]`).val();
                break;
        }

        // Collect bill allocations OR total amount for opening balance
        $payment.find('.bill-allocation-row').each(function() {
            const $allocation = $(this);
            const billId = $allocation.find('.bill-select').val();
            const amount = parseFloat($allocation.find('.allocation-amount').val()) || 0;

            if (billId && amount > 0) {
                groupData.bills.push({
                    purchase_id: parseInt(billId),
                    amount: amount
                });
            }
        });

        // For opening balance payments, use the total amount even if no bills
        if (paymentType === 'opening_balance' && totalAmount > 0) {
            groupData.totalAmount = totalAmount;
            paymentGroups.push(groupData);
            hasValidPayments = true;
        } else if (groupData.bills.length > 0) {
            groupData.totalAmount = totalAmount;
            paymentGroups.push(groupData);
            hasValidPayments = true;
        }
    });

    if (!hasValidPayments) {
        toastr.error('Please add at least one payment method with bill allocations');
        return false;
    }

    // Validate allocations don't exceed bill amounts
    const billTotals = {};
    paymentGroups.forEach(group => {
        group.bills.forEach(bill => {
            billTotals[bill.purchase_id] = (billTotals[bill.purchase_id] || 0) + bill.amount;
        });
    });

    for (const [billId, totalAllocated] of Object.entries(billTotals)) {
        const bill = availableSupplierPurchases.find(p => p.id == billId);
        if (bill && totalAllocated > bill.total_due) {
            toastr.error(`Total allocation for ${bill.reference_no} exceeds bill amount`);
            return false;
        }
    }

    // Show loading
    $('#submitBulkPayment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    // Submit flexible payment
    $.ajax({
        url: '/submit-flexible-bulk-purchase-payment',
        method: 'POST',
        data: {
            supplier_id: supplierId,
            payment_date: paymentDate,
            payment_type: paymentType,
            payment_groups: paymentGroups,
            notes: $('#notes').val() || '',
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 200) {
                // Show receipt modal with reference number
                $('#receiptReferenceNo').text(response.bulk_reference || 'N/A');
                $('#receiptTotalAmount').text(parseFloat(response.total_amount || 0).toFixed(2));

                // Hide submit button and show receipt modal
                $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
                $('#paymentReceiptModal').modal('show');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            toastr.error(error?.message || 'Flexible payment submission failed');
        },
        complete: function() {
            $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
        }
    });

    return false;
}

// Initialize flexible many-to-many payment system
$(document).ready(function() {
    console.log('Flexible Many-to-Many Payment System Ready');

    // Initialize system variables
    initializeFlexiblePaymentSystem();

    // Bill Search/Filter functionality
    $('#billSearchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        $('.bill-item').each(function() {
            const invoiceNo = $(this).find('h6').text().toLowerCase();
            const billNumber = $(this).find('.text-muted').text().toLowerCase();

            if (invoiceNo.includes(searchValue) || billNumber.includes(searchValue)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Add Payment Method button
    $('#addFlexiblePayment').click(function() {
        addFlexiblePayment();
    });


    // Remove Payment Method
    $(document).on('click', '.remove-payment-btn', function() {
        const paymentId = $(this).data('payment-id');

        // Remove allocations from tracking
        if (paymentMethodAllocations[paymentId]) {
            Object.keys(paymentMethodAllocations[paymentId].billAllocations).forEach(billId => {
                const amount = paymentMethodAllocations[paymentId].billAllocations[billId];
                billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
                if (billPaymentAllocations[billId] <= 0) {
                    delete billPaymentAllocations[billId];
                }
            });
            delete paymentMethodAllocations[paymentId];
        }

        $(this).closest('.payment-method-item').fadeOut(() => {
            $(this).closest('.payment-method-item').remove();
            populateFlexibleBillsList();
            updateSummaryTotals();
        });
    });

    // Payment Method Selection
    $(document).on('change', '.payment-method-select', function() {
        try {
            const paymentId = $(this).data('payment-id');
            const method = $(this).val();
            const $container = $(this).closest('.payment-method-item');

            console.log('Payment method selected:', method, 'for payment ID:', paymentId);
            console.log('Available allocations:', paymentMethodAllocations);

            // Ensure allocation object exists
            if (!paymentMethodAllocations[paymentId]) {
                paymentMethodAllocations[paymentId] = {
                    method: '',
                    totalAmount: 0,
                    billAllocations: {}
                };
            }

            paymentMethodAllocations[paymentId].method = method;

            if (method) {
                const fieldsHTML = getPaymentMethodFields(method, paymentId);
                console.log('Generated fields HTML:', fieldsHTML);

                const $detailsContainer = $container.find('.payment-details-container');
                const $fieldsContainer = $detailsContainer.find('.payment-fields');

                console.log('Details container found:', $detailsContainer.length);
                console.log('Fields container found:', $fieldsContainer.length);

                $fieldsContainer.html(fieldsHTML);
                $detailsContainer.slideDown();

                // Show success message for better user feedback
                toastr.success(`${method.toUpperCase()} payment method selected - Enter details below`);
            } else {
                $container.find('.payment-details-container').slideUp();
            }
        } catch (error) {
            console.error('Error in payment method selection:', error);
            toastr.error('Error selecting payment method: ' + error.message);
        }
    });

    // Add Bill Allocation
    $(document).on('click', '.add-bill-allocation-btn', function() {
        const paymentId = $(this).data('payment-id');
        addBillAllocation(paymentId);
    });

    // Remove Bill Allocation
    $(document).on('click', '.remove-allocation-btn', function() {
        const allocationId = $(this).data('allocation-id');
        const $row = $(this).closest('.bill-allocation-row');
        const billId = $row.find('.bill-select').val();
        const amount = parseFloat($row.find('.allocation-amount').val()) || 0;

        // Update tracking
        if (billId && amount > 0) {
            billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
            if (billPaymentAllocations[billId] <= 0) {
                delete billPaymentAllocations[billId];
            }
        }

        $row.fadeOut(() => {
            $row.remove();
            populateFlexibleBillsList();
            updateSummaryTotals();
        });
    });

    // Bill Selection in Allocation - SIMPLE MANUAL APPROACH
    $(document).on('change', '.bill-allocation-row .bill-select', function() {
        const billId = $(this).val();
        const $row = $(this).closest('.bill-allocation-row');
        const $amountInput = $row.find('.allocation-amount');
        const $hint = $row.find('.bill-amount-hint');

        if (billId) {
            const bill = availableSupplierPurchases.find(p => p.id == billId);
            const allocatedAmount = billPaymentAllocations[billId] || 0;
            const remainingAmount = bill.total_due - allocatedAmount;

            // Enable amount input but DON'T auto-fill
            $amountInput.attr('max', remainingAmount.toFixed(2)).prop('disabled', false);
            $amountInput.attr('placeholder', `Max: Rs. ${remainingAmount.toFixed(2)}`);
            $amountInput.val(''); // Empty - let user type

            // Show available amount info
            $hint.text(`Available: Rs. ${remainingAmount.toFixed(2)} (${allocatedAmount > 0 ? 'Partially Paid' : 'Unpaid'})`).show();
        } else {
            // Reset when no bill selected
            $amountInput.prop('disabled', true).val('').removeAttr('max').attr('placeholder', 'Select bill first');
            $hint.hide();
        }
    });

    // Amount Input in Allocation (FIXED WITH SYSTEM UPDATE DETECTION)
    $(document).on('input', '.allocation-amount', function() {
        // Check if this is a system update to prevent interference
        const isSystemUpdate = $(this).data('system-update');
        if (isSystemUpdate) {
            console.log('Skipping system update for allocation amount');
            return;
        }

        const $row = $(this).closest('.bill-allocation-row');
        const $hint = $row.find('.bill-amount-hint');
        const billId = $row.find('.bill-select').val();
        const amount = parseFloat($(this).val()) || 0;

        if (!billId) return;

        const bill = availableSupplierPurchases.find(p => p.id == billId);
        if (!bill) return;

        // Calculate available amount for this bill
        const prevAmount = $(this).data('prev-amount') || 0;
        const currentAllocation = billPaymentAllocations[billId] || 0;
        const maxAmount = bill.total_due - (currentAllocation - prevAmount);

        // Validate amount doesn't exceed remaining balance
        if (amount > maxAmount) {
            $(this).val(maxAmount.toFixed(2));
            // Show subtle warning in hint instead of annoying toastr
            $hint.text(`⚠️ Amount limited to remaining balance: Rs. ${maxAmount.toFixed(2)}`).removeClass('text-muted text-success').addClass('text-warning');
            return;
        }

        // Update bill payment tracking
        billPaymentAllocations[billId] = (currentAllocation - prevAmount) + amount;
        $(this).data('prev-amount', amount);

        // If bill becomes fully paid, remove from available bills
        const remainingAfterPayment = bill.total_due - billPaymentAllocations[billId];

        // Update hint with payment status
        if (remainingAfterPayment <= 0.01) {
            $hint.text('✅ Bill will be fully paid').removeClass('text-muted').addClass('text-success');
        } else {
            $hint.text(`💰 Remaining after this payment: Rs. ${remainingAfterPayment.toFixed(2)}`).removeClass('text-success').addClass('text-muted');
        }

        // Update payment method totals (only for manual changes, not system updates)
        const paymentId = $row.closest('.payment-method-item').data('payment-id');
        updatePaymentMethodTotal(paymentId);

        // Refresh available bills list and summary
        populateFlexibleBillsList();
        updateSummaryTotals();

        // Clean up zero allocations
        if (amount <= 0) {
            if (billPaymentAllocations[billId] <= 0) {
                delete billPaymentAllocations[billId];
            }
        }
    });

    // Payment Total Amount Input - SMART DISTRIBUTION TO SELECTED BILLS
    $(document).on('input', '.payment-total-amount', function() {
        try {
            const paymentId = $(this).data('payment-id');
            const totalAmount = parseFloat($(this).val()) || 0;
            const $paymentContainer = $(this).closest('.payment-method-item');

            // Ensure allocation object exists
            if (!paymentMethodAllocations[paymentId]) {
                paymentMethodAllocations[paymentId] = {
                    method: '',
                    totalAmount: 0,
                    billAllocations: {}
                };
            }

            // Check if this is a system update (to prevent recursion)
            const isSystemUpdate = $(this).data('system-update');

            if (!isSystemUpdate) {
                paymentMethodAllocations[paymentId].totalAmount = totalAmount;

                // Get existing bill allocation rows
                const $allocationRows = $paymentContainer.find('.bill-allocation-row');

                if (totalAmount > 0 && $allocationRows.length > 0) {
                    // SMART DISTRIBUTION: Distribute total amount across selected bills
                    let remainingAmount = totalAmount;
                    const billsToUpdate = [];

                    // Collect bills that are selected but get their available amounts
                    $allocationRows.each(function() {
                        const $row = $(this);
                        const $billSelect = $row.find('.bill-select');
                        const billId = $billSelect.val();

                        if (billId) {
                            const bill = availableSupplierPurchases.find(p => p.id == billId);
                            if (bill) {
                                const previousAllocated = billPaymentAllocations[billId] || 0;
                                const availableAmount = bill.total_due - previousAllocated;

                                billsToUpdate.push({
                                    row: $row,
                                    billId: billId,
                                    availableAmount: availableAmount,
                                    invoice: bill.reference_no
                                });
                            }
                        }
                    });

                    // Clear previous amounts from tracking for this payment method only
                    $allocationRows.each(function() {
                        const $row = $(this);
                        const billId = $row.find('.bill-select').val();
                        const $amountInput = $row.find('.allocation-amount');
                        const currentAmount = parseFloat($amountInput.val()) || 0;
                        const prevAmount = $amountInput.data('prev-amount') || 0;

                        if (billId && prevAmount > 0) {
                            // Remove the previous amount from global tracking
                            billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - prevAmount;
                            if (billPaymentAllocations[billId] <= 0) {
                                delete billPaymentAllocations[billId];
                            }
                            $amountInput.data('prev-amount', 0);
                        }
                    });

                    // Recalculate available amounts after clearing previous allocations
                    billsToUpdate.forEach(billInfo => {
                        const bill = availableSupplierPurchases.find(p => p.id == billInfo.billId);
                        if (bill) {
                            const currentAllocated = billPaymentAllocations[billInfo.billId] || 0;
                            billInfo.availableAmount = bill.total_due - currentAllocated;
                        }
                    });

                    // Now distribute the total amount across selected bills
                    billsToUpdate.forEach((billInfo, index) => {
                        if (remainingAmount <= 0) return;

                        // Calculate amount to allocate to this bill
                        const amountToAllocate = Math.min(remainingAmount, billInfo.availableAmount);

                        if (amountToAllocate > 0) {
                            // Update the amount input with system update flag
                            const $amountInput = billInfo.row.find('.allocation-amount');
                            $amountInput.data('system-update', true);
                            $amountInput.val(amountToAllocate.toFixed(2));

                            // Update tracking
                            billPaymentAllocations[billInfo.billId] = (billPaymentAllocations[billInfo.billId] || 0) + amountToAllocate;
                            $amountInput.data('prev-amount', amountToAllocate);

                            // Update hint
                            const $hint = billInfo.row.find('.bill-amount-hint');
                            const remainingAfterPayment = billInfo.availableAmount - amountToAllocate;

                            if (remainingAfterPayment <= 0.01) {
                                $hint.text('✅ Bill will be fully paid').removeClass('text-muted').addClass('text-success');
                            } else {
                                $hint.text(`💰 Remaining: Rs. ${remainingAfterPayment.toFixed(2)}`).removeClass('text-success').addClass('text-muted');
                            }

                            remainingAmount -= amountToAllocate;

                            // Remove system update flag after a delay
                            setTimeout(() => {
                                $amountInput.data('system-update', false);
                            }, 200);
                        }
                    });

                    // Update hint for the payment total with enhanced excess handling
                    const $hint = $paymentContainer.find('.payment-total-hint');
                    const actualAllocated = totalAmount - remainingAmount;

                    if (remainingAmount > 0.01) {
                        // There's excess amount - provide options
                        const hintText = `⚠️ Excess Rs. ${remainingAmount.toFixed(2)} - Will be treated as advance payment`;
                        if ($hint.length === 0) {
                            $paymentContainer.find('.payment-total-amount').after(
                                `<small class="payment-total-hint text-warning d-block">${hintText}</small>`
                            );
                        } else {
                            $hint.text(hintText).removeClass('text-success text-info').addClass('text-warning');
                        }

                        // Show excess amount handling options
                        const $excessOptions = $paymentContainer.find('.excess-options');
                        if ($excessOptions.length === 0) {
                            $paymentContainer.find('.payment-total-hint').after(`
                                <div class="excess-options mt-2 p-2 bg-light rounded border">
                                    <small class="text-muted d-block mb-1">💡 Excess Amount Options:</small>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="advance_${paymentId}" value="advance" checked>
                                        <label class="form-check-label small" for="advance_${paymentId}">
                                            💰 Keep as Advance Payment (Supplier Credit)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="reduce_${paymentId}" value="reduce">
                                        <label class="form-check-label small" for="reduce_${paymentId}">
                                            ✂️ Reduce Total to Rs. ${actualAllocated.toFixed(2)}
                                        </label>
                                    </div>
                                </div>
                            `);
                        } else {
                            // Update existing options
                            $excessOptions.find(`label[for="reduce_${paymentId}"]`).text(`✂️ Reduce Total to Rs. ${actualAllocated.toFixed(2)}`);
                        }
                    } else {
                        // Perfect allocation - no excess
                        const hintText = `✅ Perfect allocation: Rs. ${actualAllocated.toFixed(2)}`;
                        if ($hint.length === 0) {
                            $paymentContainer.find('.payment-total-amount').after(
                                `<small class="payment-total-hint text-success d-block">${hintText}</small>`
                            );
                        } else {
                            $hint.text(hintText).removeClass('text-warning text-info').addClass('text-success');
                        }

                        // Remove excess options if they exist
                        $paymentContainer.find('.excess-options').remove();
                    }

                    // Refresh bills list
                    populateFlexibleBillsList();
                } else {
                    // No allocations or zero amount - remove hints
                    $paymentContainer.find('.payment-total-hint').remove();

                    if (totalAmount === 0) {
                        // Clear all amounts if total is zero
                        $allocationRows.each(function() {
                            $(this).find('.allocation-amount').val('');
                        });
                    }
                }
            }

            updateSummaryTotals();
        } catch (error) {
            console.error('Error in payment total amount input:', error);
        }
    });

    // Handle Excess Amount Options
    $(document).on('change', '.excess-option', function() {
        const $paymentContainer = $(this).closest('.payment-method-item');
        const paymentId = $paymentContainer.data('payment-id');
        const $totalInput = $paymentContainer.find('.payment-total-amount');
        const selectedOption = $(this).val();

        if (selectedOption === 'reduce') {
            // Calculate actual allocated amount
            let actualAllocated = 0;
            $paymentContainer.find('.allocation-amount').each(function() {
                actualAllocated += parseFloat($(this).val()) || 0;
            });

            // Update total to match allocations
            $totalInput.data('system-update', true);
            $totalInput.val(actualAllocated.toFixed(2));

            // Update tracking
            paymentMethodAllocations[paymentId].totalAmount = actualAllocated;

            // Remove excess options
            $paymentContainer.find('.excess-options').remove();

            // Update hint
            const $hint = $paymentContainer.find('.payment-total-hint');
            $hint.text(`✅ Reduced to allocated amount: Rs. ${actualAllocated.toFixed(2)}`).removeClass('text-warning').addClass('text-success');

            // Remove system update flag
            setTimeout(() => {
                $totalInput.data('system-update', false);
            }, 100);

            toastr.success(`Payment amount reduced to Rs. ${actualAllocated.toFixed(2)} (no excess)`);
        } else if (selectedOption === 'advance') {
            toastr.info('Excess amount will be kept as advance payment (supplier credit)');
        }

        updateSummaryTotals();
    });

    // Add to Payment from Bill (Quick Add)
    $(document).on('click', '.add-to-payment-btn', function() {
        const billId = $(this).data('bill-id');
        const bill = availableSupplierPurchases.find(p => p.id == billId);

        if ($('.payment-method-item').length === 0) {
            addFlexiblePayment();
        }

        // Find the first payment method or create one
        const $firstPayment = $('.payment-method-item').first();
        const paymentId = $firstPayment.data('payment-id');

        // Add allocation
        addBillAllocation(paymentId);

        // Pre-select the bill
        const $lastAllocation = $firstPayment.find('.bill-allocation-row').last();
        $lastAllocation.find('.bill-select').val(billId).trigger('change');

        // Set full amount
        const allocatedAmount = billPaymentAllocations[billId] || 0;
        const remainingAmount = bill.total_due - allocatedAmount;
        $lastAllocation.find('.allocation-amount').val(remainingAmount.toFixed(2)).trigger('input');

        toastr.success(`Added ${bill.reference_no} to payment`);
    });
});

// Copy reference number function
function copyReferenceNumber() {
    const refNumber = $('#receiptReferenceNo').text();
    navigator.clipboard.writeText(refNumber).then(() => {
        toastr.success('Reference number copied to clipboard!');
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = refNumber;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        toastr.success('Reference number copied!');
    });
}

// Close receipt modal and reload
function closeReceiptAndReload() {
    $('#paymentReceiptModal').modal('hide');
    setTimeout(() => {
        window.location.reload();
    }, 300);
}

</script>
@endsection

