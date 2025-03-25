<script>
    $(document).ready(function() {
        let productIndex = 1;
        let productsData = {};

        // Extract the stock transfer ID from the URL
        const pathSegments = window.location.pathname.split('/');
        const stockTransferId = pathSegments[pathSegments.length - 1] !== 'add-stock-transfer' ? pathSegments[pathSegments.length - 1] : null;

        // Fetch products and stock transfer data
        fetchProductsData();
        if (stockTransferId) {
            fetchStockTransferData(stockTransferId);
        }

        // Fetch locations data
        fetchDropdownData('/location-get-all?context=all_locations', $('#from_location_id'), "Select Location");
        fetchDropdownData('/location-get-all?context=all_locations', $('#to_location_id'), "Select Location");

        // Function to fetch products data
        function fetchProductsData() {
            $.ajax({
                url: '/products/stocks',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        productsData = response.data;
                        setupAutocomplete();
                    }
                },
                error: function(error) {
                    console.error('Error fetching products data:', error);
                }
            });
        }

        // Function to fetch stock transfer data
        function fetchStockTransferData(stockTransferId) {
            $.ajax({
                url: `/api/edit-stock-transfer/${stockTransferId}`,
                method: 'GET',
                success: function(response) {
                    if (response.stockTransfer) {
                        populateForm(response.stockTransfer);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching stock transfer:', error);
                }
            });
        }

        // Function to populate the form with stock transfer data
        function populateForm(stockTransfer) {
            $('#transfer_date').val(stockTransfer.transfer_date.split(' ')[0]);
            $('#reference_no').val(stockTransfer.reference_no);
            $('#status').val(stockTransfer.status);

            fetchDropdownData('/location-get-all?context=all_locations', $('#from_location_id'), "Select Location", stockTransfer.from_location_id);
            fetchDropdownData('/location-get-all?context=all_locations', $('#to_location_id'), "Select Location", stockTransfer.to_location_id);

            stockTransfer.stock_transfer_products.forEach(product => {
                addProductToTable(product, true);
            });

            updateTotalAmount();
        }

        // Function to add a product to the table
        function addProductToTable(productData, isEditing = false) {
            const product = productData.product;
            const existingRow = $(`tr[data-product-id="${product.id}"]`);

            if (existingRow.length > 0) {
                // Update the quantity if the product already exists in the table
                const quantityInput = existingRow.find('.quantity-input');
                const newQuantity = parseInt(quantityInput.val()) + productData.quantity;
                quantityInput.val(newQuantity);
                existingRow.find('.quantity-input').trigger('change');
                return;
            }

            const batches = product.batches ? product.batches.map(batch => ({
                batch_id: batch.id,
                batch_no: batch.batch_no,
                batch_price: parseFloat(batch.retail_price),
                batch_quantity: batch.qty,
                transfer_quantity: productData.quantity
            })) : [];

            if (batches.length === 0) {
                console.error('No batches available for product:', product.product_name);
                return;
            }

            const batchOptions = batches.map(batch => `
                <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}" data-transfer-quantity="${batch.transfer_quantity}">
                    Batch ${batch.batch_no} - Current Qty: ${batch.batch_quantity} - Transfer Qty: ${batch.transfer_quantity} - Price: ${batch.batch_price}
                </option>
            `).join('');

            const quantityInput = isEditing ? `
                <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="${batches[0].transfer_quantity}" required readonly>
            ` : `
                <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="${batches[0].transfer_quantity}" required>
            `;

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
                        <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            `;

            $(".add-table-items").find("tr:last").remove();
            $(".add-table-items").append(newRow);
            addTotalRow();
            updateTotalAmount();
            productIndex++;
        }

        // Function to setup autocomplete for product search
        function setupAutocomplete() {
            const productNames = productsData.map(data => data.product.product_name);

            $('#productSearch').autocomplete({
                source: productNames,
                select: function(event, ui) {
                    const selectedProduct = productsData.find(data => data.product.product_name === ui.item.value);
                    addProductWithBatches(selectedProduct);
                    $(this).val('');
                    return false;
                }
            });
        }

        // Function to add product to the table with dynamic batches
        function addProductWithBatches(productData) {
            const product = productData.product;
            const existingRow = $(`tr[data-product-id="${product.id}"]`);

            if (existingRow.length > 0) {
                // Update the quantity if the product already exists in the table
                const quantityInput = existingRow.find('.quantity-input');
                const newQuantity = parseInt(quantityInput.val()) + 1; // Increment by 1 for this example
                quantityInput.val(newQuantity);
                existingRow.find('.quantity-input').trigger('change');
                return;
            }

            const batches = productData.batches ? productData.batches.flatMap(batch => batch.location_batches.map(locationBatch => ({
                batch_id: batch.id,
                batch_no: batch.batch_no,
                batch_price: parseFloat(batch.retail_price),
                batch_quantity: locationBatch.quantity,
                transfer_quantity: locationBatch.quantity
            }))) : [];

            if (batches.length === 0) {
                console.error('No batches available for product:', product.product_name);
                return;
            }

            const batchOptions = batches.map(batch => `
                <option value="${batch.batch_id}" data-price="${batch.batch_price}" data-quantity="${batch.batch_quantity}" data-transfer-quantity="${batch.transfer_quantity}">
                    Batch ${batch.batch_no} - Current Qty: ${batch.batch_quantity} - Transfer Qty: ${batch.transfer_quantity} - Price: ${batch.batch_price}
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
                        <input type="number" class="form-control quantity-input" name="products[${productIndex}][quantity]" min="1" value="${batches[0].transfer_quantity}" required>
                        <div class="error-message quantity-error text-danger"></div>
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
            $(".add-table-items").append(newRow);
            addTotalRow();
            updateTotalAmount();
            productIndex++;
        }

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
            const quantity = parseFloat(row.find(".quantity-input").val());
            const selectedBatch = row.find(".batch-select option:selected");
            const unitPrice = parseFloat(selectedBatch.data("price"));
            const availableQuantity = parseFloat(selectedBatch.data("quantity"));
            const transferQuantity = parseFloat(selectedBatch.data("transfer-quantity"));

            if (quantity > (availableQuantity + transferQuantity)) {
                row.find(".quantity-error").text("The quantity exceeds the available batch quantity.");
                row.find(".quantity-input").val(availableQuantity + transferQuantity);
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
                const subtotal = parseFloat($(this).find('input[name^="products"][name$="[sub_total]"]').val());
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
                const formattedDate = new Date(`${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`).toISOString().split('T')[0];
                $('#transfer_date').val(formattedDate);

                const url = stockTransferId ? `/api/stock-transfer/update/${stockTransferId}` : '/api/stock-transfer/store';
                const method = stockTransferId ? 'PUT' : 'POST';

                const formData = $(form).serialize();

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,
                    success: function(response) {
                        toastr.success(response.message);
                        window.location.href = '/list-stock-transfer';
                    },
                    error: function(response) {
                        if (response.responseJSON && response.responseJSON.errors) {
                            for (const [key, value] of Object.entries(response.responseJSON.errors)) {
                                toastr.error(value.join(', '));
                            }
                        } else {
                            toastr.error(response.responseJSON.message || 'An error occurred. Please try again.');
                        }
                    }
                });
            }
        });

        function fetchDropdownData(url, targetSelect, placeholder, selectedId = null) {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.status === 200 && Array.isArray(data.message)) {
                        targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                        data.message.forEach(item => {
                            const option = $('<option></option>').val(item.id).text(item.name || item.first_name + ' ' + item.last_name);
                            targetSelect.append(option);
                        });

                        if (selectedId) {
                            targetSelect.val(selectedId).trigger('change');
                        }
                    } else {
                        console.error(`Failed to fetch data: ${data.message}`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`Error fetching data: ${error}`);
                }
            });
        }

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
            const tableBody = $('#stockTransfer tbody');
            tableBody.empty();

            data.forEach(transfer => {
                const totalAmount = transfer.stock_transfer_products.reduce((sum, product) => {
                    return sum + (product.quantity * product.unit_price);
                }, 0);

                const row = `
                    <tr>
                        <td>${new Date(transfer.transfer_date).toLocaleDateString()}</td>
                        <td>${transfer.reference_no}</td>
                        <td>${transfer.from_location.name}</td>
                        <td>${transfer.to_location.name}</td>
                        <td>${transfer.status}</td>
                        <td>${transfer.shipping_charges || '0.00'}</td>
                        <td>${totalAmount.toFixed(2)}</td>
                        <td>${transfer.note || ''}</td>
                        <td>
                            <a href="/edit-stock-transfer/${transfer.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteStockTransfer(${transfer.id})" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tableBody.append(row);
            });
        }

        window.deleteStockTransfer = function(id) {
            if (confirm('Are you sure you want to delete this stock transfer?')) {
                $.ajax({
                    url: `/stock-transfers/${id}`,
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
