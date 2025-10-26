<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

<script>
    // Pass user permissions to JavaScript
    const userPermissions = {
        canEditSale: @json(auth()->check() && auth()->user()->can('edit sale')),
        canDeleteSale: @json(auth()->check() && auth()->user()->can('delete sale')),
        canEditProduct: @json(auth()->check() && auth()->user()->can('edit product')),
        canDeleteProduct: @json(auth()->check() && auth()->user()->can('delete product'))
    };

    // Global function to clean up modal backdrops and body styles
    window.cleanupModalBackdrop = function() {
        // Remove all modal backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());

        // Reset body styles
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        console.log('Modal backdrop cleanup completed');
    };

    document.addEventListener("DOMContentLoaded", function() {
        let selectedLocationId = null;
        let currentProductsPage = 1;
        let hasMoreProducts = true;
        let isLoadingProducts = false;
        let allProducts = []; // paginated products for card display
        let stockData = []; // not used for cards/autocomplete in new version
        let isEditing = false;
        let currentEditingSaleId = null; // Track the sale ID being edited
        let isSalesRep = false; // Track if current user is a sales rep
        let salesRepCustomersFiltered = false; // Track if sales rep customer filtering has been applied
        let salesRepCustomersLoaded = false; // Track if customers have been loaded for this session

        const posProduct = document.getElementById('posProduct');
        const billingBody = document.getElementById('billing-body');
        const discountInput = document.getElementById('discount');
        const finalValue = document.getElementById('total');
        const categoryBtn = document.getElementById('category-btn');
        const allProductsBtn = document.getElementById('allProductsBtn');
        const subcategoryBackBtn = document.getElementById('subcategoryBackBtn');

        // ---- INIT ----
        // Check if user is sales rep and handle vehicle/route selection
        // This must be called FIRST before any display restoration
        checkSalesRepStatus();

        // Protect sales rep customer filtering from being overridden
        protectSalesRepCustomerFiltering();
        fetchAllLocations();
        $('#locationSelect').on('change', handleLocationChange);
        $('#locationSelectDesktop').on('change', handleLocationChange);
        fetchCategories();
        fetchBrands();
        initAutocomplete();

        // Auto-focus product search input for quick product search
        setTimeout(() => {
            const productSearchInput = document.getElementById('productSearchInput');
            if (productSearchInput) {
                productSearchInput.focus();
                productSearchInput.select(); // Also select any existing text

                // Add a subtle visual feedback for auto-focus
                productSearchInput.style.transition = 'all 0.3s ease';
                productSearchInput.style.boxShadow = '0 0 10px rgba(0, 123, 255, 0.3)';
                productSearchInput.style.borderColor = '#007bff';

                setTimeout(() => {
                    productSearchInput.style.boxShadow = '';
                    productSearchInput.style.borderColor = '';
                }, 2000); // Remove highlight after 2 seconds

                console.log('Product search input auto-focused for quick searching');
            }
        }, 500); // Small delay to ensure all elements are loaded

        // Auto-focus when page becomes visible (e.g., switching browser tabs back to POS)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                setTimeout(() => {
                    const productSearchInput = document.getElementById('productSearchInput');
                    if (productSearchInput && document.activeElement !== productSearchInput) {
                        productSearchInput.focus();
                        console.log('Product search input focused on page visibility change');
                    }
                }, 200);
            }
        });

        // Listen for customer changes to update pricing
        $('#customer-id').on('change', function() {
            // Update pricing for existing products in billing table
            const billingBody = document.getElementById('billing-body');
            const existingRows = billingBody ? billingBody.querySelectorAll('tr') : [];

            if (existingRows.length > 0) {
                const currentCustomer = getCurrentCustomer();
                console.log('Customer changed to:', currentCustomer);

                // Update all existing billing rows with new customer pricing
                updateAllBillingRowsPricing(currentCustomer.customer_type);

                // toastr.success('Product prices and discounts updated based on new customer type!');
            }
        });

        // ---- CUSTOMER TYPE PRICING FUNCTIONS ----
        /**
         * Get the current customer's type and details
         */
        function getCurrentCustomer() {
            const customerId = $('#customer-id').val();
            if (!customerId || customerId === '1') {
                return {
                    id: 1,
                    customer_type: 'retailer'
                }; // Default walk-in customer as retailer
            }

            // Get customer data from the select option
            const customerOption = $('#customer-id option:selected');

            // First try to get customer type from data attribute (most reliable)
            let customerType = customerOption.attr('data-customer-type');

            if (customerType) {
                console.log('Customer type found in data attribute:', customerType);
            } else {
                // Fallback: Extract from dropdown text
                const customerText = customerOption.text();
                console.log('Customer dropdown text:', customerText);

                // Extract customer type from the option text format: "Name - Type (Mobile)"
                customerType = 'retailer'; // Default fallback

                if (customerText.toLowerCase().includes('- wholesaler')) {
                    customerType = 'wholesaler';
                } else if (customerText.toLowerCase().includes('- retailer')) {
                    customerType = 'retailer';
                } else {
                    // Last resort: make AJAX call to get customer data
                    console.warn('Customer type not found, fetching from server...');

                    $.ajax({
                        url: `/customer-get-by-id/${customerId}`,
                        method: 'GET',
                        async: false, // Synchronous call (not recommended but needed here)
                        success: function(response) {
                            if (response && response.customer_type) {
                                customerType = response.customer_type;
                                console.log('Fetched customer type from server:', customerType);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Failed to fetch customer type:', error);
                        }
                    });
                }
            }

            const result = {
                id: parseInt(customerId),
                customer_type: customerType
            };

            console.log('Final getCurrentCustomer result:', result);
            return result;
        }

        /**
         * Determine the correct price based on customer type and batch pricing
         */
        function getCustomerTypePrice(batch, product, customerType) {
            console.log('Determining price for customer type:', customerType, 'Batch:', batch, 'Product:',
                product);

            let selectedPrice = 0;
            let priceSource = '';

            if (customerType === 'wholesaler') {
                console.log('Processing wholesaler pricing...');
                // Wholesaler pricing logic
                if (batch && batch.wholesale_price && parseFloat(batch.wholesale_price) > 0) {
                    selectedPrice = parseFloat(batch.wholesale_price);
                    priceSource = 'batch_wholesale_price';
                    console.log('Using batch wholesale price:', selectedPrice);
                } else if (batch && batch.special_price && parseFloat(batch.special_price) > 0) {
                    selectedPrice = parseFloat(batch.special_price);
                    priceSource = 'batch_special_price';
                    console.log('Using batch special price:', selectedPrice);
                } else if (batch && batch.retail_price && parseFloat(batch.retail_price) > 0) {
                    selectedPrice = parseFloat(batch.retail_price);
                    priceSource = 'batch_retail_price';
                    console.log('Using batch retail price:', selectedPrice);
                } else if (batch && batch.max_retail_price && parseFloat(batch.max_retail_price) > 0) {
                    selectedPrice = parseFloat(batch.max_retail_price);
                    priceSource = 'batch_max_retail_price';
                    console.log('Using batch max retail price:', selectedPrice);
                } else if (product.whole_sale_price && parseFloat(product.whole_sale_price) > 0) {
                    selectedPrice = parseFloat(product.whole_sale_price);
                    priceSource = 'product_wholesale_price';
                    console.log('Using product wholesale price:', selectedPrice, 'from field whole_sale_price');
                } else if (product.special_price && parseFloat(product.special_price) > 0) {
                    selectedPrice = parseFloat(product.special_price);
                    priceSource = 'product_special_price';
                    console.log('Using product special price:', selectedPrice);
                } else if (product.retail_price && parseFloat(product.retail_price) > 0) {
                    selectedPrice = parseFloat(product.retail_price);
                    priceSource = 'product_retail_price';
                    console.log('Using product retail price as fallback:', selectedPrice);
                } else if (product.max_retail_price && parseFloat(product.max_retail_price) > 0) {
                    selectedPrice = parseFloat(product.max_retail_price);
                    priceSource = 'product_max_retail_price';
                }
            } else {
                // Retailer pricing logic (default)
                if (batch && batch.retail_price && parseFloat(batch.retail_price) > 0) {
                    selectedPrice = parseFloat(batch.retail_price);
                    priceSource = 'batch_retail_price';
                } else if (batch && batch.special_price && parseFloat(batch.special_price) > 0) {
                    selectedPrice = parseFloat(batch.special_price);
                    priceSource = 'batch_special_price';
                } else if (batch && batch.max_retail_price && parseFloat(batch.max_retail_price) > 0) {
                    selectedPrice = parseFloat(batch.max_retail_price);
                    priceSource = 'batch_max_retail_price';
                } else if (product.retail_price && parseFloat(product.retail_price) > 0) {
                    selectedPrice = parseFloat(product.retail_price);
                    priceSource = 'product_retail_price';
                } else if (product.special_price && parseFloat(product.special_price) > 0) {
                    selectedPrice = parseFloat(product.special_price);
                    priceSource = 'product_special_price';
                } else if (product.max_retail_price && parseFloat(product.max_retail_price) > 0) {
                    selectedPrice = parseFloat(product.max_retail_price);
                    priceSource = 'product_max_retail_price';
                }
            }

            console.log('Selected price:', selectedPrice, 'from:', priceSource);

            // Validate price is not zero
            if (selectedPrice <= 0) {
                console.error('No valid price found for product:', product.product_name, 'customer type:',
                    customerType);
                logPricingError(product, customerType, batch);
                return {
                    price: 0,
                    source: 'error',
                    hasError: true
                };
            }

            return {
                price: selectedPrice,
                source: priceSource,
                hasError: false
            };
        }

        /**
         * Log pricing errors for admin review
         */
        function logPricingError(product, customerType, batch) {
            const errorData = {
                product_id: product.id,
                product_name: product.product_name,
                customer_type: customerType,
                batch_id: batch ? batch.id : null,
                batch_no: batch ? batch.batch_no : null,
                timestamp: new Date().toISOString(),
                location_id: selectedLocationId
            };

            // Send error to backend for logging
            fetch('/pos/log-pricing-error', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(errorData)
            }).catch(error => {
                console.error('Failed to log pricing error:', error);
            });

            console.error('Pricing Error Logged:', errorData);
        }

        /**
         * Update all existing billing rows with new pricing based on customer type
         */
        function updateAllBillingRowsPricing(newCustomerType) {
            // Skip price updates during edit mode to preserve original sale data
            if (isEditing) {
                console.log('Edit mode active: Skipping automatic price updates to preserve original sale data');
                return;
            }
            
            const billingBody = document.getElementById('billing-body');
            const existingRows = billingBody ? billingBody.querySelectorAll('tr') : [];

            if (existingRows.length === 0) {
                console.log('No existing billing rows to update');
                return;
            }

            console.log(`Updating ${existingRows.length} billing rows for customer type: ${newCustomerType}`);

            existingRows.forEach((row) => {
                try {
                    // Extract product data from row attributes
                    const productId = row.getAttribute('data-product-id');
                    const batchId = row.getAttribute('data-batch-id');

                    if (!productId) {
                        console.warn('Product ID not found in billing row, skipping update');
                        return;
                    }

                    // Get product data from row or fetch from available data
                    const productData = getProductDataById(productId);
                    const batchData = batchId ? getBatchDataById(batchId) : null;

                    if (!productData) {
                        console.warn(`Product data not found for ID: ${productId}, skipping update`);
                        return;
                    }

                    // Calculate new price based on customer type
                    const pricingResult = getCustomerTypePrice(batchData, productData, newCustomerType);

                    console.log(`Pricing result for ${productData.product_name}:`, pricingResult);

                    if (pricingResult.hasError || pricingResult.price <= 0) {
                        console.warn(
                            `Invalid price calculated for product ${productData.product_name}: ${pricingResult.price}`
                        );
                        return;
                    }

                    // Update the price and discount in the billing row
                    updateBillingRowPrice(row, pricingResult.price, pricingResult.source);

                    console.log(
                        `Updated ${productData.product_name}: Price ₹${pricingResult.price} (${pricingResult.source}) with auto-calculated discount`
                    );
                } catch (error) {
                    console.error('Error updating billing row pricing:', error);
                }
            });

            // Recalculate total after all price updates
            updateTotals();

            console.log('All billing rows updated successfully');
        }

        /**
         * Update the price in a specific billing row with dynamic discount calculation
         */
        function updateBillingRowPrice(row, newPrice, priceSource) {
            // Find the price input field
            const priceInput = row.querySelector('.price-input.unit-price');
            const quantityInput = row.querySelector('.quantity-input');
            const totalCell = row.querySelector('.total-price');

            // Find discount fields
            const fixedDiscountInput = row.querySelector('.fixed_discount');
            const percentDiscountInput = row.querySelector('.percent_discount');

            // Get MRP from price input data attribute or calculate it
            let mrp = 0;
            if (priceInput) {
                mrp = parseFloat(priceInput.getAttribute('data-max-retail-price')) || 0;
            }

            // If MRP not found, try to get it from product data
            if (mrp === 0) {
                const productId = row.getAttribute('data-product-id');
                const productData = getProductDataById(productId);
                if (productData && productData.max_retail_price) {
                    mrp = parseFloat(productData.max_retail_price);
                }
            }

            if (priceInput) {
                // Update the input value and data attribute
                priceInput.value = parseFloat(newPrice).toFixed(2);
                priceInput.setAttribute('data-price', newPrice);
            }

            // Calculate new discount values based on MRP and new price
            if (mrp > 0) {
                const newFixedDiscount = mrp - newPrice;
                const newPercentDiscount = (newFixedDiscount / mrp) * 100;

                // Update fixed discount field
                if (fixedDiscountInput) {
                    fixedDiscountInput.value = newFixedDiscount.toFixed(2);
                }

                // Update percentage discount field
                if (percentDiscountInput) {
                    percentDiscountInput.value = newPercentDiscount.toFixed(2);
                }

                console.log(
                    `Updated discounts - Fixed: ₹${newFixedDiscount.toFixed(2)}, Percentage: ${newPercentDiscount.toFixed(2)}%`
                );
            } else {
                console.warn('MRP not found, unable to calculate discount');
            }

            // Update total if quantity cell exists
            if (quantityInput && totalCell) {
                const quantity = parseFloat(quantityInput.value || 1);
                const newTotal = (newPrice * quantity).toFixed(2);
                totalCell.textContent = formatAmountWithSeparators(newTotal);

                // Update data attribute if exists
                totalCell.setAttribute('data-total', newTotal);
            }

            // Update row's price data attributes
            row.setAttribute('data-unit-price', newPrice);
            row.setAttribute('data-price-source', priceSource);
        }

        /**
         * Get product data by ID from available sources
         */
        function getProductDataById(productId) {
            if (!productId) return null;

            // Try to find product in allProducts array first with null checks
            let product = null;

            if (allProducts && Array.isArray(allProducts)) {
                product = allProducts.find(p => p && p.id && p.id == productId);
            }

            if (!product && stockData && Array.isArray(stockData)) {
                // Try to find in stockData if available
                const stockEntry = stockData.find(s => s && s.product && s.product.id && s.product.id ==
                    productId);
                if (stockEntry && stockEntry.product) {
                    product = stockEntry.product;
                }
            }

            return product;
        }

        /**
         * Get batch data by ID from available sources
         */
        function getBatchDataById(batchId) {
            if (!batchId) return null;

            // Search through all products' batches
            for (const product of allProducts) {
                if (product.batches && Array.isArray(product.batches)) {
                    const batch = product.batches.find(b => b.id == batchId);
                    if (batch) return batch;
                }
            }

            // Search through stockData
            for (const stockEntry of stockData) {
                if (stockEntry.batches && Array.isArray(stockEntry.batches)) {
                    const batch = stockEntry.batches.find(b => b.id == batchId);
                    if (batch) return batch;
                }
            }

            return null;
        }

        // ---- SALES REP FUNCTIONS ----
        function restoreSalesRepDisplayFromStorage() {
            // Only restore if user is confirmed as sales rep
            if (!isSalesRep) {
                console.log('User is not a sales rep, skipping display restoration');
                return;
            }

            // Early restoration to prevent display flicker on page refresh
            const storedSelection = getSalesRepSelection();
            if (storedSelection && storedSelection.vehicle && storedSelection.route) {
                console.log('Restoring sales rep display from storage on page load:', storedSelection);

                // Set flag to indicate we have a valid stored selection
                window.hasStoredSalesRepSelection = true;

                // Use setTimeout to ensure DOM elements are available
                setTimeout(() => {
                    updateSalesRepDisplay(storedSelection);

                    // Also apply customer filtering immediately
                    setTimeout(() => {
                        filterCustomersByRoute(storedSelection);

                        // Validate customers after filtering with longer delay and only once
                        setTimeout(() => {
                            if (!window.validationPerformed) {
                                window.validationPerformed = true;
                                validateCustomerRouteMatch();
                            }
                        }, 2000); // Increased delay to ensure filtering completes
                    }, 300);
                }, 100);

                // Store the selection again to ensure it's fresh in both storages
                storeSalesRepSelection(storedSelection);
            } else {
                window.hasStoredSalesRepSelection = false;
                console.log('No valid stored selection found for early restoration');
            }
        }

        function getSalesRepSelection() {
            try {
                // First check sessionStorage (current session)
                let storedData = sessionStorage.getItem('salesRepSelection');
                let parsedData = storedData ? JSON.parse(storedData) : null;

                if (!parsedData) {
                    storedData = localStorage.getItem('salesRepSelection');
                    parsedData = storedData ? JSON.parse(storedData) : null;

                    // If found in localStorage, also store in sessionStorage for current session
                    if (parsedData) {
                        sessionStorage.setItem('salesRepSelection', JSON.stringify(parsedData));
                        console.log('Restored sales rep selection from localStorage to sessionStorage');
                    }
                }

                return parsedData;
            } catch (e) {
                console.warn('Error parsing sales rep selection from storage:', e);
                return null;
            }
        }

        function hasSalesRepSelection() {
            const selection = getSalesRepSelection();
            const isValid = selection &&
                selection.vehicle &&
                selection.route &&
                selection.vehicle.id &&
                selection.route.id;
            return isValid;
        }

        function protectSalesRepCustomerFiltering() {
            let debounceTimer = null;
            let lastFilterTime = 0;
            const FILTER_COOLDOWN = 3000; // 3 seconds cooldown between filters

            // Monitor when new options are added to the customer dropdown
            const observer = new MutationObserver(function(mutations) {
                // Prevent too frequent filtering
                const now = Date.now();
                if (now - lastFilterTime < FILTER_COOLDOWN) {
                    console.log('Customer filtering on cooldown, skipping...');
                    return;
                }

                // Debounce to prevent rapid repeated calls
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }

                debounceTimer = setTimeout(() => {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList' && isSalesRep && !
                            filteringInProgress) {
                            const customerSelect = $('#customer-id');
                            const options = customerSelect.find('option');

                            // Only process if we have meaningful changes
                            if (options.length <= 1) return;

                            // Check if this is an unwanted change (e.g., Walk-in Customer present for sales reps)
                            const hasWalkIn = options.filter(function() {
                                return $(this).text().toLowerCase().includes(
                                    'walk-in');
                            }).length > 0;

                            // Check if we have customers from wrong route
                            const selection = getSalesRepSelection();
                            let hasWrongRouteCustomers = false;

                            if (selection && selection.route && selection.route.name) {
                                const selectedRouteName = selection.route.name
                                    .toLowerCase();

                                // Check if customers from other routes are present
                                options.each(function() {
                                    const optionText = $(this).text()
                                        .toLowerCase();
                                    if (optionText !== 'please select' &&
                                        !optionText.includes('walk-in') &&
                                        !optionText.includes(
                                            selectedRouteName) &&
                                        (optionText.includes('kalmunai') ||
                                            optionText.includes('retailer'))) {
                                        hasWrongRouteCustomers = true;
                                        return false; // Break out of each loop
                                    }
                                });
                            }

                            // Only trigger if there's actually an issue
                            if ((salesRepCustomersFiltered && (hasWalkIn ||
                                    hasWrongRouteCustomers)) ||
                                (!salesRepCustomersFiltered && selection && (
                                    hasWalkIn || hasWrongRouteCustomers))) {

                                console.log(
                                    'Customer filtering needed - applying route-based filter'
                                );
                                lastFilterTime = Date.now(); // Update last filter time

                                if (selection) {
                                    filterCustomersByRoute(selection);
                                }
                            }
                        }
                    });
                }, 500); // 500ms debounce
            });

            const customerDropdown = document.getElementById('customer-id');
            if (customerDropdown) {
                observer.observe(customerDropdown, {
                    childList: true,
                    subtree: true
                });
                console.log('Customer filtering protection activated');
            }
        }

        function checkSalesRepStatus() {
            console.log('Checking sales rep status...');

            // Check if user has sales rep role
            fetch('/sales-rep/my-assignments', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Sales rep API response status:', response.status);
                    return response.json().then(data => ({
                        status: response.status,
                        data
                    }));
                })
                .then(({
                    status,
                    data
                }) => {
                    console.log('Sales rep API response:', {
                        status,
                        data
                    });

                    if (status === 200 && data.status === true && data.data && data.data.length > 0) {
                        // User is a sales rep with assignments
                        isSalesRep = true;
                        console.log('User identified as sales rep with assignments:', data.data);
                        // Early restoration of sales rep display from storage (only for sales rep)
                        restoreSalesRepDisplayFromStorage();
                        handleSalesRepUser(data.data);
                    } else if (status === 200 && data.status === false) {
                        // User is not a sales rep (explicit response)
                        isSalesRep = false;
                        hideSalesRepDisplay();
                        console.log('User is not a sales rep (API confirmed)');
                    } else if (status === 200 && data.status === true && (!data.data || data.data.length ===
                            0)) {
                        // User is a sales rep but no assignments
                        isSalesRep = true;
                        console.log('User is a sales rep but has no assignments');
                        hideSalesRepDisplay(); // Hide display if no assignments
                    } else {
                        // Other cases - treat as non-sales rep
                        isSalesRep = false;
                        hideSalesRepDisplay();
                        console.log('User is not a sales rep (other case)');
                    }
                })
                .catch(error => {
                    // User is not a sales rep or error occurred, proceed normally
                    isSalesRep = false;
                    hideSalesRepDisplay();
                    console.log('Not a sales rep or error:', error);
                });
        }

        function handleSalesRepUser(assignments) {
            console.log('Handling sales rep user with assignments:', assignments);

            // Store assignments globally for modal
            window.salesRepAssignments = assignments;

            // Ensure sales rep display is ready to be shown (remove any hiding)
            const salesRepDisplay = document.getElementById('salesRepDisplay');
            if (salesRepDisplay) {
                salesRepDisplay.classList.remove('d-none');
                console.log('Sales rep display prepared for showing');
            }

            // Check if we already have a valid selection
            if (!hasSalesRepSelection()) {
                console.log('No existing sales rep selection, showing modal');
                // Show modal for vehicle/route selection
                if (typeof showSalesRepModal === 'function') {
                    showSalesRepModal();
                } else {
                    console.error('showSalesRepModal function not found');
                }
            } else {
                console.log('Found existing sales rep selection, validating...');
                // Validate existing selection against current assignments
                const selection = getSalesRepSelection();

                // Check if we already restored the display early
                if (window.hasStoredSalesRepSelection) {
                    console.log('Display already restored early, just validating and updating if needed');
                }

                // More robust validation - check if selection has required properties
                let validAssignment = null;
                if (selection && selection.vehicle && selection.route &&
                    selection.vehicle.id && selection.route.id) {

                    // First try exact match
                    validAssignment = assignments.find(a =>
                        a.sub_location && a.route &&
                        a.sub_location.id === selection.vehicle.id &&
                        a.route.id === selection.route.id
                    );

                    // If no exact match, try to find by vehicle ID only (in case route data differs)
                    if (!validAssignment) {
                        validAssignment = assignments.find(a =>
                            a.sub_location && a.sub_location.id === selection.vehicle.id
                        );
                    }
                }

                if (validAssignment) {
                    console.log('Valid assignment found, updating display');
                    // Update selection with current assignment data but preserve existing route selection
                    const updatedSelection = {
                        ...selection, // Preserve existing selection
                        canSell: validAssignment.can_sell || selection.canSell || true
                    };
                    storeSalesRepSelection(updatedSelection);

                    // Only update display if not already restored early
                    if (!window.hasStoredSalesRepSelection) {
                        updateSalesRepDisplay(updatedSelection);
                    } else {
                        // Just ensure the display has the correct data
                        console.log('Display already restored, ensuring correct data is shown');
                        setTimeout(() => updateSalesRepDisplay(updatedSelection), 100);
                    }

                    restrictLocationAccess(updatedSelection);
                    // Apply customer filtering after a short delay
                    setTimeout(() => {
                        filterCustomersByRoute(updatedSelection);
                    }, 1000); // Increased delay to ensure all other operations complete
                } else {
                    console.log(
                        'No exact assignment match found, but selection exists - attempting to preserve selection'
                    );
                    // Instead of immediately clearing, try to use the existing selection
                    // This handles cases where the backend data structure might have changed slightly
                    if (selection && selection.vehicle && selection.route) {
                        console.log('Preserving existing selection despite validation failure');
                        // Set a default canSell value if not present
                        if (typeof selection.canSell === 'undefined') {
                            selection.canSell = true; // Default to allow sales
                        }

                        // Check if the vehicle at least exists in assignments (more lenient check)
                        const vehicleExists = assignments.some(a =>
                            a.sub_location && a.sub_location.id === selection.vehicle.id
                        );

                        if (vehicleExists) {
                            console.log('Vehicle found in assignments, preserving selection');
                            // Try to update display with existing selection
                            try {
                                // Only update display if not already restored early
                                if (!window.hasStoredSalesRepSelection) {
                                    updateSalesRepDisplay(selection);
                                } else {
                                    console.log(
                                        'Display already restored early, just ensuring customer filtering');
                                }

                                restrictLocationAccess(selection);
                                // Apply customer filtering after a short delay
                                setTimeout(() => {
                                    filterCustomersByRoute(selection);
                                }, 1000); // Increased delay
                                console.log('Successfully preserved and applied existing selection');
                            } catch (error) {
                                console.error('Error applying preserved selection:', error);
                                // Only clear and show modal if there's an actual error
                                clearSalesRepSelection();
                                if (typeof showSalesRepModal === 'function') {
                                    showSalesRepModal();
                                }
                            }
                        } else {
                            console.log('Vehicle not found in current assignments, showing modal');
                            clearSalesRepSelection();
                            if (typeof showSalesRepModal === 'function') {
                                showSalesRepModal();
                            }
                        }
                    } else {
                        console.log('Selection is invalid or incomplete, showing modal');
                        clearSalesRepSelection();
                        if (typeof showSalesRepModal === 'function') {
                            showSalesRepModal();
                        } else {
                            console.error('showSalesRepModal function not found');
                        }
                    }
                }
            }

            // Set up event listeners
            setupSalesRepEventListeners();
        }

        function setupSalesRepEventListeners() {
            console.log('Setting up sales rep event listeners');

            // Listen for selection confirmation
            window.addEventListener('salesRepSelectionConfirmed', function(event) {
                console.log('Sales rep selection confirmed event received:', event.detail);
                const selection = event.detail;
                salesRepCustomersFiltered = false; // Reset flag for new selection
                salesRepCustomersLoaded = false; // Reset loaded flag to allow fresh filtering
                updateSalesRepDisplay(selection);
                restrictLocationAccess(selection);

                // Filter customers but don't auto-select
                setTimeout(() => {
                    filterCustomersByRoute(selection);
                }, 500);
            });

            // Change selection button (Desktop)
            const changeBtnElement = document.getElementById('changeSalesRepSelection');
            if (changeBtnElement) {
                changeBtnElement.addEventListener('click', function() {
                    console.log('Change sales rep selection button clicked');
                    if (typeof showSalesRepModal === 'function') {
                        showSalesRepModal();
                    } else {
                        console.error('showSalesRepModal function not available');
                    }
                });
                console.log('Change selection button listener added');
            } else {
                console.log('Change selection button not found, will try again later');
                // Try again after a delay to ensure DOM is ready
                setTimeout(() => {
                    const delayedBtn = document.getElementById('changeSalesRepSelection');
                    if (delayedBtn) {
                        delayedBtn.addEventListener('click', function() {
                            console.log('Change sales rep selection button clicked (delayed)');
                            if (typeof showSalesRepModal === 'function') {
                                showSalesRepModal();
                            }
                        });
                        console.log('Change selection button listener added (delayed)');
                    }
                }, 1000);
            }

            // Change selection button (Mobile Menu)
            const changeBtnMenuElement = document.getElementById('changeSalesRepSelectionMenu');
            if (changeBtnMenuElement) {
                changeBtnMenuElement.addEventListener('click', function() {
                    console.log('Change sales rep selection button clicked (mobile menu)');
                    // Close the mobile menu modal first
                    const mobileMenuModal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
                    if (mobileMenuModal) {
                        mobileMenuModal.hide();
                    }
                    // Then show the sales rep modal
                    setTimeout(() => {
                        if (typeof showSalesRepModal === 'function') {
                            showSalesRepModal();
                        } else {
                            console.error('showSalesRepModal function not available');
                        }
                    }, 300);
                });
                console.log('Change selection button listener added (mobile menu)');
            } else {
                console.log('Change selection button (mobile menu) not found, will try again later');
                // Try again after a delay to ensure DOM is ready
                setTimeout(() => {
                    const delayedBtnMenu = document.getElementById('changeSalesRepSelectionMenu');
                    if (delayedBtnMenu) {
                        delayedBtnMenu.addEventListener('click', function() {
                            console.log('Change sales rep selection button clicked (mobile menu - delayed)');
                            const mobileMenuModal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
                            if (mobileMenuModal) {
                                mobileMenuModal.hide();
                            }
                            setTimeout(() => {
                                if (typeof showSalesRepModal === 'function') {
                                    showSalesRepModal();
                                }
                            }, 300);
                        });
                        console.log('Change selection button listener added (mobile menu - delayed)');
                    }
                }, 1000);
            }

            // Add page visibility change listener to restore display when returning to tab
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && isSalesRep) {
                    const selection = getSalesRepSelection();
                    if (selection && selection.vehicle && selection.route) {
                        console.log('Page became visible, ensuring sales rep display is correct');
                        setTimeout(() => {
                            updateSalesRepDisplay(selection);
                        }, 200);
                    }
                }
            });

            // Add beforeunload listener to ensure selection is saved
            window.addEventListener('beforeunload', function() {
                const selection = getSalesRepSelection();
                if (selection && isSalesRep) {
                    // Re-save to ensure persistence
                    storeSalesRepSelection(selection);
                }
            });
        }

        function updateSalesRepDisplay(selection) {
            console.log('Updating sales rep display with selection:', selection);

            const salesRepDisplay = document.getElementById('salesRepDisplay');
            const selectedVehicleDisplay = document.getElementById('selectedVehicleDisplay');
            const selectedRouteDisplay = document.getElementById('selectedRouteDisplay');
            const salesAccessBadge = document.getElementById('salesAccessBadge');
            const salesAccessText = document.getElementById('salesAccessText');

            if (!salesRepDisplay) {
                console.error('Sales rep display element not found, retrying...');
                // Retry after DOM elements are fully loaded
                setTimeout(() => updateSalesRepDisplay(selection), 500);
                return;
            }

            if (!selectedVehicleDisplay || !selectedRouteDisplay) {
                console.error('Sales rep display child elements not found, retrying...');
                setTimeout(() => updateSalesRepDisplay(selection), 500);
                return;
            }

            // Update display text with proper fallbacks
            selectedVehicleDisplay.textContent = selection.vehicle && selection.vehicle.name ?
                `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})` : 'Unknown Vehicle';
            selectedRouteDisplay.textContent = selection.route && selection.route.name ?
                selection.route.name : 'Unknown Route';

            if (salesAccessBadge && salesAccessText) {
                if (selection.canSell) {
                    salesAccessBadge.className = 'badge bg-success text-white p-2';
                    salesAccessText.textContent = 'Sales Allowed';
                } else {
                    salesAccessBadge.className = 'badge bg-warning text-dark p-2';
                    salesAccessText.textContent = 'View Only';
                }
            }

            // Show the display with proper flex styling and special class
            salesRepDisplay.style.display = 'flex';
            salesRepDisplay.classList.add('d-flex', 'sales-rep-visible');
            salesRepDisplay.classList.remove('d-none');

            // Also update mobile menu display
            const salesRepDisplayMenu = document.getElementById('salesRepDisplayMenu');
            const selectedVehicleDisplayMenu = document.getElementById('selectedVehicleDisplayMenu');
            const selectedRouteDisplayMenu = document.getElementById('selectedRouteDisplayMenu');
            const salesAccessBadgeMenu = document.getElementById('salesAccessBadgeMenu');

            console.log('Mobile menu elements found:', {
                salesRepDisplayMenu: !!salesRepDisplayMenu,
                selectedVehicleDisplayMenu: !!selectedVehicleDisplayMenu,
                selectedRouteDisplayMenu: !!selectedRouteDisplayMenu,
                salesAccessBadgeMenu: !!salesAccessBadgeMenu
            });

            if (salesRepDisplayMenu && selectedVehicleDisplayMenu && selectedRouteDisplayMenu && salesAccessBadgeMenu) {
                // Prepare vehicle text
                const vehicleText = selection.vehicle && selection.vehicle.name ?
                    `${selection.vehicle.name} (${selection.vehicle.vehicle_number || 'N/A'})` : 'Unknown Vehicle';
                const routeText = selection.route && selection.route.name ?
                    selection.route.name : 'Unknown Route';

                console.log('Updating mobile menu with:', {
                    vehicle: vehicleText,
                    route: routeText,
                    canSell: selection.canSell
                });

                // Update mobile menu text
                selectedVehicleDisplayMenu.textContent = vehicleText;
                selectedRouteDisplayMenu.textContent = routeText;

                // Update access badge for mobile
                if (selection.canSell) {
                    salesAccessBadgeMenu.className = 'badge bg-success';
                    salesAccessBadgeMenu.textContent = 'Sales Allowed';
                } else {
                    salesAccessBadgeMenu.className = 'badge bg-warning text-dark';
                    salesAccessBadgeMenu.textContent = 'View Only';
                }

                // Show mobile menu display
                salesRepDisplayMenu.style.display = 'block';
                
                console.log('Mobile menu sales rep display updated and shown');
                console.log('Mobile menu display style:', salesRepDisplayMenu.style.display);
                console.log('Vehicle text set to:', selectedVehicleDisplayMenu.textContent);
                console.log('Route text set to:', selectedRouteDisplayMenu.textContent);
            } else {
                console.error('One or more mobile menu elements not found!');
            }

            console.log('Sales rep display updated and made visible with data:', {
                vehicle: selection.vehicle?.name,
                route: selection.route?.name,
                canSell: selection.canSell
            });

            // Store the selection to localStorage for better persistence across page refreshes
            try {
                localStorage.setItem('salesRepSelection', JSON.stringify(selection));
                console.log('Sales rep selection stored in localStorage for persistence');
            } catch (e) {
                console.warn('Failed to store selection in localStorage:', e);
            }

            // Filter customers only if not already loaded for this session
            if (!salesRepCustomersLoaded) {
                setTimeout(() => {
                    filterCustomersByRoute(selection);
                }, 500);
            } else {
                console.log('Customers already loaded, skipping filter in updateSalesRepDisplay');
            }
        }

        function restrictLocationAccess(selection) {
            console.log('Restricting location access to vehicle:', selection.vehicle);

            // Override the fetchAllLocations to only include assigned vehicle
            const originalFetchLocations = window.fetchAllLocations;

            window.fetchAllLocations = function() {
                // Create a mock response with only the assigned vehicle
                const mockResponse = {
                    status: true,
                    data: [selection.vehicle]
                };
                populateLocationDropdown(mockResponse.data);

                // Auto-select the vehicle in the dropdown with multiple retries
                const selectVehicle = () => {
                    const locationSelect = document.getElementById('locationSelect');
                    if (locationSelect) {
                        locationSelect.value = selection.vehicle.id;
                        $(locationSelect).trigger('change');
                        console.log('Vehicle auto-selected:', selection.vehicle.id);
                    } else {
                        // Retry if dropdown not ready
                        setTimeout(selectVehicle, 200);
                    }
                };

                setTimeout(selectVehicle, 100);
            };

            // Re-fetch locations with restriction
            window.fetchAllLocations();

            // Also set the location immediately if dropdown already exists
            setTimeout(() => {
                const locationSelect = document.getElementById('locationSelect');
                if (locationSelect && !locationSelect.value) {
                    locationSelect.value = selection.vehicle.id;
                    $(locationSelect).trigger('change');
                    console.log('Vehicle set directly on existing dropdown');
                }
            }, 300);
        }

        let filteringInProgress = false;

        function filterCustomersByRoute(selection) {
            if (filteringInProgress) {
                console.log('Filtering already in progress, skipping...');
                return;
            }

            // Check if customers already loaded for this session
            if (salesRepCustomersLoaded) {
                console.log('Customers already loaded for this sales rep session, skipping filter');
                return;
            }

            if (!selection || !selection.route) {
                console.log('No valid selection or route provided for filtering');
                return;
            }

            filteringInProgress = true;
            console.log('Filtering customers for route:', selection.route.name, 'with cities:', selection.route
                .cities);

            if (!selection.route.cities || selection.route.cities.length === 0) {
                console.log('No cities found for selected route, trying fallback filtering');
                // Fallback: try to filter by route name pattern
                fallbackRouteFiltering(selection);
                filteringInProgress = false;
                return;
            }

            // Get the route cities (IDs instead of names)
            const routeCityIds = selection.route.cities.map(city => city.id);
            console.log('Route city IDs:', routeCityIds);

            // Get customer dropdown with retry logic
            const customerSelect = $('#customer-id');
            if (!customerSelect.length) {
                console.log('Customer dropdown not found, retrying in 200ms...');
                filteringInProgress = false;
                setTimeout(() => filterCustomersByRoute(selection), 200);
                return;
            }

            // Check if dropdown is properly initialized (has options)
            const existingOptions = customerSelect.find('option');
            if (existingOptions.length === 0) {
                console.log('Customer dropdown not initialized yet, retrying in 200ms...');
                filteringInProgress = false;
                setTimeout(() => filterCustomersByRoute(selection), 200);
                return;
            }

            // Store original options if not already stored
            if (!window.originalCustomerOptions) {
                window.originalCustomerOptions = customerSelect.html();
                console.log('Stored original customer options for backup');
            }

            // Filter customers based on city
            fetch('/customers/filter-by-cities', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        city_ids: routeCityIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Filter customers response:', data);
                    if (data.status && data.customers) {
                        populateFilteredCustomers(data.customers, selection.route.name);
                        salesRepCustomersFiltered = true; // Mark that filtering has been applied
                        salesRepCustomersLoaded = true; // Mark that customers are loaded for this session
                        console.log('Customer filtering completed successfully for route:', selection.route
                            .name);
                    } else {
                        console.error('Failed to filter customers:', data.message || 'Unknown error');
                        // Fallback to route name filtering
                        fallbackRouteFiltering(selection);
                    }
                    filteringInProgress = false; // Reset flag
                })
                .catch(error => {
                    console.error('Error filtering customers:', error);
                    // Fallback to route name filtering
                    fallbackRouteFiltering(selection);
                    filteringInProgress = false; // Reset flag
                });
        }

        function fallbackRouteFiltering(selection) {
            console.log('Using fallback route name filtering for:', selection.route.name);
            const customerSelect = $('#customer-id');
            const routeName = selection.route.name.toLowerCase();

            // Get all options and filter by route name
            const filteredOptions = [];

            if (window.originalCustomerOptions) {
                const tempDiv = $('<div>').html(window.originalCustomerOptions);
                tempDiv.find('option').each(function() {
                    const optionText = $(this).text().toLowerCase();
                    const optionValue = $(this).val();

                    // Always include "Please select" option
                    if (optionValue === '' || optionText.includes('please select')) {
                        filteredOptions.push($(this)[0].outerHTML);
                    }
                    // Include options that match the route name
                    else if (optionText.includes(routeName) ||
                        (routeName.includes('sainthasmaruthu') && optionText.includes(
                            'sainthasmaruthu')) ||
                        (routeName.includes('kalmunai') && optionText.includes('kalmunai'))) {
                        filteredOptions.push($(this)[0].outerHTML);
                    }
                });

                if (filteredOptions.length > 1) { // More than just "Please select"
                    customerSelect.html(filteredOptions.join(''));
                    console.log('Fallback filtering applied, found', filteredOptions.length - 1,
                        'customers for route');
                } else {
                    console.log('No customers found in fallback filtering, keeping original options');
                }
            }

            salesRepCustomersFiltered = true;
            salesRepCustomersLoaded = true; // Mark that customers are loaded
        }

        function populateFilteredCustomers(customers, routeName = '') {
            const customerSelect = $('#customer-id');

            // Validate customers parameter
            if (!customers || !Array.isArray(customers)) {
                console.error('populateFilteredCustomers: customers parameter is not a valid array:',
                    customers);
                restoreOriginalCustomers();
                return;
            }

            console.log(`Populating ${customers.length} filtered customers for route: ${routeName}`);

            // Clear existing options
            customerSelect.empty();

            // Add "Please select" option first
            customerSelect.append('<option value="">Please Select</option>');

            // Don't add Walk-In Customer for sales reps
            if (!isSalesRep) {
                const walkInOption = $(
                    '<option value="1" data-customer-type="retailer">Walk-in Customer (Walk-in Customer)</option>'
                );
                walkInOption.data('due', 0);
                walkInOption.data('credit_limit', 0);
                customerSelect.append(walkInOption);
            }

            // Sort customers alphabetically by name
            customers.sort((a, b) => {
                const nameA = [a.prefix, a.first_name, a.last_name].filter(Boolean).join(' ')
                    .toLowerCase();
                const nameB = [b.prefix, b.first_name, b.last_name].filter(Boolean).join(' ')
                    .toLowerCase();
                return nameA.localeCompare(nameB);
            });

            // Separate customers with and without cities for better organization
            const customersWithCity = customers.filter(c => c.city_name && c.city_name !== 'No City');
            const customersWithoutCity = customers.filter(c => !c.city_name || c.city_name === 'No City');

            // Add customers with cities first
            customersWithCity.forEach(customer => {
                const customerName = [customer.prefix, customer.first_name, customer.last_name]
                    .filter(Boolean).join(' ');
                const customerType = customer.customer_type ?
                    ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}` :
                    '';
                const cityInfo = ` [${customer.city_name}]`;
                const displayText =
                    `${customerName}${customerType}${cityInfo} (${customer.mobile || 'No mobile'})`;
                const option = $(
                    `<option value="${customer.id}" data-customer-type="${customer.customer_type || 'retailer'}">${displayText}</option>`
                );
                option.data('due', customer.current_due || 0);
                option.data('credit_limit', customer.credit_limit || 0);
                customerSelect.append(option);
            });

            // Add separator if there are customers without cities
            if (customersWithoutCity.length > 0 && customersWithCity.length > 0) {
                customerSelect.append('<option disabled>── Customers without city ──</option>');
            }

            // Add customers without cities
            customersWithoutCity.forEach(customer => {
                const customerName = [customer.prefix, customer.first_name, customer.last_name]
                    .filter(Boolean).join(' ');
                const customerType = customer.customer_type ?
                    ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}` :
                    '';
                const cityInfo = ' [No City]';
                const displayText =
                    `${customerName}${customerType}${cityInfo} (${customer.mobile || 'No mobile'})`;
                const option = $(
                    `<option value="${customer.id}" data-customer-type="${customer.customer_type || 'retailer'}">${displayText}</option>`
                );
                option.data('due', customer.current_due || 0);
                option.data('credit_limit', customer.credit_limit || 0);
                customerSelect.append(option);
            });

            // Refresh Select2 and trigger change event to update due/credit display
            customerSelect.trigger('change');

            // Auto-select appropriate customer based on user type
            setTimeout(() => {
                if (isSalesRep) {
                    // For sales reps, DO NOT auto-select any customer
                    // Keep the dropdown at "Please Select" so user must choose
                    customerSelect.val('').trigger('change');
                    console.log('Sales rep: Customer dropdown ready - user must select a customer');
                } else {
                    // For non-sales reps, select Walk-in customer
                    customerSelect.val('1').trigger('change');
                }
            }, 100);


            // Show info message with breakdown
            if (typeof toastr !== 'undefined' && routeName) {
                const withCityCount = customersWithCity.length;
                const withoutCityCount = customersWithoutCity.length;
                const totalCount = customers.length;

                let message = `Showing ${totalCount} customers from your route`;
                if (withoutCityCount > 0) {
                    message += ` (${withCityCount} with city, ${withoutCityCount} without city)`;
                }

            }
        }

        function validateCustomerRouteMatch() {
            // Function to validate that displayed customers match the selected route
            const selection = getSalesRepSelection();
            if (!selection || !selection.route || !isSalesRep) return;

            // Prevent validation during active filtering
            if (filteringInProgress) {
                console.log('Filtering in progress, skipping validation');
                return;
            }

            const customerSelect = $('#customer-id');
            const options = customerSelect.find('option');
            const routeName = selection.route.name.toLowerCase();

            let correctCustomers = 0;
            let wrongRouteCustomers = 0;

            options.each(function() {
                const optionText = $(this).text().toLowerCase();
                const optionValue = $(this).val();

                // Skip "Please Select" option
                if (!optionValue || optionText.includes('please select')) return;

                if (optionText.includes(routeName) ||
                    (routeName.includes('sainthasmaruthu') && optionText.includes('sainthasmaruthu')) ||
                    (routeName.includes('kalmunai') && optionText.includes('kalmunai'))) {
                    correctCustomers++;
                } else if (!optionText.includes('walk-in')) {
                    wrongRouteCustomers++;
                }
            });

            console.log(
                `Customer validation for route ${selection.route.name}: ${correctCustomers} correct, ${wrongRouteCustomers} wrong route`
            );

            if (wrongRouteCustomers > 0 && !salesRepCustomersFiltered) {
                setTimeout(() => filterCustomersByRoute(selection), 500);
            }
        }

        function restoreOriginalCustomers() {
            if (window.originalCustomerOptions) {
                const customerSelect = $('#customer-id');
                customerSelect.html(window.originalCustomerOptions);
                customerSelect.trigger('change');

                // Auto-select Walk-in customer and update displays
                setTimeout(() => {
                    customerSelect.val('1').trigger('change');
                }, 100);
            }
        }

        // Function to handle when sales rep selection is cleared
        function clearSalesRepFilters() {
            // Restore original customer list
            restoreOriginalCustomers();

            // Reset sales rep display
            const salesRepDisplay = document.getElementById('salesRepDisplay');
            if (salesRepDisplay) {
                salesRepDisplay.style.display = 'none';
                salesRepDisplay.classList.remove('d-flex');
            }

            // Clear selection storage
            clearSalesRepSelection();
        }

        function hideSalesRepDisplay() {
            // Hide sales rep display for non-sales rep users
            // Hide immediately to prevent flicker
            const salesRepDisplay = document.getElementById('salesRepDisplay');
            if (salesRepDisplay) {
                salesRepDisplay.style.display = 'none';
                salesRepDisplay.classList.remove('d-flex', 'sales-rep-visible');
                salesRepDisplay.classList.add('d-none');
            }

            // Also hide mobile menu sales rep display
            const salesRepDisplayMenu = document.getElementById('salesRepDisplayMenu');
            if (salesRepDisplayMenu) {
                salesRepDisplayMenu.style.display = 'none';
            }

            // Also hide any related UI elements for sales reps
            const changeSalesRepBtn = document.getElementById('changeSalesRepSelection');
            if (changeSalesRepBtn) {
                changeSalesRepBtn.style.display = 'none';
            }

            const changeSalesRepBtnMenu = document.getElementById('changeSalesRepSelectionMenu');
            if (changeSalesRepBtnMenu) {
                changeSalesRepBtnMenu.style.display = 'none';
            }
            
            console.log('Sales rep display hidden immediately (desktop and mobile)');
        }

        // Modify sale submission to check access rights
        function checkSalesAccess() {
            // Only check access for sales rep users
            if (!isSalesRep) {
                return true; // Non-sales rep users can always sell
            }

            const selection = getSalesRepSelection();
            if (!selection) {
                toastr.error('Please select your vehicle and route before making a sale.',
                    'Selection Required');
                return false;
            }

            if (!selection.canSell) {
                toastr.error('You only have view access for this vehicle/route. Sales are not permitted.',
                    'Access Denied');
                return false;
            }

            // Check if selected location matches the assigned vehicle
            const selectedLocationId = document.getElementById('locationSelect')?.value;
            if (selectedLocationId != selection.vehicle.id) {
                toastr.error('You can only sell from your assigned vehicle location.', 'Location Mismatch');
                return false;
            }

            return true;
        }

        // ---- Sales Rep Session Management ----
        function storeSalesRepSelection(selection) {
            try {
                const selectionJson = JSON.stringify(selection);
                // Store in both sessionStorage (for current session) and localStorage (for persistence)
                sessionStorage.setItem('salesRepSelection', selectionJson);
                localStorage.setItem('salesRepSelection', selectionJson);
                console.log('Sales rep selection stored in both session and local storage');
            } catch (e) {
                console.error('Failed to store sales rep selection:', e);
            }
        }

        // ---- Loader helpers ----
        function showLoader() {
            posProduct.innerHTML = `
        <div class="loader-container">
            <div class="loader">
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
                <div class="circle"></div>
            </div>
        </div>`;
        }

        function hideLoader() {
            posProduct.innerHTML = '';
        }

        // ---- CATEGORY/SUBCATEGORY/BRAND (unchanged) ----
        function fetchCategories() {
            fetch('/main-category-get-all')
                .then(response => response.json())
                .then(data => {
                    const categories = data.message;
                    const categoryContainer = document.getElementById('categoryContainer');
                    if (Array.isArray(categories)) {
                        categories.forEach(category => {
                            const card = document.createElement('div');
                            card.classList.add('category-card');
                            card.setAttribute('data-id', category.id);

                            const cardTitle = document.createElement('h6');
                            cardTitle.textContent = category.mainCategoryName;
                            card.appendChild(cardTitle);

                            const buttonContainer = document.createElement('div');
                            buttonContainer.classList.add('category-footer');

                            const allButton = document.createElement('button');
                            allButton.textContent = 'All';
                            allButton.classList.add('btn', 'btn-outline-green', 'me-2');
                            allButton.addEventListener('click', () => {
                                filterProductsByCategory(category.id);
                                closeOffcanvas('offcanvasCategory');
                            });

                            const nextButton = document.createElement('button');
                            nextButton.textContent = 'Next >>';
                            nextButton.classList.add('btn', 'btn-outline-purple');
                            nextButton.addEventListener('click', () => {
                                fetchSubcategories(category.id);
                            });

                            buttonContainer.appendChild(allButton);
                            buttonContainer.appendChild(nextButton);
                            card.appendChild(buttonContainer);

                            categoryContainer.appendChild(card);
                        });
                    } else {
                        console.error('Categories not found:', categories);
                    }
                })
                .catch(error => {
                    console.error('Error fetching categories:', error);
                });
        }

        function fetchSubcategories(categoryId) {
            fetch(`/sub_category-details-get-by-main-category-id/${categoryId}`)
                .then(response => response.json())
                .then(data => {
                    const subcategories = data.message;
                    const subcategoryContainer = document.getElementById('subcategoryContainer');
                    subcategoryContainer.innerHTML = '';
                    if (Array.isArray(subcategories)) {
                        subcategories.forEach(subcategory => {
                            const card = document.createElement('div');
                            card.classList.add('card', 'subcategory-card', 'mb-3');
                            card.setAttribute('data-id', subcategory.id);

                            const cardBody = document.createElement('div');
                            cardBody.classList.add('card-body');

                            const cardTitle = document.createElement('h6');
                            cardTitle.classList.add('card-title');
                            cardTitle.textContent = subcategory.subCategoryname;
                            cardBody.appendChild(cardTitle);

                            card.appendChild(cardBody);

                            card.addEventListener('click', () => {
                                filterProductsBySubCategory(subcategory.id);
                                closeOffcanvas('offcanvasSubcategory');
                            });

                            subcategoryContainer.appendChild(card);
                        });
                    } else {
                        console.error('Subcategories not found:', subcategories);
                    }
                    // Show/hide offcanvas
                    const subcategoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                        'offcanvasSubcategory'));
                    subcategoryOffcanvas.show();
                    const categoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                        'offcanvasCategory'));
                    categoryOffcanvas.hide();
                })
                .catch(error => console.error('Error fetching subcategories:', error));
        }
        subcategoryBackBtn.addEventListener('click', () => {
            const categoryOffcanvas = new bootstrap.Offcanvas(document.getElementById(
                'offcanvasCategory'));
            categoryOffcanvas.show();
            const subcategoryOffcanvas = bootstrap.Offcanvas.getInstance(document.getElementById(
                'offcanvasSubcategory'));
            subcategoryOffcanvas.hide();
        });

        function fetchBrands() {
            fetch('/brand-get-all')
                .then(response => response.json())
                .then(data => {
                    const brands = data.message;
                    const brandContainer = document.getElementById('brandContainer');
                    if (Array.isArray(brands)) {
                        brands.forEach(brand => {
                            const brandCard = document.createElement('div');
                            brandCard.classList.add('brand-card');
                            brandCard.setAttribute('data-id', brand.id);

                            const brandName = document.createElement('h6');
                            brandName.textContent = brand.name;
                            brandCard.appendChild(brandName);

                            brandCard.addEventListener('click', () => {
                                filterProductsByBrand(brand.id);
                                closeOffcanvas('offcanvasBrand');
                            });

                            brandContainer.appendChild(brandCard);
                        });
                    } else {
                        console.error('Brands not found:', brands);
                    }
                })
                .catch(error => {
                    console.error('Error fetching brands:', error);
                });
        }

        // // ---- LOCATION ----
        // function fetchAllLocations() {
        //     $.ajax({
        //         url: '/location-get-all',
        //         method: 'GET',
        //         success: function(data) {
        //             if (data.status === 200) populateLocationDropdown(data.message);
        //             else console.error('Error fetching locations:', data.message);
        //         },
        //         error: function(jqXHR, textStatus, errorThrown) {
        //             console.error('AJAX Error:', textStatus, errorThrown);
        //         }
        //     });
        // }

        // function populateLocationDropdown(locations) {
        //     const locationSelect = $('#locationSelect');
        //     locationSelect.empty();
        //     locationSelect.append('<option value="" disabled selected>Select Location</option>');
        //     locations.forEach((location, index) => {
        //         const option = $('<option></option>').val(location.id).text(location.name);
        //         if (index === 0) option.attr('selected', 'selected');
        //         locationSelect.append(option);
        //     });
        //     locationSelect.trigger('change');
        // }

        // ---- LOCATION ----
        function fetchAllLocations() {
            $.ajax({
                url: '/location-get-all',
                method: 'GET',
                success: function(response) {
                    // Check for status = true and data exists
                    if (response.status && Array.isArray(response.data)) {
                        populateLocationDropdown(response.data);
                    } else {
                        console.error('Error fetching locations:', response.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                }
            });
        }

        function populateLocationDropdown(locations) {
            const locationSelect = $('#locationSelect');
            const locationSelectDesktop = $('#locationSelectDesktop');
            
            locationSelect.empty(); // Clear existing options
            locationSelectDesktop.empty(); // Clear desktop options too

            // Add default prompt
            locationSelect.append('<option value="" disabled selected>Select Location</option>');
            locationSelectDesktop.append('<option value="" disabled selected>Select Location</option>');

            locations.forEach((location, index) => {
                const option = $('<option></option>').val(location.id).text(location.name);
                const optionDesktop = $('<option></option>').val(location.id).text(location.name);
                
                if (index === 0) {
                    option.attr('selected', 'selected');
                    optionDesktop.attr('selected', 'selected');
                }
                
                locationSelect.append(option);
                locationSelectDesktop.append(optionDesktop);
            });

            // Trigger change event (optional: useful if other logic depends on it)
            locationSelect.trigger('change');
            locationSelectDesktop.trigger('change');
        }

        // ---- PAGINATED PRODUCT FETCH ----
        function handleLocationChange(event) {
            selectedLocationId = $(event.target).val();
            currentProductsPage = 1;
            hasMoreProducts = true;
            allProducts = [];
            posProduct.innerHTML = '';
            if (selectedLocationId) fetchPaginatedProducts(true);
            if (!isEditing) {
                billingBody.innerHTML = '';
            }
            updateTotals();

            // Auto-focus search input after location change
            setTimeout(() => {
                const productSearchInput = document.getElementById('productSearchInput');
                if (productSearchInput) {
                    productSearchInput.focus();
                    console.log('Product search input focused after location change');
                }
            }, 300);
        }

        function fetchPaginatedProducts(reset = false) {
            if (isLoadingProducts || !selectedLocationId || !hasMoreProducts) return;
            isLoadingProducts = true;
            if (reset) showLoader();
            fetch(
                    `/api/products/stocks?location_id=${selectedLocationId}&page=${currentProductsPage}&per_page=24`
                )
                .then(res => res.json())
                .then(data => {
                    hideLoader();
                    if (data.status !== 200 || !Array.isArray(data.data)) {
                        if (reset) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                        isLoadingProducts = false;
                        return;
                    }
                    if (reset) {
                        allProducts = [];
                        posProduct.innerHTML = '';
                        stockData = []; // Reset stockData on reset
                    }
                    data.data.forEach(stock => allProducts.push(stock));
                    // Always keep stockData in sync with allProducts
                    stockData = [...allProducts];
                    displayProducts(allProducts);
                    if (data.data.length === 0 || data.data.length < 24) hasMoreProducts = false;
                    else hasMoreProducts = true;
                    isLoadingProducts = false;
                    currentProductsPage++;
                })
                .catch(e => {
                    hideLoader();
                    isLoadingProducts = false;
                    if (reset) posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                    console.error('Error fetching products:', e);
                });
        }
        // Infinite scroll (using posProduct for lazy loading)
        function setupLazyLoad() {
            let productPage = 1;
            let productLoading = false;
            posProduct.addEventListener('scroll', () => {
                // Scroll down: fetch next page
                if (
                    hasMoreProducts &&
                    !productLoading &&
                    posProduct.scrollTop + posProduct.clientHeight >= posProduct.scrollHeight - 100
                ) {
                    productPage += 1;
                    fetchPaginatedProducts();
                }
                // Scroll up: fetch previous page (if needed)
                // Uncomment below if you want to fetch previous products when scrolling up
                /*
                if (
                    productPage > 1 &&
                    !productLoading &&
                    posProduct.scrollTop <= 100
                ) {
                    productPage -= 1;
                    fetchPaginatedProducts(true); // true to reset and load previous page
                }
                */
            });
        }
        // Call setupLazyLoad after posProduct is initialized
        setupLazyLoad();
        allProductsBtn.onclick = function() {
            currentProductsPage = 1;
            hasMoreProducts = true;
            allProducts = [];
            posProduct.innerHTML = '';
            fetchPaginatedProducts(true);
        };

        // ---- DISPLAY PRODUCTS ----
        function displayProducts(products) {
            posProduct.innerHTML = '';
            if (!selectedLocationId || products.length === 0) {
                posProduct.innerHTML = '<p class="text-center">No products found.</p>';
                return;
            }
            // Only show products with stock in selected location, or unlimited stock
            const filteredProducts = products.filter(stock => {
                // Use the existing normalizeBatches function to handle both array and object formats
                const batches = normalizeBatches(stock);

                if (!batches || batches.length === 0) {
                    return false;
                }

                return batches.some(batch =>
                    Array.isArray(batch.location_batches) && batch.location_batches.some(lb =>
                        lb.location_id == selectedLocationId &&
                        (
                            // If allow_decimal, check for > 0 as float (including decimals)
                            (stock.product.unit && (stock.product.unit.allow_decimal === true ||
                                    stock
                                    .product.unit.allow_decimal === 1) ?
                                parseFloat(lb.quantity) > 0 :
                                parseInt(lb.quantity) > 0
                            ) ||
                            stock.product.stock_alert === 0
                        )
                    )
                );
            });
            filteredProducts.forEach(stock => {
                const product = stock.product;
                let locationQty = 0;

                // Use the existing normalizeBatches function to handle both array and object formats
                const batches = normalizeBatches(stock);

                batches.forEach(batch => {
                    batch.location_batches.forEach(lb => {
                        if (lb.location_id == selectedLocationId) locationQty +=
                            parseFloat(lb.quantity);
                    });
                });
                stock.total_stock = product.stock_alert === 0 ? 0 : locationQty;
                // Show unit name (e.g., "Pc(s)", "kg", etc.) based on product.unit
                const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
                // Format quantity: decimal if allowed, else integer
                let quantityDisplay;
                if (product.stock_alert === 0) {
                    quantityDisplay = `Unlimited`;
                } else if (product.unit && (product.unit.allow_decimal === true || product.unit
                        .allow_decimal === 1)) {
                    quantityDisplay =
                        `${parseFloat(stock.total_stock).toFixed(4).replace(/\.?0+$/, '')} ${unitName} in stock`;
                } else {
                    quantityDisplay = `${parseInt(stock.total_stock, 10)} ${unitName} in stock`;
                }
                const cardHTML = `
            <div class="col-xxl-3 col-xl-4 col-lg-4 col-md-6 col-sm-3">
            <div class="product-card" data-id="${product.id}">
                <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" alt="${product.product_name}">
                <div class="product-card-body">
                <h6>${product.product_name} <br>
                    <span class="badge text-dark">SKU: ${product.sku || 'N/A'}</span>
                </h6>
                <h6>
                    <span class="badge ${product.stock_alert === 0 ? 'bg-info' : stock.total_stock > 0 ? 'bg-success' : 'bg-warning'}">
                    ${quantityDisplay}
                    </span>
                </h6>
                </div>
            </div>
            </div>`;
                posProduct.insertAdjacentHTML('beforeend', cardHTML);
            });
            // Add click event to product cards
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', () => {
                    const productId = card.getAttribute('data-id');
                    const productStock = allProducts.find(stock => String(stock.product.id) ===
                        productId);
                    if (productStock) addProductToTable(productStock.product);
                });
            });
        }

        // ---- AUTOCOMPLETE (server driven, optimized for your controller) ----
        function initAutocomplete() {
            let autoAddTimeout = null;
            let lastSearchResults = [];
            let currentSearchTerm = '';
            let autocompleteAdding = false; // Prevent double-add
            let lastAddedProduct = null; // Prevent duplicate quantity increments

            $("#productSearchInput").autocomplete({
                position: {
                    my: "left top",
                    at: "left bottom",
                    collision: "none"
                },
                source: function(request, response) {
                    if (!selectedLocationId) return response([]);

                    currentSearchTerm = request.term;

                    if (autoAddTimeout) {
                        clearTimeout(autoAddTimeout);
                        autoAddTimeout = null;
                    }

                    $.ajax({
                        url: '/api/products/stocks/autocomplete',
                        data: {
                            location_id: selectedLocationId,
                            search: request.term,
                            per_page: 15
                        },
                        success: function(data) {
                            if (data.status === 200 && Array.isArray(data.data)) {
                                const filtered = data.data.filter(stock =>
                                    stock.product &&
                                    (
                                        stock.product.stock_alert == 0 ||
                                        (stock.product.unit && (stock.product.unit
                                                .allow_decimal === true || stock
                                                .product.unit.allow_decimal === 1) ?
                                            parseFloat(stock.total_stock) > 0 :
                                            parseInt(stock.total_stock) > 0
                                        )
                                    )
                                );

                                const results = filtered.map(stock => {
                                    let imeiMatch = '';
                                    let exactImeiMatch = false;
                                    if (stock.imei_numbers && stock.imei_numbers
                                        .length > 0) {
                                        const matchingImei = stock.imei_numbers
                                            .find(imei =>
                                                imei.imei_number.toLowerCase()
                                                .includes(request.term
                                                    .toLowerCase())
                                            );
                                        if (matchingImei) {
                                            imeiMatch =
                                                ` 📱 IMEI: ${matchingImei.imei_number}`;
                                            exactImeiMatch = matchingImei
                                                .imei_number.toLowerCase() ===
                                                request.term.toLowerCase();
                                        }
                                    }
                                    return {
                                        label: `${stock.product.product_name} (${stock.product.sku || ''})${imeiMatch} [Stock: ${stock.product.stock_alert == 0 ? 'Unlimited' : stock.total_stock}]`,
                                        value: stock.product.product_name,
                                        product: stock.product,
                                        stockData: stock,
                                        imeiMatch: imeiMatch ? true : false,
                                        exactImeiMatch: exactImeiMatch
                                    };
                                });

                                if (results.length === 0) results.push({
                                    label: "No results found",
                                    value: ""
                                });

                                lastSearchResults = results.filter(r => r.product);

                                // Auto-add exact SKU or IMEI match
                                const exactMatch = results.find(r =>
                                    r.product && ((r.product.sku && r.product.sku
                                        .toLowerCase() === request.term
                                        .toLowerCase()) || r.exactImeiMatch)
                                );

                                if (exactMatch && request.term.length >= 3) {
                                    const matchType = exactMatch.product.sku &&
                                        exactMatch.product.sku.toLowerCase() === request
                                        .term.toLowerCase() ? 'SKU' : 'IMEI';
                                    showSearchIndicator("⚡ Auto-adding...", "orange");
                                    autoAddTimeout = setTimeout(() => {
                                        if (!autocompleteAdding) {
                                            autocompleteAdding = true;
                                            $("#productSearchInput")
                                                .autocomplete('close');
                                            addProductFromAutocomplete(
                                                exactMatch, request.term,
                                                matchType);
                                            $("#productSearchInput").val('');
                                            hideSearchIndicator();
                                            setTimeout(() => {
                                                autocompleteAdding =
                                                    false;
                                            }, 50);
                                        }
                                    }, 500);
                                }

                                response(results);
                            } else {
                                lastSearchResults = [];
                                response([{
                                    label: "No results found",
                                    value: ""
                                }]);
                            }
                        },
                        error: function() {
                            lastSearchResults = [];
                            response([{
                                label: "No results found",
                                value: ""
                            }]);
                        }
                    });
                },
                select: function(event, ui) {
                    if (!ui.item.product || autocompleteAdding) return false;

                    autocompleteAdding = true;
                    $("#productSearchInput").val("");
                    const isImeiMatch = ui.item.imeiMatch || false;
                    addProductFromAutocomplete(ui.item, currentSearchTerm, isImeiMatch ? 'IMEI' :
                        'MANUAL');
                    setTimeout(() => {
                        autocompleteAdding = false;
                    }, 50);

                    return false;
                },
                open: function() {
                    setTimeout(() => {
                        const autocompleteInstance = $("#productSearchInput").autocomplete(
                            "instance");
                        const menu = autocompleteInstance.menu;
                        const firstItem = menu.element.find("li:first-child");

                        if (firstItem.length > 0 && !firstItem.text().includes(
                                "No results")) {
                            // Properly set the active item using jQuery UI's method
                            menu.element.find(".ui-state-focus").removeClass(
                                "ui-state-focus");
                            firstItem.addClass("ui-state-focus");
                            menu.active = firstItem;
                            showSearchIndicator("↵ Press Enter to add");
                        }
                    }, 50);
                },
                close: function() {
                    hideSearchIndicator();
                },
                minLength: 1
            }).autocomplete("instance")._renderItem = function(ul, item) {
                const li = $("<li>");
                if (item.product) {
                    // Enhanced display for IMEI products
                    if (item.imeiMatch) {
                        const productName = item.product.product_name;
                        const sku = item.product.sku || '';
                        const imeiInfo = item.label.match(/📱 IMEI: ([^\[]+)/);
                        const imeiNumber = imeiInfo ? imeiInfo[1].trim() : '';
                        const stockInfo = item.label.match(/\[Stock: ([^\]]+)\]/);
                        const stock = stockInfo ? stockInfo[1] : '';
                        
                        const html = `
                            <div style="padding: 10px 12px; background-color: #e8f4f8; border-left: 4px solid #17a2b8;">
                                <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                    ${productName} ${sku ? '<span style="color: #6c757d; font-size: 0.9em;">(' + sku + ')</span>' : ''}
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9em;">
                                    <div style="color: #17a2b8; font-weight: 500;">
                                        📱 IMEI: ${imeiNumber}
                                    </div>
                                    <div style="color: #28a745; font-weight: 500; padding-left: 10px;">
                                        Stock: ${stock}
                                    </div>
                                </div>
                            </div>
                        `;
                        li.append(html);
                    } else {
                        // Regular product display
                        const style = "padding: 8px 12px;";
                        li.append(`<div style="${style}">${item.label}</div>`);
                    }
                } else {
                    li.append(
                        `<div style="color: red; padding: 8px 12px; font-style: italic;">${item.label}</div>`
                    );
                }
                return li.appendTo(ul);
            };

            $("#productSearchInput").autocomplete("instance")._resizeMenu = function() {
                const isMobile = window.innerWidth <= 991;
                
                if (isMobile) {
                    // Mobile: use viewport width minus margins
                    const menuWidth = window.innerWidth - 10; // 5px margin on each side
                    this.menu.element.css({
                        'width': menuWidth + 'px',
                        'max-width': menuWidth + 'px',
                        'left': '5px',
                        'right': '5px'
                    });
                } else {
                    // Desktop: use input width or minimum
                    const inputWidth = this.element.outerWidth();
                    const minWidth = 450;
                    const menuWidth = Math.max(inputWidth, minWidth);
                    this.menu.element.outerWidth(menuWidth);
                }
            };

            if (!document.getElementById('autocomplete-styles')) {
                const style = document.createElement('style');
                style.id = 'autocomplete-styles';
                style.textContent = `
                .ui-autocomplete { 
                    max-height: 400px; 
                    overflow-y: auto; 
                    z-index: 1000; 
                    border: 1px solid #ddd; 
                    border-radius: 4px; 
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
                    min-width: 400px !important;
                }
                .ui-autocomplete .ui-menu-item { 
                    border-bottom: 1px solid #f0f0f0; 
                    list-style: none;
                }
                .ui-autocomplete .ui-menu-item:last-child { 
                    border-bottom: none; 
                }
                .ui-autocomplete .ui-state-focus { 
                    background: #007bff !important; 
                    color: white !important; 
                    margin: 0; 
                }
                .ui-autocomplete .ui-state-focus div { 
                    color: white !important; 
                    background-color: transparent !important;
                    border-left-color: white !important;
                }
                .ui-autocomplete .ui-state-focus div > div { 
                    color: white !important; 
                }
                .ui-autocomplete .ui-state-focus span { 
                    color: white !important; 
                }
                .ui-autocomplete .ui-menu-item div { 
                    white-space: normal; 
                    word-wrap: break-word;
                }
                #productSearchInput { 
                    position: relative; 
                }
                .search-indicator { 
                    position: absolute; 
                    right: 10px; 
                    top: 50%; 
                    transform: translateY(-50%); 
                    font-size: 12px; 
                    color: #28a745; 
                    pointer-events: none; 
                }
                .quantity-error { 
                    border: 2px solid #dc3545 !important; 
                    box-shadow: 0 0 5px rgba(220, 53, 69, 0.3) !important; 
                    background-color: #fff5f5 !important; 
                }
                .quantity-error:focus { 
                    border-color: #dc3545 !important; 
                    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; 
                }
            `;
                document.head.appendChild(style);
            }

            function showSearchIndicator(text, color = "#28a745") {
                hideSearchIndicator();
                const searchContainer = $("#productSearchInput").parent();
                if (searchContainer.css('position') !== 'relative') searchContainer.css('position', 'relative');
                const indicator = $(`<span class="search-indicator" style="color: ${color};">${text}</span>`);
                searchContainer.append(indicator);
            }

            function hideSearchIndicator() {
                $('.search-indicator').remove();
            }

            // In the keydown handler for Enter
            $("#productSearchInput").off('keydown.autocomplete').on('keydown.autocomplete', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();

                    const widget = $(this).autocomplete("widget");
                    const focused = widget.find(".ui-state-focus");
                    const currentSearchTerm = $(this).val().trim();

                    let itemToAdd = null;

                    if (focused.length > 0) {
                        // Get the item data from the autocomplete instance's active item
                        const autocompleteInstance = $(this).autocomplete("instance");
                        if (autocompleteInstance && autocompleteInstance.menu.active) {
                            itemToAdd = autocompleteInstance.menu.active.data("ui-autocomplete-item");
                        }
                    }

                    // Fallback: if no focused item found, use first item from last search results
                    if (!itemToAdd && lastSearchResults.length > 0) {
                        itemToAdd = lastSearchResults[0];
                    }

                    if (itemToAdd && itemToAdd.product) {
                        // Prevent duplicate add of same product consecutively
                        if (!lastAddedProduct || lastAddedProduct.id !== itemToAdd.product.id) {
                            lastAddedProduct = itemToAdd.product;
                            addProductFromAutocomplete(itemToAdd, currentSearchTerm, itemToAdd
                                .imeiMatch ? 'IMEI' : 'ENTER');
                        }
                    }

                    // Close autocomplete immediately
                    $(this).autocomplete('close');
                    $(this).val('');
                    event.stopImmediatePropagation();
                }
            });

            $("#productSearchInput").on('input', function() {
                lastAddedProduct = null; // reset on new input
                if (autoAddTimeout) {
                    clearTimeout(autoAddTimeout);
                    autoAddTimeout = null;
                }
                if ($(this).val().length === 0) hideSearchIndicator();
            });

            function addProductFromAutocomplete(item, searchTerm = '', matchType = '') {
                if (!item.product) return;

                // Prevent duplicate quantity increment for same product and matchType
                // For IMEI products, prevent duplicate calls when same IMEI is scanned again
                if (lastAddedProduct && lastAddedProduct.id === item.product.id) {
                    // For IMEI products, check if this is a duplicate IMEI scan
                    if (item.product.is_imei_or_serial_no === 1 && matchType === 'IMEI') {
                        console.log('Preventing duplicate IMEI scan for product:', item.product.product_name);
                        return;
                    }
                    // For non-IMEI products, prevent duplicate within short time frame
                    if (matchType !== 'MANUAL') {
                        return;
                    }
                }

                lastAddedProduct = item.product;

                console.log('Adding product from autocomplete:', item.product.product_name, 'Search term:',
                    searchTerm, 'Match type:', matchType);

                let stockEntry = stockData.find(stock => stock.product.id === item.product.id);
                if (!stockEntry && item.stockData) {
                    stockData.push(item.stockData);
                    allProducts.push(item.stockData);
                    stockEntry = item.stockData;
                }

                if (!stockEntry) {
                    fetch(
                            `/api/products/stocks?location_id=${selectedLocationId}&product_id=${item.product.id}`
                        )
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 200 && Array.isArray(data.data) && data.data.length > 0) {
                                stockData.push(data.data[0]);
                                allProducts.push(data.data[0]);
                                addProductToTable(data.data[0].product, searchTerm, matchType);
                            } else {
                                toastr.error('Stock entry not found for the product', 'Error');
                            }
                        })
                        .catch(() => {
                            toastr.error('Error fetching product stock data', 'Error');
                        });
                    return;
                }

                addProductToTable(item.product, searchTerm, matchType);
            }

            $("#productSearchInput").removeAttr("aria-live aria-autocomplete");
            $("#productSearchInput").autocomplete("instance").liveRegion.remove();

            $("#productSearchInput").autocomplete("instance")._move = function(direction, event) {
                if (!this.menu.element.is(":visible")) {
                    this.search(null, event);
                    return;
                }
                if ((this.menu.isFirstItem() && /^previous/.test(direction)) || (this.menu.isLastItem() &&
                        /^next/.test(direction))) {
                    this._value(this.term);
                    this.menu.blur();
                    return;
                }
                this.menu[direction](event);
                this.menu.element.find(".ui-state-focus").removeClass("ui-state-focus");
                this.menu.active.addClass("ui-state-focus");
            };
        }

        // Re-init autocomplete when location changes
        $('#locationSelect').on('change', () => {
            $("#productSearchInput").val('');
            if ($("#productSearchInput").data('ui-autocomplete')) {
                $("#productSearchInput").autocomplete('destroy');
            }
            initAutocomplete();
        });



        function formatAmountWithSeparators(amount) {
            return new Intl.NumberFormat().format(amount);
        }

        function parseFormattedAmount(formattedAmount) {
            if (typeof formattedAmount !== 'string' && typeof formattedAmount !== 'number') {
                return 0;
            }
            const cleaned = String(formattedAmount).replace(/[^0-9.-]/g, '');
            const parsed = parseFloat(cleaned);
            return isNaN(parsed) ? 0 : parsed;
        }

        // Filter products by category
        function filterProductsByCategory(categoryId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.main_category_id ===
                    categoryId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Filter products by subcategory
        function filterProductsBySubCategory(subCategoryId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.sub_category_id ===
                    subCategoryId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Filter products by brand
        function filterProductsByBrand(brandId) {
            showLoader();
            setTimeout(() => {
                const filteredProducts = stockData.filter(stock => stock.product.brand_id === brandId);
                displayProducts(filteredProducts);
            }, 500);
        }

        // Function to close the offcanvas
        function closeOffcanvas(offcanvasId) {
            const offcanvasElement = document.getElementById(offcanvasId);
            const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
            if (bsOffcanvas) {
                bsOffcanvas.hide();
            }
        }

        let locationId = null;
        let priceType = 'retail';
        let selectedRow;

        function addProductToTable(product, searchTerm = '', matchType = '') {
            console.log("Product to be added:", product, "Search term:", searchTerm, "Match type:", matchType);

            if (!stockData || stockData.length === 0) {
                console.error('stockData is not defined or empty');
                toastr.error('Stock data is not available', 'Error');
                return;
            }

            const stockEntry = stockData.find(stock => stock.product.id === product.id);
            console.log("stockEntry", stockEntry);

            if (!stockEntry) {
                toastr.error('Stock entry not found for the product', 'Error');
                return;
            }

            const totalQuantity = stockEntry.total_stock;

            // Get current customer information for pricing
            const currentCustomer = getCurrentCustomer();
            console.log("Current customer:", currentCustomer);

            // If product is unlimited stock (stock_alert === 0), allow sale even if quantity is 0
            if (product.stock_alert === 0) {
                // Proceed to add product with batch "all" and quantity 0 (unlimited)
                const batchesArray = normalizeBatches(stockEntry);

                // Use latest batch for pricing determination
                const latestBatch = batchesArray.length > 0 ? batchesArray[0] : null;

                // Get customer-type-based price
                const priceResult = getCustomerTypePrice(latestBatch, product, currentCustomer.customer_type);

                if (priceResult.hasError) {
                    toastr.error(
                        `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                        'Pricing Error');
                    return;
                }

                locationId = selectedLocationId;
                addProductToBillingBody(
                    product,
                    stockEntry,
                    priceResult.price,
                    "all", // batchId is "all"
                    0, // unlimited stock, so quantity is 0
                    currentCustomer.customer_type
                );
                return;
            }

            // Check if product requires IMEI
            if (product.is_imei_or_serial_no === 1) {
                const availableImeis = stockEntry.imei_numbers?.filter(imei => imei.status === "available") ||
                [];
                console.log("Available IMEIs:", availableImeis);

                const billingBody = document.getElementById('billing-body');
                const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row =>
                    row.querySelector('.product-id')?.textContent == product.id
                );

                if (existingRows.length > 0) {
                    console.log('Found existing rows for product, showing modal for additional selection');
                    // For existing rows, don't specify a batch to show all available IMEIs
                    showImeiSelectionModal(product, stockEntry, [], searchTerm, matchType, null);
                    return;
                }

                // Get the available batches for this location to determine default batch
                let batchesArray = normalizeBatches(stockEntry);
                batchesArray = batchesArray.filter(batch =>
                    Array.isArray(batch.location_batches) &&
                    batch.location_batches.some(lb =>
                        String(lb.location_id) == String(selectedLocationId) &&
                        parseFloat(lb.quantity) > 0
                    )
                );

                if (batchesArray.length === 0) {
                    toastr.error('No batches with available quantity found in this location for IMEI product',
                        'Error');
                    return;
                }

                // Sort batches by id descending (latest batch first)
                batchesArray = batchesArray.sort((a, b) => parseInt(b.id) - parseInt(a.id));

                // Check if there are multiple batches with different prices
                const uniquePrices = [];
                for (const batch of batchesArray) {
                    const priceResult = getCustomerTypePrice(batch, product, currentCustomer.customer_type);
                    if (!priceResult.hasError) {
                        uniquePrices.push(priceResult.price);
                    }
                }
                const distinctPrices = [...new Set(uniquePrices)];

                if (distinctPrices.length <= 1) {
                    // Single price - use latest batch and show its IMEIs
                    const selectedBatch = batchesArray[0];
                    console.log('Single price for IMEI product, using latest batch:', selectedBatch.id);
                    showImeiSelectionModal(product, stockEntry, [], searchTerm, matchType, selectedBatch.id);
                } else {
                    // Multiple prices - user needs to select batch first, then IMEIs
                    console.log('Multiple prices for IMEI product, showing batch selection first');
                    showBatchPriceSelectionModal(product, stockEntry, batchesArray, currentCustomer);
                }
                return;
            }

            // If no IMEI required, proceed normally
            if ((totalQuantity === 0 || totalQuantity === "0" || totalQuantity === "0.00") && product
                .stock_alert !== 0) {
                toastr.error(`Sorry, ${product.product_name} is out of stock!`, 'Warning');
                return;
            }

            // Ensure batches is always an array using helper function
            let batchesArray = normalizeBatches(stockEntry);

            // Filter batches by selected location and available quantity
            batchesArray = batchesArray.filter(batch =>
                Array.isArray(batch.location_batches) &&
                batch.location_batches.some(lb =>
                    String(lb.location_id) == String(selectedLocationId) &&
                    parseFloat(lb.quantity) > 0
                )
            );

            if (batchesArray.length === 0) {
                toastr.error('No batches with available quantity found in this location', 'Error');
                return;
            }

            // Sort batches by id descending (latest batch first)
            batchesArray = batchesArray.sort((a, b) => parseInt(b.id) - parseInt(a.id));

            // Get unique prices for the current customer type across batches in this location
            const customerTypePrices = [];
            for (const batch of batchesArray) {
                const priceResult = getCustomerTypePrice(batch, product, currentCustomer.customer_type);
                if (!priceResult.hasError) {
                    customerTypePrices.push(priceResult.price);
                }
            }

            // Remove duplicates
            const uniquePrices = [...new Set(customerTypePrices)];

            // If there's only one price or all batches have the same price, add the latest batch
            if (uniquePrices.length <= 1) {
                // Default: select "All" batch (not a real batch, but for all available)
                // Calculate total quantity for all batches in this location
                let totalQty = 0;
                batchesArray.forEach(batch => {
                    batch.location_batches.forEach(lb => {
                        if (String(lb.location_id) == String(selectedLocationId)) {
                            totalQty += parseFloat(lb.quantity);
                        }
                    });
                });

                // Use latest batch for pricing
                const latestBatch = batchesArray[0];
                const priceResult = getCustomerTypePrice(latestBatch, product, currentCustomer.customer_type);

                if (priceResult.hasError) {
                    toastr.error(
                        `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                        'Pricing Error');
                    return;
                }

                locationId = selectedLocationId;
                addProductToBillingBody(
                    product,
                    stockEntry,
                    priceResult.price,
                    "all", // batchId is "all"
                    totalQty,
                    currentCustomer.customer_type
                );
            } else {
                // Multiple prices found → show modal (user must select batch)
                showBatchPriceSelectionModal(product, stockEntry, batchesArray, currentCustomer);
            }
        }


        // Helper function to normalize batches to array format
        function normalizeBatches(stockEntry) {
            if (!stockEntry || !stockEntry.batches) {
                return [];
            }

            if (Array.isArray(stockEntry.batches)) {
                return stockEntry.batches;
            } else if (typeof stockEntry.batches === 'object' && stockEntry.batches !== null) {
                return Object.values(stockEntry.batches);
            }

            return [];
        }

        // Global variable to track currently opened modal product
        let activeModalProductId = null;

        function showBatchPriceSelectionModal(product, stockEntry, batches, currentCustomer = null) {
            const tbody = document.getElementById('batch-price-list');
            const modalElement = document.getElementById('batchPriceModal');
            const modal = new bootstrap.Modal(modalElement);

            // Get current customer if not provided
            if (!currentCustomer) {
                currentCustomer = getCurrentCustomer();
            }

            // Prevent opening modal again for same product
            if (activeModalProductId === product.id) {
                toastr.info('Batch selection already in progress for this product.');
                return;
            }
            activeModalProductId = product.id;

            // Reset modal content
            tbody.innerHTML = '';
            const batchRows = [];

            // Filter and sort batches
            const validBatches = batches.filter(batch => {
                const locationBatch = batch.location_batches.find(lb => lb.location_id ==
                    selectedLocationId);
                return locationBatch && parseFloat(locationBatch.quantity) > 0;
            }).sort((a, b) => parseInt(b.id) - parseInt(a.id));

            if (validBatches.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="5" class="text-center text-danger">No batches available</td></tr>`;
                modal.show();
                setTimeout(() => modal.hide(), 1500);
                activeModalProductId = null;
                return;
            }

            // Populate modal with batches
            validBatches.forEach((batch, index) => {
                const locationBatch = batch.location_batches.find(lb => lb.location_id ==
                    selectedLocationId);

                // Get customer-type-based price for this batch
                const priceResult = getCustomerTypePrice(batch, product, currentCustomer.customer_type);

                let priceDisplay = '';
                let priceToUse = 0;
                let buttonContent = '';

                if (priceResult.hasError) {
                    priceDisplay =
                        `<span class="text-danger">No valid price for ${currentCustomer.customer_type}</span>`;
                    buttonContent =
                        `<button class="btn btn-sm btn-secondary" disabled>No Price</button>`;
                } else {
                    priceToUse = priceResult.price;
                    const batchMrp = batch.max_retail_price !== undefined && batch.max_retail_price !==
                        null ?
                        parseFloat(batch.max_retail_price) : (product.max_retail_price || 0);

                    priceDisplay = `
                        MRP: Rs ${batchMrp.toFixed(2)}<br>
                        <strong>${currentCustomer.customer_type.charAt(0).toUpperCase() + currentCustomer.customer_type.slice(1)} Price: Rs ${priceToUse.toFixed(2)}</strong><br>
                        <small class="text-muted">Source: ${priceResult.source.replace(/_/g, ' ')}</small>
                    `;

                    buttonContent = `
                        <button class="btn btn-sm btn-primary select-batch-btn"
                            data-batch-id="${batch.id}"
                            data-customer-price="${priceToUse}"
                            data-max-retail-price="${batchMrp}"
                            data-batch-json='${JSON.stringify(batch)}'>
                            Select
                        </button>
                    `;
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td><strong>[${index + 1}]</strong></td>
            <td>${batch.batch_no}</td>
            <td>${priceDisplay}</td>
            <td>${locationBatch.quantity} PC(s)</td>
            <td>${buttonContent}</td>
        `;
                tbody.appendChild(tr);
                batchRows.push(tr);
            });

            let isModalOpen = false;

            // Handle batch selection
            function handleBatchSelect(e) {
                if (e.target.classList.contains('select-batch-btn')) {
                    const batchJson = e.target.dataset.batchJson;
                    const selectedBatch = JSON.parse(batchJson);
                    const locationBatch = selectedBatch.location_batches.find(lb => lb.location_id ==
                        selectedLocationId);
                    const qty = locationBatch?.quantity || 0;

                    // Get customer-type-based price for the selected batch
                    const priceResult = getCustomerTypePrice(selectedBatch, product, currentCustomer
                        .customer_type);

                    if (priceResult.hasError) {
                        toastr.error(
                            `This batch has no valid price configured for ${currentCustomer.customer_type} customers.`,
                            'Pricing Error');
                        return;
                    }

                    const customerPrice = priceResult.price;
                    const batchMrp = selectedBatch.max_retail_price !== undefined && selectedBatch
                        .max_retail_price !== null ?
                        parseFloat(selectedBatch.max_retail_price) : (product.max_retail_price || 0);

                    const productWithBatchPrices = {
                        ...product,
                        retail_price: customerPrice,
                        max_retail_price: batchMrp
                    };

                    // Check if this is an IMEI product
                    if (product.is_imei_or_serial_no === 1) {
                        console.log('IMEI product batch selected, opening IMEI modal for batch:', selectedBatch
                            .id);
                        // Close batch modal first
                        if (isModalOpen) {
                            modal.hide();
                            isModalOpen = false;
                        }

                        // Show IMEI selection modal for the selected batch
                        setTimeout(() => {
                            showImeiSelectionModal(product, stockEntry, [], '', 'BATCH_SELECTED',
                                selectedBatch.id);
                        }, 300);
                    } else {
                        // Add non-IMEI product to billing with quantity 1
                        addProductToBillingBody(
                            productWithBatchPrices,
                            stockEntry,
                            customerPrice,
                            selectedBatch.id,
                            qty,
                            currentCustomer.customer_type,
                            1, // Quantity is 1 when selecting from modal
                            [],
                            null,
                            null,
                            selectedBatch
                        );

                        if (isModalOpen) {
                            modal.hide();
                            isModalOpen = false;
                        }
                    }
                }
            }

            tbody.addEventListener('click', handleBatchSelect);

            // Keyboard navigation
            const handleKeyDown = function(event) {
                const key = event.key;
                if (!/^[1-9]$/.test(key)) return;

                const selectedIndex = parseInt(key, 10) - 1;
                if (batchRows[selectedIndex]) {
                    const selectBtn = batchRows[selectedIndex].querySelector('.select-batch-btn');
                    if (selectBtn) {
                        selectBtn.click();
                        if (isModalOpen) {
                            modal.hide();
                            isModalOpen = false;
                        }
                    }
                }
            };

            // Modal lifecycle
            const shownHandler = () => {
                document.addEventListener('keydown', handleKeyDown);
                isModalOpen = true;
            };

            const hiddenHandler = () => {
                document.removeEventListener('keydown', handleKeyDown);
                isModalOpen = false;
                activeModalProductId = null;
                tbody.removeEventListener('click', handleBatchSelect);
                modalElement.removeEventListener('shown.bs.modal', shownHandler);
                modalElement.removeEventListener('hidden.bs.modal', hiddenHandler);
            };

            modalElement.addEventListener('shown.bs.modal', shownHandler, {
                once: true
            });
            modalElement.addEventListener('hidden.bs.modal', hiddenHandler, {
                once: true
            });

            modal.show();
        }


        let selectedImeisInBilling = [];
        let currentImeiProduct = null;
        let currentImeiStockEntry = null;

        function showImeiSelectionModal(product, stockEntry, imeis, searchTerm = '', matchType = '',
            selectedBatchId = null) {
            currentImeiProduct = product;
            currentImeiStockEntry = stockEntry;

            console.log('Opening IMEI modal with search term:', searchTerm, 'Match type:', matchType,
                'Selected batch ID:', selectedBatchId);
            console.log('Is editing:', isEditing, 'Current sale ID:', currentEditingSaleId);

            // Force refresh stock data for IMEI products to get latest status
            if (!isEditing) {
                console.log('Force refreshing stock data for IMEI product...');
                fetch(`/api/products/stocks/autocomplete?search=${encodeURIComponent(product.product_name)}&location_id=${selectedLocationId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            // Find the current product in the response
                            const updatedStockEntry = data.find(item => item.product.id === product.id);
                            if (updatedStockEntry) {
                                console.log('Updated stock entry:', updatedStockEntry);
                                // Update the global stockData
                                const stockIndex = stockData.findIndex(stock => stock.product.id === product
                                    .id);
                                if (stockIndex !== -1) {
                                    stockData[stockIndex] = updatedStockEntry;
                                    console.log('Updated global stockData for product:', product.id);
                                }
                                // Use the updated stock entry
                                continueWithImeiModal(product, updatedStockEntry, searchTerm, matchType,
                                    selectedBatchId);
                            } else {
                                console.log('Product not found in updated data, using original');
                                continueWithImeiModal(product, stockEntry, searchTerm, matchType,
                                    selectedBatchId);
                            }
                        } else {
                            console.log('No updated data received, using original');
                            continueWithImeiModal(product, stockEntry, searchTerm, matchType,
                                selectedBatchId);
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing stock data:', error);
                        continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
                    });
            } else {
                continueWithImeiModal(product, stockEntry, searchTerm, matchType, selectedBatchId);
            }
        }

        function continueWithImeiModal(product, stockEntry, searchTerm = '', matchType = '', selectedBatchId =
            null) {
            console.log('=== CONTINUE WITH IMEI MODAL ===');
            console.log('Product:', product);
            console.log('StockEntry:', stockEntry);
            console.log('StockEntry IMEI Numbers:', stockEntry.imei_numbers);
            console.log('Selected Batch ID:', selectedBatchId);

            // Collect already selected IMEIs in billing
            selectedImeisInBilling = [];
            const billingBody = document.getElementById('billing-body');
            const existingRows = Array.from(billingBody.querySelectorAll('tr')).filter(row => {
                return row.querySelector('.product-id')?.textContent == product.id;
            });
            existingRows.forEach(row => {
                const imeiData = row.querySelector('.imei-data')?.textContent.trim();
                if (imeiData) {
                    // Split comma-separated IMEI values and add to selected list
                    const rowImeis = imeiData.split(',').filter(Boolean);
                    selectedImeisInBilling.push(...rowImeis);
                }
            });

            console.log('Currently selected IMEIs in billing:', selectedImeisInBilling);

            // Function to process and display IMEI data
            const processImeiData = (allRelevantImeis) => {
                console.log('ProcessImeiData called with:', allRelevantImeis);

                // *** BATCH-SPECIFIC FILTERING FOR IMEI PRODUCTS ***
                let filteredImeis = allRelevantImeis;

                // If selectedBatchId is provided, filter IMEIs to only show those belonging to that batch
                if (selectedBatchId && selectedBatchId !== "all") {
                    console.log('Filtering IMEIs for specific batch ID:', selectedBatchId);
                    filteredImeis = allRelevantImeis.filter(imei => {
                        // Check if IMEI belongs to the selected batch
                        const belongsToBatch = String(imei.batch_id) === String(selectedBatchId);
                        console.log(
                            `IMEI ${imei.imei_number} batch_id: ${imei.batch_id}, selected batch: ${selectedBatchId}, belongs: ${belongsToBatch}`
                        );
                        return belongsToBatch;
                    });

                    console.log(
                        `Filtered ${allRelevantImeis.length} IMEIs to ${filteredImeis.length} for batch ${selectedBatchId}`
                    );

                    if (filteredImeis.length === 0) {
                        toastr.warning(
                            `No IMEIs found for the selected batch. Showing all available IMEIs for this product.`
                        );
                        filteredImeis = allRelevantImeis;
                    }
                } else if (selectedBatchId === "all") {
                    console.log('Batch "all" selected, showing all available IMEIs');
                    // Keep all IMEIs when "all" is selected
                } else {
                    console.log('No specific batch selected, showing all available IMEIs');
                }

                console.log('Final filtered IMEIs for modal:', filteredImeis);

                // Ensure batches is always an array using helper function
                const batchesArray = normalizeBatches(stockEntry);

                // Find the batch for quantity calculation
                let selectedBatch = null;
                if (selectedBatchId && selectedBatchId !== "all") {
                    selectedBatch = batchesArray.find(b => String(b.id) === String(selectedBatchId));
                } else {
                    // Use the first available batch or find one with location batches
                    selectedBatch = batchesArray.find(b =>
                        b.location_batches && b.location_batches.some(lb => lb.location_id ==
                            selectedLocationId)
                    );
                }

                const batchQty = selectedBatch ? selectedBatch.total_batch_quantity || 0 : 0;
                let missingImeiCount = Math.max(0, batchQty - filteredImeis.length);

                const tbody = document.getElementById('imei-table-body');
                if (!tbody) {
                    toastr.error("IMEI table body not found");
                    return;
                }
                tbody.innerHTML = '';
                const imeiRows = [];

                // Populate filtered IMEIs only
                filteredImeis.forEach((imei, index) => {
                    const isChecked = selectedImeisInBilling.includes(imei.imei_number);

                    // Check if this IMEI matches the search term (for auto-selection)
                    const isSearchedImei = matchType === 'IMEI' && searchTerm &&
                        imei.imei_number.toLowerCase() === searchTerm.toLowerCase();

                    const row = document.createElement('tr');
                    row.dataset.imei = imei.imei_number;
                    row.dataset.imeiId = imei.id; // <-- Store primary key for edit
                    row.dataset.batchId = imei.batch_id; // Store batch ID for reference

                    // Add special styling for searched IMEI
                    if (isSearchedImei) {
                        row.style.backgroundColor = '#e8f4f8';
                        row.style.border = '2px solid #17a2b8';
                    }

                    // Add batch information to the display
                    const batchInfo = selectedBatchId && selectedBatchId !== "all" ?
                        ` (Batch: ${imei.batch_id})` :
                        (filteredImeis.length < allRelevantImeis.length ?
                            ` (Batch: ${imei.batch_id})` : '');

                    row.innerHTML = `
                <td>${index + 1}</td>
                <td><input type="checkbox" class="imei-checkbox" value="${imei.imei_number}" ${isChecked || isSearchedImei ? 'checked' : ''} data-status="${imei.status}" /></td>
                <td class="imei-display">${imei.imei_number}${isSearchedImei ? ' 🔍' : ''}${batchInfo}</td>
                <td><span class="badge ${imei.status === 'available' ? 'bg-success' : 'bg-danger'}">${imei.status}</span></td>
                <td>
                    ${(typeof userPermissions !== 'undefined' && userPermissions.canEditProduct) ? `<button class="btn btn-sm btn-warning edit-imei-btn">Edit</button>` : ''}
                    ${(typeof userPermissions !== 'undefined' && userPermissions.canDeleteProduct) ? `<button class="btn btn-sm btn-danger remove-imei-btn">Remove</button>` : ''}
                </td>
            `;
                    row.classList.add('clickable-row');
                    row.addEventListener('click', function(event) {
                        if (event.target.type !== 'checkbox') {
                            const checkbox = row.querySelector('.imei-checkbox');
                            checkbox.checked = !checkbox.checked;
                        }
                    });
                    tbody.appendChild(row);
                    imeiRows.push(row);
                });

                // Add initial manual IMEI row if needed
                if (missingImeiCount > 0) {
                    addNewImeiRow(missingImeiCount, tbody, imeiRows);
                }

                // Show modal
                const modalElement = document.getElementById('imeiModal');
                if (!modalElement) {
                    toastr.error("IMEI modal not found");
                    return;
                }

                // Update modal title to indicate batch filtering if applicable
                const modalTitle = modalElement.querySelector('.modal-title');
                if (modalTitle) {
                    let titleText = `Select IMEI for ${product.product_name}`;
                    if (selectedBatchId && selectedBatchId !== "all") {
                        titleText += ` (Batch: ${selectedBatchId})`;
                    }
                    modalTitle.textContent = titleText;
                }

                const modal = new bootstrap.Modal(modalElement);

                // Add event listener for modal cleanup
                modalElement.addEventListener('hidden.bs.modal', function modalCleanup() {
                    // Ensure backdrop is removed and body styles are reset
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 100);

                    // Remove the event listener to prevent memory leaks
                    modalElement.removeEventListener('hidden.bs.modal', modalCleanup);
                });

                modal.show();

                setupSearchAndFilter(tbody, imeiRows, searchTerm, matchType);
                setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows);
                setupAddButtonContainer(missingImeiCount, tbody, imeiRows);
                attachEditRemoveHandlers();
            };

            // If we're in edit mode, fetch the current sale's IMEI data and merge with available IMEIs
            if (isEditing && currentEditingSaleId) {
                fetch(`/sales/edit/${currentEditingSaleId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 200) {
                            // Get the current sale's IMEI numbers for this product
                            const saleProducts = data.sale_details.sale_products.filter(sp => sp
                                .product_id == product.id);
                            let currentSaleImeis = [];

                            saleProducts.forEach(sp => {
                                if (sp.imei_numbers && sp.imei_numbers.length > 0) {
                                    sp.imei_numbers.forEach(imeiNumber => {
                                        // Create IMEI object that matches the format from autocomplete
                                        currentSaleImeis.push({
                                            id: sp
                                                .id, // Use sale product ID as placeholder
                                            imei_number: imeiNumber,
                                            location_id: sp.location_id,
                                            batch_id: sp.batch_id,
                                            status: 'sold' // These are currently sold IMEIs
                                        });
                                    });
                                }
                            });

                            console.log('Current sale IMEIs:', currentSaleImeis);

                            // Merge available IMEIs from stock with current sale IMEIs
                            let allRelevantImeis = [];

                            if (stockEntry.imei_numbers) {
                                // Add available IMEIs
                                const availableImeis = stockEntry.imei_numbers.filter(imei =>
                                    imei.status === "available" && imei.location_id ==
                                    selectedLocationId
                                );
                                allRelevantImeis.push(...availableImeis);
                            }

                            // Add current sale IMEIs (these will show as sold but selectable)
                            allRelevantImeis.push(...currentSaleImeis);

                            // Remove duplicates based on IMEI number
                            allRelevantImeis = allRelevantImeis.filter((imei, index, self) =>
                                index === self.findIndex(i => i.imei_number === imei.imei_number)
                            );

                            console.log('All relevant IMEIs for edit mode:', allRelevantImeis);

                            processImeiData(allRelevantImeis);
                        } else {
                            console.error('Failed to fetch sale data for IMEI editing');
                            // Fallback to available IMEIs only
                            processImeiDataFallback();
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching sale IMEI data:', error);
                        // Fallback to available IMEIs only
                        processImeiDataFallback();
                    });
            } else {
                // Not in edit mode, use available IMEIs only
                processImeiDataFallback();
            }

            // Fallback function for non-edit mode
            function processImeiDataFallback() {
                let allRelevantImeis = [];

                console.log('StockEntry imei_numbers:', stockEntry.imei_numbers);
                console.log('Selected Location ID:', selectedLocationId);

                if (stockEntry.imei_numbers) {
                    // Add available IMEIs
                    const availableImeis = stockEntry.imei_numbers.filter(imei => {
                        console.log('Checking IMEI:', imei, 'Status:', imei.status, 'Location:', imei
                            .location_id);
                        return imei.status === "available" && imei.location_id == selectedLocationId;
                    });
                    console.log('Filtered available IMEIs:', availableImeis);
                    allRelevantImeis.push(...availableImeis);
                }

                console.log('ProcessImeiDataFallback - allRelevantImeis:', allRelevantImeis);
                processImeiData(allRelevantImeis);
            }
        }

        // --- Helper Functions ---

        function setupSearchAndFilter(tbody, imeiRows, searchTerm = '', matchType = '') {
            const searchInput = document.getElementById('imeiSearch');
            const filterSelect = document.getElementById('checkboxFilter');

            // Pre-populate search field if we searched by IMEI
            if (matchType === 'IMEI' && searchTerm && searchInput) {
                searchInput.value = searchTerm;
                searchInput.focus();

                // Add visual indication that this was auto-filled
                searchInput.style.backgroundColor = '#e8f4f8';
                setTimeout(() => {
                    searchInput.style.backgroundColor = '';
                }, 2000);
            }

            function applyFilters() {
                const searchTerm = (searchInput?.value || '').toLowerCase();
                const filterType = filterSelect?.value || 'all';

                imeiRows.forEach(row => {
                    const isManual = !row.dataset.imei;
                    const imeiNumber = isManual ?
                        (row.querySelector('.new-imei-input')?.value || '').toLowerCase() :
                        row.dataset.imei.toLowerCase();

                    const checkbox = row.querySelector('.imei-checkbox');
                    const isChecked = checkbox?.checked || false;

                    let matchesSearch = imeiNumber.includes(searchTerm);
                    let matchesFilter = true;

                    if (filterType === 'checked') {
                        matchesFilter = isChecked;
                    } else if (filterType === 'unchecked') {
                        matchesFilter = !isChecked;
                    }

                    row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
                });
            }

            searchInput?.addEventListener('input', applyFilters);
            filterSelect?.addEventListener('change', applyFilters);
        }

        function setupConfirmHandler(modal, product, stockEntry, selectedBatch, tbody, imeiRows) {
            document.getElementById('confirmImeiSelection').onclick = function() {
                const checkboxes = document.querySelectorAll('.imei-checkbox:not(.manual-checkbox)');
                const manualInputs = document.querySelectorAll('.new-imei-input');

                const selectedImeis = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
                const newImeis = Array.from(manualInputs).map(input => input.value.trim()).filter(val =>
                    val);

                const allImeis = [...selectedImeis, ...newImeis];
                const uniqueImeis = [...new Set(allImeis)];

                highlightDuplicates(allImeis, tbody);

                if (allImeis.length !== uniqueImeis.length) {
                    toastr.error("Duplicate IMEI found. Please enter unique IMEIs.");
                    return;
                }

                if (uniqueImeis.length === 0) {
                    toastr.warning("Please select or enter at least one IMEI.");
                    return;
                }

                // Properly hide modal and ensure backdrop is removed
                try {
                    modal.hide();
                    // Remove any remaining backdrop manually as a fallback
                    setTimeout(() => {
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        // Ensure body overflow is restored
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 300);
                } catch (error) {
                    console.error('Error hiding modal:', error);
                    // Force cleanup if modal.hide() fails
                    const modalElement = document.getElementById('imeiModal');
                    if (modalElement) {
                        modalElement.classList.remove('show');
                        modalElement.style.display = 'none';
                        modalElement.setAttribute('aria-hidden', 'true');
                        modalElement.removeAttribute('aria-modal');
                        modalElement.removeAttribute('role');
                    }
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }

                // Check if we have stored batch information from the product modal
                let batchId, price, currentCustomer;

                if (window.modalSelectedBatch) {
                    // Use the stored batch information from the product modal
                    batchId = window.modalSelectedBatch.batchId;
                    price = window.modalSelectedBatch.price;
                    currentCustomer = getCurrentCustomer();

                    console.log('Using stored batch information from product modal:', window
                        .modalSelectedBatch);

                    // Clear the stored information
                    delete window.modalSelectedBatch;
                } else {
                    // Use the default logic
                    batchId = selectedBatch ? selectedBatch.id : "all";
                    currentCustomer = getCurrentCustomer();
                    const priceResult = getCustomerTypePrice(selectedBatch, product, currentCustomer
                        .customer_type);

                    if (priceResult.hasError) {
                        toastr.error(
                            `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                            'Pricing Error');
                        return;
                    }
                    price = priceResult.price;
                }

                const imeiLocationId = selectedBatch?.location_batches?.[0]?.location_id ??
                    selectedLocationId;

                if (newImeis.length > 0) {
                    // Use intelligent batch selection - just send product_id, location_id, and imeis
                    fetch('/save-or-update-imei', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .content
                            },
                            body: JSON.stringify({
                                product_id: product.id,
                                location_id: imeiLocationId,
                                imeis: newImeis
                            })
                        }).then(response => response.json())
                        .then(data => {
                            if (data.status === 200) {
                                const message = data.message ||
                                    `${newImeis.length} IMEI(s) added successfully with intelligent batch selection.`;
                                toastr.success(message);
                                updateBilling(uniqueImeis, product, stockEntry, price, batchId);
                            } else {
                                toastr.error(data.message || "Failed to save new IMEIs");
                            }
                            // Final cleanup to ensure modal is completely closed
                            window.cleanupModalBackdrop();
                        })
                        .catch(err => {
                            console.error(err);
                            toastr.error("Error saving new IMEIs");
                            // Final cleanup to ensure modal is completely closed
                            window.cleanupModalBackdrop();
                        });
                } else {
                    updateBilling(uniqueImeis, product, stockEntry, price, batchId);
                    // Final cleanup to ensure modal is completely closed
                    window.cleanupModalBackdrop();
                }
            };
        }

        function highlightDuplicates(imeis, tbody) {
            const counts = {};
            imeis.forEach(imei => counts[imei] = (counts[imei] || 0) + 1);

            tbody.querySelectorAll('tr').forEach(row => {
                const isManual = !row.dataset.imei;
                const imei = isManual ? row.querySelector('.new-imei-input')?.value.trim() : row.dataset
                    .imei;

                if (counts[imei] > 1) {
                    row.style.backgroundColor = "#fff3cd"; // Light Yellow
                } else {
                    row.style.backgroundColor = ""; // Reset
                }
            });
        }

        function updateBilling(imeis, product, stockEntry, price, batchId) {
            const existingRows = Array.from(document.querySelectorAll('#billing-body tr'))
                .filter(row => row.querySelector('.product-id')?.textContent == product.id);

            existingRows.forEach(row => row.remove());

            // Get current customer and determine appropriate price
            const currentCustomer = getCurrentCustomer();

            // Ensure batches is always an array using helper function
            const batchesArray = normalizeBatches(stockEntry);
            const selectedBatch = batchesArray.find(b => b.id === parseInt(batchId));

            // Get customer-type-based price
            const priceResult = getCustomerTypePrice(selectedBatch, product, currentCustomer.customer_type);

            if (priceResult.hasError) {
                toastr.error(
                    `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                    'Pricing Error');
                return;
            }

            // *** FIX: Create separate billing row for each IMEI ***
            // Each IMEI = One row with quantity = 1 (no grouping by batch)
            console.log('Adding separate billing rows for each IMEI:', imeis);

            imeis.forEach(imeiNumber => {
                // Find the IMEI object in stockEntry to get its batch_id
                const imeiObj = stockEntry.imei_numbers?.find(imei => imei.imei_number === imeiNumber);
                const imeiBatchId = imeiObj ? imeiObj.batch_id : batchId;

                console.log(
                    `Creating individual billing row for IMEI: ${imeiNumber}, Batch: ${imeiBatchId}`
                );

                // Find the appropriate batch for pricing
                const batchForPricing = batchesArray.find(b => b.id === parseInt(imeiBatchId)) ||
                    batchesArray.find(b => b.id === parseInt(batchId)) ||
                    batchesArray[0]; // Fallback to first batch

                // Get customer-type-based price for this specific batch
                const batchPriceResult = getCustomerTypePrice(batchForPricing, product, currentCustomer
                    .customer_type);

                let finalPrice;
                if (batchPriceResult.hasError) {
                    console.warn(`Price error for batch ${imeiBatchId}, using stored price`);
                    finalPrice = price; // Use the price passed from the modal
                } else {
                    finalPrice = batchPriceResult.price;
                }

                // Add individual billing row for this single IMEI with quantity = 1
                addProductToBillingBody(
                    product,
                    stockEntry,
                    finalPrice,
                    imeiBatchId,
                    1, // batchQuantity = 1 for individual IMEI
                    currentCustomer.customer_type,
                    1, // saleQuantity = 1 for individual IMEI
                    [imeiNumber], // Array with single IMEI
                    null, // discountType
                    null, // discountAmount  
                    batchForPricing // selectedBatch
                );
            });

            // Reset lastAddedProduct to allow adding more products
            lastAddedProduct = null;
            
            updateTotals();
            fetchPaginatedProducts(true);
        }

        function addNewImeiRow(count, tbody, imeiRows) {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${tbody.querySelectorAll('tr').length + 1}</td>
                <td><input type="checkbox" class="imei-checkbox manual-checkbox" disabled /></td>
                <td>
                    <div class="input-group">
                        <input type="text" class="form-control new-imei-input" placeholder="Enter IMEI" maxlength="15" oninput="this.value=this.value.replace(/[^0-9]/g,'')" />
                        <button type="button" class="btn btn-danger btn-sm remove-imei-row">&times;</button>
                    </div>
                </td>
                <td><span class="badge bg-secondary">Manual</span></td>
                <td></td>
            `;

            const removeBtn = row.querySelector('.remove-imei-row');
            removeBtn?.addEventListener('click', function(e) {
                e.stopPropagation();
                row.remove();
                count++;
                toggleAddButton(count);
            });

            const input = row.querySelector('.new-imei-input');
            const checkbox = row.querySelector('.imei-checkbox');

            input.addEventListener('input', () => {
                checkbox.checked = input.value.trim() !== "";
            });

            tbody.appendChild(row);
            imeiRows.push(row);
            input.focus();
            count--;
            toggleAddButton(count);
        }

        function setupAddButtonContainer(count) {
            const container = document.getElementById('add-button-container') || (() => {
                const el = document.createElement('div');
                el.id = 'add-button-container';
                document.getElementById('imeiModalFooter').appendChild(el);
                return el;
            })();

            toggleAddButton(count);
        }

        function toggleAddButton(count) {
            const container = document.getElementById('add-button-container');
            if (!container) return;

            if (count > 0) {
                container.innerHTML =
                    `<button id="add-new-imei-btn" class="btn btn-sm btn-primary mt-2">+ Add New IMEI</button>`;
                document.getElementById('add-new-imei-btn').addEventListener('click', () => {
                    addNewImeiRow(count, document.getElementById('imei-table-body'), []);
                });
            } else {
                container.innerHTML = '';
            }
        }

        function attachEditRemoveHandlers() {
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('edit-imei-btn')) handleEditImei(e);
                if (e.target.classList.contains('remove-imei-btn')) handleDeleteImei(e);
            });
        }

        function handleEditImei(e) {
            const row = e.target.closest('tr');
            const displayCell = row.querySelector('.imei-display');
            const originalImei = displayCell.textContent.trim();
            const imeiId = row.dataset.imeiId;

            if (!imeiId) return toastr.error("IMEI ID not found. Can't update.");

            displayCell.innerHTML =
                `<input type="text" class="form-control edit-imei-input" value="${originalImei}" />`;
            e.target.textContent = "Update";
            e.target.classList.replace("btn-warning", "btn-success");

            e.target.onclick = function() {
                const newImei = row.querySelector('.edit-imei-input').value.trim();
                if (!newImei) return toastr.error("IMEI cannot be empty.");

                fetch('/update-imei', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            id: imeiId,
                            new_imei: newImei
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if (data.status === 200) {
                            displayCell.textContent = newImei;
                            row.dataset.imei = newImei;
                            row.querySelector('.imei-checkbox').value = newImei;
                            e.target.textContent = "Edit";
                            e.target.classList.replace("btn-success", "btn-warning");
                            toastr.success("IMEI updated successfully!");
                        } else {
                            toastr.error(data.message || "Failed to update IMEI");
                        }
                    }).catch(() => toastr.error("Network error updating IMEI"));
            };
        }

        function handleDeleteImei(e) {
            const row = e.target.closest('tr');
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            document.getElementById('confirmDeleteBtn').onclick = function() {
                const imeiId = row.dataset.imeiId;
                fetch('/delete-imei', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            id: imeiId
                        })
                    }).then(res => res.json())
                    .then(data => {
                        if (data.status === 200) {
                            row.remove();
                            toastr.success("IMEI deleted successfully!");
                            fetchPaginatedProducts(true);
                        } else {
                            toastr.error(data.message || "Failed to delete IMEI");
                        }
                    }).catch(() => toastr.error("Network error deleting IMEI"));

                modal.hide();
            };
            modal.show();
        }

        function showProductModal(product, stockEntry, row) {
            const modalBody = document.getElementById('productModalBody');
            const basePrice = product.retail_price;
            const discountAmount = product.discount_amount || 0;
            const finalPrice = product.discount_type === 'percentage' ?
                basePrice * (1 - discountAmount / 100) :
                basePrice - discountAmount;

            // Store product and stock entry for IMEI handling
            currentImeiProduct = product;
            currentImeiStockEntry = stockEntry;

            // Get current customer for default selection
            const currentCustomer = getCurrentCustomer();
            console.log('Current customer in modal:', currentCustomer);

            let batchOptions = '';
            let locationBatches = [];

            // Normalize batches to array using helper function
            const batchesArray = normalizeBatches(stockEntry);

            // Only show batches for the selected location
            locationBatches = batchesArray
                .filter(batch =>
                    Array.isArray(batch.location_batches) &&
                    batch.location_batches.some(lb => String(lb.location_id) == String(selectedLocationId))
                )
                .map(batch => {
                    // Find the location batch for the selected location
                    const locationBatch = batch.location_batches.find(lb => String(lb.location_id) ==
                        String(selectedLocationId));
                    return {
                        batch_id: batch.id,
                        batch_no: batch.batch_no,
                        retail_price: parseFloat(batch.retail_price),
                        wholesale_price: parseFloat(batch.wholesale_price),
                        special_price: parseFloat(batch.special_price),
                        max_retail_price: parseFloat(batch.max_retail_price) || parseFloat(product
                            .max_retail_price),
                        batch_quantity: locationBatch ? parseFloat(locationBatch.quantity) : 0,
                        created_at: batch.created_at || null // If available
                    };
                })
                .filter(batch => batch.batch_quantity > 0);

            // Calculate total quantity for all batches in the selected location
            let totalQuantity = 0;
            if (batchesArray.length > 0) {
                totalQuantity = batchesArray.reduce((sum, batch) => {
                    if (Array.isArray(batch.location_batches)) {
                        return sum + batch.location_batches
                            .filter(lb => String(lb.location_id) == String(selectedLocationId))
                            .reduce((s, lb) => s + (parseFloat(lb.quantity) || 0), 0);
                    }
                    return sum;
                }, 0);
            }

            // Find latest batch by created_at or by highest batch_id
            let latestBatch = null;
            if (locationBatches.length > 0) {
                latestBatch = locationBatches.reduce((latest, current) => {
                    if (current.created_at && latest.created_at) {
                        return new Date(current.created_at) > new Date(latest.created_at) ?
                            current : latest;
                    }
                    // fallback: use batch_id as number
                    return (parseInt(current.batch_id) > parseInt(latest.batch_id)) ? current :
                        latest;
                }, locationBatches[0]);
            }

            // Determine which price types are available (non-zero)
            let hasWholesale = false;
            let hasSpecial = false;
            let hasRetail = true; // Always show retail

            if (locationBatches.length > 0) {
                hasWholesale = locationBatches.some(batch => batch.wholesale_price > 0);
                hasSpecial = locationBatches.some(batch => batch.special_price > 0);
            }

            // Default price type based on customer type
            let defaultPriceType = 'retail';
            if (currentCustomer.customer_type === 'wholesaler' && hasWholesale) {
                defaultPriceType = 'wholesale';
            }

            if (locationBatches.length > 0) {
                // Build batch options with all available prices
                batchOptions = locationBatches.map((batch, idx) => {
                    let priceDisplay =
                        `R: ${formatAmountWithSeparators(batch.retail_price.toFixed(2))}`;

                    if (batch.wholesale_price > 0) {
                        priceDisplay +=
                            ` | W: ${formatAmountWithSeparators(batch.wholesale_price.toFixed(2))}`;
                    }

                    if (batch.special_price > 0) {
                        priceDisplay +=
                            ` | S: ${formatAmountWithSeparators(batch.special_price.toFixed(2))}`;
                    }

                    priceDisplay +=
                        ` | MRP: ${formatAmountWithSeparators(batch.max_retail_price.toFixed(2))}`;

                    return `
                        <option value="${batch.batch_id}" 
                        data-retail-price="${batch.retail_price}" 
                        data-wholesale-price="${batch.wholesale_price}" 
                        data-special-price="${batch.special_price}" 
                        data-max-retail-price="${batch.max_retail_price}"
                        data-quantity="${batch.batch_quantity}">
                        ${batch.batch_no} - Qty: ${formatAmountWithSeparators(batch.batch_quantity)} - ${priceDisplay}
                        </option>
                    `;
                }).join('');

                // Build price type radio buttons (only show available options)
                let priceTypeButtons = '';

                // Always show retail
                const isRetailSelected = defaultPriceType === 'retail';
                priceTypeButtons += `
                    <label class="btn btn-outline-primary ${isRetailSelected ? 'active' : ''}">
                        <input type="radio" name="modal-price-type" value="retail" ${isRetailSelected ? 'checked' : ''} hidden> 
                        <i class="fas fa-star"></i> R
                    </label>
                `;

                // Show wholesale if available
                if (hasWholesale) {
                    const isWholesaleSelected = defaultPriceType === 'wholesale';
                    priceTypeButtons += `
                        <label class="btn btn-outline-primary ${isWholesaleSelected ? 'active' : ''}">
                            <input type="radio" name="modal-price-type" value="wholesale" ${isWholesaleSelected ? 'checked' : ''} hidden> 
                            <i class="fas fa-star"></i><i class="fas fa-star"></i> W
                        </label>
                    `;
                }

                // Show special if available
                if (hasSpecial) {
                    const isSpecialSelected = defaultPriceType === 'special';
                    priceTypeButtons += `
                        <label class="btn btn-outline-primary ${isSpecialSelected ? 'active' : ''}">
                            <input type="radio" name="modal-price-type" value="special" ${isSpecialSelected ? 'checked' : ''} hidden> 
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> S
                        </label>
                    `;
                }

                // Calculate default prices for "All" option
                let allRetailPrice = latestBatch ? latestBatch.retail_price : finalPrice;
                let allWholesalePrice = latestBatch ? latestBatch.wholesale_price : 0;
                let allSpecialPrice = latestBatch ? latestBatch.special_price : 0;
                let allMrpPrice = latestBatch ? latestBatch.max_retail_price : parseFloat(product
                    .max_retail_price);

                modalBody.innerHTML = `
                    <div class="d-flex align-items-center">
                    <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;"/>
                    <div>
                        <div class="font-weight-bold">${product.product_name}</div>
                        <div class="text-muted">${product.sku}</div>
                        ${product.description ? `<div class="text-muted small">${product.description}</div>` : ''}
                    </div>
                    </div>
                    <div class="btn-group btn-group-toggle mt-3" data-toggle="buttons">
                    ${priceTypeButtons}
                    </div>
                    <select id="modalBatchDropdown" class="form-select mt-3">
                    <option value="all" 
                        data-retail-price="${allRetailPrice}" 
                        data-wholesale-price="${allWholesalePrice}" 
                        data-special-price="${allSpecialPrice}" 
                        data-max-retail-price="${allMrpPrice}"
                        data-quantity="${totalQuantity}" selected>
                        All - Qty: ${formatAmountWithSeparators(totalQuantity)} - R: ${formatAmountWithSeparators(allRetailPrice.toFixed(2))}${allWholesalePrice > 0 ? ' | W: ' + formatAmountWithSeparators(allWholesalePrice.toFixed(2)) : ''}${allSpecialPrice > 0 ? ' | S: ' + formatAmountWithSeparators(allSpecialPrice.toFixed(2)) : ''} | MRP: ${formatAmountWithSeparators(allMrpPrice.toFixed(2))}
                    </option>
                    ${batchOptions}
                    </select>
                `;
            } else {
                // No valid batches
                modalBody.innerHTML = `<div>No valid batches found for the product in this location.</div>`;
            }

            selectedRow = row;
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();

            const radioButtons = document.querySelectorAll('input[name="modal-price-type"]');
            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.btn-group-toggle .btn').forEach(btn => btn
                        .classList.remove('active'));
                    this.parentElement.classList.add('active');
                });
            });

            // Attach change handler on dropdown to update max quantity
            const batchDropdown = document.getElementById('modalBatchDropdown');
            if (batchDropdown) {
                batchDropdown.addEventListener('change', () => {
                    const selectedOption = batchDropdown.selectedOptions[0];
                    if (!selectedOption) return;

                    const maxQty = parseFloat(selectedOption.getAttribute('data-quantity'));
                    const qtyInput = selectedRow?.querySelector('.quantity-input');

                    if (qtyInput) {
                        qtyInput.setAttribute('max', maxQty);
                        qtyInput.setAttribute('title', `Available: ${maxQty}`);
                    }
                });
            }
        }

        function addProductToBillingBody(product, stockEntry, price, batchId, batchQuantity, priceType,
            saleQuantity = 1, imeis = [], discountType = null, discountAmount = null, selectedBatch = null) {

            console.log('addProductToBillingBody called with:', {
                productId: product.id,
                productName: product.product_name,
                batchId: batchId,
                price: price,
                saleQuantity: saleQuantity,
                imeis: imeis
            });

            const billingBody = document.getElementById('billing-body');
            locationId = selectedLocationId || 1;

            // Use selectedBatch if provided; fallback to stockEntry batch
            const batch = selectedBatch || normalizeBatches(stockEntry).find(b => b.id === parseInt(batchId));

            // The price parameter is already calculated based on customer type in the calling function
            // So we use it directly instead of recalculating
            price = parseFloat(price);

            if (isNaN(price) || price <= 0) {
                console.error('Invalid price for product:', product.product_name, 'Price:', price);

                // Get customer type for error message
                const currentCustomer = getCurrentCustomer();
                toastr.error(
                    `This product has no valid price configured for ${currentCustomer.customer_type} customers. Please contact admin to fix pricing.`,
                    'Pricing Error');

                // Log the error
                logPricingError(product, currentCustomer.customer_type, batch);
                return;
            }

            const activeDiscount = stockEntry.discounts?.find(d => d.is_active && !d.is_expired) || null;

            let finalPrice = price;
            let discountFixed = 0;
            let discountPercent = 0;

            // Helper: Calculate default discount using MRP - customer type price
            const defaultFixedDiscount = product.max_retail_price - price;

            // Priority order:
            // 1. Manual discount
            // 2. Active discount
            // 3. Default (MRP - customer type price)
            if (discountType && discountAmount !== null) {
                console.log('Applying manual discount:', {discountType, discountAmount, productName: product.product_name});
                if (discountType === 'fixed') {
                    discountFixed = parseFloat(discountAmount);
                    finalPrice = product.max_retail_price - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                    console.log('Fixed discount applied:', {discountFixed, finalPrice, MRP: product.max_retail_price});
                } else if (discountType === 'percentage') {
                    discountPercent = parseFloat(discountAmount);
                    finalPrice = product.max_retail_price * (1 - discountPercent / 100);
                    console.log('Percentage discount applied:', {discountPercent, finalPrice, MRP: product.max_retail_price});
                }
            } else if (activeDiscount) {
                if (activeDiscount.type === 'percentage') {
                    discountPercent = activeDiscount.amount;
                    finalPrice = product.max_retail_price * (1 - discountPercent / 100);
                } else if (activeDiscount.type === 'fixed') {
                    discountFixed = activeDiscount.amount;
                    finalPrice = product.max_retail_price - discountFixed;
                    if (finalPrice < 0) finalPrice = 0;
                }
            } else {
                discountFixed = defaultFixedDiscount;
                discountPercent = (discountFixed / product.max_retail_price) * 100;
                finalPrice = price; // Use customer type-specific price
            }

            console.log('Final discount values for product:', product.product_name, {
                discountFixed: discountFixed,
                discountPercent: discountPercent,
                finalPrice: finalPrice,
                discountType: discountType,
                discountAmount: discountAmount,
                isEditing: isEditing
            });

            let adjustedBatchQuantity = batchQuantity;
            if (batchId === "all") {
                adjustedBatchQuantity = stockEntry.total_stock;
            } else if (batch && batch.location_batches) {
                const locationBatch = batch.location_batches.find(lb => lb.location_id === locationId);
                if (locationBatch) {
                    adjustedBatchQuantity = parseFloat(locationBatch.quantity);
                }
            }

            // In edit mode, use the provided batchQuantity (which comes from backend calculation)
            // This already includes the correct max available for editing
            if (isEditing) {
                adjustedBatchQuantity = batchQuantity; // Trust the backend calculation
                console.log(`Edit mode: Using backend calculated max quantity: ${adjustedBatchQuantity}`);
            }

            // Get unit name and allow_decimal from product.unit (if available)
            const unitName = product.unit && product.unit.name ? product.unit.name : 'Pc(s)';
            const allowDecimal = product.unit && (product.unit.allow_decimal === true || product.unit
                .allow_decimal === 1);

            // Format adjustedBatchQuantity based on allowDecimal (rounded to 2 decimals)
            if (allowDecimal) {
                adjustedBatchQuantity = parseFloat(adjustedBatchQuantity).toFixed(2).replace(/\.?0+$/, '');
            } else {
                adjustedBatchQuantity = parseInt(adjustedBatchQuantity, 10);
            }

            // If allowDecimal, use step="any" and allow decimal input, else step="1"
            const qtyInputStep = allowDecimal ? 'any' : '1';
            const qtyInputPattern = allowDecimal ? '[0-9]+([.][0-9]{1,2})?' : '[0-9]*';

            // Determine initial quantity value for input
            let initialQuantityValue;
            if (isEditing && saleQuantity !== undefined) {
                // In edit mode, use the actual sale quantity
                initialQuantityValue = allowDecimal ? parseFloat(saleQuantity).toFixed(2).replace(/\.?0+$/,
                    '') : parseInt(saleQuantity, 10);
            } else if (imeis.length > 0) {
                // *** FIX: For IMEI products, each row should always have quantity = 1 ***
                // Since we now create separate rows for each IMEI, each row gets exactly 1 IMEI
                initialQuantityValue = 1;
            } else if (allowDecimal) {
                // For decimal units, use the available stock as default if less than 1, else 1
                let availableQty = parseFloat(adjustedBatchQuantity);
                if (availableQty < 1 && availableQty > 0) {
                    initialQuantityValue = availableQty.toFixed(2).replace(/\.?0+$/, '');
                } else {
                    initialQuantityValue = '1.00';
                }
            } else {
                initialQuantityValue = 1;
            }

            // If not IMEI and not in edit mode, try to merge row
            if (imeis.length === 0 && !isEditing) {
                const existingRow = Array.from(billingBody.querySelectorAll('tr')).find(row => {
                    const productIdElement = row.querySelector('.product-id');
                    const batchIdElement = row.querySelector('.batch-id');
                    const priceInputElement = row.querySelector('.price-input');

                    if (!productIdElement || !batchIdElement || !priceInputElement) {
                        return false;
                    }

                    const rowProductId = productIdElement.textContent.trim();
                    const rowBatchId = batchIdElement.textContent.trim();
                    const rowPrice = priceInputElement.value.trim();

                    // Debug logging
                    console.log('Checking merge for:', {
                        existingProductId: rowProductId,
                        newProductId: product.id,
                        existingBatchId: rowBatchId,
                        newBatchId: batchId,
                        existingPrice: parseFloat(rowPrice).toFixed(2),
                        newPrice: finalPrice.toFixed(2)
                    });

                    // For products that appear identical (same product, same price), 
                    // merge regardless of batch to avoid confusion
                    return (
                        rowProductId == product.id &&
                        parseFloat(rowPrice).toFixed(2) === finalPrice.toFixed(2)
                    );

                    // Original strict matching (uncomment if you want batch-specific rows):
                    // return (
                    //     rowProductId == product.id &&
                    //     rowBatchId == batchId &&
                    //     parseFloat(rowPrice).toFixed(2) === finalPrice.toFixed(2)
                    // );
                });
                if (existingRow) {
                    const quantityInput = existingRow.querySelector('.quantity-input');
                    let currentQty = allowDecimal ? parseFloat(quantityInput.value) : parseInt(quantityInput
                        .value, 10);
                    let newQuantity = currentQty + saleQuantity;

                    // Use parseFloat for decimal allowed, parseInt for integer
                    if (newQuantity > adjustedBatchQuantity && product.stock_alert !== 0) {
                        toastr.error(`You cannot add more than ${adjustedBatchQuantity} units of this product.`,
                            'Warning');
                        return;
                    }

                    quantityInput.value = allowDecimal ? newQuantity.toFixed(4).replace(/\.?0+$/, '') :
                        newQuantity;
                    const subtotalElement = existingRow.querySelector('.subtotal');
                    const updatedSubtotal = newQuantity * finalPrice;
                    subtotalElement.textContent = formatAmountWithSeparators(updatedSubtotal.toFixed(2));

                    updateTotals();
                    return;
                }
            }

            const row = document.createElement('tr');

            // Add data attributes for price updating functionality
            row.setAttribute('data-product-id', product.id);
            row.setAttribute('data-batch-id', batchId);
            row.setAttribute('data-unit-price', finalPrice);
            row.setAttribute('data-price-source', priceType);

            row.innerHTML = `
        <td class="text-center counter-cell" style="vertical-align: middle; font-weight: bold; color: #000;"></td>
        <td>
            <div class="d-flex align-items-start">
            <img src="/assets/images/${product.product_image || 'No Product Image Available.png'}" style="width:50px; height:50px; margin-right:10px; border-radius:50%;" class="product-image"/>
            <div class="product-info" style="min-width: 0; flex: 1;">
            <div class="font-weight-bold product-name" style="word-break: break-word; max-width: 260px; line-height: 1.2;">
            ${product.product_name}
            <span class="badge bg-info ms-1">MRP: ${product.max_retail_price}</span>
            </div>
            <div class="d-flex flex-wrap align-items-center mt-1" style="gap: 10px;">
            <span class="text-muted product-sku" style="font-size: 0.95em; word-break: break-all;">
            SKU: ${product.sku}
            </span>
            <span class="quantity-display ms-2" style="font-size: 0.95em;">
             ${adjustedBatchQuantity} ${unitName}
            </span>
            ${product.is_imei_or_serial_no === 1 ? `<span class="badge bg-info ms-2">IMEI</span>
              <i class="fas fa-info-circle show-imei-btn ms-1" style="cursor: pointer;" title="View/Edit IMEI"></i>` : ''}
            </div>
            </div>
            </div>
        </td>
        <td>
            <div class="d-flex justify-content-center">
            <button class="btn btn-danger quantity-minus btn">-</button>
            <input type="number" value="${initialQuantityValue}" max="${adjustedBatchQuantity}" class="form-control quantity-input text-center" title="Available: ${adjustedBatchQuantity}" ${imeis.length > 0 ? 'readonly' : ''} step="${qtyInputStep}" pattern="${qtyInputPattern}" data-quantity="${initialQuantityValue}">
            <button class="btn btn-success quantity-plus btn">+</button>
            </div>
            <div style="font-size: 0.85em; color: #888; text-align:center;">${unitName}</div>
        </td>
        <td><input type="number" name="discount_fixed[]" class="form-control fixed_discount" value="${discountFixed.toFixed(2)}"></td>
        <td><input type="number" name="discount_percent[]" class="form-control percent_discount" value="${discountPercent.toFixed(2)}"></td>
        <td><input type="number" value="${finalPrice.toFixed(2)}" class="form-control price-input unit-price text-center" 
            data-price="${finalPrice}"
            data-quantity="${adjustedBatchQuantity}" 
            data-retail-price="${batch ? batch.retail_price : product.retail_price}"
            data-wholesale-price="${batch ? batch.wholesale_price : (stockEntry.batches?.[0]?.wholesale_price || 0)}"
            data-special-price="${batch ? batch.special_price : (stockEntry.batches?.[0]?.special_price || 0)}"
            data-max-retail-price="${batch ? batch.max_retail_price || product.max_retail_price : product.max_retail_price}"
            min="0" readonly></td>
        <td class="subtotal total-price" data-total="${(parseFloat(initialQuantityValue) * finalPrice).toFixed(2)}">${formatAmountWithSeparators((parseFloat(initialQuantityValue) * finalPrice).toFixed(2))}</td>
        <td><button class="btn btn-danger btn-sm remove-btn">×</button></td>
        <td class="product-id d-none">${product.id}</td>
        <td class="location-id d-none">${locationId}</td>
        <td class="batch-id d-none">${batchId}</td>
        <td class="discount-data d-none">${JSON.stringify(activeDiscount || {})}</td>
        <td class="d-none imei-data">${imeis.join(',') || ''}</td>
        `;

            // Append the row first to ensure elements are available
            billingBody.insertBefore(row, billingBody.firstChild);

            // Now query the elements after inserting into DOM
            const qtyDisplayCell = row.querySelector('.quantity-display');
            const quantityInput = row.querySelector('.quantity-input');
            const plusBtn = row.querySelector('.quantity-plus');
            const minusBtn = row.querySelector('.quantity-minus');
            const showImeiBtn = row.querySelector('.show-imei-btn');

            // Handle IMEI display and input restrictions
            if (imeis.length > 0) {
                if (qtyDisplayCell) {
                    // *** FIX: Each IMEI row shows "1 of 1" since it represents a single IMEI ***
                    qtyDisplayCell.textContent = `1 ${unitName} (IMEI: ${imeis[0]})`;
                }
                if (quantityInput) quantityInput.readOnly = true;
                if (plusBtn) plusBtn.disabled = true;
                if (minusBtn) minusBtn.disabled = true;
            }

            attachRowEventListeners(row, product, stockEntry);

            // Focus search input on Enter key
            if (quantityInput) {
                quantityInput.focus();
                quantityInput.select();

                quantityInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        const searchInput = document.getElementById('productSearchInput');
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.focus();
                        }
                    }
                });
            }

            disableConflictingDiscounts(row);
            
            // Debug: Log actual DOM values after disableConflictingDiscounts
            const fixedInputDebug = row.querySelector('.fixed_discount');
            const percentInputDebug = row.querySelector('.percent_discount');
            console.log('After disableConflictingDiscounts for:', product.product_name, {
                fixedValue: fixedInputDebug?.value,
                percentValue: percentInputDebug?.value,
                fixedDisabled: fixedInputDebug?.disabled,
                percentDisabled: percentInputDebug?.disabled
            });
            
            updateTotals();

            // Auto-focus search input after adding product for quick next product search
            setTimeout(() => {
                const productSearchInput = document.getElementById('productSearchInput');
                if (productSearchInput) {
                    productSearchInput.focus();
                    productSearchInput.select(); // Select any existing text
                }
            }, 100); // Quick focus after product is added
        }
        // Global flag to throttle error display
        let isErrorShown = false;

        // Throttled function to show error only once within a time window
        function showQuantityLimitError(maxQuantity) {
            if (!isErrorShown) {
                const errorSound = document.getElementsByClassName('errorSound')[0];
                if (errorSound) {
                    errorSound.play(); // Play sound only once
                }

                toastr.error(`You cannot add more than ${maxQuantity} units of this product.`, 'Error');

                isErrorShown = true;

                // Allow error to be shown again after 2 seconds
                setTimeout(() => {
                    isErrorShown = false;
                }, 2000); // Adjust this duration as needed
            }
        }

        function attachRowEventListeners(row, product, stockEntry) {
            const quantityInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('.price-input');
            const quantityMinus = row.querySelector('.quantity-minus');
            const quantityPlus = row.querySelector('.quantity-plus');
            const removeBtn = row.querySelector('.remove-btn');
            const productImage = row.querySelector('.product-image');
            const productName = row.querySelector('.product-name');
            const fixedDiscountInput = row.querySelector(".fixed_discount");
            const percentDiscountInput = row.querySelector(".percent_discount");

            // Get allowDecimal for this product
            const allowDecimal = product.unit && (product.unit.allow_decimal === true || product.unit
                .allow_decimal === 1);


            // Handle discount inputs and price editability
            if (fixedDiscountInput) {
                // Allow free typing, validate only on change/blur
                fixedDiscountInput.addEventListener('change', () => {
                    handleDiscountToggle(fixedDiscountInput);
                    validateDiscountInput(row, fixedDiscountInput, 'fixed');
                    updatePriceEditability(row);
                    updateTotals();
                });
                fixedDiscountInput.addEventListener('blur', () => {
                    handleDiscountToggle(fixedDiscountInput);
                    validateDiscountInput(row, fixedDiscountInput, 'fixed');
                    updatePriceEditability(row);
                    updateTotals();
                });
            }
            if (percentDiscountInput) {
                // Allow free typing, validate only on change/blur
                percentDiscountInput.addEventListener('change', () => {
                    handleDiscountToggle(percentDiscountInput);
                    validateDiscountInput(row, percentDiscountInput, 'percent');
                    updatePriceEditability(row);
                    updateTotals();
                });
                percentDiscountInput.addEventListener('blur', () => {
                    handleDiscountToggle(percentDiscountInput);
                    validateDiscountInput(row, percentDiscountInput, 'percent');
                    updatePriceEditability(row);
                    updateTotals();
                });
            }

            // Price input change → Validate minimum price and recalculate discount
            // Allow free typing, validate only on change/blur
            priceInput.addEventListener('change', () => {
                validatePriceInput(row, priceInput);
            });
            priceInput.addEventListener('blur', () => {
                validatePriceInput(row, priceInput);
            });

            // Initial price editability check
            updatePriceEditability(row);



            quantityInput.addEventListener('input', () => {
                const value = quantityInput.value.trim();
                const maxQuantity = parseFloat(priceInput.getAttribute('data-quantity'));

                if (allowDecimal) {
                    const validDecimalPattern = /^\d*\.?\d{0,2}$/; // Only allow up to 2 decimals

                    if (value === '' || validDecimalPattern.test(value)) {
                        quantityInput.classList.remove('is-invalid');

                        updateTotals();

                        if (value !== '' && !value.endsWith('.')) {
                            const quantityValue = parseFloat(value);
                            if (!isNaN(quantityValue)) {
                                if (quantityValue > maxQuantity && product.stock_alert !== 0) {
                                    toastr.warning(
                                        `You are entering more than available stock for ${product.product_name}.`
                                    );
                                }

                                const subtotalElement = row.querySelector('.subtotal');
                                const price = parseFloat(priceInput.value);
                                subtotalElement.textContent = formatAmountWithSeparators((price *
                                    quantityValue).toFixed(2));

                                updateTotals();
                            }
                        }
                    } else {
                        quantityInput.classList.add('is-invalid');
                    }
                } else {
                    // Integer-only validation
                    const validIntegerPattern = /^\d+$/;

                    if (value === '' || validIntegerPattern.test(value)) {
                        quantityInput.classList.remove('is-invalid');
                        updateTotals();

                        const quantityValue = parseInt(value, 10);
                        if (!isNaN(quantityValue)) {
                            if (quantityValue > maxQuantity && product.stock_alert !== 0) {
                                toastr.warning(
                                    `You are entering more than available stock for ${product.product_name}.`
                                );
                            }

                            const subtotalElement = row.querySelector('.subtotal');
                            const price = parseFloat(priceInput.value);
                            subtotalElement.textContent = formatAmountWithSeparators((price *
                                quantityValue).toFixed(2));

                            updateTotals();
                        }
                    } else {
                        quantityInput.classList.add('is-invalid');
                    }
                }
            });

            // Minus button
            quantityMinus.addEventListener('click', () => {
                let currentQuantity = allowDecimal ? parseFloat(quantityInput.value) : parseInt(
                    quantityInput.value, 10);
                if (allowDecimal) {
                    if (currentQuantity > 0.01) {
                        currentQuantity = parseFloat((currentQuantity - 0.01).toFixed(2));
                        if (currentQuantity < 0.01) currentQuantity = 0.01;
                        quantityInput.value = currentQuantity.toFixed(2).replace(/\.?0+$/, '');
                        updateTotals();
                    }
                } else {
                    if (currentQuantity > 1) {
                        currentQuantity = currentQuantity - 1;
                        if (currentQuantity < 1) currentQuantity = 1;
                        quantityInput.value = currentQuantity;
                        updateTotals();
                    }
                }
            });

            // Plus button
            quantityPlus.addEventListener('click', () => {
                let currentQuantity = allowDecimal ? parseFloat(quantityInput.value) : parseInt(
                    quantityInput.value, 10);
                const maxQuantity = parseFloat(priceInput.getAttribute('data-quantity'));
                if (allowDecimal) {
                    if (currentQuantity < maxQuantity || product.stock_alert === 0) {
                        currentQuantity = parseFloat((currentQuantity + 0.01).toFixed(2));
                        quantityInput.value = currentQuantity.toFixed(2).replace(/\.?0+$/, '');
                        updateTotals();
                    } else {
                        showQuantityLimitError(maxQuantity);
                    }
                } else {
                    if (currentQuantity < maxQuantity || product.stock_alert === 0) {
                        currentQuantity = currentQuantity + 1;
                        quantityInput.value = currentQuantity;
                        updateTotals();
                    } else {
                        showQuantityLimitError(maxQuantity);
                    }
                }
            });

            // Event listener for the remove button
            removeBtn.addEventListener('click', () => {
                row.remove();
                updateTotals();
            });

            // Event listener for product image click
            productImage.addEventListener('click', () => {
                showProductModal(product, stockEntry, row);
            });

            // Event listener for product name click
            productName.addEventListener('click', () => {
                showProductModal(product, stockEntry, row);
            });

            const showImeiBtn = row.querySelector('.show-imei-btn');
            if (showImeiBtn) {
                showImeiBtn.addEventListener('click', function() {
                    const imeiDataCell = row.querySelector('.imei-data');
                    const batchIdCell = row.querySelector('.batch-id');
                    const productIdCell = row.querySelector('.product-id');
                    const locationIdCell = row.querySelector('.location-id');
                    
                    const imeis = imeiDataCell ? imeiDataCell.textContent.trim().split(',').filter(
                        Boolean) : [];
                    const batchId = batchIdCell ? batchIdCell.textContent.trim() : null;
                    const productId = productIdCell ? productIdCell.textContent.trim() : product.id;
                    const currentLocationId = locationIdCell ? locationIdCell.textContent.trim() : selectedLocationId;

                    if (imeis.length === 0) {
                        toastr.warning("No IMEIs found for this product.");
                        return;
                    }

                    // Fetch fresh IMEI data from API to ensure we have complete data including IDs
                    console.log('Fetching IMEI data for product:', productId, 'location:', currentLocationId);
                    
                    fetch(`/get-imeis/${productId}?location_id=${currentLocationId}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 200) {
                            console.log('IMEI data fetched successfully:', data.data);
                            
                            // Create a temporary stockEntry with the fetched IMEI data
                            const tempStockEntry = {
                                ...stockEntry,
                                imei_numbers: data.data
                            };
                            
                            // Show IMEI modal with complete data
                            showImeiSelectionModal(product, tempStockEntry, [], '', 'EDIT', batchId !== "all" ? batchId : null);
                        } else {
                            console.error('Failed to fetch IMEI data:', data.message);
                            toastr.error('Failed to load IMEI data: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching IMEI data:', error);
                        toastr.error('Network error while loading IMEI data');
                    });
                });
            }

            // Newly added: Disable conflicting discounts when product is added
            disableConflictingDiscounts(row);
        }

        document.getElementById('saveProductChanges').onclick = function() {
            const selectedPriceType = document.querySelector('input[name="modal-price-type"]:checked')
                .value;
            const selectedBatch = document.getElementById('modalBatchDropdown').selectedOptions[0];
            const batchId = selectedBatch.value;
            const batchQuantity = parseFloat(selectedBatch.getAttribute('data-quantity'));

            // Get price based on selected type, but validate it's not zero
            let price = parseFloat(selectedBatch.getAttribute(`data-${selectedPriceType}-price`));

            // If selected price type is zero, fall back to retail price
            if (!price || price <= 0) {
                console.warn(`${selectedPriceType} price is zero or invalid, falling back to retail price`);
                price = parseFloat(selectedBatch.getAttribute('data-retail-price'));
            }

            // Final validation to prevent zero prices
            if (!price || price <= 0) {
                toastr.error('Invalid price selected. Please contact admin.', 'Pricing Error');
                return;
            }

            // Get the product info from the modal context
            const modalProduct = currentImeiProduct || (selectedRow?.dataset?.product ? JSON.parse(
                selectedRow.dataset.product) : null);

            // Check if this is an IMEI product
            const isImeiProduct = modalProduct && modalProduct.is_imei_or_serial_no === 1;

            if (isImeiProduct) {
                console.log('IMEI product detected in saveProductChanges, opening IMEI modal for batch:',
                    batchId);

                // Close the product modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
                modal.hide();

                // Store the selected batch and pricing information for later use
                window.modalSelectedBatch = {
                    batchId: batchId,
                    price: price,
                    priceType: selectedPriceType,
                    batchQuantity: batchQuantity,
                    mrpPrice: parseFloat(selectedBatch.getAttribute('data-max-retail-price')) || 0
                };

                // Open IMEI selection modal for the selected batch
                setTimeout(() => {
                    const batchIdForImei = batchId === 'all' ? null : batchId;
                    showImeiSelectionModal(modalProduct, currentImeiStockEntry, [], '',
                        'MODAL_SAVE_CHANGES', batchIdForImei);
                }, 300);

                return; // Don't proceed with normal product update
            }

            // Handle non-IMEI products normally
            if (selectedRow) {
                const quantityInput = selectedRow.querySelector('.quantity-input');
                const priceInput = selectedRow.querySelector('.price-input');
                const productNameCell = selectedRow.querySelector('.product-name');
                const productSkuCell = selectedRow.querySelector('.product-sku');

                priceInput.value = price.toFixed(2);
                priceInput.setAttribute('data-quantity', batchQuantity);

                // Recalculate discount based on new price
                const mrpPrice = parseFloat(selectedBatch.getAttribute('data-max-retail-price')) || 0;

                const discountAmount = mrpPrice - price;
                const fixedDiscountInput = selectedRow.querySelector(".fixed_discount");
                const percentDiscountInput = selectedRow.querySelector(".percent_discount");

                // Reset previous discount inputs
                fixedDiscountInput.value = discountAmount > 0 ? discountAmount.toFixed(2) : '0.00';
                percentDiscountInput.value = '';

                // Disable conflicting discounts
                disableConflictingDiscounts(selectedRow);

                // Update subtotal
                const qtyVal = quantityInput.value === "" ? 0 : parseFloat(quantityInput.value);
                const subtotal = qtyVal * price;
                selectedRow.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));

                // Update batch ID and show stars
                selectedRow.querySelector('.batch-id').textContent = batchId;
                const stars = selectedPriceType === 'retail' ? '<i class="fas fa-star"></i>' :
                    selectedPriceType === 'wholesale' ?
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i>' :
                    '<i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>';

                // Clean up existing stars and add new ones
                const skuText = productSkuCell.textContent.replace(/\s*★+\s*/g, '').trim();
                productSkuCell.innerHTML = `${skuText} ${stars}`;

                console.log('Updated price in modal:', {
                    selectedPriceType,
                    price,
                    batchId,
                    mrpPrice,
                    discountAmount
                });

                // Update price editability after modal changes
                updatePriceEditability(selectedRow);
                updateTotals();
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
        };

        function disableConflictingDiscounts(row) {
            const fixed = row.querySelector(".fixed_discount");
            const percent = row.querySelector(".percent_discount");

            if (!fixed || !percent) return;

            const fixedVal = parseFloat(fixed.value) || 0;
            const percentVal = parseFloat(percent.value) || 0;

            if (fixedVal > 0) {
                percent.disabled = true;
                percent.value = '';
            } else if (percentVal > 0) {
                fixed.disabled = true;
                fixed.value = '';
            } else {
                fixed.disabled = false;
                percent.disabled = false;
            }
        }

        function handleDiscountToggle(input) {
            const row = input.closest('tr');
            const fixedDiscountInput = row.querySelector('.fixed_discount');
            const percentDiscountInput = row.querySelector('.percent_discount');
            const priceInput = row.querySelector('.price-input');

            // Get MRP
            const mrpElement = row.querySelector('.product-name .badge.bg-info');
            const mrpText = mrpElement ? mrpElement.textContent.trim() : '0';
            const mrp = parseFloat(mrpText.replace(/[^0-9.-]/g, '')) || 0;

            // Disable conflicting inputs
            if (fixedDiscountInput === input && fixedDiscountInput.value !== '') {
                percentDiscountInput.disabled = true;
                percentDiscountInput.value = '';
            } else if (percentDiscountInput === input && percentDiscountInput.value !== '') {
                fixedDiscountInput.disabled = true;
                fixedDiscountInput.value = '';
            } else {
                fixedDiscountInput.disabled = false;
                percentDiscountInput.disabled = false;
            }

            // Recalculate unit price
            if (fixedDiscountInput.value !== '') {
                const discountAmount = parseFloat(fixedDiscountInput.value);
                const calculatedPrice = mrp - discountAmount;
                priceInput.value = calculatedPrice > 0 ? calculatedPrice.toFixed(2) : '0.00';
            } else if (percentDiscountInput.value !== '') {
                const discountPercent = parseFloat(percentDiscountInput.value);
                const calculatedPrice = mrp * (1 - discountPercent / 100);
                priceInput.value = calculatedPrice > 0 ? calculatedPrice.toFixed(2) : '0.00';
            } else {
                priceInput.value = mrp.toFixed(2);
            }

            updateTotals();
        }

        function updateTotals() {
            const billingBody = document.getElementById('billing-body');
            let totalItems = 0;
            let totalAmount = 0;

            // Calculate total items and total amount from each row and update row counters
            billingBody.querySelectorAll('tr').forEach((row, index) => {
                const quantityInput = row.querySelector('.quantity-input');
                const priceInput = row.querySelector('.price-input');
                const fixedDiscountInput = row.querySelector('.fixed_discount');
                const percentDiscountInput = row.querySelector('.percent_discount');
                const counterCell = row.querySelector('.counter-cell');

                let quantity = 0;
                if (quantityInput) {
                    quantity = quantityInput.value === "" ? 0 : parseFloat(quantityInput.value);
                }
                const basePrice = parseFloat(priceInput.value) || 0;

                // Update row counter (1, 2, 3, etc.)
                if (counterCell) {
                    counterCell.textContent = index + 1;
                }

                // Recalculate subtotal based on unit price
                const subtotal = quantity * basePrice;

                // Update UI
                row.querySelector('.subtotal').textContent = formatAmountWithSeparators(subtotal
                    .toFixed(2));
                totalItems += quantity;
                totalAmount += subtotal;
            });

            // Global discount
            const discountElement = document.getElementById('global-discount');
            const discountTypeElement = document.getElementById('discount-type');
            const globalDiscount = discountElement && discountElement.value ? parseFloat(discountElement
                .value) || 0 : 0;
            const globalDiscountType = discountTypeElement ? discountTypeElement.value : 'fixed';

            // Debug logging
            console.log('Discount Element:', discountElement);
            console.log('Discount Element Value:', discountElement ? discountElement.value : 'null');
            console.log('Global Discount:', globalDiscount);
            console.log('Global Discount Type:', globalDiscountType);
            console.log('Total Amount:', totalAmount);

            let totalAmountWithDiscount = totalAmount;

            if (globalDiscount > 0) {
                if (globalDiscountType === 'percentage') {
                    totalAmountWithDiscount -= totalAmount * (globalDiscount / 100);
                    console.log('Applied percentage discount:', totalAmount * (globalDiscount / 100));
                } else {
                    totalAmountWithDiscount -= globalDiscount;
                    console.log('Applied fixed discount:', globalDiscount);
                }
            }

            console.log('Total Amount With Discount:', totalAmountWithDiscount);

            // Prevent negative totals
            totalAmountWithDiscount = Math.max(0, totalAmountWithDiscount);

            // Update UI with null checks
            // Calculate total quantity and build unit summary for all products in billing
            let unitSummary = {};
            try {
                billingBody.querySelectorAll('tr').forEach(row => {
                    const productId = row.querySelector('.product-id')?.textContent;
                    const quantityInput = row.querySelector('.quantity-input');
                    let quantity = quantityInput ? parseFloat(quantityInput.value) : 0;
                    if (productId && quantity > 0) {
                        // Find the product in stockData or allProducts with proper null checks
                        let stock = null;

                        // Search in stockData first
                        if (stockData && Array.isArray(stockData)) {
                            stock = stockData.find(s => s && s.product && s.product.id && String(s
                                .product.id) === productId);
                        }

                        // Search in allProducts if not found in stockData
                        if (!stock && allProducts && Array.isArray(allProducts)) {
                            stock = allProducts.find(s => s && s.product && s.product.id && String(s
                                .product.id) === productId);

                            // Also try direct product structure (for when allProducts contains products directly)
                            if (!stock) {
                                stock = allProducts.find(s => s && s.id && String(s.id) === productId);
                                if (stock) {
                                    // Normalize structure
                                    stock = {
                                        product: stock
                                    };
                                }
                            }
                        }

                        // Use unit information if available, otherwise default to 'pcs'
                        if (stock && stock.product && stock.product.unit) {
                            let unitShort = stock.product.unit.short_name || stock.product.unit.name ||
                                'pcs';
                            if (!unitSummary[unitShort]) unitSummary[unitShort] = 0;
                            unitSummary[unitShort] += quantity;
                        } else {
                            // Default to 'pcs' if no unit information available
                            if (!unitSummary['pcs']) unitSummary['pcs'] = 0;
                            unitSummary['pcs'] += quantity;
                        }
                    }
                });
            } catch (error) {
                console.error('Error calculating unit summary:', error);
                // Fallback to simple count
                unitSummary = {
                    'pcs': totalItems
                };
            }
            // Build display string like "4 kg, 2 pcs, 1 pack"
            let unitDisplay = Object.entries(unitSummary)
                .map(([unit, qty]) => `${qty % 1 === 0 ? qty : qty.toFixed(4).replace(/\.?0+$/, '')} ${unit}`)
                .join(', ');

            // Safe DOM updates with null checks
            const itemsCountEl = document.getElementById('items-count');
            const modalTotalItemsEl = document.getElementById('modal-total-items');
            const totalAmountEl = document.getElementById('total-amount');
            const finalTotalAmountEl = document.getElementById('final-total-amount');
            const totalEl = document.getElementById('total');
            const paymentAmountEl = document.getElementById('payment-amount');
            const modalTotalPayableEl = document.getElementById('modal-total-payable');

            if (itemsCountEl) itemsCountEl.textContent = unitDisplay || totalItems.toFixed(2);
            if (modalTotalItemsEl) modalTotalItemsEl.textContent = unitDisplay || totalItems.toFixed(2);
            if (totalAmountEl) totalAmountEl.textContent = formatAmountWithSeparators(totalAmount.toFixed(2));
            if (finalTotalAmountEl) finalTotalAmountEl.textContent = formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
            if (totalEl) totalEl.textContent = formatAmountWithSeparators(totalAmountWithDiscount.toFixed(2));
            if (paymentAmountEl) paymentAmountEl.textContent = 'Rs ' + formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));
            if (modalTotalPayableEl) modalTotalPayableEl.textContent = formatAmountWithSeparators(
                totalAmountWithDiscount.toFixed(2));

            // Update total items counter
            const totalItemsCountEl = document.getElementById('total-items-count');
            if (totalItemsCountEl) {
                const rowCount = billingBody.querySelectorAll('tr').length;
                totalItemsCountEl.textContent = rowCount;
            }

            // Validate quantities and update button states
            updatePaymentButtonsState();
        }

        // Quantity validation functions - Global scope
        function validateAllQuantities() {
            let isValid = true;
            const productRows = $('#billing-body tr');

            productRows.each(function() {
                const quantityInput = $(this).find('.quantity-input');
                const maxQuantity = parseFloat(quantityInput.attr('max')) || Infinity;
                const currentQuantity = parseFloat(quantityInput.val()) || 0;
                const isUnlimitedStock = quantityInput.attr('title') && quantityInput.attr('title')
                    .includes('Unlimited');

                // Check if quantity is valid
                if (currentQuantity <= 0) {
                    // Always invalid if quantity is zero or negative
                    quantityInput.addClass('quantity-error');
                    isValid = false;
                } else if (!isUnlimitedStock && currentQuantity > maxQuantity) {
                    // Invalid if exceeds stock (but only for non-unlimited stock items)
                    quantityInput.addClass('quantity-error');
                    isValid = false;
                } else {
                    // Valid quantity
                    quantityInput.removeClass('quantity-error');
                }
            });

            return isValid;
        }

        function updatePaymentButtonsState() {
            const isQuantityValid = validateAllQuantities();
            const paymentButtons = ['#cashButton', '#cardButton', '#chequeButton', '#creditSaleButton',
                '#multiplePayButton'
            ];

            paymentButtons.forEach(buttonId => {
                if (isQuantityValid) {
                    $(buttonId).prop('disabled', false);
                } else {
                    $(buttonId).prop('disabled', true);
                }
            });

            // Draft button is always enabled
            $('#draftButton').prop('disabled', false);
            $('#quotationButton').prop('disabled', false);
        }

        // Price validation and editability management
        function updatePriceEditability(row) {
            const priceInput = row.querySelector('.price-input');
            const fixedDiscountInput = row.querySelector('.fixed_discount');
            const percentDiscountInput = row.querySelector('.percent_discount');

            if (!priceInput || !fixedDiscountInput || !percentDiscountInput) return;

            const fixedDiscount = parseFloat(fixedDiscountInput.value) || 0;
            const percentDiscount = parseFloat(percentDiscountInput.value) || 0;

            // Make price editable only if both discount fields are empty (0)
            if (fixedDiscount === 0 && percentDiscount === 0) {
                priceInput.removeAttribute('readonly');
                priceInput.style.backgroundColor = '#fff';
                priceInput.style.cursor = 'text';
                priceInput.title = 'Price is editable when no discounts are applied';
            } else {
                priceInput.setAttribute('readonly', true);
                priceInput.style.backgroundColor = '#f8f9fa';
                priceInput.style.cursor = 'not-allowed';
                priceInput.title = 'Remove discounts to edit price manually';
            }
        }

        function validatePriceInput(row, priceInput) {
            const retailPrice = parseFloat(priceInput.getAttribute('data-retail-price')) || 0;
            const wholesalePrice = parseFloat(priceInput.getAttribute('data-wholesale-price')) || 0;
            const specialPrice = parseFloat(priceInput.getAttribute('data-special-price')) || 0;
            const maxRetailPrice = parseFloat(priceInput.getAttribute('data-max-retail-price')) || 0;

            let enteredPrice = parseFloat(priceInput.value) || 0;
            let minimumPrice = 0;
            let priceTypeName = '';
            let originalEnteredPrice = enteredPrice;

            // Determine minimum allowed price based on hierarchy
            if (specialPrice > 0) {
                minimumPrice = specialPrice;
                priceTypeName = 'special price';
            } else if (wholesalePrice > 0) {
                minimumPrice = wholesalePrice;
                priceTypeName = 'wholesale price';
            } else if (retailPrice > 0) {
                minimumPrice = retailPrice;
                priceTypeName = 'retail price';
            } else if (maxRetailPrice > 0) {
                minimumPrice = maxRetailPrice;
                priceTypeName = 'MRP';
            }

            console.log('Price validation:', {
                entered: enteredPrice,
                minimum: minimumPrice,
                type: priceTypeName,
                special: specialPrice,
                wholesale: wholesalePrice,
                retail: retailPrice,
                mrp: maxRetailPrice
            });

            // Validate against minimum price
            if (enteredPrice < minimumPrice && minimumPrice > 0) {
                toastr.error(
                    `Price cannot be below ${priceTypeName} of Rs. ${minimumPrice.toFixed(2)}. This prevents selling at loss.`,
                    'Price Validation Error'
                );
                priceInput.value = minimumPrice.toFixed(2);
                enteredPrice = minimumPrice;

                // Add visual feedback
                priceInput.style.borderColor = '#dc3545';
                setTimeout(() => {
                    priceInput.style.borderColor = '';
                }, 3000);
            }

            // Recalculate discount after price validation only if price was changed
            const fixedDiscountInput = row.querySelector('.fixed_discount');
            const percentDiscountInput = row.querySelector('.percent_discount');

            if (maxRetailPrice > 0 && enteredPrice !== originalEnteredPrice) {
                const discountAmount = maxRetailPrice - enteredPrice;
                if (fixedDiscountInput) {
                    fixedDiscountInput.value = discountAmount > 0 ? discountAmount.toFixed(2) : '0.00';
                }
                if (percentDiscountInput) {
                    percentDiscountInput.value = '';
                }
            }

            disableConflictingDiscounts(row);
            updateTotals();
        }

        function validateDiscountInput(row, discountInput, discountType) {
            const priceInput = row.querySelector('.price-input');
            if (!priceInput) return;

            const retailPrice = parseFloat(priceInput.getAttribute('data-retail-price')) || 0;
            const wholesalePrice = parseFloat(priceInput.getAttribute('data-wholesale-price')) || 0;
            const specialPrice = parseFloat(priceInput.getAttribute('data-special-price')) || 0;
            const maxRetailPrice = parseFloat(priceInput.getAttribute('data-max-retail-price')) || 0;

            let minimumPrice = 0;
            let priceTypeName = '';

            // Determine minimum allowed price based on hierarchy
            if (specialPrice > 0) {
                minimumPrice = specialPrice;
                priceTypeName = 'special price';
            } else if (wholesalePrice > 0) {
                minimumPrice = wholesalePrice;
                priceTypeName = 'wholesale price';
            } else if (retailPrice > 0) {
                minimumPrice = retailPrice;
                priceTypeName = 'retail price';
            } else if (maxRetailPrice > 0) {
                minimumPrice = maxRetailPrice;
                priceTypeName = 'MRP';
            }

            if (minimumPrice <= 0 || maxRetailPrice <= 0) return; // No validation possible

            const discountValue = parseFloat(discountInput.value) || 0;
            let finalPrice = 0;
            let maxAllowedDiscount = 0;
            let originalDiscountValue = discountValue;

            if (discountType === 'fixed') {
                finalPrice = maxRetailPrice - discountValue;
                maxAllowedDiscount = maxRetailPrice - minimumPrice;
            } else if (discountType === 'percent') {
                finalPrice = maxRetailPrice * (1 - discountValue / 100);
                maxAllowedDiscount = ((maxRetailPrice - minimumPrice) / maxRetailPrice) * 100;
            }

            console.log('Discount validation:', {
                discountType,
                discountValue,
                finalPrice,
                minimumPrice,
                maxAllowedDiscount,
                maxRetailPrice
            });

            // Check if final price after discount is below minimum
            if (finalPrice < minimumPrice) {
                if (discountType === 'fixed') {
                    toastr.error(
                        `Fixed discount cannot exceed Rs. ${maxAllowedDiscount.toFixed(2)}. This would make selling price (Rs. ${finalPrice.toFixed(2)}) below ${priceTypeName} (Rs. ${minimumPrice.toFixed(2)}).`,
                        'Discount Validation Error'
                    );
                    discountInput.value = maxAllowedDiscount.toFixed(2);
                } else {
                    toastr.error(
                        `Percentage discount cannot exceed ${maxAllowedDiscount.toFixed(2)}%. This would make selling price (Rs. ${finalPrice.toFixed(2)}) below ${priceTypeName} (Rs. ${minimumPrice.toFixed(2)}).`,
                        'Discount Validation Error'
                    );
                    discountInput.value = maxAllowedDiscount.toFixed(2);
                }

                // Add visual feedback
                discountInput.style.borderColor = '#dc3545';
                setTimeout(() => {
                    discountInput.style.borderColor = '';
                }, 3000);

                // Recalculate final price with corrected discount
                const correctedDiscount = parseFloat(discountInput.value);
                if (discountType === 'fixed') {
                    finalPrice = maxRetailPrice - correctedDiscount;
                } else {
                    finalPrice = maxRetailPrice * (1 - correctedDiscount / 100);
                }
            }

            // Update the price input with the calculated final price only if discount was corrected
            if (originalDiscountValue !== parseFloat(discountInput.value) || finalPrice >= minimumPrice) {
                priceInput.value = finalPrice.toFixed(2);
            }
        }

        //change event global discount
        const globalDiscountInput = document.getElementById('global-discount');
        const globalDiscountTypeInput = document.getElementById('discount-type');
        if (globalDiscountInput) {
            // Input event - fires immediately as user types
            globalDiscountInput.addEventListener('input', function() {
                updateTotals();
            });
            
            // Change event - fires when input loses focus and value has changed
            globalDiscountInput.addEventListener('change', function() {
                const discountValue = parseFloat(this.value) || 0;
                const discountType = globalDiscountTypeInput.value;
                if (discountType === 'percentage') {
                    this.value = Math.min(discountValue, 100); // Limit to 100%
                }
                updateTotals();
            });
            
            // Blur event - fires when input loses focus
            globalDiscountInput.addEventListener('blur', function() {
                const discountValue = parseFloat(this.value) || 0;
                const discountType = globalDiscountTypeInput.value;
                if (discountType === 'percentage') {
                    this.value = Math.min(discountValue, 100); // Limit to 100%
                }
                updateTotals();
            });
            
            // Keyup event - fires when user releases a key
            globalDiscountInput.addEventListener('keyup', function() {
                updateTotals();
            });
        }
        
        // Also trigger updateTotals when discount type changes
        if (globalDiscountTypeInput) {
            globalDiscountTypeInput.addEventListener('change', function() {
                updateTotals();
            });
        }





        let saleId = null;
        const pathSegments = window.location.pathname.split('/');
        saleId = pathSegments[pathSegments.length - 1];

        if (!isNaN(saleId) && saleId !== 'pos' && saleId !== 'list-sale') {
            fetchEditSale(saleId);
        } else {
            // console.warn('Invalid or missing saleId:', saleId);
        }

        function fetchEditSale(saleId) {
            // Set editing mode to true
            isEditing = true;
            currentEditingSaleId = saleId; // Store the sale ID being edited

            fetch(`/sales/edit/${saleId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
                    const contentType = response.headers.get('Content-Type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Invalid response format. Expected JSON.');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 200) {
                        const saleDetails = data.sale_details;

                        // Update invoice number
                        const saleInvoiceElement = document.getElementById('sale-invoice-no');
                        if (saleInvoiceElement && saleDetails.sale) {
                            saleInvoiceElement.textContent = `Invoice No: ${saleDetails.sale.invoice_no}`;
                        }

                        // Set the locationId based on the sale's location_id
                        if (saleDetails.sale && saleDetails.sale.location_id) {
                            locationId = saleDetails.sale.location_id;
                            selectedLocationId = saleDetails.sale
                                .location_id; // Ensure global variable is updated
                            // Update the location dropdown
                            const locationSelect = document.getElementById('locationSelect');
                            if (locationSelect) {
                                locationSelect.value = saleDetails.sale.location_id
                                    .toString(); // Ensure value matches option value type
                                console.log('Location ID set to:', saleDetails.sale.location_id);
                                // Manually trigger the change event to refresh products
                                $(locationSelect).trigger('change'); // Use jQuery to trigger the event
                            }
                        }

                        // Clear existing billing body before adding edit products
                        const billingBody = document.getElementById('billing-body');
                        if (billingBody) {
                            billingBody.innerHTML = '';
                            console.log('Billing body cleared for edit mode');
                        }

                        // Populate sale products
                        saleDetails.sale_products.forEach(saleProduct => {
                            const price = saleProduct.price || saleProduct.product.retail_price;

                            // Use the corrected total_quantity from backend as the max available stock
                            const maxAvailableStock = saleProduct.total_quantity;

                            // Create a normalized stock entry for the frontend
                            const normalizedStockEntry = {
                                batches: [{
                                    id: saleProduct.batch_id,
                                    batch_no: saleProduct.batch?.batch_no || 'BATCH-' +
                                        saleProduct.batch_id,
                                    retail_price: parseFloat(saleProduct.batch
                                        ?.retail_price || saleProduct.product
                                        .retail_price),
                                    wholesale_price: parseFloat(saleProduct.batch
                                        ?.wholesale_price || saleProduct.product
                                        .whole_sale_price),
                                    special_price: parseFloat(saleProduct.batch
                                        ?.special_price || saleProduct.product
                                        .special_price),
                                    location_batches: [{
                                        location_id: saleProduct.location_id,
                                        quantity: maxAvailableStock // This is the key fix
                                    }]
                                }],
                                total_stock: maxAvailableStock,
                                product: {
                                    ...saleProduct.product,
                                    batches: saleProduct.product.batches ||
                                    [] // Ensure batches is always an array
                                }
                            };

                            // Add the product to allProducts array for getProductDataById to find it
                            const existingProductIndex = allProducts.findIndex(p => p && p.id && p
                                .id === saleProduct.product.id);
                            if (existingProductIndex === -1 && saleProduct.product && saleProduct
                                .product.id) {
                                const productToAdd = {
                                    ...saleProduct.product,
                                    batches: saleProduct.product.batches ||
                                    [] // Ensure batches is always an array
                                };

                                // Ensure unit structure exists for updateTotals function
                                if (!productToAdd.unit && saleProduct.unit) {
                                    productToAdd.unit = saleProduct.unit;
                                }

                                allProducts.push(productToAdd);
                                console.log('Added product to allProducts:', productToAdd
                                    .product_name, productToAdd.id);
                            }

                            // Add product to billing with correct stock calculation
                            try {
                                addProductToBillingBody(
                                    saleProduct.product,
                                    normalizedStockEntry,
                                    price,
                                    saleProduct.batch_id,
                                    maxAvailableStock, // Batch quantity (max available)
                                    saleProduct.price_type,
                                    saleProduct
                                    .quantity, // Sale quantity (current quantity in sale)
                                    saleProduct.imei_numbers || [],
                                    saleProduct.discount_type,
                                    saleProduct.discount_amount
                                );
                                
                                console.log('Product added to billing:', saleProduct.product.product_name, {
                                    discount_type: saleProduct.discount_type,
                                    discount_amount: saleProduct.discount_amount,
                                    price: saleProduct.price
                                });
                            } catch (error) {
                                console.error('Error adding product to billing:', error,
                                    saleProduct);
                                // Continue with next product instead of breaking the whole process
                            }
                        });

                        // If the sale has a customer_id, trigger customer data fetch
                        if (saleDetails.sale && saleDetails.sale.customer_id) {
                            console.log('Fetching customer data for customer_id:', saleDetails.sale
                                .customer_id);
                            const $customerSelect = $('#customer-id');
                            if ($customerSelect.length) {
                                $customerSelect.val(saleDetails.sale.customer_id.toString());

                                // Wait for the customer select2 to finish loading (if async), then trigger change
                                setTimeout(() => {
                                    $customerSelect.trigger(
                                        'change'); // Use jQuery to trigger the event

                                    // Now call fetchCustomerData if available
                                    if (window.customerFunctions && typeof window.customerFunctions
                                        .fetchCustomerData === 'function') {
                                        window.customerFunctions.fetchCustomerData().then(() => {
                                            // After fetching, set the value and trigger change again to ensure due is updated
                                            $customerSelect.val(saleDetails.sale.customer_id
                                                .toString());
                                            $customerSelect.trigger('change');
                                            console.log(
                                                'Customer select and fetchCustomerData triggered for customer_id:',
                                                saleDetails.sale.customer_id);
                                        });
                                    } else {
                                        console.log(
                                            'Customer select and fetchCustomerData triggered for customer_id:',
                                            saleDetails.sale.customer_id);
                                    }
                                }, 200); // Adjust delay if needed for your UI
                            }
                        }

                        // Set global discount values
                        const discountElement = document.getElementById('global-discount');
                        const discountTypeElement = document.getElementById('discount-type');
                        if (discountElement && saleDetails.sale) {
                            discountElement.value = saleDetails.sale.discount_amount || 0;
                        }
                        if (discountTypeElement && saleDetails.sale) {
                            discountTypeElement.value = saleDetails.sale.discount_type || 'fixed';
                        }

                        // Update totals
                        updateTotals();
                    } else {
                        console.error('Invalid sale data:', data);
                        toastr.error('Failed to fetch sale data.', 'Error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching sale data:', error);
                    toastr.error('An error occurred while fetching sale data.', 'Error');
                });
        }



        $(document).ready(function() {

            function gatherSaleData(status) {
                const uniqueNumber = new Date().getTime() % 10000;
                const customerId = $('#customer-id').val();
                const salesDate = new Date().toISOString().slice(0, 10);

                if (!locationId) {
                    toastr.error('Location ID is required.');
                    return null;
                }

                // Get discount values
                const discountType = $('#discount-type').val() || 'fixed';
                const discountAmount = parseFormattedAmount($('#global-discount').val()) || 0;

                // Calculate total amount and final amount
                const totalAmount = parseFormattedAmount($('#total-amount').text()) || 0;
                let finalAmount = totalAmount;

                // Apply discount
                if (discountType === 'percentage') {
                    finalAmount -= totalAmount * (discountAmount / 100);
                } else {
                    finalAmount -= discountAmount;
                }

                // Ensure final amount doesn't go negative
                finalAmount = Math.max(0, finalAmount);

                const saleData = {
                    customer_id: customerId,
                    sales_date: salesDate,
                    location_id: locationId,
                    status: status,
                    sale_type: "POS",
                    products: [],
                    discount_type: discountType,
                    discount_amount: discountAmount,
                    total_amount: totalAmount,
                    final_total: finalAmount,
                };

                const productRows = $('#billing-body tr');
                if (productRows.length === 0) {
                    toastr.error('At least one product is required.');
                    return null;
                }

                productRows.each(function() {
                    const productRow = $(this);
                    const batchId = productRow.find('.batch-id').text().trim();
                    const locationId = productRow.find('.location-id').text().trim();
                    const discountFixed = parseFloat(productRow.find('.fixed_discount').val()
                        .trim()) || 0;
                    const discountPercent = parseFloat(productRow.find('.percent_discount')
                        .val().trim()) || 0;
                    const isImeiProduct = productRow.find('.imei-data').text().trim() !== '';

                    // Determine which discount is active
                    const discountType = discountFixed > 0 ? 'fixed' : 'percentage';
                    const discountAmount = discountFixed > 0 ? discountFixed : discountPercent;

                    // Get IMEI numbers if any
                    const imeiData = productRow.find('.imei-data').text().trim();
                    const imeis = imeiData ? imeiData.split(',').filter(Boolean) : [];

                    if (!locationId) {
                        toastr.error('Location ID is missing for a product.');
                        return;
                    }

                    const productData = {
                        product_id: parseInt(productRow.find('.product-id').text().trim(),
                            10),
                        location_id: parseInt(locationId, 10),
                        quantity: isImeiProduct ?
                            1 : (() => {
                                // Find the product in stockData or allProducts
                                const productId = parseInt(productRow.find(
                                    '.product-id').text().trim(), 10);
                                let stock = stockData.find(s => String(s.product.id) ===
                                        String(productId)) ||
                                    allProducts.find(s => String(s.product.id) ===
                                        String(productId));
                                const allowDecimal = stock && stock.product && (stock
                                    .product.unit?.allow_decimal === true || stock
                                    .product.unit?.allow_decimal === 1);
                                const qtyVal = productRow.find('.quantity-input').val()
                                    .trim();
                                if (allowDecimal) {
                                    const parsed = parseFloat(qtyVal);
                                    return isNaN(parsed) ? 0 : parsed;
                                } else {
                                    const parsed = parseInt(qtyVal, 10);
                                    return isNaN(parsed) ? 0 : parsed;
                                }
                            })(),
                        price_type: priceType,
                        unit_price: parseFormattedAmount(productRow.find('.price-input')
                            .val().trim()),
                        subtotal: parseFormattedAmount(productRow.find('.subtotal').text()
                            .trim()),
                        discount_amount: discountAmount,
                        discount_type: discountType,
                        tax: 0,
                        batch_id: batchId === "all" ? "all" : batchId,
                        imei_numbers: imeis,
                    };

                    saleData.products.push(productData);
                });

                return saleData;
            }


            // Function to refresh stock data immediately after sale for IMEI products
            function refreshStockDataAfterSale(saleData) {
                console.log('Refreshing stock data after sale...');

                // Get list of products sold in this sale
                const soldProducts = [];
                const billingBody = document.getElementById('billing-body');
                const billingRows = Array.from(billingBody.querySelectorAll('tr'));

                billingRows.forEach(row => {
                    const productId = row.querySelector('.product-id')?.textContent;
                    const imeiData = row.querySelector('.imei-data')?.textContent.trim();

                    if (productId && imeiData) {
                        // This row has IMEI data - need to refresh this product
                        const imeis = imeiData.split(',').filter(Boolean);
                        soldProducts.push({
                            productId: parseInt(productId),
                            soldImeis: imeis
                        });
                    }
                });

                console.log('Products with IMEIs sold:', soldProducts);

                if (soldProducts.length > 0) {
                    // Update the global stockData immediately
                    soldProducts.forEach(soldProduct => {
                        const stockIndex = stockData.findIndex(stock => stock.product.id ===
                            soldProduct.productId);
                        if (stockIndex !== -1) {
                            console.log(
                                `Updating stock data for product ${soldProduct.productId}`);

                            // Mark sold IMEIs as "sold" in the stockData
                            if (stockData[stockIndex].imei_numbers) {
                                stockData[stockIndex].imei_numbers.forEach(imei => {
                                    if (soldProduct.soldImeis.includes(imei
                                            .imei_number)) {
                                        console.log(
                                            `Marking IMEI ${imei.imei_number} as sold`
                                        );
                                        imei.status = 'sold';
                                    }
                                });
                            }

                            // Update total stock count
                            const availableImeis = stockData[stockIndex].imei_numbers?.filter(
                                imei => imei.status === 'available') || [];
                            stockData[stockIndex].total_stock = availableImeis.length;

                            console.log(
                                `Updated stock for product ${soldProduct.productId}: ${stockData[stockIndex].total_stock} available`
                            );
                        }
                    });

                    // Also trigger a background refresh for server sync
                    setTimeout(() => {
                        fetchPaginatedProducts(true);
                    }, 1000);
                }
            }

            function sendSaleData(saleData, saleId = null, onComplete = () => {}) {
                // Check sales rep access before processing sale
                if (!checkSalesAccess()) {
                    onComplete();
                    return;
                }

                // Validate walk-in customer cheque payment restriction
                if (saleData.customer_id == 1 && saleData.payments) {
                    for (let payment of saleData.payments) {
                        if (payment.payment_method === 'cheque') {
                            toastr.error(
                                'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.'
                            );
                            onComplete();
                            return;
                        }
                    }
                }

                // Extract saleId from the URL if not provided
                if (!saleId) {
                    const pathSegments = window.location.pathname.split('/');
                    const possibleSaleId = pathSegments[pathSegments.length - 1];
                    if (!isNaN(possibleSaleId) && possibleSaleId !== 'pos' && possibleSaleId !==
                        'list-sale') {
                        saleId = possibleSaleId;
                    }
                }

                const url = saleId ? `/sales/update/${saleId}` : '/sales/store';
                const method = 'POST';



                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    },
                    data: JSON.stringify(saleData),
                    timeout: 30000, // 30 second timeout to prevent infinite waiting
                    cache: false, // Prevent caching for fresh responses
                    success: function(response) {
                        if (response.message && response.invoice_html) {
                            // Immediate success feedback
                            document.getElementsByClassName('successSound')[0].play();
                            
                            // Show appropriate success message
                            if (response.sale && response.sale.transaction_type === 'sale_order') {
                                toastr.success(response.message + ' Order Number: ' + response.sale.order_number, 'Sale Order Created', {
                                    timeOut: 5000,
                                    progressBar: true
                                });
                            } else {
                                toastr.success(response.message);
                            }

                            // Store current customer before reset
                            const currentCustomerId = $('#customer-id').val();

                            // IMMEDIATE stock refresh for IMEI products - before form reset
                            refreshStockDataAfterSale(saleData);

                            // IMMEDIATE refresh of Recent Transactions data
                            fetchSalesData();

                            // IMMEDIATE form reset and UI feedback - don't wait for async operations
                            resetForm();

                            // Call onComplete immediately for button re-enabling
                            if (onComplete) onComplete();

                            // Only print for non-suspended sales and non-sale-order transactions
                            if (saleData.status !== 'suspend' && saleData.transaction_type !== 'sale_order') {
                                // Determine sale id returned from the server (fallback to local saleId)
                                const returnedSaleId = (response.sale && response.sale.id) || response.id || saleId;

                                // Use centralized printReceipt which handles mobile vs desktop logic
                                try {
                                    if (typeof window.printReceipt === 'function') {
                                        // Small delay to ensure UI has reset
                                        setTimeout(() => {
                                            window.printReceipt(returnedSaleId);
                                        }, 150);
                                    } else {
                                        // Fallback: open a hidden iframe directly if centralized function missing
                                        const fallbackIframe = document.createElement('iframe');
                                        fallbackIframe.style.cssText = 'position:fixed;width:0;height:0;border:none;opacity:0;';
                                        document.body.appendChild(fallbackIframe);
                                        const doc = fallbackIframe.contentDocument || fallbackIframe.contentWindow.document;
                                        doc.open();
                                        doc.write(response.invoice_html);
                                        doc.close();
                                        setTimeout(() => {
                                            try { fallbackIframe.contentWindow.print(); } catch (e) { console.warn('Fallback print error', e); }
                                            setTimeout(() => { if (document.body.contains(fallbackIframe)) document.body.removeChild(fallbackIframe); }, 1000);
                                        }, 200);
                                    }

                                    // Redirect for edits if needed
                                    if (saleId) {
                                        setTimeout(() => { window.location.href = '/pos-create'; }, 700);
                                    }
                                } catch (err) {
                                    console.warn('Error while initiating print:', err);
                                }
                            }

                            // ASYNC operations that don't block UI (moved to background)
                            setTimeout(() => {
                                // Background refresh - don't block main flow, make it truly async
                                Promise.all([
                                    new Promise(resolve => {
                                        fetchPaginatedProducts(true);
                                        resolve();
                                    }),
                                    new Promise(resolve => {
                                        // Clear sales cache for faster future loading
                                        fetch('/api/sales/clear-cache', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': $(
                                                    'meta[name="csrf-token"]'
                                                ).attr(
                                                    'content'
                                                )
                                            }
                                        }).catch(error => {
                                            console.warn(
                                                'Failed to clear sales cache:',
                                                error);
                                        });
                                        resolve();
                                    })
                                ]).catch(error => {
                                    console.warn(
                                        'Background refresh error:',
                                        error);
                                });

                                // Background customer data refresh
                                if (saleData && saleData.status === 'final' &&
                                    typeof window.customerFunctions !==
                                    'undefined' &&
                                    typeof window.customerFunctions
                                    .fetchCustomerData === 'function') {

                                    window.customerFunctions.fetchCustomerData()
                                        .then(function() {
                                            console.log(
                                                'Customer data refreshed after successful sale'
                                            );

                                            // Restore customer selection if needed
                                            if (currentCustomerId &&
                                                currentCustomerId !== '1') {
                                                setTimeout(function() {
                                                    $('#customer-id')
                                                        .val(
                                                            currentCustomerId
                                                        );
                                                    $('#customer-id')
                                                        .trigger(
                                                            'change');
                                                }, 100);
                                            }
                                        }).catch(function(error) {
                                            console.error(
                                                'Failed to refresh customer data:',
                                                error);
                                        });
                                }
                            }, 200); // Small delay to let UI update first

                        } else {
                            // Check if this is a credit limit error
                            if (response.message && response.message.includes(
                                    'Credit limit exceeded')) {
                                // Format the error message for better display
                                const formattedMessage = response.message.replace(/\n/g,
                                    '<br>').replace(/•/g, '&bull;');

                                swal({
                                    title: "Credit Limit Exceeded",
                                    text: formattedMessage,
                                    html: true,
                                    type: "error",
                                    confirmButtonText: "OK",
                                    confirmButtonColor: "#d33"
                                });
                            } else {
                                toastr.error('Failed to record sale: ' + response.message);
                            }
                            if (onComplete) onComplete();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error Details:', {
                            status: xhr.status,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON
                        });

                        let errorMessage = 'An error occurred while processing the sale.';
                        let useToastr = true;

                        try {
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;

                                // Check if this is a credit limit error
                                if (errorMessage.includes('Credit limit exceeded')) {
                                    useToastr = false;

                                    // Format the error message for better display
                                    const formattedMessage = errorMessage.replace(/\n/g,
                                        '<br>').replace(/•/g, '&bull;');

                                    swal({
                                        title: "Credit Limit Exceeded",
                                        text: formattedMessage,
                                        html: true,
                                        type: "error",
                                        confirmButtonText: "OK",
                                        confirmButtonColor: "#d33"
                                    });
                                }
                            } else if (xhr.responseText) {
                                // Try to parse responseText as JSON
                                try {
                                    const parsedResponse = JSON.parse(xhr.responseText);
                                    if (parsedResponse.message && parsedResponse.message
                                        .includes('Credit limit exceeded')) {
                                        useToastr = false;
                                        const formattedMessage = parsedResponse.message
                                            .replace(/\n/g, '<br>').replace(/•/g, '&bull;');

                                        swal({
                                            title: "Credit Limit Exceeded",
                                            text: formattedMessage,
                                            html: true,
                                            type: "error",
                                            confirmButtonText: "OK",
                                            confirmButtonColor: "#d33"
                                        });
                                    } else {
                                        errorMessage = parsedResponse.message || xhr
                                            .responseText;
                                    }
                                } catch (parseError) {
                                    errorMessage = xhr.responseText;
                                }
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }

                        // Use toastr for other errors
                        if (useToastr) {
                            toastr.error(errorMessage);
                        }

                        if (onComplete) onComplete();
                    }
                });
            }

            function gatherCashPaymentData() {
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                // console.log("final_amount " + totalAmount);
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'cash',
                    payment_date: today,
                    amount: totalAmount
                }];
            }

            // Quantity validation functions now in global scope above

            // Add event listeners for quantity input changes
            $(document).on('input change', '.quantity-input', function() {
                updatePaymentButtonsState();
            });

            // Initial validation on page load
            $(document).ready(function() {
                updatePaymentButtonsState();
            });

            $('#cashButton').on('click', function() {
                const button = this;
                // Immediate visual feedback
                $(button).html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop(
                    'disabled', true);

                preventDoubleClick(button, () => {
                    // Validate quantities before processing
                    if (!validateAllQuantities()) {
                        toastr.error(
                            'Please fix the invalid quantities (red borders) before processing the payment.'
                        );
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    const customerId = $('#customer-id').val();
                    // USE final_total from saleData instead of reading from DOM to avoid discrepancies
                    const totalAmount = saleData.final_total;
                    let amountGiven = parseFormattedAmount($('#amount-given').val()
                        .trim());

                    // Default to full payment if empty or invalid
                    const isWalkInCustomer = customerId == 1;

                    if (isNaN(amountGiven) || amountGiven <= 0) {
                        amountGiven = totalAmount;
                    }
                    
                    // Debug logging
                    console.log('Cash Payment Debug:', {
                        subtotal: saleData.total_amount,
                        discount: saleData.discount_amount,
                        discountType: saleData.discount_type,
                        finalTotal: saleData.final_total,
                        amountGiven: amountGiven
                    });

                    let paidAmount = amountGiven;
                    let balance = amountGiven - totalAmount;

                    // If amountGiven is greater than totalAmount, settle only up to totalAmount
                    if (amountGiven > totalAmount) {
                        paidAmount = totalAmount;
                        balance = amountGiven - totalAmount;
                    }

                    saleData.amount_given = amountGiven;
                    saleData.balance_amount = Math.max(0, balance); // Prevent negatives

                    // Block partial payment for Walk-In Customer
                    if (isWalkInCustomer && paidAmount < totalAmount) {
                        toastr.error(
                            "Partial payment is not allowed for Walk-In Customer.");
                        enableButton(button);
                        return;
                    }

                    saleData.payments = [{
                        payment_method: 'cash',
                        payment_date: new Date().toISOString().slice(0, 10),
                        amount: paidAmount // Only settle up to total amount
                    }];

                    if (paidAmount >= totalAmount) {
                        sendSaleData(saleData, null, () => enableButton(button));
                    } else {
                        // Partial payment (non-Walk-In)
                        swal({
                            title: "Partial Payment",
                            text: "You're making a partial payment of Rs. " +
                                formatAmountWithSeparators(paidAmount.toFixed(
                                    2)) +
                                ". The remaining Rs. " +
                                formatAmountWithSeparators((totalAmount -
                                    paidAmount).toFixed(2)) +
                                " will be due later.",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Proceed",
                            cancelButtonText: "Cancel"
                        }, function(isConfirm) {
                            if (isConfirm) {
                                sendSaleData(saleData, null, () => enableButton(
                                    button));
                            } else {
                                enableButton(button);
                            }
                        });
                    }
                });
            });

            $('#cardButton').on('click', function() {
                $('#cardModal').modal('show');
            });

            function gatherCardPaymentData() {
                const cardNumber = $('#card_number').val().trim();
                const cardHolderName = $('#card_holder_name').val().trim();
                const cardExpiryMonth = $('#card_expiry_month').val().trim();
                const cardExpiryYear = $('#card_expiry_year').val().trim();
                const cardSecurityCode = $('#card_security_code').val().trim();
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'card',
                    payment_date: today,
                    amount: totalAmount,
                    card_number: cardNumber,
                    card_holder_name: cardHolderName,
                    card_expiry_month: cardExpiryMonth,
                    card_expiry_year: cardExpiryYear,
                    card_security_code: cardSecurityCode
                }];
            }

            $('#confirmCardPayment').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    // Validate quantities before processing
                    if (!validateAllQuantities()) {
                        toastr.error(
                            'Please fix the invalid quantities (red borders) before processing the payment.'
                        );
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    saleData.payments = gatherCardPaymentData();
                    sendSaleData(saleData, null, () => {
                        $('#cardModal').modal('hide');
                        resetCardModal();
                        enableButton(button);
                    });
                });
            });

            $('#chequeButton').on('click', function() {
                // Check if customer is walk-in customer (ID = 1)
                const customerId = $('#customer-id').val();
                if (customerId == 1) {
                    toastr.error(
                        'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.'
                    );
                    return; // Prevent opening the modal
                }
                $('#chequeModal').modal('show');
            });

            function gatherChequePaymentData() {
                const chequeNumber = $('#cheque_number').val().trim();
                const bankBranch = $('#cheque_bank_branch').val().trim();
                const chequeReceivedDate = $('#cheque_received_date').val().trim();
                const chequeValidDate = $('#cheque_valid_date').val().trim();
                const chequeGivenBy = $('#cheque_given_by').val().trim();
                const chequeStatus = $('#cheque_status').val() || 'pending'; // Default to pending
                const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                    .trim()); // Ensure #total-amount element exists
                const today = new Date().toISOString().slice(0, 10);

                return [{
                    payment_method: 'cheque',
                    payment_date: today,
                    amount: totalAmount,
                    cheque_number: chequeNumber,
                    cheque_bank_branch: bankBranch,
                    cheque_received_date: chequeReceivedDate,
                    cheque_valid_date: chequeValidDate,
                    cheque_given_by: chequeGivenBy,
                    cheque_status: chequeStatus
                }];
            }

            function validateChequeFields() {
                let isValid = true;

                if ($('#cheque_number').val().trim() === '') {
                    $('#chequeNumberError').text('Cheque Number is required.');
                    isValid = false;
                } else {
                    $('#chequeNumberError').text('');
                }

                if ($('#cheque_received_date').val().trim() === '') {
                    $('#chequeReceivedDateError').text('Cheque Received Date is required.');
                    isValid = false;
                } else {
                    $('#chequeReceivedDateError').text('');
                }

                if ($('#cheque_valid_date').val().trim() === '') {
                    $('#chequeValidDateError').text('Cheque Valid Date is required.');
                    isValid = false;
                } else {
                    $('#chequeValidDateError').text('');
                }

                return isValid;
            }

            $('#confirmChequePayment').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    // Validate quantities before processing
                    if (!validateAllQuantities()) {
                        toastr.error(
                            'Please fix the invalid quantities (red borders) before processing the payment.'
                        );
                        enableButton(button);
                        return;
                    }

                    // Check if customer is walk-in customer (ID = 1)
                    const customerId = $('#customer-id').val();
                    if (customerId == 1) {
                        toastr.error(
                            'Cheque payment is not allowed for Walk-In Customer. Please choose another payment method or select a different customer.'
                        );
                        enableButton(button);
                        return;
                    }

                    if (!validateChequeFields()) {
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    saleData.payments = gatherChequePaymentData();
                    sendSaleData(saleData, null, () => {
                        $('#chequeModal').modal('hide');
                        resetChequeModal();
                        enableButton(button);
                    });
                });
            });

            function resetCardModal() {
                $('#card_number').val('');
                $('#card_holder_name').val('');
                $('#card_type').val('visa');
                $('#card_expiry_month').val('');
                $('#card_expiry_year').val('');
                $('#card_security_code').val('');
            }

            function resetChequeModal() {
                $('#cheque_number').val('');
                $('#bank_branch').val('');
                $('#cheque_received_date').val('');
                $('#cheque_valid_date').val('');
                $('#cheque_given_by').val('');
                $('.error-message').text('');
            }

            $('#creditSaleButton').on('click', function() {
                const button = this;
                preventDoubleClick(button, () => {
                    // Validate quantities before processing
                    if (!validateAllQuantities()) {
                        toastr.error(
                            'Please fix the invalid quantities (red borders) before processing the payment.'
                        );
                        enableButton(button);
                        return;
                    }

                    const customerId = $('#customer-id').val();
                    if (customerId == 1) {
                        toastr.error(
                            'Credit sale is not allowed for Walking Customer. Please choose another customer.'
                        );
                        enableButton(button);
                        return;
                    }

                    const saleData = gatherSaleData('final');
                    if (!saleData) {
                        toastr.error(
                            'Please add at least one product before completing the sale.'
                        );
                        enableButton(button);
                        return;
                    }

                    // Add credit payment information
                    const totalAmount = parseFormattedAmount($('#final-total-amount')
                        .text().trim());

                    saleData.payments = [{
                        payment_method: 'credit',
                        payment_date: new Date().toISOString().slice(0, 10),
                        amount: 0 // No payment amount for credit sale
                    }];

                    saleData.amount_given = 0;
                    saleData.balance_amount = 0;

                    sendSaleData(saleData, null, () => enableButton(button));
                });
            });

            $('#suspendModal').on('click', '#confirmSuspend', function() {
                const saleData = gatherSaleData('suspend');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                sendSaleData(saleData);
                let modal = bootstrap.Modal.getInstance(document.getElementById(
                    "suspendModal"));
                modal.hide();
            });

            $('#jobTicketButton').on('click', function() {
                const customerId = $('#customer-id').val();
                if (!customerId || customerId === "1") {
                    toastr.error(
                        'Please select a valid customer (not Walk-In) before creating a job ticket.'
                    );
                    return;
                }

                // Fetch customer details from API using customerId
                $.ajax({
                    url: '/customer-get-by-id/' + customerId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200 && response.customer) {
                            const customer = response.customer;
                            // Compose full name with prefix, first_name, last_name
                            const fullName = [customer.prefix, customer.first_name,
                                customer.last_name
                            ].filter(Boolean).join(' ');
                            $('#customerName').val(fullName || '');
                            $('#customerMobile').val(customer.mobile_no || '');
                            $('#customerEmail').val(customer.email || '');
                            $('#customerAddress').val(customer.address || '');
                        } else {
                            toastr.error('Failed to fetch customer details.');
                            $('#customerName').val('');
                            $('#customerMobile').val('');
                            $('#customerEmail').val('');
                            $('#customerAddress').val('');
                        }

                        // Populate total amount
                        const totalAmount = parseFormattedAmount($(
                            '#final-total-amount').text().trim());
                        $('#totalAmountInput').val(totalAmount.toFixed(2));

                        // Set default advance amount from #amount-given (if any)
                        let amountGiven = parseFormattedAmount($('#amount-given')
                            .val().trim());
                        $('#advanceAmountInput').val((isNaN(amountGiven) ? 0 :
                            amountGiven).toFixed(2));

                        // Calculate balance
                        calculateJobTicketBalance();

                        // Show modal
                        $('#jobTicketModal').modal('show');
                    },
                    error: function() {
                        toastr.error('Error fetching customer details.');
                        $('#customerName').val('');
                        $('#customerMobile').val('');
                        $('#customerEmail').val('');
                        $('#customerAddress').val('');
                    }
                });
            });

            // 2. Recalculate balance on Advance Amount change
            $('#advanceAmountInput').on('input', function() {
                calculateJobTicketBalance();
            });

            function calculateJobTicketBalance() {
                const totalAmount = parseFloat($('#totalAmountInput').val()) || 0;
                const advanceAmount = parseFloat($('#advanceAmountInput').val()) || 0;
                let balance = totalAmount - advanceAmount;
                if (balance < 0) balance = 0; // Prevent negative balance
                $('#balanceAmountInput').val(balance.toFixed(2));
            }

            $('#submitJobTicket').on('click', function() {
                // Gather sale data
                const saleData = gatherSaleData('jobticket');
                if (!saleData) {
                    toastr.error(
                        'Please add at least one product before submitting the job ticket.');
                    return;
                }

                // Attach customer details to saleData (optional, if needed by backend)
                saleData.customer_name = $('#customerName').val();
                saleData.customer_mobile = $('#customerMobile').val();
                saleData.customer_email = $('#customerEmail').val();
                saleData.customer_address = $('#customerAddress').val();

                // Attach advance and balance
                saleData.advance_amount = parseFloat($('#advanceAmountInput').val()) || 0;
                let balanceAmount = parseFloat($('#balanceAmountInput').val()) || 0;
                if (balanceAmount < 0) balanceAmount = 0; // Prevent negative balance
                saleData.balance_amount = balanceAmount;
                saleData.total_paid = saleData
                    .advance_amount; // Set total_paid to advance amount
                saleData.amount_given = saleData
                    .advance_amount; // <-- Ensure amount_given is set for backend
                saleData.jobticket_description = $('#description').val();

                // Send to backend with status "jobticket"
                sendSaleData(saleData, null, function() {
                    $('#jobTicketModal').modal('hide');
                });
            });



            document.getElementById('finalize_payment').addEventListener('click', function() {
                // Validate quantities before processing
                if (!validateAllQuantities()) {
                    toastr.error(
                        'Please fix the invalid quantities (red borders) before processing the payment.'
                    );
                    return;
                }

                const saleData = gatherSaleData('final');
                if (!saleData) {
                    toastr.error('Please add at least one product before completing the sale.');
                    return;
                }

                const paymentData = gatherPaymentData();

                // Calculate sum of all payment rows (what customer gave in total)
                const amountGiven = paymentData.reduce((sum, pay) => sum + (parseFloat(pay
                    .amount) || 0), 0);
                // Calculate final total (bill)
                const finalTotal = parseFormattedAmount(document.getElementById(
                    'modal-total-payable').textContent);

                // Calculate totalPaid: should not exceed finalTotal
                let totalPaid = Math.min(amountGiven, finalTotal);
                let balanceAmount = Math.max(0, amountGiven - finalTotal);

                // If amountGiven is less than finalTotal, balanceAmount is 0 (no change to give)
                // If amountGiven is more, balanceAmount is the change to give

                // Attach these to saleData for backend saving
                saleData.payments = paymentData;
                saleData.amount_given = amountGiven;
                saleData.total_paid = totalPaid;
                saleData.balance_amount = balanceAmount;

                // Log for debugging
                console.log("Payment Data:", paymentData);
                console.log("Sale Data:", saleData);

                // Send to server
                sendSaleData(saleData);

                // Hide modal
                let modal = bootstrap.Modal.getInstance(document.getElementById(
                    "paymentModal"));
                if (modal) modal.hide();
            });

            function gatherPaymentData() {
                const paymentData = [];
                document.querySelectorAll('.payment-row').forEach(row => {
                    const paymentMethod = row.querySelector('.payment-method').value;
                    const paymentDate = row.querySelector('.payment-date').value;
                    const amountInput = row.querySelector('.payment-amount').value;
                    let amount = parseFormattedAmount(amountInput);

                    const conditionalFields = {};
                    row.querySelectorAll(
                        '.conditional-fields input, .conditional-fields select').forEach(
                        input => {
                            conditionalFields[input.name] = input.value.trim();
                        });

                    if (!isNaN(amount) && amount > 0) {
                        paymentData.push({
                            payment_method: paymentMethod,
                            payment_date: paymentDate,
                            amount: amount,
                            ...conditionalFields
                        });
                    }
                });

                // Log each payment row for debugging
                console.log("Each Payment Row:", paymentData);

                return paymentData;
            }

            // Add event listener for payment modal to ensure correct total is displayed
            const paymentModal = document.getElementById('paymentModal');
            if (paymentModal) {
                paymentModal.addEventListener('show.bs.modal', function() {
                    // Update modal total payable when modal is opened
                    updateTotals();
                });
            }

            function fetchSuspendedSales() {
                $.ajax({
                    url: '/sales/suspended',
                    type: 'GET',
                    success: function(response) {
                        displaySuspendedSales(response);
                        $('#suspendSalesModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Failed to fetch suspended sales: ' + xhr
                            .responseText);
                    }
                });
            }

            function displaySuspendedSales(sales) {
                const suspendedSalesContainer = $('#suspendedSalesContainer');
                suspendedSalesContainer.empty();

                sales.forEach(sale => {
                    const finalTotal = parseFormattedAmount(sale.final_total);
                    const saleRow = `
                <tr>
                    <td>${sale.invoice_no}</td>
                    <td>${new Date(sale.sales_date).toLocaleDateString()}</td>
                    <td>${sale.customer ? sale.customer.name : 'Walk-In Customer'}</td>
                    <td>${sale.products.length}</td>
                    <td>Rs. ${formatAmountWithSeparators(finalTotal.toFixed(2))}</td>
                    <td>
                        ${userPermissions.canEditSale ? `<a href="/sales/edit/${sale.id}" class="btn btn-success editSaleButton" data-sale-id="${sale.id}">Edit</a>` : ''}
                        ${userPermissions.canDeleteSale ? `<button class="btn btn-danger deleteSuspendButton" data-sale-id="${sale.id}">Delete</button>` : ''}
                    </td>
                </tr>`;
                    suspendedSalesContainer.append(saleRow);
                });

                $('.editSaleButton').on('click', function() {
                    const saleId = $(this).data('sale-id');
                    // editSale(saleId);
                });

                $('.deleteSuspendButton').on('click', function() {
                    const saleId = $(this).data('sale-id');
                    deleteSuspendedSale(saleId);
                });
            }

            // Function to delete a suspended sale
            function deleteSuspendedSale(saleId) {
                $.ajax({
                    url: `/sales/delete-suspended/${saleId}`,
                    type: 'DELETE',
                    success: function(response) {
                        toastr.success(response.message);
                        // Code to update the POS page after deletion
                        fetchSuspendedSales(); // Refresh suspended sales list
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Failed to delete suspended sale: ' + xhr
                            .responseText);
                    }
                });
            }

            // Event listener for the pause circle button to fetch and show suspended sales
            $('#pauseCircleButton').on('click', function() {
                fetchSuspendedSales();
            });

            $('#amount-given').on('input', function() {
                let amountGiven = parseFormattedAmount($(this).val().trim());

                if (isNaN(amountGiven) || amountGiven < 0) {
                    amountGiven = 0;
                }

                const allPaymentButtons = [
                    '#cardButton',
                    '#chequeButton',
                    '#creditSaleButton',
                    '#multiplePayButton',
                    '#pauseCircleButton'
                ].join(',');

                // Optional: Set placeholder or clear if empty
                if (amountGiven === 0) {
                    $(this).val('');
                    // Enable all payment buttons when cleared
                    $(allPaymentButtons).prop('disabled', false); // To enable

                } else {
                    $(this).val(formatAmountWithSeparators(amountGiven));

                    $(allPaymentButtons).prop('disabled', true); // To disable
                }
            });


            // Flag to prevent double alerts
            let isProcessingAmountGiven = false;

            $('#amount-given').on('keyup', function(event) {
                if (event.key === 'Enter') {
                    // Prevent double processing


                    if (isProcessingAmountGiven) {
                        return;
                    }
                    isProcessingAmountGiven = true;

                    const totalAmount = parseFormattedAmount($('#final-total-amount').text()
                        .trim());
                    const amountGiven = parseFormattedAmount($('#amount-given').val().trim());

                    if (isNaN(amountGiven) || amountGiven <= 0) {
                        toastr.error('Please enter a valid amount given by the customer.');
                        isProcessingAmountGiven = false;
                        return;
                    }

                    const balance = amountGiven - totalAmount;

                    if (balance > 0) {
                        const formattedBalance = formatAmountWithSeparators(balance.toFixed(2));

                        swal({
                            title: "Return Amount",
                            text: `<div style="text-align: center; font-size: 24px; font-weight: bold; color: #2ecc71; margin: 20px 0;">
                                      <div style="font-size: 18px; color: #7f8c8d; margin-bottom: 10px;">Balance amount to be returned</div>
                                      <div style="font-size: 32px; color: #e74c3c;">Rs. ${formattedBalance}</div>
                                   </div>`,
                            html: true,
                            type: "info",
                            showCancelButton: false,
                            confirmButtonText: "OK",
                            allowOutsideClick: false,
                            allowEscapeKey: true,
                            closeOnEsc: true
                        }, function(isConfirm) {
                            // Reset flag whether OK clicked or ESC pressed
                            isProcessingAmountGiven = false;

                            // Only proceed with cash button if OK was clicked
                            if (isConfirm) {
                                $('#cashButton').trigger('click');
                            }
                        });
                    } else {
                        isProcessingAmountGiven = false;
                        $('#cashButton').trigger('click');
                    }
                }
            });

            // Fetch suspended sales when the POS page loads
            // fetchSuspendedSales();


            document.getElementById('quotationButton').addEventListener('click', function() {
                const saleData = gatherSaleData('quotation');
                if (!saleData) return;
                sendSaleData(saleData);
            });

            document.getElementById('draftButton').addEventListener('click', function() {
                const saleData = gatherSaleData('draft');
                if (!saleData) return;
                sendSaleData(saleData);
            });

            // Sale Order Button Handler
            document.getElementById('saleOrderButton').addEventListener('click', function() {
                // Validate that there are products in the cart
                const productRows = $('#billing-body tr');
                if (productRows.length === 0) {
                    toastr.error('Please add at least one product to create a sale order.');
                    return;
                }

                // Validate customer is selected and not Walk-in
                const customerId = $('#customer-id').val();
                const customerText = $('#customer-id option:selected').text();
                
                if (!customerId || customerId == '1' || customerText.toLowerCase().includes('walk-in')) {
                    toastr.error('Sale Orders cannot be created for Walk-In customers. Please select a valid customer.');
                    return;
                }

                // Show the Sale Order modal
                const saleOrderModal = new bootstrap.Modal(document.getElementById('saleOrderModal'));
                saleOrderModal.show();
            });

            // Confirm Sale Order Button Handler
            document.getElementById('confirmSaleOrder').addEventListener('click', function() {
                const expectedDeliveryDate = document.getElementById('expectedDeliveryDate').value;
                const orderNotes = document.getElementById('orderNotes').value.trim();

                // Validate expected delivery date
                if (!expectedDeliveryDate) {
                    toastr.error('Please select an expected delivery date.');
                    return;
                }

                // Validate date is not in the past
                const selectedDate = new Date(expectedDeliveryDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    toastr.error('Expected delivery date cannot be in the past.');
                    return;
                }

                // Gather sale data without status (we'll use transaction_type instead)
                const saleData = gatherSaleData('final'); // Use 'final' as base, we'll override
                if (!saleData) return;

                // Modify the data for Sale Order
                saleData.transaction_type = 'sale_order';
                saleData.order_status = 'pending';
                saleData.expected_delivery_date = expectedDeliveryDate;
                saleData.order_notes = orderNotes;
                saleData.status = 'final'; // Keep status as final for backend compatibility

                // Remove payments array if it exists (Sale Orders don't have payments)
                delete saleData.payments;

                // Send the sale order data
                sendSaleData(saleData);

                // Close the modal
                const saleOrderModal = bootstrap.Modal.getInstance(document.getElementById('saleOrderModal'));
                if (saleOrderModal) {
                    saleOrderModal.hide();
                }

                // Clear modal fields for next use
                document.getElementById('expectedDeliveryDate').value = '';
                document.getElementById('orderNotes').value = '';
            });

            document.getElementById('cancelButton').addEventListener('click', resetForm);

            function resetToWalkingCustomer() {
                const customerSelect = $('#customer-id');

                if (isSalesRep) {
                    // For sales reps, reset to "Please Select" - don't auto-select any customer
                    customerSelect.val('').trigger('change');
                    console.log('Sales rep: Customer reset to "Please Select"');
                } else {
                    // For non-sales reps, reset to Walk-in Customer (ID = 1)
                    // Try by value first (most reliable)
                    if (customerSelect.find('option[value="1"]').length > 0) {
                        customerSelect.val('1').trigger('change');
                        console.log('Non-sales rep: Customer reset to Walk-in Customer');
                    } else {
                        // Fallback: try to find by text
                        const walkingCustomer = customerSelect.find('option').filter(function() {
                            return $(this).text().toLowerCase().includes('walk-in');
                        });

                        if (walkingCustomer.length > 0) {
                            customerSelect.val(walkingCustomer.val()).trigger('change');
                            console.log('Non-sales rep: Customer reset to Walk-in Customer (by text)');
                        } else {
                            console.warn('Walk-in customer not found, resetting to empty');
                            customerSelect.val('').trigger('change');
                        }
                    }
                }
            }

            function resetForm() {
                // Reset editing mode
                isEditing = false;
                currentEditingSaleId = null; // Reset the editing sale ID

                resetToWalkingCustomer();
                const quantityInputs = document.querySelectorAll('.quantity-input');
                quantityInputs.forEach(input => {
                    input.value = 1;
                });

                const billingBodyRows = document.querySelectorAll('#billing-body tr');
                billingBodyRows.forEach(row => {
                    row.remove();
                });

                document.getElementById('amount-given').value = ''; // Reset the amount given field

                // Reset discount fields
                document.getElementById('global-discount').value = '';
                document.getElementById('discount-type').value = 'fixed';

                updateTotals();
            }

        });
    $(document).ready(function() {
        // Initialize DataTable
        $('#transactionTable').DataTable();

        // Fetch sales data on page load
        fetchSalesData();
    });

    let sales = [];

    // Function to fetch sales data from the server using AJAX
    function fetchSalesData() {
        $.ajax({
            url: '/sales',
            type: 'GET',
            dataType: 'json',
            data: {
                recent_transactions: 'true' // Add parameter to get all statuses for Recent Transactions
            },
            success: function(data) {
                if (Array.isArray(data)) {
                    sales = data;
                } else if (data.sales && Array.isArray(data.sales)) {
                    sales = data.sales;
                } else {
                    console.error('Unexpected data format:', data);
                }
                // Load the default tab data (e.g., 'final')
                loadTableData('final');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching sales data:', error);
            }
        });
    }

    function loadTableData(status) {
        const table = $('#transactionTable').DataTable();
        table.clear().draw(); // Clear existing data

        // Filter by status
        const filteredSales = sales.filter(sale => sale.status === status);

        if (filteredSales.length === 0) {
            table.row.add([
                '', 'No records found', '', '', '', '', ''
            ]).draw(false);
        } else {
            // Sort by id descending (latest ID first)
            const sortedSales = filteredSales.sort((a, b) => b.id - a.id);

            // Add each row in sorted order
            sortedSales.forEach((sale, index) => {
                let customerName = [
                    sale.customer?.prefix,
                    sale.customer?.first_name,
                    sale.customer?.last_name
                ].filter(Boolean).join(' ');

                table.row.add([
                    index + 1,
                    sale.invoice_no,
                    customerName || 'Walk-In Customer',
                    sale.sales_date,
                    sale.final_total,
                    `<button class='btn btn-outline-success btn-sm' onclick="printReceipt(${sale.id})">Print</button>
                 <button class='btn btn-outline-primary btn-sm' onclick="navigateToEdit(${sale.id})">Edit</button>`,
                    '' // Extra column if needed
                ]);
            });

            table.draw(); // Draw all rows at once for performance
        }
    }

    // Function to navigate to the edit page (attached to window for global access)
    window.navigateToEdit = function(saleId) {
        window.location.href = "/sales/edit/" + saleId;
    }

    // Function to print the receipt for the sale (attached to window for global access)
    window.printReceipt = function(saleId) {
        // Close any open modals before printing
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });

        // Add a small delay to ensure modal is fully closed
        setTimeout(() => {
            fetch(`/sales/print-recent-transaction/${saleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.invoice_html) {
                        // Check if mobile device
                        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
                        
                        if (isMobile) {
                            // For mobile: Open in new window/tab for better print support
                            const printWindow = window.open('', '_blank');
                            if (printWindow) {
                                printWindow.document.open();
                                printWindow.document.write(data.invoice_html);
                                printWindow.document.close();
                                
                                // Wait for content to load then trigger print
                                printWindow.onload = function() {
                                    setTimeout(() => {
                                        printWindow.print();
                                        // Don't auto-close on mobile - let user close manually
                                    }, 500);
                                };
                            } else {
                                toastr.error('Please allow pop-ups to print the receipt.');
                            }
                        } else {
                            // For desktop: Use hidden iframe method (no blank page)
                            const iframe = document.createElement('iframe');
                            iframe.style.position = 'absolute';
                            iframe.style.width = '0';
                            iframe.style.height = '0';
                            iframe.style.border = 'none';
                            iframe.style.left = '-9999px';
                            iframe.style.top = '-9999px';
                            iframe.style.visibility = 'hidden';
                            document.body.appendChild(iframe);

                            const iframeDoc = iframe.contentWindow.document;
                            iframeDoc.open();
                            iframeDoc.write(data.invoice_html);
                            iframeDoc.close();

                            // Wait for content to load
                            iframe.onload = function() {
                                setTimeout(() => {
                                    try {
                                        iframe.contentWindow.focus();
                                        iframe.contentWindow.print();
                                    } catch (e) {
                                        console.error('Print error:', e);
                                        toastr.error('Unable to print. Please try again.');
                                    }
                                    
                                    // Cleanup after printing or after timeout
                                    const cleanup = () => {
                                        if (iframe && document.body.contains(iframe)) {
                                            document.body.removeChild(iframe);
                                        }
                                    };
                                    
                                    // Try to cleanup after print dialog closes
                                    if (iframe.contentWindow.matchMedia) {
                                        const mediaQueryList = iframe.contentWindow.matchMedia('print');
                                        mediaQueryList.addListener(function(mql) {
                                            if (!mql.matches) {
                                                setTimeout(cleanup, 500);
                                            }
                                        });
                                    }
                                    
                                    // Fallback cleanup after 2 seconds
                                    setTimeout(cleanup, 2000);
                                }, 100);
                            };
                        }
                    } else {
                        toastr.error('Failed to fetch the receipt. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching the receipt:', error);
                    toastr.error('An error occurred while fetching the receipt. Please try again.');
                });
        }, 300); // Delay to ensure modal is closed
    }
});
</script>


