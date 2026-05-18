@extends('layout.layout')
@section('content')
@php
    $canUseFreeQty = (bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && auth()->user()?->can('use free quantity');
    $canManageTax = $canManageTax ?? \App\Support\TaxSettingsAccess::canManage();
    $withBill = (bool) $salesReturn->sale_id;
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/sale-return.css') }}">

<div class="container-fluid sr-page">
    <div class="page-header">
        <div class="page-sub-header">
            <h3 class="page-title">Edit Sale Return</h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Sell</a></li>
                <li class="breadcrumb-item"><a href="/sale-return/list">Sale Returns</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ul>
        </div>
    </div>

    <form id="salesReturnForm" data-edit-mode="true" data-return-id="{{ $salesReturn->id }}">
        @csrf
        <input type="hidden" name="sale_return_id" value="{{ $salesReturn->id }}">
        <input type="hidden" name="sale_id" id="sale-id" value="{{ $salesReturn->sale_id ?? '' }}">

        <div class="sr-card">
            <div class="sr-toolbar">
                <div class="sr-billing-toggle is-readonly" role="group">
                    <input type="radio" name="billingOption" id="withBill" value="withBill" class="d-none"
                        {{ $withBill ? 'checked' : '' }} disabled>
                    <label for="withBill" class="sr-billing-btn">With Bill</label>
                    <input type="radio" name="billingOption" id="withoutBill" value="withoutBill" class="d-none"
                        {{ !$withBill ? 'checked' : '' }} disabled>
                    <label for="withoutBill" class="sr-billing-btn">Without Bill</label>
                </div>
                <span class="sr-edit-note">Billing type cannot be changed</span>
                <div class="sr-fields">
                    <div class="sr-field sr-field--date">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" id="date" name="return_date"
                            value="{{ \Carbon\Carbon::parse($salesReturn->return_date)->format('Y-m-d') }}" required>
                    </div>
                    <div class="sr-field sr-field--check">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="isDefective" name="is_defective"
                                value="1" {{ $salesReturn->is_defective ? 'checked' : '' }}>
                            <label class="form-check-label" for="isDefective">Defective</label>
                        </div>
                    </div>
                </div>
            </div>

            @if($withBill)
                <div class="sr-mode-panel" id="invoiceDetailsSection">
                    <div class="sr-mode-title">Original sale</div>
                    <div class="sr-with-bill-grid">
                        <div>
                            <label for="invoiceNo" class="form-label small text-muted mb-1">Invoice</label>
                            <input type="text" class="form-control" id="invoiceNo"
                                value="{{ optional($salesReturn->sale)->invoice_no }}" disabled>
                        </div>
                        <div class="sr-sale-meta">
                            <p id="displayInvoiceNo"><strong>Invoice:</strong> {{ optional($salesReturn->sale)->invoice_no ?? '—' }}</p>
                            <p id="displayDate"><strong>Date:</strong> {{ optional($salesReturn->sale)->created_at ? \Carbon\Carbon::parse($salesReturn->sale->created_at)->format('d/m/Y') : '—' }}</p>
                            <p id="displayCustomer"><strong>Customer:</strong> {{ trim((optional($salesReturn->customer)->first_name ?? '') . ' ' . (optional($salesReturn->customer)->last_name ?? '')) ?: '—' }}</p>
                            <p id="displayLocation"><strong>Location:</strong> {{ optional($salesReturn->location)->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="customer-id" name="customer_id" value="{{ $salesReturn->customer_id }}">
                <input type="hidden" id="locationIdBill" name="location_id" value="{{ $salesReturn->location_id }}">
            @else
                <div class="sr-mode-panel" id="customerSelectionSection">
                    <div class="sr-without-bill-grid">
                        <div class="sr-field">
                            <label for="locationId">Return to location</label>
                            <select id="locationId" name="location_id" class="form-select" required>
                                <option value="">Select location</option>
                            </select>
                        </div>
                        <div class="sr-field">
                            <label for="customerId">Customer</label>
                            <select id="customerId" name="customer_id" class="form-select selectBox">
                                <option value="">Select customer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="sr-mode-panel" id="productSearchSection">
                    <div class="sr-mode-title">Add products</div>
                    <input type="text" class="form-control" id="productSearch"
                        placeholder="Search by name or SKU" autocomplete="off">
                </div>
            @endif

            <div class="sr-table-section">
                <div class="sr-table-wrap">
                    <table id="productsTable" class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th id="stockColumn">{{ $withBill ? 'Sold Qty' : 'Stock' }}</th>
                                <th>Return Qty</th>
                                @if($canUseFreeQty)<th id="freeStockColumn">{{ $withBill ? 'Sold Free' : 'Free Stock' }}</th>@endif
                                @if($canUseFreeQty)<th>Return Free</th>@endif
                                @if($canManageTax)<th>VAT</th>@endif
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="sr-footer">
                <div class="sr-totals">
                    <span>Discount: <strong id="totalReturnDiscount">Rs. 0.00</strong></span>
                    <span class="sr-total-final">Total: <strong id="returnTotalDisplay">Rs. 0.00</strong></span>
                </div>
                <div class="sr-footer-grid">
                    <div class="sr-footer-left">
                        <div>
                            <label for="discountType" class="form-label">Discount type</label>
                            <select class="form-select form-select-sm" id="discountType">
                                <option value="">None</option>
                                <option value="flat">Flat</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                        <div>
                            <label for="discountAmount" class="form-label">Discount</label>
                            <input type="number" class="form-control form-control-sm" id="discountAmount" placeholder="0" step="any">
                        </div>
                        <div>
                            <label for="returnTotal" class="form-label">Return total</label>
                            <input type="number" class="form-control form-control-sm fw-bold" id="returnTotal"
                                name="return_total" readonly required>
                        </div>
                        <div class="sr-notes-wrap">
                            <label for="notes" class="form-label">Reason / notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" required>{{ $salesReturn->notes }}</textarea>
                        </div>
                    </div>
                    <div class="sr-actions">
                        <a href="/sale-return/list" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="srSubmitBtn">
                            <i class="fas fa-save me-1"></i> Update Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    window.salesReturnData = @json($salesReturn);
    window.returnProducts = @json($salesReturn->returnProducts);
    window.isEditMode = true;
</script>

@include('contact.customer.add_customer_modal')
@include('contact.customer.city_modal')
@include('contact.customer.customer_ajax_bootstrap')
@include('contact.customer.cities_ajax')
@include('saleReturn.sale_return_ajax')
@endsection

