@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Stock Transfers</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Stock Tranfer</li>
                                <li class="breadcrumb-item active">Stock Transfer</li>
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

                                    @can('add stock-transfer')
                                        <a href={{ route('add-stock-transfer') }}><button type="button"
                                                class="btn btn-outline-info">
                                                <i class="fas fa-plus px-2"> </i>Add
                                            </button></a>
                                    @endcan

                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="stockTransfer">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Location (From)</th>
                                        <th>Location (To)</th>
                                        <th>Status</th>
                                        <th>Shipping Charges</th>
                                        <th>Total Amount</th>
                                        <th>Additional Notes</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Stock Transfer Details Modal -->
    <div class="modal fade" id="stockTransferDetailsModal" tabindex="-1" aria-labelledby="stockTransferDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="stockTransferDetailsModalLabel">
                        Stock transfer details (<span class="text-dark" id="std_reference_no"></span>)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <div class="row mb-2">
                        <div class="col-6">
                            <div class="mb-1"><strong>Location (From):</strong> <span id="std_location_from"></span></div>
                            <div class="mb-1"><small id="std_location_from_address" class="text-muted"></small></div>
                        </div>
                        <div class="col-6">
                            <div class="mb-1"><strong>Location (To):</strong> <span id="std_location_to"></span></div>
                            <div class="mb-1"><small id="std_location_to_address" class="text-muted"></small></div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <div><strong>Date:</strong> <span id="std_date"></span></div>
                        </div>
                        <div class="col-4">
                            <div><strong>Status:</strong> <span id="std_status"></span></div>
                        </div>
                        <div class="col-4">
                            <div><strong>Reference No:</strong> <span id="std_reference_no_2"></span></div>
                        </div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle mb-0" id="std_products_table">
                            <thead class="table-success">
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>Product</th>
                                    <th class="text-center" style="width:120px;">Quantity</th>
                                    <th class="text-end" style="width:150px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Product rows will be inserted dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <div class="row justify-content-end">
                        <div class="col-md-6">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td class="fw-bold">Net Total Amount:</td>
                                    <td class="text-end" id="std_total_amount"></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Additional Shipping charges:</td>
                                    <td class="text-end" id="std_shipping_charges"></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Transfer Total:</td>
                                    <td class="text-end" id="std_purchase_total"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div>
                        <strong>Activities:</strong>
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width:160px;">Date</th>
                                    <th style="width:120px;">Action</th>
                                    <th style="width:160px;">By</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody id="std_activities">
                                <!-- Activity rows will be inserted dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    @include('stock_transfer.stock_transfer_ajax')
@endsection
