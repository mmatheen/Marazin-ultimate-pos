    @extends('layout.layout')
    @section('content')
        <div class="content container-fluid">

            <div class="row">
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-sm-12">
                            <div class="page-sub-header">
                                <h3 class="page-title">Add new product</h3>
                                <ul class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                                    <li class="breadcrumb-item active">Add new product</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- table row --}}
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row align-items-center">
                                    <form class="px-3" action="#">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Product Name <span class="login-danger">*</span></label>
                                                        <input class="form-control" type="text"
                                                            placeholder="Product Name">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>SKU <span class="login-danger">*</span></label>
                                                        <input class="form-control" type="text" placeholder="SKU">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms days">
                                                        <label>Barcode Type<span class="login-danger">*</span></label>
                                                        <select class="form-control form-select select">
                                                            <option selected disabled>Please Select </option>
                                                            <option>Code 128(C128)</option>
                                                            <option>Code 39(C39)</option>
                                                            <option>EAN -8</option>
                                                            <option>EAN -A</option>
                                                            <option>EAN -E</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="input-group local-forms">
                                                        <select class="form-control form-select"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                            <option selected disabled>Unit</option>
                                                            <option>Pieces(Pcs)</option>
                                                            <option>Packets(pck)</option>
                                                        </select>
                                                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#addModal"
                                                            id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="input-group local-forms">
                                                        <select class="form-control form-select"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                            <option selected disabled>Brand</option>
                                                            <option>Acer</option>
                                                            <option>Apple</option>
                                                        </select>
                                                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                                            data-bs-target="#addModal"
                                                            id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms days">
                                                        <label>Category<span class="login-danger"></span></label>
                                                        <select class="form-control form-select select">
                                                            <option selected disabled>Please Select </option>
                                                            <option>Books</option>
                                                            <option>Electronics</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">         
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms days">
                                                        <label>Sub category<span class="login-danger"></span></label>
                                                        <select class="form-control form-select select">
                                                            <option selected disabled>Please Select </option>
                                                            <option>...</option>
                                                            <option>...</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Business Locations <span
                                                                class="login-danger">*</span></label>
                                                        <input class="form-control" type="text"
                                                            placeholder="Awesome shop">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-check ms-3">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="isActive">
                                                        <label class="form-check-label" for="isActive">
                                                            Enable stock management at product level
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Alert Quantity<span class="login-danger">*</span></label>
                                                        <input class="form-control" type="text" placeholder="0">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div id="summernote"></div> 
                                            </div>
                                            <div class="col-md-4">
                                                <span>Product image</span>
                                                <input class="form-control" type="file">
                                                <label>Max File size: 5MB
                                                    Aspect ratio should be 1:1</label>
                                            </div>
                                            <div class="col-md-3">
                                                <span>Product brochure</span>
                                                <input class="form-control" type="file">
                                                <label>Max File size: 5MB
                                                    Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</label>
                                            </div>
                                        </div>
                                    </form>
                                    <!-- Add other elements if needed -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                
            </div>

        </div>

        {{-- modal Start --}}
        <div class="row">
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add Unit</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form id="addAndEditForm" method="POST" action="">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Name<span
                                                        class="login-danger">*</span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Awesome shop">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Short name<span
                                                        class="login-danger">*</span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Awesome shop">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group local-forms days">
                                            <label>Allow decimal<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select </option>
                                                <option>Yes</option>
                                                <option>No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endsection
