@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Print Labels</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Product</li>
                                <li class="breadcrumb-item active">Print Labels</li>
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
                                <form action="#" method="">
                                    <div class="container">
                                        <div class="card mt-4">
                                            <div class="card-body">
                                                <div class="col-md-8">
                                                    <h5 class="card-title">Add products to generate Labels</h5>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Enter products name to print labels"
                                                            aria-label="Search">
                                                    </div>
                                                </div>

                                                <div class="col-md-12">

                                                    <div class="table-responsive">
                                                        <table class="table table-bordered">
                                                            <thead>
                                                                <tr>
                                                                    <th>Products</th>
                                                                    <th>No. of labels</th>
                                                                    <th>Packing Date</th>
                                                                    <th>Selling Price Group</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <!-- Dummy Data -->
                                                                <tr>
                                                                    <td>Product 1</td>
                                                                    <td>10</td>
                                                                    <td>2024-07-15</td>
                                                                    <td>Group 1</td>
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
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">Information to show in Labels</h5>
                                    </div>

                                    <div class="col-md-3 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Product Name<span class="login-danger"></span></label>
                                                <div class="input-group mb-3">
                                                    {{-- <span class="input-group-text"><i class="fas fa-search"></i></span> --}}
                                                    <input type="text" class="form-control" placeholder="Product Name"
                                                        aria-label="Search">
                                                    <span class="input-group-text"><input type="checkbox" checked></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Product Variation (recommended)<span
                                                        class="login-danger"></span></label>
                                                <div class="input-group mb-3">
                                                    {{-- <span class="input-group-text"><i class="fas fa-search"></i></span> --}}
                                                    <input type="text" class="form-control"
                                                        placeholder="Product Variation" aria-label="Search">
                                                    <span class="input-group-text"><input type="checkbox" checked></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Product Price<span class="login-danger"></span></label>
                                                <div class="input-group mb-3">
                                                    {{-- <span class="input-group-text"><i class="fas fa-search"></i></span> --}}
                                                    <input type="text" class="form-control" placeholder="Product Price"
                                                        aria-label="Search">
                                                    <span class="input-group-text"><input type="checkbox"
                                                            id="allowLoginCheckbox" onclick="toggleLoginFields()"
                                                            checked></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-2 hidingclass">
                                        <div class="form-group local-forms days">
                                            <label>Show Price<span class="login-danger">*</span></label>
                                            <select class="form-control form-select">
                                                <option selected disabled>Please Select</option>
                                                <option>Inc.tax</option>
                                                <option>Exc.tax</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 login-fields">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Business name<span class="login-danger"></span></label>
                                            <div class="input-group mb-3">
                                                {{-- <span class="input-group-text"><i class="fas fa-search"></i></span> --}}
                                                <input type="text" class="form-control" placeholder="20"
                                                    aria-label="Search">
                                                <span class="input-group-text"><input type="checkbox" checked></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 login-fields">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Print packing date<span class="login-danger"></span></label>
                                            <div class="input-group mb-3">
                                                {{-- <span class="input-group-text"><i class="fas fa-search"></i></span> --}}
                                                <input type="text" class="form-control" placeholder="12"
                                                    aria-label="Search">
                                                <span class="input-group-text"><input type="checkbox" checked></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="col-md-4 mt-3">
                                    <div class="form-group local-forms days">
                                        <label>Show Price<span class="login-danger">*</span></label>
                                        <select class="form-control form-select">
                                            <option selected disabled>Please Select</option>
                                            <option>20 Labels per Sheet, Sheet Size: 8.5" x 11", Label Size: 4" x 1", Labels
                                                per sheet: 20</option>
                                            <option>30 Labels per sheet, Sheet Size: 8.5" x 11", Label Size: 2.625" x 1",
                                                Labels per sheet: 30</option>
                                        </select>
                                    </div>
                                    <button class="btn-primary btn-lg">Preview</button>
                                </div>

                                </form>

                            </div>
                            <!-- Add other elements if needed -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleLoginFields() {
            var checkBox = document.getElementById("allowLoginCheckbox");
            var loginFields = document.querySelectorAll(".hidingclass");

            loginFields.forEach(function(field) {
                field.style.display = checkBox.checked ? "block" : "none";
            });
        }
    </script>
@endsection
