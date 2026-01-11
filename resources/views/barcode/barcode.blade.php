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
                            <div class="col-md-4">
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
                                    {{-- <option value="retail_price">Retail Price</option> --}}
                                    <option value="max_retail_price">Max Retail</option>
                                </select>
                            </div>

                            {{-- Quantity Input --}}
                            <div class="col-md-2">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="barcodeQuantity"
                                       value="6" min="1" max="500" placeholder="Qty">
                            </div>

                            {{-- Label Size Selection --}}
                            <div class="col-md-2">
                                <label class="form-label">Label Size</label>
                                <select class="form-control" id="labelSize">
                                    <option value="a4">A4 Sheet (6/row)</option>
                                    <option value="50x30">50 x 30 mm</option>
                                    <option value="50x25">50 x 25 mm</option>
                                    <option value="40x30">40 x 30 mm</option>
                                    <option value="40x25">40 x 25 mm</option>
                                    <option value="38x25">38 x 25 mm</option>
                                    <option value="34x25x3" selected>34 x 25 mm x 3</option>
                                </select>
                            </div>

                            {{-- Generate Button --}}
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success w-100" id="generateBtn" disabled>
                                    <i class="fas fa-barcode me-2"></i>GENERATE
                                </button>
                            </div>
                        </div>

                        {{-- Display Options --}}
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Display Options:</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showBatch" checked>
                                        <label class="form-check-label" for="showBatch">Show Batch Number</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showPrice" checked>
                                        <label class="form-check-label" for="showPrice">Show Price</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showSKU" checked>
                                        <label class="form-check-label" for="showSKU">Show SKU</label>
                                    </div>
                                </div>
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

    {{-- Styles --}}
    <style>
        .barcode-item {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            background: white;
            border-radius: 4px;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }

        .barcode-item:hover {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .barcode-item h6 {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
            line-height: 1.2;
            width: 100%;
        }

        .barcode-item small {
            font-size: 9px;
        }

        .barcode-item > div {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .barcode-item .barcode-code {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            margin: 4px 0;
        }

        .barcode-item .barcode-price {
            font-size: 14px;
            font-weight: bold;
            color: #28a745;
            margin: 3px 0;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .barcode-item {
                min-height: 160px;
                padding: 8px;
            }
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
                }, 150);
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
                // $('#selectedCP').text(selectedBatch.cost_price);
                // $('#selectedWP').text(selectedBatch.wholesale_price);
                // $('#selectedSP').text(selectedBatch.special_price);
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

                // Store barcodes data for printing
                window.barcodesData = barcodes;

                barcodes.forEach(function(barcode, index) {
                    const barcodeHtml = $(`
                        <div class="col-6 col-md-4 col-lg-3 mb-2">
                            <div class="barcode-item">
                                <h6 class="text-truncate" title="${barcode.product_name}">${barcode.product_name}</h6>
                                <small class="text-muted d-block">Batch: ${barcode.batch_no}</small>
                                <div class="d-flex justify-content-center align-items-center my-2" style="min-height: 40px;">
                                    ${barcode.barcode_html}
                                </div>
                                <div class="barcode-code">${barcode.sku}</div>
                                <div class="barcode-price">${barcode.price}</div>

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

            // Label size configurations
            const labelSizes = {
                'a4': { page: 'A4', margin: '2mm', width: '32mm', height: 'auto', gap: '1.5mm', svgW: '28mm', svgH: '8mm', wrap: true, perRow: false },
                '50x30': { page: '50mm 30mm', margin: '1mm', width: '48mm', height: '28mm', gap: '0', svgW: '45mm', svgH: '10mm', wrap: false, perRow: false },
                '50x25': { page: '50mm 25mm', margin: '1mm', width: '48mm', height: '23mm', gap: '0', svgW: '45mm', svgH: '8mm', wrap: false, perRow: false },
                '40x30': { page: '40mm 30mm', margin: '1mm', width: '38mm', height: '28mm', gap: '0', svgW: '35mm', svgH: '10mm', wrap: false, perRow: false },
                '40x25': { page: '40mm 25mm', margin: '1mm', width: '38mm', height: '23mm', gap: '0', svgW: '35mm', svgH: '8mm', wrap: false, perRow: false },
                '38x25': { page: '38mm 25mm', margin: '1mm', width: '36mm', height: '23mm', gap: '0', svgW: '33mm', svgH: '8mm', wrap: false, perRow: false },
                '34x25x3': { page: '4.634in auto', margin: '0', width: '1.378in', height: '0.984in', gap: '0.1in', svgW: '1.2in', svgH: '0.3in', wrap: false, perRow: 3 }
            };

            // Print Barcodes - Using hidden iframe (same page, no new window)
            $('#printBtn').on('click', function() {
                if (!window.barcodesData || window.barcodesData.length === 0) {
                    toastr.error('No barcodes to print');
                    return;
                }

                // Remove old iframe if exists
                $('#printFrame').remove();

                // Get selected label size
                var labelSize = $('#labelSize').val();
                var config = labelSizes[labelSize];

                // Get display options
                var showBatch = $('#showBatch').is(':checked');
                var showPrice = $('#showPrice').is(':checked');
                var showSKU = $('#showSKU').is(':checked');

                // Build print content
                let printContent = '';

                // For 3-in-1 row layout
                if (config.perRow === 3) {
                    let rowContent = '';
                    window.barcodesData.forEach(function(barcode, index) {
                        rowContent += '<div class="b">' +
                            '<div class="n">' + barcode.product_name + '</div>' +
                            (showBatch ? '<div class="t">' + barcode.batch_no + '</div>' : '') +
                            '<div class="c">' + barcode.barcode_html + '</div>' +
                            (showSKU ? '<div class="s">' + barcode.sku + '</div>' : '') +
                            (showPrice ? '<div class="p">' + barcode.price + '</div>' : '') +

                        '</div>';

                        // After every 3 labels or at the end, wrap in a row
                        if ((index + 1) % 3 === 0 || index === window.barcodesData.length - 1) {
                            printContent += '<div class="r">' + rowContent + '</div>';
                            rowContent = '';
                        }
                    });
                } else {
                    // Regular layout
                    window.barcodesData.forEach(function(barcode) {
                        printContent += '<div class="b">' +
                            '<div class="n">' + barcode.product_name + '</div>' +
                            (showBatch ? '<div class="t">' + barcode.batch_no + '</div>' : '') +
                            '<div class="c">' + barcode.barcode_html + '</div>' +
                            (showSKU ? '<div class="s">' + barcode.sku + '</div>' : '') +
                            (showPrice ? '<div class="p">' + barcode.price + '</div>' : '') +

                        '</div>';
                    });
                }

                // Create hidden iframe
                var iframe = document.createElement('iframe');
                iframe.id = 'printFrame';
                iframe.style.position = 'absolute';
                iframe.style.top = '-9999px';
                iframe.style.left = '-9999px';
                iframe.style.width = '210mm';
                iframe.style.height = '297mm';
                document.body.appendChild(iframe);

                // Build CSS based on label size
                var css = '*{margin:0;padding:0;box-sizing:border-box}';
                css += '@page{size:' + config.page + ';margin:' + config.margin + '}';
                css += 'body{font-family:Arial,sans-serif;padding:0}';

                if (config.perRow === 3) {
                    // 3-in-1 row layout (like your demo HTML)
                    css += '.g{display:block}';
                    css += '.r{display:flex;gap:' + config.gap + ';margin-left:' + config.gap + ';margin-right:' + config.gap + ';margin-bottom:0.2in;page-break-inside:avoid;page-break-after:avoid}';
                    css += '.b{width:' + config.width + ';height:' + config.height + ';display:flex;flex-direction:column;align-items:center;justify-content:center;box-sizing:border-box;padding:0.5mm 1mm;text-align:center}';
                } else if (config.wrap) {
                    // A4 sheet - multiple labels per page
                    css += '.g{display:flex;flex-wrap:wrap;gap:' + config.gap + ';justify-content:center}';
                    css += '.b{width:' + config.width + ';border:1px dashed #ccc;padding:1mm;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}';
                } else {
                    // Individual label printer - one label per page
                    css += '.g{display:block}';
                    css += '.b{width:' + config.width + ';height:' + config.height + ';border:none;padding:0.5mm 1mm;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;page-break-after:always}';
                }

                css += '.n{font-size:7pt;font-weight:bold;width:100%;text-align:center;line-height:1.2;word-wrap:break-word;overflow-wrap:break-word;margin-bottom:0.5mm}';
                css += '.t{font-size:6pt;font-weight:bold;color:#000;text-align:center;margin-bottom:0.3mm}';
                css += '.c{display:flex;justify-content:center;align-items:center;width:100%;margin:0}';
                css += '.c svg{max-width:' + config.svgW + ';height:' + config.svgH + ';display:block}';
                css += '.s{font-size:7pt;font-weight:bold;font-family:monospace;text-align:center;margin-top:0.3mm}';
                css += '.p{font-size:9pt;font-weight:bold;color:#000;text-align:center;margin-top:0.5mm}';
                css += '.y{font-size:5pt;color:#666;text-align:center;margin-top:0.2mm}';
                css += '@media print{.b{border:none}}'; // Hide borders in final print

                // Write content to iframe
                var doc = iframe.contentWindow.document;
                doc.open();
                doc.write('<!DOCTYPE html><html><head><title>Print</title>');
                doc.write('<style>' + css + '</style></head><body>');
                doc.write('<div class="g">' + printContent + '</div>');
                doc.write('</body></html>');
                doc.close();

                // Wait for iframe to load then print
                iframe.onload = function() {
                    setTimeout(function() {
                        iframe.contentWindow.focus();
                        iframe.contentWindow.print();
                    }, 100);
                };
            });
        });
    </script>
@endsection
