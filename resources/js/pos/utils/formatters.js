/**
 * POS Utility Functions - Formatters
 * Handles all number and currency formatting operations
 */

/**
 * Format amount with thousand separators
 * @param {number|string} amount - The amount to format
 * @returns {string} Formatted amount
 */
export function formatAmountWithSeparators(amount) {
    if (amount === '' || amount === null || amount === undefined) {
        return '';
    }

    const num = parseFloat(amount);
    if (isNaN(num)) {
        return amount;
    }

    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Parse formatted amount back to number
 * @param {string} formattedAmount - The formatted amount string
 * @returns {number} Parsed number
 */
export function parseFormattedAmount(formattedAmount) {
    if (typeof formattedAmount === 'number') {
        return formattedAmount;
    }

    if (!formattedAmount || formattedAmount === '') {
        return 0;
    }

    // Remove all commas and parse
    const cleaned = String(formattedAmount).replace(/,/g, '');
    const parsed = parseFloat(cleaned);

    return isNaN(parsed) ? 0 : parsed;
}

/**
 * Format currency with symbol
 * @param {number} amount - Amount to format
 * @param {string} symbol - Currency symbol (default: '')
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, symbol = '') {
    const formatted = formatAmountWithSeparators(amount);
    return symbol ? `${symbol} ${formatted}` : formatted;
}

/**
 * Round to specified decimal places
 * @param {number} value - Value to round
 * @param {number} decimals - Number of decimal places
 * @returns {number} Rounded value
 */
export function roundToDecimals(value, decimals = 2) {
    const multiplier = Math.pow(10, decimals);
    return Math.round(value * multiplier) / multiplier;
}

/**
 * Format percentage value
 * @param {number} value - Percentage value
 * @param {number} decimals - Decimal places (default: 2)
 * @returns {string} Formatted percentage
 */
export function formatPercentage(value, decimals = 2) {
    return `${roundToDecimals(value, decimals)}%`;
}

/**
 * Calculate percentage of a value
 * @param {number} percentage - Percentage (0-100)
 * @param {number} total - Total value
 * @returns {number} Calculated percentage amount
 */
export function calculatePercentage(percentage, total) {
    return roundToDecimals((percentage * total) / 100);
}

/**
 * Format date to readable string
 * @param {Date|string} date - Date to format
 * @param {string} format - Format type ('short', 'long', 'iso')
 * @returns {string} Formatted date string
 */
export function formatDate(date, format = 'short') {
    const d = date instanceof Date ? date : new Date(date);

    if (isNaN(d.getTime())) {
        return '';
    }

    switch (format) {
        case 'iso':
            return d.toISOString();
        case 'long':
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        default: // 'short'
            return d.toLocaleDateString('en-US');
    }
}

/**
 * Format phone number
 * @param {string} phone - Phone number
 * @returns {string} Formatted phone
 */
export function formatPhone(phone) {
    if (!phone) return '';

    // Remove all non-numeric characters
    const cleaned = phone.replace(/\D/g, '');

    // Format based on length
    if (cleaned.length === 10) {
        return cleaned.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    }

    return phone;
}
