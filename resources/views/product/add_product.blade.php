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
                                                    <input class="form-control" type="text" placeholder="Product Name">
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
                                            <div class="mb-5">
                                                <div class="form-group local-forms">
                                                    <label>Barcode Type<span class="login-danger">*</span></label>
                                                    <select class="form-control select2Box form-select select">
                                                        <option selected disabled>Please Select </option>
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
                                            <div class="mb-5">
                                                <div class="input-group local-forms">
                                                    <select id="unitSelect" class="form-control select2Box form-select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Brand</option>
                                                    </select>
                                                    <button class="btn btn-outline-primary" type="button" id="addUnitButton"><i class="fas fa-plus-circle"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-5">
                                                <div class="input-group local-forms">
                                                    <select id="brandSelect" class="form-control select2Box form-select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Brand</option>
                                                    </select>

                                                    <button class="btn btn-outline-primary" type="button" id="addBrandButton"><i class="fas fa-plus-circle"></i></button>
                                                </div>
                                            </div>
                                        </div>



                                        <div class="col-md-4">
                                            <div class="mb-5">
                                                <div class="input-group local-forms">
                                                    <select class="form-control select2Box form-select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Category</option>
                                                        <option>Books</option>
                                                        <option>Electronics</option>
                                                    </select>
                                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#addCategoryModal" id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
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
                                                    <label>Business Locations</label>
                                                    <select class="form-control form-select select" multiple="multiple">
                                                        <option>Kalmunai</option>
                                                        <option>Colombo</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" id="isActive" checked onclick="toggleLoginFields(id,'.hidden')">
                                                    <label class="form-check-label" for="isActive">
                                                        Enable stock management at product level
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms hidden">
                                                    <label>Alert Quantity<span class="login-danger">*</span></label>
                                                    <input class="form-control" type="text" placeholder="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div id="summernote"></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Product image</label>
                                            <div class="invoices-upload-btn">
                                                <input type="file" accept="image/*" name="image" id="file" class="hide-input">
                                                <label for="file" class="upload"><i class="far fa-folder-open"> &nbsp;</i> Browse..</label>
                                            </div>
                                            <span>Max File size: 5MB
                                                Aspect ratio should be 1:1</span>
                                        </div>

                                        <div class="col-md-4">
                                            <label>Product image:</label>
                                            <div class="invoices-upload-btn">
                                                <input type="file" accept="image/*" name="image" id="file" class="hide-input">
                                                <label for="file" class="upload"><i class="far fa-folder-open"> &nbsp;</i> Browse..</label>
                                            </div>
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
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <form class="px-3" action="#">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" value="" id="Enable_Product_description?">
                                                    <label class="form-check-label" for="Enable_Product_description?">
                                                        Enable Product description, IMEI or Serial Number
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" value="" id="Not_for_selling?">
                                                    <label class="form-check-label" for="Not_for_selling?">
                                                        Not for selling
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms">
                                                <label>Weight<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Weight">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group local-forms">
                                                <label>Service staff timer/Preparation time (In minutes)<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Service staff timer/Preparation time (In minutes)">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check ms-3">
                                                <input class="form-check-input" type="checkbox" value="" id="Disable_Woocommerce_Sync?">
                                                <label class="form-check-label" for="Disable_Woocommerce_Sync?">
                                                    Disable Woocommerce Sync
                                                </label>
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
                                                <div class="input-group local-forms">
                                                    <select class="form-control form-select select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Applicable Tax</option>
                                                        <option>None</option>
                                                        <option>VAT@10%</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <select class="form-control form-select select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Selling Price Tax Type</option>
                                                        <option>Inclusive</option>
                                                        <option>Exclusive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <select class="form-control form-select select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                                        <option selected disabled>Product Type</option>
                                                        <option>Single</option>
                                                        <option>Variable</option>
                                                        <option>Combo</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead class="table-success">
                                                        <tr>
                                                            <th scope="col">Default Purchase Price</th>
                                                            <th scope="col">x Margin(%) </th>
                                                            <th scope="col">Default Selling Price</th>
                                                            <th scope="col">Product image</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="row">
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label>Exc. tax:*</label>
                                                                            <input type="text" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-sm-6">
                                                                        <div class="form-group">
                                                                            <label>Inc. tax:*</label>
                                                                            <input type="text" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="form-group">
                                                                    <label>&nbsp;</label>
                                                                    <input type="text" class="form-control">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="form-group">
                                                                    <label>Exc. Tax</label>
                                                                    <input type="text" class="form-control">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <label>Product image:</label>
                                                                        <div class="invoices-upload-btn">
                                                                            <input type="file" accept="image/*" name="image" id="file" class="hide-input">
                                                                            <label for="file" class="upload"><i class="far fa-folder-open"> &nbsp;</i> Browse..</label>
                                                                        </div>
                                                                        <span>Max File size: 5MB
                                                                            Aspect ratio should be 1:1</span>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
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


        <div class="row mb-4">
            <div class="gap-4 d-flex justify-content-center">
                <div>
                    <button class="btn btn-primary">Save & Add</button>
                </div>
                <div>
                    <button class="btn btn-danger">Save And Add Another</button>
                </div>
                <div>
                    <button class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>

        {{-- modal Start --}}
        @include('unit.unit_modal')
        @include('brand.brand_modal')
        @include('category.category_modal')
        {{-- this is modal --}}


        <script>
            function toggleLoginFields(propertyId, actionClass) {
                var checkBox = document.getElementById(propertyId);
                var loginFields = document.querySelectorAll(actionClass);

                loginFields.forEach(function(field) {
                    field.style.display = checkBox.checked ? "block" : "none";
                });
            }

        </script>

    </div>

    @include('brand.brand_ajax')
    @include('unit.unit_ajax')
    @include('product.add_product_ajax')
   
    @endsection
