<script>
    $(document).ready(function() {
           // Extract the stock adjustment ID from the URL
           const pathSegments = window.location.pathname.split('/');
        const stockAdjustmentId = pathSegments[pathSegments.length - 1] === 'add-stock-adjustment' ? null : pathSegments[pathSegments.length - 1];

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
            const url = stockAdjustmentId ? `/stock-adjustment/update/${stockAdjustmentId}` : '/stock-adjustment/store';
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
                        window.location.href = '/list-stock-adjustment'; // Redirect to the list page
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
                        targetSelect.html(`<option value="" selected disabled>${placeholder}</option>`);
                        data.message.forEach(item => {
                            const option = $('<option></option>').val(item.id).text(item.name || item.first_name + ' ' + item.last_name);
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
                        productsData = response.data; // Store products data
                        setupAutocomplete(); // Initialize autocomplete
                    }
                },
                error: function(error) {
                    console.error('Error fetching products data:', error);
                    toastr.error('Failed to fetch products data. Please try again.');
                }
            });
        }

        // Initialize product autocomplete
        function setupAutocomplete() {
            const productNames = productsData.map(data => data.product.product_name);

            $('#productSearchInput').autocomplete({
                source: productNames,
                select: function(event, ui) {
                    const selectedProduct = productsData.find(data => data.product.product_name === ui.item.value);
                    addProductToTable(selectedProduct);
                    $(this).val(''); // Clear the search input
                    return false;
                }
            });
        }

        // Add selected product to the table
        function addProductToTable(productData) {
            const product = productData.product;
            const batches = productData.batches.flatMap(batch => batch.location_batches.map(locationBatch => ({
                batch_id: batch.id,
                batch_price: parseFloat(batch.retail_price),
                batch_quantity: locationBatch.quantity
            })));

            const batchOptions = batches.map(batch => `
                <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}">
                    Batch ${batch.batch_id} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price}
                </option>
            `).join('');

            const newRow = `
                <tr class="add-row">
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
                        <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="1" required>
                        <div class="error-message quantity-error text-danger"></div>
                    </td>
                    <td>
                        <input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${batches[0].batch_price}" readonly>
                    </td>
                    <td>
                        <input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${batches[0].batch_price}" readonly>
                    </td>
                    <td class="add-remove text-end">
                        <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            `;

            $('#productTableBody').append(newRow);
            updateTotalAmount();
            productIndex++; // Increment product index for unique naming
        }

        // Populate form fields and product table
        function populateForm(data) {
            // Populate basic fields
            $('#location_id').val(data.location_id);
            $('#referenceNo').val(data.reference_no);
            $('#adjustment_date').val(data.date.split(' ')[0]); // Format date to YYYY-MM-DD
            $('#adjustmentType').val(data.adjustment_type);
            $('#totalAmountRecovered').val(data.total_amount_recovered);
            $('#reason').val(data.reason);

            // Populate products table
            const productTableBody = $('#productTableBody');
            productTableBody.empty(); // Clear existing rows

            let totalAmount = 0;
            data.adjustment_products.forEach(product => {
                const subtotal = parseFloat(product.subtotal).toFixed(2);
                totalAmount += parseFloat(subtotal);

                const row = `
                    <tr class="add-row">
                        <td>
                            ${product.product.product_name}
                            <input type="hidden" name="products[${productIndex}][product_id]" value="${product.product.id}">
                        </td>
                        <td>
                            <select class="form-control batch-select" name="products[${productIndex}][batch_id]" required>
                                <option value="${product.batch_id}" data-price="${product.unit_price}" data-quantity="${product.quantity}" selected>
                                    Batch ${product.batch_id} - Qty: ${product.quantity} - Price: ${product.unit_price}
                                </option>
                            </select>
                            <div class="error-message batch-error"></div>
                        </td>
                        <td>
                            <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="${product.quantity}" required>
                            <div class="error-message quantity-error text-danger"></div>
                        </td>
                        <td>
                            <input type="text" class="form-control unit-price" name="products[${productIndex}][unit_price]" value="${product.unit_price}" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control sub_total" name="products[${productIndex}][sub_total]" value="${subtotal}" readonly>
                        </td>
                        <td class="add-remove text-end">
                            <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                `;
                productTableBody.append(row);
                productIndex++; // Increment product index for unique naming
            });

            // Update total amount
            $('#totalAmount').text(totalAmount.toFixed(2));

            // Add event listener for remove buttons
            $('.remove-btn').on('click', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

            // Add event listeners for batch and quantity changes
            $(document).on("change", ".batch-select, .quantity-input", function() {
                const row = $(this).closest("tr");
                const selectedBatch = row.find(".batch-select option:selected");
                const unitPrice = parseFloat(selectedBatch.data("price"));
                const quantity = parseFloat(row.find(".quantity-input").val());
                const subtotal = quantity * unitPrice;

                row.find(".unit-price").val(unitPrice.toFixed(2));
                row.find(".sub_total").val(subtotal.toFixed(2));
                updateTotalAmount();
            });
        }

        // Update total amount in the footer
        function updateTotalAmount() {
            let totalAmount = 0;
            $(".add-row").each(function() {
                const subtotal = parseFloat($(this).find('input[name^="products"][name$="[sub_total]"]').val());
                totalAmount += subtotal;
            });
            $('#totalAmount').text(totalAmount.toFixed(2));
        }

        // Form submission handler
        $('#stockAdjustmentForm').validate({
            rules: {
                location_id: { required: true },
                date: { required: true },
                adjustmentType: { required: true },
                reason: { required: true },
            },
            messages: {
                location_id: { required: "Please select a business location." },
                date: { required: "Please select a date." },
                adjustmentType: { required: "Please select an adjustment type." },
                reason: { required: "Please provide a reason for the adjustment." },
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.error-message'));
            },
            submitHandler: function(form) {
                submitForm(form);
            }
        });

        // Submit form data via AJAX

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

function populateStockAdjustmentTable(data, userName) {
    $('#stockAdjustmentTable').DataTable({
        destroy: true, // Destroy existing table to reinitialize
        data: data,
        columns: [
            { data: 'date' },
            { data: 'reference_no' },
            { data: 'location.name' }, // Access nested location name
            { data: 'adjustment_type' },
            {
                data: 'adjustment_products',
                render: function(data, type, row) {
                    // Calculate total amount from adjustment_products
                    let totalAmount = data.reduce((sum, product) => sum + parseFloat(product.subtotal), 0);
                    return totalAmount.toFixed(2);
                }
            },
            { data: 'total_amount_recovered' },
            { data: 'reason' },
            { data: 'user.user_name' }, // Assuming 'userName' is part of the response
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
        columnDefs: [
            { targets: [8], orderable: false } // Disable sorting for the action column
        ]
    });
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
