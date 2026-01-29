/**
 * POS Customer Manager
 * Handles all customer-related functionality including customer types, pricing, and filtering
 */

class POSCustomerManager {
    constructor(cache) {
        this.cache = cache;
        this.isFilteringCustomers = false;
        this.lastCustomerFilterCall = 0;
        this.customerFilterCooldown = 2000; // 2 seconds minimum between filter calls
        this.originalCustomerOptions = null;
        this.salesRepCustomersFiltered = false;
        this.salesRepCustomersLoaded = false;
    }

    /**
     * Get the current customer's type and details (OPTIMIZED VERSION)
     */
    getCurrentCustomer() {
        const customerId = $('#customer-id').val();
        if (!customerId || customerId === '1') {
            return {
                id: null,
                type: 'retail',
                name: 'Walk-In Customer'
            };
        }

        // Check cache first (FAST)
        const cachedCustomer = this.cache.getCachedCustomer(customerId);
        if (cachedCustomer) {
            return cachedCustomer;
        }

        // Get customer data from the select option
        const customerOption = $('#customer-id option:selected');

        // First try to get customer type from data attribute (most reliable)
        let customerType = customerOption.attr('data-customer-type');

        if (customerType) {
            console.log('Customer type found in data attribute:', customerType);
        } else {
            // Fallback: Extract from option text
            const optionText = customerOption.text();
            if (optionText.includes('[Wholesaler]')) {
                customerType = 'wholesaler';
            } else if (optionText.includes('[Retailer]')) {
                customerType = 'retailer';
            } else {
                customerType = 'retail'; // default
            }
            console.log('Customer type extracted from text:', customerType);
        }

        const result = {
            id: customerId,
            type: customerType,
            name: customerOption.text()
        };

        // Cache the result
        this.cache.setCachedCustomer(customerId, result);

        console.log('Final getCurrentCustomer result:', result);
        return result;
    }

