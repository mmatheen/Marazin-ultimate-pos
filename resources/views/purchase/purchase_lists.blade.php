@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Purchases</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Purchases</li>
                                <li class="breadcrumb-item active">List Purchases </li>
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
                                    <div class="form-group local-forms">
                                        <label>Business Location <span class="login-danger"></span></label>
                                        <select class="form-control select">
                                            <option>All</option>
                                            <option>Awesomeshop</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Supplier <span class="login-danger"></span></label>
                                        <select class="form-control select">
                                            <option>All</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Purchase Status<span class="login-danger"></span></label>
                                        <select class="form-control select">
                                            <option>All</option>
                                            <option>Received</option>
                                            <option>Pending</option>
                                            <option>Ordered</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Payment Status <span class="login-danger"></span></label>
                                        <select class="form-control select">
                                            <option>All</option>
                                            <option>Paid</option>
                                            <option>Due</option>
                                            <option>Partial</option>
                                            <option>Overdue</option>
                                        </select>
                                    </div>
                                </div>
                         
                                <div class="col-lg-3 col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Date Range<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="01/01/2024 - 12/31/2024">
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
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Location</th>
                                        <th>Supplier</th>
                                        <th>Purchase Status</th>
                                        <th>Payment Status</th>
                                        <th>Grand Total</th>
                                        <th>Payment Due</th>
                                        <th>Added By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            {{-- <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button> --}}
                                            <div class="dropdown dropdown-action">
                                                <a href="#" class="action-icon dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false"><button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button></a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;View payments</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Purchase Return</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Update Status</a>
                                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-envelope"></i>&nbsp;&nbsp;Item Received Notification</a>
                                                </div>
                                            </div>
                                        </td>
                                        <td><img src="https://via.placeholder.com/50" alt="Product 1" /></td>
                                        <td>
                                            <button class="btn btn-primary">Edit</button>
                                            <button class="btn btn-danger">Delete</button>
                                        </td>
                                        <td>Product 1</td>
                                        <td>Location 1</td>
                                        <td>$10.00</td>
                                        <td>100</td>
                                        <td>Type 1</td>
                                        <td>Category 1</td>
                                        <td>Brand 1</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add sales commission agent</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="addAndEditForm" method="POST" action="">
                            <div class="modal-body">
                                <div class="row">

                                </div>
                                <div class="row">

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
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