{{-- For jQuery --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
<!-- Include Mousetrap library -->
{{-- <script src="{{ asset('assets/js/mousetrap.js') }}"></script> --}}
<script src="https://unpkg.com/hotkeys-js/dist/hotkeys.min.js"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        let currentRowIndex = 0;

        // Enable hotkeys inside input, textarea, and select fields
        hotkeys.filter = function(event) {
            return true; // Allow shortcuts in any element
        };

        function focusQuantityInput() {
            const quantityInputs = document.querySelectorAll('.quantity-input');
            if (quantityInputs.length > 0) {
                quantityInputs[currentRowIndex].focus();
                quantityInputs[currentRowIndex].select();
                currentRowIndex = (currentRowIndex + 1) % quantityInputs.length;
            }
        }

        // F2 - Focus next quantity input
        hotkeys('f2', function(event) {
            event.preventDefault();
            focusQuantityInput();
        });

        // F4 - Focus product search
        hotkeys('f4', function(event) {
            event.preventDefault();
            const productSearchInput = document.getElementById('productSearchInput');
            if (productSearchInput) {
                productSearchInput.focus();
                productSearchInput.select();
            }
        });

        // F5 - Refresh page
        hotkeys('f5', function(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to refresh the page?')) {
                location.reload();

            }
        });

        // F6 - Click cash button
        hotkeys('f6', function(event) {
            event.preventDefault();
            const cashBtn = document.querySelector('#cashButton');
            if (cashBtn) {
                cashBtn.click();
            }
        });

        // F7 - Focus amount given input
        hotkeys('f7', function(event) {
            event.preventDefault();
            const amountInput = document.querySelector('#amount-given');
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        });

        // F8 - Focus discount input
        hotkeys('f8', function(event) {
            event.preventDefault();
            const discountInput = document.querySelector('#global-discount');
            if (discountInput) {
                discountInput.focus();
                discountInput.select();
            }
        });

        // F9 - Open customer Select2 and focus search
        hotkeys('f9', function(event) {
            event.preventDefault();
            const customerSelect = $('#customer-id');
            if (customerSelect.length) {
                customerSelect.select2('open');
                setTimeout(() => {
                    $('.select2-search__field').focus();
                }, 100);
            }
        });

        // Initial focus
        focusQuantityInput();
    });
