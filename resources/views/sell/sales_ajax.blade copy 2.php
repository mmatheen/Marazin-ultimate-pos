<script>
    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); // For CSRF token
        fetchData();

        var saleValidationOptions = {
            rules: {
                customer_id: {
                    required: true
                },
                location_id: {
                    required: true
                },
                sales_date: {
                    required: true
                },
                status: {
                    required: true
                },
                invoice_no: {
                    required: true
                },
                discount_type: {
                    required: true
                },
                discount_amount: {
                    required: true,
                    number: true
                },

                payment_method: {
                    required: true
                },
                payment_account: {
                    required: true
                },
                payment_note: {
                    required: false
                }
            },
            messages: {
                customer_id: {
                    required: "Customer is required"
                },
                location_id: {
                    required: "Location is required"
                },
                sales_date: {
                    required: "Sales Date is required"
                },
                status: {
                    required: "Status is required"
                },
                invoice_no: {
                    required: "Invoice No is required"
                },
                discount_type: {
                    required: "Discount Type is required"
                },
                discount_amount: {
                    required: "Discount Amount is required",
                    number: "Please enter a valid number"
                },
                payment_method: {
                    required: "Payment Method is required"
                },
                payment_account: {
                    required: "Payment Account is required"
                },
                payment_note: {
                    required: "Payment Note is required"
                }
            },
            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.is("select")) {
                    error.addClass('text-danger');
                    error.insertAfter(element.closest('div'));
                } else if (element.is(":checkbox")) {
                    error.addClass('text-danger');
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.addClass('text-danger');
                    error.insertAfter(element);
                }
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }
        };

        // Apply validation to forms
        $('#addSalesForm').validate(saleValidationOptions);

        // Show Image Preview
        $(".show-file").on("change", function() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                if (file.type === "application/pdf") {
                    reader.onload = function(e) {
                        $("#pdfViewer").attr("src", e.target.result);
                        $("#pdfViewer").show();
                        $("#selectedImage").hide();
                    };
                } else if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#selectedImage").attr("src", e.target.result);
                        $("#selectedImage").show();
                        $("#pdfViewer").hide();
                    };
                }

                reader.readAsDataURL(file);
            }
        });

        // Initialize DataTable
        const table = $('#salesTable').DataTable();

        // Apply validation to forms
        function fetchData(filters = {}) {
            $.ajax({
                url: '/sales',
                type: 'GET',
                data: filters,
                dataType: 'json',
                success: function(response) {
                    table.clear().draw();
                    if (response.sales && Array.isArray(response.sales)) {
                        var counter = 1;
                        response.sales.forEach(function(item) {
                            let row = $('<tr>');
                            row.append('<td>' + counter + '</td>');
                            row.append('<td>' + item.sales_date + '</td>');
                            row.append('<td>' + item.invoice_no + '</td>');
                            row.append('<td>' + item.customer.first_name + ' ' + item
                                .customer.last_name + '</td>');
                            row.append('<td>' + item.location.name + '</td>');
                            row.append('<td>' + item.status + '</td>');
                            row.append('<td><button type="button" value="' + item.id +
                                '" class="view-details btn btn-outline-info btn-sm me-2"><i class="feather-eye text-info"></i> View</button><button type="button" value="' +
                                item.id +
                                '" class="edit_btn btn btn-outline-info btn-sm me-2"><i class="feather-edit text-info"></i> Edit</button><button type="button" value="' +
                                item.id +
                                '" class="delete_btn btn btn-outline-danger btn-sm"><i class="feather-trash-2 text-danger me-1"></i>Delete</button></td>'
                                );
                            table.row.add(row).draw(false);
                            counter++;
                        });
                    } else {
                        console.error('Sales data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales data:', error);
                }
            });
        }

        function updateCalculations() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                const discountPercent = parseFloat($(this).find('.discount-percent').val()) || 0;

                const subTotal = quantity * price;
                const discountAmount = subTotal * (discountPercent / 100);
                const netCost = subTotal - discountAmount;
                const lineTotal = netCost;

                $(this).find('.sub-total').text(subTotal.toFixed(2));
                $(this).find('.net-cost').text(netCost.toFixed(2));
                $(this).find('.line-total').text(lineTotal.toFixed(2));
                $(this).find('.retail-price').text(price.toFixed(2));

                totalItems += quantity;
                netTotalAmount += lineTotal;
            });

            const discountType = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10' || taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#purchase-total').text(`Purchase Total: $ ${finalTotal.toFixed(2)}`);
            $('#discount-display').text(`(-) $ ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) $ ${taxAmount.toFixed(2)}`);
        }

        // Function to update footer (dummy implementation, replace with actual logic)
        function updateFooter() {
            // Implement the logic to update the footer based on your specific requirements
            console.log("Footer updated");
        }

        // Event listener for remove button click
        $(document).on('click', '.remove-btn', function(event) {
            event.preventDefault(); // Prevent form submission
            var row = $(this).closest('tr');
            $('#confirmRemoveModal').data('row', row).modal('show');
        });

        // Event listener for confirmation modal
        $('#confirmRemoveButton').on('click', function() {
            var row = $('#confirmRemoveModal').data('row');
            removeProduct(row);
            $('#confirmRemoveModal').modal('hide');
        });

        // Function to handle the removal of the product from the DataTable
        function removeProduct(row) {
            var table = $('#addSaleProduct').DataTable();
            var productId = row.data('id');
            var product = allProducts.find(p => p.id === productId);

            // Re-add the removed product back to the allProducts array
            allProducts.push(product);

            table.row(row).remove().draw();

            toastr.success('Product removed successfully!', 'Success');
            updateCalculations();
            updateFooter();
        }

        // Trigger calculations on events
        $(document).on('change keyup',
            '.quantity-input, .discount-percent, .price-input, #discount-amount, #discount-type, #tax-type',
            function() {
                updateCalculations();
            });

        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#addSalesForm')[0].reset(); // Reset the form
            $('.error-message').html(''); // Clear error messages
            $('#addSaleProduct').DataTable().clear().draw(); // Clear the data table
            $('#addSalesForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addSalesForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        // Fetch locations using AJAX
        $.ajax({
            url: '/location-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Location Data:', data); // Log location data
                if (data.status === 200) {
                    const locationSelect = $('#location');
                    locationSelect.html(
                        '<option selected disabled>Please Select Locations</option>');

                    data.message.forEach(function(location) {
                        const option = $('<option></option>').val(location.id).text(location
                            .name);
                        locationSelect.append(option);
                    });
                } else {
                    console.error('Failed to fetch location data:', data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching location data:', error);
            }
        });

        // Fetch customers using AJAX
        $.ajax({
            url: '/customer-get-all',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                console.log('Customer Data:', data); // Log customer data
                if (data.status === 200) {
                    const customerSelect = $('#customer-id');
                    customerSelect.html('<option selected disabled>Customer</option>');

                    data.message.forEach(function(customer) {
                        const option = $('<option></option>')
                            .val(customer.id)
                            .text(
                                `${customer.first_name} ${customer.last_name} (ID: ${customer.id})`
                                )
                            .data('details', customer);
                        customerSelect.append(option);
                    });
                } else {
                    console.error('Failed to fetch customer data:', data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching customer data:', error);
            }
        });

        // Handle customer selection
        $('#customer-id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const customerDetails = selectedOption.data('details');

            if (customerDetails) {
                $('#customer-name').text(`${customerDetails.first_name} ${customerDetails.last_name}`);
                $('#customer-phone').text(customerDetails.mobile_no);
            }
        });

        // Global variable to store combined product data
        let allProducts = [];

        // Fetch data from the single API and combine it
        fetch('/products/stocks')
            .then(response => response.json())
            .then(data => {
                console.log('Stock Data:', data); // Log stock data
                if (data.status === 200 && Array.isArray(data.data)) {
                    allProducts = data.data.map(stock => {
                        const product = stock.product;
                        const totalQuantity = stock.total_stock;

                        // Ensure product object and necessary properties are defined
                        if (product && typeof product.id !== 'undefined' && typeof product
                            .product_name !== 'undefined') {
                            return {
                                id: product.id,
                                name: product.product_name,
                                sku: product.sku,
                                quantity: totalQuantity,
                                price: product.retail_price,
                                product_details: product,
                                batches: stock.batches
                            };
                        } else {
                            console.error('Invalid product data:', product);
                            return null;
                        }
                    }).filter(product => product !== null); // Filter out invalid products

                    initAutocomplete();
                } else {
                    console.error('Unexpected format or status for stocks data:', data);
                }
            })
            .catch(err => console.error('Error fetching product data:', err));

        // Function to initialize autocomplete functionality
        function initAutocomplete() {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = allProducts.filter(product =>
                        (product.name && product.name.toLowerCase().includes(searchTerm)) ||
                        (product.sku && product.sku.toLowerCase().includes(searchTerm))
                    );
                    response(filteredProducts.map(product => ({
                        label: `${product.name} (${product.sku || 'No SKU'})`,
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
                return $("<li>")
                    .append(`<div>${item.label}</div>`)
                    .appendTo(ul);
            };
        }

        function addProductToTable(product, selectedBatchId = null, isEditing = false) {
    // Validate product data
    if (!product || typeof product.id === 'undefined' || typeof product.name === 'undefined') {
        console.error("Invalid product data:", product);
        return;
    }

    // Check if product.batches exists and is an array
    const batches = Array.isArray(product.batches) ? product.batches.map(batch => ({
        batch_id: batch.id,
        batch_price: parseFloat(batch.retail_price) || 0,
        batch_quantity: batch.qty || 0,
        batch_quantity_plus_sold: isEditing ? (batch.qty + (batch.id === selectedBatchId ? product.quantity : 0)) : batch.qty // Adjust batch quantity if editing
    })) : [];

    const totalQuantity = batches.reduce((total, batch) => total + batch.batch_quantity_plus_sold, 0); // Calculate total quantity correctly
    const finalPrice = typeof product.price !== 'undefined' ? parseFloat(product.price) : 0;

    // Generate batch options
    const batchOptions = batches.map(batch => `
        <option value="${batch.batch_id}"
                data-price="${batch.batch_price}"
                data-quantity="${batch.batch_quantity}"
                data-quantity-plus-sold="${batch.batch_quantity_plus_sold}"
                ${selectedBatchId === batch.batch_id ? 'selected' : ''}>
            Batch ${batch.batch_id} - Qty: ${batch.batch_quantity_plus_sold} - Price: ${batch.batch_price}
        </option>
    `).join('');

    const newRow = `
        <tr data-id="${product.id}">
            <td>${product.name || '-'} <br><span style="font-size:12px;">Current stock: ${totalQuantity}</span>
                <select class="form-select batch-dropdown" aria-label="Select Batch">
                    <option value="all" data-price="${finalPrice}" data-quantity="${totalQuantity}">
                        All Batches - Total Qty: ${totalQuantity} - Price: ${finalPrice}
                    </option>
                    ${batchOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control quantity-input" value="${product.quantity || 1}" min="1">
            </td>
            <td>
                <input type="number" class="form-control price-input" value="${finalPrice.toFixed(2)}" min="0">
            </td>
            <td>
                <input type="number" class="form-control discount-percent" value="${product.discount || 0}" min="0" max="100">
            </td>
            <td class="retail-price">${finalPrice.toFixed(2)}</td>
            <td class="subtotal">${(finalPrice * (product.quantity || 1)).toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm remove-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

    const $newRow = $(newRow);
    $('#addSaleProduct').DataTable().row.add($newRow).draw();
    allProducts = allProducts.filter(p => p.id !== product.id);

    // Update footer and set up event listeners
    updateFooter();
    toastr.success('Product added to the table!', 'Success');

    // Event listeners for row updates
    const quantityInput = $newRow.find('.quantity-input');
    const priceInput = $newRow.find('.price-input');
    const batchDropdown = $newRow.find('.batch-dropdown');

    $newRow.find('.remove-btn').on('click', function(event) {
        event.preventDefault(); // Prevent form submission
        var row = $(this).closest('tr');
        $('#confirmRemoveModal').data('row', row).modal('show');
    });

    $newRow.find('.quantity-minus').on('click', () => {
        if (quantityInput.val() > 1) {
            quantityInput.val(quantityInput.val() - 1);
            updateTotals();
        }
    });

    $newRow.find('.quantity-plus').on('click', () => {
        let newQuantity = parseInt(quantityInput.val(), 10) + 1;
        const selectedOption = batchDropdown.find(':selected');
        const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(selectedOption.data('quantity-plus-sold'), 10);

        if (newQuantity > maxQuantity) {
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error(`You cannot add more than ${maxQuantity} units of this product.`, 'Error');
        } else {
            quantityInput.val(newQuantity);
            updateTotals();
        }
    });

    quantityInput.on('input', () => {
        const quantityValue = parseInt(quantityInput.val(), 10);
        const selectedOption = batchDropdown.find(':selected');
        const maxQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(selectedOption.data('quantity-plus-sold'), 10);

        if (quantityValue > maxQuantity) {
            quantityInput.val(maxQuantity);
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error(`You cannot add more than ${maxQuantity} units of this product.`, 'Error');
        }
        updateTotals();
    });

    priceInput.on('input', () => {
        updateTotals();
    });

    batchDropdown.on('change', () => {
        const selectedOption = batchDropdown.find(':selected');
        const batchPrice = parseFloat(selectedOption.data('price')) || 0;
        const batchQuantity = selectedOption.val() === 'all' ? totalQuantity : parseInt(selectedOption.data('quantity-plus-sold'), 10);

        if (quantityInput.val() > batchQuantity) {
            quantityInput.val(batchQuantity);
            toastr.error(`You cannot add more than ${batchQuantity} units from this batch.`, 'Error');
        }
        priceInput.val(batchPrice.toFixed(2));
        const subtotal = parseFloat(quantityInput.val()) * batchPrice;
        $newRow.find('.subtotal').text(subtotal.toFixed(2));
        quantityInput.attr('max', batchQuantity);
        updateTotals();
    });
}
        // Function to update row totals
        function updateRow($row) {
            const batchElement = $row.find('.batch-dropdown option:selected');
            const quantity = parseFloat($row.find('.quantity-input').val()) || 0;
            const price = parseFloat(batchElement.data('price')) || 0;
            const discountPercent = parseFloat($row.find('.discount-percent').val()) || 0;
            const batchQuantity = parseFloat(batchElement.data('quantity')) || 0;

            if (quantity > batchQuantity) {
                alert('Requested quantity exceeds available batch quantity.');
                $row.find('.quantity-input').val(batchQuantity);
                quantity = batchQuantity;
            }

            const subTotal = quantity * price;
            const discountAmount = subTotal * (discountPercent / 100);
            const netCost = subTotal - discountAmount;
            const lineTotal = netCost;

            $row.find('.subtotal').text(subTotal.toFixed(2));
            $row.find('.net-cost').text(netCost.toFixed(2));
            $row.find('.line-total').text(lineTotal.toFixed(2));
            $row.find('.retail-price').text(price.toFixed(2));

            // Update batch quantity if the quantity is updated
            batchElement.data('quantity', batchQuantity - quantity);
        }

        function updateTotals() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#addSaleProduct tbody tr').each(function() {
                totalItems += parseFloat($(this).find('.quantity-input').val()) || 0;
                netTotalAmount += parseFloat($(this).find('.subtotal').text()) || 0;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#discount-net-total-amount').text(netTotalAmount.toFixed(2));
        }

        $('#addSalesForm').on('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            // Convert sales_date to the correct format
            const salesDate = $('#sales_date').val();
            formData.set('sales_date', convertDateFormat(salesDate));

            // Append the products separately since they are not part of the form fields
            $('#addSaleProduct tbody tr').each(function(index) {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat($(this).find('.price-input').val()) || 0;
                const discount = parseFloat($(this).find('.discount-percent').val()) || 0;
                const tax = parseFloat($(this).find('.product-tax').val()) || 0;
                const priceType = $(this).find('.price-type').val() ||
                'retail'; // Assuming there's a hidden or default input for price_type

                // Calculate the subtotal
                const subtotal = (quantity * unitPrice) - discount + tax;

                formData.append(`products[${index}][product_id]`, $(this).data('id'));
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][unit_price]`, unitPrice);
                formData.append(`products[${index}][discount]`, discount);
                formData.append(`products[${index}][tax]`, tax);
                formData.append(`products[${index}][subtotal]`, subtotal);
                formData.append(`products[${index}][batch_id]`, $(this).find('.batch-dropdown')
                    .val());
                formData.append(`products[${index}][price_type]`,
                priceType); // Add price_type to form data
            });

            console.log("Sales data:", formData);

            // Validate the form before submitting
            if (!$('#addSalesForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); // for sound
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            // Determine if we are updating or storing a new sale
            const saleId = $('#sale_id')
        .val(); // Assuming there's a hidden input field with the sale ID
            const url = saleId ? `/api/sales/update/${saleId}` : '/api/sales/store';
            const method = 'POST';
            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                processData: false,
                contentType: false,
                data: formData,
                success: function(response) {
                    console.log('Response:', response); // Log the response
                    if (response.message) {
                        toastr.success(response.message, 'Success');
                        resetFormAndValidation();
                        // Display the invoice if it exists
                        if (response.invoice_html) {
                            $('#invoiceContainer').html(response.invoice_html);
                        }
                    } else {
                        toastr.error('Failed to add sale.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error adding sale:', error);
                    toastr.error('Something went wrong while adding the sale.', 'Error');
                }
            });
        });

        // Function to convert date format from DD-MM-YYYY to YYYY-MM-DD
        function convertDateFormat(dateStr) {
            const [day, month, year] = dateStr.split('-');
            return `${year}-${month}-${day}`;
        }

        // Function to reset form and validation messages
        function resetFormAndValidation() {
            $('#addSalesForm')[0].reset(); // Reset the form
            $('.error-message').html(''); // Clear error messages
            $('#addSaleProduct').DataTable().clear().draw(); // Clear the data table
            $('#addSalesForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addSalesForm').find('.is-validGreen').removeClass('is-validGreen');
        }

        $('#salesTable tbody').on('click', 'button.view-details', function() {
            var saleId = $(this).val();
            $.ajax({
                url: '/sales_details/' + saleId,
                type: 'GET',
                success: function(response) {
                    if (response.salesDetails) {
                        const saleDetails = response.salesDetails;
                        const customer = saleDetails.customer;
                        const location = saleDetails.location;
                        const products = saleDetails.products;

                        // Populate modal fields
                        $('#modalTitle').text('Sale Details - Invoice No: ' + saleDetails
                            .invoice_no);
                        $('#customerDetails').text(customer.first_name + ' ' + customer
                            .last_name);
                        $('#locationDetails').text(location.name);
                        $('#salesDetails').text('Date: ' + saleDetails.sales_date +
                            ', Status: ' + saleDetails.status);

                        // Populate products table
                        const productsTableBody = $('#productsTable tbody');
                        productsTableBody.empty();
                        if (products && Array.isArray(products)) {
                            products.forEach((product, index) => {
                                const productRow = $('<tr>');
                                productRow.append('<td>' + (index + 1) + '</td>');
                                productRow.append('<td>' + product.product
                                    .product_name + '</td>');
                                productRow.append('<td>' + product.product.sku +
                                    '</td>');
                                productRow.append('<td>' + product.quantity +
                                    '</td>');
                                productRow.append('<td>' + product.price + '</td>');
                                productRow.append('<td>' + (product.quantity *
                                    product.price).toFixed(2) + '</td>');
                                productsTableBody.append(productRow);
                            });
                        }

                        // Populate payment info table
                        const paymentInfoTableBody = $('#paymentInfoTable tbody');
                        paymentInfoTableBody.empty();
                        if (saleDetails.payments && Array.isArray(saleDetails.payments)) {
                            saleDetails.payments.forEach((payment) => {
                                const paymentRow = $('<tr>');
                                paymentRow.append('<td>' + payment.date + '</td>');
                                paymentRow.append('<td>' + payment.reference_no +
                                    '</td>');
                                paymentRow.append('<td>' + payment.amount +
                                '</td>');
                                paymentRow.append('<td>' + payment.payment_mode +
                                    '</td>');
                                paymentRow.append('<td>' + payment.payment_note +
                                    '</td>');
                                paymentInfoTableBody.append(paymentRow);
                            });
                        }

                        // Populate amount details table
                        const amountDetailsTableBody = $('#amountDetailsTable tbody');
                        amountDetailsTableBody.empty();
                        amountDetailsTableBody.append('<tr><td>Total Amount</td><td>' +
                            saleDetails.total_amount + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Paid Amount</td><td>' +
                            saleDetails.paid_amount + '</td></tr>');
                        amountDetailsTableBody.append('<tr><td>Due Amount</td><td>' +
                            saleDetails.due_amount + '</td></tr>');

                        // Populate activities table
                        const activitiesTableBody = $('#activitiesTable tbody');
                        activitiesTableBody.empty();
                        if (saleDetails.activities && Array.isArray(saleDetails
                            .activities)) {
                            saleDetails.activities.forEach((activity) => {
                                const activityRow = $('<tr>');
                                activityRow.append('<td>' + activity.date +
                                '</td>');
                                activityRow.append('<td>' + activity.action +
                                    '</td>');
                                activityRow.append('<td>' + activity.by + '</td>');
                                activityRow.append('<td>' + activity.note +
                                '</td>');
                                activitiesTableBody.append(activityRow);
                            });
                        }

                        $('#saleDetailsModal').modal('show');
                    } else {
                        console.error('Sales details data is not in the expected format.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sales details:', error);
                }
            });
        });

        // Function to print the modal content
        window.printModal = function() {
            var printContents = document.getElementById('saleDetailsModal').innerHTML;
            var originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
            // location.reload();  // Reload the page to restore the original content and bindings
        };


        // Extract the sale ID from the URL and fetch data if editing
        const pathSegments = window.location.pathname.split('/');
        const saleId = pathSegments[pathSegments.length - 1] === 'add_sale' ? null : pathSegments[pathSegments
            .length - 1];

        if (saleId) {
            fetchSaleData(saleId);
        }

        // Fetch sale data for editing
        function fetchSaleData(saleId) {
            $.ajax({
                url: `/api/sales/edit/${saleId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        populateForm(response.sales);
                        $('#editSaleModal').modal('show');
                    } else {
                        toastr.error('Failed to fetch sale data.', 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching sale data:', error);
                    toastr.error('Something went wrong while fetching the sale data.', 'Error');
                }
            });
        }

        // Populate form with sale data
        function populateForm(sale) {
            $('#sale_id').val(sale.id);
            $('#location').val(sale.location_id).change();
            $('#customer-id').val(sale.customer_id).change();
            $('#sales_date').val(convertDateFormat(sale.sales_date));
            $('#status').val(sale.status).change();
            $('#invoice_no').val(sale.invoice_no);

            // Clear existing products in the table
            const productTable = $('#addSaleProduct').DataTable();
            productTable.clear().draw();

            // Populate products table
            if (sale.products && Array.isArray(sale.products)) {
                sale.products.forEach(product => {
                    const productData = {
                        id: product.product_id,
                        name: product.product.product_name,
                        sku: product.product.sku,
                        quantity: product.quantity,
                        price: product.price,
                        discount: product.discount,
                        price_type: product.price_type,
                        batch_quantity_plus_sold: product.batch_quantity_plus_sold,
                        batches: product.product.batches || []
                    };
                    addProductToTable(productData, product.batch_id,
                    true); // Pass true to indicate editing
                });
            }

            updateCalculations();
        }
    });
</script>
