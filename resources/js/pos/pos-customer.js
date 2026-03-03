/**
 * POS CUSTOMER MODULE — Phase 5
 * Customer type resolution, pricing logic, price-history lookup,
 * billing row price updates.
 * Depends on: pos-utils.js (safeParseFloat, safePercentage, formatAmountWithSeparators),
 *             pos-cache.js  (getCachedCustomer, setCachedCustomer)
 * Must load AFTER pos-utils.js and pos-cache.js, BEFORE pos_ajax.blade.php.
 *
 * Functions extracted from pos_ajax.blade.php:
 *   getCurrentCustomer, fetchCustomerTypeAsync,
 *   getCustomerTypePrice, logPricingError,
 *   getProductDataById, getBatchDataById,
 *   updateBillingRowPrice
 *
 * NOTE: updateAllBillingRowsPricing stays in pos_ajax.blade.php for now —
 * it depends on isEditing and _editModeToastShown which are still closure-scoped
 * primitives there. Will be extracted in a later phase.
 *
 * Public API:
 *   window.Pos.Customer.getCurrentCustomer,
 *   window.Pos.Customer.fetchCustomerTypeAsync,
 *   window.Pos.Customer.getCustomerTypePrice,
 *   window.Pos.Customer.logPricingError,
 *   window.Pos.Customer.getProductDataById,
 *   window.Pos.Customer.getBatchDataById,
 *   window.Pos.Customer.updateBillingRowPrice
 */

// POS namespace for customer helpers
window.Pos = window.Pos || {};
window.Pos.Customer = window.Pos.Customer || {};

// Local aliases to utility helpers
const _posUtils = window.Pos.Utils || {};
const safeParseFloat = _posUtils.safeParseFloat;
const safePercentage = _posUtils.safePercentage;
const formatAmountWithSeparators = _posUtils.formatAmountWithSeparators;

// ---- Customer Resolution ----

/**
 * Get the current customer's type and details.
 * Tries DOM data-attributes first, falls back to dropdown text, then async fetch.
 */
function getCurrentCustomer() {
    const customerId = $('#customer-id').val();
    if (!customerId || customerId === '1') {
        return { id: 1, customer_type: 'retailer' };
    }

    // Check cache first (FAST)
    const cachedCustomer = getCachedCustomer(customerId);
    if (cachedCustomer) {
        return cachedCustomer;
    }

    const customerOption = $('#customer-id option:selected');

    // Try data attribute first (most reliable)
    let customerType = customerOption.attr('data-customer-type');

    if (customerType) {
    } else {
        const customerText = customerOption.text();

        customerType = 'retailer';

        if (customerText.toLowerCase().includes('- wholesaler')) {
            customerType = 'wholesaler';
        } else if (customerText.toLowerCase().includes('- retailer')) {
            customerType = 'retailer';
        } else {
            console.warn('Customer type not found, using retailer as fallback');
            customerType = 'retailer';
            fetchCustomerTypeAsync(customerId);
        }
    }

    const result = { id: parseInt(customerId), customer_type: customerType };
    setCachedCustomer(customerId, result);
    return result;
}

/**
 * Fetch customer type asynchronously in background (non-blocking).
 * Updates cache and re-prices billing rows when result arrives.
 */
