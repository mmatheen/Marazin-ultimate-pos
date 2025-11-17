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
        max-height: 500px;
        overflow-y: auto;
    }
    
    .payment-methods-container {
        max-height: 500px;
        overflow-y: auto;
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
                            <h3 class="page-title">
                                <i class="fas fa-credit-card"></i> Purchase Bulk Payment System
                                <small class="text-muted d-block mt-1">Pay multiple purchase bills using single or multiple payment methods</small>
                            </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('manage-bulk-payments') }}">Bulk payments</a></li>
                                <li class="breadcrumb-item active">Add Purchase Payments</li>
                            </ul>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('manage-bulk-payments') }}" class="btn btn-outline-primary">
                                <i class="feather-list"></i> Manage Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   
   
        <!-- Step 1: Supplier Selection -->
        <div class="card mb-4 shadow-sm rounded border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-truck"></i> Step 1: Select Supplier</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="supplierSelect">Choose Supplier with Due Bills</label>
                    <select id="supplierSelect" class="form-control select2">
                        <option value="">🔄 Loading suppliers...</option>
                    </select>
                    <small class="form-text text-muted">Select a supplier to see their due bills and payment options</small>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadSuppliersForBulkPayment()">
                            <i class="fas fa-refresh"></i> Reload Suppliers
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="debugSupplierData()">
                            <i class="fas fa-bug"></i> Debug Supplier
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="window.testAPIs()">
                            <i class="fas fa-flask"></i> Test APIs
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="window.testAutoAllocation()">
                            <i class="fas fa-magic"></i> Test Auto-Apply
                        </button>
                    </div>
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

                <!-- Payment Type Selection -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card p-3 border border-primary">
                            <h5 class="text-primary">Payment Options</h5>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentType" id="payOpeningBalance" value="opening_balance">
                                <label class="form-check-label" for="payOpeningBalance">
                                    <strong>Pay Opening Balance Only</strong>
                                    <small class="d-block text-muted">Settle supplier's opening balance (not related to any purchase)</small>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentType" id="payPurchaseDues" value="purchase_dues" checked>
                                <label class="form-check-label" for="payPurchaseDues">
                                    <strong>Pay Purchase Dues</strong>
                                    <small class="d-block text-muted">Pay against specific purchase invoices</small>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentType" id="payBoth" value="both">
                                <label class="form-check-label" for="payBoth">
                                    <strong>Pay Both (Opening Balance + Purchase Dues)</strong>
                                    <small class="d-block text-muted">First settle opening balance, then apply to purchases</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="purchasesListContainer" class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6>Purchase Bills</h6>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="reloadPurchases()" id="reloadPurchasesBtn" style="display: none;">
                            <i class="fas fa-refresh"></i> Reload Purchases
                        </button>
                    </div>
                    <table id="purchasesList" class="table table-striped" style="margin-bottom: 70px; margin-top: 30px">
                        <thead>
                            <tr>
                                <th>Purchase ID</th>
                                <th>Purchase Date</th>
                                <th>Final Total</th>
                                <th>Total Paid</th>
                                <th>Total Due</th>
                                <th>Payment Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    
                    <!-- Individual Payment Total Display -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5>Individual Payments Total: <span id="individualPaymentTotal">Rs. 0.00</span></h5>
                                <small class="text-muted">This shows the sum of individual payment amounts entered above</small>
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
                                <span class="badge bg-info ms-2" id="methodModeIndicator">Single Mode</span>
                            </label>
                            <div class="input-group">
                                <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
                                    <option value="cash" selected><i class="fas fa-money-bill"></i> Cash</option>
                                    <option value="card">Credit Card</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="multiple">🔄 Multiple Methods</option>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" id="toggleMultiMode" title="Switch to Multi-Method Mode">
                                    <i class="fas fa-layer-group"></i>
                                </button>
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
                <div id="multiMethodContainer" class="mb-4 d-none">
                    <div class="card border-primary">
                        <div class="card-body p-0">
                            
                            <!-- Two Column Layout -->
                            <div class="row g-0">
                                <!-- Left Column: Available Bills -->
                                <div class="col-md-6 border-end">
                                    <div class="p-3">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-file-invoice-dollar"></i> Outstanding Purchase Bills
                                        </h6>
                                        <div id="availableBillsList" class="bill-items-container">
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
                                        <div id="flexiblePaymentsList" class="payment-methods-container">
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
        
        <!-- Submit Button (Hidden by default) -->
        <div id="submitButtonSection" style="display: none;">
            <button type="button" id="submitBulkPayment" class="btn btn-primary btn-lg w-100">
                <i class="fas fa-credit-card"></i> Submit Payment
            </button>
        </div>
    </form>
</div>
<div class="modal fade" id="flexibleBulkPaymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-layer-group"></i> Flexible Multi-Method Purchase Bulk Payment
                    <small class="d-block">Different payment methods for different purchase bills</small>
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

<script>
// Global variables for tracking bill payment allocations
var availableSupplierPurchases = [];
var billPaymentAllocations = {};

