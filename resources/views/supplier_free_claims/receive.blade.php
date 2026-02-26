@extends('layout.layout')
@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Receive Claimed Free Items</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('supplier-claims.index') }}">Supplier Claims</a></li>
                            <li class="breadcrumb-item active">Receive — {{ $originalPurchase->reference_no }}</li>
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

                    {{-- Summary info bar --}}
                    <div class="row mb-4 pb-3" style="border-bottom:1px solid #f0f0f0">
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Supplier</div>
                            <div class="fw-bold">{{ $originalPurchase->supplier->first_name ?? '-' }} {{ $originalPurchase->supplier->last_name ?? '' }}</div>
                        </div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Original Bill</div>
                            <div class="fw-bold">{{ $originalPurchase->reference_no }}</div>
                        </div>
                        <div class="col-6 col-md-3 mb-2 mb-md-0">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Purchase Date</div>
                            <div class="fw-bold">{{ $originalPurchase->purchase_date }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Claim Status</div>
                            <div>
                                @if($originalPurchase->claim_status === 'pending')
                                    <span class="badge bg-warning text-dark px-3 py-2" style="font-size:13px">Pending</span>
                                @elseif($originalPurchase->claim_status === 'partial')
                                    <span class="badge px-3 py-2" style="background:#fd7e14;color:#fff;font-size:13px">Partial</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Errors --}}
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form action="{{ route('supplier-claims.store', $originalPurchase->id) }}" method="POST" id="receiveClaimForm" data-skip-global="true">
                        @csrf

                        {{-- Receive Date & Location --}}
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-group local-forms calendar-icon">
                                    <label>Receive Date <span class="login-danger">*</span></label>
                                    <input type="date" name="receive_date" class="form-control"
                                           value="{{ old('receive_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group local-forms">
                                    <label>Stock Location <span class="login-danger">*</span></label>
                                    <select name="location_id" class="form-control form-select" required>
                                        <option value="">— Select Location —</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}"
                                                @selected(old('location_id', $originalPurchase->location_id) == $loc->id)>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Products table --}}
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="vertical-align:middle">
                                <thead style="background:#f8f9fa">
                                    <tr>
                                        <th style="min-width:160px">Product</th>
                                        <th class="text-center" style="min-width:140px;white-space:nowrap">
                                            Total Claimed
                                            <div class="text-muted fw-normal" style="font-size:10px;margin-top:2px">Increase if supplier adds more</div>
                                        </th>
                                        <th class="text-center" style="min-width:90px">Already<br>Received</th>
                                        <th class="text-center text-danger" style="min-width:90px">Remaining</th>
                                        <th class="text-center" style="min-width:120px">Receiving Now <span class="text-danger">*</span></th>
                                        <th style="min-width:140px">Batch No</th>
                                        <th style="min-width:140px">Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($claimItems as $i => $item)
                                        <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $item->product_id }}">
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $item->product->product_name ?? 'Unknown' }}</div>
                                                <small class="text-muted">SKU: {{ $item->product->sku ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                       name="items[{{ $i }}][adjusted_claim_qty]"
                                                       class="form-control form-control-sm text-center adj-claim-input mx-auto"
                                                       style="max-width:110px"
                                                       value="{{ old("items.{$i}.adjusted_claim_qty", $item->claim_free_quantity) }}"
                                                       min="{{ $item->claim_free_quantity }}"
                                                       step="0.0001"
                                                       data-already="{{ $item->already_received }}"
                                                       title="Original: {{ $item->claim_free_quantity }}. Increase only if supplier agrees to give more.">
                                                <div class="text-muted mt-1" style="font-size:10px">Orig: {{ $item->claim_free_quantity }}</div>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold text-success" style="font-size:15px">{{ $item->already_received }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-bold text-danger remaining-cell" style="font-size:15px">{{ $item->remaining }}</span>
                                            </td>
                                            <td class="text-center">
                                                <input type="number"
                                                       name="items[{{ $i }}][quantity_received]"
                                                       class="form-control form-control-sm text-center qty-input mx-auto"
                                                       style="max-width:100px"
                                                       value="{{ old("items.{$i}.quantity_received", $item->remaining) }}"
                                                       min="0.0001"
                                                       max="{{ $item->remaining }}"
                                                       step="0.0001"
                                                       data-already="{{ $item->already_received }}"
                                                       required>
                                            </td>
                                            <td>
                                                <input type="text"
                                                       name="items[{{ $i }}][batch_no]"
                                                       class="form-control form-control-sm"
                                                       value="{{ old("items.{$i}.batch_no", $item->batch->batch_no ?? '') }}"
                                                       placeholder="Batch No">
                                            </td>
                                            <td>
                                                <input type="date"
                                                       name="items[{{ $i }}][expiry_date]"
                                                       class="form-control form-control-sm"
                                                       value="{{ old("items.{$i}.expiry_date", $item->batch->expiry_date ?? '') }}">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No pending claim items found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3 mb-4">
                            <i class="fas fa-info-circle me-1"></i>
                            Receiving adds items to <strong>Free Stock</strong> only — the supplier bill and payable balance will <strong>not</strong> change.
                            You can increase <em>Total Claimed</em> if the supplier agreed to provide more than originally noted.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" id="submitClaimBtn" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Receipt & Update Stock
                            </button>
                            <a href="{{ route('supplier-claims.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// When adj-claim-qty changes → update remaining cell and qty-input max
document.querySelectorAll('.adj-claim-input').forEach(function (adjInput) {
    var row      = adjInput.closest('tr');
    var qtyInput = row.querySelector('.qty-input');
    var remCell  = row.querySelector('.remaining-cell');

    function updateRemaining() {
        var adjClaim = parseFloat(adjInput.value) || 0;
        var already  = parseFloat(adjInput.dataset.already) || 0;
        var rem      = Math.max(0, adjClaim - already);
        var remStr   = parseFloat(rem.toFixed(4)).toString();
        remCell.textContent = remStr;
        qtyInput.setAttribute('max', rem);
        if (parseFloat(qtyInput.value) > rem) {
            qtyInput.value = rem;
        }
    }

    adjInput.addEventListener('input',  updateRemaining);
    adjInput.addEventListener('change', updateRemaining);
});

// Guard: prevent receiving more than remaining
document.querySelectorAll('.qty-input').forEach(function (input) {
    input.addEventListener('change', function () {
        var max = parseFloat(this.getAttribute('max'));
        var val = parseFloat(this.value);
        if (!isNaN(max) && val > max) {
            this.value = max;
            alert('Cannot receive more than remaining (' + max + ')');
        }
        if (val < 0.0001) {
            this.value = 0.0001;
        }
    });
});

// Double-submit protection: disable button immediately on first click
document.getElementById('receiveClaimForm').addEventListener('submit', function () {
    var btn = document.getElementById('submitClaimBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
});
</script>
@endsection
