@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title" id="form-title">Add Purchase Return</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Purchase</a></li>
                                <li class="breadcrumb-item active" id="breadcrumb-title">Add Purchase Return</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <form class="px-3" id="addAndUpdatePurchaseReturnForm">
            <input type="hidden" id="purchase-return-id" name="purchase_return_id">

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row align-items-center">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                                    <label>Supplier<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" aria-label="Example text with button addon" aria-describedby="button-addon1" id="supplier-id" name="supplier_id" required>
                                                        <option></option>
                                                    </select>
                                                    <span class="text-danger" id="supplier_id_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-map-marker-alt"></i></span>
                                                    <label>Business Location<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" aria-label="Example text with button addon" aria-describedby="button-addon1" id="location-id" name="location_id" required>
                                                        <option></option>
                                                    </select>
                                                    <span class="text-danger" id="location_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Reference No<span class="login-danger"></span></label>
                                                    <input class="form-control" type="text" placeholder="Reference No" name="reference_no" id="reference_no">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="form-group local-forms calendar-icon">
                                                    <label>Date <span class="login-danger">*</span></label>
                                                    <input class="form-control datetimepicker" id="return_date" name="return_date" type="text" placeholder="DD-MM-YYYY" required>
                                                    <span class="text-danger" id="return_date_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label>Attach document</label>
                                                <div class="invoices-upload-btn">
                                                    <input type="file" accept=".pdf,image/*" name="attach_document" id="attach_document" class="hide-input show-file">
                                                    <label for="file" class="upload"><i class="far fa-folder-open">&nbsp;</i> Browse..</label>
                                                </div>
                                                <span>Max File size: 5MB Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</span>
                                            </div>
                                            <div class="col-md-12 my-4 d-flex justify-content-center">
                                                <img id="selectedImage" src="/assets/img/No Product Image Available.png" alt="Selected Image" width="100px" class="img-thumbnail" height="200px" style="display: none;">
                                                <iframe id="pdfViewer" width="100%" height="200px" style="display: none;"></iframe>
                                            </div>
                                        </div>
                                    </div>

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
                                        <div class="mb-3">

                                            <div class="input-group">
                                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-search"></i></span>
                                                <input type="text" id="productSearchInput" class="form-control" placeholder="Enter Product Name / SKU / Scan bar code" aria-label="Search">
                                                <div id="productSearchResults" class="dropdown-menu" style="display: none; max-height: 200px; overflow-y: auto;">
                                                    <!-- Product search results will appear here -->
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="datatable table table-stripped" style="width:100%" id="purchase_return">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product</th>
                                            <th>Batch</th>
                                            <th>Return Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                            <th><i class="fas fa-trash"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>

                            <hr>
                            <div class="table-footer">
                                <p>Total Items: <span id="total-items">.00</span></p>
                                <p>Net Total Amount: $<span id="net-total-amount">0.00</span></p>
                            </div>
                            <hr>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <button type="submit" class="btn btn-primary btn-lg">Save</button>
                <button type="reset" class="btn btn-secondary btn-reset btn-lg">Clear</button>
            </div>

        </form>
    </div>

 @include('purchase.purchase_return_ajax')
@endsection
