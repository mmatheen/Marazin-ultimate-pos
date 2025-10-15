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
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">User Management</a></li>
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
                                    @can('create sales-commission-agent')
                                        <button type="button" class="btn btn-outline-info " id="addSalesCommissionButton">
                                            New <i class="fas fa-plus px-2"> </i>
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="salesCommission" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Prefix</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Contact Number</th>
                                        <th>Address</th>
                                        <th>Sales Commission Percentage(%)</th>
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
            <div class="modal fade" id="addAndEditSalesCommissionModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="text-center mt-5 mb-4">
                            <h5 id="modalTitle"></h5>
                        </div>
                        <form id="addAndUpdateForm">

                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Prefix <span class="login-danger"></span></label>
                                                <select id="edit_prefix" name="prefix" class="form-control form-select">
                                                    <option selected disabled>Mr / Mrs / Miss</option>
                                                    <option>Mr</option>
                                                    <option>Mrs</option>
                                                    <option>Ms</option>
                                                    <option>Miss</option>
                                                </select>
                                                <span class="text-danger" id="prefix_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>First Name <span class="login-danger">*</span></label>
                                                <input class="form-control" id="edit_first_name" name="first_name"
                                                    type="text" placeholder="First Name">
                                                <span class="text-danger" id="first_name_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Last Name <span class="login-danger"></span></label>
                                                <input class="form-control" id="edit_last_name" name="last_name"
                                                    type="text" placeholder="Last Name">
                                                <span class="text-danger" id="last_name_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Email <span class="login-danger"></span></label>
                                                <input class="form-control" id="edit_email" name="email" type="text"
                                                    placeholder="Email">
                                                <span class="text-danger" id="email_error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Contact Number <span class="login-danger"></span></label>
                                                <input class="form-control" type="text" id="edit_contact_number"
                                                    name="contact_number" placeholder="Contact Number">
                                                <span class="text-danger" id="contact_number_error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col">
                                                <div class="form-group local-forms">
                                                    <label>Sales Commission Percentage (%) <span
                                                            class="login-danger"></span></label>
                                                    <input class="form-control" type="text"
                                                        id="edit_sales_commission_percentage"
                                                        name="sales_commission_percentage"
                                                        placeholder="Sales Commission Percentage (%)">
                                                    <span class="text-danger"
                                                        id="sales_commission_percentage_error"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Description <span class="login-danger"></span></label>
                                            <textarea class="form-control" id="edit_description" name="description" type="text"
                                                placeholder="Enter Description"></textarea>
                                            <span class="text-danger" id="description_error"></span>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" id="modalButton"
                                            class="btn btn-outline-primary">Save</button>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
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
                                    <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn"
                                        style="width: 100%;">Delete</button>
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

    @include('sales_commission.sales_commission_ajax')
@endsection
