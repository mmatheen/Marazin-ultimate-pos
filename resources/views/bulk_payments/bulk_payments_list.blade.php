@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title"><i class="fas fa-money-bill-wave text-primary"></i> Payment Management Hub</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('add-sale-bulk-payments') }}">Bulk Payments</a></li>
                            <li class="breadcrumb-item active">Manage All Payments</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="collapse show" id="collapseExample">
                    <div class="card card-body mb-3 py-3">
                        <form id="filterForm">
                            <div class="student-group-form">
                                <div class="row align-items-end">
                                    <div class="col-lg-4 col-md-6">
                                        <div class="form-group local-forms mb-0">
                                            <label>Payment Type <span class="login-danger">*</span></label>
                                            <select id="entity_type" name="entity_type" class="form-control select" required>
                                                <option value="">Select Payment Type</option>
                                                <option value="sale">Sale Payments (Customer payments for invoices)</option>
                                                <option value="purchase">Purchase Payments (Payments to suppliers)</option>
                                                <option value="opening_balance">Opening Balance Payments</option>
                                                <option value="return">Return Payments (Refunds & returns)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-6">
                                        <div class="form-group local-forms mb-0">
                                            <label id="contact_filter_label">Filter by Customer</label>
                                            <select id="contact_filter" name="contact_filter" class="form-control select">
                                                <option value="">All Customers</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <div class="alert alert-info mb-0 py-2" role="alert" style="font-size: 0.85rem;">
                                            <strong>Recent Active Payments</strong>
                                            <small class="d-block">Showing yesterday and today. You can only edit TODAY's payments.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Payments Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="page-title">Bulk Payments List</h3>
                                </div>
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <a href="{{ route('add-sale-bulk-payments') }}" class="btn btn-primary mr-2">
                                        <i class="fas fa-plus"></i> Add Sale Payment
                                    </a>
                                    <a href="{{ route('add-purchase-bulk-payments') }}" class="btn btn-primary mr-2">
                                        <i class="fas fa-plus"></i> Add Purchase Payment
                                    </a>
                                    <button type="button" id="viewLogsBtn" class="btn btn-outline-info">
                                        <i class="fas fa-history"></i> View Logs
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-sm" id="paymentsTable" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Reference</th>
                                        <th>Contact</th>
                                        <th>Bill/Invoice</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_payment_id" name="payment_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group local-forms">
                                <label>Amount <span class="login-danger">*</span></label>
                                <input type="number" id="edit_amount" name="amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group local-forms">
                                <label>Payment Date <span class="login-danger">*</span></label>
                                <input type="datetime-local" id="edit_payment_date" name="payment_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group local-forms">
                                <label>Payment Method <span class="login-danger">*</span></label>
                                <select id="edit_payment_method" name="payment_method" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Allow Advance Payment Option -->
                        <div class="col-md-12">
                            <div class="form-group local-forms">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_allow_advance" name="allow_advance" value="1">
                                    <label class="form-check-label" for="edit_allow_advance">
                                        <strong>Allow Advance Payment</strong>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-info-circle"></i> Check this if customer is paying MORE than the bill amount.
                                            Excess amount will be stored as customer credit for future purchases.
                                        </small>
                                    </label>
                                </div>
                            </div>
                            <div id="edit_advance_info" class="alert alert-info" style="display: none;">
                                <i class="fas fa-calculator"></i>
                                <strong>Advance Payment Details:</strong>
                                <div id="edit_advance_calculation"></div>
                            </div>
                        </div>

                        <!-- Card Details (shown only when payment method is card) -->
                        <div id="edit_card_details" style="display: none;" class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Card Number</label>
                                        <input type="text" id="edit_card_number" name="card_number" class="form-control" placeholder="xxxx xxxx xxxx xxxx">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Card Holder Name</label>
                                        <input type="text" id="edit_card_holder_name" name="card_holder_name" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Expiry Month</label>
                                        <input type="text" id="edit_card_expiry_month" name="card_expiry_month" class="form-control" placeholder="MM">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Expiry Year</label>
                                        <input type="text" id="edit_card_expiry_year" name="card_expiry_year" class="form-control" placeholder="YYYY">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cheque Details (shown only when payment method is cheque) -->
                        <div id="edit_cheque_details" style="display: none;" class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Cheque Number</label>
                                        <input type="text" id="edit_cheque_number" name="cheque_number" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Bank & Branch</label>
                                        <input type="text" id="edit_cheque_bank_branch" name="cheque_bank_branch" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Received Date</label>
                                        <input type="date" id="edit_cheque_received_date" name="cheque_received_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Valid Date</label>
                                        <input type="date" id="edit_cheque_valid_date" name="cheque_valid_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group local-forms">
                                        <label>Given By</label>
                                        <input type="text" id="edit_cheque_given_by" name="cheque_given_by" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group local-forms">
                                <label>Notes</label>
                                <textarea id="edit_notes" name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group local-forms">
                                <label>Reason for Edit <span class="login-danger">*</span></label>
                                <textarea id="edit_reason" name="reason" class="form-control" rows="2" placeholder="Please provide reason for editing this payment..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Payment Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" role="dialog" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel">Delete Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deletePaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="delete_payment_id" name="payment_id">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning!</strong> This action cannot be undone. The payment will be permanently deleted and the related sale/purchase balance will be updated.
                    </div>
                    <div class="form-group local-forms">
                        <label>Reason for Deletion <span class="login-danger">*</span></label>
                        <textarea id="delete_reason" name="reason" class="form-control" rows="3" placeholder="Please provide a detailed reason for deleting this payment..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-trash"></i> Delete Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="logsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logsModalLabel">Bulk Payment Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="logsTable" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Contact</th>
                                <th>Performed By</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <!-- Logs will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Excel-like compact table styling */
    #paymentsTable {
        font-size: 0.875rem;
    }

    #paymentsTable thead th {
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }

    #paymentsTable tbody td {
        padding: 0.35rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.875rem;
    }

    #paymentsTable tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* DataTable row grouping styling */
    #paymentsTable tr.group {
        background-color: #e9ecef !important;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
    }

    #paymentsTable tr.group td {
        padding: 0.6rem 0.75rem;
    }

    #paymentsTable tr.group:hover {
        background-color: #dee2e6 !important;
    }

    /* Compact badge styling */
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    /* Compact button styling */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>

