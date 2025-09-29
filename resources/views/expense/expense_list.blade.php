@extends('layout.layout')
@section('content')
    <style>
        .expense-card {
            border-left: 4px solid #007bff;
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .amount-display {
            font-weight: 600;
        }
        
        /* Payment button styles */
        .payment_btn {
            border-color: #28a745 !important;
            color: #28a745 !important;
        }
        .payment_btn:hover {
            background-color: #28a745 !important;
            color: white !important;
        }
        .payment_history_btn {
            border-color: #ffc107 !important;
            color: #ffc107 !important;
        }
        .payment_history_btn:hover {
            background-color: #ffc107 !important;
            color: black !important;
        }
    </style>

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Expenses Management</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Expenses</li>
                                <li class="breadcrumb-item active">List All Expenses</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Expenses</h6>
                                <h4 id="totalExpenses">0</h4>
                            </div>
                            <div>
                                <i class="feather-file-text" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Amount</h6>
                                <h4 id="totalAmount">Rs.0.00</h4>
                            </div>
                            <div>
                                <i class="feather-dollar-sign" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Paid Amount</h6>
                                <h4 id="paidAmount">Rs.0.00</h4>
                            </div>
                            <div>
                                <i class="feather-check-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Due Amount</h6>
                                <h4 id="dueAmount">Rs.0.00</h4>
                            </div>
                            <div>
                                <i class="feather-alert-circle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="row mb-3">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <select class="form-control" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    @foreach($expenseParentCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->expenseParentCatergoryName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="subCategoryFilter">
                                    <option value="">All Sub Categories</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" id="paymentStatusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="partial">Partial</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" id="startDate" placeholder="Start Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" id="endDate" placeholder="End Date">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-primary" id="filterBtn">
                                    <i class="feather-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense List -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="card-title">Expenses List</h4>
                                </div>
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    @can('create expense')
                                        <a href="{{ route('expense.create') }}" class="btn btn-outline-primary me-2">
                                            <i class="feather-plus"></i> New Expense
                                        </a>
                                    @endcan
                                    <button type="button" class="btn btn-outline-info" id="exportBtn">
                                        <i class="feather-download"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="expenseTable" class="table table-striped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Expense No.</th>
                                        <th>Date</th>
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Supplier</th>
                                        <th>Location</th>
                                        <th>Total Amount</th>
                                        <th>Paid Amount</th>
                                        <th>Due Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Expense Modal -->
        <div id="viewExpenseModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Expense Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="expenseDetails">
                        <!-- Expense details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal custom-modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3>Delete Expense</h3>
                            <p>Are you sure you want to delete this expense?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <input type="hidden" id="deleting_id">
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary paid-continue-btn confirm_delete_btn" style="width: 100%;">Delete</button>
                                </div>
                                <div class="col-6">
                                    <button type="button" class="btn btn-primary paid-cancel-btn" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" id="payment_expense_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Expense No:</strong> <span id="paymentExpenseNo"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong> <span id="paymentTotalAmount"></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Paid Amount:</strong> <span id="paymentPaidAmount"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Due Amount:</strong> <span id="paymentDueAmount" class="text-danger"></span></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="payment_amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference No</label>
                                <input type="text" class="form-control" id="payment_reference" placeholder="Enter reference">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Note</label>
                        <textarea class="form-control" id="payment_note" rows="2" placeholder="Enter payment note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">Payment History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <p><strong>Expense No:</strong> <span id="historyExpenseNo"></span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Total:</strong> <span id="historyTotalAmount"></span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Paid:</strong> <span id="historyPaidAmount"></span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Due:</strong> <span id="historyDueAmount" class="text-danger"></span></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Status:</strong> <span id="historyPaymentStatus"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Location:</strong> <span id="historyLocationName"></span></p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="paymentHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Note</th>
                                <th>Created By</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_payment_id">
                    <input type="hidden" id="edit_expense_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Expense No:</strong> <span id="editPaymentExpenseNo"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong> <span id="editPaymentTotalAmount"></span></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_payment_amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference No</label>
                                <input type="text" class="form-control" id="edit_payment_reference" placeholder="Enter reference">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Note</label>
                        <textarea class="form-control" id="edit_payment_note" rows="2" placeholder="Enter payment note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>


    @include('expense.expense_unified_ajax')
@endsection