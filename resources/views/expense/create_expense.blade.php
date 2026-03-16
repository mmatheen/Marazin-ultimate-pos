@extends('layout.layout')
@section('content')
    <style>
        .expense-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }

        .section-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .expense-items-table th,
        .expense-items-table td {
            vertical-align: middle;
        }

        .expense-items-table .form-control,
        .expense-items-table .form-select {
            min-width: 140px;
        }

        .total-section {
            background: #e9ecef;
            border-radius: 5px;
            padding: 15px;
        }
    </style>

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title" id="pageTitle">Create New Expense</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Expenses</li>
                                <li class="breadcrumb-item active" id="breadcrumbTitle">Create Expense</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense Form -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body expense-form">
                        <form id="expenseForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="expense_id" id="expense_id" value="">

                            <!-- Basic Information -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="mb-3">Basic Information</h5>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Expense No. <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="expense_no" id="expense_no" value="{{ $expenseNo ?? '' }}" readonly>
                                        <span class="text-danger" id="expense_no_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="date" id="date" value="{{ date('Y-m-d') }}" required>
                                        <small class="text-muted">Date format: DD-MM-YYYY</small>
                                        <span class="text-danger" id="date_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Reference No.</label>
                                        <input type="text" class="form-control" name="reference_no" id="reference_no" placeholder="Enter reference number">
                                        <span class="text-danger" id="reference_no_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Location <span class="text-danger">*</span></label>
                                        <select class="form-control select2 selectBox" name="location_id" id="location_id" required>
                                            <option value="">Select Location</option>
                                        </select>
                                        <span class="text-danger" id="location_id_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Attachment</label>
                                        <input type="file" class="form-control" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png">
                                        <span class="text-danger" id="attachment_error"></span>
                                        <small class="text-muted">Max size: 2MB (PDF, JPG, PNG)</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Expense Items Section -->
                            <div class="section-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Expense Items</h5>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                                        <i class="feather-plus"></i> Add Row
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered expense-items-table mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="min-width:180px;">Category <span class="text-danger">*</span></th>
                                                <th style="min-width:180px;">Sub Category</th>
                                                <th>Description <span class="text-danger">*</span></th>
                                                <th style="min-width:140px;">Amount <span class="text-danger">*</span></th>
                                                <th style="width:70px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsContainer"></tbody>
                                    </table>
                                </div>
                                <span class="text-danger" id="items_error"></span>
                            </div>

                            <!-- Totals and Payment -->
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="section-card h-100">
                                        <h5>Total Expense</h5>
                                        <h3 class="mb-1" id="totalDisplay">Rs.0.00</h3>
                                        <small class="text-muted">Auto-calculated from expense items.</small>
                                        <input type="hidden" name="total_amount" id="total_amount" value="0">
                                        <input type="hidden" name="expense_parent_category_id" id="expense_parent_category_id">
                                        <input type="hidden" name="expense_sub_category_id" id="expense_sub_category_id">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="section-card h-100">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group mb-3">
                                                    <label>Payment Method <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="payment_method" id="payment_method" required>
                                                        <option value="">Select Payment Method</option>
                                                        <option value="cash" selected>Cash</option>
                                                        <option value="bank_transfer">Bank</option>
                                                        <option value="card">Card</option>
                                                        <option value="credit">Credit</option>
                                                    </select>
                                                    <span class="text-danger" id="payment_method_error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label>Paid Amount <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="paid_amount" id="paid_amount" value="0" step="0.01" min="0" required>
                                                    <small class="text-muted">For cash/bank/card this is auto set to total. For credit set partial amount if needed.</small>
                                                    <span class="text-danger" id="paid_amount_error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group mb-0">
                                                    <label>Paid To <small class="text-muted">(Vendor / Person / Company)</small></label>
                                                    <input type="text" class="form-control" name="paid_to" id="paid_to" placeholder="Enter person/company name">
                                                    <small class="text-muted">Example: Electricity board, internet provider, cleaner, transport person.</small>
                                                    <span class="text-danger" id="paid_to_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea class="form-control" name="note" id="note" rows="3" placeholder="Enter any additional notes"></textarea>
                                        <span class="text-danger" id="note_error"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-md-12 text-end">
                                    <a href="{{ route('expense.list') }}" class="btn btn-secondary me-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="feather-save"></i> Save Expense
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('expense.expense_unified_ajax')
@endsection
