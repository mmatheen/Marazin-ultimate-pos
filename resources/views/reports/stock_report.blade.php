@extends('layout.layout')
@section('title', 'Daily Sales Report')
@section('content')

    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Daily Sales Report</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                                <li class="breadcrumb-item active">Daily Sales Report</li>
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
                        {{-- <button class="btn btn-secondary" type="button" onclick="printReport()">
                            <i class="fas fa-print"></i> &nbsp; Print
                        </button> --}}
                    </div>
                </div>
            </div>
            <div class="collapse" id="collapseExample">
                <div class="card card-body mb-4">
                    <div class="student-group-form">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Customer:</label>
                                    <select class="form-control select" id="customerFilter">
                                        <option value="">Select Customer</option>
                                        <!-- Populate with customer options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Payment Method:</label>
                                    <select class="form-control select" id="paymentMethodFilter">
                                        <option value="">Select Payment Method</option>
                                        <!-- Populate with payment method options -->
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group local-forms">
                                    <label>Date Range:</label>
                                    <div id="reportrange"
                                        style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                        <i class="fa fa-calendar"></i>&nbsp;
                                        <span></span> <i class="fa fa-caret-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            {{-- Table Section --}}
        <div class="card">
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="stockReportTable">
                <thead>
                    <tr>
                    <th>Action</th>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Unit Selling Price</th>
                    <th>Current Stock</th>
                    <th>Current Stock Value (By Purchase Price)</th>
                    <th>Current Stock Value (By Sale Price)</th>
                    <th>Potential Profit</th>
                    <th>Total Unit Sold</th>
                    <th>Total Unit Transferred</th>
                    <th>Total Unit Adjusted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockHistory as $stock)
                    <tr>
                        <td>{{ $stock['Action'] }}</td>
                        <td>{{ $stock['SKU'] }}</td>
                        <td>{{ $stock['Product'] }}</td>
                        <td>{{ $stock['Category'] }}</td>
                        <td>{{ $stock['Location'] }}</td>
                        <td>{{ number_format($stock['Unit Selling Price'], 2) }}</td>
                        <td>{{ $stock['Current stock'] }}</td>
                        <td>{{ number_format($stock['Current Stock Value (By purchase price)'], 2) }}</td>
                        <td>{{ number_format($stock['Current Stock Value (By sale price)'], 2) }}</td>
                        <td>{{ number_format($stock['Potential profit'], 2) }}</td>
                        <td>{{ $stock['Total unit sold'] }}</td>
                        <td>{{ $stock['Total Unit Transferred'] }}</td>
                        <td>{{ $stock['Total Unit Adjusted'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="text-center">No stock history available.</td>
                    </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
            </div>
        </div>

    <script>
        $(document).ready(function() {
        $('#stockReportTable').DataTable({
            "processing": true,
            "serverSide": false,
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
        });
    </script>

    </div>
@endsection