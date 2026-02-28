@extends('layout.layout')
@section('content')
<div class="container-fluid py-4">
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

                    </div>
                </div>
            </div>
        </div>


        <!-- Step 1: Customer Selection - Clean & Simple -->
        <div class="mb-3">
            <!-- Customer Selection -->
            <div class="mb-3">
                <label for="customerSelect" class="form-label">Purchase Supplier</label>
                <select id="customerSelect" class="form-control selectBox">
                    <option value="">🔄 Loading suppliers...</option>
                </select>
            </div>

            <!-- Customer Summary - Clean Text (Hidden by default) -->
            <div id="customerSummarySection" class="border rounded p-2 mb-3 bg-light" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3">
                        <span class="text-muted small">Opening Balance: <strong class="text-dark">Rs. <span id="openingBalance">0.00</span></strong></span>
                        <span class="text-muted small">Purchase Due: <strong class="text-dark">Rs. <span id="totalDueAmount">0.00</span></strong></span>
                        <span id="returnCount" class="text-info small" style="display: none;">(Has <span id="returnCountNumber">0</span> returns available)</span>
                        <span id="advanceCount" class="text-success small" style="display: none;">(Advance: Rs. <span id="advanceAmount">0.00</span>)</span>
                    </div>

                    <div class="text-end">
                        <div class="text-muted small">Amount to Pay</div>
                        <div class="h3 fw-bold text-danger mb-0" id="netCustomerDue">Rs. 48750.00</div>
                        <div class="text-muted small" id="netCalculation">Purchase Due - Returns</div>
                    </div>
                </div>

                <!-- Smart Default Behavior Text -->
                <div class="alert alert-info border-0 mb-0 mt-2 py-1 px-2">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        <strong>Auto Mode:</strong> System will intelligently allocate payment to bills.
                        <a href="#" id="customizePaymentLink" class="text-decoration-none">Customize</a>
                    </small>
                </div>
            </div>

            <!-- Payment Type Selection - Hidden by default, shown only when needed -->
            <div id="paymentTypeSection" class="mb-3" style="display: none;">
                <div class="d-flex gap-3 flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentType" id="payPurchaseDues" value="purchase_dues" checked>
                        <label class="form-check-label" for="payPurchaseDues">Purchase Bills</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentType" id="payOpeningBalance" value="opening_balance">
                        <label class="form-check-label" for="payOpeningBalance">Opening Balance</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="paymentType" id="payBoth" value="both">
                        <label class="form-check-label" for="payBoth">Both</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Payment Section - Clean & Fast -->
        <div id="paymentMethodSection" style="display: none;">
            <div class="mb-3">
                <!-- Quick Payment Input -->
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="globalPaymentAmount" class="form-label">Amount Received</label>
                        <input type="text" class="form-control" id="globalPaymentAmount" name="amount" placeholder="Max: Rs. 0.00">
                        <div id="amountError" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="paymentMethod" class="form-label">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
                            <option value="cash">Cash</option>
                            <option value="card">Credit Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="multiple">Multiple Methods</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="paidOn" class="form-label">Date</label>
                        <input class="form-control" type="date" name="payment_date" id="paidOn" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- Returns Section - Visible in Main Flow -->
                <div id="customerReturnsSection" class="mb-3" style="display: none;">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                Returns (Rs. <span id="returnsToApplyToPurchases">0.00</span> available)
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="hideReturnsBtn">
                                <i class="fas fa-times"></i> Hide
                            </button>
                        </div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40"><input type="checkbox" id="selectAllReturns"></th>
                                        <th>Return #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="customerReturnsTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-2">No pending returns</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary mt-2" id="reallocateAllCreditsBtn">
                            <i class="fas fa-exchange-alt"></i> Change Allocation
                        </button>
                    </div>
                </div>

                <!-- Advance Credit Section - Similar to Returns -->
                <div id="customerAdvanceSection" class="mb-3" style="display: none;">
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-success">
                                <i class="fas fa-piggy-bank"></i> Advance Credit (Rs. <span id="advanceToApplyToBills">0.00</span> available)
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="hideAdvanceBtn">
                                <i class="fas fa-times"></i> Hide
                            </button>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="applyAdvanceCreditCheckbox">
                            <label class="form-check-label" for="applyAdvanceCreditCheckbox">
                                Apply advance credit to this payment
                            </label>
                        </div>
                        <div id="advanceCreditAmountSection" style="display: none;">
                            <label for="advanceCreditAmountInput" class="form-label small">Amount to apply (max: Rs. <span id="maxAdvanceCredit">0.00</span>)</label>
                            <input type="number" class="form-control form-control-sm" id="advanceCreditAmountInput" placeholder="Enter amount" step="0.01" min="0">
                            <small class="text-muted">This will reduce the amount you need to pay</small>
                        </div>
                    </div>
                </div>

                <!-- Advanced Options - Progressive Disclosure -->
                <div class="mb-3">
                    <a href="#" id="showAdvancedOptions" class="text-decoration-none small">
                        <i class="fas fa-chevron-down"></i> Show outstanding bills & payment allocation
                    </a>
                </div>

                <!-- Advanced Options Container (Hidden by default) -->
                <div id="advancedOptionsContainer" class="collapse" style="display: none;">
                    <!-- Auto Allocation Info -->
                    <div id="bothPaymentTypeInfo" class="alert alert-light border py-2 px-3 mb-3" style="display: none;">
                        <small class="text-muted">
                            <i class="fas fa-magic"></i>
                            <strong>Smart Allocation:</strong> Opening Balance first (Rs. <span id="obInfoAmount">0.00</span>), then bills automatically
                        </small>
                    </div>

                    <!-- Bill Selection Details -->
                    <div id="multiMethodContainer" class="mb-3">
                        <div class="border rounded">
                            <!-- Two Column Layout -->
                            <div class="row g-0">
                                <!-- Left: Bills & Returns -->
                                <div class="col-md-6 border-end p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 small">Outstanding Bills</h6>
                                        <input type="search" id="billSearchInput" class="form-control form-control-sm" placeholder="Search..." style="max-width: 120px;">
                                    </div>
                                    <div id="availableBillsList" class="bill-items-container" style="height: 250px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                        <!-- Bills populated here -->
                                    </div>
                                </div>

                                <!-- Right: Payment Methods -->
                                <div class="col-md-6 p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 small">Payment Methods</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="addFlexiblePayment">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <div id="flexiblePaymentsList" class="payment-methods-container" style="height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                        <!-- Payment methods added here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Summary -->
                            <div class="border-top p-2 bg-light">
                                <div class="row text-center small">
                                    <div class="col-3">
                                        <div class="text-muted">Bills</div>
                                        <div class="fw-bold" id="totalBillsCount">0</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-muted">Due</div>
                                        <div class="fw-bold text-danger" id="summaryTotalDue">Rs. 0.00</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-muted">Payment</div>
                                        <div class="fw-bold text-success" id="totalPaymentAmount">Rs. 0.00</div>
                                    </div>
                                    <div class="col-3">
                                        <div class="text-muted">Balance</div>
                                        <div class="fw-bold" id="balanceAmount">Rs. 0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditional Payment Fields (Card, Cheque, Bank) -->
                <div id="cardFields" class="border rounded p-3 mb-3 d-none">
                    <h6 class="mb-3 small">Card Details</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="cardNumber" class="form-label small">Card Number</label>
                            <input type="text" class="form-control form-control-sm" id="cardNumber" name="card_number">
                        </div>
                        <div class="col-md-4">
                            <label for="cardHolderName" class="form-label small">Card Holder</label>
                            <input type="text" class="form-control form-control-sm" id="cardHolderName" name="card_holder_name">
                        </div>
                        <div class="col-md-4">
                            <label for="cardType" class="form-label small">Card Type</label>
                            <select class="form-select form-select-sm" id="cardType" name="card_type">
                                <option value="visa">Visa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="chequeFields" class="border rounded p-3 mb-3 d-none">
                    <h6 class="mb-3 small">Cheque Details</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="chequeNumber" class="form-label small">Cheque Number</label>
                            <input type="text" class="form-control form-control-sm" id="chequeNumber" name="cheque_number">
                        </div>
                        <div class="col-md-6">
                            <label for="bankBranch" class="form-label small">Bank Branch</label>
                            <input type="text" class="form-control form-control-sm" id="bankBranch" name="cheque_bank_branch">
                        </div>
                    </div>
                </div>

                <div id="bankTransferFields" class="border rounded p-3 mb-3 d-none">
                    <h6 class="mb-3 small">Bank Transfer Details</h6>
                    <label for="bankAccountNumber" class="form-label small">Bank Account Number</label>
                    <input type="text" class="form-control form-control-sm" id="bankAccountNumber" name="bank_account_number">
                </div>

                <!-- Notes -->
                <div id="notesSection" class="mb-3" style="display: none;">
                    <label for="notes" class="form-label small">Payment Notes (optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" placeholder="Add any notes..."></textarea>
                </div>

                <!-- Submit Button -->
                <div id="submitButtonSection" class="text-center" style="display: none;">
                    <button type="button" id="submitBulkPayment" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-check"></i> Submit Payment
                    </button>
                </div>
            </div>
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
                <!-- Customer Selection Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="flexibleCustomerSelect" class="form-label">Select Supplier</label>
                        <select id="flexibleCustomerSelect" class="form-control">
                            <option value="">Select Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="flexiblePaymentDate" class="form-label">Payment Date</label>
                        <input type="date" id="flexiblePaymentDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <!-- Customer Summary Cards -->
                <div class="row mb-4" id="customerSummaryCards" style="display:none;">
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
                                <h6><i class="fas fa-shopping-cart"></i> Total Sales</h6>
                                <h4 id="totalSalesAmount">Rs. 0.00</h4>
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

{{-- @include('sell.sales_ajax') --}}

<script>
// Define the supplier loading function directly here for the separate page
function loadSuppliersForBulkPayment() {
    console.log('Loading suppliers for bulk purchase payment...');

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
            var customerSelect = $('#customerSelect');
            customerSelect.empty();
            customerSelect.append('<option value="" selected disabled>Select Supplier</option>');

            if (response.status === 200 && response.message && response.message.length > 0) {
                response.message.forEach(function(supplier) {
                    // Use correct supplier API fields
                    var openingBalance = parseFloat(supplier.opening_balance) || 0;
                    var purchaseDue = parseFloat(supplier.total_purchase_due) || 0;
                    var currentBalance = parseFloat(supplier.current_balance) || 0;

                    console.log('Supplier data received:', {
                        name: supplier.first_name,
                        current_balance: supplier.current_balance,
                        total_purchase_due: supplier.total_purchase_due
                    });

                    // Show all suppliers who have any balance due
                    if (currentBalance > 0) {
                        var lastName = supplier.last_name ? supplier.last_name : '';
                        var fullName = supplier.first_name + (lastName ? ' ' + lastName : '');

                        // Build display text
                        var displayText = fullName + ' [Total Due: Rs. ' + currentBalance.toFixed(2) + ']';

                        // Show breakdown if available
                        if (openingBalance > 0 && purchaseDue > 0) {
                            displayText += ' (Opening: Rs. ' + openingBalance.toFixed(2) + ', Purchases: Rs. ' + purchaseDue.toFixed(2) + ')';
                        } else if (openingBalance > 0) {
                            displayText += ' (Opening Balance)';
                        } else if (purchaseDue > 0) {
                            displayText += ' (Purchase Due)';
                        }

                        customerSelect.append(
                            '<option value="' + supplier.id +
                            '" data-opening-balance="' + openingBalance +
                            '" data-purchase-due="' + purchaseDue +
                            '" data-total-due="' + purchaseDue +
                            '" data-advance-credit="0">' + displayText + '</option>'
                        );
                    }
                });

                if (customerSelect.find('option[value!=""]').length === 0) {
                    customerSelect.append('<option value="" disabled>No suppliers with outstanding dues found</option>');
                }
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

            var errorMessage = 'Failed to load suppliers.';
            if (xhr.status === 401) {
                errorMessage = 'Authentication required. Please refresh the page and login again.';
            } else if (xhr.status === 403) {
                errorMessage = 'Permission denied to access supplier data.';
            }

            $('#customerSelect').append('<option value="" disabled>Error: ' + errorMessage + '</option>');
        }
    });
}