// Ensure jQuery is loaded before defining functions
$(document).ready(function() {
    // Define functions globally to be accessible from onclick handlers
    window.loadSuppliersForBulkPayment = function() {
    console.log('Loading suppliers for bulk payment...');
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
    console.log('Current user authenticated:', $('meta[name="csrf-token"]').length > 0);
    
    $.ajax({
        url: '/supplier-get-all',
        type: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Supplier response for bulk payment:', response);
            var supplierSelect = $('#supplierSelect');
            
            // Clear loading message and reset select
            supplierSelect.empty();
            supplierSelect.append('<option value="" selected disabled>Choose Supplier with Due Bills</option>');
            
            console.log('Cleared select dropdown, current options:', supplierSelect.find('option').length);
            
            // Handle different response structures
            let suppliers = [];
            
            console.log('Raw API response:', response);
            
            if (response.status === 200 && Array.isArray(response.message)) {
                suppliers = response.message;
                console.log('Using response.message structure');
            } else if (response.status === 200 && Array.isArray(response.data)) {
                suppliers = response.data;
                console.log('Using response.data structure');
            } else if (Array.isArray(response)) {
                suppliers = response;
                console.log('Using direct array response');
            } else if (response.message && Array.isArray(response.message)) {
                suppliers = response.message;
                console.log('Using nested response.message structure');
            } else {
                console.log('Unknown response structure:', response);
            }
            
            console.log('Processed suppliers array:', suppliers, 'Length:', suppliers.length);
            
            if (suppliers && suppliers.length > 0) {
                var suppliersWithDue = 0;
                
                suppliers.forEach(function(supplier) {
                    // Use the correct field names from SupplierController
                    var openingBalance = parseFloat(supplier.opening_balance) || 0;
                    var purchaseDue = parseFloat(supplier.total_purchase_due) || 0;
                    var currentBalance = parseFloat(supplier.current_balance) || 0;
                    
                    // Calculate total due - current balance represents what we owe the supplier
                    var totalDue = currentBalance > 0 ? currentBalance : 0;
                    
                    // Get supplier name
                    var lastName = supplier.last_name ? supplier.last_name : '';
                    var fullName = supplier.full_name || (supplier.first_name + (lastName ? ' ' + lastName : ''));
                    
                    // Show suppliers who have due amounts OR opening balance
                    if (totalDue > 0.01 || openingBalance > 0.01) {
                        var displayText = fullName;
                        
                        // Add due amount if exists
                        if (totalDue > 0) {
                            displayText += ' (Due: Rs. ' + totalDue.toFixed(2) + ')';
                        }
                        
                        // Add opening balance info if exists
                        if (openingBalance > 0) {
                            displayText += ' [Opening: Rs. ' + openingBalance.toFixed(2) + ']';
                        }
                        
                        console.log('Adding supplier option:', {
                            id: supplier.id,
                            name: fullName,
                            openingBalance: openingBalance,
                            purchaseDue: purchaseDue,
                            totalDue: totalDue,
                            currentBalance: currentBalance
                        });
                        
                        supplierSelect.append(
                            '<option value="' + supplier.id +
                            '" data-opening-balance="' + openingBalance +
                            '" data-purchase-due="' + purchaseDue +
                            '" data-total-due="' + totalDue +
                            '" data-current-balance="' + currentBalance +
                            '" data-full-name="' + fullName +
                            '">' + displayText + '</option>'
                        );
                        
                        suppliersWithDue++;
                    } else {
                        console.log('Skipping supplier (no due amount):', {
                            id: supplier.id,
                            name: fullName,
                            openingBalance: openingBalance,
                            purchaseDue: purchaseDue,
                            totalDue: totalDue,
                            currentBalance: currentBalance
                        });
                    }
                });
                
                console.log('Loaded ' + suppliersWithDue + ' suppliers with due amounts out of ' + suppliers.length + ' total suppliers');
                
                // Show message if no suppliers with due amounts found
                if (suppliersWithDue === 0) {
                    supplierSelect.append('<option value="" disabled>No suppliers with outstanding due amounts found</option>');
                }
                
                // Trigger select2 to refresh
                if ($('#supplierSelect').hasClass('select2-hidden-accessible')) {
                    $('#supplierSelect').trigger('change.select2');
                }
            } else {
                console.error("No suppliers found in response:", response);
                supplierSelect.append('<option value="" disabled>No suppliers found</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error loading suppliers:", {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            
            $('#supplierSelect').empty()
                .append('<option value="">Choose Supplier with Due Bills</option>')
                .append('<option disabled>Error loading suppliers. Please refresh the page.</option>');
            
            // Show user-friendly error message
            if (xhr.status === 403) {
                alert('Access denied. Please check your permissions.');
            } else if (xhr.status === 401) {
                alert('Session expired. Please log in again.');
            } else {
                alert('Failed to load suppliers. Please refresh the page and try again.');
            }
        }
    });
    };

    // Global variables for flexible payment system
    let flexiblePaymentCounter = 0;
    let paymentMethodAllocations = {};
    let billPaymentAllocations = {};
    let availableSupplierPurchases = [];
    
    // Define debug function globally
    window.debugSupplierData = function() {
        var supplierId = $('#supplierSelect').val();
        var selectedOption = $('#supplierSelect').find(':selected');
        
        console.log('=== SUPPLIER DEBUG INFO ===');
        console.log('Selected supplier ID:', supplierId);
        console.log('Selected option text:', selectedOption.text());
        console.log('Selected option data attributes:', {
            'opening-balance': selectedOption.data('opening-balance'),
            'purchase-due': selectedOption.data('purchase-due'), 
            'total-due': selectedOption.data('total-due'),
            'current-balance': selectedOption.data('current-balance')
        });
        console.log('Global variables:', {
            originalOpeningBalance: window.originalOpeningBalance,
            purchaseDueAmount: window.purchaseDueAmount,
            selectedSupplierId: window.selectedSupplierId
        });
        
        if (supplierId) {
            loadSupplierPurchases(supplierId);
        } else {
            alert('Please select a supplier first');
        }
    };

    // Additional initialization
    console.log('Purchase bulk payment page initialization...');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');
    console.log('CSRF token present:', $('meta[name="csrf-token"]').length > 0);
    console.log('CSRF token value:', $('meta[name="csrf-token"]').attr('content'));
    console.log('Supplier select element found:', $('#supplierSelect').length);
    console.log('Current URL:', window.location.href);
    
    // Test a simple AJAX call to verify connectivity
    setTimeout(function() {
        console.log('Testing basic connectivity...');
        $.ajax({
            url: '/dashboard-data',
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                console.log('✅ Basic AJAX connectivity working');
            },
            error: function(xhr) {
                console.log('❌ Basic AJAX failed:', xhr.status, xhr.statusText);
            }
        });
    }, 1000);
    
    // Initialize select2 with proper settings  
    if (typeof $.fn.select2 !== 'undefined') {
        $('#supplierSelect').select2({
            placeholder: "Select Supplier",
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true
        });
        console.log('Select2 initialized on purchase bulk payment page');
        
        // Add event listener to ensure search input gets focus when dropdown opens
        $('#supplierSelect').on('select2:open', function() {
            setTimeout(function() {
                var searchField = document.querySelector('.select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 100);
        });
    } else {
        console.log('Select2 not available, using regular select');
    }
    
    // Set today's date as default for "Paid On" field
    var today = new Date();
    var todayFormatted = today.getFullYear() + '-' + 
        String(today.getMonth() + 1).padStart(2, '0') + '-' + 
        String(today.getDate()).padStart(2, '0');
    $('input[name="payment_date"]').val(todayFormatted);
    console.log('Set default date to today:', todayFormatted);
    
    // Initialize DataTable for purchases list
    if ($('#purchasesList').length > 0 && typeof $.fn.DataTable !== 'undefined') {
        $('#purchasesList').DataTable({
            columns: [
                { title: "Purchase ID (Invoice)" },
                { title: "Final Total" },
                { title: "Total Paid" },
                { title: "Total Due" },
                { title: "Payment Amount" }
            ],
            paging: true,
            searching: true,
            ordering: true
        });
        console.log('DataTable initialized for purchases list');
    }
    
    // Load suppliers immediately
    console.log('Page ready, starting supplier load...');
    console.log('DOM Ready - checking elements:');
    console.log('- supplierSelect exists:', $('#supplierSelect').length);
    console.log('- CSRF token:', $('meta[name="csrf-token"]').attr('content'));
    console.log('- jQuery version:', $.fn.jquery);
    
    loadSuppliersForBulkPayment();
    
    // Also try again after a short delay in case of timing issues
    setTimeout(function() {
        if ($('#supplierSelect option').length <= 1) {
            console.log('Retrying supplier load after timeout...');
            loadSuppliersForBulkPayment();
        }
    }, 2000);
    
    // Add reload purchases function
    window.reloadPurchases = function() {
        var supplierId = $('#supplierSelect').val();
        if (supplierId) {
            console.log('Reloading purchases for supplier:', supplierId);
            loadSupplierPurchases(supplierId);
        } else {
            alert('Please select a supplier first');
        }
    };
    
    // Debug function - can be called from console: testAPIs()
    window.testAPIs = function() {
        console.log('=== TESTING API ENDPOINTS ===');
        
        // Test suppliers API
        console.log('1. Testing suppliers API...');
        $.ajax({
            url: '/supplier-get-all',
            type: 'GET',
            success: function(response) {
                console.log('Suppliers API Response:', response);
                console.log('Number of suppliers:', (response.message || []).length);
            },
            error: function(xhr, status, error) {
                console.error('Suppliers API Error:', {status, error, response: xhr.responseText});
            }
        });
        
        // Test purchases API  
        console.log('2. Testing purchases API...');
        $.ajax({
            url: '/get-all-purchases',
            type: 'GET',
            success: function(response) {
                console.log('Purchases API Response:', response);
                console.log('Number of purchases:', (response.purchases || []).length);
                if (response.purchases && response.purchases.length > 0) {
                    console.log('Sample purchase:', response.purchases[0]);
                    console.log('Suppliers in purchases:', [...new Set(response.purchases.map(p => p.supplier_id))]);
                }
            },
            error: function(xhr, status, error) {
                console.error('Purchases API Error:', {status, error, response: xhr.responseText});
            }
        });
        
        console.log('API tests started. Check console for results...');
    };
    
    // Debug function to test auto-allocation
    window.testAutoAllocation = function() {
        console.log('=== TESTING AUTO-ALLOCATION ===');
        
        // Check current payment method
        var paymentMethod = $('#paymentMethod').val();
        console.log('Current payment method:', paymentMethod);
        
        if (paymentMethod === 'multiple') {
            alert('Auto-allocation testing works with single payment methods. Please select Cash, Cheque, or Bank Transfer first, then try again.');
            return;
        }
        
        // Check if purchase dues are selected
        var paymentType = $('input[name="paymentType"]:checked').val();
        console.log('Current payment type:', paymentType);
        
        if (!paymentType) {
            alert('Please select a payment option (Opening Balance, Purchase Dues, or Both) first.');
            return;
        }
        
        // Test with a small amount
        var testAmount = '1000';
        console.log('Setting test amount:', testAmount);
        $('#globalPaymentAmount').val(testAmount).trigger('input');
        
        setTimeout(function() {
            var appliedAmounts = [];
            $('.reference-amount').each(function() {
                var val = $(this).val();
                if (val && parseFloat(val) > 0) {
                    appliedAmounts.push(parseFloat(val));
                }
            });
            
            if (appliedAmounts.length > 0) {
                console.log('✅ Auto-allocation working! Applied amounts:', appliedAmounts);
                alert('✅ Auto-allocation is working! Check console for details. Applied to ' + appliedAmounts.length + ' bills.');
            } else {
                console.log('❌ Auto-allocation not working. No amounts applied to bills.');
                alert('❌ Auto-allocation not working. Check console for debugging information.');
            }
        }, 500);
    };
});

// Supplier selection change handler
$(document).on('change', '#supplierSelect', function() {
    console.log('Supplier selected...');
    
    var selectedOption = $(this).find(':selected');
    var supplierId = $(this).val();
    
    if (!supplierId) {
        console.log('No supplier selected - hiding sections');
        $('#supplierSummarySection').hide();
        $('#paymentMethodSection').hide();
        $('#submitButtonSection').hide();
        $('#reloadPurchasesBtn').hide();
        return;
    }
    
    console.log('Selected supplier ID:', supplierId);
    console.log('Selected option data:', {
        'opening-balance': selectedOption.data('opening-balance'),
        'purchase-due': selectedOption.data('purchase-due'),
        'total-due': selectedOption.data('total-due'),
        'current-balance': selectedOption.data('current-balance')
    });
    
    // Show supplier summary and payment method section
    $('#supplierSummarySection').show();
    $('#paymentMethodSection').show();
    
    // Get supplier data from the selected option
    var supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
    var purchaseDue = parseFloat(selectedOption.data('purchase-due')) || 0;
    var totalDue = parseFloat(selectedOption.data('total-due')) || 0;
    var currentBalance = parseFloat(selectedOption.data('current-balance')) || 0;
    
    console.log('Parsed supplier balances:', {
        openingBalance: supplierOpeningBalance,
        purchaseDue: purchaseDue,
        totalDue: totalDue,
        currentBalance: currentBalance
    });
    
                // Update balance cards with proper amounts
                $('#openingBalance').text('Rs. ' + supplierOpeningBalance.toFixed(2));
                $('#totalPurchasesAmount').text('Rs. ' + 0.00.toFixed(2)); // Will be updated by purchase loading
                $('#totalPaidAmount').text('Rs. ' + 0.00.toFixed(2)); // Will be updated by purchase loading  
                $('#totalDueAmount').text('Rs. ' + 0.00.toFixed(2)); // Will be updated by purchase loading
                $('#totalSupplierDue').text('Rs. ' + totalDue.toFixed(2));
                
                // Clear "No outstanding purchase bills found" message
                $('#purchasesList tbody').empty();
                $('#purchasesList tbody').append('<tr><td colspan="5" class="text-center">Loading purchases...</td></tr>');    // Store original data globally
    window.originalOpeningBalance = supplierOpeningBalance;
    window.purchaseDueAmount = purchaseDue;
    window.selectedSupplierId = supplierId;
    
    // Reset and clear previous validation errors
    $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
    $('#globalPaymentAmount').val('');
    
    // Load purchases for the selected supplier
    loadSupplierPurchases(supplierId);
    
    // Also load for multi-method if that mode is active
    const isMultiMethodActive = $('#paymentMethod').val() === 'multiple';
    console.log('Multi-method mode active:', isMultiMethodActive);
    if (isMultiMethodActive) {
        console.log('Loading purchases for multi-method mode due to supplier change');
        loadSupplierPurchasesForMultiMethod(supplierId);
    }
    
    // Show reload button
    $('#reloadPurchasesBtn').show();
});

// Function to reload purchases (for debugging)
function reloadPurchases() {
    var supplierId = $('#supplierSelect').val() || window.selectedSupplierId;
    if (supplierId) {
        console.log('Manual reload of purchases for supplier:', supplierId);
        console.log('Clearing table and reloading...');
        $('#purchasesList tbody').empty();
        $('#purchasesList tbody').append('<tr><td colspan="5" class="text-center">Loading purchases...</td></tr>');
        loadSupplierPurchases(supplierId);
    } else {
        console.log('No supplier selected for reload');
        alert('Please select a supplier first');
    }
}

// Removed duplicate debugSupplierData function - now defined globally above

// Add bill allocation to a payment method for purchases
function addBillAllocation(paymentId) {
    // Filter bills - exclude fully paid ones and show remaining amounts
    const availableBills = availableSupplierPurchases.filter(purchase => {
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = purchase.total_due - allocatedAmount;
        return remainingAmount > 0.01; // Avoid tiny remaining amounts
    });
    
    if (availableBills.length === 0) {
        alert('No outstanding purchase bills available for allocation. All bills are either fully paid or allocated.');
        return;
    }
    
    const allocationId = `allocation_${Date.now()}`;
    
    // Create enhanced bill options with status indicators
    const billOptions = availableBills.map(purchase => {
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = purchase.total_due - allocatedAmount;
        const statusIcon = allocatedAmount > 0 ? '🟡' : '🔴'; // Yellow for partial, Red for unpaid
        const statusText = allocatedAmount > 0 ? 'Partially Paid' : 'Unpaid';
        const invoiceDisplay = purchase.reference_no || purchase.invoice_no || 'PUR-' + purchase.id;
        
        return `<option value="${purchase.id}" data-due="${purchase.total_due}" data-invoice="${invoiceDisplay}" data-remaining="${remainingAmount}">
            ${statusIcon} ${invoiceDisplay} - Remaining: Rs.${remainingAmount.toFixed(2)} (${statusText})
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
}

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
                    $('#submitButtonSection').hide();
                } else {
                    // Show payment method section
                    $('#paymentMethodSection').show();
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
    
    console.log('=== PURCHASE AUTO-ALLOCATION ===');
    console.log('Global amount changed:', globalAmount, 'Payment type:', paymentType);
    console.log('Supplier opening balance:', supplierOpeningBalance);
    console.log('Remaining amount to distribute:', remainingAmount);
    
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
        console.log('Applying to opening balance only...');
        // Only apply to opening balance
        let newOpeningBalance = Math.max(0, supplierOpeningBalance - remainingAmount);
        $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));
        
        // Clear all purchase payment inputs
        $('.reference-amount').val(0);
        console.log('Opening balance updated to:', newOpeningBalance, 'Cleared all purchase amounts');
        
    } else if (paymentType === 'purchase_dues') {
        console.log('Applying to purchase dues only...');
        var totalApplied = 0;
        // Only apply to purchase dues in order
        $('.reference-amount').each(function() {
            // Column 4 contains the Total Due amount (0-indexed: Purchase ID, Date, Final Total, Paid, Due, Input)
            var referenceDue = parseFloat($(this).closest('tr').find('td:eq(4)').text().replace('Rs. ', '')) || 0;
            if (remainingAmount > 0 && referenceDue > 0) {
                var paymentAmount = Math.min(remainingAmount, referenceDue);
                $(this).val(paymentAmount.toFixed(2));
                remainingAmount -= paymentAmount;
                totalApplied += paymentAmount;
                console.log('Applied', paymentAmount, 'to purchase with due:', referenceDue);
            } else {
                $(this).val(0);
            }
        });
        console.log('Total applied to purchases:', totalApplied, 'Remaining:', remainingAmount);
        
        // Don't change opening balance
        $('#openingBalance').text('Rs. ' + supplierOpeningBalance.toFixed(2));
        
    } else if (paymentType === 'both') {
        console.log('Applying to both opening balance and purchase dues...');
        // First deduct from opening balance
        let newOpeningBalance = supplierOpeningBalance;
        if (newOpeningBalance > 0 && remainingAmount > 0) {
            if (remainingAmount <= newOpeningBalance) {
                newOpeningBalance -= remainingAmount;
                console.log('Applied', remainingAmount, 'to opening balance. New balance:', newOpeningBalance);
                remainingAmount = 0;
            } else {
                console.log('Applied', newOpeningBalance, 'to opening balance (full amount)');
                remainingAmount -= newOpeningBalance;
                newOpeningBalance = 0;
                console.log('Remaining amount after opening balance:', remainingAmount);
            }
        }
        $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));
        
        var totalAppliedToPurchases = 0;
        // Then apply remaining amount to purchases in order
        $('.reference-amount').each(function() {
            // Column 4 contains the Total Due amount (0-indexed: Purchase ID, Date, Final Total, Paid, Due, Input)
            var referenceDue = parseFloat($(this).closest('tr').find('td:eq(4)').text().replace('Rs. ', '')) || 0;
            if (remainingAmount > 0 && referenceDue > 0) {
                var paymentAmount = Math.min(remainingAmount, referenceDue);
                $(this).val(paymentAmount.toFixed(2));
                remainingAmount -= paymentAmount;
                totalAppliedToPurchases += paymentAmount;
                console.log('Applied', paymentAmount, 'to purchase with due:', referenceDue);
            } else {
                $(this).val(0);
            }
        });
        console.log('Total applied to purchases:', totalAppliedToPurchases, 'Final remaining:', remainingAmount);
    }
    
    console.log('=== AUTO-ALLOCATION COMPLETE ===');
    
    // Update the individual payment total display
    updateIndividualPaymentTotal();
});

// Handle bulk payment submission for purchase page
$(document).on('click', '#submitBulkPayment', function() {
    console.log('Submit purchase bulk payment button clicked');
    
    var supplierId = $('#supplierSelect').val();
    var paymentMethod = $('#paymentMethod').val();
    
    console.log('Supplier ID:', supplierId);
    console.log('Payment Method raw:', paymentMethod);
    
    // Check if this is multi-method payment
    if (paymentMethod === 'multiple') {
        return submitMultiMethodPayment();
    }
    
    // Get payment date
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
    
    console.log('Form values collected:', {
        supplierId: supplierId,
        paymentMethod: paymentMethod,
        paymentDate: paymentDate,
        globalPaymentAmount: globalPaymentAmount,
        paymentType: paymentType
    });

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
        payment_type: paymentType.replace('purchase_dues', 'sale_dues'), // Backend expects 'sale_dues' for consistency
        payments: purchasePayments // Backend expects 'payments' field
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

    console.log('Purchase payment data being sent:', paymentData);

    $.ajax({
        url: '/submit-bulk-payment',
        method: 'POST',
        data: paymentData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Purchase payment submission successful:', response);
            
            // Show success toastr notification
            if (typeof toastr !== 'undefined') {
                toastr.success('Purchase payment submitted successfully!', 'Success', {
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
            console.error('Purchase payment submission failed:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            
            // Show error toastr notification
            var errorMessage = 'Purchase payment submission failed. Please try again.';
            
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

// Multi-Method Payment Functions for Enhanced Purchase UI
function togglePaymentFields() {
    const paymentMethod = $('#paymentMethod').val();
    const isMultiMethod = paymentMethod === 'multiple';
    
    console.log('Purchase payment method changed to:', paymentMethod, 'isMultiMethod:', isMultiMethod);
    
    // Hide all conditional fields first
    $('#cardFields, #chequeFields, #bankTransferFields').addClass('d-none');
    $('#multiMethodContainer').toggleClass('d-none', !isMultiMethod);
    
    // Hide/Show the main purchases list when multiple methods is selected
    $('#purchasesListContainer').toggleClass('d-none', isMultiMethod);
    
    console.log('Container visibility after toggle:');
    console.log('- multiMethodContainer hidden:', $('#multiMethodContainer').hasClass('d-none'));
    console.log('- purchasesListContainer hidden:', $('#purchasesListContainer').hasClass('d-none'));
    
    // Update mode indicator
    const modeIndicator = $('#methodModeIndicator');
    if (isMultiMethod) {
        console.log('Switching to multi-method mode...');
        modeIndicator.text('Multi Mode').removeClass('bg-info').addClass('bg-success');
        $('#globalPaymentAmount').prop('disabled', true).val('').attr('placeholder', 'Calculated from table');
        
        // Load supplier bills for multi-method
        const supplierId = $('#supplierSelect').val();
        console.log('Selected supplier ID for multi-method:', supplierId);
        
        if (supplierId) {
            console.log('Loading purchases for multi-method...');
            loadSupplierPurchasesForMultiMethod(supplierId);
        } else {
            console.log('No supplier selected, showing message');
            $('#availableBillsList').html('<div class="text-center text-muted p-3">Please select a supplier first</div>');
        }
    } else {
        console.log('Switching to single-method mode...');
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
        $('#submitButtonSection').show();
    } else {
        $('#submitButtonSection').hide();
    }
}

// Multi-Method Group Management
let methodGroupCounter = 0;
// availableSupplierPurchases already declared above - using existing variable

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
            toastr.info('Loading supplier purchase bills...');
            loadSupplierPurchasesForMultiMethod(supplierId);
        } else {
            toastr.warning('No outstanding purchase bills available for this supplier');
        }
        return;
    }
    
    const groupIndex = methodGroupCounter++;
    console.log('Creating group with', availableSupplierPurchases.length, 'available purchases');
    
    const purchasesOptions = availableSupplierPurchases.map(purchase => 
        `<option value="${purchase.id}" data-due="${purchase.total_due}" data-invoice="${purchase.invoice_no}">
            ${purchase.invoice_no || purchase.id} - Due: Rs.${parseFloat(purchase.total_due).toFixed(2)}
        </option>`
    ).join('');
    
    console.log('Generated purchase options for new group:', purchasesOptions);
    
    // Add group HTML similar to sales but adapted for purchases
    // [Implementation similar to sales version but with purchase-specific terminology]
}

// Load supplier purchases for flexible many-to-many system  
function loadSupplierPurchasesForMultiMethod(supplierId) {
    console.log('Loading purchase bills for flexible many-to-many system:', supplierId);
    
    $.ajax({
        url: '/get-all-purchases',
        type: 'GET',
        dataType: 'json',
        data: {
            supplier_id: supplierId
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Multi-method purchases response:', response);
            
            if (response.purchases && response.purchases.length > 0) {
                // Filter for bills for the specific supplier with outstanding dues
                availableSupplierPurchases = response.purchases.filter(purchase => {
                    const isCorrectSupplier = purchase.supplier_id == supplierId;
                    // Use purchasing_status instead of status
                    const purchasingStatus = purchase.purchasing_status || purchase.status || '';
                    const isValidStatus = purchasingStatus.toLowerCase() === 'received' || 
                                         purchasingStatus.toLowerCase() === 'ordered' || 
                                         purchasingStatus.toLowerCase() === 'pending';
                    
                    // Check if there's outstanding due amount
                    const finalTotal = parseFloat(purchase.final_total) || 0;
                    const totalPaid = parseFloat(purchase.total_paid) || 0;
                    const totalDue = parseFloat(purchase.total_due) || (finalTotal - totalPaid);
                    const hasDue = totalDue > 0.01;
                    
                    console.log(`Purchase ${purchase.id}: supplier=${purchase.supplier_id}, purchasing_status=${purchasingStatus}, total_due=${totalDue}, isCorrectSupplier=${isCorrectSupplier}, isValidStatus=${isValidStatus}, hasDue=${hasDue}`);
                    
                    return isCorrectSupplier && hasDue;
                });
                
                console.log('Outstanding purchase bills for supplier ' + supplierId + ':', availableSupplierPurchases.length);
                
                // Add calculated due amounts to purchases
                availableSupplierPurchases = availableSupplierPurchases.map(purchase => {
                    const finalTotal = parseFloat(purchase.final_total) || 0;
                    const totalPaid = parseFloat(purchase.total_paid) || 0;
                    const totalDue = parseFloat(purchase.total_due) || (finalTotal - totalPaid);
                    
                    return {
                        ...purchase,
                        total_due: totalDue,
                        reference_no: purchase.reference_no || purchase.invoice_no || 'PUR-' + purchase.id
                    };
                });
                
                if (availableSupplierPurchases.length === 0) {
                    console.log('No outstanding purchases found for supplier. All supplier purchases:', response.purchases.filter(p => p.supplier_id == supplierId).map(p => ({
                        id: p.id, 
                        supplier_id: p.supplier_id, 
                        purchasing_status: p.purchasing_status,
                        total_due: p.total_due,
                        final_total: p.final_total,
                        total_paid: p.total_paid
                    })));
                    if (typeof toastr !== 'undefined') toastr.info('No outstanding purchase bills found for this supplier');
                    populateFlexibleBillsList();
                } else {
                    populateFlexibleBillsList();
                    if (typeof toastr !== 'undefined') toastr.success(`${availableSupplierPurchases.length} outstanding purchase bills loaded`);
                }
                
            } else {
                console.log('No purchases found in response');
                availableSupplierPurchases = [];
                populateFlexibleBillsList();
                if (typeof toastr !== 'undefined') toastr.warning('No purchases found for this supplier');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load supplier purchases:', {
                status: status,
                error: error,
                responseText: xhr.responseText,
                statusCode: xhr.status
            });
            if (typeof toastr !== 'undefined') toastr.error('Failed to load supplier purchases: ' + error);
            availableSupplierPurchases = [];
            populateFlexibleBillsList();
        }
    });
}

// Initialize system safety check (variables already declared above)
function initializeFlexiblePaymentSystem() {
    // Variables already declared globally above, just reset values
    flexiblePaymentCounter = 0;
    billPaymentAllocations = {};
    paymentMethodAllocations = {};
    availableSupplierPurchases = [];
    
    console.log('Flexible purchase payment system initialized');
}

// Populate flexible bills list (left side) - adapted for purchases
function populateFlexibleBillsList() {
    let billsHTML = '';
    
    if (availableSupplierPurchases.length === 0) {
        billsHTML = '<div class="alert alert-warning text-center">No outstanding purchase bills found</div>';
    } else {
        availableSupplierPurchases.forEach((purchase) => {
            const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
            const remainingAmount = purchase.total_due - allocatedAmount;
            const isFullyPaid = remainingAmount <= 0;
            
            billsHTML += `
                <div class="bill-item border rounded p-3 mb-2 ${isFullyPaid ? 'bg-light' : 'bg-white'}" data-bill-id="${purchase.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 ${isFullyPaid ? 'text-muted' : 'text-primary'}">
                                ${purchase.invoice_no || purchase.id}
                                ${isFullyPaid ? '<span class="badge bg-success ms-2">PAID</span>' : ''}
                            </h6>
                            <div class="small text-muted">Purchase Bill #${purchase.id}</div>
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
    
    console.log('Populating flexible bills list with', availableSupplierPurchases.length, 'purchases');
    $('#availableBillsList').html(billsHTML);
    console.log('Bills HTML updated, calling updateSummaryTotals...');
    updateSummaryTotals();
}

// Add new flexible payment method
// Update summary totals in flexible payment mode
function updateSummaryTotals() {
    let totalPayments = 0;
    let totalBills = availableSupplierPurchases.length;
    let totalDue = 0;
    
    // Calculate total due from purchases
    availableSupplierPurchases.forEach(purchase => {
        totalDue += parseFloat(purchase.total_due) || 0;
    });
    
    // Calculate total payments
    Object.values(paymentMethodAllocations).forEach(payment => {
        totalPayments += payment.totalAmount || 0;
    });
    
    const balance = totalPayments - totalDue;
    
    // Update summary display
    $('#totalBillsCount').text(totalBills);
    $('#totalDueAmount').text('Rs. ' + totalDue.toFixed(2));
    $('#totalPaymentAmount').text('Rs. ' + totalPayments.toFixed(2));
    $('#balanceAmount').text('Rs. ' + balance.toFixed(2))
        .removeClass('text-success text-danger text-warning')
        .addClass(balance > 0 ? 'text-success' : balance < 0 ? 'text-danger' : 'text-muted');
}

// Generate payment method specific fields
function getPaymentMethodFields(method, paymentId) {
    switch (method) {
        case 'card':
            return `
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Card Number" name="card_number_${paymentId}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Holder Name" name="card_holder_${paymentId}">
                    </div>
                </div>
            `;
        case 'cheque':
            return `
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Cheque Number" name="cheque_number_${paymentId}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Bank Branch" name="cheque_bank_${paymentId}">
                    </div>
                </div>
            `;
        case 'bank_transfer':
            return `
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Bank Account Number" name="bank_account_${paymentId}">
                </div>
            `;
        default:
            return '';
    }
}

// Populate flexible bills list
function populateFlexibleBillsList() {
    const billsList = $('#availableBillsList');
    billsList.empty();
    
    if (availableSupplierPurchases.length === 0) {
        billsList.html('<div class="text-center text-muted p-3">No outstanding purchase bills found</div>');
        return;
    }
    
    availableSupplierPurchases.forEach(purchase => {
        const totalDue = parseFloat(purchase.total_due) || 0;
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = totalDue - allocatedAmount;
        
        if (remainingAmount > 0) {
            const billHtml = `
                <div class="bill-item border rounded p-2 mb-2 bg-white" data-bill-id="${purchase.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>PUR${purchase.id}</strong>
                            <small class="text-muted d-block">${purchase.reference_no || 'No Reference'}</small>
                        </div>
                        <div class="text-end">
                            <div class="text-primary fw-bold">Rs. ${totalDue.toFixed(2)}</div>
                            <small class="text-muted">Remaining: Rs. ${remainingAmount.toFixed(2)}</small>
                        </div>
                    </div>
                </div>
            `;
            billsList.append(billHtml);
        }
    });
}

// Update summary totals in flexible payment mode
function updateSummaryTotals() {
    let totalPayments = 0;
    let totalBills = availableSupplierPurchases.length;
    let totalDue = 0;
    
    // Calculate total due from purchases
    availableSupplierPurchases.forEach(purchase => {
        totalDue += parseFloat(purchase.total_due) || 0;
    });
    
    // Calculate total payments
    Object.values(paymentMethodAllocations).forEach(payment => {
        totalPayments += payment.totalAmount || 0;
    });
    
    const balance = totalPayments - totalDue;
    
    // Update summary display
    $('#totalBillsCount').text(totalBills);
    $('#totalDueAmount').text('Rs. ' + totalDue.toFixed(2));
    $('#totalPaymentAmount').text('Rs. ' + totalPayments.toFixed(2));
    $('#balanceAmount').text('Rs. ' + balance.toFixed(2))
        .removeClass('text-success text-danger text-warning')
        .addClass(balance > 0 ? 'text-success' : balance < 0 ? 'text-danger' : 'text-muted');
}

// Generate payment method specific fields
function getPaymentMethodFields(method, paymentId) {
    switch (method) {
        case 'card':
            return `
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Card Number" name="card_number_${paymentId}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Holder Name" name="card_holder_${paymentId}">
                    </div>
                </div>
            `;
        case 'cheque':
            return `
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Cheque Number" name="cheque_number_${paymentId}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control form-control-sm" placeholder="Bank Branch" name="cheque_bank_${paymentId}">
                    </div>
                </div>
            `;
        case 'bank_transfer':
            return `
                <div class="mb-2">
                    <input type="text" class="form-control form-control-sm" placeholder="Bank Account Number" name="bank_account_${paymentId}">
                </div>
            `;
        default:
            return '';
    }
}

// Populate flexible bills list
function populateFlexibleBillsList() {
    const billsList = $('#availableBillsList');
    billsList.empty();
    
    if (availableSupplierPurchases.length === 0) {
        billsList.html('<div class="text-center text-muted p-3">No outstanding purchase bills found</div>');
        return;
    }
    
    availableSupplierPurchases.forEach(purchase => {
        const totalDue = parseFloat(purchase.total_due) || 0;
        const allocatedAmount = billPaymentAllocations[purchase.id] || 0;
        const remainingAmount = totalDue - allocatedAmount;
        
        if (remainingAmount > 0) {
            const billHtml = `
                <div class="bill-item border rounded p-2 mb-2 bg-white" data-bill-id="${purchase.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>PUR${purchase.id}</strong>
                            <small class="text-muted d-block">${purchase.reference_no || 'No Reference'}</small>
                        </div>
                        <div class="text-end">
                            <div class="text-primary fw-bold">Rs. ${totalDue.toFixed(2)}</div>
                            <small class="text-muted">Remaining: Rs. ${remainingAmount.toFixed(2)}</small>
                        </div>
                    </div>
                </div>
            `;
            billsList.append(billHtml);
        }
    });
}

function addFlexiblePayment() {
    flexiblePaymentCounter++;
    const paymentId = `payment_${flexiblePaymentCounter}`;
    
    console.log('Adding flexible payment with ID:', paymentId);
    
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
                        <option value="cash">💵 Cash</option>
                        <option value="cheque">📄 Cheque</option>
                        <option value="card">💳 Card</option>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
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
                    <i class="fas fa-list"></i> Purchase Bill Allocations
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
    
    $('#flexiblePaymentsList').append(paymentHTML);
    
    // Initialize payment method allocations
    paymentMethodAllocations[paymentId] = {
        method: '',
        totalAmount: 0,
        billAllocations: {},
        details: {}
    };
    
    console.log('Payment method allocations after adding:', paymentMethodAllocations);
    
    updateSummaryTotals();
    
    // Focus on the payment method selector
    $(`select[data-payment-id="${paymentId}"]`).focus();
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
        
        // Update UI elements if they exist (using correct element IDs from the bottom summary)
        const $totalBillsCount = $('#totalBillsCount');
        const $totalDueAmount = $('#totalDueAmount');
        const $totalPaymentAmount = $('#totalPaymentAmount');
        const $balanceAmount = $('#balanceAmount');
        
        if ($totalBillsCount.length) $totalBillsCount.text(totalBills);
        if ($totalDueAmount.length) $totalDueAmount.text(`Rs. ${totalDueAmount.toFixed(2)}`);
        if ($totalPaymentAmount.length) $totalPaymentAmount.text(`Rs. ${totalPaymentAmount.toFixed(2)}`);
        
        // Update balance calculation
        if ($balanceAmount.length) {
            // Enhanced balance display with better messaging
            if (balanceAmount > 0) {
                $balanceAmount.text(`Rs. ${balanceAmount.toFixed(2)}`).removeClass('text-success text-danger').addClass('text-warning');
            } else if (balanceAmount < 0) {
                const excessAmount = Math.abs(balanceAmount);
                $balanceAmount.text(`Rs. ${balanceAmount.toFixed(2)}`).removeClass('text-warning text-success').addClass('text-danger');
                
                // Add excess amount info
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
        
        console.log('Purchase summary totals updated:', { totalBills, totalDueAmount, totalPaymentAmount, balanceAmount });
        
    } catch (error) {
        console.error('Error in updateSummaryTotals:', error);
    }
}

// Initialize flexible many-to-many payment system for purchases
$(document).ready(function() {
    console.log('Flexible Many-to-Many Purchase Payment System Ready');
    
    // Initialize system variables
    initializeFlexiblePaymentSystem();
    
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
    
    // Add Payment Method button
    $('#addFlexiblePayment').click(function() {
        console.log('Adding flexible payment method...');
        addFlexiblePayment();
    });
    
    // Payment method selection change within flexible payments
    $(document).on('change', '.payment-method-select', function() {
        const paymentId = $(this).data('payment-id');
        const method = $(this).val();
        
        console.log('Payment method changed:', {paymentId, method});
        
        if (paymentMethodAllocations[paymentId]) {
            paymentMethodAllocations[paymentId].method = method;
        }
        
        // Show/hide payment details based on method
        const detailsContainer = $(this).closest('.payment-method-item').find('.payment-details-container');
        const paymentFields = detailsContainer.find('.payment-fields');
        
        if (method && method !== 'cash') {
            detailsContainer.show();
            paymentFields.html(getPaymentMethodFields(method, paymentId));
        } else {
            detailsContainer.hide();
            paymentFields.empty();
        }
    });
    
    // Payment amount change with auto-allocation
    $(document).on('input change', '.payment-total-amount', function() {
        const paymentId = $(this).data('payment-id');
        const amount = parseFloat($(this).val()) || 0;
        
        console.log('Payment amount changed for payment', paymentId, ':', amount);
        
        if (paymentMethodAllocations[paymentId]) {
            paymentMethodAllocations[paymentId].totalAmount = amount;
            
            // Auto-allocate this payment amount to available bills
            if (amount > 0) {
                autoAllocateToAvailableBills(paymentId, amount);
            }
        }
        
        updateSummaryTotals();
    });
    
    // Auto-allocate amount to available bills function for multiple payment methods
    function autoAllocateToAvailableBills(paymentId, totalAmount) {
        console.log('Auto-allocating', totalAmount, 'for payment', paymentId);
        
        let remainingAmount = totalAmount;
        
        // Clear existing allocations for this payment method
        $(`.bill-allocation-row[data-payment-id="${paymentId}"]`).remove();
        
        // Reset bill allocations for this payment
        if (paymentMethodAllocations[paymentId]) {
            paymentMethodAllocations[paymentId].billAllocations = {};
        }
        
        // Auto-allocate to bills in order
        if (availableSupplierPurchases && availableSupplierPurchases.length > 0) {
            availableSupplierPurchases.forEach(purchase => {
                if (remainingAmount > 0.01) {
                    const dueAmount = parseFloat(purchase.total_due) || 0;
                    const allocatedAmount = Math.min(remainingAmount, dueAmount);
                    
                    if (allocatedAmount > 0.01) {
                        // Add a bill allocation row for this payment method
                        addBillAllocation(paymentId);
                        
                        // Auto-select the bill and set amount
                        const newAllocationRow = $(`.bill-allocation-row[data-payment-id="${paymentId}"]:last`);
                        const billSelect = newAllocationRow.find('.bill-select');
                        const amountInput = newAllocationRow.find('.allocation-amount');
                        
                        // Populate bill dropdown and select this purchase
                        billSelect.val(purchase.id).trigger('change');
                        amountInput.val(allocatedAmount.toFixed(2));
                        
                        // Update allocation tracking
                        if (!paymentMethodAllocations[paymentId].billAllocations) {
                            paymentMethodAllocations[paymentId].billAllocations = {};
                        }
                        paymentMethodAllocations[paymentId].billAllocations[purchase.id] = allocatedAmount;
                        
                        remainingAmount -= allocatedAmount;
                        
                        console.log('Auto-allocated', allocatedAmount, 'to bill', purchase.id);
                    }
                }
            });
            
            console.log('Auto-allocation complete. Remaining amount:', remainingAmount);
        }
    }
    
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
    
    // Supplier selection change - load purchases for multi-method
    $('#supplierSelect').change(function() {
        const supplierId = $(this).val();
        const paymentMethod = $('#paymentMethod').val();
        
        if (supplierId && paymentMethod === 'multiple') {
            // Clear existing data and reload bills
            $('#billsPaymentTableBody').empty();
            availableSupplierPurchases = [];
            
            loadSupplierPurchasesForMultiMethod(supplierId);
        }
    });
    
    // Add Bill Allocation event handler
    $(document).on('click', '.add-bill-allocation-btn', function() {
        const paymentId = $(this).data('payment-id');
        addBillAllocation(paymentId);
    });
    
    // Remove Bill Allocation event handler
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
            // Note: populateFlexibleBillsList and updateSummaryTotals functions need to be implemented
            if (typeof populateFlexibleBillsList === 'function') populateFlexibleBillsList();
            if (typeof updateSummaryTotals === 'function') updateSummaryTotals();
        });
    });
    
    // Bill Selection in Allocation
    $(document).on('change', '.bill-allocation-row .bill-select', function() {
        const billId = $(this).val();
        const $row = $(this).closest('.bill-allocation-row');
        const $amountInput = $row.find('.allocation-amount');
        const $hint = $row.find('.bill-amount-hint');
        
        if (billId) {
            const bill = availableSupplierPurchases.find(p => p.id == billId);
            if (bill) {
                const allocatedAmount = billPaymentAllocations[billId] || 0;
                const remainingAmount = bill.total_due - allocatedAmount;
                
                // Enable amount input
                $amountInput.attr('max', remainingAmount.toFixed(2)).prop('disabled', false);
                $amountInput.attr('placeholder', `Max: Rs. ${remainingAmount.toFixed(2)}`);
                $amountInput.val('');
                
                // Show available amount info
                $hint.text(`Available: Rs. ${remainingAmount.toFixed(2)} (${allocatedAmount > 0 ? 'Partially Paid' : 'Unpaid'})`).show();
            }
        } else {
            // Reset when no bill selected
            $amountInput.prop('disabled', true).val('').removeAttr('max').attr('placeholder', 'Select bill first');
            $hint.hide();
        }
    });
    
    // Amount Input in Allocation
    $(document).on('input', '.allocation-amount', function() {
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
            $hint.text(`⚠️ Amount limited to remaining balance: Rs. ${maxAmount.toFixed(2)}`).removeClass('text-muted text-success').addClass('text-warning');
            return;
        }
        
        // Update tracking
        billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - prevAmount + amount;
        $(this).data('prev-amount', amount);
        
        // Update hint
        const newAllocation = billPaymentAllocations[billId] || 0;
        const remaining = bill.total_due - newAllocation;
        $hint.text(`Remaining: Rs. ${remaining.toFixed(2)}`).removeClass('text-warning').addClass('text-success');
        
        // Update summary if function exists
        if (typeof updateSummaryTotals === 'function') updateSummaryTotals();
    });
});

</script>
@endsection