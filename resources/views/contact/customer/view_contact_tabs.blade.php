@extends('layout.layout')

@section('content')
    @php
        $contactDisplayName = $customer->full_name ?? trim(($customer->prefix ? $customer->prefix . ' ' : '') . ($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
    @endphp
    <div class="content container-fluid">
        {{-- Page title --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-sub-header">
                    <h3 class="page-title mb-1">View Contact</h3>
                    <ul class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer') }}">Customers</a></li>
                        <li class="breadcrumb-item active">View Contact</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Profile summary --}}
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="row align-items-center g-3">
                    <div class="col-auto">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary"
                            style="width: 56px; height: 56px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h4 class="mb-0">{{ $contactDisplayName }}</h4>
                            <span class="badge bg-secondary">Customer</span>
                            @if ($customer->customer_type)
                                <span class="badge bg-info text-capitalize">{{ $customer->customer_type }}</span>
                            @endif
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $customer->address ?: '—' }}
                            <span class="mx-2">|</span>
                            <i class="fas fa-phone me-1"></i>{{ $customer->mobile_no ?: '—' }}
                            @if ($customer->email)
                                <span class="mx-2">|</span>
                                <i class="fas fa-envelope me-1"></i>{{ $customer->email }}
                            @endif
                            @if ($customer->city)
                                <span class="mx-2">|</span>
                                <i class="fas fa-city me-1"></i>{{ $customer->city->name }}
                            @endif
                        </p>
                    </div>
                    <div class="col-12 col-lg-auto" style="min-width: 260px; max-width: 100%;">
                        <label for="viewContactCustomerSwitcher" class="form-label small text-muted mb-1">Select customer</label>
                        <select id="viewContactCustomerSwitcher" class="form-control selectBox" data-placeholder="Search name or mobile…">
                            <option value="{{ $customer->id }}" selected data-slug="{{ $contactSlug ?? 'customer' }}">
                                {{ $contactDisplayName }}@if ($customer->mobile_no) · {{ $customer->mobile_no }}@endif
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-bottom mb-3 flex-wrap" id="customerViewTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledgerTabPane" type="button" role="tab" aria-controls="ledgerTabPane" aria-selected="false">
                            Ledger
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#salesTab" type="button" role="tab" aria-controls="salesTab" aria-selected="true">
                            Sales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documentsTabPane" type="button" role="tab" aria-controls="documentsTabPane" aria-selected="false">
                            Documents &amp; Note
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#paymentsTab" type="button" role="tab" aria-controls="paymentsTab" aria-selected="false">
                            Payments
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activities-tab" data-bs-toggle="tab" data-bs-target="#activitiesTabPane" type="button" role="tab" aria-controls="activitiesTabPane" aria-selected="false">
                            Activities
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Ledger --}}
                    <div class="tab-pane fade" id="ledgerTabPane" role="tabpanel" aria-labelledby="ledger-tab">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <p class="text-muted small mb-0">Statement for the selected period. Use <strong>Open full ledger</strong> for filters and exports.</p>
                            <a href="{{ route('account.ledger') }}?customer_id={{ $customer->id }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                <i class="fas fa-external-link-alt me-1"></i> Open full ledger
                            </a>
                        </div>
                        <div id="ledgerTabPlaceholder" class="text-center text-muted py-4 border rounded bg-light">
                            Open this tab to load ledger transactions.
                        </div>
                        <div id="ledgerTabLoaded" class="d-none">
                            <div class="row g-2 mb-3" id="ledgerSummaryCards"></div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover mb-0" id="customerLedgerTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                            <th class="text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customerLedgerTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="ledgerTabError" class="alert alert-danger d-none mt-2"></div>
                    </div>

                    {{-- Sales (existing DataTable + sales_ajax) --}}
                    <div class="tab-pane fade show active" id="salesTab" role="tabpanel" aria-labelledby="sales-tab">
                        <div class="d-none">
                            <select id="locationFilter"><option value=""></option></select>
                            <select id="customerFilter">
                                <option value="{{ $customer->id }}" selected>{{ $contactDisplayName }}</option>
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

                        @include('sell.partials.sales_action_modals')
                        @include('sell.sales_ajax')
                    </div>

                    {{-- Documents & Note --}}
                    <div class="tab-pane fade" id="documentsTabPane" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="text-uppercase text-muted small">Contact</h6>
                                    <p class="mb-1"><strong>Name:</strong> {{ $contactDisplayName }}</p>
                                    <p class="mb-1"><strong>Mobile:</strong> {{ $customer->mobile_no ?: '—' }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $customer->email ?: '—' }}</p>
                                    <p class="mb-0"><strong>Address:</strong> {{ $customer->address ?: '—' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 bg-light">
                                    <h6 class="text-uppercase text-muted small">Notes</h6>
                                    <p class="text-muted small mb-0">No notes stored on this contact. Use customer edit to update address and contact details.</p>
                                    @can('edit customer')
                                        <a href="{{ route('customer') }}" class="btn btn-sm btn-outline-primary mt-2">Go to customer list</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payments --}}
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
                                        <td colspan="6" class="text-center text-muted">Open this tab to load payments…</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Activities --}}
                    <div class="tab-pane fade" id="activitiesTabPane" role="tabpanel" aria-labelledby="activities-tab">
                        <p class="text-muted small mb-2">Recent changes to this customer record (where activity logging is enabled).</p>
                        <div id="activitiesTabContent" class="border rounded p-3 bg-light text-muted small">
                            Activity details are available in the system audit / activity log reports. Use <strong>Edit</strong> from the customer list to update this contact.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const customerId = @json((string) $customer->id);
            const contactSlug = @json($contactSlug ?? 'customer');
            const canonicalViewUrl = @json(route('customer.view-contact', ['id' => $customer->id, 'slug' => $contactSlug ?? 'customer']));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const ledgerDataUrl = @json(url('/customer-ledger-data'));

            function slugifyName(first, last) {
                const raw = [first || '', last || ''].join(' ').trim();
                if (!raw) return 'customer';
                const slug = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                return slug || 'customer';
            }

            // Path already has /{id}/{slug}/ — no need for ?customer_id= & ?customer_name= (duplicate).
            // Sales list uses #customerFilter from the page (pre-selected). Keep only ?tab= when switching tabs.
            (function syncContactViewUrl() {
                const u = new URL(window.location.href);
                const canonical = new URL(canonicalViewUrl, window.location.origin);
                u.pathname = canonical.pathname;
                u.searchParams.delete('customer_id');
                u.searchParams.delete('customer_name');
                window.history.replaceState({}, '', u.toString());
            })();

            /** Populate customer switcher from API (simple list) */
            function loadCustomerSwitcherOptions() {
                if (typeof jQuery === 'undefined') return;
                const $sel = jQuery('#viewContactCustomerSwitcher');
                if (!$sel.length) return;

                jQuery.ajax({
                    url: '/customer-get-all',
                    method: 'GET',
                    dataType: 'json'
                }).done(function(res) {
                    const list = (res && res.message) ? res.message : [];
                    if (!Array.isArray(list) || !list.length) return;

                    const current = String(customerId);
                    $sel.empty();
                    list.forEach(function(c) {
                        if (Number(c.id) === 1) return;
                        const name = [c.first_name, c.last_name].filter(Boolean).join(' ').trim() || ('Customer #' + c.id);
                        const mobileRaw = (c.mobile_no != null && String(c.mobile_no).trim() !== '') ? String(c.mobile_no).trim() : '';
                        const label = name + (mobileRaw ? ' · ' + mobileRaw : '');
                        const slug = slugifyName(c.first_name, c.last_name);
                        const opt = new Option(label, c.id, false, String(c.id) === current);
                        opt.setAttribute('data-slug', slug);
                        $sel.append(opt);
                    });

                    if (typeof jQuery.fn.select2 !== 'undefined') {
                        if ($sel.hasClass('select2-hidden-accessible')) {
                            $sel.select2('destroy');
                        }
                        $sel.select2({
                            placeholder: 'Search name or mobile…',
                            allowClear: false,
                            width: '100%'
                        });
                    }

                    $sel.off('change.viewContact').on('change.viewContact', function() {
                        const id = jQuery(this).val();
                        if (!id || String(id) === String(customerId)) return;
                        const opt = jQuery(this).find('option:selected');
                        const slug = opt.data('slug') || 'customer';
                        window.location.href = '/customer/view-contact/' + id + '/' + slug;
                    });
                });
            }

            let paymentsLoaded = false;
            let ledgerLoaded = false;

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

            async function loadLedger() {
                if (ledgerLoaded) return;
                ledgerLoaded = true;

                const ph = document.getElementById('ledgerTabPlaceholder');
                const wrap = document.getElementById('ledgerTabLoaded');
                const err = document.getElementById('ledgerTabError');
                const tbody = document.getElementById('customerLedgerTableBody');
                const sumRow = document.getElementById('ledgerSummaryCards');

                if (ph) ph.classList.add('d-none');
                if (wrap) wrap.classList.remove('d-none');
                if (err) {
                    err.classList.add('d-none');
                    err.textContent = '';
                }
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading…</td></tr>';

                const today = new Date();
                const end = today.toISOString().slice(0, 10);
                const params = new URLSearchParams({
                    customer_id: customerId,
                    start_date: '2020-01-01',
                    end_date: end
                });

                try {
                    const res = await fetch(ledgerDataUrl + '?' + params.toString(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const json = await res.json();
                    if (!res.ok || json.status !== 200) {
                        throw new Error(json.message || ('HTTP ' + res.status));
                    }
                    const transactions = json.transactions || [];
                    const summary = json.summary || {};

                    if (sumRow) {
                        const ob = summary.opening_balance ?? 0;
                        const due = summary.effective_due ?? summary.outstanding_due ?? summary.balance_due ?? 0;
                        sumRow.innerHTML = `
                            <div class="col-md-4">
                                <div class="border rounded p-2 small bg-light"><strong>Opening</strong><div>${formatCurrency(ob)}</div></div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 small bg-light"><strong>Outstanding / advance</strong><div>${formatCurrency(due)}</div></div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 small bg-light"><strong>Transactions</strong><div>${transactions.length}</div></div>
                            </div>`;
                    }

                    if (!tbody) return;
                    if (!transactions.length) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No ledger lines in this period</td></tr>';
                        return;
                    }
                    tbody.innerHTML = transactions.map(function(entry) {
                        const debit = parseFloat(entry.debit || 0);
                        const credit = parseFloat(entry.credit || 0);
                        const bal = parseFloat(entry.running_balance || 0);
                        const desc = entry.notes || entry.others || entry.description || entry.type || '—';
                        const dt = entry.date || entry.created_at || '—';
                        return `<tr>
                            <td>${escapeHtml(String(dt))}</td>
                            <td>${escapeHtml(String(desc))}</td>
                            <td class="text-end">${debit > 0 ? formatCurrency(debit) : '—'}</td>
                            <td class="text-end">${credit > 0 ? formatCurrency(credit) : '—'}</td>
                            <td class="text-end">${formatCurrency(bal)}</td>
                        </tr>`;
                    }).join('');
                } catch (e) {
                    console.error(e);
                    if (wrap) wrap.classList.add('d-none');
                    if (ph) ph.classList.remove('d-none');
                    if (err) {
                        err.textContent = 'Could not load ledger: ' + (e.message || 'Error');
                        err.classList.remove('d-none');
                    }
                }
            }

            function setTabQuery(name) {
                const u = new URL(window.location.href);
                u.searchParams.set('tab', name);
                window.history.replaceState({}, '', u.toString());
            }

            document.addEventListener('shown.bs.tab', function(e) {
                const id = e.target && e.target.id;
                if (id === 'payments-tab') {
                    loadPayments();
                    setTabQuery('payments');
                }
                if (id === 'sales-tab') {
                    if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#salesTable')) {
                        jQuery('#salesTable').DataTable().columns.adjust();
                    }
                    setTabQuery('sales');
                }
                if (id === 'ledger-tab') {
                    loadLedger();
                    setTabQuery('ledger');
                }
                if (id === 'documents-tab') setTabQuery('documents');
                if (id === 'activities-tab') setTabQuery('activities');
            });

            function openTabFromQuery() {
                const tab = new URLSearchParams(window.location.search).get('tab');
                const map = {
                    payments: 'payments-tab',
                    sales: 'sales-tab',
                    ledger: 'ledger-tab',
                    documents: 'documents-tab',
                    activities: 'activities-tab'
                };
                const btnId = map[tab];
                if (!btnId) return;
                const btn = document.getElementById(btnId);
                if (btn && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(btn).show();
                }
            }
            if (typeof jQuery !== 'undefined') {
                jQuery(function() {
                    loadCustomerSwitcherOptions();
                    openTabFromQuery();
                });
            } else {
                window.addEventListener('load', function() {
                    loadCustomerSwitcherOptions();
                    openTabFromQuery();
                });
            }

            document.getElementById('sales-tab')?.addEventListener('shown.bs.tab', function() {
                if (window.jQuery && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#salesTable')) {
                    jQuery('#salesTable').DataTable().columns.adjust();
                }
            });
        })();
    </script>
@endsection
