@extends('layout.layout')

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Barcode Generator</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Barcode</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Barcode Generation Section --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Generate Barcodes</h5>

                        <div class="row align-items-end">
                            {{-- Product Search --}}
                            <div class="col-md-5">
                                <label class="form-label">Search Product</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" id="productSearch"
                                           placeholder="Type product name or SKU..." autocomplete="off">

                                    {{-- Search Dropdown --}}
                                    <div id="searchDropdown" class="position-absolute w-100 bg-white border rounded shadow-sm"
                                         style="display: none; max-height: 400px; overflow-y: auto; z-index: 1000; top: 100%; margin-top: 5px;">
                                    </div>
                                </div>
                            </div>

                            {{-- Price Type Selection --}}
                            <div class="col-md-2">
                                <label class="form-label">Price Type</label>
                                <select class="form-control" id="priceType">
                                    <option value="retail_price">Retail Price</option>
                                    <option value="wholesale_price">Wholesale</option>
                                    <option value="special_price">Special</option>
                                    <option value="cost_price">Cost Price</option>
                                    <option value="max_retail_price">Max Retail</option>
                                </select>
                            </div>

                            {{-- Quantity Input --}}
                            <div class="col-md-2">
                                <label class="form-label">Number of Qty</label>
                                <input type="number" class="form-control" id="barcodeQuantity"
                                       value="2" min="1" max="100" placeholder="Enter quantity">
                            </div>

                            {{-- Generate Button --}}
                            <div class="col-md-3">
                                <button type="button" class="btn btn-success w-100" id="generateBtn" disabled>
                                    <i class="fas fa-barcode me-2"></i>GENERATE
                                </button>
                            </div>
                        </div>

                        {{-- Selected Batch Info --}}
                        <div id="selectedBatchInfo" class="mt-3" style="display: none;">
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-md-8">
                                        <strong>Product:</strong> <span id="selectedProductName"></span>
                                        <span class="ms-3"><strong>Batch:</strong> <span id="selectedBatchNo"></span></span>
                                        <span class="ms-3"><strong>SKU:</strong> <span id="selectedProductSku"></span></span>
                                        <div class="mt-1">
                                            <small>
                                                <strong>CP:</strong> <span id="selectedCP"></span> |
                                                <strong>WP:</strong> <span id="selectedWP"></span> |
                                                <strong>SP:</strong> <span id="selectedSP"></span> |
                                                <strong>RP:</strong> <span id="selectedRP"></span> |
                                                <strong>MRP:</strong> <span id="selectedMRP"></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <strong>Stock:</strong> <span id="selectedStock" class="text-success"></span>
                                        <span class="ms-3"><strong>Expiry:</strong> <span id="selectedExpiry"></span></span>
                                        <button type="button" class="btn-close float-end" id="clearSelection"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Barcode Display Section --}}
        <div class="row mt-4" id="barcodeDisplaySection" style="display: none;">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="card-title mb-0">Generated Barcodes</h5>
                            <button type="button" class="btn btn-primary" id="printBtn">
                                <i class="fas fa-print me-2"></i>PRINT
                            </button>
                        </div>

                        {{-- Barcodes Grid --}}
                        <div id="barcodesContainer" class="row g-3">
                            <!-- Barcodes will be dynamically inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Print Styles --}}
    <style>
        @media print {
            /* Hide everything except barcodes */
            body * {
                visibility: hidden;
            }

            #barcodesContainer, #barcodesContainer * {
                visibility: visible;
            }

            #barcodesContainer {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .barcode-item {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Hide buttons and unnecessary elements */
            .btn, .page-header, .breadcrumb, .card-title,
            #barcodeDisplaySection .d-flex, #selectedProductInfo {
                display: none !important;
            }
        }

        .barcode-item {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            background: white;
            border-radius: 5px;
        }

        .barcode-item h6 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .barcode-item .barcode-code {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: bold;
            margin-top: 8px;
            margin-bottom: 5px;
        }

        .barcode-item .barcode-price {
            font-size: 13px;
            font-weight: bold;
            color: #28a745;
        }

        .search-result-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }

        .search-result-item:hover {
            background-color: #f8f9fa;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: 600;
            color: #333;
        }

        .product-sku {
            color: #666;
            font-size: 0.9em;
        }

        .product-price {
            color: #28a745;
            font-weight: 500;
        }

        .product-stock {
            color: #17a2b8;
            font-size: 0.85em;
        }
    </style>

    {{-- JavaScript --}}
    <script>
        $(document).ready(function() {
            let selectedBatch = null;
            let searchTimeout = null;

            // Product Search
            $('#productSearch').on('input', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(searchTimeout);

                if (searchTerm.length < 1) {
                    $('#searchDropdown').hide();
                    return;
                }

                searchTimeout = setTimeout(function() {
                    searchProducts(searchTerm);
                }, 300);
            });

            // Search Products Function
            function searchProducts(term) {
                $.ajax({
                    url: '{{ route('barcode.search') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { search: term },
                    success: function(response) {
                        if (response.status === 200) {
                            displaySearchResults(response.products);
                        }
                    },
                    error: function(xhr) {
                        console.error('Search error:', xhr);
                        toastr.error('Error searching products');
                    }
                });
            }

            // Display Search Results with Batches
            function displaySearchResults(products) {
                const dropdown = $('#searchDropdown');
                dropdown.empty();

                if (products.length === 0) {
                    dropdown.html('<div class="p-3 text-muted text-center">No products found</div>');
                    dropdown.show();
                    return;
                }

                products.forEach(function(product) {
                    // Product Header
                    const productHeader = $(`
                        <div class="p-2 bg-light border-bottom">
                            <strong>${product.product_name}</strong> <small class="text-muted">(SKU: ${product.sku})</small>
                        </div>
                    `);
                    dropdown.append(productHeader);

                    // Batch Items
                    if (product.batches && product.batches.length > 0) {
                        product.batches.forEach(function(batch) {
                            const expiryText = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString() : 'N/A';
                            const item = $(`
                                <div class="search-result-item ps-4" data-batch='${JSON.stringify(batch)}' data-product='${JSON.stringify(product)}'>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="product-name">Batch: ${batch.batch_no || 'N/A'}</div>
                                            <div class="product-sku">Stock: ${batch.quantity} ${product.unit}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="product-price" style="font-size: 0.8em;">
                                                CP: ${batch.cost_price} | WP: ${batch.wholesale_price} | SP: ${batch.special_price} | RP: ${batch.retail_price}
                                            </div>
                                            <div class="product-stock">Expiry: ${expiryText}</div>
                                        </div>
                                    </div>
                                </div>
                            `);

                            dropdown.append(item);
                        });
                    } else {
                        dropdown.append('<div class="p-2 ps-4 text-muted">No batches with stock</div>');
                    }
                });

                dropdown.show();
            }

            // Select Batch from Dropdown
            $(document).on('click', '.search-result-item', function() {
                selectedBatch = JSON.parse($(this).attr('data-batch'));
                const product = JSON.parse($(this).attr('data-product'));

                $('#selectedProductName').text(product.product_name);
                $('#selectedBatchNo').text(selectedBatch.batch_no || 'N/A');
                $('#selectedProductSku').text(selectedBatch.sku);
                $('#selectedCP').text(selectedBatch.cost_price);
                $('#selectedWP').text(selectedBatch.wholesale_price);
                $('#selectedSP').text(selectedBatch.special_price);
                $('#selectedRP').text(selectedBatch.retail_price);
                $('#selectedMRP').text(selectedBatch.max_retail_price);
                $('#selectedStock').text(selectedBatch.quantity);
                $('#selectedExpiry').text(selectedBatch.expiry_date ? new Date(selectedBatch.expiry_date).toLocaleDateString() : 'N/A');
                $('#selectedBatchInfo').show();

                $('#productSearch').val('');
                $('#searchDropdown').hide();
                $('#generateBtn').prop('disabled', false);
            });

            // Clear Selection
            $('#clearSelection').on('click', function() {
                selectedBatch = null;
                $('#selectedBatchInfo').hide();
                $('#generateBtn').prop('disabled', true);
                $('#productSearch').val('').focus();
            });

            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#productSearch, #searchDropdown').length) {
                    $('#searchDropdown').hide();
                }
            });

            // Generate Barcodes
            $('#generateBtn').on('click', function() {
                if (!selectedBatch) {
                    toastr.error('Please select a batch first');
                    return;
                }

                const quantity = parseInt($('#barcodeQuantity').val());
                const priceType = $('#priceType').val();

                if (quantity < 1 || quantity > 100) {
                    toastr.error('Quantity must be between 1 and 100');
                    return;
                }

                $.ajax({
                    url: '{{ route('barcode.generate') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        batch_id: selectedBatch.id,
                        quantity: quantity,
                        price_type: priceType
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            displayBarcodes(response.barcodes);
                            toastr.success('Barcodes generated successfully!');
                        }
                    },
                    error: function(xhr) {
                        console.error('Generation error:', xhr);
                        const message = xhr.responseJSON?.message || 'Error generating barcodes';
                        toastr.error(message);
                    }
                });
            });

            // Display Generated Barcodes
            function displayBarcodes(barcodes) {
                const container = $('#barcodesContainer');
                container.empty();

                barcodes.forEach(function(barcode, index) {
                    const barcodeHtml = $(`
                        <div class="col-md-4 col-lg-3">
                            <div class="barcode-item">
                                <h6>${barcode.product_name}</h6>
                                <small class="text-muted">Batch: ${barcode.batch_no}</small>
                                <div>${barcode.barcode_html}</div>
                                <div class="barcode-code">${barcode.sku}</div>
                                <div class="barcode-price">${barcode.price}</div>
                                <small class="text-muted">(${barcode.price_type})</small>
                            </div>
                        </div>
                    `);

                    container.append(barcodeHtml);
                });

                $('#barcodeDisplaySection').show();

                // Scroll to barcodes section
                $('html, body').animate({
                    scrollTop: $('#barcodeDisplaySection').offset().top - 100
                }, 500);
            }

            // Print Barcodes
            $('#printBtn').on('click', function() {
                window.print();
            });
        });
    </script>
@endsection
