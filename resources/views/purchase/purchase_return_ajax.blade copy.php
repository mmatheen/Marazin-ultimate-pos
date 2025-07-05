<script>
$(document).ready(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Initialize jQuery Validation Plugin
    $("#addAndUpdatePurchaseReturnForm").validate({
        rules: {
            supplier_id: { required: true },
            return_date: { required: true }
        },
        messages: {
            supplier_id: { required: "Please select a supplier." },
            return_date: { required: "Please enter a return date." }
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
                        const option = $('<option></option>').val(item.id).text(item.name || (item.first_name + ' ' + item.last_name));
                        if (item.id == selectedId) option.attr('selected', 'selected');
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
    fetchDropdownData('/location-get-all', $('#location-id'), "Select Location", 2);

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
                    alert('Error: No purchases found for this supplier.');
                }
            },
            error: function(xhr) {
                const message = xhr.status === 404 ? 'No purchases found for this supplier.' : 'An error occurred while fetching purchase products.';
                alert(message);
            }
        });
    }

    // Initialize autocomplete functionality
    function initAutocomplete(products) {
        $("#productSearchInput").autocomplete({
            source: function(request, response) {
                const searchTerm = request.term.toLowerCase();
                const filteredProducts = products.filter(product => product.name && product.name.toLowerCase().includes(searchTerm));
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
            }
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>").append(`<div>${item.label}</div>`).appendTo(ul);
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
        } else if (product.product && product.product.unit && product.product.unit.allow_decimal !== undefined) {
            allowDecimal = product.product.unit.allow_decimal == 1;
        }
        if (existingRow.length > 0) {
            const quantityInput = existingRow.find('.purchase-quantity');
            const maxQuantity = parseFloat(quantityInput.attr('max')) || 0;
            let newQuantity = allowDecimal
                ? parseFloat(quantityInput.val()) + 1
                : parseInt(quantityInput.val()) + 1;
            if (newQuantity > maxQuantity) {
                toastr.warning(`Cannot enter more than ${maxQuantity} for this product.`, 'Quantity Limit Exceeded');
                newQuantity = maxQuantity;
            }
            quantityInput.val(allowDecimal ? newQuantity.toFixed(2) : parseInt(newQuantity));
            updateRow(existingRow);
            updateFooter();
        } else {
            const firstPurchase = product.purchases[0];
            const initialQuantity = allowDecimal
                ? parseFloat(firstPurchase.quantity)
                : parseInt(firstPurchase.quantity);
            const subtotal = firstPurchase.price * initialQuantity;
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
                        value="${allowDecimal ? initialQuantity.toFixed(2) : initialQuantity}"
                        min="0.01"
                        ${allowDecimal ? 'step="0.01"' : 'step="1"'}
                        max="${firstPurchase.batch.qty}">
                    </td>
                    <td class="unit-price amount">${firstPurchase.price || '0'}</td>
                    <td class="sub-total amount">${subtotal.toFixed(2)}</td>
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
                    if (isNaN(val)) val = 1;
                    $(this).val(val);
                }
                updateRow($newRow);
                updateFooter();
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
        const url = purchaseReturnId ? `/purchase-return/update/${purchaseReturnId}` : '/purchase-return/store';
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
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
                    setTimeout(() => { window.location.href = "/purchase-return"; }, 1000);
                }
            },
            error: function(xhr, status, error) {
                console.error(purchaseReturnId ? 'Error updating purchase return:' : 'Error adding purchase return:', error);
                toastr.error(`Something went wrong while ${purchaseReturnId ? 'updating' : 'adding'} the purchase return.`, 'Error');
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

    // ... (rest of your code for fetchData, modals, payments, etc. remains unchanged)
    // You can keep the rest of your code as is, since it is mostly correct.
});
</script>
