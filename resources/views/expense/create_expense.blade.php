@extends('layout.layout')
@section('content')
    <style>
        .expense-form {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .item-row {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
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
                            <input type="hidden" name="expense_id" id="expense_id" value="">`
                            
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
                                        <select class="form-control select2" name="location_id" id="location_id" required>
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

                            <!-- Category Information -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="mb-3">Category & Supplier Information</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Expense Category <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="expense_parent_category_id" id="expense_parent_category_id" required>
                                            <option value="">Select Category</option>
                                            @if(isset($expenseParentCategories))
                                                @foreach($expenseParentCategories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->expenseParentCatergoryName }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <span class="text-danger" id="expense_parent_category_id_error"></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Sub Category</label>
                                        <select class="form-control select2" name="expense_sub_category_id" id="expense_sub_category_id">
                                            <option value="">Select Sub Category</option>
                                        </select>
                                        <span class="text-danger" id="expense_sub_category_id_error"></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Supplier <span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="supplier_id" id="supplier_id" required>
                                            <option value="">Select Supplier</option>
                                            @if(isset($suppliers))
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" data-balance="{{ $supplier->formatted_expense_balance ?? 'Rs.0.00' }}">
                                                        {{ $supplier->full_name }} ({{ $supplier->mobile_no }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <small class="text-muted">Select the supplier for this expense</small>
                                        <span class="text-danger" id="supplier_id_error"></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Paid To <small class="text-muted">(Additional Info)</small></label>
                                        <input type="text" class="form-control" name="paid_to" id="paid_to" placeholder="Enter additional details">
                                        <small class="text-muted">Optional: Additional person/company info</small>
                                        <span class="text-danger" id="paid_to_error"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Expense Items -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5>Expense Items</h5>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">
                                            <i class="feather-plus"></i> Add Item
                                        </button>
                                    </div>
                                    
                                    <div id="itemsContainer">
                                        <!-- Items will be added here -->
                                    </div>
                                    <span class="text-danger" id="items_error"></span>
                                </div>
                            </div>

                            <!-- Additional Charges -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h5 class="mb-3">Additional Charges</h5>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Tax Amount</label>
                                        <input type="number" class="form-control" name="tax_amount" id="tax_amount" value="0" step="0.01" min="0">
                                        <span class="text-danger" id="tax_amount_error"></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Discount Type</label>
                                        <select class="form-control" name="discount_type" id="discount_type">
                                            <option value="fixed">Fixed Amount</option>
                                            <option value="percentage">Percentage</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Discount Amount</label>
                                        <input type="number" class="form-control" name="discount_amount" id="discount_amount" value="0" step="0.01" min="0">
                                        <span class="text-danger" id="discount_amount_error"></span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Shipping Charges</label>
                                        <input type="number" class="form-control" name="shipping_charges" id="shipping_charges" value="0" step="0.01" min="0">
                                        <span class="text-danger" id="shipping_charges_error"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Section -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="total-section">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5>Payment Information</h5>
                                                
                                                <div class="form-group mb-3">
                                                    <label>Payment Method <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="payment_method" id="payment_method" required>
                                                        <option value="">Select Payment Method</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="check">Check</option>
                                                        <option value="card">Card</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                    <span class="text-danger" id="payment_method_error"></span>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label>Paid Amount <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="paid_amount" id="paid_amount" value="0" step="0.01" min="0" required>
                                                    <span class="text-danger" id="paid_amount_error"></span>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <h5>Total Calculation</h5>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <td>Subtotal:</td>
                                                        <td class="text-end"><strong id="subtotalDisplay">Rs.0.00</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Tax:</td>
                                                        <td class="text-end"><strong id="taxDisplay">Rs.0.00</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Discount:</td>
                                                        <td class="text-end"><strong id="discountDisplay">Rs.0.00</strong></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Shipping:</td>
                                                        <td class="text-end"><strong id="shippingDisplay">Rs.0.00</strong></td>
                                                    </tr>
                                                    <tr class="table-active">
                                                        <td><strong>Total Amount:</strong></td>
                                                        <td class="text-end"><strong id="totalDisplay">Rs.0.00</strong></td>
                                                    </tr>
                                                </table>
                                                
                                                <input type="hidden" name="total_amount" id="total_amount" value="0">
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