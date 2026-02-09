@extends('layout.layout')

@push('styles')
<!-- DataTables RowGroup CSS - Local -->
<link rel="stylesheet" href="{{ asset('vendor/datatables-rowgroup/css/rowGroup.dataTables.min.css') }}">
<style>
    /* DataTables Row Grouping Styles */
    .group-row {
        background: #f8f9fa !important;
        color: #333 !important;
        font-weight: bold;
        font-size: 14px;
        border-left: 4px solid #007bff !important;
        border-top: 2px solid #dee2e6 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }

    .group-row td {
        background: transparent !important;
        color: #333 !important;
        padding: 12px 8px !important;
        vertical-align: middle !important;
    }

    .group-row td:first-child {
        border-left: none !important;
    }

    /* Enhance table readability */
    #chequesTable tbody tr:hover {
        background-color: #f8f9fa !important;
        cursor: pointer;
    }

    #chequesTable thead th {
        position: sticky;
        top: 0;
        background-color: #343a40 !important;
        color: white !important;
        z-index: 10;
        box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
    }

    /* RowGroup specific styles */
    table.dataTable tbody tr.dtrg-group td {
        background: #f8f9fa !important;
        color: #333 !important;
        font-weight: bold !important;
        font-size: 14px !important;
        padding: 12px 10px !important;
        border-left: 4px solid #007bff !important;
        border-top: 2px solid #dee2e6 !important;
        border-bottom: 2px solid #dee2e6 !important;
    }

    table.dataTable tbody tr.dtrg-group:hover {
        background: #e9ecef !important;
    }
