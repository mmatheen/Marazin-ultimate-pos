@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Account Ledger</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Account Ledger</li>
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
                    <!-- Ledger Type Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="border p-3 rounded bg-light">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label for="ledger_type" class="form-label"><strong>Ledger Type <span class="text-danger">*</span></strong></label>
                                        <select class="form-control selectBox" id="ledger_type" name="ledger_type" required>
                                            <option value="">Select Ledger Type</option>
                                            <option value="customer">Customer Ledger</option>
                                            <option value="supplier">Supplier Ledger</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="contact_id" class="form-label"><strong><span id="contact_label">Contact</span> <span class="text-danger">*</span></strong></label>
                                        <select class="form-control selectBox" id="contact_id" name="contact_id" required disabled>
                                            <option value="">Select Contact</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="location_id" class="form-label"><strong>Business Location</strong></label>
                                        <select class="form-control selectBox" id="location_id" name="location_id">
                                            <option value="">All Locations</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <div class="text-center mt-2">
                                            <div id="loadingStatus" style="display: none;">
                                                <i class="fa fa-spinner fa-spin text-primary"></i>
                                                <small class="text-primary">Loading ledger...</small>
                                            </div>
                                            <div id="readyStatus">
                                                <i class="fa fa-check-circle text-success"></i>
                                                <small class="text-success">Select contact to load</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        <label for="start_date" class="form-label"><strong>Start Date</strong></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="" placeholder="From beginning">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end_date" class="form-label"><strong>End Date <span class="text-danger">*</span></strong></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label"><strong>View Options</strong></label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="show_full_history" name="show_full_history">
                                            <label class="form-check-label" for="show_full_history">
                                                <small>Show Full Audit Trail</small>
                                            </label>
                                            <div class="text-muted" style="font-size: 10px;">
                                                Shows all transaction history including edits and reversals
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mt-4 text-center">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn" style="display: none;">
                                                <i class="fas fa-refresh"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-selected Contact Notification -->
                    <div id="autoSelectedNotification" class="alert alert-info d-none mb-4">
                        <i class="fa fa-info-circle"></i>
                        <span id="autoSelectedMessage">Contact automatically selected from URL parameters.</span>
                    </div>

                    <!-- Contact Details Section -->
                    <div id="contactDetailsSection" style="display: none;">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title"><span id="contact_info_title">Contact</span> Information</h5>
                                        <div id="contactDetails"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card" id="summaryCard">
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Account Summary</h5>
                                        <div id="accountSummary"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advance Management Section (Customer Only) -->
                    <div id="advanceActionsSection" class="row mb-3" style="display: none;">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-body p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="card-title mb-2 text-primary">
                                                <i class="fas fa-money-bill-wave"></i> Advanced Payment Management
                                            </h6>
                                            <p class="mb-0 text-muted">Available Advance: <strong id="advanceAmount">Rs. 0.00</strong></p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="button" class="btn btn-sm btn-success me-2" id="applyAdvanceBtn" disabled>
                                                <i class="fas fa-check"></i> Apply to Outstanding
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" id="manageAdvanceBtn" disabled>
                                                <i class="fas fa-cog"></i> Manage
                                            </button>
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

@include('reports.account_ledger_ajax')

@endsection