</script>


<!-- Include cleave.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cleave.js/1.6.0/cleave.min.js"></script>
{{-- For sound --}}
<audio class="successSound" src="{{ asset('assets/sounds/success.mp3') }}"></audio>
<audio class="errorSound" src="{{ asset('assets/sounds/error.mp3') }}"></audio>
<audio class="warningSound" src="{{ asset('assets/sounds/warning.mp3') }}"></audio>


<script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.js') }}"></script>
<script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
<script src="{{ asset('assets/plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.js') }}"></script>
<script src="{{ asset('assets/js/script.js') }}"></script>

<!-- jQuery Validation Plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>

<script>
    $(function() {
        $('.datetime').datetimepicker({
            format: 'hh:mm:ss a'
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll("input").forEach(function(input) {
            input.setAttribute("autocomplete", "off");
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('.selectBox').select2();

        $('.selectBox').on('select2:open', function() {
            // Use setTimeout to wait for DOM update
            setTimeout(() => {
                // Get all open Select2 dropdowns
                const allDropdowns = document.querySelectorAll('.select2-container--open');

                // Get the most recently opened dropdown (last one)
                const lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];

                if (lastOpenedDropdown) {
                    // Find the search input inside this dropdown
                    const searchInput = lastOpenedDropdown.querySelector(
                        '.select2-search__field');

                    if (searchInput) {
                        searchInput.focus(); // Focus the search input
                        searchInput.select(); // Optional: select any existing text
                    }
                }
            }, 10); // Very short delay to allow DOM render
        });
    });
</script>

{{-- Toaster Notifications --}}
<script>
    $(document).ready(function() {
        var successSound = document.querySelector('.successSound');
        var errorSound = document.querySelector('.errorSound');

        @if (Session::has('toastr-success'))
            toastr.success("{{ Session::get('toastr-success') }}");
            successSound.play();
        @endif

        @if (Session::has('toastr-error'))
            toastr.error("{{ Session::get('toastr-error') }}");
            errorSound.play();
        @endif

        @if (Session::has('toastr-warning'))
            toastr.warning("{{ Session::get('toastr-warning') }}");
        @endif

        @if (Session::has('toastr-info'))
            toastr.info("{{ Session::get('toastr-info') }}");
        @endif
    });
</script>

<script>
    // // Disable all console.log in production
    // if (typeof window !== 'undefined' && window.location.hostname !== 'localhost') {
    //     console.log = function() {};
    // }

    function preventDoubleClick(button, callback) {
        if (button.dataset.isProcessing === "true") return;
        button.dataset.isProcessing = "true";
        button.disabled = true;

        try {
            callback();
        } catch (error) {
            console.error("Error in button callback:", error);
            enableButton(button);
        }
    }

    function enableButton(button) {
        button.disabled = false;
        button.dataset.isProcessing = "false";

        // Restore button text based on button type
        const $button = $(button);
        if ($button.attr('id') === 'cashButton') {
            $button.html('<i class="fa fa-money"></i> Cash');
        } else if ($button.attr('id') === 'cardButton') {
            $button.html('<i class="fa fa-credit-card"></i> Card');
        } else if ($button.attr('id') === 'creditButton') {
            $button.html('<i class="fa fa-credit-card"></i> Credit');
        } else {
            // Generic restore - remove spinner and restore original text if available
            const originalText = $button.data('original-text') || $button.text().replace(/Processing\.\.\./g, '')
                .replace(/fa-spinner fa-spin/g, 'fa-money');
            $button.html(originalText);
        }
    }

    // Helper: Wrap AJAX calls with button protection
    function safeAjaxCall(button, options) {
        preventDoubleClick(button, () => {
            $.ajax(options)
                .done(function(response) {
                    if (options.done) options.done(response);
                })
                .fail(function(xhr, status, error) {
                    toastr.error('An error occurred: ' + xhr.responseText);
                    if (options.fail) options.fail(xhr, status, error);
                })
                .always(function() {
                    enableButton(button);
                    if (options.always) options.always();
                });
        });
    }
</script>

