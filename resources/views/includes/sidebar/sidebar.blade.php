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
                <li class="submenu">
                    <a href="#"><i class="fas fa-graduation-cap"></i> <span> User Management</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('UserList') }}>Users</a></li>
                        <li><a href={{ route('RoleList') }}>Roles</a></li>
                        <li><a href={{ route('SalesCommissionList') }}>Sales Commissions</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-chalkboard-teacher"></i> <span> Contacts</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('SupplierList') }}>Suppliers</a></li>
                        <li><a href="teacher-details.html">Customers</a></li>
                        <li><a href="add-teacher.html">Customer Groups</a></li>
                        <li><a href="edit-teacher.html">Import Contacts</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-building"></i> <span>Products</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href={{ route('product_list') }}>List Products</a></li>
                        <li><a href={{ route('add-product') }}>Add Products</a></li>
                        <li><a href={{ route('updatePrice') }}>Update Price</a></li>
                        <li><a href={{ route('printLabel') }}>Print Label</a></li>
                        <li><a href={{ route('variatiuonList') }}>Variations</a></li>
                        <li><a href={{ route('import_products') }}>Import Products</a></li>
                        <li><a href={{ route('importopeningstock') }}>Import Opening Stock</a></li>
                        <li><a href={{ route('sellingpricelist') }}>Selling Price Group</a></li>
                        <li><a href={{ route('unitlist') }}>Units</a></li>
                        <li><a href={{ route('catergoriesList') }}>Catergories</a></li>
                        <li><a href={{ route('brandList') }}>Brands</a></li>
                        <li><a href={{ route('warrantyList') }}>Warranties</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-book-reader"></i> <span>Purchases</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="">List Purchases</a></li>
                        <li><a href="add-subject.html">Add Purchases</a></li>
                        <li><a href="edit-subject.html">List Purchases Return</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-clipboard"></i> <span>Sell</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="invoices.html">All Sales</a></li>
                        <li><a href="invoice-grid.html">Add Sale</a></li>
                        <li><a href="add-invoice.html">List POS</a></li>
                        <li><a href="edit-invoice.html">POS</a></li>
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
                        <li><a href="fees-collections.html">List Stock Transfers</a></li>
                        <li><a href="expenses.html">Add Stock Transfers</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Stock Adjustment</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">List Stock Adjustments</a></li>
                        <li><a href="expenses.html">Add Stock Adjustment</a></li>
                    </ul>
                </li>
                <li class="submenu">
                    <a href="#"><i class="fas fa-file-invoice-dollar"></i> <span>Expenses</span> <span
                            class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="fees-collections.html">List Expenses</a></li>
                        <li><a href="expenses.html">Add Expense</a></li>
                        <li><a href="expenses.html">Expense Catergories</a></li>
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
                        <li><a href="expenses.html">Business Locations</a></li>
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
