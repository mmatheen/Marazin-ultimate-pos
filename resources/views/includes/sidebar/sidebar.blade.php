<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title">
                    <span>Main Menu</span>
                </li>

                <li>
                    <a href="{{ route('dashboard') }}" class="{{ set_active(['dashboard']) }}">
                        <i class="feather-grid"></i>
                        <span class="sidebar-text">Admin Dashboard</span>
                    </a>
                </li>

                @canany(['view user', 'view role', 'view role-permission', 'view sales-commission-agent'])
                    <li class="submenu {{ set_active(['user', 'role', 'group-role-and-permission-view', 'group-role-and-permission', 'role-and-permission-edit']) }}">
                        <a href="#">
                            <i class="fas fa-users-cog"></i>
                            <span class="sidebar-text">User Management</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view user')
                                <li><a href="{{ route('user') }}" class="{{ set_active(['user']) }}">Users</a></li>
                            @endcan

                            @can('view role')
                                <li><a href="{{ route('role') }}" class="{{ set_active(['role']) }}">Roles</a></li>
                            @endcan

                            @can('view role-permission')
                                <li><a href="{{ route('group-role-and-permission-view') }}"
                                        class="{{ set_active(['group-role-and-permission-view']) }}">Permissions</a>
                                </li>
                            @endcan

                            {{-- @can('view sales-commission-agent')
                        <li><a href="{{ route('sales-commission-agent') }}" class="{{ set_active(['sales-commission-agent'])}}">Sales Commissions</a></li>
                        @endcan --}}
                        </ul>
                    </li>
                @endcanany

                @canany(['view supplier', 'view customer', 'view customer-group'])
                    <li class="submenu {{ set_active(['supplier', 'customer', 'customer-ledger', 'supplier-ledger', 'account-ledger', 'unified-ledger', 'customer-group', 'import-contact']) }}">
                        <a href="#">
                            <i class="fas fa-address-book"></i>
                            <span class="sidebar-text">Contacts</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view supplier')
                                <li><a href="{{ route('supplier') }}" class="{{ set_active(['supplier']) }}">Supplier</a></li>
                            @endcan

                            @can('view customer')
                                <li><a href="{{ route('customer') }}" class="{{ set_active(['customer']) }}">Customer</a></li>
                            @endcan

                            {{-- @canany(['view customer', 'view supplier'])
                                <li><a href="{{ route('account.ledger') }}" class="{{ set_active(['account-ledger', 'unified-ledger']) }}"> Account Ledger</a></li>
                            @endcanany --}}



                            {{-- @can('view customer-group')
                        <li><a href="{{ route('customer-group') }}" class="{{ set_active(['customer-group'])}}">Customer Groups</a></li>
                        @endcan

                        <li><a href="{{ route('import-contact') }}" class="{{ set_active(['import-contact'])}}">Import Contacts</a></li> --}}
                        </ul>
                    </li>
                @endcanany

                @canany(['create product', 'view product', 'view unit', 'view main-category', 'view sub-category', 'view brand', 'view warranty', 'import product'])
                    <li class="submenu {{ set_active(['list-product', 'add-product', 'discounts.index', 'import-product', 'unit', 'main-category', 'sub-category', 'brand', 'warranty']) }}">
                        <a href="#">
                            <i class="fas fa-building"></i>
                            <span class="sidebar-text">Products</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view product')
                                <li><a href="{{ route('list-product') }}" class="{{ set_active(['list-product']) }}">All Products</a></li>
                            @endcan

                            @can('create product')
                                <li><a href="{{ route('add-product') }}" class="{{ set_active(['add-product']) }}">Add Product</a></li>
                            @endcan

                            @can('view discount')
                                <li><a href="{{ route('discounts.index') }}"
                                        class="{{ set_active(['discounts.index']) }}">Discounts</a></li>
                            @endcan

                            @can('import product')
                                <li><a href="{{ route('import-product') }}"
                                        class="{{ set_active(['import-product']) }}">Import Products</a></li>
                            @endcan

                            @canany(['view unit', 'view main-category', 'view sub-category', 'view brand', 'view warranty'])
                                <li class="submenu-item">
                                    <span class="menu-category">Product Settings</span>
                                </li>
                            @endcanany

                            @can('view main-category')
                                <li><a href="{{ route('main-category') }}" class="{{ set_active(['main-category']) }}">Main Category</a></li>
                            @endcan

                            @can('view sub-category')
                                <li><a href="{{ route('sub-category') }}" class="{{ set_active(['sub-category']) }}">Sub Category</a></li>
                            @endcan

                            @can('view brand')
                                <li><a href="{{ route('brand') }}" class="{{ set_active(['brand']) }}">Brands</a></li>
                            @endcan

                            @can('view unit')
                                <li><a href="{{ route('unit') }}" class="{{ set_active(['unit']) }}">Unit</a></li>
                            @endcan

                            @can('view warranty')
                                <li><a href="{{ route('warranty') }}" class="{{ set_active(['warranty']) }}">Warranty</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view purchase', 'create purchase', 'view purchase-return'])
                    <li class="submenu {{ set_active(['list-purchase', 'add-purchase', 'purchase-return', 'add-purchase-return']) }}">
                        <a href="#">
                            <i class="fas fa-shopping-bag"></i>
                            <span class="sidebar-text">Purchases</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view purchase')
                                <li><a href="{{ route('list-purchase') }}" class="{{ set_active(['list-purchase']) }}">All Purchases</a></li>
                            @endcan

                            @can('create purchase')
                                <li><a href="{{ route('add-purchase') }}" class="{{ set_active(['add-purchase']) }}">Add Purchase</a></li>
                            @endcan

                            @canany(['view purchase-return', 'create purchase-return'])
                                <li class="submenu-item">
                                    <span class="menu-category">Returns</span>
                                </li>
                            @endcanany

                            @can('view purchase-return')
                                <li><a href="{{ route('purchase-return') }}"
                                        class="{{ set_active(['purchase-return']) }}">All Returns</a></li>
                            @endcan

                            @can('create purchase-return')
                                <li><a href="{{ route('add-purchase-return') }}"
                                        class="{{ set_active(['add-purchase-return']) }}">Add Return</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view all sales', 'view own sales', 'create sale', 'access pos', 'view sale-return', 'create sale-return'])
                    <li class="submenu {{ set_active(['list-sale', 'pos-create', 'add-sale', 'quotation-list', 'draft-list', 'sale-orders-list', 'sale-return/list', 'sale-return/add']) }}">
                        <a href="#">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="sidebar-text">Sales</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('access pos')
                                <li><a href="{{ route('pos-create') }}" class="{{ set_active(['pos-create']) }}">POS</a></li>
                            @endcan

                            @canany(['view all sales', 'view own sales'])
                                <li><a href="{{ route('list-sale') }}" class="{{ set_active(['list-sale']) }}">All Sales</a></li>
                            @endcanany

                            <li><a href="{{ route('quotation-list') }}"
                                    class="{{ set_active(['quotation-list']) }}">Quotations</a></li>

                            <li><a href="{{ route('draft-list') }}" class="{{ set_active(['draft-list']) }}">Drafts</a></li>

                            @can('view sale-order')
                                <li><a href="{{ route('sale-orders-list') }}" class="{{ set_active(['sale-orders-list']) }}">Sale Orders</a></li>
                            @endcan

                            @canany(['view sale-return', 'create sale-return'])
                                <li class="submenu-item">
                                    <span class="menu-category">Returns</span>
                                </li>
                            @endcanany

                            @can('view sale-return')
                                <li><a href="{{ route('sale-return/list') }}"
                                        class="{{ set_active(['sale-return/list']) }}">All Returns</a></li>
                            @endcan

                            @can('create sale-return')
                                <li><a href="{{ route('sale-return/add') }}" class="{{ set_active(['sale-return/add']) }}">Add Return</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['bulk sale payment', 'bulk purchase payment', 'view cheque-management', 'view expense'])
                    <li class="submenu {{ set_active(['add-sale-bulk-payments', 'add-purchase-bulk-payments', 'manage-bulk-payments', 'cheque-management', 'account-ledger', 'unified-ledger', 'expense-list', 'expense-create', 'expense-edit', 'expense-parent-catergory', 'sub-expense-category']) }}">
                        <a href="#">
                            <i class="fas fa-wallet"></i>
                            <span class="sidebar-text">Accounts & Payments</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            {{-- Account Ledger --}}
                            @canany(['view customer', 'view supplier'])
                                <li><a href="{{ route('account.ledger') }}" class="{{ set_active(['account-ledger', 'unified-ledger']) }}">
                                    <i class="fas fa-book"></i> Account Ledger</a></li>
                            @endcanany

                            {{-- Cheque Management --}}
                            @can('view cheque-management')
                                <li><a href="{{ route('cheque-management') }}" class="{{ set_active(['cheque-management']) }}">
                                    <i class="fas fa-money-check"></i> Cheque Management</a></li>
                            @endcan

                            {{-- Bulk Payments Section --}}
                            @canany(['bulk sale payment', 'bulk purchase payment'])
                                <li class="submenu-item">
                                    <span class="menu-category">Bulk Payments</span>
                                </li>
                                @can('bulk sale payment')
                                    <li><a href="{{ route('add-sale-bulk-payments') }}"
                                            class="{{ set_active(['add-sale-bulk-payments']) }}">Sales Payments</a></li>
                                @endcan

                                @can('bulk purchase payment')
                                    <li><a href="{{ route('add-purchase-bulk-payments') }}"
                                            class="{{ set_active(['add-purchase-bulk-payments']) }}">Purchase Payments</a></li>
                                @endcan

                                @canany(['bulk sale payment', 'bulk purchase payment'])
                                    <li><a href="{{ route('manage-bulk-payments') }}"
                                            class="{{ set_active(['manage-bulk-payments']) }}">Manage Bulk Payments</a></li>
                                @endcanany
                            @endcanany

                            {{-- Expenses Section --}}
                            @canany(['view expense', 'create expense'])
                                <li class="submenu-item">
                                    <span class="menu-category">Expenses</span>
                                </li>
                                @can('view expense')
                                    <li><a href="{{ route('expense.list') }}" class="{{ set_active(['expense-list']) }}">All Expenses</a></li>
                                @endcan
                                @can('create expense')
                                    <li><a href="{{ route('expense.create') }}" class="{{ set_active(['expense-create']) }}">Create Expense</a></li>
                                @endcan
                                @can('view parent-expense')
                                    <li><a href="{{ route('expense-parent-catergory') }}" class="{{ set_active(['expense-parent-catergory']) }}">Expense Categories</a></li>
                                @endcan
                                @can('view child-expense')
                                    <li><a href="{{ route('sub-expense-category') }}" class="{{ set_active(['sub-expense-category']) }}">Sub Categories</a></li>
                                @endcan
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                <li class="menu-title">
                    <span>Inventory</span>
                </li>

                @canany(['view stock-transfer', 'create stock-transfer', 'view stock-adjustment', 'create stock-adjustment'])
                    <li class="submenu {{ set_active(['list-stock-transfer', 'add-stock-transfer', 'list-stock-adjustment', 'add-stock-adjustment']) }}">
                        <a href="#">
                            <i class="fas fa-warehouse"></i>
                            <span class="sidebar-text">Stock Management</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @canany(['view stock-transfer', 'create stock-transfer'])
                                <li class="submenu-item">
                                    <span class="menu-category">Stock Transfers</span>
                                </li>
                            @endcanany

                            @can('view stock-transfer')
                                <li><a href="{{ route('list-stock-transfer') }}"
                                        class="{{ set_active(['list-stock-transfer']) }}">All Transfers</a></li>
                            @endcan

                            @can('create stock-transfer')
                                <li><a href="{{ route('add-stock-transfer') }}"
                                        class="{{ set_active(['add-stock-transfer']) }}">Add Transfer</a></li>
                            @endcan

                            @canany(['view stock-adjustment', 'create stock-adjustment'])
                                <li class="submenu-item">
                                    <span class="menu-category">Stock Adjustments</span>
                                </li>
                            @endcanany

                            @can('view stock-adjustment')
                                <li><a href="{{ route('list-stock-adjustment') }}"
                                        class="{{ set_active(['list-stock-adjustment']) }}">All Adjustments</a></li>
                            @endcan

                            @can('create stock-adjustment')
                                <li><a href="{{ route('add-stock-adjustment') }}"
                                        class="{{ set_active(['add-stock-adjustment']) }}">Add Adjustment</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view sales-rep', 'view routes', 'assign routes'])
                <li class="submenu {{ set_active(['sales-rep/routes', 'sales-rep/cities', 'sales-rep/route-cities', 'sales-rep/sales-reps', 'sales-rep/targets', 'sales-rep/vehicle-locations']) }}">
                    <a href="#">
                        <i class="fas fa-user-tie"></i>
                        <span class="sidebar-text">Sales Rep Module</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>

                        @can('view routes')
                        <li><a href="{{ route('routes.create') }}"
                                class="{{ set_active(['sales-rep/routes']) }}">Routes</a></li>
                        @endcan

                        @can('view routes')
                        <li><a href="{{ route('cities.create') }}"
                                class="{{ set_active(['sales-rep/cities']) }}">Cities</a></li>
                        @endcan

                        @can('view routes')
                        <li><a href="{{ route('route-cities.create') }}"
                                class="{{ set_active(['sales-rep/route-cities']) }}">Route Cities</a></li>
                        @endcan

                        @can('view sales-rep')
                        <li><a href="{{ route('sales-reps.create') }}"
                                class="{{ set_active(['sales-rep/sales-reps']) }}">Sales Reps</a></li>
                        @endcan

                        @can('manage sales targets')
                        <li><a href="{{ route('targets.create') }}"
                                class="{{ set_active(['sales-rep/targets']) }}">Targets</a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                <li class="menu-title">
                    <span>Reports</span>
                </li>

                @canany(['view daily-report', 'view sales-report', 'view purchase-report', 'view stock-report', 'view profit-loss-report', 'view payment-report'])
                    <li class="submenu {{ set_active(['sales-report', 'purchase-report', 'stock-report', 'daily-report', 'profit-loss.report', 'payment.report', 'due-report']) }}">
                        <a href="#">
                            <i class="fas fa-chart-line"></i>
                            <span class="sidebar-text">Business Reports</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view daily-report')
                                <li><a href="{{ route('sales-report') }}" class="{{ set_active(['sales-report']) }}">Daily Sales</a></li>
                            @endcan

                            @can('view stock-report')
                                <li><a href="{{ route('stock.report') }}" class="{{ set_active(['stock-report']) }}">Stock Report</a></li>
                            @endcan

                            @can('view profit-loss-report')
                                <li><a href="{{ route('profit-loss.report') }}" class="{{ set_active(['profit-loss.report']) }}">Profit & Loss</a></li>
                            @endcan

                            @can('view payment-report')
                                <li><a href="{{ route('payment.report') }}" class="{{ set_active(['payment.report']) }}">Payments</a></li>
                            @endcan

                            <li><a href="{{ route('due.report') }}" class="{{ set_active(['due-report']) }}">Due Report</a></li>
                        </ul>
                    </li>
                @endcanany

                <li class="menu-title">
                    <span>System</span>
                </li>

                @canany(['view location', 'view settings'])
                    <li class="submenu {{ set_active(['location', 'settings.index']) }}">
                        <a href="#">
                            <i class="fas fa-cog"></i>
                            <span class="sidebar-text">Settings</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view location')
                                <li><a href="{{ route('location') }}" class="{{ set_active(['location']) }}">Locations</a></li>
                            @endcan

                            @can('view settings')
                                <li><a href="{{ route('settings.index') }}" class="{{ set_active(['settings.index']) }}">General Settings</a></li>
                            @endcan

                        </ul>
                    </li>
                @endcanany
            </ul>
        </div>
    </div>

    <style>
        /* Show text only when sidebar is expanded, hide when slim */
        .sidebar .sidebar-text {
            display: inline;
            transition: opacity 0.2s;
        }

        .sidebar.slim .sidebar-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            display: inline-block;
        }

        /* Menu category styling */
        .sidebar .submenu ul .submenu-item {
            padding: 0;
            margin: 0;
        }

        .sidebar .submenu ul .menu-category {
            display: block;
            padding: 8px 15px;
            font-size: 11px;
            font-weight: 600;
            color: #8e8e8e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .sidebar .submenu ul .submenu-item:first-child .menu-category {
            margin-top: 0;
        }
    </style>
</div>
