@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title" id="main-page-title">Add Stock Transfer</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('list-stock-transfer') }}" id="stock-management-link">Stock Transfers</a></li>
                                <li class="breadcrumb-item active" id="breadcrumb-title">Add Stock Transfer</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form class="px-3" id="stockTransferForm"> <!-- Updated form ID -->
            @csrf

            <!-- Section 1: Selected/Input Fields -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-header">
                            <h5 class="card-title" id="form-card-title">Transfer Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date<span class="login-danger">*</span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY"
                                            id="transfer_date" name="transfer_date">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Reference No<span class="login-danger"></span></label>
                                        <input class="form-control" type="text" placeholder="Reference No"
                                            id="reference_no" name="reference_no">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group local-forms">
                                        <label>Status<span class="login-danger">*</span></label>
                                        <select class="form-control form-select select" id="status" name="status">
                                            <option selected disabled>Please Select </option>
                                            <option value="pending">Pending</option>
                                            <option value="approved">Approved</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Location (From)<span class="login-danger">*</span></label>
                                        <select class="form-control form-select select" id="from_location_id"
                                            name="from_location_id">
                                            <option selected disabled>Please Select </option>
                                            <!-- Options will be populated by AJAX -->
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Location (To)<span class="login-danger">*</span></label>
                                        <select class="form-control form-select select" id="to_location_id"
                                            name="to_location_id">
                                            <option selected disabled>Please Select </option>
                                            <!-- Options will be populated by AJAX -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="location-error" class="text-danger" style="display: none;">
                                Please select different locations for "From" and "To".
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Product Search Area -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-header">
                            <h5 class="card-title">Search Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="row d-flex justify-content-center">
                                <div class="col-md-8">
                                    <div class="input-group flex-nowrap">
                                        <input type="text" id="productSearch" class="form-control"
                                            placeholder="Search for product">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Product Table -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Selected Products</h5>
                            <div class="col-md-4">
                                <input type="text" id="filterProducts" class="form-control form-control-sm"
                                       placeholder="🔍 Search in selected products...">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-bordered table-hover" id="selectedProductsTable">
                                    <thead class="table-success" style="position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Batch</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Unit Price</th>
                                            <th scope="col">Subtotal</th>
                                            <th scope="col"><i class="fas fa-trash"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody class="add-table-items">
                                        <!-- Initial empty total row -->
                                        <tr>
                                            <td colspan="4"></td>
                                            <td id="totalRow">Total : 0.00</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Additional Information -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card card-table">
                        <div class="card-header">
                            <h5 class="card-title">Additional Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                {{-- <div class="col-md-6">
                                        <label>Recovered Amount</label>
                                    <div class="form-group local-forms">
                                        <input class="form-control" type="number" placeholder="Enter Amount" id="recovered_amount" name="recovered_amount">
                                    </div>
                                </div> --}}
                                <div class="col-md-6">
                                    <div class="form-group local-forms">
                                        <label>Stock Transfer Reason</label>
                                        <textarea class="form-control" placeholder="Enter Reason" id="recovery_reason" name="note"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row mb-4">
                <div class="col-md-12 d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary btn-lg" id="button-title">Save</button>
                </div>
            </div>
        </form>
    </div>

    @include('stock_transfer.stock_transfer_ajax')

    <script>
        // Check if we're in edit mode and update titles immediately
        $(document).ready(function() {
            const currentUrl = window.location.href;
            console.log('Current URL:', currentUrl);

            if (currentUrl.includes('/edit-stock-transfer/')) {
                const pathParts = currentUrl.split('/');
                const stockTransferId = pathParts[pathParts.length - 1];

                if (stockTransferId && stockTransferId !== 'add-stock-transfer') {
                    // Update page title immediately for edit mode
                    $('#main-page-title').text('Edit Stock Transfer');
                    $('#breadcrumb-title').text('Edit Stock Transfer');
                    document.title = 'Edit Stock Transfer';
                    $('#stock-management-link').text('Stock Transfers');

                    console.log('Initial edit mode UI updates applied');
                }
            }
        });
    </script>
@endsection
