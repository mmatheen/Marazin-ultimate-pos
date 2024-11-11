@extends('layout.layout')
@section('content')
<div class="content container-fluid">

    <style>
        .hidden {
            display: block;
        }

    </style>
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

    <div class="container">
        <form id="addForm">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-body">
                            <div class="page-header">
                                <div class="row align-items-center">

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Product Name <span class="login-danger">*</span></label>
                                                    <input class="form-control" id="edit_product_name" name="product_name" type="text" placeholder="Product Name">
                                                    <span class="text-danger" id="product_name_error"></span>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Product SKU <span class="login-danger">*</span></label>
                                                    <input class="form-control" id="edit_sku" name="sku" type="text" placeholder="Product SKU">
                                                    <span class="text-danger" id="sku_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <label>Product Unit <span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" id="edit_unit_id" name="unit_id">
                                                        <option selected disabled>Product Unit</option>
                                                        @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">
                                                            {{ $unit->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger" id="unit_id_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <label>Product Brand <span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" id="edit_brand_id" name="brand_id">
                                                        <option selected disabled>Product Brand</option>
                                                        @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}">
                                                            {{ $brand->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger" id="brand_id_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <label>Main Category <span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" id="edit_main_category_id" name="main_category_id">
                                                        <option selected disabled>Main Catgory</option>
                                                        @foreach ($MainCategories as $MainCategory)
                                                        <option value="{{ $MainCategory->id }}">
                                                            {{ $MainCategory->mainCategoryName }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger" id="main_category_id_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <label>Sub Category <span class="login-danger">*</span></label>
                                                    <select class="form-control form-select" id="edit_sub_category_id" name="sub_category_id">
                                                        <option selected disabled>Sub Catgory</option>
                                                        @foreach ($SubCategories as $SubCategory)
                                                        <option value="{{ $SubCategory->id }}">
                                                            {{ $SubCategory->subCategoryname }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger" id="sub_category_id_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3 mt-4">
                                                <div class="input-group local-forms">
                                                    <label>Business Locations<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select multiple-location" id="edit_location_id" name="location_id"  multiple="multiple">
                                                        @foreach ($locations as $location)
                                                        <option value="{{ $location->id }}">
                                                            {{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span class="text-danger" id="location_id_error"></span>
                                                </div>
                                            </div>
                                        </div>


                                        <script>
                                            $(document).ready(function () {
                                                $('.multiple-location').select2({
                                                    placeholder: "  Business Location",
                                                    allowClear: true
                                                });
                                            });
                                        </script>


                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="mb-5">
                                                <div class="form-check">
                                                    <input type="hidden" name="stock_alert" value="0">
                                                    <input class="form-check-input" name="stock_alert" type="checkbox" id="edit_stock_alert" checked value="1">
                                                    <label class="form-check-label" for="edit_stock_alert" id="stock_alert_label">
                                                        Hide Alert Stock
                                                    </label>
                                                    <span class="text-danger" id="stock_alert_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4" id="alert_quantity_container">
                                            <div class="mb-5">
                                                <div class="form-group local-forms">
                                                    <label>Alert Quantity<span class="login-danger"></span></label>
                                                    <input class="form-control" type="number" id="edit_alert_quantity" name="alert_quantity" placeholder="0">
                                                    <span class="text-danger" id="alert_quantity_error"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <script>
                                            $(document).ready(function() {
                                                // Set initial state when the page loads
                                                toggleAlertQuantity();

                                                // Event listener for the checkbox change
                                                $('#edit_stock_alert').change(function() {
                                                    toggleAlertQuantity();
                                                });

                                                // Function to toggle the alert quantity field based on checkbox status
                                                function toggleAlertQuantity() {
                                                    if ($('#edit_stock_alert').is(':checked')) {
                                                        $('#alert_quantity_container').show(); // Show the alert quantity field when checkbox is checked
                                                        $('#stock_alert_label').text('Show Alert Stock'); // Change label to "Hide Alert Stock"
                                                    } else {
                                                        $('#alert_quantity_container').hide(); // Hide the alert quantity field when unchecked
                                                        $('#stock_alert_label').text('Hide Alert Stock'); // Change label to "Show Alert Stock"
                                                    }
                                                }
                                            });

                                        </script>
                                    </div>
                                    <div class="row">

                                        <div class="col-md-8">
                                            <div id="summernote"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label>Product image</label>
                                                    <div class="invoices-upload-btn">
                                                        <input type="file" accept="image/*" name="product_image" id="file" class="hide-input show-picture">
                                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                                &nbsp;</i> Browse..</label>
                                                    </div>
                                                    <span>Max File size: 5MB </span>
                                                </div>

                                                <div class="col-md-12 my-4 d-flex justify-content-center">
                                                    <img id="selectedImage" src="/assets/img/No Product Image Available.png" alt="Selected Image" width="200px" class="img-thumbnail" height="200px">
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
                                <div class="page-header">
                                    <div class="row align-items-center">
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check ms-3">
                                                        <input type="hidden" name="is_imei_or_serial_no" value="0">
                                                        <input class="form-check-input" name="is_imei_or_serial_no" type="checkbox" value="1" id="Enable_Product_description">
                                                        <label class="form-check-label" for="Enable_Product_description">
                                                            Enable Product description, IMEI or Serial Number
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-check ms-3">
                                                        <input type="hidden" name="is_for_selling" value="0">
                                                        <input class="form-check-input" name="is_for_selling" type="checkbox" value="1" id="Not_for_selling">
                                                        <label class="form-check-label" for="Not_for_selling">
                                                            Not for selling
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="input-group local-forms">
                                                        <select class="form-control form-select select" name="product_type" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                            <option selected disabled>Product Type</option>
                                                            <option value="Box">Box</option>
                                                            <option value="Bundle">Bundle</option>
                                                            <option value="Case">Case</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Pax<span class="login-danger"></span></label>
                                                            <input class="form-control" name="pax" type="number" placeholder="0">
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

                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-table">
                            <div class="card-body">
                                <div class="page-header">
                                    <div class="row align-items-center">

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead class="table-success">
                                                            <tr>
                                                                <th scope="col">Cost Price</th>
                                                                <th scope="col">Retail Price</th>
                                                                <th scope="col">Whole Sale Price</th>
                                                                <th scope="col">Special Price</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <input type="number" id="edit_original_price" name="original_price" class="form-control" placeholder="Rs .00">
                                                                            <span class="text-danger" id="original_price_error"></span>
                                                                        </div>
                                                                    </div>
                                                                </td>

                                                                <td>
                                                                    <div class="form-group">
                                                                        <input type="number" id="edit_retail_price" name="retail_price" class="form-control" placeholder="Rs .00">
                                                                        <span class="text-danger" id="retail_price_error"></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="form-group">
                                                                        <input type="number" id="edit_whole_sale_price" name="whole_sale_price" class="form-control" placeholder="Rs .00">
                                                                        <span class="text-danger" id="whole_sale_price_error"></span>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="form-group">
                                                                        <input type="text" id="edit_special_price" name="special_price" class="form-control" placeholder="Rs .00">
                                                                        <span class="text-danger" id="special_price_error"></span>
                                                                    </div>
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


                <div class="row mb-4">
                    <div class="gap-4 d-flex justify-content-center">
                        <div>
                            <button type="submit" class="btn btn-outline-primary" id="openingStockAndProduct">Save & Add Opening Stock</button>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-outline-danger" id="SaveProductButtonAndAnother">Save And Add Another</button>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-outline-primary" id="onlySaveProductButton">Save</button>
                        </div>
                    </div>
                </div>

                <!-- Button trigger modal -->

                {{-- Add/Edit modal row --}}
                <div class="row">
                    <div id="addOpeningStockModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <div class="text-center mt-2 mb-4">
                                        <h5 id="modalTitle"></h5>
                                    </div>


                                    <div class="row">
                                        <input type="hidden" name="edit_id" id="edit_id">

                                        <div class="col-md-6">
                                            <div class="form-group local-forms">
                                                <label>Location Name<span class="login-danger">*</span></label>
                                                <select id="edit_location_id" name="location_id" class="form-control">
                                                    <option selected disabled>Please Select Location</option>
                                                    @foreach($locations as $location)
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
                                                    @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>SkU <span class="login-danger">*</span></label>
                                                    <input class="form-control" id="edit_sku" name="sku" type="text" placeholder="Enter SKU">
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
                                                            <input class="form-control" id="edit_quantity" name="quantity" type="number" placeholder="Enter Quantity">
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
                                                            <input class="form-control" id="edit_unit_cost" name="unit_cost" type="text" placeholder="Enter Unit Cost">
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
                                                            <label>Lot No <span class="login-danger">*</span></label>
                                                            <input class="form-control" id="edit_lot_no" name="lot_no" type="text" placeholder="Enter Lot No">
                                                            <span class="text-danger" id="lot_no_error"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-group local-forms calendar-icon">
                                                    <label>Expiry Date <span class="login-danger">*</span></label>
                                                    <input type="text" name="expiry_date" id="edit_expiry_date" autocomplete="off" placeholder="YYYY.MM.DD" class="form-control datetimepicker me-5">
                                                </div>
                                                <span class="text-danger" id="expiry_date_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </form>
    </div>

</div>

@include('product.add_product_ajax')
@include('stock.import_opening_stock_ajax')
@endsection
