<script type="text/javascript">
    $(document).ready(function() {
        // Add CSS for batch history styling
        if (!$('#batch-history-styles').length) {
            $('<style id="batch-history-styles">')
                .prop("type", "text/css")
                .html(`
                    .batch-history {
                        max-height: 100px;
                        overflow-y: auto;
                        font-size: 11px;
                        background-color: #f8f9fa;
                        padding: 5px;
                        border-radius: 3px;
                        border: 1px solid #e9ecef;
                    }
                    .batch-history small {
                        line-height: 1.2;
                        margin-bottom: 2px;
                    }
                    .is-invalid {
                        border-color: #dc3545 !important;
                        animation: shake 0.5s;
                    }
                    @keyframes shake {
                        0%, 100% { transform: translateX(0); }
                        25% { transform: translateX(-5px); }
                        75% { transform: translateX(5px); }
                    }
                    /* Autocomplete styling for purchase module */
                    .ui-autocomplete {
                        max-height: 200px;
                        overflow-y: auto;
                        overflow-x: hidden;
                        background: #fff;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        padding: 5px 0;
                        font-size: 14px;
                        z-index: 1000;
                        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    }
                    .ui-menu .ui-menu-item {
                        padding: 8px 12px;
                        cursor: pointer;
                        border-bottom: 1px solid #f0f0f0;
                    }
                    .ui-menu .ui-menu-item:hover,
                    .ui-menu .ui-menu-item.ui-state-focus,
                    .ui-menu .ui-menu-item.ui-state-active {
                        background-color: #007bff !important;
                        color: #fff !important;
                        border-radius: 4px;
                        margin: 2px 4px;
                        border-bottom: none !important;
                    }
                    .document-upload {
                        background-color: #f8f9fa;
                        transition: all 0.3s ease;
                    }
                    .document-upload:hover {
                        background-color: #e9ecef;
                    }
                    .preview-container {
                        background-color: #fff;
                        min-height: 200px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    #purchase-selectedImage {
                        max-width: 100%;
                        max-height: 200px;
                        object-fit: contain;
                        border-radius: 5px;
                    }
                    #purchase-pdfViewer {
                        border: 1px solid #dee2e6;
                        border-radius: 5px;
                        background: #f8f9fa;
                        min-height: 200px;
                        width: 100%;
                    }
                    #purchase-pdfViewer[src=""] {
                        display: none !important;
                    }
                    .upload {
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }
                    .upload:hover {
                        background-color: #0056b3;
                        color: white;
                    }
                    .hide-input {
                        display: none;
                    }
                `)
                .appendTo("head");
        }

        // CSRF Token setup
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        // DataTable global variable
        let purchaseProductTable = null;

        // IMEI handling variables
        let purchaseImeiData = {};
        let pendingImeiProducts = [];
        let currentImeiProductIndex = 0;
        let isProcessingImei = false;
        let isProgrammaticModalClose = false; // Flag to track programmatic modal closes

        fetchProducts();
        fetchLocations();

        // Function to print modal
        function printModal() {
            window.print();
        }

        const validationMessages = {
            supplier_id: {
                required: "Supplier is required"
            },
            purchase_date: {
                required: "Purchase Date is required"
            },
            purchasing_status: {
                required: "Purchase Status is required"
            },
            location_id: {
                required: "Business Location is required"
            },
            duration: {
                required: "Duration is required",
                number: "Please enter a valid number"
            },
            duration_type: {
                required: "Period is required"
            },
            image: {
                extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
                filesize: "Max file size is 5MB"
            }
        };

        // Validation setup
        var purchaseValidationOptions = {
            rules: {
                supplier_id: {
                    required: true
                },
                purchase_date: {
                    required: true
                },
                purchasing_status: {
                    required: true
                },
                location_id: {
                    required: true
                },
                duration: {
                    required: true,
                    number: true
                },
                duration_type: {
                    required: true
                },
                image: {
                    required: false,
                    extension: "jpg|jpeg|png|gif|pdf|csv|zip|doc|docx",
                    filesize: 5242880 // 5MB
                }
            },
            messages: validationMessages,

            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.is("select")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest(
                        '.input-group')); // Place error after select container
                } else if (element.is(":checkbox") || element.is(":radio")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.addClass('text-danger small');
                    error.insertAfter(element); // Default placement for text and other inputs
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to forms
        $('#purchaseForm').validate(purchaseValidationOptions);

        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const locationSelect = $('#services');
                    locationSelect.html('<option selected disabled>Select Location</option>');

                    if (data.status === true) {
                        // Filter locations to only show parent_id === null
                        const mainLocations = data.data.filter(location => location.parent_id ===
                            null);

                        mainLocations.forEach(function(location) {
                            const option = $('<option></option>').val(location.id).text(
                                location.name);
                            locationSelect.append(option);
                        });

                        // Trigger change for the first location by default
                        if (mainLocations.length > 0) {
                            locationSelect.val(mainLocations[0].id).trigger('change');
                        }
                    } else {
                        console.error('Failed to fetch location data:', data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching location data:', error);
                }
            });
        }

        let allProducts = []; // Store all product data
        let currentPage = 1;
        let hasMore = true;

        function normalizeString(str) {
            return (str || '').toString().toLowerCase().replace(/[^a-z0-9]/gi, '');
        }

        function initAutocomplete() {
            const $input = $("#productSearchInput");

            // Add Enter key support for quick selection - Updated with working POS AJAX solution
            $input.off('keydown.autocomplete').on('keydown.autocomplete', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();

                    const widget = $(this).autocomplete("widget");
                    const focused = widget.find(".ui-state-focus");
                    const currentSearchTerm = $(this).val().trim();

                    let itemToAdd = null;

                    if (focused.length > 0) {
                        // Get the item data from the autocomplete instance's active item
                        const autocompleteInstance = $(this).autocomplete("instance");
                        if (autocompleteInstance && autocompleteInstance.menu.active) {
                            itemToAdd = autocompleteInstance.menu.active.data("ui-autocomplete-item");
                        }
                    }

                    if (itemToAdd && itemToAdd.product) {
                        addProductToTable(itemToAdd.product);
                        $(this).val('');
                        $(this).autocomplete('close');
                    }

                    event.stopImmediatePropagation();
                }
            });

            $input.autocomplete({
                minLength: 1,
                source: function(request, response) {
                    const locationId = $('#services').val();
                    const searchTermRaw = request.term.trim();
                    const searchTerm = normalizeString(searchTermRaw);

                    $.ajax({
                        url: '/products/stocks/autocomplete',
                        data: {
                            search: searchTermRaw,
                            location_id: locationId,
                            per_page: 50, // fetch more for better filtering
                            page: 1
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 200 && Array.isArray(data.data)) {
                                let items = data.data
                                    .map(item => ({
                                        label: `${item.product.product_name} (${item.product.sku || 'N/A'}) [Stock: ${item.total_stock || 0}]`,
                                        value: item.product.product_name,
                                        product: {
                                            id: item.product.id,
                                            name: item.product.product_name,
                                            sku: item.product.sku || "N/A",
                                            quantity: item.total_stock || 0,
                                            price: item.product
                                                .original_price || 0,
                                            wholesale_price: item.product
                                                .whole_sale_price || 0,
                                            special_price: item.product
                                                .special_price || 0,
                                            max_retail_price: item.product
                                                .max_retail_price || 0,
                                            retail_price: item.product
                                                .retail_price || 0,
                                            expiry_date: '', // Not available in autocomplete
                                            batch_no: '', // Not available in autocomplete
                                            stock_alert: item.product
                                                .stock_alert || 0,
                                            allow_decimal: item.product.unit
                                                ?.allow_decimal || false,
                                            is_imei_or_serial_no: item.product
                                                .is_imei_or_serial_no || false
                                        }
                                    }));

                                // Remove client-side filtering - let server-side prioritization handle this
                                // Server already returns results ordered by: exact SKU match > exact name > partial matches
                                if (items.length > 0) {
                                    response(items.slice(0,
                                        15)); // Show up to 15 results
                                } else {
                                    response([{
                                        label: "No products found",
                                        value: "",
                                        product: null
                                    }]);
                                }
                            } else {
                                response([{
                                    label: "No products found",
                                    value: "",
                                    product: null
                                }]);
                            }
                        },
                        error: function() {
                            response([{
                                label: "Error fetching products",
                                value: ""
                            }]);
                        }
                    });
                },
                select: function(event, ui) {
                    if (!ui.item.product) {
                        return false;
                    }
                    addProductToTable(ui.item.product);
                    $("#productSearchInput").val("");
                    currentPage = 1;
                    return false;
                },
                open: function() {
                    setTimeout(() => {
                        $(".ui-autocomplete").scrollTop(0); // Reset scroll on new search
                        // Auto-focus first item for Enter key selection - Updated with working POS AJAX solution
                        const autocompleteInstance = $input.autocomplete("instance");
                        const menu = autocompleteInstance.menu;
                        const firstItem = menu.element.find("li:first-child");

                        if (firstItem.length > 0 && !firstItem.text().includes(
                                "No products found")) {
                            // Properly set the active item using jQuery UI's method
                            menu.element.find(".ui-state-focus").removeClass(
                                "ui-state-focus");
                            firstItem.addClass("ui-state-focus");
                            menu.active = firstItem;
                            console.log('First item auto-focused - press Enter to add');
                        }
                    }, 50);
                }
            });

            // Custom render for autocomplete
            const autocompleteInstance = $input.data("ui-autocomplete");
            if (autocompleteInstance) {
                autocompleteInstance._renderItem = function(ul, item) {
                    if (!item.product) {
                        return $("<li>")
                            .append(`<div style="color: red;">${item.label}</div>`)
                            .appendTo(ul);
                    }
                    return $("<li>")
                        .append(`<div>${item.label}</div>`)
                        .data('ui-autocomplete-item', item)
                        .appendTo(ul);
                };
            }
        }

        // Fetch all products for other usages (not autocomplete)
        function fetchProducts(locationId) {
            let url = '/products/stocks';
            if (locationId) {
                url += `?location_id=${locationId}`;
            }
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200 && Array.isArray(data.data)) {
                        allProducts = data.data.map(stock => {
                            if (!stock.product) return null;
                            return {
                                id: stock.product.id,
                                name: stock.product.product_name,
                                sku: stock.product.sku || "N/A",
                                quantity: stock.total_stock || 0,
                                price: stock.batches?.[0]?.unit_cost || stock.product
                                    .original_price || 0,
                                wholesale_price: stock.batches?.[0]?.wholesale_price || stock
                                    .product.whole_sale_price || 0,
                                special_price: stock.batches?.[0]?.special_price || stock.product
                                    .special_price || 0,
                                max_retail_price: stock.batches?.[0]?.max_retail_price || stock
                                    .product.max_retail_price || 0,
                                retail_price: stock.batches?.[0]?.retail_price || stock.product
                                    .retail_price || 0,
                                expiry_date: stock.batches?.[0]?.expiry_date || '',
                                batch_no: stock.batches?.[0]?.batch_no || '',
                                stock_alert: stock.product.stock_alert || 0,
                                allow_decimal: stock.product.unit?.allow_decimal ||
                                    false // Pass allow_decimal
                            };
                        }).filter(product => product !== null);
                    } else {
                        console.error("Failed to fetch product data:", data);
                    }
                })
                .catch(error => console.error("Error fetching products:", error));
        }

        $(document).ready(function() {
            fetchLocations(); // Fetch all locations
            initAutocomplete(); // Initialize backend autocomplete

            $('#services').on('change', function() {
                const selectedLocationId = $(this).val();
                if (selectedLocationId) {
                    fetchProducts(selectedLocationId);
                }
            });
        });

        function addProductToTable(product, isEditing = false, prices = {}) {
            // Use global variable or get existing instance (don't create new!)
            const table = purchaseProductTable || $('#purchase_product').DataTable();
            let existingRow = null;

            $('#purchase_product tbody tr').each(function() {
                const rowProductId = $(this).data('id');
                if (rowProductId === product.id) {
                    existingRow = $(this);
                    return false;
                }
            });

            // Determine if decimal is allowed for this product
            const allowDecimal = product.allow_decimal === true || product.allow_decimal === "true";
            const quantityStep = allowDecimal ? "0.01" : "1";
            const quantityMin = allowDecimal ? "0.01" : "1";
            const quantityPattern = allowDecimal ? "[0-9]+([.][0-9]{1,2})?" : "[0-9]+";

            if (existingRow && !isEditing) {
                const quantityInput = existingRow.find('.purchase-quantity');
                let currentVal = parseFloat(quantityInput.val());
                let newQuantity = allowDecimal ? (currentVal + 1) : (parseInt(currentVal) + 1);
                quantityInput.val(newQuantity).trigger('input');
            } else {
                // Get latest batch prices using helper function
                const latestPrices = getLatestBatchPrices(product);

                // Override with any provided prices
                const wholesalePrice = parseFloat(prices.wholesale_price || latestPrices.wholesale_price) || 0;
                const specialPrice = parseFloat(prices.special_price || latestPrices.special_price) || 0;
                const maxRetailPrice = parseFloat(prices.max_retail_price || latestPrices.max_retail_price) ||
                    0;
                let retailPrice = parseFloat(prices.retail_price || latestPrices.retail_price) || 0;
                const unitCost = parseFloat(prices.unit_cost || latestPrices.unit_cost) || 0;

                // Ensure retail price doesn't exceed MRP
                if (retailPrice > maxRetailPrice && maxRetailPrice > 0) {
                    retailPrice = maxRetailPrice;
                    toastr.info(`Retail price set to MRP (${maxRetailPrice.toFixed(2)}) for ${product.name}`,
                        'Price Adjustment');
                }

                // Generate batch history for reference (earliest 5 batches)
                let batchHistoryHtml = '';
                if (product.batches && product.batches.length > 0) {
                    const earliestBatches = product.batches
                        .sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
                        .slice(0, 5);

                    batchHistoryHtml = earliestBatches.map(batch =>
                        `<small class="d-block text-muted">
                            Batch: ${batch.batch_no || 'N/A'} | 
                            Cost: ${parseFloat(batch.unit_cost || 0).toFixed(2)} | 
                            Retail: ${parseFloat(batch.retail_price || 0).toFixed(2)} | 
                            Date: ${batch.created_at ? new Date(batch.created_at).toLocaleDateString() : 'N/A'}
                        </small>`
                    ).join('');
                }

                const newRow = `
            <tr data-id="${product.id}" data-mrp="${maxRetailPrice}" data-imei-enabled="${product.is_imei_or_serial_no || false}">
            <td>${product.id}</td>
            <td>
                ${product.name} 
                <br><small>Stock: ${product.quantity}</small>
                ${batchHistoryHtml ? `<br><div class="batch-history mt-1"><strong>Recent Batches:</strong><br>${batchHistoryHtml}</div>` : ''}
            </td>
            <td>
                <input type="number" class="form-control purchase-quantity" value="${prices.quantity || 1}" min="${quantityMin}" step="${quantityStep}" pattern="${quantityPattern}" ${allowDecimal ? '' : 'oninput="this.value = this.value.replace(/[^0-9]/g, \'\')"'}>
            </td>
            <td>
                <input type="number" class="form-control product-price" value="${unitCost.toFixed(2)}" min="0">
            </td>
            <td>
                <input type="number" class="form-control discount-percent" value="0" min="0" max="100">
            </td>
            <td><input type="number" class="form-control amount unit-cost" value="${unitCost.toFixed(2)}" min="0"></td>
            <td class="sub-total">0</td>
            <td><input type="number" class="form-control special-price" value="${specialPrice.toFixed(2)}" min="0"></td>
            <td><input type="number" class="form-control wholesale-price" value="${wholesalePrice.toFixed(2)}" min="0"></td>
            <td><input type="number" class="form-control max-retail-price" value="${maxRetailPrice.toFixed(2)}" min="0"></td>
            <td><input type="number" class="form-control profit-margin" value="0" min="0" readonly></td>
            <td><input type="number" class="form-control retail-price" value="${retailPrice.toFixed(2)}" min="0" max="${maxRetailPrice}" required title="Maximum allowed: ${maxRetailPrice.toFixed(2)} (MRP)" placeholder="Max: ${maxRetailPrice.toFixed(2)}"></td>
            <td><input type="date" class="form-control expiry-date" value="${latestPrices.expiry_date}"></td>
            <td><input type="text" class="form-control batch_no" value="${latestPrices.batch_no}"></td>
            <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;

                const $newRow = $(newRow);
                table.row.add($newRow).draw();
                updateRow($newRow);
                calculateProfitMargin($newRow); // Initial profit margin calculation
                updateFooter();

                // Handle quantity, discount, and price changes
                $newRow.find(
                    ".purchase-quantity, .discount-percent, .product-price, .unit-cost"
                ).on("input", function() {
                    updateRow($newRow);
                    updateFooter();
                });

                // Handle retail price changes separately to update profit margin
                $newRow.find(".retail-price").on("input", function() {
                    validateRetailPriceAgainstMRP($newRow);
                    calculateProfitMargin($newRow);
                    updateFooter();
                });

                $newRow.find(".delete-product").on("click", function() {
                    table.row($newRow).remove().draw();
                    updateFooter();
                });
            }
        }

        function updateRow($row) {
            const quantity = parseFloat($row.find(".purchase-quantity").val()) || 0;
            const price = parseFloat($row.find(".product-price").val()) || 0;
            const discountPercent = parseFloat($row.find(".discount-percent").val()) || 0;

            // Calculate discounted unit cost
            const discountedPrice = price - (price * discountPercent) / 100;
            const unitCost = discountedPrice;
            const subTotal = unitCost * quantity;

            // Update unit cost and subtotal
            $row.find(".unit-cost").val(unitCost.toFixed(2));
            $row.find(".sub-total").text(subTotal.toFixed(2));

            // Recalculate profit margin based on current retail price and unit cost
            calculateProfitMargin($row);
        }

        function calculateProfitMargin($row) {
            const retailPrice = parseFloat($row.find(".retail-price").val()) || 0;
            const unitCost = parseFloat($row.find(".unit-cost").val()) || 0;

            let profitMargin = 0;
            if (unitCost > 0 && retailPrice > 0) {
                profitMargin = ((retailPrice - unitCost) / unitCost) * 100;
            }

            $row.find(".profit-margin").val(profitMargin.toFixed(2));
        }

        function validateRetailPriceAgainstMRP($row) {
            const retailPriceInput = $row.find(".retail-price");
            const retailPrice = parseFloat(retailPriceInput.val()) || 0;
            const mrp = parseFloat($row.data('mrp')) || 0;

            if (mrp > 0 && retailPrice > mrp) {
                // Show warning and reset to MRP
                toastr.warning(`Retail price cannot exceed MRP (${mrp.toFixed(2)}). Setting to MRP.`,
                    'Price Validation');
                retailPriceInput.val(mrp.toFixed(2));

                // Add visual feedback
                retailPriceInput.addClass('is-invalid');
                setTimeout(() => {
                    retailPriceInput.removeClass('is-invalid');
                }, 2000);
            }
        }

        function initializeExistingRowValidation($row) {
            // Add MRP validation to existing rows
            $row.find(".retail-price").on("input", function() {
                validateRetailPriceAgainstMRP($row);
                calculateProfitMargin($row);
                updateFooter();
            });
        }

        function getLatestBatchPrices(product) {
            // Helper function to get latest batch prices
            if (!product.batches || product.batches.length === 0) {
                return {
                    wholesale_price: product.wholesale_price || 0,
                    special_price: product.special_price || 0,
                    max_retail_price: product.max_retail_price || 0,
                    retail_price: product.retail_price || 0,
                    unit_cost: product.price || 0,
                    batch_no: product.batch_no || '',
                    expiry_date: product.expiry_date || ''
                };
            }

            // Sort by creation date to get latest batch
            const latestBatch = product.batches.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[
                0];

            return {
                wholesale_price: latestBatch.wholesale_price || product.wholesale_price || 0,
                special_price: latestBatch.special_price || product.special_price || 0,
                max_retail_price: latestBatch.max_retail_price || product.max_retail_price || 0,
                retail_price: latestBatch.retail_price || product.retail_price || 0,
                unit_cost: latestBatch.unit_cost || product.price || 0,
                batch_no: latestBatch.batch_no || product.batch_no || '',
                expiry_date: latestBatch.expiry_date || product.expiry_date || ''
            };
        }

        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            // CRITICAL FIX: Use DataTables API to get ALL rows (including paginated ones)
            // $('#purchase_product tbody tr').each() only gets visible rows!
            if ($.fn.DataTable.isDataTable('#purchase_product')) {
                // Get ALL rows data from DataTable (not just visible page)
                purchaseProductTable.rows().every(function() {
                    const row = this.node();
                    const quantity = parseFloat($(row).find('.purchase-quantity').val()) || 0;
                    // Remove any commas before parsing (safety for formatted numbers)
                    const subTotal = parseFloat($(row).find('.sub-total').text().replace(/,/g, '')) ||
                        0;

                    totalItems += quantity;
                    netTotalAmount += subTotal;
                });
            } else {
                // Fallback for when DataTable is not initialized yet
                $('#purchase_product tbody tr').each(function() {
                    const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
                    const subTotal = parseFloat($(this).find('.sub-total').text().replace(/,/g, '')) ||
                        0;

                    totalItems += quantity;
                    netTotalAmount += subTotal;
                });
            }

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#total').val(netTotalAmount.toFixed(2));

            const discountType = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#purchase-total').text(`Purchase Total: Rs ${finalTotal.toFixed(2)}`);
            $('#final-total').val(finalTotal.toFixed(2));
            $('#discount-display').text(`(-) Rs ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs ${taxAmount.toFixed(2)}`);

            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const paymentDue = finalTotal - paidAmount;
            $('.payment-due').text(`Rs ${paymentDue.toFixed(2)}`);
        }

        $('#discount-type, #discount-amount, #tax-type, #paid-amount').on('change input', updateFooter);


        function formatDate(dateStr) {
            const [year, month, day] = dateStr.split('-');
            return `${day}-${month}-${year}`;
        }

        const pathSegments = window.location.pathname.split('/');
        const lastSegment = pathSegments[pathSegments.length - 1];
        // Only set purchaseId if we're on an edit page and the last segment is a number
        const purchaseId = (lastSegment !== 'add-purchase' && lastSegment !== 'list-purchase' && !isNaN(
            lastSegment)) ? lastSegment : null;

        if (purchaseId) {
            fetchPurchaseData(purchaseId);
            $("#purchaseButton").text("Update Purchase");
        }

        function fetchPurchaseData(purchaseId) {
            $.ajax({
                url: `/purchase/edit/${purchaseId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        populateForm(response.purchase);
                    } else {
                        toastr.error('Failed to fetch purchase data.', 'Error');
                    }
                },
                error: handleAjaxError('fetching purchase data')
            });
        }

        function populateForm(purchase) {
            $('#supplier-id').val(purchase.supplier_id).trigger(
                'change'); // Trigger change event after setting supplier ID
            $('#reference-no').val(purchase.reference_no);
            $('#purchase-date').val(formatDate(purchase.purchase_date));
            $('#purchase-status').val(purchase.purchasing_status).change();
            $('#services').val(purchase.location_id).change();
            $('#duration').val(purchase.pay_term);
            $('#period').val(purchase.pay_term_type).change();
            $('#discount-type').val(purchase.discount_type).change();
            $('#discount-amount').val(purchase.discount_amount);
            $('#payment-status').val(purchase.payment_status).change();

            if (purchase.payments && purchase.payments.length > 0) {
                const latestPayment = purchase.payments[purchase.payments.length - 1];
                $('#payment-date').val(formatDate(latestPayment.payment_date));
                $('#payment-account').val(latestPayment.payment_account);
                $('#payment-method').val(latestPayment.payment_method);
                $('#payment-note').val(latestPayment.notes);
            }

            // Use global variable or get existing instance (don't reinitialize!)
            let productTable;
            if (purchaseProductTable) {
                productTable = purchaseProductTable;
            } else if ($.fn.DataTable.isDataTable('#purchase_product')) {
                productTable = $('#purchase_product').DataTable();
                purchaseProductTable = productTable; // Save to global
            }

            if (productTable) {
                productTable.clear().draw();
            }

            if (purchase.purchase_products && Array.isArray(purchase.purchase_products)) {
                purchase.purchase_products.forEach(product => {
                    const productData = {
                        id: product.product_id,
                        name: product.product.product_name,
                        sku: product.product.sku,
                        quantity: product.quantity,
                        price: product.unit_cost,
                        wholesale_price: product.wholesale_price,
                        special_price: product.special_price,
                        max_retail_price: product.max_retail_price,
                        expiry_date: product.batch ? product.batch.expiry_date : '',
                        batch_no: product.batch ? product.batch.batch_no : ''
                    };

                    const batchPrices = {
                        price: product.batch ? product.batch.retail_price : product.price,
                        unit_cost: product.batch ? product.batch.unit_cost : product.unit_cost,
                        wholesale_price: product.batch ? product.batch.wholesale_price : product
                            .wholesale_price,
                        special_price: product.batch ? product.batch.special_price : product
                            .special_price,
                        max_retail_price: product.batch ? product.batch.max_retail_price : product
                            .max_retail_price,
                        quantity: product.quantity
                    };

                    addProductToTable(productData, true, batchPrices);
                });
            }

            updateFooter();
        }

        $('#purchaseButton').on('click', function(event) {
            event.preventDefault();
            $('#purchaseButton').prop('disabled', true).html('Processing...');

            if (!$('#purchaseForm').valid()) {
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            // CRITICAL FIX: Get ALL rows from DataTable (including paginated ones)
            let productTableRows = [];
            if ($.fn.DataTable.isDataTable('#purchase_product')) {
                // Use DataTables API to get ALL rows across all pages
                purchaseProductTable.rows().every(function() {
                    productTableRows.push(this.node());
                });
                console.log('Product table rows count (ALL pages):', productTableRows.length);
            } else {
                // Fallback: get visible rows
                productTableRows = document.querySelectorAll('#purchase_product tbody tr');
                console.log('Product table rows count (visible only):', productTableRows.length);
            }

            if (productTableRows.length === 0) {
                toastr.error('Please add at least one product.', 'Warning');
                document.getElementsByClassName('errorSound')[0].play();
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            // Check for IMEI products and collect them
            collectImeiProducts(productTableRows);
        });

        function collectImeiProducts(productTableRows) {
            pendingImeiProducts = [];
            console.log('=== IMEI Collection Debug ===');

            // CRITICAL FIX: Get ALL rows from DataTable (including paginated ones)
            let allRows = [];
            if ($.fn.DataTable.isDataTable('#purchase_product')) {
                // Use DataTables API to get ALL rows across all pages
                purchaseProductTable.rows().every(function() {
                    allRows.push(this.node());
                });
                console.log('Using DataTables API - Collecting IMEI products from', allRows.length,
                    'rows (ALL pages)');
            } else {
                // Fallback: use provided rows
                allRows = Array.from(productTableRows);
                console.log('Using provided rows - Collecting IMEI products from', allRows.length, 'rows');
            }

            allRows.forEach((row, index) => {
                const productId = $(row).data('id');
                const quantity = parseInt($(row).find('.purchase-quantity').val()) || 0;

                // Extract product name from the second column (more robust extraction)
                const productNameCell = $(row).find('td:eq(1)');
                let productName = '';

                // Try to get product name from data attribute first
                if ($(row).data('product-name')) {
                    productName = $(row).data('product-name');
                } else {
                    // Extract from cell text, handling various formats
                    const cellText = productNameCell.text().trim();
                    // Remove any leading/trailing whitespace and extract the main product name
                    // Split by common separators and take the main part
                    productName = cellText.split(' - ')[0].split(' (')[0].trim();
                    if (!productName) {
                        productName = cellText; // fallback to full text
                    }
                }

                console.log(
                    `Product ${index + 1}: Name="${productName}", ID=${productId}, Cell Text="${productNameCell.text().trim()}"`
                );

                // Check if product has IMEI enabled
                const isImeiEnabled = $(row).data('imei-enabled') == 1 || $(row).data(
                    'imei-enabled') === true;
                const dataAttribute = $(row).data('imei-enabled');

                console.log(`Product ${productId} (${productName}): 
                    - IMEI data attribute: ${dataAttribute}
                    - IMEI enabled: ${isImeiEnabled}
                    - Quantity: ${quantity}
                    - Row HTML: ${$(row).prop('outerHTML').substring(0, 200)}...`);

                if (isImeiEnabled && quantity > 0) {
                    pendingImeiProducts.push({
                        productId: productId,
                        productName: productName,
                        quantity: quantity,
                        rowIndex: index,
                        row: row
                    });
                    console.log(
                        `✅ Added IMEI product: ${productName} (ID: ${productId}) with quantity ${quantity}`
                    );
                } else {
                    console.log(
                        `❌ Skipped product: ${productName} - IMEI: ${isImeiEnabled}, Qty: ${quantity}`
                    );
                }
            });

            console.log('=== IMEI Collection Results ===');
            console.log('Total IMEI products found:', pendingImeiProducts.length);
            console.log('IMEI products:', pendingImeiProducts);

            if (pendingImeiProducts.length > 0) {
                currentImeiProductIndex = 0;
                isProcessingImei = true;
                console.log('🎯 Starting IMEI entry process...');
                showImeiModal();
            } else {
                // No IMEI products, proceed with purchase
                console.log('ℹ️ No IMEI products found, proceeding with purchase');
                processPurchase();
            }
        }

        function showImeiModal() {
            console.log('=== Show SELECT2-Based IMEI Modal Debug ===');
            console.log('Total IMEI products:', pendingImeiProducts.length);
            console.log('IMEI products:', pendingImeiProducts);

            if (pendingImeiProducts.length === 0) {
                console.log('✅ No IMEI products, proceeding with purchase');
                isProcessingImei = false;
                isProgrammaticModalClose = false;
                processPurchase();
                return;
            }

            // Show Select2-based modal
            console.log('🎯 Showing SELECT2-based IMEI modal');

            // Set modal title
            $('#purchaseImeiModalLabel').text(`Enter IMEI Numbers - Select Product First`);

            // Clear previous data
            $('#purchaseImeiTable tbody').empty();
            $('#purchaseImeiInput').val('');
            $('#purchaseImeiError').addClass('d-none');

            // Show the product selection section
            $('#purchaseImeiProductSelection').show();
            $('#purchaseImeiEntrySection').hide();

            // Initialize Select2 dropdown with products
            initializeProductSelect2();

            updateSelect2ImeiCount();

            console.log('📱 About to show Select2-based modal #purchaseImeiModal');

            // Check if modal exists
            if ($('#purchaseImeiModal').length === 0) {
                console.error('❌ MODAL NOT FOUND: #purchaseImeiModal');
                alert('IMEI modal not found in DOM. Please check the HTML.');
                return;
            }

            $('#purchaseImeiModal').modal('show');
            console.log('📱 Select2-based modal show command executed');
        }

        function initializeProductSelect2() {
            // Clear existing options
            $('#purchaseImeiProductSelect').empty();

            // Add default option
            $('#purchaseImeiProductSelect').append(
                '<option value="">Select products to enter IMEI...</option>');

            console.log('=== Initializing Select2 with products ===');

            // Add product options
            pendingImeiProducts.forEach(product => {
                const currentImeiCount = purchaseImeiData[product.productId] ? purchaseImeiData[product
                    .productId].length : 0;
                const status = currentImeiCount === product.quantity ? '✓ Complete' :
                    `${currentImeiCount}/${product.quantity}`;
                const optionText = `${product.productName} (Qty: ${product.quantity}) - ${status}`;

                console.log(`Adding option: ${optionText} with value ${product.productId}`);

                $('#purchaseImeiProductSelect').append(
                    `<option value="${product.productId}" data-quantity="${product.quantity}">
                        ${optionText}
                    </option>`
                );
            });

            // Initialize or reinitialize Select2
            if ($('#purchaseImeiProductSelect').hasClass('select2-hidden-accessible')) {
                $('#purchaseImeiProductSelect').select2('destroy');
            }

            $('#purchaseImeiProductSelect').select2({
                placeholder: 'Select products to enter IMEI...',
                allowClear: true,
                multiple: true,
                width: '100%',
                dropdownParent: $('#purchaseImeiModal')
            });

            console.log('✅ Select2 initialized with', pendingImeiProducts.length, 'products');
        }

        function addPurchaseImeiRow(index, productId) {
            const row = `
                <tr>
                    <td>${index}</td>
                    <td>
                        <input type="text" 
                               class="form-control purchase-imei-input" 
                               data-product-id="${productId}"
                               placeholder="Enter IMEI" />
                    </td>
                    <td><button type="button" class="btn btn-sm btn-danger remove-purchase-imei-row">Remove</button></td>
                </tr>
            `;
            $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]:last`).after(row);
        }

        function updateSelect2ImeiCount() {
            const totalProducts = pendingImeiProducts.length;
            let completedProducts = 0;
            let totalRequired = 0;
            let totalEntered = 0;

            pendingImeiProducts.forEach(product => {
                const currentImeiCount = purchaseImeiData[product.productId] ? purchaseImeiData[product
                    .productId].length : 0;
                totalRequired += product.quantity;
                totalEntered += currentImeiCount;

                if (currentImeiCount === product.quantity) {
                    completedProducts++;
                }
            });

            $('#purchaseImeiCountDisplay').text(
                `Products: ${completedProducts}/${totalProducts} complete | Total IMEI: ${totalEntered}/${totalRequired}`
            );
        }

        function loadImeiEntrySectionForProducts(selectedProductIds) {
            if (!selectedProductIds || selectedProductIds.length === 0) {
                $('#purchaseImeiEntrySection').hide();
                return;
            }

            console.log('Loading IMEI entry for products:', selectedProductIds);

            // Show the entry section
            $('#purchaseImeiEntrySection').show();

            // Clear previous data
            $('#purchaseImeiTable tbody').empty();
            $('#purchaseImeiInput').val('');
            $('#purchaseImeiError').addClass('d-none');

            selectedProductIds.forEach(productId => {
                const product = pendingImeiProducts.find(p => p.productId == productId);
                if (!product) return;

                // Add product header row
                const headerRow = `
                    <tr class="table-primary" data-product-header="${productId}">
                        <td colspan="3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${product.productName}</strong> 
                                    <span class="badge badge-info ml-2">Max Qty: ${product.quantity}</span>
                                    <span class="badge badge-success ml-1" id="progress-${productId}">0/${product.quantity} entered</span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-primary add-product-row" data-product-id="${productId}">
                                        <i class="fas fa-plus"></i> Add Row
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                $('#purchaseImeiTable tbody').append(headerRow);

                // Load existing IMEI data if any
                const existingImeis = purchaseImeiData[productId] || [];

                // Start with minimum rows (2 or existing count, whichever is higher)
                const initialRowCount = Math.max(2, existingImeis.length);
                const maxRowsToShow = Math.min(initialRowCount, product.quantity);

                // Add initial IMEI input rows for this product
                for (let i = 0; i < maxRowsToShow; i++) {
                    const existingValue = existingImeis[i] || '';
                    addImeiRowForProduct(productId, product.productName, product.quantity,
                        existingValue);
                }

                // Update add button visibility for this product
                updateAddButtonVisibility(productId);
            });

            updateSelect2ImeiCount();
            updateProductProgress();
        }

        // Helper function to add a single IMEI row for a product
        function addImeiRowForProduct(productId, productName, maxQuantity, value = '') {
            const currentRows = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]`);
            const rowIndex = currentRows.length;

            const row = `
                <tr data-product-id="${productId}" data-imei-index="${rowIndex}">
                    <td>${rowIndex + 1}</td>
                    <td>
                        <input type="text" 
                               class="form-control form-control-sm purchase-imei-input" 
                               placeholder="Enter IMEI for ${productName}" 
                               data-product-id="${productId}" 
                               value="${value}" />
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-product-imei-row" data-product-id="${productId}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            // Insert after the last row of this product
            const lastRow = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]:last`);
            if (lastRow.length > 0) {
                lastRow.after(row);
            } else {
                // If no rows exist, insert after the header
                const headerRow = $(`#purchaseImeiTable tbody tr[data-product-header="${productId}"]`);
                headerRow.after(row);
            }

            // Re-number rows for this product
            renumberProductRows(productId);
        }

        // Helper function to renumber rows for a specific product
        function renumberProductRows(productId) {
            const productRows = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]`);
            productRows.each(function(index) {
                $(this).find('td:first').text(index + 1);
                $(this).attr('data-imei-index', index);
            });
        }

        function updateProductProgress() {
            // Update progress for each visible product
            $('#purchaseImeiTable tbody tr[data-product-header]').each(function() {
                const productId = $(this).data('product-header');
                const productInputs = $(
                    `#purchaseImeiTable tbody input[data-product-id="${productId}"]`);
                const filledInputs = productInputs.filter(function() {
                    return $(this).val().trim() !== '';
                });

                const product = pendingImeiProducts.find(p => p.productId == productId);
                if (product) {
                    $(`#progress-${productId}`).text(
                        `${filledInputs.length}/${product.quantity} entered`);
                }
            });
        }

        function updateUnifiedPurchaseImeiCount() {
            const totalInputs = $('#purchaseImeiTable tbody input.purchase-imei-input').length;
            const filledInputs = $('#purchaseImeiTable tbody input.purchase-imei-input').filter(function() {
                return $(this).val().trim() !== '';
            }).length;

            let countByProduct = {};
            pendingImeiProducts.forEach(product => {
                const productInputs = $(
                    `#purchaseImeiTable tbody input.purchase-imei-input[data-product-id="${product.productId}"]`
                );
                const productFilled = productInputs.filter(function() {
                    return $(this).val().trim() !== '';
                }).length;
                countByProduct[product.productName] = `${productFilled}/${product.quantity}`;
            });

            const countDisplay = Object.entries(countByProduct).map(([name, count]) => `${name}: ${count}`)
                .join(' | ');
            $('#purchaseImeiCountDisplay').text(`Total: ${filledInputs}/${totalInputs} | ${countDisplay}`);
        }

        function processPurchase() {
            console.log('Processing purchase with IMEI data:', purchaseImeiData);

            const formData = new FormData($('#purchaseForm')[0]);

            if (purchaseId && isNaN(purchaseId)) {
                toastr.error('Invalid purchase ID.', 'Error');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            const purchaseDate = formatDate($('#purchase-date').val());
            const paidDate = formatDate($('#payment-date').val());

            if (!purchaseDate || !paidDate) {
                toastr.error('Invalid date format. Please use YYYY-MM-DD.', 'Error');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            formData.append('purchase_date', purchaseDate);
            // formData.append('paid_date', paidDate);
            formData.append('final_total', $('#final-total').val());

            // FIX: Send discount and tax fields to server
            formData.append('discount_type', $('#discount-type').val() || '');
            formData.append('discount_amount', $('#discount-amount').val() || 0);
            formData.append('tax_type', $('#tax-type').val() || '');
            formData.append('tax_amount', parseFloat($('#tax-display').text().replace(/[^0-9.-]/g, '')) || 0);

            // CRITICAL FIX: Get ALL rows from DataTable (including paginated ones)
            // document.querySelectorAll() only gets visible DOM rows!
            let allRows = [];
            if ($.fn.DataTable.isDataTable('#purchase_product')) {
                // Use DataTables API to get ALL rows across all pages
                purchaseProductTable.rows().every(function() {
                    allRows.push(this.node());
                });
            } else {
                // Fallback: get visible rows
                allRows = document.querySelectorAll('#purchase_product tbody tr');
            }

            allRows.forEach((row, index) => {
                const productId = $(row).data('id');
                const quantity = $(row).find('.purchase-quantity').val() || 0;
                const price = $(row).find('.product-price').val() || 0;
                const discountPercent = $(row).find('.discount-percent').val() || 0;
                const unitCost = $(row).find('.unit-cost').val() || 0;
                const wholesalePrice = $(row).find('.wholesale-price').val() || 0;
                const specialPrice = $(row).find('.special-price').val() || 0;
                const retailPrice = $(row).find('.retail-price').val() || 0;
                const maxRetailPrice = $(row).find('.max-retail-price').val() || 0;
                // FIX: Parse float to ensure numeric value is sent to server
                const total = parseFloat($(row).find('.sub-total').text().replace(/,/g, '')) || 0;
                const batchNo = $(row).find('.batch_no').val() || '';
                const expiryDate = $(row).find('.expiry-date').val();

                formData.append(`products[${index}][product_id]`, productId);
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][price]`, price);
                formData.append(`products[${index}][discount_percent]`, discountPercent);
                formData.append(`products[${index}][unit_cost]`, unitCost);
                formData.append(`products[${index}][wholesale_price]`, wholesalePrice);
                formData.append(`products[${index}][special_price]`, specialPrice);
                formData.append(`products[${index}][retail_price]`, retailPrice);
                formData.append(`products[${index}][max_retail_price]`, maxRetailPrice);
                formData.append(`products[${index}][total]`, total);
                formData.append(`products[${index}][batch_no]`, batchNo);
                formData.append(`products[${index}][expiry_date]`, expiryDate);

                // Add IMEI data if available
                if (purchaseImeiData[productId]) {
                    console.log(`Adding IMEI data for product ${productId}:`, purchaseImeiData[
                        productId]);
                    formData.append(`products[${index}][imei_numbers]`, JSON.stringify(purchaseImeiData[
                        productId]));
                } else {
                    console.log(`No IMEI data found for product ${productId}`);
                }
            });

            const url = purchaseId ? `/purchases/update/${purchaseId}` : '/purchases/store';
            const method = 'POST';

            $.ajax({
                url: url,
                type: method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: handleAjaxSuccess,
                error: handleAjaxError('saving purchase')
            });
        }

        // IMEI Modal Event Handlers - Select2 Based

        // Select2 product selection change
        $(document).on('change', '#purchaseImeiProductSelect', function() {
            const selectedProductIds = $(this).val() || [];
            console.log('Selected products for IMEI entry:', selectedProductIds);
            loadImeiEntrySectionForProducts(selectedProductIds);
        });

        // IMEI input change to update progress
        $(document).on('input', '.purchase-imei-input', function() {
            updateProductProgress();
            updateSelect2ImeiCount();
        });

        // Add more IMEI rows for specific product
        $(document).on('click', '.add-imei-row', function() {
            const productId = $(this).data('product-id');
            const product = pendingImeiProducts.find(p => p.productId == productId);
            if (!product) {
                console.error(`Product not found: ${productId}`);
                return;
            }

            // Check current rows for this product (excluding header rows)
            const currentRows = $(
                `#purchaseImeiTable tbody tr[data-product-id="${productId}"]:not([data-product-header])`
            );
            const currentRowCount = currentRows.length;

            console.log(
                `Add row for ${product.productName}: Current rows = ${currentRowCount}, Max quantity = ${product.quantity}`
            );

            // Check if we've reached the quantity limit
            if (currentRowCount >= product.quantity) {
                toastr.warning(
                    `Cannot add more IMEI rows. Maximum quantity for ${product.productName} is ${product.quantity}`,
                    'Quantity Limit Reached');
                console.warn(
                    `Quantity limit reached for product ${product.productName}: ${currentRowCount}/${product.quantity}`
                );
                return;
            }

            const newIndex = currentRowCount;
            const newRow = `
                <tr data-product-id="${productId}" data-imei-index="${newIndex}">
                    <td>${newIndex + 1}</td>
                    <td>
                        <input type="text" 
                               class="form-control purchase-imei-input" 
                               placeholder="Enter IMEI for ${product.productName}" 
                               data-product-id="${productId}" />
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success add-imei-row" data-product-id="${productId}" ${currentRowCount + 1 >= product.quantity ? 'style="display:none;"' : ''}>
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger ml-1 remove-imei-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            // Insert after the last row of this product
            const lastRow = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]:last`);
            lastRow.after(newRow);

            // Update progress and manage add button visibility
            updateProductProgress();
            updateAddButtonVisibility(productId);

            console.log(
                `✅ Added new row for ${product.productName}. New count: ${currentRowCount + 1}/${product.quantity}`
            );
        });

        // Function to update add button visibility based on quantity limits
        function updateAddButtonVisibility(productId) {
            const product = pendingImeiProducts.find(p => p.productId == productId);
            if (!product) return;

            const currentRows = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]`);
            const currentRowCount = currentRows.length;

            // Hide/show the main "Add Row" button in the header
            const headerAddButton = $(
                `#purchaseImeiTable tbody tr[data-product-header="${productId}"] .add-product-row`);

            if (currentRowCount >= product.quantity) {
                headerAddButton.hide();
                console.log(
                    `🚫 Hiding add button for ${product.productName} (${currentRowCount}/${product.quantity})`
                );
            } else {
                headerAddButton.show();
                console.log(
                    `✅ Showing add button for ${product.productName} (${currentRowCount}/${product.quantity})`
                );
            }
        }

        // Remove IMEI row
        $(document).on('click', '.remove-imei-row', function() {
            const row = $(this).closest('tr');
            const productId = row.data('product-id');
            const product = pendingImeiProducts.find(p => p.productId == productId);

            if (product) {
                console.log(`🗑️ Removing IMEI row for ${product.productName}`);
            }

            row.remove();

            // Re-number the rows for this product
            const productRows = $(
                `#purchaseImeiTable tbody tr[data-product-id="${productId}"]:not([data-product-header])`
            );
            productRows.each(function(index) {
                $(this).find('td:first').text(index + 1);
                $(this).attr('data-imei-index', index);
            });

            updateProductProgress();
            updateSelect2ImeiCount();

            // Update add button visibility after removal
            if (productId) {
                updateAddButtonVisibility(productId);
            }
        });

        // New Add Row button in product header
        $(document).on('click', '.add-product-row', function() {
            const productId = $(this).data('product-id');
            const product = pendingImeiProducts.find(p => p.productId == productId);
            if (!product) {
                console.error(`Product not found: ${productId}`);
                return;
            }

            // Check current rows for this product
            const currentRows = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]`);
            const currentRowCount = currentRows.length;

            console.log(
                `Add row for ${product.productName}: Current rows = ${currentRowCount}, Max quantity = ${product.quantity}`
            );

            // Check if we've reached the quantity limit
            if (currentRowCount >= product.quantity) {
                toastr.warning(
                    `Cannot add more IMEI rows. Maximum quantity for ${product.productName} is ${product.quantity}`,
                    'Quantity Limit Reached');
                console.warn(
                    `Quantity limit reached for product ${product.productName}: ${currentRowCount}/${product.quantity}`
                );
                return;
            }

            // Add a new row for this product
            addImeiRowForProduct(productId, product.productName, product.quantity);

            // Update progress and button visibility
            updateProductProgress();
            updateAddButtonVisibility(productId);

            console.log(
                `✅ Added new row for ${product.productName}. New count: ${currentRowCount + 1}/${product.quantity}`
            );
        });

        // New Remove Row button for individual rows
        $(document).on('click', '.remove-product-imei-row', function() {
            const row = $(this).closest('tr');
            const productId = row.data('product-id');
            const product = pendingImeiProducts.find(p => p.productId == productId);

            // Check minimum rows (at least 1 row should remain)
            const currentRows = $(`#purchaseImeiTable tbody tr[data-product-id="${productId}"]`);
            if (currentRows.length <= 1) {
                toastr.warning('At least one row must remain for IMEI entry', 'Cannot Remove');
                return;
            }

            if (product) {
                console.log(`🗑️ Removing IMEI row for ${product.productName}`);
            }

            row.remove();

            // Re-number the rows for this product
            renumberProductRows(productId);

            updateProductProgress();
            updateSelect2ImeiCount();

            // Update add button visibility after removal
            if (productId) {
                updateAddButtonVisibility(productId);
            }
        });


        // IMEI Modal Event Handlers
        $(document).on('click', '#purchaseSaveImeiButton', function() {
            console.log('=== Saving SELECT2-based IMEI Data ===');

            // Clear any previous errors
            $('#purchaseImeiError').addClass('d-none');
            let hasError = false;
            let allImeiNumbers = [];

            // Collect IMEI data for each product in the current selection
            const selectedProductIds = $('#purchaseImeiProductSelect').val() || [];
            selectedProductIds.forEach(productId => {
                const productImeiInputs = $(
                    `#purchaseImeiTable tbody input.purchase-imei-input[data-product-id="${productId}"]`
                );
                const productImeiNumbers = [];
                const product = pendingImeiProducts.find(p => p.productId == productId);

                console.log(
                    `Collecting IMEI for product ${product.productName} (ID: ${productId})`);

                productImeiInputs.each(function() {
                    const imeiValue = $(this).val().trim();
                    if (imeiValue) {
                        // Check for duplicates within this product
                        if (productImeiNumbers.includes(imeiValue)) {
                            hasError = true;
                            $(this).addClass('is-invalid');
                            $('#purchaseImeiError').removeClass('d-none').text(
                                `Duplicate IMEI numbers found for ${product.productName}`
                            );
                            return false;
                        }

                        // Check for duplicates across all products
                        if (allImeiNumbers.includes(imeiValue)) {
                            hasError = true;
                            $(this).addClass('is-invalid');
                            $('#purchaseImeiError').removeClass('d-none').text(
                                `IMEI ${imeiValue} is used for multiple products`);
                            return false;
                        }

                        productImeiNumbers.push(imeiValue);
                        allImeiNumbers.push(imeiValue);
                        $(this).removeClass('is-invalid');
                    }
                });

                // Store IMEI data for this product
                if (!hasError) {
                    purchaseImeiData[product.productId] = productImeiNumbers;
                    console.log(
                        `✅ Saved ${productImeiNumbers.length} IMEI numbers for ${product.productName}:`,
                        productImeiNumbers);
                }
            });

            if (hasError) {
                console.log('❌ IMEI validation failed');
                return;
            }

            console.log('✅ All IMEI data collected successfully:', purchaseImeiData);

            // Set flag to indicate this is a programmatic close
            isProgrammaticModalClose = true;
            isProcessingImei = false;

            // Close modal and proceed with purchase
            $('#purchaseImeiModal').modal('hide');

            // Proceed with purchase after modal is hidden
            setTimeout(() => {
                console.log('� Proceeding with purchase...');
                processPurchase();
            }, 300);
        });

        $(document).on('click', '#purchaseSkipImeiButton', function() {
            console.log('⏭️ Skipping IMEI entry for all products');

            // Set empty arrays for all pending products
            pendingImeiProducts.forEach(product => {
                purchaseImeiData[product.productId] = [];
            });

            console.log('✅ Skipped IMEI for all products');

            // Set flag to indicate this is a programmatic close
            isProgrammaticModalClose = true;
            isProcessingImei = false;

            // Close modal and proceed with purchase
            $('#purchaseImeiModal').modal('hide');

            // Proceed with purchase after modal is hidden
            setTimeout(() => {
                console.log('� Proceeding with purchase...');
                processPurchase();
            }, 300);
        });

        $(document).on('click', '#purchaseAutoFillImeis', function() {
            console.log('🔧 Auto Fill IMEI button clicked');

            const textareaValue = $('#purchaseImeiInput').val().trim();
            if (!textareaValue) {
                toastr.warning('Please enter IMEI numbers in the textarea first', 'Warning');
                return;
            }

            // Get currently selected products
            const selectedProductIds = $('#purchaseImeiProductSelect').val() || [];
            console.log('Selected product IDs:', selectedProductIds);

            if (selectedProductIds.length === 0) {
                toastr.warning('Please select products first before auto-filling IMEI numbers',
                    'Warning');
                return;
            }

            const imeiLines = textareaValue.split('\n').map(line => line.trim()).filter(line => line);
            console.log('IMEI lines to auto-fill:', imeiLines);

            if (imeiLines.length === 0) {
                toastr.warning('No valid IMEI numbers found in the textarea', 'Warning');
                return;
            }

            let imeiIndex = 0;
            let totalFilled = 0;

            // Distribute IMEI numbers across selected products
            selectedProductIds.forEach(productId => {
                const product = pendingImeiProducts.find(p => p.productId == productId);
                if (!product) {
                    console.error(`Product not found: ${productId}`);
                    return;
                }

                console.log(
                    `Auto-filling for product: ${product.productName} (ID: ${productId})`);

                // Get existing rows for this product (excluding header rows)
                const productRows = $(
                    `#purchaseImeiTable tbody tr[data-product-id="${productId}"]:not([data-product-header])`
                );
                console.log(
                    `Found ${productRows.length} existing rows for product ${productId}`);

                // Fill existing rows first
                productRows.each(function(index) {
                    if (imeiIndex < imeiLines.length) {
                        const input = $(this).find('input.purchase-imei-input');
                        if (input.length > 0) {
                            input.val(imeiLines[imeiIndex]);
                            imeiIndex++;
                            totalFilled++;
                            console.log(
                                `✅ Filled row ${index + 1} for ${product.productName}: ${imeiLines[imeiIndex - 1]}`
                            );
                        }
                    }
                });

                // Add new rows if we have more IMEIs and haven't reached the product quantity limit
                let currentRowCount = productRows.length;
                while (imeiIndex < imeiLines.length && currentRowCount < product.quantity) {
                    // Add a new row for this product
                    const newRowIndex = currentRowCount;
                    const isLastRow = currentRowCount + 1 >= product.quantity;

                    const newRow = `
                        <tr data-product-id="${productId}" data-imei-index="${newRowIndex}">
                            <td>${newRowIndex + 1}</td>
                            <td>
                                <input type="text" 
                                       class="form-control purchase-imei-input" 
                                       placeholder="Enter IMEI for ${product.productName}" 
                                       data-product-id="${productId}" 
                                       value="${imeiLines[imeiIndex]}" />
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-success add-imei-row" data-product-id="${productId}" ${isLastRow ? 'style="display:none;"' : ''}>
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger ml-1 remove-imei-row">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    // Insert after the last row of this product
                    const lastRow = $(
                        `#purchaseImeiTable tbody tr[data-product-id="${productId}"]:last`);
                    if (lastRow.length > 0) {
                        lastRow.after(newRow);
                    } else {
                        // If no rows exist for this product, add after the header
                        const headerRow = $(
                            `#purchaseImeiTable tbody tr[data-product-header="${productId}"]`
                        );
                        if (headerRow.length > 0) {
                            headerRow.after(newRow);
                        } else {
                            console.error(`No header row found for product ${productId}`);
                            continue;
                        }
                    }

                    currentRowCount++;
                    imeiIndex++;
                    totalFilled++;
                    console.log(
                        `➕ Added new row for ${product.productName}: ${imeiLines[imeiIndex - 1]} (${currentRowCount}/${product.quantity})`
                    );
                }

                // Update add button visibility for this product after auto-fill
                updateAddButtonVisibility(productId);
            });

            // Update progress counters
            updateProductProgress();
            updateSelect2ImeiCount();

            console.log(`Auto-fill completed: ${totalFilled} IMEI numbers filled`);

            if (totalFilled > 0) {
                toastr.success(
                    `Auto-filled ${totalFilled} IMEI numbers across ${selectedProductIds.length} products`,
                    'Success');

                // Clear the textarea after successful auto-fill
                $('#purchaseImeiInput').val('');
            }

            if (imeiIndex < imeiLines.length) {
                toastr.info(
                    `${imeiLines.length - imeiIndex} IMEI numbers remaining. Select more products or ensure sufficient row capacity.`,
                    'Info');
            }
        });

        // Handle modal close events
        $('#purchaseImeiModal').on('hidden.bs.modal', function() {
            console.log('📱 Modal hidden event triggered');
            console.log('isProgrammaticModalClose:', isProgrammaticModalClose);
            console.log('isProcessingImei:', isProcessingImei);
            console.log('currentImeiProductIndex:', currentImeiProductIndex);
            console.log('pendingImeiProducts.length:', pendingImeiProducts.length);

            // If this was a programmatic close, reset the flag and don't show cancellation message
            if (isProgrammaticModalClose) {
                isProgrammaticModalClose = false;
                console.log('✅ Programmatic modal close - continuing process');
                return;
            }

            // If modal is closed without completing the process (user cancelled), reset button state
            if (isProcessingImei && currentImeiProductIndex < pendingImeiProducts.length) {
                console.log('❌ User cancelled IMEI entry');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                isProcessingImei = false;
                toastr.warning('IMEI entry cancelled. Purchase not saved.', 'Warning');
            }
        });

        function handleAjaxSuccess(response) {
            if (response.status === 400) {
                document.getElementsByClassName('errorSound')[0].play();

                // Display validation errors in form fields
                $.each(response.errors, function(key, err_value) {
                    $('#' + key + '_error').html(err_value);
                });

                // Also show toastr notifications for validation errors
                $.each(response.errors, function(key, err_value) {
                    if (Array.isArray(err_value)) {
                        // If err_value is an array, join the messages
                        toastr.error(err_value.join(', '), 'Validation Error');
                    } else {
                        // If it's a string, show it directly
                        toastr.error(err_value, 'Validation Error');
                    }
                });
            } else {
                document.getElementsByClassName('successSound')[0].play();
                toastr.success(response.message, purchaseId ? 'Purchase Updated' : 'Purchase Added');
                if (!purchaseId) {
                    resetFormAndValidation();
                }
                setTimeout(function() {
                    window.location.href = "/list-purchase";
                }, 300);

            }
            $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
        }

        function handleAjaxError(action) {
            return function(xhr, status, error) {
                // Handle validation errors returned as JSON
                if (xhr.status === 400 && xhr.responseJSON && xhr.responseJSON.errors) {
                    document.getElementsByClassName('errorSound')[0].play();

                    // Display validation errors in toastr
                    $.each(xhr.responseJSON.errors, function(key, err_value) {
                        if (Array.isArray(err_value)) {
                            toastr.error(err_value.join(', '), 'Validation Error');
                        } else {
                            toastr.error(err_value, 'Validation Error');
                        }
                    });

                    // Also display in form fields if elements exist
                    $.each(xhr.responseJSON.errors, function(key, err_value) {
                        $('#' + key + '_error').html(err_value);
                    });
                } else {
                    // Handle other types of errors
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message ?
                        xhr.responseJSON.message :
                        `Something went wrong while ${action}. Please try again.`;
                    toastr.error(errorMessage, 'Error');
                    console.error('Error:', `Status: ${xhr.status} - ${xhr.statusText}`, 'Response:', xhr
                        .responseText);
                }

                $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' :
                    'Save Purchase');
            };
        }

        function resetFormAndValidation() {
            $('#purchaseForm')[0].reset();
            $('#purchaseForm').validate().resetForm();
            // Use global variable or get existing instance (don't reinitialize!)
            if (purchaseProductTable) {
                purchaseProductTable.clear().draw();
            } else if ($.fn.DataTable.isDataTable('#purchase_product')) {
                // DataTable exists but not in global variable
                purchaseProductTable = $('#purchase_product').DataTable();
                purchaseProductTable.clear().draw();
            }
            updateFooter();
        }

        // Initialize DataTable
        $(document).ready(function() {
            // Check if DataTable already initialized (prevent double initialization)
            if (!$.fn.DataTable.isDataTable('#purchase_product')) {
                // Save DataTable instance to global variable
                purchaseProductTable = $('#purchase_product').DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "searching": false,
                    "info": true,
                    "lengthChange": true
                });
            } else {
                // DataTable already exists, just get the instance
                purchaseProductTable = $('#purchase_product').DataTable();
            }
            fetchProducts();
        });


        $(".show-file").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                // File size validation (5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    toastr.error('File size exceeds 5MB limit. Please choose a smaller file.',
                        'File Too Large');
                    $(this).val(''); // Clear the file input
                    return;
                }

                // File type validation
                const allowedTypes = [
                    'application/pdf',
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                    'text/csv', 'application/csv',
                    'application/zip', 'application/x-zip-compressed',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ];

                const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'csv', 'zip', 'doc',
                    'docx'
                ];
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.split('.').pop();

                if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                    toastr.error(
                        'Invalid file type. Please upload PDF, images, CSV, ZIP, or DOC files only.',
                        'Invalid File Type');
                    $(this).val(''); // Clear the file input
                    clearFileUpload();
                    return;
                }

                if (file.type === "application/pdf") {
                    reader.onload = function(e) {
                        // Create a blob URL for the PDF
                        const blob = new Blob([e.target.result], {
                            type: 'application/pdf'
                        });
                        const blobUrl = URL.createObjectURL(blob);

                        // Try to load PDF in iframe
                        const iframe = $("#purchase-pdfViewer");
                        iframe.attr("src", blobUrl);
                        iframe.show();
                        $("#purchase-selectedImage").hide();

                        // Add fallback link in case iframe doesn't work
                        iframe.after(`
                            <div id="pdf-fallback" class="text-center mt-2" style="display: none;">
                                <p class="text-muted mb-2">PDF preview not supported in your browser</p>
                                <a href="${blobUrl}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Open PDF in New Tab
                                </a>
                            </div>
                        `);

                        // Check if iframe loaded successfully after a short delay
                        setTimeout(function() {
                            const iframeDoc = iframe[0].contentDocument || iframe[0]
                                .contentWindow.document;
                            if (!iframeDoc || iframeDoc.body.children.length === 0) {
                                // Iframe didn't load, show fallback
                                iframe.hide();
                                $("#pdf-fallback").show();
                            } else {
                                $("#pdf-fallback").hide();
                            }
                        }, 1500);

                        // Update the help text to show PDF is loaded
                        $(".preview-container .text-muted").html(
                            '<i class="fas fa-file-pdf text-danger"></i> PDF file loaded successfully'
                        );

                        toastr.success('PDF file uploaded successfully!', 'File Uploaded');
                    };
                    reader.readAsArrayBuffer(file);
                } else if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#purchase-selectedImage").attr("src", e.target.result);
                        $("#purchase-selectedImage").show();
                        $("#purchase-pdfViewer").hide();
                        $("#pdf-fallback").remove(); // Remove any PDF fallback

                        // Reset help text
                        $(".preview-container .text-muted").html(
                            '<i class="fas fa-info-circle"></i> Upload a file to see preview (Images & PDFs supported)'
                        );

                        toastr.success('Image file uploaded successfully!', 'File Uploaded');
                    };
                    reader.readAsDataURL(file);
                } else {
                    // Handle other supported file types (CSV, ZIP, DOC, etc.)
                    $("#purchase-selectedImage").attr("src",
                        "/assets/images/No Product Image Available.png");
                    $("#purchase-selectedImage").show();
                    $("#purchase-pdfViewer").hide();
                    $("#pdf-fallback").remove(); // Remove any PDF fallback

                    // Update help text to show file type
                    const fileType = file.name.split('.').pop().toUpperCase();
                    $(".preview-container .text-muted").html(
                        `<i class="fas fa-file text-primary"></i> ${fileType} file uploaded (Preview not available)`
                    );

                    toastr.success(
                        'File uploaded successfully. Preview not available for this file type.',
                        'File Uploaded');
                    // Don't call readAsDataURL for non-preview files
                    return;
                }

                reader.onerror = function() {
                    toastr.error('Error reading file. Please try again.', 'File Read Error');
                    $(input).val(''); // Clear the file input
                    clearFileUpload();
                };

                // Only call readAsDataURL if not already called above
                if (file.type !== "application/pdf" && file.type.startsWith("image/")) {
                    // Already called above
                }
            } else {
                // No file selected or file input cleared
                $("#purchase-selectedImage").attr("src",
                    "/assets/images/No Product Image Available.png");
                $("#purchase-selectedImage").show();
                $("#purchase-pdfViewer").hide();
            }
        });

        // Function to clear file upload and reset preview
        function clearFileUpload() {
            // Clean up any blob URLs to prevent memory leaks
            const pdfViewer = document.getElementById('purchase-pdfViewer');
            if (pdfViewer && pdfViewer.src && pdfViewer.src.startsWith('blob:')) {
                URL.revokeObjectURL(pdfViewer.src);
            }

            $('#purchase_attach_document').val('');
            $("#purchase-selectedImage").attr("src", "/assets/images/No Product Image Available.png");
            $("#purchase-selectedImage").show();
            $("#purchase-pdfViewer").hide();
            $("#purchase-pdfViewer").attr("src", "");
            $("#pdf-fallback").remove(); // Remove PDF fallback if exists

            // Reset help text
            $(".preview-container .text-muted").html(
                '<i class="fas fa-info-circle"></i> Upload a file to see preview (Images & PDFs supported)');
        }

        // Add clear button functionality (if needed)
        $(document).on('click', '.clear-file-upload', function() {
            clearFileUpload();
            toastr.info('File upload cleared.', 'Cleared');
        });

        // Event listener for dynamic table changes
        $('#purchase_product').on('input', '.purchase-quantity, .discount-percent, .product-tax', function() {
            const row = $(this).closest('tr');
            updateRow(row);
            updateFooter();
        });

        // Event listener for deleting a row
        $('#purchase_product').on('click', '.delete-product', function() {
            $(this).closest('tr').remove();
            updateFooter();
            toastr.info('Product removed from the table.', 'Info');
        });

        // Fetch suppliers using AJAX
        $('#supplier-id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const supplierDetails = selectedOption.data('details');

            if (supplierDetails) {
                const openingBalance = parseFloat(supplierDetails.opening_balance || 0);
                $('#advance-payment').val(openingBalance.toFixed(2));
                const paymentDue = calculatePaymentDue(openingBalance);
                $('.payment-due').text(paymentDue.toFixed(2));
                $('#supplier-name').text(`${supplierDetails.first_name} ${supplierDetails.last_name}`);
                $('#supplier-phone').text(supplierDetails.mobile_no);
            }
        });

        // Example function to calculate payment due
        function calculatePaymentDue(openingBalance) {
            const totalPurchase = parseFloat($('#purchase-total').val() || 0);
            return totalPurchase - openingBalance;
        }

        // Move viewPurchase function to global scope
        window.viewPurchase = function(purchaseId) {
            console.log('Attempting to view purchase with ID:', purchaseId);
            $.ajax({
                url: '/get-all-purchases-product/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    console.log('Sending request to:', '/get-all-purchases-product/' +
                        purchaseId);
                },
                success: function(response) {
                    console.log('Success response:', response);
                    if (!response.purchase) {
                        alert('Purchase data not found.');
                        return;
                    }

                    var purchase = response.purchase;
                    $('#modalTitle').text('Purchase Details - ' + purchase.reference_no);
                    $('#supplierDetails').text((purchase.supplier ? purchase.supplier
                        .first_name + ' ' + purchase.supplier.last_name :
                        'Unknown Supplier'));
                    $('#locationDetails').text((purchase.location ? purchase.location.name :
                        'Unknown Location'));
                    $('#purchaseDetails').text('Date: ' + purchase.purchase_date +
                        ', Status: ' + purchase.purchasing_status);

                    var productsTable = $('#productsTable tbody');
                    productsTable.empty();
                    if (purchase.purchase_products && purchase.purchase_products.length > 0) {
                        purchase.purchase_products.forEach(function(product, index) {
                            let row = $('<tr>');
                            row.append('<td>' + (index + 1) + '</td>');
                            row.append('<td>' + (product.product ? product.product
                                .product_name : 'Unknown Product') + '</td>');
                            row.append('<td>' + (product.product ? product.product.sku :
                                'N/A') + '</td>');
                            row.append('<td>' + product.quantity + '</td>');
                            row.append('<td>' + product.unit_cost + '</td>');
                            row.append('<td>' + product.total + '</td>');
                            productsTable.append(row);
                        });
                    } else {
                        productsTable.append(
                            '<tr><td colspan="6" class="text-center">No products found</td></tr>'
                        );
                    }

                    var paymentInfoTable = $('#paymentInfoTable tbody');
                    paymentInfoTable.empty();
                    if (purchase.payments && purchase.payments.length > 0) {
                        purchase.payments.forEach(function(payment) {
                            let row = $('<tr>');
                            row.append('<td>' + (payment.payment_date || 'N/A') +
                                '</td>');
                            row.append('<td>' + (payment.id || 'N/A') + '</td>');
                            row.append('<td>' + (payment.amount || '0.00') + '</td>');
                            row.append('<td>' + (payment.payment_method || 'N/A') +
                                '</td>');
                            row.append('<td>' + (payment.notes || 'No notes') +
                                '</td>');
                            paymentInfoTable.append(row);
                        });
                    } else {
                        paymentInfoTable.append(
                            '<tr><td colspan="5" class="text-center">No payments found</td></tr>'
                        );
                    }

                    var amountDetailsTable = $('#amountDetailsTable tbody');
                    amountDetailsTable.empty();
                    amountDetailsTable.append('<tr><td>Total: ' + purchase.total +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Discount: ' + purchase.discount_amount +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Final Total: ' + purchase.final_total +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Paid: ' + purchase.total_paid +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Due: ' + purchase.total_due +
                        '</td></tr>');

                    $('#viewPurchaseProductModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching purchase details:");
                    console.error("Status:", status);
                    console.error("Error:", error);
                    console.error("Response Text:", xhr.responseText);
                    console.error("Status Code:", xhr.status);

                    let errorMessage = 'Unknown error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const responseObj = JSON.parse(xhr.responseText);
                            errorMessage = responseObj.message || responseObj.error ||
                                errorMessage;
                        } catch (e) {
                            errorMessage = xhr.responseText;
                        }
                    }

                    alert('Error loading purchase details: ' + errorMessage);
                }
            });
        };

        // Also create a legacy function name for compatibility
        function viewPurchase(purchaseId) {
            window.viewPurchase(purchaseId);
        }



        $(document).ready(function() {
            fetchPurchases();

            // Fetch and display data
            function fetchPurchases() {
                $.ajax({
                    url: '/get-all-purchases',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var table = $('#purchase-list').DataTable();
                        table.clear().draw();
                        if (response.purchases && response.purchases.length > 0) {
                            // Sort purchases by id descending (or by purchase_date if you prefer)
                            response.purchases.sort(function(a, b) {
                                // Sort by id descending
                                return b.id - a.id;

                                return new Date(b.purchase_date) - new Date(a
                                    .purchase_date);
                            });
                            response.purchases.forEach(function(item) {
                                let row = $('<tr data-id="' + item.id + '">');
                                row.append(
                                    '<td><a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    '<button type="button" class="btn btn-outline-info">Actions</button>' +
                                    '</a>' +
                                    '<div class="dropdown-menu dropdown-menu-end">' +
                                    '<a class="dropdown-item" href="#" onclick="viewPurchase(' +
                                    item.id +
                                    ')"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>' +
                                    '<a class="dropdown-item" href="/purchase/edit/' +
                                    item.id +
                                    '"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>' +
                                    '<a class="dropdown-item" href="#" onclick="openImeiManagementModal(' +
                                    item.id +
                                    ')"><i class="fas fa-barcode"></i>&nbsp;&nbsp;Manage IMEI</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>' +
                                    '<a class="dropdown-item" href="#" onclick="openPaymentModal(event, ' +
                                    item.id +
                                    ')"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;Add payments</a>' +
                                    '<a class="dropdown-item" href="#" onclick="openViewPaymentModal(event, ' +
                                    item.id +
                                    ')"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;View payments</a>' +
                                    '</div></td>'
                                );
                                row.append('<td>' + item.purchase_date + '</td>');
                                row.append('<td>' + item.reference_no + '</td>');
                                row.append('<td>' + (item.location?.name ||
                                    'Unknown') + '</td>');
                                row.append('<td>' + (item.supplier?.first_name ||
                                    'Unknown') + ' ' + (item.supplier
                                    ?.last_name || '') + '</td>');
                                row.append('<td>' + item.purchasing_status +
                                    '</td>');
                                let paymentStatusBadge = '';
                                if (item.payment_status === 'Due') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-danger">Due</span>';
                                } else if (item.payment_status === 'Partial') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-warning">Partial</span>';
                                } else if (item.payment_status === 'Paid') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-success">Paid</span>';
                                } else {
                                    paymentStatusBadge =
                                        '<span class="badge bg-secondary">' + item
                                        .payment_status + '</span>';
                                }
                                row.append('<td>' + paymentStatusBadge + '</td>');
                                row.append('<td>' + item.final_total + '</td>');
                                row.append('<td>' + item.total_due + '</td>');
                                // Show user name based on user object
                                row.append('<td>' + (item.user?.user_name || item
                                        .user?.user_name || 'Unknown') +
                                    '</td>');
                                table.row.add(row).draw(false);
                            });
                        } else {
                            console.error(
                                "No purchases found or response.purchases is undefined."
                            );
                        }

                        // Initialize or reinitialize the DataTable after adding rows
                        if ($.fn.dataTable.isDataTable('#purchase-list')) {
                            $('#purchase-list').DataTable().destroy();
                        }
                        $('#purchase-list').DataTable();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                    }
                });
            }
            // Show the modal when the button is clicked
            $('#bulkPaymentBtn').off('click').on('click', function() {
                $('#bulkPaymentModal').modal('show');
            });

            // Fetch suppliers and populate the dropdown (only if not already loaded)
            if ($('#supplierSelect option').length <= 1) {
                console.log('Fetching suppliers for bulk payment modal...');
                $.ajax({
                    url: '/supplier-get-all',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Bulk payment modal - Supplier API response:',
                            response);
                        var supplierSelect = $('#supplierSelect');
                        supplierSelect.empty();
                        supplierSelect.append(
                            '<option value="" selected disabled>Select Supplier</option>'
                        );

                        // Handle different response structures
                        let suppliers = [];

                        // Log the response for debugging
                        console.log('Supplier API response:', response);

                        if (response.status === 200 && Array.isArray(response.message)) {
                            suppliers = response.message;
                        } else if (response.status === 200 && Array.isArray(response
                                .data)) {
                            suppliers = response.data;
                        } else if (Array.isArray(response.message)) {
                            suppliers = response.message;
                        } else if (Array.isArray(response.data)) {
                            suppliers = response.data;
                        } else if (Array.isArray(response)) {
                            suppliers = response;
                        } else if (response.status === 404 && typeof response.message ===
                            'string') {
                            // Handle 404 with string message
                            console.warn('No suppliers found:', response.message);
                            suppliers = [];
                        } else if (response.message && typeof response.message ===
                            'string') {
                            // Handle any case where message is a string
                            console.warn('Supplier API returned string message:', response
                                .message);
                            suppliers = [];
                        } else {
                            console.error("Invalid response format for suppliers:",
                                response);
                            suppliers = [];
                        }

                        if (suppliers && suppliers.length > 0) {
                            suppliers.forEach(function(supplier) {
                                // Validate supplier object
                                if (supplier && supplier.id) {
                                    const supplierName = ((supplier.first_name ||
                                        '') + ' ' + (supplier.last_name ||
                                        '')).trim() || 'Unnamed Supplier';
                                    supplierSelect.append(
                                        '<option value="' + supplier.id +
                                        '" data-opening-balance="' + (supplier
                                            .opening_balance || 0) + '">' +
                                        supplierName +
                                        '</option>'
                                    );
                                }
                            });
                        } else {
                            console.warn(
                                "No suppliers found in response. Response structure:",
                                response);
                            supplierSelect.append(
                                '<option value="" disabled>No suppliers available</option>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error fetching suppliers: ", status, error);
                        var supplierSelect = $('#supplierSelect');
                        supplierSelect.empty();
                        supplierSelect.append(
                            '<option value="" selected disabled>Select Supplier</option>'
                        );
                        supplierSelect.append(
                            '<option value="" disabled>Error loading suppliers</option>'
                        );

                        // Show user-friendly error message
                        if (typeof toastr !== 'undefined') {
                            toastr.error(
                                'Failed to load suppliers. Please refresh the page and try again.',
                                'Error');
                        }
                    }
                });
            } // End of supplier dropdown population condition

            let originalOpeningBalance = 0; // Store the actual supplier opening balance

            $('#supplierSelect').off('change').on('change', function() {
                var supplierId = $(this).val();
                originalOpeningBalance = parseFloat($(this).find(':selected').data(
                    'opening-balance')) || 0;

                $('#openingBalance').text(originalOpeningBalance.toFixed(
                    2)); // Display initial balance

                $.ajax({
                    url: '/get-all-purchases',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        supplier_id: supplierId
                    }, // Ensure supplier_id is sent
                    success: function(response) {
                        var purchaseTable = $('#purchaseTable').DataTable();
                        purchaseTable
                            .clear(); // Clear the table before adding new data
                        var totalPurchaseAmount = 0,
                            totalPaidAmount = 0,
                            totalDueAmount = 0;

                        if (response.purchases && response.purchases.length > 0) {
                            response.purchases.forEach(function(purchase) {
                                if (purchase.supplier_id ==
                                    supplierId) { // Filter by supplier ID
                                    var finalTotal = parseFloat(purchase
                                        .final_total) || 0;
                                    var totalPaid = parseFloat(purchase
                                        .total_paid) || 0;
                                    var totalDue = parseFloat(purchase
                                        .total_due) || 0;

                                    if (totalDue > 0) {
                                        totalPurchaseAmount += finalTotal;
                                        totalPaidAmount += totalPaid;
                                        totalDueAmount += totalDue;

                                        purchaseTable.row.add([
                                            purchase.id + " (" +
                                            purchase.reference_no +
                                            ")",
                                            finalTotal.toFixed(2),
                                            totalPaid.toFixed(2),
                                            totalDue.toFixed(2),
                                            '<input type="number" class="form-control purchase-amount" data-purchase-id="' +
                                            purchase.id + '">'
                                        ]).draw();
                                    }
                                }
                            });
                        }

                        $('#totalPurchaseAmount').text(totalPurchaseAmount.toFixed(
                            2));
                        $('#totalPaidAmount').text(totalPaidAmount.toFixed(2));
                        $('#totalDueAmount').text(totalDueAmount.toFixed(2));
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                    }
                });
            });

            $('#globalPaymentAmount').on('input', function() {
                var globalAmount = parseFloat($(this).val()) || 0;
                var supplierOpeningBalance =
                    originalOpeningBalance; // Always use original balance
                var totalDueAmount = parseFloat($('#totalDueAmount').text()) || 0;
                var remainingAmount = globalAmount;

                // Validate global amount
                if (globalAmount > (supplierOpeningBalance + totalDueAmount)) {
                    $(this).addClass('is-invalid').after(
                        '<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>'
                    );
                    return;
                } else {
                    $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
                }

                // Deduct from opening balance first
                let newOpeningBalance = supplierOpeningBalance;
                if (newOpeningBalance > 0) {
                    if (remainingAmount <= newOpeningBalance) {
                        newOpeningBalance -= remainingAmount;
                        remainingAmount = 0;
                    } else {
                        remainingAmount -= newOpeningBalance;
                        newOpeningBalance = 0;
                    }
                }
                $('#openingBalance').text(newOpeningBalance.toFixed(2));

                // Apply the remaining amount to the purchases dynamically
                $('.purchase-amount').each(function() {
                    var purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)')
                        .text());
                    if (remainingAmount > 0) {
                        var paymentAmount = Math.min(remainingAmount, purchaseDue);
                        $(this).val(paymentAmount);
                        remainingAmount -= paymentAmount;
                    } else {
                        $(this).val(0);
                    }
                });
            });

            // Validate individual payment amounts
            $(document).on('input', '.purchase-amount', function() {
                var purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
                var paymentAmount = parseFloat($(this).val());
                if (paymentAmount > purchaseDue) {
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                    $(this).after(
                        '<span class="invalid-feedback d-block">Amount exceeds total due.</span>'
                    );
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            // Function to update the opening balance
            function updateOpeningBalance() {
                var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
                var supplierOpeningBalance = parseFloat($('#supplierSelect').find(':selected').data(
                    'opening-balance')) || 0;
                var totalPayment = 0;

                // Calculate the total payment from individual amounts
                $('.purchase-amount').each(function() {
                    totalPayment += parseFloat($(this).val()) || 0;
                });

                var remainingAmount = globalAmount - totalPayment;

                // Adjust the opening balance based on the remaining amount
                if (remainingAmount >= 0) {
                    $('#openingBalance').text((supplierOpeningBalance - remainingAmount).toFixed(2));
                } else {
                    $('#openingBalance').text("0.00");
                }
            }

            // Handle global payment amount input
            $('#globalPaymentAmount').change(function() {
                updateOpeningBalance();
            });

            // Initialize DataTable
            $(document).ready(function() {
                $('#purchaseTable').DataTable();
            });

            // Handle payment submission
            $('#submitBulkPayment').click(function() {
                var supplierId = $('#supplierSelect').val();
                var paymentMethod = $('#paymentMethod').val();
                var paymentDate = $('#paidOn').val();
                var globalPaymentAmount = $('#globalPaymentAmount').val();
                var purchasePayments = [];

                $('.purchase-amount').each(function() {
                    var purchaseId = $(this).data('purchase-id');
                    var paymentAmount = parseFloat($(this).val());
                    if (paymentAmount > 0) {
                        purchasePayments.push({
                            reference_id: purchaseId,
                            amount: paymentAmount
                        });
                    }
                });

                var paymentData = {
                    entity_type: 'supplier',
                    entity_id: supplierId,
                    payment_method: paymentMethod,
                    payment_date: paymentDate,
                    global_amount: globalPaymentAmount,
                    payments: purchasePayments
                };

                $.ajax({
                    url: '/api/submit-bulk-payment',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(paymentData),
                    success: function(response) {
                        toastr.success(response.message, 'Payment Submitted');
                        $('#bulkPaymentModal').modal('hide');
                        $('#bulkPaymentForm')[0].reset(); // Reset the form
                        fetchPurchases();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                        // alert('Failed to submit payment.');
                    }
                });
            });
            // Define the openPaymentModal function
            window.openPaymentModal = function(event, purchaseId) {
                event.preventDefault();
                // Fetch purchase details and populate the modal
                $.ajax({
                    url: '/get-purchase/' + purchaseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#purchaseId').val(response.id);
                        $('#payment_type').val('purchase');
                        $('#supplier_id').val(response.supplier?.id || '');
                        $('#reference_no').val(response.reference_no);
                        $('#paymentSupplierDetail').text((response.supplier
                            ?.first_name || 'Unknown') + ' ' + (response
                            .supplier?.last_name || ''));
                        $('#referenceNo').text(response.reference_no);
                        $('#paymentLocationDetails').text(response.location?.name ||
                            'Unknown');
                        $('#totalAmount').text(response.final_total);
                        $('#advanceBalance').text('Advance Balance : Rs. ' + response
                            .total_due);
                        $('#totalPaidAmount').text('Total Paid: Rs. ' + response
                            .total_paid);

                        // Set today's date as default in the "Paid On" field
                        var today = new Date().toISOString().split('T')[0];
                        $('#paidOn').val(today);

                        // Set the amount field to the total due amount
                        $('#payAmount').val(response.total_due);

                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(response.total_due);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseJSON.message);
                    }
                });
            }

            // Define the openViewPaymentModal function
            window.openViewPaymentModal = function(event, purchaseId) {
                event.preventDefault();
                // Set the purchaseId in the data attribute of the modal
                $('#viewPaymentModal').data('purchase-id', purchaseId);

                // Fetch payment details and populate the view payment modal
                $.ajax({
                    url: '/get-purchase/' + purchaseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#viewPaymentModal #referenceNo').text(response.reference_no);
                        $('#viewPaymentModal #viewSupplierDetail').text((response
                            .supplier?.prefix || '') + ' ' + (response.supplier
                            ?.first_name || 'Unknown') + ' ' + (response
                            .supplier?.last_name || '') + ' (' + (response
                            .supplier?.mobile_no || '') + ')');
                        $('#viewPaymentModal #viewBusinessDetail').text((response
                            .location?.name || 'Unknown') + ', ' + (response
                            .location?.address || ''));
                        $('#viewPaymentModal #viewReferenceNo').text(response
                            .reference_no);
                        $('#viewPaymentModal #viewDate').text(response.purchase_date);
                        $('#viewPaymentModal #viewPurchaseStatus').text(response
                            .purchasing_status);
                        $('#viewPaymentModal #viewPaymentStatus').text(response
                            .payment_status);

                        $('#viewPaymentModal .modal-body .table tbody').empty();

                        if (Array.isArray(response.payments) && response.payments
                            .length > 0) {
                            response.payments.forEach(function(payment) {
                                $('#viewPaymentModal .modal-body .table tbody')
                                    .append(
                                        '<tr>' +
                                        '<td>' + payment.payment_date +
                                        '</td>' +
                                        '<td>' + payment.reference_no +
                                        '</td>' +
                                        '<td>' + payment.amount + '</td>' +
                                        '<td>' + payment.payment_method +
                                        '</td>' +
                                        '<td>' + (payment.notes || '') +
                                        '</td>' +
                                        '<td>' + (payment.payment_account ||
                                            '') + '</td>' +
                                        '<td><button class="btn btn-sm btn-danger" onclick="deletePayment(' +
                                        payment.id + ')">Delete</button></td>' +
                                        '</tr>'
                                    );
                            });
                        } else {
                            $('#viewPaymentModal .modal-body .table tbody').append(
                                '<tr><td colspan="7" class="text-center">No records found</td></tr>'
                            );
                        }

                        $('#viewPaymentModal').modal('show');
                    },
                    error: function(xhr) {
                        console.log(xhr.responseJSON.message);
                    }
                });
            }

            function deletePayment(paymentId) {
                // Implement the delete payment functionality here
                if (confirm('Are you sure you want to delete this payment?')) {
                    $.ajax({
                        url: '/delete-payment/' + paymentId,
                        type: 'DELETE',
                        success: function(response) {
                            alert('Payment deleted successfully.');
                            $('#viewPaymentModal').modal('hide');
                            fetchPurchases();
                        },
                        error: function(xhr) {
                            console.log(xhr.responseJSON.message);
                        }
                    });
                }
            }

            // Reset the payment form when the modal is closed
            $('#paymentModal').on('hidden.bs.modal', function() {
                $('#paymentForm')[0].reset();
                $('#amountError').hide();
            });
            $('#savePayment').click(function() {
                var formData = new FormData($('#paymentForm')[0]);

                $.ajax({
                    url: '/api/payments',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#paymentModal').modal('hide');
                        fetchPurchases();
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Payment Added');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = '';
                        for (var key in errors) {
                            errorMessage += errors[key] + '\n';
                            // Display errors below each input field
                            if (key === 'amount') {
                                $('#payAmount').addClass('is-invalid');
                                $('#amountError').text(errors[key]).show();
                            } else if (key === 'payment_date') {
                                $('#paidOn').addClass('is-invalid');
                                $('#paidOn').next('.invalid-feedback').text(errors[
                                    key]).show();
                            } else if (key === 'reference_no') {
                                $('#referenceNo').addClass('is-invalid');
                                $('#referenceNo').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'payment_type') {
                                $('#paymentMethod').addClass('is-invalid');
                                $('#paymentMethod').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'reference_id') {
                                $('#referenceId').addClass('is-invalid');
                                $('#referenceId').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'supplier_id') {
                                $('#supplierId').addClass('is-invalid');
                                $('#supplierId').next('.invalid-feedback').text(
                                    errors[key]).show();
                            }
                        }
                    }
                });
            });

            // View Purchase Details
            // Row Click Event
            $('#purchase-list').on('click', 'tr', function(e) {
                // Prevent action if clicking on thead or if no data-id (header/footer rows)
                if ($(this).closest('thead').length || typeof $(this).data('id') ===
                    'undefined') {
                    e.stopPropagation();
                    return;
                }
                if (!$(e.target).closest('.action-icon, .dropdown-menu').length) {
                    var purchaseId = $(this).data(
                        'id'); // Extract product ID from data-id attribute
                    $.ajax({
                        url: '/get-all-purchases-product/' + purchaseId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            var purchase = response.purchase;
                            $('#modalTitle').text('Purchase Details - ' + purchase
                                .reference_no);
                            $('#supplierDetails').text(purchase.supplier
                                .first_name + ' ' + purchase.supplier.last_name);
                            $('#locationDetails').text(purchase.location.name);
                            $('#purchaseDetails').text('Date: ' + purchase
                                .purchase_date + ', Status: ' + purchase
                                .purchasing_status);

                            var productsTable = $('#productsTable tbody');
                            productsTable.empty();
                            purchase.purchase_products.forEach(function(product,
                                index) {
                                let row = $('<tr>');
                                row.append('<td>' + (index + 1) + '</td>');
                                row.append('<td>' + product.product
                                    .product_name + '</td>');
                                row.append('<td>' + product.product.sku +
                                    '</td>');
                                row.append('<td>' + product.quantity +
                                    '</td>');
                                row.append('<td>' + (product.unit_cost ||
                                        0) +
                                    '</td>');
                                row.append('<td>' + product.total +
                                    '</td>');
                                productsTable.append(row);
                            });

                            var paymentInfoTable = $('#paymentInfoTable tbody');
                            paymentInfoTable.empty();
                            purchase.payments.forEach(function(payment) {
                                let row = $('<tr>');
                                row.append('<td>' + payment.payment_date +
                                    '</td>');
                                row.append('<td>' + payment.id + '</td>');
                                row.append('<td>' + payment.amount +
                                    '</td>');
                                row.append('<td>' + payment.payment_method +
                                    '</td>');
                                row.append('<td>' + payment.notes +
                                    '</td>');
                                paymentInfoTable.append(row);
                            });

                            var amountDetailsTable = $('#amountDetailsTable tbody');
                            amountDetailsTable.empty();
                            amountDetailsTable.append('<tr><td>Total: ' + purchase
                                .total + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Discount: ' +
                                purchase.discount_amount + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Final Total: ' +
                                purchase.final_total + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Total Paid: ' +
                                purchase.total_paid + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Total Due: ' +
                                purchase.total_due + '</td></tr>');

                            $('#viewPurchaseProductModal').modal('show');
                        },
                        error: function(xhr) {
                            console.log(xhr.responseJSON.message);
                        }
                    });
                }
            });
        });

        function deletePayment(paymentId) {
            // Implement the delete payment functionality here
            console.log('Delete payment:', paymentId);
        }

        // IMEI Management Functions
        let currentPurchaseId = null;
        let currentImeiProducts = [];
        let currentSelectedProduct = null;

        // Function to open IMEI management modal
        window.openImeiManagementModal = function(purchaseId) {
            currentPurchaseId = purchaseId;
            console.log('Opening IMEI management for purchase:', purchaseId);

            // Fetch IMEI products for this purchase
            $.ajax({
                url: `/purchases/${purchaseId}/imei-products`,
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        currentImeiProducts = response.imei_products;
                        populateImeiManagementModal(response.purchase, response.imei_products);
                        $('#imeiManagementModal').modal('show');
                    } else {
                        toastr.error('Failed to fetch IMEI products');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 404) {
                        toastr.info('No IMEI products found in this purchase');
                    } else {
                        toastr.error('Error fetching IMEI products');
                    }
                    console.error('Error:', xhr.responseJSON);
                }
            });
        };

        // Make these functions globally accessible
        window.openAddImeiModal = function(purchaseProductId, productName, missingCount) {
            currentSelectedProduct = currentImeiProducts.find(p => p.purchase_product_id ===
                purchaseProductId);

            // Check if there are actually missing IMEI numbers
            if (missingCount <= 0) {
                toastr.info(`${productName} already has all required IMEI numbers`);
                return;
            }

            $('#addImeiModalLabel').text(`Add IMEI Numbers - ${productName}`);
            $('#addImeiProductInfo').text(`Missing: ${missingCount} IMEI numbers`);
            $('#addImeiPurchaseProductId').val(purchaseProductId);

            // Clear previous data
            $('#imeiInputMethod').val('individual');
            $('#individualImeiContainer').show();
            $('#bulkImeiContainer').hide();
            $('#individualImeiTable tbody').empty();
            $('#bulkImeiText').val('');
            $('#bulkImeiSeparator').val('newline');

            // Add input rows for missing IMEI numbers
            for (let i = 1; i <= missingCount; i++) {
                addIndividualImeiRow(i);
            }

            $('#addImeiModal').modal('show');
        };

        window.viewExistingImeis = function(purchaseProductId, productName) {
            const product = currentImeiProducts.find(p => p.purchase_product_id === purchaseProductId);

            $('#viewImeiModalLabel').text(`IMEI Numbers - ${productName}`);

            const imeiTableBody = $('#existingImeiTable tbody');
            imeiTableBody.empty();

            if (product.existing_imeis.length === 0) {
                imeiTableBody.append(
                    '<tr><td colspan="4" class="text-center">No IMEI numbers found</td></tr>');
            } else {
                product.existing_imeis.forEach(function(imei, index) {
                    const statusBadge = imei.status === 'available' ?
                        '<span class="badge bg-success">Available</span>' :
                        '<span class="badge bg-warning">Sold</span>';

                    // Make IMEI editable only if status is 'available'
                    const imeiField = imei.status === 'available' ?
                        `<input type="text" class="form-control form-control-sm editable-imei" value="${imei.imei_number}" data-imei-id="${imei.id}" />` :
                        `<span class="text-muted">${imei.imei_number}</span>`;

                    const deleteBtn = imei.status === 'available' ?
                        `<button class="btn btn-sm btn-danger me-1" onclick="removeImei(${imei.id})"><i class="fas fa-trash"></i></button>` :
                        '<span class="text-muted">Cannot delete</span>';

                    const editBtn = imei.status === 'available' ?
                        `<button class="btn btn-sm btn-warning" onclick="updateImei(${imei.id}, this)"><i class="fas fa-edit"></i></button>` :
                        '';

                    const row = `
                        <tr data-imei-id="${imei.id}">
                            <td>${index + 1}</td>
                            <td>${imeiField}</td>
                            <td>${statusBadge}</td>
                            <td>
                                ${deleteBtn}
                                ${editBtn}
                            </td>
                        </tr>
                    `;
                    imeiTableBody.append(row);
                });
            }

            $('#viewImeiModal').modal('show');
        };

        window.removeImei = function(imeiId) {
            if (confirm('Are you sure you want to remove this IMEI number?')) {
                $.ajax({
                    url: '/purchases/remove-imei',
                    method: 'POST',
                    data: {
                        imei_ids: [imeiId], // Send as array as expected by backend
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success('IMEI number removed successfully');
                            // Remove the row from the table
                            $(`tr[data-imei-id="${imeiId}"]`).remove();
                            // Refresh the main modal after a short delay
                            setTimeout(() => {
                                openImeiManagementModal(currentPurchaseId);
                            }, 500);
                        } else {
                            toastr.error(response.message || 'Failed to remove IMEI number');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to remove IMEI number';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        console.error('Error removing IMEI:', xhr);
                        toastr.error(errorMessage);
                    }
                });
            }
        };

        window.updateImei = function(imeiId, buttonElement) {
            const row = $(buttonElement).closest('tr');
            const imeiInput = row.find('.editable-imei');
            const newImeiValue = imeiInput.val().trim();

            if (!newImeiValue) {
                toastr.warning('IMEI number cannot be empty');
                return;
            }

            // Basic IMEI validation (10-17 digits)
            if (!/^\d{10,17}$/.test(newImeiValue)) {
                toastr.warning('IMEI must be 10-17 digits');
                return;
            }

            // Show Bootstrap confirmation modal
            $('#confirmUpdateImeiText').text(`Update IMEI number to: ${newImeiValue}?`);
            $('#confirmUpdateImeiModal').modal('show');

            // Store the update data for use when confirmed
            window.pendingImeiUpdate = {
                imeiId: imeiId,
                newImeiValue: newImeiValue
            };
        };

        // Function to actually perform the IMEI update
        window.performImeiUpdate = function() {
            if (!window.pendingImeiUpdate) return;

            const {
                imeiId,
                newImeiValue
            } = window.pendingImeiUpdate;

            $.ajax({
                url: '/purchases/update-imei',
                method: 'POST',
                data: {
                    imei_id: imeiId,
                    imei_number: newImeiValue,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success('IMEI number updated successfully');
                        $('#confirmUpdateImeiModal').modal('hide');
                        // Refresh the view modal
                        $('#viewImeiModal').modal('hide');
                        // Refresh the main modal
                        openImeiManagementModal(currentPurchaseId);
                    } else {
                        toastr.error(response.message || 'Failed to update IMEI number');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to update IMEI number';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    console.error('Error updating IMEI:', xhr);
                    toastr.error(errorMessage);
                }
            });

            // Clear pending update
            window.pendingImeiUpdate = null;
        };

        function populateImeiManagementModal(purchase, imeiProducts) {
            // Set modal title
            $('#imeiManagementModalLabel').text(`Manage IMEI Numbers - ${purchase.reference_no}`);

            // Set purchase info
            $('#imeiPurchaseInfo').html(`
                <strong>Date:</strong> ${purchase.purchase_date} | 
                <strong>Supplier:</strong> ${purchase.supplier.first_name} ${purchase.supplier.last_name} | 
                <strong>Location:</strong> ${purchase.location.name}
            `);

            // Clear and populate product list
            const productList = $('#imeiProductList');
            productList.empty();

            if (imeiProducts.length === 0) {
                productList.html(
                    '<div class="alert alert-info">No IMEI-enabled products found in this purchase.</div>');
                return;
            }

            imeiProducts.forEach(function(product) {
                const statusClass = product.missing_imei_count > 0 ? 'alert-warning' : 'alert-success';
                const statusText = product.missing_imei_count > 0 ?
                    `Missing ${product.missing_imei_count} IMEI numbers` :
                    'All IMEI numbers added';

                // Show Add IMEI button only if there are missing IMEI numbers
                const addImeiButton = product.missing_imei_count > 0 ?
                    `<button type="button" class="btn btn-sm btn-primary" onclick="openAddImeiModal(${product.purchase_product_id}, '${product.product_name}', ${product.missing_imei_count})">
                        <i class="fas fa-plus"></i> Add IMEI
                    </button>` :
                    `<span class="btn btn-sm btn-success disabled">
                        <i class="fas fa-check"></i> Complete
                    </span>`;

                const productCard = `
                    <div class="card mb-3" data-product-id="${product.product_id}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">${product.product_name}</h6>
                            <div class="btn-group">
                                ${addImeiButton}
                                <button type="button" class="btn btn-sm btn-info" onclick="viewExistingImeis(${product.purchase_product_id}, '${product.product_name}')">
                                    <i class="fas fa-list"></i> View All
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <small><strong>Batch:</strong> ${product.batch_no}</small><br>
                                    <small><strong>Quantity:</strong> ${product.quantity_purchased}</small>
                                </div>
                                <div class="col-md-6">
                                    <small><strong>Existing IMEI:</strong> ${product.existing_imei_count}</small><br>
                                    <small><strong>Unit Cost:</strong> $${product.unit_cost}</small>
                                </div>
                            </div>
                            <div class="alert ${statusClass} mt-2 mb-0">${statusText}</div>
                        </div>
                    </div>
                `;
                productList.append(productCard);
            });
        }

        function addIndividualImeiRow(index) {
            const row = `
                <tr>
                    <td>${index}</td>
                    <td><input type="text" class="form-control imei-input" placeholder="Enter IMEI number" pattern="[0-9]{10,17}" title="IMEI must be 10-17 digits"></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
            $('#individualImeiTable tbody').append(row);
        }

        // Event handlers for IMEI management
        $(document).ready(function() {
            // Input method change
            $('#imeiInputMethod').on('change', function() {
                if ($(this).val() === 'individual') {
                    $('#individualImeiContainer').show();
                    $('#bulkImeiContainer').hide();
                } else {
                    $('#individualImeiContainer').hide();
                    $('#bulkImeiContainer').show();
                }
            });

            // Add more IMEI rows
            $('#addMoreImeiRows').on('click', function() {
                const currentRowCount = $('#individualImeiTable tbody tr').length;
                addIndividualImeiRow(currentRowCount + 1);
            });

            // Save IMEI numbers
            $('#saveImeiNumbers').on('click', function() {
                const inputMethod = $('#imeiInputMethod').val();
                const purchaseProductId = $('#addImeiPurchaseProductId').val();

                let imeiNumbers = [];

                if (inputMethod === 'individual') {
                    // Collect from individual inputs
                    $('#individualImeiTable tbody input.imei-input').each(function() {
                        const value = $(this).val().trim();
                        if (value) {
                            imeiNumbers.push(value);
                        }
                    });
                } else {
                    // Process bulk text
                    const bulkText = $('#bulkImeiText').val().trim();
                    const separator = $('#bulkImeiSeparator').val();

                    if (bulkText) {
                        $.ajax({
                            url: '/purchases/bulk-add-imei',
                            method: 'POST',
                            data: {
                                purchase_product_id: purchaseProductId,
                                imei_text: bulkText,
                                separator: separator,
                                _token: $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                handleImeiSaveResponse(response);
                            },
                            error: function(xhr) {
                                handleImeiSaveError(xhr);
                            }
                        });
                        return;
                    }
                }

                if (imeiNumbers.length === 0) {
                    toastr.warning('Please enter at least one IMEI number');
                    return;
                }

                // Save individual IMEI numbers
                $.ajax({
                    url: '/purchases/add-imei',
                    method: 'POST',
                    data: {
                        purchase_product_id: purchaseProductId,
                        imei_numbers: imeiNumbers,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        handleImeiSaveResponse(response);
                    },
                    error: function(xhr) {
                        handleImeiSaveError(xhr);
                    }
                });
            });

            function handleImeiSaveResponse(response) {
                if (response.status === 200) {
                    toastr.success(`Successfully added ${response.added_count} IMEI numbers`);
                    $('#addImeiModal').modal('hide');
                    // Refresh the main modal
                    openImeiManagementModal(currentPurchaseId);
                } else {
                    toastr.error('Failed to add IMEI numbers');
                }
            }

            function handleImeiSaveError(xhr) {
                if (xhr.status === 400 && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (Array.isArray(errors)) {
                        // Bulk validation errors
                        let errorMessage = 'Validation errors found:\n';
                        errors.slice(0, 5).forEach(error => {
                            errorMessage += '• ' + error + '\n';
                        });
                        if (errors.length > 5) {
                            errorMessage += `... and ${errors.length - 5} more errors`;
                        }
                        toastr.error(errorMessage);
                    } else {
                        // Regular validation errors
                        let errorMessage = 'Validation errors:\n';
                        Object.values(errors).forEach(fieldErrors => {
                            fieldErrors.forEach(error => {
                                errorMessage += '• ' + error + '\n';
                            });
                        });
                        toastr.error(errorMessage);
                    }
                } else {
                    toastr.error('Error adding IMEI numbers: ' + (xhr.responseJSON?.message ||
                        'Unknown error'));
                }
                console.error('Error:', xhr.responseJSON);
            }
        });
    });
</script>
