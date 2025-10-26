@extends('layout.layout')
@section('title', 'Stock Report')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <style>
        .selected-column {
            background-color: #0d6efd !important;
            color: white !important;
        }
        .selected-column:hover {
            background-color: #0b5ed7 !important;
            color: white !important;
        }

        /* Select2 Styling for Perfect Alignment */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 44px !important;
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
            background-color: #fff !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px !important;
            padding-left: 12px !important;
            padding-right: 30px !important;
            color: #333 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 8px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #999 !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #80bdff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .select2-dropdown {
            border: 1px solid #ddd !important;
            border-radius: 5px !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }

        .form-group.local-forms {
            margin-bottom: 1rem;
        }

        .form-group.local-forms label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
    </style>
@endpush

@section('content')

    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Stock Report</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                                <li class="breadcrumb-item active">Stock Report</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters Section --}}
            <div class="card card-body mb-4">
                <div class="student-group-form d-flex align-items-start flex-wrap gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapseFilters" aria-expanded="false" aria-controls="collapseFilters">
                        <i class="fas fa-filter"></i> &nbsp; Filters
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-success dropdown-toggle" type="button" id="dropdownMenuButton"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Columns Visibility
                        </button>
                        <ul class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" id="columnVisibilityDropdown"
                            style="width: 400px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="hide all">1. Hide All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="show all">2. Show All Columns</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="1">SKU</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="2">Product</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="3">Batch No</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="4">Category</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="5">Location</a></li>
                                </div>
                                <div class="col-md-6">
                                    <li><a class="dropdown-item" href="#" data-value="6">Unit Cost</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="7">Unit Selling Price</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="8">Current Stock</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="9">Stock Value (Purchase)</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="10">Stock Value (Sale)</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="11">Potential Profit</a></li>
                                    <li><a class="dropdown-item" href="#" data-value="12">Expiry Date</a></li>
                                </div>
                            </div>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div class="collapse" id="collapseFilters">
                <div class="card card-body mb-3">
                <div class="student-group-form">
                <form id="stockFilterForm" method="GET" action="{{ route('stock.report') }}">
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Business Location:</label>
                                <select class="form-control selectBox" id="locationFilter" name="location_id">
                                    <option value="">All locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Category:</label>
                                <select class="form-control selectBox" id="categoryFilter" name="category_id">
                                    <option value="">All</option>
                                    @if($categories && count($categories) > 0)
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->mainCategoryName }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Sub category:</label>
                                <select class="form-control selectBox" id="subCategoryFilter" name="sub_category_id">
                                    <option value="">None</option>
                                    @if($subCategories && count($subCategories) > 0)
                                        @foreach($subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}" {{ request('sub_category_id') == $subCategory->id ? 'selected' : '' }}>
                                                {{ $subCategory->subCategoryname }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Brand:</label>
                                <select class="form-control selectBox" id="brandFilter" name="brand_id">
                                    <option value="">All</option>
                                    @if($brands && count($brands) > 0)
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                                {{ $brand->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Unit:</label>
                                <select class="form-control selectBox" id="unitFilter" name="unit_id">
                                    <option value="">All</option>
                                    @if($units && count($units) > 0)
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms" style="margin-bottom: 0;">
                                <label style="visibility: hidden;">.</label>
                                <a href="{{ route('stock.report') }}" class="btn btn-secondary d-block">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
                </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="row mb-3">
                <div class="col-xl-3 col-sm-6 col-12 d-flex">
                    <div class="card bg-info w-100">
                        <div class="card-body">
                            <div class="text-white">
                                <h6 class="text-white mb-2">Closing stock (By purchase price)</h6>
                                <h4 class="text-white mb-0">Rs. {{ number_format($summaryData['total_stock_by_purchase_price'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 d-flex">
                    <div class="card bg-success w-100">
                        <div class="card-body">
                            <div class="text-white">
                                <h6 class="text-white mb-2">Closing stock (By sale price)</h6>
                                <h4 class="text-white mb-0">Rs. {{ number_format($summaryData['total_stock_by_sale_price'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 d-flex">
                    <div class="card bg-warning w-100">
                        <div class="card-body">
                            <div class="text-white">
                                <h6 class="text-white mb-2">Potential profit</h6>
                                <h4 class="text-white mb-0">Rs. {{ number_format($summaryData['total_potential_profit'], 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12 d-flex">
                    <div class="card bg-primary w-100">
                        <div class="card-body">
                            <div class="text-white">
                                <h6 class="text-white mb-2">Profit Margin</h6>
                                <h4 class="text-white mb-0">{{ number_format($summaryData['profit_margin'], 2) }}%</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            {{-- Table Section --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover" id="stockReportTable">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>SKU</th>
                                <th>Product</th>
                                <th>Batch No</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Unit Cost</th>
                                <th>Unit Selling Price</th>
                                <th>Current stock</th>
                                <th>Stock Value (Purchase)</th>
                                <th>Stock Value (Sale)</th>
                                <th>Potential profit</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        /* Fix primary button text visibility */
        .bg-primary {
            background-color: #0d6efd !important;
        }
        
        .bg-primary .text-white {
            color: #ffffff !important;
        }

        @media print {
            .btn-group, .page-header, #stockFilterForm {
                display: none !important;
            }
        }
    </style>

    <script>
        $(document).ready(function() {
            // Get filter values
            function getFilters() {
                return {
                    location_id: $('#locationFilter').val(),
                    category_id: $('#categoryFilter').val(),
                    sub_category_id: $('#subCategoryFilter').val(),
                    brand_id: $('#brandFilter').val(),
                    unit_id: $('#unitFilter').val()
                };
            }

            // Initialize DataTable with AJAX
            var table = $('#stockReportTable').DataTable({
                "processing": true,
                "serverSide": false,
                "ajax": {
                    "url": "{{ route('stock.report') }}",
                    "type": "GET",
                    "data": function(d) {
                        return $.extend({}, d, getFilters());
                    }
                },
                "columns": [
                    { 
                        "data": null,
                        "orderable": false,
                        "render": function(data, type, row) {
                            var productId = row.product_id;
                            var stockHistoryUrl = "{{ route('productStockHistory', ':id') }}".replace(':id', productId);
                            return `<div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="${stockHistoryUrl}"><i class="fas fa-history"></i> Stock History</a></li>
                                </ul>
                            </div>`;
                        }
                    },
                    { "data": "sku" },
                    { 
                        "data": "product_name",
                        "render": function(data) {
                            return '<strong>' + data + '</strong>';
                        }
                    },
                    { 
                        "data": "batch_no",
                        "render": function(data) {
                            return '<span class="badge bg-secondary">' + data + '</span>';
                        }
                    },
                    { "data": "category" },
                    { "data": "location" },
                    { 
                        "data": "unit_cost",
                        "render": function(data) {
                            return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    },
                    { 
                        "data": "unit_selling_price",
                        "render": function(data) {
                            return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    },
                    { 
                        "data": "current_stock",
                        "className": "text-center",
                        "render": function(data) {
                            return '<strong>' + parseFloat(data).toFixed(2) + '</strong>';
                        }
                    },
                    { 
                        "data": "stock_value_purchase",
                        "className": "text-end",
                        "render": function(data) {
                            return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    },
                    { 
                        "data": "stock_value_sale",
                        "className": "text-end",
                        "render": function(data) {
                            return 'Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        }
                    },
                    { 
                        "data": "potential_profit",
                        "className": "text-end text-success",
                        "render": function(data) {
                            return '<strong>Rs. ' + parseFloat(data).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>';
                        }
                    },
                    { 
                        "data": "expiry_date",
                        "className": "text-center",
                        "render": function(data) {
                            if (!data) return '<span class="text-muted">N/A</span>';
                            
                            var expiryDate = new Date(data);
                            var today = new Date();
                            var daysToExpiry = Math.floor((expiryDate - today) / (1000 * 60 * 60 * 24));
                            
                            var badgeClass = daysToExpiry < 0 ? 'bg-danger' : (daysToExpiry < 30 ? 'bg-warning text-dark' : 'bg-success');
                            var formattedDate = expiryDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                            
                            var html = '<span class="badge ' + badgeClass + '">' + formattedDate + '</span>';
                            
                            if (daysToExpiry < 0) {
                                html += '<br><small class="text-danger">Expired</small>';
                            } else if (daysToExpiry < 30) {
                                html += '<br><small class="text-warning">' + daysToExpiry + ' days left</small>';
                            }
                            
                            return html;
                        }
                    }
                ],
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                "order": [[1, 'asc']],
                "dom": '<"dt-top"B><"dt-controls"<"dt-length"l><"dt-search"f>>rtip',
                "buttons": [
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        orientation: 'landscape',
                        pageSize: 'A4',
                        filename: () => 'stock_report_' + new Date().toISOString().slice(0, 10),
                        exportOptions: {
                            columns: ':visible:not(:first-child)'
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        filename: () => 'stock_report_' + new Date().toISOString().slice(0, 10),
                        exportOptions: {
                            columns: ':visible:not(:first-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        title: 'Stock Report',
                        exportOptions: {
                            columns: ':visible:not(:first-child)'
                        }
                    }
                ],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "emptyTable": "No stock data available",
                    "zeroRecords": "No matching records found",
                    "processing": '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                }
            });

            // Column Visibility Dropdown
            function updateDropdownHighlights() {
                $('#columnVisibilityDropdown a').each(function() {
                    const value = $(this).data('value');
                    if (value === "hide all") {
                        $(this).removeClass('selected-column');
                    } else if (value === "show all") {
                        let allVisible = true;
                        table.columns().every(function() {
                            if (!this.visible()) allVisible = false;
                        });
                        if (allVisible) $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    } else if (!isNaN(value)) {
                        if (table.column(value).visible()) $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    }
                });
            }

            $('#columnVisibilityDropdown a').on('click', function(e) {
                e.preventDefault();
                const selectedValue = $(this).data('value');
                if (selectedValue === "hide all") {
                    table.columns().visible(false);
                    $('#columnVisibilityDropdown a').removeClass('selected-column');
                    $(this).addClass('selected-column');
                } else if (selectedValue === "show all") {
                    table.columns().visible(true);
                    $('#columnVisibilityDropdown a').each(function() {
                        const val = $(this).data('value');
                        if (!isNaN(val) || val === "show all") $(this).addClass('selected-column');
                        else $(this).removeClass('selected-column');
                    });
                } else {
                    const column = table.column(selectedValue);
                    column.visible(!column.visible());
                    $('#columnVisibilityDropdown a[data-value="hide all"]').removeClass('selected-column');
                    if (column.visible()) $(this).addClass('selected-column');
                    else $(this).removeClass('selected-column');
                    updateDropdownHighlights();
                }
            });

            document.querySelectorAll('.dropdown-menu .dropdown-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });

            // Auto-reload when location changes
            $('#locationFilter').on('change', function() {
                // Reload page with new location filter to update summary cards
                var url = new URL(window.location.href);
                var locationId = $(this).val();
                
                if (locationId) {
                    url.searchParams.set('location_id', locationId);
                } else {
                    url.searchParams.delete('location_id');
                }
                
                // Preserve other filters
                var categoryId = $('#categoryFilter').val();
                var subCategoryId = $('#subCategoryFilter').val();
                var brandId = $('#brandFilter').val();
                var unitId = $('#unitFilter').val();
                
                if (categoryId) url.searchParams.set('category_id', categoryId);
                if (subCategoryId) url.searchParams.set('sub_category_id', subCategoryId);
                if (brandId) url.searchParams.set('brand_id', brandId);
                if (unitId) url.searchParams.set('unit_id', unitId);
                
                window.location.href = url.toString();
            });
        });
    </script>
@endsection