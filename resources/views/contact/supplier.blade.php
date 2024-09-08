@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Suppliers </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Contacts</li>
                                <li class="breadcrumb-item active">List Suppliers </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> &nbsp; Filters
                        </button>
                    </div>
                </div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="PurchaseDue">
                                        <label class="form-check-label" for="PurchaseDue">
                                            Purchase Due
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="PurchaseReturn">
                                        <label class="form-check-label" for="PurchaseReturn">
                                            Purchase Return
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="AdvanceBalance">
                                        <label class="form-check-label" for="AdvanceBalance">
                                            Advance Balance
                                        </label>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Assigned to <span class="login-danger">*</span></label>
                                            <select class="form-control select">
                                                <option disabled>None</option>
                                                <option>Mr Admin</option>
                                                <option>Mr Super Admin</option>
                                                <option>Others</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group local-forms">
                                            <label>Gender <span class="login-danger">*</span></label>
                                            <select class="form-control select">
                                                <option disabled>None</option>
                                                <option>Active</option>
                                                <option>In Active</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <table class="datatable table table-stripped" style="width:100%" id="example1">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Contact ID</th>
                                        <th>Business Name</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Tax Number</th>
                                        <th>Pay Term</th>
                                        <th>Opening Balance</th>
                                        <th>Advance Balance</th>
                                        <th>Added On</th>
                                        <th>Address</th>
                                        <th>Mobile</th>
                                        <th>Total Purchase Due</th>
                                        <th>Total Purchase Return Due</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <th colspan="2">Total</th>
                                        <th>$2500,000</th>
                                        <th>$0</th>
                                    </tr>
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
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add a new contact</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="addAndEditForm" method="POST" action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Prefix<span class="login-danger">*</span></label>
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
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>First Name<span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="First Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Last Name<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Last Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-mobile-alt"></i></span>
                                                <input type="text" class="form-control" placeholder="Mobile"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-envelope"></i></span>
                                                <input type="text" class="form-control" placeholder="Email"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-address-book"></i></span>
                                                <input type="text" class="form-control" placeholder="Contact ID"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3 mt-3">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-user"></i></span>
                                                <select class="form-control form-select"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                                    <option selected disabled>Contact type</option>
                                                    <option>Suppliers</option>
                                                    <option>Customers</option>
                                                    <option>Both (Supplier & Customer)</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3 mt-3">
                                            <div class="form-group local-forms calendar-icon">
                                                <label>Date <span class="login-danger">*</span></label>
                                                <input class="form-control datetimepicker" type="text"
                                                    placeholder="DD-MM-YYYY">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3 mt-3">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-user"></i></span>
                                                <select class="form-control  form-select"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="basic-addon1">
                                                    <option selected disabled>Assigned to</option>
                                                    <option>Mr SuperUser</option>
                                                    <option>Mr Ahshan</option>
                                                    <option>Mr Afshan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="basic-addon1"><i
                                                        class="fas fa-address-book"></i></span>
                                                <input type="text" class="form-control" placeholder="Opening Balance"
                                                    aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Edit modal row --}}
    </div>
@endsection
