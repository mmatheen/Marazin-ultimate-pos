@extends('layout.layout')
@section('content')
@php
    $canUseFreeQty = (bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && auth()->user()?->can('use free quantity');
@endphp
    <div class="container-fluid my-5">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Edit Sale Return</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Sell</a></li>
                                <li class="breadcrumb-item"><a href="/sale-return/list">Sale Returns</a></li>
                                <li class="breadcrumb-item active">Edit Sale Return</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Option Selection (Read-only for edit) -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body text-center">
                <h4 class="card-title mb-4 fw-bold">Billing Type</h4>
                <div class="d-flex justify-content-center gap-3">
                    <input type="radio" name="billingOption" id="withBill" value="withBill" class="d-none"
                        {{ $salesReturn->sale_id ? 'checked' : '' }} disabled>
                    <label for="withBill" class="billing-btn {{ $salesReturn->sale_id ? 'active' : '' }}">
                        With Bill
                    </label>

                    <input type="radio" name="billingOption" id="withoutBill" value="withoutBill" class="d-none"
                        {{ !$salesReturn->sale_id ? 'checked' : '' }} disabled>
                    <label for="withoutBill" class="billing-btn {{ !$salesReturn->sale_id ? 'active' : '' }}">
                        Without Bill
                    </label>
                </div>
                <p class="text-muted mt-2"><small>Billing type cannot be changed when editing</small></p>
            </div>
        </div>

        <style>
            .billing-btn {
                padding: 12px 24px;
                font-size: 16px;
                font-weight: 600;
                border-radius: 30px;
                cursor: not-allowed;
                transition: all 0.3s ease-in-out;
                background: #f8f9fa;
                border: 2px solid #ddd;
                color: #333;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
                text-align: center;
                display: inline-block;
                min-width: 140px;
                opacity: 0.7;
            }

            .billing-btn.active {
                background: #007bff;
                border-color: #007bff;
                color: white;
                box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
                opacity: 1;
            }
        </style>

        <!-- Sale Return Form -->
        <div class="card">
            <div class="card-body">
                <form id="salesReturnForm" data-edit-mode="true" data-return-id="{{ $salesReturn->id }}">
                    @csrf

                    <!-- Hidden field to track we're editing -->
                    <input type="hidden" name="sale_return_id" value="{{ $salesReturn->id }}">
                    <input type="hidden" name="sale_id" id="sale-id" value="{{ $salesReturn->sale_id ?? '' }}">

                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title">Common Details</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Date:</label>
                                        <input type="date" class="form-control" id="date" name="return_date"
                                            value="{{ \Carbon\Carbon::parse($salesReturn->return_date)->format('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="locationId" class="form-label">Location:</label>
                                        <select id="locationId" name="location_id" class="form-select" required>
                                            <option value="">Select Location</option>
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="isDefective" name="is_defective"
                                            value="1" {{ $salesReturn->is_defective ? 'checked' : '' }}>
                                        <label class="form-check-label" for="isDefective">Is Defective</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($salesReturn->sale_id)
                        <!-- Invoice Details Section (With Bill) -->
                        <div class="card mb-4" id="invoiceDetailsSection">
                            <div class="card-body">
                                <h4 class="card-title">Invoice Details</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="invoiceNo" class="form-label">Invoice No:</label>
                                            <input type="text" class="form-control" id="invoiceNo"
                                                value="{{ optional($salesReturn->sale)->invoice_no }}"
                                                placeholder="Search invoice" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mt-3">
                                        <div id="displayInvoiceNo"><strong>Invoice No.:</strong> {{ optional($salesReturn->sale)->invoice_no }}</div>
                                        <div id="displayDate"><strong>Date:</strong> {{ optional($salesReturn->sale)->created_at ? \Carbon\Carbon::parse($salesReturn->sale->created_at)->format('d/m/Y') : '' }}</div>
                                        <div id="displayCustomer"><strong>Customer:</strong> {{ optional($salesReturn->customer)->first_name }} {{ optional($salesReturn->customer)->last_name }}</div>
                                        <div id="displayLocation"><strong>Business Location:</strong> {{ optional($salesReturn->location)->name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden customer ID for with bill -->
                        <input type="hidden" id="customer-id" name="customer_id" value="{{ $salesReturn->customer_id }}">
                    @else
                        <!-- Product Search Section (Without Bill) -->
                        <div class="card mb-4" id="productSearchSection">
                            <div class="card-body">
                                <h4 class="card-title">Product Search</h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="productSearch" class="form-label">Search Product:</label>
                                            <input type="text" class="form-control" id="productSearch"
                                                placeholder="Search by product name or SKU">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Selection Section (Without Bill) -->
                        <div class="card mb-4" id="customerSelectionSection">
                            <div class="card-body">
                                <h4 class="card-title">Customer Selection</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="customerId" class="form-label">Customer:</label>
                                            <select id="customerId" name="customer_id" class="form-select">
                                                <option value="">Select Customer</option>
                                                <!-- Will be populated by JavaScript -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Products Table -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h4 class="card-title">Products</h4>
                            <div class="table-responsive">
                                <table class="table table-striped" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product</th>
                                            <th>Return Price</th>
                                            <th id="stockColumn">{{ $salesReturn->sale_id ? 'Sales Quantity' : 'Current Total Stock' }}</th>
                                            <th>Return Qty</th>
                                            @if($canUseFreeQty)<th id="freeStockColumn">{{ $salesReturn->sale_id ? 'Sales Free Qty' : 'Current Free Stock' }}</th>@endif
                                            @if($canUseFreeQty)<th>Return Free Qty</th>@endif
                                            <th>Subtotal</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productsTableBody">
                                        <!-- Products will be loaded here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Discount Section -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discountType" class="form-label">Discount Type:</label>
                                <select class="form-select" id="discountType">
                                    <option value="">Select Discount Type</option>
                                    <option value="flat">Flat</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discountAmount" class="form-label">Discount Amount:</label>
                                <input type="number" class="form-control" id="discountAmount"
                                    placeholder="Enter discount">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="returnTotal" class="form-label">Return Total:</label>
                                <input type="number" class="form-control" id="returnTotal" name="return_total"
                                    placeholder="Enter total return amount" readonly required>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes:</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"
                                    placeholder="Enter return reason" required>{{ $salesReturn->notes }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <p><strong>Total Return Discount:</strong> <span id="totalReturnDiscount">Rs. 0.00</span></p>
                            <p><strong>Return Total:</strong> <span id="returnTotalDisplay">Rs. 0.00</span></p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <a href="/sale-return/list" class="btn btn-secondary btn-lg me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-lg">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* General table styling */
        #productsTable th,
        #productsTable td {
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }

        #productsTable th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .return-quantity,
        .return-free-quantity {
            width: 120px;
            height: 40px;
            font-size: 14px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .return-quantity:focus,
        .return-free-quantity:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        .quantity-error,
        .free-quantity-error {
            font-size: 12px;
            color: red;
            margin-top: 5px;
            display: none;
            text-align: left;
        }

        .btn-danger {
            padding: 6px 10px;
            font-size: 14px;
            border-radius: 4px;
            background-color: #dc3545;
            border: none;
            color: white;
            transition: background-color 0.3s ease;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>

    <script>
        // Pass data from Blade to JavaScript
        window.salesReturnData = @json($salesReturn);
        window.returnProducts = @json($salesReturn->returnProducts);
        window.isEditMode = true;
    </script>

    @include('contact.customer.add_customer_modal')
    @include('contact.customer.city_modal')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.cities_ajax')
    @include('saleReturn.sale_return_ajax')
@endsection
