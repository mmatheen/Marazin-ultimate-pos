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
                            <i class="fas fa-graduation-cap"></i>
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
                                        class="{{ set_active(['group-role-and-permission-view']) }}">Role And Permission</a>
                                </li>
                            @endcan

                            {{-- @can('view sales-commission-agent')
                        <li><a href="{{ route('sales-commission-agent') }}" class="{{ set_active(['sales-commission-agent'])}}">Sales Commissions</a></li>
                        @endcan --}}
                        </ul>
                    </li>
                @endcanany

                @canany(['view supplier', 'view customer', 'view customer-group'])
                    <li class="submenu {{ set_active(['supplier', 'customer', 'customer-ledger', 'supplier-ledger', 'customer-group', 'import-contact']) }}">
                        <a href="#">
                            <i class="fas fa-chalkboard-teacher"></i>
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
                            @can('view customer')
                                <li><a href="{{ route('customer.ledger') }}" class="{{ set_active(['customer-ledger']) }}">Customer Ledger</a></li>
                            @endcan

                            @can('view supplier')
                                <li><a href="{{ route('supplier.ledger') }}" class="{{ set_active(['supplier-ledger']) }}">Supplier Ledger</a></li>
                            @endcan

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
                                <li><a href="{{ route('list-product') }}" class="{{ set_active(['list-product']) }}">List
                                        Products</a></li>
                            @endcan

                            @can('create product')
                                <li><a href="{{ route('add-product') }}" class="{{ set_active(['add-product']) }}">Add
                                        Product</a></li>
                            @endcan
                            @can('view discount')
                                <li><a href="{{ route('discounts.index') }}"
                                        class="{{ set_active(['discounts.index']) }}">Discounts</a></li>
                            @endcan

                            {{-- <li><a href="{{ route('update-price')}}" class="{{ set_active(['update-price'])}}">Update Price</a></li>
                        <li><a href="{{ route('print-label')}}" class="{{ set_active(['print-label'])}}">Print Label</a></li>
                        <li><a href="{{ route('variation')}}" class="{{ set_active(['variation'])}}">Variation</a></li>
                        <li><a href="{{ route('variation-title')}}" class="{{ set_active(['variation-title'])}}">Variation Title</a></li> --}}
                            @can('import product')
                                <li><a href="{{ route('import-product') }}"
                                        class="{{ set_active(['import-product']) }}">Import Products</a></li>
                            @endcan

                            {{-- <li><a href="{{ route('import-opening-stock')}}" class="{{ set_active(['import-opening-stock'])}}">Import Opening Stock</a>
                        </li>
                        <li><a href="{{ route('selling-price-group') }}" class="{{ set_active(['selling-price-group'])}}">Selling Price Group</a></li> --}}

                            @can('view unit')
                                <li><a href="{{ route('unit') }}" class="{{ set_active(['unit']) }}">Unit</a></li>
                            @endcan

                            @can('view main-category')
                                <li><a href="{{ route('main-category') }}" class="{{ set_active(['main-category']) }}">Main
                                        Category</a></li>
                            @endcan

                            @can('view sub-category')
                                <li><a href="{{ route('sub-category') }}" class="{{ set_active(['sub-category']) }}">Sub
                                        Category</a></li>
                            @endcan

                            @can('view brand')
                                <li><a href="{{ route('brand') }}" class="{{ set_active(['brand']) }}">Brands</a></li>
                            @endcan

                            @can('view warranty')
                                <li><a href="{{ route('warranty') }}" class="{{ set_active(['warranty']) }}">Warranty</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view purchase', 'create purchase', 'view purchase-return'])
                    <li class="submenu {{ set_active(['list-purchase', 'add-purchase', 'purchase-return']) }}">
                        <a href="#">
                            <i class="fas fa-book-reader"></i>
                            <span class="sidebar-text">Purchases</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view purchase')
                                <li><a href="{{ route('list-purchase') }}" class="{{ set_active(['list-purchase']) }}">List
                                        Purchases</a></li>
                            @endcan

                            @can('create purchase')
                                <li><a href="{{ route('add-purchase') }}" class="{{ set_active(['add-purchase']) }}">Add
                                        Purchases</a></li>
                            @endcan

                            @can('view purchase-return')
                                <li><a href="{{ route('purchase-return') }}"
                                        class="{{ set_active(['purchase-return']) }}">List Purchases Return</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view all sales', 'view own sales', 'create sale', 'access pos', 'view sale-return', 'create sale-return'])
                    <li class="submenu {{ set_active(['list-sale', 'pos-create', 'add-sale', 'quotation-list', 'draft-list', 'sale-return/list', 'sale-return/add']) }}">
                        <a href="#">
                            <i class="fas fa-clipboard"></i>
                            <span class="sidebar-text">Sell</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @canany(['view all sales', 'view own sales'])
                                <li><a href="{{ route('list-sale') }}" class="{{ set_active(['list-sale']) }}">All Sales</a>
                                </li>
                            @endcanany

                            @can('access pos')
                                <li><a href="{{ route('pos-create') }}" class="{{ set_active(['pos-create']) }}">POS</a></li>
                            @endcan

                            {{-- @can('create sale')
                        <li><a href="{{ route('add-sale') }}" class="{{ set_active(['add-sale'])}}">Add Sale</a></li>
                        @endcan --}}

                            {{-- Quotation , Draft list --}}
                            {{-- @can('view quotation') --}}
                            <li><a href="{{ route('quotation-list') }}"
                                    class="{{ set_active(['quotation-list']) }}">Quotation List</a></li>
                            {{-- @endcan --}}
                            {{-- @can('view draft') --}}
                            <li><a href="{{ route('draft-list') }}" class="{{ set_active(['draft-list']) }}">Draft
                                    List</a></li>
                            {{-- @endcan --}}

                            @can('view sale-return')
                                <li><a href="{{ route('sale-return/list') }}"
                                        class="{{ set_active(['sale-return/list']) }}">List Sale Return</a></li>
                            @endcan

                            @can('create sale-return')
                                <li><a href="{{ route('sale-return/add') }}" class="{{ set_active(['sale-return/add']) }}">Add
                                        Sale Return</a></li>
                            @endcan

                            {{-- Cheque Management --}}
                            @canany(['view all sales', 'view own sales'])
                                <li><a href="{{ route('cheque-management') }}" class="{{ set_active(['cheque-management']) }}">
                                    <i class="fas fa-money-check"></i> Cheque Management</a></li>
                            @endcanany

                            {{-- <li><a href="{{ route('pos-list') }}" class="{{ set_active(['pos-list'])}}">List POS</a>
                        </li> --}}
                        </ul>
                    </li>
                @endcanany

                @canany(['bulk sale payment', 'bulk purchase payment'])
                    <li class="submenu {{ set_active(['add-sale-bulk-payments', 'add-purchase-bulk-payments']) }}">
                        <a href="#">
                            <i class="fas fa-clipboard"></i>
                            <span class="sidebar-text">Add Bulk Payments</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('bulk sale payment')
                                <li><a href="{{ route('add-sale-bulk-payments') }}"
                                        class="{{ set_active(['add-sale-bulk-payments']) }}">All Sales Bulk Payments</a></li>
                            @endcan

                            @can('bulk purchase payment')
                                <li><a href="{{ route('add-purchase-bulk-payments') }}"
                                        class="{{ set_active(['add-purchase-bulk-payments']) }}">Add Purchase Bulk Payments</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                <li class="menu-title">
                    <span>Management</span>
                </li>

                @canany(['view stock-transfer', 'create stock-transfer'])
                    <li class="submenu {{ set_active(['list-stock-transfer', 'add-stock-transfer']) }}">
                        <a href="#">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="sidebar-text">Stock Transfers</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view stock-transfer')
                                <li><a href="{{ route('list-stock-transfer') }}"
                                        class="{{ set_active(['list-stock-transfer']) }}">List Stock Transfers</a></li>
                            @endcan

                            @can('create stock-transfer')
                                <li><a href="{{ route('add-stock-transfer') }}"
                                        class="{{ set_active(['add-stock-transfer']) }}">Add Stock Transfers</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view stock-adjustment', 'create stock-adjustment'])
                    <li class="submenu {{ set_active(['list-stock-adjustment', 'add-stock-adjustment']) }}">
                        <a href="#">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="sidebar-text">Stock Adjustment</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view stock-adjustment')
                                <li><a href="{{ route('list-stock-adjustment') }}"
                                        class="{{ set_active(['list-stock-adjustment']) }}">List Stock Adjustments</a></li>
                            @endcan

                            @can('create stock-adjustment')
                                <li><a href="{{ route('add-stock-adjustment') }}"
                                        class="{{ set_active(['add-stock-adjustment']) }}">Add Stock Adjustment</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                {{-- @canany(['view parent-expense', 'view child-expense'])
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Expenses</span> <span class="menu-arrow"></span></a>
                    <ul>
                        @can('view parent-expense')
                        <li><a href="{{ route('expense-parent-catergory') }}" class="{{ set_active(['expense-parent-catergory'])}}">Parent Expense Catergories</a></li>
                        @endcan
                        @can('view child-expense')
                        <li><a href="{{ route('sub-expense-category') }}" class="{{ set_active(['sub-expense-category'])}}">Child Expense Catergories</a></li>
                        @endcan
                    </ul>
                </li>
                @endcanany --}}

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

                @canany(['view daily-report', 'view sales-report', 'view purchase-report', 'view stock-report'])
                    <li class="submenu {{ set_active(['sales-report', 'purchase-report', 'stock-report', 'daily-report']) }}">
                        <a href="#">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="sidebar-text">Reports</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view daily-report')
                                <li><a href="{{ route('sales-report') }}" class="{{ set_active(['sales-report']) }}">Daily Sales Report</a></li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['view location', 'view settings'])
                    <li class="submenu {{ set_active(['location', 'settings.index']) }}">
                        <a href="#">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span class="sidebar-text">Settings</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <ul>
                            @can('view location')
                                <li><a href="{{ route('location') }}" class="{{ set_active(['location']) }}">Business
                                        Locations</a></li>
                            @endcan

                            @can('view settings')
                          
                                <li><a href="{{ route('settings.index') }}" class="{{ set_active(['settings.index']) }}">Site Settings</a></li>
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
    </style>
</div>