</style>
@endpush

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



    <!-- Filters Card with Accordion -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body py-3">
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters">
                        <i class="fas fa-filter"></i> Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="collapse" id="collapseFilters">
                <div class="card card-body mb-4">
                    <form id="filterForm" class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4 col-sm-6">
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
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="fromDate" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="fromDate" name="from_date">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="toDate" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="toDate" name="to_date">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6">
                            <label for="customerFilter" class="form-label">Customer</label>
                            <select class="form-control selectBox" id="customerFilter" name="customer_id">
                                <option value="">All Customers</option>
                                <!-- Will be populated via AJAX -->
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12">
                            <label for="chequeNumberFilter" class="form-label">Cheque Number</label>
                            <input type="text" class="form-control" id="chequeNumberFilter" name="cheque_number" placeholder="Search cheque...">
                        </div>
                        <div class="col-md-12 mt-3">
                            <button type="button" class="btn btn-secondary" id="clearFiltersBtn">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-2 g-2">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-warning text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Pending</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">Rs. {{ number_format($stats['total_pending'] ?? 0, 2) }}</h4>
                            <small style="font-size: 0.7rem;">At Risk</small>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Deposited</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">Rs. {{ number_format($stats['total_deposited'] ?? 0, 2) }}</h4>
                            <small style="font-size: 0.7rem;">In Bank</small>
                        </div>
                        <div>
                            <i class="fas fa-university"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Cleared</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">Rs. {{ number_format($stats['total_cleared'] ?? 0, 2) }}</h4>
                            <small style="font-size: 0.7rem;">Safe</small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-danger text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Bounced</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">Rs. {{ number_format($stats['total_bounced'] ?? 0, 2) }}</h4>
                            <small style="font-size: 0.7rem;">Floating Balance</small>
                        </div>
                        <div>
                            <i class="fas fa-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-info text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Due Soon</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">{{ $stats['due_soon_count'] ?? 0 }}</h4>
                            <small style="font-size: 0.7rem;">Follow-up</small>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.85rem;">Overdue</h6>
                            <h4 class="mb-0" style="font-size: 1.1rem;">{{ $stats['overdue_count'] ?? 0 }}</h4>
                            <small style="font-size: 0.7rem;">Action Needed</small>
                        </div>
                        <div>
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
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
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="chequesTable" style="width: 100%;">
                            <thead class="table-dark">
                                <tr>
                                    <th width="50" class="text-center">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th width="100">Invoice #</th>
                                    <th width="150">Customer</th>
                                    <th width="120">Cheque #</th>
                                    <th width="160">Bank/Branch</th>
                                    <th width="120">Amount</th>
                                    <th width="120">Received Date</th>
                                    <th width="120">Valid Date</th>
                                    <th width="100">Status</th>
                                    <th width="100">Bill Status</th>
                                    <th width="130">Customer Impact</th>
                                    <th width="120">Days Until Due</th>
                                    <th width="160">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="chequesTableBody">
                                @forelse($cheques as $payment)
                                <tr data-status="{{ $payment->cheque_status ?? 'pending' }}"
                                    data-amount="{{ $payment->amount }}"
                                    data-bank-charges="{{ $payment->bank_charges ?? 0 }}"
                                    data-customer-id="{{ $payment->customer_id }}">
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
                                                <br><a href="{{ route('floating-balance.customer', $payment->customer_id) }}"
                                                       class="btn btn-sm btn-outline-info mt-1 view-balance-btn"
                                                       data-customer-id="{{ $payment->customer_id }}"
                                                       target="_blank"
                                                       title="View {{ $payment->customer->full_name ?? 'Customer' }} Balance"
                                                       style="pointer-events: auto !important; position: relative; z-index: 1000;">
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
    console.log('Initializing customer dropdown...');

    // Test if dropdown is accessible
    const testSelect = $('#customerFilter');
    if (testSelect.length === 0) {
        console.error('Customer dropdown element not found!');
    } else {
        console.log('Customer dropdown element found:', testSelect);
    }

    loadCustomers();

    // Pre-populate filters from URL parameters
    populateFiltersFromURL();

    // Initialize filter status indicators
    updateFilterIndicators();

    // Add debug info to console
    console.log('Cheque Management Page Loaded');
    console.log('Available debug functions:');
    console.log('- testFloatingBalance(customerId) - Test floating balance route');
    console.log('- $(document).find(".view-balance-btn") - Find all View Balance buttons');
    console.log('Available View Balance buttons:', $('.view-balance-btn').length);

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

    // Initialize DataTable
    initializeDataTable();

    // Auto-apply filters on change without page refresh
    $('#statusFilter, #customerFilter, #fromDate, #toDate, #chequeNumberFilter').on('change input', function() {
        if (chequesDataTable) {
            applyClientSideFilters();
        }
    });

    // Clear Filters button
    $('#clearFiltersBtn').on('click', function() {
        $('#statusFilter').val('').trigger('change');
        $('#customerFilter').val('').trigger('change');
        $('#fromDate').val('');
        $('#toDate').val('');
        $('#chequeNumberFilter').val('');
        if (chequesDataTable) {
            chequesDataTable.search('').columns().search('').draw();
        }
    });

    // View Balance button handler with comprehensive error handling
    $(document).on('click', '.view-balance-btn', function(e) {
        const $btn = $(this);
        const customerId = $btn.data('customer-id');
        const href = $btn.attr('href');
        const originalText = $btn.html();

        console.log('View Balance clicked:', {
            customerId: customerId,
            href: href,
            element: this
        });

        if (!customerId) {
            e.preventDefault();
            alert('Error: Customer ID not found');
            return false;
        }

        // Prevent default and handle manually
        e.preventDefault();

        // Show loading state
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        $btn.prop('disabled', true);

        // First test if the route exists with AJAX
        $.ajax({
            url: href,
            method: 'HEAD', // Just check if route exists
            timeout: 5000,
            success: function() {
                console.log('Route verified, opening floating balance');
                // Route exists, try to open it
                const newWindow = window.open(href, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');

                if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                    // Popup blocked, show message and offer alternative
                    if (confirm('Popup was blocked. Open in current tab instead?')) {
                        window.location.href = href;
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Route check failed:', xhr.status, error);

                if (xhr.status === 404) {
                    alert('Error: Floating balance page not found');
                } else if (xhr.status === 403) {
                    alert('Error: Access denied to floating balance');
                } else if (xhr.status === 401) {
                    alert('Error: Please login to view floating balance');
                } else {
                    // Try direct navigation as fallback
                    console.log('Trying direct navigation...');
                    window.open(href, '_blank');
                }
            },
            complete: function() {
                // Restore button state
                setTimeout(() => {
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }, 1000);
            }
        });
    });

    // Allow Enter key to apply filters from text inputs
    $('#chequeNumberFilter, #fromDate, #toDate').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            applyClientSideFilters();
        }
    });

    // Client-side filtering function
    function applyClientSideFilters() {
        if (!chequesDataTable) return;

        const status = $('#statusFilter').val();
        const customer = $('#customerFilter').val();
        const chequeNumber = $('#chequeNumberFilter').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();

        console.log('Applying filters:', {
            status: status,
            customer: customer,
            customerName: $('#customerFilter option:selected').text(),
            chequeNumber: chequeNumber,
            fromDate: fromDate,
            toDate: toDate
        });

        // Trigger DataTable redraw which will use the custom search function
        chequesDataTable.draw();
    }

    // Custom search function for DataTable
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            // Only apply to our specific table
            if (settings.nTable.id !== 'chequesTable') {
                return true;
            }

            const status = $('#statusFilter').val();
            const customerFilter = $('#customerFilter').val();
            const chequeNumber = $('#chequeNumberFilter').val().toLowerCase();
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();

            // Get row data - data array contains plain text from columns
            const rowCustomer = data[2] ? data[2].toLowerCase() : ''; // Customer column
            const rowChequeNumber = data[3] ? data[3].toLowerCase().replace(/<[^>]*>/g, '').trim() : ''; // Cheque Number (strip HTML)
            const rowDate = data[7] ? data[7].trim() : ''; // Valid Date column
            const rowStatus = data[8] ? data[8].toLowerCase().replace(/<[^>]*>/g, '').trim() : ''; // Status column (strip HTML badges)

            // Filter by status
            if (status && status !== '' && status !== 'all') {
                if (!rowStatus.includes(status.toLowerCase())) {
                    return false;
                }
            }

            // Filter by customer
            if (customerFilter && customerFilter !== '' && customerFilter !== '0') {
                // Get customer name from selected option
                const selectedCustomerName = $('#customerFilter option:selected').text().toLowerCase();
                if (selectedCustomerName && selectedCustomerName !== 'all customers') {
                    if (!rowCustomer.includes(selectedCustomerName)) {
                        return false;
                    }
                }
            }

            // Filter by cheque number
            if (chequeNumber && chequeNumber !== '') {
                if (!rowChequeNumber.includes(chequeNumber)) {
                    return false;
                }
            }

            // Filter by date range
            if ((fromDate && fromDate !== '') || (toDate && toDate !== '')) {
                if (rowDate && rowDate !== 'n/a') {
                    // Parse date from dd-mm-yyyy format
                    const dateParts = rowDate.split('-');
                    if (dateParts.length === 3) {
                        const rowDateObj = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);

                        if (fromDate && fromDate !== '') {
                            const fromDateObj = new Date(fromDate);
                            if (rowDateObj < fromDateObj) return false;
                        }

                        if (toDate && toDate !== '') {
                            const toDateObj = new Date(toDate);
                            if (rowDateObj > toDateObj) return false;
                        }
                    } else {
                        // If date can't be parsed and we have filters, exclude it
                        return false;
                    }
                } else if (fromDate || toDate) {
                    // No valid date but filters are set, exclude this row
                    return false;
                }
            }

            return true;
        }
    );

    // Refresh data
    $('#refreshData').on('click', function() {
        location.reload();
    });

    // Enhanced table scrolling functionality
    initializeTableScrolling();

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

