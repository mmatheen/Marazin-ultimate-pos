@extends('layout.layout')

@section('content')
<div class="content container-fluid">
    <div class="row">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm-12">
                    <div class="page-sub-header">
                        <h3 class="page-title">Add Stock Adjustment</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="students.html">Stock Adjustment</a></li>
                            <li class="breadcrumb-item active">Add Stock Adjustment</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="stockAdjustmentForm">

                <!-- Business Location, Reference No, Date -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="businessLocation" class="form-label">Business Location:*</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="" selected disabled>Select Location</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="referenceNo" class="form-label">Reference No:</label>
                        <input type="text" class="form-control" id="referenceNo" name="referenceNo" placeholder="Enter reference number">
                    </div>
                    <div class="col-md-4">
                        <label for="date" class="form-label">Date:*</label>
                        <input type="date" class="form-control" id="adjustment_date" name="date" value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <!-- Adjustment Type -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="adjustmentType" class="form-label">Adjustment Type:*</label>
                        <select id="adjustmentType" class="form-select" name="adjustment_type">
                            <option value="" selected disabled>Please Select</option>
                            <option value="increase">Increase Stock</option>
                            <option value="decrease">Decrease Stock</option>
                        </select>
                    </div>
                </div>

                <!-- Product Search -->
                <div class="row mb-3">
                    <div class="col-md-8 offset-md-2">
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon1"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="productSearchInput" placeholder="Enter Product Name / SKU / Scan bar code">
                        </div>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <!-- Rows will be dynamically added here -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Amount:</td>
                                    <td colspan="2" class="fw-bold" id="totalAmount">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Total Amount Recovered and Reason -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="totalAmountRecovered" class="form-label">Total Amount Recovered:</label>
                        <input type="number" class="form-control" id="totalAmountRecovered" name="total_amount_recovered" value="0">
                    </div>
                    <div class="col-md-6">
                        <label for="reason" class="form-label">Reason:</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason for adjustment"></textarea>
                    </div>
                </div>

               <!-- Submit Button -->
            <div class="row mb-4">
                <div class="col-md-12 d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary btn-lg">Save</button>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>

@include('stock_adjustment.stock_adjustment_ajax')
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
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

            // Add event listener for remove buttons
            $('.remove-product').on('click', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });
        }

        function updateTotalAmount() {
            let totalAmount = 0;
            $('#productTableBody tr').each(function() {
                const subtotal = parseFloat($(this).find('td:eq(4)').text());
                totalAmount += subtotal;
            });
            $('#totalAmount').text(totalAmount.toFixed(2));
        }
    });
</script>
@endsection
