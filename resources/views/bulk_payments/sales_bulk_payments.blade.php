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
</style>
@endsection

@section('content')
<div class="container-fluid">
    <form id="bulkPaymentForm">
        <input id="sale_id" name="sale_id" type="hidden">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Sale Bulk Payment</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('manage-bulk-payments') }}">Bulk payments</a></li>
                                <li class="breadcrumb-item active">Add Sale Payments</li>
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
   
   
        <div class="card mb-4 shadow-sm rounded">
            <div class="card-body">
                <div class="form-group">
                    <label for="customerSelect">Select Customer</label>
                    <select id="customerSelect" class="form-control selectBox">
                        <option value="">Select Customer</option>
                    </select>
                </div>
                <div class="row mt-3">
                    <div class="col-md-2">
                        <div class="card bg-warning p-3 rounded text-center shadow-sm">
                            <strong>Opening Balance:</strong>
                            <span id="openingBalance" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-success p-3 rounded text-center shadow-sm">
                            <strong>Total Sales:</strong>
                            <span id="totalSalesAmount" class="d-block mt-2">Rs. 0.00</span>
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
                            <strong>Sale Due Amount:</strong>
                            <span id="totalDueAmount" class="d-block mt-2">Rs. 0.00</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-danger p-3 rounded text-center shadow-sm">
                            <strong>Total Customer Due:</strong>
                            <span id="totalCustomerDue" class="d-block mt-2">Rs. 0.00</span>
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
                                    <small class="d-block text-muted">Settle customer's opening balance (not related to any sale)</small>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentType" id="paySaleDues" value="sale_dues" checked>
                                <label class="form-check-label" for="paySaleDues">
                                    <strong>Pay Sale Dues</strong>
                                    <small class="d-block text-muted">Pay against specific sales invoices</small>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentType" id="payBoth" value="both">
                                <label class="form-check-label" for="payBoth">
                                    <strong>Pay Both (Opening Balance + Sale Dues)</strong>
                                    <small class="d-block text-muted">First settle opening balance, then apply to sales</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="salesListContainer" class="mt-4">
                    <table id="salesList" class="table table-striped" style="margin-bottom: 70px; margin-top: 30px">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
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
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" name="payment_method" onchange="togglePaymentFields()">
                                <option value="cash" selected>Cash</option>
                                <option value="card">Credit Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="paidOn" class="form-label">Paid On</label>
                            <input class="form-control datetimepicker" type="text" name="payment_date" id="paidOn" placeholder="DD-MM-YYYY">
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
                                <option value="mastercard">MasterCard</option>
                                <option value="amex">American Express</option>
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
                            <input type="text" class="form-control datetimepicker" id="cheque_received_date" name="cheque_received_date">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                            <input type="text" class="form-control datetimepicker" id="cheque_valid_date" name="cheque_valid_date">
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
        <button type="button" id="submitBulkPayment" class="btn btn-primary">Submit Payment</button>
    </form>
</diV>
@include('sell.sales_ajax')

