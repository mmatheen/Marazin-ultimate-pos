{{-- Limited home dashboard for non–Super Admin roles (Cashier, Sales Rep, etc.) --}}
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-4">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary flex-shrink-0"
                        style="width: 56px; height: 56px;">
                        <i class="fas fa-user fa-lg"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Welcome, {{ Auth::user()->user_name }}</h5>
                        <p class="text-muted mb-0 small">
                            Use the shortcuts below for your daily work. Business totals and reports are available to administrators only.
                        </p>
                        @if (Auth::user()->getRoleName())
                            <span class="badge bg-secondary mt-2">{{ Auth::user()->getRoleName() }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    @can('access pos')
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="{{ route('pos-create') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-quick-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-cash-register fa-2x text-success mb-3"></i>
                        <h6 class="mb-1 text-dark">POS</h6>
                        <p class="text-muted small mb-0">New sale / billing</p>
                    </div>
                </div>
            </a>
        </div>
    @endcan

    @can('view customer')
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="{{ route('customer') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-quick-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-users fa-2x text-primary mb-3"></i>
                        <h6 class="mb-1 text-dark">Customers</h6>
                        <p class="text-muted small mb-0">Contacts &amp; balances</p>
                    </div>
                </div>
            </a>
        </div>
    @endcan

    @canany(['view all sales', 'view own sales'])
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="{{ route('list-sale') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-quick-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-receipt fa-2x text-info mb-3"></i>
                        <h6 class="mb-1 text-dark">Sales</h6>
                        <p class="text-muted small mb-0">Invoices &amp; history</p>
                    </div>
                </div>
            </a>
        </div>
    @endcanany

    @can('view product')
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="{{ route('list-product') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-quick-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-box fa-2x text-warning mb-3"></i>
                        <h6 class="mb-1 text-dark">Products</h6>
                        <p class="text-muted small mb-0">Catalog &amp; stock</p>
                    </div>
                </div>
            </a>
        </div>
    @endcan

    @can('save draft')
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="{{ route('draft-list') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-quick-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-file-alt fa-2x text-secondary mb-3"></i>
                        <h6 class="mb-1 text-dark">Drafts</h6>
                        <p class="text-muted small mb-0">Saved drafts</p>
                    </div>
                </div>
            </a>
        </div>
    @endcan
</div>

<style>
    .dashboard-quick-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .dashboard-quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12) !important;
    }
</style>
