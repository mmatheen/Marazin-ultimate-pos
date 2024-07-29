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
                                            <table class="datatable table table-stripped" style="width:100%" id="example1">
                                                <thead>
                                                    <tr>
                                                        <th><input type="checkbox" name="" value=""
                                                                id="allchecked"
                                                                onclick="toggleLoginFields(id,'.checked')" /></th>
                                                        <th>Product Image</th>
                                                        <th>Action</th>
                                                        <th>Product</th>
                                                        <th>Business Location</th>
                                                        <th>Selling Price</th>
                                                        <th>Current Stock</th>
                                                        <th>Product Type</th>
                                                        <th>Catergory</th>
                                                        <th>Brand</th>
                                                        <th>Tax</th>
                                                        <th>SKU</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="checkbox" name="" value=""
                                                                class="checked" /></td>
                                                        <td><img src="https://via.placeholder.com/50" alt="Product 1" />
                                                        </td>
                                                        <td>
                                                            <div class="dropdown dropdown-action">
                                                                <a href="#" class="action-icon dropdown-toggle"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"><button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button></a>
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                                                    
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-database"></i>&nbsp;&nbsp;Add or edit opening stock</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-history"></i></i>&nbsp;&nbsp;Product stock history</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-copy"></i>&nbsp;&nbsp;Duplicate Product</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>Product 1</td>
                                                        <td>Location 1</td>
                                                        <td>$10.00</td>
                                                        <td>100</td>
                                                        <td>Type 1</td>
                                                        <td>Category 1</td>
                                                        <td>Brand 1</td>
                                                        <td>5%</td>
                                                        <td>SKU001</td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="checkbox" name="" value=""
                                                                class="checked" /></td>
                                                        <td><img src="https://via.placeholder.com/50" alt="Product 2" />
                                                        </td>
                                                        <td>
                                                            <div class="dropdown dropdown-action">
                                                                <a href="#" class="action-icon dropdown-toggle"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"><button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button></a>
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                                                    
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-database"></i>&nbsp;&nbsp;Add or edit opening stock</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-history"></i></i>&nbsp;&nbsp;Product stock history</a>
                                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-copy"></i>&nbsp;&nbsp;Duplicate Product</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>Product 2</td>
                                                        <td>Location 2</td>
                                                        <td>$20.00</td>
                                                        <td>200</td>
                                                        <td>Type 2</td>
                                                        <td>Category 2</td>
                                                        <td>Brand 2</td>
                                                        <td>10%</td>
                                                        <td>SKU002</td>
                                                    </tr>
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

        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  ...
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary">Save changes</button>
                </div>
              </div>
            </div>
          </div>


    </div>


    {{-- Edit modal row --}}
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
@endsection
