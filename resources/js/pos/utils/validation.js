/**
 * POS Utility Functions - Validation
 * Validation functions for POS operations
 */

import { safeParseFloat, isEmpty } from './helpers.js';

/**
 * Validate quantity value
 * @param {number|string} quantity - Quantity to validate
 * @param {number} available - Available stock
 * @returns {Object} Validation result {valid: boolean, error: string}
 */
export function validateQuantity(quantity, available = null) {
    const qty = safeParseFloat(quantity);

    if (qty <= 0) {
        return {
            valid: false,
            error: 'Quantity must be greater than 0'
        };
    }

    if (available !== null && qty > available) {
        return {
            valid: false,
            error: `Only ${available} units available in stock`
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate price value
 * @param {number|string} price - Price to validate
 * @param {number} minPrice - Minimum allowed price
 * @param {number} maxPrice - Maximum allowed price
 * @returns {Object} Validation result
 */
export function validatePrice(price, minPrice = 0, maxPrice = null) {
    const p = safeParseFloat(price);

    if (p < 0) {
        return {
            valid: false,
            error: 'Price cannot be negative'
        };
    }

    if (p < minPrice) {
        return {
            valid: false,
            error: `Price cannot be less than minimum price: ${minPrice}`
        };
    }

    if (maxPrice !== null && p > maxPrice) {
        return {
            valid: false,
            error: `Price cannot exceed maximum price: ${maxPrice}`
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate discount value
 * @param {number|string} discount - Discount value
 * @param {string} type - Discount type ('fixed' or 'percentage')
 * @param {number} maxValue - Maximum allowed value (total for fixed, 100 for percentage)
 * @returns {Object} Validation result
 */
export function validateDiscount(discount, type = 'percentage', maxValue = null) {
    const d = safeParseFloat(discount);

    if (d < 0) {
        return {
            valid: false,
            error: 'Discount cannot be negative'
        };
    }

    if (type === 'percentage' && d > 100) {
        return {
            valid: false,
            error: 'Percentage discount cannot exceed 100%'
        };
    }

    if (type === 'fixed' && maxValue !== null && d > maxValue) {
        return {
            valid: false,
            error: `Discount cannot exceed total amount: ${maxValue}`
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate IMEI format
 * @param {string} imei - IMEI number
 * @returns {Object} Validation result
 */
export function validateIMEI(imei) {
    if (!imei || isEmpty(imei)) {
        return {
            valid: false,
            error: 'IMEI is required'
        };
    }

    // IMEI should be 15 digits
    const cleaned = imei.replace(/\D/g, '');

    if (cleaned.length !== 15) {
        return {
            valid: false,
            error: 'IMEI must be 15 digits'
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate payment amount
 * @param {number|string} amount - Payment amount
 * @param {number} totalDue - Total due amount
 * @returns {Object} Validation result
 */
export function validatePaymentAmount(amount, totalDue) {
    const amt = safeParseFloat(amount);

    if (amt <= 0) {
        return {
            valid: false,
            error: 'Payment amount must be greater than 0'
        };
    }

    if (amt > totalDue) {
        return {
            valid: false,
            error: 'Payment amount cannot exceed total due'
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate customer data
 * @param {Object} customer - Customer data
 * @returns {Object} Validation result
 */
export function validateCustomer(customer) {
    if (!customer || isEmpty(customer)) {
        return {
            valid: false,
            error: 'Customer is required'
        };
    }

    if (!customer.id) {
        return {
            valid: false,
            error: 'Invalid customer selected'
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate location
 * @param {string|number} locationId - Location ID
 * @returns {Object} Validation result
 */
export function validateLocation(locationId) {
    if (!locationId || isEmpty(locationId)) {
        return {
            valid: false,
            error: 'Location is required'
        };
    }

    return { valid: true, error: null };
}

/**
 * Validate billing table
 * @param {Array} items - Billing items
 * @returns {Object} Validation result
 */
export function validateBillingTable(items) {
    if (!items || items.length === 0) {
        return {
            valid: false,
            error: 'Please add at least one product to the bill'
        };
    }

    // Check each item
    for (let i = 0; i < items.length; i++) {
        const item = items[i];

        if (!item.product_id) {
            return {
                valid: false,
                error: `Row ${i + 1}: Invalid product`
            };
        }

        const qtyValidation = validateQuantity(item.quantity);
        if (!qtyValidation.valid) {
            return {
                valid: false,
                error: `Row ${i + 1}: ${qtyValidation.error}`
            };
        }

        const priceValidation = validatePrice(item.unit_price);
        if (!priceValidation.valid) {
            return {
                valid: false,
                error: `Row ${i + 1}: ${priceValidation.error}`
            };
        }
    }

    return { valid: true, error: null };
}

/**
 * Validate sale data before submission
 * @param {Object} saleData - Complete sale data
 * @returns {Object} Validation result with errors array
 */
export function validateSaleData(saleData) {
    const errors = [];

    // Validate location
    const locationValidation = validateLocation(saleData.location_id);
    if (!locationValidation.valid) {
        errors.push(locationValidation.error);
    }

    // Validate customer
    const customerValidation = validateCustomer(saleData.customer);
    if (!customerValidation.valid) {
        errors.push(customerValidation.error);
    }

    // Validate items
    const itemsValidation = validateBillingTable(saleData.items);
    if (!itemsValidation.valid) {
        errors.push(itemsValidation.error);
    }

    // Validate payment method
    if (!saleData.payment_method || isEmpty(saleData.payment_method)) {
        errors.push('Payment method is required');
    }

    // Validate total amount
    if (saleData.final_total <= 0) {
        errors.push('Total amount must be greater than 0');
    }

    return {
        valid: errors.length === 0,
        errors: errors
    };
}
