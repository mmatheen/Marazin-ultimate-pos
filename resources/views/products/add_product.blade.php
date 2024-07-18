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

                                            <div class="col-md-4">
                                                <div class="input-group local-forms">
                                                    <select class="form-control form-select"
                                                        aria-label="Example text with button addon"
                                                        aria-describedby="button-addon1">
                                                        <option selected disabled>Please Select </option>
                                                        <option>Pieces(Pcs)</option>
                                                        <option>Packets(pck)</option>
                                                    </select>
                                                    <button class="btn btn-outline-primary" type="button"
                                                        id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                                </div>

                                            </div>


                                            <div class="col-md-4">
                                                <div class="form-group local-forms days">
                                                    <label>Brand<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Please Select </option>
                                                        <option>Acer</option>
                                                        <option>Apple</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group local-forms days">
                                                    <label>Category<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Please Select </option>
                                                        <option>Books</option>
                                                        <option>Electronics</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="form-group local-forms days">
                                                    <label>Sub category<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Please Select </option>
                                                        <option>...</option>
                                                        <option>...</option>
                                                    </select>
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
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" value="" id="isActive">
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
                                        </div>
                                    </form>
                                    <!-- Add other elements if needed -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    @endsection



    {{-- <form class="px-3" action="#">
        <div class="col-md-4">
            <div class="mb-3">
                <div class="form-group local-forms">
                    <label>Name <span class="login-danger">*</span></label>
                    <input class="form-control" type="text" placeholder="Enter Name">
                </div>
            </div>
        </div>


        <div class="mb-3">
            <div class="form-group local-forms">
                <label>Description <span class="login-danger">*</span></label>
                <textarea class="form-control" type="text" placeholder="Enter Description"></textarea>
            </div>
        </div>

        <div class="mb-3">
            <div class="row">
                <div class="col">
                    <div class="form-group local-forms">
                        <label>Duration <span class="login-danger">*</span></label>
                        <input class="form-control" type="number"
                            placeholder="Enter Duration">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group local-forms days">
                        <label>Blood Group <span class="login-danger">*</span></label>
                        <select class="form-control form-select">
                            <option selected disabled>Please Select </option>
                            <option>Days</option>
                            <option>Months</option>
                            <option>Years</option>
                        </select>
                    </div>
                </div>

            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary">Save changes</button>
            <button type="button" class="btn btn-danger"
                data-bs-dismiss="modal">Close</button>
        </div>
    </form> --}}
