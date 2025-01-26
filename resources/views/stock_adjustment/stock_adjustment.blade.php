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
                                    <a href="{{ route('add-stock-adjustment') }}">
                                        <button type="button" class="btn btn-outline-info">
                                            <i class="fas fa-plus px-2"></i>Add
                                        </button>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="stockAdjustmentTable">
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
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   @include('stock_adjustment.stock_adjustment_ajax')
<script>
    const stockAdjustMentId = window.location.pathname.split('/').pop();

if (stockAdjustMentId) {
    // Fetch stock adjustment data
    $.ajax({
        url: `/api/edit-stock-adjustment/${stockAdjustMentId}`,
        method: 'GET',
        success: function(response) {
            if (response.stockAdjustment) {
                populateForm(response.stockAdjustment);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching stock adjustment:', error);
        }
    });
}

function populateForm(data) {
    // Populate basic fields
    $('#location_id').val(data.location_id);
    $('#referenceNo').val(data.reference_no);
    $('#adjustment_date').val(data.date.split(' ')[0]); // Format date to YYYY-MM-DD
    $('#adjustmentType').val(data.adjustment_type);
    $('#totalAmountRecovered').val(data.total_amount_recovered);
    $('#reason').val(data.reason);

    // Populate products table
    const productTableBody = $('#productTableBody');
    productTableBody.empty(); // Clear existing rows

    let totalAmount = 0;
    data.adjustment_products.forEach(product => {
        const subtotal = parseFloat(product.subtotal).toFixed(2);
        totalAmount += parseFloat(subtotal);

        const row = `
            <tr>
                <td>${product.product.product_name}</td>
                <td>${product.batch_id}</td>
                <td>${product.quantity}</td>
                <td>${product.unit_price}</td>
                <td>${subtotal}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-product">Remove</button>
                </td>
            </tr>
        `;
        productTableBody.append(row);
    });

    // Update total amount
    $('#totalAmount').text(totalAmount.toFixed(2));
}
}
</script>


@endsection