function initializeTableScrolling() {
    const tableWrapper = $('.table-responsive');
    const table = $('#chequesTable');

    if (tableWrapper.length === 0 || table.length === 0) return;

    // Add scroll shadow indicators
    function updateScrollShadows() {
        const scrollLeft = tableWrapper.scrollLeft();
        const scrollWidth = tableWrapper[0].scrollWidth;
        const clientWidth = tableWrapper[0].clientWidth;
        const maxScrollLeft = scrollWidth - clientWidth;

        // Remove existing shadows
        tableWrapper.removeClass('scroll-left scroll-right');

        // Add shadows based on scroll position
        if (scrollLeft > 0) {
            tableWrapper.addClass('scroll-left');
        }
        if (scrollLeft < maxScrollLeft - 1) {
            tableWrapper.addClass('scroll-right');
        }
    }

    // Check if table needs horizontal scrolling
    function checkScrollNeeded() {
        const tableWidth = table[0].scrollWidth;
        const containerWidth = tableWrapper[0].clientWidth;

        if (tableWidth > containerWidth) {
            tableWrapper.addClass('needs-scroll');
            updateScrollShadows();
        } else {
            tableWrapper.removeClass('needs-scroll scroll-left scroll-right');
        }
    }

    // Event listeners
    tableWrapper.on('scroll', updateScrollShadows);
    $(window).on('resize', checkScrollNeeded);

    // Initial check
    setTimeout(checkScrollNeeded, 100);

    // Add keyboard navigation
    tableWrapper.on('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            tableWrapper.scrollLeft(tableWrapper.scrollLeft() - 100);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            tableWrapper.scrollLeft(tableWrapper.scrollLeft() + 100);
        }
    });

    // Make table focusable for keyboard navigation
    tableWrapper.attr('tabindex', '0');
}

function populateFiltersFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    // Populate each filter field from URL parameters
    if (urlParams.has('status')) {
        $('#statusFilter').val(urlParams.get('status'));
    }
    if (urlParams.has('customer_id')) {
        $('#customerFilter').val(urlParams.get('customer_id'));
    }
    if (urlParams.has('from_date')) {
        $('#fromDate').val(urlParams.get('from_date'));
    }
    if (urlParams.has('to_date')) {
        $('#toDate').val(urlParams.get('to_date'));
    }
    if (urlParams.has('cheque_number')) {
        $('#chequeNumberFilter').val(urlParams.get('cheque_number'));
    }

    console.log('Filters populated from URL:', {
        status: urlParams.get('status'),
        customer_id: urlParams.get('customer_id'),
        from_date: urlParams.get('from_date'),
        to_date: urlParams.get('to_date'),
        cheque_number: urlParams.get('cheque_number')
    });
}

function updateFilterIndicators() {
    const hasActiveFilters = $('#statusFilter').val() !== '' ||
                           $('#customerFilter').val() !== '' ||
                           $('#fromDate').val() !== '' ||
                           $('#toDate').val() !== '' ||
                           $('#chequeNumberFilter').val() !== '';

    // Add or remove active filter indicator
    const filterHeader = $('.card:has(#filterForm) .card-header h5');
    filterHeader.find('.filter-indicator').remove();

    if (hasActiveFilters) {
        filterHeader.append(' <span class="badge bg-primary filter-indicator">Filters Active</span>');

        // Add clear filters button if not exists
        if ($('#clearFilters').length === 0) {
            filterHeader.parent().append(`
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFilters">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            `);
        }
    } else {
        $('#clearFilters').remove();
    }
}

function clearAllFilters() {
    $('#statusFilter').val('');
    $('#customerFilter').val('');
    $('#fromDate').val('');
    $('#toDate').val('');
    $('#chequeNumberFilter').val('');
    updateFilterIndicators();
    loadCheques();
}

