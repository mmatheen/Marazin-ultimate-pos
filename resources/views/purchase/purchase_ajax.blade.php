<script type="text/javascript">
    $(document).ready(function() {
        // CSRF Token setup
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        fetchProducts();
        fetchLocations();

        // Function to print modal
        function printModal() {
            window.print();
        }

        const validationMessages = {
            supplier_id: {
                required: "Supplier is required"
            },
            purchase_date: {
                required: "Purchase Date is required"
            },
            purchasing_status: {
                required: "Purchase Status is required"
            },
            location_id: {
                required: "Business Location is required"
            },
            duration: {
                required: "Duration is required",
                number: "Please enter a valid number"
            },
            duration_type: {
                required: "Period is required"
            },
            image: {
                extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
                filesize: "Max file size is 5MB"
            }
        };

        // Validation setup
        var purchaseValidationOptions = {
            rules: {
                supplier_id: {
                    required: true
                },
                purchase_date: {
                    required: true
                },
                purchasing_status: {
                    required: true
                },
                location_id: {
                    required: true
                },
                duration: {
                    required: true,
                    number: true
                },
                duration_type: {
                    required: true
                },
                image: {
                    required: false,
                    extension: "jpg|jpeg|png|gif|pdf|csv|zip|doc|docx",
                    filesize: 5242880 // 5MB
                }
            },
            messages: validationMessages,

            errorElement: 'span',
            errorPlacement: function(error, element) {
                if (element.is("select")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest(
                        '.input-group')); // Place error after select container
                } else if (element.is(":checkbox") || element.is(":radio")) {
                    error.addClass('text-danger small');
                    error.insertAfter(element.closest('div').find('label').last());
                } else {
                    error.addClass('text-danger small');
                    error.insertAfter(element); // Default placement for text and other inputs
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
        $('#purchaseForm').validate(purchaseValidationOptions);

        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const locationSelect = $('#services');
                    locationSelect.html('<option selected disabled>Select Location</option>');

                    if (data.status === 200) {
                        data.message.forEach(function(location) {
                            const option = $('<option></option>').val(location.id).text(
                                location.name);
                            locationSelect.append(option);
                        });

                        // Trigger change for the first location by default
                        if (data.message.length > 0) {
                            locationSelect.val(data.message[0].id).trigger('change');
                        }
                    } else {
                        console.error('Failed to fetch location data:', data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching location data:', error);
                }
            });
        }
        let allProducts = []; // Store all product data

        function fetchProducts(locationId) {
            let url = '/products/stocks';

            if (locationId) {
                url += `?location_id=${locationId}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200 && Array.isArray(data.data)) {
                        allProducts = data.data.map(stock => {
                            if (!stock.product) return null;

                            return {
                                id: stock.product.id,
                                name: stock.product.product_name,
                                sku: stock.product.sku || "N/A",
                                quantity: stock.total_stock || 0,
                                price: stock.batches?.[0]?.unit_cost || stock.product
                                    .original_price || 0,
                                wholesale_price: stock.batches?.[0]?.wholesale_price || stock
                                    .product.whole_sale_price || 0,
                                special_price: stock.batches?.[0]?.special_price || stock.product
                                    .special_price || 0,
                                max_retail_price: stock.batches?.[0]?.max_retail_price || stock
                                    .product.max_retail_price || 0,
                                retail_price: stock.batches?.[0]?.retail_price || stock.product
                                    .retail_price || 0,
                                expiry_date: stock.batches?.[0]?.expiry_date || '',
                                batch_no: stock.batches?.[0]?.batch_no || '',
                                stock_alert: stock.product.stock_alert || 0
                            };
                        }).filter(product => product !== null);

                        initAutocomplete(allProducts); // Initialize autocomplete with filtered products
                    } else {
                        console.error("Failed to fetch product data:", data);
                    }
                })
                .catch(error => console.error("Error fetching products:", error));
        }

        $(document).ready(function() {
            fetchLocations(); // Fetch all locations

            $('#services').on('change', function() {
                const selectedLocationId = $(this).val();
                if (selectedLocationId) {
                    fetchProducts(
                    selectedLocationId); // Pass location ID to fetch only relevant products
                }
            });
        });

        function initAutocomplete(products) {
            $("#productSearchInput").autocomplete({
                source: function(request, response) {
                    const searchTerm = request.term.toLowerCase();
                    const filteredProducts = products.filter(
                        product =>
                        product.name.toLowerCase().includes(searchTerm) ||
                        product.sku.toLowerCase().includes(searchTerm)
                    );

                    // Filter out products with stock_alert set to 0
                    const filteredAndAlertedProducts = filteredProducts.filter(product => product
                        .stock_alert !== 0);

                    if (filteredAndAlertedProducts.length === 0) {
                        response([{
                            label: "No products found",
                            value: ""
                        }]);
                    } else {
                        // Limit to showing only one product
                        response(
                            filteredAndAlertedProducts.slice(0, 1).map(product => ({
                                label: `${product.name} (${product.sku})`,
                                value: product.name,
                                product: product,
                            }))
                        );
                    }
                },
                select: function(event, ui) {
                    if (!ui.item.product) {
                        return false;
                    }
                    addProductToTable(ui.item.product);
                    $("#productSearchInput").val("");
                    return false;
                },
            });

            // Ensure the autocomplete widget is initialized before setting _renderItem
            const autocompleteInstance = $("#productSearchInput").data("ui-autocomplete");
            if (autocompleteInstance) {
                autocompleteInstance._renderItem = function(ul, item) {
                    if (!item.product) {
                        return $("<li>")
                            .append(`<div style="color: red;">${item.label}</div>`)
                            .appendTo(ul);
                    }
                    return $("<li>")
                        .append(`<div>${item.label}</div>`)
                        .appendTo(ul);
                };
            }
        }

        function addProductToTable(product, isEditing = false, prices = {}) {
            const table = $("#purchase_product").DataTable();
            let existingRow = null;

            $('#purchase_product tbody tr').each(function() {
                const rowProductId = $(this).data('id');
                if (rowProductId === product.id) {
                    existingRow = $(this);
                    return false;
                }
            });

            if (existingRow && !isEditing) {
                const quantityInput = existingRow.find('.purchase-quantity');
                const newQuantity = parseFloat(quantityInput.val()) + 1;
                quantityInput.val(newQuantity).trigger('input');
            } else {
                const wholesalePrice = parseFloat(prices.wholesale_price || product.wholesale_price) || 0;
                const specialPrice = parseFloat(prices.special_price || product.special_price) || 0;
                const maxRetailPrice = parseFloat(prices.max_retail_price || product.max_retail_price) || 0;
                const retailPrice = parseFloat(prices.retail_price || product.retail_price) || 0;
                const unitCost = parseFloat(prices.unit_cost || product.price) || 0;

                const newRow = `
            <tr data-id="${product.id}">
                <td>${product.id}</td>
                <td>${product.name} <br><small>Stock: ${product.quantity}</small></td>
                <td><input type="number" class="form-control purchase-quantity" value="${prices.quantity || 1}" min="1"></td>
                <td>
                    <input type="number" class="form-control product-price" value="${unitCost.toFixed(2)}" min="0">
                </td>
                <td>
                    <input type="number" class="form-control discount-percent" value="0" min="0" max="100">
                </td>
                <td><input type="number" class="form-control amount unit-cost" value="${unitCost.toFixed(2)}" min="0"></td>
                <td class="sub-total">0</td>
                <td><input type="number" class="form-control special-price" value="${specialPrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control wholesale-price" value="${wholesalePrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control max-retail-price" value="${maxRetailPrice.toFixed(2)}" min="0"></td>
                <td><input type="number" class="form-control profit-margin" value="0" min="0"></td>
                <td><input type="number" class="form-control retail-price" value="${retailPrice.toFixed(2)}" min="0"></td>
                <td><input type="date" class="form-control expiry-date" value="${product.expiry_date}"></td>
                <td><input type="text" class="form-control batch_no" value="${product.batch_no}"></td>
                <td><button class="btn btn-danger btn-sm delete-product"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;

                const $newRow = $(newRow);
                table.row.add($newRow).draw();
                updateRow($newRow);
                updateFooter();

                $newRow.find(
                    ".purchase-quantity, .discount-percent, .product-price, .unit-cost, .profit-margin"
                ).on("input", function() {
                    updateRow($newRow);
                    updateFooter();
                });

                $newRow.find(".delete-product").on("click", function() {
                    table.row($newRow).remove().draw();
                    updateFooter();
                });
            }
        }

        function updateRow($row) {
            const quantity = parseFloat($row.find(".purchase-quantity").val()) || 0;
            const price = parseFloat($row.find(".product-price").val()) || 0;
            const discountPercent = parseFloat($row.find(".discount-percent").val()) || 0;
            const profitMargin = parseFloat($row.find(".profit-margin").val()) || 0;

            const discountedPrice = price - (price * discountPercent) / 100;
            const unitCost = discountedPrice;
            const retailPrice = unitCost + (unitCost * profitMargin) / 100;
            const subTotal = unitCost * quantity;

            $row.find(".unit-cost").val(unitCost.toFixed(2));
            $row.find(".retail-price").val(retailPrice.toFixed(2));
            $row.find(".sub-total").text(subTotal.toFixed(2));
        }

        function updateFooter() {
            let totalItems = 0;
            let netTotalAmount = 0;

            $('#purchase_product tbody tr').each(function() {
                const quantity = parseFloat($(this).find('.purchase-quantity').val()) || 0;
                const subTotal = parseFloat($(this).find('.sub-total').text()) || 0;

                totalItems += quantity;
                netTotalAmount += subTotal;
            });

            $('#total-items').text(totalItems.toFixed(2));
            $('#net-total-amount').text(netTotalAmount.toFixed(2));
            $('#total').val(netTotalAmount.toFixed(2));

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

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            $('#purchase-total').text(`Purchase Total: Rs ${finalTotal.toFixed(2)}`);
            $('#final-total').val(finalTotal.toFixed(2));
            $('#discount-display').text(`(-) Rs ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs ${taxAmount.toFixed(2)}`);

            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const paymentDue = finalTotal - paidAmount;
            $('.payment-due').text(`Rs ${paymentDue.toFixed(2)}`);
        }

        $('#discount-type, #discount-amount, #tax-type, #paid-amount').on('change input', updateFooter);


        function formatDate(dateStr) {
            const [year, month, day] = dateStr.split('-');
            return `${day}-${month}-${year}`;
        }

        const pathSegments = window.location.pathname.split('/');
        const purchaseId = pathSegments[pathSegments.length - 1] === 'add-purchase' ? null : pathSegments[
            pathSegments.length - 1];

        if (purchaseId) {
            fetchPurchaseData(purchaseId);
            $("#purchaseButton").text("Update Purchase");
        }

        function fetchPurchaseData(purchaseId) {
            $.ajax({
                url: `/purchase/edit/${purchaseId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        populateForm(response.purchase);
                    } else {
                        toastr.error('Failed to fetch purchase data.', 'Error');
                    }
                },
                error: handleAjaxError('fetching purchase data')
            });
        }

        function populateForm(purchase) {
            $('#supplier-id').val(purchase.supplier_id).trigger(
                'change'); // Trigger change event after setting supplier ID
            $('#reference-no').val(purchase.reference_no);
            $('#purchase-date').val(formatDate(purchase.purchase_date));
            $('#purchase-status').val(purchase.purchasing_status).change();
            $('#services').val(purchase.location_id).change();
            $('#duration').val(purchase.pay_term);
            $('#period').val(purchase.pay_term_type).change();
            $('#discount-type').val(purchase.discount_type).change();
            $('#discount-amount').val(purchase.discount_amount);
            $('#payment-status').val(purchase.payment_status).change();

            if (purchase.payments && purchase.payments.length > 0) {
                const latestPayment = purchase.payments[purchase.payments.length - 1];
                $('#payment-date').val(formatDate(latestPayment.payment_date));
                $('#payment-account').val(latestPayment.payment_account);
                $('#payment-method').val(latestPayment.payment_method);
                $('#payment-note').val(latestPayment.notes);
            }

            const productTable = $('#purchase_product').DataTable();
            productTable.clear().draw();

            if (purchase.purchase_products && Array.isArray(purchase.purchase_products)) {
                purchase.purchase_products.forEach(product => {
                    const productData = {
                        id: product.product_id,
                        name: product.product.product_name,
                        sku: product.product.sku,
                        quantity: product.quantity,
                        price: product.unit_cost,
                        wholesale_price: product.wholesale_price,
                        special_price: product.special_price,
                        max_retail_price: product.max_retail_price,
                        expiry_date: product.batch ? product.batch.expiry_date : '',
                        batch_no: product.batch ? product.batch.batch_no : ''
                    };

                    const batchPrices = {
                        price: product.batch ? product.batch.retail_price : product.price,
                        unit_cost: product.batch ? product.batch.unit_cost : product.unit_cost,
                        wholesale_price: product.batch ? product.batch.wholesale_price : product
                            .wholesale_price,
                        special_price: product.batch ? product.batch.special_price : product
                            .special_price,
                        max_retail_price: product.batch ? product.batch.max_retail_price : product
                            .max_retail_price,
                        quantity: product.quantity
                    };

                    addProductToTable(productData, true, batchPrices);
                });
            }

            updateFooter();
        }

        $('#purchaseButton').on('click', function(event) {
            event.preventDefault();
            $('#purchaseButton').prop('disabled', true).html('Processing...');

            if (!$('#purchaseForm').valid()) {
                document.getElementsByClassName('errorSound')[0].play();
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            const productTableRows = document.querySelectorAll('#purchase_product tbody tr');
            console.log('Product table rows count:', productTableRows.length); // Debug log
            if (productTableRows.length === 0) {
                toastr.error('Please add at least one product.', 'Warning');
                document.getElementsByClassName('errorSound')[0].play();
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            const formData = new FormData($('#purchaseForm')[0]);

            if (purchaseId && isNaN(purchaseId)) {
                toastr.error('Invalid purchase ID.', 'Error');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            const purchaseDate = formatDate($('#purchase-date').val());
            const paidDate = formatDate($('#payment-date').val());

            if (!purchaseDate || !paidDate) {
                toastr.error('Invalid date format. Please use YYYY-MM-DD.', 'Error');
                $('#purchaseButton').prop('disabled', false).html('Save Purchase');
                return;
            }

            formData.append('purchase_date', purchaseDate);
            // formData.append('paid_date', paidDate);
            formData.append('final_total', $('#final-total').val());

            productTableRows.forEach((row, index) => {
                const productId = $(row).data('id');
                const quantity = $(row).find('.purchase-quantity').val() || 0;
                const unitCost = $(row).find('.unit-cost').val() || 0;
                const wholesalePrice = $(row).find('.wholesale-price').val() || 0;
                const specialPrice = $(row).find('.special-price').val() || 0;
                const retailPrice = $(row).find('.retail-price').val() || 0;
                const maxRetailPrice = $(row).find('.max-retail-price').val() || 0;
                const price = $(row).find('.product-price').val() || 0;
                const total = $(row).find('.sub-total').text() || 0;
                const batchNo = $(row).find('.batch_no').val() || '';
                const expiryDate = $(row).find('.expiry-date').val();

                formData.append(`products[${index}][product_id]`, productId);
                formData.append(`products[${index}][quantity]`, quantity);
                formData.append(`products[${index}][unit_cost]`, unitCost);
                formData.append(`products[${index}][wholesale_price]`, wholesalePrice);
                formData.append(`products[${index}][special_price]`, specialPrice);
                formData.append(`products[${index}][retail_price]`, retailPrice);
                formData.append(`products[${index}][max_retail_price]`, maxRetailPrice);
                formData.append(`products[${index}][price]`, price);
                formData.append(`products[${index}][total]`, total);
                formData.append(`products[${index}][batch_no]`, batchNo);
                formData.append(`products[${index}][expiry_date]`, expiryDate);
            });

            const url = purchaseId ? `/purchases/update/${purchaseId}` : '/purchases/store';
            const method = 'POST';

            $.ajax({
                url: url,
                type: method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: handleAjaxSuccess,
                error: handleAjaxError('saving purchase')
            });
        });

        function handleAjaxSuccess(response) {
            if (response.status === 400) {
                document.getElementsByClassName('errorSound')[0].play();
                $.each(response.errors, function(key, err_value) {
                    $('#' + key + '_error').html(err_value);
                });
            } else {
                document.getElementsByClassName('successSound')[0].play();
                toastr.success(response.message, purchaseId ? 'Purchase Updated' : 'Purchase Added');
                if (!purchaseId) {
                    resetFormAndValidation();
                }
                setTimeout(function() {
                    window.location.href = "list-purchase";
                }, 300);

            }
            $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
        }

        function handleAjaxError(action) {
            return function(xhr, status, error) {
                const errorMessage =
                    `Something went wrong while ${action}. Status: ${xhr.status} - ${xhr.statusText} Response: ${xhr.responseText}`;
                // toastr.error(errorMessage, 'Error');
                console.error('Error:', errorMessage, 'Response:', xhr.responseText);
                $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' :
                    'Save Purchase');
            };
        }

        function resetFormAndValidation() {
            $('#purchaseForm')[0].reset();
            $('#purchaseForm').validate().resetForm();
            const table = $("#purchase_product").DataTable();
            table.clear().draw();
            updateFooter();
        }

        // Initialize DataTable
        $(document).ready(function() {
            $('#purchase_product').DataTable();
            fetchProducts();
        });


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

        // Event listener for dynamic table changes
        $('#purchase_product').on('input', '.purchase-quantity, .discount-percent, .product-tax', function() {
            const row = $(this).closest('tr');
            updateRow(row);
            updateFooter();
        });

        // Event listener for deleting a row
        $('#purchase_product').on('click', '.delete-product', function() {
            $(this).closest('tr').remove();
            updateFooter();
            toastr.info('Product removed from the table.', 'Info');
        });

        // Fetch suppliers using AJAX
        $('#supplier-id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const supplierDetails = selectedOption.data('details');

            if (supplierDetails) {
                const openingBalance = parseFloat(supplierDetails.opening_balance || 0);
                $('#advance-payment').val(openingBalance.toFixed(2));
                const paymentDue = calculatePaymentDue(openingBalance);
                $('.payment-due').text(paymentDue.toFixed(2));
                $('#supplier-name').text(`${supplierDetails.first_name} ${supplierDetails.last_name}`);
                $('#supplier-phone').text(supplierDetails.mobile_no);
            }
        });

        // Example function to calculate payment due
        function calculatePaymentDue(openingBalance) {
            const totalPurchase = parseFloat($('#purchase-total').val() || 0);
            return totalPurchase - openingBalance;
        }

        function viewPurchase(purchaseId) {
            $.ajax({
                url: '/get-all-purchases-product/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var purchase = response.purchase;
                    $('#modalTitle').text('Purchase Details - ' + purchase.reference_no);
                    $('#supplierDetails').text(purchase.supplier.first_name + ' ' + purchase
                        .supplier.last_name);
                    $('#locationDetails').text(purchase.location.name);
                    $('#purchaseDetails').text('Date: ' + purchase.purchase_date + ', Status: ' +
                        purchase.purchasing_status);

                    var productsTable = $('#productsTable tbody');
                    productsTable.empty();
                    purchase.purchase_products.forEach(function(product, index) {
                        let row = $('<tr>');
                        row.append('<td>' + (index + 1) + '</td>');
                        row.append('<td>' + product.product.product_name + '</td>');
                        row.append('<td>' + product.product.sku + '</td>');
                        row.append('<td>' + product.quantity + '</td>');
                        row.append('<td>' + product.price + '</td>');
                        row.append('<td>' + product.total + '</td>');
                        productsTable.append(row);
                    });

                    var paymentInfoTable = $('#paymentInfoTable tbody');
                    paymentInfoTable.empty();
                    purchase.payments.forEach(function(payment) {
                        let row = $('<tr>');
                        row.append('<td>' + payment.payment_date + '</td>');
                        row.append('<td>' + payment.id + '</td>');
                        row.append('<td>' + payment.amount + '</td>');
                        row.append('<td>' + payment.payment_method + '</td>');
                        row.append('<td>' + payment.notes + '</td>');
                        paymentInfoTable.append(row);
                    });

                    var amountDetailsTable = $('#amountDetailsTable tbody');
                    amountDetailsTable.empty();
                    amountDetailsTable.append('<tr><td>Total: ' + purchase.total + '</td></tr>');
                    amountDetailsTable.append('<tr><td>Discount: ' + purchase.discount_amount +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Final Total: ' + purchase.final_total +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Paid: ' + purchase.total_paid +
                        '</td></tr>');
                    amountDetailsTable.append('<tr><td>Total Due: ' + purchase.total_due +
                        '</td></tr>');

                    $('#viewPurchaseProductModal').modal('show');
                }
            });
        }



        $(document).ready(function() {
            fetchPurchases();

            // Fetch and display data
            function fetchPurchases() {
                $.ajax({
                    url: '/get-all-purchases',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        var table = $('#purchase-list').DataTable();
                        table.clear().draw();
                        if (response.purchases && response.purchases.length > 0) {
                            response.purchases.forEach(function(item) {
                                let row = $('<tr data-id="' + item.id + '">');
                                row.append(
                                    '<td><a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">' +
                                    '<button type="button" class="btn btn-outline-info">Actions</button>' +
                                    '</a>' +
                                    '<div class="dropdown-menu dropdown-menu-end">' +
                                    '<a class="dropdown-item" href="#" onclick="viewPurchase(' +
                                    item.id +
                                    ')"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>' +
                                    '<a class="dropdown-item" href="/purchase/edit/' +
                                    item.id +
                                    '"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-barcode"></i>&nbsp;Labels</a>' +
                                    '<a class="dropdown-item" href="#" onclick="openPaymentModal(event, ' +
                                    item.id +
                                    ')"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;Add payments</a>' +
                                    '<a class="dropdown-item" href="#" onclick="openViewPaymentModal(event, ' +
                                    item.id +
                                    ')"><i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;View payments</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Purchase Return</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Update Status</a>' +
                                    '<a class="dropdown-item" href="edit-invoice.html"><i class="fas fa-envelope"></i>&nbsp;&nbsp;Item Received Notification</a>' +
                                    '</div></td>'
                                );
                                row.append('<td>' + item.purchase_date + '</td>');
                                row.append('<td>' + item.reference_no + '</td>');
                                row.append('<td>' + (item.location?.name ||
                                    'Unknown') + '</td>');
                                row.append('<td>' + (item.supplier?.first_name ||
                                    'Unknown') + ' ' + (item.supplier
                                    ?.last_name || '') + '</td>');
                                row.append('<td>' + item.purchasing_status +
                                    '</td>');
                                let paymentStatusBadge = '';
                                if (item.payment_status === 'Due') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-danger">Due</span>';
                                } else if (item.payment_status === 'Partial') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-warning">Partial</span>';
                                } else if (item.payment_status === 'Paid') {
                                    paymentStatusBadge =
                                        '<span class="badge bg-success">Paid</span>';
                                } else {
                                    paymentStatusBadge =
                                        '<span class="badge bg-secondary">' + item
                                        .payment_status + '</span>';
                                }
                                row.append('<td>' + paymentStatusBadge + '</td>');
                                row.append('<td>' + item.final_total + '</td>');
                                row.append('<td>' + item.total_due + '</td>');
                                // Show user name based on user object
                                row.append('<td>' + (item.user?.user_name || item
                                        .user?.user_name || 'Unknown') +
                                    '</td>');
                                table.row.add(row).draw(false);
                            });
                        } else {
                            console.error(
                                "No purchases found or response.purchases is undefined."
                            );
                        }

                        // Initialize or reinitialize the DataTable after adding rows
                        if ($.fn.dataTable.isDataTable('#purchase-list')) {
                            $('#purchase-list').DataTable().destroy();
                        }
                        $('#purchase-list').DataTable();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                    }
                });
            }
            // Show the modal when the button is clicked
            $('#bulkPaymentBtn').click(function() {
                $('#bulkPaymentModal').modal('show');
            });

            // Fetch suppliers and populate the dropdown
            $.ajax({
                url: '/supplier-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    var supplierSelect = $('#supplierSelect');
                    supplierSelect.empty();
                    supplierSelect.append(
                        '<option value="" selected disabled>Select Supplier</option>');
                    if (response.message && response.message.length > 0) {
                        response.message.forEach(function(supplier) {
                            supplierSelect.append(
                                '<option value="' + supplier.id +
                                '" data-opening-balance="' + supplier
                                .opening_balance + '">' +
                                supplier.first_name + ' ' + supplier.last_name +
                                '</option>'
                            );
                        });
                    } else {
                        console.error(
                            "No suppliers found or response.message is undefined.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", status, error);
                }
            });

            let originalOpeningBalance = 0; // Store the actual supplier opening balance

            $('#supplierSelect').change(function() {
                var supplierId = $(this).val();
                originalOpeningBalance = parseFloat($(this).find(':selected').data(
                    'opening-balance')) || 0;

                $('#openingBalance').text(originalOpeningBalance.toFixed(
                2)); // Display initial balance

                $.ajax({
                    url: '/get-all-purchases',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        supplier_id: supplierId
                    }, // Ensure supplier_id is sent
                    success: function(response) {
                        var purchaseTable = $('#purchaseTable').DataTable();
                        purchaseTable
                    .clear(); // Clear the table before adding new data
                        var totalPurchaseAmount = 0,
                            totalPaidAmount = 0,
                            totalDueAmount = 0;

                        if (response.purchases && response.purchases.length > 0) {
                            response.purchases.forEach(function(purchase) {
                                if (purchase.supplier_id ==
                                    supplierId) { // Filter by supplier ID
                                    var finalTotal = parseFloat(purchase
                                        .final_total) || 0;
                                    var totalPaid = parseFloat(purchase
                                        .total_paid) || 0;
                                    var totalDue = parseFloat(purchase
                                        .total_due) || 0;

                                    if (totalDue > 0) {
                                        totalPurchaseAmount += finalTotal;
                                        totalPaidAmount += totalPaid;
                                        totalDueAmount += totalDue;

                                        purchaseTable.row.add([
                                            purchase.id + " (" +
                                            purchase.reference_no +
                                            ")",
                                            finalTotal.toFixed(2),
                                            totalPaid.toFixed(2),
                                            totalDue.toFixed(2),
                                            '<input type="number" class="form-control purchase-amount" data-purchase-id="' +
                                            purchase.id + '">'
                                        ]).draw();
                                    }
                                }
                            });
                        }

                        $('#totalPurchaseAmount').text(totalPurchaseAmount.toFixed(
                            2));
                        $('#totalPaidAmount').text(totalPaidAmount.toFixed(2));
                        $('#totalDueAmount').text(totalDueAmount.toFixed(2));
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                    }
                });
            });

            $('#globalPaymentAmount').on('input', function() {
                var globalAmount = parseFloat($(this).val()) || 0;
                var supplierOpeningBalance =
                originalOpeningBalance; // Always use original balance
                var totalDueAmount = parseFloat($('#totalDueAmount').text()) || 0;
                var remainingAmount = globalAmount;

                // Validate global amount
                if (globalAmount > (supplierOpeningBalance + totalDueAmount)) {
                    $(this).addClass('is-invalid').after(
                        '<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>'
                        );
                    return;
                } else {
                    $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
                }

                // Deduct from opening balance first
                let newOpeningBalance = supplierOpeningBalance;
                if (newOpeningBalance > 0) {
                    if (remainingAmount <= newOpeningBalance) {
                        newOpeningBalance -= remainingAmount;
                        remainingAmount = 0;
                    } else {
                        remainingAmount -= newOpeningBalance;
                        newOpeningBalance = 0;
                    }
                }
                $('#openingBalance').text(newOpeningBalance.toFixed(2));

                // Apply the remaining amount to the purchases dynamically
                $('.purchase-amount').each(function() {
                    var purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)')
                        .text());
                    if (remainingAmount > 0) {
                        var paymentAmount = Math.min(remainingAmount, purchaseDue);
                        $(this).val(paymentAmount);
                        remainingAmount -= paymentAmount;
                    } else {
                        $(this).val(0);
                    }
                });
            });

            // Validate individual payment amounts
            $(document).on('input', '.purchase-amount', function() {
                var purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
                var paymentAmount = parseFloat($(this).val());
                if (paymentAmount > purchaseDue) {
                    $(this).addClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                    $(this).after(
                        '<span class="invalid-feedback d-block">Amount exceeds total due.</span>'
                        );
                } else {
                    $(this).removeClass('is-invalid');
                    $(this).next('.invalid-feedback').remove();
                }
            });

            // Function to update the opening balance
            function updateOpeningBalance() {
                var globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
                var supplierOpeningBalance = parseFloat($('#supplierSelect').find(':selected').data(
                    'opening-balance')) || 0;
                var totalPayment = 0;

                // Calculate the total payment from individual amounts
                $('.purchase-amount').each(function() {
                    totalPayment += parseFloat($(this).val()) || 0;
                });

                var remainingAmount = globalAmount - totalPayment;

                // Adjust the opening balance based on the remaining amount
                if (remainingAmount >= 0) {
                    $('#openingBalance').text((supplierOpeningBalance - remainingAmount).toFixed(2));
                } else {
                    $('#openingBalance').text("0.00");
                }
            }

            // Handle global payment amount input
            $('#globalPaymentAmount').change(function() {
                updateOpeningBalance();
            });

            // Initialize DataTable
            $(document).ready(function() {
                $('#purchaseTable').DataTable();
            });

            // Handle payment submission
            $('#submitBulkPayment').click(function() {
                var supplierId = $('#supplierSelect').val();
                var paymentMethod = $('#paymentMethod').val();
                var paymentDate = $('#paidOn').val();
                var globalPaymentAmount = $('#globalPaymentAmount').val();
                var purchasePayments = [];

                $('.purchase-amount').each(function() {
                    var purchaseId = $(this).data('purchase-id');
                    var paymentAmount = parseFloat($(this).val());
                    if (paymentAmount > 0) {
                        purchasePayments.push({
                            reference_id: purchaseId,
                            amount: paymentAmount
                        });
                    }
                });

                var paymentData = {
                    entity_type: 'supplier',
                    entity_id: supplierId,
                    payment_method: paymentMethod,
                    payment_date: paymentDate,
                    global_amount: globalPaymentAmount,
                    payments: purchasePayments
                };

                $.ajax({
                    url: '/api/submit-bulk-payment',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(paymentData),
                    success: function(response) {
                        toastr.success(response.message, 'Payment Submitted');
                        $('#bulkPaymentModal').modal('hide');
                        $('#bulkPaymentForm')[0].reset(); // Reset the form
                        fetchPurchases();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX error: ", status, error);
                        alert('Failed to submit payment.');
                    }
                });
            });
            // Define the openPaymentModal function
            window.openPaymentModal = function(event, purchaseId) {
                event.preventDefault();
                // Fetch purchase details and populate the modal
                $.ajax({
                    url: '/get-purchase/' + purchaseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#purchaseId').val(response.id);
                        $('#payment_type').val('purchase');
                        $('#supplier_id').val(response.supplier?.id || '');
                        $('#reference_no').val(response.reference_no);
                        $('#paymentSupplierDetail').text((response.supplier
                            ?.first_name || 'Unknown') + ' ' + (response
                            .supplier?.last_name || ''));
                        $('#referenceNo').text(response.reference_no);
                        $('#paymentLocationDetails').text(response.location?.name ||
                            'Unknown');
                        $('#totalAmount').text(response.final_total);
                        $('#advanceBalance').text('Advance Balance : Rs. ' + response
                            .total_due);
                        $('#totalPaidAmount').text('Total Paid: Rs. ' + response
                            .total_paid);

                        // Set today's date as default in the "Paid On" field
                        var today = new Date().toISOString().split('T')[0];
                        $('#paidOn').val(today);

                        // Set the amount field to the total due amount
                        $('#payAmount').val(response.total_due);

                        // Ensure the Add Payment modal is brought to the front
                        $('#viewPaymentModal').modal('hide');
                        $('#paymentModal').modal('show');

                        // Validate the amount input
                        $('#payAmount').off('input').on('input', function() {
                            let amount = parseFloat($(this).val());
                            let totalDue = parseFloat(response.total_due);
                            if (amount > totalDue) {
                                $('#amountError').text(
                                    'The given amount exceeds the total due amount.'
                                ).show();
                                $(this).val(totalDue);
                            } else {
                                $('#amountError').hide();
                            }
                        });
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.message);
                    }
                });
            }

            // Define the openViewPaymentModal function
            window.openViewPaymentModal = function(event, purchaseId) {
                event.preventDefault();
                // Set the purchaseId in the data attribute of the modal
                $('#viewPaymentModal').data('purchase-id', purchaseId);

                // Fetch payment details and populate the view payment modal
                $.ajax({
                    url: '/get-purchase/' + purchaseId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#viewPaymentModal #referenceNo').text(response.reference_no);
                        $('#viewPaymentModal #viewSupplierDetail').text((response
                            .supplier?.prefix || '') + ' ' + (response.supplier
                            ?.first_name || 'Unknown') + ' ' + (response
                            .supplier?.last_name || '') + ' (' + (response
                            .supplier?.mobile_no || '') + ')');
                        $('#viewPaymentModal #viewBusinessDetail').text((response
                            .location?.name || 'Unknown') + ', ' + (response
                            .location?.address || ''));
                        $('#viewPaymentModal #viewReferenceNo').text(response
                            .reference_no);
                        $('#viewPaymentModal #viewDate').text(response.purchase_date);
                        $('#viewPaymentModal #viewPurchaseStatus').text(response
                            .purchasing_status);
                        $('#viewPaymentModal #viewPaymentStatus').text(response
                            .payment_status);

                        $('#viewPaymentModal .modal-body .table tbody').empty();

                        if (Array.isArray(response.payments) && response.payments
                            .length > 0) {
                            response.payments.forEach(function(payment) {
                                $('#viewPaymentModal .modal-body .table tbody')
                                    .append(
                                        '<tr>' +
                                        '<td>' + payment.payment_date +
                                        '</td>' +
                                        '<td>' + payment.reference_no +
                                        '</td>' +
                                        '<td>' + payment.amount + '</td>' +
                                        '<td>' + payment.payment_method +
                                        '</td>' +
                                        '<td>' + (payment.notes || '') +
                                        '</td>' +
                                        '<td>' + (payment.payment_account ||
                                            '') + '</td>' +
                                        '<td><button class="btn btn-sm btn-danger" onclick="deletePayment(' +
                                        payment.id + ')">Delete</button></td>' +
                                        '</tr>'
                                    );
                            });
                        } else {
                            $('#viewPaymentModal .modal-body .table tbody').append(
                                '<tr><td colspan="7" class="text-center">No records found</td></tr>'
                            );
                        }

                        $('#viewPaymentModal').modal('show');
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.message);
                    }
                });
            }

            function deletePayment(paymentId) {
                // Implement the delete payment functionality here
                if (confirm('Are you sure you want to delete this payment?')) {
                    $.ajax({
                        url: '/delete-payment/' + paymentId,
                        type: 'DELETE',
                        success: function(response) {
                            alert('Payment deleted successfully.');
                            $('#viewPaymentModal').modal('hide');
                            fetchPurchases();
                        },
                        error: function(xhr) {
                            alert(xhr.responseJSON.message);
                        }
                    });
                }
            }

            // Reset the payment form when the modal is closed
            $('#paymentModal').on('hidden.bs.modal', function() {
                $('#paymentForm')[0].reset();
                $('#amountError').hide();
            });
            $('#savePayment').click(function() {
                var formData = new FormData($('#paymentForm')[0]);

                $.ajax({
                    url: '/api/payments',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#paymentModal').modal('hide');
                        fetchPurchases();
                        document.getElementsByClassName('successSound')[0].play();
                        toastr.success(response.message, 'Payment Added');
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = '';
                        for (var key in errors) {
                            errorMessage += errors[key] + '\n';
                            // Display errors below each input field
                            if (key === 'amount') {
                                $('#payAmount').addClass('is-invalid');
                                $('#amountError').text(errors[key]).show();
                            } else if (key === 'payment_date') {
                                $('#paidOn').addClass('is-invalid');
                                $('#paidOn').next('.invalid-feedback').text(errors[
                                    key]).show();
                            } else if (key === 'reference_no') {
                                $('#referenceNo').addClass('is-invalid');
                                $('#referenceNo').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'payment_type') {
                                $('#paymentMethod').addClass('is-invalid');
                                $('#paymentMethod').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'reference_id') {
                                $('#referenceId').addClass('is-invalid');
                                $('#referenceId').next('.invalid-feedback').text(
                                    errors[key]).show();
                            } else if (key === 'supplier_id') {
                                $('#supplierId').addClass('is-invalid');
                                $('#supplierId').next('.invalid-feedback').text(
                                    errors[key]).show();
                            }
                        }
                    }
                });
            });

            // View Purchase Details
            // Row Click Event
            $('#purchase-list').on('click', 'tr', function(e) {
                if (!$(e.target).closest('.action-icon, .dropdown-menu').length) {
                    var purchaseId = $(this).data(
                        'id'); // Extract product ID from data-id attribute
                    $.ajax({
                        url: '/get-all-purchases-product/' + purchaseId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            var purchase = response.purchase;
                            $('#modalTitle').text('Purchase Details - ' + purchase
                                .reference_no);
                            $('#supplierDetails').text(purchase.supplier
                                .first_name + ' ' + purchase.supplier.last_name);
                            $('#locationDetails').text(purchase.location.name);
                            $('#purchaseDetails').text('Date: ' + purchase
                                .purchase_date + ', Status: ' + purchase
                                .purchasing_status);

                            var productsTable = $('#productsTable tbody');
                            productsTable.empty();
                            purchase.purchase_products.forEach(function(product,
                                index) {
                                let row = $('<tr>');
                                row.append('<td>' + (index + 1) + '</td>');
                                row.append('<td>' + product.product
                                    .product_name + '</td>');
                                row.append('<td>' + product.product.sku +
                                    '</td>');
                                row.append('<td>' + product.quantity +
                                    '</td>');
                                row.append('<td>' + product.price +
                                    '</td>');
                                row.append('<td>' + product.total +
                                    '</td>');
                                productsTable.append(row);
                            });

                            var paymentInfoTable = $('#paymentInfoTable tbody');
                            paymentInfoTable.empty();
                            purchase.payments.forEach(function(payment) {
                                let row = $('<tr>');
                                row.append('<td>' + payment.payment_date +
                                    '</td>');
                                row.append('<td>' + payment.id + '</td>');
                                row.append('<td>' + payment.amount +
                                    '</td>');
                                row.append('<td>' + payment.payment_method +
                                    '</td>');
                                row.append('<td>' + payment.notes +
                                    '</td>');
                                paymentInfoTable.append(row);
                            });

                            var amountDetailsTable = $('#amountDetailsTable tbody');
                            amountDetailsTable.empty();
                            amountDetailsTable.append('<tr><td>Total: ' + purchase
                                .total + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Discount: ' +
                                purchase.discount_amount + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Final Total: ' +
                                purchase.final_total + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Total Paid: ' +
                                purchase.total_paid + '</td></tr>');
                            amountDetailsTable.append('<tr><td>Total Due: ' +
                                purchase.total_due + '</td></tr>');

                            $('#viewPurchaseProductModal').modal('show');
                        },
                        error: function(xhr) {
                            alert(xhr.responseJSON.message);
                        }
                    });
                }
            });
        });


    });
</script>
