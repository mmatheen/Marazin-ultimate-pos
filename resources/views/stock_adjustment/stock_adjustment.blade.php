@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Stock Adjustments</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Stock Adjustment</li>
                                <li class="breadcrumb-item active">Stock Adjustment List</li>
                            </ul>
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
                                    <!-- Button to Add Stock Adjustment -->

                                    @can('add stock-adjustment')
                                        <a href="{{ route('add-stock-adjustment') }}">
                                            <button type="button" class="btn btn-outline-info">
                                                <i class="fas fa-plus px-2"></i>Add
                                            </button>
                                        </a>
                                   @endcan
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="stockAdjustmentTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference No</th>
                                        <th>Location</th>
                                        <th>Adjustment Type</th>
                                        <th>Total Amount</th>
                                        <th>Total Amount Recovered</th>
                                        <th>Reason</th>
                                        <th>Added By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('stock_adjustment.stock_adjustment_ajax');
@endsection
