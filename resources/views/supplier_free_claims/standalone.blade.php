@extends('layout.layout')
@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">New Standalone Free Qty Claim</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier-claims.index') }}">Supplier Claims</a></li>
                            <li class="breadcrumb-item active">New Standalone Claim</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card card-table">
                <div class="card-body">

                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-1"></i>
                        Use this when the supplier promises free items <strong>without a specific purchase bill</strong>.
                        No stock will be added yet — stock is added when you <strong>Receive</strong> against this claim.
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('supplier-claims.store-standalone') }}" method="POST" id="standaloneClaimForm" data-skip-global="true">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group local-forms">
                                    <label>Supplier <span class="login-danger">*</span></label>
                                    <select name="supplier_id" class="form-control form-select" required>
                                        <option value="">— Select Supplier —</option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                                                {{ $s->first_name }} {{ $s->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group local-forms calendar-icon">
                                    <label>Claim Date <span class="login-danger">*</span></label>
                                    <input type="date" name="claim_date" class="form-control"
                                           value="{{ old('claim_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group local-forms">
                                    <label>Default Stock Location <span class="login-danger">*</span></label>
                                    <select name="location_id" class="form-control form-select" required>
                                        <option value="">— Select Location —</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" @selected(old('location_id') == $loc->id)>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Product rows --}}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="fw-semibold mb-2">Claimed Products</label>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-center mb-0" id="claimItemsTable">
                                            <thead>
                                                <tr>
                                                    <th>Product <span class="login-danger">*</span></th>
                                                    <th style="width:200px">Claimed Qty <span class="login-danger">*</span></th>
                                                    <th style="width:60px" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="claimItemsBody">
                                                <tr class="claim-item-row">
                                                    <td>
                                                        <select name="items[0][product_id]" class="form-control product-select" required>
                                                            <option value="">— Search Product —</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[0][claimed_qty]"
                                                               class="form-control"
                                                               min="0.0001" step="0.01" placeholder="e.g. 10" required>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger remove-row" disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="addClaimRow">
                                        <i class="fas fa-plus"></i> Add Another Product
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="doctor-submit text-end">
                            <a href="{{ route('supplier-claims.index') }}" class="btn btn-cancel me-2">
                                Cancel
                            </a>
                            <button type="submit" id="standaloneSubmitBtn" class="btn btn-primary submit-form me-2">
                                Save Claim
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fetch and populate product dropdowns via AJAX
    function populateProductSelect(select) {
        fetch('/products/stocks?per_page=500')
            .then(r => r.json())
            .then(data => {
                if (data.status === 200 && Array.isArray(data.data)) {
                    data.data.forEach(function(stock) {
                        if (!stock.product) return;
                        var opt = document.createElement('option');
                        opt.value = stock.product.id;
                        opt.textContent = stock.product.product_name + ' (' + (stock.product.sku || 'N/A') + ')';
                        select.appendChild(opt);
                    });
                }
            });
    }

    // Init first row
    populateProductSelect(document.querySelector('.product-select'));

    var rowIndex = 1;

    document.getElementById('addClaimRow').addEventListener('click', function () {
        var tbody = document.getElementById('claimItemsBody');
        var row = document.createElement('tr');
        row.className = 'claim-item-row';
        row.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-control product-select" required>
                    <option value="">— Search Product —</option>
                </select>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][claimed_qty]"
                       class="form-control" min="0.0001" step="0.01" placeholder="e.g. 10" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        populateProductSelect(row.querySelector('.product-select'));

        rowIndex++;
        updateRemoveButtons();
    });

    document.getElementById('claimItemsBody').addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('.claim-item-row').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        var rows = document.querySelectorAll('.claim-item-row');
        rows.forEach(function(row) {
            var btn = row.querySelector('.remove-row');
            btn.disabled = rows.length === 1;
        });
    }

    // Double-submit protection
    document.getElementById('standaloneClaimForm').addEventListener('submit', function () {
        var btn = document.getElementById('standaloneSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
    });
</script>
@endsection
