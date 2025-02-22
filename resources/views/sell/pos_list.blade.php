@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Sell</a></li>
                                <li class="breadcrumb-item active">All Sales</li>
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
            </div>

            <div>
                <div class="collapse" id="collapseExample">
                    <div class="card card-body mb-4">
                        <div class="student-group-form">
                            <div class="row">
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Bussiness Location <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Awesome Shop</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>walk in Customer</option>
                                            <option>Harry</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status<span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disableds>All</option>
                                            <option>Paid</option>
                                            <option>Due</option>
                                            <option>Partial</option>
                                            <option>Overdue</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range<span class="login-danger"></span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY">
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>User<span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Mr Admin</option>
                                            <option>Mr Demo Admin</option>
                                            <option>Mr Woo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Order</option>
                                            <option>Packed</option>
                                            <option>Shiped</option>
                                            <option>Delivered</option>
                                            <option>Cancelled</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Advance</option>
                                            <option>Cash</option>
                                            <option>Card</option>
                                            <option>Cheque</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Sources <span class="login-danger">*</span></label>
                                        <select class="form-control select">
                                            <option disabled>All</option>
                                            <option>Woocommerce</option>
                                        </select>
                                    </div>
                                </div>


                                    {{-- <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="subscriptions" checked>

                                            <label class="form-check-label" for="subscriptions">
                                                Subscriptions
                                            </label>
                                        </div>
                                    </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- table row --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card card-table">
                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-content">
                            <div class="tab-pane show active" id="solid-justified-tab1">
                                <div class="card-body">
                                    <div class="page-header">
                                        <div class="row align-items-center">
                                            <div class="col-auto text-end float-end ms-auto download-grp">
                                                <!-- Button trigger modal -->
                                                <a href="#"><button type="button"
                                                        class="btn btn-outline-info">
                                                        <i class="fas fa-plus px-2"> </i>Add
                                                    </button></a>

                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="datatable table table-stripped" style="width:100%" id="posTable">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" name="" value="" id="allchecked" onclick="toggleLoginFields(id,'.checked')" /></th>
                                                    <th>Action</th>
                                                    <th>Date</th>
                                                    <th>Invoice No</th>
                                                    <th>Customer Name</th>
                                                    <th>Contact Number</th>
                                                    <th>Location</th>
                                                    <th>Payment Status</th>
                                                    <th>Payment Method</th>
                                                    <th>Total Amount</th>
                                                    <th>Total Paid</th>
                                                    <th>Sell Due</th>
                                                    <th>Total Items</th>
                                                    <th>Added By</th>
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
                </div>


            </div>
        </div>
    </div>

    <div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
    </div>


    </div>


    {{-- Edit modal row --}}
    </div>

    <script>
        function toggleLoginFields(propertyId, actionClass) {
            var checkBox = document.getElementById(propertyId);
            var loginFields = document.querySelectorAll(actionClass);
            loginFields.forEach(function(field) {
                // console.log(checkBox.checked);
                field.checked = checkBox
                    .checked; // field.checked --> All checkbox fields are currently which state
                //then it attache the value from which selected box was checked
            });
        }
    </script>

@include('sell.pos_ajax')

@endsection