// Additional initialization for the separate bulk payment page
$(document).ready(function() {
    console.log('Bulk payment page specific initialization...');

    // Initialize select2 with proper settings for standalone page
    if (typeof $.fn.select2 !== 'undefined') {
        $('#customerSelect').select2({
            placeholder: "Select Supplier",
            allowClear: true,
            width: '100%'
        });
        console.log('Select2 initialized on bulk payment page');

        // Add event listener to ensure search input gets focus when dropdown opens
        $('#customerSelect').on('select2:open', function() {
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

    // Load customers immediately
    setTimeout(function() {
        console.log('Loading customers for separate page...');
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

        // Trigger payment type change to set proper defaults
        $('input[name="paymentType"]:checked').trigger('change');
    }, 1500);

    // Progressive Disclosure: Hide Returns Button
    $(document).on('click', '#hideReturnsBtn', function() {
        $('#customerReturnsSection').slideUp();
    });

    // Progressive Disclosure: Hide Advance Credit Button
    $(document).on('click', '#hideAdvanceBtn', function() {
        $('#customerAdvanceSection').slideUp();
    });

    // Advance Credit Checkbox Handler
    $(document).on('change', '#applyAdvanceCreditCheckbox', function() {
        if ($(this).is(':checked')) {
            $('#advanceCreditAmountSection').slideDown();
            // Set default to full advance amount
            var maxAdvance = window.supplierAdvanceCredit || 0;
            var totalDue = window.totalSupplierDue || 0;
            var suggestedAmount = Math.min(maxAdvance, totalDue);
            $('#advanceCreditAmountInput').val(suggestedAmount.toFixed(2));
            $('#advanceCreditAmountInput').attr('max', maxAdvance);
            updateNetSupplierDue();
        } else {
            $('#advanceCreditAmountSection').slideUp();
            $('#advanceCreditAmountInput').val('');
            updateNetSupplierDue();
        }
    });

    // Advance Credit Amount Input Handler
    $(document).on('input', '#advanceCreditAmountInput', function() {
        var maxAdvance = window.supplierAdvanceCredit || 0;
        var inputAmount = parseFloat($(this).val()) || 0;

        if (inputAmount > maxAdvance) {
            $(this).val(maxAdvance.toFixed(2));
            toastr.warning('Amount cannot exceed available advance credit of Rs. ' + maxAdvance.toFixed(2));
        }

        updateNetSupplierDue();
    });

    // Progressive Disclosure: Customize Payment Link - Toggle functionality
    $(document).on('click', '#customizePaymentLink', function(e) {
        e.preventDefault();
        var $section = $('#paymentTypeSection');
        if ($section.is(':visible')) {
            $section.slideUp();
            $(this).text('Customize');
        } else {
            $section.slideDown();
            $(this).text('Hide Options');
        }
    });

    // Progressive Disclosure: Show Advanced Options
    $(document).on('click', '#showAdvancedOptions', function(e) {
        e.preventDefault();
        $('#advancedOptionsContainer').slideDown();
        $(this).html('<i class="fas fa-chevron-up"></i> Hide outstanding bills & payment allocation');
        $(this).attr('id', 'hideAdvancedOptions');
    });

    // Progressive Disclosure: Hide Advanced Options
    $(document).on('click', '#hideAdvancedOptions', function(e) {
        e.preventDefault();
        $('#advancedOptionsContainer').slideUp();
        $(this).html('<i class="fas fa-chevron-down"></i> Show outstanding bills & payment allocation');
        $(this).attr('id', 'showAdvancedOptions');
    });

    // Handle Payment Type changes to update payment method dropdown and helper text
    $('input[name="paymentType"]').on('change', function() {
        const selectedType = $(this).val();
        const $paymentMethod = $('#paymentMethod');
        const $helpText = $('#paymentTypeHelp');

        // Update helper text based on selection
        const helpTexts = {
            'purchase_dues': '<i class="fas fa-info-circle"></i> Pay purchase bills (invoices) for this supplier',
            'opening_balance': '<i class="fas fa-info-circle"></i> Pay only the opening balance amount',
            'both': '<i class="fas fa-info-circle"></i> Pay both opening balance and purchase bills together'
        };
        $helpText.html(helpTexts[selectedType] || '');

        if (selectedType === 'opening_balance') {
            // Opening Balance: Disable Multiple Methods, enable only cash/card/cheque/bank_transfer
            $paymentMethod.find('option').prop('disabled', false);
            $paymentMethod.find('option[value="multiple"]').prop('disabled', true);

            // If currently on multiple, switch to cash
            if ($paymentMethod.val() === 'multiple' || $paymentMethod.val() === null) {
                $paymentMethod.val('cash');
            }

            // Force update the UI
            if (typeof togglePaymentFields === 'function') {
                togglePaymentFields();
            }

            $('#bothPaymentTypeInfo').hide();
            $('.both-payment-hint').hide();
            $('.both-payment-breakdown').hide();
        } else if (selectedType === 'both') {
            // Both: Select Multiple Methods by default and disable other options
            $paymentMethod.find('option').prop('disabled', true);
            $paymentMethod.find('option[value="multiple"]').prop('disabled', false);
            $paymentMethod.val('multiple');

            // Force update the UI
            if (typeof togglePaymentFields === 'function') {
                togglePaymentFields();
            }

            // Show info banner with OB amount
            var selectedOption = $('#customerSelect').find(':selected');
            var supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
            $('#obInfoAmount').text(supplierOpeningBalance.toFixed(2));
            $('#bothPaymentTypeInfo').show();

            // Show hints for both payment type
            $('.both-payment-hint').show();
        } else {
            // Pay Purchase Dues: Select Multiple Methods by default and disable other options
            $paymentMethod.find('option').prop('disabled', true);
            $paymentMethod.find('option[value="multiple"]').prop('disabled', false);
            $paymentMethod.val('multiple');

            // Force update the UI
            if (typeof togglePaymentFields === 'function') {
                togglePaymentFields();
            }

            $('#bothPaymentTypeInfo').hide();
            $('.both-payment-hint').hide();
            $('.both-payment-breakdown').hide();
        }
    });
});

// Customer selection change handler for separate page
$(document).on('change', '#customerSelect', function() {
    console.log('Customer selected on separate page...');

    var selectedOption = $(this).find(':selected');
    var customerId = $(this).val();

    if (!customerId) {
        console.log('No customer selected - hiding sections');
        $('#customerSummarySection').hide();
        $('#paymentMethodSection').hide();
        $('#notesSection').hide();
        $('#submitButtonSection').hide();
        return;
    }

    console.log('Selected customer ID:', customerId);

    // Show customer summary and payment method section
    $('#customerSummarySection').show();
    $('#paymentMethodSection').show();
    $('#notesSection').show();
    $('#submitButtonSection').show();

    // Get customer data from the selected option
    var supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
    var purchaseDue = parseFloat(selectedOption.data('purchase-due')) || 0;
    var totalDue = parseFloat(selectedOption.data('total-due')) || 0;
    var advanceCredit = parseFloat(selectedOption.data('advance-credit')) || 0;

    // DEBUG: Log all data attributes from selected option
    console.log('Selected option attributes:', selectedOption[0].attributes);
    console.log('Data attributes parsed:', {
        'data-opening-balance': selectedOption.data('opening-balance'),
        'data-purchase-due': selectedOption.data('purchase-due'),
        'data-total-due': selectedOption.data('total-due'),
        'data-advance-credit': selectedOption.data('advance-credit')
    });

    // Trigger payment type change to set proper payment method options
    $('input[name="paymentType"]:checked').trigger('change');

    console.log('Customer balances:', {
        openingBalance: supplierOpeningBalance,
        purchaseDue: purchaseDue,
        totalDue: totalDue,
        advanceCredit: advanceCredit
    });

    // Update balance displays (text-based, no cards)
    $('#openingBalance').text(supplierOpeningBalance.toFixed(2));
    // Use totalDue (current_balance) to stay consistent with Amount to Pay calculation
    $('#totalDueAmount').text(totalDue.toFixed(2));

    // Store values globally
    window.supplierOriginalOpeningBalance = supplierOpeningBalance;
    window.purchaseDueAmount = purchaseDue;
    window.totalSupplierDue = totalDue;
    window.supplierAdvanceCredit = advanceCredit;

    // Show advance credit if available
    if (advanceCredit > 0) {
        $('#advanceAmount').text(advanceCredit.toFixed(2));
        $('#advanceCount').show();

        // Show advance credit application section
        $('#advanceToApplyToBills').text(advanceCredit.toFixed(2));
        $('#maxAdvanceCredit').text(advanceCredit.toFixed(2));
        $('#customerAdvanceSection').show();
    } else {
        $('#advanceCount').hide();
        $('#customerAdvanceSection').hide();
        $('#applyAdvanceCreditCheckbox').prop('checked', false);
        $('#advanceCreditAmountSection').hide();
    }

    // Set amount to pay
    $('#netCustomerDue').text('Rs. ' + totalDue.toFixed(2));
    window.netSupplierDue = totalDue;

    // Reset and clear previous validation errors
    $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
    $('#globalPaymentAmount').val('');

    // Load purchases and returns for the selected supplier
    loadPurchasesForSupplier(customerId);
    loadPurchaseReturns(customerId);
});

// Global variables for returns handling
var availablePurchaseReturns = [];
var selectedPurchaseReturns = [];

// Function to load customer returns
function loadPurchaseReturns(customerId) {
    console.log('Loading returns for customer:', customerId);

    $.ajax({
        url: '/supplier-returns/' + customerId,
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Returns response:', response);

            if (response.returns && response.returns.length > 0) {
                availablePurchaseReturns = response.returns.filter(ret => {
                    return parseFloat(ret.total_due) > 0 && ret.payment_status !== 'Paid';
                });

                console.log('Unpaid returns found:', availablePurchaseReturns.length);

                if (availablePurchaseReturns.length > 0) {
                    populatePurchaseReturnsTable();

                    // Update return credits in summary (text-based, no cards)
                    var totalReturnCredits = availablePurchaseReturns.reduce((sum, ret) => sum + parseFloat(ret.total_due), 0);
                    $('#totalReturnCredits').text(totalReturnCredits.toFixed(2));
                    $('#returnsToApplyToPurchases').text(totalReturnCredits.toFixed(2));
                    $('#returnCountNumber').text(availablePurchaseReturns.length);
                    $('#returnCount').show();

                    // Always show returns section when returns are available
                    $('#customerReturnsSection').show();

                    // Update Net Customer Due
                    updateNetSupplierDue();
                } else {
                    hidePurchaseReturnsUI();
                }
            } else {
                console.log('No returns found for customer');
                hidePurchaseReturnsUI();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading customer returns:', error);
            hidePurchaseReturnsUI();
            if (xhr.status === 404) {
                console.log('Returns endpoint not found - feature may not be implemented yet');
            }
        }
    });
}

// Helper function to hide returns UI
function hidePurchaseReturnsUI() {
    $('#customerReturnsSection').hide();
    $('#totalReturnCredits').text('0.00');
    $('#returnCount').hide();
    updateNetSupplierDue();
}

// Populate returns table
function populatePurchaseReturnsTable() {
    var tableBody = $('#customerReturnsTableBody');
    tableBody.empty();

    availablePurchaseReturns.forEach(function(returnBill) {
        var totalDue = parseFloat(returnBill.total_due) || 0;
        var returnDate = returnBill.return_date ? new Date(returnBill.return_date).toLocaleDateString('en-GB') : 'N/A';

        var row = '<tr class="return-row" data-return-id="' + returnBill.id + '" style="cursor: pointer;">' +
            '<td class="return-checkbox-cell" onclick="event.stopPropagation();">' +
            '<input type="checkbox" class="return-checkbox" data-return-id="' + returnBill.id + '" data-amount="' + totalDue + '">' +
            '</td>' +
            '<td><strong>' + returnBill.reference_no + '</strong></td>' +
            '<td>' + returnDate + '</td>' +
            '<td class="text-danger fw-bold">Rs. ' + totalDue.toFixed(2) + '</td>' +
            '<td onclick="event.stopPropagation();">' +
            '<select class="form-select form-select-sm return-action" data-return-id="' + returnBill.id + '" style="font-size: 0.8rem;">' +
            '<option value="apply_to_purchases" selected>Apply to Purchases</option>' +
            '<option value="cash_refund">Cash Refund</option>' +
            '</select>' +
            '</td>' +
            '</tr>';

        tableBody.append(row);
    });
}

// Handle select all returns checkbox
// Handle select all returns checkbox
$(document).on('change', '#selectAllReturns', function() {
    var isChecked = $(this).prop('checked');
    $('.return-checkbox').prop('checked', isChecked).trigger('change');
});

// Handle return row click to toggle checkbox
$(document).on('click', '.return-row', function(e) {
    // Don't toggle if clicking on checkbox or action dropdown
    if ($(e.target).hasClass('return-checkbox') || $(e.target).hasClass('return-action')) {
        return;
    }

    const returnId = $(this).data('return-id');
    const $checkbox = $(this).find('.return-checkbox');

    // Toggle checkbox
    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');

    // Visual feedback
    if ($checkbox.prop('checked')) {
        $(this).addClass('table-active');
    } else {
        $(this).removeClass('table-active');
    }
});

// Handle individual return checkbox
$(document).on('change', '.return-checkbox', function() {
    const $row = $(this).closest('.return-row');
    if ($(this).prop('checked')) {
        $row.addClass('table-active');
    } else {
        $row.removeClass('table-active');
    }
    updateSelectedPurchaseReturns();
});

// Common function to show adjust credit dialog (eliminates duplication)
function showAdjustCreditDialog(saleId) {
    const sale = availableSupplierPurchases.find(s => s.id == saleId);
    if (!sale) return;

    const currentCredit = window.billReturnCreditAllocations[saleId] || 0;

    // Get total return credit available
    let totalReturnCredit = 0;
    $('.return-checkbox:checked').each(function() {
        const action = $('.return-action[data-return-id="' + $(this).data('return-id') + '"]').val();
        if (action === 'apply_to_purchases') {
            totalReturnCredit += parseFloat($(this).data('amount'));
        }
    });

    // Calculate already allocated to other bills
    let otherAllocations = 0;
    Object.keys(window.billReturnCreditAllocations).forEach(key => {
        if (key != saleId) {
            otherAllocations += window.billReturnCreditAllocations[key];
        }
    });

    // Available credit = total return credit - what's allocated to other bills
    const availableCredit = totalReturnCredit - otherAllocations;
    // Max allowable = minimum of available credit OR bill due (can't pay more than bill due)
    const maxAllowable = Math.min(availableCredit, sale.total_due);

    Swal.fire({
        title: `Adjust Return Credit`,
        html: `
            <div class="text-start">
                <p><strong>Bill:</strong> ${sale.reference_no}</p>
                <p><strong>Bill Due:</strong> Rs.${parseFloat(sale.total_due).toFixed(2)}</p>
                <p><strong>Current Allocated:</strong> Rs.${currentCredit.toFixed(2)}</p>
                <p><strong>Total Return Credit:</strong> Rs.${totalReturnCredit.toFixed(2)}</p>
                <p><strong>Available to Allocate:</strong> Rs.${availableCredit.toFixed(2)}</p>
                <hr>
                <label class="form-label">Enter amount (0 to remove):</label>
                <input type="number" id="creditAmount" class="form-control"
                       value="${currentCredit}" min="0" max="${maxAllowable}" step="0.01">
                <small class="text-muted">Max: Rs.${maxAllowable.toFixed(2)}</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Apply',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const amount = parseFloat(document.getElementById('creditAmount').value) || 0;
            if (amount < 0 || amount > maxAllowable) {
                Swal.showValidationMessage(`Amount must be between 0 and ${maxAllowable.toFixed(2)}`);
                return false;
            }
            return amount;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const newAmount = result.value;
            if (newAmount > 0) {
                window.billReturnCreditAllocations[saleId] = newAmount;
            } else {
                delete window.billReturnCreditAllocations[saleId];
            }
            populateFlexibleBillsList();
            updateExistingPurchaseAllocationsForReturnCredits();
            toastr.success(`Return credit updated to Rs.${newAmount.toFixed(2)}`, 'Updated');
        }
    });
}

// Handle click on return credit badge to manually adjust allocation
$(document).on('click', '.return-credit-badge', function(e) {
    e.stopPropagation();
    showAdjustCreditDialog($(this).data('sale-id'));
});

// Handle return action change
$(document).on('change', '.return-action', function() {
    const returnId = $(this).data('return-id');
    const action = $(this).val();
    const $checkbox = $(`.return-checkbox[data-return-id="${returnId}"]`);
    const isChecked = $checkbox.prop('checked');

    // Show feedback about the action change
    if (isChecked) {
        if (action === 'apply_to_purchases') {
            toastr.info('Return credit will be applied to outstanding purchase bills', 'Action Changed', {timeOut: 2000});
        } else if (action === 'cash_refund') {
            toastr.info('Cash refund will be processed for this return', 'Action Changed', {timeOut: 2000});
        }
    }

    updateSelectedPurchaseReturns();
});

// Quick remove return credit from a bill
$(document).on('click', '.quick-remove-credit', function(e) {
    e.stopPropagation();
    const saleId = $(this).data('sale-id');
    const currentCredit = window.billReturnCreditAllocations[saleId] || 0;

    if (currentCredit > 0) {
        delete window.billReturnCreditAllocations[saleId];
        populateFlexibleBillsList();
        updateExistingPurchaseAllocationsForReturnCredits();
        toastr.success(`Return credit Rs.${currentCredit.toFixed(2)} removed from bill`, 'Credit Removed');
    }
});

// Quick adjust return credit for a bill (reuses common function)
$(document).on('click', '.quick-adjust-credit', function(e) {
    e.stopPropagation();
    showAdjustCreditDialog($(this).data('sale-id'));
});

// Reallocate All Credits button - shows modal with all bills
$(document).on('click', '#reallocateAllCreditsBtn', function() {
    // Get total return credit available
    let totalReturnCredit = 0;
    $('.return-checkbox:checked').each(function() {
        const action = $('.return-action[data-return-id="' + $(this).data('return-id') + '"]').val();
        if (action === 'apply_to_purchases') {
            totalReturnCredit += parseFloat($(this).data('amount'));
        }
    });

    if (totalReturnCredit === 0) {
        toastr.warning('No return credits selected for "Apply to Purchases"', 'No Credits');
        return;
    }

    // Build bills table
    let billsHTML = '<div style="max-height: 400px; overflow-y: auto;"><table class="table table-sm table-hover"><thead class="sticky-top bg-light"><tr><th>Bill #</th><th>Due</th><th>Credit</th><th>Action</th></tr></thead><tbody>';

    availableSupplierPurchases.forEach(sale => {
        const currentCredit = window.billReturnCreditAllocations[sale.id] || 0;
        billsHTML += `
            <tr>
                <td><small>${sale.reference_no}</small></td>
                <td><small>Rs.${parseFloat(sale.total_due).toFixed(2)}</small></td>
                <td><small class="${currentCredit > 0 ? 'text-info fw-bold' : 'text-muted'}">Rs.${currentCredit.toFixed(2)}</small></td>
                <td>
                    <button class="btn btn-xs btn-primary realloc-set-credit" data-sale-id="${sale.id}" data-invoice="${sale.reference_no}" data-due="${sale.total_due}">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    billsHTML += '</tbody></table></div>';

    Swal.fire({
        title: 'Reallocate Return Credits',
        html: `
            <div class="text-start">
                <div class="alert alert-info p-2 mb-2">
                    <small><strong>Total Available:</strong> Rs.${totalReturnCredit.toFixed(2)}</small>
                </div>
                ${billsHTML}
                <div class="mt-2 text-center">
                    <button class="btn btn-sm btn-warning" id="clearAllAllocations">
                        <i class="fas fa-eraser"></i> Clear All
                    </button>
                    <button class="btn btn-sm btn-success" id="autoFifoAllocate">
                        <i class="fas fa-magic"></i> Auto FIFO
                    </button>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        showConfirmButton: false,
        cancelButtonText: 'Close',
        didOpen: () => {
            // Bind events only once when modal opens
            $('#clearAllAllocations').off('click').on('click', function() {
                window.billReturnCreditAllocations = {};
                populateFlexibleBillsList();
                updateExistingPurchaseAllocationsForReturnCredits();
                toastr.success('All credits cleared!', 'Success');
                Swal.close();
                setTimeout(() => $('#reallocateAllCreditsBtn').click(), 100);
            });

            $('#autoFifoAllocate').off('click').on('click', function() {
                autoAllocateReturnCreditsToPurchases(totalReturnCredit);
                toastr.success('FIFO allocation applied!', 'Success');
                Swal.close();
            });
        }
    });

    // Handle individual set credit button (bind once per modal open)
    $(document).off('click', '.realloc-set-credit').on('click', '.realloc-set-credit', function() {
        const saleId = $(this).data('sale-id');
        const invoice = $(this).data('invoice');
        const due = parseFloat($(this).data('due'));
        const currentCredit = window.billReturnCreditAllocations[saleId] || 0;

        // Calculate available
        let allocated = 0;
        Object.keys(window.billReturnCreditAllocations).forEach(key => {
            if (key != saleId) {
                allocated += window.billReturnCreditAllocations[key];
            }
        });
        const available = totalReturnCredit - allocated;
        const maxAllowable = Math.min(available, due);

        Swal.fire({
            title: `Set Credit for ${invoice}`,
            html: `
                <div class="text-start">
                    <p><small><strong>Current:</strong> Rs.${currentCredit.toFixed(2)}</small></p>
                    <p><small><strong>Total Return Credit:</strong> Rs.${totalReturnCredit.toFixed(2)}</small></p>
                    <p><small><strong>Available:</strong> Rs.${available.toFixed(2)}</small></p>
                    <p><small><strong>Max Allowable:</strong> Rs.${maxAllowable.toFixed(2)}</small></p>
                    <input type="number" id="setCreditAmount" class="form-control" value="${currentCredit}" min="0" max="${maxAllowable}" step="0.01">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Set',
            preConfirm: () => {
                const amount = parseFloat($('#setCreditAmount').val()) || 0;
                if (amount < 0 || amount > maxAllowable) {
                    Swal.showValidationMessage(`Between 0 and ${maxAllowable.toFixed(2)}`);
                    return false;
                }
                return amount;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value > 0) {
                    window.billReturnCreditAllocations[saleId] = result.value;
                } else {
                    delete window.billReturnCreditAllocations[saleId];
                }
                populateFlexibleBillsList();
                updateExistingPurchaseAllocationsForReturnCredits();
                toastr.success('Credit updated!', 'Success');
                // Reopen main modal
                $('#reallocateAllCreditsBtn').click();
            }
        });
    });
});

// Update selected returns and totals
function updateSelectedPurchaseReturns() {
    selectedPurchaseReturns = [];
    var totalToApply = 0;
    var totalCashRefund = 0;

    $('.return-checkbox:checked').each(function() {
        var returnId = $(this).data('return-id');
        var amount = parseFloat($(this).data('amount'));
        var action = $('.return-action[data-return-id="' + returnId + '"]').val();

        selectedPurchaseReturns.push({
            return_id: returnId,
            amount: amount,
            action: action
        });

        if (action === 'apply_to_purchases') {
            totalToApply += amount;
        } else if (action === 'cash_refund') {
            totalCashRefund += amount;
        }
    });

    // Update footer totals
    $('#selectedReturnsCount').text(selectedPurchaseReturns.length + ' selected');
    $('#selectedReturnsTotal').text('Rs. ' + (totalToApply + totalCashRefund).toFixed(2));
    $('#returnsToApplyToPurchases').text(totalToApply.toFixed(2));
    $('#returnsCashRefund').text('Rs. ' + totalCashRefund.toFixed(2));

    // Update net customer due
    updateNetSupplierDue();

    // Auto-allocate return credits to bills in flexible payment system
    if (totalToApply > 0 && availableSupplierPurchases && availableSupplierPurchases.length > 0) {
        autoAllocateReturnCreditsToPurchases(totalToApply);
    } else {
        // Clear return credit allocations if no returns selected for "apply to sales"
        if (!window.billReturnCreditAllocations) {
            window.billReturnCreditAllocations = {};
        }
        window.billReturnCreditAllocations = {};

        // Refresh bills list to remove credit badges
        populateFlexibleBillsList();

        // Update existing bill allocations
        updateExistingPurchaseAllocationsForReturnCredits();
    }

    console.log('Selected returns updated:', selectedPurchaseReturns);
}

// Auto-allocate return credits to sales bills (FIFO - oldest first)
function autoAllocateReturnCreditsToPurchases(returnCreditAmount) {
    console.log('Auto-allocating return credits to purchases:', returnCreditAmount);

    // Reset any previous return credit allocations
    if (!window.billReturnCreditAllocations) {
        window.billReturnCreditAllocations = {};
    }
    window.billReturnCreditAllocations = {};

    let remainingCredit = returnCreditAmount;

    // Sort purchases by date (oldest first) for FIFO allocation
    let sortedSales = [...availableSupplierPurchases].sort((a, b) => {
        return new Date(a.purchase_date) - new Date(b.purchase_date);
    });

    // Allocate credit to bills
    for (let sale of sortedSales) {
        if (remainingCredit <= 0) break;

        const purchaseDue = parseFloat(sale.total_due) || 0;
        const allocatedAmount = Math.min(remainingCredit, purchaseDue);

        if (allocatedAmount > 0) {
            window.billReturnCreditAllocations[sale.id] = allocatedAmount;
            remainingCredit -= allocatedAmount;

            console.log(`Allocated Rs.${allocatedAmount.toFixed(2)} from returns to Sale #${sale.id}`);
        }
    }

    // Update the bills list to show allocations
    populateFlexibleBillsList();

    // Update existing bill allocations in payment methods
    updateExistingPurchaseAllocationsForReturnCredits();

    // Show info message (single notification only)
    if (returnCreditAmount > 0) {
        const allocated = returnCreditAmount - remainingCredit;
        toastr.info(
            `Rs.${allocated.toFixed(2)} return credit auto-allocated (FIFO). ` +
            (remainingCredit > 0 ? `Remaining: Rs.${remainingCredit.toFixed(2)}. ` : '') +
            `<br><strong>💡 To change:</strong> Click <strong>"Reallocate"</strong> button or edit/remove buttons on bills.`,
            'Return Credit Applied',
            {timeOut: 6000, progressBar: true, closeButton: true, escapeHtml: false}
        );
    }
}

// Update existing bill allocations when return credits are applied
function updateExistingPurchaseAllocationsForReturnCredits() {
    // Loop through all bill allocations in payment methods
    $('.bill-allocation-row').each(function() {
        const $row = $(this);
        const $billSelect = $row.find('.bill-select');
        const $amountInput = $row.find('.allocation-amount');
        const billId = $billSelect.val();

        if (!billId) return; // Skip if no bill selected

        // Find the bill data
        const bill = availableSupplierPurchases.find(s => s.id == billId);
        if (!bill) return;

        // Get current allocations
        const currentAllocationAmount = parseFloat($amountInput.val()) || 0;
        const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;

        // Get the previous allocation from tracking to calculate how much was allocated before
        const prevAmount = $amountInput.data('prev-amount') || 0;

        // Calculate what was allocated to OTHER payment methods for this bill
        const otherPaymentAllocations = (billPaymentAllocations[billId] || 0) - prevAmount;

        // Calculate bill's remaining due after return credits and other allocations
        const billRemainingDue = bill.total_due - returnCreditApplied - otherPaymentAllocations;

        // Update the amount if needed
        let needsUpdate = false;
        let newAmount = currentAllocationAmount;

        // If current allocation exceeds the new remaining due, reduce it
        if (currentAllocationAmount > billRemainingDue) {
            newAmount = Math.max(0, billRemainingDue);
            needsUpdate = true;
        }
        // If return credit was removed and there's more available, increase back to what it was or max available
        else if (returnCreditApplied === 0 && prevAmount > currentAllocationAmount && billRemainingDue > currentAllocationAmount) {
            newAmount = Math.min(prevAmount, billRemainingDue);
            needsUpdate = true;
        }

        if (needsUpdate) {
            // Update the input value with system update flag to prevent recursion
            $amountInput.data('system-update', true);
            $amountInput.val(newAmount.toFixed(2));

            // Update global tracking
            billPaymentAllocations[billId] = otherPaymentAllocations + newAmount;
            $amountInput.data('prev-amount', newAmount);

            // Update hint
            const $hint = $row.find('.bill-amount-hint');
            const remainingAfterPayment = billRemainingDue - newAmount;

            if (returnCreditApplied > 0) {
                $hint.html(`Available: Rs. ${billRemainingDue.toFixed(2)} <span class="badge bg-info return-credit-badge" data-sale-id="${billId}" style="cursor: pointer;" title="Click to adjust return credit allocation"><i class="fas fa-undo"></i> Rs.${returnCreditApplied.toFixed(2)} credit <i class="fas fa-edit" style="font-size: 0.7em;"></i></span>`);
            } else if (remainingAfterPayment <= 0.01) {
                $hint.text('✅ Bill will be fully paid').removeClass('text-muted').addClass('text-success');
            } else {
                $hint.text(`💰 Remaining: Rs. ${remainingAfterPayment.toFixed(2)}`).removeClass('text-success').addClass('text-muted');
            }

            // Update tracking
            const paymentId = $row.closest('.payment-method-item').data('payment-id');
            if (paymentId && paymentMethodAllocations[paymentId]) {
                // Recalculate this payment method's total
                updatePaymentMethodTotal(paymentId);
            }

            // Remove system update flag after a delay
            setTimeout(() => {
                $amountInput.data('system-update', false);
            }, 200);

            console.log(`Updated bill ${billId} allocation from ${currentAllocationAmount.toFixed(2)} to ${newAmount.toFixed(2)} due to return credit change`);
        }
    });

    // Update summary totals
    updateSummaryTotals();
}

// Update net customer due (after return credits)
function updateNetSupplierDue() {
    var openingBalance = window.supplierOriginalOpeningBalance || 0;
    var purchaseDue = window.purchaseDueAmount || 0;
    var totalDue = window.totalSupplierDue || 0; // Use actual total from backend (ledger)

    // Get return credits to apply to purchases
    var returnsToApply = 0;
    $('.return-checkbox:checked').each(function() {
        var returnId = $(this).data('return-id');
        var action = $('.return-action[data-return-id="' + returnId + '"]').val();
        if (action === 'apply_to_purchases') {
            returnsToApply += parseFloat($(this).data('amount')) || 0;
        }
    });

    // Get advance credit to apply
    var advanceCreditToApply = 0;
    if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
        advanceCreditToApply = parseFloat($('#advanceCreditAmountInput').val()) || 0;
    }

    var netDue = totalDue - returnsToApply - advanceCreditToApply;
    if (netDue < 0) netDue = 0; // Can't be negative

    $('#netCustomerDue').text('Rs. ' + netDue.toFixed(2));

    // Update calculation display
    var calculationParts = [];
    calculationParts.push('Rs.' + totalDue.toFixed(2));

    if (returnsToApply > 0) {
        calculationParts.push('- Rs.' + returnsToApply.toFixed(2) + ' (Returns)');
    }

    if (advanceCreditToApply > 0) {
        calculationParts.push('- Rs.' + advanceCreditToApply.toFixed(2) + ' (Advance)');
    }

    if (calculationParts.length > 1) {
        $('#netCalculation').html('<i class="fas fa-calculator"></i> ' + calculationParts.join(' '));
    } else {
        $('#netCalculation').text('Purchase Due - Returns - Advance');
    }

    // Store for later use
    window.netSupplierDue = netDue;

    console.log('Net customer due updated:', {
        openingBalance: openingBalance,
        purchaseDue: purchaseDue,
        totalDue: totalDue,
        returnsToApply: returnsToApply,
        advanceCreditToApply: advanceCreditToApply,
        netDue: netDue
    });
}

