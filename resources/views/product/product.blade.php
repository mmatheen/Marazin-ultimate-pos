@extends('layout.layout')

@section('title', 'List Product')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Products</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Products</a></li>
                                <li class="breadcrumb-item active">All Products</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div>

                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>

            <div class="collapse" id="collapseExample">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Product <span class="login-danger"></span></label>
                                    <select class="form-control selectBox select2" id="productNameFilter"
                                        style="width: 100%;">
                                        <option value="">Select Product</option>
                                        <!-- Populate with product options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Category <span class="login-danger"></span></label>
                                    <select class="form-control selectBox select2" id="categoryFilter" style="width: 100%;">
                                        <option value="">Select Category</option>
                                        <!-- Populate with category options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Brand <span class="login-danger"></span></label>
                                    <select class="form-control selectBox select2" id="brandFilter" style="width: 100%;">
                                        <option value="">Select Brand</option>
                                        <!-- Populate with brand options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Location <span class="login-danger"></span></label>
                                    <select class="form-control selectBox select2" id="locationFilter" style="width: 100%;">
                                        <option value="">Select Location</option>
                                        <!-- Populate with location options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Stock Status <span class="login-danger"></span></label>
                                    <select class="form-control selectBox select2" id="stockStatusFilter" style="width: 100%;">
                                        <option value="">All Products</option>
                                        <option value="in_stock">In Stock (Qty > 0)</option>
                                        <option value="out_of_stock">Out of Stock (Qty = 0)</option>
                                        <option value="low_stock">Low Stock (Alert Level)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <div class="form-group local-forms">

                                    <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn" style="height: 40px;">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <script>
                            $(document).ready(function() {
                                $('.selectBox.select2').select2({
                                    width: 'resolve'
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>

        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="row">
                        <div class="col-md-12">
                            <ul class="nav nav-tabs nav-tabs-solid">
                                <li class="nav-item"><a class="nav-link active" href="#solid-justified-tab1"
                                        data-bs-toggle="tab"><i class="fas fa-boxes"></i> &nbsp;All Products</a></li>
                                {{-- <li class="nav-item"><a class="nav-link" href="#solid-justified-tab2" data-bs-toggle="tab"><i class="fas fa-hourglass-half"></i>&nbsp;Stock Report</a></li> --}}
                                @can('import product')
                                    <li class="nav-item"><a class="nav-link" href="#solid-justified-tab3"
                                            data-bs-toggle="tab"><i class="fas fa-hourglass-half"></i>&nbsp;Import Bulk
                                            Product</a></li>
                                @endcan
                            </ul>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tab-content">
                                <div class="tab-pane show active" id="solid-justified-tab1">
                                    <div class="card-body">
                                        <div class="page-header">
                                            <div class="row align-items-center">
                                                <div class="col-auto text-end float-end ms-auto download-grp">
                                                    @can('create product')
                                                        <a href="{{ route('add-product') }}"><button type="button"
                                                                class="btn btn-outline-info"><i class="fas fa-plus px-2">
                                                                </i>Add</button></a>
                                                    @endcan
                                                    <a href="{{ route('products.export-template') }}"
                                                        class="btn btn-outline-info">
                                                        <i class="fas fa-download"></i>&nbsp;&nbsp;Download
                                                    </a>


                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="datatable table table-stripped" style="width:100%"
                                                id="productTable">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" id="selectAll"
                                                                style="width: 16px; height: 16px;"></th>
                                                        <!-- Select All checkbox -->
                                                        <th>Action</th>
                                                        <th>Product Image</th>
                                                        <th>Product Name</th>
                                                        <th>SKU</th>
                                                        <th>Business Location</th>
                                                        <th>Selling Price</th>
                                                        <th>Current Stock</th>
                                                        {{-- <th>Product Type</th> --}}
                                                        <th>Category</th>
                                                        <th>Brand</th>
                                                        <th>Status</th>
                                                        <th>Imei or Serial</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data from AJAX will be dynamically appended here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <button id="addLocationButton" class="btn btn-outline-primary mt-2 btn-sm"
                                            style="display: none;" data-bs-toggle="modal"
                                            data-bs-target="#addLocationModal">Add Location</button>

                                        <button id="applyDiscountButton" class="btn btn-outline-success mt-2 btn-sm ms-2"
                                            style="display: none;" data-bs-toggle="modal"
                                            data-bs-target="#applyDiscountModal">Apply Discount</button>
                                    </div>
                                </div>
                                <div class="tab-pane" id="solid-justified-tab2">
                                    <div class="table-responsive">
                                        <table class="datatable table table-stripped" style="width:100%" id="example1">
                                            <thead>
                                                <tr>
                                                    <th>Action</th>
                                                    <th>SKU</th>
                                                    <th>Product</th>
                                                    <th>Variation</th>
                                                    <th>Category</th>
                                                    <th>Location</th>
                                                    <th>Unit Selling Price</th>
                                                    <th>Current Stock</th>
                                                    <th>Current Stock Value (By Purchase Price)</th>
                                                    <th>Current Stock Value (By Sale Price)</th>
                                                    <th>Potential Profit</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data from AJAX will be dynamically appended here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane" id="solid-justified-tab3">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card card-table">
                                                <div class="card-body">
                                                    <div class="page-header">
                                                        <div class="row align-items-center">
                                                            <form action="#" id="importProductForm" method="POST"
                                                                enctype="multipart/form-data">
                                                                @csrf
                                                                <div class="row">

                                                                    <div class="col-md-6 mt-4">
                                                                        <a class="btn btn-outline-success mt-2"
                                                                            id="export_btn"
                                                                            href="{{ route('excel-product-blank-template-export') }}"><i
                                                                                class="fas fa-download"></i> &nbsp;
                                                                            Download template file</a>
                                                                    </div>

                                                                    <!-- Location Selection -->
                                                                    <div class="col-md-12 mt-3">
                                                                        <div class="mb-3">
                                                                            <label for="import_location"
                                                                                class="form-label"><i
                                                                                    class="fas fa-map-marker-alt"></i>
                                                                                Select Location for Import <span
                                                                                    class="text-danger">*</span></label>
                                                                            <select name="import_location"
                                                                                id="import_location" class="form-control"
                                                                                required>
                                                                                <option value="">Choose Location to
                                                                                    Import Products...</option>
                                                                            </select>
                                                                            <small class="text-muted">All products from the
                                                                                uploaded file will be imported to the
                                                                                selected location</small>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">File To
                                                                                Import</label>
                                                                            <div class="input-group">
                                                                                <input type="file" name="file"
                                                                                    id="file" class="form-control"
                                                                                    style="padding: 0.375rem 0.75rem; max-width: 300px;">
                                                                                <button type="submit" id="import_btn"
                                                                                    class="btn btn-primary"
                                                                                    style="min-width: 120px; max-width: 150px;">Upload</button>
                                                                            </div>
                                                                            <small id="selectedFileName"
                                                                                class="form-text text-success mt-1"></small>
                                                                        </div>
                                                                    </div>

                                                                    <script>
                                                                        document.getElementById('file').addEventListener('change', function(e) {
                                                                            const fileName = e.target.files.length ? e.target.files[0].name : '';
                                                                            document.getElementById('selectedFileName').textContent = fileName ? fileName : '';
                                                                        });
                                                                    </script>
                                                                    <div class="col-md-6 mt-3">
                                                                        <div class="progress mt-3" style="display: none;">
                                                                            <div class="progress-bar" role="progressbar"
                                                                                style="width: 0%;" aria-valuenow="0"
                                                                                aria-valuemin="0" aria-valuemax="100">
                                                                            </div>
                                                                        </div>
                                                                        <span class="text-danger" id="file_error"></span>
                                                                    </div>


                                                                </div>


                                                            </form>



                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for viewing product details -->
        <div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="productDetails">
                        <!-- Product details will be dynamically injected here -->
                    </div>
                </div>
            </div>
        </div>


        <!-- Add Location Modal -->
        <div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addLocationModalLabel">Add Location</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addLocationForm">
                            <div class="mb-3">
                                <label for="locations" class="form-label">Select Locations</label>
                                <div class="col-md-12"> <!-- Full width select box -->
                                    <div class="mb-3">
                                        <select class="form-control form-select selectBox multiple-location"
                                            id="locations" multiple="multiple">
                                            <option>Select Business Locations</option>
                                        </select>
                                    </div>
                                </div>

                                <script>
                                    $('#addLocationModal').on('shown.bs.modal', function() {
                                        $('.multiple-location').select2({
                                            dropdownParent: $('#addLocationModal'), // Modal parent
                                            placeholder: "Select Business Locations",
                                            allowClear: true
                                        });
                                    });
                                    $('#addLocationModal').on('hidden.bs.modal', function() {
                                        $('.multiple-location').select2('destroy'); // Destroy select2 on modal close
                                    });
                                </script>


                                {{-- <select id="locations" class="form-control" multiple>
                                    <!-- Options to be dynamically populated -->
                                </select> --}}
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="saveLocationsButton">Save</button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Apply Discount Modal -->
        <!-- Apply Discount Modal -->
        <div class="modal fade" id="applyDiscountModal" tabindex="-1" role="dialog" aria-labelledby="applyDiscountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="applyDiscountModalLabel">Apply Discount to Selected Products</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="discountForm">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="discountName">Discount Name *</label>
                                        <input type="text" class="form-control" id="discountName" name="name" required>
                                        <div class="invalid-feedback" id="name-error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="discountDescription">Description</label>
                                        <textarea class="form-control" id="discountDescription" name="description" rows="2"></textarea>
                                        <div class="invalid-feedback" id="description-error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="discountType">Discount Type *</label>
                                        <select class="form-control" id="discountType" name="type" required>
                                            <option value="">Select Type</option>
                                            <option value="fixed">Fixed Amount</option>
                                            <option value="percentage">Percentage</option>
                                        </select>
                                        <div class="invalid-feedback" id="type-error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="discountAmount">Amount *</label>
                                        <input type="number" step="0.01" min="0" class="form-control" id="discountAmount" name="amount" required>
                                        <small class="form-text text-muted">For percentage: Max 100%. For fixed amount: Should not exceed product price.</small>
                                        <div class="invalid-feedback" id="amount-error"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="startDate">Start Date *</label>
                                        <input type="date" class="form-control" id="startDate" name="start_date" required>
                                        <div class="invalid-feedback" id="start_date-error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="endDate">End Date (Optional)</label>
                                        <input type="date" class="form-control" id="endDate" name="end_date">
                                        <div class="invalid-feedback" id="end_date-error"></div>
                                    </div>
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" checked>
                                            <label class="form-check-label" for="isActive">Active</label>
                                        </div>
                                    </div>
                                    <div id="selected-products-info" class="mt-3">
                                        <div class="alert alert-info">
                                            <strong><i class="fas fa-info-circle"></i> Selected Products:</strong>
                                            <div id="selected-products-count">0 products selected</div>
                                        </div>
                                    </div>
                                    <div id="discount-validation-warning" class="mt-2" style="display:none;">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> <span id="validation-message"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveDiscountButton">Apply Discount</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        {{-- Delete modal --}}
        <div id="deleteModal" class="modal custom-modal fade" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3 id="deleteName"></h3>
                            <p>Are you sure want to delete?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <input type="hidden" id="deleting_id">
                                <div class="row">
                                    <div class="col-6">
                                        <button type="submit"
                                            class="confirm_delete_btn btn btn-primary paid-continue-btn"
                                            style="width: 100%;">Delete</button>
                                    </div>
                                    <div class="col-6">
                                        <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Edit modal row --}}
    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog lg">
            <div class="modal-content" id="modalContent">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="productDetails">
                    <!-- Modal content will be dynamically inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- IMEI Modal -->
    <div class="modal fade" id="imeiModal" tabindex="-1" aria-labelledby="imeiModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imeiModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentProductId" value="">
                    <table class="table table-bordered" id="imeiTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>IMEI Number</th>
                                <th>Location</th>
                                <th>Batch No</th>
                                <th>Status</th>

                            </tr>
                        </thead>
                        <tbody id="imeiTableBody">
                            <!-- IMEIs will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Prices Modal -->
    <div class="modal fade" id="batchPricesModal" tabindex="-1" aria-labelledby="batchPricesModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchPricesModalLabel">Edit Batch Prices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-12 col-md-6 mb-2 mb-md-0">
                            <h6><strong>Product:</strong> <span id="productName"></span></h6>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6><strong>SKU:</strong> <span id="productSku"></span></h6>
                        </div>
                    </div>

                    <div id="batchPricesTable" class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap">Batch No</th>
                                    <th class="text-nowrap">Stock</th>
                                    <th class="text-nowrap d-none d-md-table-cell">Cost Price</th>
                                    <th class="text-nowrap">Wholesale</th>
                                    <th class="text-nowrap">Special</th>
                                    <th class="text-nowrap">Retail</th>
                                    <th class="text-nowrap">Max Retail</th>
                                    <th class="text-nowrap d-none d-lg-table-cell">Expiry</th>
                                    <th class="text-nowrap d-none d-lg-table-cell">Locations</th>
                                </tr>
                            </thead>
                            <tbody id="batchPricesTableBody">
                                <!-- Batch rows will be populated here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile-friendly info cards for small screens -->
                    <div id="batchPricesMobile" class="d-block d-md-none">
                        <!-- Mobile cards will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveBatchPrices">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Locations Modal -->
    <div class="modal fade" id="locationsModal" tabindex="-1" aria-labelledby="locationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationsModalTitle">Product Locations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Location Name</th>
                                <th class="text-end" style="width: 100px;">Stock Qty</th>
                            </tr>
                        </thead>
                        <tbody id="locationsTableBody">
                            <!-- Location rows will be populated here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    </div>

    <script>
        // Set global permission variables - Most efficient Laravel approach
        window.canEditBatchPrices = {{ Js::from(auth()->user()->can('edit batch prices')) }};
    </script>

    @include('product.product_ajax')

@endsection
