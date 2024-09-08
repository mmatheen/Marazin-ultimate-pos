@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Role</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">User Management</li>
                                <li class="breadcrumb-item active">Add new role</li>
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
                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Role Name<span class="login-danger">*</span></label>
                                                    <input class="form-control" type="text" placeholder="Role Name">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <p>Permissions</p>
                                            <div class="col-md-12">

                                                <div class="row">
                                                    <div class="col-md-2">
                                                        Others
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Select All</p>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>View export to buttons (csv/excel/print/pdf) on tables</p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- Add other elements if needed -->

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        User
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Select All</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>View user</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Add user</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Edit user</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Delete user</p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        Roles
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Select All</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>View role</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Add role</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Edit role</p>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input class="form-check-input" type="checkbox" value=""
                                                            id="defaultCheck1">
                                                        <p>Delete role</p>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Save</button>
        </form>
    </div>

    </div>
@endsection
