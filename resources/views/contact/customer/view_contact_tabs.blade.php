@extends('layout.layout')

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">
                                View Contact
                                <span class="text-muted" style="font-size: 14px;">
                                    — {{ $customer->full_name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) }}
                                </span>
                            </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customer') }}">Customers</a></li>
                                <li class="breadcrumb-item active">View Contact</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-bottom mb-3" id="customerViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#salesTab"
                            type="button" role="tab" aria-controls="salesTab" aria-selected="true">
                            Sales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#paymentsTab"
                            type="button" role="tab" aria-controls="paymentsTab" aria-selected="false">
                            Payments
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="salesTab" role="tabpanel" aria-labelledby="sales-tab">
                        {{-- Hidden filters required by sell.sales_ajax --}}
                        <div class="d-none">
                            <select id="locationFilter"><option value=""></option></select>
                            <select id="customerFilter">
                                <option value="{{ $customer->id }}" selected>{{ $customer->full_name ?? $customer->id }}</option>
                            </select>
                            <select id="paymentStatusFilter"><option value=""></option></select>
                            <select id="userFilter"><option value=""></option></select>
                            <select id="shippingStatusFilter"><option value=""></option></select>
                            <select id="paymentMethodFilter"><option value=""></option></select>
                            <input id="dateRangeFilter" value="" />
                        </div>

                        <div class="table-responsive">
                            <table id="salesTable" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Date</th>
                                        <th>Invoice No.</th>
                                        <th>Customer Name</th>
                                        <th>Contact Number</th>
                                        <th>Location</th>
                                        <th>Payment Status</th>
                                        <th>Payment Method</th>
                                        <th>Total Amount</th>
                                        <th>Total Paid</th>
                                        <th>Sell Due</th>
                                        <th>Shipping Status</th>
                                        <th>Total Items</th>
                                        <th>Added By</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        {{-- Modals required by sell.sales_ajax action buttons (View / Add Payment / View Payments) --}}
                        @include('sell.partials.sales_action_modals')

                        {{-- Reuse existing server-side sales list logic --}}
                        @include('sell.sales_ajax')
                    </div>

                    <div class="tab-pane fade" id="paymentsTab" role="tabpanel" aria-labelledby="payments-tab">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="customerPaymentsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Invoice No</th>
                                        <th>Payment Type</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const customerId = @json((string) $customer->id);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Ensure sales list auto-filter picks up customer_id (sell.sales_ajax reads URL params)
            const url = new URL(window.location.href);
            if (!url.searchParams.get('customer_id')) {
                url.searchParams.set('customer_id', customerId);
                window.history.replaceState({}, '', url.toString());
            }

            let paymentsLoaded = false;

            function formatCurrency(v) {
                const n = Number(v || 0);
                return 'Rs. ' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function escapeHtml(str) {
                return String(str ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function flattenCollections(collections) {
                const rows = [];
                (collections || []).forEach(col => {
                    (col.payments || []).forEach(p => {
                        rows.push({
                            payment_date: p.payment_date || col.payment_date || '',
                            reference_no: p.reference_no || col.reference_no || '',
                            invoice_no: p.invoice_no || '',
                            payment_type: p.payment_type || '',
                            payment_method: p.payment_method || '',
                            amount: p.amount || 0,
                        });
                    });
                });
                return rows;
            }

            async function loadPayments() {
                if (paymentsLoaded) return;
                paymentsLoaded = true;

                const tbody = document.querySelector('#customerPaymentsTable tbody');
                if (!tbody) return;

                try {
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');

                    const form = new FormData();
                    form.append('_token', csrfToken || '');
                    form.append('customer_id', customerId);
                    form.append('start_date', '2020-01-01');
                    form.append('end_date', `${yyyy}-${mm}-${dd}`);

                    const res = await fetch(@json(route('payment.report.data')), {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: form
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const json = await res.json();
                    const rows = flattenCollections(json.collections);

                    if (!rows.length) {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No payments found</td></tr>`;
                        return;
                    }

                    tbody.innerHTML = rows.map(r => `
                        <tr>
                            <td>${escapeHtml(r.payment_date)}</td>
                            <td>${escapeHtml(r.reference_no)}</td>
                            <td>${escapeHtml(r.invoice_no)}</td>
                            <td>${escapeHtml(r.payment_type)}</td>
                            <td>${escapeHtml(r.payment_method)}</td>
                            <td class="text-end">${formatCurrency(r.amount)}</td>
                        </tr>
                    `).join('');
                } catch (e) {
                    console.error(e);
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to load payments</td></tr>`;
                }
            }

            document.addEventListener('shown.bs.tab', function(e) {
                if (e.target && e.target.id === 'payments-tab') {
                    loadPayments();
                }
            });
        })();
    </script>
@endsection

