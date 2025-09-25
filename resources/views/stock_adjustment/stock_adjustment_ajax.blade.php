<script>
    $(document).ready(function() {
        // Extract the stock adjustment ID from the URL
        const pathSegments = window.location.pathname.split('/');
        const stockAdjustmentId = pathSegments[pathSegments.length - 1] === 'add-stock-adjustment' ? null :
            pathSegments[pathSegments.length - 1];

        let productIndex = 0; // Track product index for dynamic row naming
        let productsData = []; // Store fetched products data

        // Fetch and populate location dropdown
        fetchDropdownData('/location-get-all', $('#location_id'), "Select Location");

        // Fetch products data for autocomplete (now uses backend autocompleteStock)
        function fetchProductsData(searchTerm = '', locationId = null, callback = null) {
            locationId = locationId || $('#location_id').val();
            if (!locationId) {
            productsData = [];
            if (callback) callback([]);
            else setupAutocomplete([]);
            return;
            }
            $.ajax({
            url: '/products/stocks/autocomplete',
            method: 'GET',
            data: {
                search: searchTerm,
                location_id: locationId,
                per_page: 100
            },
            success: function(response) {
                if (response.status === 200) {
                // Filter out products with 0 total_stock (unless Unlimited)
                const filteredData = response.data.filter(item => {
                    if (item.total_stock === 'Unlimited') return true;
                    return parseFloat(item.total_stock) > 0;
                });
                productsData = filteredData;
                if (filteredData.length === 0 && searchTerm) {
                    toastr.error(
                    'No product found for this search in the selected location.');
                }
                if (callback) callback(productsData);
                else setupAutocomplete(productsData);
                } else {
                productsData = [];
                if (callback) callback([]);
                else setupAutocomplete([]);
                toastr.error('Failed to fetch products for autocomplete.');
                }
            },
            error: function(error) {
                productsData = [];
                if (callback) callback([]);
                else setupAutocomplete([]);
                toastr.error('Failed to fetch products for autocomplete.');
            }
            });
        }

        // Setup autocomplete for product search input
        function setupAutocomplete(customProducts = productsData) {
            const $input = $('#productSearchInput');

            // Destroy only if previously initialized
            if ($input.data('ui-autocomplete')) {
            $input.autocomplete("destroy");
            }
            
            // Add Enter key support for quick selection - Updated with working POS AJAX solution
            $input.off('keydown.autocomplete').on('keydown.autocomplete', function(event) {
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

                    if (itemToAdd) {
                        const selectedProduct = productsData.find(data =>
                            data.product.product_name === itemToAdd.value ||
                            data.product.sku === itemToAdd.sku
                        );
                        if (selectedProduct) {
                            addOrUpdateProductInTable(selectedProduct);
                            $(this).val('');
                            $(this).autocomplete('close');
                        }
                    }

                    event.stopImmediatePropagation();
                }
            });

            // Prepare suggestions
            const productSuggestions = customProducts.map(data => ({
            label: `${data.product.product_name} (${data.product.sku})`,
            value: data.product.product_name,
            sku: data.product.sku
            }));

            // Store the latest search term to avoid race conditions
            let latestSearchTerm = '';
            let debounceTimer;

            // Initialize autocomplete
            $input.autocomplete({
            minLength: 1,
            source: function(request, response) {
                const term = $.trim(request.term).toLowerCase();
                latestSearchTerm = term;

                // If the search term is empty, show all products for the location
                if (!term) {
                response(productSuggestions);
                return;
                }

                // Debounce backend fetch and only update source after AJAX completes
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                fetchProductsData(term, $('#location_id').val(), function(
                    fetchedProducts) {
                    // Only update if the search term hasn't changed during the AJAX call
                    if (latestSearchTerm === term) {
                    const matches = fetchedProducts.map(data => ({
                        label: `${data.product.product_name} (${data.product.sku})`,
                        value: data.product.product_name,
                        sku: data.product.sku
                    })).filter(item =>
                        item.value.toLowerCase().includes(term) ||
                        (item.sku && item.sku.toLowerCase()
                        .includes(term))
                    );
                    response(matches);
                    }
                });
                }, 250);
            },
            select: function(event, ui) {
                const selectedProduct = productsData.find(data =>
                data.product.product_name === ui.item.value ||
                data.product.sku === ui.item.sku
                );
                addOrUpdateProductInTable(selectedProduct);
                $(this).val('');
                return false;
            },
            focus: function(event, ui) {
                $('#productSearchInput').val(ui.item.label);
                return false;
            },
            open: function() {
                $('.ui-autocomplete').css({
                'max-height': '200px',
                'overflow-y': 'auto',
                'overflow-x': 'hidden',
                'z-index': 1050
                });
                
                // Auto-focus first item for Enter key selection - Updated with working POS AJAX solution
                setTimeout(() => {
                    const autocompleteInstance = $input.autocomplete("instance");
                    const menu = autocompleteInstance.menu;
                    const firstItem = menu.element.find("li:first-child");
                    
                    if (firstItem.length > 0) {
                        // Properly set the active item using jQuery UI's method
                        menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                        firstItem.addClass("ui-state-focus");
                        menu.active = firstItem;
                    }
                }, 50);
            }
            }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append(
                `<div><strong>${item.value}</strong> <small class="autocomplete-sku">(${item.sku})</small></div>`
                )
                .data('ui-autocomplete-item', item)
                .appendTo(ul);
            };

            // Remove the old input event handler (handled by autocomplete source now)
            $input.off('input.autocomplete');
        }

        // When location changes, refetch products for autocomplete
        $('#location_id').on('change', function() {
            const selectedLocationId = $(this).val();
            const $productInput = $('#productSearchInput');

            // Clear existing product rows and reset index
            $('#productTableBody').empty();
            productIndex = 0;

            if (selectedLocationId) {
                fetchProductsData('', selectedLocationId);
                $productInput.prop('disabled', false);
            } else {
                $productInput.prop('disabled', true).val('');
                toastr.error('Please select a location first.');
                productsData = [];
                setupAutocomplete([]);
            }

            updateTotalAmount(); // Reset total amount
        });

        // Initial state: disable product input until location is selected
        $('#productSearchInput').prop('disabled', true);

        // Fetch products data for autocomplete if location is already selected (on edit)
        if ($('#location_id').val()) {
            fetchProductsData('', $('#location_id').val());
            $('#productSearchInput').prop('disabled', false);
        }

        // Fetch stock adjustment data if editing
        if (stockAdjustmentId) {
            $.ajax({
                url: `/edit-stock-adjustment/${stockAdjustmentId}`,
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

        // Submit form data via AJAX
        function submitForm(form) {
            const submitButton = $(form).find('button[type="submit"]');
            submitButton.prop('disabled', true); // Disable the button to prevent multiple submissions

            const formData = $(form).serialize();

            // Determine the URL and method based on whether we are updating or creating
            const url = stockAdjustmentId ? `/stock-adjustment/update/${stockAdjustmentId}` :
                '/stock-adjustment/store';
            const method = stockAdjustmentId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                },
                data: formData,
                success: function(data) {
                    if (data.message) {
                        toastr.success(data.message, 'Adjust');
                        window.location.href =
                            '/list-stock-adjustment'; // Redirect to the list page
                    } else {
                        if (data.errors) {
                            for (const [key, value] of Object.entries(data.errors)) {
                                toastr.error(value.join(', '));
                            }
                        } else {
                            toastr.error(data.message || 'Failed to create stock adjustment.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    toastr.error('An error occurred. Please try again.');
                },
                complete: function() {
                    submitButton.prop('disabled', false); // Re-enable the button
                }
            });
        }
        // Fetch and populate dropdown data
        function fetchDropdownData(url, targetSelect, placeholder, selectedId = null) {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.status === true && Array.isArray(data.data)) {
                        targetSelect.html(
                            `<option value="" selected disabled>${placeholder}</option>`);
                        data.data.forEach(item => {
                            const option = $('<option></option>').val(item.id).text(item
                                .name || item.first_name + ' ' + item.last_name);
                            if (item.id == selectedId) {
                                option.attr('selected', 'selected');
                            }
                            targetSelect.append(option);
                        });
                    } else {
                        console.error(`Failed to fetch data: ${data.message}`);
                        toastr.error('Failed to fetch dropdown data. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`Error fetching data: ${error}`);
                    toastr.error('Failed to fetch dropdown data. Please try again.');
                }
            });
        }

        function addOrUpdateProductInTable(productData) {
            if (!productData || !productData.product) {
                console.error('Invalid product data:', productData);
                toastr.error('Invalid product data. Please try again.');
                return;
            }

            console.log('Adding/updating product:', productData);



            const selectedLocationId = $('#location_id').val();
            const product = productData.product;

            // Determine if decimals are allowed for this product's unit
            const allowDecimal = product.unit && product.unit.allow_decimal;

            // Filter batches by selected location and ensure batch_quantity > 0
            const batches = [].concat.apply([], (productData.batches || []).map(batch =>
                (batch.location_batches || batch.locationBatches || [])
                .filter(locationBatch => String(locationBatch.location_id) == String(
                    selectedLocationId))
                .map(locationBatch => {
                    const batch_quantity = parseFloat(locationBatch.quantity || locationBatch.qty);
                    return (!isNaN(batch_quantity) && batch_quantity > 0) ? {
                        batch_id: locationBatch.batch_id || batch.id,
                        batch_price: parseFloat(batch.retail_price),
                        batch_quantity: batch_quantity,
                        batch_no: batch.batch_no || batch.batch_number,
                    } : null;
                })
                .filter(Boolean)
            ));

            console.log('Filtered batches:', batches);

            if (!batches || batches.length === 0) {
                toastr.warning(`No available batches for this product at the selected location.`);
                return;
            }

            // Check if product already exists in table
            const existingRow = $(`#productTableBody tr[data-product-id="${product.id}"]`);
            if (existingRow.length > 0) {
                // If already exists, increase the quantity by 1 (up to max available)
                const batchSelect = existingRow.find('.batch-select');
                const quantityInput = existingRow.find('.quantity-input');
                const selectedBatchId = batchSelect.val();
                const selectedBatch = batches.find(b => b.batch_id == selectedBatchId) || batches[0];
                const maxAvailable = selectedBatch.batch_quantity;
                let currentQuantity = parseFloat(quantityInput.val()) || 0;

                if (currentQuantity < maxAvailable) {
                    let newQty = allowDecimal ? (currentQuantity + 1) : (parseInt(currentQuantity, 10) + 1);
                    if (newQty > maxAvailable) newQty = maxAvailable;
                    quantityInput.val(newQty).trigger('change');
                } else {
                    toastr.info('Maximum available quantity reached for this batch.');
                }
                return;
            }

            // Build batch options
            const batchOptions = batches.map(batch => `
            <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}">
             ${batch.batch_no} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price.toFixed(2)}
            </option>
            `).join('');

            // Set input attributes based on allowDecimal
            const step = allowDecimal ? "0.01" : "1";
            const inputType = "number";
            const min = allowDecimal ? "0.01" : "1";
            const pattern = allowDecimal ? "[0-9]+([\\.,][0-9]+)?" : "[0-9]+";

            // Add new row to table
            const newRow = `
            <tr class="add-row" data-product-id="${product.id}">
            <td>${product.product_name}
            <input type="hidden" name="products[${productIndex}][product_id]" value="${product.id}">
            </td>
            <td>
            <select class="form-control batch-select" name="products[${productIndex}][batch_id]" required>
                ${batchOptions}
            </select>
            <div class="error-message batch-error"></div>
            </td>
            <td>
            <input 
                type="${inputType}" 
                class="form-control quantity-input" 
                name="products[${productIndex}][quantity]" 
                min="${min}" 
                step="${step}" 
                pattern="${pattern}"
                value="1"
                max="${batches[0].batch_quantity}" 
                required
            >
            <div class="error-message quantity-error text-danger"></div>
            </td>
            <td><input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${batches[0].batch_price.toFixed(2)}" readonly></td>
            <td><input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${batches[0].batch_price.toFixed(2)}" readonly></td>
            <td class="add-remove text-end">
            <a href="javascript:void(0);" class="remove-btn text-danger"><i class="fas fa-trash"></i></a>
            </td>
            </tr>
            `;
            $('#productTableBody').append(newRow);
            updateTotalAmount();

            // Event listeners for remove and change
            $('.remove-btn').off('click').on('click', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

            $(document).off('change', '.batch-select, .quantity-input').on('change',
                '.batch-select, .quantity-input',
                function() {
                    const row = $(this).closest('tr');
                    const selectedBatch = row.find('.batch-select option:selected');
                    const unitPrice = parseFloat(selectedBatch.data('price'));
                    let quantity = parseFloat(row.find('.quantity-input').val());
                    const maxQty = parseFloat(selectedBatch.data('quantity'));

                    // Ensure quantity does not exceed max available
                    if (quantity > maxQty) {
                        quantity = maxQty;
                        row.find('.quantity-input').val(quantity);
                        toastr.info('Maximum available quantity reached for this batch.');
                    }

                    // If not allowDecimal, force integer
                    if (!allowDecimal) {
                        quantity = Math.floor(quantity);
                        row.find('.quantity-input').val(quantity);
                    }

                    // Prevent less than min
                    if (quantity < parseFloat(min)) {
                        quantity = parseFloat(min);
                        row.find('.quantity-input').val(quantity);
                    }

                    const subtotal = parseFloat(unitPrice * quantity).toFixed(2);
                    row.find('.unit-price').val(unitPrice.toFixed(2));
                    row.find('.sub_total').val(subtotal);
                    updateTotalAmount();
                });

            productIndex++;
        }

        // Update total amount in the footer
        function updateTotalAmount() {
            let totalAmount = 0;
            $(".add-row").each(function() {
                const subtotal = parseFloat($(this).find('input[name^="products"][name$="[sub_total]"]')
                    .val());
                totalAmount += subtotal;
            });
            $('#totalAmount').text(totalAmount.toFixed(2));
        }
        // Form submission handler
        $('#stockAdjustmentForm').validate({
            rules: {
                location_id: {
                    required: true
                },
                date: {
                    required: true
                },
                adjustmentType: {
                    required: true
                },
                reason: {
                    required: true
                },
            },
            messages: {
                location_id: {
                    required: "Please select a business location."
                },
                date: {
                    required: "Please select a date."
                },
                adjustmentType: {
                    required: "Please select an adjustment type."
                },
                reason: {
                    required: "Please provide a reason for the adjustment."
                },
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.error-message'));
            },
            submitHandler: function(form) {
                submitForm(form);
            }
        });

        // Fetch and populate the stock adjustment data
        fetchStockAdjustmentList();

        function fetchStockAdjustmentList() {
            $.ajax({
                url: '/stock-adjustments', // API endpoint
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        populateStockAdjustmentTable(response.stockAdjustment);
                    } else {
                        console.error('Error fetching stock adjustments:', response.message);
                        toastr.error('Failed to fetch stock adjustments. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching stock adjustments:', error);
                    toastr.error('An error occurred. Please try again.');
                }
            });
        }

        function populateStockAdjustmentTable(data) {
            const table = $('#stockAdjustmentTable').DataTable({
                destroy: true, // Destroy existing table to reinitialize
                data: data,
                columns: [{
                        data: 'date'
                    },
                    {
                        data: 'reference_no'
                    },
                    {
                        data: 'location.name'
                    }, // Access nested location name
                    {
                        data: 'adjustment_type'
                    },
                    {
                        data: 'adjustment_products',
                        render: function(data, type, row) {
                            // Calculate total amount from adjustment_products
                            let totalAmount = data.reduce((sum, product) => sum + parseFloat(
                                product.subtotal), 0);
                            return totalAmount.toFixed(2);
                        }
                    },
                    {
                        data: 'total_amount_recovered'
                    },
                    {
                        data: 'reason'
                    },
                    {
                        data: 'user.user_name'
                    }, // Assuming 'userName' is part of the response
                    {
                        data: 'id',
                        render: function(data, type, row) {
                            // Add action buttons (Edit, Delete, etc.)
                            return `
                        <a href="/edit-stock-adjustment/${data}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteStockAdjustment(${data})" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                        }
                    }
                ],
                columnDefs: [{
                        targets: [8],
                        orderable: false
                    } // Disable sorting for the action column
                ]
            });

            // Add event listener for row click
            $('#stockAdjustmentTable tbody').on('click', 'tr', function() {
                const rowData = table.row(this).data(); // Get data for the clicked row
                if (rowData) {
                    showStockAdjustmentModal(rowData); // Show the modal with details
                }
            });
        }

        // Show the stock adjustment details in a modal
        function showStockAdjustmentModal(stockAdjustment) {
            // Set modal title and basic details
            $('#stockAdjustmentModal .modal-title').text(
                `Stock Adjustment Details - ${stockAdjustment.reference_no}`);
            $('#stockAdjustmentModal .modal-date').text(`Date: ${stockAdjustment.date}`);
            $('#stockAdjustmentModal .modal-location').text(`Location: ${stockAdjustment.location.name}`);
            $('#stockAdjustmentModal .modal-type').text(`Type: ${stockAdjustment.adjustment_type}`);
            $('#stockAdjustmentModal .modal-reason').text(`Reason: ${stockAdjustment.reason}`);
            $('#stockAdjustmentModal .modal-user').text(`User: ${stockAdjustment.user.user_name}`);

            // Populate the products table
            const productsTableBody = $('#stockAdjustmentModal .modal-products tbody');
            productsTableBody.empty(); // Clear existing rows

            stockAdjustment.adjustment_products.forEach(product => {
                const row = `
            <tr>
                <td>${product.product.product_name}</td>
                <td>${product.batch_id}</td>
                <td>${product.quantity}</td>
                <td>${product.unit_price}</td>
                <td>${product.subtotal}</td>
            </tr>
        `;
                productsTableBody.append(row);
            });

            // Show the modal
            $('#stockAdjustmentModal').modal('show');
        }

        // Delete stock adjustment
        function deleteStockAdjustment(id) {
            if (confirm('Are you sure you want to delete this stock adjustment?')) {
                $.ajax({
                    url: `/delete-stock-adjustment/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.status === 200) {
                            toastr.success('Stock adjustment deleted successfully.');
                            fetchStockAdjustmentList(); // Refresh the table
                        } else {
                            toastr.error('Failed to delete stock adjustment.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting stock adjustment:', error);
                        toastr.error('An error occurred. Please try again.');
                    }
                });
            }
        }
    });
</script>
