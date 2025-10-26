    <script>
        $(document).ready(function() {
            // Remove unused productToRemove variable since we removed confirmation dialog

            // Use backend autocomplete for product search (supports large datasets)
            function initProductAutocomplete() {
                const $input = $("#productSearch");
                
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

                        if (itemToAdd) {
                            disableInvoiceSearch();
                            addProductToTable(itemToAdd);
                            $(this).autocomplete('close');
                        }

                        event.stopImmediatePropagation();
                    }
                });

                $input.autocomplete({
                    source: function(request, response) {
                        // Optionally, you can get locationId if you want to filter by location
                        let locationId = $("#locationId").val();
                        $.ajax({
                            url: "/products/stocks/autocomplete",
                            method: "GET",
                            data: {
                                search: request.term,
                                location_id: locationId, // Uncomment if you want to filter by location
                                per_page: 15
                            },
                            success: function(data) {
                                if (data.status === 200 && Array.isArray(data.data)) {
                                    // Map backend response to autocomplete format
                                    const results = data.data.map(item => ({
                                        label: item.product.product_name,
                                        value: item.product.id,
                                        sku: item.product.sku,
                                        retail_price: item.product.retail_price,
                                        total_stock: item.total_stock ?? 0,
                                    }));
                                    response(results);
                                } else {
                                    response([]);
                                }
                            },
                            error: function() {
                                response([]);
                            }
                        });
                    },
                    minLength: 1,
                    select: function(event, ui) {
                        disableInvoiceSearch();
                        addProductToTable(ui.item);
                        return false; // Prevent default behavior
                    },
                    open: function() {
                        setTimeout(() => {
                            // Auto-focus first item for Enter key selection - Updated with working POS AJAX solution
                            const autocompleteInstance = $input.autocomplete("instance");
                            const menu = autocompleteInstance.menu;
                            const firstItem = menu.element.find("li:first-child");
                            
                            if (firstItem.length > 0) {
                                // Properly set the active item using jQuery UI's method
                                menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                                firstItem.addClass("ui-state-focus");
                                menu.active = firstItem;
                            }
                        }, 50);
                    }
                });

                // Safely override _renderItem only if instance exists
                const instance = $("#productSearch").autocomplete("instance");
                if (instance) {
                    instance._renderItem = function(ul, item) {
                        return $("<li>")
                            .append(`<div>${item.label}<br><small class="text-muted">${item.sku}</small></div>`)
                            .data('ui-autocomplete-item', item)
                            .appendTo(ul);
                    };
                }
            }

            // Initialize autocomplete on document ready
            initProductAutocomplete();



            // Fetch and populate locations
            fetchLocations();

            function fetchLocations() {
                $.ajax({
                    url: '/location-get-all',
                    method: 'GET',
                    success: function(response) {
                        if (response.status && response.data) {
                            const locationSelect = $("#locationId");
                            response.data.forEach(location => {
                                locationSelect.append(new Option(location.name, location.id));
                            });
                        }
                    },
                    error: function(error) {
                        console.error('Error fetching locations:', error);
                    }
                });
            }


            $("#locationId").on('change', function() {
                $("#productsTableBody").empty();
                // Optionally, reset invoice/product search fields if needed
                $("#invoiceNo").val('');
                $("#productSearch").val('');
                enableProductSearch();
                enableInvoiceSearch();
                calculateReturnTotal(); // Recalculate totals if needed
            });


            // Fetch and populate customers
            fetchCustomers();

            function fetchCustomers() {
                $.ajax({
                    url: '/customer-get-all',
                    method: 'GET',
                    success: function(data) {
                        const customerSelect = $("#customerId");
                        data.message.forEach(customer => {
                            customerSelect.append(new Option(
                                `${customer.first_name} ${customer.last_name}`, customer
                                .id));
                        });
                    },
                    error: function(error) {
                        console.error('Error fetching customers:', error);
                    }
                });
            }

            // Function to get query parameters
            function getQueryParam(param) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(param);
            }

            // Check for invoice number in URL and auto-load
            const invoiceNo = getQueryParam('invoiceNo');
            
            console.log('=== Sale Return Page Loaded ===');
            console.log('URL invoice parameter:', invoiceNo);
            
            if (invoiceNo) {
                console.log('Setting invoice number to:', invoiceNo);
                
                // Wait a bit for all elements to be ready
                setTimeout(function() {
                    const invoiceField = document.getElementById('invoiceNo');
                    console.log('Invoice field found:', invoiceField !== null);
                    
                    if (invoiceField) {
                        invoiceField.value = invoiceNo;
                        console.log('Invoice field value set to:', invoiceField.value);
                        
                        // Trigger the fetch
                        console.log('Fetching sale products for invoice:', invoiceNo);
                        fetchSaleProducts(invoiceNo);
                    } else {
                        console.error('Invoice field not found!');
                    }
                }, 300);
            } else {
                console.log('No invoice parameter in URL');
            }

            $("#invoiceNo").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "/api/search/sales",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    disableProductSearch();
                    fetchSaleProducts(ui.item.value);
                }
            });

            function fetchSaleProducts(invoiceNo) {
                console.log('fetchSaleProducts called with:', invoiceNo);
                
                $.ajax({
                    url: `/sales/${invoiceNo}`,
                    method: 'GET',
                    success: function(data) {
                        console.log('Sale data received:', data);
                        
                        const productsTableBody = $("#productsTableBody");
                        productsTableBody.empty();

                        if (!data || !data.products) {
                            console.error('Invalid sale data:', data);
                            swal({
                                title: "Invalid Sale",
                                text: "Invalid Sale ID. Please check and try again.",
                                type: "error",
                                confirmButtonText: "OK"
                            });
                            return;
                        }

                        // Check if there are any returnable products (quantity > 0)
                        if (data.products.length === 0) {
                            console.warn('No returnable products found');
                            swal({
                                title: "No Returnable Products",
                                text: "All products in this sale have already been returned or have zero quantity.",
                                type: "info",
                                confirmButtonText: "OK"
                            });
                            
                            // Clear the invoice field
                            $("#invoiceNo").val('');
                            $("#sale-id").val('');
                            
                            // Reset display
                            $("#displayInvoiceNo").html('<strong>Invoice No.:</strong>');
                            $("#displayDate").html('<strong>Date:</strong>');
                            return;
                        }

                        $("#sale-id").val(data.sale_id);
                        $("#customer-id").val(data.customer_id); // Set the customer ID

                        data.products.forEach((product, index) => {
                            // Always allow decimal input (step="any")
                            const inputAttrs =
                                `type="number" step="any" inputmode="decimal" autocomplete="off"`;

                            // Unit should always be available now
                            const unitDisplay = product.unit.short_name || 'Pc(s)';
                            
                            // Use the actual stored price from sales_products table
                            const returnPrice = parseFloat(product.return_price || product.price || 0);
                            const discountInfo = `<small class="text-info">Return: Rs. ${returnPrice.toFixed(2)}/unit</small>`;

                            const row = `
                                            <tr data-index="${index}">
                                                <td>${index + 1}</td>
                                                <td>
                                                    ${product.product.product_name}<br>
                                                    <small class="text-muted">${product.product.sku}</small>
                                                </td>
                                                <td>
                                                    <div>Rs. ${returnPrice.toFixed(2)}</div>
                                                    ${discountInfo}
                                                </td>
                                                <td><del>${product.quantity}</del>  ${product.current_quantity} ${unitDisplay}</td>
                                                <td>
                                                    <input ${inputAttrs} class="form-control return-quantity" 
                                                           name="products[${index}][quantity]" 
                                                           placeholder="Enter qty (optional)" 
                                                           max="${product.current_quantity}" 
                                                           data-unit-price="${returnPrice}" 
                                                           data-original-price="${returnPrice}"
                                                           data-product-id="${product.product.id}" 
                                                           data-batch-id="${product.batch_id}">
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

                        // Store original discount data for proportional calculation
                        window.originalDiscountData = data.original_discount;

                        // Initially clear discount fields
                        $("#discountType").val("");
                        $("#discountAmount").val("");

                        fetchCustomerDetails(data.customer_id);
                        setLocationId(data.location_id);

                        // Improved input handler for decimals and cursor position
                        $(".return-quantity").on('input', function(e) {
                            const $input = $(this);
                            let value = $input.val();

                            // Allow only numbers and one decimal point, but allow leading "0." or "."
                            value = value.replace(/[^0-9.]/g, '');

                            // Prevent multiple decimals
                            const parts = value.split('.');
                            if (parts.length > 2) {
                                value = parts[0] + '.' + parts[1];
                            }

                            // If user types just ".", convert to "0."
                            if (value === '.') {
                                value = '0.';
                            }

                            // If value starts with "0" and not "0." (e.g. "01"), remove leading zero
                            if (value.length > 1 && value.startsWith('0') && value[1] !== '.') {
                                value = value.replace(/^0+/, '');
                                if (value === '') value = '0';
                            }

                            // Set value back and keep cursor at end
                            $input.val(value);

                            const max = parseFloat($input.attr('max'));
                            let quantity = parseFloat(value) || 0;
                            const unitPrice = parseFloat($input.data('unit-price'));
                            const errorDiv = $input.siblings('.quantity-error');

                            if (quantity > max) {
                                quantity = max;
                                $input.val(quantity);
                                errorDiv.html('Quantity cannot exceed<br>the available amount.')
                                    .show();
                            } else {
                                errorDiv.hide();
                            }

                            const returnSubtotal = quantity * unitPrice;
                            $input.closest('tr').find('.return-subtotal').text(
                                `Rs. ${returnSubtotal.toFixed(2)}`);
                            calculateReturnTotal();
                        });

                        $(".remove-product").on('click', function() {
                            // Remove immediately without confirmation
                            $(this).closest('tr').remove();
                            toastr.success('Product removed successfully.');
                            calculateReturnTotal();
                            
                            // If no products left, reset the invoice
                            if ($("#productsTableBody tr").length === 0) {
                                resetInvoice();
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching sales data:', error);
                        
                        if (xhr.status === 404) {
                            swal({
                                title: "Sale Not Found",
                                text: "No sale found with this invoice number. Please check the invoice number and try again.",
                                type: "error",
                                confirmButtonText: "OK"
                            });
                        } else if (xhr.status === 409) {
                            // Handle duplicate return conflict with SweetAlert and table view
                            const response = xhr.responseJSON;
                            let title = 'Multiple Returns Not Allowed';
                            let baseMessage = 'This sale has already been returned. Multiple returns for the same invoice are not allowed.';
                            
                            let htmlContent = `<div style="text-align: left;">
                                <p style="margin-bottom: 15px; font-size: 14px; color: #666;">${baseMessage}</p>`;
                            
                            if (response && response.return_details && response.return_details.length > 0) {
                                htmlContent += `
                                    <div style="margin-top: 20px;">
                                        <h4 style="margin-bottom: 10px; color: #333; font-size: 16px;">Existing Returns:</h4>
                                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                            <thead>
                                                <tr style="background-color: #f8f9fa;">
                                                    <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: 600;">#</th>
                                                    <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: 600;">Date</th>
                                                    <th style="border: 1px solid #dee2e6; padding: 8px; text-align: right; font-weight: 600;">Amount</th>
                                                    <th style="border: 1px solid #dee2e6; padding: 8px; text-align: left; font-weight: 600;">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>`;
                                
                                response.return_details.forEach((returnDetail, index) => {
                                    const returnDate = new Date(returnDetail.return_date).toLocaleDateString();
                                    const notes = returnDetail.notes || 'No notes';
                                    
                                    htmlContent += `
                                        <tr style="background-color: ${index % 2 === 0 ? '#ffffff' : '#f8f9fa'};">
                                            <td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">${index + 1}</td>
                                            <td style="border: 1px solid #dee2e6; padding: 8px;">${returnDate}</td>
                                            <td style="border: 1px solid #dee2e6; padding: 8px; text-align: right; font-weight: 500;">Rs. ${returnDetail.return_total}</td>
                                            <td style="border: 1px solid #dee2e6; padding: 8px;">${notes}</td>
                                        </tr>`;
                                });
                                
                                htmlContent += `
                                            </tbody>
                                        </table>
                                    </div>`;
                            }
                            
                            htmlContent += `</div>`;
                            
                            swal({
                                title: title,
                                text: htmlContent,
                                html: true, // Enable HTML content
                                type: "warning",
                                confirmButtonText: "OK",
                                confirmButtonColor: "#dd6b55"
                            });
                            
                            // Clear the invoice field
                            $("#invoiceNo").val('');
                            $("#sale-id").val('');
                            
                            // Clear any existing products
                            $("#productsTableBody").empty();
                            
                            // Reset display
                            $("#displayInvoiceNo").html('<strong>Invoice No.:</strong>');
                            $("#displayDate").html('<strong>Date:</strong>');
                        } else {
                            swal({
                                title: "Error",
                                text: "Error loading sale data. Please try again.",
                                type: "error",
                                confirmButtonText: "OK"
                            });
                        }
                    }
                });
            }

            function fetchCustomerDetails(customerId) {

                $.ajax({
                    url: `/customer-get-all`,
                    method: 'GET',
                    success: function(data) {
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
                    error: function(error) {
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
                let totalReturnQuantity = 0;
                
                // Calculate total subtotal and return quantity
                $('.return-subtotal').each(function() {
                    totalSubtotal += parseFloat($(this).text().replace('Rs. ', ''));
                });
                
                $('.return-quantity').each(function() {
                    const qty = parseFloat($(this).val()) || 0;
                    totalReturnQuantity += qty;
                });

                // Calculate proportional discount based on original sale data
                let totalDiscount = 0;
                let discountType = "";
                let discountAmount = 0;
                
                if (window.originalDiscountData && window.originalDiscountData.discount_amount > 0 && totalReturnQuantity > 0) {
                    const originalDiscount = window.originalDiscountData;
                    const originalQuantity = originalDiscount.total_original_quantity || 1;
                    
                    // Calculate proportion of return vs original sale
                    const returnProportion = totalReturnQuantity / originalQuantity;
                    
                    discountType = originalDiscount.discount_type;
                    
                    if (discountType === "percentage") {
                        // For percentage discount, use the same percentage
                        discountAmount = originalDiscount.discount_amount;
                        totalDiscount = (totalSubtotal * discountAmount) / 100;
                    } else if (discountType === "fixed") {
                        // For fixed discount, apply proportionally
                        discountAmount = originalDiscount.discount_amount * returnProportion;
                        totalDiscount = discountAmount;
                    }
                    
                    // Update UI to show the calculated discount
                    const frontendDiscountType = discountType === "fixed" ? "flat" : discountType;
                    $("#discountType").val(frontendDiscountType);
                    $("#discountAmount").val(discountAmount.toFixed(2));
                    
                    console.log('Proportional discount calculated:', {
                        originalQuantity: originalQuantity,
                        returnQuantity: totalReturnQuantity,
                        proportion: returnProportion,
                        originalDiscountAmount: originalDiscount.discount_amount,
                        calculatedDiscountAmount: discountAmount,
                        discountType: discountType
                    });
                } else {
                    // Manual discount override
                    discountType = $('#discountType').val();
                    discountAmount = parseFloat($('#discountAmount').val()) || 0;
                    
                    if (discountType === 'percentage') {
                        totalDiscount = (totalSubtotal * discountAmount) / 100;
                    } else {
                        totalDiscount = discountAmount;
                    }
                }

                const returnTotal = totalSubtotal - totalDiscount;
                $('#totalReturnDiscount').text(`Rs. ${totalDiscount.toFixed(2)}`);
                $('#returnTotalDisplay').text(`Rs. ${returnTotal.toFixed(2)}`);
                $('#returnTotal').val(returnTotal.toFixed(2));
            }

            $('#discountType, #discountAmount').on('change input', function() {
                calculateReturnTotal();
            });

            // Removed confirmation dialog code since we now do instant removal

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



            function addProductToTable(product) {
                const newRow = `
                <tr>
                    <td></td>
                    <td>${product.label} <br> ${product.sku}</td>
                    <td>Rs. ${product.retail_price.toFixed(2)}</td>
                    <td>${product.total_stock !== undefined && product.total_stock !== null ? product.total_stock : 0} Pcs</td>
                    <td>
                        <input type="number" class="form-control return-quantity" name="products[${product.value}][quantity]" placeholder="Enter qty (optional)" max="${product.total_stock}" data-unit-price="${product.retail_price}" data-product-id="${product.value}">
                        <div class="quantity-error">Quantity cannot exceed<br>the available amount.</div>
                    </td>
                    <td class="return-subtotal">Rs. 0.00</td>
                    <td><button type="button" class="btn btn-danger remove-product"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
            `;

                $("#productsTableBody").append(newRow);
                updateRowNumbers();
                calculateReturnTotal();

                $(".return-quantity").on('input', function() {
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

                $(".remove-product").on('click', function() {
                    $(this).closest('tr').remove();
                    updateRowNumbers();
                    calculateReturnTotal();
                    enableInvoiceSearch();
                });
            }

            function updateRowNumbers() {
                $("#productsTableBody tr").each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            // Initialize jQuery validation
            $("#salesReturnForm").validate({
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorElement: 'div',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    if (element.prop('type') === 'checkbox') {
                        error.insertAfter(element.next('label'));
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass(errorClass).removeClass(validClass);
                },
                unhighlight: function(element, errorClass, validClass) {
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
                        number: true,
                        min: 0.01
                    },
                    notes: {
                        required: true
                    }
                },
                messages: {
                    return_date: "Please select a return date",
                    location_id: "Please select a location",
                    return_total: {
                        required: "Please enter the return total amount",
                        number: "Please enter a valid number",
                        min: "Return total must be greater than 0"
                    },
                    notes: "Please enter a reason for the return"
                },
                submitHandler: function(form) {
                    console.log('Form submit handler called');
                    const withBill = $('#withBill').is(':checked');
                    console.log('Billing mode:', withBill ? 'With Bill' : 'Without Bill');
                    
                    const isValid = validateForm();
                    console.log('Form validation result:', isValid);
                    
                    const $submitButton = $('.btn[type="submit"]');
                    $submitButton.prop('disabled', true).html('Processing...');

                    if (isValid) {
                        const formData = new FormData(form);
                        const jsonData = Object.fromEntries(formData.entries());

                        console.log('Form Data:', jsonData);

                        // Adding nested product data - only include products with return quantity > 0
                        jsonData.products = [];
                        $("#productsTableBody tr").each(function(index, row) {
                            const quantity = parseFloat($(row).find('.return-quantity').val()) || 0;
                            
                            // Only include products with return quantity > 0
                            if (quantity > 0) {
                                const $input = $(row).find('.return-quantity');
                                const product = {
                                    product_id: $input.data('productId'),
                                    quantity: quantity,
                                    original_price: $input.data('originalPrice') || $input.data('unitPrice'),
                                    return_price: $input.data('unitPrice'), // This is the actual return price
                                    subtotal: parseFloat($(row).find('.return-subtotal').text()
                                        .replace('Rs. ', '')),
                                    batch_id: $input.data('batchId') || null,
                                    price_type: "retail",
                                    discount: 0,
                                    tax: 0,
                                };
                                jsonData.products.push(product);
                            }
                        });

                        // Check if at least one product is being returned
                        if (jsonData.products.length === 0) {
                            toastr.error("Please add at least one product and enter return quantity.");
                            $submitButton.prop('disabled', false).html('Save');
                            return;
                        }

                        // Additional validation for billing options
                        if (withBill && !jsonData.sale_id) {
                            toastr.error("Please select a valid invoice first.");
                            $submitButton.prop('disabled', false).html('Save');
                            return;
                        }

                        console.log('Submitting form data:', jsonData);

                        // Using jQuery AJAX
                        $.ajax({
                            url: "/sale-return/store",
                            type: "POST",
                            data: JSON.stringify(jsonData),
                            contentType: "application/json",
                            dataType: "json",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
                            },
                            success: function(response) {
                                if (response.status === 200) {
                                    toastr.success(response.message);
                                    setTimeout(() => {
                                        window.location.href = "/sale-return/list";
                                    }, 1500);
                                } else {
                                    toastr.error(response.errors.join("<br>"));
                                    $submitButton.prop('disabled', false).html('Save');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error storing sales return:', xhr.responseText);
                                let errorMessage = "An error occurred while processing the request.";
                                
                                if (xhr.status === 422) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.errors) {
                                            errorMessage = Object.values(response.errors).flat().join('<br>');
                                        }
                                    } catch (e) {
                                        errorMessage = "Validation error occurred.";
                                    }
                                } else if (xhr.status === 419) {
                                    errorMessage = "Session expired. Please refresh the page and try again.";
                                } else if (xhr.status === 403) {
                                    errorMessage = "You don't have permission to perform this action.";
                                }
                                
                                toastr.error(errorMessage);
                                $submitButton.prop('disabled', false).html('Save');
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
                
                // Clear previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                // Check billing option specific validation
                const withBill = $('#withBill').is(':checked');
                console.log('Billing mode:', withBill ? 'With Bill' : 'Without Bill');
                
                // Validate required fields based on billing option
                const fieldsToValidate = [
                    { field: '#date', message: 'Please select a return date' },
                    { field: '#locationId', message: 'Please select a location' },
                    { field: '#notes', message: 'Please enter a reason for the return' }
                ];

                // Add conditional validation
                if (withBill) {
                    fieldsToValidate.push({ field: '#invoiceNo', message: 'Please enter an invoice number' });
                    // Also check if a valid sale is loaded
                    if (!$('#sale-id').val()) {
                        $('#invoiceNo').addClass('is-invalid');
                        $('#invoiceNo').after('<div class="invalid-feedback">Please select a valid invoice</div>');
                        isValid = false;
                    }
                } else {
                    fieldsToValidate.push({ field: '#customerId', message: 'Please select a customer' });
                }

                // Validate each field
                fieldsToValidate.forEach(validation => {
                    const $field = $(validation.field);
                    const fieldValue = $field.val();
                    console.log(`Validating ${validation.field}:`, fieldValue);
                    
                    if (!fieldValue || fieldValue.trim() === '') {
                        console.log(`Field ${validation.field} is invalid`);
                        $field.addClass('is-invalid');
                        $field.after(`<div class="invalid-feedback">${validation.message}</div>`);
                        isValid = false;
                    } else {
                        console.log(`Field ${validation.field} is valid`);
                    }
                });

                // Validate return total (this field should be auto-calculated)
                const returnTotal = parseFloat($('#returnTotal').val()) || 0;
                if (returnTotal <= 0) {
                    $('#returnTotal').addClass('is-invalid');
                    $('#returnTotal').after('<div class="invalid-feedback">Return total must be greater than 0</div>');
                    isValid = false;
                    console.log('Return total validation failed:', returnTotal);
                } else {
                    console.log('Return total is valid:', returnTotal);
                }

                // Check if at least one product has return quantity > 0
                let hasReturnQuantity = false;
                $('.return-quantity').each(function() {
                    const quantity = parseFloat($(this).val()) || 0;
                    if (quantity > 0) {
                        hasReturnQuantity = true;
                        return false; // break out of loop
                    }
                });

                if (!hasReturnQuantity) {
                    toastr.error("Please add at least one product and enter return quantity.");
                    isValid = false;
                }

                console.log('Overall form validation result:', isValid);
                return isValid;
            }

            fetchData();

            function fetchData() {
                $('#salesReturnTable').DataTable().destroy(); // Destroy previous instance if exists

                $.ajax({
                    url: '/sale-returns',
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 200) {
                            var salesReturns = response.data;
                            var totalAmount = response.totalAmount;
                            var totalDue = response.totalDue;

                            // Sort salesReturns by return_date descending (latest first)
                            salesReturns.sort(function(a, b) {
                                return new Date(b.return_date) - new Date(a.return_date);
                            });

                            // Prepare table rows
                            var rows = salesReturns.map(function(salesReturn, index) {
                                var parentSaleInvoice = salesReturn.sale ? salesReturn.sale
                                    .invoice_no : 'N/A';
                                var customerName = salesReturn.sale && salesReturn.sale
                                    .customer ?
                                    salesReturn.sale.customer.first_name + ' ' + salesReturn
                                    .sale.customer.last_name :
                                    (salesReturn.customer ? salesReturn.customer.first_name +
                                        ' ' + salesReturn.customer.last_name : 'N/A');
                                var locationName = salesReturn.sale ?
                                    (salesReturn.sale.location ? salesReturn.sale.location
                                        .name : 'N/A') :
                                    (salesReturn.location ? salesReturn.location.name : 'N/A');
                                var userName = salesReturn.user ? salesReturn.user.user_name :
                                    'N/A';

                                return [
                                    index + 1,
                                    new Date(salesReturn.return_date).toLocaleDateString() +
                                    ' ' + new Date(salesReturn.return_date)
                                    .toLocaleTimeString(),
                                    salesReturn.invoice_number,
                                    parentSaleInvoice,
                                    customerName,
                                    locationName,
                                    userName, // Show user name here
                                    salesReturn.payment_status,
                                    salesReturn.return_total,
                                    salesReturn.total_due,
                                    `<div class="dropdown dropdown-action">
                                        <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item view-sale-return" href="#" data-id="${salesReturn.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                            <a class="dropdown-item print-return-receipt" href="#" data-id="${salesReturn.id}"><i class="fas fa-print"></i>&nbsp;&nbsp;Print</a>
                                            <a class="dropdown-item edit-link" href="/sale-return/edit/${salesReturn.id}" data-id="${salesReturn.id}"><i class="far fa-edit me-2"></i>&nbsp;Edit</a>
                                            <a class="dropdown-item add-payment-btn" href="" data-id="${salesReturn.id}" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;Add Payment</a>
                                            <a class="dropdown-item view-payment-btn" href="" data-id="${salesReturn.id}" data-bs-toggle="modal" data-bs-target="#viewPaymentModal"><i class="fas fa-money-bill-wave"></i>&nbsp;&nbsp;View Payment</a>
                                        </div>
                                    </div>`
                                ];
                            });

                            // Initialize DataTable
                            $('#salesReturnTable').DataTable({
                                data: rows,
                                destroy: true,
                                columns: [{
                                        title: "#"
                                    },
                                    {
                                        title: "Return Date"
                                    },
                                    {
                                        title: "Return Invoice No."
                                    },
                                    {
                                        title: "Parent Sale Invoice"
                                    },
                                    {
                                        title: "Customer"
                                    },
                                    {
                                        title: "Location"
                                    },
                                    {
                                        title: "User"
                                    }, // Add User column
                                    {
                                        title: "Payment Status"
                                    },
                                    {
                                        title: "Return Total"
                                    },
                                    {
                                        title: "Total Due"
                                    },
                                    {
                                        title: "Actions",
                                        orderable: false,
                                        searchable: false
                                    }
                                ],
                                // Optionally, you can set default order by Return Date descending
                                order: [
                                    [1, 'desc']
                                ],
                                footerCallback: function(row, data, start, end, display) {
                                    var api = this.api();
                                    // Calculate total for Return Total and Total Due columns
                                    var totalReturn = api.column(8).data().reduce(function(
                                        a, b) {
                                        return parseFloat(a) + parseFloat(b);
                                    }, 0);
                                    var totalDue = api.column(9).data().reduce(function(a,
                                        b) {
                                        return parseFloat(a) + parseFloat(b);
                                    }, 0);

                                    $(api.column(8).footer()).html(totalReturn.toFixed(2));
                                    $(api.column(9).footer()).html(totalDue.toFixed(2));
                                }
                            });
                        }
                    },
                    error: function(error) {
                        console.log('Error fetching sales returns:', error);
                    }
                });
            }
            $(document).on('click', '.print-return-receipt', function(e) {
                e.preventDefault();
                // Get saleReturnId from button click or from modal if inside modal
                var saleReturnId = $(this).data('id') || $('#saleDetailsModal').attr('data-sale-return-id');
                fetch(`/sale-return/print/${saleReturnId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.invoice_html) {
                            const iframe = document.createElement('iframe');
                            iframe.style.position = 'fixed';
                            iframe.style.width = '0';
                            iframe.style.height = '0';
                            iframe.style.border = 'none';
                            document.body.appendChild(iframe);

                            iframe.contentDocument.open();
                            iframe.contentDocument.write(data.invoice_html);
                            iframe.contentDocument.close();

                            iframe.onload = function() {
                                iframe.contentWindow.print();
                                iframe.contentWindow.onafterprint = function() {
                                    document.body.removeChild(iframe);
                                };
                            };
                        } else {
                            alert(data.error || 'Failed to fetch the receipt. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching the receipt:', error);
                        alert('An error occurred while fetching the receipt. Please try again.');
                    });
            });


            $(document).on('click', '.view-sale-return', function() {
                var saleReturnId = $(this).data('id');
                // Set the sale return ID in the modal
                $('#saleDetailsModal').attr('data-sale-return-id', saleReturnId);
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

            $('#saleDetailsModal').on('hidden.bs.modal', function() {
                $(this).removeAttr('data-sale-return-id');
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
                                swal({
                                    title: "Success",
                                    text: "Payment deleted successfully",
                                    type: "success",
                                    confirmButtonText: "OK",
                                    timer: 2000
                                });
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
