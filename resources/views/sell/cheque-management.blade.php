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
                            <button type="button" class="btn btn-warning btn-sm me-2" id="bulkRecoveryPayment" disabled>
                                <i class="fas fa-money-bill-wave"></i> Recovery Payment
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
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-striped table-hover mb-0" id="chequesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="40" class="text-center">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th class="text-center">Invoice #</th>
                                    <th class="text-center">Customer</th>
                                    <th class="text-center">Cheque #</th>
                                    <th class="text-center">Bank/Branch</th>
                                    <th class="text-center">Amount</th>
                                    <th class="text-center">Received Date</th>
                                    <th class="text-center">Valid Date</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Bill Status</th>
                                    <th class="text-center">Customer Impact</th>
                                    <th class="text-center">Days Until Due</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="chequesTableBody">
                                @forelse($cheques as $payment)
                                <tr data-status="{{ $payment->cheque_status ?? 'pending' }}">
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
                                            @php
                                                $currentStatus = $payment->cheque_status ?? 'pending';
                                                $canUpdate = in_array($currentStatus, ['pending', 'deposited']);
                                            @endphp
                                            @if($canUpdate)
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateChequeStatus({{ $payment->id }}, '{{ $currentStatus }}')" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Status cannot be changed">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="viewStatusHistory({{ $payment->id }})" title="Status History">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            @if(($payment->cheque_status ?? 'pending') === 'bounced' && $payment->customer_id)
                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="recordRecoveryPayment({{ $payment->customer_id }}, {{ $payment->amount + ($payment->bank_charges ?? 0) }})" title="Record Recovery">
                                                <i class="fas fa-money-bill"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="viewRecoveryChain({{ $payment->id }})" title="View Recovery Chain">
                                                <i class="fas fa-link"></i>
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
                            <!-- Options will be populated dynamically based on current status -->
                        </select>
                        <small class="form-text text-muted">
                            Status transitions: Pending → Deposited/Cancelled → Cleared/Bounced/Cancelled
                        </small>
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

