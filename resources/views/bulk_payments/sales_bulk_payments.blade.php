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
        <div class="bulk-section-card mb-3">
            <!-- Customer Selection -->
            <div class="mb-2">
                <label for="customerSelect" class="form-label">Customer</label>
                <select id="customerSelect" class="form-control selectBox">
                    <option value="">🔄 Loading customers...</option>
                </select>
            </div>

            <!-- Customer Summary: primary = cash to pay; rest in collapsible details -->
            <div id="customerSummarySection" class="border rounded-3 p-2 mb-3 bg-white shadow-sm" style="display: none;">
                <div class="row g-2 align-items-start customer-summary-grid">
                    <div class="col-12 col-lg-7 min-w-0">
                        <details id="customerBalanceDetails" class="customer-balance-details border-0" open>
                            <summary class="customer-balance-summary text-muted small user-select-none">
                                <i class="fas fa-list-ul me-1 text-secondary"></i>Balance
                            </summary>
                            <div class="mt-2 pt-2 border-top border-light small">
                                <div class="d-flex flex-wrap gap-2 align-items-baseline">
                                    <span class="text-muted me-1">Opening Balance: <strong class="text-dark">Rs. <span id="openingBalance">0.00</span></strong></span>
                                    <span class="text-muted border-start border-light ps-2">Sales unpaid (invoices): <strong class="text-dark">Rs. <span id="totalDueAmount">0.00</span></strong></span>
                                    <span class="text-muted border-start border-light ps-2">Account due (ledger): <strong class="text-dark">Rs. <span id="accountDueAmount">0.00</span></strong></span>
                                    <span id="returnCount" class="text-info" style="display: none;">(<span id="returnCountNumber">0</span> returns available)</span>
                                    <span id="advanceCount" class="text-success" style="display: none;">(Credit available: Rs. <span id="advanceAmount">0.00</span>)</span>
                                </div>
                                <div id="returnCreditAppliedSummary" class="mt-1 text-muted small" style="display: none;"></div>
                                <div id="netCalculation" class="mt-1" style="display:none !important"></div>
                            </div>
                        </details>
                    </div>
                    <div class="col-12 col-lg-5">
                        <div class="amount-to-pay-highlight border border-primary rounded-3 p-2 bg-white text-start h-100">
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

            <div class="small text-muted mb-2 d-flex flex-wrap align-items-center gap-2">
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
            <div class="bulk-section-card mb-3">
                <!-- Quick Payment Input -->
                <div class="row g-3 mb-2 align-items-start">
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

                <!-- Credit / Advance Section - Similar to Returns -->
                <div id="customerAdvanceSection" class="mb-3" style="display: none;">
                    <div class="border rounded p-2 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-success">
                                <i class="fas fa-piggy-bank"></i> Credit available (Rs. <span id="advanceToApplyToBills">0.00</span> available)
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="hideAdvanceBtn">
                                <i class="fas fa-times"></i> Hide
                            </button>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="applyAdvanceCreditCheckbox">
                            <label class="form-check-label" for="applyAdvanceCreditCheckbox">
                                Apply credit to reduce cash to collect
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
                <div id="submitButtonSection" class="bulk-payment-submit-bar text-center py-2 px-2" style="display: none;">
                    <div class="container-fluid">
                        <div class="row g-2 align-items-center justify-content-center">
                            <div class="col-12 col-lg-7">
                                <div class="bulk-sticky-inline d-flex flex-wrap justify-content-center justify-content-lg-start gap-2">
                                    <div class="bulk-sticky-inline__metrics">
                                        <span class="text-muted">Due:</span> <strong id="stickyDueAmount">Rs. 0.00</strong>
                                        <span class="text-muted mx-2">|</span>
                                        <span class="text-muted">Collected:</span> <strong id="stickyCollectedAmount">Rs. 0.00</strong>
                                        <span class="text-muted mx-2">|</span>
                                        <span class="text-muted">Balance:</span> <strong id="stickyBalanceAmount">Rs. 0.00</strong>
                                    </div>
                                    <span id="stickyStatusHint" class="d-none bulk-sticky-hint bulk-sticky-hint--warning">
                                        Allocate to match due
                                    </span>
                                </div>
                            </div>
                            <div class="col-12 col-lg-5 text-center text-lg-end">
                                <button type="button" id="submitBulkPayment" class="btn btn-primary btn-lg px-5 shadow">
                                    <i class="fas fa-check"></i> Submit Payment
                                </button>
                            </div>
                        </div>
                    </div>
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



