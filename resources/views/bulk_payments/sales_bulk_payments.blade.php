@extends('layout.layout')
@section('content')
<div class="container-fluid py-2 bulk-payment-page">
    <form id="bulkPaymentForm">
        <input id="sale_id" name="sale_id" type="hidden">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header" style="padding: 2px 0; margin-bottom: 4px;">

                            <ul class="breadcrumb" style="margin-bottom: 0; font-size: 12px;">
                                <li class="breadcrumb-item"><a href="{{ route('manage-bulk-payments') }}">Bulk payments</a></li>
                                <li class="breadcrumb-item active">Add Sale Payments</li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Step flow (visible after customer selected) -->
        <div id="workflowStepsBar" class="bulk-workflow-steps mb-3" style="display: none;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3 bg-white border rounded-3 shadow-sm">
                <ol class="order-flow list-unstyled mb-0 d-flex flex-wrap align-items-center gap-2 gap-md-3 small">
                    <li class="order-flow-step"><span class="order-flow-num">1</span> Customer</li>
                    <li class="order-flow-step text-muted"><span class="order-flow-num">2</span> Payment</li>
                    <li class="order-flow-step text-muted"><span class="order-flow-num">3</span> Allocate</li>
                    <li class="order-flow-step text-muted"><span class="order-flow-num">4</span> Confirm</li>
                </ol>
            </div>
        </div>

        <!-- Step 1: Customer Selection - Clean & Simple -->
        <div class="mb-1">
            <!-- Customer Selection -->
            <div class="mb-1">
                <label for="customerSelect" class="form-label">Customer</label>
                <select id="customerSelect" class="form-control selectBox">
                    <option value="">🔄 Loading customers...</option>
                </select>
            </div>

            <!-- Customer Summary: primary = cash to pay; rest in collapsible details -->
            <div id="customerSummarySection" class="border rounded-3 p-1 mb-2 bg-white shadow-sm" style="display: none;">
                <div class="row g-1 align-items-start customer-summary-grid">
                    <div class="col-12 col-lg-7 min-w-0">
                        <details id="customerBalanceDetails" class="customer-balance-details border-0" open>
                            <summary class="customer-balance-summary text-muted small user-select-none">
                                <i class="fas fa-list-ul me-1 text-secondary"></i>Balance
                            </summary>
                            <div class="mt-1 pt-1 border-top border-light small">
                                <div class="d-flex flex-wrap gap-1 align-items-baseline">
                                    <span class="text-muted me-1">Opening Balance: <strong class="text-dark">Rs. <span id="openingBalance">0.00</span></strong></span>
                                    <span class="text-muted border-start border-light ps-2">Sales unpaid (invoices): <strong class="text-dark">Rs. <span id="totalDueAmount">0.00</span></strong></span>
                                    <span id="returnCount" class="text-info" style="display: none;">(<span id="returnCountNumber">0</span> returns available)</span>
                                    <span id="advanceCount" class="text-success" style="display: none;">(Advance: Rs. <span id="advanceAmount">0.00</span>)</span>
                                </div>
                                <div id="returnCreditAppliedSummary" class="mt-1 text-muted small" style="display: none;"></div>
                                <div id="netCalculation" class="mt-1" style="display:none !important"></div>
                            </div>
                        </details>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="amount-to-pay-highlight border border-primary rounded-3 p-1 bg-white text-start h-100">
                            <div class="text-uppercase text-muted fw-semibold mb-1" style="font-size:11px;letter-spacing:.04em;">Sales due (invoices)</div>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <div class="h4 fw-bold text-primary mb-0" id="netCustomerDue">Rs. 0.00</div>
                                <button type="button" class="btn btn-sm btn-light border rounded-circle p-1 lh-1" id="whyAmountLink" title="Why this amount?" aria-label="Why this amount?">
                                    <i class="fas fa-info-circle text-secondary" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div id="ledgerVsSalesHint" class="text-muted small mt-1" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="small text-muted mb-1 d-flex flex-wrap align-items-center gap-2">
                <a href="#" class="link-secondary text-decoration-underline" id="customizePaymentLink">Payment options</a>
            </div>

            <!-- Payment Type Selection - Hidden by default, shown only when needed -->
            <div id="paymentTypeSection" class="mb-1" style="display: none;">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="paymentType" id="paySaleDues" value="sale_dues" checked>
                        <label class="form-check-label" for="paySaleDues">Sale Bills</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="paymentType" id="payOpeningBalance" value="opening_balance">
                        <label class="form-check-label" for="payOpeningBalance">Opening Balance</label>
                    </div>
                    <div class="form-check mb-0">
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
                <div class="row g-2 mb-1 align-items-start">
                    <div class="col-md-4">
                        <label for="globalPaymentAmount" class="form-label mb-1">Amount Received</label>
                        <input type="text" class="form-control" id="globalPaymentAmount" name="amount" placeholder="Max: Rs. 0.00">
                        <div id="calculatedAmountMultipleWrap" class="calculated-amount-display mt-1 small text-muted" style="display: none;">
                            <div id="calcEmptyHint" class="mb-0">
                                <strong>Next:</strong> Add payment method → <span class="text-muted">allocate to bills</span>
                            </div>
                            <div id="calcTotalRow" class="mt-1" style="display: none;">
                                <strong>Rs. <span id="calcTotal">0.00</span></strong>
                            </div>
                        </div>
                        <div id="amountError" class="text-danger small mt-1" style="display:none;"></div>
                    </div>
                    <div class="col-md-4">
                        <label for="paymentMethod" class="form-label mb-1">Payment Method</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
                            <option value="cash">Cash</option>
                            <option value="card">Credit Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="multiple">Multiple Methods</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="paidOn" class="form-label mb-1">Payment date</label>
                        <input class="form-control" type="date" name="payment_date" id="paidOn" value="<?php echo date('Y-m-d'); ?>">
                        <small class="text-muted d-block mt-1">YYYY-MM-DD</small>
                    </div>
                </div>

                <!-- Returns Section - Visible in Main Flow -->
                <div id="customerReturnsSection" class="mb-2" style="display: none;">
                    <div class="border rounded-3 p-2 bg-white">
                        <p class="small text-muted mb-1" id="returnsSectionIntro">
                            <strong>Optional:</strong> Select return and choose <em>Apply to invoice</em>.
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0" style="font-size:12px">
                                Returns (Rs. <span id="returnsToApplyToSales">0.00</span> available)
                                <a href="#" id="toggleReturnsTable" style="font-size:11px;margin-left:8px;color:#0d6efd">▼ show</a>
                            </h6>
                        </div>
                        <div id="returnsTableWrapper" style="display:none">
                            <div class="table-responsive returns-table-wrap">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="40"><input type="checkbox" id="selectAllReturns"></th>
                                            <th>Return #</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th title="How to use this credit">Use credit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customerReturnsTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-2">No pending returns</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="reallocateAllCreditsBtn">
                                <i class="fas fa-exchange-alt"></i> Change Allocation
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advance Credit Section - Similar to Returns -->
                <div id="customerAdvanceSection" class="mb-3" style="display: none;">
                    <div class="border rounded p-2 bg-white">
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
                <div id="advancedOptionsContainer" class="collapse" style="display: none; overflow: hidden;">
                    <!-- Auto Allocation Info -->
                    <div id="bothPaymentTypeInfo" class="alert alert-light border py-1 px-2 mb-2" style="display: none;">
                        <small class="text-muted">
                            <i class="fas fa-magic"></i>
                            <strong>Smart Allocation:</strong> Opening Balance first (Rs. <span id="obInfoAmount">0.00</span>), then bills automatically
                        </small>
                    </div>

                    <!-- Bill Selection Details -->
                    <div id="multiMethodContainer" class="mb-3">
                        <div class="border rounded-3 bg-white overflow-hidden">
                            <!-- Two Column Layout -->
                            <div class="row g-0">
                                <!-- Left: Bills & Returns -->
                                <div class="col-md-6 border-end p-2 bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 small">Outstanding Bills</h6>
                                        <input type="search" id="billSearchInput" class="form-control form-control-sm" placeholder="Invoice # or note…" style="max-width: 160px;" title="Filter by invoice number, note, or bill ID">
                                    </div>
                                    <div id="availableBillsList" class="bill-items-container" style="max-height: calc(100vh - 500px); min-height: 120px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                        <!-- Bills populated here -->
                                    </div>
                                </div>

                                <!-- Right: Payment Methods -->
                                <div class="col-md-6 p-2">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <h6 class="mb-0 small fw-semibold">Payment methods</h6>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addFlexiblePayment" title="Add cash, card, cheque, or bank — then allocate to bills">
                                            <i class="fas fa-plus"></i> Add payment method
                                        </button>
                                    </div>
                                    <div id="flexiblePaymentsList" class="payment-methods-container payment-methods-container--empty border rounded" style="max-height: calc(100vh - 470px); min-height: 100px; border: 1px solid #dee2e6 !important; border-radius: 4px;">
                                        <div id="flexiblePaymentsEmptyState" class="p-3 text-center text-muted small h-100 d-flex flex-column justify-content-center align-items-center">
                                            <i class="fas fa-wallet fa-2x mb-2 text-secondary opacity-75"></i>
                                            <div class="fw-semibold text-dark mb-1">Add how the customer pays</div>
                                            <div class="mb-3">Cash, card, or other — then allocate amounts to bills on the left.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Summary -->
                            <div class="border-top p-2 bg-white">
                                <div class="small text-uppercase text-muted fw-semibold mb-1" style="font-size:11px;letter-spacing:.04em;">Summary</div>
                                <div class="row g-1 align-items-stretch allocation-summary-grid">
                                    <div class="d-none"><span id="totalBillsCount">0</span></div>
                                    <div class="col-4">
                                        <div class="allocation-summary-metric h-100 text-center">
                                            <div class="text-muted small">Total due <span class="text-danger">(net)</span></div>
                                            <div class="fw-bold allocation-summary-value" id="summaryDueAmount">Rs. 0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="allocation-summary-metric h-100 text-center">
                                            <div class="text-muted small">Cash collected</div>
                                            <div class="fw-bold allocation-summary-value" id="totalPaymentAmount">Rs. 0.00</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="allocation-summary-metric h-100 text-center">
                                            <div class="text-muted small" id="balanceLabel">Balance due</div>
                                            <div class="fw-bold allocation-summary-value" id="balanceAmount">Rs. 0.00</div>
                                        </div>
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
                    <textarea id="notes" name="notes" class="form-control" rows="1" placeholder="Add any notes..." onclick="this.rows=3" style="resize:none;transition:all 0.2s"></textarea>
                </div>

                <!-- Submit Button (sticky) -->
                <div id="submitButtonSection" class="bulk-payment-submit-bar text-center py-3 px-2" style="display: none;">
                    <button type="button" id="submitBulkPayment" class="btn btn-primary btn-lg px-5 shadow">
                        <i class="fas fa-check"></i> Submit Payment
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
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
                        <label for="flexibleCustomerSelect" class="form-label">Select Customer</label>
                        <select id="flexibleCustomerSelect" class="form-control">
                            <option value="">Select Customer</option>
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
                                <h4 id="flexibleModalCardTotalDue">Rs. 0.00</h4>
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
                <h5 class="modal-title" id="receiptModalTitle">
                    <i class="fas fa-check-circle"></i> Payment Successful
                </h5>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="fas fa-receipt fa-3x text-success mb-3" id="receiptModalIcon"></i>
                    <h5 id="receiptModalSubtitle">Payment Reference Number</h5>
                </div>
                <div class="alert alert-info" id="receiptReferenceWrap" style="font-size: 18px; font-weight: bold; font-family: monospace;">
                    <span id="receiptReferenceNo">-</span>
                </div>
                <div class="mb-3" id="receiptCopyWrap">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyReferenceNumber()">
                        <i class="fas fa-copy"></i> Copy Reference Number
                    </button>
                </div>
                <div class="text-muted">
                    <small><strong id="receiptAmountLabel">Total Amount:</strong> Rs. <span id="receiptTotalAmount">0.00</span></small>
                </div>
                <p class="mt-3 text-muted small" id="receiptModalFootnote">Save this reference number for future payment tracking and verification.</p>
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
    function parseAmountValue(value) {
        if (value === null || value === undefined) return 0;
        const clean = String(value).replace(/,/g, '').replace(/[^\d.-]/g, '');
        const num = parseFloat(clean);
        return Number.isFinite(num) ? num : 0;
    }

    function formatAmountValue(value) {
        const num = Number(value || 0);
        return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatRs(value) {
        return 'Rs. ' + formatAmountValue(value);
    }

    // Define the customer loading function directly here for the separate page
    function loadCustomersForBulkPayment() {
        console.log('Loading customers for bulk payment (separate page version)...');
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        console.log('Current user authenticated:', $('meta[name="csrf-token"]').length > 0);

        $.ajax({
            url: '/customer-get-all',
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Customer response for bulk payment:', response);
                var customerSelect = $('#customerSelect');
                customerSelect.empty();
                customerSelect.append('<option value="" selected disabled>Select Customer</option>');

                if (response.status === 200 && response.message && response.message.length > 0) {
                    window.bulkPaymentShowRepDue = !!response.show_rep_invoice_due;
                    response.message.forEach(function(customer) {
                        // Skip walk-in customer (customer ID 1)
                        if (customer.id === 1) {
                            return;
                        }

                        // Calculate total due amount
                        var openingBalance = parseFloat(customer.opening_balance) || 0;
                        var saleDue = parseFloat(customer.total_sale_due) || 0;
                        var currentDue = parseFloat(customer.current_due) || 0;
                        var advanceCredit = parseFloat(customer.total_advance_credit) || 0;

                        console.log('Customer data received:', {
                            name: customer.first_name,
                            advance_credit: customer.total_advance_credit,
                            parsed_advance: advanceCredit
                        });

                        // Only show customers who have due amounts
                            if (currentDue > 0) {
                            var lastName = customer.last_name ? customer.last_name : '';
                            var fullName = customer.first_name + (lastName ? ' ' + lastName : '');
                            var mobileRaw = customer.mobile_no != null ? String(customer.mobile_no).trim() : '';
                            var mobileSeg = mobileRaw ? ' · ' + mobileRaw : '';

                            // Build clear display text (name + mobile for Select2 search) with ledger/account due prominent
                            var displayText = fullName + mobileSeg + ' [Account Due: Rs. ' + currentDue.toFixed(2) + ']';

                            // Show breakdown if available
                            if (openingBalance > 0 && saleDue > 0) {
                                displayText += ' (Opening: Rs. ' + openingBalance.toFixed(2) + ', Sales: Rs. ' + saleDue.toFixed(2) + ')';
                            } else if (openingBalance > 0) {
                                displayText += ' (Opening Balance)';
                            } else if (saleDue > 0) {
                                displayText += ' (Sales Due)';
                            }

                            var myInv = parseFloat(customer.my_invoice_due) || 0;
                            customerSelect.append(
                                '<option value="' + customer.id +
                                '" data-opening-balance="' + openingBalance +
                                '" data-sale-due="' + saleDue +
                                '" data-total-due="' + currentDue +
                                '" data-advance-credit="' + advanceCredit +
                                '" data-my-invoice-due="' + myInv +
                                '">' + displayText + '</option>'
                            );
                        }
                    });
                } else {
                    console.error("Failed to fetch customer data or no customers found.", response);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error loading customers:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });

                // Show user-friendly error message
                var errorMessage = 'Failed to load customers.';
                if (xhr.status === 401) {
                    errorMessage = 'Authentication required. Please refresh the page and login again.';
                } else if (xhr.status === 403) {
                    errorMessage = 'Permission denied to access customer data.';
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
                placeholder: "Select Customer",
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
            loadCustomersForBulkPayment();
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

        $(document).on('click', '#toggleReturnsTable', function(e) {
            e.preventDefault();
            const $w = $('#returnsTableWrapper');
            const isVisible = $w.is(':visible');
            $w.slideToggle(150);
            $(this).text(isVisible ? '▼ show' : '▲ hide');
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
                var maxAdvance = window.customerAdvanceCredit || 0;
                var totalDue = window.totalCustomerDue || 0;
                var suggestedAmount = Math.min(maxAdvance, totalDue);
                $('#advanceCreditAmountInput').val(suggestedAmount.toFixed(2));
                $('#advanceCreditAmountInput').attr('max', maxAdvance);
                updateNetCustomerDue();
            } else {
                $('#advanceCreditAmountSection').slideUp();
                $('#advanceCreditAmountInput').val('');
                updateNetCustomerDue();
            }
        });

        // Advance Credit Amount Input Handler
        $(document).on('input', '#advanceCreditAmountInput', function() {
            var maxAdvance = window.customerAdvanceCredit || 0;
            var inputAmount = parseFloat($(this).val()) || 0;

            if (inputAmount > maxAdvance) {
                $(this).val(maxAdvance.toFixed(2));
                toastr.warning('Amount cannot exceed available advance credit of Rs. ' + maxAdvance.toFixed(2));
            }

            updateNetCustomerDue();
        });

        // Progressive Disclosure: Customize Payment Link - Toggle functionality
        $(document).on('click', '#customizePaymentLink', function(e) {
            e.preventDefault();
            var $section = $('#paymentTypeSection');
            if ($section.is(':visible')) {
                $section.slideUp();
                $(this).text('Payment options');
            } else {
                $section.slideDown();
                $(this).text('Hide payment options');
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

        $(document).on('click', '#whyAmountLink', function(e) {
            e.preventDefault();
            var $h = $('#ledgerVsSalesHint');
            if (!$h.data('why-filled')) {
                $h.html(
                    '<i class="fas fa-info-circle me-1"></i>' +
                    'Account Due (ledger) and Sales Due (invoices) can differ. The sales invoice due is shown above.'
                );
                $h.data('why-filled', true);
            }
            $h.toggle();
        });

        // Handle Payment Type changes to update payment method dropdown and helper text
        $('input[name="paymentType"]').on('change', function() {
            const selectedType = $(this).val();
            const $paymentMethod = $('#paymentMethod');
            const $helpText = $('#paymentTypeHelp');

            // Update helper text based on selection
            const helpTexts = {
                'sale_dues': '<i class="fas fa-info-circle"></i> Pay sale bills (invoices) for this customer',
                'opening_balance': '<i class="fas fa-info-circle"></i> Pay only the opening balance amount',
                'both': '<i class="fas fa-info-circle"></i> Pay both opening balance and sale bills together'
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
                var customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
                $('#obInfoAmount').text(customerOpeningBalance.toFixed(2));
                $('#bothPaymentTypeInfo').show();

                // Show hints for both payment type
                $('.both-payment-hint').show();
            } else {
                // Pay Sale Dues: Select Multiple Methods by default and disable other options
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
            $('#workflowStepsBar').hide();
            window.lastReturnSelectionSignature = null;
            window.lastLoadedSalesCustomerId = null;
            return;
        }

        console.log('Selected customer ID:', customerId);

        // Show customer summary and payment method section
        $('#customerSummarySection').show();
        $('#paymentMethodSection').show();
        $('#notesSection').show();
        $('#submitButtonSection').show();
        $('#workflowStepsBar').show();
        $('#customerBalanceDetails').prop('open', true);

        // Get customer data from the selected option
        var customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
        var saleDue = parseFloat(selectedOption.data('sale-due')) || 0;
        var totalDue = parseFloat(selectedOption.data('total-due')) || 0;
        var advanceCredit = parseFloat(selectedOption.data('advance-credit')) || 0;

        // DEBUG: Log all data attributes from selected option
        console.log('Selected option attributes:', selectedOption[0].attributes);
        console.log('Data attributes parsed:', {
            'data-opening-balance': selectedOption.data('opening-balance'),
            'data-sale-due': selectedOption.data('sale-due'),
            'data-total-due': selectedOption.data('total-due'),
            'data-advance-credit': selectedOption.data('advance-credit')
        });

        // Trigger payment type change to set proper payment method options
        $('input[name="paymentType"]:checked').trigger('change');

        console.log('Customer balances:', {
            openingBalance: customerOpeningBalance,
            saleDue: saleDue,
            totalDue: totalDue,
            advanceCredit: advanceCredit
        });

        // Update balance displays (text-based, no cards)
        $('#openingBalance').text(customerOpeningBalance.toFixed(2));
        $('#totalDueAmount').text(saleDue.toFixed(2));

        // No auto message here; optional short note only via (why?) — reset when customer changes.
        $('#ledgerVsSalesHint').text('').hide().removeData('why-filled');
        window.lastReturnSelectionSignature = null;

        // Store values globally
        window.originalOpeningBalance = customerOpeningBalance;
        window.saleDueAmount = saleDue;
        window.totalCustomerDue = totalDue;
        window.customerAdvanceCredit = advanceCredit;

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

        // Amount to Pay (cash): derived in updateNetCustomerDue from gross sales + OB − returns (do not use ledger net alone — avoids double-deducting return).
        updateNetCustomerDue();

        var myInvDueBulk = parseFloat(selectedOption.data('my-invoice-due')) || 0;
        if (window.bulkPaymentShowRepDue) {
            $('#bulkRepMyInvoicesAmount').text(myInvDueBulk.toFixed(2));
        }

        // Reset and clear previous validation errors
        $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
        $('#globalPaymentAmount').val('');

        // Load customer bills via paginated endpoint path only (avoid duplicate /sales call)
        loadCustomerReturns(customerId);
        setTimeout(updateWorkflowProgress, 0);
    });

    // Global variables for returns handling
    var availableCustomerReturns = [];
    var selectedReturns = [];

    // Function to load customer returns
    function loadCustomerReturns(customerId) {
        console.log('Loading returns for customer:', customerId);

        $.ajax({
            url: '/customer-returns/' + customerId,
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name=\"csrf-token\"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                console.log('Returns response:', response);

                if (response.returns && response.returns.length > 0) {
                    availableCustomerReturns = response.returns.filter(ret => {
                        return parseFloat(ret.total_due) > 0 && ret.payment_status !== 'Paid';
                    });

                    console.log('Unpaid returns found:', availableCustomerReturns.length);

                    if (availableCustomerReturns.length > 0) {
                        populateReturnsTable();
                        $('#returnsTableWrapper').show();
                        $('#toggleReturnsTable').text('▲ hide');

                        // Update return credits in summary (text-based, no cards)
                        var totalReturnCredits = availableCustomerReturns.reduce((sum, ret) => sum + parseFloat(ret.total_due), 0);
                        $('#totalReturnCredits').text(totalReturnCredits.toFixed(2));
                        $('#returnsToApplyToSales').text(totalReturnCredits.toFixed(2));
                        $('#returnCountNumber').text(availableCustomerReturns.length);
                        $('#returnCount').show();

                        // Always show returns section when returns are available
                        $('#customerReturnsSection').show();

                        // Update Net Customer Due
                        updateNetCustomerDue();
                    } else {
                        hideReturnsUI();
                    }
                } else {
                    console.log('No returns found for customer');
                    hideReturnsUI();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading customer returns:', error);
                hideReturnsUI();
                if (xhr.status === 404) {
                    console.log('Returns endpoint not found - feature may not be implemented yet');
                }
            }
        });
    }

    // Helper function to hide returns UI
    function hideReturnsUI() {
        $('#customerReturnsSection').hide();
        $('#totalReturnCredits').text('0.00');
        $('#returnCount').hide();
        window.lastReturnSelectionSignature = null;
        updateNetCustomerDue();
    }

    // Populate returns table
    function populateReturnsTable() {
        var tableBody = $('#customerReturnsTableBody');
        tableBody.empty();

        availableCustomerReturns.forEach(function(returnBill) {
            var totalDue = parseFloat(returnBill.total_due) || 0;
            var returnDate = returnBill.return_date ? new Date(returnBill.return_date).toLocaleDateString('en-GB') : 'N/A';

            var row = '<tr class="return-row" data-return-id="' + returnBill.id + '" style="cursor: pointer;">' +
                '<td class="return-checkbox-cell" onclick="event.stopPropagation();">' +
                '<input type="checkbox" class="return-checkbox" data-return-id="' + returnBill.id + '" data-amount="' + totalDue + '">' +
                '</td>' +
                '<td><strong>' + returnBill.invoice_number + '</strong></td>' +
                '<td>' + returnDate + '</td>' +
                '<td class="text-danger fw-bold">Rs. ' + totalDue.toFixed(2) + '</td>' +
                '<td onclick="event.stopPropagation();">' +
                '<select class="form-select form-select-sm return-action" data-return-id="' + returnBill.id + '" style="font-size: 0.8rem;">' +
                '<option value="apply_to_sales" selected title="Reduces your payable amount">Apply to invoice</option>' +
                '<option value="cash_refund" title="Refund cash to customer">Cash refund</option>' +
                '</select>' +
                '<span class="text-muted small ms-2 return-applied-to-hint" id="returnAppliedTo_' + returnBill.id + '" style="display: none;"></span>' +
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
        updateSelectedReturns();
    });

    // Common function to show adjust credit dialog (eliminates duplication)
    function showAdjustCreditDialog(saleId) {
        const sale = availableCustomerSales.find(s => s.id == saleId);
        if (!sale) return;

        const currentCredit = window.billReturnCreditAllocations[saleId] || 0;

        // Get total return credit available
        let totalReturnCredit = 0;
        $('.return-checkbox:checked').each(function() {
            const action = $('.return-action[data-return-id="' + $(this).data('return-id') + '"]').val();
            if (action === 'apply_to_sales') {
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
                    <p><strong>Bill:</strong> ${sale.invoice_no}</p>
                    <p><strong>Bill Due:</strong> Rs.${sale.total_due.toFixed(2)}</p>
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
                updateExistingBillAllocationsForReturnCredits();
                updateReturnAppliedToHintsFromAllocations();
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
            if (action === 'apply_to_sales') {
                toastr.info('Return credit will reduce your payable amount on invoices.', 'Apply to invoice', { timeOut: 2500 });
            } else if (action === 'cash_refund') {
                toastr.info('Cash refund will be processed for this return', 'Action Changed', {timeOut: 2000});
            }
        }

        updateSelectedReturns();
    });

    // Quick remove return credit from a bill
    $(document).on('click', '.quick-remove-credit', function(e) {
        e.stopPropagation();
        const saleId = $(this).data('sale-id');
        const currentCredit = window.billReturnCreditAllocations[saleId] || 0;

        if (currentCredit > 0) {
            delete window.billReturnCreditAllocations[saleId];
            populateFlexibleBillsList();
            updateExistingBillAllocationsForReturnCredits();
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
            if (action === 'apply_to_sales') {
                totalReturnCredit += parseFloat($(this).data('amount'));
            }
        });

        if (totalReturnCredit === 0) {
            toastr.warning('No return credits selected for Apply to invoice', 'No credits');
            return;
        }

        // Build bills table
        let billsHTML = '<div style="max-height: 400px; overflow-y: auto;"><table class="table table-sm table-hover"><thead class="sticky-top bg-light"><tr><th>Bill #</th><th>Due</th><th>Credit</th><th>Action</th></tr></thead><tbody>';

        availableCustomerSales.forEach(sale => {
            const currentCredit = window.billReturnCreditAllocations[sale.id] || 0;
            billsHTML += `
                <tr>
                    <td><small>${sale.invoice_no}</small></td>
                    <td><small>Rs.${sale.total_due.toFixed(2)}</small></td>
                    <td><small class="${currentCredit > 0 ? 'text-info fw-bold' : 'text-muted'}">Rs.${currentCredit.toFixed(2)}</small></td>
                    <td>
                        <button class="btn btn-xs btn-primary realloc-set-credit" data-sale-id="${sale.id}" data-invoice="${sale.invoice_no}" data-due="${sale.total_due}">
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
                    updateReturnAppliedToHintsFromAllocations();
                    populateFlexibleBillsList();
                    updateExistingBillAllocationsForReturnCredits();
                    toastr.success('All credits cleared!', 'Success');
                    Swal.close();
                    setTimeout(() => $('#reallocateAllCreditsBtn').click(), 100);
                });

                $('#autoFifoAllocate').off('click').on('click', function() {
                    autoAllocateReturnCreditsToSales(totalReturnCredit);
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
                    updateExistingBillAllocationsForReturnCredits();
                    toastr.success('Credit updated!', 'Success');
                    // Reopen main modal
                    $('#reallocateAllCreditsBtn').click();
                }
            });
        });
    });

    // Net sales outstanding after return credits allocated to bills (matches bill list "Left" totals)
    function getNetOutstandingSalesDueFromAllocations() {
        if (!availableCustomerSales || !availableCustomerSales.length) {
            return null;
        }
        const alloc = window.billReturnCreditAllocations || {};
        return availableCustomerSales.reduce((sum, sale) => {
            const ret = parseFloat(alloc[sale.id]) || 0;
            return sum + Math.max(0, parseFloat(sale.total_due || 0) - ret);
        }, 0);
    }

    // Update selected returns and totals
    function updateSelectedReturns() {
        const currentSelectionSignature = $('.return-checkbox:checked').map(function() {
            const returnId = $(this).data('return-id');
            const action = $('.return-action[data-return-id="' + returnId + '"]').val() || '';
            const amount = parseFloat($(this).data('amount')) || 0;
            return returnId + ':' + action + ':' + amount.toFixed(2);
        }).get().sort().join('|');

        if (window.lastReturnSelectionSignature === currentSelectionSignature) {
            return;
        }
        window.lastReturnSelectionSignature = currentSelectionSignature;

        selectedReturns = [];
        var totalToApply = 0;
        var totalCashRefund = 0;

        $('.return-checkbox:checked').each(function() {
            var returnId = $(this).data('return-id');
            var amount = parseFloat($(this).data('amount'));
            var action = $('.return-action[data-return-id="' + returnId + '"]').val();

            selectedReturns.push({
                return_id: returnId,
                amount: amount,
                action: action
            });

            if (action === 'apply_to_sales') {
                totalToApply += amount;
            } else if (action === 'cash_refund') {
                totalCashRefund += amount;
            }
        });

        // Update footer totals
        $('#selectedReturnsCount').text(selectedReturns.length + ' selected');
        $('#selectedReturnsTotal').text('Rs. ' + (totalToApply + totalCashRefund).toFixed(2));
        $('#returnsToApplyToSales').text(totalToApply.toFixed(2));
        $('#returnsCashRefund').text('Rs. ' + totalCashRefund.toFixed(2));

        if (totalToApply > 0) {
            $('#returnCreditAppliedSummary').html(
                'Return credit used: <strong class="text-dark">Rs. ' + totalToApply.toFixed(2) + '</strong>'
            ).show();
            $('#returnCount').hide();
        } else {
            $('#returnCreditAppliedSummary').empty().hide();
            if (availableCustomerReturns && availableCustomerReturns.length > 0) {
                $('#returnCount').show();
            }
        }

        // Auto-allocate return credits to bills in flexible payment system (must run before net / summary)
        if (totalToApply > 0 && availableCustomerSales && availableCustomerSales.length > 0) {
            autoAllocateReturnCreditsToSales(totalToApply);
        } else {
            // Clear return credit allocations if no returns selected for "apply to sales"
            if (!window.billReturnCreditAllocations) {
                window.billReturnCreditAllocations = {};
            }
            window.billReturnCreditAllocations = {};
            window.lastReturnSelectionSignature = currentSelectionSignature;

            updateReturnAppliedToHintsFromAllocations();

            // Refresh bills list to remove credit badges
            populateFlexibleBillsList();

            // Update existing bill allocations
            updateExistingBillAllocationsForReturnCredits();
        }

        // After allocations match checkboxes / FIFO, sync Amount to Pay + "Sales unpaid" line
        updateNetCustomerDue();

        console.log('Selected returns updated:', selectedReturns);
        updateSummaryTotals();
    }

    // Map combined FIFO return-credit allocation to per-return "Applied to" labels (display only)
    function updateReturnAppliedToHintsFromAllocations() {
        $('[id^="returnAppliedTo_"]').text('').hide();

        if (!window.billReturnCreditAllocations || Object.keys(window.billReturnCreditAllocations).length === 0) {
            return;
        }

        const sortedSales = [...availableCustomerSales].sort((a, b) => {
            return new Date(a.sales_date) - new Date(b.sales_date);
        });

        const remainingAlloc = {};
        Object.keys(window.billReturnCreditAllocations).forEach(k => {
            remainingAlloc[String(k)] = parseFloat(window.billReturnCreditAllocations[k]) || 0;
        });

        const returnsOrdered = [];
        $('.return-checkbox:checked').each(function() {
            const rid = $(this).data('return-id');
            if ($('.return-action[data-return-id="' + rid + '"]').val() === 'apply_to_sales') {
                returnsOrdered.push({
                    id: rid,
                    amount: parseFloat($(this).data('amount')) || 0
                });
            }
        });

        returnsOrdered.sort((a, b) => {
            const ra = availableCustomerReturns.find(r => String(r.id) === String(a.id));
            const rb = availableCustomerReturns.find(r => String(r.id) === String(b.id));
            if (!ra || !rb) return 0;
            return new Date(ra.return_date) - new Date(rb.return_date);
        });

        returnsOrdered.forEach(ret => {
            let credit = ret.amount;
            const applied = [];
            for (const sale of sortedSales) {
                if (credit <= 0.001) break;
                const bid = String(sale.id);
                const room = remainingAlloc[bid] || 0;
                if (room <= 0.001) continue;
                const take = Math.min(credit, room);
                if (take > 0.001) {
                    applied.push(sale.invoice_no);
                }
                remainingAlloc[bid] = room - take;
                credit -= take;
            }
            const $span = $('#returnAppliedTo_' + ret.id);
            if (applied.length) {
                $span.text('→ Applied Rs. ' + ret.amount.toFixed(2) + ' to: ' + [...new Set(applied)].join(', ')).show();
            }
        });

        $('.return-row').each(function() {
            const rid = $(this).data('return-id');
            const checked = $(this).find('.return-checkbox').prop('checked');
            const action = $('.return-action[data-return-id="' + rid + '"]').val();
            if (!checked || action !== 'apply_to_sales') {
                $('#returnAppliedTo_' + rid).text('').hide();
            }
        });
    }

    // Auto-allocate return credits to sales bills (FIFO - oldest first)
    function autoAllocateReturnCreditsToSales(returnCreditAmount) {
        console.log('Auto-allocating return credits to sales:', returnCreditAmount);

        // Reset any previous return credit allocations
        if (!window.billReturnCreditAllocations) {
            window.billReturnCreditAllocations = {};
        }
        window.billReturnCreditAllocations = {};

        let remainingCredit = returnCreditAmount;

        // Sort sales by date (oldest first) for FIFO allocation
        let sortedSales = [...availableCustomerSales].sort((a, b) => {
            return new Date(a.sales_date) - new Date(b.sales_date);
        });

        // Allocate credit to bills
        for (let sale of sortedSales) {
            if (remainingCredit <= 0) break;

            const saleDue = parseFloat(sale.total_due) || 0;
            const allocatedAmount = Math.min(remainingCredit, saleDue);

            if (allocatedAmount > 0) {
                window.billReturnCreditAllocations[sale.id] = allocatedAmount;
                remainingCredit -= allocatedAmount;

                console.log(`Allocated Rs.${allocatedAmount.toFixed(2)} from returns to Sale #${sale.id}`);
            }
        }

        updateReturnAppliedToHintsFromAllocations();

        // Update the bills list to show allocations
        populateFlexibleBillsList();

        // Update existing bill allocations in payment methods
        updateExistingBillAllocationsForReturnCredits();

        // Show info message (single compact line — avoid large multi-line toast)
        if (returnCreditAmount > 0) {
            const allocated = returnCreditAmount - remainingCredit;
            const msg =
                `Rs.${allocated.toFixed(2)} return credit FIFO-applied` +
                (remainingCredit > 0 ? ` (Rs.${remainingCredit.toFixed(2)} unused)` : '') +
                `. Use Reallocate or bill rows to change.`;
            toastr.info(msg, '', {timeOut: 4000, progressBar: true, closeButton: true});
        }
    }

    // Keep bill allocation UI normalized: one row per bill per payment method.
    function normalizeAllBillAllocationRows() {
        $('.payment-method-item').each(function() {
            const $paymentContainer = $(this);
            const rowsByBill = {};

            $paymentContainer.find('.bill-allocation-row').each(function() {
                const $row = $(this);
                const billId = $row.find('.bill-select').val();
                const amount = parseAmountValue($row.find('.allocation-amount').val());

                // Remove only truly empty rows. Keep selected rows even if amount is not typed yet.
                if (!billId) {
                    $row.remove();
                    return;
                }

                if (amount <= 0.001) {
                    $row.find('.allocation-amount').data('prev-amount', 0);
                    return;
                }

                if (!rowsByBill[billId]) rowsByBill[billId] = [];
                rowsByBill[billId].push($row);
            });

            Object.keys(rowsByBill).forEach(billId => {
                const rows = rowsByBill[billId];
                if (!rows.length) return;

                const bill = availableCustomerSales.find(s => String(s.id) === String(billId));
                const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
                const billDue = bill ? parseAmountValue(bill.total_due) : 0;
                const maxForBill = Math.max(0, billDue - returnCreditApplied);

                let mergedAmount = rows.reduce((sum, $row) => {
                    return sum + parseAmountValue($row.find('.allocation-amount').val());
                }, 0);
                mergedAmount = Math.max(0, Math.min(mergedAmount, maxForBill));

                const $keep = rows[0];
                const $keepInput = $keep.find('.allocation-amount');
                const $keepHint = $keep.find('.bill-amount-hint');
                const $keepSelect = $keep.find('.bill-select');

                $keepInput.val(formatAmountValue(mergedAmount));
                $keepInput.data('prev-amount', mergedAmount);

                if (bill) {
                    const selectedText = `${bill.invoice_no} · Pay now Rs.${formatAmountValue(maxForBill)}`;
                    $keepSelect.find('option:selected').text(selectedText);
                }

                const after = Math.max(0, maxForBill - mergedAmount);
                if (after <= 0.01) {
                    $keepHint.text(returnCreditApplied > 0 ? 'Settled (credit applied)' : 'Settled').removeClass('text-muted').addClass('text-success').show();
                } else {
                    $keepHint.text(`Pay now after this: Rs. ${formatAmountValue(after)}`).removeClass('text-success').addClass('text-muted').show();
                }

                for (let i = 1; i < rows.length; i++) {
                    rows[i].remove();
                }
            });

            const paymentId = $paymentContainer.data('payment-id');
            if (paymentId) {
                updatePaymentMethodTotal(paymentId);
            }
        });

        recalcBillPaymentAllocationsFromUI();
    }

    // Update existing bill allocations when return credits are applied
    function updateExistingBillAllocationsForReturnCredits() {
        // Loop through all bill allocations in payment methods
        $('.bill-allocation-row').each(function() {
            const $row = $(this);
            const $billSelect = $row.find('.bill-select');
            const $amountInput = $row.find('.allocation-amount');
            const billId = $billSelect.val();

            if (!billId) return; // Skip if no bill selected

            // Find the bill data
            const bill = availableCustomerSales.find(s => s.id == billId);
            if (!bill) return;

            // Get current allocations
            const currentAllocationAmount = parseAmountValue($amountInput.val());
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;

            // Get the previous allocation from tracking to calculate how much was allocated before
            const prevAmount = $amountInput.data('prev-amount') || 0;

            // Calculate what was allocated to OTHER payment methods for this bill
            const otherPaymentAllocations = (billPaymentAllocations[billId] || 0) - prevAmount;

            // Calculate bill's remaining due after return credits and other allocations
            const billRemainingDue = parseAmountValue(bill.total_due) - returnCreditApplied - otherPaymentAllocations;

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
                $amountInput.val(formatAmountValue(newAmount));

                // Update global tracking
                billPaymentAllocations[billId] = otherPaymentAllocations + newAmount;
                $amountInput.data('prev-amount', newAmount);

                // Update hint
                const $hint = $row.find('.bill-amount-hint');
                const remainingAfterPayment = billRemainingDue - newAmount;

                if (returnCreditApplied > 0) {
                    $hint.text(`Pay now: Rs. ${formatAmountValue(billRemainingDue)}`).removeClass('text-success').addClass('text-muted');
                } else if (remainingAfterPayment <= 0.01) {
                    $hint.text('Settled').removeClass('text-muted').addClass('text-success');
                } else {
                    $hint.text(`Pay now after this: Rs. ${formatAmountValue(remainingAfterPayment)}`).removeClass('text-success').addClass('text-muted');
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

        normalizeAllBillAllocationRows();

        updateNetCustomerDue();
        updateSummaryTotals();
    }

    // Update net customer due (after return credits)
    function updateNetCustomerDue() {
        var openingBalance = window.originalOpeningBalance || 0;
        var saleDue = window.saleDueAmount || 0;
        var totalDue = window.totalCustomerDue || 0; // Use actual total from backend (ledger)

        // Get return credits to apply to sales
        var returnsToApply = 0;
        $('.return-checkbox:checked').each(function() {
            var returnId = $(this).data('return-id');
            var action = $('.return-action[data-return-id="' + returnId + '"]').val();
            if (action === 'apply_to_sales') {
                returnsToApply += parseFloat($(this).data('amount')) || 0;
            }
        });

        // Get advance credit to apply
        var advanceCreditToApply = 0;
        if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
            advanceCreditToApply = parseFloat($('#advanceCreditAmountInput').val()) || 0;
        }

        // "Sales unpaid" row: show net invoice outstanding after allocated return credit (aligns with ledger / bill list)
        var netFromBills = getNetOutstandingSalesDueFromAllocations();
        var displaySalesUnpaid = netFromBills !== null ? netFromBills : Math.max(0, saleDue - returnsToApply);
        $('#totalDueAmount').text(formatAmountValue(displaySalesUnpaid));
        if (saleDue > displaySalesUnpaid + 0.02) {
            $('#salesDueGrossAmount').text(formatAmountValue(saleDue));
        }

        // Cash to pay: gross sales invoices + opening balance owed − return credits you select − advance applied.
        // Do NOT use totalCustomerDue (ledger current_due) here — it often already nets return credit; subtracting returnsToApply again double-deducts.
        var netDue = openingBalance + saleDue - returnsToApply - advanceCreditToApply;
        if (netDue < 0) netDue = 0;

        $('#netCustomerDue').text(formatRs(netDue));

        // Keep the summary compact: don't show the extra breakdown lines by default.
        $('#returnCreditBreakdownLine').empty().hide();
        $('#totalSettledBreakdownLine').empty().hide();

        // Store for later use
        window.netCustomerDue = netDue;

        console.log('Net customer due updated:', {
            openingBalance: openingBalance,
            saleDue: saleDue,
            totalDueLedger: totalDue,
            returnsToApply: returnsToApply,
            advanceCreditToApply: advanceCreditToApply,
            netDue: netDue
        });

        updateReturnApplyHint();
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
        var customerOpeningBalance = window.originalOpeningBalance || 0;
        var saleDueAmount = window.saleDueAmount || 0;
        var totalDueAmount = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;

        if (paymentType === 'opening_balance') {
            $('#globalPaymentAmount').attr('max', customerOpeningBalance);
            $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + customerOpeningBalance.toFixed(2));
        } else if (paymentType === 'sale_dues') {
            $('#globalPaymentAmount').attr('max', saleDueAmount);
            $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + saleDueAmount.toFixed(2));
        } else if (paymentType === 'both') {
            $('#globalPaymentAmount').attr('max', totalDueAmount);
            $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));
        }

        // Reset and re-apply global amount if any
        var globalAmount = parseAmountValue($('#globalPaymentAmount').val());
        if (globalAmount > 0) {
            $('#globalPaymentAmount').trigger('input');
        }
    });

    // Handle global payment amount input - AUTO-APPLY to sales
    $(document).on('input', '#globalPaymentAmount', function() {
        var globalAmount = parseAmountValue($(this).val());
        var customerOpeningBalance = window.originalOpeningBalance || 0;
        var remainingAmount = globalAmount;
        var paymentType = $('input[name="paymentType"]:checked').val();

        console.log('Global amount changed:', globalAmount, 'Payment type:', paymentType);

        // Validate global amount based on payment type
        var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;
        var maxAmount = 0;
        if (paymentType === 'opening_balance') {
            maxAmount = customerOpeningBalance;
        } else if (paymentType === 'sale_dues') {
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
            let newOpeningBalance = Math.max(0, customerOpeningBalance - remainingAmount);
            $('#openingBalance').text('Rs. ' + newOpeningBalance.toFixed(2));

            // Clear all sales payment inputs
            $('.reference-amount').val(0);

        } else if (paymentType === 'sale_dues') {
            // Only apply to sales dues in order
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
            $('#openingBalance').text('Rs. ' + customerOpeningBalance.toFixed(2));

        } else if (paymentType === 'both') {
            // First deduct from opening balance
            let newOpeningBalance = customerOpeningBalance;
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

    // Amount formatting UX: edit plain number, display with separators
    $(document).on('focus', '#globalPaymentAmount, .payment-total-amount, .allocation-amount', function() {
        const raw = parseAmountValue($(this).val());
        if ($(this).val() !== '') {
            $(this).val(raw ? String(raw) : '');
        }
    });

    $(document).on('blur', '#globalPaymentAmount, .payment-total-amount, .allocation-amount', function() {
        const val = $(this).val();
        if (val === '') return;
        const num = parseAmountValue(val);
        $(this).val(formatAmountValue(num));
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

        var globalPaymentAmount = parseAmountValue($('#globalPaymentAmount').val());
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
        var customerOpeningBalance = parseFloat($('#customerSelect').find(':selected').data('opening-balance')) || 0;
        var totalDueAmount = parseFloat($('#totalDueAmount').text().replace(/[^\d.-]/g, '')) || 0;
        var totalCustomerDue = parseFloat($('#totalCustomerDue').text().replace('Rs. ', '')) || 0;

        console.log('Balance details:', {
            customerOpeningBalance: customerOpeningBalance,
            totalDueAmount: totalDueAmount, // This is sale dues only
            totalCustomerDue: totalCustomerDue // This is total customer due (sales + opening balance)
        });

        if (paymentType === 'opening_balance') {
            maxAmount = customerOpeningBalance;
        } else if (paymentType === 'sale_dues') {
            // Allow payment up to total customer due even when "sale_dues" is selected
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
                saleDues: totalDueAmount,
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

        // For sale dues and both, collect individual sale payments
        var salePayments = [];
        if (paymentType === 'sale_dues' || paymentType === 'both') {
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
            entity_type: 'customer',
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
                $('#openingBalance').text('Rs. 0.00');
                $('#totalSalesAmount').text('Rs. 0.00');
                $('#totalPaidAmount').text('Rs. 0.00');
                $('#totalDueAmount').text('0.00');
                $('#totalCustomerDue').text('Rs. 0.00');
                $('#individualPaymentTotal').text('Rs. 0.00');
                $('#globalPaymentAmount').val('');

                // Reload customers to refresh due amounts
                setTimeout(function() {
                    loadCustomersForBulkPayment();
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
            $('#globalPaymentAmount').addClass('d-none').prop('disabled', true).val('');
            $('label[for="globalPaymentAmount"]').addClass('d-none');
            $('#calculatedAmountMultipleWrap').removeClass('d-none').show();
            syncPaymentMethodsEmptyState();
            updateCalculatedAmountDisplay();
            $('#advancedOptionsContainer').show();
            $('#showAdvancedOptions, #hideAdvancedOptions').first()
                .html('<i class="fas fa-chevron-up"></i> Hide outstanding bills & payment allocation')
                .attr('id', 'hideAdvancedOptions');
            const customerId = $('#customerSelect').val();
            if (customerId) {
                const hasLoadedForSameCustomer = window.lastLoadedSalesCustomerId == customerId;
                const hasSalesCache = Array.isArray(availableCustomerSales) && availableCustomerSales.length > 0;
                if (!hasLoadedForSameCustomer || !hasSalesCache) {
                    loadCustomerSalesForMultiMethod(customerId);
                }
            } else {
                $('#billsPaymentTableBody').html('<tr><td colspan="6" class="text-center text-muted">Please select a customer first</td></tr>');
            }
        } else {
            modeIndicator.text('Single Mode').removeClass('bg-success').addClass('bg-info');
            $('#globalPaymentAmount').removeClass('d-none').prop('disabled', false).attr('placeholder', '0.00');
            $('label[for="globalPaymentAmount"]').removeClass('d-none');
            $('#calculatedAmountMultipleWrap').addClass('d-none').hide();

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
    let availableCustomerSales = [];

    function addNewMethodGroup() {
        const customerId = $('#customerSelect').val();
        if (!customerId) {
            toastr.warning('Please select a customer first');
            return;
        }

        // Check if bills are available
        if (!availableCustomerSales || availableCustomerSales.length === 0) {
            // Only load bills if we haven't tried loading them already
            if (!window.isLoadingCustomerSales) {
                window.isLoadingCustomerSales = true;
                toastr.info('Loading customer bills...');
                loadCustomerSalesForMultiMethod(customerId);
            } else {
                toastr.warning('No outstanding bills available for this customer');
            }
            return;
        }

        const groupIndex = methodGroupCounter++;
        console.log('Creating group with', availableCustomerSales.length, 'available sales');

        const salesOptions = availableCustomerSales.map(sale =>
            `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${sale.invoice_no}">
                ${sale.invoice_no} - Due: Rs.${parseFloat(sale.total_due).toFixed(2)}
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

                // 3. Clear all payment method cards — restore empty-state panel
                $('#flexiblePaymentsList').html(FLEXIBLE_PAYMENTS_EMPTY_HTML);
                flexiblePaymentCounter = 0;
                syncPaymentMethodsEmptyState();

                // 4. Clear simple table
                $('#billsPaymentTableBody').empty();

                // 5. Clear available sales
                availableCustomerSales = [];
                window.lastLoadedSalesCustomerId = null;

                // 6. Clear selected returns
                selectedReturns = [];
                $('.return-checkbox').prop('checked', false);

                // 7. Reset summary
                updateSummaryTotals();

                // 8. Load new customer bills
                console.log('Customer changed - all old data cleared');
            }
        });
    });

    // Load customer sales for flexible many-to-many system
    function loadCustomerSalesForMultiMethod(customerId) {
        if (!customerId) {
            return;
        }

        // Keep only the latest customer-sales request alive to prevent duplicate same-URL calls.
        if (window.salesPaginatedRequest && window.salesPaginatedRequest.readyState !== 4) {
            window.salesPaginatedRequest.abort();
        }

        window.isLoadingCustomerSales = true;
        console.log('Loading bills for flexible many-to-many system:', customerId);

        window.salesPaginatedRequest = $.ajax({
            url: '/sales/paginated',
            method: 'GET',
            data: {
                customer_id: customerId,
                length: 100
            },
            success: function(response) {
                if (response.data) {
                    // Filter for outstanding bills
                    // Use total_due only: payment_status can be stale (e.g. "Paid" while total_due > 0 after
                    // return credit / cheque bounce flows), which would hide bills if we required Due|Partial.
                    availableCustomerSales = response.data.filter(sale => {
                        const due = parseFloat(sale.total_due) || 0;
                        return due > 0.005;
                    });

                    console.log('Outstanding bills for flexible UI:', availableCustomerSales.length);

                    // Populate the bills list (UI will show appropriate message if empty)
                    populateFlexibleBillsList();

                    if ($('.return-checkbox:checked').length > 0) {
                        updateSelectedReturns();
                    } else {
                        updateNetCustomerDue();
                        updateSummaryTotals();
                    }

                    window.lastLoadedSalesCustomerId = String(customerId);
                    window.isLoadingCustomerSales = false;
                    window.salesPaginatedRequest = null;
                }
            },
            error: function(xhr, status, error) {
                if (status === 'abort') {
                    return;
                }
                console.error('Failed to load customer sales:', error);
                toastr.error('Failed to load customer sales: ' + error);
                window.lastLoadedSalesCustomerId = null;
                window.isLoadingCustomerSales = false;
                window.salesPaginatedRequest = null;
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
        if (typeof availableCustomerSales === 'undefined') availableCustomerSales = [];

        console.log('Flexible payment system initialized');
    }

    // Helper function to escape HTML special characters
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /** HTML for empty payment-methods panel (restored after customer change / clear) */
    var FLEXIBLE_PAYMENTS_EMPTY_HTML = `
        <div id="flexiblePaymentsEmptyState" class="p-3 text-center text-muted small h-100 d-flex flex-column justify-content-center align-items-center">
            <i class="fas fa-wallet fa-2x mb-2 text-secondary opacity-75"></i>
            <div class="fw-semibold text-dark mb-1" id="emptyPaymentTitle">Add payment method</div>
            <div class="mb-3" id="emptyPaymentHint">Cash, card, or other — then allocate to bills on the left.</div>
        </div>`;

    function syncPaymentMethodsEmptyState() {
        const $list = $('#flexiblePaymentsList');
        if (!$list.length) return;
        const count = $list.find('.payment-method-item').length;
        let $empty = $('#flexiblePaymentsEmptyState');
        if (count === 0) {
            if (!$empty.length) {
                $list.html(FLEXIBLE_PAYMENTS_EMPTY_HTML);
                $empty = $('#flexiblePaymentsEmptyState');
            }
            $empty.removeClass('d-none').addClass('d-flex');
            $list.addClass('payment-methods-container--empty');
        } else {
            $empty.removeClass('d-flex').addClass('d-none');
            $list.removeClass('payment-methods-container--empty');
        }
    }

    function updatePaymentMethodHints(cashDueAmount) {
        const due = Math.max(0, parseAmountValue(cashDueAmount));
        const $addBtn = $('#addFlexiblePayment');
        const $title = $('#emptyPaymentTitle');
        const $hint = $('#emptyPaymentHint');

        $addBtn.html('<i class="fas fa-plus"></i> Add payment method');

        if (due <= 0.01) {
            $title.text('No cash collection needed');
            $hint.text('Return credit already covers this selection. Add method only if you collect additional amount.');
        } else {
            $title.text('Collect remaining cash: Rs. ' + formatAmountValue(due));
            $hint.text('Add cash, card, or other payment and allocate it to bills on the left.');
        }
    }

    /** Show hint under Amount to Pay when returns exist but none applied to sales */
    function updateReturnApplyHint() {
        const $hint = $('#amountToPayReturnHint');
        if (!$hint.length) return;
        let hasApply = false;
        $('.return-checkbox:checked').each(function() {
            const rid = $(this).data('return-id');
            if ($('.return-action[data-return-id="' + rid + '"]').val() === 'apply_to_sales') {
                hasApply = true;
            }
        });
        const show = $('#customerReturnsSection').is(':visible') && availableCustomerReturns && availableCustomerReturns.length > 0 && !hasApply;
        $hint.toggle(!!show);
    }

    // Populate flexible bills list (left side)
    function recalcBillPaymentAllocationsFromUI() {
        const newAllocations = {};

        $('.bill-allocation-row').each(function() {
            const billId = $(this).find('.bill-select').val();
            const amount = parseAmountValue($(this).find('.allocation-amount').val());

            if (billId && amount > 0) {
                newAllocations[billId] = (newAllocations[billId] || 0) + amount;
            }
        });

        billPaymentAllocations = newAllocations;
    }

    function populateFlexibleBillsList(searchTerm = '') {
        recalcBillPaymentAllocationsFromUI();
        let billsHTML = '';

        // Filter bills based on search term (invoice number or notes)
        let filteredSales = availableCustomerSales;
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase().trim();
            filteredSales = availableCustomerSales.filter(sale => {
                const invoiceMatch = sale.invoice_no && sale.invoice_no.toLowerCase().includes(searchLower);
                const notesMatch = sale.sale_notes && sale.sale_notes.toLowerCase().includes(searchLower);
                const billIdMatch = sale.id && sale.id.toString().includes(searchLower);
                return invoiceMatch || notesMatch || billIdMatch;
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
                const isPartiallyAllocated = allocatedAmount > 0.01 && remainingAmount > 0.01;
                const escapedNotes = escapeHtml(sale.sale_notes || '');

                billsHTML += `
                    <div class="bill-item border rounded p-2 mb-2 ${isFullyPaid ? 'bg-light' : 'bg-white'}" data-bill-id="${sale.id}" data-invoice="${escapeHtml(sale.invoice_no)}" data-notes="${escapedNotes}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 small ${isFullyPaid ? 'text-muted' : 'text-primary'}">
                                    ${escapeHtml(sale.invoice_no)}
                                    ${isFullyPaid ? '<span class="badge bg-light text-dark border ms-1" style="font-size: 0.6rem;">Allocated</span>' : ''}
                                    ${isPartiallyAllocated ? '<span class="badge bg-light text-dark border ms-1" style="font-size: 0.6rem;">Partially Allocated</span>' : ''}
                                    ${returnCreditApplied > 0 ? '<span class="badge bg-secondary ms-1" style="font-size: 0.6rem;">Credit Applied</span>' : ''}
                                </h6>
                                <div class="small" style="font-size: 0.75rem;">
                                    <div style="font-size:11px;color:var(--bs-secondary)">
                                        ${remainingAmount > 0.01
        ? (isPartiallyAllocated
            ? `Added: <strong>Rs.${formatAmountValue(allocatedAmount)}</strong> · Remaining amount: <strong>Rs.${formatAmountValue(Math.max(0, remainingAmount))}</strong>`
            : `Remaining amount: <strong>Rs.${formatAmountValue(Math.max(0, remainingAmount))}</strong>`)
        : 'Settled'}
                                        ${returnCreditApplied > 0
        ? ` <span class="text-muted">(after credit Rs.${formatAmountValue(returnCreditApplied)})</span>`
        : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="ms-2">
                                ${!isFullyPaid ? `
                                    <button type="button" class="btn btn-outline-primary btn-sm add-to-payment-btn" data-bill-id="${sale.id}" title="Allocate this bill to a payment method">
                                        Allocate
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
            <div class="payment-method-item border rounded p-2 mb-2 bg-white" data-payment-id="${paymentId}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="text-dark mb-0">
                        <i class="fas fa-credit-card"></i> Payment Method
                    </h6>
                    <button type="button" class="btn btn-outline-secondary btn-sm remove-payment-btn" data-payment-id="${paymentId}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="row mb-2">
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
                        <label class="form-label small">Total Amount <span class="both-payment-hint" style="display:none;"><i class="fas fa-hand-holding-usd text-muted"></i> Enter total receiving</span></label>
                            <input type="text" inputmode="decimal" class="form-control payment-total-amount"
                                placeholder="Enter total amount received"
                            data-payment-id="${paymentId}">
                        <small class="text-muted both-payment-breakdown" style="display:none;">
                            <i class="fas fa-info-circle"></i> Allocation: OB <span class="ob-portion">0.00</span> + Bills <span class="bills-portion">0.00</span> <span class="advance-portion" style="display:none;">+ Advance <span class="advance-amount">0.00</span></span>
                        </small>
                    </div>
                </div>

                <div class="payment-details-container mb-2" style="display: none;">
                    <label class="form-label small text-muted mb-1">
                        <i class="fas fa-info-circle"></i> Payment Details
                    </label>
                    <div class="payment-fields">
                        <!-- Payment method specific fields will appear here -->
                    </div>
                </div>

                <div class="bill-allocations-container">
                    <h6 class="small text-muted mb-2">
                        <i class="fas fa-list"></i> Bill Allocations
                        <button type="button" class="btn btn-outline-secondary btn-xs ms-2 add-bill-allocation-btn"
                                data-payment-id="${paymentId}">
                            <i class="fas fa-plus"></i> Add Bill
                        </button>
                    </h6>
                    <div class="bill-allocations-list" data-payment-id="${paymentId}">
                        <!-- Bill allocations will appear here -->
                    </div>
                </div>
                    <div class="unalloc-warn text-muted"
                        style="font-size:12px;display:none;margin-top:4px;padding:4px 8px;background:#f8f9fa;border:1px solid #e9ecef;border-radius:4px"></div>
            </div>
        `;

        $('#flexiblePaymentsList').prepend(paymentHTML);
        syncPaymentMethodsEmptyState();

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
        // Filter bills - exclude fully paid (including return credit) and show remaining amounts
        const availableBills = availableCustomerSales.filter(sale => {
            const allocatedAmount = billPaymentAllocations[sale.id] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[sale.id] || 0) : 0;
            const remainingAmount = sale.total_due - allocatedAmount - returnCreditApplied;
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
            return `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${escapeHtml(sale.invoice_no)}" data-remaining="${Math.max(0, remainingAmount)}" data-notes="${escapeHtml(sale.sale_notes || '')}">${escapeHtml(sale.invoice_no)} · Pay now Rs.${formatAmountValue(Math.max(0, remainingAmount))}</option>`;
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
                            <input type="text" class="form-control form-control-sm allocation-amount"
                                inputmode="decimal" step="0.01" min="0.01" placeholder="Enter amount"
                            data-allocation-id="${allocationId}" disabled>
                        <small class="text-muted bill-amount-hint" style="display: none;"></small>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm remove-allocation-btn"
                                data-allocation-id="${allocationId}" title="Remove row">
                            <i class="fas fa-trash-alt"></i>
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
                return '';
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

    function updateCalculatedAmountDisplay() {
        if ($('#paymentMethod').val() !== 'multiple') return;
        const $wrap = $('#calculatedAmountMultipleWrap');
        let total = 0;
        $('.payment-total-amount').each(function() {
            total += parseAmountValue($(this).val());
        });
        $('#calcTotal').text(formatAmountValue(total));
        const hasMethods = $('.payment-method-item').length > 0;
        const hasTotals = total > 0.009;
        if (!hasMethods) {
            $wrap.show();
            $('#calcEmptyHint').html('<strong>Next:</strong> Add payment method → <span class="text-muted">allocate to bills</span>').show();
            $('#calcTotalRow').hide();
        } else if (!hasTotals) {
            $wrap.show();
            $('#calcEmptyHint').html('<i class="fas fa-pen me-1"></i>Enter <strong>total amount</strong> on each payment method (sum updates here).').show();
            $('#calcTotalRow').hide();
        } else {
            $('#calcEmptyHint').hide();
            $('#calcTotalRow').hide();
            $wrap.hide();
        }
    }

    /** Highlight workflow steps (1–4) based on customer, payment methods, allocations, balance */
    function updateWorkflowProgress() {
        const $steps = $('.order-flow-step');
        if (!$('#customerSelect').val()) {
            $steps.removeClass('text-primary fw-bold text-success').addClass('text-muted');
            return;
        }
        $steps.removeClass('text-primary fw-bold text-success');
        $steps.eq(0).addClass('text-primary fw-bold');
        const hasPm = $('.payment-method-item').length > 0;
        let allocSum = 0;
        if (typeof billPaymentAllocations === 'object' && billPaymentAllocations !== null) {
            allocSum = Object.values(billPaymentAllocations).reduce((s, v) => s + (parseFloat(v) || 0), 0);
        }
        if (hasPm) {
            $steps.eq(1).addClass('text-primary fw-bold');
        } else {
            $steps.eq(1).addClass('text-muted');
        }
        if (allocSum > 0.01) {
            $steps.eq(2).addClass('text-primary fw-bold');
        } else {
            $steps.eq(2).addClass('text-muted');
        }
        const balTxt = $('#balanceAmount').text().replace(/[^\d.-]/g, '');
        const bal = parseFloat(balTxt) || 0;
        if (hasPm && Math.abs(bal) < 0.02 && allocSum > 0.01) {
            $steps.eq(3).addClass('text-success fw-bold');
        } else {
            $steps.eq(3).addClass('text-muted');
        }
    }

    // Update summary totals
    function updateSummaryTotals() {
        try {
            // Calculate bill totals
            let totalBills = availableCustomerSales.length || 0;
            const retAlloc = window.billReturnCreditAllocations || {};
            let totalDueAmount = availableCustomerSales.reduce((sum, sale) => {
                const ret = parseFloat(retAlloc[sale.id]) || 0;
                return sum + Math.max(0, parseFloat(sale.total_due || 0) - ret);
            }, 0);

            // Calculate payment totals
            let totalPaymentAmount = 0;
            if (paymentMethodAllocations && Object.keys(paymentMethodAllocations).length > 0) {
                Object.values(paymentMethodAllocations).forEach(payment => {
                    totalPaymentAmount += payment.totalAmount || 0;
                });
            }

            // Balance vs ledger totalCustomerDue caused false "advance" when return was not selected:
            // user pays full cash to bills (e.g. 17500) but ledger "Amount to pay" was lower (e.g. 11500).
            // Compare payment to (1) sum of cash allocated to bills, or (2) sum of bill dues minus only CHECKED returns.
            let totalCashAllocatedToBills = 0;
            if (typeof billPaymentAllocations === 'object' && billPaymentAllocations !== null) {
                Object.values(billPaymentAllocations).forEach(v => {
                    totalCashAllocatedToBills += parseFloat(v) || 0;
                });
            }

            let expectedSettlement = totalCashAllocatedToBills;
            if (expectedSettlement < 0.01) {
                expectedSettlement = availableCustomerSales.reduce((sum, sale) => sum + parseFloat(sale.total_due || 0), 0);
                let returnsChecked = 0;
                $('.return-checkbox:checked').each(function() {
                    const rid = $(this).data('return-id');
                    const action = $('.return-action[data-return-id="' + rid + '"]').val();
                    if (action === 'apply_to_sales') {
                        returnsChecked += parseFloat($(this).data('amount')) || 0;
                    }
                });
                let advanceApply = 0;
                if ($('#applyAdvanceCreditCheckbox').is(':checked')) {
                    advanceApply = parseFloat($('#advanceCreditAmountInput').val()) || 0;
                }
                expectedSettlement = Math.max(0, expectedSettlement - returnsChecked - advanceApply);
            }

            // Summary "Balance due" must match Total due (net) minus Cash collected — not
            // (allocation target − payment). When allocations sum to the payment, expectedSettlement
            // equals totalPaymentAmount and would wrongly show 0 while net due remains.
            let balanceAmount = totalDueAmount - totalPaymentAmount;

            const hasAppliedReturnCredits = selectedReturns.some(r => r.action === 'apply_to_sales');
            const hasPaymentMethods = $('.payment-method-item').length > 0;
            const showReturnAdjustedState = hasAppliedReturnCredits && !hasPaymentMethods && totalPaymentAmount < 0.01;
            if (showReturnAdjustedState) {
                // Avoid misleading "remaining to allocate" before user adds a cash method.
                balanceAmount = 0;
            }

            // Update UI elements if they exist
            const $totalBillsCount = $('#totalBillsCount');
            const $summaryDueAmount = $('#summaryDueAmount');
            const $totalPaymentAmount = $('#totalPaymentAmount');
            const $balanceAmount = $('#balanceAmount');

            if ($totalBillsCount.length) $totalBillsCount.text(totalBills);
            if ($summaryDueAmount.length) $summaryDueAmount.text(`Rs. ${formatAmountValue(totalDueAmount)}`);

            if ($totalPaymentAmount.length) {
                $totalPaymentAmount.text(`Rs. ${formatAmountValue(totalPaymentAmount)}`);
                // Update color based on amount
                if (totalPaymentAmount > 0) {
                    $totalPaymentAmount.removeClass('text-muted').addClass('text-success');
                } else {
                    $totalPaymentAmount.removeClass('text-success').addClass('text-muted');
                }
            }

            const $balanceLabel = $('#balanceLabel');
            if ($balanceLabel.length) {
                $balanceLabel.text(showReturnAdjustedState ? 'Return credit adjusted' : 'Balance due');
            }

            if ($balanceAmount.length) {
                if (balanceAmount > 0) {
                    $balanceAmount.text(`Rs. ${formatAmountValue(balanceAmount)}`).removeClass('text-success text-danger text-warning').addClass('text-primary');
                } else if (balanceAmount < 0) {
                    $balanceAmount.text(`Rs. ${formatAmountValue(balanceAmount)}`).removeClass('text-warning text-success text-primary').addClass('text-danger');
                } else {
                    $balanceAmount.text(`Rs. ${formatAmountValue(balanceAmount)}`).removeClass('text-warning text-danger text-primary').addClass('text-success');
                }
            }

            updatePaymentMethodHints(balanceAmount);

            updateWorkflowProgress();

            // Submit: show whenever a customer is selected (validation on click). Avoid hiding after load when payment total is still 0.
            const $submitSection = $('#submitButtonSection');
            if ($submitSection.length) {
                const customerId = $('#customerSelect').val();
                if (customerId) {
                    $submitSection.fadeIn();
                } else {
                    $submitSection.fadeOut();
                }
            }

            console.log('Summary totals updated:', { totalBills, totalDueAmount, totalPaymentAmount, expectedSettlement, balanceAmount });

            updateCalculatedAmountDisplay();

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
            const amount = parseAmountValue($row.find('.allocation-amount').val());
            if (billId && amount > 0) {
                billPaymentAllocations[billId] = Math.max(0, (billPaymentAllocations[billId] || 0) - amount);
            }
        });
        $billAllocationsList.empty();

        let remainingAmount = amountToDistribute;
        let billIndex = 0;

        // Sort bills by date (FIFO - oldest first)
        const sortedBills = [...availableCustomerSales].sort((a, b) => {
            return new Date(a.sales_date) - new Date(b.sales_date);
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
                                    <option value="${bill.id}" selected>${bill.invoice_no} · Pay now Rs. ${formatAmountValue(remainingDue)}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control form-control-sm allocation-amount"
                                    inputmode="decimal" value="${formatAmountValue(amountForThisBill)}"
                                    data-allocation-id="${allocationId}" data-prev-amount="${amountForThisBill}">
                                <small class="bill-amount-hint ${isFullyPaid ? 'text-success' : 'text-muted'}">
                                    ${isFullyPaid ? 'Settled' : 'Pay now after this: Rs. ' + formatAmountValue(remainingDue - amountForThisBill)}
                                </small>
                            </div>
                            <div class="col-md-3 text-end">
                                <button type="button" class="btn btn-outline-secondary btn-xs remove-bill-allocation-btn" data-allocation-id="${allocationId}" title="Remove row">
                                    <i class="fas fa-trash-alt"></i>
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
        const $container = $(`.payment-method-item[data-payment-id="${paymentId}"]`);
        const $totalInput = $(`.payment-total-amount[data-payment-id="${paymentId}"]`);

        let billsTotal = 0;
        $container.find('.allocation-amount').each(function() {
            billsTotal += parseAmountValue($(this).val());
        });

        if (paymentType === 'both') {
            const totalAmount = parseAmountValue($totalInput.val());
            if (paymentMethodAllocations[paymentId]) {
                paymentMethodAllocations[paymentId].totalAmount = totalAmount;
            }
            updateSummaryTotals();
        } else {
            if (paymentMethodAllocations[paymentId]) {
                paymentMethodAllocations[paymentId].totalAmount = billsTotal;
            }

            $totalInput.data('system-update', true);
        $totalInput.val(formatAmountValue(billsTotal));

            setTimeout(() => {
                $totalInput.data('system-update', false);
            }, 100);

            updateSummaryTotals();
        }

        const $pmContainer = $(`.payment-method-item[data-payment-id="${paymentId}"]`);
        const enteredTotal = parseAmountValue($pmContainer.find('.payment-total-amount').val());
        const unassigned = enteredTotal - billsTotal;
        let $warn = $pmContainer.find('.unalloc-warn');
        if ($warn.length === 0) {
            $pmContainer.find('.bill-allocations-container').after(
                '<div class="unalloc-warn" style="font-size:12px;display:none;' +
                'margin-top:6px;padding:4px 10px;background:#FCEBEB;color:#A32D2D;' +
                'border-radius:4px"></div>'
            );
            $warn = $pmContainer.find('.unalloc-warn');
        }
        if (unassigned > 0.01) {
            $warn.text('⚠ Rs.' + unassigned.toFixed(2) +
                ' entered but not assigned to any bill').show();
        } else {
            $warn.hide();
        }
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
        let validationFailed = false;
        let groupIndex = 0;

        // Initialize bill return allocations BEFORE collecting payment groups
        let billReturnAllocations = {};
        const hasApplyToSalesReturns = selectedReturns.some(r => r.action === 'apply_to_sales');
        if (hasApplyToSalesReturns && window.billReturnCreditAllocations) {
            billReturnAllocations = window.billReturnCreditAllocations;
            console.log('Bill return allocations available:', billReturnAllocations);
        }

        $('.payment-method-item').each(function() {
            const $payment = $(this);
            const paymentId = $payment.data('payment-id');
            const method = $payment.find('.payment-method-select').val();
            const totalAmount = parseAmountValue($payment.find('.payment-total-amount').val());

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
                        validationFailed = true;
                        return false;
                    }
                    if (!chequeBank || chequeBank.trim() === '') {
                        toastr.error(`Payment ${groupIndex}: Bank & Branch is required for cheque payments`);
                        $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
                        validationFailed = true;
                        return false;
                    }
                    if (!chequeDate || chequeDate.trim() === '') {
                        toastr.error(`Payment ${groupIndex}: Cheque Date is required`);
                        $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment');
                        validationFailed = true;
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

            // Collect bill allocations from this payment method only
            $payment.find('.bill-allocation-row').each(function() {
                const $allocation = $(this);
                const billId = $allocation.find('.bill-select').val();
                const amount = parseAmountValue($allocation.find('.allocation-amount').val());

                if (billId && amount > 0) {
                    groupData.bills.push({
                        sale_id: parseInt(billId),
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
                groupData.ob_amount = parseAmountValue(obPortionText);
            }

            // For opening balance payments, use the total amount even if no bills
            if (paymentType === 'opening_balance') {
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

        if (validationFailed) {
            return false;
        }

        const canSubmitReturnOnlyWithoutCash = () => {
            if (paymentGroups.length > 0) return false;
            if (!selectedReturns || selectedReturns.length === 0) return false;
            const hasApply = selectedReturns.some(r => r.action === 'apply_to_sales');
            const hasRefund = selectedReturns.some(r => r.action === 'cash_refund');
            if (hasApply) {
                let allocSum = 0;
                if (billReturnAllocations && typeof billReturnAllocations === 'object') {
                    Object.values(billReturnAllocations).forEach(v => {
                        allocSum += parseFloat(v) || 0;
                    });
                }
                if (allocSum < 0.01) return false;
            }
            return hasApply || hasRefund;
        };

        if (!hasValidPayments && !canSubmitReturnOnlyWithoutCash()) {
            const hasApplyOnly = selectedReturns && selectedReturns.some(r => r.action === 'apply_to_sales');
            let allocSum = 0;
            if (billReturnAllocations && typeof billReturnAllocations === 'object') {
                Object.values(billReturnAllocations).forEach(v => {
                    allocSum += parseFloat(v) || 0;
                });
            }

            if (hasApplyOnly && allocSum < 0.01) {
                toastr.warning('Allocation missing: choose bill-wise return credit via Change Allocation, then submit.', 'Allocation Required', { timeOut: 5000 });
                setTimeout(() => {
                    $('#reallocateAllCreditsBtn').trigger('click');
                }, 120);
                return false;
            }

            toastr.error('Please add at least one payment method with bill allocations, or submit returns only (apply to sales with credit allocated / cash refund).');
            return false;
        }

        // Validate allocations don't exceed bill amounts
        const billTotals = {};
        paymentGroups.forEach(group => {
            group.bills.forEach(bill => {
                billTotals[bill.sale_id] = (billTotals[bill.sale_id] || 0) + bill.amount;
            });
        });

        for (const [billId, totalAllocated] of Object.entries(billTotals)) {
            const bill = availableCustomerSales.find(s => s.id == billId);
            if (bill && totalAllocated > bill.total_due) {
                toastr.error(`Total allocation for ${bill.invoice_no} exceeds bill amount`);
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
            selected_returns: selectedReturns,
            hasApplyToSalesReturns: hasApplyToSalesReturns,
            bill_return_allocations: billReturnAllocations,
            advance_credit_applied: advanceCreditApplied,
            payment_groups: paymentGroups
        });

        // Validate: Bills in payment_groups should not include amounts already covered by return credits
        if (hasApplyToSalesReturns && Object.keys(billReturnAllocations).length > 0) {
            paymentGroups.forEach(group => {
                group.bills.forEach(bill => {
                    const returnCredit = billReturnAllocations[bill.sale_id] || 0;
                    if (returnCredit > 0) {
                        console.log(`Bill ${bill.sale_id}: Payment amount = ${bill.amount}, Return credit = ${returnCredit}`);
                    }
                });
            });
        }

        // Submit flexible payment (JSON body so empty payment_groups [] is sent; jQuery form encoding omits empty arrays)
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: '/submit-flexible-bulk-payment',
            method: 'POST',
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            data: JSON.stringify({
                customer_id: customerId,
                payment_date: paymentDate,
                payment_type: paymentType,
                payment_groups: paymentGroups,
                selected_returns: selectedReturns,
                bill_return_allocations: billReturnAllocations,
                advance_credit_applied: advanceCreditApplied,
                notes: $('#notes').val() || '',
                _token: csrfToken
            }),
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.status === 200) {
                    const total = parseFloat(response.total_amount || 0);
                    const allocationOnly = response.allocation_only === true || total <= 0.01;

                    $('#receiptReferenceNo').text(response.bulk_reference || 'N/A');
                    $('#receiptTotalAmount').text(total.toFixed(2));

                    if (allocationOnly) {
                        $('#receiptModalTitle').html('<i class="fas fa-exchange-alt"></i> Allocation Successful');
                        $('#receiptModalIcon').attr('class', 'fas fa-balance-scale fa-3x text-success mb-3');
                        $('#receiptModalSubtitle').text('Return credit settlement completed');
                        $('#receiptAmountLabel').text('Cash collected:');
                        $('#receiptModalFootnote').text(
                            'No payment row was created: return credit was applied to the sale. ' +
                            'Sales and returns balances were updated; ledger for cash did not change.'
                        );
                        $('#receiptReferenceWrap, #receiptCopyWrap').hide();
                    } else {
                        $('#receiptModalTitle').html('<i class="fas fa-check-circle"></i> Payment Successful');
                        $('#receiptModalIcon').attr('class', 'fas fa-receipt fa-3x text-success mb-3');
                        $('#receiptModalSubtitle').text('Payment Reference Number');
                        $('#receiptAmountLabel').text('Total Amount:');
                        $('#receiptModalFootnote').text('Save this reference number for future payment tracking and verification.');
                        $('#receiptReferenceWrap, #receiptCopyWrap').show();
                    }

                    $('#submitBulkPayment').prop('disabled', false).html('<i class="fas fa-credit-card"></i> Submit Payment');
                    $('#paymentReceiptModal').modal('show');

                    if (response.message && typeof toastr !== 'undefined') {
                        toastr.success(response.message, allocationOnly ? 'Allocation' : 'Payment', { timeOut: 6000 });
                    }
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON;
                if (error && error.errors) {
                    const firstKey = Object.keys(error.errors)[0];
                    const msgs = error.errors[firstKey];
                    toastr.error(Array.isArray(msgs) ? msgs[0] : String(msgs));
                } else {
                    toastr.error(error?.message || 'Flexible payment submission failed');
                }
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
                const amount = parseAmountValue($row.find('.allocation-amount').val());

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
                syncPaymentMethodsEmptyState();
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

                    const $detailsContainer = $container.find('.payment-details-container');
                    const $fieldsContainer = $detailsContainer.find('.payment-fields');

                    $fieldsContainer.html(fieldsHTML);
                    if (fieldsHTML && fieldsHTML.trim() !== '') {
                        $detailsContainer.slideDown();
                        toastr.success(`${method.toUpperCase()} selected`);
                    } else {
                        $detailsContainer.slideUp();
                    }
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
            // UX: Add Bill should always open a blank/manual allocation row.
            // Do not auto-fill or auto-distribute amounts on this click.
            addBillAllocation(paymentId);
        });

        // Remove Bill Allocation (handle both old and new button classes)
        $(document).on('click', '.remove-allocation-btn, .remove-bill-allocation-btn', function() {
            const $row = $(this).closest('.bill-allocation-row');
            const billId = $row.find('.bill-select').val();
            const amount = parseAmountValue($row.find('.allocation-amount').val());
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
                    const totalAmount = parseAmountValue($paymentContainer.find('.payment-total-amount').val());

                    if (totalAmount > 0) {
                        // Recalculate bills portion
                        let billsPortion = 0;
                        $paymentContainer.find('.allocation-amount').each(function() {
                            billsPortion += parseAmountValue($(this).val());
                        });

                        const selectedOption = $('#customerSelect').find(':selected');
                        const customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
                        const obPortion = Math.min(totalAmount, customerOpeningBalance);
                        const advancePortion = Math.max(0, totalAmount - obPortion - billsPortion);

                        // Update breakdown
                        $paymentContainer.find('.bills-portion').text(formatAmountValue(billsPortion));
                        if (advancePortion > 0.01) {
                            $paymentContainer.find('.advance-portion').show();
                            $paymentContainer.find('.advance-amount').text(formatAmountValue(advancePortion));
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

            // Prevent duplicate bill rows in the same payment method container.
            if (billId) {
                const duplicateCount = $row.closest('.payment-method-item').find('.bill-select').filter(function() {
                    return $(this).val() == billId;
                }).length;
                if (duplicateCount > 1) {
                    $(this).val('');
                    $amountInput.prop('disabled', true).val('').removeAttr('max').attr('placeholder', 'Select bill first');
                    $hint.text('This bill is already added above. Edit that row instead.').show();
                    toastr.warning('Bill already added in this payment method. Please edit the existing row.');
                    return;
                }
            }

            if (billId) {
                const bill = availableCustomerSales.find(s => s.id == billId);
                const allocatedAmount = billPaymentAllocations[billId] || 0;
                const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
                const remainingAmount = bill.total_due - allocatedAmount - returnCreditApplied;

                // Enable amount input but DON'T auto-fill
                $amountInput.attr('max', remainingAmount.toFixed(2)).prop('disabled', false);
                $amountInput.attr('placeholder', `Max: Rs. ${formatAmountValue(remainingAmount)}`);
                $amountInput.val(''); // Empty - let user type

                // Show available amount info
                $hint.text(`Pay now: Rs. ${formatAmountValue(remainingAmount)}`).show();

                normalizeAllBillAllocationRows();
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
            const amount = parseAmountValue($(this).val());

            if (!billId) return;

            const bill = availableCustomerSales.find(s => s.id == billId);
            if (!bill) return;

            // Calculate available amount for this bill (including return credits)
            const prevAmount = $(this).data('prev-amount') || 0;
            const currentAllocation = billPaymentAllocations[billId] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
            const maxAmount = bill.total_due - (currentAllocation - prevAmount) - returnCreditApplied;

            // Validate amount doesn't exceed remaining balance
            if (amount > maxAmount) {
                $(this).val(formatAmountValue(maxAmount));
                // Show subtle warning in hint instead of annoying toastr
                $hint.text(`⚠️ Amount limited to remaining balance: Rs. ${formatAmountValue(maxAmount)}`).removeClass('text-muted text-success').addClass('text-warning');
                return;
            }

            // Update bill payment tracking
            billPaymentAllocations[billId] = (currentAllocation - prevAmount) + amount;
            $(this).data('prev-amount', amount);

            // If bill becomes fully paid, remove from available bills
            const remainingAfterPayment = bill.total_due - billPaymentAllocations[billId] - returnCreditApplied;

            // Update hint with payment status
            if (remainingAfterPayment <= 0.01) {
                $hint.text('Settled').removeClass('text-muted').addClass('text-success');
            } else {
                $hint.text(`Pay now after this: Rs. ${formatAmountValue(remainingAfterPayment)}`).removeClass('text-success').addClass('text-muted');
            }

            // Keep user typing natural; formatting is applied on blur handler.

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
                const totalAmount = parseAmountValue($(this).val());
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
                            const customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;

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
                            const remainingOB = Math.max(0, customerOpeningBalance - totalOBAllocated);

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
                                    billsPortion += parseAmountValue($(this).val());
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
                            $paymentContainer.find('.ob-portion').text(formatAmountValue(obPortion));
                            $paymentContainer.find('.bills-portion').text(formatAmountValue(billsPortion));

                            if (advancePortion > 0) {
                                $paymentContainer.find('.advance-portion').show();
                                $paymentContainer.find('.advance-amount').text(formatAmountValue(advancePortion));
                            } else {
                                $paymentContainer.find('.advance-portion').hide();
                            }
                        } else {
                            // Amount is 0 or cleared - clear all bill allocations
                            $paymentContainer.find('.bill-allocation-row').each(function() {
                                const $row = $(this);
                                const billId = $row.find('.bill-select').val();
                                const amount = parseAmountValue($row.find('.allocation-amount').val());

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

                        updatePaymentMethodTotal(paymentId);
                        return;
                    }

                    // FOR "SALE_DUES" TYPE: Auto-allocate bills in FIFO order
                    if (paymentType === 'sale_dues') {
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
                                const amount = parseAmountValue($(this).find('.allocation-amount').val());

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
                                const amount = parseAmountValue($row.find('.allocation-amount').val());
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
                                actualAllocatedAmount += parseAmountValue($(this).val());
                            });

                            // Step 7: Calculate advance/excess amount
                            const advanceAmount = totalAmount - actualAllocatedAmount;

                            // Step 8: Update hint/excess options (advance payment support)
                            const $hint = $paymentContainer.find('.payment-total-hint');

                            if (advanceAmount > 0.01) {
                                const hintText = `⚠️ Extra amount Rs. ${formatAmountValue(advanceAmount)}. Choose option below (default: keep as advance).`;
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
                                            <small class="text-muted d-block mb-1">💡 Extra amount found. What to do?</small>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="advance_${paymentId}" value="advance" checked>
                                                <label class="form-check-label small" for="advance_${paymentId}">
                                                    ✅ Keep extra as Advance (Customer Credit) - default
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="reduce_${paymentId}" value="reduce">
                                                <label class="form-check-label small" for="reduce_${paymentId}">
                                                    ↩ Do not keep advance - reduce entered total to Rs. ${formatAmountValue(actualAllocatedAmount)}
                                                </label>
                                            </div>
                                        </div>
                                    `);
                                } else {
                                    $excessOptions.find(`label[for="reduce_${paymentId}"]`).text(`↩ Do not keep advance - reduce entered total to Rs. ${formatAmountValue(actualAllocatedAmount)}`);
                                }
                            } else {
                                const hintText = `✅ Perfect allocation: Rs. ${formatAmountValue(actualAllocatedAmount)}`;
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
                                const amount = parseAmountValue($row.find('.allocation-amount').val());

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
                                const bill = availableCustomerSales.find(s => s.id == billId);
                                if (bill) {
                                    const previousAllocated = billPaymentAllocations[billId] || 0;
                                    const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
                                    const availableAmount = bill.total_due - previousAllocated - returnCreditApplied;

                                    billsToUpdate.push({
                                        row: $row,
                                        billId: billId,
                                        availableAmount: availableAmount,
                                        invoice: bill.invoice_no,
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
                            const currentAmount = parseAmountValue($amountInput.val());
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
                            const bill = availableCustomerSales.find(s => s.id == billInfo.billId);
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
                                $amountInput.val(formatAmountValue(amountToAllocate));

                                // Update tracking
                                billPaymentAllocations[billInfo.billId] = (billPaymentAllocations[billInfo.billId] || 0) + amountToAllocate;
                                $amountInput.data('prev-amount', amountToAllocate);

                                // Update hint
                                const $hint = billInfo.row.find('.bill-amount-hint');
                                const remainingAfterPayment = billInfo.availableAmount - amountToAllocate;

                                if (remainingAfterPayment <= 0.01) {
                                    $hint.text('✅ Bill will be fully paid').removeClass('text-muted').addClass('text-success');
                                } else {
                                    $hint.text(`💰 Remaining: Rs. ${formatAmountValue(remainingAfterPayment)}`).removeClass('text-success').addClass('text-muted');
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
                            const hintText = `⚠️ Extra amount Rs. ${formatAmountValue(remainingAmount)}. Choose option below (default: keep as advance).`;
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
                                        <small class="text-muted d-block mb-1">💡 Extra amount found. What to do?</small>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="advance_${paymentId}" value="advance" checked>
                                            <label class="form-check-label small" for="advance_${paymentId}">
                                                ✅ Keep extra as Advance (Customer Credit) - default
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input excess-option" type="radio" name="excess_${paymentId}" id="reduce_${paymentId}" value="reduce">
                                            <label class="form-check-label small" for="reduce_${paymentId}">
                                                ↩ Do not keep advance - reduce entered total to Rs. ${formatAmountValue(actualAllocated)}
                                            </label>
                                        </div>
                                    </div>
                                `);
                            } else {
                                // Update existing options
                                $excessOptions.find(`label[for="reduce_${paymentId}"]`).text(`↩ Do not keep advance - reduce entered total to Rs. ${formatAmountValue(actualAllocated)}`);
                            }
                        } else {
                            // Perfect allocation - no excess
                            const hintText = `✅ Perfect allocation: Rs. ${formatAmountValue(actualAllocated)}`;
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
                    actualAllocated += parseAmountValue($(this).val());
                });

                // Update total to match allocations
                $totalInput.data('system-update', true);
                $totalInput.val(formatAmountValue(actualAllocated));

                // Update tracking
                paymentMethodAllocations[paymentId].totalAmount = actualAllocated;

                // Remove excess options
                $paymentContainer.find('.excess-options').remove();

                // Update hint
                const $hint = $paymentContainer.find('.payment-total-hint');
                $hint.text(`✅ Reduced to allocated amount: Rs. ${formatAmountValue(actualAllocated)}`).removeClass('text-warning').addClass('text-success');

                // Remove system update flag
                setTimeout(() => {
                    $totalInput.data('system-update', false);
                }, 100);

                toastr.success(`Payment amount reduced to Rs. ${formatAmountValue(actualAllocated)} (no excess)`);
            } else if (selectedOption === 'advance') {
                toastr.info('Default kept: extra amount will be saved as customer advance credit.');
            }

            updateSummaryTotals();
        });

        // Add to Payment from Bill (Quick Add) - FIXED: Prevent triggering auto-allocation
        $(document).on('click', '.add-to-payment-btn', function() {
            const billId = $(this).data('bill-id');
            const bill = availableCustomerSales.find(s => s.id == billId);

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

            // Calculate remaining amount BEFORE add/update
            const allocatedAmount = billPaymentAllocations[billId] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[billId] || 0) : 0;
            const remainingAmount = bill.total_due - allocatedAmount - returnCreditApplied;

            // If the bill is already added in this payment method, update that same row amount.
            const $existingRow = $firstPayment.find('.bill-allocation-row').filter(function() {
                return $(this).find('.bill-select').val() == billId;
            }).first();

            if ($existingRow.length) {
                if (remainingAmount <= 0.01) {
                    toastr.warning(`${bill.invoice_no} is already fully paid or allocated`);
                    return;
                }

                const $existingInput = $existingRow.find('.allocation-amount');
                const $existingHint = $existingRow.find('.bill-amount-hint');
                const currentAmount = parseAmountValue($existingInput.val());

                // Max allowed for this row considering other rows/allocations for same bill.
                const otherAllocations = Math.max(0, (billPaymentAllocations[billId] || 0) - currentAmount);
                const maxForRow = Math.max(0, parseAmountValue(bill.total_due) - returnCreditApplied - otherAllocations);
                const newAmount = Math.min(maxForRow, currentAmount + remainingAmount);

                $existingInput.data('system-update', true);
                $existingInput.val(formatAmountValue(newAmount));
                $existingInput.data('prev-amount', newAmount);

                billPaymentAllocations[billId] = otherAllocations + newAmount;

                const remainingAfterThis = Math.max(0, maxForRow - newAmount);
                if (remainingAfterThis <= 0.01) {
                    $existingHint.text(returnCreditApplied > 0 ? 'Settled (credit applied)' : 'Settled').removeClass('text-muted').addClass('text-success').show();
                } else {
                    $existingHint.text(`Pay now after this: Rs. ${formatAmountValue(remainingAfterThis)}`).removeClass('text-success').addClass('text-muted').show();
                }

                $existingRow.addClass('border-primary');
                $existingRow.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => {
                    $existingInput.data('system-update', false);
                    $existingRow.removeClass('border-primary');
                }, 1400);

                updatePaymentMethodTotal(paymentId);
                if (typeof normalizeAllBillAllocationRows === 'function') {
                    normalizeAllBillAllocationRows();
                }
                populateFlexibleBillsList();
                updateSummaryTotals();

                toastr.success(`Updated ${bill.invoice_no} in existing row`);
                return;
            }

            if (remainingAmount <= 0.01) {
                toastr.warning(`${bill.invoice_no} is already fully paid or allocated`);
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
            $amountInput.attr('placeholder', `Max: Rs. ${formatAmountValue(remainingAmount)}`);

            // Set amount value
            const amountValue = parseFloat(remainingAmount.toFixed(2));
            $amountInput.val(formatAmountValue(amountValue));
            $amountInput.data('prev-amount', amountValue);

            // Update tracking
            billPaymentAllocations[billId] = (billPaymentAllocations[billId] || 0) + amountValue;

            // Update hint
            if (returnCreditApplied > 0) {
                $hint.text('Settled (credit applied)').removeClass('text-muted').addClass('text-success').show();
            } else {
                $hint.text('Settled').removeClass('text-muted').addClass('text-success').show();
            }

            // Update payment method totals
            updatePaymentMethodTotal(paymentId);

            // Refresh UI
            populateFlexibleBillsList();
            updateSummaryTotals();

            toastr.success(`Added ${bill.invoice_no} - Rs. ${formatAmountValue(amountValue)}`);
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
    #bulkPaymentForm, #bulkPaymentForm * {
        font-size: 13px !important;
    }
    #bulkPaymentForm h6 { font-size: 13px !important; }
    #bulkPaymentForm .form-label { margin-bottom: 0.2rem !important; font-weight: 500; }
    #bulkPaymentForm .form-control,
    #bulkPaymentForm .form-select {
        padding: 4px 8px !important;
        height: 30px !important;
        font-size: 13px !important;
    }
    #bulkPaymentForm textarea.form-control {
        height: auto !important;
        min-height: 30px;
    }
    #bulkPaymentForm .btn-lg {
        font-size: 14px !important;
        padding: 8px 28px !important;
    }
    #netCustomerDue { font-size: 1.2rem !important; }

    #customerSummarySection,
    #customerReturnsSection,
    #advancedOptionsContainer {
        line-height: 1.28;
    }

    #customerSummarySection .small,
    #customerReturnsSection .small,
    .bill-item .small,
    .bill-amount-hint {
        font-size: 12px !important;
    }

    #customerSummarySection .text-muted,
    #customerReturnsSection .text-muted {
        letter-spacing: 0.01em;
    }

    /* Clean, minimal styling for the new bulk payments UI */
    .display-6 {
        font-size: 2.5rem;
        font-weight: 300;
        line-height: 1.2;
    }

    #customerSummarySection {
        transition: all 0.3s ease;
    }

    #paymentTypeSection {
        margin-top: 0.25rem;
    }

    #paymentTypeSection .form-check {
        margin-right: 0.5rem;
    }

    #paymentTypeSection .form-check-input {
        margin-top: 0.15rem;
    }

    /* Collapsible balance details — no default triangle clutter */
    #customerBalanceDetails > summary {
        list-style: none;
        cursor: pointer;
    }
    #customerBalanceDetails > summary::-webkit-details-marker {
        display: none;
    }
    #customerBalanceDetails[open] > summary .fa-list-ul {
        opacity: 0.85;
    }

    /* Payment methods: no scrollbar in empty state */
    .payment-methods-container--empty {
        overflow-y: hidden !important;
    }
    .payment-methods-container:not(.payment-methods-container--empty) {
        overflow-y: auto;
    }

    /* Returns table: avoid tiny scroll area when few rows */
    .returns-table-wrap {
        max-height: 140px;
        overflow-y: auto;
    }

    .bulk-payment-page {
        background: #f7f8fa;
        min-height: 100vh;
    }

    #customerReturnsSection .table th,
    #customerReturnsSection .table td {
        padding-top: 0.3rem;
        padding-bottom: 0.3rem;
    }

    #customerReturnsSection h6,
    #customerReturnsSection .btn,
    #customerReturnsSection .table {
        font-size: 12px !important;
    }

    .bulk-workflow-steps .order-flow-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.35rem;
        height: 1.35rem;
        padding: 0 4px;
        border-radius: 50%;
        background: #e9ecef;
        font-size: 11px;
        font-weight: 600;
        margin-right: 4px;
    }
    .order-flow-step.text-primary .order-flow-num {
        background: var(--bs-primary);
        color: #fff;
    }
    .order-flow-step.text-success .order-flow-num {
        background: var(--bs-success);
        color: #fff;
    }

    .bulk-payment-submit-bar {
        position: sticky;
        bottom: 0;
        z-index: 1030;
        background: #fff;
        border-top: 1px solid #dee2e6;
        box-shadow: 0 -4px 10px rgba(0, 0, 0, 0.04);
        margin-top: 1rem;
    }

    .allocation-summary-grid .allocation-summary-metric {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 0.15rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.5rem;
        background: #fbfcfd;
        border: 1px solid #eef1f4;
    }

    .allocation-summary-grid .allocation-summary-value {
        line-height: 1.1;
        min-height: 1.1rem;
    }

    .allocation-summary-grid .col-6,
    .allocation-summary-grid .col-md-3,
    .allocation-summary-grid .col-4 {
        display: flex;
    }

    .allocation-summary-grid .col-6 > .allocation-summary-metric,
    .allocation-summary-grid .col-md-3 > .allocation-summary-metric,
    .allocation-summary-grid .col-4 > .allocation-summary-metric {
        width: 100%;
    }

    .customer-summary-grid .customer-balance-details {
        width: 100%;
    }

    .customer-summary-grid #customerBalanceDetails > summary {
        margin-bottom: 0;
    }

    .customer-summary-grid .amount-to-pay-highlight {
        min-height: 100%;
    }

    #advancedOptionsContainer .border.rounded-3,
    #customerSummarySection,
    #customerReturnsSection > .border,
    #customerAdvanceSection > .border {
        box-shadow: none !important;
    }

    #advancedOptionsContainer .text-primary,
    #advancedOptionsContainer .text-info,
    #advancedOptionsContainer .text-success,
    #advancedOptionsContainer .text-danger {
        color: #4b5563 !important;
    }

    .payment-method-item,
    .bill-allocation-row {
        border-color: #e6e9ee !important;
    }

    .payment-methods-container {
        overflow-x: hidden;
    }

    .bill-allocation-row .row {
        row-gap: 0.35rem;
    }

    .bill-allocation-row .form-select,
    .bill-allocation-row .form-control {
        width: 100%;
    }

    @media (min-width: 768px) {
        .bill-allocation-row .col-md-5 {
            flex: 0 0 46%;
            max-width: 46%;
        }
        .bill-allocation-row .col-md-4 {
            flex: 0 0 38%;
            max-width: 38%;
        }
        .bill-allocation-row .col-md-3 {
            flex: 0 0 16%;
            max-width: 16%;
        }
    }

    .payment-method-item .btn {
        border-radius: 6px;
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
        border-color: #aeb7c2;
        box-shadow: 0 0 0 0.15rem rgba(174, 183, 194, 0.22);
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
        background-color: #eef2f6 !important;
        border-left: 3px solid #adb5bd;
    }
 </style>

    <!-- SweetAlert2 for beautiful alerts/dialogs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endsection
