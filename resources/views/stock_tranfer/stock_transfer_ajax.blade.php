<script>
    $(document).ready(function() {
        let productIndex = 1;
        let productsData = {};

        // Fetch locations data from the server
        fetchDropdownData('/location-get-all', $('#from_location_id'), "Select Location");
        fetchDropdownData('/location-get-all', $('#to_location_id'), "Select Location");

        // Fetch products data from the server
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

        function setupAutocomplete() {
            const productNames = productsData.map(data => data.product.product_name);

            $('#productSearch').autocomplete({
                source: productNames,
                select: function(event, ui) {
                    const selectedProduct = productsData.find(data => data.product.product_name === ui.item.value);
                    addProductToTable(selectedProduct);
                    $(this).val('');
                    return false;
                }
            });
        }

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

            $(".add-table-items").find("tr:last").remove(); // Remove the existing total row
            $(".add-table-items").append(newRow); // Append the new row
            addTotalRow(); // Add the total row again at the end
            updateTotal(); // Update the total
            productIndex++;
        }

        $(document).on("change", ".batch-select", function() {
            const row = $(this).closest("tr");
            const selectedBatch = row.find(".batch-select option:selected");
            const unitPrice = parseFloat(selectedBatch.data("price"));
            row.find(".unit-price").val(unitPrice.toFixed(2));
            const quantity = parseFloat(row.find(".quantity-input").val());
            const subtotal = quantity * unitPrice;
            row.find(".subtotal").val(subtotal.toFixed(2));
            updateTotal();
        });

        $(document).on("change", ".quantity-input", function() {
            const row = $(this).closest("tr");
            const quantity = parseFloat(row.find(".quantity-input").val());
            const selectedBatch = row.find(".batch-select option:selected");
            const unitPrice = parseFloat(selectedBatch.data("price"));
            const availableQuantity = parseFloat(selectedBatch.data("quantity"));

            if (quantity > availableQuantity) {
                row.find(".quantity-error").text("The quantity exceeds the available batch quantity.");
                row.find(".quantity-input").val(availableQuantity);
            } else {
                row.find(".quantity-error").text("");
            }

            const subtotal = quantity * unitPrice;
            row.find(".unit-price").val(unitPrice.toFixed(2));
            row.find(".subtotal").val(subtotal.toFixed(2));
            updateTotal();
        });

        $(document).on("click", ".remove-btn", function() {
            $(this).closest(".add-row").remove();
            updateTotal();
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

        function updateTotal() {
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
            submitHandler: function(form) {
                const fromLocationId = $('#from_location_id').val();
                const toLocationId = $('#to_location_id').val();

                if (fromLocationId === toLocationId) {
                    toastr.error('Please select different locations for "From" and "To".');
                    return;
                }

                // Check if any products are added
                if ($('.add-row').length === 0) {
                    toastr.error('Please add at least one product.');
                    return;
                }

                // Format the transfer date to YYYY-MM-DD
                const transferDate = $('#transfer_date').val();
                const formattedDate = new Date(transferDate.split('-').reverse().join('-')).toISOString().split('T')[0];
                $('#transfer_date').val(formattedDate);

                $.ajax({
                    url: '{{ route("stock-transfer.store") }}',
                    method: 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        toastr.success(response.message);
                        location.reload();
                    },
                    error: function(response) {
                        alert('Error: ' + response.responseJSON.message);
                    }
                });
            }
        });
   // Fetch locations data from the server
   fetchDropdownData('/location-get-all', $('#location_id'), "Select Location");


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
                    if (item.id == selectedId) {
                        option.attr('selected', 'selected');
                    }
                    targetSelect.append(option);
                });
            } else {
                console.error(`Failed to fetch data: ${data.message}`);
            }
        },
        error: function(xhr, status, error) {
            console.error(`Error fetching data: ${error}`);
        }
    });
}
    });
</script>