@vite('resources/js/bulk-payments/common.js')
@vite('resources/js/bulk-payments/sales.js')

<script>
    // Most page handlers moved into `resources/js/bulk-payments/sales.js`.
    // Returns selection logic moved into `resources/js/bulk-payments/sales.js`.

    // Normalization + return-credit bill-allocation updates moved into `resources/js/bulk-payments/sales.js`.
    function normalizeAllBillAllocationRows() {
        if (typeof window.normalizeAllBillAllocationRows === 'function' && window.normalizeAllBillAllocationRows !== normalizeAllBillAllocationRows) {
            return window.normalizeAllBillAllocationRows();
        }
    }
    window.normalizeAllBillAllocationRowsLegacy = normalizeAllBillAllocationRows;

    function updateExistingBillAllocationsForReturnCredits() {
        if (typeof window.updateExistingBillAllocationsForReturnCredits === 'function' &&
            window.updateExistingBillAllocationsForReturnCredits !== updateExistingBillAllocationsForReturnCredits) {
            return window.updateExistingBillAllocationsForReturnCredits();
        }
    }
    window.updateExistingBillAllocationsForReturnCreditsLegacy = updateExistingBillAllocationsForReturnCredits;

    // Update net customer due (after return credits)
    function updateNetCustomerDue() {
        // Forward to the Vite module implementation (keeps any remaining inline calls working).
        if (typeof window.updateNetCustomerDue === 'function' && window.updateNetCustomerDue !== updateNetCustomerDue) {
            return window.updateNetCustomerDue();
        }
    }

    // Expose for any legacy code still calling it.
    window.updateNetCustomerDueLegacy = updateNetCustomerDue;

    // Individual payment totals moved into `resources/js/bulk-payments/sales.js`.
    function updateIndividualPaymentTotal() {
        if (typeof window.updateIndividualPaymentTotal === 'function' && window.updateIndividualPaymentTotal !== updateIndividualPaymentTotal) {
            return window.updateIndividualPaymentTotal();
        }
    }
    window.updateIndividualPaymentTotalLegacy = updateIndividualPaymentTotal;

    // Global amount auto-apply moved into `resources/js/bulk-payments/sales.js`.

    // Amount formatting UX moved into `resources/js/bulk-payments/sales.js`.

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

    // Payment method UI toggling moved into `resources/js/bulk-payments/sales.js`.
    function togglePaymentFields() {
        if (typeof window.togglePaymentFields === 'function' && window.togglePaymentFields !== togglePaymentFields) {
            return window.togglePaymentFields();
        }
    }
    window.togglePaymentFieldsLegacy = togglePaymentFields;

    // Multi-Method Group Management
    let methodGroupCounter = 0;
    window.availableCustomerSales = window.availableCustomerSales || [];

    function addNewMethodGroup() {
        const customerId = $('#customerSelect').val();
        if (!customerId) {
            toastr.warning('Please select a customer first');
            return;
        }

        // Check if bills are available
        if (!window.availableCustomerSales || window.availableCustomerSales.length === 0) {
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
        console.log('Creating group with', window.availableCustomerSales.length, 'available sales');

        const salesOptions = window.availableCustomerSales.map(sale =>
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
                // CLEAR ALL OLD CUSTOMER DATA (centralized helper)
                if (typeof window.resetFlexiblePaymentSystem === 'function') {
                    window.resetFlexiblePaymentSystem();
                } else {
                    window.billPaymentAllocations = {};
                    window.paymentMethodAllocations = {};
                    if (window.billReturnCreditAllocations) window.billReturnCreditAllocations = {};
                    $('#flexiblePaymentsList').html(window.FLEXIBLE_PAYMENTS_EMPTY_HTML);
                    window.syncPaymentMethodsEmptyState();
                    $('#billsPaymentTableBody').empty();
                    updateSummaryTotals();
                }

                // Page-specific state
                flexiblePaymentCounter = 0;
                window.availableCustomerSales = [];
                window.lastLoadedSalesCustomerId = null;
                window.selectedReturns = [];

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
                    window.availableCustomerSales = response.data.filter(sale => {
                        const due = parseFloat(sale.total_due) || 0;
                        return due > 0.005;
                    });

                    console.log('Outstanding bills for flexible UI:', window.availableCustomerSales.length);

                    // Populate the bills list (UI will show appropriate message if empty)
                    populateFlexibleBillsList();

                    if ($('.return-checkbox:checked').length > 0) {
                        if (typeof window.updateSelectedReturns === 'function') {
                            window.updateSelectedReturns();
                        } else {
                            updateNetCustomerDue();
                            updateSummaryTotals();
                        }
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

    window.loadCustomerSalesForMultiMethod = loadCustomerSalesForMultiMethod;

    // Global variables for flexible system
    let flexiblePaymentCounter = 0;
    window.billPaymentAllocations = window.billPaymentAllocations || {}; // Track allocations per bill (shared with common.js)
    window.paymentMethodAllocations = window.paymentMethodAllocations || {}; // Track allocations per payment method (shared for modules)

    // Initialize system safety check
    function initializeFlexiblePaymentSystem() {
        if (typeof flexiblePaymentCounter === 'undefined') flexiblePaymentCounter = 0;
        if (typeof window.billPaymentAllocations === 'undefined') window.billPaymentAllocations = {};
        if (typeof window.paymentMethodAllocations === 'undefined') window.paymentMethodAllocations = {};
        if (typeof window.availableCustomerSales === 'undefined') window.availableCustomerSales = [];

        console.log('Flexible payment system initialized');
    }

    // escapeHtml → `resources/js/bulk-payments/common.js`; FLEXIBLE_PAYMENTS_EMPTY_HTML + syncPaymentMethodsEmptyState → `sales.js`

    // updatePaymentMethodHints moved to `resources/js/bulk-payments/sales.js`

    // updateReturnApplyHint → `resources/js/bulk-payments/sales.js`

    // recalcBillPaymentAllocationsFromUI → `resources/js/bulk-payments/common.js`

    function populateFlexibleBillsList(searchTerm = '') {
        window.recalcBillPaymentAllocationsFromUI();
        let billsHTML = '';

        // Filter bills based on search term (invoice number or notes)
        let filteredSales = window.availableCustomerSales;
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase().trim();
            filteredSales = window.availableCustomerSales.filter(sale => {
                const invoiceMatch = sale.invoice_no && sale.invoice_no.toLowerCase().includes(searchLower);
                const notesMatch = sale.sale_notes && sale.sale_notes.toLowerCase().includes(searchLower);
                const billIdMatch = sale.id && sale.id.toString().includes(searchLower);
                return invoiceMatch || notesMatch || billIdMatch;
            });
        }

        if (filteredSales.length === 0) {
            if (searchTerm && searchTerm.trim() !== '') {
                billsHTML = '<div class="alert alert-info text-center"><i class="fas fa-search"></i> No bills found matching "' + window.escapeHtml(searchTerm) + '"</div>';
            } else {
                billsHTML = '<div class="alert alert-warning text-center">No outstanding bills found</div>';
            }
        } else {
            filteredSales.forEach((sale) => {
                const allocatedAmount = window.billPaymentAllocations[sale.id] || 0;
                const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[sale.id] || 0) : 0;
                const totalAllocated = allocatedAmount + returnCreditApplied;
                const remainingAmount = sale.total_due - totalAllocated;
                const isFullyPaid = remainingAmount <= 0.01; // Small threshold for floating point
                const isPartiallyAllocated = allocatedAmount > 0.01 && remainingAmount > 0.01;
                const escapedNotes = window.escapeHtml(sale.sale_notes || '');

                billsHTML += `
                    <div class="bill-item bill-card border rounded-3 p-2 mb-2 ${isFullyPaid ? 'bill-card--settled' : ''}"
                         data-bill-id="${sale.id}"
                         data-invoice="${window.escapeHtml(sale.invoice_no)}"
                         data-notes="${escapedNotes}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-1">
                                    <div class="fw-semibold bill-card__title text-truncate ${isFullyPaid ? 'text-muted' : 'text-dark'}">
                                        ${window.escapeHtml(sale.invoice_no)}
                                    </div>
                                    ${isFullyPaid ? '<span class="badge rounded-pill border bill-badge bill-badge--muted">Allocated</span>' : ''}
                                    ${isPartiallyAllocated ? '<span class="badge rounded-pill border bill-badge bill-badge--muted">Partial</span>' : ''}
                                    ${returnCreditApplied > 0 ? '<span class="badge rounded-pill border bill-badge bill-badge--secondary">Credit</span>' : ''}
                                </div>

                                <div class="d-flex flex-wrap gap-2 mt-1 bill-card__meta">
                                    <span class="text-muted">Due: <strong class="text-dark">Rs.${formatAmountValue(Number(sale.total_due || 0))}</strong></span>
                                    <span class="text-muted">Remaining: <strong class="${remainingAmount > 0.01 ? 'text-primary' : 'text-success'}">Rs.${formatAmountValue(Math.max(0, remainingAmount))}</strong></span>
                                    ${allocatedAmount > 0.01 ? `<span class="text-muted">Added: <strong class="text-dark">Rs.${formatAmountValue(allocatedAmount)}</strong></span>` : ''}
                                    ${returnCreditApplied > 0 ? `<span class="text-muted">Credit: <strong class="text-dark">Rs.${formatAmountValue(returnCreditApplied)}</strong></span>` : ''}
                                </div>

                                ${escapedNotes ? `<div class="text-muted small mt-1 text-truncate bill-card__notes" title="${escapedNotes}">${escapedNotes}</div>` : ''}
                            </div>

                            <div class="flex-shrink-0">
                                ${!isFullyPaid ? `
                                    <button type="button" class="btn btn-primary btn-sm add-to-payment-btn bill-card__cta" data-bill-id="${sale.id}" title="Allocate this bill to a payment method">
                                        Allocate
                                    </button>
                                ` : `
                                    <span class="btn btn-success btn-sm disabled bill-card__done" aria-disabled="true" title="Settled">
                                        <i class="fas fa-check"></i>
                                    </span>
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

    // Expose for shared helpers (e.g. common.js credit sync)
    window.populateFlexibleBillsList = populateFlexibleBillsList;

    function getNextPaymentMethodIndex() {
        const used = new Set();
        $('#flexiblePaymentsList .pm-index-badge').each(function () {
            const n = parseInt($(this).text(), 10);
            if (Number.isFinite(n) && n > 0) used.add(n);
        });
        let next = 1;
        while (used.has(next)) next++;
        return next;
    }

    // Add new flexible payment method
    function addFlexiblePayment() {
        flexiblePaymentCounter++;
        const paymentId = `payment_${flexiblePaymentCounter}`;
        const paymentIndex = getNextPaymentMethodIndex();

        const paymentHTML = `
            <div class="payment-method-item border rounded p-2 mb-2 bg-white" data-payment-id="${paymentId}" data-payment-index="${paymentIndex}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="text-dark mb-0">
                        <span class="pm-index-badge me-2" aria-hidden="true">${paymentIndex}</span>
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
        window.syncPaymentMethodsEmptyState();

        // Initialize payment method allocations
        window.paymentMethodAllocations[paymentId] = {
            method: '',
            totalAmount: 0,
            billAllocations: {}
        };

        updateSummaryTotals();
    }

    // Add bill allocation to a payment method (ENHANCED WITH BILL STATUS TRACKING AND NOTES)
    function addBillAllocation(paymentId) {
        // Filter bills - exclude fully paid (including return credit) and show remaining amounts
        const availableBills = window.availableCustomerSales.filter(sale => {
            const allocatedAmount = window.billPaymentAllocations[sale.id] || 0;
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
            const allocatedAmount = window.billPaymentAllocations[sale.id] || 0;
            const returnCreditApplied = window.billReturnCreditAllocations ? (window.billReturnCreditAllocations[sale.id] || 0) : 0;
            const remainingAmount = sale.total_due - allocatedAmount - returnCreditApplied;
            return `<option value="${sale.id}" data-due="${sale.total_due}" data-invoice="${window.escapeHtml(sale.invoice_no)}" data-remaining="${Math.max(0, remainingAmount)}" data-notes="${window.escapeHtml(sale.sale_notes || '')}">${window.escapeHtml(sale.invoice_no)} · Pay now Rs.${formatAmountValue(Math.max(0, remainingAmount))}</option>`;
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

    window.updateCalculatedAmountDisplay = updateCalculatedAmountDisplay;

    // updateWorkflowProgress moved to `resources/js/bulk-payments/sales.js`

    // updateSummaryTotals moved to `resources/js/bulk-payments/sales.js`

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
                window.billPaymentAllocations[billId] = Math.max(0, (window.billPaymentAllocations[billId] || 0) - amount);
            }
        });
        $billAllocationsList.empty();

        let remainingAmount = amountToDistribute;
        let billIndex = 0;

        // Sort bills by date (FIFO - oldest first)
        const sortedBills = [...window.availableCustomerSales].sort((a, b) => {
            return new Date(a.sales_date) - new Date(b.sales_date);
        });

        // Auto-select bills until amount is exhausted
        for (const bill of sortedBills) {
            if (remainingAmount <= 0.01) break;

            const allocatedAmount = window.billPaymentAllocations[bill.id] || 0;
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
                window.billPaymentAllocations[bill.id] = (window.billPaymentAllocations[bill.id] || 0) + amountForThisBill;

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
            if (window.paymentMethodAllocations[paymentId]) {
                window.paymentMethodAllocations[paymentId].totalAmount = totalAmount;
            }
            updateSummaryTotals();
        } else {
            if (window.paymentMethodAllocations[paymentId]) {
                window.paymentMethodAllocations[paymentId].totalAmount = billsTotal;
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

    // Expose for shared helpers (e.g. common.js credit sync)
    window.updatePaymentMethodTotal = updatePaymentMethodTotal;

    // submitMultiMethodPayment moved to `resources/js/bulk-payments/sales.js`

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
                    window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) - amount;
                    if (window.billPaymentAllocations[billId] <= 0) {
                        delete window.billPaymentAllocations[billId];
                    }
                }
            });

            // Remove from payment method allocations
            if (window.paymentMethodAllocations[paymentId]) {
                delete window.paymentMethodAllocations[paymentId];
            }

            $paymentContainer.fadeOut(300, function() {
                $(this).remove();
                window.syncPaymentMethodsEmptyState();
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
                if (!window.paymentMethodAllocations[paymentId]) {
                    window.paymentMethodAllocations[paymentId] = {
                        method: '',
                        totalAmount: 0,
                        billAllocations: {}
                    };
                }

                window.paymentMethodAllocations[paymentId].method = method;

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
                window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) - amount;
                if (window.billPaymentAllocations[billId] <= 0) {
                    delete window.billPaymentAllocations[billId];
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
                // This ensures window.paymentMethodAllocations[paymentId].totalAmount is recalculated
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
                const bill = window.availableCustomerSales.find(s => s.id == billId);
                const allocatedAmount = window.billPaymentAllocations[billId] || 0;
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

            const bill = window.availableCustomerSales.find(s => s.id == billId);
            if (!bill) return;

            // Calculate available amount for this bill (including return credits)
            const prevAmount = $(this).data('prev-amount') || 0;
            const currentAllocation = window.billPaymentAllocations[billId] || 0;
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
            window.billPaymentAllocations[billId] = (currentAllocation - prevAmount) + amount;
            $(this).data('prev-amount', amount);

            // If bill becomes fully paid, remove from available bills
            const remainingAfterPayment = bill.total_due - window.billPaymentAllocations[billId] - returnCreditApplied;

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
                if (window.billPaymentAllocations[billId] <= 0) {
                    delete window.billPaymentAllocations[billId];
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
                if (!window.paymentMethodAllocations[paymentId]) {
                    window.paymentMethodAllocations[paymentId] = {
                        method: '',
                        totalAmount: 0,
                        billAllocations: {}
                    };
                }

                // Check if this is a system update (to prevent recursion)
                const isSystemUpdate = $(this).data('system-update');

                if (!isSystemUpdate) {
                    window.paymentMethodAllocations[paymentId].totalAmount = totalAmount;

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
                                    window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) - amount;
                                    if (window.billPaymentAllocations[billId] <= 0) {
                                        delete window.billPaymentAllocations[billId];
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

                            // Step 2: Set window.billPaymentAllocations to only include OTHER payment methods
                            window.billPaymentAllocations = otherPaymentsAllocations;

                            // Step 3: Clear existing allocations for THIS payment method
                            $paymentContainer.find('.bill-allocation-row').each(function() {
                                const $row = $(this);
                                const billId = $row.find('.bill-select').val();
                                const amount = parseAmountValue($row.find('.allocation-amount').val());
                                if (billId && amount > 0.01) {
                                    // Already removed by setting window.billPaymentAllocations = otherPaymentsAllocations above
                                }
                            });

                            // Step 4: Clear bill allocations list for THIS payment method
                            $paymentContainer.find('.bill-allocations-list').empty();

                            // Step 5: Auto-distribute to bills in FIFO order
                            // At this point, window.billPaymentAllocations contains ONLY OTHER payment methods
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

                            // Recalculate window.billPaymentAllocations from UI to include the newly created allocations
                            window.recalcBillPaymentAllocationsFromUI();

                            populateFlexibleBillsList();
                            updateSummaryTotals();
                        } else {
                            // Amount is 0 or cleared - clear all bill allocations
                            $paymentContainer.find('.bill-allocation-row').each(function() {
                                const $row = $(this);
                                const billId = $row.find('.bill-select').val();
                                const amount = parseAmountValue($row.find('.allocation-amount').val());

                                if (billId && amount > 0) {
                                    window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) - amount;
                                    if (window.billPaymentAllocations[billId] <= 0) {
                                        delete window.billPaymentAllocations[billId];
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
                                const bill = window.availableCustomerSales.find(s => s.id == billId);
                                if (bill) {
                                    const previousAllocated = window.billPaymentAllocations[billId] || 0;
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
                                window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) - prevAmount;
                                if (window.billPaymentAllocations[billId] <= 0) {
                                    delete window.billPaymentAllocations[billId];
                                }
                                $amountInput.data('prev-amount', 0);
                            }
                        });

                        // Recalculate available amounts after clearing previous allocations
                        billsToUpdate.forEach(billInfo => {
                            const bill = window.availableCustomerSales.find(s => s.id == billInfo.billId);
                            if (bill) {
                                const currentAllocated = window.billPaymentAllocations[billInfo.billId] || 0;
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
                                window.billPaymentAllocations[billInfo.billId] = (window.billPaymentAllocations[billInfo.billId] || 0) + amountToAllocate;
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
                window.paymentMethodAllocations[paymentId].totalAmount = actualAllocated;

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
            const bill = window.availableCustomerSales.find(s => s.id == billId);

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
            const allocatedAmount = window.billPaymentAllocations[billId] || 0;
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
                const otherAllocations = Math.max(0, (window.billPaymentAllocations[billId] || 0) - currentAmount);
                const maxForRow = Math.max(0, parseAmountValue(bill.total_due) - returnCreditApplied - otherAllocations);
                const newAmount = Math.min(maxForRow, currentAmount + remainingAmount);

                $existingInput.data('system-update', true);
                $existingInput.val(formatAmountValue(newAmount));
                $existingInput.data('prev-amount', newAmount);

                window.billPaymentAllocations[billId] = otherAllocations + newAmount;

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
            window.billPaymentAllocations[billId] = (window.billPaymentAllocations[billId] || 0) + amountValue;

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
    /* Keep Bootstrap defaults; only lightly tune density */
    .bulk-payment-page {
        background: #f7f8fa;
        min-height: 100vh;
        font-size: 0.95rem;
    }

    /* Reduce "grey empty space" by grouping into white cards */
    .bulk-section-card {
        background: #fff;
        border: 1px solid #e6e9ee;
        border-radius: 12px;
        padding: 12px;
    }

    @media (min-width: 992px) {
        .bulk-section-card {
            padding: 14px;
        }
    }

    #bulkPaymentForm .form-label {
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    #bulkPaymentForm .form-control,
    #bulkPaymentForm .form-select {
        min-height: 38px;
    }

    #bulkPaymentForm textarea.form-control {
        min-height: 38px;
    }

    #bulkPaymentForm h6 {
        font-size: 0.95rem;
    }

    #netCustomerDue {
        font-size: 1.5rem !important;
        letter-spacing: -0.01em;
    }

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

    #customerReturnsSection .table th,
    #customerReturnsSection .table td {
        padding-top: 0.3rem;
        padding-bottom: 0.3rem;
    }

    #customerReturnsSection h6 {
        font-size: 0.9rem;
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

    .bulk-sticky-inline {
        font-size: 0.9rem;
        line-height: 1.2;
    }

    .bulk-sticky-inline__metrics {
        padding: 0.35rem 0.55rem;
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        border-radius: 0.6rem;
        color: #111827;
        white-space: nowrap;
    }

    .bulk-sticky-hint {
        padding: 0.35rem 0.55rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.6rem;
        font-weight: 600;
        white-space: nowrap;
        color: #111827;
    }

    .bulk-payment-submit-bar {
        backdrop-filter: blur(6px);
    }

    .bulk-payment-submit-bar .btn.btn-lg {
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }

    /* hint backgrounds */

    .bulk-sticky-hint--warning {
        background: #fffbeb;
    }
    .bulk-sticky-hint--success {
        background: #ecfdf5;
    }
    .bulk-sticky-hint--danger {
        background: #fef2f2;
    }

    /* Bills list: modern cards */
    .bill-card {
        transition: transform 120ms ease, box-shadow 120ms ease, border-color 120ms ease;
        border-color: #e6e9ee !important;
        background: #fff;
    }

    .bill-card:hover {
        border-color: #cfd6df !important;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        transform: translateY(-1px);
    }

    .bill-card--settled {
        background: #fbfcfd;
        opacity: 0.92;
    }

    .bill-card__title {
        display: inline-block;
        max-width: 100%;
        font-size: 0.95rem;
    }

    .bill-card__meta {
        font-size: 0.85rem;
        line-height: 1.2;
    }

    .bill-card__notes {
        max-width: 48ch;
    }

    .bill-badge {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
    }

    .bill-badge--muted {
        background: #f8fafc;
        color: #111827;
        border-color: #e5e7eb !important;
    }

    .bill-badge--secondary {
        background: #f3f4f6;
        color: #111827;
        border-color: #e5e7eb !important;
    }

    .bill-card__cta {
        border-radius: 0.6rem;
        padding: 0.45rem 0.75rem;
    }

    .bill-card__done {
        border-radius: 0.6rem;
        padding: 0.45rem 0.6rem;
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

    .pm-index-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.4rem;
        height: 1.4rem;
        border-radius: 999px;
        background: #111827;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1;
        flex: 0 0 auto;
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

    /* Don't globally fade all links; keep hover specific */
    .bulk-payment-page a:hover {
        text-decoration-thickness: 2px;
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
