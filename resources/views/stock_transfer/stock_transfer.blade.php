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
                                    <a href={{ route('add-stock-transfer') }}><button type="button" class="btn btn-outline-info">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button></a>

                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="stockTransfer">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Location (From)</th>
                                        <th>Location (To)</th>
                                        {{-- <th>Status</th> --}}
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

    @include('stock_transfer.stock_transfer_ajax')

@endsection
