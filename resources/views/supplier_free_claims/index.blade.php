@extends('layout.layout')
@section('content')
<div class="content container-fluid">

    {{-- Page Header --}}
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Supplier Free Qty Claims</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Supplier Claims</li>
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

                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    {{-- Filters + New button --}}
                    <div class="page-header mb-3">
                        <div class="row align-items-end">
                            <form method="GET" class="row g-2 align-items-end w-100">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group local-forms mb-0">
                                        <label>Supplier</label>
                                        <select name="supplier_id" class="form-control form-select">
                                            <option value="">All Suppliers</option>
                                            @foreach($suppliers as $s)
                                                <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>
                                                    {{ $s->first_name }} {{ $s->last_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group local-forms mb-0">
                                        <label>Status</label>
                                        <select name="claim_status" class="form-control form-select">
                                            <option value="">All Statuses</option>
                                            <option value="pending"   @selected(request('claim_status') === 'pending')>Pending</option>
                                            <option value="partial"   @selected(request('claim_status') === 'partial')>Partial</option>
                                            <option value="fulfilled" @selected(request('claim_status') === 'fulfilled')>Fulfilled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group local-forms mb-0">
                                        <label>From Date</label>
                                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group local-forms mb-0">
                                        <label>To Date</label>
                                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-12">
                                    <div class="form-group mb-0">
                                        <label class="d-block invisible">Actions</label>
                                        <div class="d-flex gap-2 align-items-center">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Filter
                                            </button>
                                            <a href="{{ route('supplier-claims.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-redo"></i> Reset
                                            </a>
                                            @can('create supplier claims')
                                                <a href="{{ route('supplier-claims.standalone') }}" class="btn btn-success ms-auto">
                                                    <i class="fas fa-plus"></i> New Claim
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Claims list — one card per purchase bill --}}
                    @forelse($claims as $purchase)
                        @php
                            $receiptPurchases  = $purchase->claimReceipts()->with('purchaseProducts.product')->get();
                            $receiptIds        = $receiptPurchases->pluck('id');
                            $receivedByProduct = \App\Models\PurchaseProduct::whereIn('purchase_id', $receiptIds)
                                ->selectRaw('product_id, SUM(quantity) as total')
                                ->groupBy('product_id')
                                ->pluck('total', 'product_id');

                            $claimProducts  = $purchase->purchaseProducts->filter(fn($pp) => $pp->claim_free_quantity > 0);
                            $totalClaimed   = $claimProducts->sum('claim_free_quantity');
                            $totalReceived  = $receivedByProduct->sum();
                            $totalRemaining = max(0, $totalClaimed - $totalReceived);
                            $pct            = $totalClaimed > 0 ? min(100, round($totalReceived / $totalClaimed * 100)) : 0;

                            $borderColor = match($purchase->claim_status) {
                                'pending'   => '#ffc107',
                                'partial'   => '#fd7e14',
                                'fulfilled' => '#198754',
                                default     => '#dee2e6',
                            };
                        @endphp

                        <div class="card mb-3 border-0 shadow-sm">
                            {{-- Bill header --}}
                            <div class="card-header d-flex flex-wrap align-items-center gap-2 py-2"
                                 style="background:#f8f9fa;border-left:4px solid {{ $borderColor }};">
                                <strong class="fs-6 me-1">
                                    <i class="fas fa-file-invoice me-1 text-muted"></i>
                                    {{ $purchase->reference_no }}
                                </strong>
                                <span class="text-muted">|</span>
                                <span><i class="fas fa-user me-1 text-muted"></i>{{ $purchase->supplier->first_name ?? '-' }} {{ $purchase->supplier->last_name ?? '' }}</span>
                                <span class="text-muted">|</span>
                                <span><i class="fas fa-calendar-alt me-1 text-muted"></i>{{ $purchase->purchase_date }}</span>
                                <span class="text-muted">|</span>
                                @if($purchase->purchase_type === 'free_claim_standalone')
                                    <span class="badge bg-secondary">Standalone Claim</span>
                                @else
                                    <span class="badge bg-info text-dark">Purchase Bill</span>
                                @endif
                                @if($purchase->claim_status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($purchase->claim_status === 'partial')
                                    <span class="badge" style="background:#fd7e14;color:#fff">Partial</span>
                                @elseif($purchase->claim_status === 'fulfilled')
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Fulfilled</span>
                                @endif

                                @if($purchase->claim_status !== 'fulfilled')
                                    @can('receive supplier claims')
                                        <a href="{{ route('supplier-claims.create', $purchase->id) }}"
                                           class="btn btn-sm btn-primary ms-auto">
                                            <i class="fas fa-box-open me-1"></i> Receive Free Items
                                        </a>
                                    @endcan
                                @else
                                    <span class="text-success small ms-auto"><i class="fas fa-check-circle me-1"></i>All received</span>
                                @endif
                            </div>

                            <div class="card-body py-2">
                                <div class="row g-3">
                                    {{-- Left: Products claimed --}}
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">Claimed Products</p>
                                        <table class="table table-sm table-borderless mb-1">
                                            <thead>
                                                <tr style="font-size:11px;color:#6c757d">
                                                    <th>Product</th>
                                                    <th class="text-center">Claimed</th>
                                                    <th class="text-center">Received</th>
                                                    <th class="text-center text-danger">Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($claimProducts as $pp)
                                                    @php $got = (float)($receivedByProduct[$pp->product_id] ?? 0); @endphp
                                                    <tr style="font-size:13px">
                                                        <td>{{ $pp->product->product_name ?? 'Unknown' }}
                                                            <br><small class="text-muted">{{ $pp->product->sku ?? '' }}</small>
                                                        </td>
                                                        <td class="text-center fw-bold">{{ $pp->claim_free_quantity }}</td>
                                                        <td class="text-center text-success">{{ $got }}</td>
                                                        <td class="text-center text-danger fw-bold">{{ max(0, $pp->claim_free_quantity - $got) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        {{-- Progress bar --}}
                                        <div>
                                            <div class="d-flex justify-content-between" style="font-size:11px;color:#6c757d">
                                                <span>{{ $totalReceived }} / {{ $totalClaimed }} received</span>
                                                <span>{{ $pct }}%</span>
                                            </div>
                                            <div class="progress" style="height:5px">
                                                <div class="progress-bar {{ $pct==100 ? 'bg-success' : 'bg-warning' }}"
                                                     style="width:{{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right: Receipt history --}}
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px">
                                            Receipt History
                                            <span class="badge bg-light text-dark border ms-1">{{ $receiptPurchases->count() }}</span>
                                        </p>
                                        @if($receiptPurchases->isEmpty())
                                            <p class="text-muted" style="font-size:13px">
                                                <i class="fas fa-clock me-1"></i>No items received yet.
                                            </p>
                                        @else
                                            <table class="table table-sm table-borderless mb-0">
                                                <thead>
                                                    <tr style="font-size:11px;color:#6c757d">
                                                        <th>Receipt Ref</th>
                                                        <th>Date</th>
                                                        <th>Items Received</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($receiptPurchases as $receipt)
                                                        <tr style="font-size:13px">
                                                            <td><span class="badge bg-light text-dark border">{{ $receipt->reference_no }}</span></td>
                                                            <td>{{ $receipt->purchase_date }}</td>
                                                            <td>
                                                                @foreach($receipt->purchaseProducts as $rpp)
                                                                    <small class="d-block">{{ $rpp->product->product_name ?? '?' }}: <strong>{{ $rpp->quantity }}</strong></small>
                                                                @endforeach
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-gift fa-3x mb-3 d-block text-warning"></i>
                            No claims found. Claims appear when a purchase has <strong>Claim Free Qty</strong> filled in.
                        </div>
                    @endforelse

                    {{ $claims->links() }}
                </div>{{-- card-body --}}
            </div>{{-- card --}}
        </div>{{-- col --}}
    </div>{{-- row --}}
</div>{{-- content --}}
@endsection
