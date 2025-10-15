@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Import Opening Stock</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                                <li class="breadcrumb-item active">Import Opening Stock</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->
                                    <button type="button" class="btn btn-outline-info " id="addOpeningStockButton">
                                        New <i class="fas fa-plus px-2"> </i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="openingStock" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>SKU</th>
                                        <th>Location Name</th>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Batch Number</th>
                                        <th>Expiry Date</th>
                                        <th>Action</th>
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

        {{-- Add/Edit modal row --}}
        <div class="row">
            <div id="addAndEditOpeningStockModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="text-center mt-2 mb-4">
                                <h5 id="modalTitle"></h5>
                            </div>
                            <form id="addAndUpdateForm">

                                <div class="row">
                                    <input type="hidden" name="edit_id" id="edit_id">

                                    <div class="col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Location Name<span class="login-danger">*</span></label>
                                            <select id="edit_location_id" name="location_id" class="form-control">
                                                <option selected disabled>Please Select Location</option>
                                                @foreach ($locations as $location)
                                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Product Name<span class="login-danger">*</span></label>
                                            <select id="edit_product_id" name="product_id" class="form-control">
                                                <option selected disabled>Please Select Product</option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->product_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>SkU <span class="login-danger">*</span></label>
                                                <input class="form-control" id="edit_sku" name="sku" type="text"
                                                    placeholder="Enter SKU">
                                                <span class="text-danger" id="sku_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group local-forms">
                                                        <label>Quantity <span class="login-danger">*</span></label>
                                                        <input class="form-control" id="edit_quantity" name="quantity"
                                                            type="number" placeholder="Enter Quantity">
                                                        <span class="text-danger" id="quantity_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group local-forms">
                                                        <label>Unit Cost <span class="login-danger">*</span></label>
                                                        <input class="form-control" id="edit_unit_cost" name="unit_cost"
                                                            type="text" placeholder="Enter Unit Cost">
                                                        <span class="text-danger" id="unit_cost_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col">
                                                    <div class="form-group local-forms">
                                                        <label>Batch No <span class="login-danger">*</span></label>
                                                        <input class="form-control" id="edit_batch_id" name="batch_id"
                                                            type="text" placeholder="Enter Batch No">
                                                        <span class="text-danger" id="batch_id_error"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms calendar-icon">
                                                <label>Expiry Date <span class="login-danger">*</span></label>
                                                <input type="text" name="expiry_date" id="edit_expiry_date"
                                                    autocomplete="off" placeholder="YYYY.MM.DD"
                                                    class="form-control datetimepicker me-5">
                                            </div>
                                            <span class="text-danger" id="expiry_date_error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                    <button type="button" class="btn btn-outline-danger"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
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
                                        <button type="submit"
                                            class="confirm_delete_btn btn btn-primary paid-continue-btn"
                                            style="width: 100%;">Delete</button>
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


    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Import Opening Stock</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                                <li class="breadcrumb-item active">Import Opening Stock</li>
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
                                <form action="#" id="importOpeningStockForm" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>File To Import</label>
                                            <div class="invoices-upload-btn">
                                                <input type="file" name="file" id="file" class="hide-input">
                                                <label for="file" class="upload"><i class="far fa-folder-open">
                                                        &nbsp;</i> Browse..</label>
                                            </div>
                                            <button type="submit" id="import_btn"
                                                class="btn btn-outline-primary mt-3">Upload</button>
                                        </div>

                                    </div>
                                    <!-- Add other elements if needed -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="progress mt-3" style="display: none;">
                                                <div class="progress-bar" role="progressbar" style="width: 0%;"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-danger" id="file_error"></span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <a class="btn btn-outline-primary mt-2 me-2" id="export_btn"
                                                    href="{{ route('excel-export-student') }}"><i
                                                        class="fas fa-download"></i> &nbsp; Download</a>
                                                <a class="btn btn-outline-success mt-2" id="export_btn"
                                                    href="{{ route('excel-blank-template-export') }}"><i
                                                        class="fas fa-download"></i> &nbsp; Download template file</a>
                                            </div>
                                        </div>
                                    </div>

                                </form>
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
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <h5>Instructions</h5>
                                            <b>Follow the instructions carefully before importing the file.</b>
                                            <p>The columns of the file should be in the following order.</p>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-hover">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Column Number</th>
                                                        <th scope="col">Column Name</th>
                                                        <th scope="col">Instruction</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">1</th>
                                                        <td>SKU(Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">2</th>
                                                        <td>Location (Optional)
                                                            If blank first business location will be used</td>
                                                        <td>Name of the business location</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">3</th>
                                                        <td>Quantity (Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">4</th>
                                                        <td>Unit Cost (Before Tax) (Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">5</th>
                                                        <td>Lot Number (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">6</th>
                                                        <td>Expiry Date (Optional)</td>
                                                        <td>Stock expiry date in Business date format
                                                            <b>dd-mm-yyyy, Type: text, Example: 12-09-2024</b>
                                                        </td>
                                                    </tr>
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
        </div>
    </div>
    <style>
        .color {
            background-color: rgb(230, 233, 233);
            padding: 35px 20px 20px;
            border-radius: 8px;
        }

        #bg-color {
            background-color: rgb(184, 185, 243) !important;
        }


        .progress {
            height: 20px;
        }

        .progress-bar {
            background-color: #007bff;
            transition: width 0.4s ease;
        }
    </style>
    @include('stock.import_opening_stock_ajax')
@endsection
