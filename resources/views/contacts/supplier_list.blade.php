@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <style>

        </style>
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

            <p>

                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample"
                    aria-expanded="false" aria-controls="collapseExample">
                    Filters
                </button>
            </p>
            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="PurchaseDue">
                                        <label class="form-check-label" for="PurchaseDue">
                                            Purchase Due
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="PurchaseReturn">
                                        <label class="form-check-label" for="PurchaseReturn">
                                            Purchase Return
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="AdvanceBalance">
                                        <label class="form-check-label" for="AdvanceBalance">
                                            Advance Balance
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="OpeningBalance">
                                        <label class="form-check-label" for="OpeningBalance">
                                            Opening Balance
                                        </label>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-lg-3 col-md-6">
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
                                    <div class="col-lg-3 col-md-6">
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
                                        <th>Custom Field 1</th>
                                        <th>Custom Field 2</th>
                                        <th>Custom Field 3</th>
                                        <th>Custom Field 4</th>
                                        <th>Custom Field 5</th>
                                        <th>Custom Field 6</th>
                                        <th>Custom Field 7</th>
                                        <th>Custom Field 8</th>
                                        <th>Custom Field 9</th>
                                        <th>Custom Field 10</th>
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

                                    <div class="col-md-4 mt-xs-2px">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                                id="inlineRadio1" value="option1">
                                            <label class="form-check-label" for="inlineRadio1">Individual</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                                id="inlineRadio2" value="option2">
                                            <label class="form-check-label" for="inlineRadio2">Business</label>
                                        </div>
                                        <div class="col"></div>
                                        <div class="col"></div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-address-book"></i></span>
                                            <input type="text" class="form-control" placeholder="Contact ID"
                                                aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                        <span>Leave empty to autogenerate</span>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-3">
                                        {{-- <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Prefix<span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="Email">
                                            </div>
                                        </div> --}}
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
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>First Name<span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="First Name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Middle name<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Middle name">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Last Name<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Last Name">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-mobile-alt"></i></span>
                                            <input type="text" class="form-control" placeholder="Mobile"
                                                aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control"
                                                placeholder="Alternate contact number"
                                                aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control" placeholder="Landline"
                                                aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-envelope"></i></span>
                                            <input type="text" class="form-control" placeholder="Email"
                                                aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <div class="form-group local-forms calendar-icon">
                                            <label>Date Of Birth <span class="login-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text"
                                                placeholder="DD-MM-YYYY">
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="input-group local-forms">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-user"></i></span>
                                            <select class="form-control form-select"
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

                                <div class="row">
                                    <p>
                                        <button class="btn btn-primary mt-xs-2px" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#moreinformation1"
                                            aria-expanded="false" aria-controls="collapseExample">
                                            More Infomation <i class="fas fa-sort-down "></i>
                                        </button>
                                    </p>
                                    <div>
                                        <div class="collapse" id="moreinformation1">
                                            <div class="student-group-form">
                                                <hr>
                                                <div class="row mt-4">
                                                    <div class="col-md-4">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Tax number"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Opening Balance"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="d-flex justify-content-between">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <label>Pay term<span
                                                                            class="login-danger"></span></label>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="Pay term">
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <select class="form-control form-select select">
                                                                        <option selected disabled>Please Select</option>
                                                                        <option>Per Month</option>
                                                                        <option>Per Week</option>
                                                                        <option>Per Day</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr />
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Address line 1<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Address line 1">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Address line 2<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Address line 2">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control" placeholder="City"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control"
                                                                placeholder="State"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Country"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text" id="basic-addon1"><i
                                                                    class="fas fa-address-book"></i></span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Zip Code"
                                                                aria-label="Example text with button addon"
                                                                aria-describedby="button-addon1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 1<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 1">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 2<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 2">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 3<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 3">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 4<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 4">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 5<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 5">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 6<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 6">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 7<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 7">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 8<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 8">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 9<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 9">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Custom Field 10<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Custom Field 10">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row justify-content-center">
                                                    <div class="col-md-8">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Shipping Address<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Shipping Address">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <p>
                                                        <button class="btn btn-primary mt-xs-2px" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#Persons1"
                                                            aria-expanded="false" aria-controls="collapseExample">
                                                            Add Contact Persons1 <i class="fas fa-sort-down "></i>
                                                        </button>
                                                    </p>

                                                    <div class="collapse" id="Persons1">
                                                        <div class="student-group-form">
                                                            <hr>
                                                            <div class="row mt-4">

                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Prefix<span
                                                                                    class="login-danger">*</span></label>
                                                                            <select
                                                                                class="form-control form-select select">
                                                                                <option selected disabled>Mr / Mrs / Miss
                                                                                </option>
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
                                                                            <label>First Name<span
                                                                                    class="login-danger">*</span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="First Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-5">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Last Name<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Last Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Email<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Email">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Mobile Number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Mobile Number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Alternate contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Alternate contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Family contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Family contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Department<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Department">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Designation<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Designation">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Sales Commission Percentage (%)<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Sales Commission Percentage (%)">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group">
                                                                            <input class="form-check-input" type="checkbox" value=""  onclick="toggleLoginFields('allowLoginCheckbox1','.login-fields1')" id="allowLoginCheckbox1">
                                                                            <label class="form-check-label" for="allowLoginCheckbox1">
                                                                                Allow login
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div> 
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 login-fields1">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Username<span class="login-danger"></span></label>
                                                                            <input class="form-control" type="text" placeholder="Username">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields1">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields1">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Confirm Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Confirm Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="isactive2">
                                                                        <label class="form-check-label" for="isactive2">
                                                                            Is active ? 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <p>
                                                        <button class="btn btn-primary mt-xs-2px" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#Persons2"
                                                            aria-expanded="false" aria-controls="collapseExample">
                                                            Add Contact Persons2 <i class="fas fa-sort-down "></i>
                                                        </button>
                                                    </p>

                                                    <div class="collapse" id="Persons2">
                                                        <div class="student-group-form">
                                                            <hr>
                                                            <div class="row mt-4">

                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Prefix<span
                                                                                    class="login-danger">*</span></label>
                                                                            <select
                                                                                class="form-control form-select select">
                                                                                <option selected disabled>Mr / Mrs / Miss
                                                                                </option>
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
                                                                            <label>First Name<span
                                                                                    class="login-danger">*</span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="First Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-5">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Last Name<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Last Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Email<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Email">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Mobile Number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Mobile Number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Alternate contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Alternate contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Family contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Family contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Department<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Department">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Designation<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Designation">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Sales Commission Percentage (%)<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Sales Commission Percentage (%)">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group">
                                                                            <input class="form-check-input" type="checkbox" value=""  onclick="toggleLoginFields('allowLoginCheckbox2','.login-fields2')" id="allowLoginCheckbox2">
                                                                            <label class="form-check-label" for="allowLoginCheckbox2">
                                                                                Allow login
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div> 
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 login-fields2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Username<span class="login-danger"></span></label>
                                                                            <input class="form-control" type="text" placeholder="Username">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Confirm Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Confirm Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="isactive1">
                                                                        <label class="form-check-label" for="isactive1">
                                                                            Is active ? 
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>

                                                <div class="row">
                                                    <p>
                                                        <button class="btn btn-primary mt-xs-2px" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#Persons3"
                                                            aria-expanded="false" aria-controls="collapseExample">
                                                            Add Contact Persons3 <i class="fas fa-sort-down "></i>
                                                        </button>
                                                    </p>

                                                    <div class="collapse" id="Persons3">
                                                        <div class="student-group-form">
                                                            <hr>
                                                            <div class="row mt-4">

                                                                <div class="col-md-2">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Prefix<span
                                                                                    class="login-danger">*</span></label>
                                                                            <select
                                                                                class="form-control form-select select">
                                                                                <option selected disabled>Mr / Mrs / Miss
                                                                                </option>
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
                                                                            <label>First Name<span
                                                                                    class="login-danger">*</span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="First Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-5">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Last Name<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Last Name">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Email<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Email">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Mobile Number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Mobile Number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Alternate contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Alternate contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Family contact number<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Family contact number">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Department<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Department">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Designation<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Designation">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Sales Commission Percentage (%)<span
                                                                                    class="login-danger"></span></label>
                                                                            <input class="form-control" type="text"
                                                                                placeholder="Sales Commission Percentage (%)">
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="mb-3">
                                                                        <div class="form-group">
                                                                            <input class="form-check-input" type="checkbox" value="" onclick="toggleLoginFields('allowLoginCheckbox3','.login-fields3')"  id="allowLoginCheckbox3">
                                                                            <label class="form-check-label" for="allowLoginCheckbox3">
                                                                                Allow login
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div> 
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 login-fields3">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Username<span class="login-danger"></span></label>
                                                                            <input class="form-control" type="text" placeholder="Username">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields3">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                            
                                                                <div class="col-md-6 login-fields3">
                                                                    <div class="mb-3">
                                                                        <div class="form-group local-forms">
                                                                            <label>Confirm Password<span class="login-danger">*</span></label>
                                                                            <input class="form-control" type="password" placeholder="Confirm Password">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-lg-3 col-md-6">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" id="isactive2">
                                                                        <label class="form-check-label" for="isactive2">
                                                                            Is active ? 
                                                                        </label>
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

    <script>
        function toggleLoginFields(x,y) {
            var checkBox = document.getElementById(x);
            var loginFields = document.querySelectorAll(y);

            loginFields.forEach(function(field) {
                field.style.display = checkBox.checked ? "block" : "none";




            });
        }
    </script>
@endsection
