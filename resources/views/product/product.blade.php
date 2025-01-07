@extends('layout.layout')
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
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Gender <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option>Select Gender</option>
                                            <option>Female</option>
                                            <option>Male</option>
                                            <option>Others</option>
                                        </select>
                                    </div>
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
                                <li class="nav-item"><a class="nav-link active" href="#solid-justified-tab1"
                                        data-bs-toggle="tab"><i class="fas fa-boxes"></i> &nbsp;All Products</a></li>
                                <li class="nav-item"><a class="nav-link" href="#solid-justified-tab2"
                                        data-bs-toggle="tab"><i class="fas fa-hourglass-half"></i>&nbsp;Stock Report</a>
                                </li>
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
                                                    <!-- Button trigger modal -->
                                                    <a href="{{ route('add-product') }}"><button type="button"
                                                            class="btn btn-outline-info">
                                                            <i class="fas fa-plus px-2"> </i>Add
                                                        </button></a>

                                                    <button type="button" class="btn btn-outline-info "
                                                        data-bs-toggle="modal" data-bs-target="#addModal">
                                                        <i class="fas fa-download"></i>&nbsp;&nbsp;Download
                                                    </button>


                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="datatable table table-stripped" style="width:100%" id="productTable">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" id="allchecked" onclick="toggleLoginFields(id, '.checked')" /></th>
                                                        <th>Product Image</th>
                                                        <th>Action</th>
                                                        <th>Product</th>
                                                        <th>Business Location</th>
                                                        <th>Selling Price</th>
                                                        <th>Current Stock</th>
                                                        <th>Product Type</th>
                                                        <th>Category</th>
                                                        <th>Brand</th>
                                                        <th>SKU</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Data from AJAX will be dynamically appended here -->
                                                </tbody>
                                            </table>
                                        </div>


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

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <div>
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
                                    <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel
                                    </a>
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
    </div>

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

    <script>
        function toggleLoginFields(propertyId, actionClass) {
            var checkBox = document.getElementById(propertyId);
            var loginFields = document.querySelectorAll(actionClass);
            loginFields.forEach(function(field) {
                // console.log(checkBox.checked);
                field.checked = checkBox
                    .checked; // field.checked --> All checkbox fields are currently which state
                //then it attache the value from which selected box was checked
            });
        }
    </script>


@include('product.product_ajax')
@endsection

