@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Cheque Management</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Cheque Management</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Important Information Alert -->
    @if(($stats['total_bounced'] ?? 0) > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Enhanced Cheque Bounce Management</h5>
                <p class="mb-2">
                    <strong>Important:</strong> When cheques are marked as bounced, the system now maintains:
                </p>
                <ul class="mb-2">
                    <li><strong>Bills remain "PAID"</strong> - Original transactions are not affected</li>
                    <li><strong>Floating Balance</strong> - Bounced amounts are tracked separately as customer debt</li>
                    <li><strong>Recovery System</strong> - Use the recovery button to record when customers pay back bounced amounts</li>
                    <li><strong>Customer Risk Management</strong> - System automatically tracks bounce history for future decisions</li>
                </ul>
                <hr>
                <p class="mb-0">
                    <strong>Current Status:</strong> Rs. {{ number_format($stats['total_bounced'] ?? 0, 2) }} in floating balance from {{ $cheques->where('cheque_status', 'bounced')->count() }} bounced cheques.
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Pending</h6>
                            <h4 class="mb-0">Rs. {{ number_format($stats['total_pending'] ?? 0, 2) }}</h4>
                            <small>At Risk</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Cleared</h6>
                            <h4 class="mb-0">Rs. {{ number_format($stats['total_cleared'] ?? 0, 2) }}</h4>
                            <small>Safe</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Bounced</h6>
                            <h4 class="mb-0">Rs. {{ number_format($stats['total_bounced'] ?? 0, 2) }}</h4>
                            <small>Floating Balance</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-times-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Due Soon</h6>
                            <h4 class="mb-0">{{ $stats['due_soon_count'] ?? 0 }}</h4>
                            <small>Follow-up</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Overdue</h6>
                            <h4 class="mb-0">{{ $stats['overdue_count'] ?? 0 }}</h4>
                            <small>Urgent</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Success</h6>
                            <h4 class="mb-0">
                                @php
                                    $total = ($stats['total_cleared'] ?? 0) + ($stats['total_bounced'] ?? 0);
                                    $rate = $total > 0 ? round((($stats['total_cleared'] ?? 0) / $total) * 100, 1) : 0;
                                @endphp
                                {{ $rate }}%
                            </h4>
                            <small>Rate</small>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Filter Cheques</h5>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-control" id="statusFilter" name="status">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="deposited">Deposited</option>
                                <option value="cleared">Cleared</option>
                                <option value="bounced">Bounced</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fromDate" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="fromDate" name="from_date">
                        </div>
                        <div class="col-md-2">
                            <label for="toDate" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="toDate" name="to_date">
                        </div>
                        <div class="col-md-3">
                            <label for="customerFilter" class="form-label">Customer</label>
                            <select class="form-control" id="customerFilter" name="customer_id">
                                <option value="">All Customers</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="chequeNumberFilter" class="form-label">Cheque Number</label>
                            <input type="text" class="form-control" id="chequeNumberFilter" name="cheque_number" placeholder="Search cheque...">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions & Data Table -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Cheque List</h5>
                        <div>
                            <button type="button" class="btn btn-success btn-sm me-2" id="bulkClear" disabled>
                                <i class="fas fa-check"></i> Mark as Cleared
                            </button>
                            <button type="button" class="btn btn-info btn-sm me-2" id="bulkDeposit" disabled>
                                <i class="fas fa-university"></i> Mark as Deposited
                            </button>
                            <button type="button" class="btn btn-danger btn-sm me-2" id="bulkBounce" disabled>
                                <i class="fas fa-times"></i> Mark as Bounced
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="refreshData">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <span id="selectedCount">0</span> cheques selected
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="chequesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Cheque #</th>
                                    <th>Bank/Branch</th>
                                    <th>Amount</th>
                                    <th>Received Date</th>
                                    <th>Valid Date</th>
                                    <th>Status</th>
                                    <th>Bill Status</th>
                                    <th>Customer Impact</th>
                                    <th>Days Until Due</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="chequesTableBody">
                                @forelse($cheques as $payment)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input cheque-checkbox" value="{{ $payment->id }}">
                                    </td>
                                    <td>
                                        <strong>{{ $payment->sale->invoice_no ?? 'N/A' }}</strong>
                                    </td>
                                    <td>{{ $payment->customer->full_name ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="badge bg-info">{{ $payment->cheque_number ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $payment->cheque_bank_branch ?? 'N/A' }}</td>
                                    <td>
                                        <strong class="text-success">Rs. {{ number_format($payment->amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        {{ $payment->cheque_received_date ? \Carbon\Carbon::parse($payment->cheque_received_date)->format('d-m-Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        {{ $payment->cheque_valid_date ? \Carbon\Carbon::parse($payment->cheque_valid_date)->format('d-m-Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'deposited' => 'info', 
                                                'cleared' => 'success',
                                                'bounced' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $color = $statusColors[$payment->cheque_status ?? 'pending'] ?? 'light';
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ ucfirst($payment->cheque_status ?? 'pending') }}</span>
                                        @if(($payment->cheque_status ?? 'pending') === 'bounced')
                                            <br><small class="text-danger">Bank Charges: Rs. {{ number_format($payment->bank_charges ?? 0, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- NEW: Bill Status Column --}}
                                        @if($payment->sale)
                                            @php
                                                $billStatusColor = $payment->sale->payment_status === 'Paid' ? 'success' : 
                                                    ($payment->sale->payment_status === 'Partial' ? 'warning' : 'danger');
                                            @endphp
                                            <span class="badge bg-{{ $billStatusColor }}">{{ $payment->sale->payment_status }}</span>
                                            @if(($payment->cheque_status ?? 'pending') === 'bounced' && $payment->sale->payment_status === 'Paid')
                                                <br><small class="text-success"><i class="fas fa-info-circle"></i> Bill Remains Settled</small>
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- NEW: Customer Impact Column --}}
                                        @if(($payment->cheque_status ?? 'pending') === 'bounced')
                                            <span class="badge bg-warning">Floating Balance</span>
                                            <br><small class="text-danger">+Rs. {{ number_format($payment->amount + ($payment->bank_charges ?? 0), 2) }}</small>
                                            @if($payment->customer)
                                                <br><a href="/floating-balance/customer/{{ $payment->customer_id }}" class="btn btn-sm btn-outline-info mt-1">
                                                    <i class="fas fa-eye"></i> View Balance
                                                </a>
                                            @endif
                                        @else
                                            <span class="text-muted">No Impact</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->payment_method === 'cheque' && ($payment->cheque_status ?? 'pending') === 'pending' && $payment->cheque_valid_date)
                                            @php
                                                $daysUntilDue = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($payment->cheque_valid_date), false);
                                            @endphp
                                            @if($daysUntilDue < 0)
                                                <span class="badge bg-danger">{{ abs($daysUntilDue) }} days overdue</span>
                                            @elseif($daysUntilDue <= 3)
                                                <span class="badge bg-warning">{{ $daysUntilDue }} days left</span>
                                            @else
                                                <span class="badge bg-info">{{ $daysUntilDue }} days left</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info" onclick="viewChequeDetails({{ $payment->id }})" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if(in_array(($payment->cheque_status ?? 'pending'), ['pending', 'deposited']))
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChequeStatus({{ $payment->id }})" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewStatusHistory({{ $payment->id }})" title="Status History">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            @if(($payment->cheque_status ?? 'pending') === 'bounced' && $payment->customer_id)
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="recordRecoveryPayment({{ $payment->customer_id }}, {{ $payment->amount + ($payment->bank_charges ?? 0) }})" title="Record Recovery">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="13" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <h5>No cheques found</h5>
                                            <p>Try adjusting your filters or check back later.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if(isset($cheques) && method_exists($cheques, 'links'))
                    <div class="d-flex justify-content-center mt-3">
                        {{ $cheques->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Cheque Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="paymentId">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status</label>
                        <select class="form-control" id="newStatus" name="status" required>
                            <option value="">Select Status</option>
                            <option value="deposited">Deposited</option>
                            <option value="cleared">Cleared</option>
                            <option value="bounced">Bounced</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add any notes about this status change..."></textarea>
                    </div>
                    <div class="mb-3" id="bankChargesGroup" style="display: none;">
                        <label for="bankCharges" class="form-label">Bank Charges</label>
                        <input type="number" class="form-control" id="bankCharges" name="bank_charges" step="0.01" min="0" placeholder="0.00">
                        <small class="form-text text-muted">Any charges imposed by the bank for this cheque</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Recovery Payment Modal -->
<div class="modal fade" id="recoveryPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Recovery Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recoveryPaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="recoveryCustomerId">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Recovery Payment:</strong> This payment will reduce the customer's floating balance (bounced cheques, bank charges, etc.) without affecting any specific bill.
                    </div>
                    <div class="mb-3">
                        <label for="recoveryAmount" class="form-label">Recovery Amount</label>
                        <input type="number" class="form-control" id="recoveryAmount" name="amount" step="0.01" min="0.01" required placeholder="0.00">
                        <small class="form-text text-muted">Amount to recover from floating balance</small>
                    </div>
                    <div class="mb-3">
                        <label for="recoveryPaymentMethod" class="form-label">Payment Method</label>
                        <select class="form-control" id="recoveryPaymentMethod" name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="recoveryPaymentDate" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="recoveryPaymentDate" name="payment_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="recoveryNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="recoveryNotes" name="notes" rows="3" placeholder="Recovery payment for bounced cheque..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="recoveryReferenceNo" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="recoveryReferenceNo" name="reference_no" placeholder="Transaction/Receipt number (optional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cheque Details Modal -->
<div class="modal fade" id="chequeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cheque Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="chequeDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Status History Modal -->
<div class="modal fade" id="statusHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cheque Status History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="statusHistoryContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Load customers for filter dropdown
    loadCustomers();

    // Select all checkbox functionality
    $('#selectAll').on('change', function() {
        $('.cheque-checkbox').prop('checked', this.checked);
        updateBulkActionButtons();
    });

    // Individual checkbox change
    $(document).on('change', '.cheque-checkbox', function() {
        updateBulkActionButtons();
        updateSelectAllCheckbox();
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        loadCheques();
    });

    // Refresh data
    $('#refreshData').on('click', function() {
        location.reload();
    });

    // Update status form
    $('#updateStatusForm').on('submit', function(e) {
        e.preventDefault();
        updateChequeStatusSubmit();
    });

    // Show/hide bank charges field based on status
    $('#newStatus').on('change', function() {
        if (this.value === 'bounced') {
            $('#bankChargesGroup').show();
        } else {
            $('#bankChargesGroup').hide();
            $('#bankCharges').val('');
        }
    });

    // Bulk actions
    $('#bulkClear').on('click', function() {
        bulkUpdateStatus('cleared');
    });

    $('#bulkDeposit').on('click', function() {
        bulkUpdateStatus('deposited');
    });

    $('#bulkBounce').on('click', function() {
        bulkUpdateStatus('bounced');
    });

    // Recovery payment form
    $('#recoveryPaymentForm').on('submit', function(e) {
        e.preventDefault();
        submitRecoveryPayment();
    });
});

function loadCustomers() {
    $.ajax({
        url: '/customer-get-all',
        method: 'GET',
        success: function(response) {
            const customerSelect = $('#customerFilter');
            customerSelect.empty().append('<option value="">All Customers</option>');
            
            if (response && response.length > 0) {
                response.forEach(function(customer) {
                    customerSelect.append(`<option value="${customer.id}">${customer.full_name}</option>`);
                });
            }
        },
        error: function() {
            console.error('Failed to load customers');
        }
    });
}

function updateBulkActionButtons() {
    const selectedCount = $('.cheque-checkbox:checked').length;
    $('#selectedCount').text(selectedCount);
    
    if (selectedCount > 0) {
        $('#bulkClear, #bulkDeposit, #bulkBounce').prop('disabled', false);
    } else {
        $('#bulkClear, #bulkDeposit, #bulkBounce').prop('disabled', true);
    }
}

function updateSelectAllCheckbox() {
    const totalCheckboxes = $('.cheque-checkbox').length;
    const checkedCheckboxes = $('.cheque-checkbox:checked').length;
    
    if (checkedCheckboxes === 0) {
        $('#selectAll').prop('indeterminate', false).prop('checked', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
        $('#selectAll').prop('indeterminate', false).prop('checked', true);
    } else {
        $('#selectAll').prop('indeterminate', true);
    }
}

function loadCheques() {
    const formData = $('#filterForm').serialize();
    window.location.href = '{{ route("cheque-management") }}?' + formData;
}

function viewChequeDetails(paymentId) {
    $.ajax({
        url: `/cheque/status-history/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 200) {
                const payment = response.payment;
                const sale = payment.sale;
                const customer = payment.customer;
                
                // Enhanced details with bounce impact information
                let bounceImpactHtml = '';
                if (payment.cheque_status === 'bounced') {
                    bounceImpactHtml = `
                        <div class="col-12 mt-3">
                            <div class="alert alert-warning">
                                <h6 class="fw-bold"><i class="fas fa-exclamation-triangle"></i> Bounce Impact</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Bill Status:</strong> <span class="badge bg-success">Remains PAID</span></p>
                                        <p><strong>Bounce Amount:</strong> Rs. ${numberFormat(payment.amount)}</p>
                                        <p><strong>Bank Charges:</strong> Rs. ${numberFormat(payment.bank_charges || 0)}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Customer Impact:</strong> <span class="badge bg-warning">Floating Balance Added</span></p>
                                        <p><strong>Total Added:</strong> Rs. ${numberFormat((payment.amount || 0) + (payment.bank_charges || 0))}</p>
                                        <p><strong>Bounce Date:</strong> ${payment.cheque_bounce_date || 'N/A'}</p>
                                    </div>
                                </div>
                                <p><strong>Bounce Reason:</strong> ${payment.cheque_bounce_reason || 'Not specified'}</p>
                            </div>
                        </div>
                    `;
                }

                const detailsHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Payment Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td><strong>Amount:</strong></td><td>Rs. ${numberFormat(payment.amount)}</td></tr>
                                <tr><td><strong>Cheque Number:</strong></td><td>${payment.cheque_number || 'N/A'}</td></tr>
                                <tr><td><strong>Bank/Branch:</strong></td><td>${payment.cheque_bank_branch || 'N/A'}</td></tr>
                                <tr><td><strong>Given By:</strong></td><td>${payment.cheque_given_by || 'N/A'}</td></tr>
                                <tr><td><strong>Received Date:</strong></td><td>${payment.cheque_received_date || 'N/A'}</td></tr>
                                <tr><td><strong>Valid Date:</strong></td><td>${payment.cheque_valid_date || 'N/A'}</td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge bg-primary">${payment.cheque_status}</span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Sale Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr><td><strong>Invoice:</strong></td><td>${sale.invoice_no}</td></tr>
                                <tr><td><strong>Customer:</strong></td><td>${customer.full_name}</td></tr>
                                <tr><td><strong>Sale Date:</strong></td><td>${sale.sales_date}</td></tr>
                                <tr><td><strong>Total Amount:</strong></td><td>Rs. ${numberFormat(sale.final_total)}</td></tr>
                                <tr><td><strong>Total Paid:</strong></td><td>Rs. ${numberFormat(sale.total_paid)}</td></tr>
                                <tr><td><strong>Payment Status:</strong></td><td><span class="badge bg-${sale.payment_status === 'Paid' ? 'success' : 'warning'}">${sale.payment_status}</span></td></tr>
                            </table>
                        </div>
                        ${bounceImpactHtml}
                    </div>
                `;
                
                $('#chequeDetailsContent').html(detailsHtml);
                $('#chequeDetailsModal').modal('show');
            }
        },
        error: function() {
            alert('Failed to load cheque details');
        }
    });
}

function updateChequeStatus(paymentId) {
    $('#paymentId').val(paymentId);
    $('#updateStatusForm')[0].reset();
    $('#bankChargesGroup').hide();
    $('#updateStatusModal').modal('show');
}

function updateChequeStatusSubmit() {
    const paymentId = $('#paymentId').val();
    const formData = $('#updateStatusForm').serialize();
    
    $.ajax({
        url: `/cheque/update-status/${paymentId}`,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.status === 200) {
                let message = response.message;
                
                // Enhanced message for bounced cheques
                if (response.data && response.data.customer_impact) {
                    const impact = response.data.customer_impact;
                    message += `\n\nCustomer Impact:`;
                    message += `\n• ${impact.customer_name}`;
                    message += `\n• ${impact.bill_status}`;
                    message += `\n• Floating Balance: Rs. ${numberFormat(impact.floating_balance)}`;
                    message += `\n• Total Outstanding: Rs. ${numberFormat(impact.total_outstanding)}`;
                }
                
                alert(message);
                $('#updateStatusModal').modal('hide');
                location.reload();
            } else {
                alert(response.message || 'Failed to update status');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to update cheque status';
            alert(errorMsg);
        }
    });
}

// NEW: Record recovery payment function
function recordRecoveryPayment(customerId, suggestedAmount) {
    $('#recoveryCustomerId').val(customerId);
    $('#recoveryAmount').val(suggestedAmount);
    $('#recoveryPaymentDate').val(new Date().toISOString().split('T')[0]);
    $('#recoveryPaymentModal').modal('show');
}

function submitRecoveryPayment() {
    const customerId = $('#recoveryCustomerId').val();
    const formData = $('#recoveryPaymentForm').serialize();
    
    $.ajax({
        url: `/floating-balance/customer/${customerId}/recovery-payment`,
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.status === 200) {
                const balanceUpdate = response.data.balance_update;
                let message = 'Recovery payment recorded successfully!\n\n';
                message += `Payment Amount: Rs. ${numberFormat(balanceUpdate.payment_amount)}\n`;
                message += `Previous Floating Balance: Rs. ${numberFormat(balanceUpdate.old_floating_balance)}\n`;
                message += `New Floating Balance: Rs. ${numberFormat(balanceUpdate.new_floating_balance)}\n`;
                message += `Total Outstanding: Rs. ${numberFormat(balanceUpdate.total_outstanding)}`;
                
                alert(message);
                $('#recoveryPaymentModal').modal('hide');
                location.reload();
            } else {
                alert(response.message || 'Failed to record recovery payment');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to record recovery payment';
            alert(errorMsg);
        }
    });
}

function viewStatusHistory(paymentId) {
    $.ajax({
        url: `/cheque/status-history/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 200) {
                let historyHtml = '<div class="timeline">';
                
                if (response.history && response.history.length > 0) {
                    response.history.forEach(function(history) {
                        historyHtml += `
                            <div class="timeline-item">
                                <div class="timeline-marker bg-primary"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">${history.old_status || 'New'} → ${history.new_status}</h6>
                                    <p class="timeline-text">
                                        <small>
                                            <strong>Date:</strong> ${history.status_date}<br>
                                            <strong>Changed by:</strong> ${history.user ? history.user.name : 'System'}<br>
                                            ${history.remarks ? '<strong>Remarks:</strong> ' + history.remarks + '<br>' : ''}
                                            ${history.bank_charges > 0 ? '<strong>Bank Charges:</strong> Rs. ' + history.bank_charges : ''}
                                        </small>
                                    </p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    historyHtml += '<p class="text-muted">No status history available.</p>';
                }
                
                historyHtml += '</div>';
                
                $('#statusHistoryContent').html(historyHtml);
                $('#statusHistoryModal').modal('show');
            }
        },
        error: function() {
            alert('Failed to load status history');
        }
    });
}

function bulkUpdateStatus(status) {
    const selectedIds = $('.cheque-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        alert('Please select cheques to update');
        return;
    }
    
    if (!confirm(`Are you sure you want to mark ${selectedIds.length} cheques as ${status}?`)) {
        return;
    }
    
    $.ajax({
        url: '{{ route("cheque.bulk-update-status") }}',
        method: 'POST',
        data: {
            payment_ids: selectedIds,
            status: status,
            remarks: `Bulk update to ${status}`
        },
        success: function(response) {
            if (response.status === 200) {
                alert(response.message);
                location.reload();
            } else {
                alert(response.message || 'Failed to update cheques');
            }
        },
        error: function() {
            alert('Failed to perform bulk update');
        }
    });
}

function numberFormat(number) {
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -35px;
    top: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #007bff;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: bold;
}

.timeline-text {
    margin-bottom: 0;
    color: #6c757d;
}

.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border: none;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}
</style>
@endsection
