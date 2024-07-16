@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Units </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Unit</li>
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
                                        <th>Name</th>
                                        <th>Short name</th>
                                        <th>Allow decimal</th>
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
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add Unit</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="addAndEditForm" method="POST" action="">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Name <span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Short name<span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="Short name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group local-forms days">
                                            <label>Allow decimal<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                                <option>Yes</option>
                                                <option>No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="mb-3 ms-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="allowLoginCheckbox" checked 
                                                onclick="toggleLoginFields()">
                                                <label class="form-check-label" for="allowLoginCheckbox">
                                                    Add as multiple of other unit
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2 mt-3 hidingclass">
                                        <b>1 Unit = </b>
                                    </div>
                                    <div class="col-md-5 hidingclass">
                                        <div class="form-group local-forms">
                                            <label>Time base unit<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Time base unit">
                                        </div>
                                    </div>
                                    <div class="col-md-5 hidingclass">
                                        <div class="form-group local-forms days">
                                            <label>Select base unit<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Select base unit</option>
                                                <option>Pieces (Pcs)</option>
                                                <option>Packets (Pck)</option>
                                                <option>Kilo Gram (Kg)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                               
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
    
                        </form>
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
