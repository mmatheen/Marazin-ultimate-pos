<script>
    $(document).ready(function() {
        let productIndex = 1;
        let locationFilteredProducts = [];

        // Initialize autocomplete with custom source to match name or SKU
        $('#productSearch').autocomplete({
            minLength: 1,
            source: function(request, response) {
                const term = $.ui.autocomplete.escapeRegex(request.term);
                const matcher = new RegExp(term, "i");
                // Only one suggestion per product, but matches on name or SKU
                const matches = locationFilteredProducts.filter(data =>
                    matcher.test(data.product.product_name) ||
                    matcher.test(data.product.sku)
                );
                // Display as "Product Name (SKU)" for clarity
                response(matches.map(data => ({
                    label: data.product.product_name + (data.product.sku ? " (" +
                        data.product.sku + ")" : ""),
                    value: data.product.product_name
                })));
            },
            select: function(event, ui) {
                // Find the product by name or SKU
                const selectedProduct = locationFilteredProducts.find(data =>
                    data.product.product_name === ui.item.value ||
                    data.product.sku === ui.item.value
                );
                if (selectedProduct) {
                    addProductWithBatches(selectedProduct);
                    $(this).val('');
                }
                return false;
            }
        });

        $.ui.autocomplete.prototype._resizeMenu = function() {
            var ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth());
            ul.css({
                "max-height": "250px",
                "overflow-y": "auto"
            });
        };

        const pathSegments = window.location.pathname.split('/');
        const stockTransferId = pathSegments[pathSegments.length - 1] !== 'add-stock-transfer' ?
            pathSegments[pathSegments.length - 1] : null;

        fetchDropdownData('/location-get-all?context=all_locations', $('#from_location_id'), "Select Location");
        fetchDropdownData('/location-get-all?context=all_locations', $('#to_location_id'), "Select Location");

        $('#from_location_id').on('change', function() {
            fetchProductsData();

            // Clear the product search input when the location changes
            $('#productSearch').val('');
            $('.add-table-items').empty();
            addTotalRow();

        });

        if (stockTransferId) {
            const checkLocationInterval = setInterval(() => {
                if ($('#from_location_id').val()) {
                    clearInterval(checkLocationInterval);
                    fetchStockTransferData(stockTransferId);
                    fetchProductsData();
                }
            }, 200);
        }

        function fetchProductsData() {
            const fromLocationId = $('#from_location_id').val();
            if (!fromLocationId) {
                console.warn("No 'From Location' selected.");
                $('#productSearch').autocomplete("option", "source", []);
                return;
            }

            $.ajax({
                url: '/products/stocks',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        const filteredProducts = response.data
                            .map(productData => {
                                const validBatches = productData.batches.filter(batch =>
                                    batch.location_batches.some(locBatch =>
                                        locBatch.location_id == fromLocationId && locBatch
                                        .quantity > 0
                                    )
                                );
                                if (validBatches.length > 0) {
                                    return {
                                        ...productData,
                                        batches: validBatches
                                    };
                                }
                                return null;
                            })
                            .filter(Boolean);
                        locationFilteredProducts = filteredProducts;
                        // No need for setupAutocomplete, the source is dynamic and uses locationFilteredProducts
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
                url: `/edit-stock-transfer/${stockTransferId}`,
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
            fetchDropdownData('/location-get-all?context=all_locations', $('#from_location_id'),
                "Select Location", stockTransfer.from_location_id);
            fetchDropdownData('/location-get-all?context=all_locations', $('#to_location_id'),
                "Select Location", stockTransfer.to_location_id);
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

            // Filter batches to only include those in the selected "From" location
            const fromLocationId = $('#from_location_id').val();
            // Only use batches from the selected "From" location
            const batches = product.batches.flatMap(batch => {
                return batch.location_batches
                    .filter(locBatch => locBatch.location_id == fromLocationId && locBatch.quantity > 0)
                    .map(locationBatch => ({
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        batch_price: parseFloat(batch.retail_price),
                        batch_quantity: locationBatch.quantity,
                        transfer_quantity: productData.quantity
                    }));
            });

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
                    <a href="javascript:void(0);" class="remove-btn">
                        <i class="fas fa-trash" style="color: #dc3545;"></i>
                    </a>
                </td>
            </tr>
        `;

            $(".add-table-items").find("tr:last").remove();
            $(".add-table-items").append(newRow);
            addTotalRow();
            updateTotalAmount();
            productIndex++;
        }

        function addProductWithBatches(productData) {
            const fromLocationId = $('#from_location_id').val();
            if (!fromLocationId) {
                toastr.warning("Please select a 'From' location before adding products.");
                return;
            }

            const product = productData.product;
            const existingRow = $(`tr[data-product-id="${product.id}"]`);

            if (existingRow.length > 0) {
                const quantityInput = existingRow.find('.quantity-input');
                const newQuantity = parseInt(quantityInput.val()) + 1;
                quantityInput.val(newQuantity);
                existingRow.find('.quantity-input').trigger('change');
                return;
            }

            // Filter batches to only those in the selected "From" location with quantity > 0
            const batches = productData.batches.flatMap(batch => {
                return batch.location_batches
                    .filter(locBatch => locBatch.location_id == fromLocationId && locBatch.quantity > 0)
                    .map(locationBatch => ({
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        batch_price: parseFloat(batch.retail_price),
                        batch_quantity: locationBatch.quantity,
                        transfer_quantity: locationBatch.quantity
                    }));
            });

            if (batches.length === 0) {
                console.warn(`No batches available in selected location for product: ${product.product_name}`);
                toastr.error(
                    `No batches available in "${$('#from_location_id option:selected').text()}" for "${product.product_name}".`
                );
                return;
            }

            const batchOptions = batches.map(batch => `
        <option value="${batch.batch_id}" 
                data-price="${batch.batch_price}" 
                data-quantity="${batch.batch_quantity}"
                data-transfer-quantity="${batch.transfer_quantity}">
            Batch ${batch.batch_no} - Qty: ${batch.batch_quantity} - Price: ${batch.batch_price}
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

        function fetchDropdownData(url, targetSelect, placeholder, selectedId = null) {
            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.status === 200 && Array.isArray(data.message)) {
                        targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                        data.message.forEach(item => {
                            const option = $('<option></option>').val(item.id).text(item
                                .name || item.first_name + ' ' + item.last_name);
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
            // Prepare DataTable data
            const tableData = data.map((transfer, index) => {
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
                actions: actions
            };
            });

            $('#stockTransfer').DataTable({
            destroy: true,
            data: tableData,
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
                }
            ],
            order: [
                [1, "desc"]
            ]
            });
        }

        // Print function: supports both A4 and 80mm (thermal) layouts
        window.printStockTransfer = function(stockTransferId, layout = '80mm') {
            $.ajax({
            url: `/stock-transfer/get/${stockTransferId}`,
            method: 'GET',
            success: function(response) {
            if (response.status === 200) {
            const stockTransfer = response.stockTransfer;

            // --- 80mm (thermal) layout ---
            let printContent80mm = `
            <div id="printArea" style="width:80mm; max-width:80mm; font-family: 'monospace', Arial, sans-serif; font-size:12px; color:#222; margin:0 auto; padding:0; box-sizing:border-box;">
                <div style="text-align:center; margin-bottom:4px;">
                <strong style="font-size:15px;">Stock Transfer</strong><br>
                <span style="font-size:11px;">Ref: <b>${stockTransfer.reference_no}</b></span>
                </div>
                <div style="border-top:1px dashed #222; margin:4px 0;"></div>
                <table style="width:100%; font-size:11px; margin-bottom:2px;">
                <tr>
                <td style="vertical-align:top; width:50%;">
                <b>From:</b> ${stockTransfer.from_location ? stockTransfer.from_location.name : ''}<br>
                <span style="font-size:10px;">${stockTransfer.from_location && stockTransfer.from_location.address ? stockTransfer.from_location.address : ''}</span>
                </td>
                <td style="vertical-align:top; width:50%; text-align:right;">
                <b>To:</b> ${stockTransfer.to_location ? stockTransfer.to_location.name : ''}<br>
                <span style="font-size:10px;">${stockTransfer.to_location && stockTransfer.to_location.address ? stockTransfer.to_location.address : ''}</span>
                </td>
                </tr>
                <tr>
                <td colspan="2" style="font-size:10px; padding-top:2px;">
                <b>Date:</b> ${new Date(stockTransfer.transfer_date).toLocaleDateString()} &nbsp; <b>Status:</b> ${stockTransfer.status}
                </td>
                </tr>
                </table>
                <div style="border-top:1px dashed #222; margin:4px 0;"></div>
                <table style="width:100%; font-size:11px;">
                <thead>
                <tr>
                <th style="text-align:left;">#</th>
                <th style="text-align:left;">Product</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Sub</th>
                </tr>
                </thead>
                <tbody>
            `;
            stockTransfer.stock_transfer_products.forEach((product, idx) => {
                printContent80mm += `
                <tr>
                <td style="text-align:left;">${idx + 1}</td>
                <td style="text-align:left;">
                ${product.product ? product.product.product_name : ''}
                <div style="font-size:9px; color:#888;">${product.product ? product.product.sku : ''}</div>
                </td>
                <td style="text-align:center;">${product.quantity}</td>
                <td style="text-align:right;">${parseFloat(product.sub_total).toFixed(2)}</td>
                </tr>
                `;
            });
            printContent80mm += `
                </tbody>
                </table>
                <div style="border-top:1px dashed #222; margin:4px 0;"></div>
                <table style="width:100%; font-size:11px;">
                <tr>
                <td style="text-align:left;">Net Total:</td>
                <td style="text-align:right;"><b>Rs.${stockTransfer.stock_transfer_products.reduce((sum, p) => sum + (parseFloat(p.sub_total) || 0), 0).toFixed(2)}</b></td>
                </tr>
                <tr>
                <td style="text-align:left;">Shipping:</td>
                <td style="text-align:right;">Rs.${stockTransfer.shipping_charges ? parseFloat(stockTransfer.shipping_charges).toFixed(2) : '0.00'}</td>
                </tr>
                <tr>
                <td style="text-align:left;">Total:</td>
                <td style="text-align:right;"><b>Rs.${(
                stockTransfer.stock_transfer_products.reduce((sum, p) => sum + (parseFloat(p.sub_total) || 0), 0)
                + (parseFloat(stockTransfer.shipping_charges) || 0)
                ).toFixed(2)}</b></td>
                </tr>
                </table>
                <div style="border-top:1px dashed #222; margin:4px 0;"></div>
                <div style="font-size:10px; margin-bottom:2px;">
                <b>Notes:</b> ${stockTransfer.note || '--'}
                </div>
            `;

            // Activities (compact)
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
                note: log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : ''
                }));
            } else if (Array.isArray(window.lastStockTransferActivityLogs) && window.lastStockTransferActivityLogs.length > 0) {
                activities = window.lastStockTransferActivityLogs.map(log => ({
                date: log.created_at,
                action: log.description,
                user: log.causer,
                note: log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : ''
                }));
            } else if (Array.isArray(stockTransfer.activityLogs) && stockTransfer.activityLogs.length > 0) {
                activities = stockTransfer.activityLogs.map(log => ({
                date: log.created_at,
                action: log.description,
                user: log.causer,
                note: log.properties && log.properties.attributes && log.properties.attributes.note ? log.properties.attributes.note : ''
                }));
            }

            printContent80mm += `
                <div style="font-size:10px; margin-top:2px;">
                <b>Activities:</b>
                <table style="width:100%; font-size:9px; margin-top:2px;">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>By</th>
                </tr>
                </thead>
                <tbody>
            `;
            if (activities.length > 0) {
                activities.forEach(activity => {
                printContent80mm += `
                <tr>
                <td>${activity.date ? new Date(activity.date).toLocaleDateString() : ''}</td>
                <td>${activity.action || ''}</td>
                <td>${activity.user ? (activity.user.name || (activity.user.first_name && activity.user.last_name ? activity.user.first_name + ' ' + activity.user.last_name : '')) : ''}</td>
                </tr>
                `;
                });
            } else {
                printContent80mm += `
                <tr>
                <td colspan="3" style="text-align:center; color:#888;">No activities</td>
                </tr>
                `;
            }
            printContent80mm += `
                </tbody>
                </table>
                </div>
                <div style="border-top:1px dashed #222; margin:4px 0 0 0;"></div>
                <div style="text-align:center; font-size:10px; margin-top:2px;">
                Thank you!
                </div>
            </div>
            `;

            const style80mm = `<style>
                @media print {
                @page {
                size: 80mm auto;
                margin: 0.10in !important;
                }
                html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 80mm !important;
                max-width: 80mm !important;
                }
                #printArea {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
                }
                table { border-collapse: collapse; width:100%; }
                th, td { border: none !important; padding: 0 2px; }
                .no-print { display: none !important; }
                }
                #printArea { width:80mm; max-width:80mm; margin:0 auto; padding:0; box-sizing:border-box; }
                table { border-collapse: collapse; width:100%; }
                th, td { border: none !important; padding: 0 2px; }
            </style>`;

            // --- A4 layout ---
            let printContentA4 = `
            <div id="printArea" style="width:210mm; max-width:210mm; font-family: Arial, sans-serif; font-size:13px; color:#222; margin:0 auto; padding:0; box-sizing:border-box;">
                <div style="text-align:center; margin-bottom:10px;">
                <strong style="font-size:22px;">Stock Transfer</strong><br>
                <span style="font-size:14px;">Reference: <b>${stockTransfer.reference_no}</b></span>
                </div>
                <table style="width:100%; font-size:13px; margin-bottom:10px;">
                <tr>
                <td style="vertical-align:top; width:50%;">
                <b>From:</b> ${stockTransfer.from_location ? stockTransfer.from_location.name : ''}<br>
                <span style="font-size:12px;">${stockTransfer.from_location && stockTransfer.from_location.address ? stockTransfer.from_location.address : ''}</span>
                </td>
                <td style="vertical-align:top; width:50%; text-align:right;">
                <b>To:</b> ${stockTransfer.to_location ? stockTransfer.to_location.name : ''}<br>
                <span style="font-size:12px;">${stockTransfer.to_location && stockTransfer.to_location.address ? stockTransfer.to_location.address : ''}</span>
                </td>
                </tr>
                <tr>
                <td colspan="2" style="font-size:12px; padding-top:4px;">
                <b>Date:</b> ${new Date(stockTransfer.transfer_date).toLocaleDateString()} &nbsp; <b>Status:</b> ${stockTransfer.status}
                </td>
                </tr>
                </table>
                <hr style="margin:8px 0;">
                <table style="width:100%; font-size:13px; border-collapse:collapse;" border="1">
                <thead>
                <tr>
                <th style="text-align:left;">#</th>
                <th style="text-align:left;">Product</th>
                <th style="text-align:left;">SKU</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Unit Price</th>
                <th style="text-align:right;">Sub Total</th>
                </tr>
                </thead>
                <tbody>
            `;
            stockTransfer.stock_transfer_products.forEach((product, idx) => {
                printContentA4 += `
                <tr>
                <td style="text-align:left;">${idx + 1}</td>
                <td style="text-align:left;">${product.product ? product.product.product_name : ''}</td>
                <td style="text-align:left;">${product.product ? product.product.sku : ''}</td>
                <td style="text-align:center;">${product.quantity}</td>
                <td style="text-align:right;">${parseFloat(product.unit_price).toFixed(2)}</td>
                <td style="text-align:right;">${parseFloat(product.sub_total).toFixed(2)}</td>
                </tr>
                `;
            });
            printContentA4 += `
                </tbody>
                </table>
                <hr style="margin:8px 0;">
                <table style="width:100%; font-size:13px;">
                <tr>
                <td style="text-align:left;">Net Total:</td>
                <td style="text-align:right;"><b>Rs.${stockTransfer.stock_transfer_products.reduce((sum, p) => sum + (parseFloat(p.sub_total) || 0), 0).toFixed(2)}</b></td>
                </tr>
                <tr>
                <td style="text-align:left;">Shipping:</td>
                <td style="text-align:right;">Rs.${stockTransfer.shipping_charges ? parseFloat(stockTransfer.shipping_charges).toFixed(2) : '0.00'}</td>
                </tr>
                <tr>
                <td style="text-align:left;">Total:</td>
                <td style="text-align:right;"><b>Rs.${(
                stockTransfer.stock_transfer_products.reduce((sum, p) => sum + (parseFloat(p.sub_total) || 0), 0)
                + (parseFloat(stockTransfer.shipping_charges) || 0)
                ).toFixed(2)}</b></td>
                </tr>
                </table>
                <div style="font-size:13px; margin:10px 0;">
                <b>Notes:</b> ${stockTransfer.note || '--'}
                </div>
            `;

            // Activities (detailed)
            printContentA4 += `
                <div style="font-size:13px; margin-top:10px;">
                <b>Activities:</b>
                <table style="width:100%; font-size:12px; border-collapse:collapse;" border="1">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>By</th>
                    <th>Note</th>
                </tr>
                </thead>
                <tbody>
            `;
            if (activities.length > 0) {
                activities.forEach(activity => {
                printContentA4 += `
                <tr>
                <td>${activity.date ? new Date(activity.date).toLocaleString() : ''}</td>
                <td>${activity.action || ''}</td>
                <td>${activity.user ? (activity.user.name || (activity.user.first_name && activity.user.last_name ? activity.user.first_name + ' ' + activity.user.last_name : '')) : ''}</td>
                <td>${activity.note || ''}</td>
                </tr>
                `;
                });
            } else {
                printContentA4 += `
                <tr>
                <td colspan="4" style="text-align:center; color:#888;">No activities</td>
                </tr>
                `;
            }
            printContentA4 += `
                </tbody>
                </table>
                </div>
                <div style="text-align:center; font-size:13px; margin-top:10px;">
                Thank you!
                </div>
            </div>
            `;

            const styleA4 = `<style>
                @media print {
                @page {
                size: A4 portrait;
                margin: 0.5in !important;
                }
                html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm !important;
                max-width: 210mm !important;
                }
                #printArea {
                width: 210mm !important;
                max-width: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-sizing: border-box;
                }
                table { border-collapse: collapse; width:100%; }
                th, td { padding: 4px 6px; }
                .no-print { display: none !important; }
                }
                #printArea { width:210mm; max-width:210mm; margin:0 auto; padding:0; box-sizing:border-box; }
                table { border-collapse: collapse; width:100%; }
                th, td { padding: 4px 6px; }
            </style>`;

            // Print logic
            const originalContent = document.body.innerHTML;
            if (layout === 'a4' || layout === 'A4') {
                document.body.innerHTML = styleA4 + printContentA4;
            } else {
                document.body.innerHTML = style80mm + printContent80mm;
            }
            window.print();
            document.body.innerHTML = originalContent;
            } else {
            toastr.error('Failed to fetch stock transfer details for printing.');
            }
            },
            error: function(xhr, status, error) {
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