function loadCustomers() {
    console.log('Loading customers for cheque management...');
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
        url: '/customer-get-all',
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Customer response for cheque management:', response);
            const customerSelect = $('#customerFilter');
            customerSelect.empty().append('<option value="">All Customers</option>');

            if (response && response.status === 200 && response.message && Array.isArray(response.message)) {
                const customers = response.message;

                if (customers.length > 0) {
                    customers.forEach(function(customer) {
                        customerSelect.append(`<option value="${customer.id}">${customer.full_name}</option>`);
                    });

                    // Reinitialize Select2 after populating options
                    if (customerSelect.hasClass('select2-hidden-accessible')) {
                        customerSelect.select2('destroy');
                    }
                    customerSelect.select2({
                        placeholder: 'All Customers',
                        allowClear: true
                    });

                    console.log(`Successfully loaded ${customers.length} customers for cheque management`);
                } else {
                    console.warn('No customers found in the response');
                }
            } else {
                console.warn('Invalid response format for customers:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load customers for cheque management:', error);
            console.error('Status:', status);
            console.error('XHR Status:', xhr.status);
            console.error('Response:', xhr.responseText);

            // Show specific error message based on status
            const customerSelect = $('#customerFilter');
            let errorMessage = 'Error loading customers';

            if (xhr.status === 401) {
                errorMessage = 'Authentication required';
            } else if (xhr.status === 403) {
                errorMessage = 'Access denied';
            } else if (xhr.status === 404) {
                errorMessage = 'Endpoint not found';
            } else if (xhr.status === 500) {
                errorMessage = 'Server error';
            }

            customerSelect.empty().append(`<option value="">All Customers (${errorMessage})</option>`);

            // Add a manual test option for debugging
            customerSelect.append('<option value="test">Test Customer (Manual)</option>');
        }
    });
}

