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

        // Fetch products data for autocomplete
        fetchProductsData();

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
                    if (data.status === 200 && Array.isArray(data.message)) {
                        targetSelect.html(
                            `<option value="" selected disabled>${placeholder}</option>`);
                        data.message.forEach(item => {
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



        // Fetch products data for autocomplete
        function fetchProductsData() {
            $.ajax({
                url: '/products/stocks',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        // Process each product to enrich its locations using batch.location_batches
                        productsData = response.data.map(item => {
                            const allBatchLocations = item.batches.flatMap(batch =>
                                (batch.location_batches || []).map(loc => ({
                                    location_id: loc.location_id,
                                    location_name: loc.location_name
                                }))
                            );

                            // Deduplicate by location_id
                            const uniqueLocationsMap = {};
                            allBatchLocations.forEach(loc => {
                                uniqueLocationsMap[loc.location_id] = loc;
                            });

                            const enrichedLocations = Object.values(uniqueLocationsMap);

                            return {
                                ...item,
                                locations: enrichedLocations.length > 0 ?
                                    enrichedLocations : item.locations
                            };
                        }).sort((a, b) =>
                            a.product.product_name.localeCompare(b.product.product_name)
                        );

                        setupAutocomplete(); // Initialize autocomplete
                    }
                },
                error: function(error) {
                    console.error('Error fetching products data:', error);
                    toastr.error('Failed to fetch products data. Please try again.');
                }
            });
        }


        function setupAutocomplete(customProducts = productsData) {
            const $input = $('#productSearchInput');

            // Destroy only if previously initialized
            if ($input.data('ui-autocomplete')) {
                $input.autocomplete("destroy");
            }

            // Prepare suggestions
            const productSuggestions = customProducts.map(data => ({
                label: `${data.product.product_name} (${data.product.sku})`,
                value: data.product.product_name,
                sku: data.product.sku
            }));

            // Initialize autocomplete
            $input.autocomplete({
                minLength: 1,
                source: function(request, response) {
                    const term = $.trim(request.term).toLowerCase();
                    const matches = productSuggestions.filter(item =>
                        item.value.toLowerCase().includes(term) ||
                        (item.sku && item.sku.toLowerCase().includes(term))
                    );
                    response(matches);
                },
                select: function(event, ui) {
                    const selectedProduct = customProducts.find(data =>
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
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append(
                        `<div><strong>${item.value}</strong> <small class="autocomplete-sku">(${item.sku})</small></div>`
                    )
                    .appendTo(ul);
            };
        }

        $('#location_id').on('change', function() {
            const selectedLocationId = $(this).val();
            const $productInput = $('#productSearchInput');

            // Clear existing product rows and reset index
            $('#productTableBody').empty();
            productIndex = 0;

            if (selectedLocationId) {
                // Filter products by selected location using enriched locations
                const filteredProducts = productsData.filter(product => {
                    return product.locations.some(loc => loc.location_id == selectedLocationId);
                });

                // Re-initialize autocomplete with filtered products
                setupAutocomplete(filteredProducts);

                // Enable search input
                $productInput.prop('disabled', false);
            } else {
                // No location selected, disable product input again
                $productInput.prop('disabled', true).val('');
                toastr.info('Please select a location first.');
            }

            updateTotalAmount(); // Reset total amount
        });

        function addOrUpdateProductInTable(productData) {
            if (!productData || !productData.product) {
            console.error('Invalid product data:', productData);
            toastr.error('Invalid product data. Please try again.');
            return;
            }

            const selectedLocationId = $('#location_id').val();
            const product = productData.product;

            // Filter batches by selected location
            const batches = [].concat.apply([], productData.batches.map(batch =>
            (batch.location_batches || [])
            .filter(locationBatch => locationBatch.location_id == selectedLocationId)
            .map(locationBatch => ({
                batch_id: batch.id,
                batch_price: parseFloat(batch.retail_price),
                batch_quantity: locationBatch.quantity,
            }))
            ));

            if (batches.length === 0) {
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
            let currentQuantity = parseInt(quantityInput.val(), 10) || 0;

            if (currentQuantity < maxAvailable) {
                quantityInput.val(currentQuantity + 1).trigger('change');
            } else {
                toastr.info('Maximum available quantity reached for this batch.');
            }
            return;
            }

            // Build batch options
            const batchOptions = batches.map(batch => `
        <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}">
            Batch ${batch.batch_id} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price.toFixed(2)}
        </option>
        `).join('');

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
            <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="1" max="${batches[0].batch_quantity}" required>
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
                const quantity = parseFloat(row.find('.quantity-input').val());
                const maxQty = parseFloat(selectedBatch.data('quantity'));

                // Ensure quantity does not exceed max available
                if (quantity > maxQty) {
                row.find('.quantity-input').val(maxQty);
                toastr.info('Maximum available quantity reached for this batch.');
                }

                const finalQty = Math.min(quantity, maxQty);
                const subtotal = parseFloat(unitPrice * finalQty).toFixed(2);
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
