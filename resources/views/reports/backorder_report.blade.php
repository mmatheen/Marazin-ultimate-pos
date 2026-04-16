@extends('layout.layout')
@section('title', 'Pending Allocation Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
<style>
    #summaryCards .card {
        border: 1px solid #e8e9ed;
        border-radius: 12px;
        height: 100%;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }
    #summaryCards .card-body { padding: 1.1rem 1.25rem; }
    #summaryCards h6.card-title { color:#8492a6; font-size:.6875rem; font-weight:600; text-transform:uppercase; letter-spacing:1px; }
    #summaryCards h4 { font-size:1.5rem; font-weight:700; margin:0; }
    .status-badge { font-size: .75rem; padding: .35rem .55rem; border-radius: 999px; }
    .status-open { background:#fff3cd; color:#856404; }
    .status-partially_allocated { background:#cff4fc; color:#055160; }
    .status-fulfilled { background:#d1e7dd; color:#0f5132; }
    .status-cancelled { background:#f8d7da; color:#842029; }
    .status-fully_allocated { background:#e2e3e5; color:#41464b; }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title">Pending Allocation Report</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#">Reports</a></li>
                        <li class="breadcrumb-item active">Pending Allocation Report</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <div class="card-body">
                    <form id="backorderFilters">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate ?? date('Y-m-01') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate ?? date('Y-m-t') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <select class="form-control" id="location_id" name="location_id">
                                    <option value="">All Locations</option>
                                    @foreach($locations ?? [] as $location)
                                        <option value="{{ $location->id }}" {{ (string)($locationId ?? '') === (string)$location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Allocation Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="open" {{ ($status ?? '') === 'open' ? 'selected' : '' }}>Pending</option>
                                    <option value="partially_allocated" {{ ($status ?? '') === 'partially_allocated' ? 'selected' : '' }}>Partially Allocated</option>
                                    <option value="fully_allocated" {{ ($status ?? '') === 'fully_allocated' ? 'selected' : '' }}>Reserved</option>
                                    <option value="fulfilled" {{ ($status ?? '') === 'fulfilled' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ ($status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" id="generateBtn"><i class="fas fa-chart-line"></i> Generate Report</button>
                            <a href="{{ route('backorder.report') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="summaryCards" style="display:none;">
        <div class="col-12">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><h6 class="card-title">Total Requests</h6><h4 id="totalBackorders">0</h4></div></div></div>
                <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><h6 class="card-title">Requested Qty</h6><h4 id="orderedQty">0</h4></div></div></div>
                <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><h6 class="card-title">Allocated Qty</h6><h4 id="fulfilledQty">0</h4></div></div></div>
                <div class="col-lg-3 col-md-6"><div class="card"><div class="card-body"><h6 class="card-title">Pending Qty</h6><h4 id="remainingQty">0</h4></div></div></div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card" id="reportCard" style="display:none;">
                <div class="card-header"><h4 class="card-title mb-0">Pending Allocation Details</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="backorderTable">
                            <thead>
                                <tr id="tableHeaders"></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script>
let backorderTable = null;

function formatQty(value) {
    return parseFloat(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function statusLabel(status) {
    const labels = {
        open: 'Pending',
        partially_allocated: 'Partially Allocated',
        fully_allocated: 'Reserved',
        fulfilled: 'Completed',
        cancelled: 'Cancelled'
    };

    return '<span class="status-badge status-' + status + '">' + (labels[status] || status.replace(/_/g, ' ')) + '</span>';
}

function updateSummary(summary) {
    $('#summaryCards').show();
    $('#totalBackorders').text(summary.total_backorders || 0);
    $('#orderedQty').text(formatQty((summary.ordered_paid_qty || 0) + (summary.ordered_free_qty || 0)));
    $('#fulfilledQty').text(formatQty((summary.fulfilled_paid_qty || 0) + (summary.fulfilled_free_qty || 0)));
    $('#remainingQty').text(formatQty((summary.remaining_paid_qty || 0) + (summary.remaining_free_qty || 0)));
}

function updateTable(data) {
    $('#reportCard').show();

    if (backorderTable) {
        backorderTable.destroy();
    }

    const headers = ['Request ID', 'Invoice No', 'Date', 'Location', 'Product', 'SKU', 'Status', 'Requested Qty', 'Allocated Qty', 'Pending Qty', 'Allocation Activity'];
    $('#tableHeaders').html(headers.map(h => '<th>' + h + '</th>').join(''));

    backorderTable = $('#backorderTable').DataTable({
        data: data || [],
        columns: [
            { data: 'backorder_id' },
            { data: 'invoice_no', defaultContent: 'N/A' },
            { data: 'sales_date', defaultContent: 'N/A' },
            { data: 'location_name', defaultContent: 'N/A' },
            { data: 'product_name', defaultContent: 'N/A' },
            { data: 'sku', defaultContent: 'N/A' },
            { data: 'status', render: data => statusLabel(data || 'open') },
            { data: null, render: (data, type, row) => formatQty((row.ordered_paid_qty || 0) + (row.ordered_free_qty || 0)) },
            { data: null, render: (data, type, row) => formatQty((row.fulfilled_paid_qty || 0) + (row.fulfilled_free_qty || 0)) },
            { data: null, render: (data, type, row) => formatQty((row.remaining_paid_qty || 0) + (row.remaining_free_qty || 0)) },
            { data: null, render: (data, type, row) => {
                const reserved = formatQty((row.reserved_paid_qty || 0) + (row.reserved_free_qty || 0));
                const released = formatQty((row.released_paid_qty || 0) + (row.released_free_qty || 0));
                return '<div class="small"><div><strong>Reserved:</strong> ' + reserved + '</div><div><strong>Released:</strong> ' + released + '</div></div>';
            } }
        ],
        dom: 'Bfrtip',
        buttons: ['excel', 'print'],
        pageLength: 25,
        order: [[2, 'desc']]
    });
}

function loadBackorders() {
    const formData = new FormData(document.getElementById('backorderFilters'));

    fetch('{{ route('backorder.report.data') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(response => {
        updateSummary(response.summary || {});
        updateTable(response.data || []);
    })
    .catch(error => {
        console.error(error);
        alert('Failed to load backorder report.');
    });
}

$('#generateBtn').on('click', function() {
    loadBackorders();
});

$(document).ready(function() {
    loadBackorders();
});
</script>
@endpush
