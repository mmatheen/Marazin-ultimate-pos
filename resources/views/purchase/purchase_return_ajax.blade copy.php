<script>
    $(document).ready(function() {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        // Initialize jQuery Validation Plugin
        $("#addAndUpdatePurchaseReturnForm").validate({
            rules: {
                supplier_id: {
                    required: true
                },
                return_date: {
                    required: true
                }
            },
            messages: {
                supplier_id: {
                    required: "Please select a supplier."
                },
                return_date: {
                    required: "Please enter a return date."
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                element.closest('.form-group, .input-group').append(error);
            },
            highlight: function(element) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid');
            }
        });

        // Reset form button click handler
        $('.btn-reset[type="reset"]').on('click', resetFormAndValidation);

        // File input change handler
        $(".show-file").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    if (file.type === "application/pdf") {
                        $("#pdfViewer").attr("src", e.target.result).show();
                        $("#selectedImage").hide();
                    } else if (file.type.startsWith("image/")) {
                        $("#selectedImage").attr("src", e.target.result).show();
                        $("#pdfViewer").hide();
                    }
                };

                reader.readAsDataURL(file);
            }
        });

        // Fetch dropdown data
        function fetchDropdownData(url, targetSelect, placeholder, selectedId) {
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

        // Disable the product search input initially
        $('#productSearchInput').prop('disabled', true);

        // Fetch supplier and location data
        fetchDropdownData('/supplier-get-all', $('#supplier-id'), "Select Supplier");
        fetchDropdownData('/location-get-all', $('#location-id'), "Select Location",
            2); // Default to location with ID 2

        // Supplier change handler
        $('#supplier-id').change(function() {
            const supplierId = $(this).val();
            if (supplierId) {
                fetchPurchaseProductsBySupplier(supplierId);
                $('#productSearchInput').prop('disabled', false);
            } else {
                $('#productSearchInput').prop('disabled', true);
            }
        });

        // Fetch purchase products based on supplier ID
        function fetchPurchaseProductsBySupplier(supplierId) {
            $.ajax({
                url: `/purchase-products-by-supplier/${supplierId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchases) {
                        const allProducts = response.purchases.flatMap(purchase =>
                            purchase.purchase_products.map(product => ({
                                id: product.product.id,
                                name: product.product.product_name,
                                quantity: product.quantity,
                                price: product.unit_cost,
                                batches: product.batch ? [product.batch] : []
                            }))
                        );
                        initAutocomplete(allProducts);
                    } else {
                        alert('Error: No purchases found for this supplier.');
                    }
                },
                error: function(xhr) {
                    const message = xhr.status === 404 ? 'No purchases found for this supplier.' :
                        'An error occurred while fetching purchase products.';
                    alert(message);
                }
            });
        }

        // Initialize autocomplete functionality
        function initAutocomplete(products) {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = products.filter(product => product.name && product.name
                        .toLowerCase().includes(searchTerm));
                    response(filteredProducts.map(product => ({
                        label: `${product.name}`,
                        value: product.name,
                        product: product
                    })));
                },
                select: function(event, ui) {
                    $("#productSearchInput").val(ui.item.value);
                    if (ui.item.product && ui.item.product.batches.length > 0) {
                        addProductToTable(ui.item.product);
                    } else {
                        alert('Selected product does not have batch information.');
                    }
                    return false;
                }
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>")
                    .append(`<div>${item.label}</div>`)
                    .appendTo(ul);
            };
        }

        // Add product to table
        function addProductToTable(product) {
            const existingRow = $(`#purchase_return tbody tr[data-id="${product.id}"]`);

            if (existingRow.length > 0) {
                // Product already exists in the table, update the quantity
                const quantityInput = existingRow.find('.purchase-quantity');
                const maxQuantity = parseInt(quantityInput.attr('max')) || 0;
                let newQuantity = parseInt(quantityInput.val()) + 1;

                if (newQuantity > maxQuantity) {
                    toastr.warning(`Cannot enter more than ${maxQuantity} for this product.`,
                        'Quantity Limit Exceeded');
                    newQuantity = maxQuantity;
                }

                quantityInput.val(newQuantity);
                updateRow(existingRow);
                updateFooter();
            } else {
                // Product does not exist in the table, add a new row
                const quantity = 1; // Initial quantity set to 1 (can be changed later)
                const subtotal = product.price * quantity;

                // Generate batch options
                const batchOptions = (product.batches || []).map(batch => `
                <option value="${batch.id}" data-unit-cost="${batch.unit_cost}">${batch.batch_no} - Qty: ${batch.qty} - Unit Price: ${batch.unit_cost} - Exp: ${batch.expiry_date}</option>
            `).join('');

                // Generate the new row
                const newRow = `
                <tr data-id="${product.id}">
                    <td>${product.id}</td>
                    <td>${product.name || '-'}</td>
                    <td><select class="form-control batch-select">${batchOptions}</select></td>
                    <td><input type="number" class="form-control purchase-quantity" value="${quantity}" min="1" max="${product.quantity}"></td>
                    <td class="unit-price">${product.price || '0'}</td>
                    <td class="sub-total">${subtotal.toFixed(2)}</td>
                    <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;

                // Add the new row to the DataTable
                const $newRow = $(newRow);
                $('#purchase_return').DataTable().row.add($newRow).draw();

                // Update footer after adding the product
                updateFooter();
                toastr.success('New product added to the table!', 'Success');

                // Add event listeners for dynamic updates
                $newRow.find('.purchase-quantity').on('input', function() {
                    updateRow($newRow);
                    updateFooter();
                });

                $newRow.find('.batch-select').on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const unitCost = parseFloat(selectedOption.data('unit-cost')) || 0;
                    $newRow.find('.unit-price').text(unitCost.toFixed(2));
                    updateRow($newRow);
                    updateFooter();
                });
            }

            // Update row values
            function updateRow($row) {
                const quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
                const price = parseFloat($row.find('.unit-price').text()) || 0;
                const subTotal = quantity * price;

                $row.find('.sub-total').text(subTotal.toFixed(2));
            }
        }

        // Remove product from table
        function removeProductFromTable(button) {
            const row = $(button).closest('tr'); // Get the row containing the button
            const productId = row.data('id'); // Get the product ID from the row

            // Remove the row from the DataTable
            $('#purchase_return').DataTable().row(row).remove().draw();

            // Update the footer after removal
            updateFooter();

            toastr.success(`Product ID ${productId} removed from the table!`, 'Success');
        }

        // Event listener for remove button
        $('#purchase_return').on('click', '.delete-product', function() {
            removeProductFromTable(this);
        });

        // Update footer function
        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#purchase_return tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
                const price = parseFloat($(this).find('.unit-price').text()) || 0;
                const subtotal = quantity * price;

                $(this).find('.sub-total').text(subtotal.toFixed(2));

                totalItems += quantity;
                netTotalAmount += subtotal;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
        }

        // Form submission handler
        $('#addAndUpdatePurchaseReturnForm').on('submit', function(event) {
            event.preventDefault();

            // Validate the form before submitting
            if (!$('#addAndUpdatePurchaseReturnForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            const formData = new FormData(this);
            const purchaseReturnId = $('#purchase-return-id').val(); // Get the ID for update

            // Collect product data separately since they are not part of the form fields
            $('#purchase_return tbody tr').each(function(index) {
                const row = $(this);
                const quantity = parseFloat(row.find('.purchase-quantity').val()) || 0;
                const unitPrice = parseFloat(row.find('.unit-price').text()) || 0;
                const subtotal = parseFloat(row.find('.sub-total').text()) || 0;
                const batchId = row.find('.batch-select').val();

                formData.append(`products[${index}][product_id]`, row.data('id'));
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][unit_price]`, unitPrice);
                formData.append(`products[${index}][subtotal]`, subtotal);
                formData.append(`products[${index}][batch_id]`, batchId);
            });

            // Format the return date correctly
            const returnDate = $('#return_date').val();
            const formattedReturnDate = formatDate(returnDate);
            formData.set('return_date', formattedReturnDate);

            // Determine the URL and request type based on whether we're updating or creating
            const url = purchaseReturnId ? `/purchase_returns/update/${purchaseReturnId}` :
                '/purchase_returns/store';
            const requestType = purchaseReturnId ? 'POST' : 'POST';

            $.ajax({
                url: url,
                method: requestType,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    if (response.status === 400) {
                        $.each(response.errors, function(key, err_value) {
                            $('#' + key + '_error').html(err_value);
                        });
                    } else {
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Purchase Return');
                        resetFormAndValidation();
                    }
                },
                error: function(xhr, status, error) {
                    console.error(purchaseReturnId ? 'Error updating purchase return:' :
                        'Error adding purchase return:', error);
                    toastr.error(
                        `Something went wrong while ${purchaseReturnId ? 'updating' : 'adding'} the purchase return.`,
                        'Error');
                }
            });
        });

        function formatDate(inputDate) {
            const dateParts = inputDate.split("-");
            if (dateParts.length === 3) {
                return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            }
            return inputDate;
        }

        function resetFormAndValidation() {
            // Reset the form
            $('#addAndUpdatePurchaseReturnForm')[0].reset();
            $('#addAndUpdatePurchaseReturnForm').validate().resetForm();
            $('#addAndUpdatePurchaseReturnForm').find('.is-invalid').removeClass('is-invalid');

            // Clear the DataTable
            $('#purchase_return').DataTable().clear().draw();

            // Reset the footer values
            $('#total-items').text('0.00');
            $('#net-total-amount').text('0.00');

            // Disable the product search input
            $('#productSearchInput').prop('disabled', true);

            // Hide file preview
            $("#pdfViewer").hide();
            $("#selectedImage").hide();
        }

        // Initialize DataTable
        var table = $('#purchase_return_list').DataTable();

        // Fetch data with AJAX
        function fetchData() {
            $.ajax({
                url: 'purchase-returns/get-All', // API endpoint
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchases_Return) {
                        let tableData = response.purchases_Return.map(purchase => {
                            let supplierName = purchase.supplier ?
                                `${purchase.supplier.first_name} ${purchase.supplier.last_name}` :
                                'Unknown Supplier';
                            let locationName = purchase.location ? purchase.location.name :
                                'Unknown Location';
                            let grandTotal = purchase.purchase_return_products.reduce((
                                total, product) => total + parseFloat(product
                                .subtotal), 0);
                            let paymentDue = grandTotal - parseFloat(purchase.final_total ||
                                0);

                            return [
                                purchase.return_date,
                                purchase.reference_no,
                                purchase.id,
                                locationName,
                                supplierName,
                                'Due', // Assuming 'Due' as a static value for Payment Status
                                grandTotal.toFixed(2), // Format to 2 decimal places
                                paymentDue.toFixed(2), // Format to 2 decimal places
                                `<div class="dropdown dropdown-action">
                                <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item view-btn" href="#" data-id="${purchase.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                    <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                    <a class="dropdown-item edit-link" href="/purchase-returns/edit/${purchase.id}" data-id="${purchase.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                    <a class="dropdown-item add-payment-btn" href="#" data-id="${purchase.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                </div>
                            </div>`
                            ];
                        });

                        // Initialize or update the DataTable
                        table.clear().rows.add(tableData).draw();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching purchases:', error);
                }
            });
        }

        fetchData();

        // View button click to show modal
        $('#purchase_return_list tbody').on('click', '.view-btn', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const purchaseId = $(this).data('id'); // Get purchase ID directly from data attribute

            // Fetch purchase details using AJAX
            $.ajax({
                url: `purchase-returns/get-Details/${purchaseId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchase_return) {
                        const purchaseReturn = response.purchase_return;
                        const supplier = purchaseReturn.supplier ?
                            `${purchaseReturn.supplier.first_name} ${purchaseReturn.supplier.last_name}` :
                            'Unknown Supplier';
                        const location = purchaseReturn.location ? purchaseReturn.location
                            .name : 'Unknown Location';

                        // Dynamically generate products table
                        const productsHtml = purchaseReturn.purchase_return_products
                            .length > 0 ?
                            purchaseReturn.purchase_return_products.map((product, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${product.product.product_name}</td>
                                <td>$ ${parseFloat(product.unit_price).toFixed(2)}</td>
                                <td>${product.quantity} Pc(s)</td>
                                <td>$ ${parseFloat(product.subtotal).toFixed(2)}</td>
                            </tr>
                        `).join('') :
                            `<tr><td colspan="5" class="text-center">No products found for this purchase return.</td></tr>`;

                        const netTotal = purchaseReturn.purchase_return_products.reduce((
                                total, product) => total + parseFloat(product.subtotal),
                            0);
                        const returnTax = parseFloat(purchaseReturn.tax_amount || 0);
                        const returnTotal = netTotal + returnTax;

                        // Inject content into modal
                        const modalContent = `
                        <span class="close-btn" id="closeBtn">&times;</span>
                        <h2>Purchase Return Details</h2>
                        <h2>Reference No: ${purchaseReturn.reference_no}</h2>
                        <p><b>Return Date:</b> ${purchaseReturn.return_date}</p>
                        <p><b>Supplier:</b> ${supplier}</p>
                        <p><b>Business Location:</b> ${location}</p>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product Name</th>
                                    <th>Unit Price</th>
                                    <th>Return Quantity</th>
                                    <th>Return Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${productsHtml}
                        </tbody>
                    </table>
                    <div class="footer">
                        <div>
                            <p><b>Net Total Amount:</b> $${netTotal.toFixed(2)}</p>
                            <p><b>Net Total Return Tax:</b> $${returnTax.toFixed(2)}</p>
                            <p><b>Return Total:</b> $${returnTotal.toFixed(2)}</p>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button class="button print-btn">Print</button>
                            <button class="button close">Close</button>
                        </div>
                    </div>
                `;

                        $('#myModal .modal-content').html(
                            modalContent); // Inject content into modal
                        $('#myModal').css("display", "block");

                        var modal = document.getElementById("myModal");
                        var span = document.getElementById("closeBtn");
                        span.onclick = function() {
                            modal.style.display = "none";
                        }

                        // Modal close button
                        $('#myModal').on('click', '.close', function() {
                            $('#myModal').hide();
                        });

                        // Print button
                        $('#myModal').off('click').on('click', '.print-btn', function() {
                            window.print();
                        });

                    } else {
                        alert("No details found for this purchase.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching purchase details:', error);
                    alert("Error fetching purchase details.");
                }
            });
        });

        // Add Payment button click to show modal
        $('#purchase_return_list tbody').on('click', '.add-payment-btn', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const purchaseId = $(this).data('id'); // Get purchase ID directly from data attribute

            // Fetch purchase return details using AJAX
            $.ajax({
                url: `purchase-returns/get-Details/${purchaseId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchase_return) {
                        let purchaseReturn = response.purchase_return;
                        let supplier = purchaseReturn.supplier ?
                            `${purchaseReturn.supplier.first_name} ${purchaseReturn.supplier.last_name}` :
                            'Unknown Supplier';
                        let location = purchaseReturn.location ? purchaseReturn.location
                            .name : 'Unknown Location';
                        let netTotal = purchaseReturn.purchase_return_products.reduce((
                                total, product) => total + parseFloat(product.subtotal),
                            0);

                        // Populate modal fields
                        $('#supplierDetails').text(supplier);
                        $('#referenceNo').text(purchaseReturn.reference_no);
                        $('#locationDetails').text(location);
                        $('#totalAmount').text(netTotal.toFixed(2));
                        $('#payAmount').text(netTotal.toFixed(2));

                        // Open the modal
                        $('#paymentModal').modal('show');
                    } else {
                        alert("No details found for this purchase return.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching purchase return details:', error);
                    alert("Error fetching purchase return details.");
                }
            });
        });

        // Edit Purchase Return Form Handler
        const purchaseReturnId = window.location.pathname.split('/').pop();

        if (purchaseReturnId) {
            // Fetch purchase return data
            $.ajax({
                url: `/api/purchase_return/edit/${purchaseReturnId}`,
                method: 'GET',
                success: function(response) {
                    if (response.purchase_return) {
                        populateForm(response.purchase_return);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching purchase return:', error);
                }
            });

            function populateForm(data) {
                $('#purchase-return-id').val(data.id);
                $('#supplier-id').val(data.supplier_id).change();
                $('#location-id').val(data.location_id).change();
                $('#reference_no').val(data.reference_no);
                $('#return_date').val(formatDate(data.return_date));

                // Populate products
                data.purchase_return_products.forEach(product => {
                    addProductToTable(product);
                });

                // Show the attached document if exists
                if (data.attach_document) {
                    if (data.attach_document.endsWith('.pdf')) {
                        $("#pdfViewer").attr("src", data.attach_document).show();
                    } else {
                        $("#selectedImage").attr("src", data.attach_document).show();
                    }
                }
            }
        }

        function addProductToTable(product) {
        const existingRow = $(`#purchase_return tbody tr[data-id="${product.product_id}"]`);

        if (existingRow.length > 0) {
            const quantityInput = existingRow.find('.purchase-quantity');
            const maxQuantity = parseInt(quantityInput.attr('max')) || 0;
            let newQuantity = parseInt(quantityInput.val()) + parseInt(product.quantity);

            if (newQuantity > maxQuantity) {
                toastr.warning(`Cannot enter more than ${maxQuantity} for this product.`, 'Quantity Limit Exceeded');
                newQuantity = maxQuantity;
            }

            quantityInput.val(newQuantity);
            updateRow(existingRow);
            updateFooter();
        } else {
            const subtotal = product.unit_price * product.quantity;
            const batchOptions = (product.product.batches || []).map(batch => `
                <option value="${batch.id}" data-unit-cost="${batch.unit_cost}" ${batch.id == product.batch_no ? 'selected' : ''}>
                    ${batch.batch_no} - Qty: ${batch.qty} - Unit Price: ${batch.unit_cost} - Exp: ${batch.expiry_date}
                </option>
            `).join('');

            const newRow = `
                <tr data-id="${product.product_id}">
                    <td>${product.product_id}</td>
                    <td>${product.product.product_name || '-'} </td>
                    <td>
                        <select class="form-control batch-select">
                           ${batchOptions}
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control purchase-quantity" value="${product.quantity}" min="1" max="${product.quantity}">
                    </td>
                    <td class="unit-price">${product.unit_price || '0'}</td>
                    <td class="sub-total">${subtotal.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-product">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            const $newRow = $(newRow);
            $('#purchase_return').DataTable().row.add($newRow).draw();

            updateFooter();
            toastr.success('New product added to the table!', 'Success');

            $newRow.find('.purchase-quantity').on('input', function() {
                updateRow($newRow);
                updateFooter();
            });

            $newRow.find('.batch-select').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const unitCost = parseFloat(selectedOption.data('unit-cost')) || 0;
                $newRow.find('.unit-price').text(unitCost.toFixed(2));
                updateRow($newRow);
                updateFooter();
            });
        }

        function updateRow($row) {
            const quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
            const price = parseFloat($row.find('.unit-price').text()) || 0;
            const subTotal = quantity * price;

            $row.find('.sub-total').text(subTotal.toFixed(2));
        }
    }

    function formatDate(inputDate) {
        const dateParts = inputDate.split("-");
        if (dateParts.length === 3) {
            const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            return formattedDate;
        }
        return inputDate;
    }

    function updateFooter() {
        let totalItems = 0;
        let netTotalAmount = 0;

        $('#purchase_return tbody tr').each(function() {
            const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
            const price = parseFloat($(this).find('.unit-price').text()) || 0;
            const subtotal = quantity * price;

            $(this).find('.sub-total').text(subtotal.toFixed(2));

            totalItems += quantity;
            netTotalAmount += subtotal;
        });

        $('#total-items').text(totalItems.toFixed(2));
        $('#net-total-amount').text(netTotalAmount.toFixed(2));
    }



    });
</script>