<script>
$(document).ready(function() {
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    let currentEntityType = '';
    let paymentsDataTable = null;

    // Initialize DataTable with row grouping
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
            $('#paymentsTable').DataTable().destroy();
        }

        paymentsDataTable = $('#paymentsTable').DataTable({
            "order": [[0, 'desc']], // Sort by date descending
            "pageLength": 50,
            "responsive": true,
            "columnDefs": [
                { "visible": false, "targets": 2 } // Hide contact column (used for grouping)
            ],
            "drawCallback": function (settings) {
                var api = this.api();
                var rows = api.rows({ page: 'current' }).nodes();
                var last = null;

                api.column(2, { page: 'current' }).data().each(function (group, i) {
                    if (last !== group) {
                        // Calculate group total by iterating through current page rows
                        var groupTotal = 0;
                        var groupCount = 0;

                        api.rows({ page: 'current' }).every(function() {
                            var data = this.data();
                            if (data[2] === group) {
                                // Extract numeric value from HTML formatted amount
                                // Amount is in format: <strong>Rs. 17,050.00</strong>
                                var amountText = data[4];
                                var amountMatch = amountText.match(/Rs\.\s*([\d,]+\.?\d*)/);
                                if (amountMatch) {
                                    var numericAmount = parseFloat(amountMatch[1].replace(/,/g, ''));
                                    groupTotal += numericAmount;
                                }
                                groupCount++;
                            }
                        });

                        $(rows).eq(i).before(
                            '<tr class="group"><td colspan="7" style="background-color: #e9ecef; font-weight: 600;"><i class="fas fa-user-circle"></i> ' +
                            group +
                            ' <span class="badge bg-secondary ms-2">' + groupCount + ' payment(s)</span>' +
                            ' <span class="float-end text-success">Total: Rs. ' + groupTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span></td></tr>'
                        );

                        last = group;
                    }
                });
            },
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ records",
                "info": "Showing _START_ to _END_ of _TOTAL_ payments",
                "emptyTable": "No payments found. Please select a payment type."
            }
        });

        // Toggle group visibility on click
        $('#paymentsTable tbody').on('click', 'tr.group', function() {
            var currentOrder = paymentsDataTable.order()[0];
            if (currentOrder[0] === 2 && currentOrder[1] === 'asc') {
                paymentsDataTable.order([2, 'desc']).draw();
            } else {
                paymentsDataTable.order([2, 'asc']).draw();
            }
        });
    }

    // Initialize table on page load
    initializeDataTable();

    // Initialize contact filter as disabled until payment type is selected
    $('#contact_filter').prop('disabled', true);

    // Auto-load payments when payment type changes (onChange event)
    $('#entity_type').on('change', function() {
        const selectedType = $(this).val();
        showPaymentTypeInfo(selectedType);
        loadPayments();
    });

    // Helper function to convert datetime to Asia/Colombo timezone
    function convertToColombeDatetime(dateString) {
        if (!dateString) return '';

        try {
            // Create date object from the string
            let date = new Date(dateString);

            // Check if date is valid
            if (isNaN(date.getTime())) {
                console.warn('Invalid date:', dateString);
                return '';
            }

            // Convert to Asia/Colombo timezone (UTC+5:30)
            // Method 1: Using toLocaleString with timezone
            let colomboDate = new Date(date.toLocaleString("en-US", {timeZone: "Asia/Colombo"}));

            // Format for datetime-local input
            let year = colomboDate.getFullYear();
            let month = String(colomboDate.getMonth() + 1).padStart(2, '0');
            let day = String(colomboDate.getDate()).padStart(2, '0');
            let hours = String(colomboDate.getHours()).padStart(2, '0');
            let minutes = String(colomboDate.getMinutes()).padStart(2, '0');

            return `${year}-${month}-${day}T${hours}:${minutes}`;
        } catch (error) {
            console.error('Error converting date:', error);
            // Fallback: simple format without timezone conversion
            let date = new Date(dateString);
            let year = date.getFullYear();
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let day = String(date.getDate()).padStart(2, '0');
            let hours = String(date.getHours()).padStart(2, '0');
            let minutes = String(date.getMinutes()).padStart(2, '0');

            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }
    }

    // Show payment type information
    function showPaymentTypeInfo(type) {
        const descriptions = {
            'sale': '<strong>Sale Payments:</strong> These are payments received from customers for invoices/sales. Includes cash, card, cheque, and bank transfer payments.',
            'purchase': '<strong>Purchase Payments:</strong> These are payments made to suppliers for purchases/bills. Includes all outgoing payments to vendors.',
            'opening_balance': '<strong>Opening Balance Payments:</strong> These are initial balance adjustments for customers and suppliers when setting up the system.',
            'return': '<strong>Return Payments:</strong> These are refund payments for sale returns and purchase returns. Includes customer refunds and supplier returns.'
        };

        if (type && descriptions[type]) {
            $('#payment_type_description').html(descriptions[type]);
            $('#payment_type_info').slideDown();
        } else {
            $('#payment_type_info').slideUp();
        }
    }

    // Remove manual form submission - using onChange instead
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        // Prevent form submission, onChange handles it
    });

    // Load payments function - ONLY TODAY'S PAYMENTS
    function loadPayments() {
        const entityType = $('#entity_type').val();
        const contactId = $('#contact_filter').val();

        if (!entityType) {
            toastr.warning('Please select payment type', 'Select Type');
            return;
        }

        currentEntityType = entityType;

        // Get today's date in YYYY-MM-DD format
        const today = new Date();
        const todayStr = today.getFullYear() + '-' +
            String(today.getMonth() + 1).padStart(2, '0') + '-' +
            String(today.getDate()).padStart(2, '0');

        // For testing, let's also include yesterday's payments to show more data
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterdayStr = yesterday.getFullYear() + '-' +
            String(yesterday.getMonth() + 1).padStart(2, '0') + '-' +
            String(yesterday.getDate()).padStart(2, '0');

        const formData = {
            entity_type: entityType,
            start_date: yesterdayStr,  // Include yesterday for testing
            end_date: todayStr        // Through today
        };

        // Add contact filter if selected - handle the special supplier_ prefix
        if (contactId) {
            if (contactId.startsWith('supplier_')) {
                // Extract supplier ID from 'supplier_123' format
                formData.supplier_id = contactId.replace('supplier_', '');
            } else if (entityType === 'sale') {
                formData.customer_id = contactId;
            } else if (entityType === 'purchase') {
                formData.supplier_id = contactId;
            } else {
                // For opening_balance and return, determine if it's customer or supplier
                if (contactId.startsWith('supplier_')) {
                    formData.supplier_id = contactId.replace('supplier_', '');
                } else {
                    formData.customer_id = contactId;
                }
            }
        }

        console.log('Loading payments with filters:', formData);
        console.log('Date range:', yesterdayStr, 'to', todayStr);

        $.ajax({
            url: '{{ route("bulk.payments.list") }}',
            method: 'GET',
            data: formData,
            beforeSend: function() {
                toastr.info('Loading payments...', 'Please Wait');
            },
            success: function(response) {
                toastr.clear();
                console.log('Payments response:', response); // Debug log

                if (response.status === 200 && response.data && response.data.length > 0) {
                    populatePaymentsTable(response.data, response.entity_type);
                    toastr.success(`Found ${response.data.length} payments`, 'Success');
                } else {
                    populatePaymentsTable([], entityType);
                    toastr.info('No payments found for the selected criteria', 'No Data');
                    console.log('No payments found. Response:', response); // Debug log
                }
            },
            error: function(xhr, status, error) {
                toastr.clear();
                console.error('AJAX Error:', xhr, status, error);

                if (xhr.status === 401) {
                    toastr.error('Unauthorized access. Please check your permissions.', 'Permission Error');
                } else if (xhr.status === 403) {
                    toastr.error('Forbidden. You do not have permission to view bulk payments.', 'Permission Error');
                } else if (xhr.status === 404) {
                    toastr.error('Bulk payments endpoint not found.', 'Route Error');
                } else if (xhr.status === 500) {
                    toastr.error('Server error occurred while loading payments.', 'Server Error');
                } else {
                    toastr.error('Error loading payments: ' + (xhr.responseJSON?.message || 'Unknown error'), 'Error');
                }

                populatePaymentsTable([], entityType);
            }
        });
    }

    // Populate payments table with DataTable row grouping
    function populatePaymentsTable(payments, entityType) {
        console.log('Populating table with payments:', payments);

        // Clear existing data
        if (paymentsDataTable) {
            paymentsDataTable.clear();
        }

        if (payments.length === 0) {
            paymentsDataTable.draw();
            return;
        }

        payments.forEach(function(payment) {
            // Get contact name for grouping
            let contactName = '';
            if (payment.customer) {
                contactName = payment.customer.first_name + ' ' + payment.customer.last_name + ' (Customer)';
            } else if (payment.supplier) {
                contactName = payment.supplier.name + ' (Supplier)';
            } else {
                contactName = 'Unknown Contact';
            }

            // Get invoice/bill reference
            let billRef = '-';
            if (payment.sale && payment.sale.invoice_no) {
                billRef = payment.sale.invoice_no;
            } else if (payment.purchase && payment.purchase.reference_no) {
                billRef = payment.purchase.reference_no;
            } else if (payment.reference_no) {
                billRef = payment.reference_no;
            }

            // Check if payment is from today
            const paymentDate = new Date(payment.payment_date);
            const today = new Date();
            const isToday = paymentDate.toDateString() === today.toDateString();

            // Format payment method
            let methodBadge = '';
            switch(payment.payment_method) {
                case 'cash':
                    methodBadge = '<span class="badge bg-primary">Cash</span>';
                    break;
                case 'card':
                    methodBadge = '<span class="badge bg-info">Card</span>';
                    break;
                case 'cheque':
                    methodBadge = '<span class="badge bg-warning text-dark">Cheque</span>';
                    break;
                case 'bank_transfer':
                    methodBadge = '<span class="badge bg-dark">Bank</span>';
                    break;
                default:
                    methodBadge = `<span class="badge bg-secondary">${payment.payment_method}</span>`;
            }

            // Action buttons
            let actionsHtml = '';
            if (isToday) {
                actionsHtml = `
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary edit-payment-btn" data-id="${payment.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger delete-payment-btn" data-id="${payment.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </a>
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-info view-payment-logs-btn" data-id="${payment.id}" data-reference="${payment.reference_no}" title="Logs">
                        <i class="fas fa-history"></i>
                    </a>
                `;
            } else {
                actionsHtml = `
                    <span class="badge bg-secondary" title="Locked"><i class="fas fa-lock"></i></span>
                    <a href="javascript:void(0);" class="btn btn-sm btn-outline-info view-payment-logs-btn" data-id="${payment.id}" data-reference="${payment.reference_no}" title="Logs">
                        <i class="fas fa-history"></i>
                    </a>
                `;
            }

            const rowData = [
                '<div style="line-height: 1.3;"><strong>' + paymentDate.toLocaleDateString('en-IN') + '</strong><br><span class="text-muted" style="font-size: 0.75rem;">' + paymentDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</span></div>',
                '<code style="font-size: 0.8rem;">' + (payment.reference_no || '-') + '</code>',
                contactName, // This column will be hidden and used for grouping
                '<strong>' + billRef + '</strong>',
                '<strong>Rs. ' + parseFloat(payment.amount).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>',
                methodBadge,
                '<span class="badge bg-success"><i class="fas fa-check"></i> Paid</span>',
                actionsHtml
            ];

            paymentsDataTable.row.add(rowData);
        });

        paymentsDataTable.draw();
    }

    // Quick search functionality (removed since DataTable has search)

    // Clear search (removed)

    // Handle edit payment
    $(document).on('click', '.edit-payment-btn', function() {
        const paymentId = $(this).data('id');

        $.ajax({
            url: `/bulk-payment/${paymentId}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    const payment = response.payment;

                    // Double-check that payment is from today before allowing edit
                    const paymentDate = new Date(payment.payment_date);
                    const today = new Date();
                    const isToday = paymentDate.toDateString() === today.toDateString();

                    if (!isToday) {
                        toastr.error('Cannot edit past payments. Only TODAY\'s payments can be edited to maintain ledger integrity.', 'Edit Restricted');
                        return;
                    }

                    populateEditModal(payment);
                    var editModal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                    editModal.show();
                } else {
                    toastr.error('Error loading payment details', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'Error');
            }
        });
    });

    // Populate edit modal
    function populateEditModal(payment) {
        $('#edit_payment_id').val(payment.id);
        $('#edit_amount').val(payment.amount);

        // Use the helper function to convert datetime to Asia/Colombo timezone
        let paymentDateTime = convertToColombeDatetime(payment.payment_date);
        $('#edit_payment_date').val(paymentDateTime);

        $('#edit_payment_method').val(payment.payment_method);
        $('#edit_notes').val(payment.notes || '');
        $('#edit_reason').val('');

        // Populate card details
        $('#edit_card_number').val(payment.card_number || '');
        $('#edit_card_holder_name').val(payment.card_holder_name || '');
        $('#edit_card_expiry_month').val(payment.card_expiry_month || '');
        $('#edit_card_expiry_year').val(payment.card_expiry_year || '');

        // Populate cheque details
        $('#edit_cheque_number').val(payment.cheque_number || '');
        $('#edit_cheque_bank_branch').val(payment.cheque_bank_branch || '');
        $('#edit_cheque_received_date').val(payment.cheque_received_date || '');
        $('#edit_cheque_valid_date').val(payment.cheque_valid_date || '');
        $('#edit_cheque_given_by').val(payment.cheque_given_by || '');

        // Reset advance payment checkbox
        $('#edit_allow_advance').prop('checked', false);
        $('#edit_advance_info').hide();

        // Store payment details for advance calculation
        window.currentEditPayment = payment;

        // Show/hide appropriate fields based on payment method
        toggleEditPaymentMethodFields(payment.payment_method);
    }

    // Toggle card/cheque fields based on payment method
    function toggleEditPaymentMethodFields(method) {
        $('#edit_card_details').hide();
        $('#edit_cheque_details').hide();

        if (method === 'card') {
            $('#edit_card_details').show();
        } else if (method === 'cheque') {
            $('#edit_cheque_details').show();
        }
    }

    // Handle payment method change in edit modal
    $('#edit_payment_method').on('change', function() {
        toggleEditPaymentMethodFields($(this).val());
    });

    // Handle advance payment checkbox
    $('#edit_allow_advance').on('change', function() {
        calculateAdvancePayment();
    });

    // Handle amount change to recalculate advance
    $('#edit_amount').on('input', function() {
        if ($('#edit_allow_advance').is(':checked')) {
            calculateAdvancePayment();
        }
    });

    // Calculate and display advance payment details
    function calculateAdvancePayment() {
        const isAdvanceAllowed = $('#edit_allow_advance').is(':checked');
        const paymentAmount = parseFloat($('#edit_amount').val()) || 0;

        if (!isAdvanceAllowed || !window.currentEditPayment) {
            $('#edit_advance_info').hide();
            return;
        }

        // Get payment details from stored data
        const payment = window.currentEditPayment;

        // Fetch current sale/purchase details via AJAX
        $.ajax({
            url: `/bulk-payment/${payment.id}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    const paymentData = response.payment;
                    const entity = response.entity;

                    // Calculate total payments excluding current one
                    const totalOtherPayments = parseFloat(entity.total_paid || 0) - parseFloat(paymentData.amount || 0);
                    const totalAmount = parseFloat(entity.total_amount || 0);
                    const maxAllowedForBill = totalAmount - totalOtherPayments;

                    if (paymentAmount > maxAllowedForBill) {
                        const advanceAmount = paymentAmount - maxAllowedForBill;
                        const billPayment = maxAllowedForBill;

                        $('#edit_advance_calculation').html(`
                            <div class="row mt-2">
                                <div class="col-6"><strong>Total Payment Amount:</strong></div>
                                <div class="col-6 text-end">Rs. ${paymentAmount.toFixed(2)}</div>

                                <div class="col-6"><strong>Applied to Bill:</strong></div>
                                <div class="col-6 text-end text-success">Rs. ${billPayment.toFixed(2)}</div>

                                <div class="col-6"><strong>Advance (Customer Credit):</strong></div>
                                <div class="col-6 text-end text-primary"><strong>Rs. ${advanceAmount.toFixed(2)}</strong></div>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i>
                                The advance amount will be added to customer's credit balance and can be used for future purchases.
                            </small>
                        `);
                        $('#edit_advance_info').show();
                    } else {
                        $('#edit_advance_info').hide();
                    }
                }
            }
        });
    }

    // Handle edit form submission
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();

        const paymentId = $('#edit_payment_id').val();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        $.ajax({
            url: `/bulk-payment/${paymentId}`,
            method: 'PUT',
            data: data,
            beforeSend: function() {
                toastr.info('Updating payment...', 'Please Wait');
            },
            success: function(response) {
                toastr.clear();
                if (response.status === 200) {
                    var editModal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                    editModal.hide();
                    toastr.success('Payment updated successfully!', 'Success');
                    loadPayments(); // Reload the table
                } else {
                    toastr.error(response.message || 'Error updating payment', 'Error');
                }
            },
            error: function(xhr) {
                // Clear all toastr notifications immediately
                toastr.remove();
                toastr.clear();

                // Try to parse structured error message
                let errorData = null;
                let errorMessage = 'Unknown error';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    try {
                        // Try to parse JSON error structure
                        errorData = JSON.parse(xhr.responseJSON.message);
                    } catch (e) {
                        // If not JSON, use as plain message
                        errorMessage = xhr.responseJSON.message;
                    }
                }

                // Delay slightly to ensure toastr is fully cleared
                setTimeout(function() {
                    // If we have structured error data, show in SweetAlert
                    if (errorData && errorData.title) {
                        let detailsHtml = '';

                        // Build details HTML
                        if (errorData.details) {
                            detailsHtml += '<div style="text-align: left; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #dc3545;">';
                            detailsHtml += '<strong style="display: block; margin-bottom: 10px; color: #495057;">Payment Details:</strong>';
                            for (let key in errorData.details) {
                                detailsHtml += '<div style="margin: 8px 0; padding: 5px 0; border-bottom: 1px solid #dee2e6;"><strong>' + key + ':</strong> <span style="float: right; color: #dc3545;">' + errorData.details[key] + '</span></div>';
                            }
                            detailsHtml += '</div>';
                        }

                        // Add tip if exists
                        if (errorData.tip) {
                            detailsHtml += '<div style="margin-top: 15px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px; text-align: left;">';
                            detailsHtml += '<strong style="color: #856404;">💡 Tip:</strong> <span style="color: #856404;">' + errorData.tip + '</span>';
                            detailsHtml += '</div>';
                        }

                        // Show SweetAlert v1
                        swal({
                            title: errorData.title,
                            text: (errorData.message || '') + detailsHtml,
                            html: true,
                            type: 'error',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545',
                            customClass: 'swal-wide'
                        });
                    } else {
                        // Fallback to regular SweetAlert for simple errors
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                        }

                        swal({
                            title: 'Payment Update Failed',
                            text: errorMessage,
                            html: true,
                            type: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }, 100);
            }
        });
    });

    // Handle delete payment
    $(document).on('click', '.delete-payment-btn', function() {
        const paymentId = $(this).data('id');

        // First verify the payment can be deleted (today's payment only)
        $.ajax({
            url: `/bulk-payment/${paymentId}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    const payment = response.payment;

                    // Check if payment is from today
                    const paymentDate = new Date(payment.payment_date);
                    const today = new Date();
                    const isToday = paymentDate.toDateString() === today.toDateString();

                    if (!isToday) {
                        toastr.error('Cannot delete past payments. Only TODAY\'s payments can be deleted to maintain ledger integrity.', 'Delete Restricted');
                        return;
                    }

                    // Show delete modal
                    $('#delete_payment_id').val(paymentId);
                    $('#delete_reason').val('');
                    var deleteModal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
                    deleteModal.show();
                } else {
                    toastr.error('Error loading payment details', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unable to verify payment'), 'Error');
            }
        });
    });

    // Handle delete form submission
    $('#deletePaymentForm').on('submit', function(e) {
        e.preventDefault();

        const paymentId = $('#delete_payment_id').val();
        const reason = $('#delete_reason').val();

        $.ajax({
            url: `/bulk-payment/${paymentId}`,
            method: 'DELETE',
            data: { reason: reason },
            beforeSend: function() {
                toastr.info('Deleting payment...', 'Please Wait');
            },
            success: function(response) {
                toastr.clear();
                if (response.status === 200) {
                    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                    deleteModal.hide();
                    toastr.success('Payment deleted successfully!', 'Success');
                    loadPayments(); // Reload the table
                } else {
                    toastr.error('Error deleting payment');
                }
            },
            error: function(xhr) {
                toastr.clear();
                toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Handle view logs
    $('#viewLogsBtn').on('click', function() {
        // Reset modal title to show all logs
        $('#logsModalLabel').text('Bulk Payment Logs - All Payments');
        loadLogs();
        var logsModal = new bootstrap.Modal(document.getElementById('logsModal'));
        logsModal.show();
    });

    // Handle individual payment view logs button
    $(document).on('click', '.view-payment-logs-btn', function() {
        const paymentId = $(this).data('id');
        const referenceNo = $(this).data('reference');

        console.log('Viewing logs for Payment ID:', paymentId, 'Reference:', referenceNo);

        // Update modal title to show specific payment
        $('#logsModalLabel').text(`Payment Logs - ${referenceNo}`);

        // Load logs filtered by this specific payment ID
        $.ajax({
            url: '{{ route("bulk.payment.logs") }}',
            method: 'GET',
            data: {
                entity_type: currentEntityType || null,
                payment_id: paymentId  // Use payment_id instead of reference_no
            },
            success: function(response) {
                if (response.status === 200) {
                    const logs = response.data.data || [];
                    if (logs.length > 0) {
                        populateLogsTable(logs);
                        var logsModal = new bootstrap.Modal(document.getElementById('logsModal'));
                        logsModal.show();
                        toastr.success(`Found ${logs.length} log(s) for payment #${paymentId}`, 'Logs Loaded');
                    } else {
                        toastr.info('No edit/delete logs found for this payment', 'No Logs');
                    }
                } else {
                    toastr.info('No logs found for this payment', 'No Logs');
                }
            },
            error: function(xhr) {
                toastr.error('Error loading logs: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Handle entity type change to load customers/suppliers for filter
    $('#entity_type').on('change', function() {
        const entityType = $(this).val();
        const contactFilter = $('#contact_filter');
        const contactFilterLabel = $('#contact_filter_label');

        if (!entityType) {
            contactFilterLabel.text('Filter by Customer/Supplier');
            contactFilter.html('<option value="">Select payment type first</option>');
            contactFilter.prop('disabled', true);
            return;
        }

        // Update label based on entity type
        switch(entityType) {
            case 'sale':
                contactFilterLabel.html('<i class="fas fa-user"></i> Filter by Customer');
                contactFilter.html('<option value="">👥 All Customers</option>');
                break;
            case 'purchase':
                contactFilterLabel.html('<i class="fas fa-building"></i> Filter by Supplier');
                contactFilter.html('<option value="">🏢 All Suppliers</option>');
                break;
            case 'opening_balance':
                contactFilterLabel.html('<i class="fas fa-users"></i> Filter by Contact');
                contactFilter.html('<option value="">📊 All Contacts (Customers & Suppliers)</option>');
                break;
            case 'return':
                contactFilterLabel.html('<i class="fas fa-users"></i> Filter by Contact');
                contactFilter.html('<option value="">↩️ All Return Contacts</option>');
                break;
            default:
                contactFilterLabel.html('<i class="fas fa-users"></i> Filter by Contact');
                contactFilter.html('<option value="">Select payment type first</option>');
        }

        // Enable the dropdown
        contactFilter.prop('disabled', false);

        // Show loading state
        contactFilter.append('<option disabled>Loading...</option>');

        // Load customers or suppliers based on entity type
        let url;
        switch(entityType) {
            case 'sale':
                url = '/customer-get-all';
                break;
            case 'purchase':
                url = '/supplier-get-all';
                break;
            case 'opening_balance':
            case 'return':
                // For opening balance and returns, load both customers and suppliers
                url = '/customer-get-all'; // We'll load customers first, then suppliers
                break;
            default:
                url = '/customer-get-all';
        }

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                // Remove loading option
                contactFilter.find('option:contains("Loading...")').remove();

                // API returns data in 'message' field, not 'data'
                const contacts = response.message || response.data || [];

                if (response.status === 200 && contacts.length > 0) {
                    contacts.forEach(function(contact) {
                        // Both customers and suppliers have first_name and last_name
                        const firstName = contact.first_name || '';
                        const lastName = contact.last_name || '';
                        const name = (firstName + ' ' + lastName).trim();
                        const code = contact.contact_code ? ` (${contact.contact_code})` : '';
                        contactFilter.append(`<option value="${contact.id}">${name}${code}</option>`);
                    });
                    const contactType = entityType === 'sale' ? 'customers' :
                                       entityType === 'purchase' ? 'suppliers' : 'contacts';
                    toastr.success(`Loaded ${contacts.length} ${contactType}`, 'Success');

                    // For opening balance and return payments, also load suppliers if we just loaded customers
                    if ((entityType === 'opening_balance' || entityType === 'return') && url.includes('customer')) {
                        loadSuppliers(contactFilter);
                    }
                } else {
                    toastr.info(`No ${entityType === 'sale' ? 'customers' : 'suppliers'} found`, 'Info');
                }
            },
            error: function(xhr) {
                // Remove loading option
                contactFilter.find('option:contains("Loading...")').remove();
                console.error('Error loading contacts:', xhr);
                const contactType = entityType === 'sale' ? 'customers' :
                                   entityType === 'purchase' ? 'suppliers' : 'contacts';
                toastr.error(`Failed to load ${contactType}`, 'Error');
            }
        });
    });

    // Helper function to load suppliers for opening balance and return payments
    function loadSuppliers(contactFilter) {
        $.ajax({
            url: '/supplier-get-all',
            method: 'GET',
            success: function(response) {
                const suppliers = response.message || response.data || [];

                if (response.status === 200 && suppliers.length > 0) {
                    // Add a separator
                    contactFilter.append('<option disabled>--- Suppliers ---</option>');

                    suppliers.forEach(function(supplier) {
                        const name = supplier.name || 'Unnamed Supplier';
                        const code = supplier.contact_code ? ` (${supplier.contact_code})` : '';
                        contactFilter.append(`<option value="supplier_${supplier.id}">${name}${code} [Supplier]</option>`);
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading suppliers:', xhr);
            }
        });
    }

    // Handle contact filter change
    $('#contact_filter').on('change', function() {
        if ($('#entity_type').val()) {
            loadPayments();
        }
    });



    // Load logs function
    function loadLogs() {
        $.ajax({
            url: '{{ route("bulk.payment.logs") }}',
            method: 'GET',
            data: {
                entity_type: currentEntityType || null
            },
            success: function(response) {
                if (response.status === 200) {
                    populateLogsTable(response.data.data);
                }
            },
            error: function(xhr) {
                toastr.error('Error loading logs: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    }

    // Populate logs table
    function populateLogsTable(logs) {
        const tbody = $('#logsTableBody');
        tbody.empty();

        // Debug: Log first entry to see structure
        if (logs.length > 0) {
            console.log('First log entry:', logs[0]);
            console.log('Performed by data:', logs[0].performed_by);
        }

        logs.forEach(function(log) {
            const contactName = log.customer
                ? log.customer.first_name + ' ' + log.customer.last_name
                : (log.supplier ? log.supplier.name : 'N/A');

            const actionBadge = log.action === 'edit'
                ? '<span class="badge badge-primary">Edit</span>'
                : '<span class="badge badge-secondary">Delete</span>';

            const typeBadge = log.entity_type === 'sale'
                ? '<span class="badge badge-primary">Sale</span>'
                : '<span class="badge badge-primary">Purchase</span>';

            const amount = log.action === 'edit'
                ? `Rs. ${parseFloat(log.old_amount).toFixed(2)} → Rs. ${parseFloat(log.new_amount).toFixed(2)}`
                : `Rs. ${parseFloat(log.old_amount).toFixed(2)}`;

            // Get performed_by data - try multiple field names
            let performedByName = 'N/A';
            if (log.performed_by && typeof log.performed_by === 'object') {
                performedByName = log.performed_by.full_name || log.performed_by.name || log.performed_by.user_name || log.performed_by.email || 'User #' + log.performed_by.id;
            } else if (log.performed_by_id) {
                performedByName = 'User #' + log.performed_by_id;
            }

            const row = `
                <tr>
                    <td>${new Date(log.performed_at).toLocaleDateString()}</td>
                    <td>${actionBadge}</td>
                    <td>${typeBadge}</td>
                    <td>${amount}</td>
                    <td>${contactName}</td>
                    <td>${performedByName}</td>
                    <td>${log.reason || 'N/A'}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Reset edit modal when closed
    $('#editPaymentModal').on('hidden.bs.modal', function () {
        $('#editPaymentForm')[0].reset();
        $('#edit_card_details').hide();
        $('#edit_cheque_details').hide();
    });

    // Checkbox toggle functionality
    function toggleSelectAll(checkbox) {
        const checkboxes = document.querySelectorAll('.payment-checkbox');
        checkboxes.forEach(cb => cb.checked = checkbox.checked);
    }

    // Make toggle function global
    window.toggleSelectAll = toggleSelectAll;
});
</script>

<style>
/* Custom styling for SweetAlert error popup */
.swal-wide .sweet-alert {
    width: 600px !important;
    max-width: 90% !important;
}

.sweet-alert p {
    text-align: left !important;
}

.sweet-alert .sa-error-container {
    margin-top: 20px;
}

/* Enhanced UI Styling */
.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}

.page-title i {
    margin-right: 8px;
}

.form-group label i {
    margin-right: 5px;
    color: #6c757d;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.badge i {
    margin-right: 4px;
}

/* Payment type specific colors */
.badge.bg-success {
    background-color: #28a745 !important;
}

.badge.bg-primary {
    background-color: #007bff !important;
}

.badge.bg-info {
    background-color: #17a2b8 !important;
}

.badge.bg-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

/* Table enhancements */
.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
    color: #495057;
    vertical-align: middle;
    padding: 12px 8px;
}

.table td {
    vertical-align: middle;
    padding: 12px 8px;
}

.table th i {
    color: #6c757d;
    margin-right: 5px;
}

/* Standard table styling */
.datatable {
    border-collapse: separate;
    border-spacing: 0;
}

.datatable thead th {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 10;
}

.datatable tbody td {
    border: 1px solid #dee2e6;
    border-top: none;
}

/* Action buttons styling */
.actions {
    display: flex;
    gap: 4px;
    justify-content: flex-start;
    flex-wrap: wrap;
}

.actions .btn {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.2;
}

/* Checkbox styling */
.payment-checkbox, #allchecked {
    width: 16px;
    height: 16px;
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .actions {
        justify-content: center;
    }

    .actions .btn {
        padding: 2px 6px;
        font-size: 11px;
    }

    .table td, .table th {
        padding: 8px 4px;
        font-size: 13px;
    }
}

/* Alert enhancements */
.alert-primary {
    background-color: #cce7ff;
    border-color: #b3d9ff;
    color: #004085;
}

.alert-light {
    background-color: #f8f9fa;
    border-color: #e9ecef;
    color: #6c757d;
}

/* Button group spacing */
.btn-group .btn {
    margin-right: 2px;
}

/* Select dropdown enhancements */
select.form-control option {
    padding: 8px 12px;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

@endsection