    /**
     * Fetch customer type asynchronously in background (non-blocking)
     */
    fetchCustomerTypeAsync(customerId) {
        console.log('Fetching customer type in background for future calls...');

        $.ajax({
            url: `/customers/${customerId}/type`,
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    const customerData = {
                        id: customerId,
                        type: response.customer_type,
                        name: response.customer_name
                    };
                    this.cache.setCachedCustomer(customerId, customerData);
                    console.log('Customer type cached in background:', customerData);
                }
            },
            error: (xhr, status, error) => {
                console.error('Background fetch customer type failed:', error);
            }
        });
    }

    /**
     * Determine the correct price based on customer type and batch pricing
     */
    getCustomerTypePrice(batch, product, customerType) {
        console.log('Determining price for customer type:', customerType, 'Batch:', batch, 'Product:', product);

        let selectedPrice = 0;
        let priceSource = '';

        if (customerType === 'wholesaler') {
            // Wholesalers get batch wholesale price first, then product wholesale, then retail
            if (batch && batch.wholesale_price && parseFloat(batch.wholesale_price) > 0) {
                selectedPrice = parseFloat(batch.wholesale_price);
                priceSource = 'Batch Wholesale Price';
            } else if (product.whole_sale_price && parseFloat(product.whole_sale_price) > 0) {
                selectedPrice = parseFloat(product.whole_sale_price);
                priceSource = 'Product Wholesale Price';
            } else if (batch && batch.retail_price && parseFloat(batch.retail_price) > 0) {
                selectedPrice = parseFloat(batch.retail_price);
                priceSource = 'Batch Retail Price (Fallback)';
            } else if (product.retail_price && parseFloat(product.retail_price) > 0) {
                selectedPrice = parseFloat(product.retail_price);
                priceSource = 'Product Retail Price (Fallback)';
            }
        } else {
            // Retailers get batch retail price first, then product retail
            if (batch && batch.retail_price && parseFloat(batch.retail_price) > 0) {
                selectedPrice = parseFloat(batch.retail_price);
                priceSource = 'Batch Retail Price';
            } else if (product.retail_price && parseFloat(product.retail_price) > 0) {
                selectedPrice = parseFloat(product.retail_price);
                priceSource = 'Product Retail Price';
            }
        }

        console.log('Selected price:', selectedPrice, 'from:', priceSource);

        // Validate price is not zero
        if (selectedPrice <= 0) {
            console.error('Price validation failed - price is zero!', {
                product: product.product_name,
                customerType,
                batch
            });

            // Log error for admin review
            this.logPricingError(product, customerType, batch);

            // Set to MRP as final fallback
            selectedPrice = parseFloat(product.mrp) || 0;
            priceSource = 'MRP (Emergency Fallback)';
        }

        return {
            price: selectedPrice,
            source: priceSource,
            customerType: customerType
        };
    }

    /**
     * Log pricing errors for admin review
     */
    logPricingError(product, customerType, batch) {
        const errorData = {
            product_id: product.id,
            product_name: product.product_name,
            customer_type: customerType,
            batch_id: batch ? batch.id : null,
            timestamp: new Date().toISOString(),
            error: 'Zero price detected'
        };

        // Send error to backend for logging
        fetch('/pos/log-pricing-error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
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
    updateAllBillingRowsPricing(newCustomerType) {
        // Skip price updates during edit mode to preserve original sale data
        if (window.isEditing) {
            console.log('Skipping price update - in edit mode');
            return;
        }

        const billingBody = document.getElementById('billing-body');
        const existingRows = billingBody ? billingBody.querySelectorAll('tr') : [];

        if (existingRows.length === 0) {
            console.log('No billing rows to update');
            return;
        }

        console.log(`Updating ${existingRows.length} billing rows for customer type: ${newCustomerType}`);

        existingRows.forEach((row) => {
            const productId = row.querySelector('.product-id')?.textContent;
            const batchId = row.querySelector('.batch-id')?.textContent;

            if (!productId) return;

            // Get product and batch data
            const product = this.getProductDataById(productId);
            const batch = batchId && batchId !== 'all' ? this.getBatchDataById(batchId) : null;

            if (!product) {
                console.error('Product not found for ID:', productId);
                return;
            }

            // Get new price based on customer type
            const priceInfo = this.getCustomerTypePrice(batch, product, newCustomerType);

            // Update the row with new price
            this.updateBillingRowPrice(row, priceInfo.price, priceInfo.source);
        });

        // Recalculate total after all price updates
        if (typeof updateTotals === 'function') {
            updateTotals();
        }

        console.log('All billing rows updated successfully');
    }

    /**
     * Update the price in a specific billing row with dynamic discount calculation
     */
    updateBillingRowPrice(row, newPrice, priceSource) {
        const priceInput = row.querySelector('.price-input.unit-price');
        const quantityInput = row.querySelector('.quantity-input');
        const totalCell = row.querySelector('.total-price');

        // Find discount fields
        const fixedDiscountInput = row.querySelector('.fixed_discount');
        const percentDiscountInput = row.querySelector('.percent_discount');

        // Get MRP from price input data attribute or calculate it
        let mrp = 0;
        if (priceInput) {
            mrp = parseFloat(priceInput.getAttribute('data-mrp')) || 0;
        }

        // If MRP not found, try to get it from product data
        if (mrp === 0) {
            const productId = row.querySelector('.product-id')?.textContent;
            const product = this.getProductDataById(productId);
            if (product && product.mrp) {
                mrp = parseFloat(product.mrp);
            }
        }

        if (priceInput) {
            priceInput.value = newPrice.toFixed(2);
            priceInput.setAttribute('data-price-source', priceSource);
        }

        // Calculate new discount values based on MRP and new price
        if (mrp > 0 && !isNaN(mrp) && !isNaN(newPrice)) {
            const discountAmount = mrp - newPrice;
            const discountPercent = (discountAmount / mrp) * 100;

            // Update discount fields
            if (fixedDiscountInput) {
                fixedDiscountInput.value = discountAmount.toFixed(2);
            }

            if (percentDiscountInput) {
                percentDiscountInput.value = discountPercent.toFixed(2);
            }
        } else {
            // If MRP is not available, clear discounts
            if (fixedDiscountInput) {
                fixedDiscountInput.value = '0.00';
            }
            if (percentDiscountInput) {
                percentDiscountInput.value = '0.00';
            }
        }

        // Update total if quantity cell exists
        if (quantityInput && totalCell) {
            const quantity = parseFloat(quantityInput.value) || 1;
            const total = quantity * newPrice;
            totalCell.textContent = this.formatAmountWithSeparators(total.toFixed(2));
            totalCell.setAttribute('data-total', total.toFixed(2));
        }

        // Update row's price data attributes
        row.setAttribute('data-unit-price', newPrice);
        row.setAttribute('data-price-source', priceSource);
    }

    /**
     * Get product data by ID from available sources
     */
    getProductDataById(productId) {
        if (!productId) return null;

        // Try to find product in allProducts array first
        let product = null;

        if (window.allProducts && Array.isArray(window.allProducts)) {
            const stockEntry = window.allProducts.find(s => String(s.product.id) === String(productId));
            if (stockEntry) product = stockEntry.product;
        }

        if (!product && window.stockData && Array.isArray(window.stockData)) {
            const stockEntry = window.stockData.find(s => String(s.product.id) === String(productId));
            if (stockEntry) product = stockEntry.product;
        }

        return product;
    }

    /**
     * Get batch data by ID from available sources
     */
    getBatchDataById(batchId) {
        if (!batchId) return null;

        // Search through all products' batches
        if (window.allProducts) {
            for (const product of window.allProducts) {
                if (product.batches && Array.isArray(product.batches)) {
                    const batch = product.batches.find(b => String(b.id) === String(batchId));
                    if (batch) return batch;
                }
            }
        }

        // Search through stockData
        if (window.stockData) {
            for (const stockEntry of window.stockData) {
                if (stockEntry.batches && Array.isArray(stockEntry.batches)) {
                    const batch = stockEntry.batches.find(b => String(b.id) === String(batchId));
                    if (batch) return batch;
                }
            }
        }

        return null;
    }

    /**
     * Get cached customer previous price
     */
    async getCustomerPreviousPrice(customerId, productId) {
        // Check cache first
        const cachedPrice = this.cache.getCachedCustomerPrice(customerId, productId);
        if (cachedPrice !== undefined) {
            return cachedPrice;
        }

        // Fetch from server
        try {
            const response = await fetch(`/pos/customer-previous-price/${customerId}/${productId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                const price = data.previous_price || null;

                // Cache the result
                this.cache.setCachedCustomerPrice(customerId, productId, price);

                return price;
            }
        } catch (error) {
            console.error('Error fetching customer previous price:', error);
        }

        return null;
    }

    /**
     * Format amount with thousand separators
     */
    formatAmountWithSeparators(amount) {
        return parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /**
     * Filter customers by route (for sales rep)
     */
    async filterCustomersByRoute(selection) {
        if (this.isFilteringCustomers) {
            console.log('Already filtering customers, skipping...');
            return;
        }

        // Prevent filtering while customer data is still being loaded
        if (window.customerDataLoading) {
            console.log('Customer data still loading, skipping filter...');
            return;
        }

        // Check if already loaded for this session
        if (this.salesRepCustomersLoaded) {
            console.log('Customers already loaded for this session');
            return;
        }

        if (!selection || !selection.route) {
            console.log('No route selection provided');
            return;
        }

        this.isFilteringCustomers = true;
        console.log('Filtering customers for route:', selection.route.name);

        if (!selection.route.cities || selection.route.cities.length === 0) {
            console.warn('No cities assigned to this route');
            this.isFilteringCustomers = false;
            return;
        }

        const routeCityIds = selection.route.cities.map(city => city.id);
        console.log('Route city IDs:', routeCityIds);

        try {
            const response = await fetch('/customers/filter-by-cities', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ city_ids: routeCityIds })
            });

            const data = await response.json();

            if (data.success) {
                this.populateFilteredCustomers(data.customers, selection.route.name);
                this.salesRepCustomersFiltered = true;
                this.salesRepCustomersLoaded = true;
            }
        } catch (error) {
            console.error('Error filtering customers:', error);
        } finally {
            this.isFilteringCustomers = false;
        }
    }

    /**
     * Populate filtered customers
     */
    populateFilteredCustomers(customers, routeName = '') {
        const customerSelect = $('#customer-id');

        if (!customers || !Array.isArray(customers)) {
            console.error('Invalid customers data');
            return;
        }

        console.log(`Populating ${customers.length} filtered customers for route: ${routeName}`);

        // Store original options if not already stored
        if (!this.originalCustomerOptions) {
            this.originalCustomerOptions = customerSelect.html();
        }

        // Clear existing options
        customerSelect.empty();

        // Add "Please select" option first
        customerSelect.append('<option value="">Please Select</option>');

        // Sort customers alphabetically
        customers.sort((a, b) => {
            const nameA = (a.name || '').toLowerCase();
            const nameB = (b.name || '').toLowerCase();
            return nameA.localeCompare(nameB);
        });

        // Separate customers with and without cities
        const customersWithCity = customers.filter(c => c.city_name && c.city_name !== 'No City');
        const customersWithoutCity = customers.filter(c => !c.city_name || c.city_name === 'No City');

        // Add customers with cities first
        customersWithCity.forEach(customer => {
            const option = $('<option>', {
                value: customer.id,
                text: `${customer.name} (${customer.city_name})`,
                'data-customer-type': customer.customer_type || 'retail'
            });
            customerSelect.append(option);
        });

        // Add separator if needed
        if (customersWithoutCity.length > 0 && customersWithCity.length > 0) {
            customerSelect.append('<option disabled>--- Customers without City ---</option>');
        }

        // Add customers without cities
        customersWithoutCity.forEach(customer => {
            const option = $('<option>', {
                value: customer.id,
                text: customer.name,
                'data-customer-type': customer.customer_type || 'retail'
            });
            customerSelect.append(option);
        });

        // Refresh Select2
        customerSelect.trigger('change');

        // Show info message
        if (typeof toastr !== 'undefined' && routeName) {
            const message = `${customers.length} customers loaded for route: ${routeName}`;
            toastr.info(message, 'Customers Filtered');
        }
    }

    /**
     * Restore original customers
     */
    restoreOriginalCustomers() {
        if (this.originalCustomerOptions) {
            const customerSelect = $('#customer-id');
            customerSelect.html(this.originalCustomerOptions);
            customerSelect.trigger('change');
            this.salesRepCustomersFiltered = false;
            console.log('Original customers restored');
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = POSCustomerManager;
} else {
    window.POSCustomerManager = POSCustomerManager;
}
