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
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Date Range<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="01/01/2024 - 12/31/2024">
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
                                    <a href="{{ route('add-purchase-return') }}"><button type="button" class="btn btn-outline-info">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button></a>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="purchase_return_list">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Parent Purchase</th>
                                        <th>Location</th>
                                        <th>Supplier</th>
                                        <th>Payment Status</th>
                                        <th>Grand Total</th>
                                        <th>Payment Due</th>
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


    </div>
<!-- Modal -->
<div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Add payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Supplier</h5>
                                    <p id="supplierDetails" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Reference No</h5>
                                    <p id="referenceNo" style="margin: 0;"></p>
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Location</h5>
                                    <p id="locationDetails" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-light mb-3 h-100" style="font-size: 0.9rem;">
                                <div class="card-body" style="padding: 0.75rem;">
                                    <h5 class="card-title" style="font-size: 1rem; margin-bottom: 0.25rem;">Total Amount</h5>
                                    <p id="totalAmount" style="margin: 0;"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Additional form elements -->
                    <div class="row">
                        <div class="col-md-12">
                            <label for="advanceBalance" class="form-label">Advance Balance : Rs. 0.00</label>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select class="form-select" id="paymentMethod">
                                    <option selected>Cash</option>
                                    <option value="1">Credit Card</option>
                                    <option value="2">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="paidOn" class="form-label">Paid On</label>
                                <input class="form-control datetimepicker" type="text" id="paidOn"
                                placeholder="DD-MM-YYYY">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="payAmount" class="form-label">Amount</label>
                                <input type="text" class="form-control" id="payAmount">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="paymentAccount" class="form-label">Payment Account</label>
                                <select class="form-select" id="paymentAccount">
                                    <option selected>None</option>
                                    <option value="1">Account 1</option>
                                    <option value="2">Account 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="attachDocument" class="form-label">Attach Document</label>
                                <input type="file" class="form-control" id="attachDocument" accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="paymentNote" class="form-label">Payment Note</label>
                        <textarea class="form-control" id="paymentNote"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="savePayment">Save</button>
            </div>
        </div>
    </div>
</div>




  @include('purchase.purchase_return_ajax')
@endsection
