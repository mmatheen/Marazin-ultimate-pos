@extends('layout.layout')

@section('title','List Product')
@section('content')
<div class="content container-fluid">

    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">All Products</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Products</a></li>
                            <li class="breadcrumb-item active">List Products</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div>

            <div class="card card-body mb-4">
                <div class="student-group-form">
                    <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                        <i class="fas fa-filter"></i> &nbsp; Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="collapse" id="collapseExample">
            <div class="card card-body mb-4">
                <div class="student-group-form">
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Product <span class="login-danger"></span></label>
                                <select class="form-control select" id="productNameFilter">
                                    <option value="">Select Product</option>
                                    <!-- Populate with product options -->
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Category <span class="login-danger"></span></label>
                                <select class="form-control select" id="categoryFilter">
                                    <option value="">Select Category</option>
                                    <!-- Populate with category options -->
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Brand <span class="login-danger"></span></label>
                                <select class="form-control select" id="brandFilter">
                                    <option value="">Select Brand</option>
                                    <!-- Populate with brand options -->
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="form-group local-forms">
                                <label>Location <span class="login-danger"></span></label>
                                <select class="form-control select" id="locationFilter">
                                    <option value="">Select Location</option>
                                    <!-- Populate with location options -->
                                </select>
                            </div>
                        </div>
                    </div>
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
                            <li class="nav-item"><a class="nav-link active" href="#solid-justified-tab1" data-bs-toggle="tab"><i class="fas fa-boxes"></i> &nbsp;All Products</a></li>
                            {{-- <li class="nav-item"><a class="nav-link" href="#solid-justified-tab2" data-bs-toggle="tab"><i class="fas fa-hourglass-half"></i>&nbsp;Stock Report</a></li> --}}
                            @can('view import-product')
                            <li class="nav-item"><a class="nav-link" href="#solid-justified-tab3" data-bs-toggle="tab"><i class="fas fa-hourglass-half"></i>&nbsp;Import Bulk Product</a></li>
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
                                                @can('add product')
                                                <a href="{{ route('add-product') }}"><button type="button" class="btn btn-outline-info"><i class="fas fa-plus px-2"> </i>Add</button></a>
                                                @endcan
                                                <a href="{{ route('products.export-template') }}" class="btn btn-outline-info">
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
                                                    <th><input type="checkbox" id="selectAll" style="width: 16px; height: 16px;"></th> <!-- Select All checkbox -->
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
                                                    <th>Imei or Serial</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data from AJAX will be dynamically appended here -->
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <button id="addLocationButton" class="btn btn-outline-primary mt-2 btn-sm" style="display: none;"
                                    data-bs-toggle="modal" data-bs-target="#addLocationModal">Add Location</button>
                                
                                    <button id="applyDiscountButton" class="btn btn-outline-success mt-2 btn-sm ms-2" style="display: none;"
                                        data-bs-toggle="modal" data-bs-target="#applyDiscountModal">Apply Discount</button>
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
                                                        <form action="#" id="importProductForm" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <div class="row">

                                                                <div class="col-md-6 mt-4">
                                                                    <a class="btn btn-outline-success mt-2" id="export_btn" href="{{ route('excel-product-blank-template-export') }}"><i class="fas fa-download"></i> &nbsp; Download template file</a>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="d-flex justify-content-start">
                                                                        <div class="mb-3">
                                                                            <label>File To Import</label>
                                                                            <div class="invoices-upload-btn">
                                                                                <input type="file" name="file" id="file" class="hide-input">
                                                                                <label for="file" class="upload btn btn-outline-secondary"><i class="far fa-folder-open">
                                                                                        &nbsp;</i> Browse..</label>
                                                                            </div>
                                                                        </div>
                                                                        <div class="mt-3">
                                                                            <button type="submit" id="import_btn" class="btn btn-outline-primary ms-4 mt-3">Upload</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                 <div class="col-md-6 mt-3">
                                                                    <div class="progress mt-3" style="display: none;">
                                                                        <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
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
    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
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
                                        <select class="form-control form-select selectBox multiple-location" id="locations" multiple="multiple">
                                            <option>Select Business Locations</option>
                                        </select>
                                    </div>
                                </div>
                                        
                                            <script>

                                            $('#addLocationModal').on('shown.bs.modal', function () {
                                                $('.multiple-location').select2({
                                                    dropdownParent: $('#addLocationModal'), // Modal parent
                                                    placeholder: "Select Business Locations",
                                                    allowClear: true
                                                });
                                            });
                                            $('#addLocationModal').on('hidden.bs.modal', function () {
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
<div class="modal fade" id="applyDiscountModal" tabindex="-1" aria-labelledby="applyDiscountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applyDiscountModalLabel">Apply Discount to Selected Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="discountForm">
                    <div class="mb-3">
                        <label for="discountName" class="form-label">Discount Name</label>
                        <input type="text" class="form-control" id="discountName" required>
                    </div>
                    <div class="mb-3">
                        <label for="discountDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="discountDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="discountType" class="form-label">Discount Type</label>
                        <select class="form-select" id="discountType" required>
                            <option value="">Select Type</option>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="discountAmount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="discountAmount" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="startDate" class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control" id="startDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="endDate" class="form-label">End Date (Optional)</label>
                        <input type="datetime-local" class="form-control" id="endDate">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isActive">
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveDiscountButton">Save Discount</button>
            </div>
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
                                    <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn" style="width: 100%;">Delete</button>
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
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
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

@include('product.product_ajax')

@endsection