function loadCustomersWithBouncedCheques(selectElementId = '#recoveryCustomerSelect') {
    console.log('Loading customers with bounced cheques for recovery payment...');

    $.ajax({
        url: '/customers-with-bounced-cheques',
        method: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Customers with bounced cheques response:', response);
            const customerSelect = $(selectElementId);
            customerSelect.empty().append('<option value="">Select Customer with Bounced Cheques</option>');

            if (response && response.status === 200 && response.message && Array.isArray(response.message)) {
                const customers = response.message;

                if (customers.length > 0) {
                    customers.forEach(function(customer) {
                        const displayText = `${customer.full_name} (${customer.bounced_cheques_count} bounced, ₹${customer.floating_balance.toLocaleString()} floating)`;
                        customerSelect.append(`<option value="${customer.id}">${displayText}</option>`);
                    });

                    // Reinitialize Select2 if it exists
                    if (customerSelect.hasClass('select2-hidden-accessible')) {
                        customerSelect.select2('destroy');
                    }
                    customerSelect.select2({
                        placeholder: 'Select customer with bounced cheques...',
                        allowClear: true
                    });

                    console.log(`Successfully loaded ${customers.length} customers with bounced cheques`);
                } else {
                    customerSelect.append('<option value="" disabled>No customers with bounced cheques found</option>');
                    console.warn('No customers with bounced cheques found');
                }
            } else {
                console.warn('Invalid response format for customers with bounced cheques:', response);
                customerSelect.append('<option value="" disabled>Error loading customers</option>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load customers with bounced cheques:', error);
            const customerSelect = $(selectElementId);
            customerSelect.empty().append('<option value="" disabled>Error loading customers</option>');
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

// Debug function to test floating balance route
window.testFloatingBalance = function(customerId) {
    customerId = customerId || 1; // Default to customer ID 1 for testing
    const url = `/floating-balance/customer/${customerId}`;
    console.log('Testing floating balance URL:', url);

    // Test with AJAX first
    $.ajax({
        url: url,
        method: 'GET',
        success: function(response) {
            console.log('Floating balance route is working:', response);
            window.open(url, '_blank');
        },
        error: function(xhr, status, error) {
            console.error('Floating balance route error:', {
                status: xhr.status,
                error: error,
                response: xhr.responseText
            });
        }
    });
};

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

// Initialize DataTables with row grouping by cheque number
let chequesDataTable;

function initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#chequesTable')) {
        $('#chequesTable').DataTable().destroy();
    }

    chequesDataTable = $('#chequesTable').DataTable({
        order: [[3, 'asc']], // Order by Cheque # column
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        pagingType: "simple_numbers",
        columnDefs: [
            {
                targets: [0, 12], // Checkbox and Actions columns
                orderable: false,
                searchable: false
            }
        ],
        rowGroup: {
            dataSrc: 3, // Group by Cheque # column (index 3)
            startRender: function (rows, group) {
                let totalAmount = 0;
                let chequeCount = rows.count();

                // Calculate total amount for this cheque group
                rows.every(function() {
                    var rowNode = this.node();
                    // Amount is in column index 5 (6th column)
                    var amountCell = $(rowNode).find('td').eq(5);
                    var amountText = amountCell.text().trim();
                    // Extract number from "Rs. 72,433.80" format - remove Rs., commas, keep decimals
                    var amount = parseFloat(amountText.replace(/Rs\.?\s?|,/g, ''));
                    if (!isNaN(amount)) {
                        totalAmount += amount;
                    }
                });

                // Format the total with proper thousand separators
                var formattedTotal = totalAmount.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                return $('<tr class="dtrg-group">')
                    .append('<td colspan="5" style="padding: 12px 10px;"><i class="fas fa-money-check-alt me-2"></i><strong>Cheque #: ' + group + '</strong></td>')
                    .append('<td style="padding: 12px 10px; color: #28a745; font-weight: bold;"><i class="fas fa-coins me-1"></i>Total: Rs. ' + formattedTotal + '</td>')
                    .append('<td colspan="7" style="padding: 12px 10px;"><i class="fas fa-list-ol me-1"></i>' + chequeCount + ' transaction(s)</td>');
            }
        },
        drawCallback: function(settings) {
            updateSelectAllCheckbox();
            updateBulkActionButtons();
        },
        language: {
            search: "Search cheques:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ cheques",
            infoEmpty: "No cheques available",
            infoFiltered: "(filtered from _MAX_ total cheques)",
            zeroRecords: "No matching cheques found",
            emptyTable: "No cheques available",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        }
    });
}

function loadCheques() {
    // Reload DataTable without page refresh for faster filtering
    if (chequesDataTable) {
        toastr.info('Applying filters...', 'Loading', {
            timeOut: 500,
            progressBar: true
        });

        // Get current filter values
        const status = $('#statusFilter').val();
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const customer = $('#customerFilter').val();
        const chequeNumber = $('#chequeNumberFilter').val();

        // Build query string
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (fromDate) params.append('from_date', fromDate);
        if (toDate) params.append('to_date', toDate);
        if (customer) params.append('customer_id', customer);
        if (chequeNumber) params.append('cheque_number', chequeNumber);

        // Update URL without reload
        const newUrl = params.toString() ? '{{ route("cheque-management") }}?' + params.toString() : '{{ route("cheque-management") }}';
        window.history.pushState({}, '', newUrl);

        // Reload the page to get filtered data from server
        window.location.href = newUrl;
    }
}

function viewChequeDetails(paymentId) {
    $.ajax({
        url: `/cheque/status-history/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.status === 200) {
                const payment = response.payment;
                const sale = payment.sale || {};
                const customer = payment.customer || {};

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
                                <tr><td><strong>Invoice:</strong></td><td>${sale.invoice_no || 'N/A'}</td></tr>
                                <tr><td><strong>Customer:</strong></td><td>${customer.full_name || 'N/A'}</td></tr>
                                <tr><td><strong>Sale Date:</strong></td><td>${sale.sales_date || 'N/A'}</td></tr>
                                <tr><td><strong>Total Amount:</strong></td><td>Rs. ${numberFormat(sale.final_total || 0)}</td></tr>
                                <tr><td><strong>Total Paid:</strong></td><td>Rs. ${numberFormat(sale.total_paid || 0)}</td></tr>
                                <tr><td><strong>Payment Status:</strong></td><td><span class="badge bg-${sale.payment_status === 'Paid' ? 'success' : 'warning'}">${sale.payment_status || 'N/A'}</span></td></tr>
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
    // Convert to number if it's a string
    const num = typeof number === 'string' ? parseFloat(number) : number;

    // Use standard international formatting (US locale) instead of Indian
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
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

    // Check for walk-in customers
    let hasWalkInCustomer = false;
    $('.cheque-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const customerName = row.find('td:nth-child(3)').text().trim();
        const customerId = row.data('customer-id');

        if (customerName.toLowerCase() === 'walk-in customer' || customerId === 1) {
            hasWalkInCustomer = true;
            return false; // Break the loop
        }
    });

    if (hasWalkInCustomer) {
        toastr.error('Recovery payments cannot be processed for walk-in customers. Please deselect walk-in customer cheques.', 'Invalid Selection');
        return;
    }

    // Collect selected cheque details and group by customer
    let totalBouncedAmount = 0;
    let totalBankCharges = 0;
    let customerGroups = {};
    let uniqueCustomers = new Set();

    $('.cheque-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const chequeNumber = row.find('.badge').text() || 'N/A';
        const customerName = row.find('td:nth-child(3)').text() || 'Unknown';
        const customerId = row.data('customer-id');

        // Use data attributes for accurate amounts
        const amount = parseFloat(row.data('amount')) || 0;
        const bankCharges = parseFloat(row.data('bank-charges')) || 0;

        console.log(`Processing cheque: ${chequeNumber}, Customer: ${customerName} (ID: ${customerId}), Amount: ${amount}, Bank Charges: ${bankCharges}`);

        totalBouncedAmount += amount;
        totalBankCharges += bankCharges;
        uniqueCustomers.add(customerId);

        // Group by customer
        if (!customerGroups[customerId]) {
            customerGroups[customerId] = {
                name: customerName,
                cheques: [],
                totalAmount: 0,
                totalBankCharges: 0
            };
        }

        customerGroups[customerId].cheques.push({
            number: chequeNumber,
            amount: amount,
            bankCharges: bankCharges
        });
        customerGroups[customerId].totalAmount += amount;
        customerGroups[customerId].totalBankCharges += bankCharges;
    });

    // Generate grouped display
    let chequesInfo = '';
    Object.keys(customerGroups).forEach(customerId => {
        const group = customerGroups[customerId];
        chequesInfo += `
            <div class="customer-group border rounded p-3 mb-3 bg-light">
                <h6 class="text-primary mb-2"><i class="fas fa-user"></i> ${group.name}</h6>
                ${group.cheques.map(cheque => `
                    <div class="border-bottom pb-2 mb-2">
                        <strong>Cheque #${cheque.number}</strong><br>
                        <small>Amount: Rs. ${numberFormat(cheque.amount)} | Bank Charges: Rs. ${numberFormat(cheque.bankCharges)}</small>
                    </div>
                `).join('')}
                <div class="text-end">
                    <strong class="text-success">Customer Total: Rs. ${numberFormat(group.totalAmount + group.totalBankCharges)}</strong>
                </div>
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
    font-size: 10px;
}

.table thead.table-dark th {
    background-color: #343a40 !important;
    color: white !important;
    border-color: #454d55 !important;
}

.table th, .table td {
    padding: 0.3rem 0.4rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

/* Ensure table text is always visible and readable */
.table tbody td {
    color: #212529;
    font-size: 11px;
}

.table-responsive {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    border-radius: 5px;
    overflow: auto !important;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

/* Custom scrollbar styling */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Scroll shadow indicators */
.table-responsive.needs-scroll {
    position: relative;
}

.table-responsive.scroll-left::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 20px;
    background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
    z-index: 5;
    pointer-events: none;
}

.table-responsive.scroll-right::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 20px;
    background: linear-gradient(to left, rgba(0,0,0,0.1), transparent);
    z-index: 5;
    pointer-events: none;
}

/* Enhanced scroll indicator message */
.table-responsive + .text-center {
    background: linear-gradient(45deg, #f8f9fa, #e9ecef);
    border-radius: 0 0 0.375rem 0.375rem;
    transition: all 0.3s ease;
}

.table-responsive.needs-scroll + .text-center {
    background: linear-gradient(45deg, #fff3cd, #ffeaa7);
    border-color: #ffc107;
}

.table-responsive.needs-scroll + .text-center small {
    color: #856404 !important;
    font-weight: 500;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 12px;
    }

    #chequesTable th,
    #chequesTable td {
        padding: 6px 8px;
    }

    /* Hide less important columns on mobile */
    #chequesTable th:nth-child(5), /* Bank/Branch */
    #chequesTable td:nth-child(5),
    #chequesTable th:nth-child(11), /* Customer Impact */
    #chequesTable td:nth-child(11),
    #chequesTable th:nth-child(12), /* Days Until Due */
    #chequesTable td:nth-child(12) {
        display: none;
    }
}

@media (max-width: 576px) {
    /* Hide even more columns on very small screens */
    #chequesTable th:nth-child(7), /* Received Date */
    #chequesTable td:nth-child(7),
    #chequesTable th:nth-child(10), /* Bill Status */
    #chequesTable td:nth-child(10) {
        display: none;
    }
}

/* Make sure table headers are always visible */
#chequesTable thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #343a40 !important;
    color: white !important;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
    white-space: nowrap;
    min-width: fit-content;
}

/* Ensure table cells don't break content */
#chequesTable td {
    white-space: nowrap;
    padding: 4px 6px;
    font-size: 11px;
}

