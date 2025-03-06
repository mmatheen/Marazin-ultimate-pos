@extends('layout.layout')
@section('content')

<div class="container-fluid mt-4">
    <h3>Product Stock History</h3>
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title" id="product-name"></h5>
            <div class="form-group row">
                <label for="product" class="col-sm-2 col-form-label">Product:</label>
                <div class="col-sm-6">
                    <select class="form-control" id="product">
                        <option id="product-option"></option>
                    </select>
                </div>
                <label for="businessLocation" class="col-sm-2 col-form-label">Business Location:</label>
                <div class="col-sm-2">
                    <select class="form-control" id="businessLocation">
                        <option>Awesome Shop</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title" id="product-sku"></h5>
            <div class="row">
                <div class="col-md-4">
                    <h6>Quantities In</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Total Purchase</td><td id="total-purchase">0.00 packets</td></tr>
                            <tr><td>Opening Stock</td><td id="opening-stock">0.00 packets</td></tr>
                            <tr><td>Total Sell Return</td><td id="total-sell-return">0.00 packets</td></tr>
                            <tr><td>Stock Transfers (In)</td><td id="stock-transfers-in">0.00 packets</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6>Quantities Out</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Total Sold</td><td id="total-sold">0.00 packets</td></tr>
                            <tr><td>Total Stock Adjustment</td><td id="total-stock-adjustment">0.00 packets</td></tr>
                            <tr><td>Total Purchase Return</td><td id="total-purchase-return">0.00 packets</td></tr>
                            <tr><td>Stock Transfers (Out)</td><td id="stock-transfers-out">0.00 packets</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-4">
                    <h6>Totals</h6>
                    <table class="table table-borderless">
                        <tbody>
                            <tr><td>Current stock</td><td id="current-stock">0.00 packets</td></tr>
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
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('product.product_ajax')
<script>
    $(document).ready(function() {
        var productId = {{ $product->id }};
        fetchStockHistory(productId);

        function fetchStockHistory(productId) {
            $.ajax({
                url: "{{ route('productStockHistory', '') }}/" + productId,
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
            $('#total-purchase').text(data.quantitiesIn + ' packets');
            $('#opening-stock').text('0.00 packets'); // Assuming 'undefined' values to be 0
            $('#total-sell-return').text('0.00 packets'); // Assuming 'undefined' values to be 0
            $('#stock-transfers-in').text('0.00 packets'); // Assuming 'undefined' values to be 0

            // Update Quantities Out
            $('#total-sold').text(data.quantitiesOut + ' packets');
            $('#total-stock-adjustment').text('0.00 packets'); // Assuming 'undefined' values to be 0
            $('#total-purchase-return').text('0.00 packets'); // Assuming 'undefined' values to be 0
            $('#stock-transfers-out').text('0.00 packets'); // Assuming 'undefined' values to be 0

            // Update Current Stock
            $('#current-stock').text(data.currentStock + ' packets');

            // Update Stock Histories
            var stockHistoryBody = '';
            data.stockHistories.forEach(function(history) {
                stockHistoryBody += `
                    <tr>
                        <td>${history.stock_type.replace('_', ' ').toUpperCase()}</td>
                        <td>${history.quantity > 0 ? '+' + history.quantity : history.quantity}</td>
                        <td>${history.locationBatch && history.locationBatch.qty !== undefined ? history.locationBatch.qty : '0'}</td>
                        <td>${new Date(history.created_at).toLocaleString()}</td>
                        <td>${history.purchase ? history.purchase.reference_no : (history.sale ? history.sale.invoice_no : (history.saleReturn ? history.saleReturn.invoice_number : (history.purchaseReturn ? history.purchaseReturn.reference_no : 'N/A')))}</td>
                        <td>${history.purchase ? history.purchase.supplier.name : (history.sale ? (history.sale.customer ? history.sale.customer.name : 'Walk-In Customer') : (history.saleReturn ? history.saleReturn.customer.name : (history.purchaseReturn ? history.purchaseReturn.supplier.name : 'N/A')))}</td>
                    </tr>
                `;
            });
            $('#stock-history-body').html(stockHistoryBody);
        }
    });
</script>
@endsection