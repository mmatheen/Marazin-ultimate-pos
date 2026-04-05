@extends('layout.layout')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <style>
        /* Force Select2 dropdowns to full width */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--single {
            height: 44px !important;
            padding: 8px 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">All Sales</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('list-sale') }}">Sell</a></li>
                                <li class="breadcrumb-item active">All Sales</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <div class="card mb-3">
                    <div class="card-body py-3">
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                            <i class="fas fa-filter"></i> Filters
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
                                        <label>Business Location</label>
                                        <select class="form-control selectBox" id="locationFilter" name="location">
                                            <option value="">All</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Customer</label>
                                        <select class="form-control selectBox" id="customerFilter" name="customer">
                                            <option value="">All</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ trim($customer->first_name . ' ' . $customer->last_name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status</label>
                                        <select class="form-control selectBox" id="paymentStatusFilter" name="payment_status">
                                            <option value="">All</option>
                                            <option value="paid">Paid</option>
                                            <option value="due">Due</option>
                                            <option value="partial">Partial</option>
                                            <option value="overdue">Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Range</label>
                                        <input class="form-control" type="text" placeholder="Select date range" id="dateRangeFilter" name="date_range">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>User</label>
                                        <select class="form-control selectBox" id="userFilter" name="user">
                                            <option value="">All</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Shipping Status</label>
                                        <select class="form-control selectBox" id="shippingStatusFilter" name="shipping_status">
                                            <option value="">All</option>
                                            <option value="order">Order</option>
                                            <option value="packed">Packed</option>
                                            <option value="shipped">Shipped</option>
                                            <option value="delivered">Delivered</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Payment Method</label>
                                        <select class="form-control selectBox" id="paymentMethodFilter" name="payment_method">
                                            <option value="">All</option>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="cheque">Cheque</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                        </select>
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
                                                    <a href="/pos-create"><button type="button"
                                                            class="btn btn-outline-info">
                                                            <i class="fas fa-plus px-2"> </i>Add
                                                        </button></a>
                                                </div>
                                            </div>
                                        </div>



                                        <div class="table-responsive">

                                            <table id="salesTable" class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Action</th>
                                                        <th>Date</th>
                                                        <th>Invoice No.</th>
                                                        <th>Customer Name</th>
                                                        <th>Contact Number</th>
                                                        <th>Location</th>
                                                        <th>Payment Status</th>
                                                        <th>Payment Method</th>
                                                        <th>Total Amount</th>
                                                        <th>Total Paid</th>
                                                        <th>Sell Due</th>
                                                        <th>Shipping Status</th>
                                                        <th>Total Items</th>
                                                        <th>Added By</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Rows will be dynamically added here -->
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
    </div>


        @include('sell.partials.sales_action_modals')

        @include('sell.sales_ajax')
@endsection
