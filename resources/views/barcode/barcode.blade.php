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
                                    <div id="searchDropdown" class="barcode-search-dropdown position-absolute w-100"
                                         style="display: none; z-index: 1000; top: 100%; margin-top: 4px;">
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
                                <select class="form-control" id="labelSize" title="Your choice is saved in this browser until you change it">
                                    <option value="a4">A4 Sheet (6/row)</option>
                                    <option value="50x30">50 x 30 mm</option>
                                    <option value="50x25">50 x 25 mm</option>
                                    <option value="40x30">40 x 30 mm</option>
                                    <option value="40x25">40 x 25 mm</option>
                                    <option value="40x20x2">40 × 17 mm × 2 — 88 mm roll (8.8 cm)</option>
                                    <option value="38x25">38 x 25 mm</option>
                                    <option value="38x25x3">38 × 25 mm × 3 — 112 mm roll (mm)</option>
                                    <option value="34x25x3">34 × 25 mm × 3 — legacy (in, older printers / systems)</option>
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
                                        <input class="form-check-input" type="checkbox" id="showBatch">
                                        <label class="form-check-label" for="showBatch">Show Batch Number</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showPrice">
                                        <label class="form-check-label" for="showPrice">Show Price</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showSKU" checked>
                                        <label class="form-check-label" for="showSKU">Show SKU</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="showLocation" checked>
                                        <label class="form-check-label" for="showLocation">Show Location</label>
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

        /* Product search dropdown — minimal, readable */
        .barcode-search-dropdown {
            max-height: 380px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e8eaed;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08), 0 2px 8px rgba(15, 23, 42, 0.04);
            padding: 6px;
        }

        .barcode-search-dropdown__empty {
            padding: 1rem 1.25rem;
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .search-result-item {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        .barcode-search-dropdown__tip {
            font-size: 0.72rem;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.45rem 0.6rem;
            margin-bottom: 0.5rem;
            line-height: 1.35;
        }

        .barcode-search-dropdown__tip i {
            margin-right: 0.35rem;
            color: #0d6efd;
        }

        .barcode-search-group .barcode-search-group__batch:last-child {
            border-bottom: none;
        }

        .barcode-search-group {
            border: 1px solid #eef0f3;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            overflow: hidden;
            background: #fff;
        }

        .barcode-search-group:last-child {
            margin-bottom: 0;
        }

        .barcode-search-group__head {
            padding: 0.5rem 0.75rem 0.45rem;
            background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #eef0f3;
            pointer-events: none;
            user-select: none;
        }

            font-size: 0.8125rem;
            font-weight: 600;
            color: #212529;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .barcode-search-group__sku-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: 0.15rem;
        }

        .barcode-search-group__sku {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
            letter-spacing: 0.02em;
        }

        .barcode-search-group__count {
            font-size: 0.65rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .barcode-search-group__batch {
            padding: 0;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 0;
            border-left: 3px solid transparent;
            transition: background-color 0.15s ease, border-left-color 0.15s ease;
        }

        .barcode-search-group__batch:hover,
        .barcode-search-group__batch:focus {
            background-color: #f0f7ff;
            border-left-color: #0d6efd;
        }

        .barcode-search-group__batch:focus {
            outline: none;
        }

        .barcode-search-group__batch:focus-visible {
            outline: 2px solid #0d6efd;
            outline-offset: -2px;
        }

        .barcode-search-group__batch-main {
            flex: 1;
            min-width: 0;
            padding: 0.55rem 0.5rem 0.55rem 0.65rem;
            display: flex;
            align-items: center;
        }

        .barcode-search-group__batch-action {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.55rem 0.65rem 0.55rem 0.35rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            white-space: nowrap;
            border-left: 1px solid #eef0f3;
            background: #fafbfc;
            transition: color 0.15s ease, background 0.15s ease;
        }

        .barcode-search-group__batch:hover .barcode-search-group__batch-action,
        .barcode-search-group__batch:focus .barcode-search-group__batch-action {
            color: #0d6efd;
            background: #e8f2fe;
        }

        .barcode-search-group__batch-action i {
            font-size: 0.65rem;
            opacity: 0.9;
        }

        .barcode-search-group__batch .barcode-search-hit__meta {
            font-size: 0.75rem;
            color: #495057;
        }

        .barcode-search-hit__meta {
            font-size: 0.75rem;
            color: #6c757d;
            line-height: 1.45;
        }

        .badge {
            font-weight: 500;
            font-size: 0.85em;
        }
    </style>

    {{-- JavaScript --}}
    <script>
        $(document).ready(function() {
            let selectedBatch = null;
            let searchTimeout = null;
            let currentRequest = null; // Track current AJAX request

            (function initLabelSizePreference() {
                var key = 'marazin_barcode_label_size';
                var $sel = $('#labelSize');
                var saved = null;
                try {
                    saved = localStorage.getItem(key);
                } catch (e) {}
                if (saved && $sel.find('option[value="' + saved + '"]').length) {
                    $sel.val(saved);
                } else {
                    $sel.val('40x20x2');
                }
                $sel.on('change', function () {
                    try {
                        localStorage.setItem(key, $(this).val());
                    } catch (e) {}
                });
            })();

            // Product Search with optimized debouncing
            $('#productSearch').on('input', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(searchTimeout);

                // Cancel previous request if still pending
                if (currentRequest) {
                    currentRequest.abort();
                }

                if (searchTerm.length < 1) {
                    $('#searchDropdown').hide();
                    return;
                }

                // Show loading indicator
                $('#searchDropdown').html('<div class="barcode-search-dropdown__empty"><i class="fas fa-spinner fa-spin me-2 text-secondary"></i>Searching…</div>').show();

                searchTimeout = setTimeout(function() {
                    searchProducts(searchTerm);
                }, 250); // Increased to 250ms for better performance
            });

            // Search Products Function with caching
            const searchCache = {};
            function searchProducts(term) {
                // Check cache first
                if (searchCache[term]) {
                    displaySearchResults(searchCache[term]);
                    return;
                }

                currentRequest = $.ajax({
                    url: '{{ route('barcode.search') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { search: term },
                    success: function(response) {
                        currentRequest = null;
                        if (response.status === 200) {
                            searchCache[term] = response.products; // Cache results
                            displaySearchResults(response.products);
                        }
                    },
                    error: function(xhr) {
                        currentRequest = null;
                        if (xhr.statusText !== 'abort') { // Don't show error for aborted requests
                            console.error('Search error:', xhr);
                            toastr.error('Error searching products');
                        }
                    }
                });
            }

            // Display Search Results with Batches
            function displaySearchResults(products) {
                const dropdown = $('#searchDropdown');
                dropdown.empty();

                if (products.length === 0) {
                    dropdown.html('<div class="barcode-search-dropdown__empty">No products found</div>');
                    dropdown.show();
                    return;
                }

                var anyBatches = products.some(function(p) { return p.batches && p.batches.length > 0; });
                if (anyBatches) {
                    dropdown.append(
                        $('<div class="barcode-search-dropdown__tip" role="status"></div>').html(
                            '<i class="fas fa-info-circle" aria-hidden="true"></i>Gray header = product info (not clickable). <strong>Click a batch line</strong> or the <strong>Select</strong> area to choose that stock for barcodes.'
                        )
                    );
                }

                products.forEach(function(product) {
                    if (product.batches && product.batches.length > 0) {
                        const $group = $('<div class="barcode-search-group" role="group"></div>');
                        const $head = $('<div class="barcode-search-group__head"></div>');
                        $head.append($('<div class="barcode-search-group__title"></div>').text(product.product_name));
                        const $skuRow = $('<div class="barcode-search-group__sku-row"></div>');
                        if (product.sku) {
                            $skuRow.append($('<span class="barcode-search-group__sku"></span>').text('SKU ' + product.sku));
                        }
                        if (product.batches.length > 1) {
                            $skuRow.append($('<span class="barcode-search-group__count"></span>').text(product.batches.length + ' batches'));
                        }
                        $head.append($skuRow);
                        $group.append($head);

                        product.batches.forEach(function(batch) {
                            const expiryText = batch.expiry_date ? new Date(batch.expiry_date).toLocaleDateString() : 'N/A';
                            const unit = product.unit || '';
                            const metaParts = [
                                batch.batch_no || '—',
                                'Stock ' + batch.quantity + (unit ? ' ' + unit : ''),
                                'MRP Rs. ' + batch.max_retail_price,
                                'Exp ' + expiryText
                            ];
                            const batchLabel = String(batch.batch_no || 'batch');
                            const $item = $(`
                                <div class="search-result-item barcode-search-group__batch" role="option" tabindex="0">
                                    <div class="barcode-search-group__batch-main">
                                        <div class="barcode-search-hit__meta"></div>
                                    </div>
                                    <span class="barcode-search-group__batch-action">
                                        <span class="barcode-search-group__select-label">Select</span>
                                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                    </span>
                                </div>
                            `);
                            $item.find('.barcode-search-hit__meta').text(metaParts.join(' · '));
                            $item.attr('aria-label', 'Select batch ' + batchLabel);
                            $item.data('batch', batch);
                            $item.data('product', product);
                            $group.append($item);
                        });
                        dropdown.append($group);
                    } else {
                        const $empty = $('<div class="barcode-search-dropdown__empty py-2 border-bottom small"></div>');
                        $empty.text('No stock — ' + (product.product_name || 'Product'));
                        $empty.css('border-color', '#f1f3f5');
                        dropdown.append($empty);
                    }
                });

                dropdown.show();
            }

            // Select Batch from Dropdown
            $(document).on('click', '.search-result-item.barcode-search-group__batch', function() {
                var $row = $(this);
                selectedBatch = $row.data('batch');
                const product = $row.data('product');
                if (!selectedBatch || !product) {
                    toastr.error('Invalid selection — please search again');
                    return;
                }

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

            $(document).on('keydown', '.search-result-item.barcode-search-group__batch', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
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
                                ${barcode.location_name ? '<small class="text-info d-block">Location: ' + barcode.location_name + '</small>' : ''}
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

            // Label size configurations - Compact barcode heights
            // 40x20x2: @page 17mm = sticker face only (not inter-label gap). Inner pad 1+1mm → center band ~15mm tall.
            const labelSizes = {
                'a4': { page: 'A4', margin: '2mm', width: '32mm', height: 'auto', gap: '1.5mm', svgW: '28mm', svgH: '4.5mm', wrap: true, perRow: false },
                '50x30': { page: '50mm 30mm', margin: '1mm', width: '48mm', height: '28mm', gap: '0', svgW: '45mm', svgH: '4.5mm', wrap: false, perRow: false },
                '50x25': { page: '50mm 25mm', margin: '1mm', width: '48mm', height: '23mm', gap: '0', svgW: '45mm', svgH: '4.5mm', wrap: false, perRow: false },
                '40x30': { page: '40mm 30mm', margin: '1mm', width: '38mm', height: '28mm', gap: '0', svgW: '35mm', svgH: '4.5mm', wrap: false, perRow: false },
                '40x25': { page: '40mm 25mm', margin: '1mm', width: '38mm', height: '23mm', gap: '0', svgW: '35mm', svgH: '4.5mm', wrap: false, perRow: false },
                '40x20x2': { page: '88mm 17mm', margin: '0mm', rollLabelHeight: '17mm', width: '38mm', height: '100%', gap: '2mm', svgW: '38mm', svgH: '3.8mm', wrap: false, perRow: 2, colWidth: '40mm', rowFlexJustify: 'flex-start', rowAlignItems: 'stretch', bodyPadding: '0 1mm', labelInnerPadding: '1mm 0.55mm 1mm', cellJustify: 'center', pageBreakRows: true, nameMaxWidth: '36mm', nameLineClamp: 2 },
                '38x25': { page: '38mm 25mm', margin: '1mm', width: '36mm', height: '23mm', gap: '0', svgW: '33mm', svgH: '4.5mm', wrap: false, perRow: false },
                // 112 mm × 25 mm per row; 3 columns ≈ 37.33 mm each (112 ÷ 3, gap 0). Smaller SVG + rollTextTight → less overflow.
                // If roll has gutters: widen page and gap (e.g. 118 mm, gap 2 mm) and recompute colWidth.
                '38x25x3': { page: '112mm 25mm', margin: '0mm', rollLabelHeight: '25mm', width: '37.33mm', height: '100%', gap: '0mm', svgW: '28mm', svgH: '4.7mm', wrap: false, perRow: 3, colWidth: '37.33mm', rowFlexJustify: 'flex-start', rowAlignItems: 'stretch', bodyPadding: '0', labelInnerPadding: '0.15mm 0.35mm 0.15mm', cellJustify: 'center', pageBreakRows: true, rollTextTight: true, nameMaxWidth: '33mm', nameLineClamp: 3, tightTypeSizes: { n: '6.2pt', t: '5.4pt', s: '6pt', p: '6.6pt', y: '6pt', lineN: '1.06' } },
                // Legacy: inch-based 3-up (~35 mm cell); keep for older installs / saved presets that still pass value "34x25x3".
                '34x25x3': { page: '4.634in auto', margin: '0', width: '1.378in', height: '0.984in', gap: '0.1in', svgW: '1.2in', svgH: '0.2in', wrap: false, perRow: 3, rowFlexJustify: 'space-between' }

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
                var showLocation = $('#showLocation').is(':checked');

                // Build print content
                let printContent = '';

                // If config.perRow is a number > 1, build rows grouping that many labels per row
                if (typeof config.perRow === 'number' && config.perRow > 1) {
                    let rowContent = '';
                    window.barcodesData.forEach(function(barcode, index) {
                        var inner = '<div class="n">' + barcode.product_name + '</div>' +
                            (showBatch ? '<div class="t">' + barcode.batch_no + '</div>' : '') +
                            '<div class="c">' + barcode.barcode_html + '</div>' +
                            (showSKU ? '<div class="s">' + barcode.sku + '</div>' : '') +
                            (showPrice ? '<div class="p">' + barcode.price + '</div>' : '') +
                            (showLocation && barcode.location_name ? '<div class="y">' + barcode.location_name + '</div>' : '');
                        rowContent += '<div class="b">';
                        if (config.pageBreakRows) {
                            rowContent += '<div class="barcode-roll-stack">';
                        }
                        rowContent += inner;
                        if (config.pageBreakRows) {
                            rowContent += '</div>';
                        }
                        rowContent += '</div>';

                        // After every `perRow` labels or at the end, wrap in a row
                        if (((index + 1) % config.perRow === 0) || index === window.barcodesData.length - 1) {
                            printContent += '<div class="r">' + rowContent + '</div>';
                            rowContent = '';
                        }
                    });
                } else {
                    // Regular (single label per row) layout
                    window.barcodesData.forEach(function(barcode) {
                        printContent += '<div class="b">' +
                            '<div class="n">' + barcode.product_name + '</div>' +
                            (showBatch ? '<div class="t">' + barcode.batch_no + '</div>' : '') +
                            '<div class="c">' + barcode.barcode_html + '</div>' +
                            (showSKU ? '<div class="s">' + barcode.sku + '</div>' : '') +
                            (showPrice ? '<div class="p">' + barcode.price + '</div>' : '') +
                            (showLocation && barcode.location_name ? '<div class="y">' + barcode.location_name + '</div>' : '') +
                        '</div>';
                    });
                }

                // Create hidden iframe (size to label page when known — helps print preview)
                var iframe = document.createElement('iframe');
                iframe.id = 'printFrame';
                iframe.style.position = 'absolute';
                iframe.style.top = '-9999px';
                iframe.style.left = '-9999px';
                var pageBox = (function() {
                    var p = String(config.page || '').trim().split(/\s+/);
                    if (p.length >= 2 && p[1] && p[1].toLowerCase() !== 'auto') {
                        return { w: p[0], h: p[1] };
                    }
                    return null;
                })();
                // Thermal roll: optional rollLabelHeight = die-cut label FACE (e.g. 17mm). Do not include media gap between labels here — that is the printer’s job.
                var rollFaceH = (pageBox && config.rollLabelHeight != null) ? config.rollLabelHeight : (pageBox ? pageBox.h : null);
                // Vertical alignment inside each sticker cell: roll presets default to center so content isn’t stuck to the top with empty space below (fixes preview + print look vs physical label).
                var labelCellJustify = config.cellJustify != null ? config.cellJustify : ((pageBox && config.pageBreakRows && typeof config.perRow === 'number' && config.perRow > 1) ? 'center' : 'flex-start');
                if (pageBox && config.pageBreakRows && window.barcodesData.length && typeof config.perRow === 'number') {
                    var rows = Math.ceil(window.barcodesData.length / config.perRow);
                    iframe.style.width = pageBox.w;
                    iframe.style.height = 'calc(' + rows + ' * ' + rollFaceH + ')';
                } else {
                    iframe.style.width = '210mm';
                    iframe.style.height = '297mm';
                }
                document.body.appendChild(iframe);

                // Build CSS based on label size
                var css = '*{margin:0;padding:0;box-sizing:border-box}';
                css += '@page{size:' + config.page + ';margin:' + config.margin + '}';
                css += '@media print{@page{size:' + config.page + ';margin:' + config.margin + '}}';
                css += 'body{font-family:Arial,sans-serif;padding:' + (config.bodyPadding != null ? config.bodyPadding : '0') + '}';

                if (typeof config.perRow === 'number' && config.perRow > 1) {
                    // Multi-column row layout (supports perRow = 2,3,...)
                    css += '.g{display:block}';
                    var rowJustify = config.rowFlexJustify || 'flex-start';
                    var rowAlign = config.rowAlignItems != null ? config.rowAlignItems : 'flex-start';
                    css += '.r{display:flex;justify-content:' + rowJustify + ';align-items:' + rowAlign + ';gap:' + config.gap + ';width:100%;margin-bottom:0;page-break-inside:avoid}';
                    if (config.pageBreakRows) {
                        css += '.r:not(:last-child){page-break-after:always}';
                    } else {
                        css += '.r{margin-bottom:0.5mm;page-break-after:avoid}';
                    }
                    // Each label column width can be provided by config.colWidth or computed from config.width
                    var colW = config.colWidth ? config.colWidth : ('calc((' + config.width + ' - ' + config.gap + ' * ' + (config.perRow - 1) + ') / ' + config.perRow + ')');
                    var cellH = (pageBox && config.pageBreakRows) ? '100%' : config.height;
                    var colLock = config.colWidth ? (';width:' + colW + ';max-width:' + colW + ';min-width:' + colW) : '';
                    var innerPad = config.labelInnerPadding != null ? config.labelInnerPadding : '0.1mm 1mm';
                    css += '.b{flex:0 0 ' + colW + colLock + ';height:' + cellH + ';min-height:0;display:flex;flex-direction:column;align-items:center;justify-content:' + labelCellJustify + ';box-sizing:border-box;padding:' + innerPad + ';text-align:center;overflow:hidden}';
                    // Slightly reduce text sizes for small labels (overridden below for .r — keep for non-pageBox perRow)
                    css += '.n{font-size:6pt;margin-bottom:0.2mm}';
                    css += '.s{font-size:6.5pt;margin-top:0.2mm}';
                    css += '.p{font-size:8pt;margin-top:0.3mm}';
                } else if (config.wrap) {
                    // A4 sheet - multiple labels per page
                    css += '.g{display:flex;flex-wrap:wrap;gap:' + config.gap + ';justify-content:center}';
                    css += '.b{width:' + config.width + ';border:1px dashed #ccc;padding:1mm;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}';
                } else {
                    // Individual label printer - one label per page
                    css += '.g{display:block}';
                    css += '.b{width:' + config.width + ';height:' + config.height + ';border:none;padding:0.5mm 1mm;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;page-break-after:always}';
                }

                var labelRowCount = 0;
                if (pageBox && config.pageBreakRows && typeof config.perRow === 'number' && window.barcodesData.length) {
                    labelRowCount = Math.ceil(window.barcodesData.length / config.perRow);
                }

                var totalDocHeightExpr = '';
                if (pageBox && config.pageBreakRows && labelRowCount > 0) {
                    totalDocHeightExpr = 'calc(' + labelRowCount + ' * ' + rollFaceH + ')';
                }

                if (pageBox && config.pageBreakRows) {
                    css += 'html{margin:0;padding:0;width:' + pageBox.w + ';max-width:' + pageBox.w + ';background:#fff;box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}';
                    css += 'body{margin:0!important;max-width:' + pageBox.w + '!important;width:' + pageBox.w + '!important;box-sizing:border-box;}';
                    css += '.g{width:100%;max-width:' + pageBox.w + ';margin:0;display:block;}';
                    css += '.r{height:' + rollFaceH + '!important;min-height:' + rollFaceH + '!important;max-height:' + rollFaceH + '!important;overflow:hidden!important;box-sizing:border-box;}';
                    if (typeof config.perRow === 'number' && config.perRow > 1) {
                        css += '.r{justify-content:flex-start!important;gap:' + config.gap + '!important;flex-wrap:nowrap!important;}';
                        if (config.colWidth) {
                            css += '.b{flex:0 0 ' + config.colWidth + '!important;min-width:' + config.colWidth + '!important;max-width:' + config.colWidth + '!important;flex-grow:0!important;justify-content:' + labelCellJustify + '!important;}';
                        }
                    }
                    if (totalDocHeightExpr) {
                        css += 'html{height:' + totalDocHeightExpr + '!important;max-height:' + totalDocHeightExpr + '!important;min-height:' + totalDocHeightExpr + '!important;overflow:hidden!important}';
                        css += 'body{height:' + totalDocHeightExpr + '!important;max-height:' + totalDocHeightExpr + '!important;min-height:' + totalDocHeightExpr + '!important;overflow:hidden!important}';
                    }
                    css += '@media print{';
                    css += '@page{size:' + config.page + ';margin:' + config.margin + ' !important}';
                    css += 'html,body{width:' + pageBox.w + '!important;max-width:' + pageBox.w + '!important;margin:0!important;padding:' + (config.bodyPadding != null ? config.bodyPadding : '0') + '!important;';
                    if (totalDocHeightExpr) {
                        css += 'height:' + totalDocHeightExpr + '!important;max-height:' + totalDocHeightExpr + '!important;min-height:' + totalDocHeightExpr + '!important;overflow:hidden!important;';
                    }
                    css += '}';
                    if (typeof config.perRow === 'number' && config.perRow > 1) {
                        css += '.r{justify-content:flex-start!important;gap:' + config.gap + '!important;flex-wrap:nowrap!important;}';
                        if (config.colWidth) {
                            css += '.b{flex:0 0 ' + config.colWidth + '!important;min-width:' + config.colWidth + '!important;max-width:' + config.colWidth + '!important;flex-grow:0!important;justify-content:' + labelCellJustify + '!important;}';
                        }
                    }
                    css += '}';
                }

                css += '.n{font-size:7pt;font-weight:bold;width:100%;text-align:center;line-height:1.2;word-wrap:break-word;overflow-wrap:break-word;margin-bottom:0.5mm}';
                css += '.t{font-size:6pt;font-weight:bold;color:#000;text-align:center;margin-bottom:0.3mm}';
                css += '.c{display:flex;justify-content:center;align-items:center;width:100%;margin:0}';
                css += '.c svg{max-width:' + config.svgW + ';height:' + config.svgH + ';display:block}';
                css += '.s{font-size:7pt;font-weight:bold;font-family:monospace;text-align:center;margin-top:0.3mm}';
                css += '.p{font-size:9pt;font-weight:bold;color:#000;text-align:center;margin-top:0.5mm}';
                css += '.y{font-size:7pt;font-weight:900;color:#000;text-align:center;margin-top:0.4mm;letter-spacing:0.2px;width:100%;line-height:1.1;word-wrap:break-word;overflow-wrap:break-word;white-space:normal}';
                css += '@media print{.b{border:none}}'; // Hide borders in final print

                // Roll 17mm: .b flex-centers .barcode-roll-stack; padding from labelInnerPadding (overrides any inherited .b padding).
                if (typeof config.perRow === 'number' && config.perRow > 1 && pageBox && config.pageBreakRows) {
                    var rollPad = config.labelInnerPadding != null ? config.labelInnerPadding : '1mm 0.55mm 1mm';
                    css += '.r .b{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;box-sizing:border-box!important;height:' + rollFaceH + '!important;min-height:' + rollFaceH + '!important;max-height:' + rollFaceH + '!important;padding:' + rollPad + '!important;}';
                    var stackGap = config.rollTextTight === true ? '0.02mm' : '0.08mm';
                    css += '.r .barcode-roll-stack{display:flex!important;flex-direction:column!important;align-items:center!important;width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;gap:' + stackGap + '!important;}';
                    css += '.r .barcode-roll-stack>*{margin-top:0!important;margin-bottom:0!important;padding-top:0!important;padding-bottom:0!important;}';
                    if (config.colWidth) {
                        css += '.r>.b:only-child{margin-inline-start:0!important;margin-inline-end:auto!important;flex:0 0 ' + config.colWidth + '!important;width:' + config.colWidth + '!important;max-width:' + config.colWidth + '!important;}';
                    }
                    var rtTight = config.rollTextTight === true;
                    var fsN, fsT, fsS, fsP, fsY, lineN;
                    if (rtTight && config.tightTypeSizes && typeof config.tightTypeSizes === 'object') {
                        var tg = config.tightTypeSizes;
                        fsN = tg.n || '5.1pt';
                        fsT = tg.t || '4.9pt';
                        fsS = tg.s || '5.1pt';
                        fsP = tg.p || '5.5pt';
                        fsY = tg.y || '5.1pt';
                        lineN = tg.lineN || '1.03';
                    } else if (rtTight) {
                        fsN = '5.1pt';
                        fsT = '4.9pt';
                        fsS = '5.1pt';
                        fsP = '5.5pt';
                        fsY = '5.1pt';
                        lineN = '1.03';
                    } else {
                        fsN = '5.8pt';
                        fsT = '5.3pt';
                        fsS = '5.7pt';
                        fsP = '6.1pt';
                        fsY = '5.7pt';
                        lineN = '1.05';
                    }
                    var nameClamp = (config.nameLineClamp != null) ? config.nameLineClamp : 2;
                    var nameWidthRule = '';
                    if (config.nameMaxWidth != null && String(config.nameMaxWidth).length) {
                        nameWidthRule = ';max-width:' + config.nameMaxWidth + '!important;box-sizing:border-box!important;margin-left:auto!important;margin-right:auto!important';
                    }
                    css += '.r .n{font-size:' + fsN + '!important;line-height:' + lineN + '!important;display:-webkit-box!important;-webkit-box-orient:vertical!important;-webkit-line-clamp:' + nameClamp + '!important;overflow:hidden!important;word-break:break-word!important;overflow-wrap:break-word!important;white-space:normal!important;text-align:center!important;width:100%!important' + nameWidthRule + ';margin-bottom:0.15mm!important}';
                    css += '.r .t{font-size:' + fsT + '!important;line-height:1.03!important;margin-bottom:0.1mm!important}';
                    css += '.r .c{display:flex!important;justify-content:center!important;align-items:center!important;margin:0!important;width:100%!important;flex-shrink:0!important}';
                    css += '.r .c svg{max-width:' + config.svgW + '!important;height:' + config.svgH + '!important;max-height:' + config.svgH + '!important;width:auto!important;flex-shrink:0!important}';
                    css += '.r .s{font-size:' + fsS + '!important;line-height:1.03!important;margin-top:0.1mm!important}';
                    css += '.r .p{font-size:' + fsP + '!important;line-height:1.03!important;margin-top:0.1mm!important}';
                    css += '.r .y{font-size:' + fsY + '!important;line-height:1.03!important;margin-top:0.1mm!important}';
                }

                // Write content to iframe (inline html dimensions help Chrome shrink preview vs Letter/A4)
                var doc = iframe.contentWindow.document;
                doc.open();
                var htmlOpen = '<!DOCTYPE html><html';
                if (pageBox && totalDocHeightExpr) {
                    htmlOpen += ' style="margin:0;padding:0;width:' + pageBox.w + ';max-width:' + pageBox.w + ';height:' + totalDocHeightExpr + ';overflow:hidden"';
                }
                htmlOpen += '><head><title>Print</title>';
                doc.write(htmlOpen);
                doc.write('<style>' + css + '</style></head><body');
                if (pageBox && totalDocHeightExpr) {
                    doc.write(' style="margin:0;width:' + pageBox.w + ';max-width:' + pageBox.w + ';height:' + totalDocHeightExpr + ';overflow:hidden"');
                }
                doc.write('>');
                doc.write('<div class="g">' + printContent + '</div>');
                doc.write('</body></html>');
                doc.close();

                // Open print dialog once after iframe document is ready (doc.close() finishes synchronously).
                // Note: "message channel closed" in console is from a Chrome *extension*, not this app — disable extensions or use Incognito to hide it.
                var printFired = false;
                function runPrintOnce() {
                    if (printFired) return;
                    printFired = true;
                    try {
                        var win = iframe.contentWindow;
                        if (!win) return;
                        win.focus();
                        var pr = win.print();
                        if (pr != null && typeof pr.then === 'function') {
                            pr.catch(function() {});
                        }
                    } catch (e) {
                        console.error('Print failed', e);
                        toastr.error('Could not open print dialog.');
                    }
                }
                setTimeout(runPrintOnce, 150);
                setTimeout(runPrintOnce, 500);
            });
        });
    </script>
@endsection
