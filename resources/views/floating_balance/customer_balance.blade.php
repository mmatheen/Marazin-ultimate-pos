@extends('layout.layout')

@push('styles')
<style>
    .balance-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    .floating-balance {
        font-size: 1.2rem;
        font-weight: normal;
    }
    .transaction-row:hover {
        background-color: #f8f9fa;
    }
    .balance-positive {
        color: #198754;
    }
    .balance-negative {
        color: #dc3545;
    }
    .balance-neutral {
        color: #6c757d;
    }
    .card-floating-balance {
        background-color: #f0f8ff;
        border-left: 4px solid #007bff;
    }
    .card-bill-outstanding {
        background-color: #fff5f5;
        border-left: 4px solid #dc3545;
    }
    .card-bounced-cheques {
        background-color: #fffbf0;
        border-left: 4px solid #fd7e14;
    }
    .card-bank-charges {
        background-color: #f8fff8;
        border-left: 4px solid #198754;
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
                        <h3 class="page-title">Customer Floating Balance - {{ $customer->full_name }}</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ url('/cheque-management') }}">Cheque Management</a></li>
                            <li class="breadcrumb-item active">Floating Balance</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ url('/cheque-management') }}" class="btn" style="background-color: #f8f9fa; border: 1px solid #ddd; color: #495057;">
                ← Back to Cheque Management
            </a>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card balance-card">
                <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                    <h5 class="mb-0" style="color: #495057;">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Name:</strong> {{ $customer->full_name }}
                        </div>
                        <div class="col-md-4">
                            <strong>Mobile:</strong> {{ $customer->mobile_no ?? 'N/A' }}
                        </div>
                        <div class="col-md-4">
                            <strong>Email:</strong> {{ $customer->email ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card balance-card card-floating-balance">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Current Floating Balance</h6>
                    @php
                        $floatingBalance = $breakdown['floating_balance'] ?? 0;
                        $balanceClass = $floatingBalance > 0 ? 'balance-positive' : ($floatingBalance < 0 ? 'balance-negative' : 'balance-neutral');
                    @endphp
                    <div class="floating-balance {{ $balanceClass }}">
                        Rs {{ number_format(abs($floatingBalance), 2) }}
                        @if($floatingBalance > 0) <small>(Credit)</small> @elseif($floatingBalance < 0) <small>(Debit)</small> @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card balance-card card-bill-outstanding">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Bill Outstanding</h6>
                    <div class="floating-balance balance-negative">
                        Rs {{ number_format($breakdown['bill_wise_outstanding'] ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card balance-card card-bounced-cheques">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Bounced Cheques</h6>
                    <div class="floating-balance balance-negative">
                        {{ $breakdown['bounced_cheques']['count'] ?? 0 }} cheques
                        <small class="d-block">Rs {{ number_format($breakdown['bounced_cheques']['total_amount'] ?? 0, 2) }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card balance-card card-bank-charges">
                <div class="card-body text-center">
                    <h6 class="card-title text-muted">Total Bank Charges</h6>
                    <div class="floating-balance balance-negative">
                        Rs {{ number_format($breakdown['bounced_cheques']['total_charges'] ?? 0, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bounced Cheques Details -->
    @if(isset($breakdown['bounced_cheques']['cheques']) && count($breakdown['bounced_cheques']['cheques']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card balance-card">
                <div class="card-header" style="background-color: #fff8f0; border-bottom: 1px solid #dee2e6;">
                    <h5 class="mb-0" style="color: #d63384;">Bounced Cheques Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cheque No.</th>
                                    <th>Amount</th>
                                    <th>Bounce Date</th>
                                    <th>Bounce Reason</th>
                                    <th>Bank Charges</th>
                                    <th>Bill No.</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($breakdown['bounced_cheques']['cheques'] as $cheque)
                                <tr class="transaction-row">
                                    <td>{{ $cheque['cheque_number'] ?? 'N/A' }}</td>
                                    <td>Rs {{ number_format($cheque['amount'], 2) }}</td>
                                    <td>{{ $cheque['bounce_date'] ? \Carbon\Carbon::parse($cheque['bounce_date'])->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $cheque['bounce_reason'] ?? 'N/A' }}</td>
                                    <td>Rs {{ number_format($cheque['bank_charges'], 2) }}</td>
                                    <td>{{ $cheque['bill_number'] ?? 'N/A' }}</td>
                                    <td>
                                        <button class="btn btn-sm record-recovery-btn" 
                                                style="background-color: #f8fff8; border: 1px solid #198754; color: #198754;"
                                                data-payment-id="{{ $cheque['id'] }}"
                                                data-customer-id="{{ $customer->id }}"
                                                data-amount="{{ $cheque['amount'] }}">
                                            Record Recovery
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Floating Balance Transactions -->
    <div class="row">
        <div class="col-12">
            <div class="card balance-card">
                <div class="card-header" style="background-color: #f0f8ff; border-bottom: 1px solid #dee2e6;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="color: #0d6efd;">Transaction History</h5>
                        <button class="btn btn-sm" style="border: 1px solid #0d6efd; color: #0d6efd;" id="refreshHistoryBtn">
                            Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body" id="transactionHistoryContainer">
                    <div class="text-center">
                        Loading transaction history...
                    </div>
                </div>
            </div>
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
                    <input type="hidden" id="recoveryCustomerId" name="customer_id">
                    <input type="hidden" id="recoveryPaymentId" name="payment_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" id="recoveryAmount" name="amount" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" id="recoveryPaymentMethod" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="recoveryPaymentDate" name="payment_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reference No.</label>
                        <input type="text" class="form-control" id="recoveryReferenceNo" name="reference_no">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="recoveryNotes" name="notes" rows="3"></textarea>
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const customerId = {{ $customer->id }};
    
    // Load transaction history
    function loadTransactionHistory() {
        $.ajax({
            url: `/floating-balance/customer/${customerId}/history`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    displayTransactionHistory(response.data);
                } else {
                    $('#transactionHistoryContainer').html('<div class="alert alert-warning">No transaction history found.</div>');
                }
            },
            error: function() {
                $('#transactionHistoryContainer').html('<div class="alert alert-danger">Error loading transaction history.</div>');
            }
        });
    }
    
    // Display transaction history
    function displayTransactionHistory(data) {
        let html = '';
        
        if (data.floating_transactions && data.floating_transactions.length > 0) {
            html += '<div class="table-responsive">';
            html += '<table class="table table-hover">';
            html += '<thead><tr><th>Date</th><th>Type</th><th>Reference</th><th>Amount</th><th>Notes</th></tr></thead>';
            html += '<tbody>';
            
            data.floating_transactions.forEach(function(transaction) {
                const isDebit = transaction.debit > 0;
                const amount = isDebit ? `-Rs ${parseFloat(transaction.debit).toFixed(2)}` : `+Rs ${parseFloat(transaction.credit).toFixed(2)}`;
                const amountColor = isDebit ? '#dc3545' : '#198754';
                
                html += `<tr class="transaction-row">
                    <td>${new Date(transaction.transaction_date).toLocaleDateString()}</td>
                    <td><span style="background-color: #f8f9fa; color: #495057; padding: 3px 8px; border-radius: 3px; font-size: 11px;">${transaction.transaction_type.replace('_', ' ').toUpperCase()}</span></td>
                    <td>${transaction.reference_no || 'N/A'}</td>
                    <td><span style="color: ${amountColor};">${amount}</span></td>
                    <td>${transaction.notes || 'N/A'}</td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
        } else {
            html = '<div class="alert alert-info">No floating balance transactions found.</div>';
        }
        
        $('#transactionHistoryContainer').html(html);
    }
    
    // Load transaction history on page load
    loadTransactionHistory();
    
    // Refresh button
    $('#refreshHistoryBtn').click(function() {
        loadTransactionHistory();
    });
    
    // Record recovery payment
    $('.record-recovery-btn').click(function() {
        const paymentId = $(this).data('payment-id');
        const customerId = $(this).data('customer-id');
        const amount = $(this).data('amount');
        
        $('#recoveryCustomerId').val(customerId);
        $('#recoveryPaymentId').val(paymentId);
        $('#recoveryAmount').val(amount);
        $('#recoveryPaymentDate').val(new Date().toISOString().split('T')[0]);
        
        $('#recoveryPaymentModal').modal('show');
    });
    
    // Submit recovery payment
    $('#recoveryPaymentForm').submit(function(e) {
        e.preventDefault();
        
        const formData = {
            amount: $('#recoveryAmount').val(),
            payment_method: $('#recoveryPaymentMethod').val(),
            payment_date: $('#recoveryPaymentDate').val(),
            reference_no: $('#recoveryReferenceNo').val(),
            notes: $('#recoveryNotes').val()
        };
        
        $.ajax({
            url: `/floating-balance/customer/${customerId}/recovery-payment`,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status === 200) {
                    toastr.success('Recovery payment recorded successfully');
                    $('#recoveryPaymentModal').modal('hide');
                    location.reload(); // Refresh the page to show updated balances
                } else {
                    toastr.error(response.message || 'Error recording payment');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    Object.values(response.errors).forEach(function(error) {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('Error recording recovery payment');
                }
            }
        });
    });
});
</script>
@endpush