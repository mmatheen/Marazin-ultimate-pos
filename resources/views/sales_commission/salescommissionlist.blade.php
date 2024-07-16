@extends('layout.layout')
@section('content')
<div class="content container-fluid">


    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Sales Commission Agents</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">User Management</a></li>
                            <li class="breadcrumb-item active">Sales Commission Agents</li>
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
                                <button type="button" class="btn btn-outline-info " data-bs-toggle="modal" data-bs-target="#addAndEditModal">
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
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Address</th>
                                    <th>Sales Commission Percentage(%)</th>
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
        <div class="modal fade" id="addAndEditModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add sales commission agent</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="addAndEditForm" method="POST" action="">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Prefix <span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Mr / Mrs / Miss</option>
                                                <option>Mr</option>
                                                <option>Mrs</option>
                                                <option>Ms</option>
                                                <option>Miss</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>First Name <span class="login-danger">*</span></label>
                                            <input class="form-control" type="text" placeholder="First Name">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Last Name <span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Last Name">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Email <span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Email">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Contact Number <span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Contact Number">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group local-forms">
                                                <label>Sales Commission Percentage (%) <span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Sales Commission Percentage (%)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Description <span class="login-danger"></span></label>
                                        <textarea class="form-control" id="edit_description" name="description" type="text" placeholder="Enter Description"></textarea>
                                    </div>
                                </div>
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
@endsection
