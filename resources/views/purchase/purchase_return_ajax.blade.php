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
                if (data.status === true && Array.isArray(data.data)) {
                targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                data.data.forEach(item => {
                    // Only show locations where parent_id is null
                    if (url.includes('location') && item.parent_id !== null) {
                    return; // Skip this item
                    }
                    const option = $('<option></option>').val(item.id).text(item
                    .name || (item.first_name + ' ' + item.last_name));
                    if (item.id == selectedId) option.attr('selected', 'selected');
                    targetSelect.append(option);
                });
                } else if (data.status === 200 && Array.isArray(data.message)) {
                // Fallback for supplier API format
                targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                data.message.forEach(item => {
                    const option = $('<option></option>').val(item.id).text(item
                    .name || (item.first_name + ' ' + item.last_name));
                    if (item.id == selectedId) option.attr('selected', 'selected');
                    targetSelect.append(option);
                });
                } else if (data.status === 404 || (data.message && typeof data.message === 'string')) {
                // Handle 404 or string message responses
                targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                targetSelect.append('<option value="" disabled>No items available</option>');
                console.warn(`No data found: ${data.message}`);
                } else {
                console.error(`Failed to fetch data:`, data);
                targetSelect.html(`<option selected disabled>${placeholder}</option>`);
                targetSelect.append('<option value="" disabled>Error loading data</option>');
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
        fetchDropdownData('/location-get-all', $('#location-id'), "Select Location", 2);

        // Supplier change handler
        $('#supplier-id').change(function() {
            const supplierId = $(this).val();
            const locationId = $('#location-id').val();
            if (supplierId && locationId) {
                fetchPurchaseProductsBySupplierAndLocation(supplierId, locationId);
                $('#productSearchInput').prop('disabled', false);
            } else {
                $('#productSearchInput').prop('disabled', true);
                clearProductTable();
            }
        });

        // Location change handler
        $('#location-id').change(function() {
            const locationId = $(this).val();
            const supplierId = $('#supplier-id').val();
            if (supplierId && locationId) {
                fetchPurchaseProductsBySupplierAndLocation(supplierId, locationId);
                $('#productSearchInput').prop('disabled', false);
            } else {
                $('#productSearchInput').prop('disabled', true);
                clearProductTable();
            }
        });

        // Clear product table when no supplier/location selected
        function clearProductTable() {
            if ($.fn.DataTable.isDataTable('#purchase_return')) {
                $('#purchase_return').DataTable().clear().draw();
            }
            updateFooter();
        }

        // Fetch purchase products based on supplier ID and location ID
        function fetchPurchaseProductsBySupplierAndLocation(supplierId, locationId) {
            $.ajax({
                url: `/purchase-products-by-supplier/${supplierId}`,
                type: 'GET',
                data: {
                    location_id: locationId
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.products) {
                        const allProducts = response.products.map(product => ({
                            id: product.product.id,
                            name: product.product.product_name,
                            unit: product.unit,
                            purchases: product.purchases.map(purchase => ({
                                purchase_id: purchase.purchase_id,
                                batch: purchase.batch,
                                quantity: purchase.quantity,
                                price: purchase.unit_cost
                            }))
                        }));
                        initAutocomplete(allProducts);
                    } else {
                        toastr.error('No purchases found for this supplier at the selected location.', 'Error');
                    }
                },
                error: function(xhr) {
                    const message = xhr.status === 404 ? 'No purchases found for this supplier at the selected location.' :
                        'An error occurred while fetching purchase products.';
                      toastr.error(message);
                }
            });
        }

        // Initialize autocomplete functionality
        function initAutocomplete(products) {
            const $input = $("#productSearchInput");
            
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

                    if (itemToAdd && itemToAdd.product) {
                        $("#productSearchInput").val(itemToAdd.value);
                        addProductToTable(itemToAdd.product);
                        $(this).autocomplete('close');
                    }

                    event.stopImmediatePropagation();
                }
            });

            $input.autocomplete({
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
                    addProductToTable(ui.item.product);
                    return false;
                },
                open: function() {
                    setTimeout(() => {
                        // Auto-focus first item for Enter key selection - Updated with working POS AJAX solution
                        const autocompleteInstance = $input.autocomplete("instance");
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
            }).autocomplete("instance")._renderItem = function(ul, item) {
                return $("<li>").append(`<div>${item.label}</div>`).data('ui-autocomplete-item', item).appendTo(ul);
            };
        }

        // Edit Purchase Return Form Handler
        const purchaseReturnId = window.location.pathname.split('/').pop();

        if (purchaseReturnId) {
            $.ajax({
                url: `/purchase-return/edit/${purchaseReturnId}`,
                method: 'GET',
                success: function(response) {
                    if (response.purchase_return) {
                        populateForm(response.purchase_return);
                        $('#productSearchInput').prop('disabled', true);
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
                data.purchase_return_products.forEach(product => {
                    const currentBatchQty = product.batch.qty + product.quantity;
                    addProductToTable({
                        id: product.product.id,
                        name: product.product.product_name,
                        purchases: [{
                            purchase_id: product.id,
                            batch: {
                                id: product.batch.id,
                                batch_no: product.batch.batch_no,
                                qty: currentBatchQty,
                                expiry_date: product.batch.expiry_date
                            },
                            quantity: product.quantity,
                            price: parseFloat(product.unit_price)
                        }]
                    });
                });
                if (data.attach_document) {
                    if (data.attach_document.endsWith('.pdf')) {
                        $("#pdfViewer").attr("src", data.attach_document).show();
                    } else {
                        $("#selectedImage").attr("src", data.attach_document).show();
                    }
                }
            }
        }

        // Format date as dd-mm-yyyy
        function formatDate(inputDate) {
            const dateParts = inputDate.split("-");
            if (dateParts.length === 3) {
                return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            }
            return inputDate;
        }

        // Add product to table
        function addProductToTable(product) {
            if (!product || !product.purchases || product.purchases.length === 0) {
                toastr.warning('Selected product does not have purchase information.', 'Warning');
                return;
            }
            const existingRow = $(`#purchase_return tbody tr[data-id="${product.id}"]`);
            let allowDecimal = false;
            if (product.unit && product.unit.allow_decimal !== undefined) {
                allowDecimal = product.unit.allow_decimal == 1;
            } else if (product.product && product.product.unit && product.product.unit.allow_decimal !==
                undefined) {
                allowDecimal = product.product.unit.allow_decimal == 1;
            }
            if (existingRow.length > 0) {
                const quantityInput = existingRow.find('.purchase-quantity');
                const maxQuantity = parseFloat(quantityInput.attr('max')) || 0;
                let newQuantity = allowDecimal ?
                    parseFloat(quantityInput.val()) + 1 :
                    parseInt(quantityInput.val()) + 1;
                if (newQuantity > maxQuantity) {
                    toastr.warning(`Cannot enter more than ${maxQuantity} for this product.`,
                        'Quantity Limit Exceeded');
                    newQuantity = maxQuantity;
                }
                quantityInput.val(allowDecimal ? newQuantity.toFixed(2) : parseInt(newQuantity));
                updateRow(existingRow);
                updateFooter();
            } else {
                const firstPurchase = product.purchases[0];
                const initialQuantity = ""; // Start with empty quantity - user must specify return amount
                const subtotal = 0; // Start with 0 since no quantity specified
                const batchOptions = product.purchases.map(purchase => `
                <option value="${purchase.batch.id}" data-unit-cost="${purchase.price}" data-max-qty="${purchase.batch.qty}">
                    ${purchase.batch.batch_no} - Qty: ${purchase.batch.qty} - Unit Price: ${purchase.price} - Exp: ${purchase.batch.expiry_date || '-'}
                </option>
            `).join('');
                const newRow = `
                <tr data-id="${product.id}">
                    <td>${product.id}</td>
                    <td>${product.name || '-'}</td>
                    <td><select class="form-control batch-select">${batchOptions}</select></td>
                    <td>
                        <input type="number" class="form-control purchase-quantity"
                        value=""
                        placeholder="Enter return quantity"
                        min="0.01"
                        ${allowDecimal ? 'step="0.01"' : 'step="1"'}
                        max="${firstPurchase.quantity}">
                    </td>
                    <td class="unit-price amount">${firstPurchase.price || '0'}</td>
                    <td class="sub-total amount">0.00</td>
                    <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
                const $newRow = $(newRow);
                $('#purchase_return').DataTable().row.add($newRow).draw();
                updateFooter();
                toastr.success('New product added to the table!', 'Success');
                $newRow.find('.purchase-quantity').on('input', function() {
                    if (!allowDecimal) {
                        let val = parseInt($(this).val());
                        if (isNaN(val)) val = 0;
                        $(this).val(val);
                    }
                    updateRow($newRow);
                    updateFooter();
                }).on('focus', function() {
                    // Select all text when focused for easy editing
                    $(this).select();
                }).on('keydown', function(e) {
                    // Allow backspace, delete, tab, escape, enter
                    if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                        // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.keyCode === 65 && e.ctrlKey === true) ||
                        (e.keyCode === 67 && e.ctrlKey === true) ||
                        (e.keyCode === 86 && e.ctrlKey === true) ||
                        (e.keyCode === 88 && e.ctrlKey === true)) {
                        return;
                    }
                    // Ensure that it is a number and stop the keypress
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
                        (e.keyCode < 96 || e.keyCode > 105) && 
                        e.keyCode !== 190 && e.keyCode !== 110) {
                        e.preventDefault();
                    }
                });
                $newRow.find('.batch-select').on('change', function() {
                    const selectedOption = $(this).find('option:selected');
                    const unitCost = parseFloat(selectedOption.data('unit-cost')) || 0;
                    const maxQty = parseFloat(selectedOption.data('max-qty')) || 0;
                    $newRow.find('.unit-price').text(unitCost.toFixed(2));
                    $newRow.find('.purchase-quantity').attr('max', maxQty);
                    updateRow($newRow);
                    updateFooter();
                });
            }

            function updateRow($row) {
                let quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
                if (!allowDecimal) {
                    quantity = parseInt(quantity);
                    $row.find('.purchase-quantity').val(quantity);
                }
                const price = parseFloat($row.find('.unit-price').text()) || 0;
                const subTotal = quantity * price;
                $row.find('.sub-total').text(subTotal.toFixed(2));
            }
        }

        // Remove product from table
        function removeProductFromTable(button) {
            const row = $(button).closest('tr');
            const productId = row.data('id');
            $('#purchase_return').DataTable().row(row).remove().draw();
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
            // Format currency properly
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
        }

        // Form submission handler
        $('#addAndUpdatePurchaseReturnForm').on('submit', function(event) {
            event.preventDefault();
            const $submitButton = $('.btn[type="submit"]');
            $submitButton.prop('disabled', true).html('Processing...');
            if (!$('#addAndUpdatePurchaseReturnForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                $submitButton.prop('disabled', false).html('Save');
                return;
            }
            const formData = new FormData(this);
            const purchaseReturnId = $('#purchase-return-id').val();
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
            const returnDate = $('#return_date').val();
            const formattedReturnDate = formatDate(returnDate);
            formData.set('return_date', formattedReturnDate);
            const url = purchaseReturnId ? `/purchase-return/update/${purchaseReturnId}` :
                '/purchase-return/store';
            $.ajax({
                url: url,
                method: 'POST',
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
                        $submitButton.prop('disabled', false).html('Save');
                    } else {
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Purchase Return');
                        resetFormAndValidation();
                        $submitButton.prop('disabled', false).html('Save');
                        setTimeout(() => {
                            window.location.href = "/purchase-return";
                        }, 1000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(purchaseReturnId ? 'Error updating purchase return:' :
                        'Error adding purchase return:', error);
                    toastr.error(
                        `Something went wrong while ${purchaseReturnId ? 'updating' : 'adding'} the purchase return.`,
                        'Error');
                    $submitButton.prop('disabled', false).html('Save');
                }
            });
        });

        function resetFormAndValidation() {
            $('#addAndUpdatePurchaseReturnForm')[0].reset();
            $('#addAndUpdatePurchaseReturnForm').validate().resetForm();
            $('#addAndUpdatePurchaseReturnForm').find('.is-invalid').removeClass('is-invalid');
            $('#purchase_return').DataTable().clear().draw();
            $('#total-items').text('0.00');
            $('#net-total-amount').text('0.00');
            $('#productSearchInput').prop('disabled', true);
            $("#pdfViewer").hide();
            $("#selectedImage").hide();
        }

        // Initialize DataTable for both tables if needed
        if (!$.fn.DataTable.isDataTable('#purchase_return')) {
            $('#purchase_return').DataTable();
        }
        var table = $('#purchase_return_list').DataTable();

        // Fetch data with AJAX
        function fetchData() {
            $.ajax({
                url: '/purchase-returns/get-All', // API endpoint
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
                            let totalPaid = purchase.payments.reduce((total, payment) =>
                                total + parseFloat(payment.amount), 0);
                            let paymentDue = grandTotal - totalPaid;

                            return [
                                purchase.return_date,
                                purchase.reference_no,
                                purchase.id,
                                locationName,
                                supplierName,
                                purchase.payment_status,
                                grandTotal.toFixed(2), // Format to 2 decimal places
                                paymentDue.toFixed(2), // Format to 2 decimal places
                                `<div class="dropdown dropdown-action">
                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item view-btn" href="#" data-id="${purchase.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                <a class="dropdown-item edit-link" href="/purchase-return/edit/${purchase.id}" data-id="${purchase.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                <a class="dropdown-item add-payment-btn" href="" data-id="${purchase.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                <a class="dropdown-item view-payment-btn" href="" data-id="${purchase.id}" data-bs-toggle="modal" data-bs-target="#viewPaymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;View Payment</a>
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
            const purchaseReturnId = $(this).data(
                'id'); // Get purchase return ID directly from data attribute

            $.ajax({
                url: `/purchase-returns/get-Details/${purchaseReturnId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var purchaseReturn = response.purchase_return;
                    $('#modalTitle').text('Purchase Return Details - ' + purchaseReturn
                        .reference_no);
                    $('#supplierDetails').text(purchaseReturn.supplier.first_name + ' ' +
                        purchaseReturn.supplier.last_name);
                    $('#locationDetails').text(purchaseReturn.location.name);
                    $('#purchaseDetails').text('Date: ' + purchaseReturn.return_date +
                        ', Status: ' + purchaseReturn.payment_status);

                    var productsTable = $('#productsTable tbody');
                    productsTable.empty();
                    purchaseReturn.purchase_return_products.forEach(function(product,
                        index) {
                        let row = $('<tr>');
                        row.append('<td>' + (index + 1) + '</td>');
                        row.append('<td>' + product.product.product_name + '</td>');
                        row.append('<td>' + product.product.sku + '</td>');
                        row.append('<td>' + product.quantity + '</td>');
                        row.append('<td>' + product.unit_price + '</td>');
                        row.append('<td>' + product.subtotal + '</td>');
                        productsTable.append(row);
                    });

                    var paymentInfoTable = $('#paymentInfoTable tbody');
                    paymentInfoTable.empty();
                    purchaseReturn.payments.forEach(function(payment) {
                        let row = $('<tr>');
                        row.append('<td>' + payment.payment_date + '</td>');
                        row.append('<td>' + payment.id + '</td>');
                        row.append('<td>' + payment.amount + '</td>');
                        row.append('<td>' + payment.payment_method + '</td>');
                        row.append('<td>' + (payment.notes ? payment.notes : '') +
                            '</td>');
                        paymentInfoTable.append(row);
                    });

                    var amountDetailsTable = $('#amountDetailsTable tbody');
                    amountDetailsTable.empty();
                    amountDetailsTable.append('<tr><td>Total: ' + purchaseReturn
                        .return_total + '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Paid: ' + purchaseReturn
                        .total_paid + '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Due: ' + purchaseReturn
                        .total_due + '</td></tr>');

                    $('#viewPurchaseReturnProductModal').modal('show');
                }
            });
        });

        // Add Payment button click to show modal
        $('#purchase_return_list tbody').on('click', '.add-payment-btn', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const purchaseId = $(this).data('id'); // Get purchase ID directly from data attribute

            // Fetch purchase return details using AJAX
            $.ajax({
                url: `/purchase-returns/get-Details/${purchaseId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchase_return) {
                        populatePaymentModal(response.purchase_return);
                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
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

        // View Payment button click to show modal
        $('#purchase_return_list tbody').on('click', '.view-payment-btn', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const purchaseReturnId = $(this).data('id'); // Get purchase ID directly from data attribute

            // Fetch purchase return details using AJAX
            $.ajax({
                url: `/purchase-returns/get-Details/${purchaseReturnId}`,
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

                        // Populate modal fields
                        $('#viewSupplierDetail').text(supplier);
                        $('#viewBusinessDetail').text(location);
                        $('#viewReferenceNo').text(purchaseReturn.reference_no);
                        $('#viewDate').text(purchaseReturn.return_date);
                        $('#viewPurchaseStatus').text(purchaseReturn.payment_status);
                        $('#viewPaymentStatus').text(purchaseReturn.payment_status);

                        // Populate payments table
                        const paymentsHtml = purchaseReturn.payments.length > 0 ?
                            purchaseReturn.payments.map(payment => `
                            <tr>
                                <td>${payment.payment_date}</td>
                                <td>${payment.reference_no || 'N/A'}</td>
                                <td>${parseFloat(payment.amount).toFixed(2)}</td>
                                <td>${payment.payment_method}</td>
                                <td>${payment.notes || 'N/A'}</td>
                                <td>${payment.payment_account || 'N/A'}</td>
                                <td><button class="btn btn-danger btn-sm delete-payment" data-id="${payment.id}">Delete</button></td>
                            </tr>
                        `).join('') :
                            `<tr><td colspan="7" class="text-center">No records found</td></tr>`;

                        $('#viewPaymentModal tbody').html(paymentsHtml);

                        // Set data attribute for purchase ID
                        $('#viewPaymentModal').data('purchase-return-id', purchaseReturnId);

                        // Show the modal
                        $('#viewPaymentModal').modal('show');
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

        document.getElementById('addPayment').addEventListener('click', function(event) {
            openPaymentModal(event, $('#viewPaymentModal').data('purchase-return-id'));
        });

        // Function to open the Add Payment modal from the View Payment modal
        function openPaymentModal(event, purchaseReturnId) {
            event.preventDefault();

            // Fetch purchase return details using AJAX
            $.ajax({
                url: `/purchase-returns/get-Details/${purchaseReturnId}`,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.purchase_return) {
                        populatePaymentModal(response.purchase_return);
                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
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
        }

        // Function to populate the payment modal fields
        function populatePaymentModal(purchaseReturn) {
            let supplier = purchaseReturn.supplier ?
                `${purchaseReturn.supplier.first_name} ${purchaseReturn.supplier.last_name}` :
                'Unknown Supplier';
            let location = purchaseReturn.location ? purchaseReturn.location.name : 'Unknown Location';
            let netTotal = purchaseReturn.purchase_return_products.reduce((total, product) => total +
                parseFloat(product.subtotal), 0);
            let totalPaid = purchaseReturn.payments.reduce((total, payment) => total + parseFloat(payment
                .amount), 0);
            let totalDue = netTotal - totalPaid;

            // Populate modal fields
            $('#paymentSupplierDetail').text(supplier);
            $('#referenceNo').text(purchaseReturn.reference_no);
            $('#paymentLocationDetails').text(location);
            $('#totalAmount').text(netTotal.toFixed(2));
            $('#totalPaidAmount').text(totalPaid.toFixed(2));
            $('#payAmount').val(totalDue.toFixed(2));

            // Set hidden fields
            $('#purchaseReturnId').val(purchaseReturn.id);
            $('#payment_type').val('purchase_return');
            $('#supplier_id').val(purchaseReturn.supplier_id);
            $('#reference_no').val(purchaseReturn.reference_no);

            // Set today's date as default in the "Paid On" field
            var today = new Date().toISOString().split('T')[0];
            $('#paidOn').val(today);

            // Validate the amount input
            $('#payAmount').off('input').on('input', function() {
                let amount = parseFloat($(this).val());
                if (amount > totalDue) {
                    $('#amountError').text('The given amount exceeds the total due amount.').show();
                    $(this).val(totalDue.toFixed(2));
                } else {
                    $('#amountError').hide();
                }
            });
        }



        // Delete payment button click
        $('#viewPaymentModal tbody').on('click', '.delete-payment', function(event) {
            event.preventDefault(); // Prevent default link behavior
            const paymentId = $(this).data('id'); // Get payment ID directly from data attribute

            // Confirm deletion
            if (confirm('Are you sure you want to delete this payment?')) {
                // Delete payment using AJAX
                $.ajax({
                    url: `/payments/${paymentId}`,
                    type: 'DELETE',
                    success: function(response) {
                        if (response.status === 200) {
                            // Refresh the payment list
                            $('#viewPaymentModal').modal('hide');
                            fetchData();
                            toastr.success(response.message, 'Payment Deleted');
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting payment:', error);
                        toastr.error('Error deleting payment.');
                    }
                });
            }
        });


        // Save payment button click
        $('#savePayment').on('click', function() {
            const paymentMethod = $('#paymentMethod').val();
            let isValid = true;

            // Remove previous error messages
            $('.error').remove();

            // Validate the common fields
            if (!$('#paidOn').val()) {
                isValid = false;
                $('#paidOn').after('<span class="error text-danger">Paid On date is required.</span>');
            }
            if (!$('#payAmount').val()) {
                isValid = false;
                $('#payAmount').after('<span class="error text-danger">Amount is required.</span>');
            }

            // Validate payment method specific fields
            if (paymentMethod === 'card') {
                if (!$('#cardNumber').val()) {
                    isValid = false;
                    $('#cardNumber').after(
                        '<span class="error text-danger">Card Number is required.</span>');
                }
                if (!$('#cardHolderName').val()) {
                    isValid = false;
                    $('#cardHolderName').after(
                        '<span class="error text-danger">Card Holder Name is required.</span>');
                }
                if (!$('#expiryMonth').val()) {
                    isValid = false;
                    $('#expiryMonth').after(
                        '<span class="error text-danger">Expiry Month is required.</span>');
                }
                if (!$('#expiryYear').val()) {
                    isValid = false;
                    $('#expiryYear').after(
                        '<span class="error text-danger">Expiry Year is required.</span>');
                }
                if (!$('#securityCode').val()) {
                    isValid = false;
                    $('#securityCode').after(
                        '<span class="error text-danger">Security Code is required.</span>');
                }
            } else if (paymentMethod === 'cheque') {
                if (!$('#chequeNumber').val()) {
                    isValid = false;
                    $('#chequeNumber').after(
                        '<span class="error text-danger">Cheque Number is required.</span>');
                }
                if (!$('#bankBranch').val()) {
                    isValid = false;
                    $('#bankBranch').after(
                        '<span class="error text-danger">Bank Branch is required.</span>');
                }
                if (!$('#cheque_received_date').val()) {
                    isValid = false;
                    $('#cheque_received_date').after(
                        '<span class="error text-danger">Cheque Received Date is required.</span>');
                }
                if (!$('#cheque_valid_date').val()) {
                    isValid = false;
                    $('#cheque_valid_date').after(
                        '<span class="error text-danger">Cheque Valid Date is required.</span>');
                }
                if (!$('#cheque_given_by').val()) {
                    isValid = false;
                    $('#cheque_given_by').after(
                        '<span class="error text-danger">Cheque Given By is required.</span>');
                }
            }

            if (isValid) {
                const paymentData = $('#paymentForm').serialize();

                $.ajax({
                    url: '/api/payments',
                    type: 'POST',
                    data: paymentData,
                    success: function(response) {
                        $('#paymentModal').modal('hide');
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Payment Added');
                        fetchData();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving payment:', error);
                        toastr.error(response.message);
                    }
                });
            }
        });


    });
</script>