function fetchCustomerTypeAsync(customerId) {

    $.ajax({
        url: window.PosConfig.routes.customerById + customerId,
        method: 'GET',
        async: true,
        success: function(response) {
            if (response && response.customer_type) {
                const customerData = {
                    id: parseInt(customerId),
                    customer_type: response.customer_type
                };

                setCachedCustomer(customerId, customerData);

                const currentCustomerId = $('#customer-id').val();
                if (currentCustomerId === customerId) {
                    updateAllBillingRowsPricing(response.customer_type);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Background fetch failed for customer type:', error);
        }
    });
}

// ---- Pricing Logic ----

/**
 * Determine the correct price based on customer type and batch pricing.
 * Returns { price, source, hasError }.
 */
function getCustomerTypePrice(batch, product, customerType) {

    let selectedPrice = 0;
    let priceSource   = '';

    if (customerType === 'wholesaler') {
        if (batch && batch.wholesale_price && parseFloat(batch.wholesale_price) > 0) {
            selectedPrice = parseFloat(batch.wholesale_price);
            priceSource = 'batch_wholesale_price';
        } else if (batch && batch.special_price && parseFloat(batch.special_price) > 0) {
            selectedPrice = parseFloat(batch.special_price);
            priceSource = 'batch_special_price';
        } else if (batch && batch.retail_price && parseFloat(batch.retail_price) > 0) {
            selectedPrice = parseFloat(batch.retail_price);
            priceSource = 'batch_retail_price';
        } else if (batch && batch.max_retail_price && parseFloat(batch.max_retail_price) > 0) {
            selectedPrice = parseFloat(batch.max_retail_price);
            priceSource = 'batch_max_retail_price';
        } else if (product.whole_sale_price && parseFloat(product.whole_sale_price) > 0) {
            selectedPrice = parseFloat(product.whole_sale_price);
            priceSource = 'product_wholesale_price';
        } else if (product.special_price && parseFloat(product.special_price) > 0) {
            selectedPrice = parseFloat(product.special_price);
            priceSource = 'product_special_price';
        } else if (product.retail_price && parseFloat(product.retail_price) > 0) {
            selectedPrice = parseFloat(product.retail_price);
            priceSource = 'product_retail_price';
        } else if (product.max_retail_price && parseFloat(product.max_retail_price) > 0) {
            selectedPrice = parseFloat(product.max_retail_price);
            priceSource = 'product_max_retail_price';
        }
    } else {
        // Retailer pricing (default)
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

    if (selectedPrice <= 0) {
        console.error('No valid price found for product:', product.product_name, 'customer type:', customerType);
        logPricingError(product, customerType, batch);
        return { price: 0, source: 'error', hasError: true };
    }

    return { price: selectedPrice, source: priceSource, hasError: false };
}

/**
 * Log pricing errors to backend for admin review.
 */
function logPricingError(product, customerType, batch) {
    const errorData = {
        product_id:    product.id,
        product_name:  product.product_name,
        customer_type: customerType,
        batch_id:      batch ? batch.id : null,
        batch_no:      batch ? batch.batch_no : null,
        timestamp:     new Date().toISOString(),
        location_id:   window.selectedLocationId
    };

    fetch(window.PosConfig.routes.logPricingError, {
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

// ---- Product / Batch Data Lookup ----

/**
 * Get product data by ID from window.allProducts / window.stockData,
 * with a DOM fallback reading data-product-pricing from billing rows.
 * NOTE: allProducts/stockData elements are stock entries with shape
 *       { product: {id, ...}, batches: [...], total_stock: ... }
 *       NOT raw product objects. Always search via .product.id.
 */
function getProductDataById(productId) {
    if (!productId) return null;

    let product = null;

    // allProducts elements: { product: {...}, batches: [...] } — search via .product.id
    if (window.allProducts && Array.isArray(window.allProducts)) {
        const stockEntry = window.allProducts.find(
            s => s && s.product && s.product.id && s.product.id == productId
        );
        if (stockEntry && stockEntry.product) {
            product = stockEntry.product;
        }
    }

    // Fallback: stockData (same shape as allProducts, kept in sync)
    if (!product && window.stockData && Array.isArray(window.stockData)) {
        const stockEntry = window.stockData.find(
            s => s && s.product && s.product.id && s.product.id == productId
        );
        if (stockEntry && stockEntry.product) {
            product = stockEntry.product;
        }
    }

    // DOM fallback: read data-product-pricing from billing row
    // This works even after allProducts/stockData is reset by a filter/category change.
    if (!product) {
        const row = document.querySelector(`#billing-body tr[data-product-id="${productId}"]`);
        if (row) {
            const pricingAttr = row.getAttribute('data-product-pricing');
            if (pricingAttr) {
                try {
                    product = JSON.parse(pricingAttr);
                } catch (e) { /* ignore */ }
            }
        }
    }

    return product;
}

/**
 * Get batch data by ID from window.allProducts or window.stockData,
 * with a DOM fallback reading data-batch-pricing from billing rows.
 */
function getBatchDataById(batchId) {
    if (!batchId) return null;

    for (const product of (window.allProducts || [])) {
        if (product.batches && Array.isArray(product.batches)) {
            const batch = product.batches.find(b => b.id == batchId);
            if (batch) return batch;
        }
    }

    for (const stockEntry of (window.stockData || [])) {
        if (stockEntry.batches && Array.isArray(stockEntry.batches)) {
            const batch = stockEntry.batches.find(b => b.id == batchId);
            if (batch) return batch;
        }
    }

    // DOM fallback: read data-batch-pricing from billing row matching this batch
    const row = document.querySelector(`#billing-body tr[data-batch-id="${batchId}"]`);
    if (row) {
        const batchAttr = row.getAttribute('data-batch-pricing');
        if (batchAttr) {
            try {
                return JSON.parse(batchAttr);
            } catch (e) { /* ignore */ }
        }
    }

    return null;
}

// ---- Billing Row Price Update ----

/**
 * Update price and recalculate discount in a single billing row.
 */
function updateBillingRowPrice(row, newPrice, priceSource) {
    const priceInput          = row.querySelector('.price-input.unit-price');
    const quantityInput       = row.querySelector('.quantity-input');
    const totalCell           = row.querySelector('.total-price');
    const fixedDiscountInput  = row.querySelector('.fixed_discount');
    const percentDiscountInput = row.querySelector('.percent_discount');

    // Get MRP from input data-attribute or from product data
    let mrp = 0;
    if (priceInput) {
        mrp = safeParseFloat(priceInput.getAttribute('data-max-retail-price'), 0);
    }
    if (mrp === 0) {
        const productId   = row.getAttribute('data-product-id');
        const productData = getProductDataById(productId);
        if (productData && productData.max_retail_price) {
            mrp = safeParseFloat(productData.max_retail_price, 0);
        }
    }

    if (priceInput) {
        priceInput.value = parseFloat(newPrice).toFixed(2);
        priceInput.setAttribute('data-price', newPrice);
    }

    if (mrp > 0 && !isNaN(mrp) && !isNaN(newPrice)) {
        const newFixedDiscount   = mrp - safeParseFloat(newPrice, 0);
        const newPercentDiscount = safePercentage(newFixedDiscount, mrp, 0);
        const validFixed         = safeParseFloat(newFixedDiscount, 0);
        const validPercent       = safeParseFloat(newPercentDiscount, 0);

        if (fixedDiscountInput)   fixedDiscountInput.value   = validFixed.toFixed(2);
        if (percentDiscountInput) percentDiscountInput.value = validPercent.toFixed(2);

    } else {
        if (fixedDiscountInput)   fixedDiscountInput.value   = '0.00';
        if (percentDiscountInput) percentDiscountInput.value = '0.00';
    }

    if (quantityInput && totalCell) {
        const quantity = parseFloat(quantityInput.value || 1);
        const newTotal = (newPrice * quantity).toFixed(2);
        totalCell.textContent = formatAmountWithSeparators(newTotal);
        totalCell.setAttribute('data-total', newTotal);
    }

    row.setAttribute('data-unit-price', newPrice);
    row.setAttribute('data-price-source', priceSource);
}

// ---- Expose all via namespace ----
window.Pos.Customer.getCurrentCustomer     = getCurrentCustomer;
window.Pos.Customer.fetchCustomerTypeAsync = fetchCustomerTypeAsync;
window.Pos.Customer.getCustomerTypePrice   = getCustomerTypePrice;
window.Pos.Customer.logPricingError        = logPricingError;
window.Pos.Customer.getProductDataById     = getProductDataById;
window.Pos.Customer.getBatchDataById       = getBatchDataById;
window.Pos.Customer.updateBillingRowPrice  = updateBillingRowPrice;
