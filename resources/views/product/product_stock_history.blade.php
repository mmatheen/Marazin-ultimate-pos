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

                <!-- Location Filter Status Indicator -->
                <div id="location-filter-status" style="display: none;" class="alert alert-info mb-3">
                    <i class="fas fa-filter"></i> <strong>Location Filter Active:</strong> <span id="filter-location-name"></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="clear-location-filter-btn">
                        <i class="fas fa-times"></i> Clear Filter
                    </button>
                </div>

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
                                    <td>Purchase Return Reversal</td>
                                    <td id="purchase-return-reversal">{{ $stock_type_sums['purchase_return_reversal'] ?? '0.00' }} packets</td>
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
                            <th>Paid Qty Change</th>
                            <th>Free Qty Change</th>
                            <th>New Quantity</th>
                            <th>Event Time</th>
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
            var stockTable = $('#stockTable').DataTable({
                order: [[4, 'asc']], // Show from oldest to latest for clear movement trail
                columnDefs: [
                    { orderable: true, targets: [0, 1, 2, 3, 4, 5, 6] } // All columns orderable
                ],
                drawCallback: function() {
                    injectDateGroupHeaders(this.api());
                }
            });
            var productId = getProductIdFromUrl(); // Get product ID from URL
            var initialLoad = true;

            function extractDateGroupKeyFromCell(cellHtml) {
                const html = String(cellHtml || '');
                const match = html.match(/class="date-group-key"[^>]*>([^<]+)/);
                return match ? match[1] : 'older';
            }

            function getDateGroupTitle(groupKey) {
                if (groupKey === 'today') {
                    return 'Today';
                }

                if (groupKey === 'yesterday') {
                    return 'Yesterday';
                }

                return 'Older Updates';
            }

            function injectDateGroupHeaders(api) {
                const tbody = $(api.table().body());
                tbody.find('tr.stock-date-group-row').remove();

                const rowNodes = api.rows({
                    page: 'current'
                }).nodes();
                const rowData = api.rows({
                    page: 'current'
                }).data();

                if (!rowNodes || rowNodes.length === 0) {
                    return;
                }

                let lastGroupKey = null;

                for (let i = 0; i < rowData.length; i++) {
                    const dateCellHtml = rowData[i][4] || '';
                    const groupKey = extractDateGroupKeyFromCell(dateCellHtml);

                    if (groupKey !== lastGroupKey) {
                        $(rowNodes[i]).before(
                            '<tr class="stock-date-group-row table-secondary">' +
                            '<td colspan="7"><strong>' + getDateGroupTitle(groupKey) + '</strong></td>' +
                            '</tr>'
                        );
                        lastGroupKey = groupKey;
                    }
                }
            }

            // Initialize location filter status
            updateLocationFilterStatus();

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
                updateLocationFilterStatus();
                fetchStockHistory(productId);
            });

            // Clear location filter button click handler
            $('#clear-location-filter-btn').click(function() {
                clearLocationFilter();
            });

            function updateLocationFilterStatus() {
                const locationId = $('#businessLocation').val();
                const locationName = $('#businessLocation option:selected').text();

                if (locationId) {
                    $('#filter-location-name').text(locationName);
                    $('#location-filter-status').show();
                } else {
                    $('#location-filter-status').hide();
                }
            }

            function clearLocationFilter() {
                $('#businessLocation').val('').trigger('change');
            }

            function fetchStockHistory(productId) {
                if (!productId) {
                    console.log('No product ID provided');
                    return;
                }

                const locationId = $('#businessLocation').val();
                const locationName = $('#businessLocation option:selected').text();
                console.log('Fetching stock history for product:', productId, 'location:', locationId, 'location name:', locationName);

                // Show loading state
                $('#stock-history-body').html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading stock history...</td></tr>');

                $.ajax({
                    url: "/products/stock-history/" + productId,
                    method: "GET",
                    data: {
                        location_id: locationId
                    },
                    dataType: "json",
                    success: function(response) {
                        console.log('Stock history response:', response);
                        console.log('Location filter applied:', locationId ? 'Yes (ID: ' + locationId + ')' : 'No (All locations)');
                        console.log('Stock histories count:', response.stock_histories ? response.stock_histories.length : 0);
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
                            '<tr><td colspan="7" class="text-center text-muted">' + errorMessage + '</td></tr>'
                        );
                    }
                });
            }

            function updateStockHistoryView(data) {
                console.log('Updating stock history view with data:', data);

                // Handle error cases
                if (data.error) {
                    $('#stock-history-body').html(
                        '<tr><td colspan="7" class="text-center text-muted">' + data.error + '</td></tr>'
                    );
                    return;
                }

                // Ensure we have valid data structure
                if (!data.product) {
                    $('#stock-history-body').html(
                        '<tr><td colspan="7" class="text-center text-muted">Invalid product data received.</td></tr>'
                    );
                    return;
                }

                // Update product information and add location filter indicator
                const locationId = $('#businessLocation').val();
                const locationName = $('#businessLocation option:selected').text();
                const productTitle = data.product.product_name + (locationId ? ' - Filtered by: ' + locationName : ' - All Locations');

                $('#product-name').text(data.product.product_name);
                $('#product-sku').text(productTitle + ' (' + data.product.sku + ')');

                // Safely handle stock_type_sums with default values
                const stockSums = data.stock_type_sums || {};

                // Update Quantities In
                $('#total-purchase').text((stockSums['purchase'] || 0) + ' Pcs');
                $('#opening-stock').text((stockSums['opening_stock'] || 0) + ' Pcs');
                const totalSellReturn =
                    (stockSums['sales_return_with_bill'] || 0) +
                    (stockSums['sales_return_without_bill'] || 0) +
                    (stockSums['sale_reversal'] || 0);
                $('#total-sell-return').text(totalSellReturn + ' Pcs');
                $('#purchase-return-reversal').text((stockSums['purchase_return_reversal'] || 0) + ' Pcs');
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
                    let message = 'No stock movements found for this product';

                    if (locationId) {
                        message += ' in location: <strong>' + locationName + '</strong>';
                        message += '<br><small class="text-muted">Try selecting "All Locations" to see all stock movements for this product.</small>';
                    }

                    $('#stock-history-body').html(
                        '<tr><td colspan="7" class="text-center text-muted">' + message + '</td></tr>'
                    );
                    return;
                }

                const getHistoryTimestamp = function(history) {
                    return new Date(history.created_at || history.updated_at).getTime() || 0;
                };

                const formatDateTime = function(value) {
                    if (!value) {
                        return 'N/A';
                    }

                    const parsedDate = new Date(value);
                    if (Number.isNaN(parsedDate.getTime())) {
                        return 'N/A';
                    }

                    return parsedDate.toLocaleString('en-GB', {
                        timeZone: 'Asia/Colombo',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                    });
                };

                const getColomboDayKey = function(dateValue) {
                    const parsedDate = new Date(dateValue);
                    if (Number.isNaN(parsedDate.getTime())) {
                        return null;
                    }

                    return new Intl.DateTimeFormat('en-CA', {
                        timeZone: 'Asia/Colombo',
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit'
                    }).format(parsedDate);
                };

                const renderHistoryDateCell = function(history) {
                    const createdAt = history.created_at;
                    const updatedAt = history.updated_at;
                    const createdAtDate = createdAt ? new Date(createdAt) : null;
                    const sortValue = (createdAtDate && !Number.isNaN(createdAtDate.getTime()))
                        ? createdAtDate.toISOString()
                        : '';
                    let groupKey = 'older';

                    if (createdAtDate && !Number.isNaN(createdAtDate.getTime())) {
                        const createdDayKey = getColomboDayKey(createdAtDate);
                        const todayDayKey = getColomboDayKey(new Date());
                        const yesterdayDayKey = getColomboDayKey(new Date(Date.now() - 24 * 60 * 60 * 1000));

                        if (createdDayKey && todayDayKey && createdDayKey === todayDayKey) {
                            groupKey = 'today';
                        } else if (createdDayKey && yesterdayDayKey && createdDayKey === yesterdayDayKey) {
                            groupKey = 'yesterday';
                        }
                    }

                    const hasSeparateUpdateTime = updatedAt && createdAt &&
                        (new Date(updatedAt).getTime() !== new Date(createdAt).getTime());

                    if (hasSeparateUpdateTime) {
                        return '<span style="display:none;">' + sortValue + '</span>' +
                            '<span class="date-group-key" style="display:none;">' + groupKey + '</span>' +
                            '<div><strong>' + formatDateTime(createdAt) + '</strong></div>' +
                            '<small class="text-muted">Updated: ' + formatDateTime(updatedAt) + '</small>';
                    }

                    return '<span style="display:none;">' + sortValue + '</span>' +
                        '<span class="date-group-key" style="display:none;">' + groupKey + '</span>' +
                        '<div><strong>' + formatDateTime(createdAt || updatedAt) + '</strong></div>';
                };

                // First pass in chronological order to compute the stock after each movement.
                const chronologicalHistories = [...data.stock_histories].sort((a, b) => {
                    const timeDiff = getHistoryTimestamp(a) - getHistoryTimestamp(b);
                    if (timeDiff !== 0) {
                        return timeDiff;
                    }
                    return (a.id || 0) - (b.id || 0);
                });

                const runningStockByHistoryId = new Map();
                let runningStock = 0;

                chronologicalHistories.forEach(function(history) {
                    if ([
                            'purchase', 'opening_stock',
                            'sales_return_with_bill', 'sales_return_without_bill',
                            'sale_reversal', 'purchase_return_reversal', 'transfer_in'
                        ].includes(history.stock_type)) {
                        runningStock += parseFloat(history.quantity);
                    } else if ([
                            'sale', 'adjustment',
                            'purchase_return', 'purchase_reversal',
                            'transfer_out'
                        ].includes(history.stock_type)) {
                        runningStock -= Math.abs(history.quantity);
                    }

                    runningStockByHistoryId.set(history.id, runningStock);
                });

                // Display in chronological order (oldest to latest)
                // so each row naturally explains the full movement trail from start.
                if (chronologicalHistories.length > 0) {
                    chronologicalHistories.forEach(function(history) {
                        var referenceNo = getReferenceNo(history);
                        var customerSupplierInfo = getCustomerSupplierInfo(history);
                        var stockAfterMovement = runningStockByHistoryId.get(history.id) || 0;

                        // Calculate paid and free quantities with correct signs
                        var totalQty = parseFloat(history.quantity);
                        var absoluteFreeQty = parseFloat(history.free_quantity) || 0;

                        // Apply the same direction (sign) to free qty as the total quantity
                        // If total is negative (sale/return), free qty should also be negative
                        var sign = totalQty >= 0 ? 1 : -1;
                        var freeQty = absoluteFreeQty * sign;
                        var paidQty = totalQty - freeQty;

                        // Format with + or - sign
                        var paidQtyDisplay = paidQty > 0 ? '+' + paidQty.toFixed(2) : paidQty.toFixed(2);
                        var freeQtyDisplay = freeQty > 0 ? '+' + freeQty.toFixed(2) : (freeQty !== 0 ? freeQty.toFixed(2) : '0.00');

                        // Add row without drawing (for performance)
                        stockTable.row.add([
                            history.stock_type.replace('_', ' ').toUpperCase(),
                            paidQtyDisplay,
                            freeQtyDisplay,
                            stockAfterMovement.toFixed(2),
                            renderHistoryDateCell(history),
                            referenceNo,
                            customerSupplierInfo
                        ]);
                    });

                    // Draw all rows at once (oldest-first)
                    stockTable.draw();
                }
            }

            function getReferenceNo(history) {
                const matchedHistoryTransaction = getMatchedHistoryTransaction(history);

                const referencePathByType = {
                    purchase: 'purchase.reference_no',
                    sale: 'sale.invoice_no',
                    purchase_return: 'purchase_return.reference_no',
                    sale_return: 'sales_return.reference_no'
                };

                const matchedReferencePath = referencePathByType[matchedHistoryTransaction.type];
                if (matchedReferencePath) {
                    return getNestedValue(matchedHistoryTransaction.record, matchedReferencePath, 'N/A');
                }

                if (history.stock_type === 'adjustment') {
                    return getNestedValue(history, 'location_batch.batch.stock_adjustments.0.stock_adjustment.reference_no', 'N/A');
                }

                if (history.stock_type === 'transfer_in' || history.stock_type === 'transfer_out') {
                    return getNestedValue(history, 'location_batch.batch.stock_transfers.0.stock_transfer.reference_no', 'N/A');
                }

                return 'N/A';
            }

            function getMatchedHistoryTransaction(history) {
                const matcherRules = [
                    {
                        types: ['purchase'],
                        relationPath: 'location_batch.batch.purchase_products',
                        type: 'purchase'
                    },
                    {
                        types: ['sale'],
                        relationPath: 'location_batch.batch.sales_products',
                        type: 'sale'
                    },
                    {
                        types: ['purchase_return', 'purchase_return_reversal'],
                        relationPath: 'location_batch.batch.purchase_returns',
                        type: 'purchase_return'
                    },
                    {
                        types: ['sales_return_with_bill', 'sales_return_without_bill'],
                        relationPath: 'location_batch.batch.sale_returns',
                        type: 'sale_return'
                    },
                ];

                // sale_reversal special handling with fallback priority.
                if (history.stock_type === 'sale_reversal') {
                    const saleReturnTransactions = getNestedArray(history, 'location_batch.batch.sale_returns');
                    if (saleReturnTransactions.length > 0) {
                        return {
                            type: 'sale_return',
                            record: findBestMatchingTransaction(history, saleReturnTransactions)
                        };
                    }

                    const saleTransactions = getNestedArray(history, 'location_batch.batch.sales_products');
                    if (saleTransactions.length > 0) {
                        return {
                            type: 'sale',
                            record: findBestMatchingTransaction(history, saleTransactions)
                        };
                    }
                }

                for (const rule of matcherRules) {
                    if (!rule.types.includes(history.stock_type)) {
                        continue;
                    }

                    const transactions = getNestedArray(history, rule.relationPath);
                    if (transactions.length > 0) {
                        return {
                            type: rule.type,
                            record: findBestMatchingTransaction(history, transactions)
                        };
                    }
                }

                return { type: null, record: null };
            }

            function findBestMatchingTransaction(stockHistory, transactions) {
                if (!transactions || transactions.length === 0) return null;
                if (transactions.length === 1) return transactions[0];

                // Try to match based on quantity and timing
                const historyDate = new Date(stockHistory.created_at || stockHistory.updated_at);
                const historyQuantity = Math.abs(stockHistory.quantity);

                // Find transactions with matching quantity
                const quantityMatches = transactions.filter(transaction => {
                    return Math.abs(transaction.quantity) === historyQuantity;
                });

                if (quantityMatches.length === 1) {
                    return quantityMatches[0];
                }

                // If multiple quantity matches or no quantity matches, find closest by time
                let bestMatch = transactions[0];
                let smallestTimeDiff = Math.abs(new Date(bestMatch.created_at || bestMatch.updated_at) - historyDate);

                for (let i = 1; i < transactions.length; i++) {
                    const transactionDate = new Date(transactions[i].created_at || transactions[i].updated_at);
                    const timeDiff = Math.abs(transactionDate - historyDate);

                    if (timeDiff < smallestTimeDiff) {
                        smallestTimeDiff = timeDiff;
                        bestMatch = transactions[i];
                    }
                }

                return bestMatch;
            }

            function getCustomerSupplierInfo(history) {
                const matchedHistoryTransaction = getMatchedHistoryTransaction(history);

                const partyPathByType = {
                    purchase: 'purchase.supplier',
                    sale: 'sale.customer',
                    purchase_return: 'purchase_return.supplier',
                    sale_return: 'sales_return.customer'
                };

                const partyPath = partyPathByType[matchedHistoryTransaction.type];
                if (!partyPath) {
                    return 'N/A';
                }

                const party = getNestedValue(matchedHistoryTransaction.record, partyPath, null);
                return formatContactName(party);
            }

            function getNestedValue(source, path, defaultValue = undefined) {
                if (!source || !path) {
                    return defaultValue;
                }

                const keys = path.split('.');
                let current = source;

                for (const key of keys) {
                    if (current == null) {
                        return defaultValue;
                    }

                    current = current[key];
                }

                return current ?? defaultValue;
            }

            function getNestedArray(source, path) {
                const value = getNestedValue(source, path, []);
                return Array.isArray(value) ? value : [];
            }

            function formatContactName(contact) {
                if (!contact) {
                    return 'N/A';
                }

                const firstName = (contact.first_name || '').trim();
                const lastName = (contact.last_name || '').trim();
                const fullName = [firstName, lastName].filter(Boolean).join(' ').trim();

                return fullName || 'N/A';
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
