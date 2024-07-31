@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Warranties</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Warranties</a></li>
                            <li class="breadcrumb-item active">List Warranties</li>
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
                                <button type="button" class="btn btn-outline-info " id="addWarrantyButton">
                                  New  <i class="fas fa-plus px-2"> </i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="warranty" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Period</th>
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
        <div id="addAndEditWarrantyModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle"></h5>
                        </div>
                        <form id="addAndUpdateForm">

                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Name <span class="login-danger">*</span></label>
                                    <input class="form-control" id="edit_name" name="name" type="text" placeholder="Enter Name">
                                    <span class="text-danger" id="name_error"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Description <span class="login-danger"></span></label>
                                    <textarea class="form-control" id="edit_description" name="description" placeholder="Enter Description"></textarea>
                                    <span class="text-danger" id="description_error"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group local-forms">
                                            <label>Duration <span class="login-danger">*</span></label>
                                            <input class="form-control" id="edit_duration" name="duration" type="number" placeholder="Enter Duration">
                                            <span class="text-danger" id="duration_error"></span>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group local-forms">
                                            <label>Period<span class="login-danger">*</span></label>
                                            <select class="form-control" id="edit_duration_type" name="duration_type">
                                                <option selected disabled>Please Select </option>
                                                <option>days</option>
                                                <option>months</option>
                                                <option>years</option>
                                            </select>
                                            <span class="text-danger" id="duration_type_error"></span>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <div class="modal-footer">
                                <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                <button type="button" class="btn btn-outline-danger"" data-bs-dismiss="modal">Close</button>
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

@include('warranty.warranty_ajax')
@endsection