/* Allow some columns to wrap if needed */
#chequesTable td:nth-child(3), /* Customer */
#chequesTable td:nth-child(5)  /* Bank/Branch */ {
    white-space: normal;
    word-break: break-word;
    max-width: 160px;
}

/* Remove all table row shading/striping */
#chequesTable tbody tr {
    background-color: #ffffff !important;
}

#chequesTable tbody tr:nth-child(odd),
#chequesTable tbody tr:nth-child(even) {
    background-color: #ffffff !important;
}

#chequesTable tbody tr:hover {
    background-color: #ffffff !important;
}

/* Badge and status visibility improvements */
.badge {
    font-size: 9px;
    font-weight: 600;
    padding: 0.2em 0.4em;
}

.btn-group .btn {
    border-radius: 0.2rem;
    margin-right: 1px;
    padding: 0.2rem 0.35rem;
    font-size: 10px;
}

.btn-group .btn i {
    font-size: 10px;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

/* View Balance button styling */
.view-balance-btn {
    position: relative !important;
    z-index: 10 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
    text-decoration: none !important;
    transition: all 0.3s ease;
}

.view-balance-btn:hover {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
    color: white !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-balance-btn:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
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

/* Loading overlay styling */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    border-radius: 0.375rem;
}

.loading-overlay .text-center {
    padding: 20px;
}

/* Selection counter styling */
#selectedCount .text-muted {
    font-size: 0.85em;
    font-style: italic;
}
</style>

@push('scripts')
<!-- DataTables RowGroup JS - Local -->
<script src="{{ asset('vendor/datatables-rowgroup/js/dataTables.rowGroup.min.js') }}"></script>
@endpush

@endsection
