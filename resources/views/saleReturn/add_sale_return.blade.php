@extends('layout.layout')
@section('content')
@php
    $canUseFreeQty = (bool)(\App\Models\Setting::value('enable_free_qty') ?? 1) && auth()->user()?->can('use free quantity');
    $canManageTax = $canManageTax ?? \App\Support\TaxSettingsAccess::canManage();
    $isEditSaleReturn = isset($salesReturn);
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/sale-return.css') }}">

<div class="container-fluid sr-page">
    <div class="page-header">
        <div class="page-sub-header">
            <h3 class="page-title" id="form-title">Add Sale Return</h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Sell</a></li>
                <li class="breadcrumb-item active" id="breadcrumb-title">Add Sale Return</li>
            </ul>
        </div>
    </div>

    <form id="salesReturnForm">
        @csrf
        <div class="sr-card">
            <div class="sr-toolbar">
                <div class="sr-billing-toggle" role="group" aria-label="Billing option">
                    <input type="radio" name="billingOption" id="withBill" value="withBill" checked>
                    <label for="withBill" class="sr-billing-btn">With Bill</label>
                    <input type="radio" name="billingOption" id="withoutBill" value="withoutBill">
                    <label for="withoutBill" class="sr-billing-btn">Without Bill</label>
                </div>
                <div class="sr-fields">
                    <div class="sr-field sr-field--date">
                        <label for="date">Date</label>
                        <input type="date" class="form-control" id="date" name="return_date" required>
                    </div>
                    <div class="sr-field sr-field--check">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="isDefective" name="is_defective" value="1">
                            <label class="form-check-label" for="isDefective">Defective</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- With Bill: invoice sets location automatically --}}
            <div class="sr-mode-panel" id="invoiceDetailsSection">
                <div class="sr-mode-title">Original sale</div>
                <div class="sr-with-bill-grid">
                    <div class="sr-invoice-search">
                        <label for="invoiceNo" class="form-label small text-muted mb-1">Invoice number</label>
                        <input type="text" class="form-control" id="invoiceNo" name="invoiceNo"
                            placeholder="Search invoice no. and press Enter" autocomplete="off">
                        <input type="hidden" id="sale-id" name="sale_id">
                        <input type="hidden" id="customer-id" name="customer_id">
                        <input type="hidden" id="locationIdBill" name="location_id" value="">
                    </div>
                    <div class="sr-sale-meta">
                        <p id="displayInvoiceNo"><strong>Invoice:</strong> —</p>
                        <p id="displayDate"><strong>Date:</strong> —</p>
                        <p id="displayCustomer"><strong>Customer:</strong> —</p>
                        <p id="displayLocation"><strong>Location:</strong> —</p>
                    </div>
                </div>
            </div>

            {{-- Without Bill: choose return location + customer --}}
            <div class="sr-mode-panel" id="customerSelectionSection" style="display: none;">
                <div class="sr-without-bill-grid">
                    <div class="sr-field">
                        <label for="locationId">Return to location</label>
                        <select id="locationId" class="form-select" required>
                            <option value="">Select location</option>
                        </select>
                        <small class="text-muted d-block mt-1">Stock will be added to this location</small>
                    </div>
                    <div class="sr-field">
                        <label for="customerId">Customer</label>
                        <div class="sr-customer-row">
                            <select id="customerId" class="form-select selectBox">
                                <option value="">Select customer</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="addCustomerButton" title="Add customer">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sr-mode-panel" id="productSearchSection" style="display: none;">
                <div class="sr-mode-title">Add products</div>
                <label for="productSearch" class="form-label small text-muted mb-1">Search by name or SKU</label>
                <input type="text" class="form-control" id="productSearch"
                    placeholder="Type to search…" autocomplete="off">
            </div>

            <div class="sr-table-section">
                <div class="sr-table-wrap">
                    <table id="productsTable" class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th id="stockColumn">Sold Qty</th>
                                <th>Return Qty</th>
                                @if($canUseFreeQty)<th id="freeStockColumn">Sold Free</th>@endif
                                @if($canUseFreeQty)<th>Return Free</th>@endif
                                @if($canManageTax)<th>VAT</th>@endif
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody"></tbody>
                    </table>
                </div>
                <p class="sr-empty-hint" id="srEmptyHint">
                    <i class="fas fa-box-open"></i>
                    <span id="srEmptyHintText">Enter invoice number to load sale lines</span>
                </p>
            </div>

            <div class="sr-footer">
                <div class="sr-totals">
                    <span>Subtotal: <strong id="returnSubtotalDisplay">Rs. 0.00</strong></span>
                    @if($canManageTax)<span>VAT: <strong id="totalReturnVat">Rs. 0.00</strong></span>@endif
                    <span>Discount: <strong id="totalReturnDiscount">Rs. 0.00</strong></span>
                    <span class="sr-total-final">Total: <strong id="returnTotalDisplay">Rs. 0.00</strong></span>
                </div>
                <div class="sr-footer-grid">
                    <div class="sr-footer-left">
                        <div>
                            <label for="discountType" class="form-label">Discount type</label>
                            <select id="discountType" class="form-select form-select-sm">
                                <option value="">None</option>
                                <option value="percentage">%</option>
                                <option value="flat">Flat</option>
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
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                placeholder="Return reason (required)" required></textarea>
                        </div>
                    </div>
                    <div class="sr-actions">
                        <button type="submit" class="btn btn-primary" id="srSubmitBtn">
                            @if($isEditSaleReturn)
                                <i class="fas fa-save me-1"></i> Update Return
                            @else
                                <i class="fas fa-check me-1"></i> Save Return
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Remove product?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-2">Remove this line from the return?</div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteButton">Remove</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateEl = document.getElementById('date');
    if (dateEl && !dateEl.value) dateEl.value = today;

    function syncLocationFieldNames(withBill) {
        const locSelect = document.getElementById('locationId');
        const locBill = document.getElementById('locationIdBill');
        if (!locSelect || !locBill) return;
        if (withBill) {
            locBill.setAttribute('name', 'location_id');
            locSelect.removeAttribute('name');
        } else {
            locSelect.setAttribute('name', 'location_id');
            locBill.removeAttribute('name');
            locBill.value = '';
        }
    }

    function updateEmptyHint() {
        const withBill = document.getElementById('withBill').checked;
        const hint = document.getElementById('srEmptyHintText');
        const rows = document.querySelectorAll('#productsTableBody tr').length;
        const wrap = document.getElementById('srEmptyHint');
        if (hint) {
            hint.textContent = withBill
                ? 'Enter invoice number to load sale lines'
                : 'Select location, then search and add products';
        }
        if (wrap) wrap.style.display = rows > 0 ? 'none' : 'block';
    }

    function toggleSearchSections() {
        const withBill = document.getElementById('withBill').checked;
        const invoiceDetailsSection = document.getElementById('invoiceDetailsSection');
        const productSearchSection = document.getElementById('productSearchSection');
        const productsTableBody = document.getElementById('productsTableBody');
        const stockColumn = document.getElementById('stockColumn');
        const freeStockColumn = document.getElementById('freeStockColumn');
        const customerSelectionSection = document.getElementById('customerSelectionSection');

        syncLocationFieldNames(withBill);

        if (withBill) {
            invoiceDetailsSection.style.display = 'block';
            productSearchSection.style.display = 'none';
            customerSelectionSection.style.display = 'none';
            if (stockColumn) stockColumn.textContent = 'Sold Qty';
            if (freeStockColumn) freeStockColumn.textContent = 'Sold Free';
            document.getElementById('customer-id')?.setAttribute('name', 'customer_id');
            document.getElementById('customerId')?.removeAttribute('name');
            document.getElementById('invoiceNo').disabled = false;
            document.getElementById('sale-id').disabled = false;
            document.getElementById('productSearch').disabled = true;
        } else {
            invoiceDetailsSection.style.display = 'none';
            productSearchSection.style.display = 'block';
            customerSelectionSection.style.display = 'block';
            if (stockColumn) stockColumn.textContent = 'Stock';
            if (freeStockColumn) freeStockColumn.textContent = 'Free Stock';
            document.getElementById('customer-id')?.removeAttribute('name');
            document.getElementById('customerId')?.setAttribute('name', 'customer_id');
            document.getElementById('invoiceNo').disabled = true;
            document.getElementById('sale-id').disabled = true;
            document.getElementById('productSearch').disabled = false;
            document.getElementById('locationIdBill').value = '';
            document.getElementById('displayLocation').innerHTML = '<strong>Location:</strong> —';
        }

        productsTableBody.innerHTML = '';
        updateEmptyHint();
    }

    document.getElementById('withBill').addEventListener('change', toggleSearchSections);
    document.getElementById('withoutBill').addEventListener('change', toggleSearchSections);

    const observer = new MutationObserver(updateEmptyHint);
    const tbody = document.getElementById('productsTableBody');
    if (tbody) observer.observe(tbody, { childList: true });

    toggleSearchSections();
});
</script>

@include('contact.customer.add_customer_modal')
@include('contact.customer.city_modal')
@include('contact.customer.customer_ajax_bootstrap')
@include('contact.customer.cities_ajax')
@include('saleReturn.sale_return_ajax')
@endsection

