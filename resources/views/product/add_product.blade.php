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
                                                <div class="input-group local-forms">
                                                    <select id="unitSelect" class="form-control select2Box form-select">
                                                        <option selected disabled>Unit</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">


                                        <div class="col-md-4">
                                            <div class="mb-5">
                                                <div class="input-group local-forms">
                                                    <select id="brandSelect" class="form-control select2Box form-select">
                                                        <option selected disabled>Brand</option>
                                                    </select>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-5">
                                                <div class="input-group local-forms">
                                                    <select class="form-control select2Box form-select">
                                                        <option selected disabled>Category</option>
                                                        @foreach ($MainCategories as $MainCategory)
                                                            <option value="{{ $MainCategory->id }}">
                                                                {{ $MainCategory->mainCategoryName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Sub category<span class="login-danger"></span></label>
                                                    <select class="form-control form-select select2Box">
                                                        @foreach ($SubCategories as $SubCategory)
                                                        <option value="{{ $SubCategory->id }}">
                                                            {{ $SubCategory->subCategoryname }}</option>
                                                    @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
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
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" id="isActive" checked
                                                        onclick="toggleLoginFields(id,'.hidden')">
                                                    <label class="form-check-label" for="isActive">
                                                        Hide  Alert Stock
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

                                    </div>

                                    <div class="row">

                                        <div class="col-md-8">
                                            <div id="summernote" value></div>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Product image</label>
                                            <div class="invoices-upload-btn">
                                                <input type="file" accept="image/*" name="image" id="file"
                                                    class="hide-input">
                                                <label for="file" class="upload"><i class="far fa-folder-open">
                                                        &nbsp;</i> Browse..</label>
                                            </div>
                                            <span>Max File size: 5MB
                                                Aspect ratio should be 1:1</span>
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
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="Enable_Product_description?">
                                                    <label class="form-check-label" for="Enable_Product_description?">
                                                        Enable Product description, IMEI or Serial Number
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="form-check ms-3">
                                                    <input class="form-check-input" type="checkbox" value=""
                                                        id="Not_for_selling?">
                                                    <label class="form-check-label" for="Not_for_selling?">
                                                        Not for selling
                                                    </label>
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
                                                    <select class="form-control form-select select"
                                                        aria-label="Example text with button addon"
                                                        aria-describedby="button-addon1">
                                                        <option selected disabled>Product Type</option>
                                                        <option>Box</option>
                                                        <option>Bundle</option>
                                                        <option>Case</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Pax<span class="login-danger"></span></label>
                                                        <input class="form-control" type="text" placeholder="0">
                                                    </div>
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
                                                            <th scope="col">Retail Price</th>
                                                            <th scope="col">Whole Sale Price</th>
                                                            <th scope="col">Special Price</th>
                                                            <th scope="col">Original Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control" placeholder="Rs .00">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control" placeholder="Rs .00">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="form-group">
                                                                    <input type="text" class="form-control" placeholder="Rs .00">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="row">
                                                                    <div class="col-md-12">
                                                                        <input type="text" class="form-control" placeholder="Rs .00">
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
        @include('category.main_category_modal')
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

    @include('unit.unit_ajax')
    @include('brand.brand_ajax')
@endsection
