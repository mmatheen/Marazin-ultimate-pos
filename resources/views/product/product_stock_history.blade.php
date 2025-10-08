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
                            @foreach ($products as $prod)
                                <option value="{{ $prod->id }}" {{ $prod->id == $product->id ? 'selected' : '' }}>
                                    {{ $prod->product_name }} - {{ $prod->sku }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <label for="businessLocation" class="col-sm-2 col-form-label">Business Location:</label>
                    <div class="col-sm-2">
                        <select class="form-control selectBox" id="businessLocation">
                            <option value="">All Locations</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
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
                                <tr>
                                    <td>Total Purchase</td>
                                    <td id="total-purchase">{{ $stock_type_sums['purchase'] ?? '0.00' }} packets</td>
                                </tr>
                                <tr>
                                    <td>Opening Stock</td>
                                    <td id="opening-stock">{{ $stock_type_sums['opening_stock'] ?? '0.00' }} packets</td>
                                </tr>
                                <tr>
                                    <td>Total Sell Return</td>
                                    <td id="total-sell-return">{{ $stock_type_sums['sales_return_with_bill'] ?? '0.00' }}
                                        packets</td>
                                </tr>
                                <tr>
                                    <td>Stock Transfers (In)</td>
                                    <td id="stock-transfers-in">{{ $stock_type_sums['transfer_in'] ?? '0.00' }} packets
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6>Quantities Out</h6>
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td>Total Sold</td>
                                    <td id="total-sold">{{ $stock_type_sums['sale'] ?? '0.00' }} packets</td>
                                </tr>
                                <tr>
                                    <td>Total Stock Adjustment</td>
                                    <td id="total-stock-adjustment">{{ $stock_type_sums['adjustment'] ?? '0.00' }} packets
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Purchase Return</td>
                                    <td id="total-purchase-return">{{ $stock_type_sums['purchase_return'] ?? '0.00' }}
                                        packets</td>
                                </tr>
                                <tr>
                                    <td>Stock Transfers (Out)</td>
                                    <td id="stock-transfers-out">{{ $stock_type_sums['transfer_out'] ?? '0.00' }} packets
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6>Totals</h6>
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td>Current stock</td>
                                    <td id="current-stock">{{ $current_stock }} packets</td>
                                </tr>
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

    <script>
        $(document).ready(function() {

            $('#product').select2({
                placeholder: "Search product by name or SKU",
                minimumInputLength: 1,
                ajax: {
                    url: window.location.href, // Same URL as current page
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            term: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    }
                }
            });
            $('#product').on('select2:open', function() {
                // Use setTimeout to wait for DOM update
                setTimeout(() => {
                    // Get all open Select2 dropdowns
                    const allDropdowns = document.querySelectorAll('.select2-container--open');

                    // Get the most recently opened dropdown (last one)
                    const lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];

                    if (lastOpenedDropdown) {
                        // Find the search input inside this dropdown
                        const searchInput = lastOpenedDropdown.querySelector(
                            '.select2-search__field');

                        if (searchInput) {
                            searchInput.focus(); // Focus the search input
                            searchInput.select(); // Optional: select any existing text
                        }
                    }
                }, 10); // Very short delay to allow DOM render
            });
            var stockTable = $('#stockTable').DataTable();
            var productId = getProductIdFromUrl(); // Get product ID from URL
            var initialLoad = true;

            // Load stock history on page load
            if (productId) {
                fetchStockHistory(productId);
            }

            // Event listener for product selection change
            $('#product').change(function() {
                productId = $(this).val();
                updateUrlWithProductId(productId);
                fetchStockHistory(productId);
            });

            // Also trigger when location changes
            $('#businessLocation').change(function() {
                const productId = $('#product').val();
                fetchStockHistory(productId);
            });

            function fetchStockHistory(productId) {
                if (!productId) {
                    console.log('No product ID provided');
                    return;
                }
                
                const locationId = $('#businessLocation').val();
                console.log('Fetching stock history for product:', productId, 'location:', locationId);

                // Show loading state
                $('#stock-history-body').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading stock history...</td></tr>');

                $.ajax({
                    url: "/products/stock-history/" + productId,
                    method: "GET",
                    data: {
                        location_id: locationId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log('Stock history response:', response);
                        updateStockHistoryView(response);
                        if (!initialLoad) {
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }
                        initialLoad = false;
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching stock history:', error);
                        console.error('Status:', status);
                        console.error('Response:', xhr.responseText);
                        
                        // Show user-friendly error message
                        let errorMessage = 'Error loading stock history. Please try again.';
                        if (xhr.status === 404) {
                            errorMessage = 'No stock history found for this product' + (locationId ? ' in the selected location' : '') + '.';
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server error. Please contact support if the problem persists.';
                        }
                        
                        $('#stock-history-body').html(
                            '<tr><td colspan="6" class="text-center text-muted">' + errorMessage + '</td></tr>'
                        );
                    }
                });
            }

            function updateStockHistoryView(data) {
                console.log('Updating stock history view with data:', data);
                
                // Handle error cases
                if (data.error) {
                    $('#stock-history-body').html(
                        '<tr><td colspan="6" class="text-center text-muted">' + data.error + '</td></tr>'
                    );
                    return;
                }
                
                // Ensure we have valid data structure
                if (!data.product) {
                    $('#stock-history-body').html(
                        '<tr><td colspan="6" class="text-center text-muted">Invalid product data received.</td></tr>'
                    );
                    return;
                }

                $('#product-name').text(data.product.product_name);
                $('#product-sku').text(data.product.product_name + ' (' + data.product.sku + ')');

                // Safely handle stock_type_sums with default values
                const stockSums = data.stock_type_sums || {};
                
                // Update Quantities In
                $('#total-purchase').text((stockSums['purchase'] || 0) + ' Pcs');
                $('#opening-stock').text((stockSums['opening_stock'] || 0) + ' Pcs');
                $('#total-sell-return').text((stockSums['sales_return_with_bill'] || 0) + ' Pcs');
                $('#stock-transfers-in').text((stockSums['transfer_in'] || 0) + ' Pcs');

                // Update Quantities Out
                $('#total-sold').text((stockSums['sale'] || 0) + ' Pcs');
                $('#total-stock-adjustment').text((stockSums['adjustment'] || 0) + ' Pcs');
                $('#total-purchase-return').text((stockSums['purchase_return'] || 0) + ' Pcs');
                $('#stock-transfers-out').text((stockSums['transfer_out'] || 0) + ' Pcs');

                // Update Current Stock
                $('#current-stock').text((data.current_stock || 0) + ' Pcs');

                // Clear existing data
                stockTable.clear();
                
                // Handle empty stock histories
                if (!data.stock_histories || data.stock_histories.length === 0) {
                    $('#stock-history-body').html(
                        '<tr><td colspan="6" class="text-center text-muted">No stock movements found for this product' + 
                        ($('#businessLocation').val() ? ' in the selected location' : '') + '.</td></tr>'
                    );
                    return;
                }

                let runningStock = 0;

                // Sort histories by date ascending
                const sortedHistories = [...data.stock_histories].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));

                if (sortedHistories.length > 0) {
                    sortedHistories.forEach(function(history) {
                        if ([
                                'purchase', 'opening_stock',
                                'sales_return_with_bill', 'sales_return_without_bill',
                                'transfer_in'
                            ].includes(history.stock_type)) {
                            runningStock += parseFloat(history.quantity);
                        } else if ([
                                'sale', 'adjustment',
                                'purchase_return', 'transfer_out'
                            ].includes(history.stock_type)) {
                            runningStock -= Math.abs(history.quantity);
                        }

                        var referenceNo = getReferenceNo(history);
                        var customerSupplierInfo = getCustomerSupplierInfo(history);

                        stockTable.row.add([
                            history.stock_type.replace('_', ' ').toUpperCase(),
                            history.quantity > 0 ? '+' + history.quantity : history.quantity,
                            runningStock.toFixed(2),
                            new Date(history.created_at).toLocaleString(),
                            referenceNo,
                            customerSupplierInfo
                        ]).draw();
                    });
                }
            }

            function getReferenceNo(history) {
                if (history.stock_type === 'purchase' && history.location_batch?.batch?.purchase_products?.length) {
                    return history.location_batch.batch.purchase_products[0]?.purchase?.reference_no || 'N/A';
                } else if (history.stock_type === 'sale' && history.location_batch?.batch?.sales_products?.length) {
                    return history.location_batch.batch.sales_products[0]?.sale?.invoice_no || 'N/A';
                } else if (history.stock_type === 'purchase_return' && history.location_batch?.batch
                    ?.purchase_returns?.length) {
                    return history.location_batch.batch.purchase_returns[0]?.purchase_return?.reference_no || 'N/A';
                } else if (history.stock_type === 'sale_return' && history.location_batch?.batch?.sale_returns
                    ?.length) {
                    return history.location_batch.batch.sale_returns[0]?.sales_return?.reference_no || 'N/A';
                } else if (history.stock_type === 'adjustment' && history.location_batch?.batch?.stock_adjustments
                    ?.length) {
                    return history.location_batch.batch.stock_adjustments[0]?.stock_adjustment?.reference_no ||
                        'N/A';
                } else if (history.stock_type === 'transfer_in' || history.stock_type === 'transfer_out') {
                    return history.location_batch.batch.stock_transfers[0]?.stock_transfer?.reference_no || 'N/A';
                } else {
                    return 'N/A';
                }
            }

            function getCustomerSupplierInfo(history) {
                if (history.stock_type === 'purchase' && history.location_batch?.batch?.purchase_products?.length) {
                    const supplier = history.location_batch.batch.purchase_products[0]?.purchase?.supplier;
                    return supplier ? (supplier.first_name + ' ' + supplier.last_name) : 'N/A';
                } else if (history.stock_type === 'sale' && history.location_batch?.batch?.sales_products?.length) {
                    const customer = history.location_batch.batch.sales_products[0]?.sale?.customer;
                    return customer ? (customer.first_name + ' ' + customer.last_name) : 'N/A';
                } else if (history.stock_type === 'purchase_return' && history.location_batch?.batch
                    ?.purchase_returns?.length) {
                    const supplier = history.location_batch.batch.purchase_returns[0]?.purchase_return?.supplier;
                    return supplier ? (supplier.first_name + ' ' + supplier.last_name) : 'N/A';
                } else if (history.stock_type === 'sale_return' && history.location_batch?.batch?.sale_returns
                    ?.length) {
                    const customer = history.location_batch.batch.sale_returns[0]?.sales_return?.customer;
                    return customer ? (customer.first_name + ' ' + customer.last_name) : 'N/A';
                } else {
                    return 'N/A';
                }
            }

            // Helper Functions
            function getProductIdFromUrl() {
                const path = window.location.pathname;
                const segments = path.split('/');
                return segments[segments.length - 1];
            }

            function updateUrlWithProductId(productId) {
                const newUrl = `/products/stock-history/${productId}`;
                window.history.pushState({
                    path: newUrl
                }, '', newUrl);
            }
        });
        
    </script>
    @include('product.product_ajax')
@endsection
