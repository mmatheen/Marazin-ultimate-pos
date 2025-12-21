<script>
    $(document).ready(function() {
        let productIndex = 1;
        let locationFilteredProducts = [];

          // Set default status to "pending" and date to today
        $('#status').val('pending').trigger('change');
        let today = new Date();
        let day = String(today.getDate()).padStart(2, '0');
        let month = String(today.getMonth() + 1).padStart(2, '0');
        let year = today.getFullYear();
        let formattedDate = `${day}-${month}-${year}`;
        $('#transfer_date').val(formattedDate);

        // Initialize autocomplete to search by product name OR SKU, and only show products available in the selected "From" location
        const $productSearchInput = $('#productSearch');

        // Add Enter key support for quick selection - Updated with working POS AJAX solution
        $productSearchInput.off('keydown.autocomplete').on('keydown.autocomplete', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();

                const widget = $(this).autocomplete("widget");
                const focused = widget.find(".ui-state-focus");

                let itemToAdd = null;

                if (focused.length > 0) {
                    // Get the item data from the autocomplete instance's active item
                    const autocompleteInstance = $(this).autocomplete("instance");
                    if (autocompleteInstance && autocompleteInstance.menu.active) {
                        itemToAdd = autocompleteInstance.menu.active.data("ui-autocomplete-item");
                    }
                }

                if (itemToAdd && itemToAdd.value !== '') {
                    // Find the product by name or SKU (case-insensitive)
                    const selectedProduct = locationFilteredProducts.find(data =>
                        (data.product.product_name && data.product.product_name.toLowerCase() === itemToAdd.value.toLowerCase()) ||
                        (data.product.sku && data.product.sku.toLowerCase() === itemToAdd.value.toLowerCase())
                    );
                    if (selectedProduct) {
                        addProductWithBatches(selectedProduct);
                        $(this).val('');
                        $(this).autocomplete('close');
                    }
                }

                event.stopImmediatePropagation();
            }
        });

        $productSearchInput.autocomplete({
            minLength: 1,
            source: function(request, response) {
            const fromLocationId = $('#from_location_id').val();
            if (!fromLocationId) {
                response([]);
                return;
            }
            $.ajax({
                url: '/products/stocks/autocomplete',
                method: 'GET',
                data: {
                search: request.term,
                location_id: fromLocationId, // Only fetch products for selected location
                per_page: 1000
                },
                success: function(res) {
                if (res.status === 200 && Array.isArray(res.data)) {
                    // Filter products to only those with stock in the selected location
                    locationFilteredProducts = res.data.filter(data => {
                    // Check if any batch in this location has quantity > 0
                    // Convert batches object to array if needed
                    let batches = [];
                    if (Array.isArray(data.batches)) {
                        batches = data.batches;
                    } else if (data.batches && typeof data.batches === 'object') {
                        batches = Object.values(data.batches);
                    } else if (data.product && Array.isArray(data.product.batches)) {
                        batches = data.product.batches;
                    } else if (data.product && data.product.batches && typeof data.product.batches === 'object') {
                        batches = Object.values(data.product.batches);
                    }
                    return batches.some(batch => {
                        const locationBatches = batch.location_batches || batch.locationBatches || [];
                        return locationBatches.some(locBatch => locBatch.location_id == fromLocationId && parseFloat(locBatch.quantity ?? locBatch.qty) > 0);
                    });
                    });
                    if (locationFilteredProducts.length === 0) {
                    response([{
                        label: "No products found for selected location",
                        value: ""
                    }]);
                    } else {
                    response(locationFilteredProducts.map(data => ({
                        label: data.product.product_name + (data.product.sku ? " (" + data.product.sku + ")" : ""),
                        value: data.product.product_name,
                        sku: data.product.sku
                    })));
                    }
                } else {
                    response([{
                    label: '<span style="color:#888;">No products found for selected location</span>',
                    value: ''
                    }]);
                }
                },
                error: function() {
                response([{
                    label: '<span style="color:#888;">No products found for selected location</span>',
                    value: ''
                }]);
                }
            });
            },
            focus: function(event, ui) {
            // Prevent value from being inserted if it's the "no products" message
            if (ui.item.value === '') {
                event.preventDefault();
                return false;
            }
            $('#productSearch').val(ui.item.label);
            return false;
            },
            select: function(event, ui) {
            // Prevent selection if it's the "no products" message
            if (ui.item.value === '') {
                event.preventDefault();
                return false;
            }
            console.log('Autocomplete select triggered:', ui.item);
            console.log('locationFilteredProducts:', locationFilteredProducts);
            
            // Find the product by name or SKU (case-insensitive)
            const selectedProduct = locationFilteredProducts.find(data =>
                (data.product.product_name && data.product.product_name.toLowerCase() === ui.item.value.toLowerCase()) ||
                (data.product.sku && data.product.sku.toLowerCase() === ui.item.value.toLowerCase())
            );
            
            console.log('Selected product found:', selectedProduct);
            
            if (selectedProduct) {
                addProductWithBatches(selectedProduct);
                $(this).val('');
            } else {
                console.error('Product not found in locationFilteredProducts');
            }
            return false;
            },
            open: function() {
                setTimeout(() => {
                    // Auto-focus first item for Enter key selection - Updated with working POS AJAX solution
                    const autocompleteInstance = $productSearchInput.autocomplete("instance");
                    const menu = autocompleteInstance.menu;
                    const firstItem = menu.element.find("li:first-child");

                    if (firstItem.length > 0 && !firstItem.text().includes("No products found")) {
                        // Properly set the active item using jQuery UI's method
                        menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                        firstItem.addClass("ui-state-focus");
                        menu.active = firstItem;
                    }
                }, 50);
            }
        });

        // Safely set custom _renderItem if instance exists
        var autocompleteInstance = $('#productSearch').autocomplete('instance');
        if (autocompleteInstance) {
            autocompleteInstance._renderItem = function(ul, item) {
                // Render HTML for "no products" message
                if (item.value === '') {
                    return $("<li></li>")
                        .append(item.label)
                        .data('ui-autocomplete-item', item)
                        .appendTo(ul);
                }
                return $("<li></li>")
                    .append($("<div></div>").text(item.label))
                    .data('ui-autocomplete-item', item)
                    .appendTo(ul);
            };
        }

        $.ui.autocomplete.prototype._resizeMenu = function() {
            var ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth());
            ul.css({
            "max-height": "250px",
            "overflow-y": "auto"
            });
        };

        const pathSegments = window.location.pathname.split('/');
        // Only extract ID if we're on an edit page
        const stockTransferId = window.location.pathname.includes('/edit-stock-transfer/') ?
            pathSegments[pathSegments.length - 1] : null;

        // fetchDropdownData('/location-get-all?context=all_locations', $('#from_location_id'), "Select Location");
        // fetchDropdownData('/location-get-all?context=all_locations', $('#to_location_id'), "Select Location");

        // Reusable function to populate any dropdown
    function populateDropdown($select, data, placeholder, selectedId = null) {
        $select.empty().append(`<option selected disabled>${placeholder}</option>`);
        data.forEach(item => {
            const option = $('<option></option>')
                .val(item.id)
                .text(item.name || item.first_name + ' ' + item.last_name);
            $select.append(option);
        });
        if (selectedId) {
            $select.val(selectedId).trigger('change');
        }
    }

    // Fetch all locations and filter for "From" and "To"
    $.ajax({
        url: '/location-get-all?context=all_locations',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === true && Array.isArray(response.data)) {
                const allLocations = response.data;

                // Populate "To Location" with ALL locations
                populateDropdown($('#to_location_id'), allLocations, "Select Location");

                populateDropdown($('#from_location_id'), allLocations, "Select Location");


                // If editing an existing transfer, restore selected values after dropdowns are populated
                if (stockTransferId && stockTransferId !== 'add-stock-transfer' && window.location.pathname.includes('/edit-stock-transfer/')) {
                    console.log('Checking for stock transfer ID:', stockTransferId);
                    console.log('Current pathname:', window.location.pathname);
                    console.log('Attempting to fetch stock transfer data for ID:', stockTransferId);
                    // Fetch stock transfer data after a short delay to ensure dropdowns are ready
                    setTimeout(() => {
                        fetchStockTransferData(stockTransferId);
                    }, 500);
                } else {
                    console.log('Not on edit page, skipping stock transfer data fetch');
                }
            } else {
                console.error('Failed to load locations:', response.message);
                toastr.error('Could not load location data.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching locations:', error);
            toastr.error('Failed to connect to server.');
        }
    });

        $('#from_location_id').on('change', function() {
            // Clear the product search input and table when the location changes
            $('#productSearch').val('');
            $('.add-table-items').empty();
            addTotalRow();
            // No need to prefetch products, autocomplete will fetch as user types
        });

        if (stockTransferId && stockTransferId !== 'add-stock-transfer' && window.location.pathname.includes('/edit-stock-transfer/')) {
            console.log('Secondary check - Attempting to fetch stock transfer data for ID:', stockTransferId);
            // Also try to fetch after locations are loaded
            setTimeout(() => {
                fetchStockTransferData(stockTransferId);
            }, 1000);
        }

        // fetchProductsData is no longer needed, autocomplete handles fetching

        // Function to fetch stock transfer data
        function fetchStockTransferData(stockTransferId) {
            console.log('fetchStockTransferData called with ID:', stockTransferId);

            // Validate that stockTransferId is a number
            if (!stockTransferId || isNaN(stockTransferId)) {
                console.error('Invalid stock transfer ID:', stockTransferId);
                return;
            }

            $.ajax({
                url: `/edit-stock-transfer/${stockTransferId}`,
                method: 'GET',
                success: function(response) {
                    console.log('Fetch response received:', response);
                    if (response.stockTransfer) {
                        console.log('Stock transfer data found, populating form...');
                        populateForm(response.stockTransfer);
                    } else {
                        console.error('No stock transfer data in response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching stock transfer:', {xhr, status, error});
                    toastr.error('Failed to load stock transfer data');
                }
            });
        }

        // Function to populate the form with stock transfer data
        function populateForm(stockTransfer) {
            console.log('populateForm called with data:', stockTransfer);

            // Update page title and headings for editing
            const transferName = stockTransfer.reference_no || `Stock Transfer #${stockTransfer.id}`;

            // Use setTimeout to ensure DOM is ready
            setTimeout(() => {
                document.title = `Edit Stock Transfer`;
                $('#main-page-title').text(`Edit Stock Transfer`);
                $('#breadcrumb-title').text(`Edit ${transferName}`);
                $('#form-card-title').text(`${transferName} - Transfer Details`);
                $('#stock-management-link').text('Stock Transfers');
                $('#button-title').text('Update');

                console.log('Page titles and breadcrumb updated for transfer:', transferName);
            }, 100);

            // Fix: Set date in DD-MM-YYYY format
            let date = stockTransfer.transfer_date.split(' ')[0];
            if (date.includes('-')) {
                let parts = date.split('-');
                if (parts[0].length === 4) date = `${parts[2]}-${parts[1]}-${parts[0]}`;
            }
            console.log('Setting date:', date);
            $('#transfer_date').val(date);

            console.log('Setting reference no:', stockTransfer.reference_no);
            $('#reference_no').val(stockTransfer.reference_no);

            console.log('Setting status:', stockTransfer.status);
            $('#status').val(stockTransfer.status).trigger('change');

            // Set dropdowns directly if options are already loaded
            console.log('Setting from location:', stockTransfer.from_location_id);
            $('#from_location_id').val(stockTransfer.from_location_id).trigger('change');

            console.log('Setting to location:', stockTransfer.to_location_id);
            $('#to_location_id').val(stockTransfer.to_location_id).trigger('change');

            // Wait for dropdowns to be populated if needed
            setTimeout(() => {
                $('#from_location_id').val(stockTransfer.from_location_id).trigger('change');
                $('#to_location_id').val(stockTransfer.to_location_id).trigger('change');

                // Clear products table before adding
                console.log('Clearing products table and adding new products');
                $('.add-table-items').empty();
                productIndex = 1;

                // Add each product row
                if (stockTransfer.stock_transfer_products && stockTransfer.stock_transfer_products.length > 0) {
                    console.log('Adding', stockTransfer.stock_transfer_products.length, 'products to table');
                    stockTransfer.stock_transfer_products.forEach((product, index) => {
                        console.log('Adding product', index + 1, ':', product);
                        addProductToTable(product, true);
                    });
                } else {
                    console.warn('No stock transfer products found');
                }

                addTotalRow();
                updateTotalAmount();
                console.log('Form population completed');
            }, 300);
        }

        // Function to add a product to the table
        function addProductToTable(productData, isEditing = false) {
            console.log('addProductToTable called with:', { productData, isEditing, productIndex });

            const product = productData.product;
            const existingRow = $(`tr[data-product-id="${product.id}"]`);
            if (existingRow.length > 0) {
                console.log('Product already exists in table, updating quantity');
                // Update the quantity if the product already exists in the table
                const quantityInput = existingRow.find('.quantity-input');
                const newQuantity = parseFloat(quantityInput.val()) + productData.quantity;
                quantityInput.val(newQuantity);
                existingRow.find('.quantity-input').trigger('change');
                return;
            }

            // Filter batches to only include those in the selected "From" location
            const fromLocationId = $('#from_location_id').val();
            console.log('From location ID:', fromLocationId);

            // --- FIX: Support batches as object or array, and location_batches as string/number ---
            let batchesArr = [];
            if (Array.isArray(productData.batches)) {
                batchesArr = productData.batches;
            } else if (productData.batches && typeof productData.batches === 'object') {
                batchesArr = Object.values(productData.batches);
            } else if (Array.isArray(product.batches)) {
                batchesArr = product.batches;
            } else if (product.batches && typeof product.batches === 'object') {
                batchesArr = Object.values(product.batches);
            }

            console.log('Batches array:', batchesArr);

            // Only use batches from the selected "From" location
            const batches = batchesArr.flatMap(batch => {
                // Support both camelCase and snake_case for location_batches
                const locationBatches = batch.location_batches || batch.locationBatches || [];
                return locationBatches
                    .filter(locBatch =>
                        locBatch.location_id == fromLocationId &&
                        parseFloat(locBatch.quantity ?? locBatch.qty) > 0
                    )
                    .map(locationBatch => ({
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        batch_price: parseFloat(batch.retail_price),
                        batch_quantity: parseFloat(locationBatch.quantity ?? locationBatch.qty),
                        transfer_quantity: productData.quantity
                    }));
            });

            console.log('Filtered batches:', batches);

            if (batches.length === 0) {
                console.error('No batches available for product:', product.product_name);
                toastr.error(
                    `No batches available in "${$('#from_location_id option:selected').text()}" for "${product.product_name}".`
                );
                return;
            }

            // Determine if decimals are allowed for this product
            const allowDecimal = product.unit && product.unit.allow_decimal;
            console.log('Product unit info:', product.unit, 'Allow decimal:', allowDecimal);

            // Format the initial quantity value based on decimal allowance
            const initialQuantity = allowDecimal ?
                parseFloat(batches[0].transfer_quantity).toFixed(4) :
                Math.floor(batches[0].transfer_quantity);

            const quantityInput = `
            <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]"
                   min="0.0001" value="${initialQuantity}" required
                   ${allowDecimal ? 'step="0.0001"' : 'step="1"'}
                   data-allow-decimal="${allowDecimal}">
            `;

            // Format quantity for display: show decimals only if allowed
            function formatQty(qty) {
            return allowDecimal ? parseFloat(qty).toFixed(2) : parseInt(qty);
            }

            const batchOptions = batches.map(batch => `
            <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}" data-transfer-quantity="${batch.transfer_quantity}" data-allow-decimal="${allowDecimal}">
                Batch ${batch.batch_no} - Current Qty: ${formatQty(batch.batch_quantity, allowDecimal)} - Transfer Qty: ${formatQty(batch.transfer_quantity, allowDecimal)} - Price: ${batch.batch_price}
            </option>
            `).join('');

            const newRow = `
            <tr class="add-row" data-product-id="${product.id}">
                <td>
                ${product.product_name}
                <input type="hidden" name="products[${productIndex}][product_id]" value="${product.id}">
                </td>
                <td>
                <select class="form-control batch-select" name="products[${productIndex}][batch_id]" required>
                    ${batchOptions}
                </select>
                <div class="error-message batch-error"></div>
                </td>
                <td>
                ${quantityInput}
                <div class="error-message quantity-error text-danger"></div>
                </td>
                <td>
                <input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${batches[0].batch_price}" readonly>
                </td>
                <td>
                <input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${(batches[0].batch_price * batches[0].transfer_quantity).toFixed(2)}" readonly>
                </td>
                <td class="add-remove text-end">
                <a href="javascript:void(0);" class="remove-btn">
                    <i class="fas fa-trash" style="color: #dc3545;"></i>
                </a>
                </td>
            </tr>
            `;

            $(".add-table-items").find("tr:last").remove();
            $(".add-table-items").prepend(newRow);
            addTotalRow();
            updateTotalAmount();
            productIndex++;
        }

        function addProductWithBatches(productData) {
            const fromLocationId = $('#from_location_id').val();
            console.log('addProductWithBatches called:', {productData, fromLocationId});
            
            if (!fromLocationId) {
            toastr.warning("Please select a 'From' location before adding products.");
            return;
            }

            const product = productData.product;
            console.log('Product details:', product);
            
            const existingRow = $(`tr[data-product-id="${product.id}"]`);

            if (existingRow.length > 0) {
            console.log('Product already exists, updating quantity');
            const quantityInput = existingRow.find('.quantity-input');
            const newQuantity = parseFloat(quantityInput.val()) + 1;
            quantityInput.val(newQuantity);
            existingRow.find('.quantity-input').trigger('change');
            return;
            }

            // --- FIX: Support batches as object or array, and location_batches as string/number ---
            let batchesArr = [];
            if (Array.isArray(productData.batches)) {
            batchesArr = productData.batches;
            } else if (productData.batches && typeof productData.batches === 'object') {
            batchesArr = Object.values(productData.batches);
            } else if (Array.isArray(product.batches)) {
            batchesArr = product.batches;
            } else if (product.batches && typeof product.batches === 'object') {
            batchesArr = Object.values(product.batches);
            }
            
            console.log('Batches array:', batchesArr);

            // Determine if decimals are allowed for this product
            const allowDecimal = product.unit && product.unit.allow_decimal;

            // Format quantity for display: show decimals only if allowed
            function formatQty(qty) {
            return allowDecimal ? parseFloat(qty).toFixed(2) : parseInt(qty);
            }

            // Filter batches to only those in the selected "From" location with quantity > 0
            const batches = batchesArr.flatMap(batch => {
            // Support both camelCase and snake_case for location_batches
            const locationBatches = batch.location_batches || batch.locationBatches || [];
            return locationBatches
                .filter(locBatch =>
                locBatch.location_id == fromLocationId &&
                parseFloat(locBatch.quantity ?? locBatch.qty) > 0
                )
                .map(locationBatch => ({
                batch_id: batch.id,
                batch_no: batch.batch_no,
                batch_price: parseFloat(batch.retail_price),
                batch_quantity: parseFloat(locationBatch.quantity ?? locationBatch.qty),
                transfer_quantity: parseFloat(locationBatch.quantity ?? locationBatch.qty)
                }));
            });
            
            console.log('Filtered batches for location:', batches);

            if (batches.length === 0) {
            console.warn(`No batches available in selected location for product: ${product.product_name}`);
            toastr.error(
                `No batches available in "${$('#from_location_id option:selected').text()}" for "${product.product_name}".`
            );
            return;
            }

            const quantityInput = `
            <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="${batches[0].transfer_quantity}" required ${allowDecimal ? 'step="0.01"' : 'step="1"'}>
            <div class="error-message quantity-error text-danger"></div>
            `;

            const batchOptions = batches.map(batch => `
            <option value="${batch.batch_id}"
                data-price="${batch.batch_price}"
                data-quantity="${batch.batch_quantity}"
                data-transfer-quantity="${batch.transfer_quantity}">
                Batch ${batch.batch_no} - Qty: ${formatQty(batch.batch_quantity)} - Price: ${batch.batch_price}
            </option>
            `).join('');

            const newRow = `
            <tr class="add-row" data-product-id="${product.id}">
                <td>
                ${product.product_name}
                <input type="hidden" name="products[${productIndex}][product_id]" value="${product.id}">
                </td>
                <td>
                <select class="form-control batch-select" name="products[${productIndex}][batch_id]" required>
                    ${batchOptions}
                </select>
                <div class="error-message batch-error"></div>
                </td>
                <td>
                ${quantityInput}
                </td>
                <td>
                <input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${batches[0].batch_price}" readonly>
                </td>
                <td>
                <input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${(batches[0].batch_price * batches[0].transfer_quantity).toFixed(2)}" readonly>
                </td>
                <td class="add-remove text-end">
                <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                </td>
            </tr>
            `;

            $(".add-table-items").find("tr:last").remove();
            $(".add-table-items").prepend(newRow);
            addTotalRow();
            updateTotalAmount();
            productIndex++;
        }

        // Event listeners for quantity and batch changes
        $(document).on("change", ".batch-select", function() {
            const row = $(this).closest("tr");
            const selectedBatch = row.find(".batch-select option:selected");
            const unitPrice = parseFloat(selectedBatch.data("price"));
            row.find(".unit-price").val(unitPrice.toFixed(2));
            const quantity = parseFloat(row.find(".quantity-input").val());
            const subtotal = quantity * unitPrice;
            row.find(".sub_total").val(subtotal.toFixed(2));
            updateTotalAmount();
        });

        $(document).on("change", ".quantity-input", function() {
            const row = $(this).closest("tr");
            let quantity = parseFloat(row.find(".quantity-input").val());
            const selectedBatch = row.find(".batch-select option:selected");
            const unitPrice = parseFloat(selectedBatch.data("price"));
            const availableQuantity = parseFloat(selectedBatch.data("quantity"));

            if (quantity > availableQuantity) {
                row.find(".quantity-error").text("The quantity exceeds the available batch quantity.");
                row.find(".quantity-input").val(availableQuantity);
                quantity = availableQuantity;
            } else {
                row.find(".quantity-error").text("");
            }

            const subtotal = quantity * unitPrice;
            row.find(".unit-price").val(unitPrice.toFixed(2));
            row.find(".sub_total").val(subtotal.toFixed(2));
            updateTotalAmount();
        });

        $(document).on("click", ".remove-btn", function() {
            $(this).closest(".add-row").remove();
            updateTotalAmount();
            return false;
        });

        function addTotalRow() {
            const totalRow = `
            <tr>
                <td colspan="4"></td>
                <td id="totalRow">Total : 0.00</td>
                <td></td>
            </tr>
            `;
            $(".add-table-items").append(totalRow);
        }

        function updateTotalAmount() {
            let total = 0;
            $(".add-row").each(function() {
            const subtotal = parseFloat($(this).find('input[name$="[sub_total]"]').val());
            total += subtotal;
            });
            $('#totalRow').text(`Total : ${total.toFixed(2)}`);
        }

        $('#stockTransferForm').validate({
            rules: {
                from_location_id: {
                    required: true
                },
                to_location_id: {
                    required: true
                },
                transfer_date: {
                    required: true,
                }
            },
            messages: {
                from_location_id: {
                    required: "Please select a 'From' location."
                },
                to_location_id: {
                    required: "Please select a 'To' location."
                },
                transfer_date: {
                    required: "Please select a transfer date."
                }
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('td').find('.error-message'));
            },
            submitHandler: function(form, event) {

                event.preventDefault();

                // Get submit button
                const $submitBtn = $(form).find('button[type="submit"]');

                // Prevent multiple submissions
                if ($submitBtn.prop('disabled')) {
                    return false;
                }

                const fromLocationId = $('#from_location_id').val();
                const toLocationId = $('#to_location_id').val();
                if (fromLocationId === toLocationId) {
                    toastr.error('Please select different locations for "From" and "To".');
                    return;
                }
                if ($('.add-row').length === 0) {
                    toastr.error('Please add at least one product.');
                    return;
                }
                const transferDate = $('#transfer_date').val();
                const dateParts = transferDate.split('-');
                const formattedDate = new Date(`${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`)
                    .toISOString().split('T')[0];
                $('#transfer_date').val(formattedDate);
                const url = stockTransferId ? `/stock-transfer/update/${stockTransferId}` :
                    '/stock-transfer/store';
                const method = stockTransferId ? 'PUT' : 'POST';
                const formData = $(form).serialize();

                console.log('Form submission details:', {
                    stockTransferId: stockTransferId,
                    url: url,
                    method: method,
                    isEdit: !!stockTransferId
                });

                // Disable button and change text
                const originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text(stockTransferId ? 'Updating...' : 'Saving...');

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    success: function(response) {
                        console.log('Form submission successful:', response);
                        toastr.success(response.message);

                        // Small delay before redirect to allow user to see the success message
                        setTimeout(() => {
                            window.location.href = '/list-stock-transfer';
                        }, 1500);
                    },
                    error: function(response) {
                        // Re-enable button on error
                        $submitBtn.prop('disabled', false).text(originalText);

                        if (response.responseJSON && response.responseJSON.errors) {
                            for (const [key, value] of Object.entries(response
                                    .responseJSON.errors)) {
                                toastr.error(value.join(', '));
                            }
                        } else {
                            toastr.error(response.responseJSON.message ||
                                'An error occurred. Please try again.');
                        }
                    }
                });
            }
        });

        // function fetchDropdownData(url, targetSelect, placeholder, selectedId = null) {
        //     $.ajax({
        //         url: url,
        //         method: 'GET',
        //         dataType: 'json',
        //         success: function(data) {
        //             if (data.status === 200 && Array.isArray(data.message)) {
        //                 targetSelect.html(`<option selected disabled>${placeholder}</option>`);
        //                 data.message.forEach(item => {
        //                     const option = $('<option></option>').val(item.id).text(item
        //                         .name || item.first_name + ' ' + item.last_name);
        //                     targetSelect.append(option);
        //                 });
        //                 if (selectedId) {
        //                     targetSelect.val(selectedId).trigger('change');
        //                 }
        //             } else {
        //                 console.error(`Failed to fetch data: ${data.message}`);
        //             }
        //         },
        //         error: function(xhr, status, error) {
        //             console.error(`Error fetching data: ${error}`);
        //         }
        //     });
        // }

        fetchStockTransferList();

        function fetchStockTransferList() {
            $.ajax({
                url: '/stock-transfers',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        populateStockTransferTable(response.stockTransfers);
                    } else {
                        console.error('Error fetching stock transfers:', response.message);
                        toastr.error('Failed to fetch stock transfers. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching stock transfers:', error);
                    toastr.error('An error occurred. Please try again.');
                }
            });
        }

        function populateStockTransferTable(data) {

            // Add created_at data for proper sorting
            const enhancedTableData = data.map((transfer, index) => {
                const totalAmount = transfer.stock_transfer_products.reduce((sum, product) => {
                    return sum + (product.quantity * product.unit_price);
                }, 0);

                // Action buttons HTML
                let actions = `
                <button onclick="viewStockTransfer(${transfer.id})" class="btn btn-sm btn-outline-info">
                <i class="fas fa-eye"></i> view
                </button>
                <button onclick="printStockTransfer(${transfer.id}, '80mm')" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print"></i> 80mm
                </button>
                <button onclick="printStockTransfer(${transfer.id}, 'a4')" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-print"></i> A4
                </button>
            `;
                @can('edit stock-transfer')
                    actions += `
                <a href="/edit-stock-transfer/${transfer.id}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-edit"></i> edit
                </a>
                `;
                @endcan
                @can('delete stock-transfer')
                    actions += `
                <button onclick="deleteStockTransfer(${transfer.id})" class="btn btn-sm btn-outline-danger">
                <i class="fas fa-trash"></i> delete
                </button>
                `;
                @endcan

                return {
                    index: index + 1,
                    transfer_date: new Date(transfer.transfer_date).toLocaleDateString(),
                    reference_no: transfer.reference_no,
                    from_location: transfer.from_location.name,
                    to_location: transfer.to_location.name,
                    status: transfer.status,
                    shipping_charges: transfer.shipping_charges || '0.00',
                    total_amount: totalAmount.toFixed(2),
                    note: transfer.note || '',
                    actions: actions,
                    created_at: transfer.created_at, // Add created_at for sorting
                    sort_order: index // Add original order index
                };
            });

            $('#stockTransfer').DataTable({
                destroy: true,
                data: enhancedTableData,
                // Improved dom: buttons above, then length (show entries) below
                dom: '<"row mb-3"<"col-sm-12"B>>' + // Buttons row
                    '<"row mb-3"<"col-sm-6"l><"col-sm-6"f>>' + // Show entries and search row
                    'rtip',
                buttons: [{
                        extend: 'pdf',
                        exportOptions: {
                            columns: ':not(:last-child)' // Exclude last column (Actions)
                        },
                        title: ' ', // Empty to avoid default title
                        filename: 'stock_transfer', // Set download filename
                        customize: function(doc) {
                            // Centered custom title
                            doc.content.splice(0, 0, {
                                text: 'Stock Transfer List',
                                alignment: 'center',
                                fontSize: 18,
                                bold: true,
                                margin: [0, 0, 0, 12]
                            });
                        }
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            columns: ':not(:last-child)' // Exclude last column (Actions)
                        },
                        title: function() {

                            return '<div style="text-align:center;font-size:20px;font-weight:bold;">Stock Transfer List</div>';
                        },
                        customize: function(win) {
                            // Center the title in the print window
                            $(win.document.body).find('h1').css('text-align', 'center');
                        }
                    }
                ],
                columns: [{
                        data: 'index',
                        title: '#'
                    },
                    {
                        data: 'transfer_date',
                        title: 'Transfer Date'
                    },
                    {
                        data: 'reference_no',
                        title: 'Reference No'
                    },
                    {
                        data: 'from_location',
                        title: 'From Location'
                    },
                    {
                        data: 'to_location',
                        title: 'To Location'
                    },
                    {
                        data: 'status',
                        title: 'Status'
                    },
                    {
                        data: 'shipping_charges',
                        title: 'Shipping Charges'
                    },
                    {
                        data: 'total_amount',
                        title: 'Total Amount'
                    },
                    {
                        data: 'note',
                        title: 'Note'
                    },
                    {
                        data: 'actions',
                        title: 'Actions',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        title: 'Created At',
                        visible: false // Hidden column for sorting
                    },
                    {
                        data: 'sort_order',
                        title: 'Sort Order',
                        visible: false // Hidden column to maintain backend order
                    }
                ],
                order: [
                    [11, "asc"] // Sort by sort_order (index 11) to maintain backend order
                ]
            });
        }

            window.printStockTransfer = function(stockTransferId, layout = '80mm') {
            $.ajax({
                url: `/stock-transfer/get/${stockTransferId}`,
                method: 'GET',
                success: function(response) {
                    if (response.status !== 200) {
                        toastr.error('Failed to fetch stock transfer details for printing.');
                        return;
                    }
                    const st = response.stockTransfer;
                    const products = st.stock_transfer_products || [];

                    // Helper: Get latest batch MRP or fallback to product max_retail_price
                    function getMRP(product) {
                        if (product && Array.isArray(product.batches) && product.batches.length > 0) {
                            let latest = product.batches.reduce((a, b) =>
                                new Date(a.created_at) > new Date(b.created_at) ? a : b
                            );
                            if (latest.max_retail_price && +latest.max_retail_price > 0) return parseFloat(latest.max_retail_price).toLocaleString();
                        }
                        if (product && product.max_retail_price) return parseFloat(product.max_retail_price).toLocaleString();
                        return '';
                    }

                    function getUserName(user) {
                        if (!user) return '';
                        if (user.name) return user.name;
                        if (user.first_name || user.last_name) return (user.first_name ? user.first_name : '') + ' ' + (user.last_name ? user.last_name : '');
                        return '';
                    }

                    // Format numbers with thousand separator, show decimals only if needed (e.g., 1,000.25, 10,000, 1,200,000.50)
                    function formatAmount(num) {
                        if (isNaN(num) || num === null) return '';
                        // Show up to 2 decimals if not integer, else no decimals
                        return Number(num).toLocaleString('en-US', {
                            minimumFractionDigits: (Math.floor(num) !== Number(num)) ? 2 : 0,
                            maximumFractionDigits: 2
                        });
                    }

                    let activities = [];
                    let sources = [st.activities, window.activityLogs, window.lastStockTransferActivityLogs, st.activityLogs];
                    for (let arr of sources) {
                        if (Array.isArray(arr) && arr.length > 0) {
                            activities = arr.map(log => ({
                                date: log.date || log.created_at,
                                action: log.action || log.description,
                                user: log.user || log.causer,
                                note: log.note || (log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : '')
                            }));
                            break;
                        }
                    }

                    // Calculate total amount and shipping
                    const netTotal = products.reduce((sum, p) => sum + (parseFloat(p.sub_total) || 0), 0);
                    const shipping = st.shipping_charges ? parseFloat(st.shipping_charges) : 0;
                    const total = netTotal + shipping;

                    // --- 80mm thermal layout (now like A4: name row, then below: MRP, unit price, qty, subtotal) ---
                    let printContent80mm = `
                    <div id="printArea" style="width:80mm; font-family: 'monospace', Arial, sans-serif; font-size:13px; color:#111;">
                        <div style="text-align:center; margin-bottom:6px;">
                            <strong style="font-size:18px; font-weight:900;">Stock Transfer</strong><br>
                            <span style="font-size:13px; font-weight:bold;">Ref: <b>${st.reference_no}</b></span>
                        </div>
                        <div style="border-top:2px dashed #222; margin:6px 0;"></div>
                        <table style="width:100%; font-size:13px; margin-bottom:4px;">
                            <tr>
                                <td style="vertical-align:top; width:50%; font-weight:bold;"><b>From:</b> <span style="font-size:13px; font-weight:600;">${st.from_location?.name || ''}</span><br><span style="font-size:11px;">${st.from_location?.address || ''}</span></td>
                                <td style="vertical-align:top; width:50%; text-align:right; font-weight:bold;"><b>To:</b> <span style="font-size:13px; font-weight:600;">${st.to_location?.name || ''}</span><br><span style="font-size:11px;">${st.to_location?.address || ''}</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-size:12px; padding-top:3px; font-weight:bold;"><b>Date:</b> <span style="font-weight:600;">${new Date(st.transfer_date).toLocaleDateString()}</span> &nbsp; <b>Status:</b> <span style="font-weight:600;">${st.status}</span></td>
                            </tr>
                        </table>
                        <div style="border-top:2px dashed #222; margin:6px 0;"></div>
                        <table style="width:100%; font-size:13px; border-collapse:collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align:left; width:7%; font-size:13px; font-weight:900;">#</th>
                                    <th style="text-align:left; font-size:13px; font-weight:900;">ITEMS</th>
                                    <th style="text-align:right; width:17%; font-size:13px; font-weight:900;">RATE</th>
                                    <th style="text-align:center; width:13%; font-size:13px; font-weight:900;">QTY</th>
                                    <th style="text-align:right; width:19%; font-size:13px; font-weight:900;">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${products.map((p, i) => {
                                    let prod = p.product || {};
                                    let mrp = getMRP(prod);
                                    let prodName = prod.product_name || '';
                                    let unitPrice = formatAmount(p.unit_price);
                                    let subTotal = formatAmount(p.sub_total);

                                    // MRP: strikethrough if different from unit price, else extra bold and clear
                                    let mrpHtml = '';
                                    if (mrp && mrp !== unitPrice) {
                                        mrpHtml = `<span style="text-decoration:line-through; color:#111; font-weight:900; font-size:13px; letter-spacing:1px; background: #ffe; padding:1px 4px; border-radius:2px;">${mrp}</span>`;
                                    } else if (mrp) {
                                        mrpHtml = `<span style="font-weight:900; font-size:13px; color:#111; letter-spacing:1px; background: #ffe; padding:1px 4px; border-radius:2px;">${mrp}</span>`;
                                    }

                                    // Second row: MRP, unit price, qty, subtotal
                                    return `
                                    <tr>
                                        <td style="vertical-align:top; font-weight:900; font-size:13px;"><b>${i + 1}</b></td>
                                        <td colspan="4" style="vertical-align:top;">
                                            <span style="font-weight:900; font-size:13px;">${prodName}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            ${mrp ? `<span style="font-size:12px; font-weight:900;">MRP: <span style="font-size:13px; font-weight:900;">${mrpHtml}</span></span>` : ''}
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="font-size:12px; font-weight:900;">${unitPrice}</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="font-size:12px; font-weight:900;">${formatAmount(p.quantity)}</span>
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="font-size:12px; font-weight:900;">${subTotal}</span>
                                        </td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        <div style="border-top:2px dashed #222; margin:6px 0;"></div>
                        <table style="width:100%; font-size:13px;">
                            <tr>
                                <td style="text-align:left; font-weight:bold;">Net Total:</td>
                                <td style="text-align:right; font-weight:900;"><b>Rs.${formatAmount(netTotal)}</b></td>
                            </tr>
                            <tr>
                                <td style="text-align:left; font-weight:bold;">Shipping:</td>
                                <td style="text-align:right; font-weight:900;">Rs.${formatAmount(shipping)}</td>
                            </tr>
                            <tr>
                                <td style="text-align:left; font-weight:bold;">Total:</td>
                                <td style="text-align:right; font-weight:900;"><b>Rs.${formatAmount(total)}</b></td>
                            </tr>
                        </table>
                        <div style="border-top:2px dashed #222; margin:6px 0;"></div>
                        <div style="font-size:12px; margin-bottom:3px; font-weight:bold;"><b>Notes:</b> <span style="font-weight:normal;">${st.note || '--'}</span></div>
                        <div style="font-size:12px; margin-top:3px;">
                            <b style="font-size:13px;">Activities:</b>
                            <table style="width:100%; font-size:11px; margin-top:3px;">
                                <thead>
                                    <tr>
                                        <th style="font-weight:bold;">Date</th>
                                        <th style="font-weight:bold;">Action</th>
                                        <th style="font-weight:bold;">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${activities.length > 0 ?
                                        activities.map(a => `
                                            <tr>
                                                <td>${a.date ? new Date(a.date).toLocaleDateString() : ''}</td>
                                                <td>${a.action || ''}</td>
                                                <td>${getUserName(a.user)}</td>
                                            </tr>
                                        `).join('') :
                                        `<tr><td colspan="3" style="text-align:center; color:#888;">No activities</td></tr>`
                                    }
                                </tbody>
                            </table>
                        </div>
                        <div style="border-top:2px dashed #222; margin:6px 0 0 0;"></div>
                        <div style="text-align:center; font-size:13px; margin-top:3px; font-weight:900;"><b>Thank you!</b></div>
                    </div>
                    `;
                    const style80mm = `<style>
                        @media print {
                            @page { size: 80mm auto; margin: 0.10in !important; }
                            html, body {
                                background: #fff !important;
                                margin: 0 !important;
                                padding: 0 !important;
                                width: 80mm !important;
                                max-width: 80mm !important;
                            }
                            #printArea { width: 80mm !important; max-width: 80mm !important; }
                            th, td { padding: 0 2px; }
                        }
                        #printArea { width:80mm; max-width:80mm; margin:0 auto; padding:0; }
                        table { border-collapse: collapse; width:100%; }
                        th, td { border: none !important; padding: 0 2px; }
                    </style>`;

                    // --- A4 layout (image-style: product name row, then below: MRP, unit price, qty, subtotal) ---
                    let printContentA4 = `
                    <div id="printArea" style="width:800px; max-width:800px; margin:0 auto; font-family: Arial, sans-serif; font-size:13px; color:#111;">
                        <div style="text-align:center; margin-bottom:10px;">
                            <strong style="font-size:22px;">Stock Transfer</strong><br>
                            <span style="font-size:14px;">Reference: <b>${st.reference_no}</b></span>
                        </div>
                        <table style="width:100%; font-size:13px; margin-bottom:10px;">
                            <tr>
                                <td style="vertical-align:top; width:50%;"><b>From:</b> ${st.from_location?.name || ''}<br><span style="font-size:12px;">${st.from_location?.address || ''}</span></td>
                                <td style="vertical-align:top; width:50%; text-align:right;"><b>To:</b> ${st.to_location?.name || ''}<br><span style="font-size:12px;">${st.to_location?.address || ''}</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="font-size:12px; padding-top:4px;"><b>Date:</b> ${new Date(st.transfer_date).toLocaleDateString()} &nbsp; <b>Status:</b> ${st.status}</td>
                            </tr>
                        </table>
                        <hr style="border:1px solid #222; margin:10px 0;">
                        <table style="width:100%; font-size:13px; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f3f3f3;">
                                    <th style="text-align:left; width:5%;">#</th>
                                    <th style="text-align:left;">ITEMS</th>
                                    <th style="text-align:right; width:15%;">RATE</th>
                                    <th style="text-align:center; width:10%;">QTY</th>
                                    <th style="text-align:right; width:15%;">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${products.map((p, i) => {
                                    let prod = p.product || {};
                                    let mrp = getMRP(prod);
                                    let prodName = prod.product_name || '';
                                    let unitPrice = formatAmount(p.unit_price);
                                    let subTotal = formatAmount(p.sub_total);

                                    // MRP: strikethrough if different from unit price, else normal
                                    let mrpHtml = '';
                                    if (mrp && mrp !== unitPrice) {
                                        mrpHtml = `<span style="text-decoration:line-through; color:#888;">${mrp}</span>`;
                                    } else if (mrp) {
                                        mrpHtml = mrp;
                                    }

                                    // Second row: MRP, unit price, qty, subtotal
                                    return `
                                    <tr>
                                        <td style="vertical-align:top;"><b>${i + 1}</b></td>
                                        <td colspan="4" style="vertical-align:top;">
                                            <span style="font-weight:bold;">${prodName}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>
                                            ${mrp ? `<span style="font-size:12px;">MRP: ${mrpHtml}</span>` : ''}
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="font-size:12px;">${unitPrice}</span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="font-size:12px;">${formatAmount(p.quantity)}</span>
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="font-size:12px;">${subTotal}</span>
                                        </td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                        <hr style="border:1px solid #222; margin:10px 0;">
                        <table style="width:100%; font-size:13px;">
                            <tr>
                                <td style="text-align:left;">Net Total:</td>
                                <td style="text-align:right;"><b>Rs.${formatAmount(netTotal)}</b></td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Shipping:</td>
                                <td style="text-align:right;">Rs.${formatAmount(shipping)}</td>
                            </tr>
                            <tr>
                                <td style="text-align:left;">Total:</td>
                                <td style="text-align:right;"><b>Rs.${formatAmount(total)}</b></td>
                            </tr>
                        </table>
                        <div style="font-size:12px; margin:10px 0;"><b>Notes:</b> ${st.note || '--'}</div>
                        <div style="font-size:12px; margin-top:10px;">
                            <b>Activities:</b>
                            <table style="width:100%; font-size:11px; margin-top:4px; border-collapse:collapse;">
                                <thead>
                                    <tr style="background:#f3f3f3;">
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${activities.length > 0 ?
                                        activities.map(a => `
                                            <tr>
                                                <td>${a.date ? new Date(a.date).toLocaleString() : ''}</td>
                                                <td>${a.action || ''}</td>
                                                <td>${getUserName(a.user)}</td>
                                            </tr>
                                        `).join('') :
                                        `<tr><td colspan="3" style="text-align:center; color:#888;">No activities</td></tr>`
                                    }
                                </tbody>
                            </table>
                        </div>
                        <hr style="border:1px solid #222; margin:10px 0 0 0;">
                        <div style="text-align:center; font-size:13px; margin-top:10px;"><b>Thank you!</b></div>
                    </div>
                    `;
                    const styleA4 = `<style>
                        @media print {
                            @page { size: 800px auto; margin: 0.5in; }
                            html, body {
                                background: #fff !important;
                                margin: 0 !important;
                                padding: 0 !important;
                                width: 800px !important;
                                max-width: 800px !important;
                            }
                            #printArea { width: 800px !important; max-width: 800px !important; }
                        }
                        #printArea { width:800px; max-width:800px; margin:0 auto; padding:0; }
                        table { border-collapse: collapse; width:100%; }
                        th, td { border: none !important; padding: 4px 6px; }
                        hr { border: 1px solid #222; }
                    </style>`;

                    // Print logic (restore after print)
                    const originalContent = document.body.innerHTML;
                    if (layout.toLowerCase() === 'a4') {
                        document.body.innerHTML = styleA4 + printContentA4;
                    } else {
                        document.body.innerHTML = style80mm + printContent80mm;
                    }
                    window.print();
                    document.body.innerHTML = originalContent;
                },
                error: function() {
                    toastr.error('An error occurred while printing. Please try again.');
                }
            });
          }

    // Make viewStockTransfer globally accessible
    window.viewStockTransfer = function(stockTransferId) {
        $.ajax({
            url: `/stock-transfer/get/${stockTransferId}`,
            method: 'GET',
            success: function(response) {
                if (response.status === 200) {
                    const stockTransfer = response.stockTransfer;
                    populateStockTransferDetailsModal(stockTransfer);
                    $('#stockTransferDetailsModal').modal('show');
                } else {
                    console.error('Error fetching stock transfer details:', response
                        .message);
                    toastr.error(
                        'Failed to fetch stock transfer details. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching stock transfer details:', error);
                toastr.error('An error occurred. Please try again.');
            }
        });
    }

    // Populate the new modal with correct IDs
    function populateStockTransferDetailsModal(stockTransfer) {
        // Set header fields
        $('#std_date').text(new Date(stockTransfer.transfer_date).toLocaleDateString());
        $('#std_reference_no').text(stockTransfer.reference_no);
        $('#std_reference_no_2').text(stockTransfer.reference_no);
        $('#std_status').text(stockTransfer.status);

        // Set location fields
        $('#std_location_from').text(stockTransfer.from_location ? stockTransfer.from_location.name : '');
        $('#std_location_to').text(stockTransfer.to_location ? stockTransfer.to_location.name : '');
        $('#std_location_from_address').text(stockTransfer.from_location && stockTransfer.from_location
            .address ? stockTransfer.from_location.address : '');
        $('#std_location_to_address').text(stockTransfer.to_location && stockTransfer.to_location.address ?
            stockTransfer.to_location.address : '');

        // Set shipping charges and total
        $('#std_shipping_charges').text(stockTransfer.shipping_charges ? parseFloat(stockTransfer
            .shipping_charges).toFixed(2) : '0.00');

        // Calculate total amount (sum of sub_totals)
        const totalAmount = stockTransfer.stock_transfer_products.reduce((sum, product) => {
            return sum + (parseFloat(product.sub_total) || 0);
        }, 0);
        $('#std_total_amount').text(totalAmount.toFixed(2));

        // Calculate purchase total (total + shipping)
        const purchaseTotal = totalAmount + (parseFloat(stockTransfer.shipping_charges) || 0);
        $('#std_purchase_total').text(purchaseTotal.toFixed(2));

        // Populate products table
        const $tbody = $('#std_products_table tbody');
        $tbody.empty();
        stockTransfer.stock_transfer_products.forEach((product, idx) => {
            $tbody.append(`
                <tr>
                    <td class="text-center">${idx + 1}</td>
                    <td>
                        ${product.product ? product.product.product_name : ''}
                        <br>
                        <small class="text-muted">${product.product ? product.product.sku : ''}</small>
                    </td>
                    <td class="text-center">${product.quantity}</td>
                    <td class="text-end">${parseFloat(product.sub_total).toFixed(2)}</td>
                </tr>
            `);
        });

        // Additional notes
        $('#std_additional_notes').text(stockTransfer.note || '');

        // Populate activities
        const $activities = $('#std_activities');
        $activities.empty();

        // Prefer stockTransfer.activities, then window.activityLogs, then response.activityLogs
        let activities = [];
        if (Array.isArray(stockTransfer.activities) && stockTransfer.activities.length > 0) {
            activities = stockTransfer.activities.map(activity => ({
                date: activity.date,
                action: activity.action,
                user: activity.user,
                note: activity.note
            }));
        } else if (Array.isArray(window.activityLogs) && window.activityLogs.length > 0) {
            activities = window.activityLogs.map(log => ({
                date: log.created_at,
                action: log.description,
                user: log.causer,
                note: log.properties && log.properties.attributes && log.properties.attributes
                    .note ? log.properties.attributes.note : ''
            }));
        } else if (Array.isArray(window.lastStockTransferActivityLogs) && window
            .lastStockTransferActivityLogs.length > 0) {
            activities = window.lastStockTransferActivityLogs.map(log => ({
                date: log.created_at,
                action: log.description,
                user: log.causer,
                note: log.properties && log.properties.attributes && log.properties.attributes
                    .note ? log.properties.attributes.note : ''
            }));
        } else if (Array.isArray(stockTransfer.activityLogs) && stockTransfer.activityLogs.length > 0) {
            activities = stockTransfer.activityLogs.map(log => ({
                date: log.created_at,
                action: log.description,
                user: log.causer,
                note: log.properties && log.properties.attributes && log.properties.attributes
                    .note ? log.properties.attributes.note : ''
            }));
        }

        if (activities.length > 0) {
            activities.forEach(activity => {
                $activities.append(`
                    <tr>
                        <td>${activity.date ? new Date(activity.date).toLocaleString() : ''}</td>
                        <td>${activity.action || ''}</td>
                        <td>${activity.user ? (activity.user.name || (activity.user.first_name && activity.user.last_name ? activity.user.first_name + ' ' + activity.user.last_name : '')) : ''}</td>
                        <td>${activity.note || ''}</td>
                    </tr>
                `);
            });
        } else if (window.activityLogs && Array.isArray(window.activityLogs) && window.activityLogs.length >
            0) {
            window.activityLogs.forEach(log => {
                $activities.append(`
                    <tr>
                        <td>${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</td>
                        <td>${log.description || ''}</td>
                        <td>${log.causer && (log.causer.name || (log.causer.first_name && log.causer.last_name ? log.causer.first_name + ' ' + log.causer.last_name : '')) || ''}</td>
                        <td>${log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : ''}</td>
                    </tr>
                `);
            });
        } else if (window.response && Array.isArray(window.response.activityLogs) && window.response
            .activityLogs.length > 0) {
            window.response.activityLogs.forEach(log => {
                $activities.append(`
                    <tr>
                        <td>${log.created_at ? new Date(log.created_at).toLocaleString() : ''}</td>
                        <td>${log.description || ''}</td>
                        <td>${log.causer && (log.causer.name || (log.causer.first_name && log.causer.last_name ? log.causer.first_name + ' ' + log.causer.last_name : '')) || ''}</td>
                        <td>${log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : ''}</td>
                    </tr>
                `);
            });
        } else {
            $activities.append(
                '<tr><td colspan="4" class="text-center text-muted">No activities found.</td></tr>');
        }
    }

    window.deleteStockTransfer = function(id) {
    if (confirm('Are you sure you want to delete this stock transfer?')) {
        $.ajax({
            url: `/stock-transfer/delete/${id}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            success: function(response) {
                if (response.status === 200) {
                    toastr.success('Stock transfer deleted successfully.');
                    fetchStockTransferList();
                } else {
                    toastr.error('Failed to delete stock transfer.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error deleting stock transfer:', error);
                toastr.error('An error occurred. Please try again.');
            }
        });
    }
    }
    });
</script>
