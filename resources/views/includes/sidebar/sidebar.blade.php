<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title">
                    <span>Main Menu</span>
                </li>
                <li class="submenu active">
                    <a href="#"><i class="feather-grid"></i> <span> Dashboard</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="index.html" class="active">Admin Dashboard</a></li>
                        <li><a href="teacher-dashboard.html">Teacher Dashboard</a></li>
                        <li><a href="student-dashboard.html">Student Dashboard</a></li>
                    </ul>
                </li>
                <li class="submenu {{ set_active(['user'])}}">
                    <a href="#"><i class="fas fa-graduation-cap"></i> <span> User Management</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('user') }} class="{{ set_active(['user'])}}">Users</a></li>
                        <li><a href={{ route('role') }} class="{{ set_active(['role'])}}">Roles</a></li>
                        <li><a href={{ route('group-role-and-permission-view') }} class="{{ set_active(['group-role-and-permission-view'])}}">Role And Permission</a></li>
                        <li><a href={{ route('sales-commission-agent') }} class="{{ set_active(['sales-commission-agent'])}}">Sales Commissions</a></li>
                    </ul>
                </li>
                <li class="submenu {{ set_active(['supplier']) }}">
                    <a href="#"><i class="fas fa-chalkboard-teacher"></i> <span> Contacts</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('supplier') }} class="{{ set_active(['supplier'])}}">Supplier</a></li>
                        <li><a href={{ route('customer') }} class="{{ set_active(['customer'])}}">Customer</a></li>
                        <li><a href={{ route('customer-group') }} class="{{ set_active(['customer-group'])}}">Customer Groups</a></li>
                        <li><a href={{ route('import-contact') }} class="{{ set_active(['import-contact'])}}">Import Contacts</a></li>
                    </ul>
                </li>
                <li class="submenu {{ set_active(['warranty'])}}">
                    <a href="#"><i class="fas fa-building"></i> <span>Products</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('list-product') }} class="{{ set_active(['list-product'])}}">List Products</a></li>
                        {{-- {{ activeSegment('product_list') }} --}}
                        <li><a href={{ route('add-product')}} class="{{ set_active(['add-product'])}}">Add Product</a></li>
                        <li><a href={{ route('update-price')}} class="{{ set_active(['update-price'])}}">Update Price</a></li>
                        <li><a href={{ route('print-label')}} class="{{ set_active(['print-label'])}}">Print Label</a></li>
                        <li><a href={{ route('variation')}} class="{{ set_active(['variation'])}}">Variation</a></li>
                        <li><a href={{ route('variation-title')}} class="{{ set_active(['variation-title'])}}">Variation Title</a></li>
                        <li><a href={{ route('import-product')}} class="{{ set_active(['import-product'])}}">Import Products</a></li>
                        <li><a href={{ route('import-opening-stock')}} class="{{ set_active(['import-opening-stock'])}}">Import Opening Stock</a></li>
                        <li><a href={{ route('selling-price-group') }} class="{{ set_active(['selling-price-group'])}}">Selling Price Group</a></li>
                        <li><a href={{ route('unit') }} class="{{ set_active(['unit'])}}">Unit</a></li>
                        <li><a href={{ route('main-category') }} class="{{ set_active(['main-category'])}}">Main Category</a></li>
                        <li><a href={{ route('sub-category') }} class="{{ set_active(['sub-category'])}}">Sub Category</a></li>
                        <li><a href={{ route('brand')}} class="{{ set_active(['brand'])}}">Brands</a></li>
                        @can('View Warranty')
                        <li><a href={{ route('warranty') }} class="{{ set_active(['warranty'])}}">Warranty</a></li>
                         @endcan
                    </ul>
                </li>
                <li class="submenu {{ set_active(['list-purchase'])}}">
                    <a href="#"><i class="fas fa-book-reader"></i> <span>Purchases</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('list-purchase') }} class="{{ set_active(['list-purchase'])}}">List Purchases</a></li>
                        <li><a href={{ route('add-purchase') }} class="{{ set_active(['add-purchase'])}}">Add Purchases</a></li>
                        <li><a href={{ route('purchase-return') }} class="{{ set_active(['purchase-return'])}}">List Purchases Return</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-clipboard"></i> <span>Sell</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('list-sale') }} class="{{ set_active(['list-sale'])}}">All Sales</a></li>
                        <li><a href={{ route('add-sale') }} class="{{ set_active(['add-sale'])}}">Add Sale</a></li>

                        <li><a href={{ route('pos-list') }} class="{{ set_active(['pos-list'])}}">List POS</a></li>
                        <li><a href={{ route('pos-create') }} class="{{ set_active(['pos-create'])}}">POS</a></li>
                        <li><a href="view-invoice.html">Add Draft</a></li>
                        <li><a href="invoices-settings.html">List Draft</a></li>
                        <li><a href="invoices-settings.html">Add Quatation</a></li>
                        <li><a href="invoices-settings.html">List Quatations</a></li>
                        <li><a href="invoices-settings.html">List Sell Return</a></li>
                        <li><a href="invoices-settings.html">Shipments</a></li>
                        <li><a href="invoices-settings.html">Discounts</a></li>
                        <li><a href="invoices-settings.html">Import Sales</a></li>
                    </ul>
                </li>
                <li class="menu-title">
                    <span>Management</span>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Stock Transfers</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('list-stock-transfer') }}" class="{{ set_active(['list-stock-transfer'])}}">List Stock Transfers</a></li>
                        <li><a href={{ route('add-stock-transfer') }} class="{{ set_active(['add-stock-transfer'])}}">Add Stock Transfers</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Stock Adjustment</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="">List Stock Adjustments</a></li>
                        <li><a href="">Add Stock Adjustment</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Expenses</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">List Expenses</a></li>
                        <li><a href="expenses.html">Add Expense</a></li>
                        <li><a href={{ route('expense-parent-catergory') }} class="{{ set_active(['expense-parent-catergory'])}}">Parent Expense Catergories</a></li>
                        <li><a href={{ route('sub-expense-category') }} class="{{ set_active(['sub-expense-category'])}}">Child Expense Catergories</a></li>
                    </ul>
                </li>

                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Payment Accounts</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">List Accounts</a></li>
                        <li><a href="expenses.html">Balance Sheets</a></li>
                        <li><a href="expenses.html">Trial Balance</a></li>
                        <li><a href="expenses.html">Cash Flow</a></li>
                        <li><a href="expenses.html">Payment Account Report</a></li>
                    </ul>
                </li>

                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Reports</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">Profit / Loss Report</a></li>
                        <li><a href="expenses.html">Purchase & Sale</a></li>
                        <li><a href="expenses.html">Tax Report</a></li>
                        <li><a href="expenses.html">Supplier & Customer Report</a></li>
                        <li><a href="expenses.html">Customer Groups Report</a></li>
                        <li><a href="expenses.html">Stock Report</a></li>
                        <li><a href="expenses.html">Stock Adjustment Report</a></li>
                        <li><a href="expenses.html">Trending Products</a></li>
                        <li><a href="expenses.html">Item Report</a></li>
                        <li><a href="expenses.html">Product Purchase Report</a></li>
                        <li><a href="expenses.html">Product Sell Report</a></li>
                        <li><a href="expenses.html">Purchase Payment Report</a></li>
                        <li><a href="expenses.html">Sell Payment Report</a></li>
                        <li><a href="expenses.html">Expense Report</a></li>
                        <li><a href="expenses.html">Register Report</a></li>
                        <li><a href="expenses.html">Sales Representative Report</a></li>
                        <li><a href="expenses.html">Activity Log</a></li>
                    </ul>
                </li>

                <li>
                    <a href="holiday.html"><i class="fas fa-holly-berry"></i> <span>Notification Templates</span></a>
                </li>

                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Settings</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">Business Settings</a></li>
                        <li><a href="{{ route('location') }}" class="{{ set_active(['location'])}}">Business Locations</a></li>
                        <li><a href="expenses.html">Invoice Settings</a></li>
                        <li><a href="expenses.html">Barcode Settings</a></li>
                        <li><a href="expenses.html">Receipt Printers</a></li>
                        <li><a href="expenses.html">Tax Rates</a></li>
                        <li><a href="expenses.html">Package Subscription</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
