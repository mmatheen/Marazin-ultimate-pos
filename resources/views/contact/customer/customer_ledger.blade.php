@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Customer Ledger</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Customer Ledger</li>
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
                                        <label for="customer_id" class="form-label"><strong>Customer <span class="text-danger">*</span></strong></label>
                                        <select class="form-select" id="customer_id" name="customer_id" required>
                                            <option value="">Select Customer</option>
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

                    <!-- Auto-selected Customer Notification -->
                    <div id="autoSelectedNotification" class="alert alert-info d-none">
                        <i class="fa fa-info-circle"></i> 
                        <span id="autoSelectedMessage">Customer automatically selected from customer list.</span>
                    </div>

                    <!-- Customer Details Section -->
                    <div id="customerDetailsSection" style="display: none;">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Customer Information</h5>
                                        <div id="customerDetails"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h5 class="card-title text-white">Account Summary</h5>
                                        <div id="accountSummary"></div>
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

@include('contact.customer.customer_ledger_ajax')

@if(isset($customerId))
<script>
    // Auto-select customer from controller parameter
    $(document).ready(function() {
        setTimeout(function() {
            if ($('#customer_id').length) {
                $('#customer_id').val('{{ $customerId }}').trigger('change');
                loadCustomerDetails('{{ $customerId }}');
                loadCustomerLedger();
            }
        }, 1000); // Wait for customers to load
    });
</script>
@endif

@endsection
