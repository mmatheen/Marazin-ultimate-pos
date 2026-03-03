/**
 * POS UTILITY MODULE — Phase 2
 * Pure stateless helpers. Zero dependencies on DOM, AJAX, or other POS modules.
 * Safe to load first, before any other POS script.
 *
 * Functions extracted from pos_ajax.blade.php:
 *   debounce, isValidationToastActive, showValidationToast,
 *   safeParseFloat, safePercentage,
 *   formatAmountWithSeparators, parseFormattedAmount, formatCurrency
 */

// ---- Debounce ----
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ---- Validation Toast (throttled — only one toast at a time) ----
let isValidationToastActive = false;

function showValidationToast(message, title = 'Stock Limit', type = 'warning') {
    if (isValidationToastActive) {
        return;
    }

    isValidationToastActive = true;

    const toastOptions = {
        onHidden: function() {
            isValidationToastActive = false;
        }
    };

    if (type === 'warning') {
        toastr.warning(message, title, toastOptions);
    } else if (type === 'error') {
        toastr.error(message, title, toastOptions);
    } else {
        toastr.info(message, title, toastOptions);
    }
}

// ---- Safe Number Helpers ----

/**
 * Safely parse numeric values to prevent Infinity/-Infinity errors
 */
function safeParseFloat(value, defaultValue = 0) {
    const parsed = parseFloat(value);
    return (isFinite(parsed) && !isNaN(parsed)) ? parsed : defaultValue;
}

/**
 * Safely calculate percentage to prevent division by zero
 */
function safePercentage(numerator, denominator, defaultValue = 0) {
    if (!denominator || denominator === 0 || !isFinite(denominator)) {
        return defaultValue;
    }
    const result = (numerator / denominator) * 100;
    return isFinite(result) ? result : defaultValue;
}

// ---- Formatting ----

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

function formatCurrency(amount) {
    return parseFloat(amount || 0).toFixed(2);
}

// ---- Expose all utilities as globals (required for jQuery-based POS) ----
window.debounce                  = debounce;
window.showValidationToast       = showValidationToast;
window.safeParseFloat            = safeParseFloat;
window.safePercentage            = safePercentage;
window.formatAmountWithSeparators = formatAmountWithSeparators;
window.parseFormattedAmount      = parseFormattedAmount;
window.formatCurrency            = formatCurrency;

// isValidationToastActive is shared state — expose it via a getter/setter
// so pos_ajax.blade.php inline code can still read/write it
Object.defineProperty(window, 'isValidationToastActive', {
    get: () => isValidationToastActive,
    set: (v) => { isValidationToastActive = v; },
    configurable: true,
});