// Function to load purchases for selected supplier
function loadPurchasesForSupplier(supplierId) {
    // Prevent duplicate concurrent requests
    if (window.isLoadingPurchases) {
        console.log('Skipping duplicate loadPurchasesForSupplier call - already loading');
        return;
    }
    window.isLoadingPurchases = true;
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
            window.isLoadingPurchases = false;

            if (response.purchases && response.purchases.length > 0) {
                // --- Populate flexible bill list (many-to-many system) ---
                availableSupplierPurchases = response.purchases.filter(purchase => {
                    const isDue = parseFloat(purchase.total_due) > 0;
                    const isOutstanding = purchase.payment_status === 'Due' || purchase.payment_status === 'Partial';
                    return isDue && isOutstanding;
                });
                console.log('Outstanding purchase bills for flexible UI:', availableSupplierPurchases.length);
                populateFlexibleBillsList();

                // --- Populate simple table ---
                var salesTableBody = $('#salesList tbody');
                salesTableBody.empty();

                var totalSalesAmount = 0,
                    totalPaidAmount = 0,
                    totalDueAmount = 0;

                response.purchases.forEach(function(purchase) {
                    var finalTotal = parseFloat(purchase.final_total) || 0;
                    var totalDue = parseFloat(purchase.total_due) || 0;
                    var totalPaid = finalTotal - totalDue;

                    totalSalesAmount += finalTotal;
                    totalPaidAmount += totalPaid;
                    totalDueAmount += totalDue;

                    if (totalDue > 0) {
                        var row = '<tr>' +
                            '<td>' + purchase.id + ' (' + purchase.reference_no + ')</td>' +
                            '<td>' + finalTotal.toFixed(2) + '</td>' +
                            '<td>' + totalPaid.toFixed(2) + '</td>' +
                            '<td>' + totalDue.toFixed(2) + '</td>' +
                            '<td><input type="number" class="form-control reference-amount" data-reference-id="' + purchase.id + '" min="0" max="' + totalDue + '" step="0.01" placeholder="0.00" value="0"></td>' +
                            '</tr>';
                        salesTableBody.append(row);
                    }
                });

                $('#totalSalesAmount').text('Rs. ' + totalSalesAmount.toFixed(2));
                $('#totalPaidAmount').text('Rs. ' + totalPaidAmount.toFixed(2));
                // No 'Rs.' prefix here - the HTML label already has it before the span
                $('#totalDueAmount').text(totalDueAmount.toFixed(2));

                window.purchaseDueAmount = totalDueAmount;
                // Sync totalSupplierDue to actual bill sum so Amount to Pay is correct
                window.totalSupplierDue = totalDueAmount;
                updateNetSupplierDue();
                $('#globalPaymentAmount').attr('max', totalDueAmount);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));

            } else {
                console.log('No purchases found for supplier');
                availableSupplierPurchases = [];
                populateFlexibleBillsList();
                $('#salesList tbody').empty();
                $('#salesList tbody').append('<tr><td colspan="5" class="text-center">No pending purchases found for this supplier</td></tr>');
                $('#totalSalesAmount').text('Rs. 0.00');
                $('#totalPaidAmount').text('Rs. 0.00');
                $('#totalDueAmount').text('0.00');
            }
        },
        error: function(xhr, status, error) {
            window.isLoadingPurchases = false;
            console.error('Error loading supplier purchases:', error);
            toastr.error('Failed to load supplier purchases: ' + error);
            $('#salesList tbody').empty();
            $('#salesList tbody').append('<tr><td colspan="5" class="text-center text-danger">Error loading purchase data</td></tr>');
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
    var customerId = $('#customerSelect').val();
    console.log('Payment type changed to:', paymentType);

    if (!customerId) {
        return;
    }

    // Show/hide sales list based on payment type
    if (paymentType === 'opening_balance') {
        $('#salesListContainer').hide();
    } else {
        $('#salesListContainer').show();
    }

    // Update max amount and placeholder for global payment input
    var supplierOpeningBalance = window.supplierOriginalOpeningBalance || 0;
    var purchaseDueAmount = window.purchaseDueAmount || 0;
    var totalDueAmount = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;

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

// Handle global payment amount input - AUTO-APPLY to sales
$(document).on('input', '#globalPaymentAmount', function() {
    var globalAmount = parseFloat($(this).val()) || 0;
    var supplierOpeningBalance = window.supplierOriginalOpeningBalance || 0;
    var remainingAmount = globalAmount;
    var paymentType = $('input[name="paymentType"]:checked').val();

    console.log('Global amount changed:', globalAmount, 'Payment type:', paymentType);

    // Validate global amount based on payment type
    var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;
    var maxAmount = 0;
    if (paymentType === 'opening_balance') {
        maxAmount = supplierOpeningBalance;
    } else if (paymentType === 'purchase_dues') {
        maxAmount = totalCustomerDue;
    } else if (paymentType === 'both') {
        maxAmount = totalCustomerDue;
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

        // Clear all sales payment inputs
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

        // Then apply remaining amount to sales in order
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

    var customerId = $('#customerSelect').val();
    var paymentMethod = $('#paymentMethod').val();

    console.log('Customer ID:', customerId);
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
        customerId: customerId,
        paymentMethod: paymentMethod,
        paymentDate: paymentDate,
        globalPaymentAmount: globalPaymentAmount,
        paymentType: paymentType
    });

    console.log('Date after conversion:', paymentDate);

    // Validate customer selection
    if (!customerId) {
        console.error('Validation failed: No customer selected');
        return;
    }

    // Validate payment amount based on payment type
    var maxAmount = 0;
    var supplierOpeningBalance = parseFloat($('#customerSelect').find(':selected').data('opening-balance')) || 0;
    var totalDueAmount = parseFloat($('#totalDueAmount').text().replace('Rs. ', '')) || 0;
    var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;

    console.log('Balance details:', {
        supplierOpeningBalance: supplierOpeningBalance,
        totalDueAmount: totalDueAmount, // This is purchase dues only
        totalCustomerDue: totalCustomerDue // This is total customer due (sales + opening balance)
    });

    if (paymentType === 'opening_balance') {
        maxAmount = supplierOpeningBalance;
    } else if (paymentType === 'purchase_dues') {
        // Allow payment up to total purchase due even when "purchase_dues" is selected
        maxAmount = totalCustomerDue;
    } else if (paymentType === 'both') {
        maxAmount = totalCustomerDue;
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
        customerId: customerId,
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
        console.error('Validation failed: Payment amount exceeds total customer due', {
            globalAmount: globalPaymentAmount,
            maxAmount: maxAmount,
            paymentType: paymentType,
            purchaseDues: totalDueAmount,
            totalCustomerDue: totalCustomerDue
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
    var salePayments = [];
    if (paymentType === 'purchase_dues' || paymentType === 'both') {
        $('.reference-amount').each(function() {
            var referenceId = $(this).data('reference-id');
            var paymentAmount = parseFloat($(this).val()) || 0;
            if (paymentAmount > 0) {
                salePayments.push({
                    reference_id: referenceId,
                    amount: paymentAmount
                });
            }
        });
    }

    var paymentData = {
        entity_type: 'supplier',
        entity_id: customerId,
        payment_method: paymentMethod,
        payment_date: paymentDate,
        global_amount: globalPaymentAmount,
        payment_type: paymentType,
        sale_payments: salePayments
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
        url: '/submit-bulk-payment',
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
            $('#customerSelect').val('').trigger('change');
            $('#salesList tbody').empty();
            $('#openingBalance').text('0.00');
            $('#totalSalesAmount').text('Rs. 0.00');
            $('#totalPaidAmount').text('Rs. 0.00');
            $('#totalDueAmount').text('0.00');
            $('#totalCustomerDue').text('Rs. 0.00');
            $('#individualPaymentTotal').text('Rs. 0.00');
            $('#globalPaymentAmount').val('');

            // Reload customers to refresh due amounts
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
        $('#globalPaymentAmount').prop('disabled', true).val('').attr('placeholder', 'Calculated from allocations');        // Load customer bills for simple payment table
        const customerId = $('#customerSelect').val();
        if (customerId && availableSupplierPurchases.length === 0) {
            loadPurchasesForMultiMethod(customerId);
        } else {
            $('#billsPaymentTableBody').html('<tr><td colspan="6" class="text-center text-muted">Please select a customer first</td></tr>');
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

    // Show submit button if payment method is selected and customer is chosen
    if ($('#customerSelect').val() && paymentMethod !== '') {
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
    const customerId = $('#customerSelect').val();
    if (!customerId) {
        toastr.warning('Please select a customer first');
        return;
    }

    // Check if bills are available
    if (!availableSupplierPurchases || availableSupplierPurchases.length === 0) {
        // Only load bills if we haven't tried loading them already
        if (!window.isLoadingCustomerSales) {
            window.isLoadingCustomerSales = true;
            toastr.info('Loading supplier purchase bills...');
            loadPurchasesForMultiMethod(customerId);
        } else {
            toastr.warning('No outstanding purchase bills available for this supplier');
        }
        return;
    }

    const groupIndex = methodGroupCounter++;
    console.log('Creating group with', availableSupplierPurchases.length, 'available purchases');

    const salesOptions = availableSupplierPurchases.map(sale =>
        `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${sale.reference_no}">
            ${sale.reference_no} - Due: Rs.${parseFloat(sale.total_due).toFixed(2)}
        </option>`
    ).join('');

    console.log('Generated sales options for new group:', salesOptions);

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
        const remainingAmount = selectedOption.data('remaining') || 0; // FIX: Use remaining amount after return credits
        const $row = $(this).closest('.bill-row');

        $row.find('.due-display').text(parseFloat(remainingAmount).toFixed(2));
        $row.find('.bill-amount').attr('max', remainingAmount).val('');
        updateSummaryTotals();
    });

    // Amount change calculation
    $(document).on('input', '.bill-amount', function() {
        updateSummaryTotals();
    });

    // Customer selection change - load sales for multi-method
    $('#customerSelect').change(function() {
        const customerId = $(this).val();
        const paymentMethod = $('#paymentMethod').val();

        if (customerId && paymentMethod === 'multiple') {
            // CLEAR ALL OLD CUSTOMER DATA
            // 1. Clear bill allocations tracking
            billPaymentAllocations = {};
            paymentMethodAllocations = {};

            // 2. Clear return credit allocations
            if (window.billReturnCreditAllocations) {
                window.billReturnCreditAllocations = {};
            }

            // 3. Clear all payment method cards
            $('#flexiblePaymentsList').empty();
            flexiblePaymentCounter = 0;

            // 4. Clear simple table
            $('#billsPaymentTableBody').empty();

            // 5. Clear available sales
            availableSupplierPurchases = [];
            window.isLoadingPurchases = false; // Reset loading flag so fresh load is allowed

            // 6. Clear selected returns
            selectedPurchaseReturns = [];
            $('.return-checkbox').prop('checked', false);

            // 7. Reset summary
            updateSummaryTotals();

            // 8. Load new customer bills
            loadPurchasesForMultiMethod(customerId);

            console.log('Customer changed - all old data cleared');
        }
    });
});

// Load supplier purchases for flexible many-to-many system
// Reuses already-loaded data from loadPurchasesForSupplier - no extra AJAX call
function loadPurchasesForMultiMethod(supplierId) {
    if (availableSupplierPurchases && availableSupplierPurchases.length > 0) {
        // Data already loaded by loadPurchasesForSupplier - just refresh the UI
        populateFlexibleBillsList();
    } else {
        // Data not loaded yet - trigger the single shared load
        loadPurchasesForSupplier(supplierId);
    }
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

// Helper function to escape HTML special characters
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Populate flexible bills list (left side)
function recalcBillPaymentAllocationsFromUI() {
    const newAllocations = {};

    $('.bill-allocation-row').each(function() {
        const billId = $(this).find('.bill-select').val();
        const amount = parseFloat($(this).find('.allocation-amount').val()) || 0;

        if (billId && amount > 0) {
            newAllocations[billId] = (newAllocations[billId] || 0) + amount;
        }
    });

    billPaymentAllocations = newAllocations;
}

function populateFlexibleBillsList(searchTerm = '') {
    recalcBillPaymentAllocationsFromUI();
    let billsHTML = '';

    // Filter bills based on search term (reference number)
    let filteredSales = availableSupplierPurchases;
    if (searchTerm && searchTerm.trim() !== '') {
        const searchLower = searchTerm.toLowerCase().trim();
        filteredSales = availableSupplierPurchases.filter(sale => {
            const refMatch = sale.reference_no && sale.reference_no.toLowerCase().includes(searchLower);
            const billIdMatch = sale.id && sale.id.toString().includes(searchLower);
            return refMatch || billIdMatch;
        });
    }

    if (filteredSales.length === 0) {
        if (searchTerm && searchTerm.trim() !== '') {
            billsHTML = '<div class="alert alert-info text-center"><i class="fas fa-search"></i> No bills found matching "' + escapeHtml(searchTerm) + '"</div>';
        } else {
            billsHTML = '<div class="alert alert-warning text-center">No outstanding bills found</div>';
        }
    } else {
        filteredSales.forEach((sale) => {
            const allocatedAmount = billPaymentAllocations[sale.id] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[sale.id] || 0) : 0;
            const totalAllocated = allocatedAmount + returnCreditApplied;
            const remainingAmount = sale.total_due - totalAllocated;
            const isFullyPaid = remainingAmount <= 0.01; // Small threshold for floating point

            billsHTML += `
                <div class="bill-item border rounded p-2 mb-2 ${isFullyPaid ? 'bg-light' : 'bg-white'}" data-bill-id="${sale.id}" data-invoice="${escapeHtml(sale.reference_no)}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 small ${isFullyPaid ? 'text-muted' : 'text-primary'}">
                                ${escapeHtml(sale.reference_no)}
                                ${isFullyPaid ? '<span class="badge bg-success ms-1" style="font-size: 0.6rem;">PAID</span>' : ''}
                                ${returnCreditApplied > 0 ? '<span class="badge bg-info ms-1" style="font-size: 0.6rem;"><i class="fas fa-undo"></i> Credit</span>' : ''}
                            </h6>
                            <div class="small" style="font-size: 0.75rem;">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Current Due:</span>
                                    <span class="fw-bold">Rs. ${parseFloat(sale.total_due).toFixed(2)}</span>
                                </div>
                                ${returnCreditApplied > 0 ? `
                                    <div class="d-flex justify-content-between align-items-center text-info">
                                        <span><i class="fas fa-undo"></i> Return Credit:</span>
                                        <div>
                                            <span class="me-2">Rs. ${returnCreditApplied.toFixed(2)}</span>
                                            <button class="btn btn-xs btn-outline-warning quick-adjust-credit" data-sale-id="${sale.id}" title="Adjust amount" style="padding: 0.1rem 0.3rem; font-size: 0.65rem;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-xs btn-outline-danger quick-remove-credit" data-sale-id="${sale.id}" title="Remove credit" style="padding: 0.1rem 0.3rem; font-size: 0.65rem;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                ` : ''}
                                ${allocatedAmount > 0 ? `
                                    <div class="d-flex justify-content-between text-success">
                                        <span>Allocated:</span>
                                        <span>Rs. ${allocatedAmount.toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                ${(returnCreditApplied > 0 || allocatedAmount > 0) ? `
                                    <div class="d-flex justify-content-between border-top mt-1 pt-1">
                                        <span class="fw-bold ${isFullyPaid ? 'text-success' : 'text-warning'}">Remaining:</span>
                                        <span class="fw-bold ${isFullyPaid ? 'text-success' : 'text-warning'}">
                                            Rs. ${Math.max(0, remainingAmount).toFixed(2)}
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="ms-2">
                            ${!isFullyPaid ? `
                                <button class="btn btn-primary btn-sm add-to-payment-btn" data-bill-id="${sale.id}" style="font-size: 0.7rem; padding: 0.25rem 0.4rem;">
                                    <i class="fas fa-plus"></i>
                                </button>
                            ` : `
                                <button class="btn btn-success btn-sm" disabled style="font-size: 0.7rem; padding: 0.25rem 0.4rem;">
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
                    <label class="form-label small">Total Amount <span class="both-payment-hint" style="display:none;"><i class="fas fa-hand-holding-usd text-success"></i> Enter total receiving</span></label>
                    <input type="number" class="form-control payment-total-amount"
                           step="0.01" min="0.01" placeholder="Enter total amount received"
                           data-payment-id="${paymentId}">
                    <small class="text-muted both-payment-breakdown" style="display:none;">
                        <i class="fas fa-info-circle"></i> Allocation: OB <span class="ob-portion">0.00</span> + Bills <span class="bills-portion">0.00</span> <span class="advance-portion" style="display:none;">+ Advance <span class="advance-amount">0.00</span></span>
                    </small>
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

// Add bill allocation to a payment method (ENHANCED WITH BILL STATUS TRACKING AND NOTES)
function addBillAllocation(paymentId) {
    // Filter bills - exclude fully paid ones and show remaining amounts
    const availableBills = availableSupplierPurchases.filter(sale => {
        const allocatedAmount = billPaymentAllocations[sale.id] || 0;
        const remainingAmount = sale.total_due - allocatedAmount;
        return remainingAmount > 0.01; // Avoid tiny remaining amounts
    });

    if (availableBills.length === 0) {
        toastr.warning('No outstanding bills available for allocation. All bills are either fully paid or allocated.');
        return;
    }

    const allocationId = `allocation_${Date.now()}`;

    // Create enhanced bill options with status indicators and notes
    const billOptions = availableBills.map(sale => {
        const allocatedAmount = billPaymentAllocations[sale.id] || 0;
        const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[sale.id] || 0) : 0;
        const remainingAmount = sale.total_due - allocatedAmount - returnCreditApplied;
        const statusIcon = allocatedAmount > 0 ? '🟡' : (returnCreditApplied > 0 ? '🔵' : '🔴'); // Blue for return credit, Yellow for partial, Red for unpaid
        const statusText = allocatedAmount > 0 ? 'Partially Paid' : (returnCreditApplied > 0 ? 'Return Credit Applied' : 'Unpaid');
        const hasNotes = false; // Purchases do not carry sale_notes
        const noteIndicator = '';
        const notePreview = '';
        const returnCreditInfo = returnCreditApplied > 0 ? ` (After Rs.${returnCreditApplied.toFixed(2)} return credit)` : '';

        return `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${escapeHtml(sale.reference_no)}" data-remaining="${Math.max(0, remainingAmount)}">
            ${statusIcon} ${escapeHtml(sale.reference_no)} - Rs.${Math.max(0, remainingAmount).toFixed(2)} (${statusText})${returnCreditInfo}
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

    $(`.bill-allocations-list[data-payment-id="${paymentId}"]`).prepend(allocationHTML);

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
        let totalDueAmount = availableSupplierPurchases.reduce((sum, sale) => sum + parseFloat(sale.total_due || 0), 0);

        // Calculate payment totals
        let totalPaymentAmount = 0;
        if (paymentMethodAllocations && Object.keys(paymentMethodAllocations).length > 0) {
            Object.values(paymentMethodAllocations).forEach(payment => {
                totalPaymentAmount += payment.totalAmount || 0;
            });
        }

        // Calculate balance using actual bills sum (not stale supplier dropdown value)
        let balanceAmount = totalDueAmount - totalPaymentAmount;

        // Update UI elements if they exist
        const $totalBillsCount = $('#totalBillsCount');
        const $totalDueAmountFlex = $('#summaryTotalDue');
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
                $('#excessInfo').remove(); // Remove excess info when balance is positive
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
                    $('#excessInfo small').html(`<i class="fas fa-info-circle"></i> Excess Rs. ${excessAmount.toFixed(2)} will be treated as advance payment`);
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

// Auto-distribute amount to bills (for "both" payment type)
function autoDistributeToBills(paymentId, amountToDistribute) {
    const $paymentContainer = $(`.payment-method-item[data-payment-id="${paymentId}"]`);
    const $billAllocationsList = $paymentContainer.find('.bill-allocations-list');

    // Clear existing allocations for this payment method
    $billAllocationsList.find('.bill-allocation-row').each(function() {
        const $row = $(this);
        const billId = $row.find('.bill-select').val();
        const amount = parseFloat($row.find('.allocation-amount').val()) || 0;
        if (billId && amount > 0) {
            billPaymentAllocations[billId] = Math.max(0, (billPaymentAllocations[billId] || 0) - amount);
        }
    });
    $billAllocationsList.empty();

    let remainingAmount = amountToDistribute;
    let billIndex = 0;

    // Sort bills by date (FIFO - oldest first)
    const sortedBills = [...availableSupplierPurchases].sort((a, b) => {
        return new Date(a.purchase_date) - new Date(b.purchase_date);
    });

    // Auto-select bills until amount is exhausted
    for (const bill of sortedBills) {
        if (remainingAmount <= 0.01) break;

        const allocatedAmount = billPaymentAllocations[bill.id] || 0;
        const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[bill.id] || 0) : 0;
        const remainingDue = bill.total_due - allocatedAmount - returnCreditApplied;

        if (remainingDue > 0.01) {
            const amountForThisBill = Math.min(remainingAmount, remainingDue);

            // Add bill allocation row
            const allocationId = `allocation_${Date.now()}_${billIndex}`;
            const isFullyPaid = (amountForThisBill >= remainingDue - 0.01);

            const allocationHTML = `
                <div class="bill-allocation-row border rounded p-2 mb-2 bg-white" data-allocation-id="${allocationId}">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <select class="form-select form-select-sm bill-select" data-allocation-id="${allocationId}">
                                <option value="${bill.id}" selected>${bill.reference_no} - Rs. ${parseFloat(bill.total_due).toFixed(2)}</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" class="form-control form-control-sm allocation-amount"
                                   value="${amountForThisBill.toFixed(2)}"
                                   data-allocation-id="${allocationId}" data-prev-amount="${amountForThisBill}">
                            <small class="bill-amount-hint ${isFullyPaid ? 'text-success' : 'text-muted'}">
                                ${isFullyPaid ? '✅ Bill will be fully paid' : '💰 Remaining: Rs. ' + (remainingDue - amountForThisBill).toFixed(2)}
                            </small>
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-danger btn-xs remove-bill-allocation-btn" data-allocation-id="${allocationId}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $billAllocationsList.prepend(allocationHTML);

            // Update tracking
            billPaymentAllocations[bill.id] = (billPaymentAllocations[bill.id] || 0) + amountForThisBill;

            remainingAmount -= amountForThisBill;
            billIndex++;
        }
    }

    populateFlexibleBillsList();

    return remainingAmount;
}

// Update payment method allocation totals (FOR NON-BOTH TYPES)
function updatePaymentMethodTotal(paymentId) {
    const paymentType = $('input[name="paymentType"]:checked').val();

    // For "both" type, don't auto-calculate from bills - user enters total manually
    if (paymentType === 'both') {
        return; // Total is entered manually by user
    }

    let billsTotal = 0;

    // Find all allocation amounts within this specific payment method
    $(`.payment-method-item[data-payment-id="${paymentId}"] .allocation-amount`).each(function() {
        billsTotal += parseFloat($(this).val()) || 0;
    });

    // Update internal tracking
    paymentMethodAllocations[paymentId].totalAmount = billsTotal;

    // Update the payment total input field (mark as system update to avoid recursion)
    const $totalInput = $(`.payment-total-amount[data-payment-id="${paymentId}"]`);
    $totalInput.data('system-update', true);
    $totalInput.val(billsTotal.toFixed(2));

    // Remove system update flag after a short delay
    setTimeout(() => {
        $totalInput.data('system-update', false);
    }, 100);

    updateSummaryTotals();
}

// Submit flexible many-to-many payment
function submitMultiMethodPayment() {
    const customerId = $('#customerSelect').val();
    const paymentDate = $('#paidOn').val() || $('[name="payment_date"]').val();
    const paymentType = $('input[name="paymentType"]:checked').val();

    if (!customerId) {
        toastr.error('Please select a customer');
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

    // Initialize bill return allocations BEFORE collecting payment groups
    let billReturnAllocations = {};
    const hasApplyToSalesReturns = selectedPurchaseReturns.some(r => r.action === 'apply_to_purchases');
    if (hasApplyToSalesReturns && window.billReturnCreditAllocations) {
        billReturnAllocations = window.billReturnCreditAllocations;
        console.log('Bill return allocations available:', billReturnAllocations);
    }

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
                // UI already shows the correct payment amount (after return credit deduction)
                // Just send it as-is
                groupData.bills.push({
                    purchase_id: parseInt(billId),
                    amount: amount
                });
            }
        });

        // Include advance amount if user selected "Keep as Advance Payment"
        let totalBillsAllocated = 0;
        groupData.bills.forEach(bill => {
            totalBillsAllocated += bill.amount;
        });
        const advancePaymentAmount = totalAmount - totalBillsAllocated;
        const selectedAdvanceOption = $payment.find(`input[name="excess_${paymentId}"]:checked`).val();
        if (advancePaymentAmount > 0.01 && selectedAdvanceOption === 'advance') {
            groupData.advance_amount = advancePaymentAmount;
        }

        // For "both" payment type, include OB portion from breakdown
        if (paymentType === 'both') {
            const obPortionText = $payment.find('.ob-portion').text();
            const obPortion = parseFloat(obPortionText) || 0;

            if (obPortion > 0) {
                groupData.ob_amount = obPortion;
            }
        }

        // For opening balance payments, use the total amount even if no bills
        if (paymentType === 'opening_balance' && totalAmount > 0) {
            groupData.totalAmount = totalAmount;
            paymentGroups.push(groupData);
            hasValidPayments = true;
        } else if (paymentType === 'both' && (groupData.bills.length > 0 || groupData.ob_amount > 0)) {
            // For "both" type, valid if has bills OR OB amount
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
        const bill = availableSupplierPurchases.find(s => s.id == billId);
        if (bill && totalAllocated > bill.total_due) {
            toastr.error(`Total allocation for ${bill.reference_no} exceeds bill amount`);
            return false;
        }
    }

    // Show loading
    $('#submitBulkPayment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    // Collect advance credit application if checked
    let advanceCreditApplied = 0;
    if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
        advanceCreditApplied = parseFloat($('#advanceCreditAmountInput').val()) || 0;
    }

    console.log('Submitting payment with:', {
        selected_returns: selectedPurchaseReturns,
        hasApplyToSalesReturns: hasApplyToSalesReturns,
        bill_return_allocations: billReturnAllocations,
        advance_credit_applied: advanceCreditApplied,
        payment_groups: paymentGroups
    });

    // Validate: Bills in payment_groups should not include amounts already covered by return credits
    if (hasApplyToSalesReturns && Object.keys(billReturnAllocations).length > 0) {
        paymentGroups.forEach(group => {
            group.bills.forEach(bill => {
                const returnCredit = billReturnAllocations[bill.purchase_id] || 0;
                if (returnCredit > 0) {
                    console.log(`Bill ${bill.purchase_id}: Payment amount = ${bill.amount}, Return credit = ${returnCredit}`);
                }
            });
        });
    }

    // Submit flexible purchase payment
    $.ajax({
        url: '/submit-flexible-bulk-purchase-payment',
        method: 'POST',
        data: {
            supplier_id: customerId,
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

    // Bill Search/Filter functionality - searches invoice numbers and sale notes
    let billSearchTimeout;
    $('#billSearchInput').on('keyup', function() {
        const searchValue = $(this).val();

        // Debounce the search to avoid too many updates
        clearTimeout(billSearchTimeout);
        billSearchTimeout = setTimeout(function() {
            populateFlexibleBillsList(searchValue);
        }, 300);
    });

    // Clear search when input is cleared
    $('#billSearchInput').on('search', function() {
        if ($(this).val() === '') {
            populateFlexibleBillsList('');
        }
    });

    // Add Payment Method button
    $('#addFlexiblePayment').click(function() {
        addFlexiblePayment();
    });


    // Remove Payment Method
    $(document).on('click', '.remove-payment-btn', function() {
        const paymentId = $(this).data('payment-id');
        const $paymentContainer = $(this).closest('.payment-method-item');

        // Clear all bill allocations from tracking for this payment method
        $paymentContainer.find('.bill-allocation-row').each(function() {
            const $row = $(this);
            const billId = $row.find('.bill-select').val();
            const amount = parseFloat($row.find('.allocation-amount').val()) || 0;

            if (billId && amount > 0) {
                // Remove from global tracking
                billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
                if (billPaymentAllocations[billId] <= 0) {
                    delete billPaymentAllocations[billId];
                }
            }
        });

        // Remove from payment method allocations
        if (paymentMethodAllocations[paymentId]) {
            delete paymentMethodAllocations[paymentId];
        }

        $paymentContainer.fadeOut(300, function() {
            $(this).remove();
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
        const $paymentContainer = $(this).closest('.payment-method-item');
        const totalAmount = parseFloat($paymentContainer.find('.payment-total-amount').val()) || 0;
        const paymentType = $('input[name="paymentType"]:checked').val();

        // If total amount is entered, auto-allocate bills in FIFO order
        if (totalAmount > 0 && paymentType !== 'both') {
            // Calculate how much is already allocated
            let alreadyAllocated = 0;
            $paymentContainer.find('.allocation-amount').each(function() {
                alreadyAllocated += parseFloat($(this).val()) || 0;
            });

            const remainingToAllocate = totalAmount - alreadyAllocated;

            if (remainingToAllocate > 0.01) {
                // Auto-allocate remaining amount using FIFO
                autoDistributeToBills(paymentId, remainingToAllocate);
                toastr.success('Bills auto-allocated in FIFO order (oldest first)', 'Success');
            } else if (remainingToAllocate < -0.01) {
                toastr.warning('Total amount is less than already allocated bills. Please adjust.', 'Warning');
            } else {
                // Add manual row if exact match
                addBillAllocation(paymentId);
            }
        } else {
            // No total amount entered or "both" type - add manual row
            addBillAllocation(paymentId);
        }
    });

    // Remove Bill Allocation (handle both old and new button classes)
    $(document).on('click', '.remove-allocation-btn, .remove-bill-allocation-btn', function() {
        const allocationId = $(this).data('allocation-id');
        const $row = $(this).closest('.bill-allocation-row');
        const billId = $row.find('.bill-select').val();
        const amount = parseFloat($row.find('.allocation-amount').val()) || 0;
        const paymentId = $row.closest('.payment-method-item').data('payment-id');
        const paymentType = $('input[name="paymentType"]:checked').val();

        // Update tracking
        if (billId && amount > 0) {
            billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
            if (billPaymentAllocations[billId] <= 0) {
                delete billPaymentAllocations[billId];
            }
        }

        $row.fadeOut(200, function() {
            $row.remove();

            // For "both" type, recalculate breakdown after removing bill
            if (paymentType === 'both' && paymentId) {
                const $paymentContainer = $(`.payment-method-item[data-payment-id="${paymentId}"]`);
                const totalAmount = parseFloat($paymentContainer.find('.payment-total-amount').val()) || 0;

                if (totalAmount > 0) {
                    // Recalculate bills portion
                    let billsPortion = 0;
                    $paymentContainer.find('.allocation-amount').each(function() {
                        billsPortion += parseFloat($(this).val()) || 0;
                    });

                    const selectedOption = $('#customerSelect').find(':selected');
                    const supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
                    const obPortion = Math.min(totalAmount, supplierOpeningBalance);
                    const advancePortion = Math.max(0, totalAmount - obPortion - billsPortion);

                    // Update breakdown
                    $paymentContainer.find('.bills-portion').text(billsPortion.toFixed(2));
                    if (advancePortion > 0.01) {
                        $paymentContainer.find('.advance-portion').show();
                        $paymentContainer.find('.advance-amount').text(advancePortion.toFixed(2));
                    } else {
                        $paymentContainer.find('.advance-portion').hide();
                    }
                }
            }

            // CRITICAL FIX: Update payment method total after removing bill
            // This ensures paymentMethodAllocations[paymentId].totalAmount is recalculated
            if (paymentId) {
                updatePaymentMethodTotal(paymentId);
            }

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
            const bill = availableSupplierPurchases.find(s => s.id == billId);
            const allocatedAmount = billPaymentAllocations[billId] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
            const remainingAmount = bill.total_due - allocatedAmount - returnCreditApplied;

            // Enable amount input but DON'T auto-fill
            $amountInput.attr('max', remainingAmount.toFixed(2)).prop('disabled', false);
            $amountInput.attr('placeholder', `Max: Rs. ${remainingAmount.toFixed(2)}`);
            $amountInput.val(''); // Empty - let user type

            // Show available amount info
            if (returnCreditApplied > 0) {
                $hint.html(`Available: Rs. ${remainingAmount.toFixed(2)} <span class="badge bg-info return-credit-badge" data-sale-id="${billId}" style="cursor: pointer;" title="Click to adjust return credit allocation"><i class="fas fa-undo"></i> Rs.${returnCreditApplied.toFixed(2)} credit <i class="fas fa-edit" style="font-size: 0.7em;"></i></span>`).show();
            } else {
                $hint.text(`Available: Rs. ${remainingAmount.toFixed(2)} (${allocatedAmount > 0 ? 'Partially Paid' : 'Unpaid'})`).show();
            }
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

        const bill = availableSupplierPurchases.find(s => s.id == billId);
        if (!bill) return;

        // Calculate available amount for this bill (including return credits)
        const prevAmount = $(this).data('prev-amount') || 0;
        const currentAllocation = billPaymentAllocations[billId] || 0;
        const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
        const maxAmount = bill.total_due - (currentAllocation - prevAmount) - returnCreditApplied;

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
        const remainingAfterPayment = bill.total_due - billPaymentAllocations[billId] - returnCreditApplied;

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

    // Payment Total Amount Input - AUTO-DISTRIBUTION FOR "BOTH" TYPE
    $(document).on('input', '.payment-total-amount', function() {
        try {
            const paymentId = $(this).data('payment-id');
            const totalAmount = parseFloat($(this).val()) || 0;
            const $paymentContainer = $(this).closest('.payment-method-item');
            const paymentType = $('input[name="paymentType"]:checked').val();

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

                // FOR "BOTH" TYPE: Auto-distribute to OB + Bills
                if (paymentType === 'both') {
                    if (totalAmount > 0) {
                        const selectedOption = $('#customerSelect').find(':selected');
                        const supplierOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;

                        // Calculate how much OB has ALREADY been allocated in OTHER payment methods
                        let totalOBAllocated = 0;
                        $('.payment-method-item').each(function() {
                            const thisPaymentId = $(this).data('payment-id');
                            if (thisPaymentId !== paymentId) {
                                const obText = $(this).find('.ob-portion').text();
                                totalOBAllocated += parseFloat(obText) || 0;
                            }
                        });

                        // Calculate remaining OB available for THIS payment
                        const remainingOB = Math.max(0, supplierOpeningBalance - totalOBAllocated);

                        let obPortion = 0;
                        let billsPortion = 0;
                        let advancePortion = 0;
                        let remainingAmount = totalAmount;

                        // First allocate to opening balance (only if OB still available)
                        if (remainingOB > 0) {
                            obPortion = Math.min(remainingAmount, remainingOB);
                            remainingAmount -= obPortion;
                        }

                        // Auto-select bills and allocate remaining
                        if (remainingAmount > 0) {
                            autoDistributeToBills(paymentId, remainingAmount);

                            // Calculate bills portion
                            $paymentContainer.find('.allocation-amount').each(function() {
                                billsPortion += parseFloat($(this).val()) || 0;
                            });

                            remainingAmount -= billsPortion;
                        }

                        // Any remaining is advance payment
                        if (remainingAmount > 0.01) {
                            advancePortion = remainingAmount;
                        }

                        // Show breakdown
                        $paymentContainer.find('.both-payment-hint').show();
                        $paymentContainer.find('.both-payment-breakdown').show();
                        $paymentContainer.find('.ob-portion').text(obPortion.toFixed(2));
                        $paymentContainer.find('.bills-portion').text(billsPortion.toFixed(2));

                        if (advancePortion > 0) {
                            $paymentContainer.find('.advance-portion').show();
                            $paymentContainer.find('.advance-amount').text(advancePortion.toFixed(2));
                        } else {
                            $paymentContainer.find('.advance-portion').hide();
                        }
                    } else {
                        // Amount is 0 or cleared - clear all bill allocations
                        $paymentContainer.find('.bill-allocation-row').each(function() {
                            const $row = $(this);
                            const billId = $row.find('.bill-select').val();
                            const amount = parseFloat($row.find('.allocation-amount').val()) || 0;

                            if (billId && amount > 0) {
                                billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
                                if (billPaymentAllocations[billId] <= 0) {
                                    delete billPaymentAllocations[billId];
                                }
                            }
                        });

                        // Clear bill allocations list
                        $paymentContainer.find('.bill-allocations-list').empty();

                        // Hide breakdown
                        $paymentContainer.find('.both-payment-breakdown').hide();
                        $paymentContainer.find('.advance-portion').hide();

                        populateFlexibleBillsList();
                    }

                    updateSummaryTotals();
                    return;
                }

                // FOR "PURCHASE_DUES" TYPE: Auto-allocate bills in FIFO order
                if (paymentType === 'purchase_dues') {
                    if (totalAmount > 0) {
                        // Step 1: Get allocations from OTHER payment methods ONLY (exclude this one)
                        const otherPaymentsAllocations = {};
                        $('.bill-allocation-row').each(function() {
                            const $container = $(this).closest('.payment-method-item');
                            const currentPaymentId = $container.data('payment-id');

                            // Skip rows from THIS payment method
                            if (currentPaymentId === paymentId) {
                                return;
                            }

                            const billId = $(this).find('.bill-select').val();
                            const amount = parseFloat($(this).find('.allocation-amount').val()) || 0;

                            if (billId && amount > 0.01) {
                                otherPaymentsAllocations[billId] = (otherPaymentsAllocations[billId] || 0) + amount;
                            }
                        });

                        // Step 2: Set billPaymentAllocations to only include OTHER payment methods
                        billPaymentAllocations = otherPaymentsAllocations;

                        // Step 3: Clear existing allocations for THIS payment method
                        $paymentContainer.find('.bill-allocation-row').each(function() {
                            const $row = $(this);
                            const billId = $row.find('.bill-select').val();
                            const amount = parseFloat($row.find('.allocation-amount').val()) || 0;
                            if (billId && amount > 0.01) {
                                // Already removed by setting billPaymentAllocations = otherPaymentsAllocations above
                            }
                        });

                        // Step 4: Clear bill allocations list for THIS payment method
                        $paymentContainer.find('.bill-allocations-list').empty();

                        // Step 5: Auto-distribute to bills in FIFO order
                        // At this point, billPaymentAllocations contains ONLY OTHER payment methods
                        const returnedAmount = autoDistributeToBills(paymentId, totalAmount);

                        // Step 6: Get actual allocated amount from newly created rows
                        let actualAllocatedAmount = 0;
                        $paymentContainer.find('.allocation-amount').each(function() {
                            actualAllocatedAmount += parseFloat($(this).val()) || 0;
                        });

                        // Step 7: Calculate advance/excess amount
                        const advanceAmount = totalAmount - actualAllocatedAmount;

                        // Step 8: Update hint/excess options (advance payment support)
                        const $hint = $paymentContainer.find('.payment-total-hint');

                        if (advanceAmount > 0.01) {
                            const hintText = `⚠️ Excess Rs. ${advanceAmount.toFixed(2)} - Will be treated as advance payment`;
                            if ($hint.length === 0) {
                                $paymentContainer.find('.payment-total-amount').after(
                                    `<small class="payment-total-hint text-warning d-block">${hintText}</small>`
                                );
                            } else {
                                $hint.text(hintText).removeClass('text-success text-info').addClass('text-warning');
                            }

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
                                                ✂️ Reduce Total to Rs. ${actualAllocatedAmount.toFixed(2)}
                                            </label>
                                        </div>
                                    </div>
                                `);
                            } else {
                                $excessOptions.find(`label[for="reduce_${paymentId}"]`).text(`✂️ Reduce Total to Rs. ${actualAllocatedAmount.toFixed(2)}`);
                            }
                        } else {
                            const hintText = `✅ Perfect allocation: Rs. ${actualAllocatedAmount.toFixed(2)}`;
                            if ($hint.length === 0) {
                                $paymentContainer.find('.payment-total-amount').after(
                                    `<small class="payment-total-hint text-success d-block">${hintText}</small>`
                                );
                            } else {
                                $hint.text(hintText).removeClass('text-warning text-info').addClass('text-success');
                            }

                            $paymentContainer.find('.excess-options').remove();
                        }

                        // Recalculate billPaymentAllocations from UI to include the newly created allocations
                        recalcBillPaymentAllocationsFromUI();

                        populateFlexibleBillsList();
                        updateSummaryTotals();
                    } else {
                        // Amount is 0 or cleared - clear all bill allocations
                        $paymentContainer.find('.bill-allocation-row').each(function() {
                            const $row = $(this);
                            const billId = $row.find('.bill-select').val();
                            const amount = parseFloat($row.find('.allocation-amount').val()) || 0;

                            if (billId && amount > 0) {
                                billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) - amount;
                                if (billPaymentAllocations[billId] <= 0) {
                                    delete billPaymentAllocations[billId];
                                }
                            }
                        });

                        // Clear bill allocations list and hints
                        $paymentContainer.find('.bill-allocations-list').empty();
                        $paymentContainer.find('.payment-total-hint').remove();
                        $paymentContainer.find('.excess-options').remove();
                        populateFlexibleBillsList();
                        updateSummaryTotals();
                    }
                    return;
                }

                // Get existing bill allocation rows (for other payment types)
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
                            const bill = availableSupplierPurchases.find(s => s.id == billId);
                            if (bill) {
                                const previousAllocated = billPaymentAllocations[billId] || 0;
                                const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
                                const availableAmount = bill.total_due - previousAllocated - returnCreditApplied;

                                billsToUpdate.push({
                                    row: $row,
                                    billId: billId,
                                    availableAmount: availableAmount,
                                    invoice: bill.reference_no,
                                    returnCredit: returnCreditApplied
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
                        const bill = availableSupplierPurchases.find(s => s.id == billInfo.billId);
                        if (bill) {
                            const currentAllocated = billPaymentAllocations[billInfo.billId] || 0;
                            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billInfo.billId] || 0) : 0;
                            billInfo.availableAmount = bill.total_due - currentAllocated - returnCreditApplied;
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

    // Add to Payment from Bill (Quick Add) - FIXED: Prevent triggering auto-allocation
    $(document).on('click', '.add-to-payment-btn', function() {
        const billId = $(this).data('bill-id');
        const bill = availableSupplierPurchases.find(s => s.id == billId);

        if (!bill) {
            toastr.error('Bill not found');
            return;
        }

        if ($('.payment-method-item').length === 0) {
            addFlexiblePayment();
        }

        // Find the first payment method or create one
        const $firstPayment = $('.payment-method-item').first();
        const paymentId = $firstPayment.data('payment-id');

        // Calculate remaining amount BEFORE adding allocation
        const allocatedAmount = billPaymentAllocations[billId] || 0;
        const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
        const remainingAmount = bill.total_due - allocatedAmount - returnCreditApplied;

        if (remainingAmount <= 0.01) {
            toastr.warning(`${bill.reference_no} is already fully paid or allocated`);
            return;
        }

        // Add allocation row
        addBillAllocation(paymentId);

        // Pre-select the bill in the newly created row
        const $lastAllocation = $firstPayment.find('.bill-allocation-row').first(); // Get the first one since prepend
        const $billSelect = $lastAllocation.find('.bill-select');
        const $amountInput = $lastAllocation.find('.allocation-amount');
        const $hint = $lastAllocation.find('.bill-amount-hint');

        // Set the bill selection
        $billSelect.val(billId);

        // Enable amount input
        $amountInput.prop('disabled', false);
        $amountInput.attr('max', remainingAmount.toFixed(2));
        $amountInput.attr('placeholder', `Max: Rs. ${remainingAmount.toFixed(2)}`);

        // Set amount value
        const amountValue = parseFloat(remainingAmount.toFixed(2));
        $amountInput.val(amountValue.toFixed(2));
        $amountInput.data('prev-amount', amountValue);

        // Update tracking
        billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) + amountValue;

        // Update hint
        if (returnCreditApplied > 0) {
            $hint.html(`✅ Bill will be fully paid <span class="badge bg-info" style="font-size: 0.65rem;"><i class="fas fa-undo"></i> Rs.${returnCreditApplied.toFixed(2)} credit</span>`).removeClass('text-muted').addClass('text-success').show();
        } else {
            $hint.text('✅ Bill will be fully paid').removeClass('text-muted').addClass('text-success').show();
        }

        // Update payment method totals
        updatePaymentMethodTotal(paymentId);

        // Refresh UI
        populateFlexibleBillsList();
        updateSummaryTotals();

        toastr.success(`Added ${bill.reference_no} - Rs. ${amountValue.toFixed(2)}`);
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

<!-- Clean UI Styling -->
<style>
/* Clean, minimal styling for the new bulk payments UI */
.display-6 {
    font-size: 2.5rem;
    font-weight: 300;
    line-height: 1.2;
}

#customerSummarySection {
    transition: all 0.3s ease;
}

.border-bottom {
    border-bottom: 1px solid #dee2e6 !important;
}

/* Progressive disclosure transitions */
#customerReturnsSection,
#paymentTypeSection,
#advancedOptionsContainer {
    transition: all 0.3s ease;
}

.collapse {
    display: none;
}

.collapse.show {
    display: block;
}

/* Bill items container scrollbar styling */
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

/* Clean hover effects */
a:hover {
    opacity: 0.8;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Smooth animations */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Return row hover */
.return-row {
    transition: background-color 0.2s ease;
}

.return-row:hover {
    background-color: #f8f9fa !important;
}

.return-row.table-active {
    background-color: #e7f3ff !important;
    border-left: 3px solid #0dcaf0;
}
</style>

<!-- SweetAlert2 for beautiful alerts/dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection

