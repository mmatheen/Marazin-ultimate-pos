@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Purchase Return</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Purchase</a></li>
                                <li class="breadcrumb-item active">Add Purchase Return</li>
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
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <span class="input-group-text" id="basic-addon1"><i
                                                            class="fas fa-user"></i></span>
                                                    <select class="form-control form-select"
                                                        aria-label="Example text with button addon"
                                                        aria-describedby="button-addon1">
                                                        <option selected disabled>Supplier</option>
                                                        <option>Michael</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms days">
                                                    <label>Business Location<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Please Select</option>
                                                        <option>Awesome Shop</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Reference No<span class="login-danger"></span></label>
                                                    <input class="form-control" type="text" placeholder="Reference No">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms calendar-icon">
                                                    <label>Date Of Birth <span class="login-danger">*</span></label>
                                                    <input class="form-control datetimepicker" type="text"
                                                        placeholder="DD-MM-YYYY">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label>Product image</label>
                                                <div class="invoices-upload-btn">
                                                    <input type="file" accept="image/*" name="image" id="file"
                                                        class="hide-input">
                                                    <label for="file" class="upload"><i class="far fa-folder-open">
                                                            &nbsp;</i> Browse..</label>
                                                </div>
                                                <span>Max File size: 5MB
                                                    Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Add other elements if needed -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <h5>Search Products</h5>
                        <div class="page-header">
                            <div class="row d-flex justify-content-center mt-4">
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-search"></i></span>
                                        <input type="text" class="form-control"
                                            placeholder="Enter Product Name / SKU / Scan bar code" aria-label="Username"
                                            aria-describedby="basic-addon1">
                                    </div>
                                </div>

                                <!-- Add other elements if needed -->
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="example1">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th><i class="fas fa-trash"></i></th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <div class="row mt-5">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <div class="form-group local-forms days">
                                        <label>Purchase Tax<span class="login-danger">*</span></label>
                                        <select class="form-control form-select select">
                                            <option selected disabled>None</option>
                                            <option>VAT@10%</option>
                                            <option>CGST@10%</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Save</button>
        </form>
    </div>

    </div>
@endsection
