@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Categories</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Categories</li>
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
                                    <button type="button" class="btn btn-outline-info " data-bs-toggle="modal"
                                        data-bs-target="#addModal">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Catergory</th>
                                        <th>Catergory Code</th>
                                        <th>Allow decimal</th>
                                        <th>Description</th>
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

        {{-- Add modal row --}}
        <div class="row">
            <div id="addModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg  modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="text-center mt-2 mb-4">
                                <h5>Add Catergory</h5>
                            </div>
                            <form class="px-3" action="#">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Category name<span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="Category name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Category Code<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Category Code">
                                                <span>Category code is same as HSN code</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Description <span class="login-danger"></span></label>
                                                <textarea class="form-control" type="text" placeholder="Description"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group local-forms days">
                                            <input type="checkbox" id="allowLoginCheckbox" checked
                                                onclick="toggleLoginFields()">
                                            <p>Add as sub taxonomy</p>
                                        </div>
                                    </div>
                                
                                    <div class="row">
                                        <div class="col-md-12 hidingclass">
                                            <div class="form-group local-forms days">
                                                <label>Select parent category<span class="login-danger"></span></label>
                                                <select class="form-control form-select">
                                                    <option selected disabled>Select parent category</option>
                                                    <option>Biscuits</option>
                                                    <option>Chocolate</option>
                                                    <option>Electronics</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary">Save</button>
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Edit modal row --}}

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
