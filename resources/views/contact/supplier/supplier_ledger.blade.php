@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Supplier Ledger</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supplier Ledger</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form id="ledgerFilterForm" class="border p-3 rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="supplier_id" class="form-label"><strong>Supplier <span class="text-danger">*</span></strong></label>
                                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="location_id" class="form-label"><strong>Business Location</strong></label>
                                        <select class="form-select" id="location_id" name="location_id">
                                            <option value="">All Locations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="start_date" class="form-label"><strong>Start Date <span class="text-danger">*</span></strong></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="2025-01-01" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="end_date" class="form-label"><strong>End Date <span class="text-danger">*</span></strong></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="2025-12-31" required>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="w-100">
                                            <div id="filterStatus" class="text-center">
                                                <i class="fa fa-check-circle text-success"></i>
                                                <small class="text-success">Ready</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Auto-selected Supplier Notification -->
                    <div id="autoSelectedNotification" class="alert alert-info d-none">
                        <i class="fa fa-info-circle"></i> 
                        <span id="autoSelectedMessage">Supplier automatically selected from supplier list.</span>
                    </div>

                    <!-- Supplier Details Section -->
                    <div id="supplierDetailsSection" style="display: none;">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Supplier Information</h5>
                                        <div id="supplierDetails"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body">
                                        <h5 class="card-title text-dark">Account Summary</h5>
                                        <div id="accountSummary"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Supplier Advance Payment Actions -->
                        <div id="advanceActionsSection" class="row mb-3" style="display: none;">
                            <div class="col-12">
                                <div class="card border-primary">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="card-title mb-2 text-primary">
                                                    <i class="fa fa-credit-card"></i> Supplier Advance Management
                                                </h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Available Advance: <strong class="text-primary">₹<span id="advanceAmount">0.00</span></strong></small><br>
                                                        <small id="advanceStatusText" class="text-info">Ready to apply</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Status: <span class="badge badge-info" id="advanceStatus">Ready</span></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-right">
                                                <button type="button" id="applyAdvanceBtn" class="btn btn-primary btn-sm mb-1" style="min-width: 150px;">
                                                    <i class="fa fa-magic"></i> Apply to Bills
                                                </button><br>
                                                <button type="button" id="manageAdvanceBtn" class="btn btn-outline-warning btn-sm mb-1" style="min-width: 150px;">
                                                    <i class="fa fa-cog"></i> Manage Advance
                                                </button><br>
                                                <button type="button" id="refreshLedgerBtn" class="btn btn-outline-info btn-sm" style="min-width: 150px;">
                                                    <i class="fa fa-refresh"></i> Refresh
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ledger Table -->
                    <div id="ledgerTableSection" style="display: none;">
                        <div class="table-responsive">
                            <table class="datatable table table-striped table-bordered" id="ledgerTable" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Payment Status</th>
                                        <th>Debit (Rs.)</th>
                                        <th>Credit (Rs.)</th>
                                        <th>Balance (Rs.)</th>
                                        <th>Payment Method</th>
                                        <th>Others</th>
                                    </tr>
                                </thead>
                                <tbody id="ledgerTableBody">
                                    <!-- Ledger entries will be populated here -->
                                </tbody>
                                <tfoot id="ledgerTableFooter" class="table-secondary">
                                    <tr class="fw-bold">
                                        <td colspan="6" class="text-end">Total:</td>
                                        <td class="text-end" id="totalDebit">Rs. 0.00</td>
                                        <td class="text-end" id="totalCredit">Rs. 0.00</td>
                                        <td class="text-end" id="totalBalance">Rs. 0.00</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- No Data Message -->
                    <div id="noDataMessage" style="display: none;">
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> No transactions found for the selected criteria.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Supplier Advance Management Modal -->
<div class="modal fade" id="advanceManagementModal" tabindex="-1" role="dialog" aria-labelledby="advanceManagementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="advanceManagementModalLabel">
                    <i class="fa fa-credit-card"></i> Supplier Advance Management
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fa fa-plus-circle"></i> Apply Advance to Bills</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Apply supplier's advance amount to reduce outstanding purchase bills.</p>
                                <div class="form-group">
                                    <label>Available Advance Amount:</label>
                                    <h4 class="text-primary">₹<span id="modalAdvanceAmount">0.00</span></h4>
                                </div>
                                <button type="button" id="modalApplyAdvanceBtn" class="btn btn-primary btn-block">
                                    <i class="fa fa-magic"></i> Apply to Outstanding Bills
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fa fa-exchange"></i> Manage Returns & Credits</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Handle purchase returns and decide how to process credit amounts.</p>
                                <div class="form-group">
                                    <label>Return Processing Options:</label>
                                    <div class="btn-group-vertical btn-block">
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="handleReturnOption('cash')">
                                            <i class="fa fa-money"></i> Cash Credit
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="handleReturnOption('advance')">
                                            <i class="fa fa-credit-card"></i> Add to Advance
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="handleReturnOption('adjust')">
                                            <i class="fa fa-adjust"></i> Adjust Against Bills
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fa fa-cogs"></i> Advanced Options</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Manual Advance Entry:</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="manualAdvanceAmount" placeholder="0.00" step="0.01" min="0">
                                                <div class="input-group-append">
                                                    <button class="btn btn-info" type="button" onclick="addManualAdvance()">
                                                        <i class="fa fa-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Advance Adjustment:</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="adjustAdvanceAmount" placeholder="0.00" step="0.01" min="0">
                                                <div class="input-group-append">
                                                    <button class="btn btn-danger" type="button" onclick="adjustAdvance()">
                                                        <i class="fa fa-minus"></i> Deduct
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="refreshAdvanceData()">
                    <i class="fa fa-refresh"></i> Refresh Data
                </button>
            </div>
        </div>
    </div>
</div>

@include('contact.supplier.supplier_ledger_ajax')

@if(isset($supplierId))
<script>
    // Auto-select supplier from controller parameter
    $(document).ready(function() {
        setTimeout(function() {
            if ($('#supplier_id').length) {
                $('#supplier_id').val('{{ $supplierId }}').trigger('change');
                loadSupplierDetails('{{ $supplierId }}');
                loadSupplierLedger();
            }
        }, 1000); // Wait for suppliers to load
    });
</script>
@endif

@endsection
