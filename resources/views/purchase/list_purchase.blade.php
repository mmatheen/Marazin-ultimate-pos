@extends('layout.layout')
@section('content')
<div class="content container-fluid">
    <style>
        .login-fields1 { display: none; }
        .login-fields2 { display: none; }
        .login-fields3 { display: none; }
        .hidden { display: none; }
        .hiddenway_two_action { display: none; }
        .modal-xl { max-width: 90%; }
        .modal-body { height: 75vh; overflow-y: auto; }
        @media print {
            .modal-dialog { max-width: 100%; margin: 0; padding: 0; }
            .modal-content { border: none; }
            .modal-body { height: auto; overflow: visible; }
            body * { visibility: hidden; }
            .modal-content, .modal-content * { visibility: visible; }
        }
    </style>
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
            <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
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

    {{-- Table Row --}}
    <div class="row">
        <div class="col-sm-12">
            <div class="card card-table">
                <div class="card-body">
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col-auto text-end float-end ms-auto download-grp">
                                <!-- Button trigger modal -->
                                <a href="{{ route('add-purchase') }}"><button type="button" class="btn btn-outline-info">
                                    <i class="fas fa-plus px-2"> </i>Add
                                </button></a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="datatable table table-stripped" style="width:100%" id="purchase-list">
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
                                <!-- Rows will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="viewPurchaseProductModal" tabindex="-1" aria-labelledby="viewPurchaseProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modalTitle"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Supplier:</h5>
                                <p id="supplierDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Location:</h5>
                                <p id="locationDetails"></p>
                            </div>
                            <div class="col-md-4">
                                <h5>Purchase Details:</h5>
                                <p id="purchaseDetails"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mt-5">
                                <h5>Products:</h5>
                                <table class="table table-bordered" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Product rows will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <h5>Payment Info:</h5>
                                <table class="table table-bordered" id="paymentInfoTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference No</th>
                                            <th>Amount</th>
                                            <th>Payment Mode</th>
                                            <th>Payment Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Payment info will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6 mt-3">
                                <h5>Amount Details:</h5>
                                <table class="table" id="amountDetailsTable">
                                    <tbody>
                                        <!-- Amount details will be inserted here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5>Activities:</h5>
                                <table class="table table-bordered" id="activitiesTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>By</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4">No records found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-secondary" onclick="printModal()">Print</button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('purchase.purchase_ajax')
@endsection
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

</script>
