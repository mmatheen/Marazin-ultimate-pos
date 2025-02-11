{{-- <script>
    $(document).ready(function () {
        let productToRemove;

        // Fetch and populate locations
        fetchLocations();

        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                success: function (data) {
                    const locationSelect = $("#locationId");
                    data.message.forEach(location => {
                        locationSelect.append(new Option(location.name, location.id));
                    });
                },
                error: function (error) {
                    console.error('Error fetching locations:', error);
                }
            });
        }

        $("#invoiceNo").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/api/search/sales",
                    data: {
                        term: request.term
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function (event, ui) {
                disableProductSearch();
                fetchSaleProducts(ui.item.value);
            }
        });

        function fetchSaleProducts(invoiceNo) {
            $.ajax({
                url: `/api/sales/${invoiceNo}`,
                method: 'GET',
                success: function (data) {
                    const productsTableBody = $("#productsTableBody");
                    productsTableBody.empty();

                    if (!data || !data.products) {
                        alert('Invalid Sale ID');
                        return;
                    }

                    $("#sale-id").val(data.sale_id);

                    data.products.forEach((product, index) => {
                        const row = `
                          <tr data-index="${index}">
                            <td>${index + 1}</td>
                            <td>${product.product.product_name}<br><small class="text-muted">${product.product.sku}</small></td>
                            <td>Rs. ${parseFloat(product.price).toFixed(2)}</td>
                            <td><del>${product.quantity}</del>  ${product.current_quantity} Pc(s)</td>
                            <td>
                              <input type="number" class="form-control return-quantity" name="products[${index}][quantity]" placeholder="Enter qty" max="${product.current_quantity}" data-unit-price="${product.price}" data-product-id="${product.product.id}" data-batch-id="${product.batch_id}" required>
                              <div class="quantity-error">Quantity cannot exceed<br>the available amount.</div>
                            </td>
                            <td class="return-subtotal">Rs. 0.00</td>
                            <td><button type="button" class="btn btn-danger remove-product"><i class="fas fa-trash-alt"></i></button></td>
                          </tr>
                        `;
                        productsTableBody.append(row);
                    });

                    $("#displayInvoiceNo").html(`<strong>Invoice No.:</strong> ${invoiceNo}`);
                    $("#displayDate").html(
                        `<strong>Date:</strong> ${new Date(data.products[0].created_at).toLocaleDateString()}`
                    );

                    fetchCustomerDetails(data.customer_id);
                    setLocationId(data.location_id);

                    $(".return-quantity").on('input', function () {
                        const max = parseInt($(this).attr('max'));
                        let quantity = parseInt($(this).val());
                        const unitPrice = parseFloat($(this).data('unit-price'));
                        const errorDiv = $(this).siblings('.quantity-error');

                        if (quantity > max) {
                            quantity = max;
                            $(this).val(quantity);
                            errorDiv.html('Quantity cannot exceed<br>the available amount.').show();
                        } else {
                            errorDiv.hide();
                        }

                        const returnSubtotal = quantity * unitPrice;
                        $(this).closest('tr').find('.return-subtotal').text(`Rs. ${returnSubtotal.toFixed(2)}`);
                        calculateReturnTotal();
                    });

                    $(".remove-product").on('click', function () {
                        productToRemove = $(this).closest('tr');
                        $('#confirmDeleteModal').modal('show');
                    });
                },
                error: function (error) {
                    console.error('Error fetching sales data:', error);
                }
            });
        }

        function fetchCustomerDetails(customerId) {
            $.ajax({
                url: `/customer-get-all`,
                method: 'GET',
                success: function (data) {
                    const customer = data.message.find(c => c.id == customerId);
                    if (customer) {
                        $("#displayCustomer").html(
                            `<strong>Customer:</strong> ${customer.first_name} ${customer.last_name}`
                        );
                    } else {
                        $("#displayCustomer").html('<strong>Customer:</strong> N/A');
                    }
                },
                error: function (error) {
                    console.error('Error fetching customer data:', error);
                }
            });
        }

        function setLocationId(locationId) {
            const locationSelect = $("#locationId");
            locationSelect.val(locationId);
            if (locationSelect.val() === null) {
                $("#displayLocation").html('<strong>Business Location:</strong> N/A');
            } else {
                $("#displayLocation").html(
                    `<strong>Business Location:</strong> ${locationSelect.find("option:selected").text()}`
                );
            }
        }

        function calculateReturnTotal() {
            let totalSubtotal = 0;
            $('.return-subtotal').each(function () {
                totalSubtotal += parseFloat($(this).text().replace('Rs. ', ''));
            });

            const discountType = $('#discountType').val();
            const discountAmount = parseFloat($('#discountAmount').val()) || 0;
            let totalDiscount = 0;

            if (discountType === 'percentage') {
                totalDiscount = (totalSubtotal * discountAmount) / 100;
            } else {
                totalDiscount = discountAmount;
            }

            const returnTotal = totalSubtotal - totalDiscount;
            $('#totalReturnDiscount').text(`Rs. ${totalDiscount.toFixed(2)}`);
            $('#returnTotalDisplay').text(`Rs. ${returnTotal.toFixed(2)}`);
            $('#returnTotal').val(returnTotal.toFixed(2));
        }

        $('#discountType, #discountAmount').on('change input', function () {
            calculateReturnTotal();
        });

        $("#confirmDeleteButton").on('click', function () {
            productToRemove.remove();
            $('#confirmDeleteModal').modal('hide');
            toastr.success('Product removed successfully.');

            if ($("#productsTableBody tr").length === 0) {
                resetInvoice();
            } else {
                calculateReturnTotal();
            }
        });

        function resetInvoice() {
            $("#displayInvoiceNo").html('<strong>Invoice No.:</strong> PR0001');
            $("#displayDate").html('<strong>Date:</strong> 01/16/2025');
            $("#displayCustomer").html('<strong>Customer:</strong>');
            $("#displayLocation").html('<strong>Business Location:</strong>');
            $("#invoiceNo").val('');
            toastr.info('All products removed. Invoice reset.');
            enableProductSearch();
        }

        function disableProductSearch() {
            $("#productSearch").prop('disabled', true);
            $("#stockColumn").text('Sales Quantity');
        }

        function enableProductSearch() {
            $("#productSearch").prop('disabled', false);
            $("#stockColumn").text('Current Stock');
        }

        function disableInvoiceSearch() {
            $("#invoiceNo").prop('disabled', true);
        }

        function enableInvoiceSearch() {
            $("#invoiceNo").prop('disabled', false);
        }

        // Autocomplete Product Search
        $("#productSearch").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/products/stocks",
                    method: 'GET',
                    success: function (data) {
                        const products = data.data.map(product => ({
                            label: product.product.product_name,
                            value: product.product.id,
                            sku: product.product.sku,
                            retail_price: product.product.retail_price,
                            total_stock: product.total_stock,
                            // batches: product.batches
                        }));
                        response(products);
                    }
                });
            },
            minLength: 2,
            select: function (event, ui) {
                disableInvoiceSearch();
                addProductToTable(ui.item);
            }
        });

        function addProductToTable(product) {
            const newRow = `
              <tr>
                <td></td>
                <td>${product.label} <br> ${product.sku}</td>
                <td>Rs. ${product.retail_price.toFixed(2)}</td>
                <td>${product.total_stock} Pcs</td>
                <td>
                    <input type="number" class="form-control return-quantity" name="products[${product.value}][quantity]" placeholder="Enter qty" max="${product.total_stock}" data-unit-price="${product.retail_price}" data-product-id="${product.value}" required>
                  <div class="quantity-error">Quantity cannot exceed<br>the available amount.</div>
                </td>
                <td class="return-subtotal">Rs. 0.00</td>
                <td><button type="button" class="btn btn-danger remove-product"><i class="fas fa-trash-alt"></i></button></td>
              </tr>
            `;

            $("#productsTableBody").append(newRow);
            updateRowNumbers();
            calculateReturnTotal();

            $(".return-quantity").on('input', function () {
                const max = parseInt($(this).attr('max'));
                let quantity = parseInt($(this).val());
                const unitPrice = parseFloat($(this).data('unit-price'));
                const errorDiv = $(this).siblings('.quantity-error');

                if (quantity > max) {
                    quantity = max;
                    $(this).val(quantity);
                    errorDiv.html('Quantity cannot exceed<br>the available amount.').show();
                } else {
                    errorDiv.hide();
                }

                const returnSubtotal = quantity * unitPrice;
                $(this).closest('tr').find('.return-subtotal').text(`Rs. ${returnSubtotal.toFixed(2)}`);
                calculateReturnTotal();
            });

            $(".remove-product").on('click', function () {
                $(this).closest('tr').remove();
                updateRowNumbers();
                calculateReturnTotal();
                enableInvoiceSearch();
            });
        }

        function updateRowNumbers() {
            $("#productsTableBody tr").each(function (index) {
                $(this).find('td:first').text(index + 1);
            });
        }

        // Initialize jQuery validation
        $("#salesReturnForm").validate({
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorElement: 'div',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                if (element.prop('type') === 'checkbox') {
                    error.insertAfter(element.next('label'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass(errorClass).removeClass(validClass);
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass(errorClass).addClass(validClass);
            },
            rules: {
                return_date: {
                    required: true
                },
                location_id: {
                    required: true
                },
                return_total: {
                    required: true,
                    number: true
                },
                'products[][quantity]': {
                    required: true,
                    number: true,
                    min: 1
                }
            },
            messages: {
                return_date: "Please select a return date",
                location_id: "Please select a location",
                return_total: {
                    required: "Please enter the return total amount",
                    number: "Please enter a valid number"
                },
                'products[][quantity]': {
                    required: "Please enter a return quantity",
                    number: "Please enter a valid number",
                    min: "Quantity must be at least 1"
                }
            },
            submitHandler: function (form) {
            const isValid = validateForm();

            if (isValid) {
                const formData = new FormData(form);
                const jsonData = Object.fromEntries(formData.entries());

                // Adding nested product data
                jsonData.products = [];
                $("#productsTableBody tr").each(function (index, row) {
                    const product = {
                        product_id: $(row).find('.return-quantity').data('productId'),
                        quantity: $(row).find('.return-quantity').val(),
                        original_price: $(row).find('.return-quantity').data('unitPrice'),
                        return_price: $(row).find('.return-quantity').data('unitPrice'),
                        subtotal: parseFloat($(row).find('.return-subtotal').text().replace('Rs. ', '')),
                        batch_id: $(row).find('.return-quantity').data('batchId') || null,
                        price_type: "retail",
                        discount: 0,
                        tax: 0,
                    };
                    jsonData.products.push(product);
                });

                fetch("/api/sales-returns/store", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('input[name=_token]').val()
                    },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(response => {
                    if (response.status === 200) {
                        // Success handling
                        toastr.success(response.message);
                        resetInvoice();
                    } else {
                        toastr.error(response.errors.join("<br>"));
                    }
                })
                .catch(error => console.error('Error storing sales return:', error));
            } else {
                toastr.error("Please fill in all required fields.");
            }
        }

        });

        function validateForm() {
            let isValid = true;
            document.querySelectorAll('#salesReturnForm input, #salesReturnForm select, #salesReturnForm textarea').forEach(element => {
                if (element.required && !element.value) {
                    isValid = false;
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            });

            return isValid;
        }
    });
</script> --}}