<!-- Bulk Recovery Payment Modal -->
<div class="modal fade" id="bulkRecoveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recovery Payment for Multiple Bounced Cheques</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkRecoveryForm">
                <div class="modal-body">
                    <!-- Selected Cheques Summary -->
                    <div class="alert alert-info">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-info-circle"></i> Selected Bounced Cheques
                        </h6>
                        <div id="selectedChequesInfo">
                            <!-- Will be populated with selected cheques details -->
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total Bounced Amount:</strong> 
                                <span id="totalBouncedAmount" class="text-danger">Rs. 0.00</span>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Bank Charges:</strong> 
                                <span id="totalBankCharges" class="text-warning">Rs. 0.00</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <strong>Total Recovery Required:</strong> 
                                <span id="totalRecoveryAmount" class="text-primary fs-5">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recovery Payment Options -->
                    <div class="row">
                        <div class="col-md-6">
                            <label for="recoveryMethod" class="form-label fw-bold">Recovery Payment Method</label>
                            <select class="form-control" id="recoveryMethod" name="recovery_method" required>
                                <option value="">Select Recovery Method</option>
                                <option value="cash">Cash Payment</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card Payment</option>
                                <option value="new_cheque">New Cheque</option>
                                <option value="partial_cash_cheque">Partial Cash + New Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="recoveryDate" class="form-label fw-bold">Recovery Date</label>
                            <input type="date" class="form-control" id="recoveryDate" name="recovery_date" required>
                        </div>
                    </div>

                    <!-- Dynamic Payment Fields -->
                    <div id="recoveryPaymentFields" class="mt-3">
                        <!-- Will be populated based on selected method -->
                    </div>

                    <!-- Recovery Notes -->
                    <div class="mt-3">
                        <label for="recoveryNotes" class="form-label fw-bold">Recovery Notes</label>
                        <textarea class="form-control" id="recoveryNotes" name="recovery_notes" rows="3" 
                                  placeholder="Notes about this recovery payment..."></textarea>
                    </div>

                    <!-- Payment Summary -->
                    <div id="paymentSummary" class="mt-3" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Payment Summary</h6>
                                <div id="summaryContent">
                                    <!-- Will be populated with payment breakdown -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Process Recovery Payment
                    </button>
                </div>
            </form>
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

    // Bulk Recovery Payment
    $('#bulkRecoveryPayment').on('click', function() {
        openBulkRecoveryModal();
    });

    // Recovery method change
    $('#recoveryMethod').on('change', function() {
        updateRecoveryPaymentFields();
    });

    // Bulk recovery form submission
    $('#bulkRecoveryForm').on('submit', function(e) {
        e.preventDefault();
        processBulkRecoveryPayment();
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
    
    // Disable all bulk actions initially
    $('#bulkClear, #bulkDeposit, #bulkBounce, #bulkRecoveryPayment').prop('disabled', true);
    
    if (selectedCount > 0) {
        // Get statuses of selected cheques
        const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
            return $(this).closest('tr').data('status');
        }).get();
        
        // Check if all selected cheques can be cleared (must be deposited)
        const canClear = selectedStatuses.every(status => status === 'deposited');
        
        // Check if all selected cheques can be deposited (must be pending)
        const canDeposit = selectedStatuses.every(status => status === 'pending');
        
        // Check if all selected cheques can be bounced (must be deposited)
        const canBounce = selectedStatuses.every(status => status === 'deposited');
        
        // Check if all selected cheques are bounced (for recovery payment)
        const canRecovery = selectedStatuses.every(status => status === 'bounced');
        
        // Enable appropriate buttons
        if (canClear) {
            $('#bulkClear').prop('disabled', false);
        }
        if (canDeposit) {
            $('#bulkDeposit').prop('disabled', false);
        }
        if (canBounce) {
            $('#bulkBounce').prop('disabled', false);
        }
        if (canRecovery) {
            $('#bulkRecoveryPayment').prop('disabled', false);
        }
        
        // If no valid actions available, show a message
        if (!canClear && !canDeposit && !canBounce && !canRecovery) {
            $('#selectedCount').html(selectedCount + ' <small class="text-muted">(no valid bulk actions for current selection)</small>');
        } else {
            // Show what actions are available
            const availableActions = [];
            if (canClear) availableActions.push('Clear');
            if (canDeposit) availableActions.push('Deposit');
            if (canBounce) availableActions.push('Bounce');
            if (canRecovery) availableActions.push('Recovery');
            
            if (availableActions.length > 0) {
                $('#selectedCount').html(selectedCount + ' <small class="text-success">(can: ' + availableActions.join(', ') + ')</small>');
            }
        }
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
            toastr.error('Failed to load cheque details', 'Loading Error', {
                timeOut: 5000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

function updateChequeStatus(paymentId, currentStatus = 'pending') {
    $('#paymentId').val(paymentId);
    $('#updateStatusForm')[0].reset();
    $('#bankChargesGroup').hide();
    
    // Define valid status transitions
    const validTransitions = {
        'pending': ['deposited', 'cancelled'],
        'deposited': ['cleared', 'bounced', 'cancelled'],
        'cleared': [], // No further transitions allowed
        'bounced': [], // No further transitions allowed  
        'cancelled': [] // No further transitions allowed
    };
    
    // Get available options for current status
    const availableOptions = validTransitions[currentStatus] || [];
    
    // Clear and populate status dropdown
    const statusSelect = $('#newStatus');
    statusSelect.empty();
    statusSelect.append('<option value="">Select Status</option>');
    
    // Add only valid options
    availableOptions.forEach(function(status) {
        const label = status.charAt(0).toUpperCase() + status.slice(1);
        statusSelect.append(`<option value="${status}">${label}</option>`);
    });
    
    // Show message if no transitions available
    if (availableOptions.length === 0) {
        statusSelect.append('<option value="" disabled>No status changes allowed</option>');
        statusSelect.prop('disabled', true);
    } else {
        statusSelect.prop('disabled', false);
    }
    
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
                    const detailMessage = `Customer: ${impact.customer_name} | ${impact.bill_status} | Floating Balance: Rs. ${numberFormat(impact.floating_balance)} | Total Outstanding: Rs. ${numberFormat(impact.total_outstanding)}`;
                    
                    // Show success with detailed info
                    toastr.success(message + '<br><small>' + detailMessage + '</small>', 'Cheque Status Updated', {
                        timeOut: 8000,
                        extendedTimeOut: 3000,
                        allowHtml: true
                    });
                } else {
                    toastr.success(message, 'Success');
                }
                
                $('#updateStatusModal').modal('hide');
                setTimeout(() => location.reload(), 1000); // Small delay to show toastr
            } else {
                toastr.error(response.message || 'Failed to update status', 'Error');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to update cheque status';
            toastr.error(errorMsg, 'Error');
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
                const detailMessage = `Payment: Rs. ${numberFormat(balanceUpdate.payment_amount)} | Old Balance: Rs. ${numberFormat(balanceUpdate.old_floating_balance)} | New Balance: Rs. ${numberFormat(balanceUpdate.new_floating_balance)} | Total Outstanding: Rs. ${numberFormat(balanceUpdate.total_outstanding)}`;
                
                toastr.success('Recovery payment recorded successfully!<br><small>' + detailMessage + '</small>', 'Payment Recorded', {
                    timeOut: 8000,
                    extendedTimeOut: 3000,
                    allowHtml: true
                });
                
                $('#recoveryPaymentModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message || 'Failed to record recovery payment', 'Error');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to record recovery payment';
            toastr.error(errorMsg, 'Error');
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
            toastr.error('Failed to load status history', 'Loading Error', {
                timeOut: 5000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

function bulkUpdateStatus(status) {
    const selectedIds = $('.cheque-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        toastr.warning('Please select cheques to update', 'No Selection', {
            timeOut: 5000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
        return;
    }
    
    // Validate status transitions for selected cheques
    const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
        return $(this).closest('tr').data('status');
    }).get();
    
    // Define valid transitions
    const validTransitions = {
        'cleared': ['deposited'],
        'deposited': ['pending'],
        'bounced': ['deposited']
    };
    
    // Check if all selected cheques can be updated to target status
    const requiredStatuses = validTransitions[status] || [];
    const canUpdate = selectedStatuses.every(currentStatus => requiredStatuses.includes(currentStatus));
    
    if (!canUpdate) {
        toastr.error(`Cannot update cheques to ${status}. Only ${requiredStatuses.join(' or ')} cheques can be marked as ${status}.`, 'Invalid Status Transition', {
            timeOut: 8000,
            progressBar: true,
            positionClass: 'toast-top-right'
        });
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
                toastr.success(response.message, 'Bulk Update Successful', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    escapeHtml: false
                });
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to update cheques', 'Update Failed', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });
            }
        },
        error: function() {
            toastr.error('Failed to perform bulk update', 'Network Error', {
                timeOut: 8000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    });
}

function numberFormat(number) {
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(number);
}

// ================= BULK RECOVERY PAYMENT FUNCTIONS =================

/**
 * Open bulk recovery modal for selected bounced cheques
 */
function openBulkRecoveryModal() {
    const selectedIds = $('.cheque-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        toastr.warning('Please select bounced cheques for recovery payment', 'No Selection');
        return;
    }
    
    // Validate all selected are bounced
    const selectedStatuses = $('.cheque-checkbox:checked').map(function() {
        return $(this).closest('tr').data('status');
    }).get();
    
    const allBounced = selectedStatuses.every(status => status === 'bounced');
    if (!allBounced) {
        toastr.error('Only bounced cheques can be selected for recovery payment', 'Invalid Selection');
        return;
    }
    
    // Collect selected cheque details
    let totalBouncedAmount = 0;
    let totalBankCharges = 0;
    let chequesInfo = '';
    
    $('.cheque-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const chequeNumber = row.find('.badge').text() || 'N/A';
        const customerName = row.find('td:nth-child(3)').text() || 'Unknown';
        const amount = parseFloat(row.find('.text-success').text().replace(/[Rs.\s,]/g, '')) || 0;
        const bankCharges = parseFloat(row.find('.text-danger').text().replace(/[Rs.\s,]/g, '')) || 0;
        
        totalBouncedAmount += amount;
        totalBankCharges += bankCharges;
        
        chequesInfo += `
            <div class="border-bottom pb-2 mb-2">
                <strong>Cheque #${chequeNumber}</strong> - ${customerName}<br>
                <small>Amount: Rs. ${numberFormat(amount)} | Bank Charges: Rs. ${numberFormat(bankCharges)}</small>
            </div>
        `;
    });
    
    // Update modal content
    $('#selectedChequesInfo').html(chequesInfo);
    $('#totalBouncedAmount').text('Rs. ' + numberFormat(totalBouncedAmount));
    $('#totalBankCharges').text('Rs. ' + numberFormat(totalBankCharges));
    $('#totalRecoveryAmount').text('Rs. ' + numberFormat(totalBouncedAmount + totalBankCharges));
    
    // Set default recovery date
    $('#recoveryDate').val(new Date().toISOString().split('T')[0]);
    
    // Reset form
    $('#bulkRecoveryForm')[0].reset();
    $('#recoveryDate').val(new Date().toISOString().split('T')[0]);
    $('#recoveryPaymentFields').html('');
    $('#paymentSummary').hide();
    
    // Store selected data for processing
    $('#bulkRecoveryModal').data('selectedIds', selectedIds);
    $('#bulkRecoveryModal').data('totalAmount', totalBouncedAmount + totalBankCharges);
    
    // Show modal
    $('#bulkRecoveryModal').modal('show');
}

/**
 * Update recovery payment fields based on selected method
 */
function updateRecoveryPaymentFields() {
    const method = $('#recoveryMethod').val();
    const fieldsContainer = $('#recoveryPaymentFields');
    const totalAmount = $('#bulkRecoveryModal').data('totalAmount') || 0;
    
    fieldsContainer.html('');
    $('#paymentSummary').hide();
    
    if (!method) return;
    
    let fieldsHtml = '';
    
    switch(method) {
        case 'cash':
            fieldsHtml = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-money-bill-wave"></i> Cash Payment</h6>
                    <p class="mb-0">Full recovery amount will be paid in cash: <strong>Rs. ${numberFormat(totalAmount)}</strong></p>
                </div>
            `;
            showPaymentSummary('Cash Payment', totalAmount, 0);
            break;
            
        case 'bank_transfer':
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Bank Account Number</label>
                        <input type="text" class="form-control" name="bank_account" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number">
                    </div>
                </div>
            `;
            showPaymentSummary('Bank Transfer', totalAmount, 0);
            break;
            
        case 'card':
            fieldsHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Card Number (Last 4 digits)</label>
                        <input type="text" class="form-control" name="card_number" maxlength="4" placeholder="****">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Card Type</label>
                        <select class="form-control" name="card_type">
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                            <option value="amex">American Express</option>
                        </select>
                    </div>
                </div>
            `;
            showPaymentSummary('Card Payment', totalAmount, 0);
            break;
            
        case 'new_cheque':
            fieldsHtml = `
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> New Cheque Payment</h6>
                    <p class="mb-0">Customer will provide a new cheque for the full recovery amount</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">New Cheque Number</label>
                        <input type="text" class="form-control" name="new_cheque_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank/Branch</label>
                        <input type="text" class="form-control" name="new_cheque_bank" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Cheque Date</label>
                        <input type="date" class="form-control" name="new_cheque_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valid Until Date</label>
                        <input type="date" class="form-control" name="new_cheque_valid_date" required>
                    </div>
                </div>
            `;
            showPaymentSummary('New Cheque', 0, totalAmount);
            break;
            
        case 'partial_cash_cheque':
            fieldsHtml = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Partial Cash + New Cheque</h6>
                    <p class="mb-0">Customer will pay part in cash and provide a new cheque for the remaining amount</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Cash Amount</label>
                        <input type="number" class="form-control" id="partialCashAmount" name="cash_amount" 
                               step="0.01" min="0" max="${totalAmount}" required>
                        <small class="text-muted">Max: Rs. ${numberFormat(totalAmount)}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cheque Amount (Auto-calculated)</label>
                        <input type="number" class="form-control" id="partialChequeAmount" readonly>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">New Cheque Number</label>
                        <input type="text" class="form-control" name="new_cheque_number" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank/Branch</label>
                        <input type="text" class="form-control" name="new_cheque_bank" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <label class="form-label">Cheque Date</label>
                        <input type="date" class="form-control" name="new_cheque_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Valid Until Date</label>
                        <input type="date" class="form-control" name="new_cheque_valid_date" required>
                    </div>
                </div>
            `;
            break;
    }
    
    fieldsContainer.html(fieldsHtml);
    
    // Add event listener for partial cash amount calculation
    if (method === 'partial_cash_cheque') {
        $('#partialCashAmount').on('input', function() {
            const cashAmount = parseFloat($(this).val()) || 0;
            const chequeAmount = totalAmount - cashAmount;
            $('#partialChequeAmount').val(chequeAmount.toFixed(2));
            
            showPaymentSummary('Cash + Cheque', cashAmount, chequeAmount);
        });
    }
}

/**
 * Show payment summary
 */
function showPaymentSummary(method, cashAmount, chequeAmount) {
    let summaryHtml = `
        <div class="row">
            <div class="col-md-4">
                <strong>Payment Method:</strong><br>${method}
            </div>
    `;
    
    if (cashAmount > 0) {
        summaryHtml += `
            <div class="col-md-4">
                <strong>Cash Amount:</strong><br>Rs. ${numberFormat(cashAmount)}
            </div>
        `;
    }
    
    if (chequeAmount > 0) {
        summaryHtml += `
            <div class="col-md-4">
                <strong>Cheque Amount:</strong><br>Rs. ${numberFormat(chequeAmount)}
            </div>
        `;
    }
    
    summaryHtml += `
        </div>
        <hr>
        <div class="text-center">
            <strong class="fs-5 text-primary">Total Recovery: Rs. ${numberFormat(cashAmount + chequeAmount)}</strong>
        </div>
    `;
    
    $('#summaryContent').html(summaryHtml);
    $('#paymentSummary').show();
}

/**
 * Process bulk recovery payment
 */
function processBulkRecoveryPayment() {
    const selectedIds = $('#bulkRecoveryModal').data('selectedIds');
    const formData = new FormData($('#bulkRecoveryForm')[0]);
    
    // Add selected cheque IDs to form data
    formData.append('cheque_ids', JSON.stringify(selectedIds));
    
    $.ajax({
        url: '/cheque/bulk-recovery-payment',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status === 200) {
                toastr.success(response.message, 'Recovery Payment Processed', {
                    timeOut: 8000,
                    progressBar: true,
                    positionClass: 'toast-top-right'
                });
                
                $('#bulkRecoveryModal').modal('hide');
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to process recovery payment', 'Error');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to process recovery payment';
            toastr.error(errorMsg, 'Error');
        }
    });
}

// View Recovery Chain Function
function viewRecoveryChain(paymentId) {
    $.ajax({
        url: `/payment/${paymentId}/recovery-chain`,
        type: 'GET',
        success: function(response) {
            if (response.status === 200) {
                showRecoveryChainModal(response.data);
            } else {
                toastr.error('Failed to load recovery chain', 'Error');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Network error occurred';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status === 404) {
                errorMsg = 'Payment not found';
            }
            toastr.error(errorMsg, 'Error');
        }
    });
}

function showRecoveryChainModal(data) {
    let modalContent = `
        <div class="modal fade" id="recoveryChainModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Recovery Chain Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Original Payment Info -->
                        <div class="card mb-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Original Bounced Payment</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Cheque Number:</strong><br>
                                        <span class="badge bg-info">${data.original_payment.cheque_number}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Amount:</strong><br>
                                        <span class="text-danger">Rs. ${numberFormat(Math.abs(data.original_payment.amount))}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Bank Charges:</strong><br>
                                        <span class="text-warning">Rs. ${numberFormat(data.original_payment.bank_charges)}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Due:</strong><br>
                                        <span class="text-danger"><strong>Rs. ${numberFormat(data.total_original)}</strong></span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <strong>Bounce Date:</strong> ${data.original_payment.bounce_date || 'N/A'}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Bounce Reason:</strong> ${data.original_payment.bounce_reason || 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Summary -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6>Total Recovered</h6>
                                        <h4>Rs. ${numberFormat(data.total_recovered)}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6>Pending Recovery</h6>
                                        <h4>Rs. ${numberFormat(data.pending_recovery)}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card ${data.total_recovered >= data.total_original ? 'bg-success' : 'bg-danger'} text-white">
                                    <div class="card-body">
                                        <h6>Remaining</h6>
                                        <h4>Rs. ${numberFormat(data.total_original - data.total_recovered - data.pending_recovery)}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Payments -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-money-bill-wave"></i> Recovery Payments (${data.recoveries.length})</h6>
                            </div>
                            <div class="card-body">`;

    if (data.recoveries.length > 0) {
        modalContent += `
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Details</th>
                                                <th>Created By</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
        
        data.recoveries.forEach(recovery => {
            let statusBadge = recovery.payment_status === 'completed' ? 'bg-success' : 'bg-warning';
            let methodDetails = '';
            
            if (recovery.payment_method === 'card') {
                methodDetails = `Card: ${recovery.card_type} ${recovery.card_number}`;
            } else if (recovery.payment_method === 'cheque') {
                methodDetails = `Cheque: ${recovery.cheque_number} (${recovery.cheque_status})`;
            } else if (recovery.payment_method === 'bank_transfer') {
                methodDetails = `Bank: ${recovery.bank_account}`;
            } else if (recovery.actual_payment_method === 'partial_cash_cheque') {
                methodDetails = 'Partial: Cash + Cheque';
            }
            
            modalContent += `
                                            <tr>
                                                <td>${recovery.payment_date}</td>
                                                <td>
                                                    <span class="badge bg-primary">${recovery.payment_method}</span>
                                                    ${recovery.actual_payment_method !== recovery.payment_method ? 
                                                        `<br><small class="text-muted">(${recovery.actual_payment_method})</small>` : ''}
                                                </td>
                                                <td><strong>Rs. ${numberFormat(recovery.amount)}</strong></td>
                                                <td><span class="badge ${statusBadge}">${recovery.payment_status}</span></td>
                                                <td>
                                                    ${methodDetails}
                                                    ${recovery.reference_no ? `<br><small>Ref: ${recovery.reference_no}</small>` : ''}
                                                    ${recovery.notes ? `<br><small class="text-muted">${recovery.notes}</small>` : ''}
                                                </td>
                                                <td>
                                                    ${recovery.created_by}
                                                    <br><small class="text-muted">${recovery.created_at}</small>
                                                </td>
                                            </tr>`;
        });
        
        modalContent += `
                                        </tbody>
                                    </table>
                                </div>`;
    } else {
        modalContent += `
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No recovery payments recorded yet</h6>
                                    <p class="text-muted">Use bulk recovery options to record recovery payments</p>
                                </div>`;
    }

    modalContent += `
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>`;

    // Remove any existing modal
    $('#recoveryChainModal').remove();
    
    // Add new modal to body
    $('body').append(modalContent);
    
    // Show modal
    $('#recoveryChainModal').modal('show');
    
    // Remove modal from DOM when hidden
    $('#recoveryChainModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
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
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
}

.table thead.table-dark th {
    background-color: #343a40 !important;
    color: white !important;
    border-color: #454d55 !important;
}

.table th, .table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

/* Ensure table text is always visible and readable */
.table tbody td {
    color: #212529;
    font-size: 14px;
}

.table-responsive {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 5px;
    overflow: hidden;
}

/* Make sure table headers are always visible */
#chequesTable thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #343a40 !important;
    color: white !important;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
}

/* Ensure proper contrast for all table elements */
.table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(0, 0, 0, 0.05);
}

.table-hover > tbody > tr:hover > td {
    background-color: rgba(0, 123, 255, 0.075);
}

/* Badge and status visibility improvements */
.badge {
    font-size: 11px;
    font-weight: 600;
    padding: 0.35em 0.65em;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

/* Bulk action button styling */
.btn:disabled {
    opacity: 0.3 !important;
    cursor: not-allowed !important;
}

.btn:disabled:hover {
    background-color: var(--bs-secondary) !important;
    border-color: var(--bs-secondary) !important;
}

/* Selection counter styling */
#selectedCount .text-muted {
    font-size: 0.85em;
    font-style: italic;
}
</style>
@endsection
