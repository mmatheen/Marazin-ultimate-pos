@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Location</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Locations</a></li>
                            <li class="breadcrumb-item active">List Locations</li>
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

                                @can('create location')
                                <button type="button" class="btn btn-outline-info " id="addLocationButton">
                                    New <i class="fas fa-plus px-2"> </i>
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="location" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Location ID</th>
                                    <th>Address</th>
                                    <th>Province</th>
                                    <th>District</th>
                                    <th>City</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Telephone No</th>
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
        <div id="addAndEditLocationModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle"></h5>
                        </div>
                        <form id="addAndLocationUpdateForm">
                            <div class="row">
                                <input type="hidden" name="edit_id" id="edit_id">

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Name<span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_name" name="name" type="text" placeholder="Name">
                                            <span class="text-danger" id="name_error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Location ID<span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_location_id" name="location_id" type="text" placeholder="location ID">
                                            <span class="text-danger" id="location_id_error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Address<span class="login-danger">*</span></label>
                                            <textarea class="form-control" id="edit_address" name="address" placeholder="Address"></textarea>
                                            <span class="text-danger" id="address_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Province<span class="login-danger">*</span></label>
                                            <select class="form-control form-select" id="edit_province" name="province">
                                                <option selected disabled>Select Province</option>
                                                <option value="Western">Western</option>
                                                <option value="Central">Central</option>
                                                <option value="Southern">Southern</option>
                                                <option value="North Western">North Western</option>
                                                <option value="North Central">North Central</option>
                                                <option value="Northern">Northern</option>
                                                <option value="Eastern">Eastern</option>
                                                <option value="Uva">Uva</option>
                                                <option value="Sabaragamuwa">Sabaragamuwa</option>
                                            </select>
                                            <span class="text-danger" id="province_error"></span>
                                        </div>
                                    </div>
                                
                                </div>


                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>District<span class="login-danger">*</span></label>
                                            <select class="form-control form-select" id="edit_district" name="district">
                                                <option selected disabled>Select District</option>
                                            </select>
                                            <span class="text-danger" id="district_error"></span>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>City<span class="login-danger"></span></label>
                                            <input class="form-control" id="edit_city" name="city" type="text" placeholder="City">
                                            <span class="text-danger" id="city_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Email<span class="login-danger"></span></label>
                                            <input type="text" class="form-control" id="edit_email" name="email" placeholder="Email">
                                            <span class="text-danger" id="email_error"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Phone<span class="login-danger"></span></label>
                                            <input type="text" class="form-control" id="edit_mobile" name="mobile" placeholder="Phone No">
                                            <span class="text-danger" id="mobile_error"></span>
                                        </div>


                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Telephone<span class="login-danger"></span></label>
                                            <input type="text" class="form-control" id="edit_telephone_no" name="telephone_no" placeholder="Telephone No">
                                            <span class="text-danger" id="telephone_no_error">Email is required</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
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

@include('location.location_ajax')
@endsection



