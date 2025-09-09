<script type="text/javascript">
    $(document).ready(function() {
        // ================================
        // INITIALIZATION & CONFIGURATION
        // ================================
        
        // CSRF Token setup
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Global variables
        let allProducts = [];
        let currentPage = 1;
        let hasMore = true;
        const pathSegments = window.location.pathname.split('/');
        const purchaseId = pathSegments[pathSegments.length - 1] === 'add-purchase' ? null : pathSegments[pathSegments.length - 1];
        
        // Initialize application
        initializeApp();
        
        function initializeApp() {
            setupValidation();
            fetchProducts();
            fetchLocations();
            initAutocomplete();
            setupEventHandlers();
            initializeDataTable();
            
            // Load purchase data if editing
            if (purchaseId) {
                fetchPurchaseData(purchaseId);
                $("#purchaseButton").text("Update Purchase");
            }
        }

        // ================================
        // VALIDATION CONFIGURATION
        // ================================
        
        const validationMessages = {
            supplier_id: { required: "Supplier is required" },
            purchase_date: { required: "Purchase Date is required" },
            purchasing_status: { required: "Purchase Status is required" },
            location_id: { required: "Business Location is required" },
            duration: { required: "Duration is required", number: "Please enter a valid number" },
            duration_type: { required: "Period is required" },
            image: {
                extension: "Please upload a valid file (jpg, jpeg, png, gif, pdf, csv, zip, doc, docx)",
                filesize: "Max file size is 5MB"
            }
        };

        function setupValidation() {
            const purchaseValidationOptions = {
                rules: {
                    supplier_id: { required: true },
                    purchase_date: { required: true },
                    purchasing_status: { required: true },
                    location_id: { required: true },
                    duration: { required: true, number: true },
                    duration_type: { required: true },
                    image: {
                        required: false,
                        extension: "jpg|jpeg|png|gif|pdf|csv|zip|doc|docx",
                        filesize: 5242880 // 5MB
                    }
                },
                messages: validationMessages,
                errorElement: 'span',
                errorPlacement: function(error, element) {
                    error.addClass('text-danger small');
                    if (element.is("select")) {
                        error.insertAfter(element.closest('.input-group'));
                    } else if (element.is(":checkbox") || element.is(":radio")) {
                        error.insertAfter(element.closest('div').find('label').last());
                    } else {
                        error.insertAfter(element);
                    }
                },
                highlight: function(element) {
                    $(element).addClass('is-invalidRed').removeClass('is-validGreen');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalidRed').addClass('is-validGreen');
                }
            };
            
            $('#purchaseForm').validate(purchaseValidationOptions);
        }

        // ================================
        // LOCATION MANAGEMENT
        // ================================
        
        function fetchLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const locationSelect = $('#services');
                    locationSelect.html('<option selected disabled>Select Location</option>');

                    if (data.status === true) {
                        // Filter locations to only show parent_id === null
                        const mainLocations = data.data.filter(location => location.parent_id === null);
                        
                        mainLocations.forEach(function(location) {
                            const option = $('<option></option>').val(location.id).text(location.name);
                            locationSelect.append(option);
                        });

                        // Trigger change for the first location by default
                        if (mainLocations.length > 0) {
                            locationSelect.val(mainLocations[0].id).trigger('change');
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

        // ================================
        // PRODUCT MANAGEMENT & AUTOCOMPLETE
        // ================================
        
        function normalizeString(str) {
            return (str || '').toString().toLowerCase().replace(/[^a-z0-9]/gi, '');
        }

        function initAutocomplete() {
            const $input = $("#productSearchInput");

            $input.autocomplete({
                minLength: 1,
                source: function(request, response) {
                    const locationId = $('#services').val();
                    const searchTermRaw = request.term.trim();
                    const searchTerm = normalizeString(searchTermRaw);

                    $.ajax({
                        url: '/products/stocks/autocomplete',
                        data: {
                            search: searchTermRaw,
                            location_id: locationId,
                            per_page: 50,
                            page: 1
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status === 200 && Array.isArray(data.data)) {
                                let items = data.data.map(item => ({
                                    label: `${item.product.product_name} (${item.product.sku || 'N/A'})`,
                                    value: item.product.product_name,
                                    product: mapProductData(item)
                                }));

                                // Filter products that match search term
                                const filtered = items.filter(item => {
                                    const normSku = normalizeString(item.product.sku);
                                    const normName = normalizeString(item.product.name);
                                    return normSku.includes(searchTerm) || normName.includes(searchTerm);
                                });

                                response(filtered.length > 0 ? filtered.slice(0, 10) : [{
                                    label: "No products found",
                                    value: "",
                                    product: null
                                }]);
                            } else {
                                response([{ label: "No products found", value: "", product: null }]);
                            }
                        },
                        error: function() {
                            response([{ label: "Error fetching products", value: "" }]);
                        }
                    });
                },
                select: function(event, ui) {
                    if (!ui.item.product) {
                        return false;
                    }
                    addProductToTable(ui.item.product);
                    $("#productSearchInput").val("");
                    currentPage = 1;
                    return false;
                },
                open: function() {
                    setTimeout(() => {
                        $(".ui-autocomplete").scrollTop(0);
                    }, 0);
                }
            });

            // Custom render for autocomplete
            const autocompleteInstance = $input.data("ui-autocomplete");
            if (autocompleteInstance) {
                autocompleteInstance._renderItem = function(ul, item) {
                    if (!item.product) {
                        return $("<li>").append(`<div style="color: red;">${item.label}</div>`).appendTo(ul);
                    }
                    return $("<li>").append(`<div>${item.label}</div>`).appendTo(ul);
                };
            }
        }

        function mapProductData(item) {
            return {
                id: item.product.id,
                name: item.product.product_name,
                sku: item.product.sku || "N/A",
                quantity: item.total_stock || 0,
                price: item.product.original_price || 0,
                wholesale_price: item.product.whole_sale_price || 0,
                special_price: item.product.special_price || 0,
                max_retail_price: item.product.max_retail_price || 0,
                retail_price: item.product.retail_price || 0,
                expiry_date: '',
                batch_no: '',
                stock_alert: item.product.stock_alert || 0,
                allow_decimal: item.product.unit?.allow_decimal || false
            };
        }

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
                                price: stock.batches?.[0]?.unit_cost || stock.product.original_price || 0,
                                wholesale_price: stock.batches?.[0]?.wholesale_price || stock.product.whole_sale_price || 0,
                                special_price: stock.batches?.[0]?.special_price || stock.product.special_price || 0,
                                max_retail_price: stock.batches?.[0]?.max_retail_price || stock.product.max_retail_price || 0,
                                retail_price: stock.batches?.[0]?.retail_price || stock.product.retail_price || 0,
                                expiry_date: stock.batches?.[0]?.expiry_date || '',
                                batch_no: stock.batches?.[0]?.batch_no || '',
                                stock_alert: stock.product.stock_alert || 0,
                                allow_decimal: stock.product.unit?.allow_decimal || false
                            };
                        }).filter(product => product !== null);
                    } else {
                        console.error("Failed to fetch product data:", data);
                    }
                })
                .catch(error => console.error("Error fetching products:", error));
        }
        // ================================
        // PRODUCT TABLE MANAGEMENT
        // ================================
        
        function addProductToTable(product, isEditing = false, prices = {}) {
            const table = $("#purchase_product").DataTable();
            let existingRow = null;

            // Check if product already exists in table
            $('#purchase_product tbody tr').each(function() {
                const rowProductId = $(this).data('id');
                if (rowProductId === product.id) {
                    existingRow = $(this);
                    return false;
                }
            });

            // Determine decimal settings
            const allowDecimal = product.allow_decimal === true || product.allow_decimal === "true";
            const quantityStep = allowDecimal ? "0.01" : "1";
            const quantityMin = allowDecimal ? "0.01" : "1";
            const quantityPattern = allowDecimal ? "[0-9]+([.][0-9]{1,2})?" : "[0-9]+";

            if (existingRow && !isEditing) {
                // Update existing row quantity
                const quantityInput = existingRow.find('.purchase-quantity');
                let currentVal = parseFloat(quantityInput.val());
                let newQuantity = allowDecimal ? (currentVal + 1) : (parseInt(currentVal) + 1);
                quantityInput.val(newQuantity).trigger('input');
            } else {
                // Create new row with prices
                const productPrices = calculateProductPrices(product, prices);
                const newRow = createProductRow(product, productPrices, quantityStep, quantityMin, quantityPattern, allowDecimal);
                
                const $newRow = $(newRow);
                table.row.add($newRow).draw();
                
                // Setup row event handlers and calculations
                setupRowEventHandlers($newRow);
                updateRowCalculations($newRow);
                updateFooterTotals();
            }
        }

        function calculateProductPrices(product, prices) {
            return {
                wholesalePrice: parseFloat(prices.wholesale_price || product.wholesale_price) || 0,
                specialPrice: parseFloat(prices.special_price || product.special_price) || 0,
                maxRetailPrice: parseFloat(prices.max_retail_price || product.max_retail_price) || 0,
                retailPrice: parseFloat(prices.retail_price || product.retail_price) || 0,
                unitCost: parseFloat(prices.unit_cost || product.price) || 0,
                quantity: prices.quantity || 1
            };
        }

        function createProductRow(product, prices, quantityStep, quantityMin, quantityPattern, allowDecimal) {
            // Calculate initial profit margin
            const profitMargin = calculateProfitMargin(prices.retailPrice, prices.unitCost);
            
            return `
                <tr data-id="${product.id}">
                    <td>${product.id}</td>
                    <td>${product.name} <br><small>Stock: ${product.quantity}</small></td>
                    <td>
                        <input type="number" class="form-control purchase-quantity" value="${prices.quantity}" 
                               min="${quantityMin}" step="${quantityStep}" pattern="${quantityPattern}" 
                               ${allowDecimal ? '' : 'oninput="this.value = this.value.replace(/[^0-9]/g, \'\')"'}>
                    </td>
                    <td>
                        <input type="number" class="form-control product-price" value="${prices.unitCost.toFixed(2)}" 
                               min="0" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control discount-percent" value="0" 
                               min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control amount unit-cost" value="${prices.unitCost.toFixed(2)}" 
                               min="0" step="0.01" readonly>
                    </td>
                    <td class="sub-total">0</td>
                    <td>
                        <input type="number" class="form-control special-price" value="${prices.specialPrice.toFixed(2)}" 
                               min="0" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control wholesale-price" value="${prices.wholesalePrice.toFixed(2)}" 
                               min="0" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control max-retail-price" value="${prices.maxRetailPrice.toFixed(2)}" 
                               min="0" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control profit-margin" value="${profitMargin.toFixed(2)}" 
                               min="0" step="0.01" readonly>
                    </td>
                    <td>
                        <input type="number" class="form-control retail-price" value="${prices.retailPrice.toFixed(2)}" 
                               min="0" step="0.01" required>
                    </td>
                    <td>
                        <input type="date" class="form-control expiry-date" value="${product.expiry_date}">
                    </td>
                    <td>
                        <input type="text" class="form-control batch_no" value="${product.batch_no}">
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm delete-product">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }

        function setupRowEventHandlers($row) {
            // Handle input changes for calculations
            $row.find('.purchase-quantity, .discount-percent, .product-price').on('input', function() {
                updateRowCalculations($row);
                updateFooterTotals();
            });

            // Handle retail price changes for profit margin calculation
            $row.find('.retail-price').on('input', function() {
                const retailPrice = parseFloat($(this).val()) || 0;
                const unitCost = parseFloat($row.find('.unit-cost').val()) || 0;
                const profitMargin = calculateProfitMargin(retailPrice, unitCost);
                $row.find('.profit-margin').val(profitMargin.toFixed(2));
                
                updateRowCalculations($row);
                updateFooterTotals();
            });

            // Handle delete button
            $row.find('.delete-product').on('click', function() {
                const table = $("#purchase_product").DataTable();
                table.row($row).remove().draw();
                updateFooterTotals();
                toastr.info('Product removed from the table.', 'Info');
            });
        }

        function calculateProfitMargin(retailPrice, unitCost) {
            if (unitCost <= 0) return 0;
            return ((retailPrice - unitCost) / unitCost) * 100;
        }

        function updateRowCalculations($row) {
            const quantity = parseFloat($row.find('.purchase-quantity').val()) || 0;
            const price = parseFloat($row.find('.product-price').val()) || 0;
            const discountPercent = parseFloat($row.find('.discount-percent').val()) || 0;

            // Calculate discounted unit cost
            const discountedPrice = price - (price * discountPercent) / 100;
            const unitCost = discountedPrice;
            const subTotal = unitCost * quantity;

            // Update fields
            $row.find('.unit-cost').val(unitCost.toFixed(2));
            $row.find('.sub-total').text(subTotal.toFixed(2));

            // Update profit margin if retail price exists
            const retailPrice = parseFloat($row.find('.retail-price').val()) || 0;
            if (retailPrice > 0) {
                const profitMargin = calculateProfitMargin(retailPrice, unitCost);
                $row.find('.profit-margin').val(profitMargin.toFixed(2));
            }
        }

        // ================================
        // FOOTER CALCULATIONS & TOTALS
        // ================================
        
        function updateFooterTotals() {
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

            // Calculate discount
            const discountType = $('#discount-type').val();
            const discountInput = parseFloat($('#discount-amount').val()) || 0;
            let discountAmount = 0;

            if (discountType === 'fixed') {
                discountAmount = discountInput;
            } else if (discountType === 'percentage') {
                discountAmount = (netTotalAmount * discountInput) / 100;
            }

            // Calculate tax
            const taxType = $('#tax-type').val();
            let taxAmount = 0;

            if (taxType === 'vat10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            } else if (taxType === 'cgst10') {
                taxAmount = (netTotalAmount - discountAmount) * 0.10;
            }

            // Calculate final total
            const finalTotal = netTotalAmount - discountAmount + taxAmount;

            // Update display elements
            $('#purchase-total').text(`Purchase Total: Rs ${finalTotal.toFixed(2)}`);
            $('#final-total').val(finalTotal.toFixed(2));
            $('#discount-display').text(`(-) Rs ${discountAmount.toFixed(2)}`);
            $('#tax-display').text(`(+) Rs ${taxAmount.toFixed(2)}`);

            // Calculate payment due
            const paidAmount = parseFloat($('#paid-amount').val()) || 0;
            const paymentDue = finalTotal - paidAmount;
            $('.payment-due').text(`Rs ${paymentDue.toFixed(2)}`);
        }

        // ================================
        // EVENT HANDLERS SETUP
        // ================================
        
        function setupEventHandlers() {
            // Footer calculation triggers
            $('#discount-type, #discount-amount, #tax-type, #paid-amount').on('change input', updateFooterTotals);

            // Location change handler
            $('#services').on('change', function() {
                const selectedLocationId = $(this).val();
                if (selectedLocationId) {
                    fetchProducts(selectedLocationId);
                }
            });

            // Supplier change handler
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

            // File upload preview
            $(".show-file").on("change", handleFilePreview);

            // Dynamic table event handlers
            $('#purchase_product').on('input', '.purchase-quantity, .discount-percent, .product-tax', function() {
                const row = $(this).closest('tr');
                updateRowCalculations(row);
                updateFooterTotals();
            });

            $('#purchase_product').on('click', '.delete-product', function() {
                $(this).closest('tr').remove();
                updateFooterTotals();
                toastr.info('Product removed from the table.', 'Info');
            });

            // Purchase form submission
            $('#purchaseButton').on('click', handlePurchaseSubmission);
        }

        function handleFilePreview() {
            const input = this;
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();

                if (file.type === "application/pdf") {
                    reader.onload = function(e) {
                        $("#pdfViewer").attr("src", e.target.result).show();
                        $("#purchase-selectedImage").hide();
                    };
                } else if (file.type.startsWith("image/")) {
                    reader.onload = function(e) {
                        $("#purchase-selectedImage").attr("src", e.target.result).show();
                        $("#pdfViewer").hide();
                    };
                }

                reader.readAsDataURL(file);
            }
        }

        function calculatePaymentDue(openingBalance) {
            const totalPurchase = parseFloat($('#purchase-total').val() || 0);
            return totalPurchase - openingBalance;
        }

        // ================================
        // DATA TABLE INITIALIZATION
        // ================================
        
        function initializeDataTable() {
            $('#purchase_product').DataTable();
        }

        // ================================
        // UTILITY FUNCTIONS
        // ================================
        
        function formatDate(dateStr) {
            const [year, month, day] = dateStr.split('-');
            return `${day}-${month}-${year}`;
        }

        function printModal() {
            window.print();
        }

        // ================================
        // PURCHASE DATA MANAGEMENT
        // ================================
        
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
            // Basic form fields
            $('#supplier-id').val(purchase.supplier_id).trigger('change');
            $('#reference-no').val(purchase.reference_no);
            $('#purchase-date').val(formatDate(purchase.purchase_date));
            $('#purchase-status').val(purchase.purchasing_status).change();
            $('#services').val(purchase.location_id).change();
            $('#duration').val(purchase.pay_term);
            $('#period').val(purchase.pay_term_type).change();
            $('#discount-type').val(purchase.discount_type).change();
            $('#discount-amount').val(purchase.discount_amount);
            $('#payment-status').val(purchase.payment_status).change();

            // Payment information
            if (purchase.payments && purchase.payments.length > 0) {
                const latestPayment = purchase.payments[purchase.payments.length - 1];
                $('#payment-date').val(formatDate(latestPayment.payment_date));
                $('#payment-account').val(latestPayment.payment_account);
                $('#payment-method').val(latestPayment.payment_method);
                $('#payment-note').val(latestPayment.notes);
            }

            // Populate product table
            populateProductTable(purchase.purchase_products);
            updateFooterTotals();
        }

        function populateProductTable(purchaseProducts) {
            const productTable = $('#purchase_product').DataTable();
            productTable.clear().draw();

            if (purchaseProducts && Array.isArray(purchaseProducts)) {
                purchaseProducts.forEach(product => {
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
                        batch_no: product.batch ? product.batch.batch_no : '',
                        allow_decimal: product.product.unit?.allow_decimal || false
                    };

                    const batchPrices = {
                        retail_price: product.batch ? product.batch.retail_price : product.retail_price,
                        unit_cost: product.batch ? product.batch.unit_cost : product.unit_cost,
                        wholesale_price: product.batch ? product.batch.wholesale_price : product.wholesale_price,
                        special_price: product.batch ? product.batch.special_price : product.special_price,
                        max_retail_price: product.batch ? product.batch.max_retail_price : product.max_retail_price,
                        quantity: product.quantity
                    };

                    addProductToTable(productData, true, batchPrices);
                });
            }
        }

        // ================================
        // PURCHASE FORM SUBMISSION
        // ================================
        
        function handlePurchaseSubmission(event) {
            event.preventDefault();
            $('#purchaseButton').prop('disabled', true).html('Processing...');

            // Validate form
            if (!$('#purchaseForm').valid()) {
                showValidationError();
                return;
            }

            // Check if products exist
            const productTableRows = document.querySelectorAll('#purchase_product tbody tr');
            if (productTableRows.length === 0) {
                showProductError();
                return;
            }

            // Prepare and submit form data
            const formData = prepareFormData(productTableRows);
            submitPurchaseData(formData);
        }

        function showValidationError() {
            document.getElementsByClassName('errorSound')[0].play();
            toastr.error('Invalid inputs, Check & try again!!', 'Warning');
            $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
        }

        function showProductError() {
            toastr.error('Please add at least one product.', 'Warning');
            document.getElementsByClassName('errorSound')[0].play();
            $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
        }

        function prepareFormData(productTableRows) {
            const formData = new FormData($('#purchaseForm')[0]);

            // Validate purchase ID for updates
            if (purchaseId && isNaN(purchaseId)) {
                toastr.error('Invalid purchase ID.', 'Error');
                $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
                return null;
            }

            // Format and validate dates
            const purchaseDate = formatDate($('#purchase-date').val());
            if (!purchaseDate) {
                toastr.error('Invalid date format. Please use YYYY-MM-DD.', 'Error');
                $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
                return null;
            }

            // Add basic form data
            formData.append('purchase_date', purchaseDate);
            formData.append('final_total', $('#final-total').val());

            // Add product data
            addProductDataToForm(formData, productTableRows);

            return formData;
        }

        function addProductDataToForm(formData, productTableRows) {
            productTableRows.forEach((row, index) => {
                const $row = $(row);
                const productData = {
                    product_id: $row.data('id'),
                    quantity: $row.find('.purchase-quantity').val() || 0,
                    unit_cost: $row.find('.unit-cost').val() || 0,
                    wholesale_price: $row.find('.wholesale-price').val() || 0,
                    special_price: $row.find('.special-price').val() || 0,
                    retail_price: $row.find('.retail-price').val() || 0,
                    max_retail_price: $row.find('.max-retail-price').val() || 0,
                    price: $row.find('.product-price').val() || 0,
                    total: $row.find('.sub-total').text() || 0,
                    batch_no: $row.find('.batch_no').val() || '',
                    expiry_date: $row.find('.expiry-date').val()
                };

                Object.keys(productData).forEach(key => {
                    formData.append(`products[${index}][${key}]`, productData[key]);
                });
            });
        }

        function submitPurchaseData(formData) {
            if (!formData) return;

            const url = purchaseId ? `/purchases/update/${purchaseId}` : '/purchases/store';

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: handleAjaxSuccess,
                error: handleAjaxError('saving purchase')
            });
        }
        // ================================
        // AJAX RESPONSE HANDLERS
        // ================================
        
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
                    window.location.href = "/list-purchase";
                }, 300);
            }
            
            $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
        }

        function handleAjaxError(action) {
            return function(xhr, status, error) {
                const errorMessage = `Something went wrong while ${action}. Status: ${xhr.status} - ${xhr.statusText}`;
                console.error('Error:', errorMessage, 'Response:', xhr.responseText);
                $('#purchaseButton').prop('disabled', false).html(purchaseId ? 'Update Purchase' : 'Save Purchase');
            };
        }

        function resetFormAndValidation() {
            $('#purchaseForm')[0].reset();
            $('#purchaseForm').validate().resetForm();
            const table = $("#purchase_product").DataTable();
            table.clear().draw();
            updateFooterTotals();
        }

        // ================================
        // PURCHASE VIEW & LIST MANAGEMENT
        // ================================
        
        function viewPurchase(purchaseId) {
            $.ajax({
                url: '/get-all-purchases-product/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populatePurchaseModal(response.purchase);
                    $('#viewPurchaseProductModal').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching purchase details:", error);
                }
            });
        }

        function populatePurchaseModal(purchase) {
            $('#modalTitle').text('Purchase Details - ' + purchase.reference_no);
            $('#supplierDetails').text(purchase.supplier.first_name + ' ' + purchase.supplier.last_name);
            $('#locationDetails').text(purchase.location.name);
            $('#purchaseDetails').text('Date: ' + purchase.purchase_date + ', Status: ' + purchase.purchasing_status);

            // Populate products table
            const productsTable = $('#productsTable tbody');
            productsTable.empty();
            purchase.purchase_products.forEach(function(product, index) {
                let row = $('<tr>');
                row.append('<td>' + (index + 1) + '</td>');
                row.append('<td>' + product.product.product_name + '</td>');
                row.append('<td>' + product.product.sku + '</td>');
                row.append('<td>' + product.quantity + '</td>');
                row.append('<td>' + (product.unit_cost || 0) + '</td>');
                row.append('<td>' + product.total + '</td>');
                productsTable.append(row);
            });

            // Populate payment info table
            const paymentInfoTable = $('#paymentInfoTable tbody');
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

            // Populate amount details table
            const amountDetailsTable = $('#amountDetailsTable tbody');
            amountDetailsTable.empty();
            const amountDetails = [
                { label: 'Total', value: purchase.total },
                { label: 'Discount', value: purchase.discount_amount },
                { label: 'Final Total', value: purchase.final_total },
                { label: 'Total Paid', value: purchase.total_paid },
                { label: 'Total Due', value: purchase.total_due }
            ];
            
            amountDetails.forEach(detail => {
                amountDetailsTable.append(`<tr><td>${detail.label}: ${detail.value}</td></tr>`);
            });
        }

        // ================================
        // PURCHASE LIST MANAGEMENT
        // ================================
        
        function initializePurchaseList() {
            fetchPurchases();
            setupSupplierDropdown();
            setupBulkPaymentHandlers();
            setupPaymentModalHandlers();
        }

        function fetchPurchases() {
            $.ajax({
                url: '/get-all-purchases',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populatePurchaseTable(response.purchases);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", status, error);
                }
            });
        }

        function populatePurchaseTable(purchases) {
            const table = $('#purchase-list').DataTable();
            table.clear().draw();
            
            if (purchases && purchases.length > 0) {
                // Sort purchases by id descending
                purchases.sort((a, b) => b.id - a.id);
                
                purchases.forEach(function(item) {
                    const row = createPurchaseTableRow(item);
                    table.row.add(row).draw(false);
                });
            }

            // Reinitialize DataTable
            if ($.fn.dataTable.isDataTable('#purchase-list')) {
                $('#purchase-list').DataTable().destroy();
            }
            $('#purchase-list').DataTable();
        }

        function createPurchaseTableRow(item) {
            const paymentStatusBadge = getPaymentStatusBadge(item.payment_status);
            const actionsDropdown = createActionsDropdown(item.id);
            
            let row = $('<tr data-id="' + item.id + '">');
            row.append('<td>' + actionsDropdown + '</td>');
            row.append('<td>' + item.purchase_date + '</td>');
            row.append('<td>' + item.reference_no + '</td>');
            row.append('<td>' + (item.location?.name || 'Unknown') + '</td>');
            row.append('<td>' + (item.supplier?.first_name || 'Unknown') + ' ' + (item.supplier?.last_name || '') + '</td>');
            row.append('<td>' + item.purchasing_status + '</td>');
            row.append('<td>' + paymentStatusBadge + '</td>');
            row.append('<td>' + item.final_total + '</td>');
            row.append('<td>' + item.total_due + '</td>');
            row.append('<td>' + (item.user?.user_name || 'Unknown') + '</td>');
            
            return row;
        }

        function getPaymentStatusBadge(status) {
            const badgeClasses = {
                'Due': 'bg-danger',
                'Partial': 'bg-warning',
                'Paid': 'bg-success'
            };
            
            const badgeClass = badgeClasses[status] || 'bg-secondary';
            return `<span class="badge ${badgeClass}">${status}</span>`;
        }

        function createActionsDropdown(itemId) {
            return `
                <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <button type="button" class="btn btn-outline-info">Actions</button>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#" onclick="viewPurchase(${itemId})">
                        <i class="fas fa-eye"></i>&nbsp;&nbsp;View
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="fas fa-print"></i>&nbsp;&nbsp;Print
                    </a>
                    <a class="dropdown-item" href="/purchase/edit/${itemId}">
                        <i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="fas fa-trash"></i>&nbsp;&nbsp;Delete
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="fas fa-barcode"></i>&nbsp;Labels
                    </a>
                    <a class="dropdown-item" href="#" onclick="openPaymentModal(event, ${itemId})">
                        <i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;Add payments
                    </a>
                    <a class="dropdown-item" href="#" onclick="openViewPaymentModal(event, ${itemId})">
                        <i class="fas fa-money-bill-alt"></i>&nbsp;&nbsp;View payments
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Purchase Return
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="far fa-edit me-2"></i>&nbsp;&nbsp;Update Status
                    </a>
                    <a class="dropdown-item" href="edit-invoice.html">
                        <i class="fas fa-envelope"></i>&nbsp;&nbsp;Item Received Notification
                    </a>
                </div>
            `;
        }
        // ================================
        // BULK PAYMENT & SUPPLIER MANAGEMENT
        // ================================
        
        function setupSupplierDropdown() {
            $.ajax({
                url: '/supplier-get-all',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populateSupplierDropdown(response.message);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", status, error);
                }
            });
        }

        function populateSupplierDropdown(suppliers) {
            const supplierSelect = $('#supplierSelect');
            supplierSelect.empty();
            supplierSelect.append('<option value="" selected disabled>Select Supplier</option>');
            
            if (suppliers && suppliers.length > 0) {
                suppliers.forEach(function(supplier) {
                    supplierSelect.append(
                        `<option value="${supplier.id}" data-opening-balance="${supplier.opening_balance}">
                            ${supplier.first_name} ${supplier.last_name}
                        </option>`
                    );
                });
            }
        }

        function setupBulkPaymentHandlers() {
            let originalOpeningBalance = 0;

            // Show bulk payment modal
            $('#bulkPaymentBtn').click(function() {
                $('#bulkPaymentModal').modal('show');
            });

            // Handle supplier selection
            $('#supplierSelect').change(function() {
                const supplierId = $(this).val();
                originalOpeningBalance = parseFloat($(this).find(':selected').data('opening-balance')) || 0;
                $('#openingBalance').text(originalOpeningBalance.toFixed(2));
                loadSupplierPurchases(supplierId);
            });

            // Handle global payment amount input
            $('#globalPaymentAmount').on('input', function() {
                handleGlobalPaymentInput(originalOpeningBalance);
            });

            // Validate individual payment amounts
            $(document).on('input', '.purchase-amount', validateIndividualPayment);

            // Handle bulk payment submission
            $('#submitBulkPayment').click(submitBulkPayment);
        }

        function loadSupplierPurchases(supplierId) {
            $.ajax({
                url: '/get-all-purchases',
                type: 'GET',
                dataType: 'json',
                data: { supplier_id: supplierId },
                success: function(response) {
                    populateSupplierPurchaseTable(response.purchases, supplierId);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", status, error);
                }
            });
        }

        function populateSupplierPurchaseTable(purchases, supplierId) {
            const purchaseTable = $('#purchaseTable').DataTable();
            purchaseTable.clear();
            
            let totalPurchaseAmount = 0, totalPaidAmount = 0, totalDueAmount = 0;

            if (purchases && purchases.length > 0) {
                purchases.forEach(function(purchase) {
                    if (purchase.supplier_id == supplierId && purchase.total_due > 0) {
                        const finalTotal = parseFloat(purchase.final_total) || 0;
                        const totalPaid = parseFloat(purchase.total_paid) || 0;
                        const totalDue = parseFloat(purchase.total_due) || 0;

                        totalPurchaseAmount += finalTotal;
                        totalPaidAmount += totalPaid;
                        totalDueAmount += totalDue;

                        purchaseTable.row.add([
                            `${purchase.id} (${purchase.reference_no})`,
                            finalTotal.toFixed(2),
                            totalPaid.toFixed(2),
                            totalDue.toFixed(2),
                            `<input type="number" class="form-control purchase-amount" data-purchase-id="${purchase.id}">`
                        ]).draw();
                    }
                });
            }

            updatePurchaseTotals(totalPurchaseAmount, totalPaidAmount, totalDueAmount);
        }

        function updatePurchaseTotals(totalPurchase, totalPaid, totalDue) {
            $('#totalPurchaseAmount').text(totalPurchase.toFixed(2));
            $('#totalPaidAmount').text(totalPaid.toFixed(2));
            $('#totalDueAmount').text(totalDue.toFixed(2));
        }

        function handleGlobalPaymentInput(originalOpeningBalance) {
            const globalAmount = parseFloat($('#globalPaymentAmount').val()) || 0;
            const totalDueAmount = parseFloat($('#totalDueAmount').text()) || 0;
            let remainingAmount = globalAmount;

            // Validate global amount
            if (globalAmount > (originalOpeningBalance + totalDueAmount)) {
                $('#globalPaymentAmount').addClass('is-invalid')
                    .after('<span class="invalid-feedback d-block">Global amount exceeds total due amount.</span>');
                return;
            } else {
                $('#globalPaymentAmount').removeClass('is-invalid').next('.invalid-feedback').remove();
            }

            // Deduct from opening balance first
            let newOpeningBalance = originalOpeningBalance;
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

            // Distribute remaining amount to purchases
            distributePaymentToPurchases(remainingAmount);
        }

        function distributePaymentToPurchases(remainingAmount) {
            $('.purchase-amount').each(function() {
                const purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
                if (remainingAmount > 0) {
                    const paymentAmount = Math.min(remainingAmount, purchaseDue);
                    $(this).val(paymentAmount);
                    remainingAmount -= paymentAmount;
                } else {
                    $(this).val(0);
                }
            });
        }

        function validateIndividualPayment() {
            const purchaseDue = parseFloat($(this).closest('tr').find('td:eq(3)').text());
            const paymentAmount = parseFloat($(this).val());
            
            if (paymentAmount > purchaseDue) {
                $(this).addClass('is-invalid').next('.invalid-feedback').remove();
                $(this).after('<span class="invalid-feedback d-block">Amount exceeds total due.</span>');
            } else {
                $(this).removeClass('is-invalid').next('.invalid-feedback').remove();
            }
        }

        function submitBulkPayment() {
            const paymentData = collectBulkPaymentData();
            
            $.ajax({
                url: '/api/submit-bulk-payment',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(paymentData),
                success: function(response) {
                    toastr.success(response.message, 'Payment Submitted');
                    $('#bulkPaymentModal').modal('hide');
                    $('#bulkPaymentForm')[0].reset();
                    fetchPurchases();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: ", status, error);
                }
            });
        }

        function collectBulkPaymentData() {
            const purchasePayments = [];
            
            $('.purchase-amount').each(function() {
                const purchaseId = $(this).data('purchase-id');
                const paymentAmount = parseFloat($(this).val());
                if (paymentAmount > 0) {
                    purchasePayments.push({
                        reference_id: purchaseId,
                        amount: paymentAmount
                    });
                }
            });

            return {
                entity_type: 'supplier',
                entity_id: $('#supplierSelect').val(),
                payment_method: $('#paymentMethod').val(),
                payment_date: $('#paidOn').val(),
                global_amount: $('#globalPaymentAmount').val(),
                payments: purchasePayments
            };
        }
        // ================================
        // PAYMENT MODAL HANDLERS
        // ================================
        
        function setupPaymentModalHandlers() {
            // Initialize DataTable for purchase table
            $('#purchaseTable').DataTable();
            
            // Reset payment form when modal is closed
            $('#paymentModal').on('hidden.bs.modal', function() {
                $('#paymentForm')[0].reset();
                $('#amountError').hide();
            });

            // Handle payment form submission
            $('#savePayment').click(handlePaymentSubmission);

            // Setup purchase table row click events
            setupPurchaseTableEvents();
        }

        function handlePaymentSubmission() {
            const formData = new FormData($('#paymentForm')[0]);

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
                error: handlePaymentSubmissionError
            });
        }

        function handlePaymentSubmissionError(xhr) {
            const errors = xhr.responseJSON.errors;
            const errorFields = {
                'amount': '#payAmount',
                'payment_date': '#paidOn',
                'reference_no': '#referenceNo',
                'payment_type': '#paymentMethod',
                'reference_id': '#referenceId',
                'supplier_id': '#supplierId'
            };

            Object.keys(errors).forEach(key => {
                const fieldSelector = errorFields[key];
                if (fieldSelector) {
                    $(fieldSelector).addClass('is-invalid');
                    if (key === 'amount') {
                        $('#amountError').text(errors[key]).show();
                    } else {
                        $(fieldSelector).next('.invalid-feedback').text(errors[key]).show();
                    }
                }
            });
        }

        function setupPurchaseTableEvents() {
            // Purchase list row click event
            $('#purchase-list').on('click', 'tr', function(e) {
                // Prevent action if clicking on thead or if no data-id
                if ($(this).closest('thead').length || typeof $(this).data('id') === 'undefined') {
                    e.stopPropagation();
                    return;
                }
                
                if (!$(e.target).closest('.action-icon, .dropdown-menu').length) {
                    const purchaseId = $(this).data('id');
                    viewPurchaseDetails(purchaseId);
                }
            });
        }

        function viewPurchaseDetails(purchaseId) {
            $.ajax({
                url: '/get-all-purchases-product/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populatePurchaseModal(response.purchase);
                    $('#viewPurchaseProductModal').modal('show');
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON.message);
                }
            });
        }

        // ================================
        // GLOBAL PAYMENT MODAL FUNCTIONS
        // ================================
        
        window.openPaymentModal = function(event, purchaseId) {
            event.preventDefault();
            
            $.ajax({
                url: '/get-purchase/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populatePaymentModal(response);
                    setupPaymentValidation(response);
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON.message);
                }
            });
        };

        function populatePaymentModal(response) {
            $('#purchaseId').val(response.id);
            $('#payment_type').val('purchase');
            $('#supplier_id').val(response.supplier?.id || '');
            $('#reference_no').val(response.reference_no);
            $('#paymentSupplierDetail').text((response.supplier?.first_name || 'Unknown') + ' ' + (response.supplier?.last_name || ''));
            $('#referenceNo').text(response.reference_no);
            $('#paymentLocationDetails').text(response.location?.name || 'Unknown');
            $('#totalAmount').text(response.final_total);
            $('#advanceBalance').text('Advance Balance : Rs. ' + response.total_due);
            $('#totalPaidAmount').text('Total Paid: Rs. ' + response.total_paid);

            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('#paidOn').val(today);
            $('#payAmount').val(response.total_due);

            // Show payment modal
            $('#viewPaymentModal').modal('hide');
            $('#paymentModal').modal('show');
        }

        function setupPaymentValidation(response) {
            $('#payAmount').off('input').on('input', function() {
                const amount = parseFloat($(this).val());
                const totalDue = parseFloat(response.total_due);
                
                if (amount > totalDue) {
                    $('#amountError').text('The given amount exceeds the total due amount.').show();
                    $(this).val(totalDue);
                } else {
                    $('#amountError').hide();
                }
            });
        }

        window.openViewPaymentModal = function(event, purchaseId) {
            event.preventDefault();
            $('#viewPaymentModal').data('purchase-id', purchaseId);

            $.ajax({
                url: '/get-purchase/' + purchaseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    populateViewPaymentModal(response);
                    $('#viewPaymentModal').modal('show');
                },
                error: function(xhr) {
                    console.log(xhr.responseJSON.message);
                }
            });
        };

        function populateViewPaymentModal(response) {
            $('#viewPaymentModal #referenceNo').text(response.reference_no);
            $('#viewPaymentModal #viewSupplierDetail').text(
                `${response.supplier?.prefix || ''} ${response.supplier?.first_name || 'Unknown'} ${response.supplier?.last_name || ''} (${response.supplier?.mobile_no || ''})`
            );
            $('#viewPaymentModal #viewBusinessDetail').text(`${response.location?.name || 'Unknown'}, ${response.location?.address || ''}`);
            $('#viewPaymentModal #viewReferenceNo').text(response.reference_no);
            $('#viewPaymentModal #viewDate').text(response.purchase_date);
            $('#viewPaymentModal #viewPurchaseStatus').text(response.purchasing_status);
            $('#viewPaymentModal #viewPaymentStatus').text(response.payment_status);

            const paymentTableBody = $('#viewPaymentModal .modal-body .table tbody');
            paymentTableBody.empty();

            if (Array.isArray(response.payments) && response.payments.length > 0) {
                response.payments.forEach(function(payment) {
                    paymentTableBody.append(createPaymentTableRow(payment));
                });
            } else {
                paymentTableBody.append('<tr><td colspan="7" class="text-center">No records found</td></tr>');
            }
        }

        function createPaymentTableRow(payment) {
            return `
                <tr>
                    <td>${payment.payment_date}</td>
                    <td>${payment.reference_no}</td>
                    <td>${payment.amount}</td>
                    <td>${payment.payment_method}</td>
                    <td>${payment.notes || ''}</td>
                    <td>${payment.payment_account || ''}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.id})">
                            Delete
                        </button>
                    </td>
                </tr>
            `;
        }

        function deletePayment(paymentId) {
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
                        console.log(xhr.responseJSON.message);
                    }
                });
            }
        }

        // ================================
        // FINAL INITIALIZATION
        // ================================
        
        // Initialize purchase list management when DOM is ready
        $(document).ready(function() {
            if ($('#purchase-list').length) {
                initializePurchaseList();
            }
        });

    });
</script>
