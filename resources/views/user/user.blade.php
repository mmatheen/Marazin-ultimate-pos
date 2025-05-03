@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Users</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Users</a></li>
                            <li class="breadcrumb-item active">List Users</li>
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

                                @can('create user')
                                <button type="button" class="btn btn-outline-info " id="addButton">
                                    New <i class="fas fa-plus px-2"> </i>
                                </button>
                                @endcan

                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="user" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name Title</th>
                                    <th>Full Name</th>
                                    <th>User Name</th>
                                    <th>Role</th>
                                    <th>Location</th>
                                    <th>Email</th>
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

    <!-- Modal -->
    <div class="row">
        <form id="addAndUserUpdateForm">
            <div id="addAndEditModal" class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="text-center my-4">
                            <h5 id="modalTitle"></h5>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <input type="hidden" name="edit_id" id="edit_id">

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="input-group local-forms">
                                            <label>Name Title <span class="login-danger">*</span></label>
                                            <select class="form-control form-select" id="edit_name_title" name="name_title">
                                                <option selected disabled>Mr / Mrs / Miss</option>
                                                <option value="Mr">Mr</option>
                                                <option value="Mrs">Mrs</option>
                                                <option value="Ms">Ms</option>
                                                <option value="Miss">Miss</option>
                                            </select>
                                            <span class="text-danger" id="name_title_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Full Name <span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_full_name" name="full_name" type="text" placeholder="Enter Full Name">
                                            <span class="text-danger" id="full_name_error"></span>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>User Name <span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_user_name" name="user_name" type="text" placeholder="Enter Last Name">
                                            <span class="text-danger" id="user_name_name_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-5">
                                        <div class="input-group local-forms">
                                            <label>Role Name<span class="login-danger">*</span></label>

                                            <select id="edit_role_name" name="roles" class="roleDropdown form-control form-select">
                                                <option selected disabled>Select Role</option>
                                            </select>
                                            <span class="text-danger" id="role_name_error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-5">
                                        <div class="input-group local-forms">
                                            <label>Location Name<span class="login-danger">*</span></label>

                                            <select id="edit_location_id" name="location_id[]" class="locationDropdown form-control form-select" multiple>
                                                <!-- Populate options dynamically from the backend -->
                                            </select>
                                            <span class="text-danger" id="location_name_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    $(document).ready(function() {
                                        $('#edit_location_id').select2({
                                            placeholder: 'Select Locations',
                                            allowClear: true,
                                            tags: true,
                                            tokenSeparators: [',', ' '],
                                            width: '100%',
                                        });

                                        $('#addAndEditModal').on('hidden.bs.modal', function() {
                                            $('#edit_location_id').val(null).trigger('change');
                                        });
                                    });

                                </script>


                                <style>
                                                                        /* Add this to your CSS file */
                                .select2-container--default .select2-selection--multiple {
                                    min-width: 100% !important;
                                }

                                </style>


                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Email <span class="login-danger">*</span></label>
                                        <input class="form-control" id="edit_email" name="email" type="email" placeholder="Enter Email">
                                        <span class="text-danger" id="email_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Password <span class="login-danger">*</span></label>
                                        <input class="form-control pass-input1" id="edit_password" name="password" type="password" placeholder="Enter Password">
                                        <span class="profile-views feather-eye-off toggle-password1"></span>
                                        <span class="text-danger" id="password_error"></span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Confirm Password <span class="login-danger">*</span></label>
                                        <input class="form-control pass-input2" id="edit_confirm_password" name="password_confirmation" autocomplete="new-password" type="password" placeholder="Enter Password">
                                        <span class="profile-views feather-eye-off toggle-password2"></span>
                                        <span class="text-danger" id="confirm_password_error"></span>
                                    </div>
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
        </form>
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
                                    <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn" style="width: 100%;">Delete</button>
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

@include('user.user_ajax')
@include('location.location_ajax')
@include('role.role_ajax')
@endsection
