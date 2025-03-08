@extends('layout.layout')

@section('content')
<div class="container-fluid mt-4">
    <h3>Product Stock History</h3>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title" id="product-name">{{ $product->product_name }}</h5>
            <div class="form-group row">
                <label for="product" class="col-sm-2 col-form-label">Product:</label>
                <div class="col-sm-6">
                    <select class="form-control" id="product">
                        <option value="{{ $product->id }}">{{ $product->product_name }} - {{ $product->sku }}</option>
                        <!-- Add other products here if needed -->
                    </select>
                </div>
                <label for="businessLocation" class="col-sm-2 col-form-label">Business Location:</label>
                <div class="col-sm-2">
                    <select class="form-control" id="businessLocation">
                        <option>Awesome Shop</option>
                        <!-- Add other locations here if needed -->
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title" id="product-sku">{{ $product->product_name }} ({{ $product->sku }})</h5>
            <div class="row">
                <div class="col-md-4">
                    <h6>Quantities In</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Total Purchase</td><td id="total-purchase">{{ $stock_type_sums['purchase'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Opening Stock</td><td id="opening-stock">{{ $stock_type_sums['opening_stock'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Total Sell Return</td><td id="total-sell-return">{{ $stock_type_sums['sales_return_with_bill'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Stock Transfers (In)</td><td id="stock-transfers-in">{{ $stock_type_sums['transfer_in'] ?? '0.00' }} packets</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6>Quantities Out</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Total Sold</td><td id="total-sold">{{ $stock_type_sums['sale'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Total Stock Adjustment</td><td id="total-stock-adjustment">{{ $stock_type_sums['adjustment'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Total Purchase Return</td><td id="total-purchase-return">{{ $stock_type_sums['purchase_return'] ?? '0.00' }} packets</td></tr>
                            <tr><td>Stock Transfers (Out)</td><td id="stock-transfers-out">{{ $stock_type_sums['transfer_out'] ?? '0.00' }} packets</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6>Totals</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Current stock</td><td id="current-stock">{{ $current_stock }} packets</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <hr>

            <table id="stockTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Quantity change</th>
                        <th>New Quantity</th>
                        <th>Date</th>
                        <th>Reference No</th>
                        <th>Customer/Supplier information</th>
                    </tr>
                </thead>
                <tbody id="stock-history-body">
                    <!-- Stock history rows will be appended here by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('product.product_ajax')

<script>
    $(document).ready(function() {
        // Initialize DataTables
        var stockTable = $('#stockTable').DataTable();

        // Fetch the initial stock history for the loaded product
        var productId = {{ $product->id }};
        fetchStockHistory(productId);

        // Event listener for product selection change
        $('#product').change(function() {
            productId = $(this).val();
            fetchStockHistory(productId);
        });

        function fetchStockHistory(productId) {
            $.ajax({
                url: "/products/stock-history/" + productId,
                method: "GET",
                success: function(response) {
                    updateStockHistoryView(response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        function updateStockHistoryView(data) {
            $('#product-name').text(data.product.product_name);
            $('#product-option').text(data.product.product_name + ' - ' + data.product.sku);
            $('#product-sku').text(data.product.product_name + ' (' + data.product.sku + ')');

            // Update Quantities In
            $('#total-purchase').text(data.stock_type_sums['purchase'] ? data.stock_type_sums['purchase'] + ' packets' : '0.00 packets');
            $('#opening-stock').text(data.stock_type_sums['opening_stock'] ? data.stock_type_sums['opening_stock'] + ' packets' : '0.00 packets');
            $('#total-sell-return').text(data.stock_type_sums['sales_return_with_bill'] ? data.stock_type_sums['sales_return_with_bill'] + ' packets' : '0.00 packets');
            $('#stock-transfers-in').text(data.stock_type_sums['transfer_in'] ? data.stock_type_sums['transfer_in'] + ' packets' : '0.00 packets');

            // Update Quantities Out
            $('#total-sold').text(data.stock_type_sums['sale'] ? data.stock_type_sums['sale'] + ' packets' : '0.00 packets');
            $('#total-stock-adjustment').text(data.stock_type_sums['adjustment'] ? data.stock_type_sums['adjustment'] + ' packets' : '0.00 packets');
            $('#total-purchase-return').text(data.stock_type_sums['purchase_return'] ? data.stock_type_sums['purchase_return'] + ' packets' : '0.00 packets');
            $('#stock-transfers-out').text(data.stock_type_sums['transfer_out'] ? data.stock_type_sums['transfer_out'] + ' packets' : '0.00 packets');

            // Update Current Stock
            $('#current-stock').text(data.current_stock + ' packets');

            // Clear existing data
            stockTable.clear();

            // Update Stock Histories
            if (data.stock_histories) {
                data.stock_histories.forEach(function(history) {
                    var referenceNo = getReferenceNo(history);
                    var customerSupplierInfo = getCustomerSupplierInfo(history);

                    stockTable.row.add([
                        history.stock_type.replace('_', ' ').toUpperCase(),
                        history.quantity > 0 ? '+' + history.quantity : history.quantity,
                        history.location_batch && history.location_batch.qty !== undefined ? history.location_batch.qty : '0',
                        new Date(history.created_at).toLocaleString(),
                        referenceNo,
                        customerSupplierInfo
                    ]).draw();
                });
            }
        }

        function getReferenceNo(history) {
            if (history.stock_type === 'purchase' && history.location_batch && history.location_batch.batch && history.location_batch.batch.purchase_products) {
                return history.location_batch.batch.purchase_products[0]?.purchase?.reference_no || 'N/A';
            } else if (history.stock_type === 'sale' && history.location_batch && history.location_batch.batch && history.location_batch.batch.sales_products) {
                return history.location_batch.batch.sales_products[0]?.sale?.invoice_no || 'N/A';
            } else if (history.stock_type === 'purchase_return' && history.location_batch && history.location_batch.batch && history.location_batch.batch.purchase_returns) {
                return history.location_batch.batch.purchase_returns[0]?.purchase_return?.reference_no || 'N/A';
            } else if (history.stock_type === 'sale_return' && history.location_batch && history.location_batch.batch && history.location_batch.batch.sale_returns) {
                return history.location_batch.batch.sale_returns[0]?.sales_return?.reference_no || 'N/A';
            } else if (history.stock_type === 'adjustment' && history.location_batch && history.location_batch.batch && history.location_batch.batch.stock_adjustments) {
                return history.location_batch.batch.stock_adjustments[0]?.stock_adjustment?.reference_no || 'N/A';
            } else if (history.stock_type === 'transfer_in' || history.stock_type === 'transfer_out') {
                return history.location_batch.batch.stock_transfers[0]?.stock_transfer?.reference_no || 'N/A';
            } else {
                return 'N/A';
            }
        }

        function getCustomerSupplierInfo(history) {
            if (history.stock_type === 'purchase' && history.location_batch && history.location_batch.batch && history.location_batch.batch.purchase_products) {
                const supplier = history.location_batch.batch.purchase_products[0]?.purchase?.supplier;
                return supplier ? (supplier.first_name + ' ' + supplier.last_name) : 'N/A';
            } else if (history.stock_type === 'sale' && history.location_batch && history.location_batch.batch && history.location_batch.batch.sales_products) {
                const customer = history.location_batch.batch.sales_products[0]?.sale?.customer;
                return customer ? (customer.first_name + ' ' + customer.last_name) : 'N/A';
            } else if (history.stock_type === 'purchase_return' && history.location_batch && history.location_batch.batch && history.location_batch.batch.purchase_returns) {
                const supplier = history.location_batch.batch.purchase_returns[0]?.purchase_return?.supplier;
                return supplier ? (supplier.first_name + ' ' + supplier.last_name) : 'N/A';
            } else if (history.stock_type === 'sale_return' && history.location_batch && history.location_batch.batch && history.location_batch.batch.sale_returns) {
                const customer = history.location_batch.batch.sale_returns[0]?.sales_return?.customer;
                return customer ? (customer.first_name + ' ' + customer.last_name) : 'N/A';
            } else {
                return 'N/A';
            }
        }
    });
</script>
@endsection