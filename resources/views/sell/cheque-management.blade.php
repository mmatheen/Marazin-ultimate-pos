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
                            <!-- Debug: Raw value = {{ $stats['total_bounced'] ?? 'null' }} -->
                            <small>Lost</small>
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
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
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
                                <tr><td><strong>Total Due:</strong></td><td>Rs. ${numberFormat(sale.total_due)}</td></tr>
                            </table>
                        </div>
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
                alert('Cheque status updated successfully');
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
