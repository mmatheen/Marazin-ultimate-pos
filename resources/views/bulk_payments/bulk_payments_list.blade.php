@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Bulk Payment Management</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('add-sale-bulk-payments') }}">Bulk Payments</a></li>
                            <li class="breadcrumb-item active">Manage Payments</li>
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
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <form id="filterForm">
                            <div class="student-group-form">
                                <div class="row align-items-end">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Payment Type <span class="login-danger">*</span></label>
                                            <select id="entity_type" name="entity_type" class="form-control select" required>
                                                <option value="">Select Type</option>
                                                <option value="sale">Sale Payments</option>
                                                <option value="purchase">Purchase Payments</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Start Date</label>
                                            <input type="date" id="start_date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group local-forms">
                                            <label>End Date</label>
                                            <input type="date" id="end_date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group local-forms mb-0">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <button type="button" id="resetBtn" class="btn btn-secondary">
                                                <i class="fas fa-sync"></i> Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h3 class="page-title">Bulk Payments List</h3>
                                    <p class="text-muted mb-0">Select a payment type and date range to view payments</p>
                                </div>
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <a href="{{ route('add-sale-bulk-payments') }}" class="btn btn-primary mr-2">
                                        <i class="fas fa-plus"></i> Add Sale Payment
                                    </a>
                                    <a href="{{ route('add-purchase-bulk-payments') }}" class="btn btn-primary mr-2">
                                        <i class="fas fa-plus"></i> Add Purchase Payment
                                    </a>
                                    <button type="button" id="viewLogsBtn" class="btn btn-secondary">
                                        <i class="fas fa-history"></i> View Logs
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="paymentsTable" class="table border-0 star-student table-hover table-center mb-0 datatable table-striped">
                                <thead class="student-thread">
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Contact</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
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

    let paymentsDataTable;
    let currentEntityType = '';

    // Initialize DataTable
    function initializeDataTable() {
        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
            $('#paymentsTable').DataTable().destroy();
        }
        
        paymentsDataTable = $('#paymentsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[0, "desc"]], // Sort by date descending
            "pageLength": 25,
            "language": {
                "search": "Search payments:",
                "lengthMenu": "Show _MENU_ payments per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ payments",
                "infoEmpty": "No payments found",
                "emptyTable": "No payments available"
            }
        });
    }

    // Initialize table on page load
    initializeDataTable();

    // Don't auto-load payments - require user to select payment type first

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        loadPayments();
    });

    // Reset button
    $('#resetBtn').on('click', function() {
        $('#filterForm')[0].reset();
        $('#start_date').val('{{ date("Y-m-d") }}');
        $('#end_date').val('{{ date("Y-m-d") }}');
        // Clear the table when resetting
        if (paymentsDataTable) {
            paymentsDataTable.clear().draw();
        }
        toastr.info('Filters reset. Please select a payment type to view payments.', 'Reset');
    });

    // Load payments function
    function loadPayments() {
        const entityType = $('#entity_type').val();
        
        if (!entityType) {
            toastr.error('Please select payment type');
            return;
        }

        currentEntityType = entityType;
        
        const formData = {
            entity_type: entityType,
            start_date: $('#start_date').val(),
            end_date: $('#end_date').val()
        };

        $.ajax({
            url: '{{ route("bulk.payments.list") }}',
            method: 'GET',
            data: formData,
            beforeSend: function() {
                toastr.info('Loading payments...', 'Please Wait');
            },
            success: function(response) {
                toastr.clear();
                
                if (response.status === 200 && response.data && response.data.length > 0) {
                    populatePaymentsTable(response.data, response.entity_type);
                    toastr.success(`Found ${response.data.length} payments`, 'Success');
                } else {
                    populatePaymentsTable([], entityType);
                    toastr.info('No payments found for the selected criteria', 'No Data');
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

    // Populate payments table
    function populatePaymentsTable(payments, entityType) {
        console.log('Populating table with payments:', payments);
        
        // Clear existing data
        if ($.fn.DataTable.isDataTable('#paymentsTable')) {
            paymentsDataTable.clear();
        }

        payments.forEach(function(payment) {
            const contactName = entityType === 'sale' 
                ? (payment.customer ? payment.customer.first_name + ' ' + payment.customer.last_name : 'N/A')
                : (payment.supplier ? payment.supplier.name : 'N/A');

            const entityInfo = entityType === 'sale' 
                ? (payment.sale ? payment.sale.invoice_no : 'N/A')
                : (payment.purchase ? payment.purchase.reference_no : 'N/A');

            const rowData = [
                new Date(payment.payment_date).toLocaleDateString(),
                payment.reference_no || entityInfo,
                contactName,
                'Rs. ' + parseFloat(payment.amount).toFixed(2),
                `<span class="badge badge-primary">${payment.payment_method}</span>`,
                `<span class="badge badge-success">Paid</span>`,
                `<div class="actions">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-payment-btn" data-id="${payment.id}" title="Edit Payment">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary delete-payment-btn ml-1" data-id="${payment.id}" title="Delete Payment">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`
            ];

            paymentsDataTable.row.add(rowData);
        });

        paymentsDataTable.draw();
    }

    // Handle edit payment
    $(document).on('click', '.edit-payment-btn', function() {
        const paymentId = $(this).data('id');
        
        $.ajax({
            url: `/bulk-payment/${paymentId}/edit`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    populateEditModal(response.payment);
                    var editModal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                    editModal.show();
                } else {
                    toastr.error('Error loading payment details');
                }
            },
            error: function(xhr) {
                toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Populate edit modal
    function populateEditModal(payment) {
        $('#edit_payment_id').val(payment.id);
        $('#edit_amount').val(payment.amount);
        $('#edit_payment_date').val(payment.payment_date.replace(' ', 'T'));
        $('#edit_payment_method').val(payment.payment_method);
        $('#edit_notes').val(payment.notes || '');
        $('#edit_reason').val('');
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
                    toastr.error('Error updating payment');
                }
            },
            error: function(xhr) {
                toastr.clear();
                toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Handle delete payment
    $(document).on('click', '.delete-payment-btn', function() {
        const paymentId = $(this).data('id');
        $('#delete_payment_id').val(paymentId);
        $('#delete_reason').val('');
        var deleteModal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
        deleteModal.show();
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
        loadLogs();
        var logsModal = new bootstrap.Modal(document.getElementById('logsModal'));
        logsModal.show();
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

            const row = `
                <tr>
                    <td>${new Date(log.performed_at).toLocaleDateString()}</td>
                    <td>${actionBadge}</td>
                    <td>${typeBadge}</td>
                    <td>${amount}</td>
                    <td>${contactName}</td>
                    <td>${log.performed_by ? log.performed_by.name : 'N/A'}</td>
                    <td>${log.reason || 'N/A'}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }
});
</script>
@endsection