<script>
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
                response.message.forEach(function(customer) {
                    // Skip walk-in customer (customer ID 1)
                    if (customer.id === 1) {
                        return;
                    }
                    
                    // Calculate total due amount
                    var openingBalance = parseFloat(customer.opening_balance) || 0;
                    var saleDue = parseFloat(customer.total_sale_due) || 0;
                    var currentDue = parseFloat(customer.current_due) || 0;
                    
                    // Only show customers who have due amounts
                    if (currentDue > 0) {
                        var lastName = customer.last_name ? customer.last_name : '';
                        var fullName = customer.first_name + (lastName ? ' ' + lastName : '');
                        var displayText = fullName + ' (Due: Rs. ' + currentDue.toFixed(2) + ')';
                        if (openingBalance > 0) {
                            displayText += ' [Opening: Rs. ' + openingBalance.toFixed(2) + ']';
                        }
                        
                        customerSelect.append(
                            '<option value="' + customer.id +
                            '" data-opening-balance="' + openingBalance +
                            '" data-sale-due="' + saleDue +
                            '" data-total-due="' + currentDue +
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
    
    // Set today's date as default for "Paid On" field in DD-MM-YYYY format
    var today = new Date();
    var todayFormatted = String(today.getDate()).padStart(2, '0') + '-' + 
        String(today.getMonth() + 1).padStart(2, '0') + '-' + 
        today.getFullYear();
    $('input[name="payment_date"]').val(todayFormatted);
    console.log('Set default date to today (DD-MM-YYYY):', todayFormatted);
    
    // Initialize DataTable for sales list
    if ($('#salesList').length > 0 && typeof $.fn.DataTable !== 'undefined') {
        $('#salesList').DataTable({
            columns: [
                { title: "Sale ID (Invoice)" },
                { title: "Final Total" },
                { title: "Total Paid" },
                { title: "Total Due" },
                { title: "Payment Amount" }
            ],
            paging: true,
            searching: true,
            ordering: true
        });
        console.log('DataTable initialized for sales list');
    }
    
    // Load customers immediately
    setTimeout(function() {
        console.log('Loading customers for separate page...');
        loadCustomersForBulkPayment();
    }, 1000);
});

// Customer selection change handler for separate page
$(document).on('change', '#customerSelect', function() {
    console.log('Customer selected on separate page...');
    
    var selectedOption = $(this).find(':selected');
    var customerId = $(this).val();
    
    if (!customerId) {
        console.log('No customer selected');
        return;
    }
    
    console.log('Selected customer ID:', customerId);
    
    // Get customer data from the selected option
    var customerOpeningBalance = parseFloat(selectedOption.data('opening-balance')) || 0;
    var saleDue = parseFloat(selectedOption.data('sale-due')) || 0;
    var totalDue = parseFloat(selectedOption.data('total-due')) || 0;
    
    console.log('Customer balances:', {
        openingBalance: customerOpeningBalance,
        saleDue: saleDue,
        totalDue: totalDue
    });
    
    // Update balance cards
    $('#openingBalance').text('Rs. ' + customerOpeningBalance.toFixed(2));
    $('#totalCustomerDue').text('Rs. ' + totalDue.toFixed(2));
    
    // Store original opening balance globally
    window.originalOpeningBalance = customerOpeningBalance;
    window.saleDueAmount = saleDue;
    
    // Reset and clear previous validation errors
    $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
    $('#globalPaymentAmount').val('');
    
    // Load sales for the selected customer
    loadCustomerSales(customerId);
});

// Function to load sales for selected customer
function loadCustomerSales(customerId) {
    console.log('Loading sales for customer:', customerId);
    
    $.ajax({
        url: '/sales',
        method: 'GET',
        dataType: 'json',
        data: {
            customer_id: customerId
        },
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Sales response:', response);
            
            if (response.sales && response.sales.length > 0) {
                var salesTableBody = $('#salesList tbody');
                salesTableBody.empty();
                
                var totalSalesAmount = 0,
                    totalPaidAmount = 0,
                    totalDueAmount = 0;
                
                response.sales.forEach(function(sale) {
                    if (sale.customer_id == customerId && sale.status === 'final') { // Only final sales
                        var finalTotal = parseFloat(sale.final_total) || 0;
                        var totalPaid = parseFloat(sale.total_paid) || 0;
                        var totalDue = parseFloat(sale.total_due) || 0;
                        
                        // Include all sales (both paid and due) in totals
                        totalSalesAmount += finalTotal;
                        totalPaidAmount += totalPaid;
                        totalDueAmount += totalDue;
                        
                        // Only add to table if there's a due amount
                        if (totalDue > 0) {
                            var row = '<tr>' +
                                '<td>' + sale.id + ' (' + sale.invoice_no + ')</td>' +
                                '<td>' + finalTotal.toFixed(2) + '</td>' +
                                '<td>' + totalPaid.toFixed(2) + '</td>' +
                                '<td>' + totalDue.toFixed(2) + '</td>' +
                                '<td><input type="number" class="form-control reference-amount" data-reference-id="' + sale.id + '" min="0" max="' + totalDue + '" step="0.01" placeholder="0.00" value="0"></td>' +
                                '</tr>';
                            
                            salesTableBody.append(row);
                        }
                    }
                });
                
                console.log('Totals calculated:', {
                    totalSalesAmount: totalSalesAmount,
                    totalPaidAmount: totalPaidAmount,
                    totalDueAmount: totalDueAmount
                });
                
                // Update summary cards
                $('#totalSalesAmount').text('Rs. ' + totalSalesAmount.toFixed(2));
                $('#totalPaidAmount').text('Rs. ' + totalPaidAmount.toFixed(2));
                $('#totalDueAmount').text('Rs. ' + totalDueAmount.toFixed(2));
                
                // Store totalDueAmount for validation purposes
                window.saleDueAmount = totalDueAmount;
                
                // Update global payment amount max and placeholder
                $('#globalPaymentAmount').attr('max', totalDueAmount);
                $('#globalPaymentAmount').attr('placeholder', 'Max: Rs. ' + totalDueAmount.toFixed(2));
                
            } else {
                console.log('No sales found for customer');
                $('#salesList tbody').empty();
                $('#salesList tbody').append('<tr><td colspan="5" class="text-center">No pending sales found for this customer</td></tr>');
                
                // Reset totals
                $('#totalSalesAmount').text('Rs. 0.00');
                $('#totalPaidAmount').text('Rs. 0.00');
                $('#totalDueAmount').text('Rs. 0.00');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading customer sales:', error);
            $('#salesList tbody').empty();
            $('#salesList tbody').append('<tr><td colspan="5" class="text-center text-danger">Error loading sales data</td></tr>');
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
    var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
    if (globalAmount > 0) {
        $('#globalPaymentAmount').trigger('input');
    }
});

// Handle global payment amount input - AUTO-APPLY to sales
$(document).on('input', '#globalPaymentAmount', function() {
    var globalAmount = parseFloat($(this).val()) || 0;
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

// Handle bulk payment submission for separate page
$(document).on('click', '#submitBulkPayment', function() {
    console.log('Submit bulk payment button clicked (separate page)');
    
    var customerId = $('#customerSelect').val();
    var paymentMethod = $('#paymentMethod').val();
    
    console.log('Customer ID:', customerId);
    console.log('Payment Method raw:', paymentMethod);
    
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
    var customerOpeningBalance = parseFloat($('#customerSelect').find(':selected').data('opening-balance')) || 0;
    var totalDueAmount = parseFloat($('#totalDueAmount').text().replace('Rs. ', '')) || 0;
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
            $('#totalDueAmount').text('Rs. 0.00');
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
</script>
@endsection