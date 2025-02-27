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

        // Fetch and populate customers
        fetchCustomers();

        function fetchCustomers() {
            $.ajax({
                url: '/customer-get-all',
                method: 'GET',
                success: function (data) {
                    const customerSelect = $("#customerId");
                    data.message.forEach(customer => {
                        customerSelect.append(new Option(`${customer.first_name} ${customer.last_name}`, customer.id));
                    });
                },
                error: function (error) {
                    console.error('Error fetching customers:', error);
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
                    $("#customer-id").val(data.customer_id); // Set the customer ID

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
                            errorDiv.html('Quantity cannot exceed<br>the available amount.')
                                .show();
                        } else {
                            errorDiv.hide();
                        }

                        const returnSubtotal = quantity * unitPrice;
                        $(this).closest('tr').find('.return-subtotal').text(
                            `Rs. ${returnSubtotal.toFixed(2)}`);
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
                        $("#customer-id").val(customer.id); // Set the customer ID
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
                    `<strong>Business Location:</strong> ${locationSelect.find("option:selected").text()}`);
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
                const $submitButton = $('.btn[type="submit"]');
                $submitButton.prop('disabled', true).html('Processing...');

                if (isValid) {
                    const formData = new FormData(form);
                    const jsonData = Object.fromEntries(formData.entries());

                    // Adding nested product data
                    jsonData.products = [];
                    $("#productsTableBody tr").each(function (index, row) {
                        const product = {
                            product_id: $(row).find('.return-quantity').data(
                                'productId'),
                            quantity: $(row).find('.return-quantity').val(),
                            original_price: $(row).find('.return-quantity').data(
                                'unitPrice'),
                            return_price: $(row).find('.return-quantity').data(
                                'unitPrice'),
                            subtotal: parseFloat($(row).find('.return-subtotal').text()
                                .replace('Rs. ', '')),
                            batch_id: $(row).find('.return-quantity').data('batchId') ||
                                null,
                            price_type: "retail",
                            discount: 0,
                            tax: 0,
                        };
                        jsonData.products.push(product);
                    });

                    // Using jQuery AJAX
                    $.ajax({
                        url: "/sale-return/store",
                        type: "POST",
                        data: JSON.stringify(jsonData),
                        contentType: "application/json",
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': $('input[name=_token]').val()
                        },
                        success: function (response) {
                            if (response.status === 200) {
                                toastr.success(response.message);
                                setTimeout(() => {
                                    window.location.href =
                                        "/sale-return/list"; // Redirect after success
                                }, 1500); // Delay for toastr message display
                            } else {
                                toastr.error(response.errors.join("<br>"));
                                $submitButton.prop('disabled', false).html(
                                    'Save'); // Re-enable on error
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error storing sales return:', error);
                            toastr.error(
                                "An error occurred while processing the request.");
                            $submitButton.prop('disabled', false).html(
                                'Save'); // Re-enable on error
                        }
                    });
                } else {
                    toastr.error("Please fill in all required fields.");
                    $submitButton.prop('disabled', false).html(
                        'Save'); // Re-enable on validation fail
                }
            }

        });

        function validateForm() {
            let isValid = true;
            document.querySelectorAll(
                '#salesReturnForm input, #salesReturnForm select, #salesReturnForm textarea').forEach(
                element => {
                    if (element.required && !element.value) {
                        isValid = false;
                        element.classList.add('is-invalid');
                    } else {
                        element.classList.remove('is-invalid');
                    }
                });

            return isValid;
        }

        fetchData();

        function fetchData() {
            $.ajax({
                url: '/sale-returns',
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        var salesReturns = response.data;
                        var totalAmount = response.totalAmount;
                        var totalDue = response.totalDue;

                        $('#salesReturnTable tbody').empty();
                        salesReturns.forEach(function(salesReturn) {
                            var parentSaleInvoice = salesReturn.sale ? salesReturn
                                .sale.invoice_no : 'N/A';
                            var customerName = salesReturn.sale && salesReturn.sale
                                .customer ?
                                salesReturn.sale.customer.first_name + ' ' +
                                salesReturn.sale.customer.last_name :
                                (salesReturn.customer ? salesReturn.customer
                                    .first_name + ' ' + salesReturn.customer
                                    .last_name : 'N/A');
                            var locationName = salesReturn.sale ? salesReturn.sale
                                .location.name : (salesReturn.location ? salesReturn
                                    .location.name : 'N/A');

                            $('#salesReturnTable tbody').append(`
                                                <tr>
                                                    <td>${new Date(salesReturn.return_date).toLocaleDateString()}</td>
                                                    <td>${salesReturn.invoice_number}</td>
                                                    <td>${parentSaleInvoice}</td>
                                                    <td>${customerName}</td>
                                                    <td>${locationName}</td>
                                                    <td>${salesReturn.payment_status}</td>
                                                    <td>${salesReturn.return_total}</td>
                                                    <td>${salesReturn.total_due}</td>
                                                    <td>
                                                        <div class="dropdown dropdown-action">
                                                            <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                                            </a>
                                                            <div class="dropdown-menu dropdown-menu-end">
                                                                <a class="dropdown-item view-sale-return" href="#" data-id="${salesReturn.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                                                <a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                                                <a class="dropdown-item edit-link" href="/salesReturn-return/edit/${salesReturn.id}" data-id="${salesReturn.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                                                <a class="dropdown-item add-payment-btn" href="" data-id="${salesReturn.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                                                <a class="dropdown-item view-payment-btn" href="" data-id="${salesReturn.id}" data-bs-toggle="modal" data-bs-target="#viewPaymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;View Payment</a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            `);
                        });

                        $('#salesReturnTable tfoot tr').find('td:eq(2)').text(totalAmount);
                        $('#salesReturnTable tfoot tr').find('td:eq(3)').text(totalDue);
                    }
                },
                error: function(error) {
                    console.log('Error fetching sales returns:', error);
                }
            });
        }

        // Event listener for the view button
        $(document).on('click', '.view-sale-return', function() {
            var saleReturnId = $(this).data('id');
            $.ajax({
                url: '/sale-return-get/' + saleReturnId,
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        var saleReturn = response.data;
                        $('#modalTitle').text('Sale Return Details - ' +
                            saleReturn.invoice_number);
                        var customerDetails = (saleReturn.sale && saleReturn
                                .sale.customer) ?
                            saleReturn.sale.customer.prefix + ' ' + saleReturn
                            .sale.customer.first_name + ' ' + saleReturn.sale
                            .customer.last_name +
                            ', ' + saleReturn.sale.customer.address + ', ' +
                            saleReturn.sale.customer.mobile_no + ', ' +
                            saleReturn.sale.customer.email : 'N/A';
                        var locationDetails = (saleReturn.sale && saleReturn
                                .sale.location) ?
                            saleReturn.sale.location.name + ', ' + saleReturn
                            .sale.location.address + ', ' + saleReturn.sale
                            .location.city + ', ' +
                            saleReturn.sale.location.district + ', ' +
                            saleReturn.sale.location.province + ', ' +
                            saleReturn.sale.location.email + ', ' + saleReturn
                            .sale.location.mobile : 'N/A';
                        var salesDetails = saleReturn.sale ? saleReturn.sale
                            .invoice_no + ' - ' + saleReturn.sale.final_total :
                            'N/A';

                        $('#customerDetails').text(customerDetails);
                        $('#locationDetails').text(locationDetails);
                        $('#salesDetails').text(salesDetails);


                        $('#productsTable tbody').empty();
                        saleReturn.return_products.forEach(function(product,
                            index) {
                            $('#productsTable tbody').append(`
                                                    <tr>
                                                        <td>${index + 1}</td>
                                                        <td>${product.product.product_name}</td>
                                                        <td>${product.product.sku}</td>
                                                        <td>${product.quantity}</td>
                                                        <td>${product.return_price}</td>
                                                        <td>${product.subtotal}</td>
                                                    </tr>
                                                `);
                        });

                        $('#paymentInfoTable tbody').empty();
                        saleReturn.payments.forEach(function(payment) {
                            $('#paymentInfoTable tbody').append(`
                                                    <tr>
                                                        <td>${new Date(payment.payment_date).toLocaleDateString()}</td>
                                                        <td>${payment.reference_no ? payment.reference_no : 'N/A'}</td>
                                                        <td>${payment.amount}</td>
                                                        <td>${payment.payment_method}</td>
                                                        <td>${payment.notes}</td>
                                                    </tr>
                                                `);
                        });

                        var amountDetails = `
                                                <tr>
                                                    <td>Total Amount:</td>
                                                    <td>${saleReturn.return_total}</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Paid:</td>
                                                    <td>${saleReturn.total_paid}</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Due:</td>
                                                    <td>${saleReturn.total_due}</td>
                                                </tr>
                                            `;
                        $('#amountDetailsTable tbody').html(amountDetails);

                        $('#activitiesTable tbody').empty();
                        $('#activitiesTable tbody').append(
                            '<tr><td colspan="4">No records found.</td></tr>'
                        );

                        $('#saleDetailsModal').modal('show');
                    }
                },
                error: function(error) {
                    console.log('Error fetching sale return details:', error);
                }
            });
        });

        // Event listener for the add payment button
        $(document).on('click', '.add-payment-btn', function() {
            var saleReturnId = $(this).data('id');
            $.ajax({
                url: '/sale-return-get/' + saleReturnId,
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        var saleReturn = response.data;
                        $('#saleReturnId').val(saleReturn.id);
                        // Determine the payment type based on stock_type
                        var paymentType = saleReturn.stock_type ===
                            'with_bill' ? 'sale_return_with_bill' :
                            'sale_return_without_bill';
                        $('#payment_type').val(paymentType);
                        $('#customer_id').val(saleReturn.customer_id);
                        $('#reference_no').val(saleReturn.invoice_number);

                        var customerDetails = saleReturn.sale && saleReturn.sale
                            .customer ? saleReturn.sale.customer.prefix + ' ' +
                            saleReturn.sale.customer.first_name + ' ' +
                            saleReturn.sale.customer.last_name + ', ' +
                            saleReturn.sale.customer.address + ', ' + saleReturn
                            .sale.customer.mobile_no + ', ' + saleReturn.sale
                            .customer.email : 'N/A';
                        var locationDetails = saleReturn.sale && saleReturn.sale
                            .location ? saleReturn.sale.location.name + ', ' +
                            saleReturn.sale.location.address + ', ' + saleReturn
                            .sale.location.city + ', ' + saleReturn.sale
                            .location.district + ', ' + saleReturn.sale.location
                            .province + ', ' + saleReturn.sale.location.email +
                            ', ' + saleReturn.sale.location.mobile : 'N/A';

                        $('#paymentCustomerDetail').text(customerDetails);
                        $('#paymentReferenceNo').text(saleReturn
                            .invoice_number);
                        $('#paymentLocationDetails').text(locationDetails);
                        $('#totalAmount').text(saleReturn.return_total);
                        $('#totalPaidAmount').text(saleReturn.total_paid);
                        $('#payAmount').val(saleReturn.total_due);

                        var today = new Date().toISOString().split('T')[0];
                        $('#paidOn').val(today);

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(saleReturn
                                .total_due);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue.toFixed(2));
                            } else {
                                $('#amountError').hide();
                            }
                        });

                        $('#paymentModal').modal('show');
                    }
                },
                error: function(error) {
                    console.log(
                        'Error fetching sale return details for payment:',
                        error);
                }
            });
        });

        $(document).on('click', '.view-payment-btn', function() {
            var saleReturnId = $(this).data('id');
            $.ajax({
                url: '/sale-return-get/' + saleReturnId,
                method: 'GET',
                success: function(response) {
                    if (response.status === 200) {
                        var saleReturn = response.data;

                        var customerDetails = (saleReturn.sale && saleReturn
                                .sale.customer) ?
                            saleReturn.sale.customer.prefix + ' ' + saleReturn
                            .sale.customer.first_name + ' ' + saleReturn.sale
                            .customer.last_name +
                            ', ' + saleReturn.sale.customer.address + ', ' +
                            saleReturn.sale.customer.mobile_no + ', ' +
                            saleReturn.sale.customer.email : 'N/A';
                        var locationDetails = (saleReturn.sale && saleReturn
                                .sale.location) ?
                            saleReturn.sale.location.name + ', ' + saleReturn
                            .sale.location.address + ', ' + saleReturn.sale
                            .location.city + ', ' +
                            saleReturn.sale.location.district + ', ' +
                            saleReturn.sale.location.province + ', ' +
                            saleReturn.sale.location.email + ', ' + saleReturn
                            .sale.location.mobile : 'N/A';

                        $('#viewCustomerDetail').text(customerDetails);
                        $('#viewBusinessDetail').text(locationDetails);
                        $('#viewReferenceNo').text(saleReturn.invoice_number);
                        $('#viewDate').text(new Date(saleReturn.return_date)
                            .toLocaleDateString());
                        $('#viewSaleStatus').text(saleReturn.sale ? saleReturn
                            .sale.status : 'N/A');
                        $('#viewPaymentStatus').text(saleReturn.payment_status);

                        var paymentRows = '';
                        if (saleReturn.payments.length > 0) {
                            paymentRows = saleReturn.payments.map(payment => `
                        <tr>
                            <td>${new Date(payment.payment_date).toLocaleDateString()}</td>
                            <td>${payment.reference_no ? payment.reference_no : 'N/A'}</td>
                            <td>${payment.amount}</td>
                            <td>${payment.payment_method}</td>
                            <td>${payment.notes}</td>
                            <td>${payment.payment_account ? payment.payment_account : 'N/A'}</td>
                            <td>
                                <button class="btn btn-danger btn-sm delete-payment-btn" data-id="${payment.id}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('');
                        } else {
                            paymentRows =
                                '<tr><td colspan="7" class="text-center">No records found</td></tr>';
                        }

                        $('#viewPaymentModal tbody').html(paymentRows);
                        $('#viewPaymentModal').modal('show');
                    }
                },
                error: function(error) {
                    console.log('Error fetching sale return payment details:',
                        error);
                }
            });
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

        // Delete payment
        $(document).on('click', '.delete-payment-btn', function() {
            var paymentId = $(this).data('id');
            if (confirm('Are you sure you want to delete this payment?')) {
                $.ajax({
                    url: '/payments/' + paymentId,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.status === 200) {
                            alert('Payment deleted successfully');
                            location.reload();
                        }
                    },
                    error: function(error) {
                        console.log('Error deleting payment:', error);
                    }
                });
            }
        });

    });
</script>