<script>
    $(document).ready(function () {
        let productToRemove;

        // Fetch and populate locations
        fetchLocations();

        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                success: function (data) {
                    const locationSelect = $("#locationId");
                    data.message.forEach(location => {
                        locationSelect.append(new Option(location.name, location.id));
                    });
                },
                error: function (error) {
                    console.error('Error fetching locations:', error);
                }
            });
        }

        $("#invoiceNo").autocomplete({
    source: function (request, response) {
        $.ajax({
            url: "/api/search/sales",
            data: {
                term: request.term
            },
            success: function (data) {
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        disableProductSearch();
        fetchSaleProducts(ui.item.value);
    }
});

function fetchSaleProducts(invoiceNo) {
    $.ajax({
        url: `/api/sales/${invoiceNo}`,
        method: 'GET',
        success: function (data) {
            const productsTableBody = $("#productsTableBody");
            productsTableBody.empty();

            if (!data || !data.products) {
                alert('Invalid Sale ID');
                return;
            }

            $("#sale-id").val(data.sale_id);

            data.products.forEach((product, index) => {
                const row = `
                  <tr data-index="${index}">
                    <td>${index + 1}</td>
                    <td>${product.product.product_name}<br><small class="text-muted">${product.product.sku}</small></td>
                    <td>Rs. ${parseFloat(product.price).toFixed(2)}</td>
                    <td><del>${product.quantity}</del>  ${product.current_quantity} Pc(s)</td>
                    <td>
                      <input type="number" class="form-control return-quantity" name="products[${index}][quantity]" placeholder="Enter qty" max="${product.current_quantity}" data-unit-price="${product.price}" data-product-id="${product.product.id}" data-batch-id="${product.batch_id}" required>
                      <div class="quantity-error">Quantity cannot exceed<br>the available amount.</div>
                    </td>
                    <td class="return-subtotal">Rs. 0.00</td>
                    <td><button type="button" class="btn btn-danger remove-product"><i class="fas fa-trash-alt"></i></button></td>
                  </tr>
                `;
                productsTableBody.append(row);
            });

            $("#displayInvoiceNo").html(`<strong>Invoice No.:</strong> ${invoiceNo}`);
            $("#displayDate").html(
                `<strong>Date:</strong> ${new Date(data.products[0].created_at).toLocaleDateString()}`
            );

            fetchCustomerDetails(data.customer_id);
            setLocationId(data.location_id);

            $(".return-quantity").on('input', function () {
                const max = parseInt($(this).attr('max'));
                let quantity = parseInt($(this).val());
                const unitPrice = parseFloat($(this).data('unit-price'));
                const errorDiv = $(this).siblings('.quantity-error');

                if (quantity > max) {
                    quantity = max;
                    $(this).val(quantity);
                    errorDiv.html('Quantity cannot exceed<br>the available amount.').show();
                } else {
                    errorDiv.hide();
                }

                const returnSubtotal = quantity * unitPrice;
                $(this).closest('tr').find('.return-subtotal').text(`Rs. ${returnSubtotal.toFixed(2)}`);
                calculateReturnTotal();
            });

            $(".remove-product").on('click', function () {
                productToRemove = $(this).closest('tr');
                $('#confirmDeleteModal').modal('show');
            });
        },
        error: function (error) {
            console.error('Error fetching sales data:', error);
        }
    });
}

        function fetchCustomerDetails(customerId) {
            $.ajax({
                url: `/customer-get-all`,
                method: 'GET',
                success: function (data) {
                    const customer = data.message.find(c => c.id == customerId);
                    if (customer) {
                        $("#displayCustomer").html(
                            `<strong>Customer:</strong> ${customer.first_name} ${customer.last_name}`
                        );
                    } else {
                        $("#displayCustomer").html('<strong>Customer:</strong> N/A');
                    }
                },
                error: function (error) {
                    console.error('Error fetching customer data:', error);
                }
            });
        }

        function setLocationId(locationId) {
            const locationSelect = $("#locationId");
            locationSelect.val(locationId);
            if (locationSelect.val() === null) {
                $("#displayLocation").html('<strong>Business Location:</strong> N/A');
            } else {
                $("#displayLocation").html(
                    `<strong>Business Location:</strong> ${locationSelect.find("option:selected").text()}`
                );
            }
        }

        function calculateReturnTotal() {
            let totalSubtotal = 0;
            $('.return-subtotal').each(function () {
                totalSubtotal += parseFloat($(this).text().replace('Rs. ', ''));
            });

            const discountType = $('#discountType').val();
            const discountAmount = parseFloat($('#discountAmount').val()) || 0;
            let totalDiscount = 0;

            if (discountType === 'percentage') {
                totalDiscount = (totalSubtotal * discountAmount) / 100;
            } else {
                totalDiscount = discountAmount;
            }

            const returnTotal = totalSubtotal - totalDiscount;
            $('#totalReturnDiscount').text(`Rs. ${totalDiscount.toFixed(2)}`);
            $('#returnTotalDisplay').text(`Rs. ${returnTotal.toFixed(2)}`);
            $('#returnTotal').val(returnTotal.toFixed(2));
        }

        $('#discountType, #discountAmount').on('change input', function () {
            calculateReturnTotal();
        });

        $("#confirmDeleteButton").on('click', function () {
            productToRemove.remove();
            $('#confirmDeleteModal').modal('hide');
            toastr.success('Product removed successfully.');

            if ($("#productsTableBody tr").length === 0) {
                resetInvoice();
            } else {
                calculateReturnTotal();
            }
        });

        function resetInvoice() {
            $("#displayInvoiceNo").html('<strong>Invoice No.:</strong> PR0001');
            $("#displayDate").html('<strong>Date:</strong> 01/16/2025');
            $("#displayCustomer").html('<strong>Customer:</strong>');
            $("#displayLocation").html('<strong>Business Location:</strong>');
            $("#invoiceNo").val('');
            enableProductSearch();
        }

        function disableProductSearch() {
            $("#productSearch").prop('disabled', true);
            $("#stockColumn").text('Sales Quantity');
        }

        function enableProductSearch() {
            $("#productSearch").prop('disabled', false);
            $("#stockColumn").text('Current Stock');
        }

        function disableInvoiceSearch() {
            $("#invoiceNo").prop('disabled', true);
        }

        function enableInvoiceSearch() {
            $("#invoiceNo").prop('disabled', false);
        }

        // Autocomplete Product Search
        $("#productSearch").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/products/stocks",
                    method: 'GET',
                    success: function (data) {
                        const products = data.data.map(product => ({
                            label: product.product.product_name,
                            value: product.product.id,
                            sku: product.product.sku,
                            retail_price: product.product.retail_price,
                            total_stock: product.total_stock,
                            // batches: product.batches
                        }));
                        response(products);
                    }
                });
            },
            minLength: 2,
            select: function (event, ui) {
                disableInvoiceSearch();
                addProductToTable(ui.item);
            }
        });

        function addProductToTable(product) {
            const newRow = `
              <tr>
                <td></td>
                <td>${product.label} <br> ${product.sku}</td>
                <td>Rs. ${product.retail_price.toFixed(2)}</td>
                <td>${product.total_stock} Pcs</td>
                <td>
                    <input type="number" class="form-control return-quantity" name="products[${product.value}][quantity]" placeholder="Enter qty" max="${product.total_stock}" data-unit-price="${product.retail_price}" data-product-id="${product.value}" required>
                  <div class="quantity-error">Quantity cannot exceed<br>the available amount.</div>
                </td>
                <td class="return-subtotal">Rs. 0.00</td>
                <td><button type="button" class="btn btn-danger remove-product"><i class="fas fa-trash-alt"></i></button></td>
              </tr>
            `;

            $("#productsTableBody").append(newRow);
            updateRowNumbers();
            calculateReturnTotal();

            $(".return-quantity").on('input', function () {
                const max = parseInt($(this).attr('max'));
                let quantity = parseInt($(this).val());
                const unitPrice = parseFloat($(this).data('unit-price'));
                const errorDiv = $(this).siblings('.quantity-error');

                if (quantity > max) {
                    quantity = max;
                    $(this).val(quantity);
                    errorDiv.html('Quantity cannot exceed<br>the available amount.').show();
                } else {
                    errorDiv.hide();
                }

                const returnSubtotal = quantity * unitPrice;
                $(this).closest('tr').find('.return-subtotal').text(`Rs. ${returnSubtotal.toFixed(2)}`);
                calculateReturnTotal();
            });

            $(".remove-product").on('click', function () {
                $(this).closest('tr').remove();
                updateRowNumbers();
                calculateReturnTotal();
                enableInvoiceSearch();
            });
        }

        function updateRowNumbers() {
            $("#productsTableBody tr").each(function (index) {
                $(this).find('td:first').text(index + 1);
            });
        }

        // Initialize jQuery validation
        $("#salesReturnForm").validate({
            errorClass: 'is-invalid',
            validClass: 'is-valid',
            errorElement: 'div',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                if (element.prop('type') === 'checkbox') {
                    error.insertAfter(element.next('label'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass(errorClass).removeClass(validClass);
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass(errorClass).addClass(validClass);
            },
            rules: {
                return_date: {
                    required: true
                },
                location_id: {
                    required: true
                },
                return_total: {
                    required: true,
                    number: true
                },
                'products[][quantity]': {
                    required: true,
                    number: true,
                    min: 1
                }
            },
            messages: {
                return_date: "Please select a return date",
                location_id: "Please select a location",
                return_total: {
                    required: "Please enter the return total amount",
                    number: "Please enter a valid number"
                },
                'products[][quantity]': {
                    required: "Please enter a return quantity",
                    number: "Please enter a valid number",
                    min: "Quantity must be at least 1"
                }
            },
            submitHandler: function (form) {
            const isValid = validateForm();

            if (isValid) {
                const formData = new FormData(form);
                const jsonData = Object.fromEntries(formData.entries());

                // Adding nested product data
                jsonData.products = [];
                $("#productsTableBody tr").each(function (index, row) {
                    const product = {
                        product_id: $(row).find('.return-quantity').data('productId'),
                        quantity: $(row).find('.return-quantity').val(),
                        original_price: $(row).find('.return-quantity').data('unitPrice'),
                        return_price: $(row).find('.return-quantity').data('unitPrice'),
                        subtotal: parseFloat($(row).find('.return-subtotal').text().replace('Rs. ', '')),
                        batch_id: $(row).find('.return-quantity').data('batchId') || null,
                        price_type: "retail",
                        discount: 0,
                        tax: 0,
                    };
                    jsonData.products.push(product);
                });

                fetch("/sale-return/store", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('input[name=_token]').val()
                    },
                    body: JSON.stringify(jsonData)
                })
                .then(response => response.json())
                .then(response => {
                    if (response.status === 200) {
                        // Success handling
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.errors.join("<br>"));
                    }
                })
                .catch(error => console.error('Error storing sales return:', error));
            } else {
                toastr.error("Please fill in all required fields.");
            }
        }

        });

        function validateForm() {
            let isValid = true;
            document.querySelectorAll('#salesReturnForm input, #salesReturnForm select, #salesReturnForm textarea').forEach(element => {
                if (element.required && !element.value) {
                    isValid = false;
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            });

            return isValid;
        }
    });
</script>
